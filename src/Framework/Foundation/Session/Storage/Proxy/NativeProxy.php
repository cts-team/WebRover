<?php


namespace WebRover\Framework\Foundation\Session\Storage\Proxy;


/**
 * This proxy is built-in session handlers in PHP 5.3.x.
 *
 * Class NativeProxy
 * @package WebRover\Framework\Foundation\Session\Storage\Proxy
 */
class NativeProxy extends AbstractProxy
{
    public function __construct()
    {
        // this makes an educated guess as to what the handler is since it should already be set.
        $this->saveHandlerName = ini_get('session.save_handler');
    }

    /**
     * Returns true if this handler wraps an internal PHP session save handler using \SessionHandler.
     *
     * @return bool False
     */
    public function isWrapper()
    {
        return false;
    }
}