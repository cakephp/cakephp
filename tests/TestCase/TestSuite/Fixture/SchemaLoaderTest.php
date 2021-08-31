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
use Cake\TestSuite\Fixture\SchemaLoader;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

class SchemaLoaderTest extends TestCase
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
        (new SchemaCleaner())->dropTables('test', ['schema_loader_test_one', 'schema_loader_test_two']);
    }

    public function testLoadingFiles(): void
    {
        $connection = ConnectionManager::get('test');

        $schemaFiles[] = $this->createSchemaFile('schema_loader_test_one');
        $schemaFiles[] = $this->createSchemaFile('schema_loader_test_two');

        (new SchemaLoader())->loadFiles($schemaFiles, 'test', false, false);

        $connection = ConnectionManager::get('test');
        $tables = $connection->getSchemaCollection()->listTables();
        $this->assertContains('schema_loader_test_one', $tables);
        $this->assertContains('schema_loader_test_two', $tables);
    }

    public function testLoadMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SchemaLoader())->loadFiles('missing_schema_file.sql', 'test', false, false);
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
