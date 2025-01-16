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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
// phpcs:disable PSR1.Files.SideEffects

use Cake\I18n\Date;
use Cake\I18n\DateTime;
use function Cake\I18n\__ as cake__;
use function Cake\I18n\__d as cake__d;
use function Cake\I18n\__dn as cake__dn;
use function Cake\I18n\__dx as cake__dx;
use function Cake\I18n\__dxn as cake__dxn;
use function Cake\I18n\__n as cake__n;
use function Cake\I18n\__x as cake__x;
use function Cake\I18n\__xn as cake__xn;
use function Cake\I18n\toDate as cakeToDate;
use function Cake\I18n\toDateTime as cakeToDateTime;

if (!function_exists('__')) {
    /**
     * Returns a translated string if one is found; Otherwise, the submitted message.
     *
     * @param string $singular Text to translate.
     * @param mixed ...$args Array with arguments or multiple arguments in function.
     * @return string The translated text.
     * @link https://book.cakephp.org/5/en/core-libraries/global-constants-and-functions.html#__
     */
    function __(string $singular, mixed ...$args): string
    {
        return cake__($singular, ...$args);
    }
}

if (!function_exists('__n')) {
    /**
     * Returns correct plural form of message identified by $singular and $plural for count $count.
     * Some languages have more than one form for plural messages dependent on the count.
     *
     * @param string $singular Singular text to translate.
     * @param string $plural Plural text.
     * @param int $count Count.
     * @param mixed ...$args Array with arguments or multiple arguments in function.
     * @return string Plural form of translated string.
     * @link https://book.cakephp.org/5/en/core-libraries/global-constants-and-functions.html#__n
     */
    function __n(string $singular, string $plural, int $count, mixed ...$args): string
    {
        return cake__n($singular, $plural, $count, ...$args);
    }
}

if (!function_exists('__d')) {
    /**
     * Allows you to override the current domain for a single message lookup.
     *
     * @param string $domain Domain.
     * @param string $msg String to translate.
     * @param mixed ...$args Array with arguments or multiple arguments in function.
     * @return string Translated string.
     * @link https://book.cakephp.org/5/en/core-libraries/global-constants-and-functions.html#__d
     */
    function __d(string $domain, string $msg, mixed ...$args): string
    {
        return cake__d($domain, $msg, ...$args);
    }
}

if (!function_exists('__dn')) {
    /**
     * Allows you to override the current domain for a single plural message lookup.
     * Returns correct plural form of message identified by $singular and $plural for count $count
     * from domain $domain.
     *
     * @param string $domain Domain.
     * @param string $singular Singular string to translate.
     * @param string $plural Plural.
     * @param int $count Count.
     * @param mixed ...$args Array with arguments or multiple arguments in function.
     * @return string Plural form of translated string.
     * @link https://book.cakephp.org/5/en/core-libraries/global-constants-and-functions.html#__dn
     */
    function __dn(string $domain, string $singular, string $plural, int $count, mixed ...$args): string
    {
        return cake__dn($domain, $singular, $plural, $count, ...$args);
    }
}

if (!function_exists('__x')) {
    /**
     * Returns a translated string if one is found; Otherwise, the submitted message.
     * The context is a unique identifier for the translations string that makes it unique
     * within the same domain.
     *
     * @param string $context Context of the text.
     * @param string $singular Text to translate.
     * @param mixed ...$args Array with arguments or multiple arguments in function.
     * @return string Translated string.
     * @link https://book.cakephp.org/5/en/core-libraries/global-constants-and-functions.html#__x
     */
    function __x(string $context, string $singular, mixed ...$args): string
    {
        return cake__x($context, $singular, ...$args);
    }
}

if (!function_exists('__xn')) {
    /**
     * Returns correct plural form of message identified by $singular and $plural for count $count.
     * Some languages have more than one form for plural messages dependent on the count.
     * The context is a unique identifier for the translations string that makes it unique
     * within the same domain.
     *
     * @param string $context Context of the text.
     * @param string $singular Singular text to translate.
     * @param string $plural Plural text.
     * @param int $count Count.
     * @param mixed ...$args Array with arguments or multiple arguments in function.
     * @return string Plural form of translated string.
     * @link https://book.cakephp.org/5/en/core-libraries/global-constants-and-functions.html#__xn
     */
    function __xn(string $context, string $singular, string $plural, int $count, mixed ...$args): string
    {
        return cake__xn($context, $singular, $plural, $count, ...$args);
    }
}

if (!function_exists('__dx')) {
    /**
     * Allows you to override the current domain for a single message lookup.
     * The context is a unique identifier for the translations string that makes it unique
     * within the same domain.
     *
     * @param string $domain Domain.
     * @param string $context Context of the text.
     * @param string $msg String to translate.
     * @param mixed ...$args Array with arguments or multiple arguments in function.
     * @return string Translated string.
     * @link https://book.cakephp.org/5/en/core-libraries/global-constants-and-functions.html#__dx
     */
    function __dx(string $domain, string $context, string $msg, mixed ...$args): string
    {
        return cake__dx($domain, $context, $msg, ...$args);
    }
}

if (!function_exists('__dxn')) {
    /**
     * Returns correct plural form of message identified by $singular and $plural for count $count.
     * Allows you to override the current domain for a single message lookup.
     * The context is a unique identifier for the translations string that makes it unique
     * within the same domain.
     *
     * @param string $domain Domain.
     * @param string $context Context of the text.
     * @param string $singular Singular text to translate.
     * @param string $plural Plural text.
     * @param int $count Count.
     * @param mixed ...$args Array with arguments or multiple arguments in function.
     * @return string Plural form of translated string.
     * @link https://book.cakephp.org/5/en/core-libraries/global-constants-and-functions.html#__dxn
     */
    function __dxn(
        string $domain,
        string $context,
        string $singular,
        string $plural,
        int $count,
        mixed ...$args,
    ): string {
        return cake__dxn($domain, $context, $singular, $plural, $count, ...$args);
    }
}

if (!function_exists('toDateTime')) {
    /**
     * Converts a value to a DateTime object.
     *
     *  integer - value is treated as a Unix timestamp
     *  float - value is treated as a Unix timestamp with microseconds
     *  string - value is treated as an Atom-formatted timestamp, unless otherwise specified
     *  Other values returns as null.
     *
     * @param mixed $value The value to convert to DateTime.
     * @param string $format The datetime format the value is in. Defaults to Atom (ex: 1970-01-01T12:00:00+00:00) format.
     * @return \Cake\I18n\DateTime|null Returns a DateTime object if parsing is successful, or NULL otherwise.
     * @since 5.1.1
     */
    function toDateTime(mixed $value, string $format = DateTimeInterface::ATOM): ?DateTime
    {
        return cakeToDateTime($value, $format);
    }
}

if (!function_exists('toDate')) {
    /**
     * Converts a value to a Date object.
     *
     *  integer - value is treated as a Unix timestamp
     *  float - value is treated as a Unix timestamp with microseconds
     *  string - value is treated as a I18N short formatted date, unless otherwise specified
     *  Other values returns as null.
     *
     * @param mixed $value The value to convert to Date.
     * @param string $format The date format the value is in. Defaults to Short (ex: 1970-01-01) format.
     * @return \Cake\I18n\Date|null Returns a Date object if parsing is successful, or NULL otherwise.
     * @since 5.1.1
     */
    function toDate(mixed $value, string $format = 'Y-m-d'): ?Date
    {
        return cakeToDate($value, $format);
    }
}
