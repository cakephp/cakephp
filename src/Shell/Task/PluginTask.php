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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Shell\Task;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Filesystem\File;
use Cake\Filesystem\Folder;
use Cake\Shell\Task\BakeTask;

/**
 * The Plugin Task handles creating an empty plugin, ready to be used
 *
 */
class PluginTask extends BakeTask {

/**
 * Path to the bootstrap file. Changed in tests.
 *
 * @var string
 */
	public $bootstrap = null;

/**
 * Tasks this task uses.
 *
 * @var array
 */
	public $tasks = ['Template', 'Project'];

/**
 * initialize
 *
 * @return void
 */
	public function initialize() {
		$this->path = current(App::path('Plugin'));
		$this->bootstrap = ROOT . DS . 'config' . DS . 'bootstrap.php';
	}

/**
 * Execution method always used for tasks
 *
 * @param string $name The name of the plugin to bake.
 * @return void
 */
	public function main($name = null) {
		if (empty($name)) {
			$this->err('<error>You must provide a plugin name in CamelCase format.</error>');
			$this->err('To make an "Example" plugin, run <info>Console/cake bake plugin Example</info>.');
			return false;
		}
		$plugin = $this->_camelize($name);
		$pluginPath = $this->_pluginPath($plugin);
		if (is_dir($pluginPath)) {
			$this->out(sprintf('Plugin: %s already exists, no action taken', $plugin));
			$this->out(sprintf('Path: %s', $pluginPath));
			return false;
		}
		if (!$this->bake($plugin)) {
			$this->error(sprintf("An error occurred trying to bake: %s in %s", $plugin, $this->path . $plugin));
		}
	}

/**
 * Bake the plugin, create directories and files
 *
 * @param string $plugin Name of the plugin in CamelCased format
 * @return bool
 */
	public function bake($plugin) {
		$pathOptions = App::path('Plugin');
		if (count($pathOptions) > 1) {
			$this->findPath($pathOptions);
		}
		$this->hr();
		$this->out(sprintf("<info>Plugin Name:</info> %s", $plugin));
		$this->out(sprintf("<info>Plugin Directory:</info> %s", $this->path . $plugin));
		$this->hr();

		$classBase = 'src';

		$looksGood = $this->in('Look okay?', ['y', 'n', 'q'], 'y');

		if (strtolower($looksGood) === 'y') {
			$Folder = new Folder($this->path . $plugin);
			$directories = [
				'config',
				$classBase . DS . 'Model' . DS . 'Behavior',
				$classBase . DS . 'Model' . DS . 'Table',
				$classBase . DS . 'Model' . DS . 'Entity',
				$classBase . DS . 'Shell' . DS . 'Task',
				$classBase . DS . 'Controller' . DS . 'Component',
				$classBase . DS . 'View' . DS . 'Helper',
				$classBase . DS . 'Template',
				'tests' . DS . 'TestCase' . DS . 'Controller' . DS . 'Component',
				'tests' . DS . 'TestCase' . DS . 'View' . DS . 'Helper',
				'tests' . DS . 'TestCase' . DS . 'Model' . DS . 'Behavior',
				'tests' . DS . 'Fixture',
				'webroot'
			];

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

			$controllerFileName = 'AppController.php';

			$out = "<?php\n\n";
			$out .= "namespace {$plugin}\\Controller;\n\n";
			$out .= "use App\\Controller\\AppController as BaseController;\n\n";
			$out .= "class AppController extends BaseController {\n\n";
			$out .= "}\n";
			$this->createFile($this->path . $plugin . DS . $classBase . DS . 'Controller' . DS . $controllerFileName, $out);

			$hasAutoloader = $this->_modifyAutoloader($plugin, $this->path);
			$this->_generateRoutes($plugin, $this->path);
			$this->_modifyBootstrap($plugin, $hasAutoloader);
			$this->_generatePhpunitXml($plugin, $this->path);
			$this->_generateTestBootstrap($plugin, $this->path);

			$this->hr();
			$this->out(sprintf('<success>Created:</success> %s in %s', $plugin, $this->path . $plugin), 2);
		}

		return true;
	}

/**
 * Update the app's bootstrap.php file.
 *
 * @param string $plugin Name of plugin
 * @param bool $hasAutoloader Whether or not there is an autoloader configured for
 * the plugin
 * @return void
 */
	protected function _modifyBootstrap($plugin, $hasAutoloader) {
		$bootstrap = new File($this->bootstrap, false);
		$contents = $bootstrap->read();
		if (!preg_match("@\n\s*Plugin::loadAll@", $contents)) {
			$autoload = $hasAutoloader ? null : "'autoload' => true, ";
			$bootstrap->append(sprintf(
				"\nPlugin::load('%s', [%s'bootstrap' => false, 'routes' => true]);\n",
				$plugin,
				$autoload
			));
			$this->out('');
			$this->out(sprintf('%s modified', $this->bootstrap));
		}
	}

/**
 * Generate a routes file for the plugin being baked.
 *
 * @param string $plugin The plugin to generate routes for.
 * @param string $path The path to save the routes.php file in.
 * @return void
 */
	protected function _generateRoutes($plugin, $path) {
		$this->Template->set([
			'plugin' => $plugin,
		]);
		$this->out('Generating routes.php file...');
		$out = $this->Template->generate('config', 'routes');
		$file = $path . $plugin . DS . 'config' . DS . 'routes.php';
		$this->createFile($file, $out);
	}

/**
 * Generate a phpunit.xml stub for the plugin.
 *
 * @param string $plugin Name of plugin
 * @param string $path The path to save the phpunit.xml file to.
 * @return void
 */
	protected function _generatePhpunitXml($plugin, $path) {
		$this->Template->set([
			'plugin' => $plugin,
			'path' => $path
		]);
		$this->out('Generating phpunit.xml file...');
		$out = $this->Template->generate('test', 'phpunit.xml');
		$file = $path . $plugin . DS . 'phpunit.xml';
		$this->createFile($file, $out);
	}

/**
 * Generate a Test/bootstrap.php stub for the plugin.
 *
 * @param string $plugin Name of plugin
 * @param string $path The path to save the phpunit.xml file to.
 * @return void
 */
	protected function _generateTestBootstrap($plugin, $path) {
		$this->Template->set([
			'plugin' => $plugin,
			'path' => $path,
			'root' => ROOT
		]);
		$this->out('Generating tests/bootstrap.php file...');
		$out = $this->Template->generate('test', 'bootstrap');
		$file = $path . $plugin . DS . 'tests' . DS . 'bootstrap.php';
		$this->createFile($file, $out);
	}

/**
 * Modifies App's composer.json to include the plugin and tries to call
 * composer dump-autoload to refresh the autoloader cache
 *
 * @param string $plugin Name of plugin
 * @param string $path The path to save the phpunit.xml file to.
 * @return bool True if composer could be modified correctly
 */
	protected function _modifyAutoloader($plugin, $path) {
		$path = dirname($path);
		$file = $path . DS . 'composer.json';

		if (!file_exists($file)) {
			return false;
		}

		$config = json_decode(file_get_contents($file), true);
		$config['autoload']['psr-4'][$plugin . '\\'] = "./plugins/$plugin/src";
		$config['autoload-dev']['psr-4'][$plugin . '\\Test\\'] = "./plugins/$plugin/tests";

		$this->out('<info>Modifying composer autoloader</info>');

		$out = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		$this->createFile($file, $out);

		$composer = $this->Project->findComposer();

		if (!$composer) {
			$this->error('Could not locate composer, Add composer to your PATH, or use the -composer option.');
			return false;
		}

		try {
			$command = 'cd ' . escapeshellarg($path) . '; ';
			$command .= 'php ' . escapeshellarg($composer) . ' dump-autoload';
			$this->callProcess($command);
		} catch (\RuntimeException $e) {
			$error = $e->getMessage();
			$this->error(sprintf('Could not run `composer dump-autoload`: %s', $error));
			return false;
		}

		return true;
	}

/**
 * find and change $this->path to the user selection
 *
 * @param array $pathOptions The list of paths to look in.
 * @return void
 */
	public function findPath(array $pathOptions) {
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
			$prompt = 'Choose a plugin path from the paths above.';
			$choice = $this->in($prompt, null, 1);
			if (intval($choice) > 0 && intval($choice) <= $max) {
				$valid = true;
			}
		}
		$this->path = $pathOptions[$choice - 1];
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return \Cake\Console\ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->description(
			'Create the directory structure, AppController class and testing setup for a new plugin. ' .
			'Can create plugins in any of your bootstrapped plugin paths.'
		)->addArgument('name', [
			'help' => 'CamelCased name of the plugin to create.'
		])->addOption('composer', [
			'default' => ROOT . '/composer.phar',
			'help' => 'The path to the composer executable.'
		])->removeOption('plugin');

		return $parser;
	}

}
