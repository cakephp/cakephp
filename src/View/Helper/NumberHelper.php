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

use Cake\I18n\Number;
use Cake\View\Helper;
use function Cake\Core\h;

/**
 * Number helper library.
 *
 * Methods to make numbers more readable.
 *
 * @method string ordinal(float|int $value, array $options = []) See Number::ordinal()
 * @method string precision(string|float|int $number, int $precision = 3, array $options = []) See Number::precision()
 * @method string toPercentage(string|float|int $value, int $precision = 3, array $options = []) See Number::toPercentage()
 * @method string toReadableSize(string|float|int $size) See Number::toReadableSize()
 * @link https://book.cakephp.org/5/en/views/helpers/number.html
 * @see \Cake\I18n\Number
 */
class NumberHelper extends Helper
{
    /**
     * Call methods from Cake\I18n\Number utility class
     *
     * @param string $method Method to invoke
     * @param array $params Array of params for the method.
     * @return mixed Whatever is returned by called method, or false on failure
     */
    public function __call(string $method, array $params): mixed
    {
        return Number::{$method}(...$params);
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
     * - `escape` - Whether to escape html in resulting string
     *
     * @param string|float|int $number A floating point number.
     * @param array<string, mixed> $options An array with options.
     * @return string Formatted number
     * @link https://book.cakephp.org/5/en/views/helpers/number.html#formatting-numbers
     */
    public function format(string|float|int $number, array $options = []): string
    {
        $formatted = Number::format($number, $options);
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
     * - `useIntlCode` - Whether to replace the currency symbol with the international
     *   currency code.
     * - `escape` - Whether to escape html in resulting string
     *
     * @param string|float $number Value to format.
     * @param string|null $currency International currency name such as 'USD', 'EUR', 'JPY', 'CAD'
     * @param array<string, mixed> $options Options list.
     * @return string Number formatted as a currency.
     */
    public function currency(string|float $number, ?string $currency = null, array $options = []): string
    {
        $formatted = Number::currency($number, $currency, $options);
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
     * @param string|float $value A floating point number
     * @param array<string, mixed> $options Options list.
     * @return string formatted delta
     */
    public function formatDelta(string|float $value, array $options = []): string
    {
        $formatted = Number::formatDelta($value, $options);
        $options += ['escape' => true];

        return $options['escape'] ? h($formatted) : $formatted;
    }

    /**
     * Event listeners.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [];
    }
}
