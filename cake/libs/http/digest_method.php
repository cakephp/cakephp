<?php
/**
 * Digest authentication
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.http
 * @since         CakePHP(tm) v 2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Digest authentication
 *
 * @package       cake
 * @subpackage    cake.cake.libs.http
 */
class DigestMethod {

/**
 * Authentication
 *
 * @param HttpSocket $http
 * @return void
 * @throws Exception
 * @link http://www.ietf.org/rfc/rfc2617.txt
 */
	public static function authentication(&$http) {
		if (isset($http->request['auth']['user'], $http->request['auth']['pass'])) {
			if (!isset($http->config['request']['auth']['realm']) && !self::_getServerInformation($http)) {
				return;
			}
			$http->request['header']['Authorization'] = self::_generateHeader($http);
		}
	}

/**
 * Retrive information about the authetication
 *
 * @param HttpSocket $http
 * @return boolean
 */
	protected static function _getServerInformation(&$http) {
		$originalRequest = $http->request;
		$http->request['auth'] = array('method' => false);
		$http->request($http->request);
		$http->request = $originalRequest;

		if (empty($http->response['header']['Www-Authenticate'])) {
			return false;
		}
		preg_match_all('@(\w+)=(?:(?:")([^"]+)"|([^\s,$]+))@', $http->response['header']['Www-Authenticate'], $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$http->config['request']['auth'][$match[1]] = $match[2];
		}
		if (!empty($http->config['request']['auth']['qop']) && empty($http->config['request']['auth']['nc'])) {
			$http->config['request']['auth']['nc'] = 1;
		}
		return true;
	}

/**
 * Generate the header Authorization
 *
 * @param HttpSocket $http
 * @return string
 */
	protected static function _generateHeader(&$http) {
		$a1 = md5($http->request['auth']['user'] . ':' . $http->config['request']['auth']['realm'] . ':' . $http->request['auth']['pass']);
		$a2 = md5($http->request['method'] . ':' . $http->request['uri']['path']);

		if (empty($http->config['request']['auth']['qop'])) {
			$response = md5($a1 . ':' . $http->config['request']['auth']['nonce'] . ':' . $a2);
		} else {
			$http->config['request']['auth']['cnonce'] = uniqid();
			$nc = sprintf('%08x', $http->config['request']['auth']['nc']++);
			$response = md5($a1 . ':' . $http->config['request']['auth']['nonce'] . ':' . $nc . ':' . $http->config['request']['auth']['cnonce'] . ':auth:' . $a2);
		}

		$authHeader = 'Digest ';
		$authHeader .= 'username="' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $http->request['auth']['user']) . '", ';
		$authHeader .= 'realm="' . $http->config['request']['auth']['realm'] . '", ';
		$authHeader .= 'nonce="' . $http->config['request']['auth']['nonce'] . '", ';
		$authHeader .= 'uri="' . $http->request['uri']['path'] . '", ';
		$authHeader .= 'response="' . $response . '"';
		if (!empty($http->config['request']['auth']['opaque'])) {
			$authHeader .= ', opaque="' . $http->config['request']['auth']['opaque'] . '"';
		}
		if (!empty($http->config['request']['auth']['qop'])) {
			$authHeader .= ', qop="auth", nc=' . $nc . ', cnonce="' . $http->config['request']['auth']['cnonce'] . '"';
		}
		return $authHeader;
	}
}
