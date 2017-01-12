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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\I18n\Time;
use Cake\Network\Exception\InvalidCsrfTokenException;
use Cake\Network\Response;
use Cake\Utility\Security;

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
 * This component integrates with the FormHelper automatically and when
 * used together your forms will have CSRF tokens automatically added
 * when `$this->Form->create(...)` is used in a view.
 */
class CsrfComponent extends Component
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
     * Startup callback.
     *
     * Validates the CSRF token for POST data. If
     * the request is a GET request, and the cookie value is absent a cookie will be set.
     *
     * Once a cookie is set it will be copied into request->params['_csrfToken']
     * so that application and framework code can easily access the csrf token.
     *
     * RequestAction requests do not get checked, nor will
     * they set a cookie should it be missing.
     *
     * @param \Cake\Event\Event $event Event instance.
     * @return void
     */
    public function startup(Event $event)
    {
        $controller = $event->getSubject();
        $request = $controller->request;
        $response = $controller->response;
        $cookieName = $this->_config['cookieName'];

        /* @var \Cake\Network\Request $request */
        $cookieData = $request->getCookie($cookieName);
        if ($cookieData) {
            $request->params['_csrfToken'] = $cookieData;
        }

        if ($request->is('requested')) {
            return;
        }

        if ($request->is('get') && $cookieData === null) {
            $this->_setCookie($request, $response);
        }
        if ($request->is(['put', 'post', 'delete', 'patch']) || $request->getData()) {
            $this->_validateToken($request);
            unset($request->data[$this->_config['field']]);
        }
    }

    /**
     * Events supported by this component.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [
            'Controller.startup' => 'startup',
        ];
    }

    /**
     * Set the cookie in the response.
     *
     * Also sets the request->params['_csrfToken'] so the newly minted
     * token is available in the request data.
     *
     * @param \Cake\Http\ServerRequest $request The request object.
     * @param \Cake\Network\Response $response The response object.
     * @return void
     */
    protected function _setCookie(ServerRequest $request, Response $response)
    {
        $expiry = new Time($this->_config['expiry']);
        $value = hash('sha512', Security::randomBytes(16), false);

        $request->params['_csrfToken'] = $value;
        $response->cookie([
            'name' => $this->_config['cookieName'],
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
    protected function _validateToken(ServerRequest $request)
    {
        $cookie = $request->getCookie($this->_config['cookieName']);
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
