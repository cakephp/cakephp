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
 * This interface defines the methods you can depend on in
 * a connection.
 *
 * @method object getDriver() Gets the driver instance. {@see \Cake\Database\Connnection::getDriver()}
 * @method $this setLogger($logger) Set the current logger. {@see \Cake\Database\Connnection::setLogger()}
 * @method bool supportsDynamicConstraints() Returns whether the driver supports adding or dropping constraints to
 *   already created tables. {@see \Cake\Database\Connnection::supportsDynamicConstraints()}
 * @method \Cake\Database\Schema\Collection getSchemaCollection() Gets a Schema\Collection object for this connection.
 *    {@see \Cake\Database\Connnection::getSchemaCollection()}
 * @method \Cake\Database\Query newQuery() Create a new Query instance for this connection.
 *    {@see \Cake\Database\Connnection::newQuery()}
 * @method \Cake\Database\StatementInterface prepare($sql) Prepares a SQL statement to be executed.
 *    {@see \Cake\Database\Connnection::prepare()}
 * @method \Cake\Database\StatementInterface execute($query, $params = [], array $types = []) Executes a query using
 *   `$params` for interpolating values and $types as a hint for each those params.
 *   {@see \Cake\Database\Connnection::execute()}
 * @method \Cake\Database\StatementInterface query(string $sql) Executes a SQL statement and returns the Statement
 *   object as result. {@see \Cake\Database\Connnection::query()}
 */
interface ConnectionInterface extends LoggerAwareInterface
{
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
     * @return array<string, mixed>
     */
    public function config(): array;

    /**
     * Executes a callable function inside a transaction, if any exception occurs
     * while executing the passed callable, the transaction will be rolled back
     * If the result of the callable function is `false`, the transaction will
     * also be rolled back. Otherwise, the transaction is committed after executing
     * the callback.
     *
     * The callback will receive the connection instance as its first argument.
     *
     * ### Example:
     *
     * ```
     * $connection->transactional(function ($connection) {
     *   $connection->newQuery()->delete('users')->execute();
     * });
     * ```
     *
     * @param callable $callback The callback to execute within a transaction.
     * @return mixed The return value of the callback.
     * @throws \Exception Will re-throw any exception raised in $callback after
     *   rolling back the transaction.
     */
    public function transactional(callable $callback);

    /**
     * Run an operation with constraints disabled.
     *
     * Constraints should be re-enabled after the callback succeeds/fails.
     *
     * ### Example:
     *
     * ```
     * $connection->disableConstraints(function ($connection) {
     *   $connection->newQuery()->delete('users')->execute();
     * });
     * ```
     *
     * @param callable $callback The callback to execute within a transaction.
     * @return mixed The return value of the callback.
     * @throws \Exception Will re-throw any exception raised in $callback after
     *   rolling back the transaction.
     */
    public function disableConstraints(callable $callback);

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
