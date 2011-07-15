<?php
/**
 * Base class for Bake Tasks.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class BakeTask extends Shell {

/**
 * Name of plugin
 *
 * @var string
 * @access public
 */
	var $plugin = null;

/**
 * The db connection being used for baking
 *
 * @var string
 * @access public
 */
	var $connection = null;

/**
 * Flag for interactive mode
 *
 * @var boolean
 */
	var $interactive = false;

/**
 * Gets the path for output.  Checks the plugin property
 * and returns the correct path.
 *
 * @return string Path to output.
 * @access public
 */
	function getPath() {
		$path = $this->path;
		if (isset($this->plugin)) {
			$name = substr($this->name, 0, strlen($this->name) - 4);
			$path = $this->_pluginPath($this->plugin) . Inflector::pluralize(Inflector::underscore($name)) . DS;
		}
		return $path;
	}
}
