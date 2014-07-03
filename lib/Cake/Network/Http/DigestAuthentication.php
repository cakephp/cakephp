<?php
/**
 * Digest authentication
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
 * @package       Cake.Network.Http
 * @since         CakePHP(tm) v 2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Digest authentication
 *
 * @package       Cake.Network.Http
 */
class DigestAuthentication {

/**
 * Authentication
 *
 * @param HttpSocket $http Http socket instance.
 * @param array &$authInfo Authentication info.
 * @return void
 * @link http://www.ietf.org/rfc/rfc2617.txt
 */
	public static function authentication(HttpSocket $http, &$authInfo) {
		if (isset($authInfo['user'], $authInfo['pass'])) {
			if (!isset($authInfo['realm']) && !self::_getServerInformation($http, $authInfo)) {
				return;
			}
			$http->request['header']['Authorization'] = self::_generateHeader($http, $authInfo);
		}
	}

/**
 * Retrieve information about the authentication
 *
 * @param HttpSocket $http Http socket instance.
 * @param array &$authInfo Authentication info.
 * @return bool
 */
	protected static function _getServerInformation(HttpSocket $http, &$authInfo) {
		$originalRequest = $http->request;
		$http->configAuth(false);
		$http->request($http->request);
		$http->request = $originalRequest;
		$http->configAuth('Digest', $authInfo);

		if (empty($http->response['header']['WWW-Authenticate'])) {
			return false;
		}
		preg_match_all('@(\w+)=(?:(?:")([^"]+)"|([^\s,$]+))@', $http->response['header']['WWW-Authenticate'], $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$authInfo[$match[1]] = $match[2];
		}
		if (!empty($authInfo['qop']) && empty($authInfo['nc'])) {
			$authInfo['nc'] = 1;
		}
		return true;
	}

/**
 * Generate the header Authorization
 *
 * @param HttpSocket $http Http socket instance.
 * @param array &$authInfo Authentication info.
 * @return string
 */
	protected static function _generateHeader(HttpSocket $http, &$authInfo) {
		$a1 = md5($authInfo['user'] . ':' . $authInfo['realm'] . ':' . $authInfo['pass']);
		$a2 = md5($http->request['method'] . ':' . $http->request['uri']['path']);

		if (empty($authInfo['qop'])) {
			$response = md5($a1 . ':' . $authInfo['nonce'] . ':' . $a2);
		} else {
			$authInfo['cnonce'] = uniqid();
			$nc = sprintf('%08x', $authInfo['nc']++);
			$response = md5($a1 . ':' . $authInfo['nonce'] . ':' . $nc . ':' . $authInfo['cnonce'] . ':auth:' . $a2);
		}

		$authHeader = 'Digest ';
		$authHeader .= 'username="' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $authInfo['user']) . '", ';
		$authHeader .= 'realm="' . $authInfo['realm'] . '", ';
		$authHeader .= 'nonce="' . $authInfo['nonce'] . '", ';
		$authHeader .= 'uri="' . $http->request['uri']['path'] . '", ';
		$authHeader .= 'response="' . $response . '"';
		if (!empty($authInfo['opaque'])) {
			$authHeader .= ', opaque="' . $authInfo['opaque'] . '"';
		}
		if (!empty($authInfo['qop'])) {
			$authHeader .= ', qop="auth", nc=' . $nc . ', cnonce="' . $authInfo['cnonce'] . '"';
		}
		return $authHeader;
	}

}
