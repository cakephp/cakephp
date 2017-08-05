<?php
namespace Cake\Cache;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Cake\Cache\CacheEngine;

class SimpleCache implements CacheInterface
{
    /**
     * @var \Cake\Cache\CacheEngine
     */
    protected $engine;

    public function __construct(CacheEngine $engine)
    {
        $this->engine = $engine;
    }

    public function get($key, $default = null)
    {
        $result = $engine->read($key);

        return $result === false ? $default : $result;
    }

    public function set($key, $value, $ttl = null)
    {
        if ($ttl !== null) {
            $duration = $this->engine->getConfig('duration');
            $this->engine->setConfig('duration', $this->ttlToSeconds($ttl));
        }

        try {
            $result = $this->engine->write($key, $value);
        } finally {
            if ($ttl !== null) {
                $this->engine->setConfig('duration', $duration);
            }
        }

        return $result;
    }

    public function delete($key)
    {
        return $this->engine->delete($key);
    }

    public function clear()
    {
        return $this->engine->clear(false);
    }

    public function getMultiple($keys, $default = null)
    {
        $keys   = $this->getAsArray($keys);
        $result = [];

        foreach ($keys as $key) {
            $value = $this->engine->get($key, $default);
        }

        return $result;
    }

    public function setMultiple($values, $ttl = null)
    {
        if ($ttl !== null) {
            $duration = $this->engine->getConfig('duration');
            $this->engine->setConfig('duration', $this->ttlToSeconds($ttl));
        }

        try {
            $result = true;
            foreach ($values as $key => $value) {
                $result = $this->engine->set($key, $value) && $result;
            }
        } finally {
            if ($ttl !== null) {
                $this->engine->setConfig('duration', $duration);
            }
        }

        return $result;
    }

    public function deleteMultiple($keys)
    {
        $keys = $this->getAsArray($keys);

        $result = true;
        foreach ($keys as $key) {
            $result = $this->engine->delete($key) && $result;
        }

        return $result;
    }

    public function has($key)
    {
        return $this->get($key) === null ? false : true;
    }

    protected function getAsArray($keys)
    {
        if ($keys instanceof \Traversable) {
            return iterator_to_array($keys);
        }

        if (is_array($keys)) {
            return $keys;
        }

        throw new InvalidArgumentException('"$keys" must be an array or instanceof Traversable');
    }

    /**
     * @param int|\DateInterval $ttl
     * @return int seconds
     */
    function ttlToSeconds($ttl)
    {
        if (is_int($ttl)) {
            return $ttl;
        }

        return $ttl->days * 86400 + $ttl->h * 3600 + $ttl->i * 60 + $ttl->s;
    }
}
