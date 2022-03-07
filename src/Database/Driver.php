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
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Statement\Statement;
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
     * @var array<int>  DB-specific error codes that allow connect retry
     */
    protected const RETRY_ERROR_CODES = [];

    /**
     * @var class-string<\Cake\Database\Statement\Statement>
     */
    protected const STATEMENT_CLASS = Statement::class;

    /**
     * Instance of PDO.
     *
     * @var \PDO|null
     */
    protected ?PDO $pdo = null;

    /**
     * Configuration data.
     *
     * @var array<string, mixed>
     */
    protected array $_config = [];

    /**
     * Base configuration that is merged into the user
     * supplied configuration data.
     *
     * @var array<string, mixed>
     */
    protected array $_baseConfig = [];

    /**
     * Indicates whether the driver is doing automatic identifier quoting
     * for all queries
     *
     * @var bool
     */
    protected bool $_autoQuoting = false;

    /**
     * The server version
     *
     * @var string|null
     */
    protected ?string $_version = null;

    /**
     * The last number of connection retry attempts.
     *
     * @var int
     */
    protected int $connectRetries = 0;

    /**
     * The schema dialect for this driver
     *
     * @var \Cake\Database\Schema\SchemaDialect
     */
    protected SchemaDialect $_schemaDialect;

    /**
     * Constructor
     *
     * @param array<string, mixed> $config The configuration for the driver.
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
     * @param array<string, mixed> $config configuration to be used for creating connection
     * @return \PDO
     */
    protected function createPdo(string $dsn, array $config): PDO
    {
        $action = function () use ($dsn, $config): PDO {
            return new PDO(
                $dsn,
                $config['username'] ?: null,
                $config['password'] ?: null,
                $config['flags']
            );
        };

        $retry = new CommandRetry(new ErrorCodeWaitStrategy(static::RETRY_ERROR_CODES, 5), 4);
        try {
            return $retry->run($action);
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
    }

    /**
     * @inheritDoc
     */
    abstract public function connect(): void;

    /**
     * @inheritDoc
     */
    public function disconnect(): void
    {
        $this->pdo = null;
        $this->_version = null;
    }

    /**
     * Returns connected server version.
     *
     * @return string
     */
    public function version(): string
    {
        return $this->_version ??= (string)$this->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Get the PDO connection instance.
     *
     * @return \PDO
     */
    protected function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }

        /** @var \PDO */
        return $this->pdo;
    }

    /**
     * Execute the SQL query using the internal PDO instance.
     *
     * @param string $sql SQL query.
     * @return int|false
     */
    public function exec(string $sql): int|false
    {
        return $this->getPdo()->exec($sql);
    }

    /**
     * @inheritDoc
     */
    abstract public function enabled(): bool;

    /**
     * @inheritDoc
     */
    public function prepare(Query|string $query): StatementInterface
    {
        $statement = $this->getPdo()->prepare($query instanceof Query ? $query->sql() : $query);

        $typeMap = null;
        if ($query instanceof Query && $query->isResultsCastingEnabled() && $query->type() === Query::TYPE_SELECT) {
            $typeMap = $query->getSelectTypeMap();
        }

        /** @var \Cake\Database\StatementInterface */
        return new (static::STATEMENT_CLASS)($statement, $this, $typeMap);
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction(): bool
    {
        if ($this->getPdo()->inTransaction()) {
            return true;
        }

        return $this->getPdo()->beginTransaction();
    }

    /**
     * @inheritDoc
     */
    public function commitTransaction(): bool
    {
        if (!$this->getPdo()->inTransaction()) {
            return false;
        }

        return $this->getPdo()->commit();
    }

    /**
     * @inheritDoc
     */
    public function rollbackTransaction(): bool
    {
        if (!$this->getPdo()->inTransaction()) {
            return false;
        }

        return $this->getPdo()->rollBack();
    }

    /**
     * Returns whether a transaction is active for connection.
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->getPdo()->inTransaction();
    }

    /**
     * @inheritDoc
     */
    public function quote($value, $type = PDO::PARAM_STR): string
    {
        return $this->getPdo()->quote((string)$value, $type);
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
                !str_contains($value, ',') &&
                substr($value, 0, 1) !== '0' &&
                !str_contains($value, 'e')
            )
        ) {
            return (string)$value;
        }

        return $this->getPdo()->quote((string)$value, PDO::PARAM_STR);
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
    public function lastInsertId(?string $table = null): string
    {
        return $this->getPdo()->lastInsertId($table);
    }

    /**
     * @inheritDoc
     */
    public function isConnected(): bool
    {
        if (isset($this->pdo)) {
            try {
                $connected = (bool)$this->pdo->query('SELECT 1');
            } catch (PDOException $e) {
                $connected = false;
            }
        } else {
            $connected = false;
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
     * Returns whether the driver supports the feature.
     *
     * Defaults to true for FEATURE_QUOTE and FEATURE_SAVEPOINT.
     *
     * @param string $feature Driver feature name
     * @return bool
     */
    public function supports(string $feature): bool
    {
        switch ($feature) {
            case static::FEATURE_DISABLE_CONSTRAINT_WITHOUT_TRANSACTION:
            case static::FEATURE_QUOTE:
            case static::FEATURE_SAVEPOINT:
                return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function compileQuery(Query $query, ValueBinder $binder): array
    {
        $processor = $this->newCompiler();
        $translator = $this->queryTranslator($query->type());
        $query = $translator($query);

        return [$query, $processor->compile($query, $binder)];
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
    public function newTableSchema(string $table, array $columns = []): TableSchemaInterface
    {
        /** @var class-string<\Cake\Database\Schema\TableSchemaInterface> $className */
        $className = $this->_config['tableSchema'] ?? TableSchema::class;

        return new $className($table, $columns);
    }

    /**
     * Returns the maximum alias length allowed.
     * This can be different from the maximum identifier length for columns.
     *
     * @return int|null Maximum alias length or null if no limit
     */
    public function getMaxAliasLength(): ?int
    {
        return static::MAX_ALIAS_LENGTH;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->pdo = null;
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'connected' => $this->pdo !== null,
        ];
    }
}
