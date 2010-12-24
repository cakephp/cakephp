<?php
/**
 * CacheHelper helps create full page view caching.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs.view.helpers
 * @since         CakePHP(tm) v 1.0.0.2277
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * CacheHelper helps create full page view caching.
 *
 * When using CacheHelper you don't call any of its methods, they are all automatically
 * called by View, and use the $cacheAction settings set in the controller.
 *
 * @package       cake.libs.view.helpers
 * @link http://book.cakephp.org/view/1376/Cache
 */
class CacheHelper extends AppHelper {

/**
 * Array of strings replaced in cached views.
 * The strings are found between `<!--nocache--><!--/nocache-->` in views
 *
 * @var array
 */
	protected $_replace = array();

/**
 * Array of string that are replace with there var replace above.
 * The strings are any content inside `<!--nocache--><!--/nocache-->` and includes the tags in views
 *
 * @var array
 */
	protected $_match = array();

/**
 * Parses the view file and stores content for cache file building.
 *
 * @return void
 */
	public function afterRender($viewFile) {
		$caching = (($this->_View->cacheAction != false)) && (Configure::read('Cache.check') === true);
		if ($caching) {
			$this->cache($viewFile, $this->_View->output, false);
		}
	}

/**
 * Parses the layout file and stores content for cache file building.
 *
 * @return void
 */
	public function afterLayout($layoutFile) {
		$caching = (($this->_View->cacheAction != false)) && (Configure::read('Cache.check') === true);
		if ($caching) {
			$this->cache($layoutFile, $this->_View->output, true);
		}
		$this->_View->output = preg_replace('/<!--\/?nocache-->/', '', $this->_View->output);
	}

/**
 * Main method used to cache a view
 *
 * @param string $file File to cache
 * @param string $out output to cache
 * @param boolean $cache Whether or not a cache file should be written.
 * @return string view ouput
 */
	public function cache($file, $out, $cache = false) {
		$cacheTime = 0;
		$useCallbacks = false;
		$cacheAction = $this->_View->cacheAction;

		if (is_array($cacheAction)) {
			$keys = array_keys($cacheAction);
			$index = null;

			foreach ($keys as $action) {
				if ($action == $this->request->params['action']) {
					$index = $action;
					break;
				}
			}

			if (!isset($index) && $this->request->params['action'] == 'index') {
				$index = 'index';
			}

			$options = $cacheAction;
			if (isset($cacheAction[$index])) {
				if (is_array($cacheAction[$index])) {
					$options = array_merge(array('duration' => 0, 'callbacks' => false), $cacheAction[$index]);
				} else {
					$cacheTime = $cacheAction[$index];
				}
			}
			if (isset($options['duration'])) {
				$cacheTime = $options['duration'];
			}
			if (isset($options['callbacks'])) {
				$useCallbacks = $options['callbacks'];
			}
		} else {
			$cacheTime = $cacheAction;
		}

		if ($cacheTime != '' && $cacheTime > 0) {
			$this->_parseFile($file, $out);
			if ($cache === true) {
				$cached = $this->_parseOutput($out);
				$this->_writeFile($cached, $cacheTime, $useCallbacks);
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
 */
	protected function _parseFile($file, $cache) {
		if (is_file($file)) {
			$file = file_get_contents($file);
		} elseif ($file = fileExistsInPath($file)) {
			$file = file_get_contents($file);
		}
		preg_match_all('/(<!--nocache-->(?<=<!--nocache-->)[\\s\\S]*?(?=<!--\/nocache-->)<!--\/nocache-->)/i', $cache, $outputResult, PREG_PATTERN_ORDER);
		preg_match_all('/(?<=<!--nocache-->)([\\s\\S]*?)(?=<!--\/nocache-->)/i', $file, $fileResult, PREG_PATTERN_ORDER);
		$fileResult = $fileResult[0];
		$outputResult = $outputResult[0];

		if (!empty($this->_replace)) {
			foreach ($outputResult as $i => $element) {
				$index = array_search($element, $this->_match);
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
					$this->_replace[] = $cacheBlock;
					$this->_match[] = $outputResult[$i];
				}
				$i++;
			}
		}
	}

/**
 * Parse the output and replace cache tags
 *
 * @param string $cache Output to replace content in.
 * @return string with all replacements made to <!--nocache--><!--nocache-->
 */
	protected function _parseOutput($cache) {
		$count = 0;
		if (!empty($this->_match)) {
			foreach ($this->_match as $found) {
				$original = $cache;
				$length = strlen($found);
				$position = 0;

				for ($i = 1; $i <= 1; $i++) {
					$position = strpos($cache, $found, $position);

					if ($position !== false) {
						$cache = substr($original, 0, $position);
						$cache .= $this->_replace[$count];
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
 */
	protected function _writeFile($content, $timestamp, $useCallbacks = false) {
		$now = time();

		if (is_numeric($timestamp)) {
			$cacheTime = $now + $timestamp;
		} else {
			$cacheTime = strtotime($timestamp, $now);
		}
		$path = $this->request->here;
		if ($this->here == '/') {
			$path = 'home';
		}
		$cache = strtolower(Inflector::slug($path));

		if (empty($cache)) {
			return;
		}
		$cache = $cache . '.php';
		$file = '<!--cachetime:' . $cacheTime . '--><?php';

		if (empty($this->_View->plugin)) {
			$file .= '
			App::import(\'Controller\', \'' . $this->_View->name. '\');
			';
		} else {
			$file .= '
			App::import(\'Controller\', \'' . $this->_View->plugin . '.' . $this->_View->name. '\');
			';
		}

		$file .= '$controller = new ' . $this->_View->name . 'Controller();
				$controller->plugin = $this->plugin = \'' . $this->_View->plugin . '\';
				$controller->helpers = $this->helpers = unserialize(\'' . serialize($this->_View->helpers) . '\');
				$controller->layout = $this->layout = \'' . $this->_View->layout. '\';
				$controller->request = $this->request = unserialize(\'' . str_replace("'", "\\'", serialize($this->request)) . '\');
				$controller->theme = $this->theme = \'' . $this->_View->theme . '\';
				Router::setRequestInfo($controller->request);';

		if ($useCallbacks == true) {
			$file .= '
				$controller->constructClasses();
				$controller->startupProcess();';
		}

		$file .= '
				$this->loadHelpers();
		?>';
		$content = preg_replace("/(<\\?xml)/", "<?php echo '$1';?>",$content);
		$file .= $content;
		return cache('views' . DS . $cache, $file, $timestamp);
	}
}
