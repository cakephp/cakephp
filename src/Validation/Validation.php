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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Validation;

use BackedEnum;
use Cake\Chronos\ChronosDate;
use Cake\Chronos\ChronosTime;
use Cake\Core\Exception\CakeException;
use Cake\I18n\DateTime;
use Cake\Utility\Text;
use Countable;
use DateTimeInterface;
use InvalidArgumentException;
use NumberFormatter;
use Psr\Http\Message\UploadedFileInterface;
use ReflectionEnum;
use ReflectionException;
use RuntimeException;
use UnhandledMatchError;

/**
 * Validation Class. Used for validation of model data
 *
 * Offers different validation methods.
 */
class Validation
{
    /**
     * Default locale
     *
     * @var string
     */
    public const DEFAULT_LOCALE = 'en_US';

    /**
     * Same as operator.
     *
     * @var string
     */
    public const COMPARE_SAME = '===';

    /**
     * Not same as comparison operator.
     *
     * @var string
     */
    public const COMPARE_NOT_SAME = '!==';

    /**
     * Equal to comparison operator.
     *
     * @var string
     */
    public const COMPARE_EQUAL = '==';

    /**
     * Not equal to comparison operator.
     *
     * @var string
     */
    public const COMPARE_NOT_EQUAL = '!=';

    /**
     * Greater than comparison operator.
     *
     * @var string
     */
    public const COMPARE_GREATER = '>';

    /**
     * Greater than or equal to comparison operator.
     *
     * @var string
     */
    public const COMPARE_GREATER_OR_EQUAL = '>=';

    /**
     * Less than comparison operator.
     *
     * @var string
     */
    public const COMPARE_LESS = '<';

    /**
     * Less than or equal to comparison operator.
     *
     * @var string
     */
    public const COMPARE_LESS_OR_EQUAL = '<=';

    /**
     * @var array<string>
     */
    protected const COMPARE_STRING = [
        self::COMPARE_EQUAL,
        self::COMPARE_NOT_EQUAL,
        self::COMPARE_SAME,
        self::COMPARE_NOT_SAME,
    ];

    /**
     * Datetime ISO8601 format
     *
     * @var string
     */
    public const DATETIME_ISO8601 = 'iso8601';

    /**
     * Some complex patterns needed in multiple places
     *
     * @var array<string, string>
     */
    protected static array $_pattern = [
        'hostname' => '(?:[_\p{L}0-9][-_\p{L}0-9]*\.)*(?:[\p{L}0-9][-\p{L}0-9]{0,62})\.(?:(?:[a-z]{2}\.)?[a-z]{2,})',
        'latitude' => '[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?)',
        'longitude' => '[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)',
    ];

    /**
     * Holds an array of errors messages set in this class.
     * These are used for debugging purposes
     *
     * @var array
     */
    public static array $errors = [];

    /**
     * Checks that a string contains something other than whitespace
     *
     * Returns true if string contains something other than whitespace
     *
     * @param mixed $check Value to check
     * @return bool Success
     */
    public static function notBlank(mixed $check): bool
    {
        if (!$check && !is_bool($check) && !is_numeric($check)) {
            return false;
        }

        return static::_check($check, '/[^\s]+/m');
    }

    /**
     * Checks that a string contains only integer or letters.
     *
     * This method's definition of letters and integers includes unicode characters.
     * Use `asciiAlphaNumeric()` if you want to exclude unicode.
     *
     * @param mixed $check Value to check
     * @return bool Success
     */
    public static function alphaNumeric(mixed $check): bool
    {
        if ((empty($check) && $check !== '0') || !is_scalar($check)) {
            return false;
        }

        return self::_check($check, '/^[\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}]+$/Du');
    }

    /**
     * Checks that a doesn't contain any alpha numeric characters
     *
     * This method's definition of letters and integers includes unicode characters.
     * Use `notAsciiAlphaNumeric()` if you want to exclude ascii only.
     *
     * @param mixed $check Value to check
     * @return bool Success
     */
    public static function notAlphaNumeric(mixed $check): bool
    {
        return !static::alphaNumeric($check);
    }

    /**
     * Checks that a string contains only ascii integer or letters.
     *
     * @param mixed $check Value to check
     * @return bool Success
     */
    public static function asciiAlphaNumeric(mixed $check): bool
    {
        if ((empty($check) && $check !== '0') || !is_scalar($check)) {
            return false;
        }

        return self::_check($check, '/^[[:alnum:]]+$/');
    }

    /**
     * Checks that a doesn't contain any non-ascii alpha numeric characters
     *
     * @param mixed $check Value to check
     * @return bool Success
     */
    public static function notAsciiAlphaNumeric(mixed $check): bool
    {
        return !static::asciiAlphaNumeric($check);
    }

    /**
     * Checks that a string length is within specified range.
     * Spaces are included in the character count.
     * Returns true if string matches value min, max, or between min and max,
     *
     * @param mixed $check Value to check for length
     * @param int $min Minimum value in range (inclusive)
     * @param int $max Maximum value in range (inclusive)
     * @return bool Success
     */
    public static function lengthBetween(mixed $check, int $min, int $max): bool
    {
        if (!is_scalar($check)) {
            return false;
        }
        $length = mb_strlen((string)$check);

        return $length >= $min && $length <= $max;
    }

    /**
     * Validation of credit card numbers.
     * Returns true if $check is in the proper credit card format.
     *
     * @param mixed $check credit card number to validate
     * @param array<string>|string $type 'all' may be passed as a string, defaults to fast which checks format of
     *     most major credit cards if an array is used only the values of the array are checked.
     *    Example: ['amex', 'bankcard', 'maestro']
     * @param bool $deep set to true this will check the Luhn algorithm of the credit card.
     * @param string|null $regex A custom regex, this will be used instead of the defined regex values.
     * @return bool Success
     * @see \Cake\Validation\Validation::luhn()
     */
    public static function creditCard(
        mixed $check,
        array|string $type = 'fast',
        bool $deep = false,
        ?string $regex = null,
    ): bool {
        if (!is_string($check) && !is_int($check)) {
            return false;
        }

        $check = str_replace(['-', ' '], '', (string)$check);
        if (mb_strlen($check) < 13) {
            return false;
        }

        if ($regex !== null && static::_check($check, $regex)) {
            return !$deep || static::luhn($check);
        }
        $cards = [
            'all' => [
                'amex' => '/^3[47]\\d{13}$/',
                'bankcard' => '/^56(10\\d\\d|022[1-5])\\d{10}$/',
                'diners' => '/^(?:3(0[0-5]|[68]\\d)\\d{11})|(?:5[1-5]\\d{14})$/',
                'disc' => '/^(?:6011|650\\d)\\d{12}$/',
                'electron' => '/^(?:417500|4917\\d{2}|4913\\d{2})\\d{10}$/',
                'enroute' => '/^2(?:014|149)\\d{11}$/',
                'jcb' => '/^(3\\d{4}|2131|1800)\\d{11}$/',
                'maestro' => '/^(?:5020|6\\d{3})\\d{12}$/',
                'mc' => '/^(5[1-5]\\d{14})|(2(?:22[1-9]|2[3-9][0-9]|[3-6][0-9]{2}|7[0-1][0-9]|720)\\d{12})$/',
                'solo' => '/^(6334[5-9][0-9]|6767[0-9]{2})\\d{10}(\\d{2,3})?$/',
                // phpcs:ignore Generic.Files.LineLength
                'switch' => '/^(?:49(03(0[2-9]|3[5-9])|11(0[1-2]|7[4-9]|8[1-2])|36[0-9]{2})\\d{10}(\\d{2,3})?)|(?:564182\\d{10}(\\d{2,3})?)|(6(3(33[0-4][0-9])|759[0-9]{2})\\d{10}(\\d{2,3})?)$/',
                'visa' => '/^4\\d{12}(\\d{3})?$/',
                'voyager' => '/^8699[0-9]{11}$/',
            ],
            // phpcs:ignore Generic.Files.LineLength
            'fast' => '/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6011[0-9]{12}|3(?:0[0-5]|[68][0-9])[0-9]{11}|3[47][0-9]{13})$/',
        ];

        if (is_array($type)) {
            foreach ($type as $value) {
                $regex = $cards['all'][strtolower($value)];

                if (static::_check($check, $regex)) {
                    return static::luhn($check);
                }
            }
        } elseif ($type === 'all') {
            foreach ($cards['all'] as $value) {
                $regex = $value;

                if (static::_check($check, $regex)) {
                    return static::luhn($check);
                }
            }
        } else {
            $regex = $cards['fast'];

            if (static::_check($check, $regex)) {
                return static::luhn($check);
            }
        }

        return false;
    }

    /**
     * Used to check the count of a given value of type array or Countable.
     *
     * @param mixed $check The value to check the count on.
     * @param string $operator Can be either a word or operand
     *    is greater >, is less <, greater or equal >=
     *    less or equal <=, is less <, equal to ==, not equal !=
     * @param int $expectedCount The expected count value.
     * @return bool Success
     */
    public static function numElements(mixed $check, string $operator, int $expectedCount): bool
    {
        if (!is_array($check) && !$check instanceof Countable) {
            return false;
        }

        return self::comparison(count($check), $operator, $expectedCount);
    }

    /**
     * Used to compare 2 numeric values.
     *
     * @param mixed $check1 The left value to compare.
     * @param string $operator Can be one of following operator strings:
     *   '>', '<', '>=', '<=', '==', '!=', '===' and '!=='. You can use one of
     *   the Validation::COMPARE_* constants.
     * @param mixed $check2 The right value to compare.
     * @return bool Success
     */
    public static function comparison(mixed $check1, string $operator, mixed $check2): bool
    {
        if (
            (!is_numeric($check1) || !is_numeric($check2)) &&
            !in_array($operator, static::COMPARE_STRING)
        ) {
            return false;
        }

        try {
            return match ($operator) {
                static::COMPARE_GREATER => $check1 > $check2,
                static::COMPARE_LESS => $check1 < $check2,
                static::COMPARE_GREATER_OR_EQUAL => $check1 >= $check2,
                static::COMPARE_LESS_OR_EQUAL => $check1 <= $check2,
                static::COMPARE_EQUAL => $check1 == $check2,
                static::COMPARE_NOT_EQUAL => $check1 != $check2,
                static::COMPARE_SAME => $check1 === $check2,
                static::COMPARE_NOT_SAME => $check1 !== $check2,
            };
        } catch (UnhandledMatchError) {
            static::$errors[] = 'You must define a valid $operator parameter for Validation::comparison()';
        }

        return false;
    }

    /**
     * Compare one field to another.
     *
     * If both fields have exactly the same value this method will return true.
     *
     * @param mixed $check The value to find in $field.
     * @param string $field The field to check $check against. This field must be present in $context.
     * @param array<string, mixed> $context The validation context.
     * @return bool
     */
    public static function compareWith(mixed $check, string $field, array $context): bool
    {
        return self::compareFields($check, $field, static::COMPARE_SAME, $context);
    }

    /**
     * Compare one field to another.
     *
     * Return true if the comparison matches the expected result.
     *
     * @param mixed $check The value to find in $field.
     * @param string $field The field to check $check against. This field must be present in $context.
     * @param string $operator Comparison operator. See Validation::comparison().
     * @param array<string, mixed> $context The validation context.
     * @return bool
     * @since 3.6.0
     */
    public static function compareFields(mixed $check, string $field, string $operator, array $context): bool
    {
        if (!isset($context['data']) || !array_key_exists($field, $context['data'])) {
            return false;
        }

        return static::comparison($check, $operator, $context['data'][$field]);
    }

    /**
     * Used when a custom regular expression is needed.
     *
     * @param mixed $check The value to check.
     * @param string|null $regex If $check is passed as a string, $regex must also be set to valid regular expression
     * @return bool Success
     */
    public static function custom(mixed $check, ?string $regex = null): bool
    {
        if (!is_scalar($check)) {
            return false;
        }
        if ($regex === null) {
            static::$errors[] = 'You must define a regular expression for Validation::custom()';

            return false;
        }

        return static::_check($check, $regex);
    }

    /**
     * Date validation, determines if the string passed is a valid date.
     * keys that expect full month, day and year will validate leap years.
     *
     * Years are valid from 0001 to 2999.
     *
     * ### Formats:
     *
     * - `ymd` 2006-12-27 or 06-12-27 separators can be a space, period, dash, forward slash
     * - `dmy` 27-12-2006 or 27-12-06 separators can be a space, period, dash, forward slash
     * - `mdy` 12-27-2006 or 12-27-06 separators can be a space, period, dash, forward slash
     * - `dMy` 27 December 2006 or 27 Dec 2006
     * - `Mdy` December 27, 2006 or Dec 27, 2006 comma is optional
     * - `My` December 2006 or Dec 2006
     * - `my` 12/2006 or 12/06 separators can be a space, period, dash, forward slash
     * - `ym` 2006/12 or 06/12 separators can be a space, period, dash, forward slash
     * - `y` 2006 just the year without any separators
     *
     * @param mixed $check a valid date string/object
     * @param array<string>|string $format Use a string or an array of the keys above.
     *    Arrays should be passed as ['dmy', 'mdy', ...]
     * @param string|null $regex If a custom regular expression is used this is the only validation that will occur.
     * @return bool Success
     */
    public static function date(mixed $check, array|string $format = 'ymd', ?string $regex = null): bool
    {
        if (
            (class_exists(ChronosDate::class) && $check instanceof ChronosDate)
            || $check instanceof DateTimeInterface
        ) {
            return true;
        }
        if (is_object($check)) {
            return false;
        }
        if (is_array($check)) {
            $check = static::_getDateString($check);
            $format = 'ymd';
        }

        if ($regex !== null) {
            return static::_check($check, $regex);
        }
        $month = '(0[123456789]|10|11|12)';
        $separator = '([- /.])';
        // Don't allow 0000, but 0001-2999 are ok.
        $fourDigitYear = '(?:(?!0000)[012]\d{3})';
        $twoDigitYear = '(?:\d{2})';
        $year = '(?:' . $fourDigitYear . '|' . $twoDigitYear . ')';

        // phpcs:disable Generic.Files.LineLength
        // 2 or 4 digit leap year sub-pattern
        $leapYear = '(?:(?:(?:(?!0000)[012]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00)))';
        // 4 digit leap year sub-pattern
        $fourDigitLeapYear = '(?:(?:(?:(?!0000)[012]\\d)(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00)))';

        $regex['dmy'] = '%^(?:(?:31(\\/|-|\\.|\\x20)(?:0?[13578]|1[02]))\\1|(?:(?:29|30)' .
            $separator . '(?:0?[13-9]|1[0-2])\\2))' . $year . '$|^(?:29' .
            $separator . '0?2\\3' . $leapYear . ')$|^(?:0?[1-9]|1\\d|2[0-8])' .
            $separator . '(?:(?:0?[1-9])|(?:1[0-2]))\\4' . $year . '$%';

        $regex['mdy'] = '%^(?:(?:(?:0?[13578]|1[02])(\\/|-|\\.|\\x20)31)\\1|(?:(?:0?[13-9]|1[0-2])' .
            $separator . '(?:29|30)\\2))' . $year . '$|^(?:0?2' . $separator . '29\\3' . $leapYear . ')$|^(?:(?:0?[1-9])|(?:1[0-2]))' .
            $separator . '(?:0?[1-9]|1\\d|2[0-8])\\4' . $year . '$%';

        $regex['ymd'] = '%^(?:(?:' . $leapYear .
            $separator . '(?:0?2\\1(?:29)))|(?:' . $year .
            $separator . '(?:(?:(?:0?[13578]|1[02])\\2(?:31))|(?:(?:0?[13-9]|1[0-2])\\2(29|30))|(?:(?:0?[1-9])|(?:1[0-2]))\\2(?:0?[1-9]|1\\d|2[0-8]))))$%';

        $regex['dMy'] = '/^((31(?!\\ (Feb(ruary)?|Apr(il)?|June?|(Sep(?=\\b|t)t?|Nov)(ember)?)))|((30|29)(?!\\ Feb(ruary)?))|(29(?=\\ Feb(ruary)?\\ ' . $fourDigitLeapYear . '))|(0?[1-9])|1\\d|2[0-8])\\ (Jan(uary)?|Feb(ruary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep(?=\\b|t)t?|Nov|Dec)(ember)?)\\ ' . $fourDigitYear . '$/';

        $regex['Mdy'] = '/^(?:(((Jan(uary)?|Ma(r(ch)?|y)|Jul(y)?|Aug(ust)?|Oct(ober)?|Dec(ember)?)\\ 31)|((Jan(uary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep)(tember)?|(Nov|Dec)(ember)?)\\ (0?[1-9]|([12]\\d)|30))|(Feb(ruary)?\\ (0?[1-9]|1\\d|2[0-8]|(29(?=,?\\ ' . $fourDigitLeapYear . ')))))\\,?\\ ' . $fourDigitYear . ')$/';

        $regex['My'] = '%^(Jan(uary)?|Feb(ruary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep(?=\\b|t)t?|Nov|Dec)(ember)?)' .
            $separator . $fourDigitYear . '$%';
        // phpcs:enable Generic.Files.LineLength

        $regex['my'] = '%^(' . $month . $separator . $year . ')$%';
        $regex['ym'] = '%^(' . $year . $separator . $month . ')$%';
        $regex['y'] = '%^(' . $fourDigitYear . ')$%';

        $format = (array)$format;
        foreach ($format as $key) {
            if (static::_check($check, $regex[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validates a datetime value
     *
     * All values matching the "date" core validation rule, and the "time" one will be valid
     *
     * Years are valid from 0001 to 2999.
     *
     * ### Formats:
     *
     *  - `ymd` 2006-12-27 or 06-12-27 separators can be a space, period, dash, forward slash
     *  - `dmy` 27-12-2006 or 27-12-06 separators can be a space, period, dash, forward slash
     *  - `mdy` 12-27-2006 or 12-27-06 separators can be a space, period, dash, forward slash
     *  - `dMy` 27 December 2006 or 27 Dec 2006
     *  - `Mdy` December 27, 2006 or Dec 27, 2006 comma is optional
     *  - `My` December 2006 or Dec 2006
     *  - `my` 12/2006 or 12/06 separators can be a space, period, dash, forward slash
     *  - `ym` 2006/12 or 06/12 separators can be a space, period, dash, forward slash
     *  - `y` 2006 just the year without any separators
     *
     * Time is validated as 24hr (HH:MM[:SS][.FFFFFF]) or am/pm ([H]H:MM[a|p]m)
     *
     * Seconds and fractional seconds (microseconds) are allowed but optional
     * in 24hr format.
     *
     * @param mixed $check Value to check
     * @param array|string $dateFormat Format of the date part. See Validation::date() for more information.
     *   Or `Validation::DATETIME_ISO8601` to validate an ISO8601 datetime value.
     * @param string|null $regex Regex for the date part. If a custom regular expression is used
     *   this is the only validation that will occur.
     * @return bool True if the value is valid, false otherwise
     * @see \Cake\Validation\Validation::date()
     * @see \Cake\Validation\Validation::time()
     */
    public static function datetime(mixed $check, array|string $dateFormat = 'ymd', ?string $regex = null): bool
    {
        if ($check instanceof DateTimeInterface) {
            return true;
        }
        if (is_object($check)) {
            return false;
        }
        if (is_array($dateFormat) && count($dateFormat) === 1) {
            $dateFormat = reset($dateFormat);
        }
        if ($dateFormat === static::DATETIME_ISO8601 && !static::iso8601($check)) {
            return false;
        }

        $valid = false;
        if (is_array($check)) {
            $check = static::_getDateString($check);
            $dateFormat = 'ymd';
        }
        $parts = preg_split('/[\sT]+/', $check);
        if ($parts && count($parts) > 1) {
            $date = rtrim(array_shift($parts), ',');
            $time = implode(' ', $parts);
            if ($dateFormat === static::DATETIME_ISO8601) {
                $dateFormat = 'ymd';
                $time = preg_split("/[TZ\-\+\.]/", $time) ?: [];
                $time = array_shift($time);
            }
            $valid = static::date($date, $dateFormat, $regex) && static::time($time);
        }

        return $valid;
    }

    /**
     * Validates an iso8601 datetime format
     * ISO8601 recognize datetime like 2019 as a valid date. To validate and check date integrity, use @see \Cake\Validation\Validation::datetime()
     *
     * @param mixed $check Value to check
     * @return bool True if the value is valid, false otherwise
     * @see Regex credits: https://www.myintervals.com/blog/2009/05/20/iso-8601-date-validation-that-doesnt-suck/
     */
    public static function iso8601(mixed $check): bool
    {
        if ($check instanceof DateTimeInterface) {
            return true;
        }
        if (is_object($check)) {
            return false;
        }

        // phpcs:ignore Generic.Files.LineLength
        $regex = '/^([\+-]?\d{4}(?!\d{2}\b))((-?)((0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/';

        return static::_check($check, $regex);
    }

    /**
     * Time validation, determines if the string passed is a valid time.
     * Validates time as 24hr (HH:MM[:SS][.FFFFFF]) or am/pm ([H]H:MM[a|p]m)
     *
     * Seconds and fractional seconds (microseconds) are allowed but optional
     * in 24hr format.
     *
     * @param mixed $check a valid time string/object
     * @return bool Success
     */
    public static function time(mixed $check): bool
    {
        if (
            (class_exists(ChronosTime::class) && $check instanceof ChronosTime)
            || $check instanceof DateTimeInterface
        ) {
            return true;
        }
        if (is_array($check)) {
            $check = static::_getDateString($check);
        }

        if (!is_scalar($check)) {
            return false;
        }

        $meridianClockRegex = '^((0?[1-9]|1[012])(:[0-5]\d){0,2} ?([AP]M|[ap]m))$';
        $standardClockRegex = '^([01]\d|2[0-3])((:[0-5]\d){1,2}|(:[0-5]\d){2}\.\d{0,6})$';

        return static::_check($check, '%' . $meridianClockRegex . '|' . $standardClockRegex . '%');
    }

    /**
     * Date and/or time string validation.
     * Uses `I18n::Time` to parse the date. This means parsing is locale dependent.
     *
     * @param mixed $check a date string or object (will always pass)
     * @param string $type Parser type, one out of 'date', 'time', and 'datetime'
     * @param string|int|null $format any format accepted by IntlDateFormatter
     * @return bool Success
     * @throws \InvalidArgumentException when unsupported $type given
     * @see \Cake\I18n\Time::parseDate()
     * @see \Cake\I18n\Time::parseTime()
     * @see \Cake\I18n\Time::parseDateTime()
     */
    public static function localizedTime(mixed $check, string $type = 'datetime', string|int|null $format = null): bool
    {
        if (!class_exists(DateTime::class)) {
            throw new CakeException(
                'The Cake\I18n\DateTime class is not available. Install the cakephp/i18n package.',
            );
        }

        if (
            (class_exists(ChronosTime::class) && $check instanceof ChronosTime)
            || $check instanceof DateTimeInterface
        ) {
            return true;
        }
        if (!is_string($check)) {
            return false;
        }
        static $methods = [
            'date' => 'parseDate',
            'time' => 'parseTime',
            'datetime' => 'parseDateTime',
        ];
        if (empty($methods[$type])) {
            throw new InvalidArgumentException('Unsupported parser type given.');
        }
        $method = $methods[$type];

        return DateTime::$method($check, $format) !== null;
    }

    /**
     * Validates if passed value is boolean-like.
     *
     * The list of what is considered to be boolean values may be set via $booleanValues.
     *
     * @param mixed $check Value to check.
     * @param array<string|int|bool> $booleanValues List of valid boolean values, defaults to `[true, false, 0, 1, '0', '1']`.
     * @return bool Success.
     */
    public static function boolean(mixed $check, array $booleanValues = [true, false, 0, 1, '0', '1']): bool
    {
        return in_array($check, $booleanValues, true);
    }

    /**
     * Validates if given value is truthy.
     *
     * The list of what is considered to be truthy values, may be set via $truthyValues.
     *
     * @param mixed $check Value to check.
     * @param array<string|int|bool> $truthyValues List of valid truthy values, defaults to `[true, 1, '1']`.
     * @return bool Success.
     */
    public static function truthy(mixed $check, array $truthyValues = [true, 1, '1']): bool
    {
        return in_array($check, $truthyValues, true);
    }

    /**
     * Validates if given value is falsey.
     *
     * The list of what is considered to be falsey values, may be set via $falseyValues.
     *
     * @param mixed $check Value to check.
     * @param array<string|int|bool> $falseyValues List of valid falsey values, defaults to `[false, 0, '0']`.
     * @return bool Success.
     */
    public static function falsey(mixed $check, array $falseyValues = [false, 0, '0']): bool
    {
        return in_array($check, $falseyValues, true);
    }

    /**
     * Checks that a value is a valid decimal. Both the sign and exponent are optional.
     *
     * Be aware that the currently set locale is being used to determine
     * the decimal and thousands separator of the given number.
     *
     * Valid Places:
     *
     * - null => Any number of decimal places, including none. The '.' is not required.
     * - true => Any number of decimal places greater than 0, or a float|double. The '.' is required.
     * - 1..N => Exactly that many number of decimal places. The '.' is required.
     *
     * @param mixed $check The value the test for decimal.
     * @param int|true|null $places Decimal places.
     * @param string|null $regex If a custom regular expression is used, this is the only validation that will occur.
     * @return bool Success
     */
    public static function decimal(mixed $check, int|bool|null $places = null, ?string $regex = null): bool
    {
        if (!is_scalar($check)) {
            return false;
        }

        if ($regex === null) {
            $lnum = '[0-9]+';
            $dnum = "[0-9]*[\.]{$lnum}";
            $sign = '[+-]?';
            $exp = "(?:[eE]{$sign}{$lnum})?";

            if ($places === null) {
                $regex = "/^{$sign}(?:{$lnum}|{$dnum}){$exp}$/";
            } elseif ($places === true) {
                if (is_float($check) && floor($check) === $check) {
                    $check = sprintf('%.1f', $check);
                }
                $regex = "/^{$sign}{$dnum}{$exp}$/";
            } else {
                $places = '[0-9]{' . $places . '}';
                $dnum = "(?:[0-9]*[\.]{$places}|{$lnum}[\.]{$places})";
                $regex = "/^{$sign}{$dnum}{$exp}$/";
            }
        }

        // account for localized floats.
        $locale = ini_get('intl.default_locale') ?: static::DEFAULT_LOCALE;
        $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        $decimalPoint = $formatter->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
        $groupingSep = $formatter->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL);

        // There are two types of non-breaking spaces - we inject a space to account for human input
        if ($groupingSep === "\xc2\xa0" || $groupingSep === "\xe2\x80\xaf") {
            $check = str_replace([' ', $groupingSep, $decimalPoint], ['', '', '.'], (string)$check);
        } else {
            $check = str_replace([$groupingSep, $decimalPoint], ['', '.'], (string)$check);
        }

        return static::_check($check, $regex);
    }

    /**
     * Validates for an email address.
     *
     * Only uses getmxrr() checking for deep validation, or
     * any PHP version on a non-windows distribution
     *
     * @param mixed $check Value to check
     * @param bool|null $deep Perform a deeper validation (if true), by also checking availability of host
     * @param string|null $regex Regex to use (if none it will use built in regex)
     * @return bool Success
     */
    public static function email(mixed $check, ?bool $deep = false, ?string $regex = null): bool
    {
        if (!is_string($check)) {
            return false;
        }

        // phpcs:ignore Generic.Files.LineLength
        $regex ??= '/^[\p{L}0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[\p{L}0-9!#$%&\'*+\/=?^_`{|}~-]+)*@' . self::$_pattern['hostname'] . '$/ui';

        $return = static::_check($check, $regex);
        if ($deep === false || $deep === null) {
            return $return;
        }

        if ($return && preg_match('/@(' . static::$_pattern['hostname'] . ')$/i', $check, $regs)) {
            if (function_exists('getmxrr') && getmxrr($regs[1], $mxhosts)) {
                return true;
            }
            if (function_exists('checkdnsrr') && checkdnsrr($regs[1], 'MX')) {
                return true;
            }

            return is_array(gethostbynamel($regs[1] . '.'));
        }

        return false;
    }

    /**
     * Checks that the value is a valid backed enum instance or value.
     *
     * @param mixed $check Value to check
     * @param class-string<\BackedEnum> $enumClassName The valid backed enum class name
     * @return bool Success
     * @since 5.0.3
     */
    public static function enum(mixed $check, string $enumClassName): bool
    {
        return static::checkEnum($check, $enumClassName);
    }

    /**
     * Checks that the value is a valid backed enum instance or value.
     *
     * @param mixed $check Value to check
     * @param array<\BackedEnum> $cases Array of enum cases that are valid.
     * @return bool Success
     * @since 5.1.0
     */
    public static function enumOnly(mixed $check, array $cases): bool
    {
        if ($cases === []) {
            throw new InvalidArgumentException('At least one case needed for `enumOnly()` validation.');
        }

        $firstKey = array_key_first($cases);
        $firstValue = $cases[$firstKey];
        $enumClassName = $firstValue::class;

        $options = ['only' => $cases];

        return static::checkEnum($check, $enumClassName, $options);
    }

    /**
     * Checks that the value is a valid backed enum instance or value.
     *
     * @param mixed $check Value to check
     * @param array<\BackedEnum> $cases Array of enum cases that are not valid.
     * @return bool Success
     * @since 5.1.0
     */
    public static function enumExcept(mixed $check, array $cases): bool
    {
        if ($cases === []) {
            throw new InvalidArgumentException('At least one case needed for `enumExcept()` validation.');
        }

        $firstKey = array_key_first($cases);
        $firstValue = $cases[$firstKey];
        $enumClassName = $firstValue::class;

        $options = ['except' => $cases];

        return static::checkEnum($check, $enumClassName, $options);
    }

    /**
     * @param mixed $check
     * @param class-string $enumClassName
     * @param array<string, mixed> $options
     * @return bool
     */
    protected static function checkEnum(mixed $check, string $enumClassName, array $options = []): bool
    {
        if (
            $check instanceof $enumClassName &&
            $check instanceof BackedEnum
        ) {
            return static::isValidEnum($check, $options);
        }

        $backingType = null;
        try {
            $reflectionEnum = new ReflectionEnum($enumClassName);

            /** @var \ReflectionNamedType|null $reflectionBackingType */
            $reflectionBackingType = $reflectionEnum->getBackingType();
            if ($reflectionBackingType) {
                if (method_exists($reflectionBackingType, 'getName')) {
                    $backingType = $reflectionBackingType->getName();
                } else {
                    $backingType = (string)$reflectionBackingType;
                }
            }
        } catch (ReflectionException) {
        }

        if ($backingType === null) {
            throw new InvalidArgumentException(
                'The `$enumClassName` argument must be the classname of a valid backed enum.',
            );
        }

        if (!is_string($check) && !is_int($check)) {
            return false;
        }

        if ($backingType === 'int') {
            if (!is_numeric($check)) {
                return false;
            }
            $check = (int)$check;
        }

        if (get_debug_type($check) !== (string)$backingType) {
            return false;
        }

        $options += [
            'only' => null,
            'except' => null,
        ];

        /** @var class-string<\BackedEnum> $enumClassName */
        $enum = $enumClassName::tryFrom($check);
        if ($enum === null) {
            return false;
        }

        return static::isValidEnum($enum, $options);
    }

    /**
     * @param \BackedEnum $enum
     * @param array<string, mixed> $options
     * @return bool
     */
    protected static function isValidEnum(BackedEnum $enum, array $options): bool
    {
        $options += ['only' => null, 'except' => null];

        if ($options['only']) {
            if (!is_array($options['only'])) {
                $options['only'] = [$options['only']];
            }

            if (in_array($enum, $options['only'], true)) {
                return true;
            }

            return false;
        }

        if ($options['except']) {
            if (!is_array($options['except'])) {
                $options['except'] = [$options['except']];
            }

            if (in_array($enum, $options['except'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks that value is exactly $comparedTo.
     *
     * @param mixed $check Value to check
     * @param mixed $comparedTo Value to compare
     * @return bool Success
     */
    public static function equalTo(mixed $check, mixed $comparedTo): bool
    {
        return $check === $comparedTo;
    }

    /**
     * Checks that value has a valid file extension.
     *
     * Supports checking `\Psr\Http\Message\UploadedFileInterface` instances and
     * and arrays with a `name` key.
     *
     * @param mixed $check Value to check
     * @param array<string> $extensions file extensions to allow. By default extensions are 'gif', 'jpeg', 'png', 'jpg'
     * @return bool Success
     */
    public static function extension(mixed $check, array $extensions = ['gif', 'jpeg', 'png', 'jpg']): bool
    {
        if (interface_exists(UploadedFileInterface::class) && $check instanceof UploadedFileInterface) {
            $check = $check->getClientFilename();
        } elseif (is_array($check) && isset($check['name'])) {
            $check = $check['name'];
        } elseif (is_array($check)) {
            return static::extension(array_shift($check), $extensions);
        }

        if (!$check) {
            return false;
        }

        $extension = strtolower(pathinfo($check, PATHINFO_EXTENSION));
        foreach ($extensions as $value) {
            if ($extension === strtolower($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validation of an IP address.
     *
     * @param mixed $check The string to test.
     * @param string $type The IP Protocol version to validate against
     * @return bool Success
     */
    public static function ip(mixed $check, string $type = 'both'): bool
    {
        if (!is_string($check)) {
            return false;
        }

        $type = strtolower($type);
        $flags = 0;
        if ($type === 'ipv4') {
            $flags = FILTER_FLAG_IPV4;
        }
        if ($type === 'ipv6') {
            $flags = FILTER_FLAG_IPV6;
        }

        return (bool)filter_var($check, FILTER_VALIDATE_IP, ['flags' => $flags]);
    }

    /**
     * Checks whether the length of a string (in characters) is greater or equal to a minimal length.
     *
     * @param mixed $check The string to test
     * @param int $min The minimal string length
     * @return bool Success
     */
    public static function minLength(mixed $check, int $min): bool
    {
        if (!is_scalar($check)) {
            return false;
        }

        return mb_strlen((string)$check) >= $min;
    }

    /**
     * Checks whether the length of a string (in characters) is smaller or equal to a maximal length.
     *
     * @param mixed $check The string to test
     * @param int $max The maximal string length
     * @return bool Success
     */
    public static function maxLength(mixed $check, int $max): bool
    {
        if (!is_scalar($check)) {
            return false;
        }

        return mb_strlen((string)$check) <= $max;
    }

    /**
     * Checks whether the length of a string (in bytes) is greater or equal to a minimal length.
     *
     * @param mixed $check The string to test
     * @param int $min The minimal string length (in bytes)
     * @return bool Success
     */
    public static function minLengthBytes(mixed $check, int $min): bool
    {
        if (!is_scalar($check)) {
            return false;
        }

        return strlen((string)$check) >= $min;
    }

    /**
     * Checks whether the length of a string (in bytes) is smaller or equal to a maximal length.
     *
     * @param mixed $check The string to test
     * @param int $max The maximal string length
     * @return bool Success
     */
    public static function maxLengthBytes(mixed $check, int $max): bool
    {
        if (!is_scalar($check)) {
            return false;
        }

        return strlen((string)$check) <= $max;
    }

    /**
     * Checks that a value is a monetary amount.
     *
     * @param mixed $check Value to check
     * @param string $symbolPosition Where symbol is located (left/right)
     * @return bool Success
     */
    public static function money(mixed $check, string $symbolPosition = 'left'): bool
    {
        $money = '(?!0,?\d)(?:\d{1,3}(?:([, .])\d{3})?(?:\1\d{3})*|(?:\d+))((?!\1)[,.]\d{1,2})?';
        if ($symbolPosition === 'right') {
            $regex = '/^' . $money . '(?<!\x{00a2})\p{Sc}?$/u';
        } else {
            $regex = '/^(?!\x{00a2})\p{Sc}?' . $money . '$/u';
        }

        return static::_check($check, $regex);
    }

    /**
     * Validates a multiple select. Comparison is case sensitive by default.
     *
     * Valid Options
     *
     * - in => provide a list of choices that selections must be made from
     * - max => maximum number of non-zero choices that can be made
     * - min => minimum number of non-zero choices that can be made
     *
     * @param mixed $check Value to check
     * @param array<string, mixed> $options Options for the check.
     * @param bool $caseInsensitive Set to true for case insensitive comparison.
     * @return bool Success
     */
    public static function multiple(mixed $check, array $options = [], bool $caseInsensitive = false): bool
    {
        $defaults = ['in' => null, 'max' => null, 'min' => null];
        $options += $defaults;

        $check = array_filter((array)$check, function ($value) {
            return $value || is_numeric($value);
        });
        if (!$check) {
            return false;
        }
        if ($options['max'] && count($check) > $options['max']) {
            return false;
        }
        if ($options['min'] && count($check) < $options['min']) {
            return false;
        }
        if ($options['in'] && is_array($options['in'])) {
            if ($caseInsensitive) {
                $options['in'] = array_map('mb_strtolower', $options['in']);
            }
            foreach ($check as $val) {
                $strict = !is_numeric($val);
                if ($caseInsensitive) {
                    $val = mb_strtolower((string)$val);
                }
                if (!in_array((string)$val, $options['in'], $strict)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Checks if a value is numeric.
     *
     * @param mixed $check Value to check
     * @return bool Success
     */
    public static function numeric(mixed $check): bool
    {
        return is_numeric($check);
    }

    /**
     * Checks if a value is a natural number.
     *
     * @param mixed $check Value to check
     * @param bool $allowZero Set true to allow zero, defaults to false
     * @return bool Success
     * @see https://en.wikipedia.org/wiki/Natural_number
     */
    public static function naturalNumber(mixed $check, bool $allowZero = false): bool
    {
        $regex = $allowZero ? '/^(?:0|[1-9][0-9]*)$/' : '/^[1-9][0-9]*$/';

        return static::_check($check, $regex);
    }

    /**
     * Validates that a number is in specified range.
     *
     * If $lower and $upper are set, the range is inclusive.
     * If they are not set, will return true if $check is a
     * legal finite on this platform.
     *
     * @param mixed $check Value to check
     * @param float|null $lower Lower limit
     * @param float|null $upper Upper limit
     * @return bool Success
     */
    public static function range(mixed $check, ?float $lower = null, ?float $upper = null): bool
    {
        if (!is_numeric($check)) {
            return false;
        }
        if ((float)$check != $check) {
            return false;
        }
        if (isset($lower, $upper)) {
            return $check >= $lower && $check <= $upper;
        }

        return is_finite((float)$check);
    }

    /**
     * Checks that a value is a valid URL according to https://www.w3.org/Addressing/URL/url-spec.txt
     *
     * The regex checks for the following component parts:
     *
     * - a valid, optional, scheme
     * - a valid IP address OR
     *   a valid domain name as defined by section 2.3.1 of https://www.ietf.org/rfc/rfc1035.txt
     *   with an optional port number
     * - an optional valid path
     * - an optional query string (get parameters)
     * - an optional fragment (anchor tag) as defined in RFC 3986
     *
     * @param mixed $check Value to check
     * @param bool $strict Require URL to be prefixed by a valid scheme (one of http(s)/ftp(s)/file/news/gopher)
     * @return bool Success
     * @link https://tools.ietf.org/html/rfc3986
     */
    public static function url(mixed $check, bool $strict = false): bool
    {
        if (!is_string($check)) {
            return false;
        }

        static::_populateIp();

        $emoji = '\x{1F190}-\x{1F9EF}';
        $alpha = '0-9\p{L}\p{N}' . $emoji;
        $hex = '(%[0-9a-f]{2})';
        $subDelimiters = preg_quote('/!"$&\'()*+,-.@_:;=~[]', '/');
        $path = '([' . $subDelimiters . $alpha . ']|' . $hex . ')';
        $fragmentAndQuery = '([\?' . $subDelimiters . $alpha . ']|' . $hex . ')';
        // phpcs:disable Generic.Files.LineLength
        $regex = '/^(?:(?:https?|ftps?|sftp|file|news|gopher):\/\/)' . ($strict ? '' : '?') .
            '(?:' . static::$_pattern['IPv4'] . '|\[' . static::$_pattern['IPv6'] . '\]|' . static::$_pattern['hostname'] . ')(?::[1-9][0-9]{0,4})?' .
            '(?:\/' . $path . '*)?' .
            '(?:\?' . $fragmentAndQuery . '*)?' .
            '(?:#' . $fragmentAndQuery . '*)?$/iu';
        // phpcs:enable Generic.Files.LineLength

        return static::_check($check, $regex);
    }

    /**
     * Checks if a value is in a given list. Comparison is case-sensitive by default.
     *
     * @param mixed $check Value to check.
     * @param array<string> $list List to check against.
     * @param bool $caseInsensitive Set to true for case-insensitive comparison.
     * @return bool Success.
     */
    public static function inList(mixed $check, array $list, bool $caseInsensitive = false): bool
    {
        if (!is_scalar($check)) {
            return false;
        }
        if ($caseInsensitive) {
            $list = array_map('mb_strtolower', $list);
            $check = mb_strtolower((string)$check);
        } else {
            $list = array_map('strval', $list);
        }

        return in_array((string)$check, $list, true);
    }

    /**
     * Checks that a value is a valid UUID - https://tools.ietf.org/html/rfc4122
     *
     * @param mixed $check Value to check
     * @return bool Success
     */
    public static function uuid(mixed $check): bool
    {
        $regex = '/^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[0-5][a-fA-F0-9]{3}-[089aAbB][a-fA-F0-9]{3}-[a-fA-F0-9]{12}$/';

        return self::_check($check, $regex);
    }

    /**
     * Runs a regular expression match.
     *
     * @param mixed $check Value to check against the $regex expression
     * @param string $regex Regular expression
     * @return bool Success of match
     */
    protected static function _check(mixed $check, string $regex): bool
    {
        return is_scalar($check) && preg_match($regex, (string)$check);
    }

    /**
     * Luhn algorithm
     *
     * @param mixed $check Value to check.
     * @return bool Success
     * @see https://en.wikipedia.org/wiki/Luhn_algorithm
     */
    public static function luhn(mixed $check): bool
    {
        if (!is_scalar($check) || (int)$check === 0) {
            return false;
        }
        $sum = 0;
        $check = (string)$check;
        $length = strlen($check);

        for ($position = 1 - ($length % 2); $position < $length; $position += 2) {
            $sum += (int)$check[$position];
        }

        for ($position = $length % 2; $position < $length; $position += 2) {
            $number = (int)$check[$position] * 2;
            $sum += $number < 10 ? $number : $number - 9;
        }

        return $sum % 10 === 0;
    }

    /**
     * Checks the mime type of a file.
     *
     * Will check the mimetype of files/UploadedFileInterface instances
     * by checking the using finfo on the file, not relying on the content-type
     * sent by the client.
     *
     * @param mixed $check Value to check.
     * @param array|string $mimeTypes Array of mime types or regex pattern to check.
     * @return bool Success
     * @throws \Cake\Core\Exception\CakeException when mime type can not be determined.
     */
    public static function mimeType(mixed $check, array|string $mimeTypes = []): bool
    {
        $file = static::getFilename($check);
        if ($file === null) {
            return false;
        }

        if (!function_exists('finfo_open')) {
            throw new CakeException('ext/fileinfo is required for validating file mime types');
        }

        if (!is_file($file)) {
            throw new CakeException('Cannot validate mimetype for a missing file');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file) : null;

        if (!$mime) {
            throw new CakeException('Can not determine the mimetype.');
        }

        if (is_string($mimeTypes)) {
            return self::_check($mime, $mimeTypes);
        }

        foreach ($mimeTypes as $key => $val) {
            $mimeTypes[$key] = strtolower($val);
        }

        return in_array(strtolower($mime), $mimeTypes, true);
    }

    /**
     * Helper for reading the file name.
     *
     * @param mixed $check The data to read a filename out of.
     * @return string|null Either the filename or null on failure.
     */
    protected static function getFilename(mixed $check): ?string
    {
        if ($check instanceof UploadedFileInterface) {
            // Uploaded files throw exceptions on upload errors.
            try {
                $uri = $check->getStream()->getMetadata('uri');
                if (is_string($uri)) {
                    return $uri;
                }

                return null;
            } catch (RuntimeException) {
                return null;
            }
        }

        if (is_string($check)) {
            return $check;
        }

        return null;
    }

    /**
     * Checks the filesize
     *
     * Will check the filesize of files/UploadedFileInterface instances
     * by checking the filesize() on disk and not relying on the length
     * reported by the client.
     *
     * @param mixed $check Value to check.
     * @param string $operator See `Validation::comparison()`.
     * @param string|int $size Size in bytes or human readable string like '5MB'.
     * @return bool Success
     */
    public static function fileSize(mixed $check, string $operator, string|int $size): bool
    {
        $file = static::getFilename($check);
        if ($file === null) {
            return false;
        }

        if (is_string($size)) {
            $size = Text::parseFileSize($size);
        }
        $filesize = filesize($file);

        return static::comparison($filesize, $operator, $size);
    }

    /**
     * Checking for upload errors
     *
     * Supports checking `\Psr\Http\Message\UploadedFileInterface` instances and
     * and arrays with a `error` key.
     *
     * @param mixed $check Value to check.
     * @param bool $allowNoFile Set to true to allow UPLOAD_ERR_NO_FILE as a pass.
     * @return bool
     * @see https://secure.php.net/manual/en/features.file-upload.errors.php
     */
    public static function uploadError(mixed $check, bool $allowNoFile = false): bool
    {
        if ($check instanceof UploadedFileInterface) {
            $code = $check->getError();
        } elseif (is_array($check)) {
            if (!isset($check['error'])) {
                return false;
            }

            $code = $check['error'];
        } else {
            $code = $check;
        }
        if ($allowNoFile) {
            return in_array((int)$code, [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE], true);
        }

        return (int)$code === UPLOAD_ERR_OK;
    }

    /**
     * Validate an uploaded file.
     *
     * Helps join `uploadError`, `fileSize` and `mimeType` into
     * one higher level validation method.
     *
     * ### Options
     *
     * - `types` - An array of valid mime types. If empty all types
     *   will be accepted. The `type` will not be looked at, instead
     *   the file type will be checked with ext/finfo.
     * - `minSize` - The minimum file size in bytes. Defaults to not checking.
     * - `maxSize` - The maximum file size in bytes. Defaults to not checking.
     * - `optional` - Whether this file is optional. Defaults to false.
     *   If true a missing file will pass the validator regardless of other constraints.
     *
     * @param mixed $file The uploaded file data from PHP.
     * @param array<string, mixed> $options An array of options for the validation.
     * @return bool
     */
    public static function uploadedFile(mixed $file, array $options = []): bool
    {
        if (!($file instanceof UploadedFileInterface)) {
            return false;
        }

        $options += [
            'minSize' => null,
            'maxSize' => null,
            'types' => null,
            'optional' => false,
        ];

        if (!static::uploadError($file, $options['optional'])) {
            return false;
        }

        if ($options['optional'] && $file->getError() === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        if (
            isset($options['minSize'])
            && !static::fileSize($file, static::COMPARE_GREATER_OR_EQUAL, $options['minSize'])
        ) {
            return false;
        }

        if (
            isset($options['maxSize'])
            && !static::fileSize($file, static::COMPARE_LESS_OR_EQUAL, $options['maxSize'])
        ) {
            return false;
        }

        if (isset($options['types']) && !static::mimeType($file, $options['types'])) {
            return false;
        }

        return true;
    }

    /**
     * Validates the size of an uploaded image.
     *
     * @param mixed $file The uploaded file data from PHP.
     * @param array<string, mixed> $options Options to validate width and height.
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function imageSize(mixed $file, array $options): bool
    {
        if (!isset($options['height']) && !isset($options['width'])) {
            throw new InvalidArgumentException(
                'Invalid image size validation parameters! Missing `width` and / or `height`.',
            );
        }

        $file = static::getFilename($file);
        if ($file === null) {
            return false;
        }
        $width = null;
        $height = null;
        $imageSize = getimagesize($file);
        if ($imageSize) {
            [$width, $height] = $imageSize;
        }
        $validWidth = null;
        $validHeight = null;

        if (isset($options['height'])) {
            $validHeight = self::comparison($height, $options['height'][0], $options['height'][1]);
        }
        if (isset($options['width'])) {
            $validWidth = self::comparison($width, $options['width'][0], $options['width'][1]);
        }
        if ($validHeight !== null && $validWidth !== null) {
            return $validHeight && $validWidth;
        }
        if ($validHeight !== null) {
            return $validHeight;
        }
        if ($validWidth !== null) {
            return $validWidth;
        }

        throw new InvalidArgumentException('The 2nd argument is missing the `width` and / or `height` options.');
    }

    /**
     * Validates the image width.
     *
     * @param mixed $file The uploaded file data from PHP.
     * @param string $operator Comparison operator.
     * @param int $width Min or max width.
     * @return bool
     */
    public static function imageWidth(mixed $file, string $operator, int $width): bool
    {
        return self::imageSize($file, [
            'width' => [
                $operator,
                $width,
            ],
        ]);
    }

    /**
     * Validates the image height.
     *
     * @param mixed $file The uploaded file data from PHP.
     * @param string $operator Comparison operator.
     * @param int $height Min or max height.
     * @return bool
     */
    public static function imageHeight(mixed $file, string $operator, int $height): bool
    {
        return self::imageSize($file, [
            'height' => [
                $operator,
                $height,
            ],
        ]);
    }

    /**
     * Validates a geographic coordinate.
     *
     * Supported formats:
     *
     * - `<latitude>, <longitude>` Example: `-25.274398, 133.775136`
     *
     * ### Options
     *
     * - `type` - A string of the coordinate format, right now only `latLong`.
     * - `format` - By default `both`, can be `long` and `lat` as well to validate
     *   only a part of the coordinate.
     *
     * @param mixed $value Geographic location as string
     * @param array<string, mixed> $options Options for the validation logic.
     * @return bool
     */
    public static function geoCoordinate(mixed $value, array $options = []): bool
    {
        if (!is_scalar($value)) {
            return false;
        }

        $options += [
            'format' => 'both',
            'type' => 'latLong',
        ];
        if ($options['type'] !== 'latLong') {
            throw new InvalidArgumentException(sprintf(
                'Unsupported coordinate type `%s`. Use `latLong` instead.',
                $options['type'],
            ));
        }
        $pattern = '/^' . self::$_pattern['latitude'] . ',\s*' . self::$_pattern['longitude'] . '$/';
        if ($options['format'] === 'long') {
            $pattern = '/^' . self::$_pattern['longitude'] . '$/';
        }
        if ($options['format'] === 'lat') {
            $pattern = '/^' . self::$_pattern['latitude'] . '$/';
        }

        return (bool)preg_match($pattern, (string)$value);
    }

    /**
     * Convenience method for latitude validation.
     *
     * @param mixed $value Latitude as string
     * @param array<string, mixed> $options Options for the validation logic.
     * @return bool
     * @link https://en.wikipedia.org/wiki/Latitude
     * @see \Cake\Validation\Validation::geoCoordinate()
     */
    public static function latitude(mixed $value, array $options = []): bool
    {
        $options['format'] = 'lat';

        return self::geoCoordinate($value, $options);
    }

    /**
     * Convenience method for longitude validation.
     *
     * @param mixed $value Latitude as string
     * @param array<string, mixed> $options Options for the validation logic.
     * @return bool
     * @link https://en.wikipedia.org/wiki/Longitude
     * @see \Cake\Validation\Validation::geoCoordinate()
     */
    public static function longitude(mixed $value, array $options = []): bool
    {
        $options['format'] = 'long';

        return self::geoCoordinate($value, $options);
    }

    /**
     * Check that the input value is within the ascii byte range.
     *
     * This method will reject all non-string values.
     *
     * @param mixed $value The value to check
     * @return bool
     */
    public static function ascii(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return strlen($value) <= mb_strlen($value, 'utf-8');
    }

    /**
     * Check that the input value is a utf8 string.
     *
     * This method will reject all non-string values.
     *
     * # Options
     *
     * - `extended` - Disallow bytes higher within the basic multilingual plane.
     *   MySQL's older utf8 encoding type does not allow characters above
     *   the basic multilingual plane. Defaults to false.
     *
     * @param mixed $value The value to check
     * @param array<string, mixed> $options An array of options. See above for the supported options.
     * @return bool
     */
    public static function utf8(mixed $value, array $options = []): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $options += ['extended' => false];
        if ($options['extended']) {
            return preg_match('//u', $value) === 1;
        }

        return preg_match('/[\x{10000}-\x{10FFFF}]/u', $value) === 0;
    }

    /**
     * Check that the input value is an integer
     *
     * This method will accept strings that contain only integer data
     * as well.
     *
     * @param mixed $value The value to check
     * @return bool
     */
    public static function isInteger(mixed $value): bool
    {
        if (is_int($value)) {
            return true;
        }

        if (!is_string($value) || !is_numeric($value)) {
            return false;
        }

        return (bool)preg_match('/^-?[0-9]+$/', $value);
    }

    /**
     * Check that the input value is an array.
     *
     * @param mixed $value The value to check
     * @return bool
     */
    public static function isArray(mixed $value): bool
    {
        return is_array($value);
    }

    /**
     * Check that the input value is a scalar.
     *
     * This method will accept integers, floats, strings and booleans, but
     * not accept arrays, objects, resources and nulls.
     *
     * @param mixed $value The value to check
     * @return bool
     */
    public static function isScalar(mixed $value): bool
    {
        return is_scalar($value);
    }

    /**
     * Check that the input value is a 6 digits hex color.
     *
     * @param mixed $check The value to check
     * @return bool Success
     */
    public static function hexColor(mixed $check): bool
    {
        return static::_check($check, '/^#[0-9a-f]{6}$/iD');
    }

    /**
     * Check that the input value has a valid International Bank Account Number IBAN syntax
     * Requirements are uppercase, no whitespaces, max length 34, country code and checksum exist at right spots,
     * body matches against checksum via Mod97-10 algorithm
     *
     * @param mixed $check The value to check
     * @return bool Success
     */
    public static function iban(mixed $check): bool
    {
        if (
            !is_string($check) ||
            !preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/', $check)
        ) {
            return false;
        }

        $country = substr($check, 0, 2);
        $checkInt = intval(substr($check, 2, 2));
        $account = substr($check, 4);
        $search = range('A', 'Z');
        $replace = [];
        foreach (range(10, 35) as $tmp) {
            $replace[] = strval($tmp);
        }
        $numStr = str_replace($search, $replace, $account . $country . '00');
        $checksum = intval(substr($numStr, 0, 1));
        $numStrLength = strlen($numStr);
        for ($pos = 1; $pos < $numStrLength; $pos++) {
            $checksum *= 10;
            $checksum += intval(substr($numStr, $pos, 1));
            $checksum %= 97;
        }

        return $checkInt === 98 - $checksum;
    }

    /**
     * Converts an array representing a date or datetime into a ISO string.
     * The arrays are typically sent for validation from a form generated by
     * the CakePHP FormHelper.
     *
     * @param array<string, mixed> $value The array representing a date or datetime.
     * @return string
     */
    protected static function _getDateString(array $value): string
    {
        $formatted = '';
        if (
            isset($value['year'], $value['month'], $value['day']) &&
            (
                is_numeric($value['year']) &&
                is_numeric($value['month']) &&
                is_numeric($value['day'])
            )
        ) {
            $formatted .= sprintf('%d-%02d-%02d ', $value['year'], $value['month'], $value['day']);
        }

        if (isset($value['hour'])) {
            if (isset($value['meridian']) && (int)$value['hour'] === 12) {
                $value['hour'] = 0;
            }
            if (isset($value['meridian'])) {
                $value['hour'] = strtolower($value['meridian']) === 'am' ? $value['hour'] : $value['hour'] + 12;
            }
            $value += ['minute' => 0, 'second' => 0, 'microsecond' => 0];
            if (
                is_numeric($value['hour']) &&
                is_numeric($value['minute']) &&
                is_numeric($value['second']) &&
                is_numeric($value['microsecond'])
            ) {
                $formatted .= sprintf(
                    '%02d:%02d:%02d.%06d',
                    $value['hour'],
                    $value['minute'],
                    $value['second'],
                    $value['microsecond'],
                );
            }
        }

        return trim($formatted);
    }

    /**
     * Lazily populate the IP address patterns used for validations
     *
     * @return void
     */
    protected static function _populateIp(): void
    {
        // phpcs:disable Generic.Files.LineLength
        if (!isset(static::$_pattern['IPv6'])) {
            $pattern = '((([0-9A-Fa-f]{1,4}:){7}(([0-9A-Fa-f]{1,4})|:))|(([0-9A-Fa-f]{1,4}:){6}';
            $pattern .= '(:|((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})';
            $pattern .= '|(:[0-9A-Fa-f]{1,4})))|(([0-9A-Fa-f]{1,4}:){5}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})';
            $pattern .= '(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:)';
            $pattern .= '{4}(:[0-9A-Fa-f]{1,4}){0,1}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2}))';
            $pattern .= '{3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:){3}(:[0-9A-Fa-f]{1,4}){0,2}';
            $pattern .= '((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|';
            $pattern .= '((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:){2}(:[0-9A-Fa-f]{1,4}){0,3}';
            $pattern .= '((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2}))';
            $pattern .= '{3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:)(:[0-9A-Fa-f]{1,4})';
            $pattern .= '{0,4}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)';
            $pattern .= '|((:[0-9A-Fa-f]{1,4}){1,2})))|(:(:[0-9A-Fa-f]{1,4}){0,5}((:((25[0-5]|2[0-4]';
            $pattern .= '\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4})';
            $pattern .= '{1,2})))|(((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})))(%.+)?';

            static::$_pattern['IPv6'] = $pattern;
        }
        if (!isset(static::$_pattern['IPv4'])) {
            $pattern = '(?:(?:25[0-5]|2[0-4][0-9]|(?:(?:1[0-9])?|[1-9]?)[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|(?:(?:1[0-9])?|[1-9]?)[0-9])';
            static::$_pattern['IPv4'] = $pattern;
        }
        // phpcs:enable Generic.Files.LineLength
    }

    /**
     * Reset internal variables for another validation run.
     *
     * @return void
     */
    protected static function _reset(): void
    {
        static::$errors = [];
    }
}
