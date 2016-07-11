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
namespace Cake\Routing;

use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Network\Session;

/**
 * Provides the requestAction() method for doing sub-requests
 *
 * @deprecated 3.3.0 Use view cells instead.
 */
trait RequestActionTrait
{

    /**
     * Calls a controller's method from any location. Can be used to connect controllers together
     * or tie plugins into a main application. requestAction can be used to return rendered views
     * or fetch the return value from controller actions.
     *
     * Under the hood this method uses Router::reverse() to convert the $url parameter into a string
     * URL. You should use URL formats that are compatible with Router::reverse()
     *
     * ### Examples
     *
     * A basic example getting the return value of the controller action:
     *
     * ```
     * $variables = $this->requestAction('/articles/popular');
     * ```
     *
     * A basic example of request action to fetch a rendered page without the layout.
     *
     * ```
     * $viewHtml = $this->requestAction('/articles/popular', ['return']);
     * ```
     *
     * You can also pass the URL as an array:
     *
     * ```
     * $vars = $this->requestAction(['controller' => 'articles', 'action' => 'popular']);
     * ```
     *
     * ### Passing other request data
     *
     * You can pass POST, GET, COOKIE and other data into the request using the appropriate keys.
     * Cookies can be passed using the `cookies` key. Get parameters can be set with `query` and post
     * data can be sent using the `post` key.
     *
     * ```
     * $vars = $this->requestAction('/articles/popular', [
     *   'query' => ['page' => 1],
     *   'cookies' => ['remember_me' => 1],
     * ]);
     * ```
     *
     * ### Sending environment or header values
     *
     * By default actions dispatched with this method will use the global $_SERVER and $_ENV
     * values. If you want to override those values for a request action, you can specify the values:
     *
     * ```
     * $vars = $this->requestAction('/articles/popular', [
     *   'environment' => ['CONTENT_TYPE' => 'application/json']
     * ]);
     * ```
     *
     * ### Transmitting the session
     *
     * By default actions dispatched with this method will use the standard session object.
     * If you want a particular session instance to be used, you need to specify it.
     *
     * ```
     * $vars = $this->requestAction('/articles/popular', [
     *   'session' => new Session($someSessionConfig)
     * ]);
     * ```
     *
     * @param string|array $url String or array-based url.  Unlike other url arrays in CakePHP, this
     *    url will not automatically handle passed arguments in the $url parameter.
     * @param array $extra if array includes the key "return" it sets the autoRender to true.  Can
     *    also be used to submit GET/POST data, and passed arguments.
     * @return mixed Boolean true or false on success/failure, or contents
     *    of rendered action if 'return' is set in $extra.
     */
    public function requestAction($url, array $extra = [])
    {
        trigger_error('requestAction() is deprecated. Use view cells instead.', E_USER_DEPRECATED);

        if (empty($url)) {
            return false;
        }
        if (($index = array_search('return', $extra)) !== false) {
            $extra['return'] = 0;
            $extra['autoRender'] = 1;
            unset($extra[$index]);
        }
        $extra += ['autoRender' => 0, 'return' => 1, 'bare' => 1, 'requested' => 1];

        $baseUrl = Configure::read('App.fullBaseUrl');
        if (is_string($url) && strpos($url, $baseUrl) === 0) {
            $url = Router::normalize(str_replace($baseUrl, '', $url));
        }
        if (is_string($url)) {
            $params = [
                'url' => $url
            ];
        } elseif (is_array($url)) {
            $defaultParams = ['plugin' => null, 'controller' => null, 'action' => null];
            $params = [
                'params' => $url + $defaultParams,
                'base' => false,
                'url' => Router::reverse($url)
            ];
            if (empty($params['params']['pass'])) {
                $params['params']['pass'] = [];
            }
        }
        $current = Router::getRequest();
        if ($current) {
            $params['base'] = $current->base;
            $params['webroot'] = $current->webroot;
        }

        $params['post'] = $params['query'] = [];
        if (isset($extra['post'])) {
            $params['post'] = $extra['post'];
        }
        if (isset($extra['query'])) {
            $params['query'] = $extra['query'];
        }
        if (isset($extra['cookies'])) {
            $params['cookies'] = $extra['cookies'];
        }
        if (isset($extra['environment'])) {
            $params['environment'] = $extra['environment'] + $_SERVER + $_ENV;
        }
        unset($extra['environment'], $extra['post'], $extra['query']);

        $params['session'] = isset($extra['session']) ? $extra['session'] : new Session();

        $request = new Request($params);
        $request->addParams($extra);
        $dispatcher = DispatcherFactory::create();
        $result = $dispatcher->dispatch($request, new Response());
        Router::popRequest();
        return $result;
    }
}
