<?php
/**
 * Base class for Bake Tasks.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.console.shells.tasks
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
	public $plugin = null;

/**
 * The db connection being used for baking
 *
 * @var string
 * @access public
 */
	public $connection = null;

/**
 * Flag for interactive mode
 *
 * @var boolean
 */
	public $interactive = false;

/**
 * Gets the path for output.  Checks the plugin property
 * and returns the correct path.
 *
 * @return string Path to output.
 */
	public function getPath() {
		$path = $this->path;
		if (isset($this->plugin)) {
			$path = $this->_pluginPath($this->plugin) . Inflector::pluralize(Inflector::underscore($this->name)) . DS;
		}
		return $path;
	}

/**
 * Base execute method parses some parameters and sets some properties on the bake tasks.
 * call when overriding execute()
 *
 * @return void
 */
	public function execute() {
		foreach($this->args as $i => $arg) {
			if (strpos($arg, '.')) {
				list($this->params['plugin'], $this->args[$i]) = pluginSplit($arg);
				break;
			}
		}
		if (isset($this->params['plugin'])) {
			$this->plugin = $this->params['plugin'];
		}
	}

}
