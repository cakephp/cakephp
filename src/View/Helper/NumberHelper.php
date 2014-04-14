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
 * @see \Cake\Utility\Number
 */
class NumberHelper extends Helper {

/**
 * Default config for this class
 *
 * @var mixed
 */
	protected $_defaultConfig = [
		'engine' => 'Cake\Utility\Number'
	];

/**
 * Cake\Utility\Number instance
 *
 * @var \Cake\Utility\Number
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
 * @param array $config Configuration settings for the helper
 * @throws \Cake\Error\Exception When the engine class could not be found.
 */
	public function __construct(View $View, array $config = array()) {
		parent::__construct($View, $config);

		$config = $this->_config;

		$engineClass = App::classname($config['engine'], 'Utility');
		if ($engineClass) {
			$this->_engine = new $engineClass($config);
		} else {
			throw new Error\Exception(sprintf('Class for %s could not be found', $config['engine']));
		}
	}

/**
 * Call methods from Cake\Utility\Number utility class
 *
 * @param string $method Method to invoke
 * @param array $params Array of params for the method.
 * @return mixed Whatever is returned by called method, or false on failure
 */
	public function __call($method, $params) {
		return call_user_func_array(array($this->_engine, $method), $params);
	}

/**
 * Formats a number with a level of precision.
 *
 * @see \Cake\Utility\Number::precision()
 *
 * @param float $number A floating point number.
 * @param int $precision The precision of the returned number.
 * @return float Formatted float.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::precision
 */
	public function precision($number, $precision = 3) {
		return $this->_engine->precision($number, $precision);
	}

/**
 * Returns a formatted-for-humans file size.
 *
 * @see \Cake\Utility\Number::toReadableSize()
 *
 * @param int $size Size in bytes
 * @return string Human readable size
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
 * @see \Cake\Utility\Number::toPercentage()
 *
 * @param float $number A floating point number
 * @param int $precision The precision of the returned number
 * @param array $options Options
 * @return string Percentage string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::toPercentage
 */
	public function toPercentage($number, $precision = 2, array $options = array()) {
		return $this->_engine->toPercentage($number, $precision, $options);
	}

/**
 * Formats a number into a currency format.
 *
 * @see \Cake\Utility\Number::format()
 *
 * @param float $number A floating point number.
 * @param array $options Array of options.
 * @return string Formatted number
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::format
 */
	public function format($number, array $options = []) {
		return $this->_engine->format($number, $options);
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
 * @see \Cake\Utility\Number::currency()
 *
 * @param float $number
 * @param string $currency Shortcut to default options. Valid values are 'USD', 'EUR', 'GBP', otherwise
 *   set at least 'before' and 'after' options.
 * 'USD' is the default currency, use Number::defaultCurrency() to change this default.
 * @param array $options
 * @return string Number formatted as a currency.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::currency
 */
	public function currency($number, $currency = null, array $options = array()) {
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
 * @see \Cake\Utility\Number::addFormat()
 *
 * @param string $formatName The format name to be used in the future.
 * @param array $options The array of options for this format.
 * @return void
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::addFormat
 */
	public function addFormat($formatName, array $options) {
		return $this->_engine->addFormat($formatName, $options);
	}

/**
 * Getter/setter for default currency
 *
 * @see  \Cake\Utility\Number::defaultCurrency()
 *
 * @param string $currency The currency to be used in the future.
 * @return string Currency
 */
	public function defaultCurrency($currency) {
		return $this->_engine->defaultCurrency($currency);
	}

/**
 * Event listeners.
 *
 * @return array
 */
	public function implementedEvents() {
		return [];
	}

}
