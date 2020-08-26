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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n\Formatter;

use Aura\Intl\FormatterInterface;

/**
 * A formatter that will interpolate variables using sprintf and
 * select the correct plural form when required
 */
class SprintfFormatter implements FormatterInterface
{
    /**
     * Returns a string with all passed variables interpolated into the original
     * message. Variables are interpolated using the sprintf format.
     *
     * @param string $locale The locale in which the message is presented.
     * @param string $string The message to be translated
     * @param array $tokensValues The list of values to interpolate in the message
     * @return string The formatted message
     * @psalm-suppress ParamNameMismatch
     */
    public function format($locale, $string, array $tokensValues): string
    {
        unset($tokensValues['_singular']);

        return vsprintf($string, $tokensValues);
    }
}
