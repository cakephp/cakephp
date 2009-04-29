<?php
/* SVN FILE: $Id$ */
/**
 * The FixtureTest handles creating and updating fixture files.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008,	Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 * @since         CakePHP(tm) v 1.3
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Task class for creating and updating fixtures files.
 *
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 */
class FixtureTask extends Shell {
/**
 * Name of plugin
 *
 * @var string
 * @access public
 */
	var $plugin = null;
/**
 * Tasks to be loaded by this Task
 *
 * @var array
 * @access public
 */
	var $tasks = array('Model');
/**
 * path to fixtures directory
 *
 * @var string
 * @access public
 */
	var $path = null;
/**
 * Override initialize
 *
 * @access public
 */
	function initialize() {
		$this->path = $this->params['working'] . DS . 'tests' . DS . 'fixtures' . DS;
	}
/**
 * Execution method always used for tasks
 *
 * @access public
 */
	function execute() {
		if (empty($this->args)) {
			$this->__interactive();
		}

		if (isset($this->args[0])) {
			if (strtolower($this->args[0]) == 'all') {
				return $this->all();
			}
			$controller = Inflector::camelize($this->args[0]);
			$actions = null;
			if (isset($this->args[1]) && $this->args[1] == 'scaffold') {
				$this->out('Baking scaffold for ' . $controller);
				$actions = $this->bakeActions($controller);
			} else {
				$actions = 'scaffold';
			}
			if ($this->bake($controller, $actions)) {
				if ($this->_checkUnitTest()) {
					$this->bakeTest($controller);
				}
			}
		}
	}

/**
 * Bake All the Fixtures at once.  Will only bake fixtures for models that exist.
 *
 * @access public
 * @return void
 **/
	function all() {
		$ds = 'default';
		if (isset($this->params['connection'])) {
			$ds = $this->params['connection'];
		}
	}

/**
 * Interactive baking function
 *
 * @access private
 */
	function __interactive($modelName = false) {
		
	}

/**
 * Assembles and writes a Fixture file
 *
 * @return string Baked fixture
 * @access private
 */
	function bake() {
	
	}

/**
 * Displays help contents
 *
 * @access public
 */
	function help() {
		$this->hr();
		$this->out("Usage: cake bake fixture <arg1> <arg2>...");
		$this->hr();
		$this->out('Commands:');
		$this->out("\n\fixture <name>\n\t\tbakes fixture with specified name.");
		$this->out("\n\fixture all\n\t\tbakes all fixtures.");
		$this->out("");
		$this->_stop();
	}
}
?>
