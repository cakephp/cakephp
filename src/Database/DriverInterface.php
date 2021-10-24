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
use Cake\Database\Schema\TableSchema;
use Closure;

/**
 * Interface for database driver.
 *
 * @method int|null getMaxAliasLength() Returns the maximum alias length allowed.
 * @method int getConnectRetries() Returns the number of connection retry attempts made.
 * @method bool supports(string $feature) Checks whether a feature is supported by the driver.
 * @method bool inTransaction() Returns whether a transaction is active.
 */
interface DriverInterface
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
     * @return bool True on success, false on failure.
     */
    public function connect(): bool;

    /**
     * Disconnects from database server.
     *
     * @return void
     */
    public function disconnect(): void;

    /**
     * Returns correct connection resource or object that is internally used.
     *
     * @return object Connection object used internally.
     */
    public function getConnection();

    /**
     * Set the internal connection object.
     *
     * @param object $connection The connection instance.
     * @return $this
     */
    public function setConnection($connection);

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
    public function prepare($query): StatementInterface;

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
     * Get the SQL for releasing a save point.
     *
     * @param string|int $name Save point name or id
     * @return string
     */
    public function releaseSavePointSQL($name): string;

    /**
     * Get the SQL for creating a save point.
     *
     * @param string|int $name Save point name or id
     * @return string
     */
    public function savePointSQL($name): string;

    /**
     * Get the SQL for rollingback a save point.
     *
     * @param string|int $name Save point name or id
     * @return string
     */
    public function rollbackSavePointSQL($name): string;

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
     * Returns whether the driver supports adding or dropping constraints
     * to already created tables.
     *
     * @return bool True if driver supports dynamic constraints.
     * @deprecated 4.3.0 Fixtures no longer dynamically drop and create constraints.
     */
    public function supportsDynamicConstraints(): bool;

    /**
     * Returns whether this driver supports save points for nested transactions.
     *
     * @return bool True if save points are supported, false otherwise.
     * @deprecated 4.3.0 Use `supports(DriverInterface::FEATURE_SAVEPOINT)` instead
     */
    public function supportsSavePoints(): bool;

    /**
     * Returns a value in a safe representation to be used in a query string
     *
     * @param mixed $value The value to quote.
     * @param int $type Must be one of the \PDO::PARAM_* constants
     * @return string
     */
    public function quote($value, $type): string;

    /**
     * Checks if the driver supports quoting.
     *
     * @return bool
     * @deprecated 4.3.0 Use `supports(DriverInterface::FEATURE_QUOTE)` instead
     */
    public function supportsQuoting(): bool;

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
    public function schemaValue($value): string;

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
     * @param string|null $column the name of the column representing the primary key.
     * @return string|int
     */
    public function lastInsertId(?string $table = null, ?string $column = null);

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
     * Returns an instance of a QueryCompiler.
     *
     * @return \Cake\Database\QueryCompiler
     */
    public function newCompiler(): QueryCompiler;

    /**
     * Constructs new TableSchema.
     *
     * @param string $table The table name.
     * @param array $columns The list of columns for the schema.
     * @return \Cake\Database\Schema\TableSchema
     */
    public function newTableSchema(string $table, array $columns = []): TableSchema;
}
