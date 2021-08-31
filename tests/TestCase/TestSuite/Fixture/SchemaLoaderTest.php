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

use Cake\Console\ConsoleIo;
use Cake\Database\Connection;
use Cake\Database\DriverInterface;
use Cake\Database\Schema\Collection;
use Cake\Database\Schema\SchemaDialect;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\SchemaCleaner;
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

    public function setUp(): void
    {
        parent::setUp();
        $this->restore = $GLOBALS['__PHPUNIT_BOOTSTRAP'];
        unset($GLOBALS['__PHPUNIT_BOOTSTRAP']);

        $this->loader = new SchemaLoader(['outputLevel' => ConsoleIo::QUIET]);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $GLOBALS['__PHPUNIT_BOOTSTRAP'] = $this->restore;

        (new SchemaCleaner())->dropTables('test', ['schema_loader_test_one', 'schema_loader_test_two']);
        ConnectionManager::drop('schema_test');
    }

    /**
     * Tests loading schema files.
     */
    public function testLoadingFiles(): void
    {
        $connection = ConnectionManager::get('test');

        $schemaFiles[] = $this->createSchemaFile('schema_loader_test_one');
        $schemaFiles[] = $this->createSchemaFile('schema_loader_test_two');

        $this->loader->loadFiles($schemaFiles, 'test', false, false);

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
        $this->loader->loadFiles('missing_schema_file.sql', 'test', false, false);
    }

    /**
     * Tests dropping and truncating tables during schema load.
     */
    public function testDropTruncateTables(): void
    {
        $connection = $this->createMock(Connection::class);

        $tableSchema = $this->createMock(TableSchema::class);
        $schemaDialect = $this->createMock(SchemaDialect::class);
        $schemaDialect
            ->expects($this->atLeastOnce())->method('dropConstraintSql')->with($tableSchema)->willReturn(['']);
        $schemaDialect
            ->expects($this->atLeastOnce())->method('dropTableSql')->with($tableSchema)->willReturn(['']);
        $schemaDialect
            ->expects($this->atLeastOnce())->method('truncateTableSql')->with($tableSchema)->willReturn(['']);
        $driver = $this->createMock(DriverInterface::class);
        $driver
            ->expects($this->atLeastOnce())->method('schemaDialect')->willReturn($schemaDialect);
        $connection
            ->expects($this->atLeastOnce())->method('getDriver')->willReturn($driver);

        $schemaCollection = $this->createMock(Collection::class);
        $schemaCollection
            ->expects($this->atLeastOnce())->method('listTables')->willReturn(['schema_test']);
        $schemaCollection
            ->expects($this->atLeastOnce())->method('describe')->willReturn($tableSchema);
        $connection
            ->expects($this->atLeastOnce())->method('getSchemaCollection')->willReturn($schemaCollection);

        ConnectionManager::setConfig('schema_test', $connection);
        $this->loader->loadFiles([], 'schema_test', true, true);
    }

    protected function createSchemaFile(string $tableName): string
    {
        $connection = ConnectionManager::get('test');

        $schema = new TableSchema($tableName);
        $schema
            ->addColumn('id', 'integer')
            ->addColumn('name', 'string');

        $query = $schema->createSql($connection)[0];
        $tmpFile = tempnam(sys_get_temp_dir(), 'SchemaLoaderTest');
        file_put_contents($tmpFile, $query);

        return $tmpFile;
    }
}
