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
use Cake\TestSuite\ConnectionHelper;
use Cake\TestSuite\TestCase;
use TestApp\Database\Driver\TestDriver;

class ConnectionHelperTest extends TestCase
{
    protected function tearDown(): void
    {
        ConnectionManager::drop('query_logging');
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

    public function testEnableQueryLogging(): void
    {
        $connection = new Connection(['driver' => TestDriver::class]);
        ConnectionManager::setConfig('query_logging', $connection);
        $this->assertFalse($connection->isQueryLoggingEnabled());

        (new ConnectionHelper())->enableQueryLogging(['query_logging']);
        $this->assertTrue($connection->isQueryLoggingEnabled());
    }
}
