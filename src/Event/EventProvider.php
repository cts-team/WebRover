<?php


namespace WebRover\Framework\Event;


use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use WebRover\Framework\Container\ServiceProvider;
use WebRover\Framework\Kernel\Application;

/**
 * Class EventProvider
 * @package WebRover\Framework\Event
 */
class EventProvider extends ServiceProvider
{
    protected $listen = [];

    protected $subscribe = [];

    public function register()
    {
        if (!$this->app->has('event')) {
            $this->app->singleton('event', function (Application $app) {
                return new EventDispatcher();
            });
        }

        $this->app->extend('event', function (EventDispatcher $dispatcher, Application $app) {
            foreach ($this->listen as $event => $listeners) {
                $listeners = array_reverse($listeners);

                foreach ($listeners as $k => $listener) {
                    if ($app->offsetExists($listener)) {
                        $listener = $app->offsetGet($listener);
                    } else {
                        $listener = new $listener;
                    }

                    if (!method_exists($listener, 'handle')) continue;

                    $dispatcher->addListener($event, [$listener, 'handle'], $k);
                }
            }

            foreach ($this->subscribe as $subscriber) {
                if ($app->offsetExists($subscriber)) {
                    $subscriber = $app->make($subscriber);
                } else {
                    $subscriber = new $subscriber;
                }

                if (!($subscriber instanceof EventSubscriberInterface)) {
                    throw new \InvalidArgumentException('event subscriber ' . $subscriber . ' must is an instance of EventSubscriberInterface');
                }

                $dispatcher->addSubscriber($subscriber);
            }


            return $dispatcher;
        });
    }
}
