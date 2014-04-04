<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command\Task;

use Cake\Console\Command\Task\BakeTask;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Utility\Inflector;

/**
 * Behavior code generator.
 */
class BehaviorTask extends BakeTask {

/**
 * Tasks to be loaded by this Task
 *
 * @var array
 */
	public $tasks = ['Test', 'Template'];

/**
 * Task name used in path generation.
 *
 * @var string
 */
	public $name = 'Model/Behavior';

/**
 * Override initialize
 *
 * @return void
 */
	public function initialize() {
		$this->path = current(App::path('Model/Behavior'));
	}

/**
 * Execute method
 *
 * @return void
 */
	public function execute() {
		parent::execute();
		$name = Inflector::classify($this->args[0]);
		$this->bake($name);
		$this->bakeTest($name);
	}

/**
 * Generate a class stub
 *
 * @param string $className The classname to generate.
 * @return void
 */
	public function bake($name) {
		$namespace = Configure::read('App.namespace');
		if ($this->plugin) {
			$namespace = Plugin::getNamespace($this->plugin);
		}
		$data = compact('name', 'namespace');
		$this->Template->set($data);
		$contents = $this->Template->generate('classes', 'behavior');

		$path = $this->getPath();
		$filename = $path . $name . 'Behavior.php';
		$this->createFile($filename, $contents);
		return $contents;
	}

/**
 * Generate a test case.
 *
 * @return void
 */
	public function bakeTest($className) {
		if (!empty($this->params['no-test'])) {
			return;
		}
		$this->Test->plugin = $this->plugin;
		return $this->Test->bake('Behavior', $className);
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return \Cake\Console\ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->description(
			__d('cake_console', 'Bake a behavior class file.')
		)->addArgument('name', [
			'help' => __d('cake_console', 'Name of the Behavior to bake. Can use Plugin.name to bake controllers into plugins.')
		])->addOption('plugin', [
			'short' => 'p',
			'help' => __d('cake_console', 'Plugin to bake the controller into.')
		])->addOption('theme', [
			'short' => 't',
			'help' => __d('cake_console', 'Theme to use when baking code.')
		])->addOption('no-test', [
			'boolean' => true,
			'help' => __d('cake_console', 'Do not generate a test skeleton.')
		])->addOption('force', [
			'short' => 'f',
			'boolean' => true,
			'help' => __d('cake_console', 'Force overwriting existing files without prompting.')
		]);

		return $parser;
	}
}
