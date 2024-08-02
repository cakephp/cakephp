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

use Cake\Chronos\Chronos;
use Cake\Chronos\DifferenceFormatterInterface;
use Closure;
use DateTimeZone;
use IntlDateFormatter;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Extends the built-in DateTime class to provide handy methods and locale-aware
 * formatting helpers.
 *
 * @psalm-immutable
 */
class DateTime extends Chronos implements JsonSerializable, Stringable
{
    use DateFormatTrait;

    /**
     * The default locale to be used for displaying formatted date strings.
     *
     * Use static::setDefaultLocale() and static::getDefaultLocale() instead.
     *
     * @var string|null
     */
    protected static ?string $defaultLocale = null;

    /**
     * Whether lenient parsing is enabled for IntlDateFormatter.
     *
     * Defaults to true which is the default for IntlDateFormatter.
     *
     * @var bool
     */
    protected static bool $lenientParsing = true;

    /**
     * The format to use when formatting a time using `Cake\I18n\DateTime::i18nFormat()`
     * and `__toString`. This format is also used by `parseDateTime()`.
     *
     * The format should be either the formatting constants from IntlDateFormatter as
     * described in (https://secure.php.net/manual/en/class.intldateformatter.php) or a pattern
     * as specified in (https://unicode-org.github.io/icu-docs/apidoc/released/icu4c/classSimpleDateFormat.html#details)
     *
     * It is possible to provide an array of 2 constants. In this case, the first position
     * will be used for formatting the date part of the object and the second position
     * will be used to format the time part.
     *
     * @var array<int>|string|int
     * @see \Cake\I18n\DateTime::i18nFormat()
     */
    protected static array|string|int $_toStringFormat = [IntlDateFormatter::SHORT, IntlDateFormatter::SHORT];

    /**
     * The format to use when converting this object to JSON.
     *
     * The format should be either the formatting constants from IntlDateFormatter as
     * described in (https://secure.php.net/manual/en/class.intldateformatter.php) or a pattern
     * as specified in (https://unicode-org.github.io/icu-docs/apidoc/released/icu4c/classSimpleDateFormat.html#details)
     *
     * It is possible to provide an array of 2 constants. In this case, the first position
     * will be used for formatting the date part of the object and the second position
     * will be used to format the time part.
     *
     * @var \Closure|array<int>|string|int
     * @see \Cake\I18n\DateTime::i18nFormat()
     */
    protected static Closure|array|string|int $_jsonEncodeFormat = "yyyy-MM-dd'T'HH':'mm':'ssxxx";

    /**
     * The format to use when formatting a time using `Cake\I18n\DateTime::nice()`
     *
     * The format should be either the formatting constants from IntlDateFormatter as
     * described in (https://secure.php.net/manual/en/class.intldateformatter.php) or a pattern
     * as specified in (https://unicode-org.github.io/icu-docs/apidoc/released/icu4c/classSimpleDateFormat.html#details)
     *
     * It is possible to provide an array of 2 constants. In this case, the first position
     * will be used for formatting the date part of the object and the second position
     * will be used to format the time part.
     *
     * @var array<int>|string|int
     * @see \Cake\I18n\DateTime::nice()
     */
    public static array|string|int $niceFormat = [IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT];

    /**
     * The format to use when formatting a time using `Cake\I18n\DateTime::timeAgoInWords()`
     * and the difference is more than `Cake\I18n\DateTime::$wordEnd`
     *
     * @var array<int>|string|int
     * @see \Cake\I18n\DateTime::timeAgoInWords()
     */
    public static array|string|int $wordFormat = [IntlDateFormatter::SHORT, IntlDateFormatter::NONE];

    /**
     * The format to use when formatting a time using `DateTime::timeAgoInWords()`
     * and the difference is less than `DateTime::$wordEnd`
     *
     * @var array<string>
     * @see \Cake\I18n\DateTime::timeAgoInWords()
     */
    public static array $wordAccuracy = [
        'year' => 'day',
        'month' => 'day',
        'week' => 'day',
        'day' => 'hour',
        'hour' => 'minute',
        'minute' => 'minute',
        'second' => 'second',
    ];

    /**
     * The end of relative time telling
     *
     * @var string
     * @see \Cake\I18n\DateTime::timeAgoInWords()
     */
    public static string $wordEnd = '+1 month';

    /**
     * serialise the value as a Unix Timestamp
     *
     * @var string
     */
    public const UNIX_TIMESTAMP_FORMAT = 'unixTimestampFormat';

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
     * Sets the default format used when type converting instances of this type to string
     *
     * The format should be either the formatting constants from IntlDateFormatter as
     * described in (https://secure.php.net/manual/en/class.intldateformatter.php) or a pattern
     * as specified in (https://unicode-org.github.io/icu-docs/apidoc/released/icu4c/classSimpleDateFormat.html#details)
     *
     * It is possible to provide an array of 2 constants. In this case, the first position
     * will be used for formatting the date part of the object and the second position
     * will be used to format the time part.
     *
     * @param array<int>|string|int $format Format.
     * @return void
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public static function setToStringFormat($format): void
    {
        static::$_toStringFormat = $format;
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
     * Sets the default format used when converting this object to JSON
     *
     * The format should be either the formatting constants from IntlDateFormatter as
     * described in (https://secure.php.net/manual/en/class.intldateformatter.php) or a pattern
     * as specified in (http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details)
     *
     * It is possible to provide an array of 2 constants. In this case, the first position
     * will be used for formatting the date part of the object and the second position
     * will be used to format the time part.
     *
     * Alternatively, the format can provide a callback. In this case, the callback
     * can receive this datetime object and return a formatted string.
     *
     * @see \Cake\I18n\DateTime::i18nFormat()
     * @param \Closure|array|string|int $format Format.
     * @return void
     */
    public static function setJsonEncodeFormat(Closure|array|string|int $format): void
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
     *  $time = DateTime::parseDateTime('10/13/2013 12:54am');
     *  $time = DateTime::parseDateTime('13 Oct, 2013 13:54', 'dd MMM, y H:mm');
     *  $time = DateTime::parseDateTime('10/10/2015', [IntlDateFormatter::SHORT, IntlDateFormatter::NONE]);
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
        $format ??= static::$_toStringFormat;
        $format = is_int($format) ? [$format, $format] : $format;

        return static::_parseDateTime($time, $format, $tz);
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
     *  $time = DateTime::parseDate('10/13/2013');
     *  $time = DateTime::parseDate('13 Oct, 2013', 'dd MMM, y');
     *  $time = DateTime::parseDate('13 Oct, 2013', IntlDateFormatter::SHORT);
     * ```
     *
     * @param string $date The date string to parse.
     * @param array|string|int|null $format Any format accepted by IntlDateFormatter.
     * @return static|null
     */
    public static function parseDate(string $date, array|string|int|null $format = null): ?static
    {
        $format ??= static::$wordFormat;
        if (is_int($format)) {
            $format = [$format, IntlDateFormatter::NONE];
        }

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
     *  $time = DateTime::parseTime('11:23pm');
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
     * Get the difference formatter instance.
     *
     * @param \Cake\Chronos\DifferenceFormatterInterface|null $formatter Difference formatter
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
     * DateTime::UNIX_TIMESTAMP_FORMAT to get a unix timestamp
     *
     * ### Examples
     *
     * ```
     * $time = new DateTime('2014-04-20 22:10');
     * $time->i18nFormat(); // outputs '4/20/14, 10:10 PM' for the en-US locale
     * $time->i18nFormat(\IntlDateFormatter::FULL); // Use the full date and time format
     * $time->i18nFormat([\IntlDateFormatter::FULL, \IntlDateFormatter::SHORT]); // Use full date but short time format
     * $time->i18nFormat('yyyy-MM-dd HH:mm:ss'); // outputs '2014-04-20 22:10'
     * $time->i18nFormat(DateTime::UNIX_TIMESTAMP_FORMAT); // outputs '1398031800'
     * ```
     *
     * You can control the default format used through `DateTime::setToStringFormat()`.
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
     * You can control the default locale used through `DateTime::setDefaultLocale()`.
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
            return $this->getTimestamp();
        }

        $time = $this;

        if ($timezone) {
            $time = $time->setTimezone($timezone);
        }

        $format ??= static::$_toStringFormat;
        $format = is_int($format) ? [$format, $format] : $format;
        $locale = $locale ?: DateTime::getDefaultLocale();

        return $this->_formatObject($time, $format, $locale);
    }

    /**
     * Returns a nicely formatted date string for this object.
     *
     * The format to be used is stored in the static property `DateTime::$niceFormat`.
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
     * Returns either a relative or a formatted absolute date depending
     * on the difference between the current time and this object.
     *
     * ### Options:
     *
     * - `from` => another Time object representing the "now" time
     * - `format` => a fall back format if the relative time is longer than the duration specified by end
     * - `accuracy` => Specifies how accurate the date should be described (array)
     *     - year =>   The format if years > 0   (default "day")
     *     - month =>  The format if months > 0  (default "day")
     *     - week =>   The format if weeks > 0   (default "day")
     *     - day =>    The format if weeks > 0   (default "hour")
     *     - hour =>   The format if hours > 0   (default "minute")
     *     - minute => The format if minutes > 0 (default "minute")
     *     - second => The format if seconds > 0 (default "second")
     * - `end` => The end of relative time telling
     * - `relativeString` => The printf compatible string when outputting relative time
     * - `absoluteString` => The printf compatible string when outputting absolute time
     * - `timezone` => The user timezone the timestamp should be formatted in.
     *
     * Relative dates look something like this:
     *
     * - 3 weeks, 4 days ago
     * - 15 seconds ago
     *
     * Default date formatting is d/M/YY e.g: on 18/2/09. Formatting is done internally using
     * `i18nFormat`, see the method for the valid formatting strings
     *
     * The returned string includes 'ago' or 'on' and assumes you'll properly add a word
     * like 'Posted ' before the function output.
     *
     * NOTE: If the difference is one week or more, the lowest level of accuracy is day
     *
     * @param array<string, mixed> $options Array of options.
     * @return string Relative time string.
     */
    public function timeAgoInWords(array $options = []): string
    {
        return static::diffFormatter()->timeAgoInWords($this, $options);
    }

    /**
     * Get list of timezone identifiers
     *
     * @param string|int|null $filter A regex to filter identifier
     *   Or one of DateTimeZone class constants
     * @param string|null $country A two-letter ISO 3166-1 compatible country code.
     *   This option is only used when $filter is set to DateTimeZone::PER_COUNTRY
     * @param array<string, mixed>|bool $options If true (default value) groups the identifiers list by primary region.
     *   Otherwise, an array containing `group`, `abbr`, `before`, and `after`
     *   keys. Setting `group` and `abbr` to true will group results and append
     *   timezone abbreviation in the display value. Set `before` and `after`
     *   to customize the abbreviation wrapper.
     * @return array List of timezone identifiers
     * @since 2.2
     */
    public static function listTimezones(
        string|int|null $filter = null,
        ?string $country = null,
        array|bool $options = []
    ): array {
        if (is_bool($options)) {
            $options = [
                'group' => $options,
            ];
        }
        $defaults = [
            'group' => true,
            'abbr' => false,
            'before' => ' - ',
            'after' => null,
        ];
        $options += $defaults;
        $group = $options['group'];

        $regex = null;
        if (is_string($filter)) {
            $regex = $filter;
            $filter = null;
        }
        $filter ??= DateTimeZone::ALL;
        $identifiers = DateTimeZone::listIdentifiers($filter, (string)$country) ?: [];

        if ($regex) {
            foreach ($identifiers as $key => $tz) {
                if (!preg_match($regex, $tz)) {
                    unset($identifiers[$key]);
                }
            }
        }

        if ($group) {
            $groupedIdentifiers = [];
            $now = time();
            $before = $options['before'];
            $after = $options['after'];
            foreach ($identifiers as $tz) {
                $abbr = '';
                if ($options['abbr']) {
                    $dateTimeZone = new DateTimeZone($tz);
                    $trans = $dateTimeZone->getTransitions($now, $now);
                    $abbr = isset($trans[0]['abbr']) ?
                        $before . $trans[0]['abbr'] . $after :
                        '';
                }
                $item = explode('/', $tz, 2);
                if (isset($item[1])) {
                    $groupedIdentifiers[$item[0]][$tz] = $item[1] . $abbr;
                } else {
                    $groupedIdentifiers[$item[0]] = [$tz => $item[0] . $abbr];
                }
            }

            return $groupedIdentifiers;
        }

        return array_combine($identifiers, $identifiers);
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
     * @inheritDoc
     */
    public function __toString(): string
    {
        return (string)$this->i18nFormat();
    }
}

// phpcs:disable
class_alias('Cake\I18n\DateTime', 'Cake\I18n\FrozenTime');
// phpcs:enable
