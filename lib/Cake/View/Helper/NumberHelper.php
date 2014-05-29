<?php
/**
 * Number Helper.
 *
 * Methods to make numbers more readable.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Helper
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeNumber', 'Utility');
App::uses('AppHelper', 'View/Helper');
App::uses('Hash', 'Utility');

/**
 * Number helper library.
 *
 * Methods to make numbers more readable.
 *
 * @package       Cake.View.Helper
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html
 * @see CakeNumber
 */
class NumberHelper extends AppHelper {

/**
 * CakeNumber instance
 *
 * @var CakeNumber
 */
	protected $_engine = null;

/**
 * Default Constructor
 *
 * ### Settings:
 *
 * - `engine` Class name to use to replace CakeNumber functionality
 *            The class needs to be placed in the `Utility` directory.
 *
 * @param View $View The View this helper is being attached to.
 * @param array $settings Configuration settings for the helper
 * @throws CakeException When the engine class could not be found.
 */
	public function __construct(View $View, $settings = array()) {
		$settings = Hash::merge(array('engine' => 'CakeNumber'), $settings);
		parent::__construct($View, $settings);
		list($plugin, $engineClass) = pluginSplit($settings['engine'], true);
		App::uses($engineClass, $plugin . 'Utility');
		if (class_exists($engineClass)) {
			$this->_engine = new $engineClass($settings);
		} else {
			throw new CakeException(__d('cake_dev', '%s could not be found', $engineClass));
		}
	}

/**
 * Call methods from CakeNumber utility class
 *
 * @param string $method Method to call.
 * @param array $params Parameters to pass to method.
 * @return mixed Whatever is returned by called method, or false on failure
 */
	public function __call($method, $params) {
		return call_user_func_array(array($this->_engine, $method), $params);
	}

/**
 * Formats a number with a level of precision.
 *
 * @param float $number A floating point number.
 * @param integer $precision The precision of the returned number.
 * @return float Formatted float.
 * @see CakeNumber::precision()
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::precision
 */
	public function precision($number, $precision = 3) {
		return $this->_engine->precision($number, $precision);
	}

/**
 * Returns a formatted-for-humans file size.
 *
 * @param integer $size Size in bytes
 * @return string Human readable size
 * @see CakeNumber::toReadableSize()
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::toReadableSize
 */
	public function toReadableSize($size) {
		return $this->_engine->toReadableSize($size);
	}

/**
 * Formats a number into a percentage string.
 *
 * Options:
 *
 * - `multiply`: Multiply the input value by 100 for decimal percentages.
 *
 * @param float $number A floating point number
 * @param integer $precision The precision of the returned number
 * @param array $options Options
 * @return string Percentage string
 * @see CakeNumber::toPercentage()
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::toPercentage
 */
	public function toPercentage($number, $precision = 2, $options = array()) {
		return $this->_engine->toPercentage($number, $precision, $options);
	}

/**
 * Formats a number into a currency format.
 *
 * @param float $number A floating point number
 * @param integer $options If integer then places, if string then before, if (,.-) then use it
 *   or array with places and before keys
 * @return string formatted number
 * @see CakeNumber::format()
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::format
 */
	public function format($number, $options = false) {
		return $this->_engine->format($number, $options);
	}

/**
 * Formats a number into a currency format.
 *
 * @param float $number Number to format.
 * @param string $currency Shortcut to default options. Valid values are 'USD', 'EUR', 'GBP', otherwise
 *   set at least 'before' and 'after' options.
 * 'USD' is the default currency, use CakeNumber::defaultCurrency() to change this default.
 * @param array $options Options list.
 * @return string Number formatted as a currency.
 * @see CakeNumber::currency()
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::currency
 */
	public function currency($number, $currency = null, $options = array()) {
		return $this->_engine->currency($number, $currency, $options);
	}

/**
 * Add a currency format to the Number helper. Makes reusing
 * currency formats easier.
 *
 * {{{ $this->Number->addFormat('NOK', array('before' => 'Kr. ')); }}}
 *
 * You can now use `NOK` as a shortform when formatting currency amounts.
 *
 * {{{ $this->Number->currency($value, 'NOK'); }}}
 *
 * Added formats are merged with the defaults defined in Cake\Utility\Number::$_currencyDefaults
 * See Cake\Utility\Number::currency() for more information on the various options and their function.
 *
 * @param string $formatName The format name to be used in the future.
 * @param array $options The array of options for this format.
 * @return void
 * @see CakeNumber::addFormat()
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::addFormat
 */
	public function addFormat($formatName, $options) {
		return $this->_engine->addFormat($formatName, $options);
	}

/**
 * Getter/setter for default currency
 *
 * @param string $currency The currency to be used in the future.
 * @return string Currency
 * @see CakeNumber::defaultCurrency()
 */
	public function defaultCurrency($currency) {
		return $this->_engine->defaultCurrency($currency);
	}

}
