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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Carbon\Carbon;
use IntlDateFormatter;
use JsonSerializable;

/**
 * Extends the cakephp Time class to provide date only and locale-aware
 * formatting helpers
 *
 */
class Date extends Time implements JsonSerializable
{

    /**
     * The format to use when formatting a date using `Cake\I18n\Date::i18nFormat()`
     * and `__toString`
     *
     * The format should be either the formatting constants from IntlDateFormatter as
     * described in (http://www.php.net/manual/en/class.intldateformatter.php) or a pattern
     * as specified in (http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details)
     *
     * It is possible to provide an array of 2 constants. In this case, the first position
     * will be used for formatting the date part of the object and the second position
     * will be used to format the time part, which should be -1 for date only formatting.
     *
     * @var mixed
     * @see \Cake\I18n\Time::i18nFormat()
     */
    protected static $_toStringFormat = [IntlDateFormatter::SHORT, -1];

    /**
     * The format to use when formatting a date using `Cake\I18n\Date::nice()`
     *
     * The format should be either the formatting constants from IntlDateFormatter as
     * described in (http://www.php.net/manual/en/class.intldateformatter.php) or a pattern
     * as specified in (http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details)
     *
     * It is possible to provide an array of 2 constants. In this case, the first position
     * will be used for formatting the date part of the object and the second position
     * will be used to format the time part, which should be -1 for date only formatting.
     *
     * @var mixed
     * @see \Cake\I18n\Time::nice()
     */
    public static $niceFormat = [IntlDateFormatter::MEDIUM, -1];

    /**
     * The format to use when formatting a date using `Cake\I18n\Time::timeAgoInWords()`
     * and the difference is more than `Cake\I18n\Time::$wordEnd`
     *
     * @var string
     * @see \Cake\I18n\Time::timeAgoInWords()
     */
    public static $wordFormat = [IntlDateFormatter::SHORT, -1];

    /**
     * The format to use when formatting a date using `Time::timeAgoInWords()`
     * and the difference is less than `Date::$wordEnd`
     *
     * @var array
     * @see \Cake\I18n\Time::timeAgoInWords()
     */
    public static $wordAccuracy = [
        'year' => "day",
        'month' => "day",
        'week' => "day",
    ];

    /**
     * The end of relative date telling
     *
     * @var string
     * @see \Cake\I18n\Time::timeAgoInWords()
     */
    public static $wordEnd = '+1 year';
}
