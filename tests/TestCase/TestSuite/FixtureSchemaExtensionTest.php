<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\FixtureSchemaExtension;
use Cake\TestSuite\TestCase;

class FixtureSchemaExtensionTest extends TestCase
{
    /**
     * Test connection aliasing during construction.
     *
     * @return void
     */
    public function testConnectionAliasing()
    {
        $this->skipIf(!extension_loaded('pdo_sqlite'), 'Requires SQLite extension');
        ConnectionManager::setConfig('test_fixture_schema', [
            'className' => Connection::class,
            'driver' => Sqlite::class,
            'database' => TMP . 'fixture_schema.sqlite',
        ]);
        $this->assertNotContains('fixture_schema', ConnectionManager::configured());
        $extension = new FixtureSchemaExtension();

        $this->assertContains('test_fixture_schema', ConnectionManager::configured());
        $this->assertSame(
            ConnectionManager::get('test_fixture_schema'),
            ConnectionManager::get('fixture_schema')
        );
        $this->assertSame(
            ConnectionManager::get('test'),
            ConnectionManager::get('default')
        );
        $this->assertNull($extension->executeBeforeFirstTest());
        ConnectionManager::drop('test_fixture_schema');
    }
}
