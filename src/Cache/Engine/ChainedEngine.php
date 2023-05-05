<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache\Engine;

use Cake\Cache\Cache;
use Cake\Cache\CacheEngine;
use Cake\Cache\Exception\InvalidArgumentException;
use Cake\Utility\Hash;
use RuntimeException;

/**
 * Chained engine for cache.
 */
class ChainedEngine extends CacheEngine
{
    /**
     * Main cache engine
     *
     * @var \Cake\Cache\CacheEngine
     */
    protected CacheEngine $engine;

    /**
     * Constructor
     *
     * @param array $config Engine config
     * @param \Cake\Cache\CacheEngine $engine Main cache engine
     */
    public function __construct(array $config, CacheEngine $engine)
    {
        $this->engine = $engine;
        $this->setConfig($config);
    }

    /**
     * @inheritDoc
     */
    public function init(array $config = []): bool
    {
        if ($this->engine->init($config)) {
            return true;
        }
        $alias = Hash::get($config, 'alias');
        if (!array_key_exists('fallback', $config)) {
            $this->engine = new NullEngine();
            trigger_error(sprintf(
                '"%s" does not have a fallback config. Setting NullEngine as fallback.',
                $alias
            ), E_USER_WARNING);

            return true;
        }

        if ($config['fallback'] === $alias) {
            throw new InvalidArgumentException(sprintf(
                '"%s" cache configuration cannot fallback to itself.',
                $alias
            ), 0);
        }

        /** @var \Cake\Cache\CacheEngine $fallbackEngine */
        $fallbackEngine = clone Cache::pool($config['fallback']);
        $newConfig = $config + ['groups' => [], 'prefix' => null];
        $fallbackEngine->setConfig('groups', $newConfig['groups'], false);
        if ($newConfig['prefix']) {
            $fallbackEngine->setConfig('prefix', $newConfig['prefix'], false);
        }
        if (!$fallbackEngine->init($config)) {
            throw new RuntimeException(sprintf(
                '"%s" fallback configuration for "%s" cache configuration cannot be initialised.',
                $config['fallback'],
                $alias
            ));
        }

        $this->engine = $fallbackEngine;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        return $this->engine->get($key, $default);
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        return $this->engine->set($key, $value, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function increment(string $key, int $offset = 1)
    {
        return $this->engine->increment($key, $offset);
    }

    /**
     * @inheritDoc
     */
    public function decrement(string $key, int $offset = 1)
    {
        return $this->engine->decrement($key, $offset);
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
        return $this->engine->delete($key);
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return $this->engine->clear();
    }

    /**
     * @inheritDoc
     */
    public function clearGroup(string $group): bool
    {
        return $this->engine->clearGroup($group);
    }
}
