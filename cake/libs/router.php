<?php
/* SVN FILE: $Id$ */
/**
 * Parses the request URL into controller, action, and parameters.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Included libraries.
 *
 */
	if (!class_exists('Object')) {
		 uses ('object');
	}
/**
 * Parses the request URL into controller, action, and parameters.
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
uses('overloadable');
 
class Router extends Overloadable {
/**
 * Array of routes
 *
 * @var array
 */
	var $routes = array();
/**
 * CAKE_ADMIN route
 *
 * @var array
 */
	var $__admin = null;
/**
 * Directive for Router to parse out file extensions for mapping to Content-types.
 *
 * @var boolean
 */
	var $__parseExtensions = false;
/**
 * 'Constant' regular expression definitions for named route elements
 *
 * @var array
 */
	var $__named = array(
		'Action' => 'index|show|list|add|create|edit|update|remove|del|delete|new|view|item',
		'Year' => '[12][0-9]{3}',
		'ID' => '[0-9]+'
	);
 /**
  * Initialize the Router object
  *
  */
	function __construct() {
		if (defined('CAKE_ADMIN')) {
			$admin = CAKE_ADMIN;
			if (!empty($admin)) {
				$this->__admin = array('/:' . $admin . '/:controller/:action/* (default)',
										'/^(?:\/(?:(' . $admin . ')(?:\\/([a-zA-Z0-9_\\-\\.]+)(?:\\/([a-zA-Z0-9_\\-\\.]+)(?:[\\/\\?](.*))?)?)?))[\/]*$/',
										array($admin, 'controller', 'action'), array());
			}
		}
	}
/**
 * Gets a reference to the Router object instance
 *
 * @return object
 */
	function &getInstance() {
		static $instance = array();

		if (!isset($instance[0]) || !$instance[0]) {
			$instance[0] =& new Router();
		}

		return $instance[0];
	}
/**
 * Gets the named route elements for use in app/config/routes.php
 *
 * @return array
 */
	function getMap() {
		$_this =& Router::getInstance();
		return $_this->__named;
	}
/**
 * TODO: Better description. Returns this object's routes array. Returns false if there are no routes available.
 *
 * @param string $route			An empty string, or a route string "/"
 * @param array $default		NULL or an array describing the default route
 * @param array $requirements	An array matching the named elements in the route to regular expressions which that element should match.
 * @see routes
 * @return array			Array of routes
 */
	function connect($route, $default = null, $requirements = array()) {

		$_this =& Router::getInstance();
		$parsed = $names = array();

		if (defined('CAKE_ADMIN') && $default == null) {
			if ($route == CAKE_ADMIN) {
				$_this->routes[] = $_this->__admin;
				$_this->__admin = null;
			}
		}

		if (!empty($default) && !isset($default['action'])) {
			$default['action'] = 'index';
		}

		$r = null;
		if (($route == '') || ($route == '/')) {
			$regexp = '/^[\/]*$/';
			$_this->routes[] = array($route, $regexp, array(), $default);
		} else {
			$elements = array();

			foreach(explode('/', $route) as $element) {
				if (trim($element)) {
					$elements[] = $element;
				}
			}

			if (!count($elements)) {
				return false;
			}

			foreach($elements as $element) {

				if (preg_match('/^:(.+)$/', $element, $r)) {
					if (isset($requirements[$r[1]])) {
						$parsed[] = '(?:\/(' . $requirements[$r[1]] . '))?';
					} else {
						$parsed[] = '(?:\/([^\/]+))?';
					}
					$names[] = $r[1];
				} elseif(preg_match('/^\*$/', $element, $r)) {
					$parsed[] = '(?:\/(.*))?';
				} else {
					$parsed[] = '/' . $element;
				}
			}

			$regexp = '#^' . join('', $parsed) . '[\/]*$#';
			$_this->routes[] = array($route, $regexp, $names, $default);
		}
		return $_this->routes;
	}
/**
 * Parses given URL and returns an array of controllers, action and parameters
 * taken from that URL.
 *
 * @param string $url URL to be parsed
 * @return array
 */
	function parse($url) {
		$_this =& Router::getInstance();

		if ($url && ('/' != $url[0])) {
			if (!defined('SERVER_IIS')) {
				$url = '/' . $url;
			}
		}
		$out = array();
		$r = null;
		$default_route = array('/:controller/:action/* (default)',
								'/^(?:\/(?:([a-zA-Z0-9_\\-\\.]+)(?:\\/([a-zA-Z0-9_\\-\\.]+)(?:[\\/\\?](.*))?)?))[\\/]*$/',
								array('controller', 'action'), array());

		if (defined('CAKE_ADMIN') && $_this->__admin != null) {
			$_this->routes[] = $_this->__admin;
			$_this->__admin = null;
		}
		$_this->connect('/bare/:controller/:action/*', array('bare' => '1'));
		$_this->connect('/ajax/:controller/:action/*', array('bare' => '1'));

		if (defined('WEBSERVICES') && WEBSERVICES == 'on') {
			$_this->connect('/rest/:controller/:action/*', array('webservices' => 'Rest'));
			$_this->connect('/rss/:controller/:action/*', array('webservices' => 'Rss'));
			$_this->connect('/soap/:controller/:action/*', array('webservices' => 'Soap'));
			$_this->connect('/xml/:controller/:action/*', array('webservices' => 'Xml'));
			$_this->connect('/xmlrpc/:controller/:action/*', array('webservices' => 'XmlRpc'));
		}
		$_this->routes[] = $default_route;
		$ext = array();

		if (strpos($url, '?') !== false) {
			$url = substr($url, 0, strpos($url, '?'));
		}
		if ($_this->__parseExtensions && preg_match('/\.[0-9a-zA-Z]*$/', $url, $ext) == 1) {
			$ext = substr($ext[0], 1);
			$url = substr($url, 0, strpos($url, '.' . $ext));
		}

		foreach($_this->routes as $route) {
			list($route, $regexp, $names, $defaults) = $route;

			if (preg_match($regexp, $url, $r)) {
				// remove the first element, which is the url
				array_shift ($r);
				// hack, pre-fill the default route names
				foreach($names as $name) {
					$out[$name] = null;
				}
				$ii = 0;

				if (is_array($defaults)) {
					foreach($defaults as $name => $value) {
						if (preg_match('#[a-zA-Z_\-]#i', $name)) {
							$out[$name] = $value;
						} else {
							$out['pass'][] = $value;
						}
					}
				}

				foreach($r as $found) {
					// if $found is a named url element (i.e. ':action')
					if (isset($names[$ii])) {
						$out[$names[$ii]] = $found;
					} else {
						// unnamed elements go in as 'pass'
						$pass = new NeatArray(explode('/', $found));
						$pass->cleanup();
						$out['pass'] = $pass->value;
					}
					$ii++;
				}
				break;
			}
		}
		if (!empty($ext)) {
			$out['url']['ext'] = $ext;
		}
		return $out;
	}
/**
 * Instructs the router to parse out file extensions from the URL
 *
 * @return void
 */
	function parseExtensions() {
		$_this =& Router::getInstance();
		$_this->__parseExtensions = true;
	}
}

?>