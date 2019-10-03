<?php


namespace WebRover\Framework\Kernel\EventListener;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use WebRover\Framework\Kernel\Event\GetResponseForExceptionEvent;
use WebRover\Framework\Kernel\KernelEvents;

class ExceptionListener implements EventSubscriberInterface
{
    public function onException(GetResponseForExceptionEvent $event)
    {
        $whoops = app()->make('whoops');

        $exception = $event->getException();

        $response = $whoops->handleException($exception);

        $event->setResponse(new Response($response));
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onException']
        ];
    }
}