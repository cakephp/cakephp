<?php
/**
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Helper
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppHelper', 'View/Helper');

/**
 * JsEngineBaseClass
 *
 * Abstract Base Class for All JsEngines to extend. Provides generic methods.
 *
 * @package       Cake.View.Helper
 */
abstract class JsBaseEngineHelper extends AppHelper {

/**
 * The js snippet for the current selection.
 *
 * @var string
 */
	public $selection;

/**
 * Collection of option maps. Option maps allow other helpers to use generic names for engine
 * callbacks and options.  Allowing uniform code access for all engine types.  Their use is optional
 * for end user use though.
 *
 * @var array
 */
	protected $_optionMap = array();

/**
 * An array of lowercase method names in the Engine that are buffered unless otherwise disabled.
 * This allows specific 'end point' methods to be automatically buffered by the JsHelper.
 *
 * @var array
 */
	public $bufferedMethods = array('event', 'sortable', 'drag', 'drop', 'slider');

/**
 * Contains a list of callback names -> default arguments.
 *
 * @var array
 */
	protected $_callbackArguments = array();

/**
 * Create an `alert()` message in Javascript
 *
 * @param string $message Message you want to alter.
 * @return string completed alert()
 */
	public function alert($message) {
		return 'alert("' . $this->escape($message) . '");';
	}

/**
 * Redirects to a URL.  Creates a window.location modification snippet
 * that can be used to trigger 'redirects' from Javascript.
 *
 * @param mixed $url
 * @param array  $options
 * @return string completed redirect in javascript
 */
	public function redirect($url = null) {
		return 'window.location = "' . Router::url($url) . '";';
	}

/**
 * Create a `confirm()` message
 *
 * @param string $message Message you want confirmed.
 * @return string completed confirm()
 */
	public function confirm($message) {
		return 'confirm("' . $this->escape($message) . '");';
	}

/**
 * Generate a confirm snippet that returns false from the current
 * function scope.
 *
 * @param string $message Message to use in the confirm dialog.
 * @return string completed confirm with return script
 */
	public function confirmReturn($message) {
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
 */
	public function prompt($message, $default = '') {
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
 */
	public function object($data = array(), $options = array()) {
		$defaultOptions = array(
			'prefix' => '', 'postfix' => '',
		);
		$options = array_merge($defaultOptions, $options);

		return $options['prefix'] . json_encode($data) . $options['postfix'];
	}

/**
 * Converts a PHP-native variable of any type to a JSON-equivalent representation
 *
 * @param mixed $val A PHP variable to be converted to JSON
 * @param boolean $quoteString If false, leaves string values unquoted
 * @return string a JavaScript-safe/JSON representation of $val
 */
	public function value($val = array(), $quoteString = null, $key = 'value') {
		if ($quoteString === null) {
			$quoteString = true;
		}
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
 * @param string $string String that needs to get escaped.
 * @return string Escaped string.
 */
	public function escape($string) {
		return $this->_utf8ToHex($string);
	}

/**
 * Encode a string into JSON.  Converts and escapes necessary characters.
 *
 * @param string $string The string that needs to be utf8->hex encoded
 * @return void
 */
	protected function _utf8ToHex($string) {
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
 * @return JsBaseEngineHelper instance of $this. Allows chained methods.
 */
	abstract public function get($selector);

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
 */
	abstract public function event($type, $callback, $options = array());

/**
 * Create a domReady event. This is a special event in many libraries
 *
 * @param string $functionBody The code to run on domReady
 * @return string completed domReady method
 */
	abstract public function domReady($functionBody);

/**
 * Create an iteration over the current selection result.
 *
 * @param string $callback The function body you wish to apply during the iteration.
 * @return string completed iteration
 */
	abstract public function each($callback);

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
 */
	abstract public function effect($name, $options = array());

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
 */
	abstract public function request($url, $options = array());

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
 */
	abstract public function drag($options = array());

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
 * @param array $options Array of options for the drop. See above.
 * @return string Completed drop script
 */
	abstract public function drop($options = array());

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
 */
	abstract public function sortable($options = array());

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
 * @param array $options Array of options for the slider. See above.
 * @return string Completed slider script
 */
	abstract public function slider($options = array());

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
 */
	abstract public function serializeForm($options = array());

/**
 * Parse an options assoc array into an Javascript object literal.
 * Similar to object() but treats any non-integer value as a string,
 * does not include `{ }`
 *
 * @param array $options Options to be converted
 * @param array $safeKeys Keys that should not be escaped.
 * @return string Parsed JSON options without enclosing { }.
 */
	protected function _parseOptions($options, $safeKeys = array()) {
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
 */
	protected function _mapOptions($method, $options) {
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
 * @param array $callbacks Additional Keys that contain callbacks
 * @return array Array of options with callbacks added.
 */
	protected function _prepareCallbacks($method, $options, $callbacks = array()) {
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
 * Convenience wrapper method for all common option processing steps.
 * Runs _mapOptions, _prepareCallbacks, and _parseOptions in order.
 *
 * @param string $method Name of method processing options for.
 * @param array $options Array of options to process.
 * @return string Parsed options string.
 */
	protected function _processOptions($method, $options) {
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
 */
	protected function _toQuerystring($parameters) {
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
