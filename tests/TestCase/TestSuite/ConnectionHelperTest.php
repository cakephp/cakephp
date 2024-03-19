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

use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\MissingDatasourceConfigException;
use Cake\TestSuite\ConnectionHelper;
use Cake\TestSuite\TestCase;
use TestApp\Database\Driver\TestDriver;

class ConnectionHelperTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        ConnectionManager::drop('query_logging');
        ConnectionManager::drop('something');
        ConnectionManager::drop('test_something');
        ConnectionManager::dropAlias('something');
    }

    public function testAliasConnections(): void
    {
        ConnectionManager::dropAlias('default');

        (new ConnectionHelper())->addTestAliases();

        $this->assertSame(
            ConnectionManager::get('test'),
            ConnectionManager::get('default')
        );
    }

    public function testAliasNonDefaultConnections(): void
    {
        $connection = new Connection(['driver' => TestDriver::class]);
        ConnectionManager::setConfig('test_something', $connection);

        (new ConnectionHelper())->addTestAliases();

        // Having a test_ alias defined will generate an alias for the unprefixed
        // connection for simpler CI configuration
        $this->assertSame(
            ConnectionManager::get('test_something'),
            ConnectionManager::get('something')
        );
    }

    public function testAliasNoTestClass(): void
    {
        $connection = new Connection(['driver' => TestDriver::class]);
        ConnectionManager::setConfig('something', $connection);

        (new ConnectionHelper())->addTestAliases();

        // Should raise as no test connection was defined.
        $this->expectException(MissingDatasourceConfigException::class);
        ConnectionManager::get('test_something');
    }

    public function testAliasNonDefaultConnectionWithTestConnection(): void
    {
        $testConnection = new Connection(['driver' => TestDriver::class]);
        $connection = new Connection(['driver' => TestDriver::class]);
        ConnectionManager::setConfig('something', $connection);
        ConnectionManager::setConfig('test_something', $testConnection);

        (new ConnectionHelper())->addTestAliases();

        // Development connections that have test_ prefix connections defined
        // should have an alias defined for the test_ prefixed name. This allows
        // access to the development connection to resolve to the test prefixed name
        // in tests.
        $this->assertSame($testConnection, ConnectionManager::get('test_something'));
        $this->assertSame($testConnection, ConnectionManager::get('something'));
    }

    public function testEnableQueryLogging(): void
    {
        $connection = new Connection(['driver' => TestDriver::class]);
        ConnectionManager::setConfig('query_logging', $connection);
        $this->assertFalse($connection->getDriver()->log(''));

        (new ConnectionHelper())->enableQueryLogging(['query_logging']);
        $this->assertTrue($connection->getDriver()->log(''));
    }
}
