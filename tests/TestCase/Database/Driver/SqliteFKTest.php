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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Driver;

use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Tests Sqlite driver
 */
class SqliteFKTest extends TestCase
{
    protected $_currentState;

    public $driver;

    public function setUp(): void
    {
        parent::setUp();

        $config = ConnectionManager::getConfig('test');
        $this->skipIf(strpos($config['driver'], 'Sqlite') === false, 'Not using Sqlite for test config');

        ConnectionManager::setConfig('sqliteFKtest', ['url' => 'sqlite:///:memory:']);
        $connection = ConnectionManager::get('sqliteFKtest');
        $connection->connect();
        $this->driver = $connection->getDriver();
        $this->_currentState = $this->driver->isForeignKeyEnabled();
    }

    public function tearDown(): void
    {
        // Restore current state
        $this->driver->getConnection()->exec('PRAGMA foreign_keys = ' . ($this->_currentState ? 'ON' : 'OFF'));
        $this->driver = null;
        ConnectionManager::drop('sqliteFKtest');

        parent::tearDown();
    }

    public function testInitialFKCheckAsDefault()
    {
        $driver = $this->driver;
        $current = $driver->isForeignKeyEnabled();
        $this->assertEquals('PRAGMA foreign_keys = OFF', $driver->disableForeignKeySQL());
        $this->assertEquals(
            $current ?
            'PRAGMA foreign_keys = ON' :
            'PRAGMA foreign_keys = OFF',
            $driver->enableForeignKeySQL()
        );
    }

    public function testInitialFKCheckAsON()
    {
        $driver = $this->driver;
        $current = $driver->isForeignKeyEnabled();
        $driver->getConnection()->exec('PRAGMA foreign_keys = ON');

        $this->assertTrue($driver->isForeignKeyEnabled());
        $this->assertEquals('PRAGMA foreign_keys = OFF', $driver->disableForeignKeySQL());
        $this->assertEquals('PRAGMA foreign_keys = ON', $driver->enableForeignKeySQL());
    }

    public function testInitialFKCheckAsOFF()
    {
        $driver = $this->driver;
        $current = $driver->isForeignKeyEnabled();
        $driver->getConnection()->exec('PRAGMA foreign_keys = OFF');

        $this->assertFalse($driver->isForeignKeyEnabled());
        $this->assertEquals('PRAGMA foreign_keys = OFF', $driver->disableForeignKeySQL());
        $this->assertEquals('PRAGMA foreign_keys = OFF', $driver->enableForeignKeySQL());
    }
}
