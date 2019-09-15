<?php


namespace WebRover\Framework\Cache;


interface CacheInterface
{
    public function has($key);

    public function get($key, $default = null);

    public function set($key, $value, $ttl = 0);

    public function delete($key);

    public function flush();
}