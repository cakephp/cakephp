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

use Cake\Chronos\DifferenceFormatterInterface;
use Closure;
use DateTime;
use DateTimeZone;
use IntlDateFormatter;
use RuntimeException;

/**
 * Trait for date formatting methods shared by both Time & Date.
 *
 * This trait expects that the implementing class define static::$_toStringFormat.
 */
trait DateFormatTrait
{
    /**
     * The default locale to be used for displaying formatted date strings.
     *
     * Use static::setDefaultLocale() and static::getDefaultLocale() instead.
     *
     * @var string|null
     */
    protected static $defaultLocale;

    /**
     * Whether lenient parsing is enabled for IntlDateFormatter.
     *
     * Defaults to true which is the default for IntlDateFormatter.
     *
     * @var bool
     */
    protected static $lenientParsing = true;

    /**
     * In-memory cache of date formatters
     *
     * @var \IntlDateFormatter[]
     */
    protected static $_formatters = [];

    /**
     * Gets the default locale.
     *
     * @return string|null The default locale string to be used or null.
     */
    public static function getDefaultLocale(): ?string
    {
        return static::$defaultLocale;
    }

    /**
     * Sets the default locale.
     *
     * Set to null to use IntlDateFormatter default.
     *
     * @param string|null $locale The default locale string to be used.
     * @return void
     */
    public static function setDefaultLocale(?string $locale = null): void
    {
        static::$defaultLocale = $locale;
    }

    /**
     * Gets whether locale format parsing is set to lenient.
     *
     * @return bool
     */
    public static function lenientParsingEnabled(): bool
    {
        return static::$lenientParsing;
    }

    /**
     * Enables lenient parsing for locale formats.
     *
     * @return void
     */
    public static function enableLenientParsing(): void
    {
        static::$lenientParsing = true;
    }

    /**
     * Enables lenient parsing for locale formats.
     *
     * @return void
     */
    public static function disableLenientParsing(): void
    {
        static::$lenientParsing = false;
    }

    /**
     * Returns a nicely formatted date string for this object.
     *
     * The format to be used is stored in the static property `Time::niceFormat`.
     *
     * @param string|\DateTimeZone|null $timezone Timezone string or DateTimeZone object
     * in which the date will be displayed. The timezone stored for this object will not
     * be changed.
     * @param string|null $locale The locale name in which the date should be displayed (e.g. pt-BR)
     * @return string Formatted date string
     */
    public function nice($timezone = null, $locale = null): string
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
     * resource: http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details.
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
     * @param string|int|int[]|null $format Format string.
     * @param string|\DateTimeZone|null $timezone Timezone string or DateTimeZone object
     * in which the date will be displayed. The timezone stored for this object will not
     * be changed.
     * @param string|null $locale The locale name in which the date should be displayed (e.g. pt-BR)
     * @return string|int Formatted and translated date string
     */
    public function i18nFormat($format = null, $timezone = null, $locale = null)
    {
        if ($format === Time::UNIX_TIMESTAMP_FORMAT) {
            return $this->getTimestamp();
        }

        $time = $this;

        if ($timezone) {
            // Handle the immutable and mutable object cases.
            $time = clone $this;
            $time = $time->timezone($timezone);
        }

        $format = $format ?? static::$_toStringFormat;
        $locale = $locale ?: static::$defaultLocale;

        return $this->_formatObject($time, $format, $locale);
    }

    /**
     * Returns a translated and localized date string.
     * Implements what IntlDateFormatter::formatObject() is in PHP 5.5+
     *
     * @param \DateTime|\DateTimeImmutable $date Date.
     * @param string|int|int[] $format Format.
     * @param string|null $locale The locale name in which the date should be displayed.
     * @return string
     */
    protected function _formatObject($date, $format, ?string $locale): string
    {
        $pattern = '';

        if (is_array($format)) {
            [$dateFormat, $timeFormat] = $format;
        } elseif (is_int($format)) {
            $dateFormat = $timeFormat = $format;
        } else {
            $dateFormat = $timeFormat = IntlDateFormatter::FULL;
            $pattern = $format;
        }

        if ($locale === null) {
            $locale = I18n::getLocale();
        }

        // phpcs:ignore Generic.Files.LineLength
        if (preg_match('/@calendar=(japanese|buddhist|chinese|persian|indian|islamic|hebrew|coptic|ethiopic)/', $locale)) {
            $calendar = IntlDateFormatter::TRADITIONAL;
        } else {
            $calendar = IntlDateFormatter::GREGORIAN;
        }

        $timezone = $date->getTimezone()->getName();
        $key = "{$locale}.{$dateFormat}.{$timeFormat}.{$timezone}.{$calendar}.{$pattern}";

        if (!isset(static::$_formatters[$key])) {
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
            if ($formatter === false) {
                throw new RuntimeException(
                    'Your version of icu does not support creating a date formatter for ' .
                    "`$key`. You should try to upgrade libicu and the intl extension."
                );
            }
            static::$_formatters[$key] = $formatter;
        }

        return static::$_formatters[$key]->format($date->format('U'));
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return (string)$this->i18nFormat();
    }

    /**
     * Resets the format used to the default when converting an instance of this type to
     * a string
     *
     * @return void
     */
    public static function resetToStringFormat(): void
    {
        static::setToStringFormat([IntlDateFormatter::SHORT, IntlDateFormatter::SHORT]);
    }

    /**
     * Sets the default format used when type converting instances of this type to string
     *
     * The format should be either the formatting constants from IntlDateFormatter as
     * described in (https://secure.php.net/manual/en/class.intldateformatter.php) or a pattern
     * as specified in (http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details)
     *
     * It is possible to provide an array of 2 constants. In this case, the first position
     * will be used for formatting the date part of the object and the second position
     * will be used to format the time part.
     *
     * @param string|int|int[] $format Format.
     * @return void
     */
    public static function setToStringFormat($format): void
    {
        static::$_toStringFormat = $format;
    }

    /**
     * @inheritDoc
     */
    public static function setJsonEncodeFormat($format): void
    {
        static::$_jsonEncodeFormat = $format;
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
     * @param string|int|int[]|null $format Any format accepted by IntlDateFormatter.
     * @param \DateTimeZone|string|null $tz The timezone for the instance
     * @return static|null
     */
    public static function parseDateTime(string $time, $format = null, $tz = null)
    {
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

        $locale = static::$defaultLocale ?? I18n::getLocale();
        $formatter = datefmt_create(
            $locale,
            $dateFormat,
            $timeFormat,
            $tz,
            null,
            $pattern
        );
        $formatter->setLenient(static::$lenientParsing);

        $time = $formatter->parse($time);
        if ($time !== false) {
            $dateTime = new DateTime('@' . $time);

            if (!($tz instanceof DateTimeZone)) {
                $tz = new DateTimeZone($tz ?? date_default_timezone_get());
            }
            $dateTime->setTimezone($tz);

            return new static($dateTime);
        }

        return null;
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
     * @param string|int|array|null $format Any format accepted by IntlDateFormatter.
     * @return static|null
     */
    public static function parseDate(string $date, $format = null)
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
     * @param string|int|null $format Any format accepted by IntlDateFormatter.
     * @return static|null
     */
    public static function parseTime(string $time, $format = null)
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
    public function jsonSerialize()
    {
        if (static::$_jsonEncodeFormat instanceof Closure) {
            return call_user_func(static::$_jsonEncodeFormat, $this);
        }

        return $this->i18nFormat(static::$_jsonEncodeFormat);
    }

    /**
     * Get the difference formatter instance.
     *
     * @return \Cake\Chronos\DifferenceFormatterInterface
     */
    public static function getDiffFormatter(): DifferenceFormatterInterface
    {
        // Use the static property defined in chronos.
        if (static::$diffFormatter === null) {
            static::$diffFormatter = new RelativeTimeFormatter();
        }

        return static::$diffFormatter;
    }

    /**
     * Set the difference formatter instance.
     *
     * @param \Cake\Chronos\DifferenceFormatterInterface $formatter The formatter instance when setting.
     * @return void
     */
    public static function setDiffFormatter(DifferenceFormatterInterface $formatter): void
    {
        static::$diffFormatter = $formatter;
    }

    /**
     * Returns the data that should be displayed when debugging this object
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        /** @psalm-suppress PossiblyNullReference */
        return [
            'time' => $this->format('Y-m-d H:i:s.uP'),
            'timezone' => $this->getTimezone()->getName(),
            'fixedNowTime' => static::hasTestNow() ? static::getTestNow()->format('Y-m-d\TH:i:s.uP') : false,
        ];
    }
}
