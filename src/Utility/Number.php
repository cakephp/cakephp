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
 * Currencies supported by the helper. You can add additional currency formats
 * with Cake\Utility\Number::addFormat
 *
 * @var array
 */
	protected static $_currencies = array(
		'AUD' => array(
			'wholeSymbol' => '$', 'wholePosition' => 'before', 'fractionSymbol' => 'c', 'fractionPosition' => 'after',
			'zero' => 0, 'places' => 2, 'thousands' => ',', 'decimals' => '.', 'negative' => '()', 'escape' => true,
			'fractionExponent' => 2
		),
		'CAD' => array(
			'wholeSymbol' => '$', 'wholePosition' => 'before', 'fractionSymbol' => 'c', 'fractionPosition' => 'after',
			'zero' => 0, 'places' => 2, 'thousands' => ',', 'decimals' => '.', 'negative' => '()', 'escape' => true,
			'fractionExponent' => 2
		),
		'USD' => array(
			'wholeSymbol' => '$', 'wholePosition' => 'before', 'fractionSymbol' => 'c', 'fractionPosition' => 'after',
			'zero' => 0, 'places' => 2, 'thousands' => ',', 'decimals' => '.', 'negative' => '()', 'escape' => true,
			'fractionExponent' => 2
		),
		'EUR' => array(
			'wholeSymbol' => '€', 'wholePosition' => 'before', 'fractionSymbol' => false, 'fractionPosition' => 'after',
			'zero' => 0, 'places' => 2, 'thousands' => '.', 'decimals' => ',', 'negative' => '()', 'escape' => true,
			'fractionExponent' => 0
		),
		'GBP' => array(
			'wholeSymbol' => '£', 'wholePosition' => 'before', 'fractionSymbol' => 'p', 'fractionPosition' => 'after',
			'zero' => 0, 'places' => 2, 'thousands' => ',', 'decimals' => '.', 'negative' => '()', 'escape' => true,
			'fractionExponent' => 2
		),
		'JPY' => array(
			'wholeSymbol' => '¥', 'wholePosition' => 'before', 'fractionSymbol' => false, 'fractionPosition' => 'after',
			'zero' => 0, 'places' => 2, 'thousands' => ',', 'decimals' => '.', 'negative' => '()', 'escape' => true,
			'fractionExponent' => 0
		),
	);

/**
 * A list of number formatters indexed by locale
 *
 * @var array
 */
	protected static $_formatters = [];

/**
 * A list of currency formatters indexed by locale
 *
 * @var array
 */
	protected static $_currencyFormatters = [];

/**
 * Default currency used by Number::currency()
 *
 * @var string
 */
	protected static $_defaultCurrency = 'USD';

/**
 * Formats a number with a level of precision.
 *
 * @param float $value A floating point number.
 * @param int $precision The precision of the returned number.
 * @return float Formatted float.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::precision
 */
	public static function precision($value, $precision = 3) {
		$locale = ini_get('intl.default_locale') ?: 'en_US';
		if (!isset(static::$_formatters[$locale])) {
			static::$_formatters[$locale] = new NumberFormatter($locale, NumberFormatter::DECIMAL);
		}
		$formatter = static::$_formatters[$locale];
		$formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $precision);
		$formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $precision);
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
 * - `locale` - The locale name to use for formatting the number, e.g. fr_FR
 * - `before` - The string to place before whole numbers, e.g. '['
 * - `after` - The string to place after decimal numbers, e.g. ']'
 * - `escape` - Set to false to prevent escaping
 *
 * @param float $value A floating point number.
 * @param array $options An array with options.
 * @return string Formatted number
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::format
 */
	public static function format($value, array $options = []) {
		$locale = isset($options['locale']) ? $options['locale'] : ini_get('intl.default_locale');

		if (!$locale) {
			$locale = 'en_US';
		}

		if (!isset(static::$_formatters[$locale])) {
			static::$_formatters[$locale] = new NumberFormatter($locale, NumberFormatter::DECIMAL);
		}

		$formatter = static::$_formatters[$locale];
		$map = [
			'places' => NumberFormatter::MIN_FRACTION_DIGITS,
			'precision' => NumberFormatter::MAX_FRACTION_DIGITS
		];

		foreach ($map as $opt => $setting) {
			if (isset($options[$opt])) {
				$formatter->setAttribute($setting, $options[$opt]);
			}
		}

		$options += ['before' => '', 'after' => '', 'escape' => true];
		$out = $options['before'] . $formatter->format($value) . $options['after'];

		if (!empty($options['escape'])) {
			return h($out);
		}

		return $out;
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
 * - `escape` - Set to false to prevent escaping
 *
 * @param float $value A floating point number
 * @param array $options Options list.
 * @return string formatted delta
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::formatDelta
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
 * - `fractionSymbol` - The currency symbol to use for fractional numbers.
 * - `fractionPosition` - The position the fraction symbol should be placed
 *   valid options are 'before' & 'after'.
 * - `before` - The currency symbol to place before whole numbers
 *   ie. '$'. `before` is an alias for `wholeSymbol`.
 * - `after` - The currency symbol to place after decimal numbers
 *   ie. 'c'. Set to boolean false to use no decimal symbol.
 *   eg. 0.35 => $0.35. `after` is an alias for `fractionSymbol`
 * - `zero` - The text to use for zero values, can be a
 *   string or a number. ie. 0, 'Free!'
 * - `places` - Number of decimal places to use. ie. 2
 * - `fractionExponent` - Fraction exponent of this specific currency. Defaults to 2.
 * - `thousands` - Thousands separator ie. ','
 * - `decimals` - Decimal separator symbol ie. '.'
 * - `negative` - Symbol for negative numbers. If equal to '()',
 *   the number will be wrapped with ( and )
 * - `escape` - Should the output be escaped for html special characters.
 *   The default value for this option is controlled by the currency settings.
 *   By default all currencies contain utf-8 symbols and don't need this changed. If you require
 *   non HTML encoded symbols you will need to update the settings with the correct bytes.
 *
 * @param float $value Value to format.
 * @param string $currency Shortcut to default options. Valid values are
 *   'USD', 'EUR', 'GBP', otherwise set at least 'before' and 'after' options.
 * @param array $options Options list.
 * @return string Number formatted as a currency.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::currency
 */
	public static function currency($value, $currency = null, array $options = array()) {
		$value = (float)$value;
		$currency = $currency ?: static::defaultCurrency();

		if (isset($options['zero']) && !$value) {
			return $options['zero'];
		}

		$locale = isset($options['locale']) ? $options['locale'] : ini_get('intl.default_locale');

		if (!$locale) {
			$locale = 'en_US';
		}

		if (!isset(static::$_currencyFormatters[$locale])) {
			static::$_currencyFormatters[$locale] = new NumberFormatter(
				$locale,
				NumberFormatter::CURRENCY
			);
		}

		$formatter = static::$_currencyFormatters[$locale];

		if (isset($options['places'])) {
			$formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $options['places']);
		}

		if (isset($options['precision'])) {
			$formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $options['precision']);
		}

		if (!empty($options['pattern'])) {
			$formatter->setPattern($options['pattern']);
		}

		if (!empty($options['fractionSymbol']) && $value > 0 && $value < 1) {
			$value = $value * 100;
			$pos = isset($options['fractionPosition']) ? $options['fractionPosition'] : 'after';
			return static::format($value, ['precision' => 0, $pos => $options['fractionSymbol']]);
		}

		$before = isset($options['before']) ? $options['before'] : null;
		$after = isset($options['after']) ? $options['after'] : null;
		return $before . $formatter->formatCurrency($value, $currency) . $after;
	}

/**
 * Add a currency format to the Number helper. Makes reusing
 * currency formats easier.
 *
 * {{{ $number->addFormat('NOK', array('before' => 'Kr. ')); }}}
 *
 * You can now use `NOK` as a shortform when formatting currency amounts.
 *
 * {{{ $number->currency($value, 'NOK'); }}}
 *
 * Added formats are merged with the defaults defined in Cake\Utility\Number::$_currencyDefaults
 * See Cake\Utility\Number::currency() for more information on the various options and their function.
 *
 * @param string $formatName The format name to be used in the future.
 * @param array $options The array of options for this format.
 * @return void
 * @see NumberHelper::currency()
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::addFormat
 */
	public static function addFormat($formatName, array $options) {
		static::$_currencies[$formatName] = $options + static::$_currencyDefaults;
	}

/**
 * Getter/setter for default currency
 *
 * @param string $currency Default currency string used by currency() if $currency argument is not provided
 * @return string Currency
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::defaultCurrency
 */
	public static function defaultCurrency($currency = null) {
		if ($currency) {
			self::$_defaultCurrency = $currency;
		}
		return self::$_defaultCurrency;
	}

}
