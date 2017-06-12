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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
use Cake\I18n\I18n;

if (!function_exists('__')) {
    /**
     * Returns a translated string if one is found; Otherwise, the submitted message.
     *
     * @param string $singular Text to translate.
     * @param array ...$args Array with arguments or multiple arguments in function.
     * @return string|null The translated text, or null if invalid.
     * @link https://book.cakephp.org/3.0/en/core-libraries/global-constants-and-functions.html#__
     */
    function __($singular, ...$args)
    {
        if (!$singular) {
            return null;
        }
        if (isset($args[0]) && is_array($args[0])) {
            $args = $args[0];
        }

        return I18n::translator()->translate($singular, $args);
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
     * @param array ...$args Array with arguments or multiple arguments in function.
     * @return string|null Plural form of translated string, or null if invalid.
     * @link https://book.cakephp.org/3.0/en/core-libraries/global-constants-and-functions.html#__n
     */
    function __n($singular, $plural, $count, ...$args)
    {
        if (!$singular) {
            return null;
        }
        if (isset($args[0]) && is_array($args[0])) {
            $args = $args[0];
        }

        return I18n::translator()->translate(
            $plural,
            ['_count' => $count, '_singular' => $singular] + $args
        );
    }

}

if (!function_exists('__d')) {
    /**
     * Allows you to override the current domain for a single message lookup.
     *
     * @param string $domain Domain.
     * @param string $msg String to translate.
     * @param array ...$args Array with arguments or multiple arguments in function.
     * @return string|null Translated string.
     * @link https://book.cakephp.org/3.0/en/core-libraries/global-constants-and-functions.html#__d
     */
    function __d($domain, $msg, ...$args)
    {
        if (!$msg) {
            return null;
        }
        if (isset($args[0]) && is_array($args[0])) {
            $args = $args[0];
        }

        return I18n::translator($domain)->translate($msg, $args);
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
     * @param array ...$args Array with arguments or multiple arguments in function.
     * @return string|null Plural form of translated string.
     * @link https://book.cakephp.org/3.0/en/core-libraries/global-constants-and-functions.html#__dn
     */
    function __dn($domain, $singular, $plural, $count, ...$args)
    {
        if (!$singular) {
            return null;
        }
        if (isset($args[0]) && is_array($args[0])) {
            $args = $args[0];
        }

        return I18n::translator($domain)->translate(
            $plural,
            ['_count' => $count, '_singular' => $singular] + $args
        );
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
     * @param array ...$args Array with arguments or multiple arguments in function.
     * @return string|null Translated string.
     * @link https://book.cakephp.org/3.0/en/core-libraries/global-constants-and-functions.html#__x
     */
    function __x($context, $singular, ...$args)
    {
        if (!$singular) {
            return null;
        }
        if (isset($args[0]) && is_array($args[0])) {
            $args = $args[0];
        }

        return I18n::translator()->translate($singular, ['_context' => $context] + $args);
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
     * @param array ...$args Array with arguments or multiple arguments in function.
     * @return string|null Plural form of translated string.
     * @link https://book.cakephp.org/3.0/en/core-libraries/global-constants-and-functions.html#__xn
     */
    function __xn($context, $singular, $plural, $count, ...$args)
    {
        if (!$singular) {
            return null;
        }
        if (isset($args[0]) && is_array($args[0])) {
            $args = $args[0];
        }

        return I18n::translator()->translate(
            $plural,
            ['_count' => $count, '_singular' => $singular, '_context' => $context] + $args
        );
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
     * @param array ...$args Array with arguments or multiple arguments in function.
     * @return string|null Translated string.
     * @link https://book.cakephp.org/3.0/en/core-libraries/global-constants-and-functions.html#__dx
     */
    function __dx($domain, $context, $msg, ...$args)
    {
        if (!$msg) {
            return null;
        }
        if (isset($args[0]) && is_array($args[0])) {
            $args = $args[0];
        }

        return I18n::translator($domain)->translate(
            $msg,
            ['_context' => $context] + $args
        );
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
     * @param array ...$args Array with arguments or multiple arguments in function.
     * @return string|null Plural form of translated string.
     * @link https://book.cakephp.org/3.0/en/core-libraries/global-constants-and-functions.html#__dxn
     */
    function __dxn($domain, $context, $singular, $plural, $count, ...$args)
    {
        if (!$singular) {
            return null;
        }
        if (isset($args[0]) && is_array($args[0])) {
            $args = $args[0];
        }

        return I18n::translator($domain)->translate(
            $plural,
            ['_count' => $count, '_singular' => $singular, '_context' => $context] + $args
        );
    }

}
