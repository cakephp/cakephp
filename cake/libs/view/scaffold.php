<?php
/**
 * Scaffold.
 *
 * Automatic forms and actions generation for rapid web application development.
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
 * @package       cake.libs.view
 * @since         Cake v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('View', 'Theme');

/**
 * ScaffoldView provides specific view file loading features for scaffolded views.
 *
 * @package cake.libs.view
 */
class ScaffoldView extends ThemeView {

/**
 * Override _getViewFileName Appends special scaffolding views in.
 *
 * @param string $name name of the view file to get.
 * @return string action
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
		$names[] = 'scaffolds' . DS . $subDir . $name;

		$paths = $this->_paths($this->plugin);
		$exts = array($this->ext);
		if ($this->ext !== '.ctp') {
			array_push($exts, '.ctp');
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

		if ($name === 'scaffolds' . DS . $subDir . 'error') {
			return LIBS . 'view' . DS . 'errors' . DS . 'scaffold_error.ctp';
		}

		throw new MissingViewException($paths[0] . $name . $this->ext);
	}
}
