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
namespace Cake\Http\Client\Auth;

use Cake\Core\Exception\Exception;
use Cake\Http\Client\Request;
use Cake\Utility\Security;

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
class Oauth
{

    /**
     * Add headers for Oauth authorization.
     *
     * @param \Cake\Network\Http\Request $request The request object.
     * @param array $credentials Authentication credentials.
     * @return \Cake\Network\Http\Request The updated request.
     * @throws \Cake\Core\Exception\Exception On invalid signature types.
     */
    public function authentication(Request $request, array $credentials)
    {
        if (!isset($credentials['consumerKey'])) {
            return $request;
        }
        if (empty($credentials['method'])) {
            $credentials['method'] = 'hmac-sha1';
        }
        $credentials['method'] = strtoupper($credentials['method']);
        switch ($credentials['method']) {
            case 'HMAC-SHA1':
                $hasKeys = isset(
                    $credentials['consumerSecret'],
                    $credentials['token'],
                    $credentials['tokenSecret']
                );
                if (!$hasKeys) {
                    return $request;
                }
                $value = $this->_hmacSha1($request, $credentials);
                break;

            case 'RSA-SHA1':
                if (!isset($credentials['privateKey'])) {
                    return $request;
                }
                $value = $this->_rsaSha1($request, $credentials);
                break;

            case 'PLAINTEXT':
                $hasKeys = isset(
                    $credentials['consumerSecret'],
                    $credentials['token'],
                    $credentials['tokenSecret']
                );
                if (!$hasKeys) {
                    return $request;
                }
                $value = $this->_plaintext($request, $credentials);
                break;

            default:
                throw new Exception(sprintf('Unknown Oauth signature method %s', $credentials['method']));
        }
        return $request->withHeader('Authorization', $value);
    }

    /**
     * Plaintext signing
     *
     * This method is **not** suitable for plain HTTP.
     * You should only ever use PLAINTEXT when dealing with SSL
     * services.
     *
     * @param \Cake\Network\Http\Request $request The request object.
     * @param array $credentials Authentication credentials.
     * @return string Authorization header.
     */
    protected function _plaintext($request, $credentials)
    {
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
     * @param \Cake\Network\Http\Request $request The request object.
     * @param array $credentials Authentication credentials.
     * @return string
     */
    protected function _hmacSha1($request, $credentials)
    {
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
     * Use RSA-SHA1 signing.
     *
     * This method is suitable for plain HTTP or HTTPS.
     *
     * @param \Cake\Network\Http\Request $request The request object.
     * @param array $credentials Authentication credentials.
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function _rsaSha1($request, $credentials)
    {
        if (!function_exists('openssl_pkey_get_private')) {
            throw new \RuntimeException('RSA-SHA1 signature method requires the OpenSSL extension.');
        }

        $nonce = isset($credentials['nonce']) ? $credentials['nonce'] : Security::randomBytes(16);
        $timestamp = isset($credentials['timestamp']) ? $credentials['timestamp'] : time();
        $values = [
            'oauth_version' => '1.0',
            'oauth_nonce' => $nonce,
            'oauth_timestamp' => $timestamp,
            'oauth_signature_method' => 'RSA-SHA1',
            'oauth_consumer_key' => $credentials['consumerKey'],
        ];
        if (isset($credentials['consumerSecret'])) {
            $values['oauth_consumer_secret'] = $credentials['consumerSecret'];
        }
        if (isset($credentials['token'])) {
            $values['oauth_token'] = $credentials['token'];
        }
        if (isset($credentials['tokenSecret'])) {
            $values['oauth_token_secret'] = $credentials['tokenSecret'];
        }
        $baseString = $this->baseString($request, $values);

        if (isset($credentials['realm'])) {
            $values['oauth_realm'] = $credentials['realm'];
        }

        if (is_resource($credentials['privateKey'])) {
            $resource = $credentials['privateKey'];
            $privateKey = stream_get_contents($resource);
            rewind($resource);
            $credentials['privateKey'] = $privateKey;
        }

        $credentials += [
            'privateKeyPassphrase' => null,
        ];
        if (is_resource($credentials['privateKeyPassphrase'])) {
            $resource = $credentials['privateKeyPassphrase'];
            $passphrase = stream_get_line($resource, 0, PHP_EOL);
            rewind($resource);
            $credentials['privateKeyPassphrase'] = $passphrase;
        }
        $privateKey = openssl_pkey_get_private($credentials['privateKey'], $credentials['privateKeyPassphrase']);
        $signature = '';
        openssl_sign($baseString, $signature, $privateKey);
        openssl_free_key($privateKey);

        $values['oauth_signature'] = base64_encode($signature);

        return $this->_buildAuth($values);
    }

    /**
     * Generate the Oauth basestring
     *
     * - Querystring, request data and oauth_* parameters are combined.
     * - Values are sorted by name and then value.
     * - Request values are concatenated and urlencoded.
     * - The request URL (without querystring) is normalized.
     * - The HTTP method, URL and request parameters are concatenated and returned.
     *
     * @param \Cake\Network\Http\Request $request The request object.
     * @param array $oauthValues Oauth values.
     * @return string
     */
    public function baseString($request, $oauthValues)
    {
        $parts = [
            $request->getMethod(),
            $this->_normalizedUrl($request->getUri()),
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
     * @param Psr\Http\Message\UriInterface $uri Uri object to build a normalized version of.
     * @return string Normalized URL
     */
    protected function _normalizedUrl($uri)
    {
        $scheme = $uri->getScheme();
        $defaultPorts = [
            'http' => 80,
            'https' => 443
        ];
        $port = $uri->getPort();
        if ($port && $port != $defaultPorts[$scheme]) {
            $parts['host'] .= ':' . $port;
        }
        $out = $scheme . '://';
        $out .= strtolower($uri->getHost());
        $out .= $uri->getPath();
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
     * @param \Cake\Network\Http\Request $request The request object.
     * @param array $oauthValues Oauth values.
     * @return string sorted and normalized values
     */
    protected function _normalizedParams($request, $oauthValues)
    {
        $query = parse_url($request->url(), PHP_URL_QUERY);
        parse_str($query, $queryArgs);

        $post = [];
        $body = $request->body();
        if (is_string($body) && $request->getHeaderLine('content-type') === 'application/x-www-form-urlencoded') {
            parse_str($body, $post);
        }
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
    protected function _buildAuth($data)
    {
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
    protected function _encode($value)
    {
        return str_replace(
            '+',
            ' ',
            str_replace('%7E', '~', rawurlencode($value))
        );
    }
}
