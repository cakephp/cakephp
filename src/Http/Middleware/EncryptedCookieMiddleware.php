<?php
declare(strict_types=1);

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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
class EncryptedCookieMiddleware implements MiddlewareInterface
{
    use CookieCryptTrait;

    /**
     * The list of cookies to encrypt/decrypt
     *
     * @var string[]
     */
    protected $cookieNames;

    /**
     * Encryption key to use.
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
     * @param string[] $cookieNames The list of cookie names that should have their values encrypted.
     * @param string $key The encryption key to use.
     * @param string $cipherType The cipher type to use. Defaults to 'aes'.
     */
    public function __construct(array $cookieNames, string $key, string $cipherType = 'aes')
    {
        $this->cookieNames = $cookieNames;
        $this->key = $key;
        $this->cipherType = $cipherType;
    }

    /**
     * Apply cookie encryption/decryption.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getCookieParams()) {
            $request = $this->decodeCookies($request);
        }

        $response = $handler->handle($request);
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
    protected function _getCookieEncryptionKey(): string
    {
        return $this->key;
    }

    /**
     * Decode cookies from the request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to decode cookies from.
     * @return \Psr\Http\Message\ServerRequestInterface Updated request with decoded cookies.
     */
    protected function decodeCookies(ServerRequestInterface $request): ServerRequestInterface
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
    protected function encodeCookies(Response $response): Response
    {
        /** @var \Cake\Http\Cookie\CookieInterface[] $cookies */
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
    protected function encodeSetCookieHeader(ResponseInterface $response): ResponseInterface
    {
        /** @var \Cake\Http\Cookie\CookieInterface[] $cookies */
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
