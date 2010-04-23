<?php
/**
 * Javascript Generator class file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake
 * @subpackage    cake.cake.libs.view.helpers
 * @since         CakePHP v 1.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Javascript Generator helper class for easy use of JavaScript.
 *
 * JsHelper provides an abstract interface for authoring JavaScript with a
 * given client-side library.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.view.helpers
 */
class JsHelper extends AppHelper {
/**
 * Whether or not you want scripts to be buffered or output.
 *
 * @var boolean
 * @access public
 */
	var $bufferScripts = true;

/**
 * helpers
 *
 * @var array
 * @access public
 */
	var $helpers = array('Html', 'Form');

/**
 * Variables to pass to Javascript.
 *
 * @var array
 * @see JsHelper::set()
 * @access private
 */
	var $__jsVars = array();

/**
 * Scripts that are queued for output
 *
 * @var array
 * @see JsHelper::buffer()
 * @access private
 */
	var $__bufferedScripts = array();

/**
 * Current Javascript Engine that is being used
 *
 * @var string
 * @access private
 */
	var $__engineName;

/**
 * The javascript variable created by set() variables.
 *
 * @var string
 * @access public
 */
	var $setVariable = APP_DIR;

/**
 * Constructor - determines engine helper
 *
 * @param array $settings Settings array contains name of engine helper.
 * @return void
 * @access public
 */
	function __construct($settings = array()) {
		$className = 'Jquery';
		if (is_array($settings) && isset($settings[0])) {
			$className = $settings[0];
		} elseif (is_string($settings)) {
			$className = $settings;
		}
		$engineName = $className;
		list($plugin, $className) = pluginSplit($className);

		$this->__engineName = $className . 'Engine';
		$engineClass = $engineName . 'Engine';
		$this->helpers[] = $engineClass;
		parent::__construct();
	}

/**
 * call__ Allows for dispatching of methods to the Engine Helper.
 * methods in the Engines bufferedMethods list will be automatically buffered.
 * You can control buffering with the buffer param as well. By setting the last parameter to
 * any engine method to a boolean you can force or disable buffering.
 *
 * e.g. `$js->get('#foo')->effect('fadeIn', array('speed' => 'slow'), true);`
 *
 * Will force buffering for the effect method. If the method takes an options array you may also add
 * a 'buffer' param to the options array and control buffering there as well.
 *
 * e.g. `$js->get('#foo')->event('click', $functionContents, array('buffer' => true));`
 *
 * The buffer parameter will not be passed onto the EngineHelper.
 *
 * @param string $method Method to be called
 * @param array $params Parameters for the method being called.
 * @return mixed Depends on the return of the dispatched method, or it could be an instance of the EngineHelper
 * @access public
 */
	function call__($method, $params) {
		if (isset($this->{$this->__engineName}) && method_exists($this->{$this->__engineName}, $method)) {
			$buffer = false;
			if (in_array(strtolower($method), $this->{$this->__engineName}->bufferedMethods)) {
				$buffer = true;
			}
			if (count($params) > 0) {
				$lastParam = $params[count($params) - 1];
				$hasBufferParam = (is_bool($lastParam) || is_array($lastParam) && isset($lastParam['buffer']));
				if ($hasBufferParam && is_bool($lastParam)) {
					$buffer = $lastParam;
					unset($params[count($params) - 1]);
				} elseif ($hasBufferParam && is_array($lastParam)) {
					$buffer = $lastParam['buffer'];
					unset($params['buffer']);
				}
			}
			$out = $this->{$this->__engineName}->dispatchMethod($method, $params);
			if ($this->bufferScripts && $buffer && is_string($out)) {
				$this->buffer($out);
				return null;
			}
			if (is_object($out) && is_a($out, 'JsBaseEngineHelper')) {
				return $this;
			}
			return $out;
		}
		if (method_exists($this, $method . '_')) {
			return $this->dispatchMethod($method . '_', $params);
		}
		trigger_error(sprintf(__('JsHelper:: Missing Method %s is undefined', true), $method), E_USER_WARNING);
	}

/**
 * Workaround for Object::Object() existing. Since Object::object exists, it does not
 * fall into call__ and is not passed onto the engine helper. See JsBaseEngineHelper::object() for
 * more information on this method.
 *
 * @param mixed $data Data to convert into JSON
 * @param array $options Options to use for encoding JSON.  See JsBaseEngineHelper::object() for more details.
 * @return string encoded JSON
 * @deprecated Remove when support for PHP4 and Object::object are removed.
 * @access public
 */
	function object($data = array(), $options = array()) {
		return $this->{$this->__engineName}->object($data, $options);
	}

/**
 * Overwrite inherited Helper::value()
 * See JsBaseEngineHelper::value() for more information on this method.
 *
 * @param mixed $val A PHP variable to be converted to JSON
 * @param boolean $quoteStrings If false, leaves string values unquoted
 * @return string a JavaScript-safe/JSON representation of $val
 * @access public
 **/
	function value($val, $quoteString = true) {
		return $this->{$this->__engineName}->value($val, $quoteString);
	}

/**
 * Writes all Javascript generated so far to a code block or
 * caches them to a file and returns a linked script.  If no scripts have been
 * buffered this method will return null.  If the request is an XHR(ajax) request
 * onDomReady will be set to false. As the dom is already 'ready'.
 *
 * ### Options
 *
 * - `inline` - Set to true to have scripts output as a script block inline
 *   if `cache` is also true, a script link tag will be generated. (default true)
 * - `cache` - Set to true to have scripts cached to a file and linked in (default false)
 * - `clear` - Set to false to prevent script cache from being cleared (default true)
 * - `onDomReady` - wrap cached scripts in domready event (default true)
 * - `safe` - if an inline block is generated should it be wrapped in <![CDATA[ ... ]]> (default true)
 *
 * @param array $options options for the code block
 * @return mixed Completed javascript tag if there are scripts, if there are no buffered
 *   scripts null will be returned.
 * @access public
 */
	function writeBuffer($options = array()) {
		$domReady = isset($this->params['isAjax']) ? !$this->params['isAjax'] : true;
		$defaults = array(
			'onDomReady' => $domReady, 'inline' => true, 
			'cache' => false, 'clear' => true, 'safe' => true
		);
		$options = array_merge($defaults, $options);
		$script = implode("\n", $this->getBuffer($options['clear']));

		if (empty($script)) {
			return null;
		}

		if ($options['onDomReady']) {
			$script = $this->{$this->__engineName}->domReady($script);
		}
		$opts = $options;
		unset($opts['onDomReady'], $opts['cache'], $opts['clear']);

		if (!$options['cache'] && $options['inline']) {
			return $this->Html->scriptBlock($script, $opts);
		}

		if ($options['cache'] && $options['inline']) {
			$filename = md5($script);
			if (!file_exists(JS . $filename . '.js')) {
				cache(str_replace(WWW_ROOT, '', JS) . $filename . '.js', $script, '+999 days', 'public');
			}
			return $this->Html->script($filename);
		}
		$this->Html->scriptBlock($script, $opts);
		return null;
	}

/**
 * Write a script to the buffered scripts.
 *
 * @param string $script Script string to add to the buffer.
 * @param boolean $top If true the script will be added to the top of the 
 *   buffered scripts array.  If false the bottom.
 * @return void
 * @access public
 */
	function buffer($script, $top = false) {
		if ($top) {
			array_unshift($this->__bufferedScripts, $script);
		} else {
			$this->__bufferedScripts[] = $script;
		}
	}

/**
 * Get all the buffered scripts
 *
 * @param boolean $clear Whether or not to clear the script caches (default true)
 * @return array Array of scripts added to the request.
 * @access public
 */
	function getBuffer($clear = true) {
		$this->_createVars();
		$scripts = $this->__bufferedScripts;
		if ($clear) {
			$this->__bufferedScripts = array();
			$this->__jsVars = array();
		}
		return $scripts;
	}

/**
 * Generates the object string for variables passed to javascript.
 *
 * @return string Generated JSON object of all set vars
 * @access protected
 */
	function _createVars() {
		if (!empty($this->__jsVars)) {
			$setVar = (strpos($this->setVariable, '.')) ? $this->setVariable : 'window.' . $this->setVariable;
			$this->buffer($setVar . ' = ' . $this->object($this->__jsVars) . ';', true);
		}
	}

/**
 * Generate an 'Ajax' link.  Uses the selected JS engine to create a link
 * element that is enhanced with Javascript.  Options can include
 * both those for HtmlHelper::link() and JsBaseEngine::request(), JsBaseEngine::event();
 *
 * ### Options
 *
 * - `confirm` - Generate a confirm() dialog before sending the event.
 * - `id` - use a custom id.
 * - `htmlAttributes` - additional non-standard htmlAttributes.  Standard attributes are class, id,
 *    rel, title, escape, onblur and onfocus.
 * - `buffer` - Disable the buffering and return a script tag in addition to the link.
 *
 * @param string $title Title for the link.
 * @param mixed $url Mixed either a string URL or an cake url array.
 * @param array $options Options for both the HTML element and Js::request()
 * @return string Completed link. If buffering is disabled a script tag will be returned as well.
 * @access public
 */
	function link($title, $url = null, $options = array()) {
		if (!isset($options['id'])) {
			$options['id'] = 'link-' . intval(mt_rand());
		}
		list($options, $htmlOptions) = $this->_getHtmlOptions($options);
		$out = $this->Html->link($title, $url, $htmlOptions);
		$this->get('#' . $htmlOptions['id']);
		$requestString = '';
		if (isset($options['confirm'])) {
			$requestString = $this->confirmReturn($options['confirm']);
			unset($options['confirm']);
		}
		$requestString .= $this->request($url, $options);
		if (!empty($requestString)) {
			$event = $this->event('click', $requestString, $options);
		}
		if (isset($options['buffer']) && $options['buffer'] == false) {
			$opts = array_intersect_key(array('safe' => null), $options);
			$out .= $this->Html->scriptBlock($event, $opts);
		}
		return $out;
	}

/**
 * Pass variables into Javascript.  Allows you to set variables that will be
 * output when the buffer is fetched with `JsHelper::getBuffer()` or `JsHelper::writeBuffer()`
 * The Javascript variable used to output set variables can be controlled with `JsHelper::$setVariable`
 *
 * @param mixed $one Either an array of variables to set, or the name of the variable to set.
 * @param mixed $two If $one is a string, $two is the value for that key.
 * @return void
 * @access public
 */
	function set($one, $two = null) {
		$data = null;
		if (is_array($one)) {
			if (is_array($two)) {
				$data = array_combine($one, $two);
			} else {
				$data = $one;
			}
		} else {
			$data = array($one => $two);
		}
		if ($data == null) {
			return false;
		}
		$this->__jsVars = array_merge($this->__jsVars, $data);
	}

/**
 * Uses the selected JS engine to create a submit input
 * element that is enhanced with Javascript.  Options can include
 * both those for FormHelper::submit() and JsBaseEngine::request(), JsBaseEngine::event();
 *
 * Forms submitting with this method, cannot send files. Files do not transfer over XmlHttpRequest
 * and require an iframe or flash.
 *
 * @param string $title The display text of the submit button.
 * @param array $options Array of options to use.
 * @return string Completed submit button.
 * @access public
 */
	function submit($caption = null, $options = array()) {
		if (!isset($options['id'])) {
			$options['id'] = 'submit-' . intval(mt_rand());
		}
		$formOptions = array('div');
		list($options, $htmlOptions) = $this->_getHtmlOptions($options, $formOptions);
		$out = $this->Form->submit($caption, $htmlOptions);

		$this->get('#' . $htmlOptions['id']);

		$options['data'] = $this->serializeForm(array('isForm' => false, 'inline' => true));
		$requestString = $url = '';
		if (isset($options['confirm'])) {
			$requestString = $this->confirmReturn($options['confirm']);
			unset($options['confirm']);
		}
		if (isset($options['url'])) {
			$url = $options['url'];
			unset($options['url']);
		}
		if (!isset($options['method'])) {
			$options['method'] = 'post';
		}
		$options['dataExpression'] = true;
		$requestString .= $this->request($url, $options);
		if (!empty($requestString)) {
			$event = $this->event('click', $requestString, $options);
		}
		if (isset($options['buffer']) && $options['buffer'] == false) {
			$opts = array_intersect_key(array('safe' => null), $options);
			$out .= $this->Html->scriptBlock($event, $opts);
		}
		return $out;
	}

/**
 * Parse a set of Options and extract the Html options.
 * Extracted Html Options are removed from the $options param.
 *
 * @param array $options Options to filter.
 * @param array $additional Array of additional keys to extract and include in the return options array.
 * @return array Array of js options and Htmloptions
 * @access protected
 */
	function _getHtmlOptions($options, $additional = array()) {
		$htmlKeys = array_merge(array('class', 'id', 'escape', 'onblur', 'onfocus', 'rel', 'title'), $additional);
		$htmlOptions = array();
		foreach ($htmlKeys as $key) {
			if (isset($options[$key])) {
				$htmlOptions[$key] = $options[$key];
			}
			unset($options[$key]);
		}
		if (isset($options['htmlAttributes'])) {
			$htmlOptions = array_merge($htmlOptions, $options['htmlAttributes']);
			unset($options['htmlAttributes']);
		}
		return array($options, $htmlOptions);
	}
}

/**
 * JsEngineBaseClass
 *
 * Abstract Base Class for All JsEngines to extend. Provides generic methods.
 *
 * @package cake.view.helpers
 */
class JsBaseEngineHelper extends AppHelper {
/**
 * Determines whether native JSON extension is used for encoding.  Set by object constructor.
 *
 * @var boolean
 * @access public
 */
	var $useNative = false;

/**
 * The js snippet for the current selection.
 *
 * @var string
 * @access public
 */
	var $selection;

/**
 * Collection of option maps. Option maps allow other helpers to use generic names for engine
 * callbacks and options.  Allowing uniform code access for all engine types.  Their use is optional
 * for end user use though.
 *
 * @var array
 * @access protected
 */
	var $_optionMap = array();

/**
 * An array of lowercase method names in the Engine that are buffered unless otherwise disabled.
 * This allows specific 'end point' methods to be automatically buffered by the JsHelper.
 *
 * @var array
 * @access public
 */
	var $bufferedMethods = array('event', 'sortable', 'drag', 'drop', 'slider');

/**
 * Contains a list of callback names -> default arguments.
 *
 * @var array
 * @access protected
 */
	var $_callbackArguments = array();

/**
 * Constructor.
 *
 * @return void
 */
	function __construct() {
		$this->useNative = function_exists('json_encode');
	}

/**
 * Create an `alert()` message in Javascript
 *
 * @param string $message Message you want to alter.
 * @return string completed alert()
 * @access public
 */
	function alert($message) {
		return 'alert("' . $this->escape($message) . '");';
	}

/**
 * Redirects to a URL.  Creates a window.location modification snippet
 * that can be used to trigger 'redirects' from Javascript.
 *
 * @param mixed $url
 * @param array  $options
 * @return string completed redirect in javascript
 * @access public
 */
	function redirect($url = null) {
		return 'window.location = "' . Router::url($url) . '";';
	}

/**
 * Create a `confirm()` message
 *
 * @param string $message Message you want confirmed.
 * @return string completed confirm()
 * @access public
 */
	function confirm($message) {
		return 'confirm("' . $this->escape($message) . '");';
	}

/**
 * Generate a confirm snippet that returns false from the current
 * function scope.
 *
 * @param string $message Message to use in the confirm dialog.
 * @return string completed confirm with return script
 * @access public
 */
	function confirmReturn($message) {
		$out = 'var _confirm = ' . $this->confirm($message);
		$out .= "if (!_confirm) {\n\treturn false;\n}";
		return $out;
	}

/**
 * Create a `prompt()` Javascript function
 *
 * @param string $message Message you want to prompt.
 * @param string $default Default message
 * @return string completed prompt()
 * @access public
 */
	function prompt($message, $default = '') {
		return 'prompt("' . $this->escape($message) . '", "' . $this->escape($default) . '");';
	}

/**
 * Generates a JavaScript object in JavaScript Object Notation (JSON)
 * from an array.  Will use native JSON encode method if available, and $useNative == true
 *
 * ### Options:
 *
 * - `prefix` - String prepended to the returned data.
 * - `postfix` - String appended to the returned data.
 *
 * @param array $data Data to be converted.
 * @param array $options Set of options, see above.
 * @return string A JSON code block
 * @access public
 */
	function object($data = array(), $options = array()) {
		$defaultOptions = array(
			'prefix' => '', 'postfix' => '',
		);
		$options = array_merge($defaultOptions, $options);

		if (is_object($data)) {
			$data = get_object_vars($data);
		}

		$out = $keys = array();
		$numeric = true;

		if ($this->useNative && function_exists('json_encode')) {
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
					$val = $this->object($val);
				} else {
					$val = $this->value($val);
				}
				if (!$numeric) {
					$val = '"' . $this->value($key, false) . '":' . $val;
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
		return $rt;
	}

/**
 * Converts a PHP-native variable of any type to a JSON-equivalent representation
 *
 * @param mixed $val A PHP variable to be converted to JSON
 * @param boolean $quoteStrings If false, leaves string values unquoted
 * @return string a JavaScript-safe/JSON representation of $val
 * @access public
 */
	function value($val, $quoteString = true) {
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
				if ($quoteString) {
					$val = '"' . $val . '"';
				}
			break;
		}
		return $val;
	}

/**
 * Escape a string to be JSON friendly.
 *
 * List of escaped elements:
 *
 * - "\r" => '\n'
 * - "\n" => '\n'
 * - '"' => '\"'
 *
 * @param  string $script String that needs to get escaped.
 * @return string Escaped string.
 * @access public
 */
	function escape($string) {
		App::import('Core', 'Multibyte');
		return $this->_utf8ToHex($string);
	}

/**
 * Encode a string into JSON.  Converts and escapes necessary characters.
 *
 * @param string $string The string that needs to be utf8->hex encoded
 * @return void
 * @access protected
 */
	function _utf8ToHex($string) {
		$length = strlen($string);
		$return = '';
		for ($i = 0; $i < $length; ++$i) {
			$ord = ord($string{$i});
			switch (true) {
				case $ord == 0x08:
					$return .= '\b';
					break;
				case $ord == 0x09:
					$return .= '\t';
					break;
				case $ord == 0x0A:
					$return .= '\n';
					break;
				case $ord == 0x0C:
					$return .= '\f';
					break;
				case $ord == 0x0D:
					$return .= '\r';
					break;
				case $ord == 0x22:
				case $ord == 0x2F:
				case $ord == 0x5C:
					$return .= '\\' . $string{$i};
					break;
				case (($ord >= 0x20) && ($ord <= 0x7F)):
					$return .= $string{$i};
					break;
				case (($ord & 0xE0) == 0xC0):
					if ($i + 1 >= $length) {
						$i += 1;
						$return .= '?';
						break;
					}
					$charbits = $string{$i} . $string{$i + 1};
					$char = Multibyte::utf8($charbits);
					$return .= sprintf('\u%04s', dechex($char[0]));
					$i += 1;
					break;
				case (($ord & 0xF0) == 0xE0):
					if ($i + 2 >= $length) {
						$i += 2;
						$return .= '?';
						break;
					}
					$charbits = $string{$i} . $string{$i + 1} . $string{$i + 2};
					$char = Multibyte::utf8($charbits);
					$return .= sprintf('\u%04s', dechex($char[0]));
					$i += 2;
					break;
				case (($ord & 0xF8) == 0xF0):
					if ($i + 3 >= $length) {
					   $i += 3;
					   $return .= '?';
					   break;
					}
					$charbits = $string{$i} . $string{$i + 1} . $string{$i + 2} . $string{$i + 3};
					$char = Multibyte::utf8($charbits);
					$return .= sprintf('\u%04s', dechex($char[0]));
					$i += 3;
					break;
				case (($ord & 0xFC) == 0xF8):
					if ($i + 4 >= $length) {
					   $i += 4;
					   $return .= '?';
					   break;
					}
					$charbits = $string{$i} . $string{$i + 1} . $string{$i + 2} . $string{$i + 3} . $string{$i + 4};
					$char = Multibyte::utf8($charbits);
					$return .= sprintf('\u%04s', dechex($char[0]));
					$i += 4;
					break;
				case (($ord & 0xFE) == 0xFC):
					if ($i + 5 >= $length) {
					   $i += 5;
					   $return .= '?';
					   break;
					}
					$charbits = $string{$i} . $string{$i + 1} . $string{$i + 2} . $string{$i + 3} . $string{$i + 4} . $string{$i + 5};
					$char = Multibyte::utf8($charbits);
					$return .= sprintf('\u%04s', dechex($char[0]));
					$i += 5;
					break;
			}
		}
		return $return;
	}

/**
 * Create javascript selector for a CSS rule
 *
 * @param string $selector The selector that is targeted
 * @return object instance of $this. Allows chained methods.
 * @access public
 */
	function get($selector) {
		trigger_error(sprintf(__('%s does not have get() implemented', true), get_class($this)), E_USER_WARNING);
		return $this;
	}

/**
 * Add an event to the script cache. Operates on the currently selected elements.
 *
 * ### Options
 *
 * - `wrap` - Whether you want the callback wrapped in an anonymous function. (defaults to true)
 * - `stop` - Whether you want the event to stopped. (defaults to true)
 *
 * @param string $type Type of event to bind to the current dom id
 * @param string $callback The Javascript function you wish to trigger or the function literal
 * @param array $options Options for the event.
 * @return string completed event handler
 * @access public
 */
	function event($type, $callback, $options = array()) {
		trigger_error(sprintf(__('%s does not have event() implemented', true), get_class($this)), E_USER_WARNING);
	}

/**
 * Create a domReady event. This is a special event in many libraries
 *
 * @param string $functionBody The code to run on domReady
 * @return string completed domReady method
 * @access public
 */
	function domReady($functionBody) {
		trigger_error(sprintf(__('%s does not have domReady() implemented', true), get_class($this)), E_USER_WARNING);
	}

/**
 * Create an iteration over the current selection result.
 *
 * @param string $callback The function body you wish to apply during the iteration.
 * @return string completed iteration
 */
	function each($callback) {
		trigger_error(sprintf(__('%s does not have each() implemented', true), get_class($this)), E_USER_WARNING);
	}

/**
 * Trigger an Effect.
 *
 * ### Supported Effects
 *
 * The following effects are supported by all core JsEngines
 *
 * - `show` - reveal an element.
 * - `hide` - hide an element.
 * - `fadeIn` - Fade in an element.
 * - `fadeOut` - Fade out an element.
 * - `slideIn` - Slide an element in.
 * - `slideOut` - Slide an element out.
 *
 * ### Options
 *
 * - `speed` - Speed at which the animation should occur. Accepted values are 'slow', 'fast'. Not all effects use
 *   the speed option.
 *
 * @param string $name The name of the effect to trigger.
 * @param array $options Array of options for the effect.
 * @return string completed string with effect.
 * @access public
 */
	function effect($name, $options) {
		trigger_error(sprintf(__('%s does not have effect() implemented', true), get_class($this)), E_USER_WARNING);
	}

/**
 * Make an XHR request
 *
 * ### Event Options
 *
 * - `complete` - Callback to fire on complete.
 * - `success` - Callback to fire on success.
 * - `before` - Callback to fire on request initialization.
 * - `error` - Callback to fire on request failure.
 *
 * ### Options
 *
 * - `method` - The method to make the request with defaults to GET in more libraries
 * - `async` - Whether or not you want an asynchronous request.
 * - `data` - Additional data to send.
 * - `update` - Dom id to update with the content of the request.
 * - `type` - Data type for response. 'json' and 'html' are supported. Default is html for most libraries.
 * - `evalScripts` - Whether or not <script> tags should be eval'ed.
 * - `dataExpression` - Should the `data` key be treated as a callback.  Useful for supplying `$options['data']` as
 *    another Javascript expression.
 *
 * @param mixed $url Array or String URL to target with the request.
 * @param array $options Array of options. See above for cross library supported options
 * @return string XHR request.
 * @access public
 */
	function request($url, $options = array()) {
		trigger_error(sprintf(__('%s does not have request() implemented', true), get_class($this)), E_USER_WARNING);
	}

/**
 * Create a draggable element.  Works on the currently selected element.
 * Additional options may be supported by the library implementation.
 *
 * ### Options
 *
 * - `handle` - selector to the handle element.
 * - `snapGrid` - The pixel grid that movement snaps to, an array(x, y)
 * - `container` - The element that acts as a bounding box for the draggable element.
 *
 * ### Event Options
 *
 * - `start` - Event fired when the drag starts
 * - `drag` - Event fired on every step of the drag
 * - `stop` - Event fired when dragging stops (mouse release)
 *
 * @param array $options Options array see above.
 * @return string Completed drag script
 * @access public
 */
	function drag($options = array()) {
		trigger_error(sprintf(__('%s does not have drag() implemented', true), get_class($this)), E_USER_WARNING);
	}

/**
 * Create a droppable element. Allows for draggable elements to be dropped on it.
 * Additional options may be supported by the library implementation.
 *
 * ### Options
 *
 * - `accept` - Selector for elements this droppable will accept.
 * - `hoverclass` - Class to add to droppable when a draggable is over.
 *
 * ### Event Options
 *
 * - `drop` - Event fired when an element is dropped into the drop zone.
 * - `hover` - Event fired when a drag enters a drop zone.
 * - `leave` - Event fired when a drag is removed from a drop zone without being dropped.
 *
 * @return string Completed drop script
 * @access public
 */
	function drop($options = array()) {
		trigger_error(sprintf(__('%s does not have drop() implemented', true), get_class($this)), E_USER_WARNING);
	}

/**
 * Create a sortable element.
 * Additional options may be supported by the library implementation.
 *
 * ### Options
 *
 * - `containment` - Container for move action
 * - `handle` - Selector to handle element. Only this element will start sort action.
 * - `revert` - Whether or not to use an effect to move sortable into final position.
 * - `opacity` - Opacity of the placeholder
 * - `distance` - Distance a sortable must be dragged before sorting starts.
 *
 * ### Event Options
 *
 * - `start` - Event fired when sorting starts
 * - `sort` - Event fired during sorting
 * - `complete` - Event fired when sorting completes.
 *
 * @param array $options Array of options for the sortable. See above.
 * @return string Completed sortable script.
 * @access public
 */
	function sortable() {
		trigger_error(sprintf(__('%s does not have sortable() implemented', true), get_class($this)), E_USER_WARNING);
	}

/**
 * Create a slider UI widget.  Comprised of a track and knob.
 * Additional options may be supported by the library implementation.
 *
 * ### Options
 *
 * - `handle` - The id of the element used in sliding.
 * - `direction` - The direction of the slider either 'vertical' or 'horizontal'
 * - `min` - The min value for the slider.
 * - `max` - The max value for the slider.
 * - `step` - The number of steps or ticks the slider will have.
 * - `value` - The initial offset of the slider.
 *
 * ### Events
 *
 * - `change` - Fired when the slider's value is updated
 * - `complete` - Fired when the user stops sliding the handle
 *
 * @return string Completed slider script
 * @access public
 */
	function slider() {
		trigger_error(sprintf(__('%s does not have slider() implemented', true), get_class($this)), E_USER_WARNING);
	}

/**
 * Serialize the form attached to $selector.
 * Pass `true` for $isForm if the current selection is a form element.
 * Converts the form or the form element attached to the current selection into a string/json object
 * (depending on the library implementation) for use with XHR operations.
 *
 * ### Options
 *
 * - `isForm` - is the current selection a form, or an input? (defaults to false)
 * - `inline` - is the rendered statement going to be used inside another JS statement? (defaults to false)
 *
 * @param array $options options for serialization generation.
 * @return string completed form serialization script
 * @access public
 */
	function serializeForm() {
		trigger_error(
			sprintf(__('%s does not have serializeForm() implemented', true), get_class($this)), E_USER_WARNING
		);
	}

/**
 * Parse an options assoc array into an Javascript object literal.
 * Similar to object() but treats any non-integer value as a string,
 * does not include `{ }`
 *
 * @param array $options Options to be converted
 * @param array $safeKeys Keys that should not be escaped.
 * @return string Parsed JSON options without enclosing { }.
 * @access protected
 */
	function _parseOptions($options, $safeKeys = array()) {
		$out = array();
		$safeKeys = array_flip($safeKeys);
		foreach ($options as $key => $value) {
			if (!is_int($value) && !isset($safeKeys[$key])) {
				$value = $this->value($value);
			}
			$out[] = $key . ':' . $value;
		}
		sort($out);
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
 */
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

/**
 * Prepare callbacks and wrap them with function ([args]) { } as defined in
 * _callbackArgs array.
 *
 * @param string $method Name of the method you are preparing callbacks for.
 * @param array $options Array of options being parsed
 * @param string $callbacks Additional Keys that contain callbacks
 * @return array Array of options with callbacks added.
 * @access protected
 */
	function _prepareCallbacks($method, $options, $callbacks = array()) {
		$wrapCallbacks = true;
		if (isset($options['wrapCallbacks'])) {
			$wrapCallbacks = $options['wrapCallbacks'];
		}
		unset($options['wrapCallbacks']);
		if (!$wrapCallbacks) {
			return $options;
		}
		$callbackOptions = array();
		if (isset($this->_callbackArguments[$method])) {
			$callbackOptions = $this->_callbackArguments[$method];
		}
		$callbacks = array_unique(array_merge(array_keys($callbackOptions), (array)$callbacks));

		foreach ($callbacks as $callback) {
			if (empty($options[$callback])) {
				continue;
			}
			$args = null;
			if (!empty($callbackOptions[$callback])) {
				$args = $callbackOptions[$callback];
			}
			$options[$callback] = 'function (' . $args . ') {' . $options[$callback] . '}';
		}
		return $options;
	}

/**
 * Conveinence wrapper method for all common option processing steps.
 * Runs _mapOptions, _prepareCallbacks, and _parseOptions in order.
 *
 * @param string $method Name of method processing options for.
 * @param array $options Array of options to process.
 * @return string Parsed options string.
 * @access protected
 */
	function _processOptions($method, $options) {
		$options = $this->_mapOptions($method, $options);
		$options = $this->_prepareCallbacks($method, $options);
		$options = $this->_parseOptions($options, array_keys($this->_callbackArguments[$method]));
		return $options;
	}

/**
 * Convert an array of data into a query string
 *
 * @param array $parameters Array of parameters to convert to a query string
 * @return string Querystring fragment
 * @access protected
 */
	function _toQuerystring($parameters) {
		$out = '';
		$keys = array_keys($parameters);
		$count = count($parameters);
		for ($i = 0; $i < $count; $i++) {
			$out .= $keys[$i] . '=' . $parameters[$keys[$i]];
			if ($i < $count - 1) {
				$out .= '&';
			}
		}
		return $out;
	}
}
?>