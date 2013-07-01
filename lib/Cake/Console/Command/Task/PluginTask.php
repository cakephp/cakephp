<?php
/**
 * The Plugin Task handles creating an empty plugin, ready to be used
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppShell', 'Console/Command');
App::uses('File', 'Utility');
App::uses('Folder', 'Utility');

/**
 * The Plugin Task handles Creating an empty plugin, ready to be used
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
		$this->bootstrap = APP . 'Config' . DS . 'bootstrap.php';
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
 * @param string $plugin
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
 * @param string $skel
 * @param array $skip File names not to copy
 * @return boolean
 */
	public function bake($plugin, $skel = null, $skip = array('empty')) {
		$pathOptions = App::path('plugins');
		if (count($pathOptions) > 1) {
			$this->findPath($pathOptions);
		}

		if (!$skel && !empty($this->params['skel'])) {
			$skel = $this->params['skel'];
		}
		while (!$skel) {
			$skel = $this->in(
				__d('cake_console', "What is the path to the directory layout you wish to copy?"),
				null,
				CAKE . 'Console' . DS . 'Templates' . DS . 'plugin'
			);
			if (!$skel) {
				$this->err(__d('cake_console', 'The directory path you supplied was empty. Please try again.'));
			} else {
				while (is_dir($skel) === false) {
					$skel = $this->in(
						__d('cake_console', 'Directory path does not exist please choose another:'),
						null,
						CAKE . 'Console' . DS . 'Templates' . DS . 'skel'
					);
				}
			}
		}

		$path = $this->path . $plugin;

		$this->hr();
		$this->out(__d('cake_console', "<info>Plugin Name:</info> %s", $plugin));
		$this->out(__d('cake_console', '<info>Skel Directory</info>: %s', $skel));
		$this->out(__d('cake_console', '<info>Will be copied to</info>: %s', $path));
		$this->hr();
		$looksGood = $this->in(__d('cake_console', 'Look okay?'), array('y', 'n', 'q'), 'y');

		switch (strtolower($looksGood)) {
			case 'y':
				if (!empty($this->params['empty'])) {
					$skip = array();
				}

				$replacements = array(
					'__PLUGIN__' => $plugin,
					'__SINGULAR__' => Inflector::singularize($plugin),
					'__PLURAL__' => Inflector::pluralize($plugin)
				);

				if ($this->_copy($skel, $path, $skip, $replacements)) {
					$this->hr();
					$this->out(__d('cake_console', '<success>Created:</success> %s in %s', $plugin, $this->path));
					$this->hr();
				} else {
					$this->err(__d('cake_console', "<error>Could not create</error> '%s' properly.", $plugin));
					return false;
				}
				return true;
			case 'n':
				unset($this->args[0]);
				$this->execute();
				return false;
			case 'q':
				$this->out(__d('cake_console', '<error>Bake Aborted.</error>'));
				return false;
		}
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
 * @param array $pathOptions
 * @return void
 */
	public function findPath($pathOptions) {
		$valid = false;
		foreach ($pathOptions as $i => $path) {
			if (!is_dir($path)) {
				array_splice($pathOptions, $i, 1);
			}
		}
		$max = count($pathOptions);
		while (!$valid) {
			foreach ($pathOptions as $i => $option) {
				$this->out($i + 1 . '. ' . $option);
			}
			$prompt = __d('cake_console', 'Choose a plugin path from the paths above.');
			$choice = $this->in($prompt, null, 1);
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
		return $parser->description(__d('cake_console',
			'Create the directory structure, AppModel and AppController classes for a new plugin. ' .
			'Can create plugins in any of your bootstrapped plugin paths.'
		))->addArgument('name', array(
			'help' => __d('cake_console', 'CamelCased name of the plugin to create.')
		))->addOption('empty', array(
			'boolean' => true,
			'help' => __d('cake_console', 'Create empty files in each of the directories. Good if you are using git')
		))->addOption('skel', array(
			'default' => current(App::core('Console')) . 'Templates' . DS . 'plugin',
			'help' => __d('cake_console', 'The directory layout to use for the new plugin skeleton.')
		));
	}

/**
 * Copy one folder to another location recursively.
 *
 * @param string $from Source path
 * @param string $to Target path
 * @param array $skip File name/patterns not to copy
 * @param array $replacements find-and-replace pairs applied to paths and contents
 * @return boolean
 */
	protected function _copy($from, $to, $skip, $replacements = array()) {
		$copy = array(
			'dir' => array(),
			'file' => array()
		);

		$from = realpath($from);
		$fromLen = strlen($from);

		$Folder = new Folder($from);
		list($folders, $files) = $Folder->Tree($from, $skip);

		$toFolder = new Folder($to);
		if (!file_exists($to) && !$toFolder->create($to)) {
			$this->error(__d('cake_console', "Unable to create folder: %s", $to));
			return false;
		}
		$toFolder->cd($to);
		$to = $toFolder->pwd();

		foreach ($folders as $source) {
			$relative = realpath($source);
			$relative = trim(substr($relative, $fromLen), DS);
			if (!$relative) {
				continue;
			}

			$success = $this->_create(
				$source,
				Folder::addPathElement($to, $relative),
				'dir',
				$replacements
			);

			if (!$success) {
				return false;
			}
		}

		foreach ($files as $source) {
			$relative = realpath($source);
			$relative = trim(substr($relative, $fromLen), DS);
			if (!$relative) {
				continue;
			}

			$success = $this->_create(
				$source,
				Folder::addPathElement($to, $relative),
				'file',
				$replacements
			);

			if (!$success) {
				return false;
			}
		}

		return true;
	}

/**
 * Create a single file or folder
 *
 * @param string $from Source path
 * @param string $to Target path
 * @param string $type 'dir' or 'file'
 * @param array $replacements find-and-replace pairs applied to paths and contents
 * @return boolean
 */
	protected function _create($from, $to, $type, $replacements) {
		$to = str_replace(
			array_keys($replacements),
			array_values($replacements),
			$to
		);

		if ($type === 'dir') {
			$Folder = new Folder($to);

			if (file_exists($to)) {
				return true;
			} elseif($Folder->create($to)) {
				$Folder->cd($to);
				$this->out(sprintf(' * %s created', $Folder->pwd()), 1, Shell::VERBOSE);
				return true;
			}

			$this->error(__d('cake_console', "Error creating folder: %s", $to));
			return false;
		}

		$contents = file_get_contents($from);
		$contents = str_replace(
			array_keys($replacements),
			array_values($replacements),
			$contents
		);

		$File = new File($to, true);

		if ($File->write($contents)) {
			$this->out(sprintf(' * %s created', $File->pwd()), 1, Shell::VERBOSE);
			return true;
		}

		$this->error(__d('cake_console', "Error creating file: %s", $to));
		return false;
	}
}
