<?php
/* SVN FILE: $Id$ */

/**
 * Javascript Helper class file.
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
 * @subpackage		cake.cake.libs.view.helpers
 * @since			CakePHP v 0.10.0.1076
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Javascript Helper class for easy use of JavaScript.
 *
 * JavascriptHelper encloses all methods needed while working with JavaScript.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.view.helpers
 */

class JavascriptHelper extends Helper{

	var $_cachedEvents = array();
	var $_cacheEvents = false;
	var $_cacheToFile = false;
	var $_cacheAll = false;
	var $_rules = array();
	var $safe = false;

/**
 * Returns a JavaScript script tag.
 *
 * @param  string $script The JavaScript to be wrapped in SCRIPT tags.
 * @param  boolean $allowCache Allows the script to be cached if non-event caching is active
 * @param  boolean $safe Wraps the script in an HTML comment and a CDATA block
 * @return string The full SCRIPT element, with the JavaScript inside it.
 */
	function codeBlock($script = null, $allowCache = true, $safe = false) {
		if ($this->_cacheEvents && $this->_cacheAll && $allowCache && $script !== null) {
			$this->_cachedEvents[] = $script;
		} else {
			$block = ($script !== null);
			if ($safe || $this->safe) {
				$script  = "\n" . '<!--//--><![CDATA[//><!--' . "\n" . $script;
				if ($block) {
					$script .= "\n" . '//--><!]]>' . "\n";
				}
			}

			if ($block) {
				return sprintf($this->tags['javascriptblock'], $script);
			} else {
				return sprintf($this->tags['javascriptstart'], $script);
			}
		}
	}
/**
 * Returns a JavaScript include tag (SCRIPT element)
 *
 * @param  string $url URL to JavaScript file.
 * @return string
 */
	function link($url) {
		if (strpos($url, '.') === false && strpos($url, '?') === false) {
			$url .= '.js';
		}
		if (strpos($url, '://') === false) {
			$url = $this->webroot . $this->themeWeb . JS_URL . $url;
		}
		return sprintf($this->tags['javascriptlink'], $url);
	}
/**
 * Returns a JavaScript include tag for an externally-hosted script
 *
 * @param  string $url URL to JavaScript file.
 * @return string
 * @deprecated As of 1.2, use JavascriptHelper::link()
 */
	function linkOut($url) {
		if (strpos($url, ".") === false) {
			$url .= ".js";
		}
		return sprintf($this->tags['javascriptlink'], $url);
	}
/**
 * Escape carriage returns and single and double quotes for JavaScript segments.
 *
 * @param string $script string that might have javascript elements
 * @return string escaped string
 */
	function escapeScript($script) {
		$script = r(array("\r\n", "\n", "\r"), '\n', $script);
		$script = r(array('"', "'"), array('\"', "\\'"), $script);
		return $script;
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
 */
	function escapeString($string) {
		$escape = array("\r\n" => '\n', "\r" => '\n', "\n" => '\n', '"' => '\"', "'" => "\\'");
		return r(array_keys($escape), array_values($escape), $string);
	}
/**
 * Attach an event to an element. Used with the Prototype library.
 *
 * @param string $object Object to be observed
 * @param string $event event to observe
 * @param string $observer function to call
 * @param boolean $useCapture default true
 * @return boolean true on success
 */
	function event($object, $event, $observer = null, $useCapture = false) {

		if ($useCapture == true) {
			$useCapture = 'true';
		} else {
			$useCapture = 'false';
		}

		if (strpos($object, 'window') !== false || strpos($object, 'document') !== false || strpos($object, '$(') !== false || strpos($object, '"') !== false || strpos($object, '\'') !== false) {
			$b = "Event.observe({$object}, '{$event}', function(event){ {$observer} }, {$useCapture});";
		} elseif (strpos($object, '\'') === 0) {
			$b = "Event.observe(" . substr($object, 1) . ", '{$event}', function(event){ {$observer} }, {$useCapture});";
		} else {
			$chars = array('#', ' ', ', ', '.', ':');
			$found = false;
			foreach ($chars as $char) {
				if (strpos($object, $char) !== false) {
					$found = true;
					break;
				}
			}
			if ($found) {
				$this->_rules[$object] = $event;
			} else {
				$b = "Event.observe(\$('{$object}'), '{$event}', function(event){ {$observer} }, {$useCapture});";
			}
		}

		if (isset($b) && !empty($b)) {
			if ($this->_cacheEvents === true) {
				$this->_cachedEvents[] = $b;
				return;
			} else {
				return $this->codeBlock($b);
			}
		}
	}
/**
 * Cache JavaScript events created with event()
 *
 * @param boolean $file If true, code will be written to a file
 * @param boolean $all If true, all code written with JavascriptHelper will be sent to a file
 * @return null
 */
	function cacheEvents($file = false, $all = false) {
		$this->_cacheEvents = true;
		$this->_cacheToFile = $file;
		$this->_cacheAll = $all;
	}
/**
 * Write cached JavaScript events
 *
 * @return string
 */
	function writeEvents() {

		$rules = array();
		if (!empty($this->_rules)) {
			foreach ($this->_rules as $sel => $event) {
				$rules[] = "\t'{$sel}': function(element, event) {\n\t\t{$event}\n\t}";
			}
			$this->_cacheEvents = true;
		}

		if ($this->_cacheEvents) {

			$this->_cacheEvents = false;
			$events = $this->_cachedEvents;
			$data = implode("\n", $events);
			$this->_cachedEvents = array();

			if (!empty($rules)) {
				$data .= "\nEventSelectors.start({\n" . implode(",\n\n", $rules) . "\n});\n";
			}

			if (!empty($events) || !empty($rules)) {
				if ($this->_cacheToFile) {
					$filename = md5($data);
					if (!file_exists(JS . $filename . '.js')) {
						cache(r(WWW_ROOT, '', JS) . $filename . '.js', $data, '+999 days', 'public');
					}
					return $this->link($filename);
				} else {
					return $this->codeBlock("\n" . $data . "\n");
				}
			}
		}
	}
/**
 * Includes the Prototype Javascript library (and anything else) inside a single script tag.
 *
 * Note: The recommended approach is to copy the contents of
 * javascripts into your application's
 * public/javascripts/ directory, and use @see javascriptIncludeTag() to
 * create remote script links.
 * @return string script with all javascript in/javascripts folder
 */
	function includeScript($script = "") {
		if ($script == "") {
			$files = scandir(JS);
			$javascript = '';

			foreach($files as $file) {
				if (substr($file, -3) == '.js') {
					$javascript .= file_get_contents(JS . "{$file}") . "\n\n";
				}
			}
		} else {
			$javascript = file_get_contents(JS . "$script.js") . "\n\n";
		}
		return $this->codeBlock("\n\n" . $javascript);
	}
/**
 * Generates a JavaScript object in JavaScript Object Notation (JSON)
 * from an array
 *
 * @param array $data Data to be converted
 * @param boolean $block Wraps return value in a <script/> block if true
 * @param string $prefix Prepends the string to the returned data
 * @param string $postfix Appends the string to the returned data
 * @param array $stringKeys A list of array keys to be treated as a string
 * @param boolean $quoteKeys If false, treats $stringKey as a list of keys *not* to be quoted
 * @param string $q The type of quote to use
 * @return string A JSON code block
 */
	function object($data = array(), $block = false, $prefix = '', $postfix = '', $stringKeys = array(), $quoteKeys = true, $q = "\"") {
		if (is_object($data)) {
			$data = get_object_vars($data);
		}

		$out = array();
		$key = array();

		if (is_array($data)) {
			$keys = array_keys($data);
		}

		$numeric = true;

		if (!empty($keys)) {
			foreach($keys as $key) {
				if (!is_numeric($key)) {
					$numeric = false;
					break;
				}
			}
		}

		foreach($data as $key => $val) {
			if (is_array($val) || is_object($val)) {
				$val = $this->object($val, false, '', '', $stringKeys, $quoteKeys, $q);
			} else {
				if ((!count($stringKeys) && !is_numeric($val) && !is_bool($val)) || ($quoteKeys && in_array($key, $stringKeys)) || (!$quoteKeys && !in_array($key, $stringKeys))) {
					$val = $q . $this->escapeString($val) . $q;
				}
				if (trim($val) == '') {
					$val = 'null';
				}
			}

			if (!$numeric) {
				$val = $key . ':' . $val;
			}

			$out[] = $val;
		}

		if (!$numeric) {
			$rt = '{' . join(', ', $out) . '}';
		} else {
			$rt = '[' . join(', ', $out) . ']';
		}
		$rt = $prefix . $rt . $postfix;

		if ($block) {
			$rt = $this->codeBlock($rt);
		}

		return $rt;
	}
/**
 * AfterRender callback.  Writes any cached events to the view, or to a temp file.
 *
 * @return null
 */
	function afterRender() {
		echo $this->writeEvents();
	}
}

?>