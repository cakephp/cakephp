<?php
/**
 * CacheHelper helps create full page view caching.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Helper
 * @since         CakePHP(tm) v 1.0.0.2277
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppHelper', 'View/Helper');

/**
 * CacheHelper helps create full page view caching.
 *
 * When using CacheHelper you don't call any of its methods, they are all automatically
 * called by View, and use the $cacheAction settings set in the controller.
 *
 * @package       Cake.View.Helper
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/cache.html
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
 * Counter used for counting nocache section tags.
 *
 * @var integer
 */
	protected $_counter = 0;

/**
 * Is CacheHelper enabled? should files + output be parsed.
 *
 * @return boolean
 */
	protected function _enabled() {
		return (($this->_View->cacheAction != false)) && (Configure::read('Cache.check') === true);
	}

/**
 * Parses the view file and stores content for cache file building.
 *
 * @param string $viewFile
 * @return void
 */
	public function afterRenderFile($viewFile, $output) {
		if ($this->_enabled()) {
			return $this->_parseContent($viewFile, $output);
		}
	}

/**
 * Parses the layout file and stores content for cache file building.
 *
 * @param string $layoutFile
 * @return void
 */
	public function afterLayout($layoutFile) {
		if ($this->_enabled()) {
			$this->_View->output = $this->cache($layoutFile, $this->_View->output);
		}
		$this->_View->output = preg_replace('/<!--\/?nocache-->/', '', $this->_View->output);
	}

/**
 * Parse a file + output.  Matches nocache tags between the current output and the current file
 * stores a reference of the file, so the generated can be swapped back with the file contents when
 * writing the cache file.
 *
 * @param string $file The filename to process.
 * @param string $out The output for the file.
 * @return string Updated content.
 */
	protected function _parseContent($file, $out) {
		$out = preg_replace_callback('/<!--nocache-->/', array($this, '_replaceSection'), $out);
		$this->_parseFile($file, $out);
		return $out;
	}

/**
 * Main method used to cache a view
 *
 * @param string $file File to cache
 * @param string $out output to cache
 * @return string view ouput
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/cache.html
 */
	public function cache($file, $out) {
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
			$cached = $this->_parseOutput($out);
			$this->_writeFile($cached, $cacheTime, $useCallbacks);
			$out = $this->_stripTags($out);
		}
		return $out;
	}

/**
 * Parse file searching for no cache tags
 *
 * @param string $file The filename that needs to be parsed.
 * @param string $cache The cached content
 * @return void
 */
	protected function _parseFile($file, $cache) {
		if (is_file($file)) {
			$file = file_get_contents($file);
		} elseif ($file = fileExistsInPath($file)) {
			$file = file_get_contents($file);
		}
		preg_match_all('/(<!--nocache:\d{3}-->(?<=<!--nocache:\d{3}-->)[\\s\\S]*?(?=<!--\/nocache-->)<!--\/nocache-->)/i', $cache, $outputResult, PREG_PATTERN_ORDER);
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
 * Munges the output from a view with cache tags, and numbers the sections.
 * This helps solve issues with empty/duplicate content.
 *
 * @return string The content with cake:nocache tags replaced.
 */
	protected function _replaceSection() {
		$this->_counter += 1;
		return sprintf('<!--nocache:%03d-->', $this->_counter);
	}

/**
 * Strip cake:nocache tags from a string. Since View::render()
 * only removes un-numbered nocache tags, remove all the numbered ones.
 * This is the complement to _replaceSection.
 *
 * @param string $content String to remove tags from.
 * @return string String with tags removed.
 */
	protected function _stripTags($content) {
		return preg_replace('#<!--/?nocache(\:\d{3})?-->#', '', $content);
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
 * @param string $timestamp Duration to set for cache file.
 * @param boolean $useCallbacks
 * @return boolean success of caching view.
 */
	protected function _writeFile($content, $timestamp, $useCallbacks = false) {
		$now = time();

		if (is_numeric($timestamp)) {
			$cacheTime = $now + $timestamp;
		} else {
			$cacheTime = strtotime($timestamp, $now);
		}
		$path = $this->request->here();
		if ($path == '/') {
			$path = 'home';
		}
		$cache = strtolower(Inflector::slug($path));

		if (empty($cache)) {
			return;
		}
		$cache = $cache . '.php';
		$file = '<!--cachetime:' . $cacheTime . '--><?php';

		if (empty($this->_View->plugin)) {
			$file .= "
			App::uses('{$this->_View->name}Controller', 'Controller');
			";
		} else {
			$file .= "
			App::uses('{$this->_View->plugin}AppController', '{$this->_View->plugin}.Controller');
			App::uses('{$this->_View->name}Controller', '{$this->_View->plugin}.Controller');
			";
		}

		$file .= '
				$request = unserialize(base64_decode(\'' . base64_encode(serialize($this->request)) . '\'));
				$response = new CakeResponse(array("charset" => Configure::read("App.encoding")));
				$controller = new ' . $this->_View->name . 'Controller($request, $response);
				$controller->plugin = $this->plugin = \'' . $this->_View->plugin . '\';
				$controller->helpers = $this->helpers = unserialize(base64_decode(\'' . base64_encode(serialize($this->_View->helpers)) . '\'));
				$controller->layout = $this->layout = \'' . $this->_View->layout . '\';
				$controller->theme = $this->theme = \'' . $this->_View->theme . '\';
				$controller->viewVars = unserialize(base64_decode(\'' . base64_encode(serialize($this->_View->viewVars)) . '\'));
				Router::setRequestInfo($controller->request);
				$this->request = $request;';

		if ($useCallbacks == true) {
			$file .= '
				$controller->constructClasses();
				$controller->startupProcess();';
		}

		$file .= '
				$this->viewVars = $controller->viewVars;
				$this->loadHelpers();
				extract($this->viewVars, EXTR_SKIP);
		?>';
		$content = preg_replace("/(<\\?xml)/", "<?php echo '$1'; ?>", $content);
		$file .= $content;
		return cache('views' . DS . $cache, $file, $timestamp);
	}

}
