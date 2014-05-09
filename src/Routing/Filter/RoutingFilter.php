<?php

namespace Cake\Routing\Filter;

use Cake\Event\Event;
use Cake\Routing\DispatcherFilter;
use Cake\Routing\Router;

/**
 * A dispatcher filter that applies routing rules to the request.
 *
 * This filter will call Router::parse() when the request has no controller
 * parameter defined.
 */
class RoutingFilter extends DispatcherFilter {

/**
 * Priority setting.
 *
 * This filter is normally fired last just before the request
 * is dispatched.
 *
 * @var int
 */
	protected $_priority = -10;

/**
 * Applies Routing and additionalParameters to the request to be dispatched.
 * If Routes have not been loaded they will be loaded, and app/Config/routes.php will be run.
 *
 * @param \Cake\Event\Event $event containing the request, response and additional params
 * @return void
 */
	public function beforeDispatch(Event $event) {
		$request = $event->data['request'];
		Router::setRequestInfo($request);

		if (empty($request->params['controller'])) {
			$params = Router::parse($request->url);
			$request->addParams($params);
		}
	}

}
