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
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use NumberFormatter;

/**
 * Number helper library.
 *
 * Methods to make numbers more readable.
 *
 * @link https://book.cakephp.org/3/en/core-libraries/number.html
 */
class Number
{
    /**
     * Default locale
     *
     * @var string
     */
    const DEFAULT_LOCALE = 'en_US';

    /**
     * Format type to format as currency
     *
     * @var string
     */
    const FORMAT_CURRENCY = 'currency';

    /**
     * A list of number formatters indexed by locale and type
     *
     * @var array
     */
    protected static $_formatters = [];

    /**
     * Default currency used by Number::currency()
     *
     * @var string|null
     */
    protected static $_defaultCurrency;

    /**
     * Formats a number with a level of precision.
     *
     * Options:
     *
     * - `locale`: The locale name to use for formatting the number, e.g. fr_FR
     *
     * @param float $value A floating point number.
     * @param int $precision The precision of the returned number.
     * @param array $options Additional options
     * @return string Formatted float.
     * @link https://book.cakephp.org/3/en/core-libraries/number.html#formatting-floating-point-numbers
     */
    public static function precision($value, $precision = 3, array $options = [])
    {
        $formatter = static::formatter(['precision' => $precision, 'places' => $precision] + $options);

        return $formatter->format($value);
    }

    /**
     * Returns a formatted-for-humans file size.
     *
     * @param int $size Size in bytes
     * @return string Human readable size
     * @link https://book.cakephp.org/3/en/core-libraries/number.html#interacting-with-human-readable-values
     */
    public static function toReadableSize($size)
    {
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
     * @param float $value A floating point number
     * @param int $precision The precision of the returned number
     * @param array $options Options
     * @return string Percentage string
     * @link https://book.cakephp.org/3/en/core-libraries/number.html#formatting-percentages
     */
    public static function toPercentage($value, $precision = 2, array $options = [])
    {
        $options += ['multiply' => false, 'type' => NumberFormatter::PERCENT];
        if (!$options['multiply']) {
            $value /= 100;
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
     * @param float $value A floating point number.
     * @param array $options An array with options.
     * @return string Formatted number
     */
    public static function format($value, array $options = [])
    {
        $formatter = static::formatter($options);
        $options += ['before' => '', 'after' => ''];

        return $options['before'] . $formatter->format($value) . $options['after'];
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
     * @param array $options An array with options.
     * @return float point number
     */
    public static function parseFloat($value, array $options = [])
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
     * @param float $value A floating point number
     * @param array $options Options list.
     * @return string formatted delta
     */
    public static function formatDelta($value, array $options = [])
    {
        $options += ['places' => 0];
        $value = number_format($value, $options['places'], '.', '');
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
     * - `useIntlCode` - Whether or not to replace the currency symbol with the international
     *   currency code.
     *
     * @param float $value Value to format.
     * @param string|null $currency International currency name such as 'USD', 'EUR', 'JPY', 'CAD'
     * @param array $options Options list.
     * @return string Number formatted as a currency.
     */
    public static function currency($value, $currency = null, array $options = [])
    {
        $value = (float)$value;
        $currency = $currency ?: static::defaultCurrency();

        if (isset($options['zero']) && !$value) {
            return $options['zero'];
        }

        $formatter = static::formatter(['type' => static::FORMAT_CURRENCY] + $options);
        $abs = abs($value);
        if (!empty($options['fractionSymbol']) && $abs > 0 && $abs < 1) {
            $value *= 100;
            $pos = isset($options['fractionPosition']) ? $options['fractionPosition'] : 'after';

            return static::format($value, ['precision' => 0, $pos => $options['fractionSymbol']]);
        }

        $before = isset($options['before']) ? $options['before'] : null;
        $after = isset($options['after']) ? $options['after'] : null;

        return $before . $formatter->formatCurrency($value, $currency) . $after;
    }

    /**
     * Getter/setter for default currency
     *
     * @param string|bool|null $currency Default currency string to be used by currency()
     * if $currency argument is not provided. If boolean false is passed, it will clear the
     * currently stored value
     * @return string|null Currency
     */
    public static function defaultCurrency($currency = null)
    {
        if (!empty($currency)) {
            return self::$_defaultCurrency = $currency;
        }

        if ($currency === false) {
            return self::$_defaultCurrency = null;
        }

        if (empty(self::$_defaultCurrency)) {
            $locale = ini_get('intl.default_locale') ?: static::DEFAULT_LOCALE;
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            self::$_defaultCurrency = $formatter->getTextAttribute(NumberFormatter::CURRENCY_CODE);
        }

        return self::$_defaultCurrency;
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
     * - `useIntlCode` - Whether or not to replace the currency symbol with the international
     *   currency code.
     *
     * @param array $options An array with options.
     * @return \NumberFormatter The configured formatter instance
     */
    public static function formatter($options = [])
    {
        $locale = isset($options['locale']) ? $options['locale'] : ini_get('intl.default_locale');

        if (!$locale) {
            $locale = static::DEFAULT_LOCALE;
        }

        $type = NumberFormatter::DECIMAL;
        if (!empty($options['type'])) {
            $type = $options['type'];
            if ($options['type'] === static::FORMAT_CURRENCY) {
                $type = NumberFormatter::CURRENCY;
            }
        }

        if (!isset(static::$_formatters[$locale][$type])) {
            static::$_formatters[$locale][$type] = new NumberFormatter($locale, $type);
        }

        $formatter = static::$_formatters[$locale][$type];

        $options = array_intersect_key($options, [
            'places' => null,
            'precision' => null,
            'pattern' => null,
            'useIntlCode' => null
        ]);
        if (empty($options)) {
            return $formatter;
        }

        $formatter = clone $formatter;

        return static::_setAttributes($formatter, $options);
    }

    /**
     * Configure formatters.
     *
     * @param string $locale The locale name to use for formatting the number, e.g. fr_FR
     * @param int $type The formatter type to construct. Defaults to NumberFormatter::DECIMAL.
     * @param array $options See Number::formatter() for possible options.
     * @return void
     */
    public static function config($locale, $type = NumberFormatter::DECIMAL, array $options = [])
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
     * @param array $options See Number::formatter() for possible options.
     * @return \NumberFormatter
     */
    protected static function _setAttributes(NumberFormatter $formatter, array $options = [])
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
     * @param int|float $value An integer
     * @param array $options An array with options.
     * @return string
     */
    public static function ordinal($value, array $options = [])
    {
        return static::formatter(['type' => NumberFormatter::ORDINAL] + $options)->format($value);
    }
}
