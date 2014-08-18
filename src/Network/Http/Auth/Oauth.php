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

use Cake\Error;
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
 * @param \Cake\Network\Request $request The request object.
 * @param array $credentials Authentication credentials.
 * @return void
 * @throws \Cake\Error\Exception On invalid signature types.
 */
	public function authentication(Request $request, array $credentials) {
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

			case 'PLAINTEXT':
				$value = $this->_plaintext($request, $credentials);
				break;

			default:
				throw new Error\Exception(sprintf('Unknown Oauth signature method %s', $credentials['method']));

		}
		$request->header('Authorization', $value);
	}

/**
 * Plaintext signing
 *
 * This method is **not** suitable for plain HTTP.
 * You should only ever use PLAINTEXT when dealing with SSL
 * services.
 *
 * @param \Cake\Network\Request $request The request object.
 * @param array $credentials Authentication credentials.
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
		];
		if (isset($credentials['realm'])) {
			$values['oauth_realm'] = $credentials['realm'];
		}
		$key = [$credentials['consumerSecret'], $credentials['tokenSecret']];
		$key = implode('&', $key);
		$values['oauth_signature'] = $key;

		return $this->_buildAuth($values);
	}

/**
 * Use HMAC-SHA1 signing.
 *
 * This method is suitable for plain HTTP or HTTPS.
 *
 * @param \Cake\Network\Request $request The request object.
 * @param array $credentials Authentication credentials.
 * @return string
 */
	protected function _hmacSha1($request, $credentials) {
		$nonce = isset($credentials['nonce']) ? $credentials['nonce'] : uniqid();
		$timestamp = isset($credentials['timestamp']) ? $credentials['timestamp'] : time();
		$values = [
			'oauth_version' => '1.0',
			'oauth_nonce' => $nonce,
			'oauth_timestamp' => $timestamp,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_token' => $credentials['token'],
			'oauth_consumer_key' => $credentials['consumerKey'],
		];
		$baseString = $this->baseString($request, $values);

		if (isset($credentials['realm'])) {
			$values['oauth_realm'] = $credentials['realm'];
		}
		$key = [$credentials['consumerSecret'], $credentials['tokenSecret']];
		$key = array_map([$this, '_encode'], $key);
		$key = implode('&', $key);

		$values['oauth_signature'] = base64_encode(
			hash_hmac('sha1', $baseString, $key, true)
		);
		return $this->_buildAuth($values);
	}

/**
 * Generate the Oauth basestring
 *
 * - Querystring, request data and oauth_* parameters are combined.
 * - Values are sorted by name and then value.
 * - Request values are concatenated and urlencoded.
 * - The request URL (without querystring) is normalized.
 * - The HTTP method, URL and request parameters are concatenated and returnned.
 *
 * @param \Cake\Network\Request $request The request object.
 * @param array $oauthValues Oauth values.
 * @return string
 */
	public function baseString($request, $oauthValues) {
		$parts = [
			$request->method(),
			$this->_normalizedUrl($request->url()),
			$this->_normalizedParams($request, $oauthValues),
		];
		$parts = array_map([$this, '_encode'], $parts);
		return implode('&', $parts);
	}

/**
 * Builds a normalized URL
 *
 * Section 9.1.2. of the Oauth spec
 *
 * @param string $url URL
 * @return string Normalized URL
 * @throws \Cake\Error\Exception On invalid URLs
 */
	protected function _normalizedUrl($url) {
		$parts = parse_url($url);
		if (!$parts) {
			throw new Error\Exception('Unable to parse URL');
		}
		$scheme = strtolower($parts['scheme'] ?: 'http');
		$defaultPorts = [
			'http' => 80,
			'https' => 443
		];
		if (isset($parts['port']) && $parts['port'] != $defaultPorts[$scheme]) {

			$parts['host'] .= ':' . $parts['port'];
		}
		$out = $scheme . '://';
		$out .= strtolower($parts['host']);
		$out .= $parts['path'];
		return $out;
	}

/**
 * Sorts and normalizes request data and oauthValues
 *
 * Section 9.1.1 of Oauth spec.
 *
 * - URL encode keys + values.
 * - Sort keys & values by byte value.
 *
 * @param \Cake\Network\Request $request The request object.
 * @param array $oauthValues Oauth values.
 * @return string sorted and normalized values
 */
	protected function _normalizedParams($request, $oauthValues) {
		$query = parse_url($request->url(), PHP_URL_QUERY);
		parse_str($query, $queryArgs);

		$post = [];
		$body = $request->body();
		$contentType = $request->header('content-type');

		if (is_array($body)) {
			$post = $body;
		}

		$args = array_merge($queryArgs, $oauthValues, $post);
		uksort($args, 'strcmp');

		$pairs = [];
		foreach ($args as $k => $val) {
			if (is_array($val)) {
				sort($val, SORT_STRING);
				foreach ($val as $nestedVal) {
					$pairs[] = "$k=$nestedVal";
				}
			} else {
				$pairs[] = "$k=$val";
			}
		}
		return implode('&', $pairs);
	}

/**
 * Builds the Oauth Authorization header value.
 *
 * @param array $data The oauth_* values to build
 * @return string
 */
	protected function _buildAuth($data) {
		$out = 'OAuth ';
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
 * @param string $value Value to encode.
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
