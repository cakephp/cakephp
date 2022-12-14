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
use Cake\Database\Driver\Sqlite;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\ConnectionHelper;
use Cake\TestSuite\Fixture\SchemaLoader;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

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

    public function setUp(): void
    {
        parent::setUp();
        $this->restore = $GLOBALS['__PHPUNIT_BOOTSTRAP'];
        unset($GLOBALS['__PHPUNIT_BOOTSTRAP']);

        $this->loader = new SchemaLoader();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $GLOBALS['__PHPUNIT_BOOTSTRAP'] = $this->restore;

        (new ConnectionHelper())->dropTables('test', ['schema_loader_test_one', 'schema_loader_test_two']);
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
        $tables = $connection->getSchemaCollection()->listTables();
        $this->assertContains('schema_generator', $tables);
        $this->assertContains('schema_generator_comment', $tables);
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
}
