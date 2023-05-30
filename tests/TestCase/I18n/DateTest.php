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

use Cake\Cache\Cache;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\I18n\I18n;
use Cake\I18n\Package;
use Cake\TestSuite\TestCase;
use IntlDateFormatter;

/**
 * DateTest class
 */
class DateTest extends TestCase
{
    /**
     * setup
     */
    public function setUp(): void
    {
        parent::setUp();

        Cache::clear('_cake_core_');
        I18n::setTranslator('cake', function () {
            $package = new Package();
            $package->setMessages([
                '{0} ago' => '{0} ago (translated)',
            ]);

            return $package;
        }, 'fr_FR');
    }

    /**
     * Teardown
     */
    public function tearDown(): void
    {
        parent::tearDown();
        DateTime::setDefaultLocale(null);
        date_default_timezone_set('UTC');
    }

    /**
     * Ensure that instances can be built from other objects.
     */
    public function testConstructFromAnotherInstance(): void
    {
        $time = '2015-01-22';
        $frozen = new Date($time);
        $subject = new Date($frozen);
        $this->assertSame($time, $subject->format('Y-m-d'), 'date construction');
    }

    /**
     * test formatting dates taking in account preferred i18n locale file
     */
    public function testI18nFormat(): void
    {
        $time = new Date('Thu Jan 14 13:59:28 2010');
        $result = $time->i18nFormat();
        $expected = '1/14/10';
        $this->assertSame($expected, $result);

        $result = $time->i18nFormat('HH:mm:ss');
        $expected = '00:00:00';
        $this->assertSame($expected, $result);

        DateTime::setDefaultLocale('fr-FR');
        $result = $time->i18nFormat(IntlDateFormatter::FULL);
        $result = str_replace(' à', '', $result);
        $expected = 'jeudi 14 janvier 2010';
        $this->assertStringStartsWith($expected, $result);

        $result = $time->i18nFormat(IntlDateFormatter::FULL, 'es-ES');
        $this->assertStringContainsString('14 de enero de 2010', $result, 'Default locale should not be used');

        $time = new Date('2014-01-01T00:00:00Z');
        $result = $time->i18nFormat(IntlDateFormatter::FULL, 'en-US');
        $result = preg_replace('/[\pZ\pC]/u', ' ', $result);
        $this->assertStringStartsWith('Wednesday, January 1, 2014', $result);
    }

    public function testDiffForHumans(): void
    {
        I18n::setLocale('fr_FR');
        $time = new Date('yesterday');
        $this->assertSame('1 day ago (translated)', $time->diffForHumans());
        I18n::setLocale(I18n::getDefaultLocale());
    }

    /**
     * test __toString
     */
    public function testToString(): void
    {
        $date = new Date('2015-11-06 11:32:45');
        $this->assertSame('11/6/15', (string)$date);
    }

    /**
     * test nice()
     */
    public function testNice(): void
    {
        $date = new Date('2015-11-06 11:32:45');

        $this->assertSame('Nov 6, 2015', $date->nice());
        $this->assertSame('6 nov. 2015', $date->nice('fr-FR'));
    }

    /**
     * test jsonSerialize()
     */
    public function testJsonSerialize(): void
    {
        if (version_compare(INTL_ICU_VERSION, '50.0', '<')) {
            $this->markTestSkipped('ICU 5x is needed');
        }

        $date = new Date('2015-11-06 11:32:45');
        $this->assertSame('"2015-11-06"', json_encode($date));
    }

    /**
     * Tests change JSON encoding format
     */
    public function testSetJsonEncodeFormat(): void
    {
        $date = new Date('2015-11-06 11:32:45');

        Date::setJsonEncodeFormat(static function ($d) {
            return $d->format(DATE_ATOM);
        });
        $this->assertSame('"2015-11-06T00:00:00+00:00"', json_encode($date));

        Date::setJsonEncodeFormat("yyyy-MM-dd'T'HH':'mm':'ssZZZZZ");
        $this->assertSame('"2015-11-06T00:00:00Z"', json_encode($date));
    }

    /**
     * test parseDate()
     */
    public function testParseDate(): void
    {
        $date = Date::parseDate('11/6/15');
        $this->assertSame('2015-11-06 00:00:00', $date->format('Y-m-d H:i:s'));

        DateTime::setDefaultLocale('fr-FR');
        $date = Date::parseDate('13 10, 2015');
        $this->assertSame('2015-10-13 00:00:00', $date->format('Y-m-d H:i:s'));
    }

    /**
     * Tests disabling leniency when parsing locale format.
     */
    public function testLenientParseDate(): void
    {
        DateTime::setDefaultLocale('pt_BR');

        DateTime::disableLenientParsing();
        $date = Date::parseDate('04/21/2013');
        $this->assertSame(null, $date);

        DateTime::enableLenientParsing();
        $date = Date::parseDate('04/21/2013');
        $this->assertSame('2014-09-04', $date->format('Y-m-d'));
    }

    /**
     * provider for timeAgoInWords() tests
     *
     * @return array
     */
    public static function timeAgoProvider(): array
    {
        return [
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
     */
    public function testTimeAgoInWords(string $input, string $expected): void
    {
        $date = new Date($input);
        $result = $date->timeAgoInWords();
        $this->assertEquals($expected, $result);
    }

    /**
     * test the timezone option for timeAgoInWords
     */
    public function testTimeAgoInWordsTimezone(): void
    {
        $date = new Date('1990-07-31 20:33:00 UTC');
        $result = $date->timeAgoInWords(
            [
                'timezone' => 'America/Vancouver',
                'end' => '+1month',
                'format' => 'dd-MM-YYYY',
            ]
        );
        $this->assertSame('on 31-07-1990', $result);
    }

    /**
     * provider for timeAgo with an end date.
     *
     * @return array
     */
    public static function timeAgoEndProvider(): array
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
     * test the end option for timeAgoInWords
     *
     * @dataProvider timeAgoEndProvider
     */
    public function testTimeAgoInWordsEnd(string $input, string $expected, string $end): void
    {
        $time = new Date($input);
        $result = $time->timeAgoInWords(['end' => $end]);
        $this->assertEquals($expected, $result);
    }

    /**
     * test the custom string options for timeAgoInWords
     */
    public function testTimeAgoInWordsCustomStrings(): void
    {
        $date = new Date('-8 years -4 months -2 weeks -3 days');
        $result = $date->timeAgoInWords([
            'relativeString' => 'at least %s ago',
            'accuracy' => ['year' => 'year'],
            'end' => '+10 years',
        ]);
        $expected = 'at least 8 years ago';
        $this->assertSame($expected, $result);

        $date = new Date('+4 months +2 weeks +3 days');
        $result = $date->timeAgoInWords([
            'absoluteString' => 'exactly on %s',
            'accuracy' => ['year' => 'year'],
            'end' => '+2 months',
        ]);
        $expected = 'exactly on ' . date('n/j/y', strtotime('+4 months +2 weeks +3 days'));
        $this->assertSame($expected, $result);
    }

    /**
     * Test the accuracy option for timeAgoInWords()
     */
    public function testDateAgoInWordsAccuracy(): void
    {
        $date = new Date('+8 years +4 months +2 weeks +3 days');
        $result = $date->timeAgoInWords([
            'accuracy' => ['year' => 'year'],
            'end' => '+10 years',
        ]);
        $expected = '8 years';
        $this->assertSame($expected, $result);

        $date = new Date('+8 years +4 months +2 weeks +3 days');
        $result = $date->timeAgoInWords([
            'accuracy' => ['year' => 'month'],
            'end' => '+10 years',
        ]);
        $expected = '8 years, 4 months';
        $this->assertSame($expected, $result);

        $date = new Date('+8 years +4 months +2 weeks +3 days');
        $result = $date->timeAgoInWords([
            'accuracy' => ['year' => 'week'],
            'end' => '+10 years',
        ]);
        $expected = '8 years, 4 months, 2 weeks';
        $this->assertSame($expected, $result);

        $date = new Date('+8 years +4 months +2 weeks +3 days');
        $result = $date->timeAgoInWords([
            'accuracy' => ['year' => 'day'],
            'end' => '+10 years',
        ]);
        $expected = '8 years, 4 months, 2 weeks, 3 days';
        $this->assertSame($expected, $result);

        $date = new Date('+1 years +5 weeks');
        $result = $date->timeAgoInWords([
            'accuracy' => ['year' => 'year'],
            'end' => '+10 years',
        ]);
        $expected = '1 year';
        $this->assertSame($expected, $result);

        $date = new Date('now');
        $result = $date->timeAgoInWords([
            'accuracy' => 'day',
        ]);
        $expected = 'today';
        $this->assertSame($expected, $result);
    }

    /**
     * Test the format option of timeAgoInWords()
     */
    public function testDateAgoInWordsWithFormat(): void
    {
        $date = new Date('2007-9-25');
        $result = $date->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertSame('on 2007-09-25', $result);

        $date = new Date('2007-9-25');
        $result = $date->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertSame('on 2007-09-25', $result);

        $date = new Date('+2 weeks +2 days');
        $result = $date->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertMatchesRegularExpression('/^2 weeks, [1|2] day(s)?$/', $result);

        $date = new Date('+2 months +2 days');
        $result = $date->timeAgoInWords(['end' => '1 month', 'format' => 'yyyy-MM-dd']);
        $this->assertSame('on ' . date('Y-m-d', strtotime('+2 months +2 days')), $result);
    }

    /**
     * test timeAgoInWords() with negative values.
     */
    public function testDateAgoInWordsNegativeValues(): void
    {
        $date = new Date('-2 months -2 days');
        $result = $date->timeAgoInWords(['end' => '3 month']);
        $this->assertSame('2 months, 2 days ago', $result);

        $date = new Date('-2 months -2 days');
        $result = $date->timeAgoInWords(['end' => '3 month']);
        $this->assertSame('2 months, 2 days ago', $result);

        $date = new Date('-2 months -2 days');
        $result = $date->timeAgoInWords(['end' => '1 month', 'format' => 'yyyy-MM-dd']);
        $this->assertSame('on ' . date('Y-m-d', strtotime('-2 months -2 days')), $result);

        $date = new Date('-2 years -5 months -2 days');
        $result = $date->timeAgoInWords(['end' => '3 years']);
        $this->assertSame('2 years, 5 months, 2 days ago', $result);

        $date = new Date('-2 weeks -2 days');
        $result = $date->timeAgoInWords(['format' => 'yyyy-MM-dd']);
        $this->assertSame('2 weeks, 2 days ago', $result);

        $date = new Date('-3 years -12 months');
        $result = $date->timeAgoInWords();
        $expected = 'on ' . $date->format('n/j/y');
        $this->assertSame($expected, $result);

        $date = new Date('-1 month -1 week -6 days');
        $result = $date->timeAgoInWords(
            ['end' => '1 year', 'accuracy' => ['month' => 'month']]
        );
        $this->assertSame('1 month ago', $result);

        $date = new Date('-1 years -2 weeks -3 days');
        $result = $date->timeAgoInWords(
            ['accuracy' => ['year' => 'year']]
        );
        $expected = 'on ' . $date->format('n/j/y');
        $this->assertSame($expected, $result);

        $date = new Date('-13 months -5 days');
        $result = $date->timeAgoInWords(['end' => '2 years']);
        $this->assertSame('1 year, 1 month, 5 days ago', $result);
    }

    /**
     * Tests that parsing a date in a timezone other than UTC
     * will not alter the date
     */
    public function testParseDateDifferentTimezone(): void
    {
        date_default_timezone_set('Europe/Paris');
        $result = Date::parseDate('25-02-2016', 'd-M-y');
        $this->assertSame('25-02-2016', $result->format('d-m-Y'));
    }

    /**
     * Tests the default locale setter.
     */
    public function testDefaultLocaleEffectsFormatting(): void
    {
        $result = Date::parseDate('12/03/2015');
        $this->assertSame('Dec 3, 2015', $result->nice());

        DateTime::setDefaultLocale('fr-FR');

        $result = Date::parseDate('12/03/2015');
        $this->assertSame('12 mars 2015', $result->nice());

        $expected = 'Y-m-d';
        $result = Date::parseDate('12/03/2015');
        $this->assertSame('2015-03-12', $result->format($expected));
    }
}
