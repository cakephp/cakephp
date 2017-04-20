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

use Cake\I18n\Time;
use Cake\Network\Exception\InvalidCsrfTokenException;
use Cake\Utility\Security;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Provides CSRF protection & validation.
 *
 * This component adds a CSRF token to a cookie. The cookie value is compared to
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
     *  - cookieName = The name of the cookie to send.
     *  - expiry = How long the CSRF token should last. Defaults to browser session.
     *  - secure = Whether or not the cookie will be set with the Secure flag. Defaults to false.
     *  - httpOnly = Whether or not the cookie will be set with the HttpOnly flag. Defaults to false.
     *  - field = The form field to check. Changing this will also require configuring
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
     * @param array $config Config options
     */
    public function __construct(array $config = [])
    {
        $this->_config = $config + $this->_defaultConfig;
    }

    /**
     * Serve assets if the path matches one.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $cookies = $request->getCookieParams();
        $cookieData = null;
        if (isset($cookies[$this->_config['cookieName']])) {
            $cookieData = $cookies[$this->_config['cookieName']];
        }

        if (!empty($cookieData)) {
            $params = $request->getAttribute('params');
            $params['_csrfToken'] = $cookieData;
            $request = $request->withAttribute('params', $params);
        }

        $requested = $request->getParam('requested');
        if ($requested === 1) {
            return $next($request, $response);
        }

        $method = $request->getMethod();
        if ($method === 'GET' && $cookieData === null) {
            $this->_setToken($request, $response);

            return $next($request, $response);
        }

        $request = $this->_validateAndUnsetTokenField($request);

        return $next($request, $response);
    }

    /**
     * Checks if the request is POST, PUT, DELETE or PATCH and validates the CSRF token
     *
     * @param \Cake\Http\ServerRequest $request The request object.
     * @return void
     */
    protected function _validateAndUnsetTokenField(ServerRequestInterface $request)
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
     * Set the token in the response.
     *
     * Also sets the request->params['_csrfToken'] so the newly minted
     * token is available in the request data.
     *
     * @param \Cake\Http\ServerRequest $request The request object.
     * @param \Cake\Http\Response $response The response object.
     * @return void
     */
    protected function _setToken(ServerRequestInterface &$request, ResponseInterface &$response)
    {
        $expiry = new Time($this->_config['expiry']);
        $value = hash('sha512', Security::randomBytes(16), false);

        $params = $request->getAttribute('params');
        $params['_csrfToken'] = $value;
        $request = $request->withAttribute('params', $params);

        $response = $response->withCookie($this->_config['cookieName'], [
            'value' => $value,
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
     * @throws \Cake\Network\Exception\InvalidCsrfTokenException when the CSRF token is invalid or missing.
     * @return void
     */
    protected function _validateToken(ServerRequestInterface $request)
    {
        $cookies = $request->getCookieParams();
        $cookie = isset($cookies[$this->_config['cookieName']]) ? $cookies[$this->_config['cookieName']] : null;
        $post = $request->getData($this->_config['field']);
        $header = $request->getHeaderLine('X-CSRF-Token');

        if (!$cookie) {
            throw new InvalidCsrfTokenException(__d('cake', 'Missing CSRF token cookie'));
        }

        if ($post !== $cookie && $header !== $cookie) {
            throw new InvalidCsrfTokenException(__d('cake', 'CSRF token mismatch.'));
        }
    }
}
