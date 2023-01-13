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

use Cake\Chronos\ChronosDate;
use Cake\Chronos\DifferenceFormatterInterface;
use Cake\Core\Exception\CakeException;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use IntlDateFormatter;
use InvalidArgumentException;

/**
 * Trait for date formatting methods shared by both Time & Date.
 *
 * This trait expects that the implementing class define static::$_toStringFormat.
 */
trait DateFormatTrait
{
    /**
     * Returns a nicely formatted date string for this object.
     *
     * The format to be used is stored in the static property `Time::niceFormat`.
     *
     * @param \DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * in which the date will be displayed. The timezone stored for this object will not
     * be changed.
     * @param string|null $locale The locale name in which the date should be displayed (e.g. pt-BR)
     * @return string Formatted date string
     */
    public function nice(DateTimeZone|string|null $timezone = null, ?string $locale = null): string
    {
        return (string)$this->i18nFormat(static::$niceFormat, $timezone, $locale);
    }

    /**
     * Returns a formatted string for this time object using the preferred format and
     * language for the specified locale.
     *
     * It is possible to specify the desired format for the string to be displayed.
     * You can either pass `IntlDateFormatter` constants as the first argument of this
     * function, or pass a full ICU date formatting string as specified in the following
     * resource: https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax.
     *
     * Additional to `IntlDateFormatter` constants and date formatting string you can use
     * Time::UNIX_TIMESTAMP_FORMAT to get a unix timestamp
     *
     * ### Examples
     *
     * ```
     * $time = new Time('2014-04-20 22:10');
     * $time->i18nFormat(); // outputs '4/20/14, 10:10 PM' for the en-US locale
     * $time->i18nFormat(\IntlDateFormatter::FULL); // Use the full date and time format
     * $time->i18nFormat([\IntlDateFormatter::FULL, \IntlDateFormatter::SHORT]); // Use full date but short time format
     * $time->i18nFormat('yyyy-MM-dd HH:mm:ss'); // outputs '2014-04-20 22:10'
     * $time->i18nFormat(Time::UNIX_TIMESTAMP_FORMAT); // outputs '1398031800'
     * ```
     *
     * You can control the default format used through `Time::setToStringFormat()`.
     *
     * You can read about the available IntlDateFormatter constants at
     * https://secure.php.net/manual/en/class.intldateformatter.php
     *
     * If you need to display the date in a different timezone than the one being used for
     * this Time object without altering its internal state, you can pass a timezone
     * string or object as the second parameter.
     *
     * Finally, should you need to use a different locale for displaying this time object,
     * pass a locale string as the third parameter to this function.
     *
     * ### Examples
     *
     * ```
     * $time = new Time('2014-04-20 22:10');
     * $time->i18nFormat(null, null, 'de-DE');
     * $time->i18nFormat(\IntlDateFormatter::FULL, 'Europe/Berlin', 'de-DE');
     * ```
     *
     * You can control the default locale used through `Time::setDefaultLocale()`.
     * If empty, the default will be taken from the `intl.default_locale` ini config.
     *
     * @param array<int>|string|int|null $format Format string.
     * @param \DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * in which the date will be displayed. The timezone stored for this object will not
     * be changed.
     * @param string|null $locale The locale name in which the date should be displayed (e.g. pt-BR)
     * @return string|int Formatted and translated date string
     */
    public function i18nFormat(
        array|string|int|null $format = null,
        DateTimeZone|string|null $timezone = null,
        ?string $locale = null
    ): string|int {
        if ($format === DateTime::UNIX_TIMESTAMP_FORMAT) {
            if ($this instanceof ChronosDate) {
                return $this->native->getTimestamp();
            } else {
                return $this->getTimestamp();
            }
        }

        $time = $this;

        if ($time instanceof DateTime && $timezone) {
            $time = $time->setTimezone($timezone);
        }

        $format = $format ?? static::$_toStringFormat;
        $locale = $locale ?: DateTime::getDefaultLocale();

        return $this->_formatObject($time instanceof DateTimeInterface ? $time : $time->native, $format, $locale);
    }

    /**
     * Returns a translated and localized date string.
     * Implements what IntlDateFormatter::formatObject() is in PHP 5.5+
     *
     * @param \DateTimeInterface $date Date.
     * @param array<int>|string|int $format Format.
     * @param string|null $locale The locale name in which the date should be displayed.
     * @return string
     */
    protected function _formatObject(
        DateTimeInterface $date,
        array|string|int $format,
        ?string $locale
    ): string {
        $pattern = '';

        if (is_array($format)) {
            [$dateFormat, $timeFormat] = $format;
        } elseif (is_int($format)) {
            $dateFormat = $timeFormat = $format;
        } else {
            $dateFormat = $timeFormat = IntlDateFormatter::FULL;
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
        if ($timezone === '+00:00' || $timezone === 'Z') {
            $timezone = 'UTC';
        } elseif ($timezone[0] === '+' || $timezone[0] === '-') {
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
            $key = "{$locale}.{$dateFormat}.{$timeFormat}.{$timezone}.{$calendar}.{$pattern}";
            throw new CakeException(
                'Your version of icu does not support creating a date formatter for ' .
                "`$key`. You should try to upgrade libicu and the intl extension."
            );
        }

        return (string)$formatter->format($date->format('U'));
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return (string)$this->i18nFormat();
    }

    /**
     * Returns a new Time object after parsing the provided time string based on
     * the passed or configured date time format. This method is locale dependent,
     * Any string that is passed to this function will be interpreted as a locale
     * dependent string.
     *
     * When no $format is provided, the `toString` format will be used.
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
     * @param array<int>|string|int|null $format Any format accepted by IntlDateFormatter.
     * @param \DateTimeZone|string|null $tz The timezone for the instance
     * @return static|null
     */
    public static function parseDateTime(
        string $time,
        array|string|int|null $format = null,
        DateTimeZone|string|null $tz = null
    ): ?static {
        $format = $format ?? static::$_toStringFormat;
        $pattern = '';

        if (is_array($format)) {
            [$dateFormat, $timeFormat] = $format;
        } elseif (is_int($format)) {
            $dateFormat = $timeFormat = $format;
        } else {
            $dateFormat = $timeFormat = IntlDateFormatter::FULL;
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

    /**
     * Returns a new Time object after parsing the provided $date string based on
     * the passed or configured date time format. This method is locale dependent,
     * Any string that is passed to this function will be interpreted as a locale
     * dependent string.
     *
     * When no $format is provided, the `wordFormat` format will be used.
     *
     * If it was impossible to parse the provided time, null will be returned.
     *
     * Example:
     *
     * ```
     *  $time = Time::parseDate('10/13/2013');
     *  $time = Time::parseDate('13 Oct, 2013', 'dd MMM, y');
     *  $time = Time::parseDate('13 Oct, 2013', IntlDateFormatter::SHORT);
     * ```
     *
     * @param string $date The date string to parse.
     * @param array|string|int|null $format Any format accepted by IntlDateFormatter.
     * @return static|null
     */
    public static function parseDate(string $date, array|string|int|null $format = null): ?static
    {
        if (is_int($format)) {
            $format = [$format, IntlDateFormatter::NONE];
        }
        $format = $format ?: static::$wordFormat;

        return static::parseDateTime($date, $format);
    }

    /**
     * Returns a new Time object after parsing the provided $time string based on
     * the passed or configured date time format. This method is locale dependent,
     * Any string that is passed to this function will be interpreted as a locale
     * dependent string.
     *
     * When no $format is provided, the IntlDateFormatter::SHORT format will be used.
     *
     * If it was impossible to parse the provided time, null will be returned.
     *
     * Example:
     *
     * ```
     *  $time = Time::parseTime('11:23pm');
     * ```
     *
     * @param string $time The time string to parse.
     * @param array|string|int|null $format Any format accepted by IntlDateFormatter.
     * @return static|null
     */
    public static function parseTime(string $time, array|string|int|null $format = null): ?static
    {
        if (is_int($format)) {
            $format = [IntlDateFormatter::NONE, $format];
        }
        $format = $format ?: [IntlDateFormatter::NONE, IntlDateFormatter::SHORT];

        return static::parseDateTime($time, $format);
    }

    /**
     * Returns a string that should be serialized when converting this object to JSON
     *
     * @return string|int
     */
    public function jsonSerialize(): mixed
    {
        if (static::$_jsonEncodeFormat instanceof Closure) {
            return call_user_func(static::$_jsonEncodeFormat, $this);
        }

        return $this->i18nFormat(static::$_jsonEncodeFormat);
    }

    /**
     * Get the difference formatter instance.
     *
     * @param \Cake\Chronos\DifferenceFormatterInterface $formatter Difference formatter
     * @return \Cake\I18n\RelativeTimeFormatter
     */
    public static function diffFormatter(?DifferenceFormatterInterface $formatter = null): RelativeTimeFormatter
    {
        if ($formatter) {
            if (!$formatter instanceof RelativeTimeFormatter) {
                throw new InvalidArgumentException('Formatter for I18n must extend RelativeTimeFormatter.');
            }

            return static::$diffFormatter = $formatter;
        }

        /** @var \Cake\I18n\RelativeTimeFormatter $formatter */
        $formatter = static::$diffFormatter ??= new RelativeTimeFormatter();

        return $formatter;
    }
}
