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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n;

use Cake\Chronos\Chronos;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\I18n\I18n;
use Cake\I18n\Time;
use Cake\TestSuite\TestCase;
use DateTime as NativeDateTime;
use DateTimeZone;
use IntlDateFormatter;

/**
 * TimeTest class
 */
class TimeTest extends TestCase
{
    /**
     * @var \Cake\Chronos\ChronosInterface|null
     */
    protected $now;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->now = Time::getTestNow();

        Configure::write('Error.ignoredDeprecationPaths', [
            'src/I18n/Time.php',
        ]);
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Time::setTestNow($this->now);
        Time::setDefaultLocale(null);
        Time::resetToStringFormat();
        Time::setJsonEncodeFormat("yyyy-MM-dd'T'HH':'mm':'ssxxx");

        FrozenTime::setDefaultLocale(null);
        FrozenTime::resetToStringFormat();
        FrozenTime::setJsonEncodeFormat("yyyy-MM-dd'T'HH':'mm':'ssxxx");

        date_default_timezone_set('UTC');
        I18n::setLocale(I18n::getDefaultLocale());
    }

    /**
     * Provider for ensuring that Time and FrozenTime work the same way.
     *
     * @return array
     */
    public static function classNameProvider(): array
    {
        return ['mutable' => ['Cake\I18n\Time'], 'immutable' => ['Cake\I18n\FrozenTime']];
    }

    /**
     * Ensure that instances can be built from other objects.
     *
     * @dataProvider classNameProvider
     */
    public function testConstructFromAnotherInstance(string $class): void
    {
        $time = '2015-01-22 10:33:44.123456';
        $frozen = new FrozenTime($time, 'America/Chicago');
        $subject = new $class($frozen);
        $this->assertSame($time, $subject->format('Y-m-d H:i:s.u'), 'frozen time construction');

        $mut = new Time($time, 'America/Chicago');
        $subject = new $class($mut);
        $this->assertSame($time, $subject->format('Y-m-d H:i:s.u'), 'mutable time construction');

        $mut = new Chronos($time, 'America/Chicago');
        $subject = new $class($mut);
        $this->assertSame($time, $subject->format('Y-m-d H:i:s.u'), 'mutable time construction');

        $mut = new NativeDateTime($time, new DateTimeZone('America/Chicago'));
        $subject = new $class($mut);
        $this->assertSame($time, $subject->format('Y-m-d H:i:s.u'), 'time construction');
    }

    /**
     * provider for timeAgoInWords() tests
     *
     * @return array
     */
    public static function timeAgoProvider(): array
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
     */
    public function testTimeAgoInWords(string $input, string $expected): void
    {
        $time = new Time($input);
        $result = $time->timeAgoInWords();
        $this->assertEquals($expected, $result);
    }

    /**
     * testTimeAgoInWords method
     *
     * @dataProvider timeAgoProvider
     */
    public function testTimeAgoInWordsFrozenTime(string $input, string $expected): void
    {
        $time = new FrozenTime($input);
        $result = $time->timeAgoInWords();
        $this->assertEquals($expected, $result);
    }

    /**
     * provider for timeAgo with an end date.
     *
     * @return array
     */
    public function timeAgoEndProvider(): array
    {
        return [
            [
                '+4 months +2 weeks +3 days',
                '4 months, 2 weeks, 3 days',
                '8 years',
            ],
            [
                '+4 months +2 weeks +1 day',
                '4 months, 2 weeks, 1 day',
                '8 years',
            ],
            [
                '+3 months +2 weeks',
                '3 months, 2 weeks',
                '8 years',
            ],
            [
                '+3 months +2 weeks +1 day',
                '3 months, 2 weeks, 1 day',
                '8 years',
            ],
            [
                '+1 months +1 week +1 day',
                '1 month, 1 week, 1 day',
                '8 years',
            ],
            [
                '+2 months +2 days',
                '2 months, 2 days',
                '+2 months +2 days',
            ],
            [
                '+2 months +12 days',
                '2 months, 1 week, 5 days',
                '3 months',
            ],
        ];
    }

    /**
     * test the timezone option for timeAgoInWords
     *
     * @dataProvider classNameProvider
     */
    public function testTimeAgoInWordsTimezone(string $class): void
    {
        $time = new FrozenTime('1990-07-31 20:33:00 UTC');
        $result = $time->timeAgoInWords(
            [
                'timezone' => 'America/Vancouver',
                'end' => '+1month',
                'format' => 'dd-MM-YYYY HH:mm:ss',
            ]
        );
        $this->assertSame('on 31-07-1990 13:33:00', $result);
    }

    /**
     * test the end option for timeAgoInWords
     *
     * @dataProvider timeAgoEndProvider
     */
    public function testTimeAgoInWordsEnd(string $input, string $expected, string $end): void
    {
        $time = new Time($input);
        $result = $time->timeAgoInWords(['end' => $end]);
        $this->assertEquals($expected, $result);
    }

    /**
     * test the custom string options for timeAgoInWords
     *
     * @dataProvider classNameProvider
     */
    public function testTimeAgoInWordsCustomStrings(string $class): void
    {
        $time = new $class('-8 years -4 months -2 weeks -3 days');
        $result = $time->timeAgoInWords([
            'relativeString' => 'at least %s ago',
            'accuracy' => ['year' => 'year'],
            'end' => '+10 years',
        ]);
        $expected = 'at least 8 years ago';
        $this->assertSame($expected, $result);

        $time = new $class('+4 months +2 weeks +3 days');
        $result = $time->timeAgoInWords([
            'absoluteString' => 'exactly on %s',
            'accuracy' => ['year' => 'year'],
            'end' => '+2 months',
        ]);
        $expected = 'exactly on ' . date('n/j/y', strtotime('+4 months +2 weeks +3 days'));
        $this->assertSame($expected, $result);
    }

    /**
     * Test the accuracy option for timeAgoInWords()
     *
     * @dataProvider classNameProvider
     */
    public function testTimeAgoInWordsAccuracy(string $class): void
    {
        $time = new $class('+8 years +4 months +2 weeks +3 days');
        $result = $time->timeAgoInWords([
            'accuracy' => ['year' => 'year'],
            'end' => '+10 years',
        ]);
        $expected = '8 years';
        $this->assertSame($expected, $result);

        $time = new $class('+8 years +4 months +2 weeks +3 days');
        $result = $time->timeAgoInWords([
            'accuracy' => ['year' => 'month'],
            'end' => '+10 years',
        ]);
        $expected = '8 years, 4 months';
        $this->assertSame($expected, $result);

        $time = new $class('+8 years +4 months +2 weeks +3 days');
        $result = $time->timeAgoInWords([
            'accuracy' => ['year' => 'week'],
            'end' => '+10 years',
        ]);
        $expected = '8 years, 4 months, 2 weeks';
        $this->assertSame($expected, $result);

        $time = new $class('+8 years +4 months +2 weeks +3 days');
        $result = $time->timeAgoInWords([
            'accuracy' => ['year' => 'day'],
            'end' => '+10 years',
        ]);
        $expected = '8 years, 4 months, 2 weeks, 3 days';
        $this->assertSame($expected, $result);

        $time = new $class('+1 years +5 weeks');
        $result = $time->timeAgoInWords([
            'accuracy' => ['year' => 'year'],
            'end' => '+10 years',
        ]);
        $expected = '1 year';
        $this->assertSame($expected, $result);

        $time = new $class('+58 minutes');
        $result = $time->timeAgoInWords([
            'accuracy' => 'hour',
        ]);
        $expected = 'in about an hour';
        $this->assertSame($expected, $result);

        $time = new $class('+23 hours');
        $result = $time->timeAgoInWords([
            'accuracy' => 'day',
        ]);
        $expected = 'in about a day';
        $this->assertSame($expected, $result);

        $time = new $class('+20 days');
        $result = $time->timeAgoInWords(['accuracy' => 'month']);
        $this->assertSame('in about a month', $result);
    }

    /**
     * Test the format option of timeAgoInWords()
     *
     * @dataProvider classNameProvider
     */
    public function testTimeAgoInWordsWithFormat(string $class): void
    {
        $time = new $class('2007-9-25');
        $result = $time->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertSame('on 2007-09-25', $result);

        $time = new $class('+2 weeks +2 days');
        $result = $time->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertMatchesRegularExpression('/^2 weeks, [1|2] day(s)?$/', $result);

        $time = new $class('+2 months +2 days');
        $result = $time->timeAgoInWords(['end' => '1 month', 'format' => 'yyyy-MM-dd']);
        $this->assertSame('on ' . date('Y-m-d', strtotime('+2 months +2 days')), $result);
    }

    /**
     * test timeAgoInWords() with negative values.
     *
     * @dataProvider classNameProvider
     */
    public function testTimeAgoInWordsNegativeValues(string $class): void
    {
        $time = new $class('-2 months -2 days');
        $result = $time->timeAgoInWords(['end' => '3 month']);
        $this->assertSame('2 months, 2 days ago', $result);

        $time = new $class('-2 months -2 days');
        $result = $time->timeAgoInWords(['end' => '3 month']);
        $this->assertSame('2 months, 2 days ago', $result);

        $time = new $class('-2 months -2 days');
        $result = $time->timeAgoInWords(['end' => '1 month', 'format' => 'yyyy-MM-dd']);
        $this->assertSame('on ' . date('Y-m-d', strtotime('-2 months -2 days')), $result);

        $time = new $class('-2 years -5 months -2 days');
        $result = $time->timeAgoInWords(['end' => '3 years']);
        $this->assertSame('2 years, 5 months, 2 days ago', $result);

        $time = new $class('-2 weeks -2 days');
        $result = $time->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertSame('2 weeks, 2 days ago', $result);

        $time = new $class('-3 years -12 months');
        $result = $time->timeAgoInWords();
        $expected = 'on ' . $time->format('n/j/y');
        $this->assertSame($expected, $result);

        $time = new $class('-1 month -1 week -6 days');
        $result = $time->timeAgoInWords(
            ['end' => '1 year', 'accuracy' => ['month' => 'month']]
        );
        $this->assertSame('1 month ago', $result);

        $time = new $class('-1 years -2 weeks -3 days');
        $result = $time->timeAgoInWords(
            ['accuracy' => ['year' => 'year']]
        );
        $expected = 'on ' . $time->format('n/j/y');
        $this->assertSame($expected, $result);

        $time = new $class('-13 months -5 days');
        $result = $time->timeAgoInWords(['end' => '2 years']);
        $this->assertSame('1 year, 1 month, 5 days ago', $result);

        $time = new $class('-58 minutes');
        $result = $time->timeAgoInWords(['accuracy' => 'hour']);
        $this->assertSame('about an hour ago', $result);

        $time = new $class('-23 hours');
        $result = $time->timeAgoInWords(['accuracy' => 'day']);
        $this->assertSame('about a day ago', $result);

        $time = new $class('-20 days');
        $result = $time->timeAgoInWords(['accuracy' => 'month']);
        $this->assertSame('about a month ago', $result);
    }

    /**
     * testNice method
     *
     * @dataProvider classNameProvider
     */
    public function testNice(string $class): void
    {
        $time = new $class('2014-04-20 20:00', 'UTC');
        $this->assertTimeFormat('Apr 20, 2014, 8:00 PM', $time->nice());

        $result = $time->nice('America/New_York');
        $this->assertTimeFormat('Apr 20, 2014, 4:00 PM', $result);
        $this->assertSame('UTC', $time->getTimezone()->getName());

        $this->assertTimeFormat('20 avr. 2014 20:00', $time->nice(null, 'fr-FR'));
        $this->assertTimeFormat('20 avr. 2014 16:00', $time->nice('America/New_York', 'fr-FR'));

        // Test with custom default locale
        $class::setDefaultLocale('fr-FR');
        $this->assertTimeFormat('20 avr. 2014 20:00', $time->nice());
    }

    /**
     * test formatting dates taking in account preferred i18n locale file
     *
     * @dataProvider classNameProvider
     */
    public function testI18nFormat(string $class): void
    {
        $time = new $class('Thu Jan 14 13:59:28 2010');

        // Test the default format which should be SHORT
        $result = $time->i18nFormat();
        $expected = '1/14/10, 1:59 PM';
        $this->assertTimeFormat($expected, $result);

        // Test with a custom timezone
        $result = $time->i18nFormat('HH:mm:ss', 'Australia/Sydney');
        $expected = '00:59:28';
        $this->assertTimeFormat($expected, $result);

        // Test using a time-specific format
        $format = [IntlDateFormatter::NONE, IntlDateFormatter::SHORT];
        $result = $time->i18nFormat($format);
        $expected = '1:59 PM';
        $this->assertTimeFormat($expected, $result);

        // Test using a specific format, timezone and locale
        $result = $time->i18nFormat(IntlDateFormatter::FULL, null, 'es-ES');
        $expected = 'jueves, 14 de enero de 2010, 13:59:28 (GMT)';
        $this->assertTimeFormat($expected, $result);

        // Test with custom default locale
        $class::setDefaultLocale('fr-FR');
        $result = $time->i18nFormat(IntlDateFormatter::FULL);
        $expected = 'jeudi 14 janvier 2010 13:59:28 UTC';
        $this->assertTimeFormat($expected, $result);

        // Test with a non-gregorian calendar in locale
        $result = $time->i18nFormat(IntlDateFormatter::FULL, 'Asia/Tokyo', 'ja-JP@calendar=japanese');
        $expected = '平成22年1月14日木曜日 22時59分28秒 日本標準時';
        $this->assertTimeFormat($expected, $result);

        // Test with milliseconds
        $timeMillis = new FrozenTime('2014-07-06T13:09:01.523000+00:00');
        $result = $timeMillis->i18nFormat("yyyy-MM-dd'T'HH':'mm':'ss.SSSxxx", null, 'en-US');
        $expected = '2014-07-06T13:09:01.523+00:00';
        $this->assertSame($expected, $result);
    }

    /**
     * testI18nFormatUsingSystemLocale
     */
    public function testI18nFormatUsingSystemLocale(): void
    {
        $time = new Time(1556864870);
        I18n::setLocale('ar');
        $this->assertSame('٢٠١٩-٠٥-٠٣', $time->i18nFormat('yyyy-MM-dd'));

        I18n::setLocale('en');
        $this->assertSame('2019-05-03', $time->i18nFormat('yyyy-MM-dd'));
    }

    /**
     * test formatting dates with offset style timezone
     *
     * @dataProvider classNameProvider
     * @see https://github.com/facebook/hhvm/issues/3637
     */
    public function testI18nFormatWithOffsetTimezone(string $class): void
    {
        $time = new $class('2014-01-01T00:00:00+00');
        $result = $time->i18nFormat(IntlDateFormatter::FULL);
        $expected = 'Wednesday January 1 2014 12:00:00 AM GMT';
        $this->assertTimeFormat($expected, $result);

        $time = new $class('2014-01-01T00:00:00+09');
        $result = $time->i18nFormat(IntlDateFormatter::FULL);
        $expected = 'Wednesday January 1 2014 12:00:00 AM GMT+09:00';
        $this->assertTimeFormat($expected, $result);

        $time = new $class('2014-01-01T00:00:00-01:30');
        $result = $time->i18nFormat(IntlDateFormatter::FULL);
        $expected = 'Wednesday January 1 2014 12:00:00 AM GMT-01:30';
        $this->assertTimeFormat($expected, $result);

        $time = new $class('2014-01-01T00:00Z');
        $result = $time->i18nFormat(IntlDateFormatter::FULL);
        $expected = 'Wednesday January 1 2014 12:00:00 AM GMT';
        $this->assertTimeFormat($expected, $result);
    }

    /**
     * testListTimezones
     *
     * @dataProvider classNameProvider
     */
    public function testListTimezones(string $class): void
    {
        $return = $class::listTimezones();
        $this->assertTrue(isset($return['Asia']['Asia/Bangkok']));
        $this->assertSame('Bangkok', $return['Asia']['Asia/Bangkok']);
        $this->assertTrue(isset($return['America']['America/Argentina/Buenos_Aires']));
        $this->assertSame('Argentina/Buenos_Aires', $return['America']['America/Argentina/Buenos_Aires']);
        $this->assertTrue(isset($return['UTC']['UTC']));
        $this->assertArrayNotHasKey('Cuba', $return);
        $this->assertArrayNotHasKey('US', $return);

        $return = $class::listTimezones('#^Asia/#');
        $this->assertTrue(isset($return['Asia']['Asia/Bangkok']));
        $this->assertArrayNotHasKey('Pacific', $return);

        $return = $class::listTimezones(null, null, ['abbr' => true]);
        $this->assertTrue(isset($return['Asia']['Asia/Jakarta']));
        $this->assertSame('Jakarta - WIB', $return['Asia']['Asia/Jakarta']);
        $this->assertSame('Regina - CST', $return['America']['America/Regina']);

        $return = $class::listTimezones(null, null, [
            'abbr' => true,
            'before' => ' (',
            'after' => ')',
        ]);
        $this->assertSame('Jayapura (WIT)', $return['Asia']['Asia/Jayapura']);
        $this->assertSame('Regina (CST)', $return['America']['America/Regina']);

        $return = $class::listTimezones('#^(America|Pacific)/#', null, false);
        $this->assertArrayHasKey('America/Argentina/Buenos_Aires', $return);
        $this->assertArrayHasKey('Pacific/Tahiti', $return);

        $return = $class::listTimezones(DateTimeZone::ASIA);
        $this->assertTrue(isset($return['Asia']['Asia/Bangkok']));
        $this->assertArrayNotHasKey('Pacific', $return);

        $return = $class::listTimezones(DateTimeZone::PER_COUNTRY, 'US', false);
        $this->assertArrayHasKey('Pacific/Honolulu', $return);
        $this->assertArrayNotHasKey('Asia/Bangkok', $return);
    }

    /**
     * Tests that __toString uses the i18n formatter
     *
     * @dataProvider classNameProvider
     */
    public function testToString(string $class): void
    {
        $time = new $class('2014-04-20 22:10');
        $class::setDefaultLocale('fr-FR');
        $class::setToStringFormat(IntlDateFormatter::FULL);
        $this->assertTimeFormat('dimanche 20 avril 2014 22:10:00 UTC', (string)$time);
    }

    /**
     * Data provider for invalid values.
     *
     * @return array
     */
    public function invalidDataProvider(): array
    {
        return [
            [null],
            [''],
        ];
    }

    /**
     * Test that invalid datetime values do not trigger errors.
     *
     * @dataProvider invalidDataProvider
     * @param mixed $value
     */
    public function testToStringInvalid($value): void
    {
        $time = new Time($value);
        $this->assertIsString((string)$time);
        $this->assertNotEmpty((string)$time);
    }

    /**
     * Test that invalid datetime values do not trigger errors.
     *
     * @dataProvider invalidDataProvider
     * @param mixed $value
     */
    public function testToStringInvalidFrozen($value): void
    {
        $time = new FrozenTime($value);
        $this->assertIsString((string)$time);
        $this->assertNotEmpty((string)$time);
    }

    /**
     * These invalid values are not invalid on windows :(
     *
     * @dataProvider classNameProvider
     */
    public function testToStringInvalidZeros(string $class): void
    {
        $this->skipIf(DS === '\\', 'All zeros are valid on windows.');
        $this->skipIf(PHP_INT_SIZE === 4, 'IntlDateFormatter throws exceptions on 32-bit systems');
        $time = new $class('0000-00-00');
        $this->assertIsString((string)$time);
        $this->assertNotEmpty((string)$time);

        $time = new $class('0000-00-00 00:00:00');
        $this->assertIsString((string)$time);
        $this->assertNotEmpty((string)$time);
    }

    /**
     * Tests diffForHumans
     *
     * @dataProvider classNameProvider
     */
    public function testDiffForHumans(string $class): void
    {
        $time = new $class('2014-04-20 10:10:10');

        $other = new $class('2014-04-27 10:10:10');
        $this->assertSame('1 week before', $time->diffForHumans($other));

        $other = new $class('2014-04-21 09:10:10');
        $this->assertSame('23 hours before', $time->diffForHumans($other));

        $other = new $class('2014-04-13 09:10:10');
        $this->assertSame('1 week after', $time->diffForHumans($other));

        $other = new $class('2014-04-06 09:10:10');
        $this->assertSame('2 weeks after', $time->diffForHumans($other));

        $other = new $class('2014-04-21 10:10:10');
        $this->assertSame('1 day before', $time->diffForHumans($other));

        $other = new $class('2014-04-22 10:10:10');
        $this->assertSame('2 days before', $time->diffForHumans($other));

        $other = new $class('2014-04-20 10:11:10');
        $this->assertSame('1 minute before', $time->diffForHumans($other));

        $other = new $class('2014-04-20 10:12:10');
        $this->assertSame('2 minutes before', $time->diffForHumans($other));

        $other = new $class('2014-04-20 10:10:09');
        $this->assertSame('1 second after', $time->diffForHumans($other));

        $other = new $class('2014-04-20 10:10:08');
        $this->assertSame('2 seconds after', $time->diffForHumans($other));
    }

    /**
     * Tests diffForHumans absolute
     *
     * @dataProvider classNameProvider
     */
    public function testDiffForHumansAbsolute(string $class): void
    {
        Time::setTestNow(new $class('2015-12-12 10:10:10'));
        $time = new $class('2014-04-20 10:10:10');
        $this->assertSame('1 year', $time->diffForHumans(null, true));

        $other = new $class('2014-04-27 10:10:10');
        $this->assertSame('1 week', $time->diffForHumans($other, true));

        $time = new $class('2016-04-20 10:10:10');
        $this->assertSame('4 months', $time->diffForHumans(null, true));
    }

    /**
     * Tests diffForHumans with now
     *
     * @dataProvider classNameProvider
     */
    public function testDiffForHumansNow(string $class): void
    {
        Time::setTestNow(new $class('2015-12-12 10:10:10'));
        $time = new $class('2014-04-20 10:10:10');
        $this->assertSame('1 year ago', $time->diffForHumans());

        $time = new $class('2016-04-20 10:10:10');
        $this->assertSame('4 months from now', $time->diffForHumans());
    }

    /**
     * Tests encoding a Time object as JSON
     *
     * @dataProvider classNameProvider
     */
    public function testJsonEncode(string $class): void
    {
        if (version_compare(INTL_ICU_VERSION, '50.0', '<')) {
            $this->markTestSkipped('ICU 5x is needed');
        }

        $time = new $class('2014-04-20 10:10:10');
        $this->assertSame('"2014-04-20T10:10:10+00:00"', json_encode($time));

        $class::setJsonEncodeFormat('yyyy-MM-dd HH:mm:ss');
        $this->assertSame('"2014-04-20 10:10:10"', json_encode($time));

        $class::setJsonEncodeFormat($class::UNIX_TIMESTAMP_FORMAT);
        $this->assertSame('1397988610', json_encode($time));
    }

    /**
     * Test jsonSerialize no side-effects
     *
     * @dataProvider classNameProvider
     */
    public function testJsonEncodeSideEffectFree(string $class): void
    {
        if (version_compare(INTL_ICU_VERSION, '50.0', '<')) {
            $this->markTestSkipped('ICU 5x is needed');
        }
        $date = new \Cake\I18n\FrozenTime('2016-11-29 09:00:00');
        $this->assertInstanceOf('DateTimeZone', $date->timezone);

        $result = json_encode($date);
        $this->assertSame('"2016-11-29T09:00:00+00:00"', $result);
        $this->assertInstanceOf('DateTimeZone', $date->getTimezone());
    }

    /**
     * Tests change JSON encoding format
     *
     * @dataProvider classNameProvider
     */
    public function testSetJsonEncodeFormat(string $class): void
    {
        $time = new $class('2014-04-20 10:10:10');

        $class::setJsonEncodeFormat(static function ($t) {
            return $t->format(DATE_ATOM);
        });
        $this->assertSame('"2014-04-20T10:10:10+00:00"', json_encode($time));

        $class::setJsonEncodeFormat("yyyy-MM-dd'T'HH':'mm':'ssZZZZZ");
        $this->assertSame('"2014-04-20T10:10:10Z"', json_encode($time));
    }

    /**
     * Tests debugInfo
     *
     * @dataProvider classNameProvider
     */
    public function testDebugInfo(string $class): void
    {
        $time = new $class('2014-04-20 10:10:10');
        $expected = [
            'time' => '2014-04-20 10:10:10.000000+00:00',
            'timezone' => 'UTC',
            'fixedNowTime' => $class::getTestNow()->format('Y-m-d\TH:i:s.uP'),
        ];
        $this->assertEquals($expected, $time->__debugInfo());
    }

    /**
     * Tests parsing a string into a Time object based on the locale format.
     *
     * @dataProvider classNameProvider
     */
    public function testParseDateTime(string $class): void
    {
        $time = $class::parseDateTime('01/01/1970 00:00am');
        $this->assertNotNull($time);
        $this->assertSame('1970-01-01 00:00', $time->format('Y-m-d H:i'));
        $this->assertSame(date_default_timezone_get(), $time->tzName);

        $time = $class::parseDateTime('10/13/2013 12:54am');
        $this->assertNotNull($time);
        $this->assertSame('2013-10-13 00:54', $time->format('Y-m-d H:i'));
        $this->assertSame(date_default_timezone_get(), $time->tzName);

        // Default format does not include time zone in time string
        // Time zone is ignored and is interpreted as default time zone
        $time = $class::parseDateTime('10/13/2013 12:54am GMT+08:00');
        $this->assertNotNull($time);
        $this->assertSame('2013-10-13 00:54', $time->format('Y-m-d H:i'));
        $this->assertSame(date_default_timezone_get(), $time->tzName);

        // Unlike DateTime constructor, the instance is not created with the time zone
        // in time string but converted to default time zone.
        $time = $class::parseDateTime('10/13/2013 12:54:00am GMT+08:00', [IntlDateFormatter::SHORT, IntlDateFormatter::FULL]);
        $this->assertNotNull($time);
        $this->assertSame('2013-10-12 16:54', $time->format('Y-m-d H:i'));
        $this->assertSame(date_default_timezone_get(), $time->tzName);

        $time = $class::parseDateTime('10/13/2013 12:54am', null, 'Europe/London');
        $this->assertNotNull($time);
        $this->assertSame('2013-10-13 00:54', $time->format('Y-m-d H:i'));
        $this->assertSame('Europe/London', $time->tzName);

        $class::setDefaultLocale('fr-FR');
        $time = $class::parseDateTime('13 10, 2013 12:54');
        $this->assertNotNull($time);
        $this->assertSame('2013-10-13 12:54', $time->format('Y-m-d H:i'));

        $time = $class::parseDateTime('13 foo 10 2013 12:54');
        $this->assertNull($time);
    }

    /**
     * Tests parsing a string into a Time object based on the locale format.
     *
     * @dataProvider classNameProvider
     */
    public function testParseDate(string $class): void
    {
        $time = $class::parseDate('10/13/2013 12:54am');
        $this->assertNotNull($time);
        $this->assertSame('2013-10-13 00:00', $time->format('Y-m-d H:i'));

        $time = $class::parseDate('10/13/2013');
        $this->assertNotNull($time);
        $this->assertSame('2013-10-13 00:00', $time->format('Y-m-d H:i'));

        $class::setDefaultLocale('fr-FR');
        $time = $class::parseDate('13 10, 2013 12:54');
        $this->assertNotNull($time);
        $this->assertSame('2013-10-13 00:00', $time->format('Y-m-d H:i'));

        $time = $class::parseDate('13 foo 10 2013 12:54');
        $this->assertNull($time);

        $time = $class::parseDate('13 10, 2013', 'dd M, y');
        $this->assertNotNull($time);
        $this->assertSame('2013-10-13', $time->format('Y-m-d'));
    }

    /**
     * Tests parsing times using the parseTime function
     *
     * @dataProvider classNameProvider
     */
    public function testParseTime(string $class): void
    {
        $time = $class::parseTime('12:54am');
        $this->assertNotNull($time);
        $this->assertSame('00:54:00', $time->format('H:i:s'));

        $class::setDefaultLocale('fr-FR');
        $time = $class::parseTime('23:54');
        $this->assertNotNull($time);
        $this->assertSame('23:54:00', $time->format('H:i:s'));

        $time = $class::parseTime('31c2:54');
        $this->assertNull($time);
    }

    /**
     * Tests disabling leniency when parsing locale format.
     *
     * @dataProvider classNameProvider
     */
    public function testLenientParseDate(string $class): void
    {
        $class::setDefaultLocale('pt_BR');

        $class::disableLenientParsing();
        $time = $class::parseDate('04/21/2013');
        $this->assertSame(null, $time);

        $class::enableLenientParsing();
        $time = $class::parseDate('04/21/2013');
        $this->assertSame('2014-09-04', $time->format('Y-m-d'));
    }

    /**
     * Tests that timeAgoInWords when using a russian locale does not break things
     *
     * @dataProvider classNameProvider
     */
    public function testRussianTimeAgoInWords(string $class): void
    {
        I18n::setLocale('ru_RU');
        $time = new $class('5 days ago');
        $result = $time->timeAgoInWords();
        $this->assertSame('5 days ago', $result);
    }

    /**
     * Tests that parsing a date respects de default timezone in PHP.
     *
     * @dataProvider classNameProvider
     */
    public function testParseDateDifferentTimezone(string $class): void
    {
        date_default_timezone_set('Europe/Paris');
        $class::setDefaultLocale('fr-FR');
        $result = $class::parseDate('12/03/2015');
        $this->assertSame('2015-03-12', $result->format('Y-m-d'));
        $this->assertEquals(new DateTimeZone('Europe/Paris'), $result->tz);
    }

    /**
     * Tests the default locale setter.
     *
     * @dataProvider classNameProvider
     */
    public function testGetSetDefaultLocale(string $class): void
    {
        $class::setDefaultLocale('fr-FR');
        $this->assertSame('fr-FR', $class::getDefaultLocale());
    }

    /**
     * Custom assert to allow for variation in the version of the intl library, where
     * some translations contain a few extra commas.
     */
    public function assertTimeFormat(string $expected, string $result, string $message = ''): void
    {
        $expected = str_replace([',', '(', ')', ' at', ' م.', ' ه‍.ش.', ' AP', ' AH', ' SAKA', 'à '], '', $expected);
        $expected = str_replace(['  '], ' ', $expected);

        $result = str_replace('temps universel coordonné', 'UTC', $result);
        $result = str_replace('Temps universel coordonné', 'UTC', $result);
        $result = str_replace('tiempo universal coordinado', 'GMT', $result);
        $result = str_replace('Coordinated Universal Time', 'GMT', $result);

        $result = str_replace([',', '(', ')', ' at', ' م.', ' ه‍.ش.', ' AP', ' AH', ' SAKA', 'à '], '', $result);
        $result = str_replace(['  '], ' ', $result);

        $this->assertSame($expected, $result, $message);
    }
}
