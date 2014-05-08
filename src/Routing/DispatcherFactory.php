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
namespace Cake\Routing;

use Cake\Routing\Dispatcher;

/**
 * A factory for creating dispatchers with all the desired middleware
 * connected.
 */
class DispatcherFactory {

/**
 * Stack of middleware to apply to dispatchers.
 *
 * @var array
 */
	protected static $_stack = [];

/**
 * Add a new middleware object to the stack of middleware
 * that will be executed.
 *
 * @param \Cake\Routing\Middleware $middleware
 * @return void
 */
	public static function add($middleware) {
		if (is_string($middleware)) {
			$middleware = static::_createMiddleware($middleware);
		}
		static::$_stack[] = $middleware;
	}

/**
 * Create a dispatcher that has all the configured middleware applied.
 *
 * @return \Cake\Routing\Dispatcher
 */
	public static function create() {
		$dispatcher = new Dispatcher();
		foreach (static::$_stack as $middleware) {
			$dispatcher->add($middleware);
		}
		return $dispatcher;
	}

/**
 * Clear the middleware stack.
 *
 * @return void
 */
	public static function clear() {
		static::$_stack = [];
	}

}
