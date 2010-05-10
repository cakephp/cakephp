<?php
/**
 * The Plugin Task handles creating an empty plugin, ready to be used
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
 * @subpackage    cake.cake.console.libs.tasks
 * @since         CakePHP(tm) v 1.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Task class for creating a plugin
 *
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 */
class PluginTask extends Shell {

/**
 * Tasks
 *
 */
	var $tasks = array('Model', 'Controller', 'View');

/**
 * path to CONTROLLERS directory
 *
 * @var array
 * @access public
 */
	var $path = null;

/**
 * initialize
 *
 * @return void
 */
	function initialize() {
		$this->path = APP . 'plugins' . DS;
	}

/**
 * Execution method always used for tasks
 *
 * @return void
 */
	function execute() {
		if (empty($this->params['skel'])) {
			$this->params['skel'] = '';
			if (is_dir(CAKE_CORE_INCLUDE_PATH . DS . CAKE . 'console' . DS . 'templates' . DS . 'skel') === true) {
				$this->params['skel'] = CAKE_CORE_INCLUDE_PATH . DS . CAKE . 'console' . DS . 'templates' . DS . 'skel';
			}
		}
		$plugin = null;

		if (isset($this->args[0])) {
			$plugin = Inflector::camelize($this->args[0]);
			$pluginPath = $this->_pluginPath($plugin);
			$this->Dispatch->shiftArgs();
			if (is_dir($pluginPath)) {
				$this->out(sprintf(__('Plugin: %s', true), $plugin));
				$this->out(sprintf(__('Path: %s', true), $pluginPath));
			} elseif (isset($this->args[0])) {
				$this->err(sprintf(__('%s in path %s not found.', true), $plugin, $pluginPath));
				$this->_stop();
			} else {
				$this->__interactive($plugin);
			}
		} else {
			return $this->__interactive();
		}

		if (isset($this->args[0])) {
			$task = Inflector::classify($this->args[0]);
			$this->Dispatch->shiftArgs();
			if (in_array($task, $this->tasks)) {
				$this->{$task}->plugin = $plugin;
				$this->{$task}->path = $pluginPath . Inflector::underscore(Inflector::pluralize($task)) . DS;

				if (!is_dir($this->{$task}->path)) {
					$this->err(sprintf(__("%s directory could not be found.\nBe sure you have created %s", true), $task, $this->{$task}->path));
				}
				$this->{$task}->loadTasks();
				return $this->{$task}->execute();
			}
		}
	}

/**
 * Interactive interface
 *
 * @access private
 * @return void
 */
	function __interactive($plugin = null) {
		while ($plugin === null) {
			$plugin = $this->in(__('Enter the name of the plugin in CamelCase format', true));
		}

		if (!$this->bake($plugin)) {
			$this->err(sprintf(__("An error occured trying to bake: %s in %s", true), $plugin, $this->path . Inflector::underscore($pluginPath)));
		}
	}

/**
 * Bake the plugin, create directories and files
 *
 * @params $plugin name of the plugin in CamelCased format
 * @access public
 * @return bool
 */
	function bake($plugin) {
		$pluginPath = Inflector::underscore($plugin);

		$pathOptions = App::path('plugins');
		if (count($pathOptions) > 1) {
			$this->findPath($pathOptions);
		}
		$this->hr();
		$this->out(sprintf(__("Plugin Name: %s", true),  $plugin));
		$this->out(sprintf(__("Plugin Directory: %s", true), $this->path . $pluginPath));
		$this->hr();

		$looksGood = $this->in(__('Look okay?', true), array('y', 'n', 'q'), 'y');

		if (strtolower($looksGood) == 'y') {
			$verbose = $this->in(__('Do you want verbose output?', true), array('y', 'n'), 'n');

			$Folder =& new Folder($this->path . $pluginPath);
			$directories = array(
				'config' . DS . 'schema',
				'models' . DS . 'behaviors',
				'models' . DS . 'datasources',
				'controllers' . DS . 'components',
				'libs',
				'views' . DS . 'helpers',
				'tests' . DS . 'cases' . DS . 'components',
				'tests' . DS . 'cases' . DS . 'helpers',
				'tests' . DS . 'cases' . DS . 'behaviors',
				'tests' . DS . 'cases' . DS . 'controllers',
				'tests' . DS . 'cases' . DS . 'models',
				'tests' . DS . 'groups',
				'tests' . DS . 'fixtures',
				'vendors',
				'vendors' . DS . 'shells' . DS . 'tasks',
				'webroot'
			);

			foreach ($directories as $directory) {
				$dirPath = $this->path . $pluginPath . DS . $directory;
				$Folder->create($dirPath);
				$File =& new File($dirPath . DS . 'empty', true);
			}

			if (strtolower($verbose) == 'y') {
				foreach ($Folder->messages() as $message) {
					$this->out($message);
				}
			}

			$errors = $Folder->errors();
			if (!empty($errors)) {
				return false;
			}

			$controllerFileName = $pluginPath . '_app_controller.php';

			$out = "<?php\n\n";
			$out .= "class {$plugin}AppController extends AppController {\n\n";
			$out .= "}\n\n";
			$out .= "?>";
			$this->createFile($this->path . $pluginPath. DS . $controllerFileName, $out);

			$modelFileName = $pluginPath . '_app_model.php';

			$out = "<?php\n\n";
			$out .= "class {$plugin}AppModel extends AppModel {\n\n";
			$out .= "}\n\n";
			$out .= "?>";
			$this->createFile($this->path . $pluginPath . DS . $modelFileName, $out);

			$this->hr();
			$this->out(sprintf(__("Created: %s in %s", true), $plugin, $this->path . $pluginPath));
			$this->hr();
		}

		return true;
	}

/**
 * find and change $this->path to the user selection
 *
 * @return void
 */
	function findPath($pathOptions) {
		$valid = false;
		$max = count($pathOptions);
		while (!$valid) {
			foreach ($pathOptions as $i => $option) {
				$this->out($i + 1 .'. ' . $option);
			}
			$prompt = __('Choose a plugin path from the paths above.', true);
			$choice = $this->in($prompt);
			if (intval($choice) > 0 && intval($choice) <= $max) {
				$valid = true;
			}
		}
		$this->path = $pathOptions[$choice - 1];
	}

/**
 * Help
 *
 * @return void
 * @access public
 */
	function help() {
		$this->hr();
		$this->out("Usage: cake bake plugin <arg1> <arg2>...");
		$this->hr();
		$this->out('Commands:');
		$this->out();
		$this->out("plugin <name>");
		$this->out("\tbakes plugin directory structure");
		$this->out();
		$this->out("plugin <name> model");
		$this->out("\tbakes model. Run 'cake bake model help' for more info.");
		$this->out();
		$this->out("plugin <name> controller");
		$this->out("\tbakes controller. Run 'cake bake controller help' for more info.");
		$this->out();
		$this->out("plugin <name> view");
		$this->out("\tbakes view. Run 'cake bake view help' for more info.");
		$this->out();
		$this->_stop();
	}
}
