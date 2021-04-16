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
        $this->assertSame(
            1,
            $connection->newQuery()->select('id')->from('test_table')->execute()->count()
        );
        $this->assertSame(
            1,
            $connection->newQuery()->select('id')->from('test_table2')->execute()->count()
        );

        $this->expectException(\PDOException::class);
        $connection->delete('test_table');
    }

    public function testDropSchema()
    {
        $connection = ConnectionManager::get('test');
        /** @var SchemaDialect $dialect */
        $dialect = $connection->getDriver()->schemaDialect();

        $this->createSchemas();

        $stmt = $dialect->listTablesSql($connection->config());
        $tables = $connection->execute($stmt[0])->fetch();

        // Assert that the schema is not empty
        $this->assertSame(['test_table'], $tables, 'The schema should not be empty.');

        // Drop the schema
        (new SchemaCleaner())->drop('test');

        // Schema is empty
        $tables = $connection->execute($stmt[0])->count();
        $this->assertSame(0, $tables, 'The schema should be empty.');
    }

    public function testTruncateSchema()
    {
        $connection = ConnectionManager::get('test');

        $this->createSchemas();

        (new SchemaCleaner())->truncate('test');

        $this->assertSame(
            0,
            $connection->newQuery()->select('id')->from('test_table')->execute()->count()
        );
        $this->assertSame(
            0,
            $connection->newQuery()->select('id')->from('test_table2')->execute()->count()
        );
    }

    private function createSchemas()
    {
        $schemaCleaner = new SchemaCleaner();
        $schemaCleaner->drop('test');

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
