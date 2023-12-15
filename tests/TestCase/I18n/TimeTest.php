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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n;

use Cake\Chronos\Chronos;
use Cake\I18n\DateTime;
use Cake\I18n\I18n;
use Cake\I18n\Time;
use Cake\TestSuite\TestCase;
use DateTimeImmutable;
use IntlDateFormatter;
use InvalidArgumentException;

class TimeTest extends TestCase
{
    /**
     * @var \Cake\Chronos\Chronos|null
     */
    protected $now;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->now = DateTime::getTestNow();
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        DateTime::setTestNow($this->now);
        DateTime::setDefaultLocale(null);
        Time::resetToStringFormat();

        I18n::setLocale(I18n::getDefaultLocale());
    }

    public function testConstruct(): void
    {
        $time = '10:33:44.123456';

        $mut = new Time($time);
        $subject = new Time($mut);
        $this->assertSame($time, $subject->format('H:i:s.u'));

        $mut = new Chronos($time);
        $subject = new Time($mut);
        $this->assertSame($time, $subject->format('H:i:s.u'));

        $mut = new DateTimeImmutable($time);
        $subject = new Time($mut);
        $this->assertSame($time, $subject->format('H:i:s.u'));
    }

    public function testNice(): void
    {
        $time = new Time('20:00');
        $this->assertTimeFormat('8:00:00 PM', $time->nice());
        $this->assertTimeFormat('20:00:00', $time->nice('fr-FR'));

        // Test with custom default locale
        DateTime::setDefaultLocale('fr-FR');
        $this->assertTimeFormat('20:00:00', $time->nice());
    }

    /**
     * test formatting dates taking in account preferred i18n locale file
     */
    public function testI18nFormat(): void
    {
        $time = new Time('13:59:28');

        // Test the default format which should be SHORT
        $this->assertTimeFormat('1:59 PM', $time->i18nFormat());

        // Test using a time-specific format
        $this->assertTimeFormat('1:59:28 PM', $time->i18nFormat(IntlDateFormatter::MEDIUM));

        // Test using a specific format and locale
        $this->assertTimeFormat('13:59:28', $time->i18nFormat(IntlDateFormatter::MEDIUM, 'es-ES'));

        // Test with custom default locale
        DateTime::setDefaultLocale('es-ES');
        $this->assertTimeFormat('13:59:28', $time->i18nFormat(IntlDateFormatter::MEDIUM));
    }

    /**
     * testI18nFormatUsingSystemLocale
     */
    public function testI18nFormatUsingSystemLocale(): void
    {
        $time = new Time('12:00:00');
        I18n::setLocale('es');
        $this->assertTimeFormat('12:00:00', $time->i18nFormat("HH':'mm':'ss"));
    }

    public function testToString(): void
    {
        $time = new Time('22:10');
        DateTime::setDefaultLocale('fr-FR');
        Time::setToStringFormat(IntlDateFormatter::MEDIUM);
        $this->assertTimeFormat('22:10:00', (string)$time);
    }

    /**
     * Tests encoding a Time object as JSON
     */
    public function testJsonEncode(): void
    {
        $time = new Time('10:10:10');
        $this->assertTimeFormat('"10:10:10"', json_encode($time));

        Time::setJsonEncodeFormat('HH:mm:ss');
        $this->assertTimeFormat('"10:10:10"', json_encode($time));

        Time::setJsonEncodeFormat(fn (Time $time) => 'custom format');
        $this->assertTimeFormat('"custom format"', json_encode($time));
    }

    public function testInvalidJsonEncodeFormat()
    {
        $this->expectException(InvalidArgumentException::class);
        Time::setJsonEncodeFormat(DateTime::UNIX_TIMESTAMP_FORMAT);
        json_encode(new Time('10:10:10'));
    }

    /**
     * Tests parsing times using the parseTime function
     */
    public function testParseTime(): void
    {
        $time = Time::parseTime('12:54am');
        $this->assertNotNull($time);
        $this->assertSame('00:54:00', $time->format('H:i:s'));

        $time = Time::parseTime('12:54am', IntlDateFormatter::SHORT);
        $this->assertNotNull($time);
        $this->assertSame('00:54:00', $time->format('H:i:s'));

        $time = Time::parseTime('12:54', "HH':'ss");
        $this->assertNotNull($time);
        $this->assertSame('12:00:54', $time->format('H:i:s'));

        DateTime::setDefaultLocale('fr-FR');
        $time = Time::parseTime('23:54');
        $this->assertNotNull($time);
        $this->assertSame('23:54:00', $time->format('H:i:s'));

        $time = Time::parseTime('31c2:54');
        $this->assertNull($time);
    }

    /**
     * Custom assert to allow for variation in the version of the intl library, where
     * some translations contain a few extra commas.
     */
    public function assertTimeFormat(string $expected, string $result, string $message = ''): void
    {
        $expected = str_replace([',', '(', ')', ' at', ' م.', ' ه‍.ش.', ' AP', ' AH', ' SAKA', 'à '], '', $expected);
        $expected = str_replace(['  ', ' '], ' ', $expected);

        $result = str_replace('temps universel coordonné', 'UTC', $result);
        $result = str_replace('Temps universel coordonné', 'UTC', $result);
        $result = str_replace('tiempo universal coordinado', 'UTC', $result);
        $result = str_replace('Coordinated Universal Time', 'UTC', $result);

        $result = str_replace([',', '(', ')', ' at', ' م.', ' ه‍.ش.', ' AP', ' AH', ' SAKA', 'à '], '', $result);
        $result = str_replace(['  ', ' '], ' ', $result);

        $this->assertSame($expected, $result, $message);
    }
}
