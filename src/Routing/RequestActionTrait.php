<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing;

use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\Routing\Filter\ControllerFactoryFilter;
use Cake\Routing\Filter\RoutingFilter;

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
     * @deprecated 3.3.0 You should refactor your code to use View Cells instead of this method.
     */
    public function requestAction($url, array $extra = [])
    {
        deprecationWarning(
            'RequestActionTrait::requestAction() is deprecated. ' .
            'You should refactor to use View Cells or Components instead.'
        );
        if (empty($url)) {
            return false;
        }
        $isReturn = array_search('return', $extra, true);
        if ($isReturn !== false) {
            $extra['return'] = 0;
            $extra['autoRender'] = 1;
            unset($extra[$isReturn]);
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
            $params['base'] = $current->getAttribute('base');
            $params['webroot'] = $current->getAttribute('webroot');
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

        $request = new ServerRequest($params);
        $request->addParams($extra);
        $dispatcher = DispatcherFactory::create();

        // If an application is using PSR7 middleware,
        // we need to 'fix' their missing dispatcher filters.
        $needed = [
            'routing' => RoutingFilter::class,
            'controller' => ControllerFactoryFilter::class
        ];
        foreach ($dispatcher->filters() as $filter) {
            if ($filter instanceof RoutingFilter) {
                unset($needed['routing']);
            }
            if ($filter instanceof ControllerFactoryFilter) {
                unset($needed['controller']);
            }
        }
        foreach ($needed as $class) {
            $dispatcher->addFilter(new $class());
        }
        $result = $dispatcher->dispatch($request, new Response());
        Router::popRequest();

        return $result;
    }
}
