<?php
/**
 * CakeNumber Utility.
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

/**
 * Number helper library.
 *
 * Methods to make numbers more readable.
 *
 * @package       Cake.Utility
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html
 */
class CakeNumber {

/**
 * Currencies supported by the helper.  You can add additional currency formats
 * with CakeNumber::addFormat
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
 * If native number_format() should be used. If >= PHP5.4
 *
 * @var boolean
 */
	protected static $_numberFormatSupport = null;

/**
 * Formats a number with a level of precision.
 *
 * @param float $number A floating point number.
 * @param integer $precision The precision of the returned number.
 * @return float Formatted float.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::precision
 */
	public static function precision($number, $precision = 3) {
		return sprintf("%01.{$precision}F", $number);
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
				return __d('cake', '%d KB', self::precision($size / 1024, 0));
			case round($size / 1024 / 1024, 2) < 1024:
				return __d('cake', '%.2f MB', self::precision($size / 1024 / 1024, 2));
			case round($size / 1024 / 1024 / 1024, 2) < 1024:
				return __d('cake', '%.2f GB', self::precision($size / 1024 / 1024 / 1024, 2));
			default:
				return __d('cake', '%.2f TB', self::precision($size / 1024 / 1024 / 1024 / 1024, 2));
		}
	}

/**
 * Formats a number into a percentage string.
 *
 * @param float $number A floating point number
 * @param integer $precision The precision of the returned number
 * @return string Percentage string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::toPercentage
 */
	public static function toPercentage($number, $precision = 2) {
		return self::precision($number, $precision) . '%';
	}

/**
 * Formats a number into a currency format.
 *
 * @param float $number A floating point number
 * @param integer $options if int then places, if string then before, if (,.-) then use it
 *   or array with places and before keys
 * @return string formatted number
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::format
 */
	public static function format($number, $options = false) {
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

		$out = $before . self::_numberFormat($number, $places, $decimals, $thousands) . $after;

		if ($escape) {
			return h($out);
		}
		return $out;
	}

/**
 * Alternative number_format() to accommodate multibyte decimals and thousands < PHP 5.4
 *
 * @param float $number
 * @param integer $places
 * @param string $decimals
 * @param string $thousands
 * @return string
 */
	protected static function _numberFormat($number, $places = 0, $decimals = '.', $thousands = ',') {
		if (!isset(self::$_numberFormatSupport)) {
			self::$_numberFormatSupport = version_compare(PHP_VERSION, '5.4.0', '>=');
		}
		if (self::$_numberFormatSupport) {
			return number_format($number, $places, $decimals, $thousands);
		}
		$number = number_format($number, $places, '.', '');
		$after = '';
		$foundDecimal = strpos($number, '.');
		if ($foundDecimal !== false) {
			$after = substr($number, $foundDecimal);
			$number = substr($number, 0, $foundDecimal);
		}
		while (($foundThousand = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $number)) != $number) {
			$number = $foundThousand;
		}
		$number .= $after;
		return strtr($number, array(' ' => $thousands, '.' => $decimals));
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
 * - `escape` - Should the output be htmlentity escaped? Defaults to true
 *
 * @param float $number
 * @param string $currency Shortcut to default options. Valid values are
 *   'USD', 'EUR', 'GBP', otherwise set at least 'before' and 'after' options.
 * @param array $options
 * @return string Number formatted as a currency.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::currency
 */
	public static function currency($number, $currency = 'USD', $options = array()) {
		$default = self::$_currencyDefaults;

		if (isset(self::$_currencies[$currency])) {
			$default = self::$_currencies[$currency];
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
		if ($number == 0 ) {
			if ($options['zero'] !== 0 ) {
				return $options['zero'];
			}
		} elseif ($number < 1 && $number > -1 ) {
			if ($options['fractionSymbol'] !== false) {
				$multiply = intval('1' . str_pad('', $options['places'], '0'));
				$number = $number * $multiply;
				$options['places'] = null;
				$symbolKey = 'fraction';
			}
		}

		$position = $options[$symbolKey . 'Position'] != 'after' ? 'before' : 'after';
		$options[$position] = $options[$symbolKey . 'Symbol'];

		$abs = abs($number);
		$result = self::format($abs, $options);

		if ($number < 0 ) {
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
 * Added formats are merged with the defaults defined in CakeNumber::$_currencyDefaults
 * See CakeNumber::currency() for more information on the various options and their function.
 *
 * @param string $formatName The format name to be used in the future.
 * @param array $options The array of options for this format.
 * @return void
 * @see NumberHelper::currency()
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::addFormat
 */
	public static function addFormat($formatName, $options) {
		self::$_currencies[$formatName] = $options + self::$_currencyDefaults;
	}

}
