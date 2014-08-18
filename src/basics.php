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
 * @since         0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\I18n\I18n;
use Cake\Utility\Debugger;
use Cake\Collection\Collection;

/**
 * Basic defines for timing functions.
 */
	define('SECOND', 1);
	define('MINUTE', 60);
	define('HOUR', 3600);
	define('DAY', 86400);
	define('WEEK', 604800);
	define('MONTH', 2592000);
	define('YEAR', 31536000);

if (!function_exists('debug')) {

/**
 * Prints out debug information about given variable.
 *
 * Only runs if debug level is greater than zero.
 *
 * @param mixed $var Variable to show debug information for.
 * @param bool $showHtml If set to true, the method prints the debug data in a browser-friendly way.
 * @param bool $showFrom If set to true, the method prints from where the function was called.
 * @return void
 * @link http://book.cakephp.org/2.0/en/development/debugging.html#basic-debugging
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#debug
 */
	function debug($var, $showHtml = null, $showFrom = true) {
		if (!Configure::read('debug')) {
			return;
		}

		$file = '';
		$line = '';
		$lineInfo = '';
		if ($showFrom) {
			$trace = Debugger::trace(array('start' => 1, 'depth' => 2, 'format' => 'array'));
			$file = str_replace(array(CAKE_CORE_INCLUDE_PATH, ROOT), '', $trace[0]['file']);
			$line = $trace[0]['line'];
		}
		$html = <<<HTML
<div class="cake-debug-output">
%s
<pre class="cake-debug">
%s
</pre>
</div>
HTML;
		$text = <<<TEXT
%s
########## DEBUG ##########
%s
###########################

TEXT;
		$template = $html;
		if (php_sapi_name() === 'cli' || $showHtml === false) {
			$template = $text;
			if ($showFrom) {
				$lineInfo = sprintf('%s (line %s)', $file, $line);
			}
		}
		if ($showHtml === null && $template !== $text) {
			$showHtml = true;
		}
		$var = Debugger::exportVar($var, 25);
		if ($showHtml) {
			$template = $html;
			$var = h($var);
			if ($showFrom) {
				$lineInfo = sprintf('<span><strong>%s</strong> (line <strong>%s</strong>)</span>', $file, $line);
			}
		}
		printf($template, $lineInfo, $var);
	}

}

if (!function_exists('stackTrace')) {

/**
 * Outputs a stack trace based on the supplied options.
 *
 * ### Options
 *
 * - `depth` - The number of stack frames to return. Defaults to 999
 * - `args` - Should arguments for functions be shown? If true, the arguments for each method call
 *   will be displayed.
 * - `start` - The stack frame to start generating a trace from. Defaults to 1
 *
 * @param array $options Format for outputting stack trace
 * @return mixed Formatted stack trace
 * @see Debugger::trace()
 */
	function stackTrace(array $options = array()) {
		if (!Configure::read('debug')) {
			return;
		}

		$options += array('start' => 0);
		$options['start']++;
		echo Debugger::trace($options);
	}

}

if (!function_exists('h')) {

/**
 * Convenience method for htmlspecialchars.
 *
 * @param string|array|object $text Text to wrap through htmlspecialchars. Also works with arrays, and objects.
 *    Arrays will be mapped and have all their elements escaped. Objects will be string cast if they
 *    implement a `__toString` method. Otherwise the class name will be used.
 * @param bool $double Encode existing html entities
 * @param string $charset Character set to use when escaping. Defaults to config value in 'App.encoding' or 'UTF-8'
 * @return string Wrapped text
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#h
 */
	function h($text, $double = true, $charset = null) {
		if (is_string($text)) {
			//optimize for strings
		} elseif (is_array($text)) {
			$texts = array();
			foreach ($text as $k => $t) {
				$texts[$k] = h($t, $double, $charset);
			}
			return $texts;
		} elseif (is_object($text)) {
			if (method_exists($text, '__toString')) {
				$text = (string)$text;
			} else {
				$text = '(object)' . get_class($text);
			}
		} elseif (is_bool($text)) {
			return $text;
		}

		static $defaultCharset = false;
		if ($defaultCharset === false) {
			$defaultCharset = Configure::read('App.encoding');
			if ($defaultCharset === null) {
				$defaultCharset = 'UTF-8';
			}
		}
		if (is_string($double)) {
			$charset = $double;
		}
		return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, ($charset) ? $charset : $defaultCharset, $double);
	}

}

if (!function_exists('pluginSplit')) {

/**
 * Splits a dot syntax plugin name into its plugin and class name.
 * If $name does not have a dot, then index 0 will be null.
 *
 * Commonly used like `list($plugin, $name) = pluginSplit($name);`
 *
 * @param string $name The name you want to plugin split.
 * @param bool $dotAppend Set to true if you want the plugin to have a '.' appended to it.
 * @param string $plugin Optional default plugin to use if no plugin is found. Defaults to null.
 * @return array Array with 2 indexes. 0 => plugin name, 1 => class name
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#pluginSplit
 */
	function pluginSplit($name, $dotAppend = false, $plugin = null) {
		if (strpos($name, '.') !== false) {
			$parts = explode('.', $name, 2);
			if ($dotAppend) {
				$parts[0] .= '.';
			}
			return $parts;
		}
		return array($plugin, $name);
	}

}

if (!function_exists('namespaceSplit')) {

/**
 * Split the namespace from the classname.
 *
 * Commonly used like `list($namespace, $className) = namespaceSplit($class);`
 *
 * @param string $class The full class name, ie `Cake\Core\App`
 * @return array Array with 2 indexes. 0 => namespace, 1 => classname
 */
	function namespaceSplit($class) {
		$pos = strrpos($class, '\\');
		if ($pos === false) {
			return array('', $class);
		}
		return array(substr($class, 0, $pos), substr($class, $pos + 1));
	}

}

if (!function_exists('pr')) {

/**
 * print_r() convenience function
 *
 * In terminals this will act the same as using print_r() directly, when not run on cli
 * print_r() will wrap <PRE> tags around the output of given array. Similar to debug().
 *
 * @param mixed $var Variable to print out
 * @return void
 * @see debug()
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#pr
 * @see debug()
 */
	function pr($var) {
		if (Configure::read('debug')) {
			$template = php_sapi_name() !== 'cli' ? '<pre>%s</pre>' : "\n%s\n";
			printf($template, print_r($var, true));
		}
	}

}

if (!function_exists('env')) {

/**
 * Gets an environment variable from available sources, and provides emulation
 * for unsupported or inconsistent environment variables (i.e. DOCUMENT_ROOT on
 * IIS, or SCRIPT_NAME in CGI mode). Also exposes some additional custom
 * environment information.
 *
 * @param string $key Environment variable name.
 * @return string Environment variable setting.
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#env
 */
	function env($key) {
		if ($key === 'HTTPS') {
			if (isset($_SERVER['HTTPS'])) {
				return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
			}
			return (strpos(env('SCRIPT_URI'), 'https://') === 0);
		}

		if ($key === 'SCRIPT_NAME') {
			if (env('CGI_MODE') && isset($_ENV['SCRIPT_URL'])) {
				$key = 'SCRIPT_URL';
			}
		}

		$val = null;
		if (isset($_SERVER[$key])) {
			$val = $_SERVER[$key];
		} elseif (isset($_ENV[$key])) {
			$val = $_ENV[$key];
		} elseif (getenv($key) !== false) {
			$val = getenv($key);
		}

		if ($key === 'REMOTE_ADDR' && $val === env('SERVER_ADDR')) {
			$addr = env('HTTP_PC_REMOTE_ADDR');
			if ($addr !== null) {
				$val = $addr;
			}
		}

		if ($val !== null) {
			return $val;
		}

		switch ($key) {
			case 'DOCUMENT_ROOT':
				$name = env('SCRIPT_NAME');
				$filename = env('SCRIPT_FILENAME');
				$offset = 0;
				if (!strpos($name, '.php')) {
					$offset = 4;
				}
				return substr($filename, 0, -(strlen($name) + $offset));
			case 'PHP_SELF':
				return str_replace(env('DOCUMENT_ROOT'), '', env('SCRIPT_FILENAME'));
			case 'CGI_MODE':
				return (PHP_SAPI === 'cgi');
			case 'HTTP_BASE':
				$host = env('HTTP_HOST');
				$parts = explode('.', $host);
				$count = count($parts);

				if ($count === 1) {
					return '.' . $host;
				} elseif ($count === 2) {
					return '.' . $host;
				} elseif ($count === 3) {
					$gTLD = array(
						'aero',
						'asia',
						'biz',
						'cat',
						'com',
						'coop',
						'edu',
						'gov',
						'info',
						'int',
						'jobs',
						'mil',
						'mobi',
						'museum',
						'name',
						'net',
						'org',
						'pro',
						'tel',
						'travel',
						'xxx'
					);
					if (in_array($parts[1], $gTLD)) {
						return '.' . $host;
					}
				}
				array_shift($parts);
				return '.' . implode('.', $parts);
		}
		return null;
	}

}

if (!function_exists('__')) {

/**
 * Returns a translated string if one is found; Otherwise, the submitted message.
 *
 * @param string $singular Text to translate
 * @param mixed $args Array with arguments or multiple arguments in function
 * @return mixed translated string
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#__
 */
	function __($singular, $args = null) {
		if (!$singular) {
			return;
		}

		$arguments = func_num_args() === 2 ? (array)$args : array_slice(func_get_args(), 1);
		return I18n::translator()->translate($singular, $arguments);
	}

}

if (!function_exists('__n')) {

/**
 * Returns correct plural form of message identified by $singular and $plural for count $count.
 * Some languages have more than one form for plural messages dependent on the count.
 *
 * @param string $singular Singular text to translate
 * @param string $plural Plural text
 * @param int $count Count
 * @param mixed $args Array with arguments or multiple arguments in function
 * @return mixed plural form of translated string
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#__n
 */
	function __n($singular, $plural, $count, $args = null) {
		if (!$singular) {
			return;
		}

		$arguments = func_num_args() === 4 ? (array)$args : array_slice(func_get_args(), 3);
		return I18n::translator()->translate(
			$plural,
			['_count' => $count, '_singular' => $singular] + $arguments
		);
	}

}

if (!function_exists('__d')) {

/**
 * Allows you to override the current domain for a single message lookup.
 *
 * @param string $domain Domain
 * @param string $msg String to translate
 * @param mixed $args Array with arguments or multiple arguments in function
 * @return string translated string
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#__d
 */
	function __d($domain, $msg, $args = null) {
		if (!$msg) {
			return;
		}
		$arguments = func_num_args() === 3 ? (array)$args : array_slice(func_get_args(), 2);
		return I18n::translator($domain)->translate($msg, $arguments);
	}

}

if (!function_exists('__dn')) {

/**
 * Allows you to override the current domain for a single plural message lookup.
 * Returns correct plural form of message identified by $singular and $plural for count $count
 * from domain $domain.
 *
 * @param string $domain Domain
 * @param string $singular Singular string to translate
 * @param string $plural Plural
 * @param int $count Count
 * @param mixed $args Array with arguments or multiple arguments in function
 * @return string plural form of translated string
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#__dn
 */
	function __dn($domain, $singular, $plural, $count, $args = null) {
		if (!$singular) {
			return;
		}

		$arguments = func_num_args() === 5 ? (array)$args : array_slice(func_get_args(), 4);
		return I18n::translator($domain)->translate(
			$plural,
			['_count' => $count, '_singular' => $singular] + $arguments
		);
	}

}

if (!function_exists('__x')) {

/**
 * Returns a translated string if one is found; Otherwise, the submitted message.
 *
 * @param string $context Context of the text
 * @param string $singular Text to translate
 * @param mixed $args Array with arguments or multiple arguments in function
 * @return mixed translated string
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#__
 */
	function __x($context, $singular, $args = null) {
		if (!$singular) {
			return;
		}

		$arguments = func_num_args() === 3 ? (array)$args : array_slice(func_get_args(), 2);
		return I18n::translator()->translate($singular, ['_context' => $context] + $arguments);
	}

}

if (!function_exists('collection')) {

/**
 * Returns a new Cake\Collection\Collection object wrapping the passed argument
 *
 * @param \Traversable|array $items The items from which the collection will be built
 * @return \Cake\Collection\Collection
 */
	function collection($items) {
		return new Collection($items);
	}

}
