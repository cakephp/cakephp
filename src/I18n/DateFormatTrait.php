<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Cake\Chronos\Date as ChronosDate;
use Cake\Chronos\MutableDate;
use IntlDateFormatter;

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
     * @var string
     * @deprecated 3.2.9 Use static::setDefaultLocale() and static::getDefaultLocale() instead.
     */
    public static $defaultLocale;

    /**
     * In-memory cache of date formatters
     *
     * @var array
     */
    protected static $_formatters = [];

    /**
     * The format to use when when converting this object to json
     *
     * The format should be either the formatting constants from IntlDateFormatter as
     * described in (http://www.php.net/manual/en/class.intldateformatter.php) or a pattern
     * as specified in (http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details)
     *
     * It is possible to provide an array of 2 constants. In this case, the first position
     * will be used for formatting the date part of the object and the second position
     * will be used to format the time part.
     *
     * @var string|array|int
     * @see \Cake\I18n\Time::i18nFormat()
     */
    protected static $_jsonEncodeFormat = "yyyy-MM-dd'T'HH:mm:ssxxx";

    /**
     * Caches whether or not this class is a subclass of a Date or MutableDate
     *
     * @var bool
     */
    protected static $_isDateInstance;

    /**
     * Gets the default locale.
     *
     * @return string|null The default locale string to be used or null.
     */
    public static function getDefaultLocale()
    {
        return static::$defaultLocale;
    }

    /**
     * Sets the default locale.
     *
     * @param string|null $locale The default locale string to be used or null.
     * @return void
     */
    public static function setDefaultLocale($locale = null)
    {
        static::$defaultLocale = $locale;
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
    public function nice($timezone = null, $locale = null)
    {
        return $this->i18nFormat(static::$niceFormat, $timezone, $locale);
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
     * If you wish to control the default format to be used for this method, you can alter
     * the value of the static `Time::$defaultLocale` variable and set it to one of the
     * possible formats accepted by this function.
     *
     * You can read about the available IntlDateFormatter constants at
     * http://www.php.net/manual/en/class.intldateformatter.php
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
     * You can control the default locale to be used by setting the static variable
     * `Time::$defaultLocale` to a  valid locale string. If empty, the default will be
     * taken from the `intl.default_locale` ini config.
     *
     * @param string|int|null $format Format string.
     * @param string|\DateTimeZone|null $timezone Timezone string or DateTimeZone object
     * in which the date will be displayed. The timezone stored for this object will not
     * be changed.
     * @param string|null $locale The locale name in which the date should be displayed (e.g. pt-BR)
     * @return string Formatted and translated date string
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

        $format = $format !== null ? $format : static::$_toStringFormat;
        $locale = $locale ?: static::$defaultLocale;

        return $this->_formatObject($time, $format, $locale);
    }

    /**
     * Returns a translated and localized date string.
     * Implements what IntlDateFormatter::formatObject() is in PHP 5.5+
     *
     * @param \DateTime $date Date.
     * @param string|int|array $format Format.
     * @param string $locale The locale name in which the date should be displayed.
     * @return string
     */
    protected function _formatObject($date, $format, $locale)
    {
        $pattern = $dateFormat = $timeFormat = $calendar = null;

        if (is_array($format)) {
            list($dateFormat, $timeFormat) = $format;
        } elseif (is_numeric($format)) {
            $dateFormat = $format;
        } else {
            $dateFormat = $timeFormat = IntlDateFormatter::FULL;
            $pattern = $format;
        }

        if (preg_match('/@calendar=(japanese|buddhist|chinese|persian|indian|islamic|hebrew|coptic|ethiopic)/', $locale)) {
            $calendar = IntlDateFormatter::TRADITIONAL;
        } else {
            $calendar = IntlDateFormatter::GREGORIAN;
        }

        $timezone = $date->getTimezone()->getName();
        $key = "{$locale}.{$dateFormat}.{$timeFormat}.{$timezone}.{$calendar}.{$pattern}";

        if (!isset(static::$_formatters[$key])) {
            if ($timezone === '+00:00') {
                $timezone = 'UTC';
            } elseif ($timezone[0] === '+' || $timezone[0] === '-') {
                $timezone = 'GMT' . $timezone;
            }
            static::$_formatters[$key] = datefmt_create(
                $locale,
                $dateFormat,
                $timeFormat,
                $timezone,
                $calendar,
                $pattern
            );
        }

        return static::$_formatters[$key]->format($date->format('U'));
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->i18nFormat();
    }

    /**
     * Resets the format used to the default when converting an instance of this type to
     * a string
     *
     * @return void
     */
    public static function resetToStringFormat()
    {
        static::setToStringFormat([IntlDateFormatter::SHORT, IntlDateFormatter::SHORT]);
    }

    /**
     * Sets the default format used when type converting instances of this type to string
     *
     * @param string|array|int $format Format.
     * @return void
     */
    public static function setToStringFormat($format)
    {
        static::$_toStringFormat = $format;
    }

    /**
     * Sets the default format used when converting this object to json
     *
     * @param string|array|int $format Format.
     * @return void
     */
    public static function setJsonEncodeFormat($format)
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
     * If it was impossible to parse the provided time, null will be returned.
     *
     * Example:
     *
     * ```
     *  $time = Time::parseDateTime('10/13/2013 12:54am');
     *  $time = Time::parseDateTime('13 Oct, 2013 13:54', 'dd MMM, y H:mm');
     *  $time = Time::parseDateTime('10/10/2015', [IntlDateFormatter::SHORT, -1]);
     * ```
     *
     * @param string $time The time string to parse.
     * @param string|array|null $format Any format accepted by IntlDateFormatter.
     * @return static|null
     */
    public static function parseDateTime($time, $format = null)
    {
        $dateFormat = $format ?: static::$_toStringFormat;
        $timeFormat = $pattern = null;

        if (is_array($dateFormat)) {
            list($newDateFormat, $timeFormat) = $dateFormat;
            $dateFormat = $newDateFormat;
        } else {
            $pattern = $dateFormat;
            $dateFormat = null;
        }

        if (static::$_isDateInstance === null) {
            static::$_isDateInstance =
                is_subclass_of(static::class, ChronosDate::class) ||
                is_subclass_of(static::class, MutableDate::class);
        }

        $defaultTimezone = static::$_isDateInstance ? 'UTC' : date_default_timezone_get();
        $formatter = datefmt_create(
            static::$defaultLocale,
            $dateFormat,
            $timeFormat,
            $defaultTimezone,
            null,
            $pattern
        );
        $time = $formatter->parse($time);
        if ($time !== false) {
            $result = new static('@' . $time);

            return static::$_isDateInstance ? $result : $result->setTimezone($defaultTimezone);
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
     * @param string|int|null $format Any format accepted by IntlDateFormatter.
     * @return static|null
     */
    public static function parseDate($date, $format = null)
    {
        if (is_int($format)) {
            $format = [$format, -1];
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
    public static function parseTime($time, $format = null)
    {
        if (is_int($format)) {
            $format = [-1, $format];
        }
        $format = $format ?: [-1, IntlDateFormatter::SHORT];

        return static::parseDateTime($time, $format);
    }

    /**
     * Returns a string that should be serialized when converting this object to json
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->i18nFormat(static::$_jsonEncodeFormat);
    }

    /**
     * Get the difference formatter instance or overwrite the current one.
     *
     * @param \Cake\I18n\RelativeTimeFormatter|null $formatter The formatter instance when setting.
     * @return \Cake\I18n\RelativeTimeFormatter The formatter instance.
     */
    public static function diffFormatter($formatter = null)
    {
        if ($formatter === null) {
            // Use the static property defined in chronos.
            if (static::$diffFormatter === null) {
                static::$diffFormatter = new RelativeTimeFormatter();
            }

            return static::$diffFormatter;
        }

        return static::$diffFormatter = $formatter;
    }

    /**
     * Returns the data that should be displayed when debugging this object
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'time' => $this->toIso8601String(),
            'timezone' => $this->getTimezone()->getName(),
            'fixedNowTime' => $this->hasTestNow() ? $this->getTestNow()->toIso8601String() : false
        ];
    }
}
