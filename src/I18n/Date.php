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

use Cake\Chronos\Date as BaseDate;
use IntlDateFormatter;
use JsonSerializable;

/**
 * Extends the Date class provided by Chronos.
 *
 * Adds handy methods and locale-aware formatting helpers
 */
class Date extends BaseDate implements JsonSerializable
{
    use DateFormatTrait;

    /**
     * The format to use when formatting a time using `Cake\I18n\Date::i18nFormat()`
     * and `__toString`
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
     * @see \Cake\I18n\DateFormatTrait::i18nFormat()
     */
    protected static $_toStringFormat = [IntlDateFormatter::SHORT, -1];

    /**
     * The format to use when formatting a time using `Cake\I18n\Date::timeAgoInWords()`
     * and the difference is more than `Cake\I18n\Date::$wordEnd`
     *
     * @var string
     * @see \Cake\I18n\DateFormatTrait::parseDate()
     */
    public static $wordFormat = [IntlDateFormatter::SHORT, -1];

    /**
     * The format to use when formatting a time using `Cake\I18n\Date::nice()`
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
        'year' => "day",
        'month' => "day",
        'week' => "day",
        'day' => "day",
        'hour' => "day",
        'minute' => "day",
        'second' => "day",
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
        $date = $this;

        $options += [
            'from' => static::now(),
            'timezone' => null,
            'format' => static::$wordFormat,
            'accuracy' => static::$wordAccuracy,
            'end' => static::$wordEnd,
            'relativeString' => __d('cake', '%s ago'),
            'absoluteString' => __d('cake', 'on %s'),
        ];
        if (is_string($options['accuracy'])) {
            foreach (static::$wordAccuracy as $key => $level) {
                $options[$key] = $options['accuracy'];
            }
        } else {
            $options['accuracy'] += static::$wordAccuracy;
        }
        if ($options['timezone']) {
            $date = $date->timezone($options['timezone']);
        }

        $now = $options['from']->format('U');
        $inSeconds = $date->format('U');
        $backwards = ($inSeconds > $now);

        $futureTime = $now;
        $pastTime = $inSeconds;
        if ($backwards) {
            $futureTime = $inSeconds;
            $pastTime = $now;
        }
        $diff = $futureTime - $pastTime;

        if (!$diff) {
            return __d('cake', 'today');
        }

        if ($diff > abs($now - (new static($options['end']))->format('U'))) {
            return sprintf($options['absoluteString'], $date->i18nFormat($options['format']));
        }

        // If more than a week, then take into account the length of months
        if ($diff >= 604800) {
            list($future['H'], $future['i'], $future['s'], $future['d'], $future['m'], $future['Y']) = explode('/', date('H/i/s/d/m/Y', $futureTime));

            list($past['H'], $past['i'], $past['s'], $past['d'], $past['m'], $past['Y']) = explode('/', date('H/i/s/d/m/Y', $pastTime));
            $weeks = $days = $hours = $minutes = $seconds = 0;

            $years = $future['Y'] - $past['Y'];
            $months = $future['m'] + ((12 * $years) - $past['m']);

            if ($months >= 12) {
                $years = floor($months / 12);
                $months = $months - ($years * 12);
            }
            if ($future['m'] < $past['m'] && $future['Y'] - $past['Y'] === 1) {
                $years--;
            }

            if ($future['d'] >= $past['d']) {
                $days = $future['d'] - $past['d'];
            } else {
                $daysInPastMonth = date('t', $pastTime);
                $daysInFutureMonth = date('t', mktime(0, 0, 0, $future['m'] - 1, 1, $future['Y']));

                if (!$backwards) {
                    $days = ($daysInPastMonth - $past['d']) + $future['d'];
                } else {
                    $days = ($daysInFutureMonth - $past['d']) + $future['d'];
                }

                if ($future['m'] != $past['m']) {
                    $months--;
                }
            }

            if (!$months && $years >= 1 && $diff < ($years * 31536000)) {
                $months = 11;
                $years--;
            }

            if ($months >= 12) {
                $years = $years + 1;
                $months = $months - 12;
            }

            if ($days >= 7) {
                $weeks = floor($days / 7);
                $days = $days - ($weeks * 7);
            }
        } else {
            $years = $months = $weeks = 0;
            $days = floor($diff / 86400);

            $diff = $diff - ($days * 86400);

            $hours = floor($diff / 3600);
            $diff = $diff - ($hours * 3600);

            $minutes = floor($diff / 60);
            $diff = $diff - ($minutes * 60);
            $seconds = $diff;
        }

        $fWord = $options['accuracy']['day'];
        if ($years > 0) {
            $fWord = $options['accuracy']['year'];
        } elseif (abs($months) > 0) {
            $fWord = $options['accuracy']['month'];
        } elseif (abs($weeks) > 0) {
            $fWord = $options['accuracy']['week'];
        } elseif (abs($days) > 0) {
            $fWord = $options['accuracy']['day'];
        }

        $fNum = str_replace(['year', 'month', 'week', 'day'], [1, 2, 3, 4], $fWord);

        $relativeDate = '';
        if ($fNum >= 1 && $years > 0) {
            $relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '{0} year', '{0} years', $years, $years);
        }
        if ($fNum >= 2 && $months > 0) {
            $relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '{0} month', '{0} months', $months, $months);
        }
        if ($fNum >= 3 && $weeks > 0) {
            $relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '{0} week', '{0} weeks', $weeks, $weeks);
        }
        if ($fNum >= 4 && $days > 0) {
            $relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '{0} day', '{0} days', $days, $days);
        }

        // When time has passed
        if (!$backwards && $relativeDate) {
            return sprintf($options['relativeString'], $relativeDate);
        }
        if (!$backwards) {
            $aboutAgo = [
                'day' => __d('cake', 'about a day ago'),
                'week' => __d('cake', 'about a week ago'),
                'month' => __d('cake', 'about a month ago'),
                'year' => __d('cake', 'about a year ago')
            ];

            return $aboutAgo[$fWord];
        }

        // When time is to come
        if (!$relativeDate) {
            $aboutIn = [
                'day' => __d('cake', 'in about a day'),
                'week' => __d('cake', 'in about a week'),
                'month' => __d('cake', 'in about a month'),
                'year' => __d('cake', 'in about a year')
            ];

            return $aboutIn[$fWord];
        }

        return $relativeDate;
    }
}
