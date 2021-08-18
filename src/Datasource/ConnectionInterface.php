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
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * This interface defines the methods you can depend on in a connection.
 */
interface ConnectionInterface extends LoggerAwareInterface
{
    /**
     * Gets the driver instance.
     *
     * @return object
     */
    public function getDriver(): object;

    /**
     * Gets the current logger object.
     *
     * @return \Psr\Log\LoggerInterface logger instance
     */
    public function getLogger(): LoggerInterface;

    /**
     * Set a cacher.
     *
     * @param \Psr\SimpleCache\CacheInterface $cacher Cacher object
     * @return $this
     */
    public function setCacher(CacheInterface $cacher);

    /**
     * Get a cacher.
     *
     * @return \Psr\SimpleCache\CacheInterface $cacher Cacher object
     */
    public function getCacher(): CacheInterface;

    /**
     * Get the configuration name for this connection.
     *
     * @return string
     */
    public function configName(): string;

    /**
     * Get the configuration data used to create the connection.
     *
     * @return array
     */
    public function config(): array;

    /**
     * Enable/disable query logging
     *
     * @param bool $enable Enable/disable query logging
     * @return $this
     */
    public function enableQueryLogging(bool $enable = true);

    /**
     * Disable query logging
     *
     * @return $this
     */
    public function disableQueryLogging();

    /**
     * Check if query logging is enabled.
     *
     * @return bool
     */
    public function isQueryLoggingEnabled(): bool;
}
