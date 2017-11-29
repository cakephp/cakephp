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

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\Time;
use Cake\Network\Exception\InvalidCsrfTokenException;
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
     *  - `cookieName` = The name of the cookie to send.
     *  - `expiry` = How long the CSRF token should last. Defaults to browser session.
     *  - `secure` = Whether or not the cookie will be set with the Secure flag. Defaults to false.
     *  - `httpOnly` = Whether or not the cookie will be set with the HttpOnly flag. Defaults to false.
     *  - `field` = The form field to check. Changing this will also require configuring
     *    FormHelper.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'cookieName' => 'csrfToken',
        'expiry' => 0,
        'secure' => false,
        'httpOnly' => false,
        'field' => '_csrfToken',
    ];

    /**
     * Configuration
     *
     * @var array
     */
    protected $_config = [];

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
        $cookies = $request->getCookieParams();
        $cookieData = Hash::get($cookies, $this->_config['cookieName']);

        if (strlen($cookieData) > 0) {
            $params = $request->getAttribute('params');
            $params['_csrfToken'] = $cookieData;
            $request = $request->withAttribute('params', $params);
        }

        $method = $request->getMethod();
        if ($method === 'GET' && $cookieData === null) {
            $token = $this->_createToken();
            $request = $this->_addTokenToRequest($token, $request);
            $response = $this->_addTokenCookie($token, $request, $response);

            return $next($request, $response);
        }
        $request = $this->_validateAndUnsetTokenField($request);

        return $next($request, $response);
    }

    /**
     * Checks if the request is POST, PUT, DELETE or PATCH and validates the CSRF token
     *
     * @param \Cake\Http\ServerRequest $request The request object.
     * @return \Cake\Http\ServerRequest
     */
    protected function _validateAndUnsetTokenField(ServerRequest $request)
    {
        if (in_array($request->getMethod(), ['PUT', 'POST', 'DELETE', 'PATCH']) || $request->getData()) {
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
        return hash('sha512', Security::randomBytes(16), false);
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

        return $response->withCookie($this->_config['cookieName'], [
            'value' => $token,
            'expire' => $expiry->format('U'),
            'path' => $request->getAttribute('webroot'),
            'secure' => $this->_config['secure'],
            'httpOnly' => $this->_config['httpOnly'],
        ]);
    }

    /**
     * Validate the request data against the cookie token.
     *
     * @param \Cake\Http\ServerRequest $request The request to validate against.
     * @return void
     * @throws \Cake\Network\Exception\InvalidCsrfTokenException When the CSRF token is invalid or missing.
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

        if ($post !== $cookie && $header !== $cookie) {
            throw new InvalidCsrfTokenException(__d('cake', 'CSRF token mismatch.'));
        }
    }
}
