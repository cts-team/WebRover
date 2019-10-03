<?php


namespace WebRover\Framework\Kernel\EventListener;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use WebRover\Framework\Kernel\Event\GetResponseForExceptionEvent;
use WebRover\Framework\Kernel\KernelEvents;
use Whoops\RunInterface;

/**
 * Class ExceptionListener
 * @package WebRover\Framework\Kernel\EventListener
 */
class ExceptionListener implements EventSubscriberInterface
{
    private $whoops;

    public function __construct(RunInterface $whoops)
    {
        $this->whoops = $whoops;
    }

    public function onException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        $response = $this->whoops->handleException($exception);

        $event->setResponse(new Response($response));
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onException']
        ];
    }
}
