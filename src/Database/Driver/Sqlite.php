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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Driver;

use Cake\Database\Dialect\SqliteDialectTrait;
use Cake\Database\Driver;
use Cake\Database\Query;
use Cake\Database\Statement\PDOStatement;
use Cake\Database\Statement\SqliteStatement;
use Cake\Database\StatementInterface;
use InvalidArgumentException;
use PDO;

/**
 * Class Sqlite
 */
class Sqlite extends Driver
{
    use SqliteDialectTrait;

    /**
     * Base configuration settings for Sqlite driver
     *
     * - `mask` The mask used for created database
     *
     * @var array
     */
    protected $_baseConfig = [
        'persistent' => false,
        'username' => null,
        'password' => null,
        'database' => ':memory:',
        'encoding' => 'utf8',
        'mask' => 0644,
        'flags' => [],
        'init' => [],
    ];

    /**
     * Establishes a connection to the database server
     *
     * @return bool true on success
     */
    public function connect(): bool
    {
        if ($this->_connection) {
            return true;
        }
        $config = $this->_config;
        $config['flags'] += [
            PDO::ATTR_PERSISTENT => $config['persistent'],
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        if (!is_string($config['database']) || !strlen($config['database'])) {
            $name = $config['name'] ?? 'unknown';
            throw new InvalidArgumentException(
                "The `database` key for the `{$name}` SQLite connection needs to be a non-empty string."
            );
        }

        $databaseExists = file_exists($config['database']);

        $dsn = "sqlite:{$config['database']}";
        $this->_connect($dsn, $config);

        if (!$databaseExists && $config['database'] !== ':memory:') {
            // phpcs:disable
            @chmod($config['database'], $config['mask']);
            // phpcs:enable
        }

        if (!empty($config['init'])) {
            foreach ((array)$config['init'] as $command) {
                $this->getConnection()->exec($command);
            }
        }

        return true;
    }

    /**
     * Returns whether php is able to use this driver for connecting to database
     *
     * @return bool true if it is valid to use this driver
     */
    public function enabled(): bool
    {
        return in_array('sqlite', PDO::getAvailableDrivers(), true);
    }

    /**
     * Prepares a sql statement to be executed
     *
     * @param string|\Cake\Database\Query $query The query to prepare.
     * @return \Cake\Database\StatementInterface
     */
    public function prepare($query): StatementInterface
    {
        $this->connect();
        $isObject = $query instanceof Query;
        /**
         * @psalm-suppress PossiblyInvalidMethodCall
         * @psalm-suppress PossiblyInvalidArgument
         */
        $statement = $this->_connection->prepare($isObject ? $query->sql() : $query);
        $result = new SqliteStatement(new PDOStatement($statement, $this), $this);
        /** @psalm-suppress PossiblyInvalidMethodCall */
        if ($isObject && $query->isBufferedResultsEnabled() === false) {
            $result->bufferResults(false);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function supportsDynamicConstraints(): bool
    {
        return false;
    }
}
