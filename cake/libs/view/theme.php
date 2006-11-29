<?php
/* SVN FILE: $Id$ */
/**
 * A custom view class that is used for themeing
 *
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
 * @subpackage		cake.cake.libs.view
 * @since			CakePHP v 0.10.0.1076
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
 * Enter description here...
 *
 * @var unknown_type
 */
	var $themeElement;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $themeLayout;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $themePath;

/**
 * Enter description here...
 *
 * @param unknown_type $controller
 */
	function __construct (&$controller) {
		parent::__construct($controller);
    	$this->theme =& $controller->theme;
    	$this->themeWeb = 'themed/'.$this->theme.'/';
    	$this->themeElement = VIEWS.'themed'.DS.$this->theme.DS.'elements'.DS;
    	$this->themeLayout =  VIEWS.'themed'.DS.$this->theme.DS.'layouts'.DS;
    	$this->themePath = VIEWS.'themed'.DS.$this->theme.DS;
	}

/**
 * Enter description here...
 *
 * @param unknown_type $code
 * @param unknown_type $name
 * @param unknown_type $message
 */
	function error($code, $name, $message) {
		$file = $this->themeLayout.'error'.$this->ext;
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
		$file = $this->themeElement.$name.$this->ext;
		if (!is_null($this->plugin)) {
			if (file_exists(APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . 'elements' . DS . $this->theme . DS . $name . $this->ext)) {
				$file = APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . 'elements' . DS . $this->theme . DS . $name . $this->ext;
				$params = array_merge_recursive($params, $this->loaded);
				return $this->_render($file, array_merge($this->viewVars, $params), false);
			} elseif (file_exists(APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . 'elements' . DS . $name . $this->ext)) {
				$file = APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . 'elements' . DS . $name . $this->ext;
				$params = array_merge_recursive($params, $this->loaded);
				return $this->_render($file, array_merge($this->viewVars, $params), false);
			} elseif (file_exists(APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . 'elements' . DS . $this->theme . DS . $name . '.thtml')) {
				$file = APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . 'elements' . DS . $this->theme . DS . $name . '.thtml';
				$params = array_merge_recursive($params, $this->loaded);
				return $this->_render($file, array_merge($this->viewVars, $params), false);
			} elseif (file_exists(APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . 'elements' . DS . $name . '.thtml')) {
				$file = APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . 'elements' . DS . $name . '.thtml';
				$params = array_merge_recursive($params, $this->loaded);
				return $this->_render($file, array_merge($this->viewVars, $params), false);
			}
		}

		$paths = Configure::getInstance();
		foreach($paths->viewPaths as $path) {
			if (file_exists($file)) {
				$params = array_merge_recursive($params, $this->loaded);
				return $this->_render($file, array_merge($this->viewVars, $params), false);
			} elseif (file_exists($path . 'elements' . DS . $name . $this->ext)) {
				$file = $path . 'elements' . DS . $name . $this->ext;
				$params = array_merge_recursive($params, $this->loaded);
				return $this->_render($file, array_merge($this->viewVars, $params), false);
			} elseif (file_exists($this->themeElement . $name . '.thtml')) {
				$file = $this->themeElement . $name . '.thtml';
				$params = array_merge_recursive($params, $this->loaded);
				return $this->_render($file, array_merge($this->viewVars, $params), false);
			} elseif (file_exists($path . 'elements' . DS . $name . '.thtml')) {
				$file = $path . 'elements' . DS . $name . '.thtml';
				$params = array_merge_recursive($params, $this->loaded);
				return $this->_render($file, array_merge($this->viewVars, $params), false);
			}
		}
		return "(Error rendering Element: {$name})";
	}

/**
 * Enter description here...
 *
 * @param unknown_type $action
 * @return unknown
 */
	function _getViewFileName($action) {
		$action = Inflector::underscore($action);
		$paths = Configure::getInstance();

		if (!is_null($this->webservices)) {
			$type = strtolower($this->webservices) . DS;
		} else {
			$type = null;
		}

		$position = strpos($action, '..');

		if ($position === false) {
		} else {
			$action = explode('/', $action);
			$i = array_search('..', $action);
			unset($action[$i - 1]);
			unset($action[$i]);
			$action='..' . DS . implode(DS, $action);
		}
		if (!is_null($this->plugin)) {
			$viewFileName = APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . $this->theme . DS . $this->viewPath . DS . $action . $this->ext;
			if (file_exists(APP . 'views' . DS . 'plugins' . DS . $this->plugin . DS . $this->subDir . $type . $action . $this->ext)) {
				return APP . 'views' . DS . 'plugins' . DS . $this->plugin . DS . $this->subDir . $type . $action . $this->ext;
			} elseif (file_exists($viewFileName)) {
				return $viewFileName;
			} elseif (file_exists(APP . 'views' . DS . 'plugins' . DS . $this->plugin . DS . $this->subDir . $type . $action . '.thtml')) {
				return APP . 'views' . DS . 'plugins' . DS . $this->plugin . DS . $this->subDir . $type . $action . '.thtml';
			} 	elseif (file_exists(APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . $this->viewPath . DS . $action . '.thtml')) {
				return APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . $this->viewPath . DS . $action . '.thtml';
			} else {
				return $this->cakeError('missingView', array(array(
												'className' => $this->name,
												'action' => $action,
												'file' => $viewFileName,
												'base' => $this->base)));
			}
		}

		foreach($paths->viewPaths as $path) {
			if (file_exists($path . $this->themePath . $this->viewPath . DS . $this->subDir . $type . $action . $this->ext)) {
				return $path . $this->themePath .  $this->viewPath . DS . $this->subDir . $type . $action . $this->ext;
			} elseif (file_exists($path . $this->viewPath . DS . $this->subDir . $type . $action . $this->ext)) {
				return $path . $this->viewPath . DS . $this->subDir . $type . $action . $this->ext;
			} elseif (file_exists($path . $this->themePath . $this->viewPath . DS . $this->subDir . $type . $action . '.thtml')) {
				return $path . $this->themePath .  $this->viewPath . DS . $this->subDir . $type . $action . '.thtml';
			} elseif (file_exists($path . $this->viewPath . DS . $this->subDir . $type . $action . '.thtml')) {
				return $path . $this->viewPath . DS . $this->subDir . $type . $action . '.thtml';
			}
		}

		if ($viewFileName = fileExistsInPath(LIBS . 'view' . DS . 'templates' . DS . 'errors' . DS . $type . $action . '.ctp')) {
		} elseif($viewFileName = fileExistsInPath(LIBS . 'view' . DS . 'templates' . DS . $this->viewPath . DS . $type . $action . '.ctp')) {
		} else {
			$viewFileName =  $this->themePath . $this->viewPath . DS . $this->subDir . $type . $action . $this->ext;
		}

		return $viewFileName;
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

		if (isset($this->plugin) && !is_null($this->plugin)) {
			if (file_exists(APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . 'layouts' . DS .  $this->theme . DS . $this->layout . $this->ext)) {
				return APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . 'layouts' . DS . $this->layout . $this->ext;
			} elseif (file_exists(APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . 'layouts' . DS . $this->layout . $this->ext)) {
				return APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . 'layouts' . DS . $this->layout . $this->ext;
			} elseif (file_exists(APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . 'layouts' . DS .  $this->theme . DS . $this->layout . '.thtml')) {
				return APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . 'layouts' . DS . $this->layout . '.thtml';
			} elseif (file_exists(APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . 'layouts' . DS . $this->layout . '.thtml')) {
				return APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . 'layouts' . DS . $this->layout . '.thtml';
			}
		}

		if (file_exists($this->themeLayout . $this->subDir . $type . $this->layout . $this->ext)) {
			$layoutFileName = $this->themeLayout . $this->subDir . $type . $this->layout . $this->ext;
		} elseif (file_exists(LAYOUTS . $this->subDir . $type . $this->layout . $this->ext)) {
			$layoutFileName = LAYOUTS . $this->subDir . $type . $this->layout . $this->ext;
		} elseif (file_exists($this->themeLayout . $this->subDir . $type . $this->layout . '.thtml')) {
			$layoutFileName = $this->themeLayout . $this->subDir . $type . $this->layout . '.thtml';
		} elseif (file_exists(LAYOUTS . $this->subDir . $type . $this->layout . '.thtml')) {
			$layoutFileName = LAYOUTS . $this->subDir . $type . $this->layout . '.thtml';
		}elseif ($layoutFileName = fileExistsInPath(LIBS . 'view' . DS . 'templates' . DS . 'layouts' . DS . $type . $this->layout . '.ctp')) {
		} else {
			$layoutFileName = LAYOUTS . $type . $this->layout . $this->ext;
		}

		return $layoutFileName;
	}
}

?>