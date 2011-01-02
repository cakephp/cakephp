<?php
/**
 * Basic authentication
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
 * @package       cake.libs.http
 * @since         CakePHP(tm) v 2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Basic authentication
 *
 * @package       cake.libs.http
 */
class BasicAuthentication {

/**
 * Authentication
 *
 * @param HttpSocket $http
 * @param array $authInfo
 * @return void
 * @see http://www.ietf.org/rfc/rfc2617.txt
 */
	public static function authentication(HttpSocket $http, &$authInfo) {
		if (isset($authInfo['user'], $authInfo['pass'])) {
			$http->request['header']['Authorization'] = self::_generateHeader($authInfo['user'], $authInfo['pass']);
		}
	}

/**
 * Proxy Authentication
 *
 * @param HttpSocket $http
 * @param array $proxyInfo
 * @return void
 * @see http://www.ietf.org/rfc/rfc2617.txt
 */
	public static function proxyAuthentication(HttpSocket $http, &$proxyInfo) {
		if (isset($proxyInfo['user'], $proxyInfo['pass'])) {
			$http->request['header']['Proxy-Authorization'] = self::_generateHeader($proxyInfo['user'], $proxyInfo['pass']);
		}
	}

/**
 * Generate basic [proxy] authentication header
 *
 * @param string $user
 * @param string $pass
 * @return string
 */
	protected static function _generateHeader($user, $pass) {
		return 'Basic ' . base64_encode($user . ':' . $pass);
	}

}
