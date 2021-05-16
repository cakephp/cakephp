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

use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Schema\SchemaGenerator;
use Cake\TestSuite\TestCase;

/**
 * SchemaGenerator Test
 */
class SchemaGeneratorTest extends TestCase
{
    /**
     * test reload on a table subset.
     *
     * @return void
     */
    public function testReload()
    {
        $generator = new SchemaGenerator(__DIR__ . '/test_schema.php', 'test');

        // only drop tables we'll create again.
        $tables = ['schema_generator', 'schema_generator_comment'];
        $generator->reload($tables);

        $connection = ConnectionManager::get('test');
        $schema = $connection->getSchemaCollection();

        $result = $schema->listTables();
        $this->assertContains('schema_generator', $result);
        $this->assertContains('schema_generator_comment', $result);

        foreach ($tables as $table) {
            $meta = $schema->describe($table);
            foreach ($meta->dropSql($connection) as $stmt) {
                $connection->execute($stmt);
            }
        }
    }
}
