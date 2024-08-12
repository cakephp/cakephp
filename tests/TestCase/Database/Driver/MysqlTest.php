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

use Cake\Database\Driver\Mysql;
use Cake\Database\DriverFeatureEnum;
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
        $this->skipIf(!str_contains($config['driver'], 'Mysql'), 'Not using Mysql for test config');
    }

    /**
     * Test connecting to MySQL with default configuration
     */
    public function testConnectionConfigDefault(): void
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Mysql')
            ->onlyMethods(['createPdo'])
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
            'log' => false,
        ];

        $expected['flags'] += [
            PDO::ATTR_PERSISTENT => true,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        $driver->expects($this->once())->method('createPdo')
            ->with($dsn, $expected);

        $driver->connect();
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
            'log' => false,
        ];
        $driver = $this->getMockBuilder('Cake\Database\Driver\Mysql')
            ->onlyMethods(['createPdo'])
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

        $connection = $this->getMockBuilder('PDO')
            ->disableOriginalConstructor()
            ->onlyMethods(['exec'])
            ->getMock();
        $connection->expects($this->exactly(3))
            ->method('exec')
            ->with(
                ...self::withConsecutive(['Execute this'], ['this too'], ["SET time_zone = 'Antarctica'"])
            );

        $driver->expects($this->once())->method('createPdo')
            ->with($dsn, $expected)
            ->willReturn($connection);
        $driver->connect();
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
        $connection = ConnectionManager::get('test');
        $connection->getDriver()->disconnect();
        $this->assertFalse($connection->getDriver()->isConnected(), 'Not connected now.');

        $connection->getDriver()->connect();
        $this->assertTrue($connection->getDriver()->isConnected(), 'Should be connected.');
    }

    public function testRollbackTransactionAutoConnect(): void
    {
        $connection = ConnectionManager::get('test');
        $connection->getDriver()->disconnect();

        $driver = $connection->getDriver();
        $this->assertFalse($driver->rollbackTransaction());
        $this->assertTrue($driver->isConnected());
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
        /** @var \PHPUnit\Framework\MockObject\MockObject&\PDO $connection */
        $connection = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMock();
        $connection->expects($this->once())
            ->method('getAttribute')
            ->with(PDO::ATTR_SERVER_VERSION)
            ->willReturn($dbVersion);

        /** @var \PHPUnit\Framework\MockObject\MockObject&\Cake\Database\Driver\Mysql $driver */
        $driver = $this->getMockBuilder(Mysql::class)
            ->onlyMethods(['createPdo'])
            ->getMock();

        $driver->expects($this->once())
            ->method('createPdo')
            ->willReturn($connection);

        $result = $driver->version();
        $this->assertSame($expectedVersion, $result);
    }

    public static function versionStringProvider(): array
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
        foreach ($featureVersions[$serverType] as $feature => $version) {
            $this->assertSame(
                version_compare($driver->version(), $version, '>='),
                $driver->supports(DriverFeatureEnum::from($feature))
            );
        }

        $this->assertTrue($driver->supports(DriverFeatureEnum::DISABLE_CONSTRAINT_WITHOUT_TRANSACTION));
        $this->assertTrue($driver->supports(DriverFeatureEnum::SAVEPOINT));

        $this->assertFalse($driver->supports(DriverFeatureEnum::TRUNCATE_WITH_CONSTRAINTS));
    }

    /**
     * Tests identifier quoting
     */
    public function testQuoteIdentifier(): void
    {
        $driver = new Mysql();

        $result = $driver->quoteIdentifier('name');
        $expected = '`name`';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Model.*');
        $expected = '`Model`.*';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Items.No_ 2');
        $expected = '`Items`.`No_ 2`';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Items.No_ 2 thing');
        $expected = '`Items`.`No_ 2 thing`';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Items.No_ 2 thing AS thing');
        $expected = '`Items`.`No_ 2 thing` AS `thing`';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Items.Item Category Code = :c1');
        $expected = '`Items`.`Item Category Code` = :c1';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('MTD()');
        $expected = 'MTD()';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('(sm)');
        $expected = '(sm)';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('name AS x');
        $expected = '`name` AS `x`';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Model.name AS x');
        $expected = '`Model`.`name` AS `x`';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Function(Something.foo)');
        $expected = 'Function(`Something`.`foo`)';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Function(SubFunction(Something.foo))');
        $expected = 'Function(SubFunction(`Something`.`foo`))';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Function(Something.foo) AS x');
        $expected = 'Function(`Something`.`foo`) AS `x`';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('name-with-minus');
        $expected = '`name-with-minus`';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('my-name');
        $expected = '`my-name`';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Foo-Model.*');
        $expected = '`Foo-Model`.*';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Team.P%');
        $expected = '`Team`.`P%`';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Team.G/G');
        $expected = '`Team`.`G/G`';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Model.name as y');
        $expected = '`Model`.`name` AS `y`';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('nämé');
        $expected = '`nämé`';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('aßa.nämé');
        $expected = '`aßa`.`nämé`';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('aßa.*');
        $expected = '`aßa`.*';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Modeß.nämé as y');
        $expected = '`Modeß`.`nämé` AS `y`';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Model.näme Datum as y');
        $expected = '`Model`.`näme Datum` AS `y`';
        $this->assertEquals($expected, $result);
    }
}
