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

use Cake\Http\Cookie\Cookie;
use Cake\Http\Exception\InvalidCsrfTokenException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\Time;
use Cake\Utility\Hash;
use Cake\Utility\Security;

/**
 * Provides CSRF protection & validation.
 *
 * This middleware adds a CSRF token to a cookie. The cookie value is compared to
 * request data, or the X-CSRF-Token header on each PATCH, POST,
 * PUT, or DELETE request.
 *
 * If the request data is missing or does not match the cookie data,
 * an InvalidCsrfTokenException will be raised.
 *
 * This middleware integrates with the FormHelper automatically and when
 * used together your forms will have CSRF tokens automatically added
 * when `$this->Form->create(...)` is used in a view.
 */
class CsrfProtectionMiddleware
{
    /**
     * Default config for the CSRF handling.
     *
     *  - `cookieName` The name of the cookie to send.
     *  - `expiry` A strotime compatible value of how long the CSRF token should last.
     *    Defaults to browser session.
     *  - `secure` Whether or not the cookie will be set with the Secure flag. Defaults to false.
     *  - `httpOnly` Whether or not the cookie will be set with the HttpOnly flag. Defaults to false.
     *  - `samesite` Value for "SameSite" attribute. Default to null.
     *  - `field` The form field to check. Changing this will also require configuring
     *    FormHelper.
     *  - `verifyTokenSource` Generate and verify tokens that include the application salt
     *    value. This prevents tokens from being manipulated by an attacker via XSS or physical 
     *    access. This behavior is disabled by default as it is not cross compatible with tokens
     *    created in earlier versions of CakePHP. It is recommended that you enable this setting
     *    if possible as it is the default in 4.x.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'cookieName' => 'csrfToken',
        'expiry' => 0,
        'secure' => false,
        'httpOnly' => false,
        'samesite' => null,
        'field' => '_csrfToken',
        'verifyTokenSource' => false,
    ];

    /**
     * Configuration
     *
     * @var array
     */
    protected $_config = [];

    /**
     * Callback for deciding whether or not to skip the token check for particular request.
     *
     * CSRF protection token check will be skipped if the callback returns `true`.
     *
     * @var callable|null
     */
    protected $whitelistCallback;

    /**
     * @var int
     */
    const TOKEN_VALUE_LENGTH = 16;

    /**
     * Constructor
     *
     * @param array $config Config options. See $_defaultConfig for valid keys.
     */
    public function __construct(array $config = [])
    {
        $this->_config = $config + $this->_defaultConfig;
    }

    /**
     * Checks and sets the CSRF token depending on the HTTP verb.
     *
     * @param \Cake\Http\ServerRequest $request The request.
     * @param \Cake\Http\Response $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Cake\Http\Response A response
     */
    public function __invoke(ServerRequest $request, Response $response, $next)
    {
        if (
            $this->whitelistCallback !== null
            && call_user_func($this->whitelistCallback, $request) === true
        ) {
            return $next($request, $response);
        }

        $cookies = $request->getCookieParams();
        $cookieData = Hash::get($cookies, $this->_config['cookieName']);

        if (is_string($cookieData) && strlen($cookieData) > 0) {
            $params = $request->getAttribute('params');
            $params['_csrfToken'] = $cookieData;
            $request = $request->withAttribute('params', $params);
        }

        $method = $request->getMethod();
        if ($method === 'GET' && $cookieData === null) {
            $token = $this->createToken();
            $request = $this->_addTokenToRequest($token, $request);
            $response = $this->_addTokenCookie($token, $request, $response);

            return $next($request, $response);
        }
        $request = $this->_validateAndUnsetTokenField($request);

        return $next($request, $response);
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
     * Checks if the request is POST, PUT, DELETE or PATCH and validates the CSRF token
     *
     * @param \Cake\Http\ServerRequest $request The request object.
     * @return \Cake\Http\ServerRequest
     */
    protected function _validateAndUnsetTokenField(ServerRequest $request)
    {
        if (in_array($request->getMethod(), ['PUT', 'POST', 'DELETE', 'PATCH'], true) || $request->getData()) {
            $this->_validateToken($request);
            $body = $request->getParsedBody();
            if (is_array($body)) {
                unset($body[$this->_config['field']]);
                $request = $request->withParsedBody($body);
            }
        }

        return $request;
    }

    /**
     * Create a new token to be used for CSRF protection
     *
     * @return string
     */
    protected function _createToken()
    {
        return $this->createToken();
    }

    /**
     * Create a new token to be used for CSRF protection.
     *
     * @return string
     */
    public function createToken()
    {
        $value = Security::randomBytes(static::TOKEN_VALUE_LENGTH);
        if (!$this->_config['verifyTokenSource']) {
            return hash('sha512', $value, false);
        }

        return $value . hash_hmac('sha1', $value, Security::getSalt());
    }

    /**
     * Add a CSRF token to the request parameters.
     *
     * @param string $token The token to add.
     * @param \Cake\Http\ServerRequest $request The request to augment
     * @return \Cake\Http\ServerRequest Modified request
     */
    protected function _addTokenToRequest($token, ServerRequest $request)
    {
        $params = $request->getAttribute('params');
        $params['_csrfToken'] = $token;

        return $request->withAttribute('params', $params);
    }

    /**
     * Add a CSRF token to the response cookies.
     *
     * @param string $token The token to add.
     * @param \Cake\Http\ServerRequest $request The request to validate against.
     * @param \Cake\Http\Response $response The response.
     * @return \Cake\Http\Response $response Modified response.
     */
    protected function _addTokenCookie($token, ServerRequest $request, Response $response)
    {
        $expiry = new Time($this->_config['expiry']);

        $cookie = new Cookie(
            $this->_config['cookieName'],
            $token,
            $expiry,
            $request->getAttribute('webroot'),
            '',
            (bool)$this->_config['secure'],
            (bool)$this->_config['httpOnly'],
            isset($this->_config['samesite']) ? $this->_config['samesite'] : $this->_defaultConfig['samesite']
        );

        return $response->withCookie($cookie);
    }

    /**
     * Validate the request data against the cookie token.
     *
     * @param \Cake\Http\ServerRequest $request The request to validate against.
     * @return void
     * @throws \Cake\Http\Exception\InvalidCsrfTokenException When the CSRF token is invalid or missing.
     */
    protected function _validateToken(ServerRequest $request)
    {
        $cookies = $request->getCookieParams();
        $cookie = Hash::get($cookies, $this->_config['cookieName']);
        $post = Hash::get($request->getParsedBody(), $this->_config['field']);
        $header = $request->getHeaderLine('X-CSRF-Token');

        if (!$cookie) {
            throw new InvalidCsrfTokenException(__d('cake', 'Missing CSRF token cookie'));
        }

        if ($this->_config['verifyTokenSource']) {
            // This path validates that the token was generated by our application.
            if ($this->_compareToken($post, $cookie) || $this->_compareToken($header, $cookie)) {
                return;
            }

            throw new InvalidCsrfTokenException(__d('cake', 'CSRF token mismatch.'));
        }

        // Backwards compatibility mode. This path compares tokens as opaque strings.
        if (Security::constantEquals($post, $cookie) || Security::constantEquals($header, $cookie)) {
            return;
        }

        throw new InvalidCsrfTokenException(__d('cake', 'CSRF token mismatch.'));
    }

    /**
     * Ensure that the request token matches the cookie value and that
     * both were generated by us.
     *
     * @param mixed $post The request token.
     * @param mixed $cookie The cookie token.
     * @return bool
     */
    protected function _compareToken($post, $cookie): bool
    {
        if (!is_string($post)) {
            $post = '';
        }
        if (!is_string($cookie)) {
            $cookie = '';
        }
        $postKey = (string)substr($post, 0, static::TOKEN_VALUE_LENGTH);
        $postHmac = (string)substr($post, static::TOKEN_VALUE_LENGTH);
        $cookieKey = (string)substr($cookie, 0, static::TOKEN_VALUE_LENGTH);
        $cookieHmac = (string)substr($cookie, static::TOKEN_VALUE_LENGTH);

        // Put all checks in a list
        // so they all burn time reducing timing attack window.
        $checks = [
            hash_equals($postKey, $cookieKey),
            hash_equals($postHmac, $cookieHmac),
            hash_equals(
                $postHmac,
                hash_hmac('sha1', $postKey, Security::getSalt())
            ),
        ];

        foreach ($checks as $check) {
            if ($check !== true) {
                return false;
            }
        }

        return true;
    }
}
