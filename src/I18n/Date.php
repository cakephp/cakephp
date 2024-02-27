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
use Closure;
use IntlDateFormatter;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Extends the Date class provided by Chronos.
 *
 * Adds handy methods and locale-aware formatting helpers.
 *
 * @psalm-immutable
 */
class Date extends ChronosDate implements JsonSerializable, Stringable
{
    use DateFormatTrait;

    /**
     * The format to use when formatting a time using `Cake\I18n\Date::i18nFormat()`
     * and `__toString`.
     *
     * The format should be either the formatting constants from IntlDateFormatter as
     * described in (https://secure.php.net/manual/en/class.intldateformatter.php) or a pattern
     * as specified in (https://unicode-org.github.io/icu-docs/apidoc/released/icu4c/classSimpleDateFormat.html#details)
     *
     * @var string|int
     * @see \Cake\I18n\Date::i18nFormat()
     */
    protected static string|int $_toStringFormat = IntlDateFormatter::SHORT;

    /**
     * The format to use when converting this object to JSON.
     *
     * The format should be either the formatting constants from IntlDateFormatter as
     * described in (https://secure.php.net/manual/en/class.intldateformatter.php) or a pattern
     * as specified in (https://unicode-org.github.io/icu-docs/apidoc/released/icu4c/classSimpleDateFormat.html#details)
     *
     * @var \Closure|string|int
     * @see \Cake\I18n\Date::i18nFormat()
     */
    protected static Closure|string|int $_jsonEncodeFormat = 'yyyy-MM-dd';

    /**
     * The format to use when formatting a time using `Cake\I18n\Date::timeAgoInWords()`
     * and the difference is more than `Cake\I18n\Date::$wordEnd`
     *
     * @var string|int
     * @see \Cake\I18n\Date::parseDate()
     */
    public static string|int $wordFormat = IntlDateFormatter::SHORT;

    /**
     * The format to use when formatting a time using `Cake\I18n\Date::nice()`
     *
     * The format should be either the formatting constants from IntlDateFormatter as
     * described in (https://secure.php.net/manual/en/class.intldateformatter.php) or a pattern
     * as specified in (https://unicode-org.github.io/icu-docs/apidoc/released/icu4c/classSimpleDateFormat.html#details)
     *
     * @var string|int
     * @see \Cake\I18n\Date::nice()
     */
    public static string|int $niceFormat = IntlDateFormatter::MEDIUM;

    /**
     * The format to use when formatting a time using `Date::timeAgoInWords()`
     * and the difference is less than `Date::$wordEnd`
     *
     * @var array<string, string>
     * @see \Cake\I18n\Date::timeAgoInWords()
     */
    public static array $wordAccuracy = [
        'year' => 'day',
        'month' => 'day',
        'week' => 'day',
        'day' => 'day',
        'hour' => 'day',
        'minute' => 'day',
        'second' => 'day',
    ];

    /**
     * The end of relative time telling
     *
     * @var string
     * @see \Cake\I18n\Date::timeAgoInWords()
     */
    public static string $wordEnd = '+1 month';

    /**
     * Sets the default format used when type converting instances of this type to string
     *
     * The format should be either the formatting constants from IntlDateFormatter as
     * described in (https://secure.php.net/manual/en/class.intldateformatter.php) or a pattern
     * as specified in (https://unicode-org.github.io/icu-docs/apidoc/released/icu4c/classSimpleDateFormat.html#details)
     *
     * @param string|int $format Format.
     * @return void
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public static function setToStringFormat($format): void
    {
        static::$_toStringFormat = $format;
    }

    /**
     * Sets the default format used when converting this object to JSON
     *
     * The format should be either the formatting constants from IntlDateFormatter as
     * described in (https://secure.php.net/manual/en/class.intldateformatter.php) or a pattern
     * as specified in (http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details)
     *
     * Alternatively, the format can provide a callback. In this case, the callback
     * can receive this object and return a formatted string.
     *
     * @see \Cake\I18n\Date::i18nFormat()
     * @param \Closure|string|int $format Format.
     * @return void
     */
    public static function setJsonEncodeFormat(Closure|string|int $format): void
    {
        static::$_jsonEncodeFormat = $format;
    }

    /**
     * Returns a new Date object after parsing the provided $date string based on
     * the passed or configured format. This method is locale dependent,
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
     *  $time = Date::parseDate('10/13/2013');
     *  $time = Date::parseDate('13 Oct, 2013', 'dd MMM, y');
     *  $time = Date::parseDate('13 Oct, 2013', IntlDateFormatter::SHORT);
     * ```
     *
     * @param string $date The date string to parse.
     * @param string|int|null $format Any format accepted by IntlDateFormatter.
     * @return static|null
     */
    public static function parseDate(string $date, string|int|null $format = null): ?static
    {
        $format ??= static::$wordFormat;
        if (is_int($format)) {
            $format = [$format, IntlDateFormatter::NONE];
        }

        return static::_parseDateTime($date, $format);
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
     * ### Examples
     *
     * ```
     * $date = new Date('2014-04-20');
     * $date->i18nFormat(); // outputs '4/20/14' for the en-US locale
     * $date->i18nFormat(\IntlDateFormatter::FULL); // Use the full date format
     * $date->i18nFormat('yyyy-MM-dd'); // outputs '2014-04-20'
     * ```
     *
     * You can control the default format used through `Date::setToStringFormat()`.
     *
     * You can read about the available IntlDateFormatter constants at
     * https://secure.php.net/manual/en/class.intldateformatter.php
     *
     * Should you need to use a different locale for displaying this time object,
     * pass a locale string as the third parameter to this function.
     *
     * ### Examples
     *
     * ```
     * $date = new Date('2014-04-20');
     * $time->i18nFormat(null, 'de-DE');
     * $time->i18nFormat(\IntlDateFormatter::FULL, 'de-DE');
     * ```
     *
     * You can control the default locale used through `Date::setDefaultLocale()`.
     * If empty, the default will be taken from the `intl.default_locale` ini config.
     *
     * @param string|int|null $format Format string.
     * @param string|null $locale The locale name in which the date should be displayed (e.g. pt-BR)
     * @return string|int Formatted and translated date string
     */
    public function i18nFormat(
        string|int|null $format = null,
        ?string $locale = null
    ): string|int {
        if ($format === DateTime::UNIX_TIMESTAMP_FORMAT) {
            throw new InvalidArgumentException('UNIT_TIMESTAMP_FORMAT is not supported for Date.');
        }

        $format ??= static::$_toStringFormat;
        $format = is_int($format) ? [$format, IntlDateFormatter::NONE] : $format;
        $locale = $locale ?: DateTime::getDefaultLocale();

        return $this->_formatObject($this->native, $format, $locale);
    }

    /**
     * Returns a nicely formatted date string for this object.
     *
     * The format to be used is stored in the static property `Date::$niceFormat`.
     *
     * @param string|null $locale The locale name in which the date should be displayed (e.g. pt-BR)
     * @return string Formatted date string
     */
    public function nice(?string $locale = null): string
    {
        return (string)$this->i18nFormat(static::$niceFormat, $locale);
    }

    /**
     * Returns either a relative or a formatted absolute date depending
     * on the difference between the current date and this object.
     *
     * ### Options:
     *
     * - `from` => another Date object representing the "now" date
     * - `format` => a fall back format if the relative time is longer than the duration specified by end
     * - `accuracy` => Specifies how accurate the date should be described (array)
     *     - year =>   The format if years > 0   (default "day")
     *     - month =>  The format if months > 0  (default "day")
     *     - week =>   The format if weeks > 0   (default "day")
     *     - day =>    The format if weeks > 0   (default "day")
     * - `end` => The end of relative date telling
     * - `relativeString` => The printf compatible string when outputting relative date
     * - `absoluteString` => The printf compatible string when outputting absolute date
     * - `timezone` => The user timezone the timestamp should be formatted in.
     *
     * Relative dates look something like this:
     *
     * - 3 weeks, 4 days ago
     * - 1 day ago
     *
     * Default date formatting is d/M/YY e.g: on 18/2/09. Formatting is done internally using
     * `i18nFormat`, see the method for the valid formatting strings.
     *
     * The returned string includes 'ago' or 'on' and assumes you'll properly add a word
     * like 'Posted ' before the function output.
     *
     * NOTE: If the difference is one week or more, the lowest level of accuracy is day.
     *
     * @param array<string, mixed> $options Array of options.
     * @return string Relative time string.
     */
    public function timeAgoInWords(array $options = []): string
    {
        return static::diffFormatter()->dateAgoInWords($this, $options);
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
class_alias('Cake\I18n\Date', 'Cake\I18n\FrozenDate');
// phpcs:enable
