<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
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
 * @subpackage		cake.cake.libs
 * @since			CakePHP v 1.0.0.2363
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class Configure extends Object {
/**
 * Hold array with paths to view files
 *
 * @var array
 * @access public
 */
	var $viewPaths = array();
/**
 * Hold array with paths to controller files
 *
 * @var array
 * @access public
 */
	var $controllerPaths = array();
/**
 * Enter description here...
 *
 * @var array
 * @access public
 */
	var $modelPaths = array();
/**
 * Enter description here...
 *
 * @var array
 * @access public
 */
	var $helperPaths = array();
/**
 * Enter description here...
 *
 * @var array
 * @access public
 */
	var $componentPaths = array();
/**
 * Enter description here...
 *
 * @var array
 * @access public
 */
	var $behaviorPaths = array();
/**
 * Return a singleton instance of Configure.
 *
 * @return Configure instance
 * @access public
 */
	function &getInstance() {
		static $instance = array();
		if (!$instance) {
			$instance[0] =& new Configure;
			$instance[0]->__loadBootstrap();
		}
		return $instance[0];
	}
/**
 * Sets the var modelPaths
 *
 * @param array $modelPaths
 * @access private
 */
	function __buildModelPaths($modelPaths) {
		$_this =& Configure::getInstance();
		$_this->modelPaths[] = MODELS;
		if (isset($modelPaths)) {
			foreach($modelPaths as $value) {
				$_this->modelPaths[] = $value;
			}
		}
	}
/**
 * Sets the var viewPaths
 *
 * @param array $viewPaths
 * @access private
 */
	function __buildViewPaths($viewPaths) {
		$_this =& Configure::getInstance();
		$_this->viewPaths[] = VIEWS;
		$_this->viewPaths[] = VIEWS . 'errors' . DS;
		if (isset($viewPaths)) {
			foreach($viewPaths as $value) {
				$_this->viewPaths[] = $value;
			}
		}
	}
/**
 * Sets the var controllerPaths
 *
 * @param array $controllerPaths
 * @access private
 */
	function __buildControllerPaths($controllerPaths) {
		$_this =& Configure::getInstance();
		$_this->controllerPaths[] = CONTROLLERS;
		if (isset($controllerPaths)) {
			foreach($controllerPaths as $value) {
				$_this->controllerPaths[] = $value;
			}
		}
	}
/**
 * Sets the var helperPaths
 *
 * @param array $helperPaths
 * @access private
 */
	function __buildHelperPaths($helperPaths) {
		$_this =& Configure::getInstance();
		$_this->helperPaths[] = HELPERS;
		if (isset($helperPaths)) {
			foreach($helperPaths as $value) {
				$_this->helperPaths[] = $value;
			}
		}
	}
/**
 * Sets the var componentPaths
 *
 * @param array $componentPaths
 * @access private
 */
	function __buildComponentPaths($componentPaths) {
		$_this =& Configure::getInstance();
		$_this->componentPaths[] = COMPONENTS;
		if (isset($componentPaths)) {
			foreach($componentPaths as $value) {
				$_this->componentPaths[] = $value;
			}
		}
	}
/**
 * Sets the var behaviorPaths
 *
 * @param array $behaviorPaths
 * @access private
 */
	function __buildBehaviorPaths($behaviorPaths) {
		$_this =& Configure::getInstance();
		$_this->behaviorPaths[] = BEHAVIORS;
		if (isset($behaviorPaths)) {
			foreach($behaviorPaths as $value) {
				$_this->behaviorPaths[] = $value;
			}
		}
	}
/**
 * Loads the app/config/bootstrap.php
 * If the alternative paths are set in this file
 * they will be added to the paths vars
 *
 * @access private
 */
	function __loadBootstrap() {
		$_this =& Configure::getInstance();
		$modelPaths = null;
		$viewPaths = null;
		$controllerPaths = null;
		$helperPaths = null;
		$componentPaths = null;
		$behaviorPaths = null;
		require APP_PATH . 'config' . DS . 'bootstrap.php';
		$_this->__buildModelPaths($modelPaths);
		$_this->__buildViewPaths($viewPaths);
		$_this->__buildControllerPaths($controllerPaths);
		$_this->__buildHelperPaths($helperPaths);
		$_this->__buildComponentPaths($componentPaths);
		$_this->__buildBehaviorPaths($behaviorPaths);
	}
}

?>