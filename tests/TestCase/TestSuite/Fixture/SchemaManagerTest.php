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

use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\SchemaCleaner;
use Cake\TestSuite\Fixture\SchemaManager;
use Cake\TestSuite\TestCase;
use RuntimeException;

class SchemaManagerTest extends TestCase
{
    /**
     * @var bool|null
     */
    protected $restore;

    public function setUp(): void
    {
        parent::setUp();
        $this->restore = $GLOBALS['__PHPUNIT_BOOTSTRAP'];
        unset($GLOBALS['__PHPUNIT_BOOTSTRAP']);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $GLOBALS['__PHPUNIT_BOOTSTRAP'] = $this->restore;
    }

    public function testCreateFromOneFile(): void
    {
        $connection = ConnectionManager::get('test');

        $tableName = 'test_table_' . rand();
        (new SchemaCleaner())->dropTables('test', [$tableName]);

        $file = $this->createSchemaFile($tableName);

        SchemaManager::create('test', $file, false, false);

        // Assert that the test table was created
        $this->assertSame(
            0,
            $connection->newQuery()->select('id')->from($tableName)->execute()->count()
        );

        // Cleanup
        (new SchemaCleaner())->dropTables('test', [$tableName]);
    }

    public function testCreateFromMultipleFiles(): void
    {
        $connection = ConnectionManager::get('test');
        $tables = [
            'test_table_' . rand(),
            'test_table_' . rand(),
            'test_table_' . rand(),
        ];

        (new SchemaCleaner())->dropTables('test', $tables);

        $files = [];
        foreach ($tables as $table) {
            $files[] = $this->createSchemaFile($table);
        }

        SchemaManager::create('test', $files, false, false);

        // Assert that all test tables were created
        foreach ($tables as $table) {
            $this->assertSame(
                0,
                $connection->newQuery()->select('id')->from($table)->execute()->count()
            );
        }

        // Cleanup
        (new SchemaCleaner())->dropTables('test', $tables);
    }

    public function testCreateFromNonExistentFile(): void
    {
        $this->expectException(RuntimeException::class);
        SchemaManager::create('test', 'foo');
    }

    public function testCreateFromCorruptedFile(): void
    {
        $query = 'This is no valid SQL';
        $tmpFile = tempnam(sys_get_temp_dir(), 'SchemaManagerTest');
        file_put_contents($tmpFile, $query);

        $this->expectException(RuntimeException::class);
        SchemaManager::create('test', $tmpFile, false, false);
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
}
