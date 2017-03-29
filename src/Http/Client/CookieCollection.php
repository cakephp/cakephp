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

use Cake\Http\Cookie\CookieCollection as BaseCollection;

/**
 * Container class for cookies used in Http\Client.
 *
 * Provides cookie jar like features for storing cookies between
 * requests, as well as appending cookies to new requests.
 *
 * @deprecated 3.5.0 Use Cake\Http\Cookie\CookieCollection instead.
 */
class CookieCollection extends BaseCollection
{

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

        $header = $response->getHeader('Set-Cookie');
        $cookies = $this->parseSetCookieHeader($header);
        $cookies = $this->setRequestDefaults($cookies, $host, $path);
        foreach ($cookies as $cookie) {
            $this->cookies[$cookie->getId()] = $cookie;
        }
        $this->removeExpiredCookies($host, $path);
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

        return $this->findMatchingCookies($scheme, $host, $path);
    }

    /**
     * Get all the stored cookies.
     *
     * @return array
     */
    public function getAll()
    {
        $out = [];
        foreach ($this->cookies as $cookie) {
            $out[] = [
                'name' => $cookie->getName(),
                'value' => $cookie->getValue(),
                'path' => $cookie->getPath(),
                'domain' => $cookie->getDomain(),
                'secure' => $cookie->isSecure(),
                'httponly' => $cookie->isHttpOnly(),
                'expires' => $cookie->getExpiry()
            ];
        }

        return $out;
    }
}

// @deprecated Add backwards compat alias.
class_alias('Cake\Http\Client\CookieCollection', 'Cake\Network\Http\CookieCollection');
