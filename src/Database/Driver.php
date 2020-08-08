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
namespace Cake\Database;

use Cake\Core\App;
use Cake\Core\Retry\CommandRetry;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Database\Retry\ErrorCodeWaitStrategy;
use Cake\Database\Schema\SchemaDialect;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Statement\PDOStatement;
use Closure;
use InvalidArgumentException;
use PDO;
use PDOException;

/**
 * Represents a database driver containing all specificities for
 * a database engine including its SQL dialect.
 */
abstract class Driver implements DriverInterface
{
    /**
     * @var int|null Maximum alias length or null if no limit
     */
    protected const MAX_ALIAS_LENGTH = null;

    /**
     * @var int[] DB-specific error codes that allow connect retry
     */
    protected const RETRY_ERROR_CODES = [];

    /**
     * Instance of PDO.
     *
     * @var \PDO
     */
    protected $_connection;

    /**
     * Configuration data.
     *
     * @var array
     */
    protected $_config;

    /**
     * Base configuration that is merged into the user
     * supplied configuration data.
     *
     * @var array
     */
    protected $_baseConfig = [];

    /**
     * Indicates whether or not the driver is doing automatic identifier quoting
     * for all queries
     *
     * @var bool
     */
    protected $_autoQuoting = false;

    /**
     * Whether or not the server supports common table expressions.
     *
     * @var bool|null
     */
    protected $supportsCTEs = null;

    /**
     * The server version
     *
     * @var string|null
     */
    protected $_version;

    /**
     * The last number of connection retry attempts.
     *
     * @var int
     */
    protected $connectRetries = 0;

    /**
     * Constructor
     *
     * @param array $config The configuration for the driver.
     * @throws \InvalidArgumentException
     */
    public function __construct(array $config = [])
    {
        if (empty($config['username']) && !empty($config['login'])) {
            throw new InvalidArgumentException(
                'Please pass "username" instead of "login" for connecting to the database'
            );
        }
        $config += $this->_baseConfig;
        $this->_config = $config;
        if (!empty($config['quoteIdentifiers'])) {
            $this->enableAutoQuoting();
        }
    }

    /**
     * Establishes a connection to the database server
     *
     * @param string $dsn A Driver-specific PDO-DSN
     * @param array $config configuration to be used for creating connection
     * @return bool true on success
     */
    protected function _connect(string $dsn, array $config): bool
    {
        $action = function () use ($dsn, $config) {
            $this->setConnection(new PDO(
                $dsn,
                $config['username'] ?: null,
                $config['password'] ?: null,
                $config['flags']
            ));
        };

        $retry = new CommandRetry(new ErrorCodeWaitStrategy(static::RETRY_ERROR_CODES, 5), 4);
        try {
            $retry->run($action);
        } catch (PDOException $e) {
            throw new MissingConnectionException(
                [
                    'driver' => App::shortName(static::class, 'Database/Driver'),
                    'reason' => $e->getMessage(),
                ],
                null,
                $e
            );
        } finally {
            $this->connectRetries = $retry->getRetries();
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    abstract public function connect(): bool;

    /**
     * @inheritDoc
     */
    public function disconnect(): void
    {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->_connection = null;
        $this->_version = null;
    }

    /**
     * Returns connected server version.
     *
     * @return string
     */
    public function version(): string
    {
        if ($this->_version === null) {
            $this->connect();
            $this->_version = (string)$this->_connection->getAttribute(PDO::ATTR_SERVER_VERSION);
        }

        return $this->_version;
    }

    /**
     * Get the internal PDO connection instance.
     *
     * @return \PDO
     */
    public function getConnection()
    {
        if ($this->_connection === null) {
            throw new MissingConnectionException([
                'driver' => App::shortName(static::class, 'Database/Driver'),
                'reason' => 'Unknown',
            ]);
        }

        return $this->_connection;
    }

    /**
     * Set the internal PDO connection instance.
     *
     * @param \PDO $connection PDO instance.
     * @return $this
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function setConnection($connection)
    {
        $this->_connection = $connection;

        return $this;
    }

    /**
     * @inheritDoc
     */
    abstract public function enabled(): bool;

    /**
     * @inheritDoc
     */
    public function prepare($query): StatementInterface
    {
        $this->connect();
        $statement = $this->_connection->prepare($query instanceof Query ? $query->sql() : $query);

        return new PDOStatement($statement, $this);
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction(): bool
    {
        $this->connect();
        if ($this->_connection->inTransaction()) {
            return true;
        }

        return $this->_connection->beginTransaction();
    }

    /**
     * @inheritDoc
     */
    public function commitTransaction(): bool
    {
        $this->connect();
        if (!$this->_connection->inTransaction()) {
            return false;
        }

        return $this->_connection->commit();
    }

    /**
     * @inheritDoc
     */
    public function rollbackTransaction(): bool
    {
        $this->connect();
        if (!$this->_connection->inTransaction()) {
            return false;
        }

        return $this->_connection->rollBack();
    }

    /**
     * @inheritDoc
     */
    abstract public function releaseSavePointSQL($name): string;

    /**
     * @inheritDoc
     */
    abstract public function savePointSQL($name): string;

    /**
     * @inheritDoc
     */
    abstract public function rollbackSavePointSQL($name): string;

    /**
     * @inheritDoc
     */
    abstract public function disableForeignKeySQL(): string;

    /**
     * @inheritDoc
     */
    abstract public function enableForeignKeySQL(): string;

    /**
     * @inheritDoc
     */
    abstract public function supportsDynamicConstraints(): bool;

    /**
     * @inheritDoc
     */
    public function supportsSavePoints(): bool
    {
        return true;
    }

    /**
     * Returns true if the server supports common table expressions.
     *
     * @return bool
     */
    public function supportsCTEs(): bool
    {
        return $this->supportsCTEs === true;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $value The value to quote.
     * @param int $type Type to be used for determining kind of quoting to perform.
     * @return string
     */
    public function quote($value, $type = PDO::PARAM_STR): string
    {
        $this->connect();

        return $this->_connection->quote($value, $type);
    }

    /**
     * Checks if the driver supports quoting, as PDO_ODBC does not support it.
     *
     * @return bool
     */
    public function supportsQuoting(): bool
    {
        $this->connect();

        return $this->_connection->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'odbc';
    }

    /**
     * @inheritDoc
     */
    abstract public function queryTranslator(string $type): Closure;

    /**
     * @inheritDoc
     */
    abstract public function schemaDialect(): SchemaDialect;

    /**
     * @inheritDoc
     */
    abstract public function quoteIdentifier(string $identifier): string;

    /**
     * @inheritDoc
     */
    public function schemaValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if ($value === false) {
            return 'FALSE';
        }
        if ($value === true) {
            return 'TRUE';
        }
        if (is_float($value)) {
            return str_replace(',', '.', (string)$value);
        }
        /** @psalm-suppress InvalidArgument */
        if (
            (
                is_int($value) ||
                $value === '0'
            ) ||
            (
                is_numeric($value) &&
                strpos($value, ',') === false &&
                substr($value, 0, 1) !== '0' &&
                strpos($value, 'e') === false
            )
        ) {
            return (string)$value;
        }

        return $this->_connection->quote($value, PDO::PARAM_STR);
    }

    /**
     * @inheritDoc
     */
    public function schema(): string
    {
        return $this->_config['schema'];
    }

    /**
     * @inheritDoc
     */
    public function lastInsertId(?string $table = null, ?string $column = null)
    {
        $this->connect();

        if ($this->_connection instanceof PDO) {
            return $this->_connection->lastInsertId($table);
        }

        return $this->_connection->lastInsertId($table, $column);
    }

    /**
     * @inheritDoc
     */
    public function isConnected(): bool
    {
        if ($this->_connection === null) {
            $connected = false;
        } else {
            try {
                $connected = (bool)$this->_connection->query('SELECT 1');
            } catch (PDOException $e) {
                $connected = false;
            }
        }

        return $connected;
    }

    /**
     * @inheritDoc
     */
    public function enableAutoQuoting(bool $enable = true)
    {
        $this->_autoQuoting = $enable;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function disableAutoQuoting()
    {
        $this->_autoQuoting = false;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isAutoQuotingEnabled(): bool
    {
        return $this->_autoQuoting;
    }

    /**
     * @inheritDoc
     */
    public function compileQuery(Query $query, ValueBinder $generator): array
    {
        $processor = $this->newCompiler();
        $translator = $this->queryTranslator($query->type());
        $query = $translator($query);

        return [$query, $processor->compile($query, $generator)];
    }

    /**
     * @inheritDoc
     */
    public function newCompiler(): QueryCompiler
    {
        return new QueryCompiler();
    }

    /**
     * @inheritDoc
     */
    public function newTableSchema(string $table, array $columns = []): TableSchema
    {
        $className = TableSchema::class;
        if (isset($this->_config['tableSchema'])) {
            /** @var class-string<\Cake\Database\Schema\TableSchema> $className */
            $className = $this->_config['tableSchema'];
        }

        return new $className($table, $columns);
    }

    /**
     * Returns the maximum alias length allowed.
     * This can be different than the maximum identifier length for columns.
     *
     * @return int|null Maximum alias length or null if no limit
     */
    public function getMaxAliasLength(): ?int
    {
        return static::MAX_ALIAS_LENGTH;
    }

    /**
     * Returns the number of connection retry attempts made.
     *
     * @return int
     */
    public function getConnectRetries(): int
    {
        return $this->connectRetries;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->_connection = null;
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            'connected' => $this->_connection !== null,
        ];
    }
}
