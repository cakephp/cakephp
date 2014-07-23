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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n\Formatter;

use Aura\Intl\Exception;
use Aura\Intl\FormatterInterface;
use Cake\I18n\PluralRules;
use MessageFormatter;

/**
 * A formatter that will interpolate variables using the MessageFormatter class
 */
class IcuFormatter implements FormatterInterface {

/**
 * Returns a string with all passed variables interpolated into the original
 * message. Variables are interpolated using the MessageFormatter class.
 *
 * If an array is passed in `$message`, it will trigger the plural selection
 * routine. Plural forms are selected depending on the locale and the `_count`
 * key passed in `$vars`.
 *
 * @param string $locale The locale in which the message is presented.
 * @param string|array $message The message to be translated
 * @return string The formatted message
 */
	public function format($locale, $message, array $vars) {
		if (!is_string($message)) {
			$count = isset($vars['_count']) ? $vars['_count'] : 0;
			$form = PluralRules::calculate($locale, $vars['_count']);
			$message = $message[$form];
		}

		$formatter = new MessageFormatter($locale, $message);

		if (!$formatter) {
			throw new Exception\CannotInstantiateFormatter(
				intl_get_error_message(),
				intl_get_error_code()
			);
		}

		$result = $formatter->format($vars);
        if ($result === false) {
            throw new Exception\CannotFormat(
                $formatter->getErrorMessage(),
                $formatter->getErrorCode()
            );
        }

		return $result;
	}

}
