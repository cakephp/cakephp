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
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Driver;

use Cake\Database\Connection;
use Cake\Database\Driver\Mariadb;
use Cake\Database\DriverFeatureEnum;
use Cake\Database\Query\SelectQuery;
use Cake\Datasource\ConnectionManager;
use Mockery;
use PDO;

/**
 * Tests Mariadb driver
 */
class MariadbTest extends MysqlTest
{
    protected string $driverClass = Mariadb::class;

    protected string $driverName = 'Mariadb';

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
        $this->skipIf(!$driver instanceof Mariadb);

        $featureVersions = [
            'json' => '10.2.7',
            'cte' => '10.2.1',
            'window' => '10.2.0',
            'string-agg' => '10.5.0',
            'intersect' => '10.3.0',
            'intersect-all' => '10.5.0',
            'except' => '10.3.0',
            'except-all' => '10.5.0',
        ];
        foreach ($featureVersions as $feature => $version) {
            $this->assertSame(
                version_compare($driver->version(), $version, '>='),
                $driver->supports(DriverFeatureEnum::from($feature)),
            );
        }

        $this->assertTrue($driver->supports(DriverFeatureEnum::DISABLE_CONSTRAINT_WITHOUT_TRANSACTION));
        $this->assertTrue($driver->supports(DriverFeatureEnum::SAVEPOINT));
        $this->assertTrue($driver->supports(DriverFeatureEnum::GROUP_CONCAT));

        $this->assertFalse($driver->supports(DriverFeatureEnum::TRUNCATE_WITH_CONSTRAINTS));
    }

    /**
     * Tests string aggregation translation for MariaDB.
     */
    public function testStringAggTranslationForMariadb(): void
    {
        $driver = Mockery::mock($this->driverClass)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $driver->__construct([]);
        $driver->shouldReceive('enabled')->andReturn(true);
        $driver->shouldReceive('connect')->andReturnNull();
        $driver->shouldReceive('getPdo')->andReturn(Mockery::mock(PDO::class));
        $driver->shouldReceive('version')->andReturn('10.5.0');

        $connection = new Connection(['driver' => $driver, 'log' => false]);
        $query = new SelectQuery($connection);
        $query->select([
            'names' => $query->func()->stringAgg('name', ',', ['sort_order' => 'DESC']),
        ])->from('authors');

        $this->assertSame(
            'SELECT (STRING_AGG(name, :param0 ORDER BY sort_order DESC)) AS names FROM authors',
            $query->sql(),
        );
    }
}
