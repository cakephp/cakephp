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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n;

use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use function Cake\I18n\toDate;
use function Cake\I18n\toDateTime;

/**
 * Test cases for functions in I18n\functions.php
 */
class FunctionsTest extends TestCase
{
    #[DataProvider('toDateTimeProvider')]
    public function testToDateTime(mixed $rawValue, string $format, ?DateTime $expected): void
    {
        if ($expected === null) {
            $this->assertNull(toDateTime($rawValue, $format));

            return;
        }
        $result = toDateTime($rawValue, $format);
        $this->assertNotNull($result);
        $this->assertEquals($expected->format($format), $result->format($format));
    }

    /**
     * @return array The array of test cases.
     */
    public static function toDateTimeProvider(): array
    {
        $date = new DateTime('2024-07-01T14:30:00Z');
        $now = $date->format(DateTimeInterface::ATOM);
        $timestamp = $date->getTimestamp();

        return [
            // DateTime input types
            '(datetime) DateTime object' => [new DateTime($now), DateTimeInterface::ATOM, $date],
            '(datetime) DateTimeImmutable object' => [new DateTimeImmutable($now), DateTimeInterface::ATOM, DateTime::createFromFormat(DateTimeInterface::ATOM, $now)],

            // Date input types
            '(date) Date object' => [new Date($now), DateTimeInterface::ATOM, $date->setTime(0, 0, 0)],

            // string input types
            '(string) valid datetime string' => [$now, DateTimeInterface::ATOM, $date],
            '(string) valid datetime string with custom format' => ['01-07-2024 14:30:00', 'd-m-Y H:i:s', DateTime::createFromFormat('d-m-Y H:i:s', '01-07-2024 14:30:00')],
            '(string) empty string' => ['', DateTimeInterface::ATOM, null],
            '(string) space' => [' ', DateTimeInterface::ATOM, null],
            '(string) non-date string' => ['abc', DateTimeInterface::ATOM, null],
            '(string) double 0' => ['00', DateTimeInterface::ATOM, DateTime::createFromFormat('U', '0')],
            '(string) single 0' => ['0', DateTimeInterface::ATOM, DateTime::createFromFormat('U', '0')],
            '(string) false' => ['false', DateTimeInterface::ATOM, null],
            '(string) true' => ['true', DateTimeInterface::ATOM, null],
            '(string) partially valid date' => ['2024-07-01T14:30:00', 'Y-m-d\TH:i:s', DateTime::createFromFormat('Y-m-d\TH:i:s', '2024-07-01T14:30:00')],

            // int input types
            '(int) valid timestamp' => [$timestamp, DateTimeInterface::ATOM, $date],
            '(int) negative timestamp' => [-1000, DateTimeInterface::ATOM, DateTime::createFromFormat('U', '-1000')],
            '(int) large timestamp' => [2147483647, DateTimeInterface::ATOM, DateTime::createFromFormat('U', '2147483647')],
            '(int) zero' => [0, DateTimeInterface::ATOM, DateTime::createFromFormat('U', '0')],

            // float input types
            '(float) positive' => [5.5, DateTimeInterface::ATOM, DateTime::createFromFormat('U', '5')->microsecond(500000)],
            '(float) round' => [5.0, DateTimeInterface::ATOM, DateTime::createFromFormat('U', '5')],
            '(float) NaN' => [NAN, DateTimeInterface::ATOM, null],
            '(float) INF' => [INF, DateTimeInterface::ATOM, null],
            '(float) -INF' => [-INF, DateTimeInterface::ATOM, null],
            '(float) timestamp' => [$timestamp + 0.0, DateTimeInterface::ATOM, $date],

            // other input types
            '(other) null' => [null, DateTimeInterface::ATOM, null],
            '(other) empty array' => [[], DateTimeInterface::ATOM, null],
            '(other) int array' => [[5], DateTimeInterface::ATOM, null],
            '(other) string array' => [['5'], DateTimeInterface::ATOM, null],
            '(other) simple object' => [new stdClass(), DateTimeInterface::ATOM, null],

            // mixed valid cases
            '(mixed) DateTimeImmutable string input' => ['2024-07-01T14:30:00Z', DateTimeInterface::ATOM, DateTime::createFromFormat(DateTimeInterface::ATOM, '2024-07-01T14:30:00Z')],
            '(mixed) integer string input' => ['1719844200', DateTimeInterface::ATOM, DateTime::createFromFormat('U', '1719844200')],

            // Custom format cases
            '(custom format) valid date' => ['01-07-2024', 'd-m-Y', DateTime::createFromFormat('d-m-Y', '01-07-2024')],
            '(custom format) valid datetime' => ['01-07-2024 14:30:00', 'd-m-Y H:i:s', DateTime::createFromFormat('d-m-Y H:i:s', '01-07-2024 14:30:00')],
            '(custom format) invalid date' => ['31-02-2024', 'd-m-Y', DateTime::createFromFormat('d-m-Y', '02-03-2024')],
            '(custom format) partially valid datetime' => ['01-07-2024 14:30', 'd-m-Y H:i', DateTime::createFromFormat('d-m-Y H:i', '01-07-2024 14:30')],
            '(custom format) valid month/year' => ['07-2024', 'm-Y', DateTime::createFromFormat('m-Y', '07-2024')],
        ];
    }

    #[DataProvider('toDateProvider')]
    public function testToDate(mixed $rawValue, string $format, ?Date $expected): void
    {
        if ($expected === null) {
            $this->assertNull(toDate($rawValue, $format));

            return;
        }
        $result = toDate($rawValue, $format);
        $this->assertNotNull($result);
        $this->assertEquals($expected->format($format), $result->format($format));
    }

    /**
     * @return array The array of test cases.
     */
    public static function toDateProvider(): array
    {
        $date = Date::parse('2024-07-01');
        $dateTime = new DateTime('2024-07-01T00:00:00Z');
        $timestamp = $dateTime->getTimestamp();

        return [
            // Date input types
            '(date) Date object' => [Date::create(2024, 7, 1), 'Y-m-d', $date],

            // DateTime input types
            '(datetime) DateTime object' => [new DateTime('2024-07-01'), 'Y-m-d', Date::create(2024, 7, 1)],
            '(datetime) DateTimeImmutable object' => [new DateTimeImmutable('2024-07-01'), 'Y-m-d', Date::create(2024, 7, 1)],

            // string input types
            '(string) valid date string' => ['2024-07-01', 'Y-m-d', $date],
            '(string) valid date string with custom format' => ['01-07-2024', 'd-m-Y', Date::create(2024, 7, 1)],
            '(string) empty string' => ['', 'Y-m-d', null],
            '(string) space' => [' ', 'Y-m-d', null],
            '(string) non-date string' => ['abc', 'Y-m-d', null],
            '(string) false' => ['false', 'Y-m-d', null],
            '(string) true' => ['true', 'Y-m-d', null],
            '(string) partially valid date' => ['2024-07-01', 'Y-m-d', Date::create(2024, 7, 1)],
            '(string) date with time' => ['2024-07-01T14:30:00', "Y-m-d'T'H:m:s", null],

            // int input types
            '(int) valid timestamp' => [$timestamp, 'Y-m-d', Date::create(2024, 7, 1)],
            '(int) negative timestamp' => [-1000, 'Y-m-d', Date::create(1969, 12, 31)],
            '(int) large timestamp' => [2147483647, 'Y-m-d', Date::create(2038, 1, 19)],
            '(int) zero' => [0, 'Y-m-d', Date::create(1970, 1, 1)],

            // float input types
            '(float) positive' => [5.5, 'Y-m-d', Date::create(1970, 1, 1)],
            '(float) round' => [5.0, 'Y-m-d', Date::create(1970, 1, 1)],
            '(float) NaN' => [NAN, 'Y-m-d', null],
            '(float) INF' => [INF, 'Y-m-d', null],
            '(float) -INF' => [-INF, 'Y-m-d', null],
            '(float) timestamp' => [$timestamp + 0.0, 'Y-m-d', Date::create(2024, 7, 1)],

            // other input types
            '(other) null' => [null, 'Y-m-d', null],
            '(other) empty array' => [[], 'Y-m-d', null],
            '(other) int array' => [[5], 'Y-m-d', null],
            '(other) string array' => [['5'], 'Y-m-d', null],
            '(other) simple object' => [new stdClass(), 'Y-m-d', null],

            // mixed valid cases
            '(mixed) DateTime string input' => ['2024-07-01T00:00:00Z', "Y-m-d'T'H:m:s'Z'", null],
            '(mixed) integer string input' => ['1719844200', 'U', Date::create(2024, 7, 1)],

            // custom format cases
            '(custom format) valid date' => ['01-07-2024', 'd-m-Y', Date::create(2024, 7, 1)],
            '(custom format) valid datetime' => ['01-07-2024 14:30:00', 'd-m-Y H:i:s', Date::create(2024, 7, 1)],
            '(custom format) valid month/year' => ['07-2024', 'm-Y', Date::create(2024, 7, 1)],
            '(custom format) invalid date' => ['31-02-2024', 'd-m-Y', Date::create(2024, 3, 2)],
            '(custom format) invalid datetime' => ['01-07-2024 14:30', 'd-m-Y H:i', Date::create(2024, 7, 1)],
        ];
    }
}
