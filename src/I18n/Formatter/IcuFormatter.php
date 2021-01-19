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

use Cake\I18n\Exception\I18nException;
use Cake\I18n\FormatterInterface;
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
     * @param string $message The message to be translated
     * @param array $tokenValues The list of values to interpolate in the message
     * @return string The formatted message
     * @throws \Cake\I18n\Exception\I18nException
     */
    public function format(string $locale, string $message, array $tokenValues): string
    {
        if ($message === '') {
            return $message;
        }

        $formatter = new MessageFormatter($locale, $message);
        $result = $formatter->format($tokenValues);
        if ($result === false) {
            throw new I18nException($formatter->getErrorMessage(), $formatter->getErrorCode());
        }

        return $result;
    }
}
