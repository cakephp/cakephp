<?php
/**
 * TimeTest file
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n;

use Cake\I18n\Time;
use Cake\TestSuite\TestCase;

/**
 * TimeTest class
 *
 */
class TimeTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->now = Time::getTestNow();
        $this->locale = Time::$defaultLocale;
        Time::$defaultLocale = 'en_US';
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Time::setTestNow($this->now);
        Time::$defaultLocale = $this->locale;
        Time::resetToStringFormat();
        date_default_timezone_set('UTC');
    }

    /**
     * Restored the original system timezone
     *
     * @return void
     */
    protected function _restoreSystemTimezone()
    {
        date_default_timezone_set($this->_systemTimezoneIdentifier);
    }

    /**
     * Provides values and expectations for the toQuarter method
     *
     * @return array
     */
    public function toQuarterProvider()
    {
        return [
            ['2007-12-25', 4],
            ['2007-9-25', 3],
            ['2007-3-25', 1],
            ['2007-3-25', ['2007-01-01', '2007-03-31'], true],
            ['2007-5-25', ['2007-04-01', '2007-06-30'], true],
            ['2007-8-25', ['2007-07-01', '2007-09-30'], true],
            ['2007-12-25', ['2007-10-01', '2007-12-31'], true],
        ];
    }

    /**
     * testToQuarter method
     *
     * @dataProvider toQuarterProvider
     * @return void
     */
    public function testToQuarter($date, $expected, $range = false)
    {
        $this->assertEquals($expected, (new Time($date))->toQuarter($range));
    }

    /**
     * provider for timeAgoInWords() tests
     *
     * @return array
     */
    public static function timeAgoProvider()
    {
        return [
            ['-12 seconds', '12 seconds ago'],
            ['-12 minutes', '12 minutes ago'],
            ['-2 hours', '2 hours ago'],
            ['-1 day', '1 day ago'],
            ['-2 days', '2 days ago'],
            ['-2 days -3 hours', '2 days, 3 hours ago'],
            ['-1 week', '1 week ago'],
            ['-2 weeks -2 days', '2 weeks, 2 days ago'],
            ['+1 week', '1 week'],
            ['+1 week 1 day', '1 week, 1 day'],
            ['+2 weeks 2 day', '2 weeks, 2 days'],
            ['2007-9-24', 'on 9/24/07'],
            ['now', 'just now'],
        ];
    }

    /**
     * testTimeAgoInWords method
     *
     * @dataProvider timeAgoProvider
     * @return void
     */
    public function testTimeAgoInWords($input, $expected)
    {
        $time = new Time($input);
        $result = $time->timeAgoInWords();
        $this->assertEquals($expected, $result);
    }

    /**
     * provider for timeAgo with an end date.
     *
     * @return void
     */
    public function timeAgoEndProvider()
    {
        return [
            [
                '+4 months +2 weeks +3 days',
                '4 months, 2 weeks, 3 days',
                '8 years'
            ],
            [
                '+4 months +2 weeks +1 day',
                '4 months, 2 weeks, 1 day',
                '8 years'
            ],
            [
                '+3 months +2 weeks',
                '3 months, 2 weeks',
                '8 years'
            ],
            [
                '+3 months +2 weeks +1 day',
                '3 months, 2 weeks, 1 day',
                '8 years'
            ],
            [
                '+1 months +1 week +1 day',
                '1 month, 1 week, 1 day',
                '8 years'
            ],
            [
                '+2 months +2 days',
                '2 months, 2 days',
                '+2 months +2 days'
            ],
            [
                '+2 months +12 days',
                '2 months, 1 week, 5 days',
                '3 months'
            ],
        ];
    }
    /**
     * test the timezone option for timeAgoInWords
     *
     * @return void
     */
    public function testTimeAgoInWordsTimezone()
    {
        $time = new Time('1990-07-31 20:33:00 UTC');
        $result = $time->timeAgoInWords(
            [
                'timezone' => 'America/Vancouver',
                'end' => '+1month',
                'format' => 'dd-MM-YYYY HH:mm:ss'
            ]
        );
        $this->assertEquals('on 31-07-1990 13:33:00', $result);
    }

    /**
     * test the end option for timeAgoInWords
     *
     * @dataProvider timeAgoEndProvider
     * @return void
     */
    public function testTimeAgoInWordsEnd($input, $expected, $end)
    {
        $time = new Time($input);
        $result = $time->timeAgoInWords(['end' => $end]);
        $this->assertEquals($expected, $result);
    }

    /**
     * test the custom string options for timeAgoInWords
     *
     * @return void
     */
    public function testTimeAgoInWordsCustomStrings()
    {
        $time = new Time('-8 years -4 months -2 weeks -3 days');
        $result = $time->timeAgoInWords([
            'relativeString' => 'at least %s ago',
            'accuracy' => ['year' => 'year'],
            'end' => '+10 years'
        ]);
        $expected = 'at least 8 years ago';
        $this->assertEquals($expected, $result);

        $time = new Time('+4 months +2 weeks +3 days');
        $result = $time->timeAgoInWords([
            'absoluteString' => 'exactly on %s',
            'accuracy' => ['year' => 'year'],
            'end' => '+2 months'
        ]);
        $expected = 'exactly on ' . date('n/j/y', strtotime('+4 months +2 weeks +3 days'));
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the accuracy option for timeAgoInWords()
     *
     * @return void
     */
    public function testTimeAgoInWordsAccuracy()
    {
        $time = new Time('+8 years +4 months +2 weeks +3 days');
        $result = $time->timeAgoInWords([
            'accuracy' => ['year' => 'year'],
            'end' => '+10 years'
        ]);
        $expected = '8 years';
        $this->assertEquals($expected, $result);

        $time = new Time('+8 years +4 months +2 weeks +3 days');
        $result = $time->timeAgoInWords([
            'accuracy' => ['year' => 'month'],
            'end' => '+10 years'
        ]);
        $expected = '8 years, 4 months';
        $this->assertEquals($expected, $result);

        $time = new Time('+8 years +4 months +2 weeks +3 days');
        $result = $time->timeAgoInWords([
            'accuracy' => ['year' => 'week'],
            'end' => '+10 years'
        ]);
        $expected = '8 years, 4 months, 2 weeks';
        $this->assertEquals($expected, $result);

        $time = new Time('+8 years +4 months +2 weeks +3 days');
        $result = $time->timeAgoInWords([
            'accuracy' => ['year' => 'day'],
            'end' => '+10 years'
        ]);
        $expected = '8 years, 4 months, 2 weeks, 3 days';
        $this->assertEquals($expected, $result);

        $time = new Time('+1 years +5 weeks');
        $result = $time->timeAgoInWords([
            'accuracy' => ['year' => 'year'],
            'end' => '+10 years'
        ]);
        $expected = '1 year';
        $this->assertEquals($expected, $result);

        $time = new Time('+58 minutes');
        $result = $time->timeAgoInWords([
            'accuracy' => 'hour'
        ]);
        $expected = 'in about an hour';
        $this->assertEquals($expected, $result);

        $time = new Time('+23 hours');
        $result = $time->timeAgoInWords([
            'accuracy' => 'day'
        ]);
        $expected = 'in about a day';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the format option of timeAgoInWords()
     *
     * @return void
     */
    public function testTimeAgoInWordsWithFormat()
    {
        $time = new Time('2007-9-25');
        $result = $time->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertEquals('on 2007-09-25', $result);

        $time = new Time('2007-9-25');
        $result = $time->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertEquals('on 2007-09-25', $result);

        $time = new Time('+2 weeks +2 days');
        $result = $time->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertRegExp('/^2 weeks, [1|2] day(s)?$/', $result);

        $time = new Time('+2 months +2 days');
        $result = $time->timeAgoInWords(['end' => '1 month', 'format' => 'yyyy-MM-dd']);
        $this->assertEquals('on ' . date('Y-m-d', strtotime('+2 months +2 days')), $result);
    }

    /**
     * test timeAgoInWords() with negative values.
     *
     * @return void
     */
    public function testTimeAgoInWordsNegativeValues()
    {
        $time = new Time('-2 months -2 days');
        $result = $time->timeAgoInWords(['end' => '3 month']);
        $this->assertEquals('2 months, 2 days ago', $result);

        $time = new Time('-2 months -2 days');
        $result = $time->timeAgoInWords(['end' => '3 month']);
        $this->assertEquals('2 months, 2 days ago', $result);

        $time = new Time('-2 months -2 days');
        $result = $time->timeAgoInWords(['end' => '1 month', 'format' => 'yyyy-MM-dd']);
        $this->assertEquals('on ' . date('Y-m-d', strtotime('-2 months -2 days')), $result);

        $time = new Time('-2 years -5 months -2 days');
        $result = $time->timeAgoInWords(['end' => '3 years']);
        $this->assertEquals('2 years, 5 months, 2 days ago', $result);

        $time = new Time('-2 weeks -2 days');
        $result = $time->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertEquals('2 weeks, 2 days ago', $result);

        $time = new Time('-3 years -12 months');
        $result = $time->timeAgoInWords();
        $expected = 'on ' . $time->format('n/j/y');
        $this->assertEquals($expected, $result);

        $time = new Time('-1 month -1 week -6 days');
        $result = $time->timeAgoInWords(
            ['end' => '1 year', 'accuracy' => ['month' => 'month']]
        );
        $this->assertEquals('1 month ago', $result);

        $time = new Time('-1 years -2 weeks -3 days');
        $result = $time->timeAgoInWords(
            ['accuracy' => ['year' => 'year']]
        );
        $expected = 'on ' . $time->format('n/j/y');
        $this->assertEquals($expected, $result);

        $time = new Time('-13 months -5 days');
        $result = $time->timeAgoInWords(['end' => '2 years']);
        $this->assertEquals('1 year, 1 month, 5 days ago', $result);

        $time = new Time('-58 minutes');
        $result = $time->timeAgoInWords(['accuracy' => 'hour']);
        $this->assertEquals('about an hour ago', $result);

        $time = new Time('-23 hours');
        $result = $time->timeAgoInWords(['accuracy' => 'day']);
        $this->assertEquals('about a day ago', $result);
    }

    /**
     * testNice method
     *
     * @return void
     */
    public function testNice()
    {
        $time = new Time('2014-04-20 20:00', 'UTC');
        $this->assertTimeFormat('Apr 20, 2014, 8:00 PM', $time->nice());

        $result = $time->nice('America/New_York');
        $this->assertTimeFormat('Apr 20, 2014, 4:00 PM', $result);
        $this->assertEquals('UTC', $time->getTimezone()->getName());

        $this->assertTimeFormat('20 avr. 2014 20:00', $time->nice(null, 'fr-FR'));
        $this->assertTimeFormat('20 avr. 2014 16:00', $time->nice('America/New_York', 'fr-FR'));
    }

    /**
     * testToUnix method
     *
     * @return void
     */
    public function testToUnix()
    {
        $time = new Time('2014-04-20 08:00:00');
        $this->assertEquals('1397980800', $time->toUnixString());

        $time = new Time('2021-12-11 07:00:01');
        $this->assertEquals('1639206001', $time->toUnixString());
    }

    /**
     * testIsThisWeek method
     *
     * @return void
     */
    public function testIsThisWeek()
    {
        $time = new Time('this sunday');
        $this->assertTrue($time->isThisWeek());

        $this->assertTrue($time->modify('-1 day')->isThisWeek());
        $this->assertFalse($time->modify('-6 days')->isThisWeek());

        $time = new Time();
        $time->year = $time->year - 1;
        $this->assertFalse($time->isThisWeek());
    }

    /**
     * testIsThisMonth method
     *
     * @return void
     */
    public function testIsThisMonth()
    {
        $time = new Time();
        $this->assertTrue($time->isThisMonth());

        $time->year = $time->year + 1;
        $this->assertFalse($time->isThisMonth());

        $time = new Time();
        $this->assertFalse($time->modify('next month')->isThisMonth());
    }

    /**
     * testIsThisYear method
     *
     * @return void
     */
    public function testIsThisYear()
    {
        $time = new Time();
        $this->assertTrue($time->isThisYear());

        $time->year = $time->year + 1;
        $this->assertFalse($time->isThisYear());

        $thisYear = date('Y');
        $time = new Time("$thisYear-01-01 00:00", 'Australia/Sydney');

        $now = clone $time;
        $now->timezone('UTC');
        Time::setTestNow($now);
        $this->assertFalse($time->isThisYear());
    }

    /**
     * testWasWithinLast method
     *
     * @return void
     */
    public function testWasWithinLast()
    {
        $this->assertTrue((new Time('-1 day'))->wasWithinLast('1 day'));
        $this->assertTrue((new Time('-1 week'))->wasWithinLast('1 week'));
        $this->assertTrue((new Time('-1 year'))->wasWithinLast('1 year'));
        $this->assertTrue((new Time('-1 second'))->wasWithinLast('1 second'));
        $this->assertTrue((new Time('-1 day'))->wasWithinLast('1 week'));
        $this->assertTrue((new Time('-1 week'))->wasWithinLast('2 week'));
        $this->assertTrue((new Time('-1 second'))->wasWithinLast('10 minutes'));
        $this->assertTrue((new Time('-1 month'))->wasWithinLast('13 month'));
        $this->assertTrue((new Time('-1 seconds'))->wasWithinLast('1 hour'));

        $this->assertFalse((new Time('-1 year'))->wasWithinLast('1 second'));
        $this->assertFalse((new Time('-1 year'))->wasWithinLast('0 year'));
        $this->assertFalse((new Time('-1 weeks'))->wasWithinLast('1 day'));

        $this->assertTrue((new Time('-3 days'))->wasWithinLast('5'));
    }

    /**
     * testWasWithinLast method
     *
     * @return void
     */
    public function testIsWithinNext()
    {
        $this->assertFalse((new Time('-1 day'))->isWithinNext('1 day'));
        $this->assertFalse((new Time('-1 week'))->isWithinNext('1 week'));
        $this->assertFalse((new Time('-1 year'))->isWithinNext('1 year'));
        $this->assertFalse((new Time('-1 second'))->isWithinNext('1 second'));
        $this->assertFalse((new Time('-1 day'))->isWithinNext('1 week'));
        $this->assertFalse((new Time('-1 week'))->isWithinNext('2 week'));
        $this->assertFalse((new Time('-1 second'))->isWithinNext('10 minutes'));
        $this->assertFalse((new Time('-1 month'))->isWithinNext('13 month'));
        $this->assertFalse((new Time('-1 seconds'))->isWithinNext('1 hour'));

        $this->assertTrue((new Time('+1 day'))->isWithinNext('1 day'));
        $this->assertTrue((new Time('+1 week'))->isWithinNext('7 day'));
        $this->assertTrue((new Time('+1 second'))->isWithinNext('1 minute'));
        $this->assertTrue((new Time('+1 month'))->isWithinNext('1 month'));
    }

    /**
     * test formatting dates taking in account preferred i18n locale file
     *
     * @return void
     */
    public function testI18nFormat()
    {
        $time = new Time('Thu Jan 14 13:59:28 2010');
        $result = $time->i18nFormat();
        $expected = '1/14/10, 1:59 PM';
        $this->assertTimeFormat($expected, $result);

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, null, 'es-ES');
        $expected = 'jueves, 14 de enero de 2010, 13:59:28 (GMT)';
        $this->assertTimeFormat($expected, $result);

        $format = [\IntlDateFormatter::NONE, \IntlDateFormatter::SHORT];
        $result = $time->i18nFormat($format);
        $expected = '1:59 PM';
        $this->assertTimeFormat($expected, $result);

        $result = $time->i18nFormat('HH:mm:ss', 'Australia/Sydney');
        $expected = '00:59:28';
        $this->assertTimeFormat($expected, $result);

        Time::$defaultLocale = 'fr-FR';
        $result = $time->i18nFormat(\IntlDateFormatter::FULL);
        $expected = 'jeudi 14 janvier 2010 13:59:28 UTC';
        $this->assertTimeFormat($expected, $result);

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, null, 'es-ES');
        $expected = 'jueves, 14 de enero de 2010, 13:59:28 (GMT)';
        $this->assertTimeFormat($expected, $result, 'DEfault locale should not be used');

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, null, 'fa-AF');
        $expected = 'پنجشنبه ۱۴ جنوری ۲۰۱۰، ساعت ۱۳:۵۹:۲۸ (GMT)';
        $this->assertTimeFormat($expected, $result);

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, null, 'fa-SA');
        $expected = 'پنجشنبه ۱۴ ژانویهٔ ۲۰۱۰، ساعت ۱۳:۵۹:۲۸ (GMT)';
        $this->assertTimeFormat($expected, $result);

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, null, 'fa-IR');
        $expected = 'پنجشنبه ۱۴ ژانویهٔ ۲۰۱۰ م.، ساعت ۱۳:۵۹:۲۸ (GMT)';
        $this->assertTimeFormat($expected, $result);

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, null, 'fa-IR@calendar=persian');
        $expected = 'پنجشنبه ۲۴ دی ۱۳۸۸ ه‍.ش.، ساعت ۱۳:۵۹:۲۸ (GMT)';
        $this->assertTimeFormat($expected, $result);

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, null, 'en-IR@calendar=persian');
        $expected = 'Thursday, Dey 24, 1388 at 1:59:28 PM GMT';
        $this->assertTimeFormat($expected, $result);

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, null, 'ps-IR@calendar=persian');
        $expected = 'پنجشنبه د  ۱۳۸۸ د مرغومی ۲۴ ۱۳:۵۹:۲۸ (GMT)';
        $this->assertTimeFormat($expected, $result);

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, null, 'fa-KW@calendar=islamic');
        $expected = 'پنجشنبه ۲۹ محرم ۱۴۳۱ هجری قمری، ساعت ۱۳:۵۹:۲۸ (GMT)';
        $this->assertTimeFormat($expected, $result);

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, null, 'en-KW@calendar=islamic');
        $expected = 'Thursday, Muharram 29, 1431 at 1:59:28 PM GMT';
        $this->assertTimeFormat($expected, $result);

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, 'Asia/Tokyo', 'ja-JP@calendar=japanese');
        $expected = '平成22年1月14日木曜日 22時59分28秒 日本標準時';
        $this->assertTimeFormat($expected, $result);

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, 'Asia/Tokyo', 'ja-JP@calendar=japanese');
        $expected = '平成22年1月14日木曜日 22時59分28秒 日本標準時';
        $this->assertTimeFormat($expected, $result);
    }

    /**
     * test formatting dates with offset style timezone
     *
     * @see https://github.com/facebook/hhvm/issues/3637
     * @return void
     */
    public function testI18nFormatWithOffsetTimezone()
    {
        $time = new Time('2014-01-01T00:00:00+00');
        $result = $time->i18nFormat(\IntlDateFormatter::FULL);
        $expected = 'Wednesday January 1 2014 12:00:00 AM GMT';
        $this->assertTimeFormat($expected, $result);

        $time = new Time('2014-01-01T00:00:00+09');
        $result = $time->i18nFormat(\IntlDateFormatter::FULL);
        $expected = 'Wednesday January 1 2014 12:00:00 AM GMT+09:00';
        $this->assertTimeFormat($expected, $result);

        $time = new Time('2014-01-01T00:00:00-01:30');
        $result = $time->i18nFormat(\IntlDateFormatter::FULL);
        $expected = 'Wednesday January 1 2014 12:00:00 AM GMT-01:30';
        $this->assertTimeFormat($expected, $result);
    }

    /**
     * testListTimezones
     *
     * @return void
     */
    public function testListTimezones()
    {
        $return = Time::listTimezones();
        $this->assertTrue(isset($return['Asia']['Asia/Bangkok']));
        $this->assertEquals('Bangkok', $return['Asia']['Asia/Bangkok']);
        $this->assertTrue(isset($return['America']['America/Argentina/Buenos_Aires']));
        $this->assertEquals('Argentina/Buenos_Aires', $return['America']['America/Argentina/Buenos_Aires']);
        $this->assertTrue(isset($return['UTC']['UTC']));
        $this->assertFalse(isset($return['Cuba']));
        $this->assertFalse(isset($return['US']));

        $return = Time::listTimezones('#^Asia/#');
        $this->assertTrue(isset($return['Asia']['Asia/Bangkok']));
        $this->assertFalse(isset($return['Pacific']));

        $return = Time::listTimezones(null, null, ['abbr' => true]);
        $this->assertTrue(isset($return['Asia']['Asia/Jakarta']));
        $this->assertEquals('Jakarta - WIB', $return['Asia']['Asia/Jakarta']);
        $this->assertEquals('Regina - CST', $return['America']['America/Regina']);

        $return = Time::listTimezones(null, null, [
            'abbr' => true,
            'before' => ' (',
            'after' => ')',
        ]);
        $this->assertEquals('Jayapura (WIT)', $return['Asia']['Asia/Jayapura']);
        $this->assertEquals('Regina (CST)', $return['America']['America/Regina']);

        $return = Time::listTimezones('#^(America|Pacific)/#', null, false);
        $this->assertTrue(isset($return['America/Argentina/Buenos_Aires']));
        $this->assertTrue(isset($return['Pacific/Tahiti']));

        $return = Time::listTimezones(\DateTimeZone::ASIA);
        $this->assertTrue(isset($return['Asia']['Asia/Bangkok']));
        $this->assertFalse(isset($return['Pacific']));

        $return = Time::listTimezones(\DateTimeZone::PER_COUNTRY, 'US', false);
        $this->assertTrue(isset($return['Pacific/Honolulu']));
        $this->assertFalse(isset($return['Asia/Bangkok']));
    }

    /**
     * Tests that __toString uses the i18n formatter
     *
     * @return void
     */
    public function testToString()
    {
        $time = new Time('2014-04-20 22:10');
        Time::$defaultLocale = 'fr-FR';
        Time::setToStringFormat(\IntlDateFormatter::FULL);
        $this->assertTimeFormat('dimanche 20 avril 2014 22:10:00 UTC', (string)$time);
    }

    /**
     * Data provider for invalid values.
     *
     * @return array
     */
    public function invalidDataProvider()
    {
        return [
            [null],
            [false],
            [''],
        ];
    }

    /**
     * Test that invalid datetime values do not trigger errors.
     *
     * @dataProvider invalidDataProvider
     * @return void
     */
    public function testToStringInvalid($value)
    {
        $time = new Time($value);
        $this->assertInternalType('string', (string)$time);
        $this->assertNotEmpty((string)$time);
    }

    /**
     * These invalid values are not invalid on windows :(
     *
     * @return void
     */
    public function testToStringInvalidZeros()
    {
        $this->skipIf(DS === '\\', 'All zeros are valid on windows.');
        $this->skipIf(PHP_INT_SIZE === 4, 'IntlDateFormatter throws exceptions on 32-bit systems');
        $time = new Time('0000-00-00');
        $this->assertInternalType('string', (string)$time);
        $this->assertNotEmpty((string)$time);

        $time = new Time('0000-00-00 00:00:00');
        $this->assertInternalType('string', (string)$time);
        $this->assertNotEmpty((string)$time);
    }

    /**
     * Tests diffForHumans
     *
     * @return void
     */
    public function testDiffForHumans()
    {
        $time = new Time('2014-04-20 10:10:10');
        $other = new Time('2014-04-27 10:10:10');
        $this->assertEquals('1 week ago', $time->diffForHumans($other));

        $other = new Time('2014-04-21 09:10:10');
        $this->assertEquals('23 hours ago', $time->diffForHumans($other));

        $other = new Time('2014-04-13 09:10:10');
        $this->assertEquals('1 week', $time->diffForHumans($other));
    }

    /**
     * Tests encoding a Time object as json
     *
     * @return void
     */
    public function testJsonEnconde()
    {
        $time = new Time('2014-04-20 10:10:10');
        $this->assertEquals('"2014-04-20T10:10:10+0000"', json_encode($time));
        Time::setJsonEncodeFormat('yyyy-MM-dd HH:mm:ss');
        $this->assertEquals('"2014-04-20 10:10:10"', json_encode($time));
    }

    /**
     * Tests debugInfo
     *
     * @return void
     */
    public function testDebugInfo()
    {
        $time = new Time('2014-04-20 10:10:10');
        $expected = [
            'time' => '2014-04-20T10:10:10+0000',
            'timezone' => 'UTC',
            'fixedNowTime' => Time::getTestNow()->toISO8601String()
        ];
        $this->assertEquals($expected, $time->__debugInfo());
    }

    /**
     * Tests parsing a string into a Time object based on the locale format.
     *
     * @return void
     */
    public function testParseDateTime()
    {
        $time = Time::parseDateTime('10/13/2013 12:54am');
        $this->assertNotNull($time);
        $this->assertEquals('2013-10-13 00:54', $time->format('Y-m-d H:i'));

        Time::$defaultLocale = 'fr-FR';
        $time = Time::parseDateTime('13 10, 2013 12:54');
        $this->assertNotNull($time);
        $this->assertEquals('2013-10-13 12:54', $time->format('Y-m-d H:i'));

        $time = Time::parseDateTime('13 foo 10 2013 12:54');
        $this->assertNull($time);
    }

    /**
     * Tests parsing a string into a Time object based on the locale format.
     *
     * @return void
     */
    public function testParseDate()
    {
        $time = Time::parseDate('10/13/2013 12:54am');
        $this->assertNotNull($time);
        $this->assertEquals('2013-10-13 00:00', $time->format('Y-m-d H:i'));

        $time = Time::parseDate('10/13/2013');
        $this->assertNotNull($time);
        $this->assertEquals('2013-10-13 00:00', $time->format('Y-m-d H:i'));

        Time::$defaultLocale = 'fr-FR';
        $time = Time::parseDate('13 10, 2013 12:54');
        $this->assertNotNull($time);
        $this->assertEquals('2013-10-13 00:00', $time->format('Y-m-d H:i'));

        $time = Time::parseDate('13 foo 10 2013 12:54');
        $this->assertNull($time);

        $time = Time::parseDate('13 10, 2013', 'dd M, y');
        $this->assertNotNull($time);
        $this->assertEquals('2013-10-13', $time->format('Y-m-d'));
    }

    /**
     * Tests parsing times using the parseTime function
     *
     * @return void
     */
    public function testParseTime()
    {
        $time = Time::parseTime('12:54am');
        $this->assertNotNull($time);
        $this->assertEquals('00:54:00', $time->format('H:i:s'));

        Time::$defaultLocale = 'fr-FR';
        $time = Time::parseTime('23:54');
        $this->assertNotNull($time);
        $this->assertEquals('23:54:00', $time->format('H:i:s'));

        $time = Time::parseTime('31c2:54');
        $this->assertNull($time);
    }

    /**
     * Tests that parsing a date respects de default timezone in PHP.
     *
     * @return void
     */
    public function testParseDateDifferentTimezone()
    {
        date_default_timezone_set('Europe/Paris');
        Time::$defaultLocale = 'fr-FR';
        $result = Time::parseDate('12/03/2015');
        $this->assertEquals('2015-03-12', $result->format('Y-m-d'));
        $this->assertEquals(new \DateTimeZone('Europe/Paris'), $result->tz);
    }

    /**
     * Tests the "from now" time calculation.
     *
     * @return void
     */
    public function testFromNow()
    {
        $date = clone $this->now;
        $date->modify('-1 year');
        $date->modify('-6 days');
        $date->modify('-51 seconds');
        $interval = Time::fromNow($date);
        $result = $interval->format("%y %m %d %H %i %s");
        $this->assertEquals($result, '1 0 6 00 0 51');
    }

    /**
     * Custom assert to allow for variation in the version of the intl library, where
     * some translations contain a few extra commas.
     *
     * @param string $expected
     * @param string $result
     * @return void
     */
    public function assertTimeFormat($expected, $result)
    {
        $expected = str_replace([',', '(', ')', ' at', ' م.', ' ه‍.ش.', ' AP', ' AH', ' SAKA'], '', $expected);
        $expected = str_replace(['  '], ' ', $expected);

        $result = str_replace([',', '(', ')', ' at', ' م.', ' ه‍.ش.', ' AP', ' AH', ' SAKA'], '', $result);
        $result = str_replace(['  '], ' ', $result);

        return $this->assertEquals($expected, $result);
    }
}
