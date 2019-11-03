<?php


namespace WebRover\Framework\Kernel\EventListener;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use WebRover\Framework\Kernel\Event\GetResponseEvent;
use WebRover\Framework\Kernel\KernelEvents;

class RequestListener implements EventSubscriberInterface
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (in_array(strtoupper($request->getMethod()), ['POST', 'PUT', 'PATCH'])) {
            $content = json_decode($request->getContent(), true);

            if (is_array($content)) {
                $post = $request->request->all();
                $post = array_merge($content, $post);
                $request->request->replace($post);
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1024]
        ];
    }
}