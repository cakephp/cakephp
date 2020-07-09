<?php
declare(strict_types=1);

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
namespace Cake\View\Helper;

use Cake\Core\App;
use Cake\Core\Exception\Exception;
use Cake\I18n\Number;
use Cake\View\Helper;
use Cake\View\View;

/**
 * Number helper library.
 *
 * Methods to make numbers more readable.
 *
 * @link https://book.cakephp.org/4/en/views/helpers/number.html
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
        'engine' => Number::class,
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
     * @param \Cake\View\View $view The View this helper is being attached to.
     * @param array $config Configuration settings for the helper
     * @throws \Cake\Core\Exception\Exception When the engine class could not be found.
     */
    public function __construct(View $view, array $config = [])
    {
        parent::__construct($view, $config);

        $config = $this->_config;

        /** @psalm-var class-string<\Cake\I18n\Number>|null $engineClass */
        $engineClass = App::className($config['engine'], 'Utility');
        if ($engineClass === null) {
            throw new Exception(sprintf('Class for %s could not be found', $config['engine']));
        }

        $this->_engine = new $engineClass($config);
    }

    /**
     * Call methods from Cake\I18n\Number utility class
     *
     * @param string $method Method to invoke
     * @param array $params Array of params for the method.
     * @return mixed Whatever is returned by called method, or false on failure
     */
    public function __call(string $method, array $params)
    {
        return $this->_engine->{$method}(...$params);
    }

    /**
     * Formats a number with a level of precision.
     *
     * @param float|string $number A floating point number.
     * @param int $precision The precision of the returned number.
     * @param array $options Additional options.
     * @return string Formatted float.
     * @see \Cake\I18n\Number::precision()
     * @link https://book.cakephp.org/4/en/views/helpers/number.html#formatting-floating-point-numbers
     */
    public function precision($number, int $precision = 3, array $options = []): string
    {
        return $this->_engine->precision($number, $precision, $options);
    }

    /**
     * Returns a formatted-for-humans file size.
     *
     * @param int|string $size Size in bytes
     * @return string Human readable size
     * @see \Cake\I18n\Number::toReadableSize()
     * @link https://book.cakephp.org/4/en/views/helpers/number.html#interacting-with-human-readable-values
     */
    public function toReadableSize($size): string
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
     * @param float|string $number A floating point number
     * @param int $precision The precision of the returned number
     * @param array $options Options
     * @return string Percentage string
     * @see \Cake\I18n\Number::toPercentage()
     * @link https://book.cakephp.org/4/en/views/helpers/number.html#formatting-percentages
     */
    public function toPercentage($number, int $precision = 2, array $options = []): string
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
     * @param float|string $number A floating point number.
     * @param array $options An array with options.
     * @return string Formatted number
     * @link https://book.cakephp.org/4/en/views/helpers/number.html#formatting-numbers
     */
    public function format($number, array $options = []): string
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
     * - `pattern` - An ICU number pattern to use for formatting the number. e.g #,##0.00
     * - `useIntlCode` - Whether or not to replace the currency symbol with the international
     *   currency code.
     * - `escape` - Whether or not to escape html in resulting string
     *
     * @param float|string $number Value to format.
     * @param string|null $currency International currency name such as 'USD', 'EUR', 'JPY', 'CAD'
     * @param array $options Options list.
     * @return string Number formatted as a currency.
     */
    public function currency($number, ?string $currency = null, array $options = []): string
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
     * @param float|string $value A floating point number
     * @param array $options Options list.
     * @return string formatted delta
     */
    public function formatDelta($value, array $options = []): string
    {
        $formatted = $this->_engine->formatDelta($value, $options);
        $options += ['escape' => true];

        return $options['escape'] ? h($formatted) : $formatted;
    }

    /**
     * Getter/setter for default currency
     *
     * @param string|false|null $currency Default currency string to be used by currency()
     * if $currency argument is not provided. If boolean false is passed, it will clear the
     * currently stored value. Null reads the current default.
     * @return string|null Currency
     * @deprecated 3.9.0 Use setDefaultCurrency()/getDefaultCurrency() instead.
     */
    public function defaultCurrency($currency): ?string
    {
        return $this->_engine->defaultCurrency($currency);
    }

    /**
     * Event listeners.
     *
     * @return array
     */
    public function implementedEvents(): array
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
    public function ordinal($value, array $options = []): string
    {
        return $this->_engine->ordinal($value, $options);
    }
}
