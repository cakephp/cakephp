<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Network\Http\Auth;

use Cake\Network\Http\Client;
use Cake\Network\Http\Request;

/**
 * Digest authentication adapter for Cake\Network\Http\Client
 *
 * Generally not directly constructed, but instead used by Cake\Network\Http\Client
 * when $options['auth']['type'] is 'digest'
 */
class Digest {

/**
 * Instance of Cake\Network\Http\Client
 *
 * @var \Cake\Network\Http\Client
 */
	protected $_client;

/**
 * Constructor
 *
 * @param \Cake\Network\Http\Client $client Http client object.
 * @param array $options Options list.
 */
	public function __construct(Client $client, $options = null) {
		$this->_client = $client;
	}

/**
 * Add Authorization header to the request.
 *
 * @param \Cake\Network\Request $request The request object.
 * @param array $credentials Authentication credentials.
 * @return void
 * @see http://www.ietf.org/rfc/rfc2617.txt
 */
	public function authentication(Request $request, array $credentials) {
		if (!isset($credentials['username'], $credentials['password'])) {
			return;
		}
		if (!isset($credentials['realm'])) {
			$credentials = $this->_getServerInfo($request, $credentials);
		}
		if (!isset($credentials['realm'])) {
			return;
		}
		$value = $this->_generateHeader($request, $credentials);
		$request->header('Authorization', $value);
	}

/**
 * Retrieve information about the authentication
 *
 * Will get the realm and other tokens by performing
 * another request without authentication to get authentication
 * challenge.
 *
 * @param \Cake\Network\Request $request The request object.
 * @param array $credentials Authentication credentials.
 * @return Array modified credentials.
 */
	protected function _getServerInfo(Request $request, $credentials) {
		$response = $this->_client->get(
			$request->url(),
			[],
			['auth' => []]
		);

		if (!$response->header('WWW-Authenticate')) {
			return false;
		}
		preg_match_all(
			'@(\w+)=(?:(?:")([^"]+)"|([^\s,$]+))@',
			$response->header('WWW-Authenticate'),
			$matches,
			PREG_SET_ORDER
		);
		foreach ($matches as $match) {
			$credentials[$match[1]] = $match[2];
		}
		if (!empty($credentials['qop']) && empty($credentials['nc'])) {
			$credentials['nc'] = 1;
		}
		return $credentials;
	}

/**
 * Generate the header Authorization
 *
 * @param \Cake\Network\Request $request The request object.
 * @param array $credentials Authentication credentials.
 * @return string
 */
	protected function _generateHeader(Request $request, $credentials) {
		$path = parse_url($request->url(), PHP_URL_PATH);
		$a1 = md5($credentials['username'] . ':' . $credentials['realm'] . ':' . $credentials['password']);
		$a2 = md5($request->method() . ':' . $path);

		if (empty($credentials['qop'])) {
			$response = md5($a1 . ':' . $credentials['nonce'] . ':' . $a2);
		} else {
			$credentials['cnonce'] = uniqid();
			$nc = sprintf('%08x', $credentials['nc']++);
			$response = md5($a1 . ':' . $credentials['nonce'] . ':' . $nc . ':' . $credentials['cnonce'] . ':auth:' . $a2);
		}

		$authHeader = 'Digest ';
		$authHeader .= 'username="' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $credentials['username']) . '", ';
		$authHeader .= 'realm="' . $credentials['realm'] . '", ';
		$authHeader .= 'nonce="' . $credentials['nonce'] . '", ';
		$authHeader .= 'uri="' . $path . '", ';
		$authHeader .= 'response="' . $response . '"';
		if (!empty($credentials['opaque'])) {
			$authHeader .= ', opaque="' . $credentials['opaque'] . '"';
		}
		if (!empty($credentials['qop'])) {
			$authHeader .= ', qop="auth", nc=' . $nc . ', cnonce="' . $credentials['cnonce'] . '"';
		}
		return $authHeader;
	}

}
