<?php
/* SVN FILE: $Id$ */

/**
 * Javascript Helper class file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.view.helpers
 * @since			CakePHP(tm) v 0.10.0.1076
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

class JavascriptHelper extends AppHelper {
	var $__scriptBuffer = null;
	var $_cachedEvents = array();
	var $_cacheEvents = false;
	var $_cacheToFile = false;
	var $_cacheAll = false;
	var $_rules = array();
	var $enabled = true;
	var $safe = false;
/**
 * html tags used by this helper.
 *
 * @var array
 */
	var $tags = array(
		'javascriptblock' => '<script type="text/javascript">%s</script>',
		'javascriptstart' => '<script type="text/javascript">',
		'javascriptlink' => '<script type="text/javascript" src="%s"></script>',
		'javascriptend' => '</script>',
	);

/**
 * Returns a JavaScript script tag.
 *
 * @param string $script The JavaScript to be wrapped in SCRIPT tags.
 * @param array $options Set of options: allowCache, safe
 * @param boolean $safe DEPRECATED. Use $options['safe'] instead
 * @return string The full SCRIPT element, with the JavaScript inside it.
 */
	function codeBlock($script = null, $options = array(), $safe = null) {
		if (!empty($options) && !is_array($options)) {
			$options = array('allowCache' => $options);
		} else if (empty($options)) {
			$options = array();
		}

		$defaultOptions = array('allowCache' => true, 'safe' => true);
		$options = array_merge($defaultOptions, $options);

		foreach($defaultOptions as $option => $value) {
			if (isset($$option) && $$option !== null) {
				$options[$option] = $$option;
			}
		}

		if ($this->_cacheEvents && $this->_cacheAll && $options['allowCache'] && $script !== null) {
			$this->_cachedEvents[] = $script;
		} else {
			$block = ($script !== null);
			if (($options['safe'] || $this->safe) && !($this->_cacheAll && $options['allowCache'])) {
				$script  = "\n" . '//<![CDATA[' . "\n" . $script;
				if ($block) {
					$script .= "\n" . '//]]>' . "\n";
				}
			}

			if ($script === null && $this->_cacheAll && $options['allowCache']) {
				$this->__scriptBuffer = @ob_get_contents();
				@ob_end_clean();
				ob_start();
				return null;
			}

			if ($block) {
				return sprintf($this->tags['javascriptblock'], $script);
			} else {
				return sprintf($this->tags['javascriptstart'], $script);
			}
		}
	}
/**
 * Ends a block of cached JavaScript code
 *
 * @return mixed
 */
	function blockEnd() {
		$script = @ob_get_contents();
		@ob_end_clean();
		ob_start();
		echo $this->__scriptBuffer;
		$this->__scriptBuffer = null;

		if (!empty($script)) {
			$this->_cachedEvents[] = $script;
			return null;
		}
		return $this->tags['javascriptend'];
	}
/**
 * Returns a JavaScript include tag (SCRIPT element)
 *
 * @param  mixed  $url String URL to JavaScript file, or an array of URLs.
 * @param  boolean $inline If true, the <script /> tag will be printed inline,
 *                         otherwise it will be printed in the <head />
 * @return string
 */
	function link($url, $inline = true) {
		if (is_array($url)) {
			$out = '';
			foreach ($url as $i) {
				$out .= "\n\t" . $this->link($i, $inline);
			}
			if ($inline)  {
				return $out . "\n";
			}
			return;
		}

		if (strpos($url, '.js') === false && strpos($url, '?') === false) {
			$url .= '.js';
		}
		if (strpos($url, '://') === false) {
			$url = $this->webroot(JS_URL . $url);
		}
		$out = $this->output(sprintf($this->tags['javascriptlink'], $url));

		if ($inline) {
			return $out;
		} else {
			$view =& ClassRegistry::getObject('view');
			$view->addScript($out);
		}
	}
/**
 * Escape carriage returns and single and double quotes for JavaScript segments.
 *
 * @param string $script string that might have javascript elements
 * @return string escaped string
 */
	function escapeScript($script) {
		$script = str_replace(array("\r\n", "\n", "\r"), '\n', $script);
		$script = str_replace(array('"', "'"), array('\"', "\\'"), $script);
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
		return str_replace(array_keys($escape), array_values($escape), $string);
	}
/**
 * Attach an event to an element. Used with the Prototype library.
 *
 * @param string $object Object to be observed
 * @param string $event event to observe
 * @param string $observer function to call
 * @param array $options Set options: useCapture, allowCache, safe
 * @return boolean true on success
 */
	function event($object, $event, $observer = null, $options = array()) {
		if (!empty($options) && !is_array($options)) {
			$options = array('useCapture' => $options);
		} else if (empty($options)) {
			$options = array();
		}

		$defaultOptions = array('useCapture' => false);
		$options = array_merge($defaultOptions, $options);

		if ($options['useCapture'] == true) {
			$options['useCapture'] = 'true';
		} else {
			$options['useCapture'] = 'false';
		}

		if (strpos($object, 'window') !== false || strpos($object, 'document') !== false || strpos($object, '$(') !== false || strpos($object, '"') !== false || strpos($object, '\'') !== false) {
			$b = "Event.observe({$object}, '{$event}', function(event) { {$observer} }, {$options['useCapture']});";
		} elseif (strpos($object, '\'') === 0) {
			$b = "Event.observe(" . substr($object, 1) . ", '{$event}', function(event) { {$observer} }, {$options['useCapture']});";
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
				$b = "Event.observe(\$('{$object}'), '{$event}', function(event) { {$observer} }, {$options['useCapture']});";
			}
		}

		if (isset($b) && !empty($b)) {
			if ($this->_cacheEvents === true) {
				$this->_cachedEvents[] = $b;
				return;
			} else {
				return $this->codeBlock($b, array_diff_key($options, $defaultOptions));
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
 * Gets (and clears) the current JavaScript event cache
 *
 * @param boolean $clear
 * @return string
 */
	function getCache($clear = true) {
		$out = '';
		$rules = array();

		if (!empty($this->_rules)) {
			foreach ($this->_rules as $sel => $event) {
				$rules[] = "\t'{$sel}': function(element, event) {\n\t\t{$event}\n\t}";
			}
		}
		$data = implode("\n", $this->_cachedEvents);

		if (!empty($rules)) {
			$data .= "\nvar Rules = {\n" . implode(",\n\n", $rules) . "\n}";
			$data .= "\nEventSelectors.start(Rules);\n";
		}
		if ($clear) {
			$this->_rules = array();
			$this->_cacheEvents = false;
			$this->_cachedEvents = array();
		}
		return $data;
	}
/**
 * Write cached JavaScript events
 *
 * @param boolean $inline If true, returns JavaScript event code.  Otherwise it is added to the
 *                        output of $scripts_for_layout in the layout.
 * @param array $options Set options for codeBlock
 * @return string
 */
	function writeEvents($inline = true, $options = array()) {
		$out = '';
		$rules = array();

		if ($this->_cacheEvents) {
			$data = $this->getCache();

			if (!empty($data)) {
				if ($this->_cacheToFile) {
					$filename = md5($data);
					if (!file_exists(JS . $filename . '.js')) {
						cache(str_replace(WWW_ROOT, '', JS) . $filename . '.js', $data, '+999 days', 'public');
					}
					$out = $this->link($filename);
				} else {
					$out = $this->codeBlock("\n" . $data . "\n", $options);
				}
				if ($inline) {
					return $out;
				} else {
					$view =& ClassRegistry::getObject('view');
					$view->addScript($out);
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
 *
 * @param string $script Script file to include
 * @param array $options Set options for codeBlock
 * @return string script with all javascript in/javascripts folder
 */
	function includeScript($script = "", $options = array()) {
		if ($script == "") {
			$files = scandir(JS);
			$javascript = '';

			foreach ($files as $file) {
				if (substr($file, -3) == '.js') {
					$javascript .= file_get_contents(JS . "{$file}") . "\n\n";
				}
			}
		} else {
			$javascript = file_get_contents(JS . "$script.js") . "\n\n";
		}
		return $this->codeBlock("\n\n" . $javascript, $options);
	}
/**
 * Generates a JavaScript object in JavaScript Object Notation (JSON)
 * from an array
 *
 * @param array $data Data to be converted
 * @param array $options Set of options: block, prefix, postfix, stringKeys, quoteKeys, q
 * @param string $prefix DEPRECATED, use $options['prefix'] instead. Prepends the string to the returned data
 * @param string $postfix DEPRECATED, use $options['postfix'] instead. Appends the string to the returned data
 * @param array $stringKeys DEPRECATED, use $options['stringKeys'] instead. A list of array keys to be treated as a string
 * @param boolean $quoteKeys DEPRECATED, use $options['quoteKeys'] instead. If false, treats $stringKey as a list of keys *not* to be quoted
 * @param string $q DEPRECATED, use $options['q'] instead. The type of quote to use
 * @return string A JSON code block
 */
	function object($data = array(), $options = array(), $prefix = null, $postfix = null, $stringKeys = null, $quoteKeys = null, $q = null) {
		if (!empty($options) && !is_array($options)) {
			$options = array('block' => $options);
		} else if (empty($options)) {
			$options = array();
		}

		$defaultOptions = array('block' => false, 'prefix' => '', 'postfix' => '', 'stringKeys' => array(), 'quoteKeys' => true, 'q' => '"');
		$options = array_merge($defaultOptions, $options);

		foreach($defaultOptions as $option => $value) {
			if (isset($$option) && $$option !== null) {
				$options[$option] = $$option;
			}
		}

		if (is_object($data)) {
			$data = get_object_vars($data);
		}

		$out = array();
		$keys = array();

		if (is_array($data)) {
			$keys = array_keys($data);
		}

		$numeric = true;
		if (!empty($keys)) {
			$numeric = (array_values($keys) === array_keys(array_values($keys)));
		}

		foreach ($data as $key => $val) {
			if (is_array($val) || is_object($val)) {
				$val = $this->object($val, $options);
			} else {
				if ((!count($options['stringKeys']) && !is_numeric($val) && !is_bool($val)) || ($options['quoteKeys'] && in_array($key, $options['stringKeys'], true)) || (!$options['quoteKeys'] && !in_array($key, $options['stringKeys'], true))) {
					$val = $options['q'] . $this->escapeString($val) . $options['q'];
				}
				if ($val === null) {
					$val = 'null';
				}
				if (is_bool($val)) {
					$val = ife($val, 'true', 'false');
				}
			}

			if (!$numeric) {
				$val = $options['q'] . $key . $options['q'] . ':' . $val;
			}

			$out[] = $val;
		}

		if (!$numeric) {
			$rt = '{' . join(', ', $out) . '}';
		} else {
			$rt = '[' . join(', ', $out) . ']';
		}
		$rt = $options['prefix'] . $rt . $options['postfix'];

		if ($options['block']) {
			$rt = $this->codeBlock($rt, array_diff_key($options, $defaultOptions));
		}

		return $rt;
	}
/**
 * AfterRender callback.  Writes any cached events to the view, or to a temp file.
 *
 * @return null
 */
	function afterRender() {
		if (!$this->enabled) {
			return;
		}
		echo $this->writeEvents(true);
	}
}

?>