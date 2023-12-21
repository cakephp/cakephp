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
namespace Cake\I18n;

use NumberFormatter;
use function Cake\Core\deprecationWarning;

/**
 * Number helper library.
 *
 * Methods to make numbers more readable.
 *
 * @link https://book.cakephp.org/4/en/core-libraries/number.html
 */
class Number
{
    /**
     * Default locale
     *
     * @var string
     */
    public const DEFAULT_LOCALE = 'en_US';

    /**
     * Format type to format as currency
     *
     * @var string
     */
    public const FORMAT_CURRENCY = 'currency';

    /**
     * Format type to format as currency, accounting style (negative numbers in parentheses)
     *
     * @var string
     */
    public const FORMAT_CURRENCY_ACCOUNTING = 'currency_accounting';

    /**
     * ICU Constant for accounting format; not yet widely supported by INTL library.
     * This will be able to go away once CakePHP minimum PHP requirement is 7.4.1 or higher.
     * See UNUM_CURRENCY_ACCOUNTING in https://unicode-org.github.io/icu-docs/apidoc/released/icu4c/unum_8h.html
     *
     * @var int
     */
    public const CURRENCY_ACCOUNTING = 12;

    /**
     * A list of number formatters indexed by locale and type
     *
     * @var array<string, array<int, mixed>>
     */
    protected static $_formatters = [];

    /**
     * Default currency used by Number::currency()
     *
     * @var string|null
     */
    protected static $_defaultCurrency;

    /**
     * Default currency format used by Number::currency()
     *
     * @var string|null
     */
    protected static $_defaultCurrencyFormat;

    /**
     * Formats a number with a level of precision.
     *
     * Options:
     *
     * - `locale`: The locale name to use for formatting the number, e.g. fr_FR
     *
     * @param string|float|int $value A floating point number.
     * @param int $precision The precision of the returned number.
     * @param array<string, mixed> $options Additional options
     * @return string Formatted float.
     * @link https://book.cakephp.org/4/en/core-libraries/number.html#formatting-floating-point-numbers
     */
    public static function precision($value, int $precision = 3, array $options = []): string
    {
        $formatter = static::formatter(['precision' => $precision, 'places' => $precision] + $options);

        return $formatter->format((float)$value);
    }

    /**
     * Returns a formatted-for-humans file size.
     *
     * @param string|float|int $size Size in bytes
     * @return string Human readable size
     * @link https://book.cakephp.org/4/en/core-libraries/number.html#interacting-with-human-readable-values
     */
    public static function toReadableSize($size): string
    {
        $size = (int)$size;

        switch (true) {
            case $size < 1024:
                return __dn('cake', '{0,number,integer} Byte', '{0,number,integer} Bytes', $size, $size);
            case round($size / 1024) < 1024:
                return __d('cake', '{0,number,#,###.##} KB', $size / 1024);
            case round($size / 1024 / 1024, 2) < 1024:
                return __d('cake', '{0,number,#,###.##} MB', $size / 1024 / 1024);
            case round($size / 1024 / 1024 / 1024, 2) < 1024:
                return __d('cake', '{0,number,#,###.##} GB', $size / 1024 / 1024 / 1024);
            default:
                return __d('cake', '{0,number,#,###.##} TB', $size / 1024 / 1024 / 1024 / 1024);
        }
    }

    /**
     * Formats a number into a percentage string.
     *
     * Options:
     *
     * - `multiply`: Multiply the input value by 100 for decimal percentages.
     * - `locale`: The locale name to use for formatting the number, e.g. fr_FR
     *
     * @param string|float|int $value A floating point number
     * @param int $precision The precision of the returned number
     * @param array<string, mixed> $options Options
     * @return string Percentage string
     * @link https://book.cakephp.org/4/en/core-libraries/number.html#formatting-percentages
     */
    public static function toPercentage($value, int $precision = 2, array $options = []): string
    {
        $options += ['multiply' => false, 'type' => NumberFormatter::PERCENT];
        if (!$options['multiply']) {
            $value = (float)$value / 100;
        }

        return static::precision($value, $precision, $options);
    }

    /**
     * Formats a number into the correct locale format
     *
     * Options:
     *
     * - `places` - Minimum number or decimals to use, e.g 0
     * - `precision` - Maximum Number of decimal places to use, e.g. 2
     * - `pattern` - An ICU number pattern to use for formatting the number. e.g #,##0.00
     * - `locale` - The locale name to use for formatting the number, e.g. fr_FR
     * - `before` - The string to place before whole numbers, e.g. '['
     * - `after` - The string to place after decimal numbers, e.g. ']'
     *
     * @param string|int|float $value A floating point number.
     * @param array<string, mixed> $options An array with options.
     * @return string Formatted number
     */
    public static function format($value, array $options = []): string
    {
        $formatter = static::formatter($options);
        $options += ['before' => '', 'after' => ''];

        return $options['before'] . $formatter->format((float)$value) . $options['after'];
    }

    /**
     * Parse a localized numeric string and transform it in a float point
     *
     * Options:
     *
     * - `locale` - The locale name to use for parsing the number, e.g. fr_FR
     * - `type` - The formatter type to construct, set it to `currency` if you need to parse
     *    numbers representing money.
     *
     * @param string $value A numeric string.
     * @param array<string, mixed> $options An array with options.
     * @return float point number
     */
    public static function parseFloat(string $value, array $options = []): float
    {
        $formatter = static::formatter($options);

        return (float)$formatter->parse($value, NumberFormatter::TYPE_DOUBLE);
    }

    /**
     * Formats a number into the correct locale format to show deltas (signed differences in value).
     *
     * ### Options
     *
     * - `places` - Minimum number or decimals to use, e.g 0
     * - `precision` - Maximum Number of decimal places to use, e.g. 2
     * - `locale` - The locale name to use for formatting the number, e.g. fr_FR
     * - `before` - The string to place before whole numbers, e.g. '['
     * - `after` - The string to place after decimal numbers, e.g. ']'
     *
     * @param string|float $value A floating point number
     * @param array<string, mixed> $options Options list.
     * @return string formatted delta
     */
    public static function formatDelta($value, array $options = []): string
    {
        $options += ['places' => 0];
        $value = number_format((float)$value, $options['places'], '.', '');
        $sign = $value > 0 ? '+' : '';
        $options['before'] = isset($options['before']) ? $options['before'] . $sign : $sign;

        return static::format($value, $options);
    }

    /**
     * Formats a number into a currency format.
     *
     * ### Options
     *
     * - `locale` - The locale name to use for formatting the number, e.g. fr_FR
     * - `fractionSymbol` - The currency symbol to use for fractional numbers.
     * - `fractionPosition` - The position the fraction symbol should be placed
     *    valid options are 'before' & 'after'.
     * - `before` - Text to display before the rendered number
     * - `after` - Text to display after the rendered number
     * - `zero` - The text to use for zero values, can be a string or a number. e.g. 0, 'Free!'
     * - `places` - Number of decimal places to use. e.g. 2
     * - `precision` - Maximum Number of decimal places to use, e.g. 2
     * - `pattern` - An ICU number pattern to use for formatting the number. e.g #,##0.00
     * - `useIntlCode` - Whether to replace the currency symbol with the international
     *   currency code.
     *
     * @param string|float $value Value to format.
     * @param string|null $currency International currency name such as 'USD', 'EUR', 'JPY', 'CAD'
     * @param array<string, mixed> $options Options list.
     * @return string Number formatted as a currency.
     */
    public static function currency($value, ?string $currency = null, array $options = []): string
    {
        $value = (float)$value;
        $currency = $currency ?: static::getDefaultCurrency();

        if (isset($options['zero']) && !$value) {
            return $options['zero'];
        }

        $formatter = static::formatter(['type' => static::getDefaultCurrencyFormat()] + $options);
        $abs = abs($value);
        if (!empty($options['fractionSymbol']) && $abs > 0 && $abs < 1) {
            $value *= 100;
            $pos = $options['fractionPosition'] ?? 'after';

            return static::format($value, ['precision' => 0, $pos => $options['fractionSymbol']]);
        }

        $before = $options['before'] ?? '';
        $after = $options['after'] ?? '';
        $value = $formatter->formatCurrency($value, $currency);

        return $before . $value . $after;
    }

    /**
     * Getter/setter for default currency. This behavior is *deprecated* and will be
     * removed in future versions of CakePHP.
     *
     * @deprecated 3.9.0 Use {@link getDefaultCurrency()} and {@link setDefaultCurrency()} instead.
     * @param string|false|null $currency Default currency string to be used by {@link currency()}
     * if $currency argument is not provided. If boolean false is passed, it will clear the
     * currently stored value
     * @return string|null Currency
     */
    public static function defaultCurrency($currency = null): ?string
    {
        deprecationWarning(
            'Number::defaultCurrency() is deprecated. ' .
            'Use Number::setDefaultCurrency()/getDefaultCurrency() instead.'
        );

        if ($currency === false) {
            static::setDefaultCurrency(null);

            // This doesn't seem like a useful result to return, but it's what the old version did.
            // Retaining it for backward compatibility.
            return null;
        }
        if ($currency !== null) {
            static::setDefaultCurrency($currency);
        }

        return static::getDefaultCurrency();
    }

    /**
     * Getter for default currency
     *
     * @return string Currency
     */
    public static function getDefaultCurrency(): string
    {
        if (static::$_defaultCurrency === null) {
            $locale = ini_get('intl.default_locale') ?: static::DEFAULT_LOCALE;
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            static::$_defaultCurrency = $formatter->getTextAttribute(NumberFormatter::CURRENCY_CODE);
        }

        return static::$_defaultCurrency;
    }

    /**
     * Setter for default currency
     *
     * @param string|null $currency Default currency string to be used by {@link currency()}
     * if $currency argument is not provided. If null is passed, it will clear the
     * currently stored value
     * @return void
     */
    public static function setDefaultCurrency(?string $currency = null): void
    {
        static::$_defaultCurrency = $currency;
    }

    /**
     * Getter for default currency format
     *
     * @return string Currency Format
     */
    public static function getDefaultCurrencyFormat(): string
    {
        if (static::$_defaultCurrencyFormat === null) {
            static::$_defaultCurrencyFormat = static::FORMAT_CURRENCY;
        }

        return static::$_defaultCurrencyFormat;
    }

    /**
     * Setter for default currency format
     *
     * @param string|null $currencyFormat Default currency format to be used by currency()
     * if $currencyFormat argument is not provided. If null is passed, it will clear the
     * currently stored value
     * @return void
     */
    public static function setDefaultCurrencyFormat($currencyFormat = null): void
    {
        static::$_defaultCurrencyFormat = $currencyFormat;
    }

    /**
     * Returns a formatter object that can be reused for similar formatting task
     * under the same locale and options. This is often a speedier alternative to
     * using other methods in this class as only one formatter object needs to be
     * constructed.
     *
     * ### Options
     *
     * - `locale` - The locale name to use for formatting the number, e.g. fr_FR
     * - `type` - The formatter type to construct, set it to `currency` if you need to format
     *    numbers representing money or a NumberFormatter constant.
     * - `places` - Number of decimal places to use. e.g. 2
     * - `precision` - Maximum Number of decimal places to use, e.g. 2
     * - `pattern` - An ICU number pattern to use for formatting the number. e.g #,##0.00
     * - `useIntlCode` - Whether to replace the currency symbol with the international
     *   currency code.
     *
     * @param array<string, mixed> $options An array with options.
     * @return \NumberFormatter The configured formatter instance
     */
    public static function formatter(array $options = []): NumberFormatter
    {
        $locale = $options['locale'] ?? ini_get('intl.default_locale');

        if (!$locale) {
            $locale = static::DEFAULT_LOCALE;
        }

        $type = NumberFormatter::DECIMAL;
        if (!empty($options['type'])) {
            $type = $options['type'];
            if ($options['type'] === static::FORMAT_CURRENCY) {
                $type = NumberFormatter::CURRENCY;
            } elseif ($options['type'] === static::FORMAT_CURRENCY_ACCOUNTING) {
                if (defined('NumberFormatter::CURRENCY_ACCOUNTING')) {
                    $type = NumberFormatter::CURRENCY_ACCOUNTING;
                } else {
                    $type = static::CURRENCY_ACCOUNTING;
                }
            }
        }

        if (!isset(static::$_formatters[$locale][$type])) {
            static::$_formatters[$locale][$type] = new NumberFormatter($locale, $type);
        }

        /** @var \NumberFormatter $formatter */
        $formatter = static::$_formatters[$locale][$type];

        // PHP 8.0.0 - 8.0.6 throws an exception when cloning NumberFormatter after a failed parse
        if (version_compare(PHP_VERSION, '8.0.6', '>') || version_compare(PHP_VERSION, '8.0.0', '<')) {
            $options = array_intersect_key($options, [
                'places' => null,
                'precision' => null,
                'pattern' => null,
                'useIntlCode' => null,
            ]);
            if (empty($options)) {
                return $formatter;
            }
        }

        $formatter = clone $formatter;

        return static::_setAttributes($formatter, $options);
    }

    /**
     * Configure formatters.
     *
     * @param string $locale The locale name to use for formatting the number, e.g. fr_FR
     * @param int $type The formatter type to construct. Defaults to NumberFormatter::DECIMAL.
     * @param array<string, mixed> $options See Number::formatter() for possible options.
     * @return void
     */
    public static function config(string $locale, int $type = NumberFormatter::DECIMAL, array $options = []): void
    {
        static::$_formatters[$locale][$type] = static::_setAttributes(
            new NumberFormatter($locale, $type),
            $options
        );
    }

    /**
     * Set formatter attributes
     *
     * @param \NumberFormatter $formatter Number formatter instance.
     * @param array<string, mixed> $options See Number::formatter() for possible options.
     * @return \NumberFormatter
     */
    protected static function _setAttributes(NumberFormatter $formatter, array $options = []): NumberFormatter
    {
        if (isset($options['places'])) {
            $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $options['places']);
        }

        if (isset($options['precision'])) {
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $options['precision']);
        }

        if (!empty($options['pattern'])) {
            $formatter->setPattern($options['pattern']);
        }

        if (!empty($options['useIntlCode'])) {
            // One of the odd things about ICU is that the currency marker in patterns
            // is denoted with ¤, whereas the international code is marked with ¤¤,
            // in order to use the code we need to simply duplicate the character wherever
            // it appears in the pattern.
            $pattern = trim(str_replace('¤', '¤¤ ', $formatter->getPattern()));
            $formatter->setPattern($pattern);
        }

        return $formatter;
    }

    /**
     * Returns a formatted integer as an ordinal number string (e.g. 1st, 2nd, 3rd, 4th, [...])
     *
     * ### Options
     *
     * - `type` - The formatter type to construct, set it to `currency` if you need to format
     *    numbers representing money or a NumberFormatter constant.
     *
     * For all other options see formatter().
     *
     * @param float|int $value An integer
     * @param array<string, mixed> $options An array with options.
     * @return string
     */
    public static function ordinal($value, array $options = []): string
    {
        return static::formatter(['type' => NumberFormatter::ORDINAL] + $options)->format($value);
    }
}
