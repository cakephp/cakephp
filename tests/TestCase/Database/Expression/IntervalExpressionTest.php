<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\IntervalExpression;
use Cake\Database\Query;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Type;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;

/**
 * Tests IntervalExpression class
 */
class IntervalExpressionTest extends TestCase
{
    /**
     * @var \Cake\Database\Driver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $driver;

    /**
     * @var \Cake\Datasource\ConnectionInterface
     */
    protected static $connection;

    /**
     * @var array
     */
    protected static $data = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$connection = ConnectionManager::get('test');
        self::$data['tz'] = $utc = new \DateTimeZone('UTC');
        self::$data['interval'] = \DateInterval::createFromDateString('+1 year + 2 seconds + 111 milliseconds');
        self::$data['date'] = '2021-04-17 02:03:04.321000';
        self::$data['tmpTable'] = (new TableSchema('interval_test'))
            ->addColumn('interval_date', ['type' => 'datetimefractional', 'precision' => 6])
            ->setTemporary(true);
        foreach (self::$data['tmpTable']->createSql(self::$connection) as $query) {
            self::$connection->query($query);
        }
    }

    /**
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        foreach (self::$data['tmpTable']->dropSql(self::$connection) as $query) {
            self::$connection->query($query);
        }
        self::$connection = self::$data = null;
        parent::tearDownAfterClass();
    }

    /**
     * Tests interval values.
     *
     * @return void
     */
    public function testInterval()
    {
        // Query using direct date value
        $iExp = new IntervalExpression(
            new FrozenTime(self::$data['date'], self::$data['tz']),
            self::$data['interval']
        );
        $query = new Query(self::$connection);
        $query->select([ $iExp ]);
        $stm = $query->execute();
        $result = $stm->fetchColumn(0);
        $resultDt = Type::build('datetimefractional')->toPHP($result, self::$connection->getDriver());
        $this->assertContainsEquals(
            $resultDt,
            [new FrozenTime('2022-04-17 02:03:06.432000', self::$data['tz'])]
        );
    }

    /**
     * Tests interval values using an expression.
     *
     * @return void
     */
    public function testIntervalWithExpression()
    {
        // Create temporary table and populate with date
        $query = new Query(self::$connection);
        self::$connection->execute('INSERT INTO interval_test VALUES (\'' . self::$data['date'] . '\')');
        // Query using subquery
        $iExp = new IntervalExpression(
            (new Query(self::$connection))->select(['interval_date'])->from('interval_test')->limit(1),
            self::$data['interval']
        );
        $query->select([ $iExp ]);
        $stm = $query->execute();
        $result = $stm->fetchColumn(0);
        $resultDt = Type::build('datetimefractional')->toPHP($result, self::$connection->getDriver());
        $this->assertContainsEquals(
            $resultDt,
            [new FrozenTime('2022-04-17 02:03:06.432000', self::$data['tz'])]
        );
    }
}
