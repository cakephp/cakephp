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
			return $this->execute();
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
			return $path;
		}
	}

/**
 * Uses composer to generate a new package using the cakephp/app project.
 *
 * @param string $path Project path
 * @return mixed
 */
	public function bake($path) {
		$composer = $this->params['composer'];
		if (!file_exists($composer)) {
			$this->error(__d('cake_console', 'Cannot bake project. Could not find composer at "%s".', $composer));
			return false;
		}
		$this->out(__d('cake_console', '<info>Downloading a new cakephp app from packagist.org</info>'));

		$command = 'php ' . escapeshellarg($composer) . ' create-project --dev cakephp/app ' . escapeshellarg($path);

		$descriptorSpec = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w']
		];
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
 * Enables Configure::read('Routing.prefixes') in /app/Config/routes.php
 *
 * @param string $name Name to use as admin routing
 * @return bool Success
 */
	public function cakeAdmin($name) {
		$path = $this->appPath ?: APP;
		$path .= 'Config/';
		$File = new File($path . 'routes.php');
		$contents = $File->read();
		if (preg_match('%(\s*[/]*Configure::write\(\'Routing.prefixes\',[\s\'a-z,\)\(\]\[]*\);)%', $contents, $match)) {
			$result = str_replace($match[0], "\n" . 'Configure::write(\'Routing.prefixes\', [\'' . $name . '\']);', $contents);
			if ($File->write($result)) {
				Configure::write('Routing.prefixes', [$name]);
				return true;
			}
		}
		return false;
	}

/**
 * Checks for Configure::read('Routing.prefixes') and forces user to input it if not enabled
 *
 * @return string Admin route to use
 */
	public function getPrefix() {
		$admin = '';
		$prefixes = Configure::read('Routing.prefixes');
		if (!empty($prefixes)) {
			if (count($prefixes) === 1) {
				return $prefixes[0] . '_';
			}
			if ($this->interactive) {
				$this->out();
				$this->out(__d('cake_console', 'You have more than one routing prefix configured'));
			}
			$options = [];
			foreach ($prefixes as $i => $prefix) {
				$options[] = $i + 1;
				if ($this->interactive) {
					$this->out($i + 1 . '. ' . $prefix);
				}
			}
			$selection = $this->in(__d('cake_console', 'Please choose a prefix to bake with.'), $options, 1);
			return $prefixes[$selection - 1] . '_';
		}
		if ($this->interactive) {
			$this->hr();
			$this->out(__d('cake_console', 'You need to enable %s in %s to use prefix routing.',
					'Configure::write(\'Routing.prefixes\', [\'admin\'])',
					'/app/Config/routes.php'));
			$this->out(__d('cake_console', 'What would you like the prefix route to be?'));
			$this->out(__d('cake_console', 'Example: %s', 'www.example.com/admin/controller'));
			while (!$admin) {
				$admin = $this->in(__d('cake_console', 'Enter a routing prefix:'), null, 'admin');
			}
			if ($this->cakeAdmin($admin) !== true) {
				$this->out(__d('cake_console', '<error>Unable to write to</error> %s.', '/app/Config/routes.php'));
				$this->out(__d('cake_console', 'You need to enable %s in %s to use prefix routing.',
					'Configure::write(\'Routing.prefixes\', [\'admin\'])',
					'/app/Config/routes.php'));
				return $this->_stop();
			}
			return $admin . '_';
		}
		return '';
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
