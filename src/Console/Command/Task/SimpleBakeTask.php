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
 * Base class for simple bake tasks code generator.
 */
abstract class SimpleBakeTask extends BakeTask {

/**
 * Tasks to be loaded by this Task
 *
 * @var array
 */
	public $tasks = ['Test', 'Template'];

/**
 * Get the generated object's name.
 *
 * @return string
 */
	abstract public function name();

/**
 * Get the generated object's filename without the leading path.
 *
 * @param string $name The name of the object being generated
 * @return string
 */
	abstract public function fileName($name);

/**
 * Get the template name.
 *
 * @return string
 */
	abstract public function template();

/**
 * Get template data.
 *
 * @return array
 */
	public function templateData() {
		$namespace = Configure::read('App.namespace');
		if ($this->plugin) {
			$namespace = Plugin::getNamespace($this->plugin);
		}
		return ['namespace' => $namespace];
	}

/**
 * Execute method
 *
 * @return void
 */
	public function main($name = null) {
		parent::main();
		if (empty($name)) {
			return $this->error('You must provide a name to bake a ' . $this->name());
		}
		$name = Inflector::camelize($name);
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
		$this->Template->set('name', $name);
		$this->Template->set($this->templateData());
		$contents = $this->Template->generate('classes', $this->template());

		$filename = $this->getPath() . $this->fileName($name);
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
		return $this->Test->bake($this->name(), $className);
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return \Cake\Console\ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$name = $this->name();
		$parser->description(
			__d('cake_console', 'Bake a %s class file.', $name)
		)->addArgument('name', [
			'help' => __d(
				'cake_console',
				'Name of the %s to bake. Can use Plugin.name to bake %s files into plugins.',
				$name,
				$name
			)
		])->addOption('no-test', [
			'boolean' => true,
			'help' => __d('cake_console', 'Do not generate a test skeleton.')
		]);

		return $parser;
	}

}
