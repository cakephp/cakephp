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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use Cake\Database\Schema\SchemaDialect;
use Cake\Database\Schema\TableSchemaInterface;
use Closure;
use Psr\Log\LoggerAwareInterface;
use Stringable;

/**
 * Interface for database driver.
 */
interface DriverInterface extends LoggerAwareInterface
{
    /**
     * Common Table Expressions (with clause) support.
     *
     * @var string
     */
    public const FEATURE_CTE = 'cte';

    /**
     * Disabling constraints without being in transaction support.
     *
     * @var string
     */
    public const FEATURE_DISABLE_CONSTRAINT_WITHOUT_TRANSACTION = 'disable-constraint-without-transaction';

    /**
     * Native JSON data type support.
     *
     * @var string
     */
    public const FEATURE_JSON = 'json';

    /**
     * PDO::quote() support.
     *
     * @var string
     */
    public const FEATURE_QUOTE = 'quote';

    /**
     * Transaction savepoint support.
     *
     * @var string
     */
    public const FEATURE_SAVEPOINT = 'savepoint';

    /**
     * Truncate with foreign keys attached support.
     *
     * @var string
     */
    public const FEATURE_TRUNCATE_WITH_CONSTRAINTS = 'truncate-with-constraints';

    /**
     * Window function support (all or partial clauses).
     *
     * @var string
     */
    public const FEATURE_WINDOW = 'window';

    /**
     * Establishes a connection to the database server.
     *
     * @throws \Cake\Database\Exception\MissingConnectionException If database connection could not be established.
     * @return void
     */
    public function connect(): void;

    /**
     * Disconnects from database server.
     *
     * @return void
     */
    public function disconnect(): void;

    /**
     * Returns whether php is able to use this driver for connecting to database.
     *
     * @return bool True if it is valid to use this driver.
     */
    public function enabled(): bool;

    /**
     * Prepares a sql statement to be executed.
     *
     * @param \Cake\Database\Query|string $query The query to turn into a prepared statement.
     * @return \Cake\Database\StatementInterface
     */
    public function prepare(Query|string $query): StatementInterface;

    /**
     * Executes a query using $params for interpolating values and $types as a hint for each
     * those params.
     *
     * @param string $sql SQL to be executed and interpolated with $params
     * @param array $params List or associative array of params to be interpolated in $sql as values.
     * @param array $types List or associative array of types to be used for casting values in query.
     * @return \Cake\Database\StatementInterface Executed statement
     */
    public function execute(string $sql, array $params = [], array $types = []): StatementInterface;

    /**
     * Executes the provided query after compiling it for the specific driver
     * dialect and returns the executed Statement object.
     *
     * @param \Cake\Database\Query $query The query to be executed.
     * @return \Cake\Database\StatementInterface Executed statement
     */
    public function run(Query $query): StatementInterface;

    /**
     * Starts a transaction.
     *
     * @return bool True on success, false otherwise.
     */
    public function beginTransaction(): bool;

    /**
     * Commits a transaction.
     *
     * @return bool True on success, false otherwise.
     */
    public function commitTransaction(): bool;

    /**
     * Rollbacks a transaction.
     *
     * @return bool True on success, false otherwise.
     */
    public function rollbackTransaction(): bool;

    /**
     * Returns whether a transaction is active.
     *
     * @return bool
     */
    public function inTransaction(): bool;

    /**
     * Get the SQL for releasing a save point.
     *
     * @param string|int $name Save point name or id
     * @return string
     */
    public function releaseSavePointSQL(string|int $name): string;

    /**
     * Get the SQL for creating a save point.
     *
     * @param string|int $name Save point name or id
     * @return string
     */
    public function savePointSQL(string|int $name): string;

    /**
     * Get the SQL for rollingback a save point.
     *
     * @param string|int $name Save point name or id
     * @return string
     */
    public function rollbackSavePointSQL(string|int $name): string;

    /**
     * Get the SQL for disabling foreign keys.
     *
     * @return string
     */
    public function disableForeignKeySQL(): string;

    /**
     * Get the SQL for enabling foreign keys.
     *
     * @return string
     */
    public function enableForeignKeySQL(): string;

    /**
     * Returns a value in a safe representation to be used in a query string
     *
     * @param mixed $value The value to quote.
     * @param int $type Must be one of the \PDO::PARAM_* constants
     * @return string
     */
    public function quote(mixed $value, int $type): string;

    /**
     * Returns a callable function that will be used to transform a passed Query object.
     * This function, in turn, will return an instance of a Query object that has been
     * transformed to accommodate any specificities of the SQL dialect in use.
     *
     * @param string $type The type of query to be transformed
     * (select, insert, update, delete).
     * @return \Closure
     */
    public function queryTranslator(string $type): Closure;

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
    public function schemaDialect(): SchemaDialect;

    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words.
     *
     * @param string $identifier The identifier expression to quote.
     * @return string
     */
    public function quoteIdentifier(string $identifier): string;

    /**
     * Escapes values for use in schema definitions.
     *
     * @param mixed $value The value to escape.
     * @return string String for use in schema definitions.
     */
    public function schemaValue(mixed $value): string;

    /**
     * Returns the schema name that's being used.
     *
     * @return string
     */
    public function schema(): string;

    /**
     * Returns last id generated for a table or sequence in database.
     *
     * @param string|null $table table name or sequence to get last insert value from.
     * @return string
     */
    public function lastInsertId(?string $table = null): string;

    /**
     * Checks whether the driver is connected.
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Sets whether this driver should automatically quote identifiers
     * in queries.
     *
     * @param bool $enable Whether to enable auto quoting
     * @return $this
     */
    public function enableAutoQuoting(bool $enable = true);

    /**
     * Disable auto quoting of identifiers in queries.
     *
     * @return $this
     */
    public function disableAutoQuoting();

    /**
     * Returns whether this driver should automatically quote identifiers
     * in queries.
     *
     * @return bool
     */
    public function isAutoQuotingEnabled(): bool;

    /**
     * Returns whether the driver supports the feature.
     *
     * Defaults to true for FEATURE_QUOTE and FEATURE_SAVEPOINT.
     *
     * @param string $feature Driver feature name
     * @return bool
     */
    public function supports(string $feature): bool;

    /**
     * Transforms the passed query to this Driver's dialect and returns an instance
     * of the transformed query and the full compiled SQL string.
     *
     * @param \Cake\Database\Query $query The query to compile.
     * @param \Cake\Database\ValueBinder $binder The value binder to use.
     * @return array containing 2 entries. The first entity is the transformed query
     * and the second one the compiled SQL.
     */
    public function compileQuery(Query $query, ValueBinder $binder): array;

    /**
     * Constructs new TableSchema.
     *
     * @param string $table The table name.
     * @param array $columns The list of columns for the schema.
     * @return \Cake\Database\Schema\TableSchemaInterface
     */
    public function newTableSchema(string $table, array $columns = []): TableSchemaInterface;

    /**
     * Returns the maximum alias length allowed.
     *
     * @return int|null
     */
    public function getMaxAliasLength(): ?int;

    /**
     * Logs a message or query using the configured logger object.
     *
     * @param \Stringable|string $message Message string or query.
     * @param array $context Logging context.
     * @return bool True if message was logged.
     */
    public function log(Stringable|string $message, array $context = []): bool;
}
