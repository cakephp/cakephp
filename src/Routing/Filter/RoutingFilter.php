<?php

namespace Cake\Routing\Filter;

use Cake\Event\Event;
use Cake\Routing\DispatcherFilter;
use Cake\Routing\Router;

class RoutingFilter extends DispatcherFilter {

	public $priority = -10;

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
