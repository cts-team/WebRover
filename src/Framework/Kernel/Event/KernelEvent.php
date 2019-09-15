<?php


namespace WebRover\Framework\Kernel\Event;


use Symfony\Component\EventDispatcher\Event;
use WebRover\Framework\Foundation\Request;
use WebRover\Framework\Kernel\HttpKernelInterface;

/**
 * Base class for events
 *
 * Class KernelEvent
 * @package WebRover\Framework\Kernel\Event
 */
class KernelEvent extends Event
{
    private $kernel;
    private $request;
    private $requestType;

    /**
     * @param HttpKernelInterface $kernel The kernel in which this event was thrown
     * @param Request $request The request the kernel is currently processing
     * @param int $requestType The request type the kernel is currently processing; one of
     *                                         HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST
     */
    public function __construct(HttpKernelInterface $kernel, Request $request, $requestType)
    {
        $this->kernel = $kernel;
        $this->request = $request;
        $this->requestType = $requestType;
    }

    /**
     * Returns the kernel in which this event was thrown.
     *
     * @return HttpKernelInterface
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * Returns the request the kernel is currently processing.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns the request type the kernel is currently processing.
     *
     * @return int One of HttpKernelInterface::MASTER_REQUEST and
     *             HttpKernelInterface::SUB_REQUEST
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * Checks if this is a master request.
     *
     * @return bool True if the request is a master request
     */
    public function isMasterRequest()
    {
        return HttpKernelInterface::MASTER_REQUEST === $this->requestType;
    }
}