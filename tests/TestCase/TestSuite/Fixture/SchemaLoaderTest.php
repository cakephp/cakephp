<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite\Fixture;

use Cake\Database\Connection;
use Cake\Database\Driver;
use Cake\Database\Driver\Sqlite;
use Cake\Database\DriverFeatureEnum;
use Cake\Database\Schema\CheckConstraint;
use Cake\Database\Schema\ForeignKey;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\ConnectionHelper;
use Cake\TestSuite\Fixture\SchemaLoader;
use Cake\TestSuite\TestCase;
use Closure;
use InvalidArgumentException;
use Psr\Log\AbstractLogger;
use Stringable;

class SchemaLoaderTest extends TestCase
{
    /**
     * @var bool|null
     */
    protected $restore;

    /**
     * @var \Cake\TestSuite\Fixture\SchemaLoader
     */
    protected $loader;

    protected $truncateDbFile = TMP . 'schema_loader_test.sqlite';

    protected function setUp(): void
    {
        parent::setUp();

        if (isset($GLOBALS['__PHPUNIT_BOOTSTRAP'])) {
            $this->restore = $GLOBALS['__PHPUNIT_BOOTSTRAP'];
            unset($GLOBALS['__PHPUNIT_BOOTSTRAP']);
        }

        $this->loader = new SchemaLoader();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->restore !== null) {
            $GLOBALS['__PHPUNIT_BOOTSTRAP'] = $this->restore;
        }

        ConnectionHelper::dropTables('test', ['schema_loader_test_one', 'schema_loader_test_two']);
        ConnectionManager::drop('test_schema_loader');

        if (file_exists($this->truncateDbFile)) {
            unlink($this->truncateDbFile);
        }
    }

    /**
     * Tests loading schema files.
     */
    public function testLoadSqlFiles(): void
    {
        $connection = ConnectionManager::get('test');

        $schemaFiles[] = $this->createSchemaFile('schema_loader_test_one');
        $schemaFiles[] = $this->createSchemaFile('schema_loader_test_two');

        $this->loader->loadSqlFiles($schemaFiles, 'test', false, false);

        $connection = ConnectionManager::get('test');
        $tables = $connection->getSchemaCollection()->listTables();
        $this->assertContains('schema_loader_test_one', $tables);
        $this->assertContains('schema_loader_test_two', $tables);
    }

    /**
     * Tests loading missing files.
     */
    public function testLoadMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->loader->loadSqlFiles('missing_schema_file.sql', 'test', false, false);
    }

    /**
     * Tests dropping and truncating tables during schema load.
     */
    public function testDropTruncateTables(): void
    {
        $this->skipIf(!extension_loaded('pdo_sqlite'), 'Skipping as SQLite extension is missing');
        ConnectionManager::setConfig('test_schema_loader', [
            'className' => Connection::class,
            'driver' => Sqlite::class,
            'database' => $this->truncateDbFile,
        ]);

        $schemaFile = $this->createSchemaFile('schema_loader_first');
        $this->loader->loadSqlFiles($schemaFile, 'test_schema_loader', true, true);
        $connection = ConnectionManager::get('test_schema_loader');

        $result = $connection->getSchemaCollection()->listTables();
        $this->assertEquals(['schema_loader_first'], $result);

        $schemaFile = $this->createSchemaFile('schema_loader_second');
        $this->loader->loadSqlFiles($schemaFile, 'test_schema_loader', true, true);

        $result = $connection->getSchemaCollection()->listTables();
        $this->assertEquals(['schema_loader_second'], $result);

        $statement = $connection->execute('SELECT * FROM schema_loader_second');
        $result = $statement->fetchAll();
        $this->assertCount(0, $result, 'Table should be empty.');
    }

    /**
     * loadInternalFile() must disable constraints via
     * ConnectionHelper::runWithoutConstraints() rather than calling
     * Connection::disableConstraints() directly, so that drivers requiring
     * a transaction wrapper (e.g. Postgres) don't emit a warning.
     *
     * Runs against the real `test` connection so that this is only
     * meaningfully exercised on drivers that actually require the wrapper
     * (Postgres), rather than simulating one. On other drivers this test is
     * skipped in favor of testLoadInternalFileSkipsTransactionForDriversThatSupportIt().
     *
     * @link https://github.com/cakephp/cakephp/issues/19474
     */
    public function testLoadInternalFileWrapsConstraintDisablingInTransactionForDriversThatRequireIt(): void
    {
        $connection = ConnectionManager::get('test');
        assert($connection instanceof Connection);
        $driver = $connection->getWriteDriver();

        $this->skipIf(
            $driver->supports(DriverFeatureEnum::DISABLE_CONSTRAINT_WITHOUT_TRANSACTION),
            'This driver supports disabling constraints without a transaction.',
        );

        try {
            $queries = $this->captureQueries($driver, function (): void {
                $this->loader->loadInternalFile(__DIR__ . '/test_schema.php', 'test');
            });

            $tables = $connection->getSchemaCollection()->listTables();
            $this->assertContains('schema_generator', $tables);
            $this->assertSame(
                ['BEGIN', $driver->disableForeignKeySQL(), $driver->enableForeignKeySQL(), 'COMMIT'],
                $this->filterQueries($queries, $driver),
                'Constraint disabling must be wrapped in a transaction for drivers that require it.',
            );
        } finally {
            ConnectionHelper::dropTables('test', ['schema_generator', 'schema_generator_comment']);
        }
    }

    /**
     * Runs against the real `test` connection; only meaningful for drivers
     * that support disabling constraints without a transaction. On drivers
     * that require one (Postgres), this test is skipped in favor of
     * testLoadInternalFileWrapsConstraintDisablingInTransactionForDriversThatRequireIt().
     */
    public function testLoadInternalFileSkipsTransactionForDriversThatSupportIt(): void
    {
        $connection = ConnectionManager::get('test');
        assert($connection instanceof Connection);
        $driver = $connection->getWriteDriver();

        $this->skipIf(
            !$driver->supports(DriverFeatureEnum::DISABLE_CONSTRAINT_WITHOUT_TRANSACTION),
            'This driver requires a transaction to disable constraints.',
        );

        try {
            $queries = $this->captureQueries($driver, function (): void {
                $this->loader->loadInternalFile(__DIR__ . '/test_schema.php', 'test');
            });

            $tables = $connection->getSchemaCollection()->listTables();
            $this->assertContains('schema_generator', $tables);
            $this->assertSame(
                [$driver->disableForeignKeySQL(), $driver->enableForeignKeySQL()],
                $this->filterQueries($queries, $driver),
                'No transaction should be started for drivers that support disabling constraints directly.',
            );
        } finally {
            ConnectionHelper::dropTables('test', ['schema_generator', 'schema_generator_comment']);
        }
    }

    public function testLoadInternalFiles(): void
    {
        $this->skipIf(!extension_loaded('pdo_sqlite'), 'Skipping as SQLite extension is missing');
        ConnectionManager::setConfig('test_schema_loader', [
            'className' => Connection::class,
            'driver' => Sqlite::class,
            'database' => $this->truncateDbFile,
        ]);

        $this->loader->loadInternalFile(__DIR__ . '/test_schema.php', 'test_schema_loader');

        $connection = ConnectionManager::get('test_schema_loader');
        /** @var \Cake\Database\Schema\Collection $schema */
        $schema = $connection->getSchemaCollection();
        $tables = $schema->listTables();
        $this->assertContains('schema_generator', $tables);
        $this->assertContains('schema_generator_comment', $tables);

        $table = $schema->describe('schema_generator');

        $constraint = $table->constraint('checked_relation_id');
        assert($constraint instanceof CheckConstraint);
        $this->assertEquals('checked_relation_id', $constraint->getName());
        $this->assertEquals('relation_id > 1', $constraint->getExpression());

        $key = $table->constraint('relation_fk');
        assert($key instanceof ForeignKey);
        $this->assertEquals('relation_fk', $key->getName());
        $this->assertEquals(['relation_id'], $key->getColumns());
    }

    protected function createSchemaFile(string $tableName): string
    {
        $connection = ConnectionManager::get('test');

        $schema = new TableSchema($tableName);
        $schema
            ->addColumn('id', 'integer')
            ->addColumn('name', 'string');

        $query = $schema->createSql($connection)[0] . ';';
        $query .= "\nINSERT INTO {$tableName} (id, name) VALUES (1, 'testing');";
        $tmpFile = tempnam(sys_get_temp_dir(), 'SchemaLoaderTest');
        file_put_contents($tmpFile, $query);

        return $tmpFile;
    }

    /**
     * Runs $callback while capturing the SQL statements $driver logs, in order.
     *
     * @return array<string>
     */
    private function captureQueries(Driver $driver, Closure $callback): array
    {
        $logger = new class extends AbstractLogger {
            /**
             * @var array<string>
             */
            public array $queries = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->queries[] = (string)$message;
            }
        };
        $driver->setLogger($logger);

        try {
            $callback();
        } finally {
            $driver->disableQueryLogging();
        }

        return $logger->queries;
    }

    /**
     * Narrows a captured query log down to the statements relevant to
     * constraint disabling and transaction boundaries, preserving order.
     *
     * @param array<string> $queries
     * @return array<string>
     */
    private function filterQueries(array $queries, Driver $driver): array
    {
        $relevant = ['BEGIN', 'COMMIT', $driver->disableForeignKeySQL(), $driver->enableForeignKeySQL()];

        return array_values(array_filter(
            $queries,
            fn(string $query): bool => in_array($query, $relevant, true),
        ));
    }
}
