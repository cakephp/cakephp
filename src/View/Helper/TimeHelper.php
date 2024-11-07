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
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Chronos\ChronosDate;
use Cake\I18n\DateTime;
use Cake\View\Helper;
use Cake\View\StringTemplateTrait;
use DateTimeInterface;
use DateTimeZone;
use Exception;

/**
 * Time Helper class for easy use of time data.
 *
 * Manipulation of time data.
 *
 * @link https://book.cakephp.org/5/en/views/helpers/time.html
 * @see \Cake\I18n\Time
 */
class TimeHelper extends Helper
{
    use StringTemplateTrait;

    /**
     * Config options
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'outputTimezone' => null,
    ];

    /**
     * Get a timezone.
     *
     * Will use the provided timezone, or default output timezone if defined.
     *
     * @param \DateTimeZone|string|null $timezone The override timezone if applicable.
     * @return \DateTimeZone|string|null The chosen timezone or null.
     */
    protected function _getTimezone(DateTimeZone|string|null $timezone): DateTimeZone|string|null
    {
        if ($timezone) {
            return $timezone;
        }

        return $this->getConfig('outputTimezone');
    }

    /**
     * Returns a DateTime object, given either a UNIX timestamp or a valid strtotime() date string.
     *
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
     * @return \Cake\I18n\DateTime
     */
    public function fromString(
        ChronosDate|DateTimeInterface|string|int $dateString,
        DateTimeZone|string|null $timezone = null
    ): DateTime {
        $time = new DateTime($dateString);
        if ($timezone !== null) {
            return $time->setTimezone($timezone);
        }

        return $time;
    }

    /**
     * Returns a nicely formatted date string for given Datetime string.
     *
     * @param \DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
     * @param string|null $locale Locale string.
     * @return string Formatted date string
     */
    public function nice(
        ChronosDate|DateTimeInterface|string|int|null $dateString = null,
        DateTimeZone|string|null $timezone = null,
        ?string $locale = null
    ): string {
        $timezone = $this->_getTimezone($timezone);

        return (new DateTime($dateString))->nice($timezone, $locale);
    }

    /**
     * Returns true, if the given datetime string is today.
     *
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
     * @return bool True if the given datetime string is today.
     */
    public function isToday(
        ChronosDate|DateTimeInterface|string|int $dateString,
        DateTimeZone|string|null $timezone = null
    ): bool {
        return (new DateTime($dateString, $timezone))->isToday();
    }

    /**
     * Returns true, if the given datetime string is in the future.
     *
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
     * @return bool True if the given datetime string lies in the future.
     */
    public function isFuture(
        ChronosDate|DateTimeInterface|string|int $dateString,
        DateTimeZone|string|null $timezone = null
    ): bool {
        return (new DateTime($dateString, $timezone))->isFuture();
    }

    /**
     * Returns true, if the given datetime string is in the past.
     *
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
     * @return bool True if the given datetime string lies in the past.
     */
    public function isPast(
        ChronosDate|DateTimeInterface|string|int $dateString,
        DateTimeZone|string|null $timezone = null
    ): bool {
        return (new DateTime($dateString, $timezone))->isPast();
    }

    /**
     * Returns true if given datetime string is within this week.
     *
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
     * @return bool True if datetime string is within current week
     */
    public function isThisWeek(
        ChronosDate|DateTimeInterface|string|int $dateString,
        DateTimeZone|string|null $timezone = null
    ): bool {
        return (new DateTime($dateString, $timezone))->isThisWeek();
    }

    /**
     * Returns true if given datetime string is within this month
     *
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
     * @return bool True if datetime string is within the current month
     */
    public function isThisMonth(
        ChronosDate|DateTimeInterface|string|int $dateString,
        DateTimeZone|string|null $timezone = null
    ): bool {
        return (new DateTime($dateString, $timezone))->isThisMonth();
    }

    /**
     * Returns true if given datetime string is within the current year.
     *
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
     * @return bool True if datetime string is within current year
     */
    public function isThisYear(
        ChronosDate|DateTimeInterface|string|int $dateString,
        DateTimeZone|string|null $timezone = null
    ): bool {
        return (new DateTime($dateString, $timezone))->isThisYear();
    }

    /**
     * Returns true if given datetime string was yesterday.
     *
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
     * @return bool True if datetime string was yesterday
     */
    public function wasYesterday(
        ChronosDate|DateTimeInterface|string|int $dateString,
        DateTimeZone|string|null $timezone = null
    ): bool {
        return (new DateTime($dateString, $timezone))->isYesterday();
    }

    /**
     * Returns true if given datetime string is tomorrow.
     *
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
     * @return bool True if datetime string was yesterday
     */
    public function isTomorrow(
        ChronosDate|DateTimeInterface|string|int $dateString,
        DateTimeZone|string|null $timezone = null
    ): bool {
        return (new DateTime($dateString, $timezone))->isTomorrow();
    }

    /**
     * Returns the quarter
     *
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param bool $range if true returns a range in Y-m-d format
     * @return list<string>|int 1, 2, 3, or 4 quarter of year or array if $range true
     * @see \Cake\I18n\Time::toQuarter()
     */
    public function toQuarter(
        ChronosDate|DateTimeInterface|string|int $dateString,
        bool $range = false
    ): array|int {
        return (new DateTime($dateString))->toQuarter($range);
    }

    /**
     * Returns a UNIX timestamp from a textual datetime description.
     *
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
     * @return string UNIX timestamp
     * @see \Cake\I18n\Time::toUnix()
     */
    public function toUnix(
        ChronosDate|DateTimeInterface|string|int $dateString,
        DateTimeZone|string|null $timezone = null
    ): string {
        return (new DateTime($dateString, $timezone))->toUnixString();
    }

    /**
     * Returns a date formatted for Atom RSS feeds.
     *
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
     * @return string Formatted date string
     * @see \Cake\I18n\Time::toAtom()
     */
    public function toAtom(
        ChronosDate|DateTimeInterface|string|int $dateString,
        DateTimeZone|string|null $timezone = null
    ): string {
        $timezone = $this->_getTimezone($timezone) ?: date_default_timezone_get();

        return (new DateTime($dateString))->setTimezone($timezone)->toAtomString();
    }

    /**
     * Formats date for RSS feeds
     *
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
     * @return string Formatted date string
     */
    public function toRss(
        ChronosDate|DateTimeInterface|string|int $dateString,
        DateTimeZone|string|null $timezone = null
    ): string {
        $timezone = $this->_getTimezone($timezone) ?: date_default_timezone_get();

        return (new DateTime($dateString))->setTimezone($timezone)->toRssString();
    }

    /**
     * Formats a date into a phrase expressing the relative time.
     *
     * ### Additional options
     *
     * - `element` - The element to wrap the formatted time in.
     *   Has a few additional options:
     *   - `tag` - The tag to use, defaults to 'span'.
     *   - `class` - The class name to use, defaults to `time-ago-in-words`.
     *   - `title` - Defaults to the $dateTime input.
     *
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int $dateTime UNIX timestamp, strtotime() valid
     *   string or DateTime object.
     * @param array<string, mixed> $options Default format if timestamp is used in $dateString
     * @return string Relative time string.
     * @see \Cake\I18n\Time::timeAgoInWords()
     */
    public function timeAgoInWords(
        ChronosDate|DateTimeInterface|string|int $dateTime,
        array $options = []
    ): string {
        $element = null;
        $options += [
            'element' => null,
            'timezone' => null,
        ];
        $options['timezone'] = $this->_getTimezone($options['timezone']);
        if ($options['timezone'] && $dateTime instanceof DateTimeInterface) {
            if ($dateTime instanceof DateTime) {
                $dateTime = clone $dateTime;
            }
            /** @var \DateTimeImmutable|\DateTime $dateTime */
            $dateTime = $dateTime->setTimezone($options['timezone']);
            unset($options['timezone']);
        }

        if (!empty($options['element'])) {
            $element = [
                'tag' => 'span',
                'class' => 'time-ago-in-words',
                'title' => $dateTime,
            ];

            if (is_array($options['element'])) {
                $element = $options['element'] + $element;
            } else {
                $element['tag'] = $options['element'];
            }
            unset($options['element']);
        }
        $relativeDate = (new DateTime($dateTime))->timeAgoInWords($options);

        if ($element) {
            return sprintf(
                '<%s%s>%s</%s>',
                $element['tag'],
                $this->templater()->formatAttributes($element, ['tag']),
                $relativeDate,
                $element['tag']
            );
        }

        return $relativeDate;
    }

    /**
     * Returns true if specified datetime was within the interval specified, else false.
     *
     * @param string $timeInterval the numeric value with space then time type.
     *    Example of valid types: 6 hours, 2 days, 1 minute.
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
     * @return bool
     * @see \Cake\I18n\Time::wasWithinLast()
     */
    public function wasWithinLast(
        string $timeInterval,
        ChronosDate|DateTimeInterface|string|int $dateString,
        DateTimeZone|string|null $timezone = null
    ): bool {
        return (new DateTime($dateString, $timezone))->wasWithinLast($timeInterval);
    }

    /**
     * Returns true if specified datetime is within the interval specified, else false.
     *
     * @param string $timeInterval the numeric value with space then time type.
     *    Example of valid types: 6 hours, 2 days, 1 minute.
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
     * @return bool
     * @see \Cake\I18n\Time::wasWithinLast()
     */
    public function isWithinNext(
        string $timeInterval,
        ChronosDate|DateTimeInterface|string|int $dateString,
        DateTimeZone|string|null $timezone = null
    ): bool {
        return (new DateTime($dateString, $timezone))->isWithinNext($timeInterval);
    }

    /**
     * Returns gmt as a UNIX timestamp.
     *
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int|null $string UNIX timestamp, strtotime() valid string or DateTime object
     * @return string UNIX timestamp
     * @see \Cake\I18n\Time::gmt()
     */
    public function gmt(ChronosDate|DateTimeInterface|string|int|null $string = null): string
    {
        return (new DateTime($string))->toUnixString();
    }

    /**
     * Returns a formatted date string, given either a Time instance,
     * UNIX timestamp or a valid strtotime() date string.
     *
     * This method is an alias for TimeHelper::i18nFormat().
     *
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int|null $date UNIX timestamp, strtotime() valid string
     *   or DateTime object (or a date format string).
     * @param array<int>|string|int|null $format date format string (or a UNIX timestamp,
     *   `strtotime()` valid string or DateTime object).
     * @param string|false $invalid Default value to display on invalid dates
     * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
     * @return string|int|false Formatted and translated date string
     * @see \Cake\I18n\Time::i18nFormat()
     */
    public function format(
        ChronosDate|DateTimeInterface|string|int|null $date,
        array|string|int|null $format = null,
        string|false $invalid = false,
        DateTimeZone|string|null $timezone = null
    ): string|int|false {
        return $this->i18nFormat($date, $format, $invalid, $timezone);
    }

    /**
     * Returns a formatted date string, given either a Datetime instance,
     * UNIX timestamp or a valid strtotime() date string.
     *
     * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|string|int|null $date UNIX timestamp, strtotime() valid string or DateTime object
     * @param array|string|int|null $format Intl compatible format string.
     * @param string|false $invalid Default value to display on invalid dates
     * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
     * @return string|int|false Formatted and translated date string or value for `$invalid` on failure.
     * @throws \Exception When the date cannot be parsed
     * @see \Cake\I18n\Time::i18nFormat()
     */
    public function i18nFormat(
        ChronosDate|DateTimeInterface|string|int|null $date,
        array|string|int|null $format = null,
        string|false $invalid = false,
        DateTimeZone|string|null $timezone = null
    ): string|int|false {
        if ($date === null) {
            return $invalid;
        }
        $timezone = $this->_getTimezone($timezone);

        try {
            if ($date instanceof DateTime) {
                return $date->i18nFormat($format, $timezone);
            }

            return (new DateTime($date))->i18nFormat($format, $timezone);
        } catch (Exception $e) {
            if ($invalid === false) {
                throw $e;
            }

            return $invalid;
        }
    }

    /**
     * Event listeners.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [];
    }
}
