<?php
/**
 * Command-line code generation utility to automate programmer chores.
 *
 * Bake is CakePHP's code generation script, which can help you kickstart
 * application development by writing fully functional skeleton controllers,
 * models, and views. Going further, Bake can also write Unit Tests for you.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.console.libs
 * @since         CakePHP(tm) v 1.2.0.5012
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Bake is a command-line code generation utility for automating programmer chores.
 *
 * @package       cake
 * @subpackage    cake.cake.console.libs
 * @link          http://book.cakephp.org/view/1522/Code-Generation-with-Bake
 */
class BakeShell extends Shell {

/**
 * Contains tasks to load and instantiate
 *
 * @var array
 * @access public
 */
	var $tasks = array('Project', 'DbConfig', 'Model', 'Controller', 'View', 'Plugin', 'Fixture', 'Test');

/**
 * Override loadTasks() to handle paths
 *
 * @access public
 */
	function loadTasks() {
		parent::loadTasks();
		$task = Inflector::classify($this->command);
		if (isset($this->{$task}) && !in_array($task, array('Project', 'DbConfig'))) {
			if (isset($this->params['connection'])) {
				$this->{$task}->connection = $this->params['connection'];
			}
			foreach($this->args as $i => $arg) {
				if (strpos($arg, '.')) {
					list($this->params['plugin'], $this->args[$i]) = pluginSplit($arg);
					break;
				}
			}
			if (isset($this->params['plugin'])) {
				$this->{$task}->plugin = $this->params['plugin'];
			}
		}
	}

/**
 * Override main() to handle action
 *
 * @access public
 */
	function main() {
		if (!is_dir($this->DbConfig->path)) {
			if ($this->Project->execute()) {
				$this->DbConfig->path = $this->params['working'] . DS . 'config' . DS;
			} else {
				return false;
			}
		}

		if (!config('database')) {
			$this->out(__("Your database configuration was not found. Take a moment to create one.", true));
			$this->args = null;
			return $this->DbConfig->execute();
		}
		$this->out('Interactive Bake Shell');
		$this->hr();
		$this->out('[D]atabase Configuration');
		$this->out('[M]odel');
		$this->out('[V]iew');
		$this->out('[C]ontroller');
		$this->out('[P]roject');
		$this->out('[F]ixture');
		$this->out('[T]est case');
		$this->out('[Q]uit');

		$classToBake = strtoupper($this->in(__('What would you like to Bake?', true), array('D', 'M', 'V', 'C', 'P', 'F', 'T', 'Q')));
		switch ($classToBake) {
			case 'D':
				$this->DbConfig->execute();
				break;
			case 'M':
				$this->Model->execute();
				break;
			case 'V':
				$this->View->execute();
				break;
			case 'C':
				$this->Controller->execute();
				break;
			case 'P':
				$this->Project->execute();
				break;
			case 'F':
				$this->Fixture->execute();
				break;
			case 'T':
				$this->Test->execute();
				break;
			case 'Q':
				exit(0);
				break;
			default:
				$this->out(__('You have made an invalid selection. Please choose a type of class to Bake by entering D, M, V, F, T, or C.', true));
		}
		$this->hr();
		$this->main();
	}

/**
 * Quickly bake the MVC
 *
 * @access public
 */
	function all() {
		$this->hr();
		$this->out('Bake All');
		$this->hr();

		if (!isset($this->params['connection']) && empty($this->connection)) {
			$this->connection = $this->DbConfig->getConfig();
		}

		if (empty($this->args)) {
			$this->Model->interactive = true;
			$name = $this->Model->getName($this->connection);
		}

		foreach (array('Model', 'Controller', 'View') as $task) {
			$this->{$task}->connection = $this->connection;
			$this->{$task}->interactive = false;
		}

		if (!empty($this->args[0])) {
			$name = $this->args[0];
		}

		$modelExists = false;
		$model = $this->_modelName($name);
		if (App::import('Model', $model)) {
			$object = new $model();
			$modelExists = true;
		} else {
			App::import('Model', 'Model', false);
			$object = new Model(array('name' => $name, 'ds' => $this->connection));
		}

		$modelBaked = $this->Model->bake($object, false);

		if ($modelBaked && $modelExists === false) {
			$this->out(sprintf(__('%s Model was baked.', true), $model));
			if ($this->_checkUnitTest()) {
				$this->Model->bakeFixture($model);
				$this->Model->bakeTest($model);
			}
			$modelExists = true;
		}

		if ($modelExists === true) {
			$controller = $this->_controllerName($name);
			if ($this->Controller->bake($controller, $this->Controller->bakeActions($controller))) {
				$this->out(sprintf(__('%s Controller was baked.', true), $name));
				if ($this->_checkUnitTest()) {
					$this->Controller->bakeTest($controller);
				}
			}
			if (App::import('Controller', $controller)) {
				$this->View->args = array($controller);
				$this->View->execute();
				$this->out(sprintf(__('%s Views were baked.', true), $name));
			}
			$this->out(__('Bake All complete', true));
			array_shift($this->args);
		} else {
			$this->err(__('Bake All could not continue without a valid model', true));
		}
		$this->_stop();
	}

/**
 * Displays help contents
 *
 * @access public
 */
	function help() {
		$this->out('CakePHP Bake:');
		$this->hr();
		$this->out('The Bake script generates controllers, views and models for your application.');
		$this->out('If run with no command line arguments, Bake guides the user through the class');
		$this->out('creation process. You can customize the generation process by telling Bake');
		$this->out('where different parts of your application are using command line arguments.');
		$this->hr();
		$this->out("Usage: cake bake <command> <arg1> <arg2>...");
		$this->hr();
		$this->out('Params:');
		$this->out("\t-app <path> Absolute/Relative path to your app folder.\n");
		$this->out('Commands:');
		$this->out("\n\tbake help\n\t\tshows this help message.");
		$this->out("\n\tbake all <name>\n\t\tbakes complete MVC. optional <name> of a Model");
		$this->out("\n\tbake project <path>\n\t\tbakes a new app folder in the path supplied\n\t\tor in current directory if no path is specified");
		$this->out("\n\tbake plugin <name>\n\t\tbakes a new plugin folder in the path supplied\n\t\tor in current directory if no path is specified.");
		$this->out("\n\tbake db_config\n\t\tbakes a database.php file in config directory.");
		$this->out("\n\tbake model\n\t\tbakes a model. run 'bake model help' for more info");
		$this->out("\n\tbake view\n\t\tbakes views. run 'bake view help' for more info");
		$this->out("\n\tbake controller\n\t\tbakes a controller. run 'bake controller help' for more info");
		$this->out("\n\tbake fixture\n\t\tbakes fixtures. run 'bake fixture help' for more info.");
		$this->out("\n\tbake test\n\t\tbakes unit tests. run 'bake test help' for more info.");
		$this->out();

	}
}
