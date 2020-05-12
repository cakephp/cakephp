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

use Cake\Database\Expression\DateTimeIntervalExpression;
use Cake\Database\Expression\IntervalExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Cake\Database\Type;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use DateInterval;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Tests IntervalExpression class
 */
class IntervalExpressionTest extends TestCase
{
    /**
     * @var \Cake\Database\Driver
     */
    protected $driver;

    /**
     * @var \Cake\Database\Connection
     */
    protected $connection;

    /**
     * @var array
     */
    protected $data = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        $this->data['tz'] = $utc = new DateTimeZone('UTC');
        $this->data['interval'] = DateInterval::createFromDateString('1 day + 2 minute + 2 seconds + 111 milliseconds');
        $this->data['intervalNegative'] = DateInterval::createFromDateString(
            '2 year + 1 month + 10 day + 2 minute + 2 seconds + 110 milliseconds ago + 10 seconds'
        );
        $this->data['dtInterval'] = DateInterval::createFromDateString(
            '2 year + 1 month + 10 day + 2 minute + 2 seconds + 110 milliseconds'
        );
        $this->data['dtIntervalInvalid'] = DateInterval::createFromDateString(
            'third monday of february'
        );
        $this->data['date'] = '2021-04-17 02:03:04.32';
    }

    /**
     * Tests interval values.
     *
     * @return void
     * @throws \Exception
     */
    public function testInterval()
    {
        $iExp = new IntervalExpression($this->data['interval']);
        $result = $iExp->sql(new ValueBinder());
        $this->assertSame(
            "INTERVAL '01 00:02:02.111000' DAY_MICROSECOND",
            $result
        );
    }

    /**
     * Tests negative interval values.
     *
     * @return void
     * @throws \Exception
     */
    public function testNegativeInterval()
    {
        $iExp = new IntervalExpression($this->data['intervalNegative']);
        $result = $iExp->sql(new ValueBinder());
        $this->assertSame(
            "INTERVAL '-2-1' YEAR_MONTH + INTERVAL -10 DAY + INTERVAL -2 MINUTE + INTERVAL 8 SECOND",
            $result
        );
    }

    /**
     * Tests interval values.
     *
     * @return void
     * @throws \Exception
     */
    public function testDateTimeInterval()
    {
        $iExp = new DateTimeIntervalExpression(
            new FrozenTime($this->data['date'], $this->data['tz']),
            $this->data['dtInterval']
        );
        $resultDt = Type::build('datetimefractional')->toPHP(
            (new Query($this->connection))->select([ $iExp ])->execute()->fetchColumn(0),
            $this->connection->getDriver()
        );
        $this->assertGreaterThanOrEqual(
            new FrozenTime('2023-05-27 02:05:06.429', $this->data['tz']),
            $resultDt
        );
        $this->assertLessThanOrEqual(
            new FrozenTime('2023-05-27 02:05:06.43', $this->data['tz']),
            $resultDt
        );
    }

    /**
     * Test an invalid interval.
     *
     * @return void
     * @throws \Exception
     */
    public function testInvalidDateTimeInterval()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cloned interval object cannot be a special or relative format.');
        new IntervalExpression(
            $this->data['dtIntervalInvalid']
        );
    }

    /**
     * Test an invalid interval.
     *
     * @return void
     * @throws \Exception
     */
    public function testInvalidSubject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subject must be ' . DateTimeInterface::class . ' or ' . ExpressionInterface::class);
        new DateTimeIntervalExpression(
            'This is not a valid subject.',
            $this->data['dtInterval']
        );
    }
}
