<?php
/**
 * CacheHelper helps create full page view caching.
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
 * @since         CakePHP(tm) v 1.0.0.2277
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * CacheHelper helps create full page view caching.
 *
 * When using CacheHelper you don't call any of its methods, they are all automatically
 * called by View, and use the $cacheAction settings set in the controller.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.view.helpers
 * @link http://book.cakephp.org/view/1376/Cache
 */
class CacheHelper extends AppHelper {

/**
 * Array of strings replaced in cached views.
 * The strings are found between <cake:nocache><cake:nocache> in views
 *
 * @var array
 * @access private
 */
	var $__replace = array();

/**
 * Array of string that are replace with there var replace above.
 * The strings are any content inside <cake:nocache><cake:nocache> and includes the tags in views
 *
 * @var array
 * @access private
 */
	var $__match = array();

/**
 * cache action time
 *
 * @var object
 * @access public
 */
	var $cacheAction;

/**
 * Main method used to cache a view
 *
 * @param string $file File to cache
 * @param string $out output to cache
 * @param boolean $cache Whether or not a cache file should be written.
 * @return string view ouput
 */
	function cache($file, $out, $cache = false) {
		$cacheTime = 0;
		$useCallbacks = false;
		if (is_array($this->cacheAction)) {
			$keys = array_keys($this->cacheAction);
			$index = null;

			foreach ($keys as $action) {
				if ($action == $this->params['action']) {
					$index = $action;
					break;
				}
			}

			if (!isset($index) && $this->action == 'index') {
				$index = 'index';
			}

			$options = $this->cacheAction;
			if (isset($this->cacheAction[$index])) {
				if (is_array($this->cacheAction[$index])) {
					$options = array_merge(array('duration' => 0, 'callbacks' => false), $this->cacheAction[$index]);
				} else {
					$cacheTime = $this->cacheAction[$index];
				}
			}
			if (isset($options['duration'])) {
				$cacheTime = $options['duration'];
			}
			if (isset($options['callbacks'])) {
				$useCallbacks = $options['callbacks'];
			}
		} else {
			$cacheTime = $this->cacheAction;
		}

		if ($cacheTime != '' && $cacheTime > 0) {
			$this->__parseFile($file, $out);
			if ($cache === true) {
				$cached = $this->__parseOutput($out);
				$this->__writeFile($cached, $cacheTime, $useCallbacks);
			}
			return $out;
		} else {
			return $out;
		}
	}

/**
 * Parse file searching for no cache tags
 *
 * @param string $file The filename that needs to be parsed.
 * @param string $cache The cached content
 * @access private
 */
	function __parseFile($file, $cache) {
		if (is_file($file)) {
			$file = file_get_contents($file);
		} elseif ($file = fileExistsInPath($file)) {
			$file = file_get_contents($file);
		}
		preg_match_all('/(<cake:nocache>(?<=<cake:nocache>)[\\s\\S]*?(?=<\/cake:nocache>)<\/cake:nocache>)/i', $cache, $outputResult, PREG_PATTERN_ORDER);
		preg_match_all('/(?<=<cake:nocache>)([\\s\\S]*?)(?=<\/cake:nocache>)/i', $file, $fileResult, PREG_PATTERN_ORDER);
		$fileResult = $fileResult[0];
		$outputResult = $outputResult[0];

		if (!empty($this->__replace)) {
			foreach ($outputResult as $i => $element) {
				$index = array_search($element, $this->__match);
				if ($index !== false) {
					unset($outputResult[$i]);
				}
			}
			$outputResult = array_values($outputResult);
		}

		if (!empty($fileResult)) {
			$i = 0;
			foreach ($fileResult as $cacheBlock) {
				if (isset($outputResult[$i])) {
					$this->__replace[] = $cacheBlock;
					$this->__match[] = $outputResult[$i];
				}
				$i++;
			}
		}
	}

/**
 * Parse the output and replace cache tags
 *
 * @param string $cache Output to replace content in.
 * @return string with all replacements made to <cake:nocache><cake:nocache>
 * @access private
 */
	function __parseOutput($cache) {
		$count = 0;
		if (!empty($this->__match)) {
			foreach ($this->__match as $found) {
				$original = $cache;
				$length = strlen($found);
				$position = 0;

				for ($i = 1; $i <= 1; $i++) {
					$position = strpos($cache, $found, $position);

					if ($position !== false) {
						$cache = substr($original, 0, $position);
						$cache .= $this->__replace[$count];
						$cache .= substr($original, $position + $length);
					} else {
						break;
					}
				}
				$count++;
			}
			return $cache;
		}
		return $cache;
	}

/**
 * Write a cached version of the file
 *
 * @param string $content view content to write to a cache file.
 * @param sting $timestamp Duration to set for cache file.
 * @return boolean success of caching view.
 * @access private
 */
	function __writeFile($content, $timestamp, $useCallbacks = false) {
		$now = time();

		if (is_numeric($timestamp)) {
			$cacheTime = $now + $timestamp;
		} else {
			$cacheTime = strtotime($timestamp, $now);
		}
		$path = $this->here;
		if ($this->here == '/') {
			$path = 'home';
		}
		$cache = strtolower(Inflector::slug($path));

		if (empty($cache)) {
			return;
		}
		$cache = $cache . '.php';
		$file = '<!--cachetime:' . $cacheTime . '--><?php';

		if (empty($this->plugin)) {
			$file .= '
			App::import(\'Controller\', \'' . $this->controllerName. '\');
			';
		} else {
			$file .= '
			App::import(\'Controller\', \'' . $this->plugin . '.' . $this->controllerName. '\');
			';
		}

		$file .= '$controller =& new ' . $this->controllerName . 'Controller();
				$controller->plugin = $this->plugin = \''.$this->plugin.'\';
				$controller->helpers = $this->helpers = unserialize(\'' . serialize($this->helpers) . '\');
				$controller->base = $this->base = \'' . $this->base . '\';
				$controller->layout = $this->layout = \'' . $this->layout. '\';
				$controller->webroot = $this->webroot = \'' . $this->webroot . '\';
				$controller->here = $this->here = \'' . $this->here . '\';
				$controller->params = $this->params = unserialize(stripslashes(\'' . addslashes(serialize($this->params)) . '\'));
				$controller->action = $this->action = unserialize(\'' . serialize($this->action) . '\');
				$controller->data = $this->data = unserialize(stripslashes(\'' . addslashes(serialize($this->data)) . '\'));
				$controller->viewVars = $this->viewVars = unserialize(base64_decode(\'' . base64_encode(serialize($this->viewVars)) . '\'));
				$controller->theme = $this->theme = \'' . $this->theme . '\';
				Router::setRequestInfo(array($this->params, array(\'base\' => $this->base, \'webroot\' => $this->webroot)));';

		if ($useCallbacks == true) {
			$file .= '
				$controller->constructClasses();
				$controller->Component->initialize($controller);
				$controller->beforeFilter();
				$controller->Component->startup($controller);';
		}

		$file .= '
				$loadedHelpers = array();
				$loadedHelpers = $this->_loadHelpers($loadedHelpers, $this->helpers);
				foreach (array_keys($loadedHelpers) as $helper) {
					$camelBackedHelper = Inflector::variable($helper);
					${$camelBackedHelper} =& $loadedHelpers[$helper];
					$this->loaded[$camelBackedHelper] =& ${$camelBackedHelper};
					$this->{$helper} =& $loadedHelpers[$helper];
				}
				extract($this->viewVars, EXTR_SKIP);
		?>';
		$content = preg_replace("/(<\\?xml)/", "<?php echo '$1';?>",$content);
		$file .= $content;
		return cache('views' . DS . $cache, $file, $timestamp);
	}
}
