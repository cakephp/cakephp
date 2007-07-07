<?php
/* SVN FILE: $Id$ */

/**
 * Methods for displaying presentation data in the view.
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
 * @subpackage		cake.cake.libs.view
 * @since			CakePHP(tm) v 0.10.0.1076
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Included libraries.
 */
uses ('view' . DS . 'helper', 'class_registry');

/**
 * View, the V in the MVC triad.
 *
 * Class holding methods for displaying presentation data.
 *
 * @package			cake
 * @subpackage		cake.cake.libs.view
 */
class View extends Object {
/**
 * Name of the controller.
 *
 * @var string Name of controller
 * @access public
 */
	var $name = null;

/**
 * Stores the current URL (for links etc.)
 *
 * @var string Current URL
 */
	var $here = null;

/**
 * Not used. 2006-09
 *
 * @var unknown_type
 * @access public
 */
	var $parent = null;
/**
 * Action to be performed.
 *
 * @var string Name of action
 * @access public
 */
	var $action = null;
/**
 * Name of current model this view context is attached to
 *
 * @var string
 */
	var $model = null;
/**
 * Name of association model this view context is attached to
 *
 * @var string
 */
	var $association = null;
/**
 * Name of current model field this view context is attached to
 *
 * @var string
 */
	var $field = null;
/**
 * Suffix of current field this view context is attached to
 *
 * @var string
 */
	var $fieldSuffix = null;
/**
 * Name of current model ID this view context is attached to
 *
 * @var string
 */
	var $modelId = null;
/**
 * An array of names of models the particular controller wants to use.
 *
 * @var mixed A single name as a string or a list of names as an array.
 * @access protected
 */
	var $uses = false;
/**
 * An array of names of built-in helpers to include.
 *
 * @var mixed A single name as a string or a list of names as an array.
 * @access protected
 */
	var $helpers = array('Html');
/**
 * Path to View.
 *
 * @var string Path to View
 */
	var $viewPath;
/**
 * Path to Layout.
 *
 * @var string Path to Layout
 */
	var $layoutPath = null;
/**
 * Variables for the view
 *
 * @var array
 * @access private
 */
	var $viewVars = array();
/**
 * Scripts (and/or other <head /> tags) for the layout
 *
 * @var array
 * @access private
 */
	var $__scripts = array();
/**
 * Title HTML element of this View.
 *
 * @var boolean
 * @access private
 */
	var $pageTitle = false;
/**
 * An array of model objects.
 *
 * @var array Array of model objects.
 * @access public
 */
	var $models = array();
/**
 * Path parts for creating links in views.
 *
 * @var string Base URL
 * @access public
 */
	var $base = null;
/**
 * Name of layout to use with this View.
 *
 * @var string
 * @access public
 */
	var $layout = 'default';
/**
 * Turns on or off Cake's conventional mode of rendering views. On by default.
 *
 * @var boolean
 * @access public
 */
	var $autoRender = true;
/**
 * Turns on or off Cake's conventional mode of finding layout files. On by default.
 *
 * @var boolean
 * @access public
 */
	var $autoLayout = true;

/**
 * Array of parameter data
 *
 * @var array Parameter data
 */
	var $params;
/**
 * True when the view has been rendered.
 *
 * @var boolean
 */
	var $hasRendered = null;
/**
 * Reference to the Controller for this view.
 *
 * @var Controller
 */
	var $controller = null;
/**
 * Array of loaded view helpers.
 *
 * @var array
 */
	var $loaded = array();
/**
 * File extension. Defaults to Cake's template ".ctp".
 *
 * @var array
 */
	var $ext = '.ctp';
/**
 * Sub-directory for this view file.
 *
 * @var string
 */
	var $subDir = null;
/**
 * Enter description here... Themes. New in Cake RC4.
 *
 * @var array
 */
	var $themeWeb = null;
/**
 * Plugin name. A Plugin is a sub-application. New in Cake RC4.
 *
 * @link http://manual.cakephp.org/chapter/plugins
 * @var string
 */
	var $plugin = null;
/**
 * Creates system path to plugin: plugins . DS . plugin_name . DS
 *
 * @var string
 */
	var $pluginPath = null;
/**
 * Holds an array of plugin paths.
 * VIEWS . $this->pluginPath
 * APP . $this->pluginPath . views . DS
 *
 * @var array
 */
	var $pluginPaths = array();
/**
 * Current passed params
 *
 * @var mixed
 */
	var $passedArgs = array();
/**
 * Controller URL-generation data
 *
 * @var mixed
 */
	var $namedArgs = array();
/**
 * Controller URL-generation data
 *
 * @var string
 */
	var $argSeparator = null;
/**
 * List of variables to collect from the associated controller
 *
 * @var array
 * @access protected
 */
	var $__passedVars = array('viewVars', 'action', 'autoLayout', 'autoRender', 'ext', 'base', 'webroot', 'helpers', 'here', 'layout', 'modelNames', 'name', 'pageTitle', 'layoutPath', 'viewPath', 'params', 'data', 'webservices', 'plugin', 'passedArgs', 'namedArgs', 'argSeparator', 'cacheAction');
/**
 * List of generated DOM UUIDs
 *
 * @var array
 */
	var $uuids = array();
/**
 * Constructor
 *
 * @return View
 */
	function __construct(&$controller) {
		if (is_object($controller)) {
			$count = count($this->__passedVars);
			for ($j = 0; $j < $count; $j++) {
				$var = $this->__passedVars[$j];
				$this->{$var} = $controller->{$var};
			}
		}
		if (!is_null($this->plugin)) {
			$this->pluginPath = 'plugins'. DS . $this->plugin . DS;
			$this->pluginPaths = array(
				VIEWS . $this->pluginPath,
				APP . $this->pluginPath . 'views' . DS,
			);
		}
		parent::__construct();
		ClassRegistry::addObject('view', $this);
	}

/**
 * Renders view for given action and layout. If $file is given, that is used
 * for a view filename (e.g. customFunkyView.ctp).
 *
 * @param string $action Name of action to render for
 * @param string $layout Layout to use
 * @param string $file Custom filename for view
 */
	function render($action = null, $layout = null, $file = null) {

		if (isset($this->hasRendered) && $this->hasRendered) {
			return true;
		} else {
			$this->hasRendered = false;
		}

		if (!$action) {
			$action = $this->action;
		}
		$tempLayout = $this->layout;

		if ($layout) {
			$this->layout = $layout;
		}

		if ($file) {
			$viewFileName = $file;
			$this->_missingView($viewFileName, $action);
		} else {
			$viewFileName = $this->_getViewFileName($action);
		}

		if ($viewFileName && !$this->hasRendered) {
			if (substr($viewFileName, -3) === 'ctp' || substr($viewFileName, -5) === 'thtml') {
				$out = View::_render($viewFileName, $this->viewVars);
			} else {
				$out = $this->_render($viewFileName, $this->viewVars);
			}

			if ($out !== false) {
				if ($this->layout && $this->autoLayout) {
					$out = $this->renderLayout($out);
					if (isset($this->loaded['cache']) && (($this->cacheAction != false)) && (defined('CACHE_CHECK') && CACHE_CHECK === true)) {
						$replace = array('<cake:nocache>', '</cake:nocache>');
						$out = str_replace($replace, '', $out);
					}
				}

				print $out;
				$this->layout = $tempLayout;
				$this->hasRendered = true;
			} else {
				$out = $this->_render($viewFileName, $this->viewVars);
				trigger_error(sprintf(__("Error in view %s, got: <blockquote>%s</blockquote>", true), $viewFileName, $out), E_USER_ERROR);
			}
			return true;
		}
	}
/**
 * Renders a piece of PHP with provided parameters and returns HTML, XML, or any other string.
 *
 * This realizes the concept of Elements, (or "partial layouts")
 * and the $params array is used to send data to be used in the
 * Element.
 *
 * @link
 * @param string $name Name of template file in the/app/views/elements/ folder
 * @param array $params Array of data to be made available to the for rendered view (i.e. the Element)
 * @return string Rendered output
 */
	function renderElement($name, $params = array(), $loadHelpers = false) {

		if (isset($params['plugin'])) {
			$this->plugin = $params['plugin'];
			$this->pluginPath = 'plugins' . DS . $this->plugin . DS;
			$this->pluginPaths = array(
									VIEWS . $this->pluginPath,
									APP . $this->pluginPath . 'views' . DS,
								);
		}

		$paths = Configure::getInstance();
		$viewPaths = am($this->pluginPaths, $paths->viewPaths);

		$file = null;
		foreach ($viewPaths as $path) {
			if (file_exists($path . 'elements' . DS . $name . $this->ext)) {
				$file = $path . 'elements' . DS . $name . $this->ext;
				break;
			} elseif (file_exists($path . 'elements' . DS . $name . '.thtml')) {
				$file = $path . 'elements' . DS . $name . '.thtml';
				break;
			}
		}

		if (!is_null($file)) {
			$params = array_merge_recursive($params, $this->loaded);
			return $this->_render($file, array_merge($this->viewVars, $params), $loadHelpers);
		}

		if (!is_null($this->pluginPath)) {
			$file = APP . $this->pluginPath . 'views' . DS . 'elements' . DS . $name . $this->ext;
		} else {
			$file = VIEWS . 'elements' . DS . $name . $this->ext;
		}

		if (Configure::read() > 0) {
			return "Not Found: " . $file;
		}
	}

/**
 * Wrapper for View::renderElement();
 *
 * @param string $name Name of template file in the/app/views/elements/ folder
 * @param array $params Array of data to be made available to the for rendered view (i.e. the Element)
 * @return string View::renderElement()
 * @access public
 */
	function element($name, $params = array()) {
		if (in_array('cache', array_keys($params))) {
			$expires = '+1 day';
			if ($params['cache'] !== true) {
				$expires = $params['cache'];
			}
			if ($expires) {
				$plugin = null;
				if (isset($params['plugin'])) {
					$plugin = $params['plugin'];
				}
				$cacheFile = 'element_' . $plugin .'_' . convertSlash($name);
				$cache = cache('views' . DS . $cacheFile, null, $expires);
				if ($cache) {
					return $cache;
				} else {
					$element = $this->renderElement($name, $params);
               cache('views' . DS . $cacheFile, $element, $expires);
               return $element;
				}
			}
		}
		return $this->renderElement($name, $params);
	}
/**
 * Renders a layout. Returns output from _render(). Returns false on error.
 *
 * @param string $content_for_layout Content to render in a view, wrapped by the surrounding layout.
 * @return mixed Rendered output, or false on error
 */
	function renderLayout($content_for_layout) {
		$layout_fn = $this->_getLayoutFileName();

		if (Configure::read() > 2 && $this->controller != null) {
			$debug = View::_render(LIBS . 'view' . DS . 'templates' . DS . 'elements' . DS . 'dump.ctp', array('controller' => $this->controller), false);
		} else {
			$debug = '';
		}

		if ($this->pageTitle !== false) {
			$pageTitle = $this->pageTitle;
		} else {
			$pageTitle = Inflector::humanize($this->viewPath);
		}

		$data_for_layout = array_merge($this->viewVars,
			array(
				'title_for_layout'   => $pageTitle,
				'content_for_layout' => $content_for_layout,
				'scripts_for_layout' => join("\n\t", $this->__scripts),
				'cakeDebug'          => $debug
			)
		);

		if (is_file($layout_fn)) {
			if (empty($this->loaded) && !empty($this->helpers)) {
				$loadHelpers = true;
			} else {
				$loadHelpers = false;
				$data_for_layout = array_merge($data_for_layout, $this->loaded);
			}

			if (substr($layout_fn, -3) === 'ctp' || substr($layout_fn, -5) === 'thtml') {
				$out = View::_render($layout_fn, $data_for_layout, $loadHelpers, true);
			} else {
				$out = $this->_render($layout_fn, $data_for_layout, $loadHelpers);
			}

			if ($out === false) {
				$out = $this->_render($layout_fn, $data_for_layout);
				trigger_error(sprintf(__("Error in layout %s, got: <blockquote>%s</blockquote>", true), $layout_fn, $out), E_USER_ERROR);
				return false;
			} else {
				return $out;
			}
		} else {
			return $this->cakeError('missingLayout', array(
					array(
						'layout' => $this->layout,
						'file' => $layout_fn,
						'base' => $this->base
					)
			));
		}
	}
/**
 * Returns a list of variables available in the current View context
 *
 * @return array
 * @access public
 */
	function getVars() {
		return array_keys($this->viewVars);
	}
/**
 * Returns the contents of the given View variable(s)
 *
 * @return array
 * @access public
 */
	function getVar($var) {
		if (!isset($this->viewVars[$var])) {
			return null;
		} else {
			return $this->viewVars[$var];
		}
	}
/**
 * Adds a script block or other element to be inserted in $scripts_for_layout in
 * the <head /> of a document layout
 *
 * @param string $name
 * @param string $content
 * @return void
 * @access public
 */
	function addScript($name, $content = null) {
		if (empty($content)) {
			if (!in_array($name, array_values($this->__scripts))) {
				$this->__scripts[] = $name;
			}
		} else {
			$this->__scripts[$name] = $content;
		}
	}
/**
 * Generates a unique, non-random DOM ID for an object, based on the object type and the target URL.
 *
 * @param string $object Type of object, i.e. 'form' or 'link'
 * @param string $url The object's target URL
 * @return string
 * @access public
 */
	function uuid($object, $url) {
		$c = 1;
		$hash = $object . substr(md5($object . Router::url($url)), 0, 10);
		while (in_array($hash, $this->uuids)) {
			$hash = $object . substr(md5($object . Router::url($url) . $c), 0, 10);
			$c++;
		}
		$this->uuids[] = $hash;
		return $hash;
	}
/**
 * Allows a template or element to set a variable that will be available in
 * a layout or other element.  Analagous to Controller::set.
 *
 * @param mixed $one A string or an array of data.
 * @param mixed $two Value in case $one is a string (which then works as the key).
 * 				Unused if $one is an associative array, otherwise serves as the values to $one's keys.
 * @return unknown
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

		foreach ($data as $name => $value) {
			if ($name == 'title') {
				$this->pageTitle = $value;
			} else {
				$this->viewVars[$name] = $value;
			}
		}
	}

/**
 * Displays an error page to the user. Uses layouts/error.ctp to render the page.
 *
 * @param int $code HTTP Error code (for instance: 404)
 * @param string $name Name of the error (for instance: Not Found)
 * @param string $message Error message as a web page
 */
	function error($code, $name, $message) {
		header ("HTTP/1.1 {$code} {$name}");
		print ($this->_render(
			LAYOUTS . 'error.ctp',
			array(
				'code' => $code,
				'name' => $name,
				'message' => $message
			)
		));
	}

/**************************************************************************
 * Private methods.
 *************************************************************************/

/**
 * Returns filename of given action's template file (.ctp) as a string. CamelCased action names will be under_scored! This means that you can have LongActionNames that refer to long_action_names.ctp views.
 *
 * @param string $action Controller action to find template filename for
 * @return string Template filename
 * @access private
 */
	function _getViewFileName($action) {
		$action = Inflector::underscore($action);

		if (!is_null($this->webservices)) {
			$type = strtolower($this->webservices) . DS;
		} else {
			$type = null;
		}

		$position = strpos($action, '..');
		if ($position !== false) {
			$action = explode('/', $action);
			$i = array_search('..', $action);
			unset($action[$i - 1]);
			unset($action[$i]);
			$action = '..' . DS . implode(DS, $action);
		}

		$paths = Configure::getInstance();
		$viewPaths = am($this->pluginPaths, $paths->viewPaths);

		$name = $this->viewPath . DS . $this->subDir . $type . $action;
		foreach ($viewPaths as $path) {
			if (file_exists($path . $name . $this->ext)) {
				return $path . $name . $this->ext;
			} elseif (file_exists($path . $name . '.thtml')) {
				return $path . $name . '.thtml';
			}
		}

		if ($viewFileName = fileExistsInPath(LIBS . 'view' . DS . 'templates' . DS . 'errors' . DS . $type . $action . '.ctp')) {
			return $viewFileName;
		} elseif ($viewFileName = fileExistsInPath(LIBS . 'view' . DS . 'templates' . DS . $this->viewPath . DS . $type . $action . '.ctp')) {
			return $viewFileName;
		} else {
			if (!is_null($this->pluginPath)) {
				$viewFileName = APP . $this->pluginPath . 'views' . DS . $name . $this->ext;
			} else {
				$viewFileName = VIEWS . $name . $this->ext;
			}
			$this->_missingView($viewFileName, $action);
		}
		return false;
	}

/**
 * Returns layout filename for this template as a string.
 *
 * @return string Filename for layout file (.ctp).
 * @access private
 */
	function _getLayoutFileName() {
		if (isset($this->webservices) && !is_null($this->webservices)) {
			$type = strtolower($this->webservices) . DS;
		} else {
			$type = null;
		}

		if (!is_null($this->layoutPath)) {
			$type = $this->layoutPath . DS;
		}

		$paths = Configure::getInstance();
		$viewPaths = am($this->pluginPaths, $paths->viewPaths);

		$name = $this->subDir . $type . $this->layout;
		foreach ($viewPaths as $path) {
			if (file_exists($path . 'layouts' . DS . $name . $this->ext)) {
				return $path . 'layouts' . DS . $name . $this->ext;
			} elseif (file_exists($path . 'layouts' . DS . $name . '.thtml')) {
				return $path . 'layouts' . DS . $name . '.thtml';
			}
		}

		if (!is_null($this->pluginPath)) {
			$layoutFileName = APP . $this->pluginPath . 'views' . DS . 'layouts' . DS . $name . $this->ext;
		} else {
			$layoutFileName = VIEWS . 'layouts' . DS . $name . $this->ext;
		}

		$default = fileExistsInPath(LIBS . 'view' . DS . 'templates' . DS . 'layouts' . DS . $type . $this->layout . '.ctp');
		if (empty($default) && !empty($type)) {
			$default = fileExistsInPath(LIBS . 'view' . DS . 'templates' . DS . 'layouts' . DS . $type . 'default.ctp');
		}
		if (empty($default)) {
			$default = fileExistsInPath(LIBS . 'view' . DS . 'templates' . DS . 'layouts' . DS . $this->layout . '.ctp');
		}

		if (!empty($default)) {
			return $default;
		}
		return $layoutFileName;
	}

/**
 * Renders and returns output for given view filename with its
 * array of data.
 *
 * @param string $___viewFn Filename of the view
 * @param array $___dataForView Data to include in rendered view
 * @return string Rendered output
 * @access private
 */
	function _render($___viewFn, $___dataForView, $loadHelpers = true, $cached = false) {
		if ($this->helpers != false && $loadHelpers === true) {
			$loadedHelpers = array();
			$loadedHelpers = $this->_loadHelpers($loadedHelpers, $this->helpers);

			foreach (array_keys($loadedHelpers) as $helper) {
				$replace = strtolower(substr($helper, 0, 1));
				$camelBackedHelper = preg_replace('/\\w/', $replace, $helper, 1);

				${$camelBackedHelper} =& $loadedHelpers[$helper];

				if (is_array(${$camelBackedHelper}->helpers) && !empty(${$camelBackedHelper}->helpers)) {
					$subHelpers = ${$camelBackedHelper}->helpers;
					foreach ($subHelpers as $subHelper) {
						${$camelBackedHelper}->{$subHelper} =& $loadedHelpers[$subHelper];
					}
				}
				$this->loaded[$camelBackedHelper] =& ${$camelBackedHelper};
			}
		}

		if ($this->helpers != false && $loadHelpers === true) {
			foreach ($loadedHelpers as $helper) {
				if (is_object($helper)) {
					if (is_subclass_of($helper, 'Helper') || is_subclass_of($helper, 'helper')) {
						$helper->beforeRender();
					}
				}
			}
		}

		extract($___dataForView, EXTR_SKIP);
		$BASE = $this->base;
		$params =& $this->params;
		$page_title = $this->pageTitle;

		ob_start();

		if (Configure::read() > 0) {
			include ($___viewFn);
		} else {
			@include ($___viewFn);
		}

		if ($this->helpers != false && $loadHelpers === true) {
			foreach ($loadedHelpers as $helper) {
				if (is_object($helper)) {
					if (is_subclass_of($helper, 'Helper') || is_subclass_of($helper, 'helper')) {
						$helper->afterRender();
					}
				}
			}
		}

		$out = ob_get_clean();

		if (isset($this->loaded['cache']) && (($this->cacheAction != false)) && (defined('CACHE_CHECK') && CACHE_CHECK === true)) {
			if (is_a($this->loaded['cache'], 'CacheHelper')) {
				$cache =& $this->loaded['cache'];

				if ($cached === true) {
					$cache->view = &$this;
				}

				$cache->base			= $this->base;
				$cache->here			= $this->here;
				$cache->helpers			= $this->helpers;
				$cache->action			= $this->action;
				$cache->controllerName	= $this->name;
				$cache->layout	= $this->layout;
				$cache->cacheAction		= $this->cacheAction;
				$cache->cache($___viewFn, $out, $cached);
			}
		}

		return $out;
	}
/**
 * Loads helpers, with their dependencies.
 *
 * @param array $loaded List of helpers that are already loaded.
 * @param array $helpers List of helpers to load.
 * @return array
 */
	function &_loadHelpers(&$loaded, $helpers) {
		$helpers[] = 'Session';

		foreach ($helpers as $helper) {
			$parts = preg_split('/\/|\./', $helper);

			if (count($parts) === 1) {
				$plugin = $this->plugin;
			} else {
				$plugin = Inflector::underscore($parts['0']);
				$helper = $parts[count($parts) - 1];
			}
			$helperCn = $helper . 'Helper';

			if (in_array($helper, array_keys($loaded)) !== true) {
				if (!class_exists($helperCn)) {
				    if (is_null($plugin) || !loadPluginHelper($plugin, $helper)) {
						if (!loadHelper($helper)) {
							$this->cakeError('missingHelperFile', array(array(
								'helper' => $helper,
								'file' => Inflector::underscore($helper) . '.php',
								'base' => $this->base
							)));
							exit();
						}
				    }
					if (!class_exists($helperCn)) {
						$this->cakeError('missingHelperClass', array(array(
							'helper' => $helper,
							'file' => Inflector::underscore($helper) . '.php',
							'base' => $this->base
						)));
						exit();
					}
				}

				$camelBackedHelper = Inflector::variable($helper);
				${$camelBackedHelper} =& new $helperCn();

				$vars = array('base', 'webroot', 'here', 'params', 'action', 'data', 'themeWeb', 'plugin', 'namedArgs', 'argSeparator');
				$c = count($vars);
				for ($j = 0; $j < $c; $j++) {
					${$camelBackedHelper}->{$vars[$j]} = $this->{$vars[$j]};
				}

				if (!empty($this->validationErrors)) {
					${$camelBackedHelper}->validationErrors = $this->validationErrors;
				}

				$loaded[$helper] =& ${$camelBackedHelper};

				if (is_array(${$camelBackedHelper}->helpers)) {
					$loaded = &$this->_loadHelpers($loaded, ${$camelBackedHelper}->helpers);
				}
			}
		}
		return $loaded;
	}
/**
 * Render cached view
 *
 * @param string $filename the cache file to include
 * @param string $timeStart the page render start time
 * @return void
 */
	function renderCache($filename, $timeStart) {
		ob_start();
		include ($filename);

		if (Configure::read() > 0 && $this->layout != 'xml') {
			echo "<!-- Cached Render Time: " . round(getMicrotime() - $timeStart, 4) . "s -->";
		}

		$out = ob_get_clean();

		if (preg_match('/^<!--cachetime:(\\d+)-->/', $out, $match)) {
			if (time() >= $match['1']) {
				@unlink($filename);
				unset ($out);
				return;
			} else {
				if ($this->layout === 'xml') {
					header('Content-type: text/xml');
				}
				$out = str_replace('<!--cachetime:'.$match['1'].'-->', '', $out);
				e($out);
				die();
			}
		}
	}
/**
 * Return a misssing view error message
 *
 * @param string $viewFileName the filename that should exist
 * @return cakeError
 */
	function _missingView($viewFileName = null, $action = null) {
		if (!is_file($viewFileName) && !fileExistsInPath($viewFileName) || $viewFileName === '/' || $viewFileName === '\\') {
			if (strpos($action, 'missingAction') !== false) {
				$errorAction = 'missingAction';
			} else {
				$errorAction = 'missingView';
			}

			foreach (array($this->name, 'errors') as $viewDir) {
				$errorAction = Inflector::underscore($errorAction);
				if (file_exists(VIEWS . $viewDir . DS . $errorAction . $this->ext)) {
					$missingViewFileName = VIEWS . $viewDir . DS . $errorAction . $this->ext;
				} elseif (file_exists(VIEWS . $viewDir . DS . $errorAction . '.thtml')) {
					$missingViewFileName = VIEWS . $viewDir . DS . $errorAction . '.thtml';
				} elseif ($missingViewFileName = fileExistsInPath(LIBS . 'view' . DS . 'templates' . DS . $viewDir . DS . $errorAction . '.ctp')) {
				} else {
					$missingViewFileName = false;
				}
				$missingViewExists = is_file($missingViewFileName);

				if ($missingViewExists) {
					break;
				}
			}

			if (strpos($action, 'missingView') === false) {
				return $this->cakeError('missingView', array(
											array('className' => $this->name,
												'action' => $this->action,
												'file' => $viewFileName,
												'base' => $this->base)));
				exit();
			}
		}
	}
}
?>