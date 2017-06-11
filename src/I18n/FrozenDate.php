<?php
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

use Cake\Chronos\Date as ChronosDate;
use IntlDateFormatter;
use JsonSerializable;

/**
 * Extends the Date class provided by Chronos.
 *
 * Adds handy methods and locale-aware formatting helpers
 *
 * This object provides an immutable variant of Cake\I18n\Date
 */
class FrozenDate extends ChronosDate implements JsonSerializable
{
    use DateFormatTrait;

    /**
     * The format to use when formatting a time using `Cake\I18n\Date::i18nFormat()`
     * and `__toString`
     *
     * The format should be either the formatting constants from IntlDateFormatter as
     * described in (https://secure.php.net/manual/en/class.intldateformatter.php) or a pattern
     * as specified in (http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details)
     *
     * It is possible to provide an array of 2 constants. In this case, the first position
     * will be used for formatting the date part of the object and the second position
     * will be used to format the time part.
     *
     * @var string|array|int
     * @see \Cake\I18n\DateFormatTrait::i18nFormat()
     */
    protected static $_toStringFormat = [IntlDateFormatter::SHORT, -1];

    /**
     * The format to use when formatting a time using `Cake\I18n\Date::timeAgoInWords()`
     * and the difference is more than `Cake\I18n\Date::$wordEnd`
     *
     * @var string|array|int
     * @see \Cake\I18n\DateFormatTrait::parseDate()
     */
    public static $wordFormat = [IntlDateFormatter::SHORT, -1];

    /**
     * The format to use when formatting a time using `Cake\I18n\Date::nice()`
     *
     * The format should be either the formatting constants from IntlDateFormatter as
     * described in (https://secure.php.net/manual/en/class.intldateformatter.php) or a pattern
     * as specified in (http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details)
     *
     * It is possible to provide an array of 2 constants. In this case, the first position
     * will be used for formatting the date part of the object and the second position
     * will be used to format the time part.
     *
     * @var string|array|int
     * @see \Cake\I18n\DateFormatTrait::nice()
     */
    public static $niceFormat = [IntlDateFormatter::MEDIUM, -1];

    /**
     * The format to use when formatting a time using `Date::timeAgoInWords()`
     * and the difference is less than `Date::$wordEnd`
     *
     * @var array
     * @see \Cake\I18n\Date::timeAgoInWords()
     */
    public static $wordAccuracy = [
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
    public static $wordEnd = '+1 month';

    /**
     * Returns either a relative or a formatted absolute date depending
     * on the difference between the current date and this object.
     *
     * ### Options:
     *
     * - `from` => another Date object representing the "now" date
     * - `format` => a fall back format if the relative time is longer than the duration specified by end
     * - `accuracy` => Specifies how accurate the date should be described (array)
     *    - year =>   The format if years > 0   (default "day")
     *    - month =>  The format if months > 0  (default "day")
     *    - week =>   The format if weeks > 0   (default "day")
     *    - day =>    The format if weeks > 0   (default "day")
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
     * @param array $options Array of options.
     * @return string Relative time string.
     */
    public function timeAgoInWords(array $options = [])
    {
        return static::diffFormatter()->dateAgoInWords($this, $options);
    }
}
