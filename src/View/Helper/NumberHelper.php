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
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Core\App;
use Cake\Error;
use Cake\Utility\Hash;
use Cake\View\Helper;
use Cake\View\View;

/**
 * Number helper library.
 *
 * Methods to make numbers more readable.
 *
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html
 * @see Cake\Utility\Number
 */
class NumberHelper extends Helper {

/**
 * Cake\Utility\Number instance
 *
 * @var Cake\Utility\Number
 */
	protected $_engine = null;

/**
 * Default Constructor
 *
 * ### Settings:
 *
 * - `engine` Class name to use to replace Cake\Utility\Number functionality
 *            The class needs to be placed in the `Utility` directory.
 *
 * @param View $View The View this helper is being attached to.
 * @param array $settings Configuration settings for the helper
 * @throws Cake\Error\Exception When the engine class could not be found.
 */
	public function __construct(View $View, $settings = array()) {
		$settings = Hash::merge(array('engine' => 'Cake\Utility\Number'), $settings);
		parent::__construct($View, $settings);
		$engineClass = App::classname($settings['engine'], 'Utility');
		if ($engineClass) {
			$this->_engine = new $engineClass($settings);
		} else {
			throw new Error\Exception(sprintf('Class for %s could not be found', $settings['engine']));
		}
	}

/**
 * Call methods from Cake\Utility\Number utility class
 * @return mixed Whatever is returned by called method, or false on failure
 */
	public function __call($method, $params) {
		return call_user_func_array(array($this->_engine, $method), $params);
	}

/**
 * @see: Cake\Utility\Number::precision()
 *
 * @param float $number A floating point number.
 * @param integer $precision The precision of the returned number.
 * @return float Formatted float.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::precision
 */
	public function precision($number, $precision = 3) {
		return $this->_engine->precision($number, $precision);
	}

/**
 * @see: Cake\Utility\Number::toReadableSize()
 *
 * @param integer $size Size in bytes
 * @return string Human readable size
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::toReadableSize
 */
	public function toReadableSize($size) {
		return $this->_engine->toReadableSize($size);
	}

/**
 * @see: Cake\Utility\Number::toPercentage()
 *
 * @param float $number A floating point number
 * @param integer $precision The precision of the returned number
 * @param array $options Options
 * @return string Percentage string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::toPercentage
 */
	public function toPercentage($number, $precision = 2, $options = array()) {
		return $this->_engine->toPercentage($number, $precision, $options);
	}

/**
 * @see: Cake\Utility\Number::format()
 *
 * @param float $number A floating point number
 * @param integer $options If integer then places, if string then before, if (,.-) then use it
 *   or array with places and before keys
 * @return string formatted number
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::format
 */
	public function format($number, $options = false) {
		return $this->_engine->format($number, $options);
	}

/**
 * @see: Cake\Utility\Number::currency()
 *
 * @param float $number
 * @param string $currency Shortcut to default options. Valid values are 'USD', 'EUR', 'GBP', otherwise
 *   set at least 'before' and 'after' options.
 * 'USD' is the default currency, use CakeNumber::defaultCurrency() to change this default.
 * @param array $options
 * @return string Number formatted as a currency.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::currency
 */
	public function currency($number, $currency = null, $options = array()) {
		return $this->_engine->currency($number, $currency, $options);
	}

/**
 * @see: Cake\Utility\Number::addFormat()
 *
 * @param string $formatName The format name to be used in the future.
 * @param array $options The array of options for this format.
 * @return void
 * @see NumberHelper::currency()
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::addFormat
 */
	public function addFormat($formatName, $options) {
		return $this->_engine->addFormat($formatName, $options);
	}

/**
 * @see CakeNumber::defaultCurrency()
 *
 * @param string $currency The currency to be used in the future.
 * @return void
 * @see NumberHelper::currency()
 */
	public function defaultCurrency($currency) {
		return $this->_engine->defaultCurrency($currency);
	}

}
