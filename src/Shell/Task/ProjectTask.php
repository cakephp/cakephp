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

use Cake\Filesystem\Folder;
use Cake\Shell\Task\BakeTask;

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
			$prompt = 'What is the path to the project you want to bake?';
			$project = $this->in($prompt, null, $suggestedPath);
		}

		$namespace = basename($project);
		if (!preg_match('/^\w[\w\d_]+$/', $namespace)) {
			$this->err('Project Name/Namespace needs to start with a letter and can only contain letters, digits and underscore');
			$this->args = [];
			return $this->main();
		}

		if ($project && !Folder::isAbsolute($project) && isset($_SERVER['PWD'])) {
			$project = $_SERVER['PWD'] . DS . $project;
		}

		$response = false;
		while (!$response && is_dir($project) === true && file_exists($project . '..' . DS . 'config' . DS . 'boostrap.php')) {
			$prompt = sprintf('<warning>A project already exists in this location:</warning> %s Overwrite?', $project);
			$response = $this->in($prompt, ['y', 'n'], 'n');
			if (strtolower($response) === 'n') {
				$response = $project = false;
			}
		}

		if ($project === false) {
			$this->out('Aborting project creation.');
			return;
		}

		if ($this->bake($project)) {
			$this->out('<success>Project baked successfully!</success>');
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
 * @return string|bool
 */
	protected function _searchPath($path) {
		$composer = ['composer.phar', 'composer'];
		foreach ($path as $dir) {
			foreach ($composer as $cmd) {
				if (is_file($dir . DS . $cmd)) {
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
			$this->error('Cannot bake project. Could not find composer. Add composer to your PATH, or use the -composer option.');
			return false;
		}
		$this->out('<info>Downloading a new cakephp app from packagist.org</info>');

		$command = 'php ' . escapeshellarg($composer) . ' create-project -s dev cakephp/app ' . escapeshellarg($path);

		try {
			$this->callProcess($command);
		} catch (\RuntimeException $e) {
			$error = $e->getMessage();
			$this->error('Installation from packagist.org failed with: %s', $error);
			return false;
		}

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
				'Generate a new CakePHP project skeleton.'
			)->addArgument('name', [
				'help' => 'Application directory to make, if it starts with "/" the path is absolute.'
			])->addOption('empty', [
				'boolean' => true,
				'help' => 'Create empty files in each of the directories. Good if you are using git'
			])->addOption('template', [
				'short' => 't',
				'help' => 'Template to use when baking code.'
			])->addOption('composer', [
				'default' => ROOT . '/composer.phar',
				'help' => 'The path to the composer executable.'
			])->removeOption('plugin');
	}

}
