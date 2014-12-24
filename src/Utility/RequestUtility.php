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
namespace Cake\Utility;

/**
 * Helper functionality for Request class.
 */
class RequestUtility {

/**
 * Get the languages accepted by the client, or check if a specific language is accepted.
 *
 * Get the list of accepted languages:
 *
 * {{{ \Cake\Network\Request::acceptLanguage(); }}}
 *
 * Check if a specific language is accepted:
 *
 * {{{ \Cake\Network\Request::acceptLanguage('es-es'); }}}
 *
 * @param string|null $language The language to test.
 * @return mixed If a $language is provided, a boolean. Otherwise the array of accepted languages.
 */
	public static function acceptLanguage($acceptLanguage, $checkLanguage = null) {
		$raw = static::parseAcceptWithQualifier($acceptLanguage);
		$accept = array();
		foreach ($raw as $languages) {
			foreach ($languages as &$lang) {
				if (strpos($lang, '_')) {
					$lang = str_replace('_', '-', $lang);
				}
				$lang = strtolower($lang);
			}
			$accept = array_merge($accept, $languages);
		}
		if ($checkLanguage === null) {
			return $accept;
		}
		return in_array(strtolower($checkLanguage), $accept);
	}

/**
 * Parse Accept* headers with qualifier options.
 *
 * Only qualifiers will be extracted, any other accept extensions will be
 * discarded as they are not frequently used.
 *
 * @param string $header Header to parse.
 * @return array
 */
	public static function parseAcceptWithQualifier($header) {
		$accept = array();
		$header = explode(',', $header);
		foreach (array_filter($header) as $value) {
			$prefValue = '1.0';
			$value = trim($value);

			$semiPos = strpos($value, ';');
			if ($semiPos !== false) {
				$params = explode(';', $value);
				$value = trim($params[0]);
				foreach ($params as $param) {
					$qPos = strpos($param, 'q=');
					if ($qPos !== false) {
						$prefValue = substr($param, $qPos + 2);
					}
				}
			}

			if (!isset($accept[$prefValue])) {
				$accept[$prefValue] = array();
			}
			if ($prefValue) {
				$accept[$prefValue][] = $value;
			}
		}
		krsort($accept);
		return $accept;
	}

}
