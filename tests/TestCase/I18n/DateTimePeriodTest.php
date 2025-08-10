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
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n;

use Cake\I18n\DateTime;
use Cake\I18n\DateTimePeriod;
use Cake\TestSuite\TestCase;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;

class DateTimePeriodTest extends TestCase
{
    public function testDateTimePeriod(): void
    {
        $period = new DateTimePeriod(new DatePeriod(new DateTimeImmutable('2025-01-01 00:00:00'), new DateInterval('PT1H'), 3));
        $output = [];
        foreach ($period as $key => $value) {
            $output[$key] = $value;
        }
        $this->assertCount(4, $output);
        $this->assertInstanceOf(DateTime::class, $output[0]);
        $this->assertSame('2025-01-01 00:00:00', $output[0]->format('Y-m-d H:i:s'));
        $this->assertInstanceOf(DateTime::class, $output[1]);
        $this->assertSame('2025-01-01 01:00:00', $output[1]->format('Y-m-d H:i:s'));
    }
}
