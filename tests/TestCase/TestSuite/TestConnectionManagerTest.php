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
namespace Cake\Test\TestCase\TestSuite;

use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use Cake\TestSuite\TestConnectionManager;

class TestConnectionManagerTest extends TestCase
{
    /**
     * Note that phinxlog tables are suffixed by _phinxlog.
     */
    public function testUnsetMigrationTables()
    {
        $input = ['foo', 'phinxlog', 'phinxlog_bar', 'some_table', 'some_plugin_phinxlog'];
        $output = TestConnectionManager::unsetMigrationTables($input);
        $this->assertSame(['foo', 'phinxlog_bar', 'some_table',], $output);
    }

    public function testAliasConnections()
    {
        ConnectionManager::drop('dummy');
        ConnectionManager::drop('test_dummy');

        ConnectionManager::setConfig('dummy', ['url' => 'sqlite:///:foo:',]);
        ConnectionManager::setConfig('test_dummy', ['url' => 'sqlite:///:bar:',]);

        $testDB = ConnectionManager::get('dummy')->config()['database'];
        $this->assertSame(':foo:', $testDB);

        TestConnectionManager::$aliasConnectionIsLoaded = false;
        TestConnectionManager::aliasConnections();

        $testDB = ConnectionManager::get('dummy')->config()['database'];
        $this->assertSame(':bar:', $testDB);

        ConnectionManager::drop('dummy');
        ConnectionManager::drop('test_dummy');
    }
}
