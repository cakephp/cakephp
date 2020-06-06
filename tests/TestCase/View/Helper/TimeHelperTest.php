<?php
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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\I18n\I18n;
use Cake\I18n\Time;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\TimeHelper;
use Cake\View\View;

/**
 * TimeHelperTest class
 */
class TimeHelperTest extends TestCase
{
    /**
     * @var \Cake\View\Helper\TimeHelper
     */
    public $Time;

    /**
     * @var string
     */
    public $locale;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->View = new View();
        $this->Time = new TimeHelper($this->View);
        Time::$defaultLocale = 'en_US';
        $this->locale = I18n::getLocale();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Time::$defaultLocale = 'en_US';
        I18n::setLocale($this->locale);
    }

    /**
     * Test element wrapping in timeAgoInWords
     *
     * @return void
     */
    public function testTimeAgoInWords()
    {
        $Time = new TimeHelper($this->View);
        $timestamp = strtotime('+8 years, +4 months +2 weeks +3 days');
        $result = $Time->timeAgoInWords($timestamp, [
            'end' => '1 years',
            'element' => 'span',
        ]);
        $expected = [
            'span' => [
                'title' => $timestamp,
                'class' => 'time-ago-in-words',
            ],
            'on ' . date('n/j/y', $timestamp),
            '/span',
        ];
        $this->assertHtml($expected, $result);

        $result = $Time->timeAgoInWords($timestamp, [
            'end' => '1 years',
            'element' => [
                'title' => 'testing',
                'rel' => 'test',
            ],
        ]);
        $expected = [
            'span' => [
                'title' => 'testing',
                'class' => 'time-ago-in-words',
                'rel' => 'test',
            ],
            'on ' . date('n/j/y', $timestamp),
            '/span',
        ];
        $this->assertHtml($expected, $result);

        $timestamp = strtotime('+2 weeks');
        $result = $Time->timeAgoInWords(
            $timestamp,
            ['end' => '1 years', 'element' => 'div']
        );
        $expected = [
            'div' => [
                'title' => $timestamp,
                'class' => 'time-ago-in-words',
            ],
            '2 weeks',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test output timezone with timeAgoInWords
     *
     * @return void
     */
    public function testTimeAgoInWordsOutputTimezone()
    {
        $Time = new TimeHelper($this->View, ['outputTimezone' => 'America/Vancouver']);
        $timestamp = new Time('+8 years, +4 months +2 weeks +3 days');
        $result = $Time->timeAgoInWords($timestamp, [
            'end' => '1 years',
            'element' => 'span',
        ]);
        $vancouver = clone $timestamp;
        $vancouver->timezone('America/Vancouver');

        $expected = [
            'span' => [
                'title' => $vancouver->__toString(),
                'class' => 'time-ago-in-words',
            ],
            'on ' . $vancouver->format('n/j/y'),
            '/span',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testToQuarter method
     *
     * @return void
     */
    public function testToQuarter()
    {
        $this->assertSame(4, $this->Time->toQuarter('2007-12-25'));
        $this->assertEquals(['2007-10-01', '2007-12-31'], $this->Time->toQuarter('2007-12-25', true));
    }

    /**
     * testNice method
     *
     * @return void
     */
    public function testNice()
    {
        $time = '2014-04-20 20:00';
        $this->assertTimeFormat('Apr 20, 2014, 8:00 PM', $this->Time->nice($time));

        $result = $this->Time->nice($time, 'America/New_York');
        $this->assertTimeFormat('Apr 20, 2014, 4:00 PM', $result);
    }

    /**
     * test nice with outputTimezone
     *
     * @return void
     */
    public function testNiceOutputTimezone()
    {
        $this->Time->setConfig('outputTimezone', 'America/Vancouver');
        $time = '2014-04-20 20:00';
        $this->assertTimeFormat('Apr 20, 2014, 1:00 PM', $this->Time->nice($time));
    }

    /**
     * testToUnix method
     *
     * @return void
     */
    public function testToUnix()
    {
        $this->assertSame('1397980800', $this->Time->toUnix('2014-04-20 08:00:00'));
    }

    /**
     * testToAtom method
     *
     * @return void
     */
    public function testToAtom()
    {
        $dateTime = new \DateTime();
        $this->assertEquals($dateTime->format($dateTime::ATOM), $this->Time->toAtom($dateTime->getTimestamp()));
    }

    /**
     * testToAtom method
     *
     * @return void
     */
    public function testToAtomOutputTimezone()
    {
        $this->Time->setConfig('outputTimezone', 'America/Vancouver');
        $dateTime = new Time();
        $vancouver = clone $dateTime;
        $vancouver->timezone('America/Vancouver');
        $this->assertEquals($vancouver->format(Time::ATOM), $this->Time->toAtom($vancouver));
    }

    /**
     * testToRss method
     *
     * @return void
     */
    public function testToRss()
    {
        $date = '2012-08-12 12:12:45';
        $time = strtotime($date);
        $this->assertEquals(date('r', $time), $this->Time->toRss($time));

        $timezones = ['Europe/London', 'Europe/Brussels', 'UTC', 'America/Denver', 'America/Caracas', 'Asia/Kathmandu'];
        foreach ($timezones as $timezone) {
            $yourTimezone = new \DateTimeZone($timezone);
            $yourTime = new \DateTime($date, $yourTimezone);
            $time = $yourTime->format('U');
            $this->assertEquals($yourTime->format('r'), $this->Time->toRss($time, $timezone), "Failed on $timezone");
        }
    }

    /**
     * test toRss with outputTimezone
     *
     * @return void
     */
    public function testToRssOutputTimezone()
    {
        $this->Time->setConfig('outputTimezone', 'America/Vancouver');
        $dateTime = new Time();
        $vancouver = clone $dateTime;
        $vancouver->timezone('America/Vancouver');

        $this->assertEquals($vancouver->format('r'), $this->Time->toRss($vancouver));
    }

    /**
     * testOfGmt method
     *
     * @return void
     */
    public function testGmt()
    {
        $this->assertEquals(1397980800, $this->Time->gmt('2014-04-20 08:00:00'));
    }

    /**
     * testIsToday method
     *
     * @return void
     */
    public function testIsToday()
    {
        $result = $this->Time->isToday('+1 day');
        $this->assertFalse($result);

        $result = $this->Time->isToday('+1 days');
        $this->assertFalse($result);

        $result = $this->Time->isToday('+0 day');
        $this->assertTrue($result);

        $result = $this->Time->isToday('-1 day');
        $this->assertFalse($result);
    }

    /**
     * testIsFuture method
     *
     * @return void
     */
    public function testIsFuture()
    {
        $this->assertTrue($this->Time->isFuture('+1 month'));
        $this->assertTrue($this->Time->isFuture('+1 days'));
        $this->assertTrue($this->Time->isFuture('+1 minute'));
        $this->assertTrue($this->Time->isFuture('+1 second'));

        $this->assertFalse($this->Time->isFuture('-1 second'));
        $this->assertFalse($this->Time->isFuture('-1 day'));
        $this->assertFalse($this->Time->isFuture('-1 week'));
        $this->assertFalse($this->Time->isFuture('-1 month'));
    }

    /**
     * testIsPast method
     *
     * @return void
     */
    public function testIsPast()
    {
        $this->assertFalse($this->Time->isPast('+1 month'));
        $this->assertFalse($this->Time->isPast('+1 days'));
        $this->assertFalse($this->Time->isPast('+1 minute'));
        $this->assertFalse($this->Time->isPast('+1 second'));

        $this->assertTrue($this->Time->isPast('-1 second'));
        $this->assertTrue($this->Time->isPast('-1 day'));
        $this->assertTrue($this->Time->isPast('-1 week'));
        $this->assertTrue($this->Time->isPast('-1 month'));
    }

    /**
     * testIsThisWeek method
     *
     * @return void
     */
    public function testIsThisWeek()
    {
        // A map of days which goes from -1 day of week to +1 day of week
        $map = [
            'Mon' => [-1, 7], 'Tue' => [-2, 6], 'Wed' => [-3, 5],
            'Thu' => [-4, 4], 'Fri' => [-5, 3], 'Sat' => [-6, 2],
            'Sun' => [-7, 1],
        ];
        $days = $map[date('D')];

        for ($day = $days[0] + 1; $day < $days[1]; $day++) {
            $this->assertTrue($this->Time->isThisWeek(($day > 0 ? '+' : '') . $day . ' days'));
        }
        $this->assertFalse($this->Time->isThisWeek($days[0] . ' days'));
        $this->assertFalse($this->Time->isThisWeek('+' . $days[1] . ' days'));
    }

    /**
     * testIsThisMonth method
     *
     * @return void
     */
    public function testIsThisMonth()
    {
        $result = $this->Time->isThisMonth('+0 day');
        $this->assertTrue($result);
        $result = $this->Time->isThisMonth($time = mktime(0, 0, 0, date('m'), mt_rand(1, 28), date('Y')));
        $this->assertTrue($result);
        $result = $this->Time->isThisMonth(mktime(0, 0, 0, date('m'), mt_rand(1, 28), date('Y') - mt_rand(1, 12)));
        $this->assertFalse($result);
        $result = $this->Time->isThisMonth(mktime(0, 0, 0, date('m'), mt_rand(1, 28), date('Y') + mt_rand(1, 12)));
        $this->assertFalse($result);
    }

    /**
     * testIsThisYear method
     *
     * @return void
     */
    public function testIsThisYear()
    {
        $result = $this->Time->isThisYear('+0 day');
        $this->assertTrue($result);
        $result = $this->Time->isThisYear(mktime(0, 0, 0, mt_rand(1, 12), mt_rand(1, 28), date('Y')));
        $this->assertTrue($result);
    }

    /**
     * testWasYesterday method
     *
     * @return void
     */
    public function testWasYesterday()
    {
        $result = $this->Time->wasYesterday('+1 day');
        $this->assertFalse($result);
        $result = $this->Time->wasYesterday('+1 days');
        $this->assertFalse($result);
        $result = $this->Time->wasYesterday('+0 day');
        $this->assertFalse($result);
        $result = $this->Time->wasYesterday('-1 day');
        $this->assertTrue($result);
        $result = $this->Time->wasYesterday('-1 days');
        $this->assertTrue($result);
        $result = $this->Time->wasYesterday('-2 days');
        $this->assertFalse($result);
    }

    /**
     * testIsTomorrow method
     *
     * @return void
     */
    public function testIsTomorrow()
    {
        $result = $this->Time->isTomorrow('+1 day');
        $this->assertTrue($result);
        $result = $this->Time->isTomorrow('+1 days');
        $this->assertTrue($result);
        $result = $this->Time->isTomorrow('+0 day');
        $this->assertFalse($result);
        $result = $this->Time->isTomorrow('-1 day');
        $this->assertFalse($result);
    }

    /**
     * testWasWithinLast method
     *
     * @return void
     */
    public function testWasWithinLast()
    {
        $this->assertTrue($this->Time->wasWithinLast('1 day', '-1 day'));
        $this->assertTrue($this->Time->wasWithinLast('1 week', '-1 week'));
        $this->assertTrue($this->Time->wasWithinLast('1 year', '-1 year'));
        $this->assertTrue($this->Time->wasWithinLast('1 second', '-1 second'));
        $this->assertTrue($this->Time->wasWithinLast('1 minute', '-1 minute'));
        $this->assertTrue($this->Time->wasWithinLast('1 year', '-1 year'));
        $this->assertTrue($this->Time->wasWithinLast('1 month', '-1 month'));
        $this->assertTrue($this->Time->wasWithinLast('1 day', '-1 day'));

        $this->assertTrue($this->Time->wasWithinLast('1 week', '-1 day'));
        $this->assertTrue($this->Time->wasWithinLast('2 week', '-1 week'));
        $this->assertFalse($this->Time->wasWithinLast('1 second', '-1 year'));
        $this->assertTrue($this->Time->wasWithinLast('10 minutes', '-1 second'));
        $this->assertTrue($this->Time->wasWithinLast('23 minutes', '-1 minute'));
        $this->assertFalse($this->Time->wasWithinLast('0 year', '-1 year'));
        $this->assertTrue($this->Time->wasWithinLast('13 month', '-1 month'));
        $this->assertTrue($this->Time->wasWithinLast('2 days', '-1 day'));

        $this->assertFalse($this->Time->wasWithinLast('1 week', '-2 weeks'));
        $this->assertFalse($this->Time->wasWithinLast('1 second', '-2 seconds'));
        $this->assertFalse($this->Time->wasWithinLast('1 day', '-2 days'));
        $this->assertFalse($this->Time->wasWithinLast('1 hour', '-2 hours'));
        $this->assertFalse($this->Time->wasWithinLast('1 month', '-2 months'));
        $this->assertFalse($this->Time->wasWithinLast('1 year', '-2 years'));

        $this->assertFalse($this->Time->wasWithinLast('1 day', '-2 weeks'));
        $this->assertFalse($this->Time->wasWithinLast('1 day', '-2 days'));
        $this->assertFalse($this->Time->wasWithinLast('0 days', '-2 days'));
        $this->assertTrue($this->Time->wasWithinLast('1 hour', '-20 seconds'));
        $this->assertTrue($this->Time->wasWithinLast('1 year', '-60 minutes -30 seconds'));
        $this->assertTrue($this->Time->wasWithinLast('3 years', '-2 months'));
        $this->assertTrue($this->Time->wasWithinLast('5 months', '-4 months'));
    }

    /**
     * test deprecated usage of wasWithinLast()
     *
     * @group deprecated
     * @return void
     */
    public function testWasWithinLastNumericString()
    {
        $this->deprecated(function () {
            $this->assertTrue($this->Time->wasWithinLast('5 ', '-3 days'));
            $this->assertTrue($this->Time->wasWithinLast('1   ', '-1 hour'));
            $this->assertTrue($this->Time->wasWithinLast('1   ', '-1 minute'));
            $this->assertTrue($this->Time->wasWithinLast('1   ', '-23 hours -59 minutes -59 seconds'));
        });
    }

    /**
     * testWasWithinLast method
     *
     * @return void
     */
    public function testIsWithinNext()
    {
        $this->assertFalse($this->Time->isWithinNext('1 day', '-1 day'));
        $this->assertFalse($this->Time->isWithinNext('1 week', '-1 week'));
        $this->assertFalse($this->Time->isWithinNext('1 year', '-1 year'));
        $this->assertFalse($this->Time->isWithinNext('1 second', '-1 second'));
        $this->assertFalse($this->Time->isWithinNext('1 minute', '-1 minute'));
        $this->assertFalse($this->Time->isWithinNext('1 year', '-1 year'));
        $this->assertFalse($this->Time->isWithinNext('1 month', '-1 month'));
        $this->assertFalse($this->Time->isWithinNext('1 day', '-1 day'));

        $this->assertFalse($this->Time->isWithinNext('1 week', '-1 day'));
        $this->assertFalse($this->Time->isWithinNext('2 week', '-1 week'));
        $this->assertFalse($this->Time->isWithinNext('1 second', '-1 year'));
        $this->assertFalse($this->Time->isWithinNext('10 minutes', '-1 second'));
        $this->assertFalse($this->Time->isWithinNext('23 minutes', '-1 minute'));
        $this->assertFalse($this->Time->isWithinNext('0 year', '-1 year'));
        $this->assertFalse($this->Time->isWithinNext('13 month', '-1 month'));
        $this->assertFalse($this->Time->isWithinNext('2 days', '-1 day'));

        $this->assertFalse($this->Time->isWithinNext('1 week', '-2 weeks'));
        $this->assertFalse($this->Time->isWithinNext('1 second', '-2 seconds'));
        $this->assertFalse($this->Time->isWithinNext('1 day', '-2 days'));
        $this->assertFalse($this->Time->isWithinNext('1 hour', '-2 hours'));
        $this->assertFalse($this->Time->isWithinNext('1 month', '-2 months'));
        $this->assertFalse($this->Time->isWithinNext('1 year', '-2 years'));

        $this->assertFalse($this->Time->isWithinNext('1 day', '-2 weeks'));
        $this->assertFalse($this->Time->isWithinNext('1 day', '-2 days'));
        $this->assertFalse($this->Time->isWithinNext('0 days', '-2 days'));
        $this->assertFalse($this->Time->isWithinNext('1 hour', '-20 seconds'));
        $this->assertFalse($this->Time->isWithinNext('1 year', '-60 minutes -30 seconds'));
        $this->assertFalse($this->Time->isWithinNext('3 years', '-2 months'));
        $this->assertFalse($this->Time->isWithinNext('5 months', '-4 months'));

        $this->assertTrue($this->Time->isWithinNext('1 day', '+1 day'));
        $this->assertTrue($this->Time->isWithinNext('7 day', '+1 week'));
        $this->assertTrue($this->Time->isWithinNext('1 minute', '+1 second'));
        $this->assertTrue($this->Time->isWithinNext('1 month', '+1 month'));
    }

    /**
     * test formatting dates taking in account preferred i18n locale file
     *
     * @return void
     */
    public function testFormat()
    {
        $time = strtotime('Thu Jan 14 13:59:28 2010');

        $result = $this->Time->format($time);
        $expected = '1/14/10, 1:59 PM';
        $this->assertTimeFormat($expected, $result);

        $result = $this->Time->format($time, \IntlDateFormatter::FULL);
        $expected = 'Thursday, January 14, 2010 at 1:59:28 PM';
        $this->assertStringStartsWith($expected, $result);

        $result = $this->Time->format('invalid date', null, 'Date invalid');
        $expected = 'Date invalid';
        $this->assertEquals($expected, $result);

        I18n::setLocale('fr_FR');
        Time::$defaultLocale = 'fr_FR';
        $time = new \Cake\I18n\FrozenTime('Thu Jan 14 13:59:28 2010');
        $result = $this->Time->format($time, \IntlDateFormatter::FULL);
        $this->assertContains('jeudi 14 janvier 2010', $result);
        $this->assertContains('13:59:28', $result);
    }

    /**
     * test format with outputTimezone
     *
     * @return void
     */
    public function testFormatOutputTimezone()
    {
        $this->Time->setConfig('outputTimezone', 'America/Vancouver');

        $time = strtotime('Thu Jan 14 8:59:28 2010 UTC');
        $result = $this->Time->format($time);
        $expected = '1/14/10, 12:59 AM';
        $this->assertTimeFormat($expected, $result);

        $time = new Time('Thu Jan 14 8:59:28 2010', 'UTC');
        $result = $this->Time->format($time);
        $expected = '1/14/10, 12:59 AM';
        $this->assertTimeFormat($expected, $result);
    }

    /**
     * test i18nFormat with outputTimezone
     *
     * @return void
     */
    public function testI18nFormatOutputTimezone()
    {
        $this->Time->setConfig('outputTimezone', 'America/Vancouver');

        $time = strtotime('Thu Jan 14 8:59:28 2010 UTC');
        $result = $this->Time->i18nFormat($time, \IntlDateFormatter::SHORT);
        $expected = '1/14/10, 12:59:28 AM';
        $this->assertStringStartsWith($expected, $result);
    }

    /**
     * Test format() with a string.
     *
     * @return void
     */
    public function testFormatString()
    {
        $time = '2010-01-14 13:59:28';
        $result = $this->Time->format($time);
        $this->assertTimeFormat('1/14/10 1:59 PM', $result);

        $result = $this->Time->format($time, 'HH:mm', null, 'America/New_York');
        $this->assertTimeFormat('08:59', $result);
    }

    /**
     * Test format() with a Time instance.
     *
     * @return void
     */
    public function testFormatTimeInstance()
    {
        $time = new Time('2010-01-14 13:59:28', 'America/New_York');
        $result = $this->Time->format($time, 'HH:mm', null, 'America/New_York');
        $this->assertTimeFormat('13:59', $result);

        $time = new Time('2010-01-14 13:59:28', 'UTC');
        $result = $this->Time->format($time, 'HH:mm', null, 'America/New_York');
        $this->assertTimeFormat('08:59', $result);
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
        $this->assertEquals(
            str_replace([',', '(', ')', ' at', ' à'], '', $expected),
            str_replace([',', '(', ')', ' at', ' à'], '', $result)
        );
    }

    /**
     * Test formatting in case the $time parameter is not set
     *
     * @return void
     */
    public function testNullDateFormat()
    {
        $result = $this->Time->format(null);
        $this->assertFalse($result);

        $fallback = 'Date invalid or not set';
        $result = $this->Time->format(null, \IntlDateFormatter::FULL, $fallback);
        $this->assertEquals($fallback, $result);
    }
}
