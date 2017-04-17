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
use Cake\Core\Exception\Exception;
use Cake\View\Helper;
use Cake\View\View;

/**
 * Number helper library.
 *
 * Methods to make numbers more readable.
 *
 * @link http://book.cakephp.org/3.0/en/views/helpers/number.html
 * @see \Cake\I18n\Number
 */
class NumberHelper extends Helper
{

    /**
     * Default config for this class
     *
     * @var array
     */
    protected $_defaultConfig = [
        'engine' => 'Cake\I18n\Number'
    ];

    /**
     * Cake\I18n\Number instance
     *
     * @var \Cake\I18n\Number
     */
    protected $_engine;

    /**
     * Default Constructor
     *
     * ### Settings:
     *
     * - `engine` Class name to use to replace Cake\I18n\Number functionality
     *            The class needs to be placed in the `Utility` directory.
     *
     * @param \Cake\View\View $View The View this helper is being attached to.
     * @param array $config Configuration settings for the helper
     * @throws \Cake\Core\Exception\Exception When the engine class could not be found.
     */
    public function __construct(View $View, array $config = [])
    {
        parent::__construct($View, $config);

        $config = $this->_config;

        $engineClass = App::className($config['engine'], 'Utility');
        if ($engineClass) {
            $this->_engine = new $engineClass($config);
        } else {
            throw new Exception(sprintf('Class for %s could not be found', $config['engine']));
        }
    }

    /**
     * Call methods from Cake\I18n\Number utility class
     *
     * @param string $method Method to invoke
     * @param array $params Array of params for the method.
     * @return mixed Whatever is returned by called method, or false on failure
     */
    public function __call($method, $params)
    {
        return call_user_func_array([$this->_engine, $method], $params);
    }

    /**
     * Formats a number with a level of precision.
     *
     * @param float $number A floating point number.
     * @param int $precision The precision of the returned number.
     * @return string Formatted float.
     * @see \Cake\I18n\Number::precision()
     * @link http://book.cakephp.org/3.0/en/views/helpers/number.html#formatting-floating-point-numbers
     */
    public function precision($number, $precision = 3)
    {
        return $this->_engine->precision($number, $precision);
    }

    /**
     * Returns a formatted-for-humans file size.
     *
     * @param int $size Size in bytes
     * @return string Human readable size
     * @see \Cake\I18n\Number::toReadableSize()
     * @link http://book.cakephp.org/3.0/en/views/helpers/number.html#interacting-with-human-readable-values
     */
    public function toReadableSize($size)
    {
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
     * @param int $precision The precision of the returned number
     * @param array $options Options
     * @return string Percentage string
     * @see \Cake\I18n\Number::toPercentage()
     * @link http://book.cakephp.org/3.0/en/views/helpers/number.html#formatting-percentages
     */
    public function toPercentage($number, $precision = 2, array $options = [])
    {
        return $this->_engine->toPercentage($number, $precision, $options);
    }

    /**
     * Formats a number into the correct locale format
     *
     * Options:
     *
     * - `places` - Minimum number or decimals to use, e.g 0
     * - `precision` - Maximum Number of decimal places to use, e.g. 2
     * - `locale` - The locale name to use for formatting the number, e.g. fr_FR
     * - `before` - The string to place before whole numbers, e.g. '['
     * - `after` - The string to place after decimal numbers, e.g. ']'
     * - `escape` - Whether or not to escape html in resulting string
     *
     * @param float $number A floating point number.
     * @param array $options An array with options.
     * @return string Formatted number
     * @link http://book.cakephp.org/3.0/en/views/helpers/number.html#formatting-numbers
     */
    public function format($number, array $options = [])
    {
        $formatted = $this->_engine->format($number, $options);
        $options += ['escape' => true];

        return $options['escape'] ? h($formatted) : $formatted;
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
     * - `escape` - Whether or not to escape html in resulting string
     *
     * @param float $number Value to format.
     * @param string|null $currency International currency name such as 'USD', 'EUR', 'JPY', 'CAD'
     * @param array $options Options list.
     * @return string Number formatted as a currency.
     */
    public function currency($number, $currency = null, array $options = [])
    {
        $formatted = $this->_engine->currency($number, $currency, $options);
        $options += ['escape' => true];

        return $options['escape'] ? h($formatted) : $formatted;
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
     * - `escape` - Set to false to prevent escaping
     *
     * @param float $value A floating point number
     * @param array $options Options list.
     * @return string formatted delta
     */
    public function formatDelta($value, array $options = [])
    {
        $formatted = $this->_engine->formatDelta($value, $options);
        $options += ['escape' => true];

        return $options['escape'] ? h($formatted) : $formatted;
    }

    /**
     * Getter/setter for default currency
     *
     * @param string|bool $currency Default currency string to be used by currency()
     * if $currency argument is not provided. If boolean false is passed, it will clear the
     * currently stored value
     * @return string Currency
     */
    public function defaultCurrency($currency)
    {
        return $this->_engine->defaultCurrency($currency);
    }

    /**
     * Event listeners.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [];
    }

    /**
     * Formats a number into locale specific ordinal suffix.
     *
     * @param int|float $value An integer
     * @param array $options An array with options.
     * @return string formatted number
     */
    public function ordinal($value, array $options = [])
    {
        return $this->_engine->ordinal($value, $options);
    }
}
