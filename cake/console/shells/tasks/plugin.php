<?php
/**
 * The Plugin Task handles creating an empty plugin, ready to be used
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
 * @package       cake.console.shells.tasks
 * @since         CakePHP(tm) v 1.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'File');

/**
 * Task class for creating a plugin
 *
 * @package       cake.console.shells.tasks
 */
class PluginTask extends Shell {

/**
 * path to CONTROLLERS directory
 *
 * @var array
 * @access public
 */
	public $path = null;

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
	public function execute() {
		$plugin = null;

		if (isset($this->args[0])) {
			$plugin = Inflector::camelize($this->args[0]);
			$pluginPath = $this->_pluginPath($plugin);
			if (is_dir($pluginPath)) {
				$this->out(__('Plugin: %s', $plugin));
				$this->out(__('Path: %s', $pluginPath));
			} else {
				$this->_interactive($plugin);
			}
		} else {
			return $this->_interactive();
		}
	}

/**
 * Interactive interface
 *
 * @access private
 * @return void
 */
	protected function _interactive($plugin = null) {
		while ($plugin === null) {
			$plugin = $this->in(__('Enter the name of the plugin in CamelCase format'));
		}

		if (!$this->bake($plugin)) {
			$this->error(__("An error occured trying to bake: %s in %s", $plugin, $this->path . Inflector::underscore($pluginPath)));
		}
	}

/**
 * Bake the plugin, create directories and files
 *
 * @params $plugin name of the plugin in CamelCased format
 * @access public
 * @return bool
 */
	public function bake($plugin) {
		$pluginPath = Inflector::underscore($plugin);

		$pathOptions = App::path('plugins');
		if (count($pathOptions) > 1) {
			$this->findPath($pathOptions);
		}
		$this->hr();
		$this->out(__("<info>Plugin Name:</info> %s",  $plugin));
		$this->out(__("<info>Plugin Directory:</info> %s", $this->path . $pluginPath));
		$this->hr();

		$looksGood = $this->in(__('Look okay?'), array('y', 'n', 'q'), 'y');

		if (strtolower($looksGood) == 'y') {
			$Folder = new Folder($this->path . $pluginPath);
			$directories = array(
				'config' . DS . 'schema',
				'models' . DS . 'behaviors',
				'models' . DS . 'datasources',
				'console' . DS . 'shells' . DS . 'tasks',
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
				'webroot'
			);

			foreach ($directories as $directory) {
				$dirPath = $this->path . $pluginPath . DS . $directory;
				$Folder->create($dirPath);
				$File = new File($dirPath . DS . 'empty', true);
			}

			foreach ($Folder->messages() as $message) {
				$this->out($message, 1, Shell::VERBOSE);
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
			$this->out(__('<success>Created:</success> %s in %s', $plugin, $this->path . $pluginPath), 2);
		}

		return true;
	}

/**
 * find and change $this->path to the user selection
 *
 * @return string plugin path
 */
	public function findPath($pathOptions) {
		$valid = false;
		$max = count($pathOptions);
		while (!$valid) {
			foreach ($pathOptions as $i => $option) {
				$this->out($i + 1 .'. ' . $option);
			}
			$prompt = __('Choose a plugin path from the paths above.');
			$choice = $this->in($prompt);
			if (intval($choice) > 0 && intval($choice) <= $max) {
				$valid = true;
			}
		}
		$this->path = $pathOptions[$choice - 1];
	}

/**
 * get the option parser for the plugin task
 *
 * @return void
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(
			'Create the directory structure, AppModel and AppController classes for a new plugin. ' .
			'Can create plugins in any of your bootstrapped plugin paths.'
		)->addArgument('name', array(
			'help' => __('CamelCased name of the plugin to create.')
		));

	}

}
