<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppShell', 'Console/Command');
App::uses('File', 'Utility');
App::uses('Folder', 'Utility');

/**
 * The Plugin Task handles creating an empty plugin, ready to be used
 *
 * @package       Cake.Console.Command.Task
 */
class PluginTask extends AppShell {

/**
 * path to plugins directory
 *
 * @var array
 */
	public $path = null;

/**
 * Path to the bootstrap file. Changed in tests.
 *
 * @var string
 */
	public $bootstrap = null;

/**
 * initialize
 *
 * @return void
 */
	public function initialize() {
		$this->path = current(App::path('plugins'));
		$this->bootstrap = CONFIG . 'bootstrap.php';
	}

/**
 * Execution method always used for tasks
 *
 * @return void
 */
	public function execute() {
		if (isset($this->args[0])) {
			$plugin = Inflector::camelize($this->args[0]);
			$pluginPath = $this->_pluginPath($plugin);
			if (is_dir($pluginPath)) {
				$this->out(__d('cake_console', 'Plugin: %s already exists, no action taken', $plugin));
				$this->out(__d('cake_console', 'Path: %s', $pluginPath));
				return false;
			}
			$this->_interactive($plugin);
		} else {
			return $this->_interactive();
		}
	}

/**
 * Interactive interface
 *
 * @param string $plugin The plugin name.
 * @return void
 */
	protected function _interactive($plugin = null) {
		while ($plugin === null) {
			$plugin = $this->in(__d('cake_console', 'Enter the name of the plugin in CamelCase format'));
		}

		if (!$this->bake($plugin)) {
			$this->error(__d('cake_console', "An error occurred trying to bake: %s in %s", $plugin, $this->path . $plugin));
		}
	}

/**
 * Bake the plugin, create directories and files
 *
 * @param string $plugin Name of the plugin in CamelCased format
 * @return bool
 */
	public function bake($plugin) {
		$pathOptions = App::path('plugins');
		if (count($pathOptions) > 1) {
			$this->findPath($pathOptions);
		}
		$this->hr();
		$this->out(__d('cake_console', "<info>Plugin Name:</info> %s", $plugin));
		$this->out(__d('cake_console', "<info>Plugin Directory:</info> %s", $this->path . $plugin));
		$this->hr();

		$looksGood = $this->in(__d('cake_console', 'Look okay?'), array('y', 'n', 'q'), 'y');

		if (strtolower($looksGood) === 'y') {
			$Folder = new Folder($this->path . $plugin);
			$directories = array(
				'Config' . DS . 'Schema',
				'Console' . DS . 'Command' . DS . 'Task',
				'Console' . DS . 'Templates',
				'Controller' . DS . 'Component',
				'Lib',
				'Locale' . DS . 'eng' . DS . 'LC_MESSAGES',
				'Model' . DS . 'Behavior',
				'Model' . DS . 'Datasource',
				'Test' . DS . 'Case' . DS . 'Controller' . DS . 'Component',
				'Test' . DS . 'Case' . DS . 'Lib',
				'Test' . DS . 'Case' . DS . 'Model' . DS . 'Behavior',
				'Test' . DS . 'Case' . DS . 'Model' . DS . 'Datasource',
				'Test' . DS . 'Case' . DS . 'View' . DS . 'Helper',
				'Test' . DS . 'Fixture',
				'View' . DS . 'Elements',
				'View' . DS . 'Helper',
				'View' . DS . 'Layouts',
				'webroot' . DS . 'css',
				'webroot' . DS . 'js',
				'webroot' . DS . 'img',
			);

			foreach ($directories as $directory) {
				$dirPath = $this->path . $plugin . DS . $directory;
				$Folder->create($dirPath);
				new File($dirPath . DS . 'empty', true);
			}

			foreach ($Folder->messages() as $message) {
				$this->out($message, 1, Shell::VERBOSE);
			}

			$errors = $Folder->errors();
			if (!empty($errors)) {
				foreach ($errors as $message) {
					$this->error($message);
				}
				return false;
			}

			$controllerFileName = $plugin . 'AppController.php';

			$out = "<?php\n\n";
			$out .= "App::uses('AppController', 'Controller');\n\n";
			$out .= "class {$plugin}AppController extends AppController {\n\n";
			$out .= "}\n";
			$this->createFile($this->path . $plugin . DS . 'Controller' . DS . $controllerFileName, $out);

			$modelFileName = $plugin . 'AppModel.php';

			$out = "<?php\n\n";
			$out .= "App::uses('AppModel', 'Model');\n\n";
			$out .= "class {$plugin}AppModel extends AppModel {\n\n";
			$out .= "}\n";
			$this->createFile($this->path . $plugin . DS . 'Model' . DS . $modelFileName, $out);

			$this->_modifyBootstrap($plugin);

			$this->hr();
			$this->out(__d('cake_console', '<success>Created:</success> %s in %s', $plugin, $this->path . $plugin), 2);
		}

		return true;
	}

/**
 * Update the app's bootstrap.php file.
 *
 * @param string $plugin Name of plugin
 * @return void
 */
	protected function _modifyBootstrap($plugin) {
		$bootstrap = new File($this->bootstrap, false);
		$contents = $bootstrap->read();
		if (!preg_match("@\n\s*CakePlugin::loadAll@", $contents)) {
			$bootstrap->append("\nCakePlugin::load('$plugin', array('bootstrap' => false, 'routes' => false));\n");
			$this->out('');
			$this->out(__d('cake_dev', '%s modified', $this->bootstrap));
		}
	}

/**
 * find and change $this->path to the user selection
 *
 * @param array $pathOptions The list of paths to look in.
 * @return void
 */
	public function findPath($pathOptions) {
		$valid = false;
		foreach ($pathOptions as $i => $path) {
			if (!is_dir($path)) {
				unset($pathOptions[$i]);
			}
		}
		$pathOptions = array_values($pathOptions);

		$max = count($pathOptions);
		while (!$valid) {
			foreach ($pathOptions as $i => $option) {
				$this->out($i + 1 . '. ' . $option);
			}
			$prompt = __d('cake_console', 'Choose a plugin path from the paths above.');
			$choice = $this->in($prompt, null, 1);
			if ((int)$choice > 0 && (int)$choice <= $max) {
				$valid = true;
			}
		}
		$this->path = $pathOptions[$choice - 1];
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->description(
			__d('cake_console',	'Create the directory structure, AppModel and AppController classes for a new plugin. ' .
			'Can create plugins in any of your bootstrapped plugin paths.')
		)->addArgument('name', array(
			'help' => __d('cake_console', 'CamelCased name of the plugin to create.')
		));

		return $parser;
	}

}
