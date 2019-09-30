<?php
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

use Aura\Intl\Exception\CannotFormat;
use Aura\Intl\Exception\CannotInstantiateFormatter;
use Aura\Intl\FormatterInterface;
use MessageFormatter;

/**
 * A formatter that will interpolate variables using the MessageFormatter class
 */
class IcuFormatter implements FormatterInterface
{
    /**
     * Returns a string with all passed variables interpolated into the original
     * message. Variables are interpolated using the MessageFormatter class.
     *
     * @param string $locale The locale in which the message is presented.
     * @param string|array $message The message to be translated
     * @param array $vars The list of values to interpolate in the message
     * @return string The formatted message
     * @throws \Aura\Intl\Exception\CannotFormat
     * @throws \Aura\Intl\Exception\CannotInstantiateFormatter
     */
    public function format($locale, $message, array $vars)
    {
        unset($vars['_singular'], $vars['_count']);

        return $this->_formatMessage($locale, $message, $vars);
    }

    /**
     * Does the actual formatting using the MessageFormatter class
     *
     * @param string $locale The locale in which the message is presented.
     * @param string|array $message The message to be translated
     * @param array $vars The list of values to interpolate in the message
     * @return string The formatted message
     * @throws \Aura\Intl\Exception\CannotInstantiateFormatter if any error occurred
     * while parsing the message
     * @throws \Aura\Intl\Exception\CannotFormat If any error related to the passed
     * variables is found
     */
    protected function _formatMessage($locale, $message, $vars)
    {
        if ($message === '') {
            return $message;
        }
        // Using procedural style as it showed twice as fast as
        // its counterpart in PHP 5.5
        $result = MessageFormatter::formatMessage($locale, $message, $vars);

        if ($result === false) {
            // The user might be interested in what went wrong, so replay the
            // previous action using the object oriented style to figure out
            $formatter = new MessageFormatter($locale, $message);
            if (!$formatter) {
                throw new CannotInstantiateFormatter(intl_get_error_message(), intl_get_error_code());
            }

            $formatter->format($vars);
            throw new CannotFormat($formatter->getErrorMessage(), $formatter->getErrorCode());
        }

        return $result;
    }
}
