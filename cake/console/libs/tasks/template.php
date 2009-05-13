<?php
/**
 * Template Task can generate templated output Used in other Tasks
 *
 * 
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org
 * @package       cake
 * @subpackage    cake.
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TemplateTask extends Shell {
/**
 * variables to add to template scope
 *
 * @var array
 **/
	var $templateVars = array();
/**
 * Initialize callback
 *
 * @access public
 * @return void
 **/
	function initialize() {
		$this->_paths = $this->Dispatch->shellPaths;
	}
/**
 * set the paths for the code generator to search for templates
 *
 * @param array $paths Array of paths to look in
 * @access public
 * @return void
 **/
	function setPaths($paths) {
		$this->_paths = $paths;
	}

/**
 * Find a template 
 *
 * @param string $directory Subdirectory to look for ie. 'views', 'objects'
 * @param string $filename lower_case_underscored filename you want.
 * @access public
 * @return string filename or false if scan failed.
 **/
	function _findTemplate($directory, $filename) {
		foreach ($this->_paths as $path) {
			$templatePath = $path . 'templates' . DS . $directory . DS . $filename . '.ctp';
			if (file_exists($templatePath) && is_file($templatePath)) {
				return $templatePath;
			}
		}
		return false;
	}

/**
 * Set variable values to the template scope
 *
 * @param mixed $one A string or an array of data.
 * @param mixed $two Value in case $one is a string (which then works as the key).
 *   Unused if $one is an associative array, otherwise serves as the values to $one's keys.
 * @return void
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
			$this->templateVars[$name] = $value;
		}
	}

/**
 * Runs the template
 *
 * @param string $directory directory / type of thing you want
 * @param string $filename template name
 * @param string $vars Additional vars to set to template scope.
 * @access public
 * @return contents of generated code template
 **/
	function generate($directory, $filename, $vars = null) {
		if ($vars !== null) {
			$this->set($vars);
		}
		$templateFile = $this->_findTemplate($directory, $filename);
		if ($templateFile) {
			extract($this->templateVars);
			ob_start();
			ob_implicit_flush(0);
			include($templateFile);
			$content = ob_get_clean();
			return $content;
		}
		return '';
	}
}