<?php
/* SVN FILE: $Id$ */
/**
 * Javascript Generator class file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package       cake
 * @subpackage    cake.cake.libs.view.helpers
 * @since         CakePHP v 1.2
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 **/
/**
 * Javascript Generator helper class for easy use of JavaScript.
 *
 * JsHelper provides an abstract interface for authoring JavaScript with a
 * given client-side library.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.view.helpers
 **/
class JsHelper extends AppHelper {
/**
 * Base URL
 *
 * @var string
 **/
	var $base = null;
/**
 * Webroot path
 *
 * @var string
 **/
	var $webroot = null;
/**
 * Theme name
 *
 * @var string
 **/
	var $themeWeb = null;
/**
 * URL to current action.
 *
 * @var string
 **/
	var $here = null;
/**
 * Parameter array.
 *
 * @var array
 **/
	var $params = array();
/**
 * Current action.
 *
 * @var string
 **/
	var $action = null;
/**
 * Plugin path
 *
 * @var string
 **/
	var $plugin = null;
/**
 * POST data for models
 *
 * @var array
 **/
	var $data = null;
/**
 * helpers
 *
 * @var array
 **/
	var $helpers = array('Html');
/**
 * Current Javascript Engine that is being used
 *
 * @var string
 * @access private
 **/
	var $__engineName;
/**
 * __objects
 *
 * @var array
 **/
	var $__objects = array();
/**
 * output
 *
 * @var string
 **/
	var $output = false;
/**
 * Constructor - determines engine helper
 *
 * @param array $settings Settings array contains name of engine helper.
 * @access public
 * @return void
 **/
	function __construct($settings = array()) {
		$className = 'jquery';
		if (is_array($settings) && isset($settings[0])) {
			$className = $settings[0];
		} elseif (is_string($settings)) {
			$className = $settings;
		}
		$engineName = $className;
		if (strpos($className, '.') !== false) {
			list($plugin, $className) = explode('.', $className);
		}
		$this->__engineName = $className . 'Engine';
		$engineClass = $engineName . 'Engine';
		$this->helpers[] = $engineClass;
		parent::__construct();
	}
/**
 * call__
 *
 * @param string $method Method to be called
 * @param array $params Parameters for the method being called.
 * @access public
 * @return void
 **/
	function call__($method, $params) {
		if (isset($this->{$this->__engineName}) && method_exists($this->{$this->__engineName}, $method)) {
			return $this->{$this->__engineName}->dispatchMethod($method, $params);
		}
		if (method_exists($this, $method . '_')) {
			return $this->dispatchMethod($method . '_', $params);
		}
		trigger_error(sprintf(__('JsHelper:: Missing Method %s is undefined', true), $method), E_USER_WARNING);
	}
/**
 * Writes all Javascript generated so far to a code block or
 * caches them to a file and returns a linked script.
 *
 * Options
 *
 * - 'inline' - Set to true to have scripts output as a script block inline
 *   if 'cache' is also true, a script link tag will be generated. (default true)
 * - 'cache' - Set to true to have scripts cached to a file and linked in (default true)
 * - 'clear' - Set to false to prevent script cache from being cleared (default true)
 * - 'onDomReady' - wrap cached scripts in domready event (default true)
 * - 'safe' - if an inline block is generated should it be wrapped in <![CDATA[ ... ]]> (default true)
 *
 * @param array $options options for the code block
 * @return string completed javascript tag.
 **/
	function writeScripts($options = array()) {
		$defaults = array('onDomReady' => true, 'inline' => true, 'cache' => true, 'clear' => true, 'safe' => true);
		$options = array_merge($defaults, $options);
		$script = implode("\n", $this->{$this->__engineName}->getCache($options['clear']));
		
		if ($options['onDomReady']) {
			$script = $this->{$this->__engineName}->domReady($script);
		}
		if (!$options['cache'] && $options['inline']) {
			return $this->Html->scriptBlock($script, $options);
		}
		if ($options['cache'] && $options['inline']) {
			//cache to file and return script tag.
		}
		$view =& ClassRegistry::getObject('view');
		$view->addScript($script);
		return null;
	}

/**
 * Loads a remote URL
 *
 * @param  string $url
 * @param  array  $options
 * @return string
 **/
/*	function load_($url = null, $options = array()) {
		if (isset($options['update'])) {
			if (!is_array($options['update'])) {
				$func = "new Ajax.Updater('{$options['update']}',";
			} else {
				$func = "new Ajax.Updater(document.createElement('div'),";
			}
			if (!isset($options['requestHeaders'])) {
				$options['requestHeaders'] = array();
			}
			if (is_array($options['update'])) {
				$options['update'] = join(' ', $options['update']);
			}
			$options['requestHeaders']['X-Update'] = $options['update'];
		} else {
			$func = "new Ajax.Request(";
		}

		$func .= "'" . Router::url($url) . "'";
		$ajax =& new AjaxHelper();
		$func .= ", " . $ajax->__optionsForAjax($options) . ")";

		if (isset($options['before'])) {
			$func = "{$options['before']}; $func";
		}
		if (isset($options['after'])) {
			$func = "$func; {$options['after']};";
		}
		if (isset($options['condition'])) {
			$func = "if ({$options['condition']}) { $func; }";
		}
		if (isset($options['confirm'])) {
			$func = "if (confirm('" . $this->Javascript->escapeString($options['confirm'])
				. "')) { $func; } else { return false; }";
		}
		return $func;
	}


/*	
	function get__($name) {
		return $this->__object($name, 'id');
	}

	function select($pattern) {
		return $this->__object($pattern, 'pattern');
	}

	function real($var) {
		return $this->__object($var, 'real');
	}

	function __object($name, $var) {
		if (!isset($this->__objects[$name])) {
			$this->__objects[$name] = new JsHelperObject($this);
			$this->__objects[$name]->{$var} = $name;
		}
		return $this->__objects[$name];
	}
*/
}

/**
 * JsEngineBaseClass 
 * 
 * Abstract Base Class for All JsEngines to extend. Provides generic methods.
 *
 * @package cake.view.helpers
 **/
class JsBaseEngineHelper extends AppHelper {
/**
 * Determines whether native JSON extension is used for encoding.  Set by object constructor.
 *
 * @var boolean
 * @access public
 **/
	var $useNative = false;
/**
 * The js snippet for the current selection.
 *
 * @var string
 * @access public
 **/
	var $selection;
/**
 * Collection of option maps.
 *
 * @var array
 **/
	var $_optionMap = array();
/**
 * Scripts that are queued for output
 *
 * @var array
 **/
	var $__cachedScripts = array();
/**
 * Constructor.
 *
 * @return void
 **/
	function __construct() {
		$this->useNative = function_exists('json_encode');
	}
/**
 * Create an alert message in Javascript
 *
 * @param string $message Message you want to alter.
 * @access public
 * @return void
 **/
	function alert($message) {
		return 'alert("' . $this->escape($message) . '");';
	}
/**
 * Redirects to a URL
 *
 * @param  mixed $url
 * @param  array  $options
 * @return string
 **/
	function redirect($url = null) {
		return 'window.location = "' . Router::url($url) . '";';
	}
/**
 * Create a confirm() message
 *
 * @param string $message Message you want confirmed.
 * @access public
 * @return void
 **/
	function confirm($message) {
		return 'confirm("' . $this->escape($message) . '");';
	}
/**
 * Create a prompt() Javascript function
 *
 * @param string $message Message you want to prompt.
 * @param string $default Default message
 * @access public
 * @return void
 **/
	function prompt($message, $default = '') {
		return 'prompt("' . $this->escape($message) . '", "' . $this->escape($default) . '");';
	}
/**
 * Generates a JavaScript object in JavaScript Object Notation (JSON)
 * from an array.  Will use native JSON encode method if available, and $useNative == true
 *
 * Options:
 *
 * - 'prefix' - String prepended to the returned data.
 * - 'postfix' - String appended to the returned data.
 * - 'stringKeys' - A list of array keys to be treated as a string
 * - 'quoteKeys' - If false treats $options['stringKeys'] as a list of keys **not** to be quoted.
 * - 'q' - Type of quote to use.
 *
 * @param array $data Data to be converted.
 * @param array $options Set of options, see above.
 * @return string A JSON code block
 * @access public
 **/
	function object($data = array(), $options = array()) {
		$defaultOptions = array(
			'block' => false, 'prefix' => '', 'postfix' => '',
			'stringKeys' => array(), 'quoteKeys' => true, 'q' => '"'
		);
		$options = array_merge($defaultOptions, $options);

		if (is_object($data)) {
			$data = get_object_vars($data);
		}

		$out = $keys = array();
		$numeric = true;

		if ($this->useNative) {
			$rt = json_encode($data);
		} else {
			if (is_null($data)) {
				return 'null';
			}
			if (is_bool($data)) {
				return $data ? 'true' : 'false';
			}
			if (is_array($data)) {
				$keys = array_keys($data);
			}

			if (!empty($keys)) {
				$numeric = (array_values($keys) === array_keys(array_values($keys)));
			}

			foreach ($data as $key => $val) {
				if (is_array($val) || is_object($val)) {
					$val = $this->object($val, array_merge($options, array('block' => false)));
				} else {
					$quoteStrings = (
						!count($options['stringKeys']) ||
						($options['quoteKeys'] && in_array($key, $options['stringKeys'], true)) ||
						(!$options['quoteKeys'] && !in_array($key, $options['stringKeys'], true))
					);
					$val = $this->value($val, $quoteStrings);
				}
				if (!$numeric) {
					$val = $options['q'] . $this->value($key, false) . $options['q'] . ':' . $val;
				}
				$out[] = $val;
			}

			if (!$numeric) {
				$rt = '{' . join(',', $out) . '}';
			} else {
				$rt = '[' . join(',', $out) . ']';
			}
		}
		$rt = $options['prefix'] . $rt . $options['postfix'];

		if ($options['block']) {
			$rt = $this->codeBlock($rt, array_diff_key($options, $defaultOptions));
		}
		return $rt;
	}
/**
 * Converts a PHP-native variable of any type to a JSON-equivalent representation
 *
 * @param mixed $val A PHP variable to be converted to JSON
 * @param boolean $quoteStrings If false, leaves string values unquoted
 * @return string a JavaScript-safe/JSON representation of $val
 * @access public
 **/
	function value($val, $quoteStrings = true) {
		switch (true) {
			case (is_array($val) || is_object($val)):
				$val = $this->object($val);
			break;
			case ($val === null):
				$val = 'null';
			break;
			case (is_bool($val)):
				$val = ($val === true) ? 'true' : 'false';
			break;
			case (is_int($val)):
				$val = $val;
			break;
			case (is_float($val)):
				$val = sprintf("%.11f", $val);
			break;
			default:
				$val = $this->escape($val);
				if ($quoteStrings) {
					$val = '"' . $val . '"';
				}
			break;
		}
		return $val;
	}
/**
 * Escape a string to be JavaScript friendly.
 *
 * List of escaped ellements:
 *	+ "\r\n" => '\n'
 *	+ "\r" => '\n'
 *	+ "\n" => '\n'
 *	+ '"' => '\"'
 *	+ "'" => "\\'"
 *
 * @param  string $script String that needs to get escaped.
 * @return string Escaped string.
 * @access public
 **/
	function escape($string) {
		$escape = array("\r\n" => '\n', "\r" => '\n', "\n" => '\n', '"' => '\"', "'" => "\\'");
		return str_replace(array_keys($escape), array_values($escape), $string);
	}
/**
 * Write a script to the cached scripts.
 *
 * @return void
 **/
	function writeCache($script) {
		$this->__cachedScripts[] = $script;
	}
/**
 * Get all the cached scripts
 *
 * @param boolean $clear Whether or not to clear the script caches
 * @return array Array of scripts added to the request.
 **/
	function getCache($clear = true) {
		$scripts = $this->__cachedScripts;
		if ($clear) {
			$this->__cachedScripts = array();
		}
		return $scripts;
	}
/**
 * Create javascript selector for a CSS rule
 *
 * @param string $selector The selector that is targeted
 * @param boolean $multiple Whether or not the selector could target more than one element.
 * @return object instance of $this. Allows chained methods.
 **/
	function get($selector, $multiple = false) {
		trigger_error(sprintf(__('%s does not have get() implemented', true), get_class($this)), E_USER_WARNING);
		return $this;
	}
/**
 * Add an event to the script cache. Operates on the currently selected elements.
 *
 * ### Options
 *
 * - 'wrap' - Whether you want the callback wrapped in an anonymous function. (defaults to true)
 * - 'stop' - Whether you want the event to stopped. (defaults to true)
 *
 * @param string $type Type of event to bind to the current dom id
 * @param string $callback The Javascript function you wish to trigger or the function literal
 * @param array $options Options for the event.
 * @return string completed event handler
 **/
	function event($type, $callback, $options = array()) {
		trigger_error(sprintf(__('%s does not have event() implemented', true), get_class($this)), E_USER_WARNING);
	}
/**
 * Create a domReady event. This is a special event in many libraries
 *
 * @param string $functionBody The code to run on domReady
 * @return string completed domReady method
 **/
	function domReady($functionBody) {
		trigger_error(sprintf(__('%s does not have domReady() implemented', true), get_class($this)), E_USER_WARNING);
	}
/**
 * Create an iteration over the current selection result.
 *
 * @param string $callback The function body you wish to apply during the iteration.
 * @return string completed iteration
 **/
	function each($callback) {
		trigger_error(sprintf(__('%s does not have each() implemented', true), get_class($this)), E_USER_WARNING);
	}
/**
 * Trigger an Effect.
 *
 * ### Supported Effects
 *
 * The following effects are supported by all JsEngines
 *
 * - 'show' - reveal an element.
 * - 'hide' - hide an element.
 * - 'fadeIn' - Fade in an element.
 * - 'fadeOut' - Fade out an element.
 * - 'toggle' - Toggle an element's visibility.
 * - 'slideIn' - Slide an element in.
 * - 'slideOut' - Slide an element out.
 *
 * ### Options
 *
 * - 'speed' - Speed at which the animation should occur. Accepted values are 'slow', 'fast'. Not all effects use
 *   the speed option.
 *
 * @param string $name The name of the effect to trigger.
 * @param array $options Array of options for the effect.
 * @return string completed string with effect.
 **/
	function effect($name, $options) {
		trigger_error(sprintf(__('%s does not have effect() implemented', true), get_class($this)), E_USER_WARNING);
	}
/**
 * Make an XHR request
 *
 * ### Options
 *
 * - 'method' - The method to make the request with defaults to GET in more libraries 
 * - 'complete' - Callback to fire on complete.
 * - 'request' - Callback to fire on request initialization.
 * - 'error' - Callback to fire on request failure.
 * - 'async' - Whether or not you want an asynchronous request.
 * - 'data' - Additional data to send.
 * - 'update' - Dom selector to update with the content of the request.
 * - 'type' - Data type for response. 'json' and 'html' are supported. Default is html for most libraries.
 * - 'evalScripts' - Whether or not <script> tags should be evaled.
 *
 * @param mixed $url Array or String URL to target with the request.
 * @param array $options Array of options. See above for cross library supported options
 * @return string XHR request.
 **/
	function request($url, $options = array()) {
		trigger_error(sprintf(__('%s does not have request() implemented', true), get_class($this)), E_USER_WARNING);	
	}
/**
 * Parse an options assoc array into an Javascript object literal.
 * Similar to object() but treats any non-integer value as a string,
 * does not include { }
 *
 * @param array $options Options to be converted
 * @param array $safeKeys Keys that should not be escaped.
 * @return string
 * @access protected
 **/
	function _parseOptions($options, $safeKeys = array()) {
		$out = array();
		foreach ($options as $key => $value) {
			if (!is_int($value) && !in_array($key, $safeKeys)) {
				$value = '"' . $this->escape($value) . '"';
			}
			$out[] = $key . ':' . $value;
		}
		return join(', ', $out);
	}
/**
 * Maps Abstract options to engine specific option names.
 * If attributes are missing from the map, they are not changed.
 *
 * @param string $method Name of method whose options are being worked with.
 * @param array $options Array of options to map.
 * @return array Array of mapped options.
 * @access protected
 **/
	function _mapOptions($method, $options) {
		if (!isset($this->_optionMap[$method])) {
			return $options;
		}
		foreach ($this->_optionMap[$method] as $abstract => $concrete) {
			if (isset($options[$abstract])) {
				$options[$concrete] = $options[$abstract];
				unset($options[$abstract]);
			}
		}
		return $options;
	}
}


class JsHelperObject {
	var $__parent = null;

	var $id = null;

	var $pattern = null;

	var $real = null;

	function __construct(&$parent) {
		if (is_object($parent)) {
			$this->setParent($parent);
		}
	}

	function toString() {
		return $this->__toString();
	}

	function __toString() {
		return $this->literal;
	}

	function ref($ref = null) {
		if ($ref == null) {
			foreach (array('id', 'pattern', 'real') as $ref) {
				if ($this->{$ref} !== null) {
					return $this->{$ref};
				}
			}
		} else {
			return ($this->{$ref} !== null);
		}
		return null;
	}

	function literal($append = null) {
		if (!empty($this->id)) {
			$data = '$("' . $this->id . '")';
		}
		if (!empty($this->pattern)) {
			$data = '$$("' . $this->pattern . '")';
		}
		if (!empty($this->real)) {
			$data = $this->real;
		}
		if (!empty($append)) {
			$data .= '.' . $append;
		}
		return $data;
	}

	function __call($name, $args) {
		$data = '';

		if (isset($this->__parent->effectMap[strtolower($name)])) {
			array_unshift($args, $this->__parent->effectMap[strtolower($name)]);
			$name = 'effect';
		}

		switch ($name) {
			case 'effect':
			case 'visualEffect':

				if (strpos($args[0], '_') || $args[0]{0} != strtoupper($args[0]{0})) {
					$args[0] = Inflector::camelize($args[0]);
				}

				if (strtolower($args[0]) == 'highlight') {
					$data .= 'new ';
				}
				if ($this->pattern == null) {
					$data .= 'Effect.' . $args[0] . '(' . $this->literal();
				} else {
					$data .= 'Effect.' . $args[0] . '(item';
				}

				if (isset($args[1]) && is_array($args[1])) {
					$data .= ', {' . $this->__options($args[1]) . '}';
				}
				$data .= ');';

				if ($this->pattern !== null) {
					$data = $this->each($data);
				}
			break;
			case 'remove':
			case 'toggle':
			case 'show':
			case 'hide':
				if (empty($args)) {
					$obj = 'Element';
					$params = '';
				} else {
					$obj = 'Effect';
					$params = ', "' . $args[0] . '"';
				}

				if ($this->pattern != null) {
					$data = $this->each($obj . ".{$name}(item);");
				} else {
					$data = $obj . ".{$name}(" . $this->literal() . ');';
				}
			break;
			case 'visible':
				$data = $this->literal() . '.visible();';
			break;
			case 'update':
				$data = $this->literal() . ".update({$args[0]});";
			break;
			case 'load':
				$data = 'new Ajax.Updater("' . $this->id . '", "' . $args[0] . '"';
				if (isset($args[1]) && is_array($args[1])) {
					$data .= ', {' . $this->__options($args[1]) . '}';
				}
				$data .= ');';
			break;
			case 'each':
			case 'all':
			case 'any':
			case 'detect':
			case 'findAll':
				if ($this->pattern != null) {
					$data = $this->__iterate($name, $args[0]);
				}
			break;
			case 'addClass':
			case 'removeClass':
			case 'hasClass':
			case 'toggleClass':
				$data = $this->literal() . ".{$name}Name(\"{$args[0]}\");";
			break;
			case 'clone':
			case 'inspect':
			case 'keys':
			case 'values':
				$data = "Object.{$name}(" . $this->literal() . ");";
			break;
			case 'extend':
				$data = "Object.extend(" . $this->literal() . ", {$args[0]});";
			break;
			case '...':
				// Handle other methods here
				// including interfaces to load other files on-the-fly
				// that add support for additional methods/replacing existing methods
			break;
			default:
				$data = $this->literal() . '.' . $name . '();';
			break;
		}

		if ($this->__parent->output) {
			echo $data;
		} else {
			return $data;
		}
	}

	function __iterate($method, $data) {
		return '$$("' . $this->pattern . '").' . $method . '(function(item) {' . $data . '});';
	}

	function setParent(&$parent) {
		$this->__parent =& $parent;
	}

	function __options($opts) {
		$options = array();
		foreach ($opts as $key => $val) {
			if (!is_int($val)) {
				$val = '"' . $val . '"';
			}
			$options[] = $key . ':' . $val;
		}
		return join(', ', $options);
	}
}
?>