<?php
/**
 * Scaffold.
 *
 * Automatic forms and actions generation for rapid web application development.
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
 * @package       Cake.View
 * @since         Cake v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ThemeView', 'View');

/**
 * ScaffoldView provides specific view file loading features for scaffolded views.
 *
 * @package       Cake.View
 */
class ScaffoldView extends ThemeView {

/**
 * Override _getViewFileName Appends special scaffolding views in.
 *
 * @param string $name name of the view file to get.
 * @return string action
 * @throws MissingViewException
 */
	protected function _getViewFileName($name = null) {
		if ($name === null) {
			$name = $this->action;
		}
		$name = Inflector::underscore($name);
		$prefixes = Configure::read('Routing.prefixes');

		if (!empty($prefixes)) {
			foreach ($prefixes as $prefix) {
				if (strpos($name, $prefix . '_') !== false) {
					$name = substr($name, strlen($prefix) + 1);
					break;
				}
			}
		}

		if ($name === 'add' || $name == 'edit') {
			$name = 'form';
		}

		$scaffoldAction = 'scaffold.' . $name;

		if (!is_null($this->subDir)) {
			$subDir = strtolower($this->subDir) . DS;
		} else {
			$subDir = null;
		}

		$names[] = $this->viewPath . DS . $subDir . $scaffoldAction;
		$names[] = 'Scaffolds' . DS . $subDir . $name;

		$paths = $this->_paths($this->plugin);
		$exts = array($this->ext);
		if ($this->ext !== '.ctp') {
			$exts[] = '.ctp';
		}
		foreach ($exts as $ext) {
			foreach ($paths as $path) {
				foreach ($names as $name) {
					if (file_exists($path . $name . $ext)) {
						return $path . $name . $ext;
					}
				}
			}
		}

		if ($name === 'Scaffolds' . DS . $subDir . 'error') {
			return CAKE . 'View' . DS . 'Errors' . DS . 'scaffold_error.ctp';
		}

		throw new MissingViewException($paths[0] . $name . $this->ext);
	}

}
