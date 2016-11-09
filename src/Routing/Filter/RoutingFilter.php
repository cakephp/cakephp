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
namespace Cake\Routing\Filter;

use Cake\Event\Event;
use Cake\Routing\DispatcherFilter;
use Cake\Routing\Exception\RedirectException;
use Cake\Routing\Router;

/**
 * A dispatcher filter that applies routing rules to the request.
 *
 * This filter will call Router::parse() when the request has no controller
 * parameter defined.
 */
class RoutingFilter extends DispatcherFilter
{

    /**
     * Priority setting.
     *
     * This filter is normally fired last just before the request
     * is dispatched.
     *
     * @var int
     */
    protected $_priority = 10;

    /**
     * Applies Routing and additionalParameters to the request to be dispatched.
     * If Routes have not been loaded they will be loaded, and config/routes.php will be run.
     *
     * @param \Cake\Event\Event $event containing the request, response and additional params
     * @return \Cake\Network\Response|null A response will be returned when a redirect route is encountered.
     */
    public function beforeDispatch(Event $event)
    {
        $request = $event->data['request'];
        if (Router::getRequest(true) !== $request) {
            Router::setRequestInfo($request);
        }

        try {
            if (empty($request->params['controller'])) {
                $params = Router::parse($request->url, $request->method());
                $request->addParams($params);
            }
        } catch (RedirectException $e) {
            $event->stopPropagation();
            $response = $event->data['response'];
            $response->statusCode($e->getCode());
            $response->header('Location', $e->getMessage());

            return $response;
        }
    }
}
