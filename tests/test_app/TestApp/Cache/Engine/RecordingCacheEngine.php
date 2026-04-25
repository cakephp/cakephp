<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Cache\Engine;

/**
 * In-memory cache engine that records every get() call and stores set()
 * payloads for inspection. Used by unit tests that assert on cache key
 * shapes.
 */
class RecordingCacheEngine extends TestAppCacheEngine
{
    /**
     * @var array<string, mixed>
     */
    public array $store = [];

    /**
     * @var list<string>
     */
    public array $reads = [];

    /**
     * @inheritDoc
     */
    public function get($key, $default = null): mixed
    {
        $this->reads[] = $key;

        return $this->store[$key] ?? $default;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        $this->store[$key] = $value;

        return true;
    }
}
