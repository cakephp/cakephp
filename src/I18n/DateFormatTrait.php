<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Cake\Core\Exception\CakeException;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use IntlDateFormatter;

/**
 * Trait for date formatting methods shared by both Time & Date.
 *
 * This trait expects that the implementing class define static::$_toStringFormat.
 */
trait DateFormatTrait
{
    /**
     * In-memory cache of date formatters
     *
     * @var array<string, \IntlDateFormatter>
     */
    protected static array $formatters = [];

    /**
     * Returns a translated and localized date string.
     * Implements what IntlDateFormatter::formatObject() is in PHP 5.5+
     *
     * @param \DateTimeInterface $date Date.
     * @param array<int>|string $format Format.
     * @param string|null $locale The locale name in which the date should be displayed.
     * @return string
     */
    protected function _formatObject(
        DateTimeInterface $date,
        array|string $format,
        ?string $locale
    ): string {
        $pattern = '';

        if (is_array($format)) {
            [$dateFormat, $timeFormat] = $format;
        } else {
            $dateFormat = IntlDateFormatter::FULL;
            $timeFormat = IntlDateFormatter::FULL;
            $pattern = $format;
        }

        $locale ??= I18n::getLocale();

        if (
            preg_match(
                '/@calendar=(japanese|buddhist|chinese|persian|indian|islamic|hebrew|coptic|ethiopic)/',
                $locale
            )
        ) {
            $calendar = IntlDateFormatter::TRADITIONAL;
        } else {
            $calendar = IntlDateFormatter::GREGORIAN;
        }

        $timezone = $date->getTimezone()->getName();
        $key = "{$locale}.{$dateFormat}.{$timeFormat}.{$timezone}.{$calendar}.{$pattern}";

        if (!isset(static::$formatters[$key])) {
            if ($timezone === '+00:00' || $timezone === 'Z') {
                $timezone = 'UTC';
            } elseif (str_starts_with($timezone, '+') || str_starts_with($timezone, '-')) {
                $timezone = 'GMT' . $timezone;
            }

            $formatter = datefmt_create(
                $locale,
                $dateFormat,
                $timeFormat,
                $timezone,
                $calendar,
                $pattern
            );

            if (!$formatter) {
                throw new CakeException(
                    'Your version of icu does not support creating a date formatter for ' .
                    "`{$key}`. You should try to upgrade libicu and the intl extension."
                );
            }

            static::$formatters[$key] = $formatter;
        }

        return (string)static::$formatters[$key]->format($date);
    }

    /**
     * Returns a new Time object after parsing the provided time string based on
     * the passed or configured date time format. This method is locale dependent,
     * Any string that is passed to this function will be interpreted as a locale
     * dependent string.
     *
     * Unlike DateTime, the time zone of the returned instance is always converted
     * to `$tz` (default time zone if null) even if the `$time` string specified a
     * time zone. This is a limitation of IntlDateFormatter.
     *
     * If it was impossible to parse the provided time, null will be returned.
     *
     * Example:
     *
     * ```
     *  $time = Time::parseDateTime('10/13/2013 12:54am');
     *  $time = Time::parseDateTime('13 Oct, 2013 13:54', 'dd MMM, y H:mm');
     *  $time = Time::parseDateTime('10/10/2015', [IntlDateFormatter::SHORT, IntlDateFormatter::NONE]);
     * ```
     *
     * @param string $time The time string to parse.
     * @param array<int>|string $format Any format accepted by IntlDateFormatter.
     * @param \DateTimeZone|string|null $tz The timezone for the instance
     * @return static|null
     */
    protected static function _parseDateTime(
        string $time,
        array|string $format,
        DateTimeZone|string|null $tz = null
    ): ?static {
        $pattern = '';

        if (is_array($format)) {
            [$dateFormat, $timeFormat] = $format;
        } else {
            $dateFormat = IntlDateFormatter::FULL;
            $timeFormat = IntlDateFormatter::FULL;
            $pattern = $format;
        }

        $locale = DateTime::getDefaultLocale() ?? I18n::getLocale();
        $formatter = datefmt_create(
            $locale,
            $dateFormat,
            $timeFormat,
            $tz,
            null,
            $pattern
        );
        if (!$formatter) {
            throw new CakeException('Unable to create IntlDateFormatter instance');
        }
        $formatter->setLenient(DateTime::lenientParsingEnabled());

        $time = $formatter->parse($time);
        if ($time === false) {
            return null;
        }

        $dateTime = new DateTimeImmutable('@' . $time);

        if (!($tz instanceof DateTimeZone)) {
            $tz = new DateTimeZone($tz ?? date_default_timezone_get());
        }
        $dateTime = $dateTime->setTimezone($tz);

        return new static($dateTime);
    }
}
