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
namespace Cake\Test\TestCase\Database;

use Cake\Database\DateInterval;
use Cake\TestSuite\TestCase;
use UnexpectedValueException;

/**
 * Tests IntervalExpression class
 */
class DateIntervalTest extends TestCase
{
    /**
     * Tests DateInterval values.
     *
     * @return void
     * @throws \Exception
     */
    public function testInterval()
    {
        $interval = DateInterval::convertFromDateInterval(
            DateInterval::createFromDateString('1 day + 2 minute + 2 seconds + 111 milliseconds')
        );
        $intervalAry = $interval->getParsed();
        $this->assertInstanceOf(DateInterval::class, $interval);
        $this->assertSame(
            [
              'YEAR' => 0,
              'MONTH' => 0,
              'DAY' => 1,
              'HOUR' => 0,
              'MINUTE' => 2,
              'SECOND' => 2,
              'MICROSECOND' => (float)111000,
            ],
            $intervalAry
        );
    }

    /**
     * Tests empty DateInterval.
     *
     * @return void
     * @throws \Exception
     */
    public function testEmptyInterval()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Interval needs to be greater than zero. Relative intervals are not supported.');
        DateInterval::convertFromDateInterval(
            DateInterval::createFromDateString('0 year')
        )->getParsed();
    }

    /**
     * Tests invalid relative DateInterval.
     *
     * @return void
     * @throws \Exception
     */
    public function testRelativeInterval()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cloned interval object cannot be a special or relative format.');
        DateInterval::convertFromDateInterval(
            DateInterval::createFromDateString('first monday of january 2020')
        );
    }
}
