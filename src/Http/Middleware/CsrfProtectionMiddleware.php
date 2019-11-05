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

use ArrayAccess;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Exception\InvalidCsrfTokenException;
use Cake\Http\Response;
use Cake\Utility\Hash;
use Cake\Utility\Security;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Provides CSRF protection & validation.
 *
 * This middleware adds a CSRF token to a cookie. The cookie value is compared to
 * token in request data, or the X-CSRF-Token header on each PATCH, POST,
 * PUT, or DELETE request. This is known as "double submit cookie" technique.
 *
 * If the request data is missing or does not match the cookie data,
 * an InvalidCsrfTokenException will be raised.
 *
 * This middleware integrates with the FormHelper automatically and when
 * used together your forms will have CSRF tokens automatically added
 * when `$this->Form->create(...)` is used in a view.
 *
 * @see https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html#double-submit-cookie
 */
class CsrfProtectionMiddleware implements MiddlewareInterface
{
    /**
     * Config for the CSRF handling.
     *
     *  - `cookieName` The name of the cookie to send.
     *  - `expiry` A strotime compatible value of how long the CSRF token should last.
     *    Defaults to browser session.
     *  - `secure` Whether or not the cookie will be set with the Secure flag. Defaults to false.
     *  - `httpOnly` Whether or not the cookie will be set with the HttpOnly flag. Defaults to false.
     *  - `field` The form field to check. Changing this will also require configuring
     *    FormHelper.
     *
     * @var array
     */
    protected $_config = [
        'cookieName' => 'csrfToken',
        'expiry' => 0,
        'secure' => false,
        'httpOnly' => false,
        'field' => '_csrfToken',
    ];

    /**
     * Callback for deciding whether or not to skip the token check for particular request.
     *
     * CSRF protection token check will be skipped if the callback returns `true`.
     *
     * @var callable|null
     */
    protected $whitelistCallback;

    /**
     * Constructor
     *
     * @param array $config Config options. See $_config for valid keys.
     */
    public function __construct(array $config = [])
    {
        $this->_config = $config + $this->_config;
    }

    /**
     * Checks and sets the CSRF token depending on the HTTP verb.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $hasData = in_array($method, ['PUT', 'POST', 'DELETE', 'PATCH'], true)
            || $request->getParsedBody();

        if (
            $hasData
            && $this->whitelistCallback !== null
            && call_user_func($this->whitelistCallback, $request) === true
        ) {
            $request = $this->_unsetTokenField($request);

            return $handler->handle($request);
        }

        $cookies = $request->getCookieParams();
        $cookieData = Hash::get($cookies, $this->_config['cookieName']);

        if ($cookieData !== null && strlen($cookieData) > 0) {
            $request = $request->withAttribute('csrfToken', $cookieData);
        }

        if ($method === 'GET' && $cookieData === null) {
            $token = $this->_createToken();
            $request = $request->withAttribute('csrfToken', $token);
            /** @var \Cake\Http\Response $response */
            $response = $handler->handle($request);

            return $this->_addTokenCookie($token, $request, $response);
        }

        if ($hasData) {
            $this->_validateToken($request);
            $request = $this->_unsetTokenField($request);
        }

        return $handler->handle($request);
    }

    /**
     * Set callback for allowing to skip token check for particular request.
     *
     * The callback will receive request instance as argument and must return
     * `true` if you want to skip token check for the current request.
     *
     * @param callable $callback A callable.
     * @return $this
     */
    public function whitelistCallback(callable $callback)
    {
        $this->whitelistCallback = $callback;

        return $this;
    }

    /**
     * Remove CSRF protection token from request data.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request object.
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function _unsetTokenField(ServerRequestInterface $request): ServerRequestInterface
    {
        $body = $request->getParsedBody();
        if (is_array($body)) {
            unset($body[$this->_config['field']]);
            $request = $request->withParsedBody($body);
        }

        return $request;
    }

    /**
     * Create a new token to be used for CSRF protection
     *
     * @return string
     */
    protected function _createToken(): string
    {
        return hash('sha512', Security::randomBytes(16), false);
    }

    /**
     * Add a CSRF token to the response cookies.
     *
     * @param string $token The token to add.
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to validate against.
     * @param \Cake\Http\Response $response The response.
     * @return \Cake\Http\Response $response Modified response.
     */
    protected function _addTokenCookie(string $token, ServerRequestInterface $request, Response $response): Response
    {
        $time = $this->_config['expiry'];
        if (is_string($time)) {
            $time = strtotime($time);
        }
        $expiry = new DateTimeImmutable('@' . $time);

        $cookie = new Cookie(
            $this->_config['cookieName'],
            $token,
            $expiry,
            $request->getAttribute('webroot'),
            '',
            (bool)$this->_config['secure'],
            (bool)$this->_config['httpOnly']
        );

        return $response->withCookie($cookie);
    }

    /**
     * Validate the request data against the cookie token.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to validate against.
     * @return void
     * @throws \Cake\Http\Exception\InvalidCsrfTokenException When the CSRF token is invalid or missing.
     */
    protected function _validateToken(ServerRequestInterface $request): void
    {
        $cookie = Hash::get($request->getCookieParams(), $this->_config['cookieName']);

        if (!$cookie) {
            throw new InvalidCsrfTokenException(__d('cake', 'Missing CSRF token cookie'));
        }

        $body = $request->getParsedBody();
        if (is_array($body) || $body instanceof ArrayAccess) {
            $post = Hash::get($body, $this->_config['field']);
            if (Security::constantEquals($post, $cookie)) {
                return;
            }
        }

        $header = $request->getHeaderLine('X-CSRF-Token');
        if (Security::constantEquals($header, $cookie)) {
            return;
        }

        throw new InvalidCsrfTokenException(__d('cake', 'Missing CSRF token body'));
    }
}
