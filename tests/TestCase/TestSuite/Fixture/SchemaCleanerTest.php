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
use Cake\TestSuite\TestCase;

class SchemaCleanerTest extends TestCase
{
    public function testForeignKeyConstruction()
    {
        $connection = ConnectionManager::get('test');

        [$table] = $this->createSchemas();

        $this->assertTestTableExistsWithCount($table, 1);

        $exceptionThrown = false;
        try {
            $connection->delete($table);
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

        [$table1, $table2] = $this->createSchemas();

        // Assert that the schema was created
        $this->assertTestTableExistsWithCount($table1, 1);
        $this->assertTestTableExistsWithCount($table2, 1);

        // Drop the schema
        (new SchemaCleaner())->dropTables('test', [$table1, $table2]);

        // Assert that the tables created were dropped
        [$sql, $params] = $dialect->listTablesSql($connection->config());
        $tables = $connection->execute($sql, $params)->count();
        $this->assertSame($initialNumberOfTables, $tables, 'The test tables should be dropped.');
    }

    public function testTruncateSchema()
    {
        [$table1, $table2] = $this->createSchemas();

        $this->assertTestTableExistsWithCount($table1, 1);
        $this->assertTestTableExistsWithCount($table2, 1);

        (new SchemaCleaner())->truncateTables('test');

        $this->assertTestTableExistsWithCount($table1, 0);
        $this->assertTestTableExistsWithCount($table2, 0);
    }

    private function assertTestTableExistsWithCount(string $table, int $count)
    {
        $this->assertSame(
            $count,
            ConnectionManager::get('test')->newQuery()->select('id')->from($table)->execute()->count()
        );
    }

    private function createSchemas()
    {
        $table1 = 'test_table_' . rand();
        $table2 = 'test_table_' . rand();

        $connection = ConnectionManager::get('test');

        $schema = new TableSchema($table1);
        $schema
            ->addColumn('id', 'integer')
            ->addColumn('name', 'string')
            ->addConstraint($table1 . '_primary', [
                'type' => TableSchema::CONSTRAINT_PRIMARY,
                'columns' => ['id'],
            ]);

        $queries = $schema->createSql($connection);
        foreach ($queries as $sql) {
            $connection->execute($sql);
        }

        $schema = new TableSchema($table2);
        $schema
            ->addColumn('id', 'integer')
            ->addColumn('name', 'string')
            ->addColumn('table1_id', 'integer')
            ->addConstraint($table2 . '_primary', [
                'type' => TableSchema::CONSTRAINT_PRIMARY,
                'columns' => ['id'],
            ])
            ->addConstraint($table2 . '_foreign_key', [
                'columns' => ['table1_id'],
                'type' => TableSchema::CONSTRAINT_FOREIGN,
                'references' => [$table1, 'id', ],
            ]);

        $queries = $schema->createSql($connection);

        foreach ($queries as $sql) {
            $connection->execute($sql);
        }

        $connection->insert($table1, ['name' => 'foo']);

        $id = $connection->newQuery()->select('id')->from($table1)->limit(1)->execute()->fetch()[0];
        $connection->insert($table2, ['name' => 'foo', 'table1_id' => $id]);

        $connection->execute($connection->getDriver()->enableForeignKeySQL());

        return [$table1, $table2];
    }
}
