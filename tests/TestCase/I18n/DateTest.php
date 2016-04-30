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

use Cake\I18n\Date;
use Cake\I18n\FrozenDate;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use DateTimeZone;

/**
 * DateTest class
 *
 */
class DateTest extends TestCase
{

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        date_default_timezone_set('UTC');
        Date::setDefaultLocale('en_US');
        FrozenDate::setDefaultLocale('en_US');
        Date::setDefaultOutputTimezone('UTC');
        FrozenDate::setDefaultOutputTimezone('UTC');
    }

    /**
     * Teardown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        date_default_timezone_set('UTC');
        Date::setDefaultLocale('en_US');
        FrozenDate::setDefaultLocale('en_US');
        Date::setDefaultOutputTimezone('UTC');
        FrozenDate::setDefaultOutputTimezone('UTC');
    }

    /**
     * Provider for ensuring that Date and FrozenDate work the same way.
     *
     * @return void
     */
    public static function classNameProvider()
    {
        return ['mutable' => ['Cake\I18n\Date'], 'immutable' => ['Cake\I18n\FrozenDate']];
    }

    /**
     * Ensure that instances can be built from other objects.
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testConstructFromAnotherInstance($class)
    {
        $time = '2015-01-22';
        $frozen = new FrozenDate($time, 'America/Chicago');
        $subject = new $class($frozen);
        $this->assertEquals($time, $subject->format('Y-m-d'), 'frozen date construction');

        $mut = new Date($time, 'America/Chicago');
        $subject = new $class($mut);
        $this->assertEquals($time, $subject->format('Y-m-d'), 'mutable date construction');
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
        $expected = '1/14/10';
        $this->assertEquals($expected, $result);

        $format = [\IntlDateFormatter::NONE, \IntlDateFormatter::SHORT];
        $result = $time->i18nFormat($format);
        $expected = '12:00 AM';
        $this->assertEquals($expected, $result);

        $result = $time->i18nFormat('HH:mm:ss', 'Australia/Sydney');
        $expected = '00:00:00';
        $this->assertEquals($expected, $result);

        $class::setDefaultLocale('fr-FR');
        $result = $time->i18nFormat(\IntlDateFormatter::FULL);
        $result = str_replace(' à', '', $result);
        $expected = 'jeudi 14 janvier 2010 00:00:00 UTC';
        $this->assertEquals($expected, $result);

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, null, 'es-ES');
        $this->assertContains('14 de enero de 2010', $result, 'Default locale should not be used');
    }

    /**
     * test __toString
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testToString($class)
    {
        $date = new $class('2015-11-06 11:32:45');
        $this->assertEquals('11/6/15', (string)$date);
    }

    /**
     * test nice()
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testNice($class)
    {
        $date = new $class('2015-11-06 11:32:45');

        $this->assertEquals('Nov 6, 2015', $date->nice());
        $this->assertEquals('Nov 6, 2015', $date->nice(new DateTimeZone('America/New_York')));
        $this->assertEquals('6 nov. 2015', $date->nice(null, 'fr-FR'));
        
        $class::setDefaultOutputTimezone('America/Vancouver');
        $date = new $class('2015-11-06 01:00:00', 'UTC');
        $this->assertEquals('Nov 5, 2015', $date->nice()); // Why is this failing!?
        $this->assertEquals('Nov 6, 2015', $date->nice('Europe/Berlin'));
        $this->assertEquals('UTC', $date->getTimezone()->getName());
    }

    /**
     * test jsonSerialize()
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testJsonSerialize($class)
    {
        $date = new $class('2015-11-06 11:32:45');
        $this->assertEquals('"2015-11-06T00:00:00+0000"', json_encode($date));
    }

    /**
     * test parseDate()
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testParseDate($class)
    {
        $date = $class::parseDate('11/6/15');
        $this->assertEquals('2015-11-06 00:00:00', $date->format('Y-m-d H:i:s'));

        $class::setDefaultLocale('fr-FR');
        $date = $class::parseDate('13 10, 2015');
        $this->assertEquals('2015-10-13 00:00:00', $date->format('Y-m-d H:i:s'));
    }

    /**
     * test parseDateTime()
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testParseDateTime($class)
    {
        $date = $class::parseDate('11/6/15 12:33:12');
        $this->assertEquals('2015-11-06 00:00:00', $date->format('Y-m-d H:i:s'));

        $class::setDefaultLocale('fr-FR');
        $date = $class::parseDate('13 10, 2015 12:54:12');
        $this->assertEquals('2015-10-13 00:00:00', $date->format('Y-m-d H:i:s'));
    }

    /**
     * Tests the default locale setter.
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testSetDefaultLocale($class)
    {
        $result = $class::parseDate('12/03/2015');
        $this->assertEquals('Dec 3, 2015', $result->nice());

        $class::setDefaultLocale('fr-FR');

        $result = $class::parseDate('12/03/2015');
        $this->assertEquals('12 mars 2015', $result->nice());

        $expected = 'Y-m-d';
        $result = $class::parseDate('12/03/2015');
        $this->assertEquals('2015-03-12', $result->format($expected));
    }

    /**
     * Tests the default locale getter.
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testGetDefaultLocale($class)
    {
        $this->testSetDefaultLocale($class);
    }

    /**
     * Tests the default output timezone setter.
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testSetDefaultOutputTimezone($class)
    {
        $expected = 'Europe/Berlin';
        $class::setDefaultOutputTimezone('Europe/Berlin');
        $result = $class::getDefaultOutputTimezone($expected);
        $this->assertEquals(new \DateTimeZone('Europe/Berlin'), $result);
        $this->assertInstanceOf('\DatetimeZone', $result);
    }

    /**
     * Tests the default output timezone getter.
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testGetDefaultOutputTimezone($class)
    {
        $this->testSetDefaultOutputTimezone($class);
    }

    /**
     * provider for timeAgoInWords() tests
     *
     * @return array
     */
    public static function timeAgoProvider()
    {
        return [
            ['-12 seconds', 'today'],
            ['-12 minutes', 'today'],
            ['-2 hours', 'today'],
            ['-1 day', '1 day ago'],
            ['-2 days', '2 days ago'],
            ['-1 week', '1 week ago'],
            ['-2 weeks -2 days', '2 weeks, 2 days ago'],
            ['+1 second', 'today'],
            ['+1 minute, +10 seconds', 'today'],
            ['+1 week', '1 week'],
            ['+1 week 1 day', '1 week, 1 day'],
            ['+2 weeks 2 day', '2 weeks, 2 days'],
            ['2007-9-24', 'on 9/24/07'],
            ['now', 'today'],
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
        $date = new Date($input);
        $result = $date->timeAgoInWords();
        $this->assertEquals($expected, $result);
        
        Date::setDefaultOutputTimezone('Europe/Berlin');
        
        $date = new Date($input);
        $result = $date->timeAgoInWords();
        $this->assertEquals($expected, $result);

        $result = $date->timeAgoInWords(
            [
                'timezone' => 'America/Vancouver',
            ]
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * testTimeAgoInWords with Frozen Date
     *
     * @dataProvider timeAgoProvider
     * @return void
     */
    public function testTimeAgoInWordsFrozenDate($input, $expected)
    {
        $FrozenDate = new FrozenDate($input);
        $result = $FrozenDate->timeAgoInWords();
        $this->assertEquals($expected, $result);
        
        FrozenDate::setDefaultOutputTimezone('Europe/Berlin');
        
        $FrozenDate = new FrozenDate($input);
        $result = $FrozenDate->timeAgoInWords();
        $this->assertEquals($expected, $result);

        $result = $FrozenDate->timeAgoInWords(
            [
                'timezone' => 'America/Vancouver',
            ]
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * test the timezone option for timeAgoInWords
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testTimeAgoInWordsTimezone($class)
    {
        $date = new $class('1990-07-31 20:33:00 UTC');
        $result = $date->timeAgoInWords(
            [
                'timezone' => 'America/Vancouver',
                'end' => '+1month',
                'format' => 'dd-MM-YYYY'
            ]
        );
        $this->assertEquals('on 31-07-1990', $result);

        $class::setDefaultOutputTimezone('Europe/Berlin');
        $date = new $class('1990-07-31 20:33:00 UTC');
        $result = $date->timeAgoInWords(
            [
                'timezone' => 'America/Vancouver',
                'end' => '+1month',
                'format' => 'dd-MM-YYYY'
            ]
        );
        $this->assertEquals('on 31-07-1990', $result);
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
     * test the end option for timeAgoInWords
     *
     * @dataProvider timeAgoEndProvider
     * @return void
     */
    public function testTimeAgoInWordsEnd($input, $expected, $end)
    {
        $time = new Date($input);
        $result = $time->timeAgoInWords(['end' => $end]);
        $this->assertEquals($expected, $result);
    }

    /**
     * test the end option for timeAgoInWords
     *
     * @dataProvider timeAgoEndProvider
     * @return void
     */
    public function testTimeAgoInWordsEndFrozenDate($input, $expected, $end)
    {
        $time = new FrozenDate($input);
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
        $date = new $class('-8 years -4 months -2 weeks -3 days');
        $result = $date->timeAgoInWords([
            'relativeString' => 'at least %s ago',
            'accuracy' => ['year' => 'year'],
            'end' => '+10 years'
        ]);
        $expected = 'at least 8 years ago';
        $this->assertEquals($expected, $result);

        $date = new $class('+4 months +2 weeks +3 days');
        $result = $date->timeAgoInWords([
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
    public function testDateAgoInWordsAccuracy($class)
    {
        $date = new $class('+8 years +4 months +2 weeks +3 days');
        $result = $date->timeAgoInWords([
            'accuracy' => ['year' => 'year'],
            'end' => '+10 years'
        ]);
        $expected = '8 years';
        $this->assertEquals($expected, $result);

        $date = new $class('+8 years +4 months +2 weeks +3 days');
        $result = $date->timeAgoInWords([
            'accuracy' => ['year' => 'month'],
            'end' => '+10 years'
        ]);
        $expected = '8 years, 4 months';
        $this->assertEquals($expected, $result);

        $date = new $class('+8 years +4 months +2 weeks +3 days');
        $result = $date->timeAgoInWords([
            'accuracy' => ['year' => 'week'],
            'end' => '+10 years'
        ]);
        $expected = '8 years, 4 months, 2 weeks';
        $this->assertEquals($expected, $result);

        $date = new $class('+8 years +4 months +2 weeks +3 days');
        $result = $date->timeAgoInWords([
            'accuracy' => ['year' => 'day'],
            'end' => '+10 years'
        ]);
        $expected = '8 years, 4 months, 2 weeks, 3 days';
        $this->assertEquals($expected, $result);

        $date = new $class('+1 years +5 weeks');
        $result = $date->timeAgoInWords([
            'accuracy' => ['year' => 'year'],
            'end' => '+10 years'
        ]);
        $expected = '1 year';
        $this->assertEquals($expected, $result);

        $date = new $class('+23 hours');
        $result = $date->timeAgoInWords([
            'accuracy' => 'day'
        ]);
        $expected = 'today';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the format option of timeAgoInWords()
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testDateAgoInWordsWithFormat($class)
    {
        $date = new $class('2007-9-25');
        $result = $date->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertEquals('on 2007-09-25', $result);

        $date = new $class('2007-9-25');
        $result = $date->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertEquals('on 2007-09-25', $result);

        $date = new $class('+2 weeks +2 days');
        $result = $date->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertRegExp('/^2 weeks, [1|2] day(s)?$/', $result);

        $date = new $class('+2 months +2 days');
        $result = $date->timeAgoInWords(['end' => '1 month', 'format' => 'yyyy-MM-dd']);
        $this->assertEquals('on ' . date('Y-m-d', strtotime('+2 months +2 days')), $result);
    }

    /**
     * test timeAgoInWords() with negative values.
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testDateAgoInWordsNegativeValues($class)
    {
        $date = new $class('-2 months -2 days');
        $result = $date->timeAgoInWords(['end' => '3 month']);
        $this->assertEquals('2 months, 2 days ago', $result);

        $date = new $class('-2 months -2 days');
        $result = $date->timeAgoInWords(['end' => '3 month']);
        $this->assertEquals('2 months, 2 days ago', $result);

        $date = new $class('-2 months -2 days');
        $result = $date->timeAgoInWords(['end' => '1 month', 'format' => 'yyyy-MM-dd']);
        $this->assertEquals('on ' . date('Y-m-d', strtotime('-2 months -2 days')), $result);

        $date = new $class('-2 years -5 months -2 days');
        $result = $date->timeAgoInWords(['end' => '3 years']);
        $this->assertEquals('2 years, 5 months, 2 days ago', $result);

        $date = new $class('-2 weeks -2 days');
        $result = $date->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertEquals('2 weeks, 2 days ago', $result);

        $date = new $class('-3 years -12 months');
        $result = $date->timeAgoInWords();
        $expected = 'on ' . $date->format('n/j/y');
        $this->assertEquals($expected, $result);

        $date = new $class('-1 month -1 week -6 days');
        $result = $date->timeAgoInWords(
            ['end' => '1 year', 'accuracy' => ['month' => 'month']]
        );
        $this->assertEquals('1 month ago', $result);

        $date = new $class('-1 years -2 weeks -3 days');
        $result = $date->timeAgoInWords(
            ['accuracy' => ['year' => 'year']]
        );
        $expected = 'on ' . $date->format('n/j/y');
        $this->assertEquals($expected, $result);

        $date = new $class('-13 months -5 days');
        $result = $date->timeAgoInWords(['end' => '2 years']);
        $this->assertEquals('1 year, 1 month, 5 days ago', $result);

        $date = new $class('-23 hours');
        $result = $date->timeAgoInWords(['accuracy' => 'day']);
        $this->assertEquals('today', $result);
    }

    /**
     * Tests that parsing a date in a timezone other than UTC
     * will not alter the date
     *
     * @dataProvider classNameProvider
     * @return void
     */
    public function testParseDateDifferentTimezone($class)
    {
        date_default_timezone_set('Europe/Paris');
        $result = $class::parseDate('25-02-2016', 'd-M-y');
        $this->assertEquals('25-02-2016', $result->format('d-m-Y'));
    }
}
