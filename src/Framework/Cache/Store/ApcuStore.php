<?php


namespace WebRover\Framework\Cache\Store;


use Symfony\Component\Cache\Adapter\ApcuAdapter;

/**
 * Class ApcuStore
 * @package WebRover\Framework\Cache\Store
 */
class ApcuStore extends AbstractStore
{
    public function connect(array $params)
    {
        $this->instance = new ApcuAdapter();

        return $this;
    }
}