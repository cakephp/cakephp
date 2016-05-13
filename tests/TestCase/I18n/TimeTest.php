<?php
/**
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

use Cake\I18n\FrozenTime;
use Cake\I18n\I18n;
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
        $this->frozenNow = FrozenTime::getTestNow();
        $this->locale = Time::getDefaultLocale();
        Time::setDefaultLocale('en_US');
        FrozenTime::setDefaultLocale('en_US');

        date_default_timezone_set('UTC');
        Time::setDefaultOutputTimezone('UTC');
        FrozenTime::setDefaultOutputTimezone('UTC');
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
        Time::setDefaultLocale($this->locale);
        Time::resetToStringFormat();

        FrozenTime::setTestNow($this->frozenNow);
        FrozenTime::setDefaultLocale($this->locale);
        FrozenTime::resetToStringFormat();

        I18n::locale(I18n::DEFAULT_LOCALE);

        date_default_timezone_set('UTC');
        Time::setDefaultOutputTimezone('UTC');
        FrozenTime::setDefaultOutputTimezone('UTC');
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
     * Provider for ensuring that Time and FrozenTime work the same way.
     *
     * @return void
     */
    public static function classNameProvider()
    {
        return ['mutable' => ['Cake\I18n\Time'], 'immutable' => ['Cake\I18n\FrozenTime']];
    }

    /**
     * Ensure that instances can be built from other objects.
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testConstructFromAnotherInstance($class)
    {
        $time = '2015-01-22 10:33:44';
        $frozen = new FrozenTime($time, 'America/Chicago');
        $subject = new $class($frozen);
        $this->assertEquals($time, $subject->format('Y-m-d H:i:s'), 'frozen time construction');

        $mut = new Time($time, 'America/Chicago');
        $subject = new $class($mut);
        $this->assertEquals($time, $subject->format('Y-m-d H:i:s'), 'mutable time construction');
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
     * testTimeAgoInWords method
     *
     * @dataProvider timeAgoProvider
     * @return void
     */
    public function testTimeAgoInWordsFrozenTime($input, $expected)
    {
        $time = new FrozenTime($input);
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
     * @dataProvider classNameProvider
     * @return void
     */
    public function testTimeAgoInWordsTimezone($class)
    {
        $time = new $class('1990-07-31 20:33:00 UTC');
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
     * test the timezone option for timeAgoInWords
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testTimeAgoInWordsTimezoneOutputDefaultTimezone($class)
    {
        $class::setDefaultOutputTimezone('Europe/Paris');
        $time = new $class('1990-07-31 20:33:00 UTC');
        $result = $time->timeAgoInWords(
            [
                'end' => '+1month',
                'format' => 'dd-MM-YYYY HH:mm:ss'
            ]
        );
        $this->assertEquals('on 31-07-1990 22:33:00', $result);

        $class::setDefaultOutputTimezone('Europe/Berlin');
        $time = new $class('1990-07-31 20:33:00 UTC');
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
     * @dataProvider classNameProvider
     * @return void
     */
    public function testTimeAgoInWordsCustomStrings($class)
    {
        $time = new $class('-8 years -4 months -2 weeks -3 days');
        $result = $time->timeAgoInWords([
            'relativeString' => 'at least %s ago',
            'accuracy' => ['year' => 'year'],
            'end' => '+10 years'
        ]);
        $expected = 'at least 8 years ago';
        $this->assertEquals($expected, $result);

        $time = new $class('+4 months +2 weeks +3 days');
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
     * @dataProvider classNameProvider
     * @return void
     */
    public function testTimeAgoInWordsAccuracy($class)
    {
        $time = new $class('+8 years +4 months +2 weeks +3 days');
        $result = $time->timeAgoInWords([
            'accuracy' => ['year' => 'year'],
            'end' => '+10 years'
        ]);
        $expected = '8 years';
        $this->assertEquals($expected, $result);

        $time = new $class('+8 years +4 months +2 weeks +3 days');
        $result = $time->timeAgoInWords([
            'accuracy' => ['year' => 'month'],
            'end' => '+10 years'
        ]);
        $expected = '8 years, 4 months';
        $this->assertEquals($expected, $result);

        $time = new $class('+8 years +4 months +2 weeks +3 days');
        $result = $time->timeAgoInWords([
            'accuracy' => ['year' => 'week'],
            'end' => '+10 years'
        ]);
        $expected = '8 years, 4 months, 2 weeks';
        $this->assertEquals($expected, $result);

        $time = new $class('+8 years +4 months +2 weeks +3 days');
        $result = $time->timeAgoInWords([
            'accuracy' => ['year' => 'day'],
            'end' => '+10 years'
        ]);
        $expected = '8 years, 4 months, 2 weeks, 3 days';
        $this->assertEquals($expected, $result);

        $time = new $class('+1 years +5 weeks');
        $result = $time->timeAgoInWords([
            'accuracy' => ['year' => 'year'],
            'end' => '+10 years'
        ]);
        $expected = '1 year';
        $this->assertEquals($expected, $result);

        $time = new $class('+58 minutes');
        $result = $time->timeAgoInWords([
            'accuracy' => 'hour'
        ]);
        $expected = 'in about an hour';
        $this->assertEquals($expected, $result);

        $time = new $class('+23 hours');
        $result = $time->timeAgoInWords([
            'accuracy' => 'day'
        ]);
        $expected = 'in about a day';
        $this->assertEquals($expected, $result);

        $time = new $class('+20 days');
        $result = $time->timeAgoInWords(['accuracy' => 'month']);
        $this->assertEquals('in about a month', $result);
    }

    /**
     * Test the format option of timeAgoInWords()
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testTimeAgoInWordsWithFormat($class)
    {
        $time = new $class('2007-9-25');
        $result = $time->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertEquals('on 2007-09-25', $result);

        $time = new $class('+2 weeks +2 days');
        $result = $time->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertRegExp('/^2 weeks, [1|2] day(s)?$/', $result);

        $time = new $class('+2 months +2 days');
        $result = $time->timeAgoInWords(['end' => '1 month', 'format' => 'yyyy-MM-dd']);
        $this->assertEquals('on ' . date('Y-m-d', strtotime('+2 months +2 days')), $result);
    }

    /**
     * test timeAgoInWords() with negative values.
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testTimeAgoInWordsNegativeValues($class)
    {
        $time = new $class('-2 months -2 days');
        $result = $time->timeAgoInWords(['end' => '3 month']);
        $this->assertEquals('2 months, 2 days ago', $result);

        $time = new $class('-2 months -2 days');
        $result = $time->timeAgoInWords(['end' => '3 month']);
        $this->assertEquals('2 months, 2 days ago', $result);

        $time = new $class('-2 months -2 days');
        $result = $time->timeAgoInWords(['end' => '1 month', 'format' => 'yyyy-MM-dd']);
        $this->assertEquals('on ' . date('Y-m-d', strtotime('-2 months -2 days')), $result);

        $time = new $class('-2 years -5 months -2 days');
        $result = $time->timeAgoInWords(['end' => '3 years']);
        $this->assertEquals('2 years, 5 months, 2 days ago', $result);

        $time = new $class('-2 weeks -2 days');
        $result = $time->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertEquals('2 weeks, 2 days ago', $result);

        $time = new $class('-3 years -12 months');
        $result = $time->timeAgoInWords();
        $expected = 'on ' . $time->format('n/j/y');
        $this->assertEquals($expected, $result);

        $time = new $class('-1 month -1 week -6 days');
        $result = $time->timeAgoInWords(
            ['end' => '1 year', 'accuracy' => ['month' => 'month']]
        );
        $this->assertEquals('1 month ago', $result);

        $time = new $class('-1 years -2 weeks -3 days');
        $result = $time->timeAgoInWords(
            ['accuracy' => ['year' => 'year']]
        );
        $expected = 'on ' . $time->format('n/j/y');
        $this->assertEquals($expected, $result);

        $time = new $class('-13 months -5 days');
        $result = $time->timeAgoInWords(['end' => '2 years']);
        $this->assertEquals('1 year, 1 month, 5 days ago', $result);

        $time = new $class('-58 minutes');
        $result = $time->timeAgoInWords(['accuracy' => 'hour']);
        $this->assertEquals('about an hour ago', $result);

        $time = new $class('-23 hours');
        $result = $time->timeAgoInWords(['accuracy' => 'day']);
        $this->assertEquals('about a day ago', $result);

        $time = new $class('-20 days');
        $result = $time->timeAgoInWords(['accuracy' => 'month']);
        $this->assertEquals('about a month ago', $result);
    }

    /**
     * testNice method
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testNice($class)
    {
        $time = new $class('2014-04-20 20:00', 'UTC');
        $this->assertTimeFormat('Apr 20, 2014, 8:00 PM', $time->nice());

        $result = $time->nice('America/New_York');
        $this->assertTimeFormat('Apr 20, 2014, 4:00 PM', $result);
        $this->assertEquals('UTC', $time->getTimezone()->getName());

        $this->assertTimeFormat('20 avr. 2014 20:00', $time->nice(null, 'fr-FR'));
        $this->assertTimeFormat('20 avr. 2014 16:00', $time->nice('America/New_York', 'fr-FR'));
    }

    /**
     * testNiceWithDefaultOutputTimezone method
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testNiceWithDefaultOutputTimezone($class)
    {
        $class::setDefaultOutputTimezone('America/Vancouver');
        $time = new $class('2014-04-20 20:00', 'UTC');

        $this->assertTimeFormat('Apr 20, 2014, 1:00 PM', $time->nice());

        $result = $time->nice('America/New_York');
        $this->assertTimeFormat('Apr 20, 2014, 4:00 PM', $result);
        $this->assertEquals('UTC', $time->getTimezone()->getName());

        $class::setDefaultOutputTimezone('Europe/Paris');
        $time = new $class('2014-04-20 20:00', 'UTC');
        $this->assertTimeFormat('20 avr. 2014 22:00', $time->nice(null, 'fr-FR'));
        $this->assertTimeFormat('20 avr. 2014 16:00', $time->nice('America/New_York', 'fr-FR'));
    }

    /**
     * test formatting dates taking in account preferred i18n locale file
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testI18nFormat($class)
    {
        $time = new $class('Thu Jan 14 13:59:28 2010');
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

        $class::setDefaultLocale('fr-FR');
        $result = $time->i18nFormat(\IntlDateFormatter::FULL);
        $expected = 'jeudi 14 janvier 2010 13:59:28 UTC';
        $this->assertTimeFormat($expected, $result);

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, null, 'es-ES');
        $expected = 'jueves, 14 de enero de 2010, 13:59:28 (GMT)';
        $this->assertTimeFormat($expected, $result, 'Default locale should not be used');

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, null, 'fa-SA');
        $expected = 'پنجشنبه ۱۴ ژانویهٔ ۲۰۱۰، ساعت ۱۳:۵۹:۲۸ (GMT)';
        $this->assertTimeFormat($expected, $result, 'fa-SA locale should be used');

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, null, 'en-IR@calendar=persian');
        $expected = 'Thursday, Dey 24, 1388 at 1:59:28 PM GMT';
        $this->assertTimeFormat($expected, $result);

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, null, 'ps-IR@calendar=persian');
        $expected = 'پنجشنبه د  ۱۳۸۸ د مرغومی ۲۴ ۱۳:۵۹:۲۸ (GMT)';
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
     * test formatting dates taking in account default output timezones.
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testI18nFormatWithDefaultOutputTimezone($class)
    {
        $time = new $class('Thu Jan 14 13:59:28 2010');

        $class::$defaultLocale = 'en-CA';
        $class::setDefaultOutputTimezone('America/Vancouver');

        $result = $time->i18nFormat();
        $expected = '1/14/10 5:59 AM';
        $this->assertTimeFormat($expected, $result);

        $result = $time->i18nFormat(null, 'America/Toronto');
        $expected = '1/14/10 8:59 AM';
        $this->assertTimeFormat($expected, $result);


        $class::$defaultLocale = 'de-DE';
        $class::setDefaultOutputTimezone('Europe/Berlin');

        $result = $time->i18nFormat();
        $expected = '14.01.10 14:59';
        $this->assertTimeFormat($expected, $result);

        $result = $time->i18nFormat(null, 'Europe/London');
        $expected = '14.01.10 13:59';
        $this->assertTimeFormat($expected, $result);
    }

    /**
     * test formatting dates with offset style timezone
     *
     * @dataProvider classNameProvider
     * @see https://github.com/facebook/hhvm/issues/3637
     * @return void
     */
    public function testI18nFormatWithOffsetTimezone($class)
    {
        $time = new $class('2014-01-01T00:00:00+00');
        $result = $time->i18nFormat(\IntlDateFormatter::FULL);
        $expected = 'Wednesday January 1 2014 12:00:00 AM GMT';
        $this->assertTimeFormat($expected, $result);

        $time = new $class('2014-01-01T00:00:00+09');
        $result = $time->i18nFormat(\IntlDateFormatter::FULL);
        $expected = 'Wednesday January 1 2014 12:00:00 AM GMT+09:00';
        $this->assertTimeFormat($expected, $result);

        $time = new $class('2014-01-01T00:00:00-01:30');
        $result = $time->i18nFormat(\IntlDateFormatter::FULL);
        $expected = 'Wednesday January 1 2014 12:00:00 AM GMT-01:30';
        $this->assertTimeFormat($expected, $result);
    }

    /**
     * test formatting dates with offset style timezone and defaultOutputTimezone
     *
     * @dataProvider classNameProvider
     * @see https://github.com/facebook/hhvm/issues/3637
     * @return void
     */
    public function testI18nFormatWithOffsetTimezoneWithDefaultOutputTimezone($class)
    {
        $class::setDefaultOutputTimezone('America/Vancouver');

        $time = new $class('2014-01-01T00:00:00+00');
        $result = $time->i18nFormat(\IntlDateFormatter::FULL);
        $expected = 'Wednesday January 1 2014 12:00:00 AM GMT';
        $this->assertTimeFormat($expected, $result);

        $time = new $class('2014-01-01T00:00:00+09');
        $result = $time->i18nFormat(\IntlDateFormatter::FULL);
        $expected = 'Wednesday January 1 2014 12:00:00 AM GMT+09:00';
        $this->assertTimeFormat($expected, $result);

        $time = new $class('2014-01-01T00:00:00-01:30');
        $result = $time->i18nFormat(\IntlDateFormatter::FULL);
        $expected = 'Wednesday January 1 2014 12:00:00 AM GMT-01:30';
        $this->assertTimeFormat($expected, $result);
    }

    /**
     * testListTimezones
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testListTimezones($class)
    {
        $return = $class::listTimezones();
        $this->assertTrue(isset($return['Asia']['Asia/Bangkok']));
        $this->assertEquals('Bangkok', $return['Asia']['Asia/Bangkok']);
        $this->assertTrue(isset($return['America']['America/Argentina/Buenos_Aires']));
        $this->assertEquals('Argentina/Buenos_Aires', $return['America']['America/Argentina/Buenos_Aires']);
        $this->assertTrue(isset($return['UTC']['UTC']));
        $this->assertFalse(isset($return['Cuba']));
        $this->assertFalse(isset($return['US']));

        $return = $class::listTimezones('#^Asia/#');
        $this->assertTrue(isset($return['Asia']['Asia/Bangkok']));
        $this->assertFalse(isset($return['Pacific']));

        $return = $class::listTimezones(null, null, ['abbr' => true]);
        $this->assertTrue(isset($return['Asia']['Asia/Jakarta']));
        $this->assertEquals('Jakarta - WIB', $return['Asia']['Asia/Jakarta']);
        $this->assertEquals('Regina - CST', $return['America']['America/Regina']);

        $return = $class::listTimezones(null, null, [
            'abbr' => true,
            'before' => ' (',
            'after' => ')',
        ]);
        $this->assertEquals('Jayapura (WIT)', $return['Asia']['Asia/Jayapura']);
        $this->assertEquals('Regina (CST)', $return['America']['America/Regina']);

        $return = $class::listTimezones('#^(America|Pacific)/#', null, false);
        $this->assertTrue(isset($return['America/Argentina/Buenos_Aires']));
        $this->assertTrue(isset($return['Pacific/Tahiti']));

        $return = $class::listTimezones(\DateTimeZone::ASIA);
        $this->assertTrue(isset($return['Asia']['Asia/Bangkok']));
        $this->assertFalse(isset($return['Pacific']));

        $return = $class::listTimezones(\DateTimeZone::PER_COUNTRY, 'US', false);
        $this->assertTrue(isset($return['Pacific/Honolulu']));
        $this->assertFalse(isset($return['Asia/Bangkok']));
    }

    /**
     * Tests that __toString uses the i18n formatter and works with OutputTimezones
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testToStringWithdefaultOutputTimezone($class)
    {
        $class::setDefaultOutputTimezone('America/Vancouver');
        $time = new $class('2014-04-20 22:10');
        $class::setDefaultLocale('fr-FR');
        $class::setToStringFormat(\IntlDateFormatter::FULL);
        $this->assertTimeFormat('dimanche 20 avril 2014 15:10:00 UTC', (string)$time);
    }

    /**
     * Tests that __toString uses the i18n formatter
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testToString($class)
    {
        $time = new $class('2014-04-20 22:10');
        $class::setDefaultLocale('fr-FR');
        $class::setToStringFormat(\IntlDateFormatter::FULL);
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
     * Test that invalid datetime values do not trigger errors.
     *
     * @dataProvider invalidDataProvider
     * @return void
     */
    public function testToStringInvalidFrozen($value)
    {
        $time = new FrozenTime($value);
        $this->assertInternalType('string', (string)$time);
        $this->assertNotEmpty((string)$time);
    }

    /**
     * These invalid values are not invalid on windows :(
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testToStringInvalidZeros($class)
    {
        $this->skipIf(DS === '\\', 'All zeros are valid on windows.');
        $this->skipIf(PHP_INT_SIZE === 4, 'IntlDateFormatter throws exceptions on 32-bit systems');
        $time = new $class('0000-00-00');
        $this->assertInternalType('string', (string)$time);
        $this->assertNotEmpty((string)$time);

        $time = new $class('0000-00-00 00:00:00');
        $this->assertInternalType('string', (string)$time);
        $this->assertNotEmpty((string)$time);
    }

    /**
     * Tests diffForHumans
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testDiffForHumans($class)
    {
        $time = new $class('2014-04-20 10:10:10');

        $other = new $class('2014-04-27 10:10:10');
        $this->assertEquals('1 week before', $time->diffForHumans($other));

        $other = new $class('2014-04-21 09:10:10');
        $this->assertEquals('23 hours before', $time->diffForHumans($other));

        $other = new $class('2014-04-13 09:10:10');
        $this->assertEquals('1 week after', $time->diffForHumans($other));

        $other = new $class('2014-04-06 09:10:10');
        $this->assertEquals('2 weeks after', $time->diffForHumans($other));

        $other = new $class('2014-04-21 10:10:10');
        $this->assertEquals('1 day before', $time->diffForHumans($other));

        $other = new $class('2014-04-22 10:10:10');
        $this->assertEquals('2 days before', $time->diffForHumans($other));

        $other = new $class('2014-04-20 10:11:10');
        $this->assertEquals('1 minute before', $time->diffForHumans($other));

        $other = new $class('2014-04-20 10:12:10');
        $this->assertEquals('2 minutes before', $time->diffForHumans($other));

        $other = new $class('2014-04-20 10:10:09');
        $this->assertEquals('1 second after', $time->diffForHumans($other));

        $other = new $class('2014-04-20 10:10:08');
        $this->assertEquals('2 seconds after', $time->diffForHumans($other));
    }

    /**
     * Tests diffForHumans absolute
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testDiffForHumansAbsolute($class)
    {
        $class::setTestNow(new $class('2015-12-12 10:10:10'));
        $time = new $class('2014-04-20 10:10:10');
        $this->assertEquals('1 year', $time->diffForHumans(null, ['absolute' => true]));

        $other = new $class('2014-04-27 10:10:10');
        $this->assertEquals('1 week', $time->diffForHumans($other, ['absolute' => true]));

        $time = new $class('2016-04-20 10:10:10');
        $this->assertEquals('4 months', $time->diffForHumans(null, ['absolute' => true]));
    }

    /**
     * Tests diffForHumans with now
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testDiffForHumansNow($class)
    {
        $class::setTestNow(new $class('2015-12-12 10:10:10'));
        $time = new $class('2014-04-20 10:10:10');
        $this->assertEquals('1 year ago', $time->diffForHumans());

        $time = new $class('2016-04-20 10:10:10');
        $this->assertEquals('4 months from now', $time->diffForHumans());
    }

    /**
     * Tests encoding a Time object as json
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testJsonEnconde($class)
    {
        $time = new $class('2014-04-20 10:10:10');
        $this->assertEquals('"2014-04-20T10:10:10+0000"', json_encode($time));

        $class::setJsonEncodeFormat('yyyy-MM-dd HH:mm:ss');
        $this->assertEquals('"2014-04-20 10:10:10"', json_encode($time));
    }

    /**
     * Tests debugInfo
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testDebugInfo($class)
    {
        $time = new $class('2014-04-20 10:10:10');
        $expected = [
            'time' => '2014-04-20T10:10:10+00:00',
            'timezone' => 'UTC',
            'fixedNowTime' => $class::getTestNow()->toIso8601String()
        ];
        $this->assertEquals($expected, $time->__debugInfo());
    }

    /**
     * Tests parsing a string into a Time object based on the locale format.
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testParseDateTime($class)
    {
        $time = $class::parseDateTime('01/01/1970 00:00am');
        $this->assertNotNull($time);
        $this->assertEquals('1970-01-01 00:00', $time->format('Y-m-d H:i'));

        $time = $class::parseDateTime('10/13/2013 12:54am');
        $this->assertNotNull($time);
        $this->assertEquals('2013-10-13 00:54', $time->format('Y-m-d H:i'));

        $class::setDefaultLocale('fr-FR');
        $time = $class::parseDateTime('13 10, 2013 12:54');
        $this->assertNotNull($time);
        $this->assertEquals('2013-10-13 12:54', $time->format('Y-m-d H:i'));

        $time = $class::parseDateTime('13 foo 10 2013 12:54');
        $this->assertNull($time);
    }

    /**
     * Tests parsing a string into a Time object based on the locale format.
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testParseDate($class)
    {
        $time = $class::parseDate('10/13/2013 12:54am');
        $this->assertNotNull($time);
        $this->assertEquals('2013-10-13 00:00', $time->format('Y-m-d H:i'));

        $time = $class::parseDate('10/13/2013');
        $this->assertNotNull($time);
        $this->assertEquals('2013-10-13 00:00', $time->format('Y-m-d H:i'));

        $class::setDefaultLocale('fr-FR');
        $time = $class::parseDate('13 10, 2013 12:54');
        $this->assertNotNull($time);
        $this->assertEquals('2013-10-13 00:00', $time->format('Y-m-d H:i'));

        $time = $class::parseDate('13 foo 10 2013 12:54');
        $this->assertNull($time);

        $time = $class::parseDate('13 10, 2013', 'dd M, y');
        $this->assertNotNull($time);
        $this->assertEquals('2013-10-13', $time->format('Y-m-d'));
    }

    /**
     * Tests parsing times using the parseTime function
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testParseTime($class)
    {
        $time = $class::parseTime('12:54am');
        $this->assertNotNull($time);
        $this->assertEquals('00:54:00', $time->format('H:i:s'));

        $class::setDefaultLocale('fr-FR');
        $time = $class::parseTime('23:54');
        $this->assertNotNull($time);
        $this->assertEquals('23:54:00', $time->format('H:i:s'));

        $time = $class::parseTime('31c2:54');
        $this->assertNull($time);
    }

    /**
     * Tests that timeAgoInWords when using a russian locale does not break things
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testRussianTimeAgoInWords($class)
    {
        I18n::locale('ru_RU');
        $time = new $class('5 days ago');
        $result = $time->timeAgoInWords();
        $this->assertEquals('5 days ago', $result);
    }

    /**
     * Tests that parsing a date respects de default timezone in PHP.
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testParseDateDifferentTimezone($class)
    {
        date_default_timezone_set('Europe/Paris');
        $class::setDefaultLocale('fr-FR');
        $result = $class::parseDate('12/03/2015');
        $this->assertEquals('2015-03-12', $result->format('Y-m-d'));
        $this->assertEquals(new \DateTimeZone('Europe/Paris'), $result->tz);
    }

    /**
     * Tests the default locale setter.
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testGetSetDefaultLocale($class)
    {
        $class::setDefaultLocale('fr-FR');
        $this->assertSame('fr-FR', $class::getDefaultLocale());
    }

    /**
     * Tests the default locale setter.
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testDefaultLocaleEffectsFormatting($class)
    {
        $result = $class::parseDate('12/03/2015');
        $this->assertRegExp('/Dec 3, 2015[ ,]+12:00 AM/', $result->nice());

        $class::setDefaultLocale('fr-FR');

        $result = $class::parseDate('12/03/2015');
        $this->assertRegexp('/12 mars 2015 (?:à )?00:00/', $result->nice());

        $expected = 'Y-m-d';
        $result = $class::parseDate('12/03/2015');
        $this->assertEquals('2015-03-12', $result->format($expected));
    }

    /**
     * Custom assert to allow for variation in the version of the intl library, where
     * some translations contain a few extra commas.
     *
     * @param string $expected
     * @param string $result
     * @return void
     */
    public function assertTimeFormat($expected, $result, $message = "")
    {
        $expected = str_replace([',', '(', ')', ' at', ' م.', ' ه‍.ش.', ' AP', ' AH', ' SAKA', 'à '], '', $expected);
        $expected = str_replace(['  '], ' ', $expected);

        $result = str_replace([',', '(', ')', ' at', ' م.', ' ه‍.ش.', ' AP', ' AH', ' SAKA', 'à '], '', $result);
        $result = str_replace(['گرینویچ'], 'GMT', $result);
        $result = str_replace(['  '], ' ', $result);

        return $this->assertSame($expected, $result, $message);
    }
}
