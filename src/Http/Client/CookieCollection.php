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
namespace Cake\Http\Client;

/**
 * Container class for cookies used in Http\Client.
 *
 * Provides cookie jar like features for storing cookies between
 * requests, as well as appending cookies to new requests.
 */
class CookieCollection
{

    /**
     * The cookies stored in this jar.
     *
     * @var array
     */
    protected $_cookies = [];

    /**
     * Store the cookies from a response.
     *
     * Store the cookies that haven't expired. If a cookie has been expired
     * and is currently stored, it will be removed.
     *
     * @param \Cake\Http\Client\Response $response The response to read cookies from
     * @param string $url The request URL used for default host/path values.
     * @return void
     */
    public function store(Response $response, $url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $path = $path ?: '/';

        $cookies = $response->cookies();
        foreach ($cookies as $name => $cookie) {
            if (empty($cookie['domain'])) {
                $cookie['domain'] = $host;
            }
            if (empty($cookie['path'])) {
                $cookie['path'] = $path;
            }
            $key = implode(';', [$cookie['name'], $cookie['domain'], $cookie['path']]);

            $expires = isset($cookie['expires']) ? $cookie['expires'] : false;
            $expiresTime = false;
            if ($expires) {
                $expiresTime = strtotime($expires);
            }
            if ($expiresTime && $expiresTime <= time()) {
                unset($this->_cookies[$key]);
                continue;
            }
            $this->_cookies[$key] = $cookie;
        }
    }

    /**
     * Get stored cookies for a URL.
     *
     * Finds matching stored cookies and returns a simple array
     * of name => value
     *
     * @param string $url The URL to find cookies for.
     * @return array
     */
    public function get($url)
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);

        $out = [];
        foreach ($this->_cookies as $cookie) {
            if ($scheme === 'http' && !empty($cookie['secure'])) {
                continue;
            }
            if (strpos($path, $cookie['path']) !== 0) {
                continue;
            }
            $leadingDot = $cookie['domain'][0] === '.';
            if ($leadingDot) {
                $cookie['domain'] = ltrim($cookie['domain'], '.');
            }

            $pattern = '/' . preg_quote(substr($cookie['domain'], 1), '/') . '$/';
            if (!preg_match($pattern, $host)) {
                continue;
            }

            $out[$cookie['name']] = $cookie['value'];
        }

        return $out;
    }

    /**
     * Get all the stored cookies.
     *
     * @return array
     */
    public function getAll()
    {
        return array_values($this->_cookies);
    }
}

// @deprecated Add backwards compat alias.
class_alias('Cake\Http\Client\CookieCollection', 'Cake\Network\Http\CookieCollection');
