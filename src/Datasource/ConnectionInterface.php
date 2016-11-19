<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

/**
 * This interface defines the methods you can depend on in
 * a connection.
 */
interface ConnectionInterface
{
    /**
     * Get the configuration name for this connection.
     *
     * @return string
     */
    public function configName();

    /**
     * Get the configuration data used to create the connection.
     *
     * @return array
     */
    public function config();

    /**
     * Sets the driver instance. If a string is passed it will be treated
     * as a class name and will be instantiated.
     *
     * @param \Cake\Database\Driver|string $driver The driver instance to use.
     * @param array $config Config for a new driver.
     * @throws \Cake\Database\Exception\MissingDriverException When a driver class is missing.
     * @throws \Cake\Database\Exception\MissingExtensionException When a driver's PHP extension is missing.
     * @return $this
     */
    public function setDriver($driver, $config = []);

    /**
     * Gets the driver instance.
     *
     * @return \Cake\Database\Driver
     */
    public function getDriver();

    /**
     * Sets the driver instance. If a string is passed it will be treated
     * as a class name and will be instantiated.
     *
     * If no params are passed it will return the current driver instance.
     *
     * @deprecated 3.4.0 Use setDriver()/getDriver() instead.
     * @param \Cake\Database\Driver|string|null $driver The driver instance to use.
     * @param array $config Either config for a new driver or null.
     * @throws \Cake\Database\Exception\MissingDriverException When a driver class is missing.
     * @throws \Cake\Database\Exception\MissingExtensionException When a driver's PHP extension is missing.
     * @return \Cake\Database\Driver
     */
    public function driver($driver = null, $config = []);

    /**
     * Executes a callable function inside a transaction, if any exception occurs
     * while executing the passed callable, the transaction will be rolled back
     * If the result of the callable function is `false`, the transaction will
     * also be rolled back. Otherwise the transaction is committed after executing
     * the callback.
     *
     * The callback will receive the connection instance as its first argument.
     *
     * @param callable $transaction The callback to execute within a transaction.
     * @return mixed The return value of the callback.
     * @throws \Exception Will re-throw any exception raised in $callback after
     *   rolling back the transaction.
     */
    public function transactional(callable $transaction);

    /**
     * Checks if a transaction is running.
     *
     * @return bool True if a transaction is running else false.
     */
    public function inTransaction();

    /**
     * Rollback current transaction.
     *
     * @return bool
     */
    public function rollback();

    /**
     * Run an operation with constraints disabled.
     *
     * Constraints should be re-enabled after the callback succeeds/fails.
     *
     * @param callable $operation The callback to execute within a transaction.
     * @return mixed The return value of the callback.
     * @throws \Exception Will re-throw any exception raised in $callback after
     *   rolling back the transaction.
     */
    public function disableConstraints(callable $operation);

    /**
     * Enables or disables query logging for this connection.
     *
     * @param bool|null $enable whether to turn logging on or disable it.
     *   Use null to read current value.
     * @return bool
     */
    public function logQueries($enable = null);

    /**
     * Sets the logger object instance. When called with no arguments
     * it returns the currently setup logger instance.
     *
     * @param object|null $instance logger object instance
     * @return object logger instance
     */
    public function logger($instance = null);
}
