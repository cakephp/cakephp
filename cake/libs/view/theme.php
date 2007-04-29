<?php
/* SVN FILE: $Id$ */
/**
 * A custom view class that is used for themeing
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
 * Theme view class
 *
 * @package			cake
 * @subpackage		cake.cake.libs.view
 */
class ThemeView extends View {
/**
 * System path to themed element: themed . DS . theme . DS . elements . DS
 *
 * @var string
 */
	var $themeElement = null;
/**
 * System path to themed layout: themed . DS . theme . DS . layouts . DS
 *
 * @var string
 */
	var $themeLayout = null;
/**
 * System path to themed: themed . DS . theme . DS
 *
 * @var string
 */
	var $themePath = null;

/**
 * Enter description here...
 *
 * @param unknown_type $controller
 */
	function __construct (&$controller) {
		parent::__construct($controller);

    	$this->theme =& $controller->theme;
    	if(!empty($this->theme)) {
    		if(is_dir(WWW_ROOT . 'themed' . DS . $this->theme)){
    			$this->themeWeb = 'themed/'. $this->theme .'/';
    		}
    		$this->themeElement = 'themed'. DS . $this->theme . DS .'elements'. DS;
    		$this->themeLayout =  'themed'. DS . $this->theme . DS .'layouts'. DS;
    		$this->themePath = 'themed'. DS . $this->theme . DS;
    	}
	}

/**
 * Enter description here...
 *
 * @param unknown_type $code
 * @param unknown_type $name
 * @param unknown_type $message
 */
	function error($code, $name, $message) {
		$file = VIEWS . $this->themeLayout.'error'.$this->ext;
		if(!file_exists($file)) {
			$file = LAYOUTS.'error'.$this->ext;
		}
		header ("HTTP/1.0 {$code} {$name}");
		print ($this->_render($file, array('code' => $code,
														'name' => $name,
														'message' => $message)));
	}

/**
 * Enter description here...
 *
 * @param unknown_type $name
 * @param unknown_type $params
 * @return unknown
 */
	function renderElement($name, $params = array()) {
		if(isset($params['plugin'])) {
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
		foreach($viewPaths as $path) {
			if(file_exists($path . $this->themeElement . $name . $this->ext)) {
				$file = $path . $this->themeElement . $name . $this->ext;
				break;
			} else if(file_exists($path . $this->themeElement . $name . '.thtml')) {
				$file = $path . $this->themeElement . $name . '.thtml';
				break;
			} else if(file_exists($path . 'elements' . DS . $name . $this->ext)) {
				$file = $path . 'elements' . DS . $name . $this->ext;
				break;
			} else if(file_exists($path . 'elements' . DS . $name . '.thtml')) {
				$file = $path . 'elements' . DS . $name . '.thtml';
				break;
			}
		}

		if(!is_null($file)) {
			$params = array_merge_recursive($params, $this->loaded);
			return $this->_render($file, array_merge($this->viewVars, $params), false);
		}

		if(!is_null($this->pluginPath)) {
			$file = APP . $this->pluginPath . $this->themeElement . $name . $this->ext;
		} else {
			$file = VIEWS . $this->themeElement . $name . $this->ext;
		}

		if(Configure::read() > 0) {
			return "Not Found: " . $file;
		}
	}

/**
 * Enter description here...
 *
 * @param unknown_type $action
 * @return unknown
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
		foreach($viewPaths as $path) {
			if(file_exists($path . $this->themePath . $name . $this->ext)) {
				return $path . $this->themePath . $name . $this->ext;
			} else if(file_exists($path . $this->themePath . $name . '.thtml')) {
				return $path . $this->themePath . $name . '.thtml';
			} else if(file_exists($path . $name . $this->ext)) {
				return $path . $name . $this->ext;
			} else if(file_exists($path . $name . '.thtml')) {
				return $path . $name . '.thtml';
			}
		}

		if ($viewFileName = fileExistsInPath(LIBS . 'view' . DS . 'templates' . DS . 'errors' . DS . $type . $action . '.ctp')) {
			return $viewFileName;
		} elseif($viewFileName = fileExistsInPath(LIBS . 'view' . DS . 'templates' . DS . $this->viewPath . DS . $type . $action . '.ctp')) {
			return $viewFileName;
		} else {
			if(!is_null($this->pluginPath)) {
				$viewFileName = APP . $this->pluginPath . $this->themePath . $name . $this->ext;
			} else {
				$viewFileName = VIEWS . $this->themePath . $name . $this->ext;
			}
			$this->_missingView($viewFileName, $action);
		}
		return false;
	}

/**
 * Enter description here...
 *
 * @return unknown
 */
	function _getLayoutFileName() {
		if (isset($this->webservices) && !is_null($this->webservices)) {
			$type = strtolower($this->webservices) . DS;
		} else {
			$type = null;
		}

		if(!is_null($this->layoutPath)) {
			$type = $this->layoutPath . DS;
		}

		$paths = Configure::getInstance();
		$viewPaths = am($this->pluginPaths, $paths->viewPaths);

		$name = $this->subDir . $type . $this->layout;
		foreach($viewPaths as $path) {
			if(file_exists($path . $this->themeLayout . $name . $this->ext)) {
				return $path . $this->themeLayout . $name . $this->ext;
			} else if(file_exists($path . $this->themeLayout . $name . '.thtml')) {
				return $path . $this->themeLayout . $name . '.thtml';
			} else if(file_exists($path . 'layouts' . DS . $name . $this->ext)) {
				return $path . 'layouts' . DS . $name . $this->ext;
			} else if(file_exists($path . 'layouts' . DS . $name . '.thtml')) {
				return $path . 'layouts' . DS . $name . '.thtml';
			}
		}

		if(!is_null($this->pluginPath)) {
			$layoutFileName = APP . $this->pluginPath . 'views' . DS . $this->themeLayout . $name . $this->ext;
		} else {
			$layoutFileName = VIEWS . $this->themeLayout . $name . $this->ext;
		}

		$default = fileExistsInPath(LIBS . 'view' . DS . 'templates' . DS . 'layouts' . DS . $type . $this->layout . '.ctp');
		if (empty($default) && !empty($type)) {
			$default = fileExistsInPath(LIBS . 'view' . DS . 'templates' . DS . 'layouts' . DS . $type . 'default.ctp');
		}
		if(empty($default)) {
			$default = fileExistsInPath(LIBS . 'view' . DS . 'templates' . DS . 'layouts' . DS . $this->layout . '.ctp');
		}

		if(!empty($default)) {
			return $default;
		}
		return $layoutFileName;
	}
}

?>
