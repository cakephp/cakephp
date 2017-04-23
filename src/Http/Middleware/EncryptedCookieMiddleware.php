<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Middleware;

use Cake\Http\Cookie\CookieCollection;
use Cake\Http\Response;
use Cake\Utility\CookieCryptTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Middlware for encrypting & decrypting cookies.
 *
 * This middleware layer will encrypt/decrypt the named cookies with the given key
 * and cipher type. To support multiple keys/cipher types use this middleware multiple
 * times.
 *
 * Cookies in request data will be decrypted, while cookies in response headers will
 * be encrypted automatically. If the response is a Cake\Http\Response, the cookie
 * data set with `withCookie()` and `cookie()`` will also be encrypted.
 *
 * The encryption types and padding are compatible with those used by CookieComponent
 * for backwards compatibility.
 */
class EncryptedCookieMiddleware
{
    use CookieCryptTrait;

    /**
     * The list of cookies to encrypt/decrypt
     * @var array
     */
    protected $cookieNames;

    /**
     * Encrpytion key to use.
     *
     * @var string
     */
    protected $key;

    /**
     * Encryption type.
     *
     * @var string
     */
    protected $cipherType;

    /**
     * Constructor
     *
     * @param array $cookieNames The list of cookie names that should have their values encrypted.
     * @param string $key The encryption key to use.
     * @param string $cipherType The cipher type to use. Defaults to 'aes', but can also be 'rijndael' for
     *   backwards compatibility.
     */
    public function __construct(array $cookieNames, $key, $cipherType = 'aes')
    {
        $this->cookieNames = $cookieNames;
        $this->key = $key;
        $this->cipherType = $cipherType;
    }

    /**
     * Apply cookie encryption/decryption.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next The next middleware to call.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        if ($request->getCookieParams()) {
            $request = $this->decodeCookies($request);
        }
        $response = $next($request, $response);
        if ($response->hasHeader('Set-Cookie')) {
            $response = $this->encodeSetCookieHeader($response);
        }
        if ($response instanceof Response) {
            $response = $this->encodeCookies($response);
        }

        return $response;
    }

    /**
     * Fetch the cookie encryption key.
     *
     * Part of the CookieCryptTrait implementation.
     *
     * @return string
     */
    protected function _getCookieEncryptionKey()
    {
        return $this->key;
    }

    /**
     * Decode cookies from the request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to decode cookies from.
     * @return \Psr\Http\Message\ServerRequestInterface Updated request with decoded cookies.
     */
    protected function decodeCookies(ServerRequestInterface $request)
    {
        $cookies = $request->getCookieParams();
        foreach ($this->cookieNames as $name) {
            if (isset($cookies[$name])) {
                $cookies[$name] = $this->_decrypt($cookies[$name], $this->cipherType, $this->key);
            }
        }

        return $request->withCookieParams($cookies);
    }

    /**
     * Encode cookies from a response's CookieCollection.
     *
     * @param \Cake\Http\Response $response The response to encode cookies in.
     * @return \Cake\Http\Response Updated response with encoded cookies.
     */
    protected function encodeCookies(Response $response)
    {
        $cookies = $response->getCookieCollection();
        foreach ($cookies as $cookie) {
            if (in_array($cookie->getName(), $this->cookieNames, true)) {
                $value = $this->_encrypt($cookie->getValue(), $this->cipherType);
                $response = $response->withCookie($cookie->withValue($value));
            }
        }

        return $response;
    }

    /**
     * Encode cookies from a response's Set-Cookie header
     *
     * @param \Psr\Http\Message\ResponseInterface $response The response to encode cookies in.
     * @return \Psr\Http\Message\ResponseInterface Updated response with encoded cookies.
     */
    protected function encodeSetCookieHeader(ResponseInterface $response)
    {
        $cookies = CookieCollection::createFromHeader($response->getHeader('Set-Cookie'));
        $header = [];
        foreach ($cookies as $cookie) {
            if (in_array($cookie->getName(), $this->cookieNames, true)) {
                $value = $this->_encrypt($cookie->getValue(), $this->cipherType);
                $cookie = $cookie->withValue($value);
            }
            $header[] = $cookie->toHeaderValue();
        }

        return $response->withHeader('Set-Cookie', $header);
    }
}
