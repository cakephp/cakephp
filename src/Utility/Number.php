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
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility;

use Cake\Error\Exception;
use NumberFormatter;

/**
 * Number helper library.
 *
 * Methods to make numbers more readable.
 *
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html
 */
class Number {

/**
 * A list of number formatters indexed by locale and type
 *
 * @var array
 */
	protected static $_formatters = [];

/**
 * Default currency used by Number::currency()
 *
 * @var string
 */
	protected static $_defaultCurrency;

/**
 * Formats a number with a level of precision.
 *
 * @param float $value A floating point number.
 * @param int $precision The precision of the returned number.
 * @return float Formatted float.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::precision
 */
	public static function precision($value, $precision = 3) {
		$formatter = static::formatter(['precision' => $precision, 'places' => $precision]);
		return $formatter->format($value);
	}

/**
 * Returns a formatted-for-humans file size.
 *
 * @param int $size Size in bytes
 * @return string Human readable size
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::toReadableSize
 */
	public static function toReadableSize($size) {
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
 * Converts filesize from human readable string to bytes
 *
 * @param string $size Size in human readable string like '5MB', '5M', '500B', '50kb' etc.
 * @param mixed $default Value to be returned when invalid size was used, for example 'Unknown type'
 * @return mixed Number of bytes as integer on success, `$default` on failure if not false
 * @throws \Cake\Error\Exception On invalid Unit type.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::fromReadableSize
 */
	public static function fromReadableSize($size, $default = false) {
		if (ctype_digit($size)) {
			return (int)$size;
		}
		$size = strtoupper($size);

		$l = -2;
		$i = array_search(substr($size, -2), array('KB', 'MB', 'GB', 'TB', 'PB'));
		if ($i === false) {
			$l = -1;
			$i = array_search(substr($size, -1), array('K', 'M', 'G', 'T', 'P'));
		}
		if ($i !== false) {
			$size = substr($size, 0, $l);
			return $size * pow(1024, $i + 1);
		}

		if (substr($size, -1) === 'B' && ctype_digit(substr($size, 0, -1))) {
			$size = substr($size, 0, -1);
			return (int)$size;
		}

		if ($default !== false) {
			return $default;
		}
		throw new Exception('No unit type.');
	}

/**
 * Formats a number into a percentage string.
 *
 * Options:
 *
 * - `multiply`: Multiply the input value by 100 for decimal percentages.
 *
 * @param float $value A floating point number
 * @param int $precision The precision of the returned number
 * @param array $options Options
 * @return string Percentage string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::toPercentage
 */
	public static function toPercentage($value, $precision = 2, array $options = array()) {
		$options += array('multiply' => false);
		if ($options['multiply']) {
			$value *= 100;
		}
		return static::precision($value, $precision) . '%';
	}

/**
 * Formats a number into the correct locale format
 *
 * Options:
 *
 * - `places` - Minimim number or decimals to use, e.g 0
 * - `precision` - Maximum Number of decimal places to use, e.g. 2
 * - `pattern` - An ICU number pattern to use for formatting the number. e.g #,###.00
 * - `locale` - The locale name to use for formatting the number, e.g. fr_FR
 * - `before` - The string to place before whole numbers, e.g. '['
 * - `after` - The string to place after decimal numbers, e.g. ']'
 *
 * @param float $value A floating point number.
 * @param array $options An array with options.
 * @return string Formatted number
 */
	public static function format($value, array $options = []) {
		$formatter = static::formatter($options);
		$options += ['before' => '', 'after' => ''];
		return $options['before'] . $formatter->format($value) . $options['after'];
	}

/**
 * Formats a number into the correct locale format to show deltas (signed differences in value).
 *
 * ### Options
 *
 * - `places` - Minimim number or decimals to use, e.g 0
 * - `precision` - Maximum Number of decimal places to use, e.g. 2
 * - `locale` - The locale name to use for formatting the number, e.g. fr_FR
 * - `before` - The string to place before whole numbers, e.g. '['
 * - `after` - The string to place after decimal numbers, e.g. ']'
 *
 * @param float $value A floating point number
 * @param array $options Options list.
 * @return string formatted delta
 */
	public static function formatDelta($value, array $options = array()) {
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
 * - `pattern` - An ICU number pattern to use for formatting the number. e.g #,###.00
 * - `useIntlCode` - Whether or not to replace the currency symbol with the international
 *   currency code.
 *
 * @param float $value Value to format.
 * @param string $currency International currency name such as 'USD', 'EUR', 'JPY', 'CAD'
 * @param array $options Options list.
 * @return string Number formatted as a currency.
 */
	public static function currency($value, $currency = null, array $options = array()) {
		$value = (float)$value;
		$currency = $currency ?: static::defaultCurrency();

		if (isset($options['zero']) && !$value) {
			return $options['zero'];
		}

		$formatter = static::formatter(['type' => 'currency'] + $options);
		$abs = abs($value);
		if (!empty($options['fractionSymbol']) && $abs > 0 && $abs < 1) {
			$value = $value * 100;
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
 * @param string|boolean $currency Default currency string to be used by currency()
 * if $currency argument is not provided. If boolean false is passed, it will clear the
 * currently stored value
 * @return string Currency
 */
	public static function defaultCurrency($currency = null) {
		if (!empty($currency)) {
			return self::$_defaultCurrency = $currency;
		}

		if ($currency === false) {
			return self::$_defaultCurrency = null;
		}

		if (empty(self::$_defaultCurrency)) {
			$locale = ini_get('intl.default_locale') ?: 'en_US';
			$formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
			self::$_defaultCurrency = $formatter->getTextAttribute(NumberFormatter::CURRENCY_CODE);
		}

		return self::$_defaultCurrency;
	}

/**
 * Returns a formatter object that can be reused for similar formatting task
 * under the same locale and options. This is often a speedier alternative to
 * using other methods in this class as on;y one formatter object needs to be
 * constructed.
 *
 * The options array accepts the following keys:
 *
 * - `locale` - The locale name to use for formatting the number, e.g. fr_FR
 * - `type` - The formatter type to construct, set it to `curency` if you need to format
 *    numbers representing money.
 * - `places` - Number of decimal places to use. e.g. 2
 * - `precision` - Maximum Number of decimal places to use, e.g. 2
 * - `pattern` - An ICU number pattern to use for formatting the number. e.g #,###.00
 * - `useIntlCode` - Whether or not to replace the currency symbol with the international
 *   currency code.
 *
 * @param array $options An array with options.
 * @return \NumberFormatter The configured formatter instance
 */
	public static function formatter($options = []) {
		$locale = isset($options['locale']) ? $options['locale'] : ini_get('intl.default_locale');

		if (!$locale) {
			$locale = 'en_US';
		}

		$type = NumberFormatter::DECIMAL;
		if (!empty($options['type']) && $options['type'] === 'currency') {
			$type = NumberFormatter::CURRENCY;
		}

		if (!isset(static::$_formatters[$locale][$type])) {
			static::$_formatters[$locale][$type] = new NumberFormatter($locale, $type);
		}

		$formatter = static::$_formatters[$locale][$type];
		$hasPlaces = isset($options['places']);
		$hasPrecision = isset($options['precision']);
		$hasPattern = !empty($options['pattern']) || !empty($options['useIntlCode']);

		if ($hasPlaces || $hasPrecision || $hasPattern) {
			$formatter = clone $formatter;
		}

		if ($hasPlaces) {
			$formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $options['places']);
		}

		if ($hasPrecision) {
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

}
