<?php
namespace Cake\I18n;

use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;

class RelativeTimeFormatter
{
    protected $_time;

    public function __construct($time)
    {
        $this->_time = $time;
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
     *    - year =>   The format if years > 0   (default "day")
     *    - month =>  The format if months > 0  (default "day")
     *    - week =>   The format if weeks > 0   (default "day")
     *    - day =>    The format if weeks > 0   (default "hour")
     *    - hour =>   The format if hours > 0   (default "minute")
     *    - minute => The format if minutes > 0 (default "minute")
     *    - second => The format if seconds > 0 (default "second")
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
     * @param array $options Array of options.
     * @return string Relative time string.
     */
    public function timeAgoInWords(array $options = [])
    {
        $time = $this->_time;

        $timezone = null;
        // TODO use options like below.
        $format = FrozenTime::$wordFormat;
        $end = FrozenTime::$wordEnd;
        $relativeString = __d('cake', '%s ago');
        $absoluteString = __d('cake', 'on %s');
        $accuracy = FrozenTime::$wordAccuracy;
        $from = FrozenTime::now();
        $opts = ['timezone', 'format', 'end', 'relativeString', 'absoluteString', 'from'];

        foreach ($opts as $option) {
            if (isset($options[$option])) {
                ${$option} = $options[$option];
                unset($options[$option]);
            }
        }

        if (isset($options['accuracy'])) {
            if (is_array($options['accuracy'])) {
                $accuracy = $options['accuracy'] + $accuracy;
            } else {
                foreach ($accuracy as $key => $level) {
                    $accuracy[$key] = $options['accuracy'];
                }
            }
        }

        if ($timezone) {
            $time = $time->timezone($timezone);
        }

        $now = $from->format('U');
        $inSeconds = $time->format('U');
        $backwards = ($inSeconds > $now);

        $futureTime = $now;
        $pastTime = $inSeconds;
        if ($backwards) {
            $futureTime = $inSeconds;
            $pastTime = $now;
        }
        $diff = $futureTime - $pastTime;

        if (!$diff) {
            return __d('cake', 'just now', 'just now');
        }

        if ($diff > abs($now - (new FrozenTime($end))->format('U'))) {
            return sprintf($absoluteString, $time->i18nFormat($format));
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

        $fWord = $accuracy['second'];
        if ($years > 0) {
            $fWord = $accuracy['year'];
        } elseif (abs($months) > 0) {
            $fWord = $accuracy['month'];
        } elseif (abs($weeks) > 0) {
            $fWord = $accuracy['week'];
        } elseif (abs($days) > 0) {
            $fWord = $accuracy['day'];
        } elseif (abs($hours) > 0) {
            $fWord = $accuracy['hour'];
        } elseif (abs($minutes) > 0) {
            $fWord = $accuracy['minute'];
        }

        $fNum = str_replace(['year', 'month', 'week', 'day', 'hour', 'minute', 'second'], [1, 2, 3, 4, 5, 6, 7], $fWord);

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
        if ($fNum >= 5 && $hours > 0) {
            $relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '{0} hour', '{0} hours', $hours, $hours);
        }
        if ($fNum >= 6 && $minutes > 0) {
            $relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '{0} minute', '{0} minutes', $minutes, $minutes);
        }
        if ($fNum >= 7 && $seconds > 0) {
            $relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '{0} second', '{0} seconds', $seconds, $seconds);
        }

        // When time has passed
        if (!$backwards && $relativeDate) {
            return sprintf($relativeString, $relativeDate);
        }
        if (!$backwards) {
            $aboutAgo = [
                'second' => __d('cake', 'about a second ago'),
                'minute' => __d('cake', 'about a minute ago'),
                'hour' => __d('cake', 'about an hour ago'),
                'day' => __d('cake', 'about a day ago'),
                'week' => __d('cake', 'about a week ago'),
                'year' => __d('cake', 'about a year ago')
            ];

            return $aboutAgo[$fWord];
        }

        // When time is to come
        if (!$relativeDate) {
            $aboutIn = [
                'second' => __d('cake', 'in about a second'),
                'minute' => __d('cake', 'in about a minute'),
                'hour' => __d('cake', 'in about an hour'),
                'day' => __d('cake', 'in about a day'),
                'week' => __d('cake', 'in about a week'),
                'year' => __d('cake', 'in about a year')
            ];

            return $aboutIn[$fWord];
        }

        return $relativeDate;
    }

    public function dateAgoInWords(array $options = [])
    {
        $date = $this->_time;
        $options += [
            'from' => FrozenDate::now(),
            'timezone' => null,
            'format' => FrozenDate::$wordFormat,
            'accuracy' => FrozenDate::$wordAccuracy,
            'end' => FrozenDate::$wordEnd,
            'relativeString' => __d('cake', '%s ago'),
            'absoluteString' => __d('cake', 'on %s'),
        ];
        if (is_string($options['accuracy'])) {
            foreach (FrozenDate::$wordAccuracy as $key => $level) {
                $options[$key] = $options['accuracy'];
            }
        } else {
            $options['accuracy'] += FrozenDate::$wordAccuracy;
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

        if ($diff > abs($now - (new FrozenDate($options['end']))->format('U'))) {
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
