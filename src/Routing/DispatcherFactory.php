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

use Cake\Core\App;
use Cake\Routing\Dispatcher;
use Cake\Routing\Error\MissingDispatcherFilterException;

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
 * Instances of filters will be re-used across all sub-requests
 * in a request.
 *
 * @param string|\Cake\Routing\DispatcherFilter $filter Either the classname of the filter
 *   or an instance to use.
 * @return \Cake\Routing\DispatcherFilter
 */
	public static function add($filter) {
		if (is_string($filter)) {
			$filter = static::_createFilter($filter);
		}
		static::$_stack[] = $filter;
		return $filter;
	}

/**
 * Create an instance of a filter.
 *
 * @param string $name The name of the filter to build.
 * @return \Cake\Routing\DispatcherFilter
 * @throws \Cake\Routing\Error\MissingDispatcherFilterException When filters cannot be found.
 */
	protected static function _createFilter($name) {
		$className = App::className($name, 'Routing/Filter', 'Filter');
		if (!$className) {
			$msg = sprintf('Cannot locate dispatcher filter named "%s".', $name);
			throw new MissingDispatcherFilterException($msg);
		}
		return new $className();
	}

/**
 * Create a dispatcher that has all the configured middleware applied.
 *
 * @return \Cake\Routing\Dispatcher
 */
	public static function create() {
		$dispatcher = new Dispatcher();
		foreach (static::$_stack as $middleware) {
			$dispatcher->addFilter($middleware);
		}
		return $dispatcher;
	}

/**
 * Get the connected dispatcher filters.
 *
 * @return array
 */
	public static function filters() {
		return static::$_stack;
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
