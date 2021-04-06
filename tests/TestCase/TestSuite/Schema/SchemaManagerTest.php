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
namespace Cake\Test\TestCase\TestSuite\Schema;

use Cake\Database\Schema\TableSchema;
use Cake\Database\StatementInterface;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Schema\SchemaCleaner;
use Cake\TestSuite\Schema\SchemaManager;
use Cake\TestSuite\TestCase;

class SchemaManagerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        SchemaCleaner::drop('test');
    }

    public function testCreateFromOneFile()
    {
        $tableName = 'test_table';
        $file = $this->createSchemaFile($tableName);

        SchemaManager::create('test', $file);

        $this->assertSame([[$tableName]], $this->listTables());
    }

    public function testCreateFromMultipleFiles()
    {
        $tableName1 = 'test_table';
        $tableName2 = 'test_table_2';
        $tableName3 = 'test_table_3';
        $file1 = $this->createSchemaFile($tableName1);
        $file2 = $this->createSchemaFile($tableName2);
        $file3 = $this->createSchemaFile($tableName3);

        SchemaManager::create('test', [$file1, $file2, $file3]);

        $this->assertSame([[$tableName1], [$tableName2], [$tableName3],], array_values($this->listTables()));
    }

    public function testCreateFromNonExistentFile()
    {
        $this->expectException(\RuntimeException::class);
        SchemaManager::create('test', 'foo');
    }

    public function testCreateFromCorruptedFile()
    {
        $query = 'This is no valid SQL';
        $tmpFile = tempnam(sys_get_temp_dir(), 'SchemaManagerTest');
        file_put_contents($tmpFile, $query);

        $this->expectException(\RuntimeException::class);
        SchemaManager::create('test', $tmpFile);
    }

    private function createSchemaFile(string $tableName): string
    {
        $connection = ConnectionManager::get('test');

        $schema = new TableSchema($tableName);
        $schema
            ->addColumn('id', 'integer')
            ->addColumn('name', 'string');

        $query = $schema->createSql($connection)[0];
        $tmpFile = tempnam(sys_get_temp_dir(), 'SchemaManagerTest');
        file_put_contents($tmpFile, $query);

        return $tmpFile;
    }

    private function listTables(): array
    {
        $connection = ConnectionManager::get('test');
        /** @var SchemaDialect $dialect */
        $dialect = $connection->getDriver()->schemaDialect();

        $stmt = $dialect->listTablesSql($connection->config());

        return $connection->execute($stmt[0])->fetchAll(StatementInterface::FETCH_TYPE_NUM);
    }
}
