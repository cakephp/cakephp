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
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Schema\SchemaCleaner;
use Cake\TestSuite\TestCase;

class SchemaCleanerTest extends TestCase
{
    public function testForeignKeyConstruction()
    {
        $connection = ConnectionManager::get('test');

        $this->createSchemas();

        $this->assertTestTablesExistWithCount(1);

        $exceptionThrown = false;
        try {
            $connection->delete('test_table');
        } catch (\Throwable $e) {
            $exceptionThrown = true;
        } finally {
            $this->assertTrue($exceptionThrown);
        }

        // Cleanup
        (new SchemaCleaner())->dropTables('test', ['test_table','test_table2']);
    }

    public function testDropSchema()
    {
        $connection = ConnectionManager::get('test');
        /** @var SchemaDialect $dialect */
        $dialect = $connection->getDriver()->schemaDialect();
        [$sql, $params] = $dialect->listTablesSql($connection->config());
        $initialNumberOfTables = $connection->execute($sql, $params)->count();

        $this->createSchemas();

        // Assert that the schema is not empty
        $this->assertTestTablesExistWithCount(1);

        // Drop the schema
        (new SchemaCleaner())->dropTables('test', ['test_table','test_table2']);

        // Schema is empty
        [$sql, $params] = $dialect->listTablesSql($connection->config());
        $tables = $connection->execute($sql, $params)->count();
        $this->assertSame($initialNumberOfTables, $tables, 'The test tables should be dropped.');
    }

    public function testTruncateSchema()
    {
        $this->createSchemas();

        $this->assertTestTablesExistWithCount(1);

        (new SchemaCleaner())->truncateTables('test');

        $this->assertTestTablesExistWithCount(0);
    }

    private function assertTestTablesExistWithCount(int $count)
    {
        $connection = ConnectionManager::get('test');

        $this->assertSame(
            $count,
            $connection->newQuery()->select('id')->from('test_table')->execute()->count()
        );
        $this->assertSame(
            $count,
            $connection->newQuery()->select('id')->from('test_table2')->execute()->count()
        );
    }

    private function createSchemas()
    {
        $schemaCleaner = new SchemaCleaner();
        $schemaCleaner->dropTables('test', ['test_table', 'test_table2']);

        $connection = ConnectionManager::get('test');

        $schema = new TableSchema('test_table');
        $schema
            ->addColumn('id', 'integer')
            ->addColumn('name', 'string')
            ->addConstraint('primary', [
                'type' => TableSchema::CONSTRAINT_PRIMARY,
                'columns' => ['id'],
            ]);

        $queries = $schema->createSql($connection);
        foreach ($queries as $sql) {
            $connection->execute($sql);
        }

        $schema = new TableSchema('test_table2');
        $schema
            ->addColumn('id', 'integer')
            ->addColumn('name', 'string')
            ->addColumn('fk_id', 'integer')
            ->addConstraint('primary', [
                'type' => TableSchema::CONSTRAINT_PRIMARY,
                'columns' => ['id'],
            ])
            ->addConstraint('foreign_key', [
                'columns' => ['fk_id'],
                'type' => TableSchema::CONSTRAINT_FOREIGN,
                'references' => ['test_table', 'id', ],
            ]);

        $queries = $schema->createSql($connection);

        foreach ($queries as $sql) {
            $connection->execute($sql);
        }

        $connection->insert('test_table', ['name' => 'foo']);

        $id = $connection->newQuery()->select('id')->from('test_table')->limit(1)->execute()->fetch()[0];
        $connection->insert('test_table2', ['name' => 'foo', 'fk_id' => $id]);

        $connection->execute($connection->getDriver()->enableForeignKeySQL());
    }
}
