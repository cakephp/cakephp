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

use Cake\Error;
use Cake\Network\Http\Client;
use Cake\Network\Http\Request;

/**
 * Oauth 1 authentication strategy for Cake\Network\Http\Client
 *
 * This object does not handle getting Oauth access tokens from the service
 * provider. It only handles make client requests *after* you have obtained the Oauth
 * tokens.
 *
 * Generally not directly constructed, but instead used by Cake\Network\Http\Client
 * when $options['auth']['type'] is 'oauth'
 */
class Oauth {

/**
 * Add headers for Oauth authorization.
 *
 * @param Request $request
 * @param array $options
 * @return void
 */
	public function authentication(Request $request, $credentials) {
		$hasKeys = isset(
			$credentials['consumerSecret'],
			$credentials['consumerKey'],
			$credentials['token'],
			$credentials['tokenSecret']
		);
		if (!$hasKeys) {
			return;
		}
		if (empty($credentials['method'])) {
			$credentials['method'] = 'hmac-sha1';
		}
		$credentials['method'] = strtoupper($credentials['method']);
		switch ($credentials['method']) {
			case 'HMAC-SHA1':
				$value = $this->_hmacSha1($request, $credentials);
				break;

			case 'RSA-SHA1':
				$value = $this->_rsaSha1($request, $credentials);
				break;

			case 'PLAINTEXT':
				$value = $this->_plaintext($request, $credentials);
				break;

			default:
				throw new Error\Exception(__d('cake_dev', 'Unknown Oauth signature method %s', $credentials['method']));

		}
		$request->header('Authorization', $value);
	}

/**
 * Plaintext signing
 *
 * @param Request $request
 * @param array $credentials
 * @return string Authorization header.
 */
	protected function _plaintext($request, $credentials) {
		$values = [
			'oauth_version' => '1.0',
			'oauth_nonce' => uniqid(),
			'oauth_timestamp' => time(),
			'oauth_signature_method' => 'PLAINTEXT',
			'oauth_token' => $credentials['token'],
			'oauth_consumer_key' => $credentials['consumerKey'],
			'oauth_signature' => $credentials['consumerSecret'] . '&' . $credentials['tokenSecret']
		];
		return $this->_buildAuth($values);
	}

	protected function _hmacSha1($request, $credentials) {

	}

	protected function _rsaSha1($request, $credentials) {

	}

	protected function _buildAuth($data) {
		$out = 'Oauth ';
		$params = [];
		foreach ($data as $key => $value) {
			$params[] = $key . '="' . $this->_encode($value) . '"';
		}
		$out .= implode(',', $params);
		return $out;
	}

/**
 * URL Encodes a value based on rules of rfc3986
 *
 * @param string $value
 * @return string
 */
	protected function _encode($value) {
		return str_replace(
			'+',
			' ',
			str_replace('%7E', '~', rawurlencode($value))
		);
	}

}
