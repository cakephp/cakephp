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
namespace Cake\Console\Command\Task;

use Cake\Console\Command\Task\BakeTask;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Utility\File;
use Cake\Utility\Folder;
use Cake\Utility\Security;
use Cake\Utility\String;

/**
 * Task class for creating new project apps and plugins
 *
 */
class ProjectTask extends BakeTask {

/**
 * App path (used in testing).
 *
 * @var string
 */
	public $appPath = null;

/**
 * Checks that given project path does not already exist, and
 * finds the app directory in it. Then it calls bake() with that information.
 *
 * @return mixed
 */
	public function main() {
		$project = null;
		if (isset($this->args[0])) {
			$project = $this->args[0];
		} else {
			$appContents = array_diff(scandir(APP), ['.', '..']);
			if (empty($appContents)) {
				$suggestedPath = rtrim(APP, DS);
			} else {
				$suggestedPath = APP . 'MyApp';
			}
		}

		while (!$project) {
			$prompt = __d('cake_console', 'What is the path to the project you want to bake?');
			$project = $this->in($prompt, null, $suggestedPath);
		}

		$namespace = basename($project);
		if (!preg_match('/^\w[\w\d_]+$/', $namespace)) {
			$this->err(__d('cake_console', 'Project Name/Namespace needs to start with a letter and can only contain letters, digits and underscore'));
			$this->args = [];
			return $this->main();
		}

		if ($project && !Folder::isAbsolute($project) && isset($_SERVER['PWD'])) {
			$project = $_SERVER['PWD'] . DS . $project;
		}

		$response = false;
		while (!$response && is_dir($project) === true && file_exists($project . 'Config' . 'boostrap.php')) {
			$prompt = __d('cake_console', '<warning>A project already exists in this location:</warning> %s Overwrite?', $project);
			$response = $this->in($prompt, ['y', 'n'], 'n');
			if (strtolower($response) === 'n') {
				$response = $project = false;
			}
		}

		if ($project === false) {
			$this->out(__d('cake_console', 'Aborting project creation.'));
			return;
		}

		if ($this->bake($project)) {
			$this->out(__d('cake_console', '<success>Project baked successfully!</success>'));
			return $project;
		}
	}

/**
 * Uses either the CLI option or looks in $PATH and cwd for composer.
 *
 * @return string|false Either the path to composer or false if it cannot be found.
 */
	public function findComposer() {
		if (!empty($this->params['composer'])) {
			$path = $this->params['composer'];
			if (file_exists($path)) {
				return $path;
			}
		}
		$composer = false;
		$path = env('PATH');
		if (!empty($path)) {
			$paths = explode(PATH_SEPARATOR, $path);
			$composer = $this->_searchPath($paths);
		}
		return $composer;
	}

/**
 * Search the $PATH for composer.
 *
 * @param array $path The paths to search.
 * @return string|boolean
 */
	protected function _searchPath($path) {
		$composer = ['composer.phar', 'composer'];
		foreach ($path as $dir) {
			foreach ($composer as $cmd) {
				if (file_exists($dir . DS . $cmd)) {
					$this->_io->verbose('Found composer executable in ' . $dir);
					return $dir . DS . $cmd;
				}
			}
		}
		return false;
	}

/**
 * Uses composer to generate a new package using the cakephp/app project.
 *
 * @param string $path Project path
 * @return mixed
 */
	public function bake($path) {
		$composer = $this->findComposer();
		if (!$composer) {
			$this->error(__d('cake_console', 'Cannot bake project. Could not find composer. Add composer to your PATH, or use the -composer option.'));
			return false;
		}
		$this->out(__d('cake_console', '<info>Downloading a new cakephp app from packagist.org</info>'));

		$command = 'php ' . escapeshellarg($composer) . ' create-project -s dev cakephp/app ' . escapeshellarg($path);

		$descriptorSpec = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w']
		];
		$this->_io->verbose('Running ' . $command);
		$process = proc_open(
			$command,
			$descriptorSpec,
			$pipes
		);
		if (!is_resource($process)) {
			$this->error(__d('cake_console', 'Could not start subprocess.'));
			return false;
		}
		$output = $error = '';
		fclose($pipes[0]);

		$output = stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		$error = stream_get_contents($pipes[2]);
		fclose($pipes[2]);
		proc_close($process);

		if ($error) {
			$this->error(__d('cake_console', 'Installation from packagist.org failed with: %s', $error));
			return false;
		}
		$this->out($output);
		return true;
	}

/**
 * get the option parser.
 *
 * @return \Cake\Console\ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(
				__d('cake_console', 'Generate a new CakePHP project skeleton.')
			)->addArgument('name', [
				'help' => __d('cake_console', 'Application directory to make, if it starts with "/" the path is absolute.')
			])->addOption('empty', [
				'boolean' => true,
				'help' => __d('cake_console', 'Create empty files in each of the directories. Good if you are using git')
			])->addOption('theme', [
				'short' => 't',
				'help' => __d('cake_console', 'Theme to use when baking code.')
			])->addOption('composer', [
				'default' => ROOT . '/composer.phar',
				'help' => __d('cake_console', 'The path to the composer executable.')
			])->removeOption('plugin');
	}

}
