<?php


namespace WebRover\Framework\Kernel\EventListener;


use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RequestContextAwareInterface;
use WebRover\Framework\Kernel\Event\FinishRequestEvent;
use WebRover\Framework\Kernel\Event\GetResponseEvent;
use WebRover\Framework\Kernel\Event\GetResponseForExceptionEvent;
use WebRover\Framework\Kernel\Exception\BadRequestHttpException;
use WebRover\Framework\Kernel\Exception\MethodNotAllowedHttpException;
use WebRover\Framework\Kernel\Exception\NotFoundHttpException;
use WebRover\Framework\Kernel\KernelEvents;

/**
 * Initializes the context from the request and sets request attributes based on a matching route.
 *
 * Class RouterListener
 * @package WebRover\Framework\Kernel\EventListener
 */
class RouterListener implements EventSubscriberInterface
{
    private $matcher;
    private $context;
    private $logger;
    private $requestStack;
    private $debug;

    /**
     * RouterListener constructor.
     * @param UrlMatcherInterface|RequestMatcherInterface $matcher The Url or Request matcher
     * @param RequestStack $requestStack
     * @param RequestContext|null $context The RequestContext (can be null when $matcher implements RequestContextAwareInterface)
     * @param LoggerInterface|null $logger The logger
     * @param bool $debug
     * @throws \InvalidArgumentException
     */
    public function __construct($matcher, RequestStack $requestStack, RequestContext $context = null, LoggerInterface $logger = null, $debug = true)
    {
        if (!$matcher instanceof UrlMatcherInterface && !$matcher instanceof RequestMatcherInterface) {
            throw new \InvalidArgumentException('Matcher must either implement UrlMatcherInterface or RequestMatcherInterface.');
        }

        if (null === $context && !$matcher instanceof RequestContextAwareInterface) {
            throw new \InvalidArgumentException('You must either pass a RequestContext or the matcher must implement RequestContextAwareInterface.');
        }

        $this->matcher = $matcher;
        $this->context = $context ?: $matcher->getContext();
        $this->requestStack = $requestStack;
        $this->logger = $logger;
        $this->debug = $debug;
    }

    private function setCurrentRequest(Request $request = null)
    {
        if (null !== $request) {
            try {
                $this->context->fromRequest($request);
            } catch (\UnexpectedValueException $e) {
                throw new BadRequestHttpException($e->getMessage(), $e, $e->getCode());
            }
        }
    }

    /**
     * After a sub-request is done, we need to reset the routing context to the parent request so that the URL generator
     * operates on the correct context again.
     */
    public function onKernelFinishRequest(FinishRequestEvent $event)
    {
        $this->setCurrentRequest($this->requestStack->getParentRequest());
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $this->setCurrentRequest($request);

        if ($request->attributes->has('_controller')) {
            // routing is already done
            return;
        }

        // add attributes based on the request (routing)
        try {
            // matching a request is more powerful than matching a URL path + context, so try that first
            if ($this->matcher instanceof RequestMatcherInterface) {
                $parameters = $this->matcher->matchRequest($request);
            } else {
                $parameters = $this->matcher->match($request->getPathInfo());
            }

            if (null !== $this->logger) {
                $this->logger->info('Matched route "{route}".', [
                    'route' => isset($parameters['_route']) ? $parameters['_route'] : 'n/a',
                    'route_parameters' => $parameters,
                    'request_uri' => $request->getUri(),
                    'method' => $request->getMethod(),
                ]);
            }

            $request->attributes->add($parameters);

            unset($parameters['_route'], $parameters['_controller']);
            $request->attributes->set('_route_params', $parameters);
        } catch (ResourceNotFoundException $e) {
            $message = sprintf('No route found for "%s %s"', $request->getMethod(), $request->getPathInfo());

            if ($referer = $request->headers->get('referer')) {
                $message .= sprintf(' (from "%s")', $referer);
            }

            throw new NotFoundHttpException($message, $e);
        } catch (MethodNotAllowedException $e) {
            $message = sprintf('No route found for "%s %s": Method Not Allowed (Allow: %s)', $request->getMethod(), $request->getPathInfo(), implode(', ', $e->getAllowedMethods()));

            throw new MethodNotAllowedHttpException($e->getAllowedMethods(), $message, $e);
        }
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$this->debug || !($e = $event->getException()) instanceof NotFoundHttpException) {
            return;
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 32]],
            KernelEvents::FINISH_REQUEST => [['onKernelFinishRequest', 0]],
            KernelEvents::EXCEPTION => ['onKernelException', -64],
        ];
    }
}