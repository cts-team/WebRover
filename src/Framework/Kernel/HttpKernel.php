<?php


namespace WebRover\Framework\Kernel;


use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use WebRover\Framework\Foundation\Exception\RequestExceptionInterface;
use WebRover\Framework\Foundation\JsonResponse;
use WebRover\Framework\Foundation\Request;
use WebRover\Framework\Foundation\RequestStack;
use WebRover\Framework\Foundation\Response;
use WebRover\Framework\Kernel\Controller\ArgumentResolverInterface;
use WebRover\Framework\Kernel\Controller\ControllerResolverInterface;
use WebRover\Framework\Kernel\Event\FilterControllerArgumentsEvent;
use WebRover\Framework\Kernel\Event\FilterControllerEvent;
use WebRover\Framework\Kernel\Event\FilterResponseEvent;
use WebRover\Framework\Kernel\Event\FinishRequestEvent;
use WebRover\Framework\Kernel\Event\GetResponseEvent;
use WebRover\Framework\Kernel\Event\GetResponseForControllerResultEvent;
use WebRover\Framework\Kernel\Event\GetResponseForExceptionEvent;
use WebRover\Framework\Kernel\Event\PostResponseEvent;
use WebRover\Framework\Kernel\Exception\BadRequestHttpException;
use WebRover\Framework\Kernel\Exception\HttpExceptionInterface;
use WebRover\Framework\Kernel\Exception\NotFoundHttpException;

/**
 * HttpKernel notifies events to convert a Request object to a Response one.
 *
 * Class HttpKernel
 * @package WebRover\Framework\Kernel
 */
class HttpKernel implements HttpKernelInterface, TerminableInterface
{
    protected $dispatcher;
    protected $resolver;
    protected $requestStack;
    private $argumentResolver;

    public function __construct(EventDispatcherInterface $dispatcher, ControllerResolverInterface $resolver, ArgumentResolverInterface $argumentResolver, RequestStack $requestStack = null)
    {
        $this->dispatcher = $dispatcher;
        $this->resolver = $resolver;
        $this->argumentResolver = $argumentResolver;
        $this->requestStack = $requestStack ?: new RequestStack();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $request->headers->set('X-Php-Ob-Level', ob_get_level());

        try {
            return $this->handleRaw($request, $type);
        } catch (\Exception $e) {
            if ($e instanceof RequestExceptionInterface) {
                $e = new BadRequestHttpException($e->getMessage(), $e);
            }
            if (false === $catch) {
                $this->finishRequest($request, $type);

                throw $e;
            }

            return $this->handleException($e, $request, $type);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(Request $request, Response $response)
    {
        $this->dispatcher->dispatch(KernelEvents::TERMINATE, new PostResponseEvent($this, $request, $response));
    }

    /**
     * @internal
     */
    public function terminateWithException(\Exception $exception, Request $request = null)
    {
        if (!$request = $request ?: $this->requestStack->getMasterRequest()) {
            throw $exception;
        }

        $response = $this->handleException($exception, $request, self::MASTER_REQUEST);

        $response->sendHeaders();
        $response->sendContent();

        $this->terminate($request, $response);
    }

    /**
     * Handles a request to convert it to a response.
     *
     * Exceptions are not caught.
     *
     * @param Request $request A Request instance
     * @param int $type The type of the request (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     *
     * @return Response A Response instance
     *
     * @throws \LogicException       If one of the listener does not behave as expected
     * @throws NotFoundHttpException When controller cannot be found
     */
    private function handleRaw(Request $request, $type = self::MASTER_REQUEST)
    {

        $this->requestStack->push($request);
        // request
        $event = new GetResponseEvent($this, $request, $type);
        $this->dispatcher->dispatch(KernelEvents::REQUEST, $event);

        if ($event->hasResponse()) {
            return $this->filterResponse($event->getResponse(), $request, $type);
        }

        // load controller
        if (false === $controller = $this->resolver->getController($request)) {
            throw new NotFoundHttpException(sprintf('Unable to find the controller for path "%s". The route is wrongly configured.', $request->getPathInfo()));
        }

        $event = new FilterControllerEvent($this, $controller, $request, $type);
        $this->dispatcher->dispatch(KernelEvents::CONTROLLER, $event);
        $controller = $event->getController();

        // controller arguments
        $arguments = $this->argumentResolver->getArguments($request, $controller);

        $event = new FilterControllerArgumentsEvent($this, $controller, $arguments, $request, $type);
        $this->dispatcher->dispatch(KernelEvents::CONTROLLER_ARGUMENTS, $event);
        $controller = $event->getController();
        $arguments = $event->getArguments();

        // call controller
        $response = \call_user_func_array($controller, $arguments);

        // view
        if (!$response instanceof Response) {
            $event = new GetResponseForControllerResultEvent($this, $request, $type, $response);
            $this->dispatcher->dispatch(KernelEvents::VIEW, $event);

            if ($event->hasResponse()) {
                $response = $event->getResponse();
            }

            if (!$response instanceof Response) {

                if (is_null($response)) {
                    $response = new Response();
                } else {
                    if (is_array($response)) {
                        $response = new JsonResponse($response);
                    } elseif (is_string($response) || is_numeric($response) || is_callable([$response, '__toString'])) {
                        $response = new Response((string)$response);
                    } else {
                        $msg = sprintf('The controller must return a response (%s given).', $this->varToString($response));

                        // the user may have forgotten to return something
                        if (null === $response) {
                            $msg .= ' Did you forget to add a return statement somewhere in your controller?';
                        }
                        throw new \LogicException($msg);
                    }
                }
            }
        }

        return $this->filterResponse($response, $request, $type);
    }

    /**
     * Filters a response object.
     *
     * @param Response $response A Response instance
     * @param Request $request An error message in case the response is not a Response object
     * @param int $type The type of the request (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     *
     * @return Response The filtered Response instance
     *
     * @throws \RuntimeException if the passed object is not a Response instance
     */
    private function filterResponse(Response $response, Request $request, $type)
    {
        $event = new FilterResponseEvent($this, $request, $type, $response);

        $this->dispatcher->dispatch(KernelEvents::RESPONSE, $event);

        $this->finishRequest($request, $type);

        return $event->getResponse();
    }

    /**
     * Publishes the finish request event, then pop the request from the stack.
     *
     * Note that the order of the operations is important here, otherwise
     * operations such as {@link RequestStack::getParentRequest()} can lead to
     * weird results.
     *
     * @param int $type
     */
    private function finishRequest(Request $request, $type)
    {
        $this->dispatcher->dispatch(KernelEvents::FINISH_REQUEST, new FinishRequestEvent($this, $request, $type));
        $this->requestStack->pop();
    }

    /**
     * Handles an exception by trying to convert it to a Response.
     *
     * @param \Exception $e An \Exception instance
     * @param Request $request A Request instance
     * @param int $type The type of the request
     *
     * @return Response A Response instance
     *
     * @throws \Exception
     */
    private function handleException(\Exception $e, $request, $type)
    {
        $event = new GetResponseForExceptionEvent($this, $request, $type, $e);
        $this->dispatcher->dispatch(KernelEvents::EXCEPTION, $event);

        // a listener might have replaced the exception
        $e = $event->getException();

        if (!$event->hasResponse()) {
            $this->finishRequest($request, $type);

            throw $e;
        }

        $response = $event->getResponse();

        // the developer asked for a specific status code
        if ($response->headers->has('X-Status-Code')) {
            @trigger_error(sprintf('Using the X-Status-Code header is deprecated since Symfony 3.3 and will be removed in 4.0. Use %s::allowCustomResponseCode() instead.', GetResponseForExceptionEvent::class), E_USER_DEPRECATED);

            $response->setStatusCode($response->headers->get('X-Status-Code'));

            $response->headers->remove('X-Status-Code');
        } elseif (!$event->isAllowingCustomResponseCode() && !$response->isClientError() && !$response->isServerError() && !$response->isRedirect()) {
            // ensure that we actually have an error response
            if ($e instanceof HttpExceptionInterface) {
                // keep the HTTP status code and headers
                $response->setStatusCode($e->getStatusCode());
                $response->headers->add($e->getHeaders());
            } else {
                $response->setStatusCode(500);
            }
        }

        try {
            return $this->filterResponse($response, $request, $type);
        } catch (\Exception $e) {
            return $response;
        }
    }

    /**
     * Returns a human-readable string for the specified variable.
     */
    private function varToString($var)
    {
        if (\is_object($var)) {
            return sprintf('Object(%s)', \get_class($var));
        }

        if (\is_array($var)) {
            $a = [];
            foreach ($var as $k => $v) {
                $a[] = sprintf('%s => %s', $k, $this->varToString($v));
            }

            return sprintf('Array(%s)', implode(', ', $a));
        }

        if (\is_resource($var)) {
            return sprintf('Resource(%s)', get_resource_type($var));
        }

        if (null === $var) {
            return 'null';
        }

        if (false === $var) {
            return 'false';
        }

        if (true === $var) {
            return 'true';
        }

        return (string)$var;
    }
}