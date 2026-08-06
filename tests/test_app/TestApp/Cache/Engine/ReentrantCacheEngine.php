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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Cache\Engine;

use Cake\Cache\Cache;
use Cake\Cache\Engine\ArrayEngine;

/**
 * Asks for a cache pool from inside its own init(), the way an engine that logs a failed
 * connection does when the logger reaches for a cache-backed resource.
 */
class ReentrantCacheEngine extends ArrayEngine
{
    /**
     * @var int
     */
    public static int $initCount = 0;

    /**
     * What the reentrant Cache::pool() call handed back.
     *
     * @var \Psr\SimpleCache\CacheInterface|null
     */
    public static $reentrantPool = null;

    /**
     * @param array<string, mixed> $config
     * @return bool
     */
    public function init(array $config = []): bool
    {
        parent::init($config);

        static::$initCount++;
        static::$reentrantPool = Cache::pool($config['reentrantTarget']);

        return true;
    }

    /**
     * @return void
     */
    public static function reset(): void
    {
        static::$initCount = 0;
        static::$reentrantPool = null;
    }
}
