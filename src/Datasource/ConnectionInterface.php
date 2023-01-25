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

use Psr\SimpleCache\CacheInterface;

/**
 * This interface defines the methods you can depend on in a connection.
 */
interface ConnectionInterface
{
    /**
     * @var string
     */
    public const ROLE_WRITE = 'write';

    /**
     * @var string
     */
    public const ROLE_READ = 'read';

    /**
     * Gets the driver instance.
     *
     * @param string $role
     * @return object
     */
    public function getDriver(string $role = self::ROLE_WRITE): object;

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
     * @return array<string, mixed>
     */
    public function config(): array;
}
