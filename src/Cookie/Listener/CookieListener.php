<?php


namespace WebRover\Framework\Cookie\Listener;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use WebRover\Framework\Encryption\DecryptException;
use WebRover\Framework\Encryption\Encrypter;
use WebRover\Framework\Kernel\Event\FilterResponseEvent;
use WebRover\Framework\Kernel\Event\GetResponseEvent;
use WebRover\Framework\Kernel\KernelEvents;

/**
 * Class CookieListener
 * @package WebRover\Framework\Cookie\Listener
 */
class CookieListener implements EventSubscriberInterface
{
    private $encryter;

    private $except = [];

    public function __construct(Encrypter $encryter, array $except = [])
    {
        $this->encryter = $encryter;
        $this->except = $except;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $cookies = $request->cookies->keys();

        foreach ($cookies as $cookie) {

            if ($this->isDisabled($cookie)) {
                continue;
            }

            $value = $request->cookies->get($cookie);

            if ($value !== '' && !is_null($value) && is_string($value)) {
                try {
                    $value = $this->encryter->decrypt($value);
                } catch (DecryptException $exception) {
                }
            }

            $request->cookies->set($cookie, $value);
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();

        foreach ($response->headers->getCookies() as $cookie) {
            if ($this->isDisabled($cookie->getName())) {
                continue;
            }

            if (is_null($cookie->getValue())) {
                continue;
            }

            $response->headers->setCookie(
                $this->duplicate(
                    $cookie,
                    $this->encryter->encrypt($cookie->getValue())
                )
            );
        }
    }

    /**
     * Duplicate a cookie with a new value.
     *
     * @param Cookie $c
     * @param mixed $value
     * @return Cookie
     */
    protected function duplicate(Cookie $c, $value)
    {
        return new Cookie(
            $c->getName(), $value, $c->getExpiresTime(), $c->getPath(),
            $c->getDomain(), $c->isSecure(), $c->isHttpOnly()
        );
    }

    /**
     * Determine whether encryption has been disabled for the given cookie.
     *
     * @param string $name
     * @return bool
     */
    public function isDisabled($name)
    {
        return in_array($name, $this->except);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse'
        ];
    }
}
