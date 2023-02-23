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
use Cake\Core\Exception\CakeException;
use Cake\Core\Retry\CommandRetry;
use Cake\Database\Exception\DatabaseException;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Database\Expression\ComparisonExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Log\LoggedQuery;
use Cake\Database\Log\QueryLogger;
use Cake\Database\Query\DeleteQuery;
use Cake\Database\Query\InsertQuery;
use Cake\Database\Query\SelectQuery;
use Cake\Database\Query\UpdateQuery;
use Cake\Database\Retry\ErrorCodeWaitStrategy;
use Cake\Database\Schema\SchemaDialect;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Statement\Statement;
use InvalidArgumentException;
use PDO;
use PDOException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Stringable;

/**
 * Represents a database driver containing all specificities for
 * a database engine including its SQL dialect.
 */
abstract class Driver
{
    use LoggerAwareTrait;

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
     * String used to start a database identifier quoting to make it safe
     *
     * @var string
     */
    protected string $_startQuote = '';

    /**
     * String used to end a database identifier quoting to make it safe
     *
     * @var string
     */
    protected string $_endQuote = '';

    /**
     * Identifier quoter
     *
     * @var \Cake\Database\IdentifierQuoter|null
     */
    protected ?IdentifierQuoter $quoter = null;

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
        $config += $this->_baseConfig + ['log' => false];
        $this->_config = $config;
        if (!empty($config['quoteIdentifiers'])) {
            $this->enableAutoQuoting();
        }
        if ($config['log'] !== false) {
            $this->logger = $this->createLogger($config['log'] === true ? null : $config['log']);
        }
    }

    /**
     * Get the configuration data used to create the driver.
     *
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return $this->_config;
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
        $action = fn () => new PDO(
            $dsn,
            $config['username'] ?: null,
            $config['password'] ?: null,
            $config['flags']
        );

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
     * Establishes a connection to the database server.
     *
     * @throws \Cake\Database\Exception\MissingConnectionException If database connection could not be established.
     * @return void
     */
    abstract public function connect(): void;

    /**
     * Disconnects from database server.
     *
     * @return void
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
        assert($this->pdo !== null);

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
     * Returns whether php is able to use this driver for connecting to database.
     *
     * @return bool True if it is valid to use this driver.
     */
    abstract public function enabled(): bool;

    /**
     * Executes a query using $params for interpolating values and $types as a hint for each
     * those params.
     *
     * @param string $sql SQL to be executed and interpolated with $params
     * @param array $params List or associative array of params to be interpolated in $sql as values.
     * @param array $types List or associative array of types to be used for casting values in query.
     * @return \Cake\Database\StatementInterface Executed statement
     */
    public function execute(string $sql, array $params = [], array $types = []): StatementInterface
    {
        $statement = $this->prepare($sql);
        if (!empty($params)) {
            $statement->bind($params, $types);
        }
        $this->executeStatement($statement);

        return $statement;
    }

    /**
     * Executes the provided query after compiling it for the specific driver
     * dialect and returns the executed Statement object.
     *
     * @param \Cake\Database\Query $query The query to be executed.
     * @return \Cake\Database\StatementInterface Executed statement
     */
    public function run(Query $query): StatementInterface
    {
        $statement = $this->prepare($query);
        $query->getValueBinder()->attachTo($statement);
        $this->executeStatement($statement);

        return $statement;
    }

    /**
     * Execute the statement and log the query string.
     *
     * @param \Cake\Database\StatementInterface $statement Statement to execute.
     * @param array|null $params List of values to be bound to query.
     * @return void
     */
    protected function executeStatement(StatementInterface $statement, ?array $params = null): void
    {
        if ($this->logger === null) {
            $statement->execute($params);

            return;
        }

        $exception = null;
        $took = 0.0;

        try {
            $start = microtime(true);
            $statement->execute($params);
            $took = (float)number_format((microtime(true) - $start) * 1000, 1);
        } catch (PDOException $e) {
            $exception = $e;
        }

        $logContext = [
            'driver' => $this,
            'error' => $exception,
            'params' => $params ?? $statement->getBoundParams(),
        ];
        if (!$exception) {
            $logContext['numRows'] = $statement->rowCount();
            $logContext['took'] = $took;
        }
        $this->log($statement->queryString(), $logContext);

        if ($exception) {
            throw $exception;
        }
    }

    /**
     * Prepares a sql statement to be executed.
     *
     * @param \Cake\Database\Query|string $query The query to turn into a prepared statement.
     * @return \Cake\Database\StatementInterface
     */
    public function prepare(Query|string $query): StatementInterface
    {
        $statement = $this->getPdo()->prepare($query instanceof Query ? $query->sql() : $query);

        $typeMap = null;
        if ($query instanceof SelectQuery && $query->isResultsCastingEnabled()) {
            $typeMap = $query->getSelectTypeMap();
        }

        /** @var \Cake\Database\StatementInterface */
        return new (static::STATEMENT_CLASS)($statement, $this, $typeMap);
    }

    /**
     * Starts a transaction.
     *
     * @return bool True on success, false otherwise.
     */
    public function beginTransaction(): bool
    {
        if ($this->getPdo()->inTransaction()) {
            return true;
        }

        $this->log('BEGIN');

        return $this->getPdo()->beginTransaction();
    }

    /**
     * Commits a transaction.
     *
     * @return bool True on success, false otherwise.
     */
    public function commitTransaction(): bool
    {
        if (!$this->getPdo()->inTransaction()) {
            return false;
        }

        $this->log('COMMIT');

        return $this->getPdo()->commit();
    }

    /**
     * Rollbacks a transaction.
     *
     * @return bool True on success, false otherwise.
     */
    public function rollbackTransaction(): bool
    {
        if (!$this->getPdo()->inTransaction()) {
            return false;
        }

        $this->log('ROLLBACK');

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
     * Returns a SQL snippet for creating a new transaction savepoint
     *
     * @param string|int $name save point name
     * @return string
     */
    public function savePointSQL(string|int $name): string
    {
        return 'SAVEPOINT LEVEL' . $name;
    }

    /**
     * Returns a SQL snippet for releasing a previously created save point
     *
     * @param string|int $name save point name
     * @return string
     */
    public function releaseSavePointSQL(string|int $name): string
    {
        return 'RELEASE SAVEPOINT LEVEL' . $name;
    }

    /**
     * Returns a SQL snippet for rollbacking a previously created save point
     *
     * @param string|int $name save point name
     * @return string
     */
    public function rollbackSavePointSQL(string|int $name): string
    {
        return 'ROLLBACK TO SAVEPOINT LEVEL' . $name;
    }

    /**
     * Get the SQL for disabling foreign keys.
     *
     * @return string
     */
    abstract public function disableForeignKeySQL(): string;

    /**
     * Get the SQL for enabling foreign keys.
     *
     * @return string
     */
    abstract public function enableForeignKeySQL(): string;

    /**
     * Transform the query to accommodate any specificities of the SQL dialect in use.
     *
     * It will also quote the identifiers if auto quoting is enabled.
     *
     * @param \Cake\Database\Query $query Query to transform.
     * @return \Cake\Database\Query
     */
    protected function transformQuery(Query $query): Query
    {
        if ($this->isAutoQuotingEnabled()) {
            $query = $this->quoter()->quote($query);
        }

        $query = match (true) {
            $query instanceof SelectQuery => $this->_selectQueryTranslator($query),
            $query instanceof InsertQuery => $this->_insertQueryTranslator($query),
            $query instanceof UpdateQuery => $this->_updateQueryTranslator($query),
            $query instanceof DeleteQuery => $this->_deleteQueryTranslator($query),
            default => throw new InvalidArgumentException(sprintf(
                'Instance of SelectQuery, UpdateQuery, InsertQuery, DeleteQuery expected. Found `%s` instead.',
                get_debug_type($query)
            )),
        };

        $translators = $this->_expressionTranslators();
        if (!$translators) {
            return $query;
        }

        $query->traverseExpressions(function ($expression) use ($translators, $query): void {
            foreach ($translators as $class => $method) {
                if ($expression instanceof $class) {
                    $this->{$method}($expression, $query);
                }
            }
        });

        return $query;
    }

    /**
     * Returns an associative array of methods that will transform Expression
     * objects to conform with the specific SQL dialect. Keys are class names
     * and values a method in this class.
     *
     * @return array<class-string, string>
     */
    protected function _expressionTranslators(): array
    {
        return [];
    }

    /**
     * Apply translation steps to select queries.
     *
     * @param \Cake\Database\Query\SelectQuery<mixed> $query The query to translate
     * @return \Cake\Database\Query\SelectQuery<mixed> The modified query
     */
    protected function _selectQueryTranslator(SelectQuery $query): SelectQuery
    {
        return $this->_transformDistinct($query);
    }

    /**
     * Returns the passed query after rewriting the DISTINCT clause, so that drivers
     * that do not support the "ON" part can provide the actual way it should be done
     *
     * @param \Cake\Database\Query\SelectQuery<mixed> $query The query to be transformed
     * @return \Cake\Database\Query\SelectQuery<mixed>
     */
    protected function _transformDistinct(SelectQuery $query): SelectQuery
    {
        if (is_array($query->clause('distinct'))) {
            $query->groupBy($query->clause('distinct'), true);
            $query->distinct(false);
        }

        return $query;
    }

    /**
     * Apply translation steps to delete queries.
     *
     * Chops out aliases on delete query conditions as most database dialects do not
     * support aliases in delete queries. This also removes aliases
     * in table names as they frequently don't work either.
     *
     * We are intentionally not supporting deletes with joins as they have even poorer support.
     *
     * @param \Cake\Database\Query\DeleteQuery $query The query to translate
     * @return \Cake\Database\Query\DeleteQuery The modified query
     */
    protected function _deleteQueryTranslator(DeleteQuery $query): DeleteQuery
    {
        $hadAlias = false;
        $tables = [];
        foreach ($query->clause('from') as $alias => $table) {
            if (is_string($alias)) {
                $hadAlias = true;
            }
            $tables[] = $table;
        }
        if ($hadAlias) {
            $query->from($tables, true);
        }

        if (!$hadAlias) {
            return $query;
        }

        return $this->_removeAliasesFromConditions($query);
    }

    /**
     * Apply translation steps to update queries.
     *
     * Chops out aliases on update query conditions as not all database dialects do support
     * aliases in update queries.
     *
     * Just like for delete queries, joins are currently not supported for update queries.
     *
     * @param \Cake\Database\Query\UpdateQuery $query The query to translate
     * @return \Cake\Database\Query\UpdateQuery The modified query
     */
    protected function _updateQueryTranslator(UpdateQuery $query): UpdateQuery
    {
        return $this->_removeAliasesFromConditions($query);
    }

    /**
     * Removes aliases from the `WHERE` clause of a query.
     *
     * @param \Cake\Database\Query\UpdateQuery|\Cake\Database\Query\DeleteQuery $query The query to process.
     * @return \Cake\Database\Query\UpdateQuery|\Cake\Database\Query\DeleteQuery The modified query.
     * @throws \Cake\Database\Exception\DatabaseException In case the processed query contains any joins, as removing
     *  aliases from the conditions can break references to the joined tables.
     * @template T of \Cake\Database\Query\UpdateQuery|\Cake\Database\Query\DeleteQuery
     * @psalm-param T $query
     * @psalm-return T
     */
    protected function _removeAliasesFromConditions(UpdateQuery|DeleteQuery $query): UpdateQuery|DeleteQuery
    {
        if ($query->clause('join')) {
            throw new DatabaseException(
                'Aliases are being removed from conditions for UPDATE/DELETE queries, ' .
                'this can break references to joined tables.'
            );
        }

        $conditions = $query->clause('where');
        assert($conditions === null || $conditions instanceof ExpressionInterface);
        if ($conditions) {
            $conditions->traverse(function ($expression) {
                if ($expression instanceof ComparisonExpression) {
                    $field = $expression->getField();
                    if (
                        is_string($field) &&
                        str_contains($field, '.')
                    ) {
                        [, $unaliasedField] = explode('.', $field, 2);
                        $expression->setField($unaliasedField);
                    }

                    return $expression;
                }

                if ($expression instanceof IdentifierExpression) {
                    $identifier = $expression->getIdentifier();
                    if (str_contains($identifier, '.')) {
                        [, $unaliasedIdentifier] = explode('.', $identifier, 2);
                        $expression->setIdentifier($unaliasedIdentifier);
                    }

                    return $expression;
                }

                return $expression;
            });
        }

        return $query;
    }

    /**
     * Apply translation steps to insert queries.
     *
     * @param \Cake\Database\Query\InsertQuery $query The query to translate
     * @return \Cake\Database\Query\InsertQuery The modified query
     */
    protected function _insertQueryTranslator(InsertQuery $query): InsertQuery
    {
        return $query;
    }

    /**
     * Get the schema dialect.
     *
     * Used by {@link \Cake\Database\Schema} package to reflect schema and
     * generate schema.
     *
     * If all the tables that use this Driver specify their
     * own schemas, then this may return null.
     *
     * @return \Cake\Database\Schema\SchemaDialect
     */
    abstract public function schemaDialect(): SchemaDialect;

    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words
     *
     * @param string $identifier The identifier to quote.
     * @return string
     */
    public function quoteIdentifier(string $identifier): string
    {
        return $this->quoter()->quoteIdentifier($identifier);
    }

    /**
     * Get identifier quoter instance.
     *
     * @return \Cake\Database\IdentifierQuoter
     */
    public function quoter(): IdentifierQuoter
    {
        return $this->quoter ??= new IdentifierQuoter($this->_startQuote, $this->_endQuote);
    }

    /**
     * Escapes values for use in schema definitions.
     *
     * @param mixed $value The value to escape.
     * @return string String for use in schema definitions.
     */
    public function schemaValue(mixed $value): string
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
                !str_starts_with($value, '0') &&
                !str_contains($value, 'e')
            )
        ) {
            return (string)$value;
        }

        return $this->getPdo()->quote((string)$value, PDO::PARAM_STR);
    }

    /**
     * Returns the schema name that's being used.
     *
     * @return string
     */
    public function schema(): string
    {
        return $this->_config['schema'];
    }

    /**
     * Returns last id generated for a table or sequence in database.
     *
     * @param string|null $table table name or sequence to get last insert value from.
     * @return string
     */
    public function lastInsertId(?string $table = null): string
    {
        return (string)$this->getPdo()->lastInsertId($table);
    }

    /**
     * Checks whether the driver is connected.
     *
     * @return bool
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
     * Sets whether this driver should automatically quote identifiers
     * in queries.
     *
     * @param bool $enable Whether to enable auto quoting
     * @return $this
     */
    public function enableAutoQuoting(bool $enable = true)
    {
        $this->_autoQuoting = $enable;

        return $this;
    }

    /**
     * Disable auto quoting of identifiers in queries.
     *
     * @return $this
     */
    public function disableAutoQuoting()
    {
        $this->_autoQuoting = false;

        return $this;
    }

    /**
     * Returns whether this driver should automatically quote identifiers
     * in queries.
     *
     * @return bool
     */
    public function isAutoQuotingEnabled(): bool
    {
        return $this->_autoQuoting;
    }

    /**
     * Returns whether the driver supports the feature.
     *
     * Should return false for unknown features.
     *
     * @param \Cake\Database\DriverFeatureEnum $feature Driver feature
     * @return bool
     */
    abstract public function supports(DriverFeatureEnum $feature): bool;

    /**
     * Transforms the passed query to this Driver's dialect and returns an instance
     * of the transformed query and the full compiled SQL string.
     *
     * @param \Cake\Database\Query $query The query to compile.
     * @param \Cake\Database\ValueBinder $binder The value binder to use.
     * @return string The compiled SQL.
     */
    public function compileQuery(Query $query, ValueBinder $binder): string
    {
        $processor = $this->newCompiler();
        $query = $this->transformQuery($query);

        return $processor->compile($query, $binder);
    }

    /**
     * @return \Cake\Database\QueryCompiler
     */
    public function newCompiler(): QueryCompiler
    {
        return new QueryCompiler();
    }

    /**
     * Constructs new TableSchema.
     *
     * @param string $table The table name.
     * @param array $columns The list of columns for the schema.
     * @return \Cake\Database\Schema\TableSchemaInterface
     */
    public function newTableSchema(string $table, array $columns = []): TableSchemaInterface
    {
        /** @var class-string<\Cake\Database\Schema\TableSchemaInterface> $className */
        $className = $this->_config['tableSchema'] ?? TableSchema::class;

        return new $className($table, $columns);
    }

    /**
     * Returns the maximum alias length allowed.
     *
     * This can be different from the maximum identifier length for columns.
     *
     * @return int|null Maximum alias length or null if no limit
     */
    public function getMaxAliasLength(): ?int
    {
        return static::MAX_ALIAS_LENGTH;
    }

    /**
     * Get the logger instance.
     *
     * @return \Psr\Log\LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Create logger instance.
     *
     * @param string|null $className Logger's class name
     * @return \Psr\Log\LoggerInterface
     */
    protected function createLogger(?string $className): LoggerInterface
    {
        $className ??= QueryLogger::class;

        /** @var class-string<\Psr\Log\LoggerInterface>|null $className */
        $className = App::className($className, 'Cake/Log', 'Log');
        if ($className === null) {
            throw new CakeException(
                'For logging you must either set the `log` config to a FQCN which implemnts Psr\Log\LoggerInterface' .
                ' or require the cakephp/log package in your composer config.'
            );
        }

        return new $className();
    }

    /**
     * Logs a message or query using the configured logger object.
     *
     * @param \Stringable|string $message Message string or query.
     * @param array $context Logging context.
     * @return bool True if message was logged.
     */
    public function log(Stringable|string $message, array $context = []): bool
    {
        if ($this->logger === null) {
            return false;
        }

        $context['query'] = $message;
        $loggedQuery = new LoggedQuery();
        $loggedQuery->setContext($context);

        $this->logger->debug((string)$loggedQuery, ['query' => $loggedQuery]);

        return true;
    }

    /**
     * Returns the connection role this driver performs.
     *
     * @return string
     */
    public function getRole(): string
    {
        return $this->_config['_role'] ?? Connection::ROLE_WRITE;
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
            'role' => $this->getRole(),
        ];
    }
}
