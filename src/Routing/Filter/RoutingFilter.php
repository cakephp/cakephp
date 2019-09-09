<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
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
     * @return \Cake\Http\Response|null A response will be returned when a redirect route is encountered.
     */
    public function beforeDispatch(Event $event)
    {
        /* @var \Cake\Http\ServerRequest $request */
        $request = $event->getData('request');
        if (Router::getRequest(true) !== $request) {
            Router::setRequestInfo($request);
        }

        try {
            if (!$request->getParam('controller')) {
                $params = Router::parseRequest($request);
                $request->addParams($params);
            }

            return null;
        } catch (RedirectException $e) {
            $event->stopPropagation();
            /* @var \Cake\Http\Response $response */
            $response = $event->getData('response');
            $response = $response->withStatus($e->getCode())
                ->withLocation($e->getMessage());

            return $response;
        }
    }
}
