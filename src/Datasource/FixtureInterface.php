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

/**
 * Defines the interface that testing fixtures use.
 */
interface FixtureInterface
{
    /**
     * Run before each test is executed.
     *
     * Should insert all the records into the test database.
     *
     * @param \Cake\Datasource\ConnectionInterface $connection An instance of the connection
     *   into which the records will be inserted.
     * @return bool
     */
    public function insert(ConnectionInterface $connection): bool;

    /**
     * Truncates the current fixture.
     *
     * @param \Cake\Datasource\ConnectionInterface $connection A reference to a db instance
     * @return bool
     */
    public function truncate(ConnectionInterface $connection): bool;

    /**
     * Get the connection name this fixture should be inserted into.
     *
     * @return string
     */
    public function connection(): string;

    /**
     * Get the table/collection name for this fixture.
     *
     * @return string
     */
    public function sourceName(): string;
}
