<?php
/**
 * Cake Number Utility.
 *
 * Methods to make numbers more readable.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Utility
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Utility;

use Cake\Error;

/**
 * Number helper library.
 *
 * Methods to make numbers more readable.
 *
 * @package       Cake.Utility
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html
 */
class Number {

/**
 * Currencies supported by the helper.  You can add additional currency formats
 * with Cake\Utility\Number::addFormat
 *
 * @var array
 */
	protected static $_currencies = array(
		'USD' => array(
			'wholeSymbol' => '$', 'wholePosition' => 'before', 'fractionSymbol' => 'c', 'fractionPosition' => 'after',
			'zero' => 0, 'places' => 2, 'thousands' => ',', 'decimals' => '.', 'negative' => '()', 'escape' => true
		),
		'GBP' => array(
			'wholeSymbol' => '&#163;', 'wholePosition' => 'before', 'fractionSymbol' => 'p', 'fractionPosition' => 'after',
			'zero' => 0, 'places' => 2, 'thousands' => ',', 'decimals' => '.', 'negative' => '()','escape' => false
		),
		'EUR' => array(
			'wholeSymbol' => '&#8364;', 'wholePosition' => 'before', 'fractionSymbol' => false, 'fractionPosition' => 'after',
			'zero' => 0, 'places' => 2, 'thousands' => '.', 'decimals' => ',', 'negative' => '()', 'escape' => false
		)
	);

/**
 * Default options for currency formats
 *
 * @var array
 */
	protected static $_currencyDefaults = array(
		'wholeSymbol' => '', 'wholePosition' => 'before', 'fractionSymbol' => '', 'fractionPosition' => 'after',
		'zero' => '0', 'places' => 2, 'thousands' => ',', 'decimals' => '.','negative' => '()', 'escape' => true,
	);

/**
 * Default currency used by CakeNumber::currency()
 *
 * @var string
 */
	protected static $_defaultCurrency = 'USD';

/**
 * If native number_format() should be used. If >= PHP5.4
 *
 * @var boolean
 */
	protected static $_numberFormatSupport = null;

/**
 * Formats a number with a level of precision.
 *
 * @param float $value A floating point number.
 * @param integer $precision The precision of the returned number.
 * @return float Formatted float.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::precision
 */
	public static function precision($value, $precision = 3) {
		return sprintf("%01.{$precision}F", $value);
	}

/**
 * Returns a formatted-for-humans file size.
 *
 * @param integer $size Size in bytes
 * @return string Human readable size
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::toReadableSize
 */
	public static function toReadableSize($size) {
		switch (true) {
			case $size < 1024:
				return __dn('cake', '%d Byte', '%d Bytes', $size, $size);
			case round($size / 1024) < 1024:
				return __d('cake', '%d KB', static::precision($size / 1024, 0));
			case round($size / 1024 / 1024, 2) < 1024:
				return __d('cake', '%.2f MB', static::precision($size / 1024 / 1024, 2));
			case round($size / 1024 / 1024 / 1024, 2) < 1024:
				return __d('cake', '%.2f GB', static::precision($size / 1024 / 1024 / 1024, 2));
			default:
				return __d('cake', '%.2f TB', static::precision($size / 1024 / 1024 / 1024 / 1024, 2));
		}
	}

/**
 * Converts filesize from human readable string to bytes
 *
 * @param string $size Size in human readable string like '5MB'
 * @param mixed $default Value to be returned when invalid size was used, for example 'Unknown type'
 * @return integer Bytes
 * @throws Cake\Error\Exception On invalid Unit type.
 */
	public static function fromReadableSize($size, $default = false) {
		if (ctype_digit($size)) {
			return $size * 1;
		}
		$size = strtoupper($size);

		$i = array_search(substr($size, -2), array('KB', 'MB', 'GB', 'TB', 'PB'));
		if ($i !== false) {
			$size = substr($size, 0, strlen($size) - 2);
			return $size * pow(1024, $i + 1);
		}

		if (substr($size, -1) == 'B' && ctype_digit(substr($size, 0, strlen($size) - 1))) {
			$size = substr($size, 0, strlen($size) - 1);
			return $size * 1;
		}

		if ($default !== false) {
			return $default;
		}
		throw new Error\Exception(__d('cake_dev', 'No unit type.'));
	}

/**
 * Formats a number into a percentage string.
 *
 * @param float $value A floating point number
 * @param integer $precision The precision of the returned number
 * @return string Percentage string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::toPercentage
 */
	public static function toPercentage($value, $precision = 2) {
		return static::precision($value, $precision) . '%';
	}

/**
 * Formats a number into a currency format.
 *
 * @param float $value A floating point number
 * @param integer $options if int then places, if string then before, if (,.-) then use it
 *   or array with places and before keys
 * @return string formatted number
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::format
 */
	public static function format($value, $options = false) {
		$places = 0;
		if (is_int($options)) {
			$places = $options;
		}

		$separators = array(',', '.', '-', ':');

		$before = $after = null;
		if (is_string($options) && !in_array($options, $separators)) {
			$before = $options;
		}
		$thousands = ',';
		if (!is_array($options) && in_array($options, $separators)) {
			$thousands = $options;
		}
		$decimals = '.';
		if (!is_array($options) && in_array($options, $separators)) {
			$decimals = $options;
		}

		$escape = true;
		if (is_array($options)) {
			$options = array_merge(array('before' => '$', 'places' => 2, 'thousands' => ',', 'decimals' => '.'), $options);
			extract($options);
		}

		$out = $before . number_format($value, $places, $decimals, $thousands) . $after;

		if ($escape) {
			return h($out);
		}
		return $out;
	}

/**
 * Formats a number into a currency format to show deltas (signed differences in value).
 *
 * ### Options
 *
 * - `places` - Number of decimal places to use. ie. 2
 * - `before` - The string to place before whole numbers. ie. '['
 * - `after` - The string to place after decimal numbers. ie. ']'
 * - `thousands` - Thousands separator ie. ','
 * - `decimals` - Decimal separator symbol ie. '.'
 *
 * @param float $value A floating point number
 * @param array $options
 * @return string formatted delta
 */
	public static function formatDelta($value, $options = array()) {
		$places = isset($options['places']) ? $options['places'] : 0;
		$value = number_format($value, $places, '.', '');
		$sign = $value > 0 ? '+' : '';
		$options['before'] = isset($options['before']) ? $options['before'] . $sign : $sign;
		return static::format($value, $options);
	}

/**
 * Formats a number into a currency format.
 *
 * ### Options
 *
 * - `wholeSymbol` - The currency symbol to use for whole numbers,
 *   greater than 1, or less than -1.
 * - `wholePosition` - The position the whole symbol should be placed
 *   valid options are 'before' & 'after'.
 * - `fractionSymbol` - The currency symbol to use for fractional numbers.
 * - `fractionPosition` - The position the fraction symbol should be placed
 *   valid options are 'before' & 'after'.
 * - `before` - The currency symbol to place before whole numbers
 *   ie. '$'. `before` is an alias for `wholeSymbol`.
 * - `after` - The currency symbol to place after decimal numbers
 *   ie. 'c'. Set to boolean false to use no decimal symbol.
 *   eg. 0.35 => $0.35.  `after` is an alias for `fractionSymbol`
 * - `zero` - The text to use for zero values, can be a
 *   string or a number. ie. 0, 'Free!'
 * - `places` - Number of decimal places to use. ie. 2
 * - `thousands` - Thousands separator ie. ','
 * - `decimals` - Decimal separator symbol ie. '.'
 * - `negative` - Symbol for negative numbers. If equal to '()',
 *   the number will be wrapped with ( and )
 * - `escape` - Should the output be escaped for html special characters.
 *   The default value for this option is controlled by the currency settings.
 *   By default the EUR, and GBP contain HTML encoded symbols. If you require non HTML
 *   encoded symbols you will need to update the settings with the correct bytes.
 *
 * @param float $value
 * @param string $currency Shortcut to default options. Valid values are
 *   'USD', 'EUR', 'GBP', otherwise set at least 'before' and 'after' options.
 * @param array $options
 * @return string Number formatted as a currency.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::currency
 */
	public static function currency($value, $currency = null, $options = array()) {
		$default = static::$_currencyDefaults;
		if ($currency === null) {
			$currency = static::defaultCurrency();
		}

		if (isset(static::$_currencies[$currency])) {
			$default = static::$_currencies[$currency];
		} elseif (is_string($currency)) {
			$options['before'] = $currency;
		}

		$options = array_merge($default, $options);

		if (isset($options['before']) && $options['before'] !== '') {
			$options['wholeSymbol'] = $options['before'];
		}
		if (isset($options['after']) && !$options['after'] !== '') {
			$options['fractionSymbol'] = $options['after'];
		}

		$result = $options['before'] = $options['after'] = null;

		$symbolKey = 'whole';
		if (!$value) {
			if ($options['zero'] !== 0 ) {
				return $options['zero'];
			}
		} elseif ($value < 1 && $value > -1) {
			if ($options['fractionSymbol'] !== false) {
				$multiply = intval('1' . str_pad('', $options['places'], '0'));
				$value = $value * $multiply;
				$options['places'] = null;
				$symbolKey = 'fraction';
			}
		}

		$position = $options[$symbolKey . 'Position'] != 'after' ? 'before' : 'after';
		$options[$position] = $options[$symbolKey . 'Symbol'];

		$abs = abs($value);
		$result = static::format($abs, $options);

		if ($value < 0) {
			if ($options['negative'] == '()') {
				$result = '(' . $result . ')';
			} else {
				$result = $options['negative'] . $result;
			}
		}
		return $result;
	}

/**
 * Add a currency format to the Number helper.  Makes reusing
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
	public static function addFormat($formatName, $options) {
		static::$_currencies[$formatName] = $options + static::$_currencyDefaults;
	}

/**
 * Getter/setter for default currency
 *
 * @param string $currency Default currency string  used by currency() if $currency argument is not provided
 * @return string Currency
 */
	public static function defaultCurrency($currency = null) {
		if ($currency) {
			self::$_defaultCurrency = $currency;
		}
		return self::$_defaultCurrency;
	}

}
