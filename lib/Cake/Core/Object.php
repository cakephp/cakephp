<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Core
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Core;

use Cake\Log\LogTrait;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Dispatcher;
use Cake\Routing\Router;
use Cake\Utility\Hash;

/**
 * Object class provides a few generic methods used in several subclasses.
 *
 * Also includes methods for logging and the special method RequestAction,
 * to call other Controllers' Actions from anywhere.
 *
 * @package       Cake.Core
 */
class Object {

	use LogTrait;
/**
 * constructor, no-op
 *
 */
	public function __construct() {
	}

/**
 * Object-to-string conversion.
 * Each class can override this method as necessary.
 *
 * @return string The name of this class
 */
	public function toString() {
		return get_class($this);
	}

/**
 * Calls a controller's method from any location. Can be used to connect controllers together
 * or tie plugins into a main application. requestAction can be used to return rendered views
 * or fetch the return value from controller actions.
 *
 * Under the hood this method uses Router::reverse() to convert the $url parameter into a string
 * URL.  You should use URL formats that are compatible with Router::reverse()
 *
 * #### Passing POST and GET data
 *
 * POST and GET data can be simulated in requestAction.  Use `$extra['query']` for
 * GET data.  The `$extra['post']` parameter allows POST data simulation.
 *
 * @param string|array $url String or array-based url.  Unlike other url arrays in CakePHP, this
 *    url will not automatically handle passed arguments in the $url parameter.
 * @param array $extra if array includes the key "return" it sets the AutoRender to true.  Can
 *    also be used to submit GET/POST data, and passed arguments.
 * @return mixed Boolean true or false on success/failure, or contents
 *    of rendered action if 'return' is set in $extra.
 */
	public function requestAction($url, $extra = array()) {
		if (empty($url)) {
			return false;
		}
		if (($index = array_search('return', $extra)) !== false) {
			$extra['return'] = 0;
			$extra['autoRender'] = 1;
			unset($extra[$index]);
		}
		$extra = array_merge(
			['autoRender' => 0, 'return' => 1, 'bare' => 1, 'requested' => 1],
			$extra
		);
		$post = $query = [];
		if (isset($extra['post'])) {
			$post = $extra['post'];
		}
		if (isset($extra['query'])) {
			$query = $extra['query'];
		}
		unset($extra['post'], $extra['query']);

		if (is_string($url) && strpos($url, FULL_BASE_URL) === 0) {
			$url = Router::normalize(str_replace(FULL_BASE_URL, '', $url));
		}
		if (is_string($url)) {
			$params = array(
				'url' => $url
			);
		} elseif (is_array($url)) {
			$params = array_merge($url, [
				'pass' => [],
				'base' => false,
				'url' => Router::reverse($url)
			]);
		}
		if (!empty($post)) {
			$params['post'] = $post;
		}
		if (!empty($query)) {
			$params['query'] = $query;
		}
		$request = new Request($params);
		$dispatcher = new Dispatcher();
		$result = $dispatcher->dispatch($request, new Response(), $extra);
		Router::popRequest();
		return $result;
	}

/**
 * Calls a method on this object with the given parameters. Provides an OO wrapper
 * for `call_user_func_array`
 *
 * @param string $method  Name of the method to call
 * @param array $params  Parameter list to use when calling $method
 * @return mixed  Returns the result of the method call
 */
	public function dispatchMethod($method, $params = array()) {
		switch (count($params)) {
			case 0:
				return $this->{$method}();
			case 1:
				return $this->{$method}($params[0]);
			case 2:
				return $this->{$method}($params[0], $params[1]);
			case 3:
				return $this->{$method}($params[0], $params[1], $params[2]);
			case 4:
				return $this->{$method}($params[0], $params[1], $params[2], $params[3]);
			case 5:
				return $this->{$method}($params[0], $params[1], $params[2], $params[3], $params[4]);
			default:
				return call_user_func_array(array(&$this, $method), $params);
		}
	}

/**
 * Stop execution of the current script.  Wraps exit() making
 * testing easier.
 *
 * @param integer|string $status see http://php.net/exit for values
 * @return void
 */
	protected function _stop($status = 0) {
		exit($status);
	}

/**
 * Allows setting of multiple properties of the object in a single line of code.  Will only set
 * properties that are part of a class declaration.
 *
 * @param array $properties An associative array containing properties and corresponding values.
 * @return void
 */
	protected function _set($properties = array()) {
		if (is_array($properties) && !empty($properties)) {
			$vars = get_object_vars($this);
			foreach ($properties as $key => $val) {
				if (array_key_exists($key, $vars)) {
					$this->{$key} = $val;
				}
			}
		}
	}

/**
 * Merges this objects $property with the property in $class' definition.
 * This classes value for the property will be merged on top of $class'
 *
 * This provides some of the DRY magic CakePHP provides.  If you want to shut it off, redefine
 * this method as an empty function.
 *
 * @param array $properties The name of the properties to merge.
 * @param string $class The class to merge the property with.
 * @param boolean $normalize Set to true to run the properties through Hash::normalize() before merging.
 * @return void
 */
	protected function _mergeVars($properties, $class, $normalize = true) {
		$classProperties = get_class_vars($class);
		foreach ($properties as $var) {
			if (
				isset($classProperties[$var]) &&
				!empty($classProperties[$var]) &&
				is_array($this->{$var}) &&
				$this->{$var} != $classProperties[$var]
			) {
				if ($normalize) {
					$classProperties[$var] = Hash::normalize($classProperties[$var]);
					$this->{$var} = Hash::normalize($this->{$var});
				}
				$this->{$var} = Hash::merge($classProperties[$var], $this->{$var});
			}
		}
	}

}
