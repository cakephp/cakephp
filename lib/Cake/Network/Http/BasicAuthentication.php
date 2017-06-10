<?php
/**
 * Basic authentication
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Network.Http
 * @since         CakePHP(tm) v 2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Basic authentication
 *
 * @package       Cake.Network.Http
 */
class BasicAuthentication {

/**
 * Authentication
 *
 * @param HttpSocket $http Http socket instance.
 * @param array &$authInfo Authentication info.
 * @return void
 * @see http://www.ietf.org/rfc/rfc2617.txt
 */
	public static function authentication(HttpSocket $http, &$authInfo) {
		if (isset($authInfo['user'], $authInfo['pass'])) {
			$http->request['header']['Authorization'] = static::_generateHeader($authInfo['user'], $authInfo['pass']);
		}
	}

/**
 * Proxy Authentication
 *
 * @param HttpSocket $http Http socket instance.
 * @param array &$proxyInfo Proxy info.
 * @return void
 * @see http://www.ietf.org/rfc/rfc2617.txt
 */
	public static function proxyAuthentication(HttpSocket $http, &$proxyInfo) {
		if (isset($proxyInfo['user'], $proxyInfo['pass'])) {
			$http->request['header']['Proxy-Authorization'] = static::_generateHeader($proxyInfo['user'], $proxyInfo['pass']);
		}
	}

/**
 * Generate basic [proxy] authentication header
 *
 * @param string $user Username.
 * @param string $pass Password.
 * @return string
 */
	protected static function _generateHeader($user, $pass) {
		return 'Basic ' . base64_encode($user . ':' . $pass);
	}

}
