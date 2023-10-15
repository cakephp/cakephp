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

use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Database\DriverInterface;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * Tests MySQL driver
 */
class MysqlTest extends TestCase
{
    /**
     * setup
     */
    public function setup(): void
    {
        parent::setUp();
        $config = ConnectionManager::getConfig('test');
        $this->skipIf(strpos($config['driver'], 'Mysql') === false, 'Not using Mysql for test config');
    }

    /**
     * Test connecting to MySQL with default configuration
     */
    public function testConnectionConfigDefault(): void
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Mysql')
            ->onlyMethods(['_connect', 'getConnection'])
            ->getMock();
        $dsn = 'mysql:host=localhost;port=3306;dbname=cake;charset=utf8mb4';
        $expected = [
            'persistent' => true,
            'host' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => 'cake',
            'port' => '3306',
            'flags' => [],
            'encoding' => 'utf8mb4',
            'timezone' => null,
            'init' => [],
        ];

        $expected['flags'] += [
            PDO::ATTR_PERSISTENT => true,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        $connection = $this->getMockBuilder('StdClass')
            ->addMethods(['exec'])
            ->getMock();

        $driver->expects($this->once())->method('_connect')
            ->with($dsn, $expected);

        $driver->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($connection));
        $driver->connect([]);
    }

    /**
     * Test connecting to MySQL with custom configuration
     */
    public function testConnectionConfigCustom(): void
    {
        $config = [
            'persistent' => false,
            'host' => 'foo',
            'database' => 'bar',
            'username' => 'user',
            'password' => 'pass',
            'port' => 3440,
            'flags' => [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'],
            'encoding' => null,
            'timezone' => 'Antarctica',
            'init' => [
                'Execute this',
                'this too',
            ],
        ];
        $driver = $this->getMockBuilder('Cake\Database\Driver\Mysql')
            ->onlyMethods(['_connect', 'getConnection'])
            ->setConstructorArgs([$config])
            ->getMock();
        $dsn = 'mysql:host=foo;port=3440;dbname=bar';
        $expected = $config;
        $expected['init'][] = "SET time_zone = 'Antarctica'";
        $expected['flags'] += [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        $connection = $this->getMockBuilder('StdClass')
            ->addMethods(['exec'])
            ->getMock();
        $connection->expects($this->exactly(3))
            ->method('exec')
            ->withConsecutive(['Execute this'], ['this too'], ["SET time_zone = 'Antarctica'"]);

        $driver->expects($this->once())->method('_connect')
            ->with($dsn, $expected);
        $driver->expects($this->any())->method('getConnection')
            ->will($this->returnValue($connection));
        $driver->connect($config);
    }

    /**
     * Test schema
     */
    public function testSchema(): void
    {
        $connection = ConnectionManager::get('test');
        $config = ConnectionManager::getConfig('test');
        $this->assertEquals($config['database'], $connection->getDriver()->schema());
    }

    /**
     * Test isConnected
     */
    public function testIsConnected(): void
    {
        $this->deprecated(function () {
            $connection = ConnectionManager::get('test');
            $connection->disconnect();
            $this->assertFalse($connection->isConnected(), 'Not connected now.');

            $connection->connect();
            $this->assertTrue($connection->isConnected(), 'Should be connected.');
        });
    }

    public function testRollbackTransactionAutoConnect(): void
    {
        $this->deprecated(function () {
            $connection = ConnectionManager::get('test');
            $connection->disconnect();

            $driver = $connection->getDriver();
            $this->assertFalse($driver->rollbackTransaction());
            $this->assertTrue($driver->isConnected());
        });
    }

    public function testCommitTransactionAutoConnect(): void
    {
        $connection = ConnectionManager::get('test');
        $driver = $connection->getDriver();

        $this->assertFalse($driver->commitTransaction());
        $this->assertTrue($driver->isConnected());
    }

    /**
     * @dataProvider versionStringProvider
     * @param string $dbVersion
     * @param string $expectedVersion
     */
    public function testVersion($dbVersion, $expectedVersion): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject&\Cake\Database\Connection $connection */
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAttribute'])
            ->getMock();
        $connection->expects($this->once())
            ->method('getAttribute')
            ->with(PDO::ATTR_SERVER_VERSION)
            ->will($this->returnValue($dbVersion));

        /** @var \PHPUnit\Framework\MockObject\MockObject&\Cake\Database\Driver\Mysql $driver */
        $driver = $this->getMockBuilder(Mysql::class)
            ->onlyMethods(['connect'])
            ->getMock();

        $driver->setConnection($connection);

        $result = $driver->version();
        $this->assertSame($expectedVersion, $result);
    }

    public function versionStringProvider(): array
    {
        return [
            ['10.2.23-MariaDB', '10.2.23-MariaDB'],
            ['5.5.5-10.2.23-MariaDB', '10.2.23-MariaDB'],
            ['5.5.5-10.4.13-MariaDB-1:10.4.13+maria~focal', '10.4.13-MariaDB-1'],
            ['8.0.0', '8.0.0'],
        ];
    }

    /**
     * Tests driver-specific feature support check.
     */
    public function testSupports(): void
    {
        $driver = ConnectionManager::get('test')->getDriver();
        $this->skipIf(!$driver instanceof Mysql);

        $serverType = $driver->isMariadb() ? 'mariadb' : 'mysql';
        $featureVersions = [
            'mysql' => [
                'json' => '5.7.0',
                'cte' => '8.0.0',
                'window' => '8.0.0',
            ],
            'mariadb' => [
                'json' => '10.2.7',
                'cte' => '10.2.1',
                'window' => '10.2.0',
            ],
        ];

        $this->assertSame(
            version_compare($driver->version(), $featureVersions[$serverType]['cte'], '>='),
            $driver->supports(DriverInterface::FEATURE_CTE)
        );
        $this->assertSame(
            version_compare($driver->version(), $featureVersions[$serverType]['json'], '>='),
            $driver->supports(DriverInterface::FEATURE_CTE)
        );
        $this->assertSame(
            version_compare($driver->version(), $featureVersions[$serverType]['window'], '>='),
            $driver->supports(DriverInterface::FEATURE_CTE)
        );
        $this->assertTrue($driver->supports(DriverInterface::FEATURE_SAVEPOINT));
        $this->assertTrue($driver->supports(DriverInterface::FEATURE_QUOTE));

        $this->assertFalse($driver->supports('this-is-fake'));
    }

    /**
     * Tests driver-specific feature support check.
     */
    public function testDeprecatedSupports(): void
    {
        $driver = ConnectionManager::get('test')->getDriver();
        $this->skipIf(!$driver instanceof Mysql);

        $this->deprecated(function () use ($driver) {
            $this->assertSame($driver->supportsCTEs(), $driver->supports(DriverInterface::FEATURE_CTE));
            $this->assertSame($driver->supportsNativeJson(), $driver->supports(DriverInterface::FEATURE_JSON));
            $this->assertSame($driver->supportsWindowFunctions(), $driver->supports(DriverInterface::FEATURE_WINDOW));
        });
    }
}
