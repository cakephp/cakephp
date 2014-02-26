<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Network\Http\Auth;

use Cake\Network\Http\Request;

/**
 * Basic authentication adapter for Cake\Network\Http\Client
 *
 * Generally not directly constructed, but instead used by Cake\Network\Http\Client
 * when $options['auth']['type'] is 'basic'
 */
class Basic {

/**
 * Add Authorization header to the request.
 *
 * @param Request $request
 * @param array $credentials
 * @return void
 * @see http://www.ietf.org/rfc/rfc2617.txt
 */
	public function authentication(Request $request, $credentials) {
		if (isset($credentials['username'], $credentials['password'])) {
			$value = $this->_generateHeader($credentials['username'], $credentials['password']);
			$request->header('Authorization', $value);
		}
	}

/**
 * Proxy Authentication
 *
 * @param Request $request
 * @param array $credentials
 * @return void
 * @see http://www.ietf.org/rfc/rfc2617.txt
 */
	public function proxyAuthentication(Request $request, $credentials) {
		if (isset($credentials['username'], $credentials['password'])) {
			$value = $this->_generateHeader($credentials['username'], $credentials['password']);
			$request->header('Proxy-Authorization', $value);
		}
	}

/**
 * Generate basic [proxy] authentication header
 *
 * @param string $user
 * @param string $pass
 * @return string
 */
	protected function _generateHeader($user, $pass) {
		return 'Basic ' . base64_encode($user . ':' . $pass);
	}

}
