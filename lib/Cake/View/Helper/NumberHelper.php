<?php
/**
 * Number Helper.
 *
 * Methods to make numbers more readable.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Helper
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppHelper', 'View/Helper');

/**
 * Number helper library.
 *
 * Methods to make numbers more readable.
 *
 * @package       Cake.View.Helper
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html
 */
class NumberHelper extends AppHelper {

/**
 * Currencies supported by the helper.  You can add additional currency formats
 * with NumberHelper::addFormat
 *
 * @var array
 */
	protected $_currencies = array(
		'USD' => array(
			'wholeSymbol' => '$', 'wholePosition' => 'before', 'fractionSymbol' => 'c', 'fractionPosition' => 'after',
			'zero' => 0, 'places' => 2, 'thousands' => ',', 'decimals' => '.', 'negative' => '()', 'escape' => true
		),
		'GBP' => array(
			'wholeSymbol'=>'&#163;', 'wholePosition' => 'before', 'fractionSymbol' => 'p', 'fractionPosition' => 'after',
			'zero' => 0, 'places' => 2, 'thousands' => ',', 'decimals' => '.', 'negative' => '()','escape' => false
		),
		'EUR' => array(
			'wholeSymbol'=>'&#8364;', 'wholePosition' => 'before', 'fractionSymbol' => false, 'fractionPosition' => 'after',
			'zero' => 0, 'places' => 2, 'thousands' => '.', 'decimals' => ',', 'negative' => '()', 'escape' => false
		)
	);

/**
 * Default options for currency formats
 *
 * @var array
 */
	protected $_currencyDefaults = array(
		'wholeSymbol'=>'', 'wholePosition' => 'before', 'fractionSymbol' => '', 'fractionPosition' => 'after',
		'zero' => '0', 'places' => 2, 'thousands' => ',', 'decimals' => '.','negative' => '()', 'escape' => true
	);

/**
 * Formats a number with a level of precision.
 *
 * @param float $number	A floating point number.
 * @param integer $precision The precision of the returned number.
 * @return float Formatted float.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::precision
 */
	public function precision($number, $precision = 3) {
		return sprintf("%01.{$precision}f", $number);
	}

/**
 * Returns a formatted-for-humans file size.
 *
 * @param integer $size Size in bytes
 * @return string Human readable size
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::toReadableSize
 */
	public function toReadableSize($size) {
		switch (true) {
			case $size < 1024:
				return __dn('cake', '%d Byte', '%d Bytes', $size, $size);
			case round($size / 1024) < 1024:
				return __d('cake', '%d KB', $this->precision($size / 1024, 0));
			case round($size / 1024 / 1024, 2) < 1024:
				return __d('cake', '%.2f MB', $this->precision($size / 1024 / 1024, 2));
			case round($size / 1024 / 1024 / 1024, 2) < 1024:
				return __d('cake', '%.2f GB', $this->precision($size / 1024 / 1024 / 1024, 2));
			default:
				return __d('cake', '%.2f TB', $this->precision($size / 1024 / 1024 / 1024 / 1024, 2));
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
	public function toPercentage($number, $precision = 2) {
		return $this->precision($number, $precision) . '%';
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
	public function format($number, $options = false) {
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
			$options = array_merge(array('before'=>'$', 'places' => 2, 'thousands' => ',', 'decimals' => '.'), $options);
			extract($options);
		}

		$out = $before . number_format($number, $places, $decimals, $thousands) . $after;

		if ($escape) {
			return h($out);
		}
		return $out;
	}

/**
 * Formats a number into a currency format.
 *
 * ### Options
 *
 * - `before` - The currency symbol to place before whole numbers ie. '$'
 * - `after` - The currency symbol to place after decimal numbers ie. 'c'. Set to boolean false to
 *    use no decimal symbol.  eg. 0.35 => $0.35.
 * - `zero` - The text to use for zero values, can be a string or a number. ie. 0, 'Free!'
 * - `places` - Number of decimal places to use. ie. 2
 * - `thousands` - Thousands separator ie. ','
 * - `decimals` - Decimal separator symbol ie. '.'
 * - `negative` - Symbol for negative numbers. If equal to '()', the number will be wrapped with ( and )
 * - `escape` - Should the output be htmlentity escaped? Defaults to true
 *
 * @param float $number
 * @param string $currency Shortcut to default options. Valid values are 'USD', 'EUR', 'GBP', otherwise
 *   set at least 'before' and 'after' options.
 * @param array $options
 * @return string Number formatted as a currency.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::currency
 */
	public function currency($number, $currency = 'USD', $options = array()) {
		$default = $this->_currencyDefaults;

		if (isset($this->_currencies[$currency])) {
			$default = $this->_currencies[$currency];
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

		$position = $options[$symbolKey.'Position'] != 'after' ? 'before' : 'after';
		$options[$position] = $options[$symbolKey.'Symbol'];

		$abs = abs($number);
		$result = $this->format($abs, $options);

		if ($number < 0 ) {
			if ($options['negative'] == '()') {
				$result = '(' . $result .')';
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
 * Added formats are merged with the following defaults.
 *
 * {{{
 *	array(
 *		'before' => '$', 'after' => 'c', 'zero' => 0, 'places' => 2, 'thousands' => ',',
 *		'decimals' => '.', 'negative' => '()', 'escape' => true
 *	)
 * }}}
 *
 * @param string $formatName The format name to be used in the future.
 * @param array $options The array of options for this format.
 * @return void
 * @see NumberHelper::currency()
 */
	public function addFormat($formatName, $options) {
		$this->_currencies[$formatName] = $options + $this->_currencyDefaults;
	}

}
