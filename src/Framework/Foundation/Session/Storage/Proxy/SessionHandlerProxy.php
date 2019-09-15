<?php


namespace WebRover\Framework\Foundation\Session\Storage\Proxy;


/**
 * Class SessionHandlerProxy
 * @package WebRover\Framework\Foundation\Session\Storage\Proxy
 */
class SessionHandlerProxy extends AbstractProxy implements \SessionHandlerInterface, \SessionUpdateTimestampHandlerInterface
{
    protected $handler;

    public function __construct(\SessionHandlerInterface $handler)
    {
        $this->handler = $handler;
        $this->wrapper = ($handler instanceof \SessionHandler);
        $this->saveHandlerName = $this->wrapper ? ini_get('session.save_handler') : 'user';
    }

    /**
     * @return \SessionHandlerInterface
     */
    public function getHandler()
    {
        return $this->handler;
    }

    // \SessionHandlerInterface

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return (bool)$this->handler->open($savePath, $sessionName);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return (bool)$this->handler->close();
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        return (string)$this->handler->read($sessionId);
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        return (bool)$this->handler->write($sessionId, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        return (bool)$this->handler->destroy($sessionId);
    }

    /**
     * @return bool
     */
    public function gc($maxlifetime)
    {
        return (bool)$this->handler->gc($maxlifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function validateId($sessionId)
    {
        return !$this->handler instanceof \SessionUpdateTimestampHandlerInterface || $this->handler->validateId($sessionId);
    }

    /**
     * {@inheritdoc}
     */
    public function updateTimestamp($sessionId, $data)
    {
        return $this->handler instanceof \SessionUpdateTimestampHandlerInterface ? $this->handler->updateTimestamp($sessionId, $data) : $this->write($sessionId, $data);
    }
}