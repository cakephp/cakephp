<?php
/**
 * Javascript Helper class file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view.helpers
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Javascript Helper class for easy use of JavaScript.
 *
 * JavascriptHelper encloses all methods needed while working with JavaScript.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.view.helpers
 * @link http://book.cakephp.org/view/1450/Javascript
 */
class JavascriptHelper extends AppHelper {

/**
 * Determines whether native JSON extension is used for encoding.  Set by object constructor.
 *
 * @var boolean
 * @access public
 */
	var $useNative = false;

/**
 * If true, automatically writes events to the end of a script or to an external JavaScript file
 * at the end of page execution
 *
 * @var boolean
 * @access public
 */
	var $enabled = true;

/**
 * Indicates whether <script /> blocks should be written 'safely,' i.e. wrapped in CDATA blocks
 *
 * @var boolean
 * @access public
 */
	var $safe = false;

/**
 * HTML tags used by this helper.
 *
 * @var array
 * @access public
 */
	var $tags = array(
		'javascriptstart' => '<script type="text/javascript">',
		'javascriptend' => '</script>',
		'javascriptblock' => '<script type="text/javascript">%s</script>',
		'javascriptlink' => '<script type="text/javascript" src="%s"></script>'
	);

/**
 * Holds options passed to codeBlock(), saved for when block is dumped to output
 *
 * @var array
 * @access protected
 * @see JavascriptHelper::codeBlock()
 */
	var $_blockOptions = array();

/**
 * Caches events written by event() for output at the end of page execution
 *
 * @var array
 * @access protected
 * @see JavascriptHelper::event()
 */
	var $_cachedEvents = array();

/**
 * Indicates whether generated events should be cached for later output (can be written at the
 * end of the page, in the <head />, or to an external file).
 *
 * @var boolean
 * @access protected
 * @see JavascriptHelper::event()
 * @see JavascriptHelper::writeEvents()
 */
	var $_cacheEvents = false;

/**
 * Indicates whether cached events should be written to an external file
 *
 * @var boolean
 * @access protected
 * @see JavascriptHelper::event()
 * @see JavascriptHelper::writeEvents()
 */
	var $_cacheToFile = false;

/**
 * Indicates whether *all* generated JavaScript should be cached for later output
 *
 * @var boolean
 * @access protected
 * @see JavascriptHelper::codeBlock()
 * @see JavascriptHelper::blockEnd()
 */
	var $_cacheAll = false;

/**
 * Contains event rules attached with CSS selectors.  Used with the event:Selectors JavaScript
 * library.
 *
 * @var array
 * @access protected
 * @see JavascriptHelper::event()
 * @link          http://alternateidea.com/event-selectors/
 */
	var $_rules = array();

/**
 * @var string
 * @access private
 */
	var $__scriptBuffer = null;

/**
 * Constructor. Checks for presence of native PHP JSON extension to use for object encoding
 *
 * @access public
 */
	function __construct($options = array()) {
		if (!empty($options)) {
			foreach ($options as $key => $val) {
				if (is_numeric($key)) {
					$key = $val;
					$val = true;
				}
				switch ($key) {
					case 'cache':

					break;
					case 'safe':
						$this->safe = $val;
					break;
				}
			}
		}
		$this->useNative = function_exists('json_encode');
		return parent::__construct($options);
	}

/**
 * Returns a JavaScript script tag.
 *
 * Options:
 *
 *  - allowCache: boolean, designates whether this block is cacheable using the
 * current cache settings.
 *  - safe: boolean, whether this block should be wrapped in CDATA tags.  Defaults
 * to helper's object configuration.
 *  - inline: whether the block should be printed inline, or written
 * to cached for later output (i.e. $scripts_for_layout).
 *
 * @param string $script The JavaScript to be wrapped in SCRIPT tags.
 * @param array $options Set of options:
 * @return string The full SCRIPT element, with the JavaScript inside it, or null,
 *   if 'inline' is set to false.
 */
	function codeBlock($script = null, $options = array()) {
		if (!empty($options) && !is_array($options)) {
			$options = array('allowCache' => $options);
		} elseif (empty($options)) {
			$options = array();
		}
		$defaultOptions = array('allowCache' => true, 'safe' => true, 'inline' => true);
		$options = array_merge($defaultOptions, $options);

		if (empty($script)) {
			$this->__scriptBuffer = @ob_get_contents();
			$this->_blockOptions = $options;
			$this->inBlock = true;
			@ob_end_clean();
			ob_start();
			return null;
		}
		if ($this->_cacheEvents && $this->_cacheAll && $options['allowCache']) {
			$this->_cachedEvents[] = $script;
			return null;
		}
		if ($options['safe'] || $this->safe) {
			$script  = "\n" . '//<![CDATA[' . "\n" . $script . "\n" . '//]]>' . "\n";
		}
		if ($options['inline']) {
			return sprintf($this->tags['javascriptblock'], $script);
		} else {
			$view =& ClassRegistry::getObject('view');
			$view->addScript(sprintf($this->tags['javascriptblock'], $script));
		}
	}

/**
 * Ends a block of cached JavaScript code
 *
 * @return mixed
 */
	function blockEnd() {
		if (!isset($this->inBlock) || !$this->inBlock) {
			return;
		}
		$script = @ob_get_contents();
		@ob_end_clean();
		ob_start();
		echo $this->__scriptBuffer;
		$this->__scriptBuffer = null;
		$options = $this->_blockOptions;
		$this->_blockOptions = array();
		$this->inBlock = false;

		if (empty($script)) {
			return null;
		}

		return $this->codeBlock($script, $options);
	}

/**
 * Returns a JavaScript include tag (SCRIPT element).  If the filename is prefixed with "/",
 * the path will be relative to the base path of your application.  Otherwise, the path will
 * be relative to your JavaScript path, usually webroot/js.
 *
 * @param mixed $url String URL to JavaScript file, or an array of URLs.
 * @param boolean $inline If true, the <script /> tag will be printed inline,
 *   otherwise it will be printed in the <head />, using $scripts_for_layout
 * @see JS_URL
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

		if (strpos($url, '://') === false) {
			if ($url[0] !== '/') {
				$url = JS_URL . $url;
			}
			if (strpos($url, '?') === false) {
				if (substr($url, -3) !== '.js') {
					$url .= '.js';
				}
			}
			$url = $this->assetTimestamp($this->webroot($url));

			if (Configure::read('Asset.filter.js')) {
				$pos = strpos($url, JS_URL);
				if ($pos !== false) {
					$url = substr($url, 0, $pos) . 'cjs/' . substr($url, $pos + strlen(JS_URL));
				}
			}
		}
		$out = sprintf($this->tags['javascriptlink'], $url);

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
		App::import('Core', 'Multibyte');
		$escape = array("\r\n" => "\n", "\r" => "\n");
		$string = str_replace(array_keys($escape), array_values($escape), $string);
		return $this->_utf8ToHex($string);
	}

/**
 * Encode a string into JSON.  Converts and escapes necessary characters.
 *
 * @return void
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
				case $ord == 0x27:
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
		$isObject = (
			strpos($object, 'window') !== false || strpos($object, 'document') !== false ||
			strpos($object, '$(') !== false || strpos($object, '"') !== false ||
			strpos($object, '\'') !== false
		);

		if ($isObject) {
			$b = "Event.observe({$object}, '{$event}', function(event) { {$observer} }, ";
			$b .= "{$options['useCapture']});";
		} elseif ($object[0] == '\'') {
			$b = "Event.observe(" . substr($object, 1) . ", '{$event}', function(event) { ";
			$b .= "{$observer} }, {$options['useCapture']});";
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
				$b = "Event.observe(\$('{$object}'), '{$event}', function(event) { ";
				$b .= "{$observer} }, {$options['useCapture']});";
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

		if (!$this->_cacheEvents) {
			return;
		}
		$data = $this->getCache();

		if (empty($data)) {
			return;
		}

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
 * ### Options
 *
 * - block - Wraps the return value in a script tag if true. Default is false
 * - prefix - Prepends the string to the returned data. Default is ''
 * - postfix - Appends the string to the returned data. Default is ''
 * - stringKeys - A list of array keys to be treated as a string.
 * - quoteKeys - If false treats $stringKeys as a list of keys **not** to be quoted. Default is true.
 * - q - The type of quote to use. Default is '"'.  This option only affects the keys, not the values.
 *
 * @param array $data Data to be converted
 * @param array $options Set of options: block, prefix, postfix, stringKeys, quoteKeys, q
 * @return string A JSON code block
 */
	function object($data = array(), $options = array()) {
		if (!empty($options) && !is_array($options)) {
			$options = array('block' => $options);
		} else if (empty($options)) {
			$options = array();
		}

		$defaultOptions = array(
			'block' => false, 'prefix' => '', 'postfix' => '',
			'stringKeys' => array(), 'quoteKeys' => true, 'q' => '"'
		);
		$options = array_merge($defaultOptions, $options, array_filter(compact(array_keys($defaultOptions))));

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
					$val = $this->object(
						$val,
						array_merge($options, array('block' => false, 'prefix' => '', 'postfix' => ''))
					);
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
				$rt = '{' . implode(',', $out) . '}';
			} else {
				$rt = '[' . implode(',', $out) . ']';
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
 */
	function value($val, $quoteStrings = true) {
		switch (true) {
			case (is_array($val) || is_object($val)):
				$val = $this->object($val);
			break;
			case ($val === null):
				$val = 'null';
			break;
			case (is_bool($val)):
				$val = !empty($val) ? 'true' : 'false';
			break;
			case (is_int($val)):
				$val = $val;
			break;
			case (is_float($val)):
				$val = sprintf("%.11f", $val);
			break;
			default:
				$val = $this->escapeString($val);
				if ($quoteStrings) {
					$val = '"' . $val . '"';
				}
			break;
		}
		return $val;
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
