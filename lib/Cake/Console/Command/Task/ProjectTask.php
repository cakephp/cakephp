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
 * @since         CakePHP(tm) v 1.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command\Task;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Utility\File;
use Cake\Utility\Folder;
use Cake\Utility\Security;
use Cake\Utility\String;

/**
 * Task class for creating new project apps and plugins
 *
 * @package       Cake.Console.Command.Task
 */
class ProjectTask extends Shell {

/**
 * configs path (used in testing).
 *
 * @var string
 */
	public $configPath = null;

/**
 * Checks that given project path does not already exist, and
 * finds the app directory in it. Then it calls bake() with that information.
 *
 * @return mixed
 */
	public function execute() {
		$project = null;
		if (isset($this->args[0])) {
			$project = $this->args[0];
		} else {
			$appContents = array_diff(scandir(APP), array('.', '..'));
			if (empty($appContents)) {
				$suggestedPath = rtrim(APP, DS);
			} else {
				$suggestedPath = APP . 'MyApp';
			}
		}

		while (!$project) {
			$prompt = __d('cake_console', "What is the path to the project you want to bake?");
			$project = $this->in($prompt, null, $suggestedPath);
		}

		$namespace = basename($project);
		if (!preg_match('/^\w[\w\d_]+$/', $namespace)) {
			$this->err(__d('cake_console', 'Project Name/Namespace needs to start with a letter and can only contain letters, digits and underscore'));
			$this->args = array();
			return $this->execute();
		}

		if ($project && !Folder::isAbsolute($project) && isset($_SERVER['PWD'])) {
			$project = $_SERVER['PWD'] . DS . $project;
		}

		$response = false;
		while (!$response && is_dir($project) === true && file_exists($project . 'Config' . 'boostrap.php')) {
			$prompt = __d('cake_console', '<warning>A project already exists in this location:</warning> %s Overwrite?', $project);
			$response = $this->in($prompt, array('y', 'n'), 'n');
			if (strtolower($response) === 'n') {
				$response = $project = false;
			}
		}

		$success = true;
		if ($this->bake($project)) {
			$path = Folder::slashTerm($project);

			if ($this->appNamespace($path) === true) {
				$this->out(__d('cake_console', ' * Namespace set for \'App.namespace\' and namespace declarations'));
			} else {
				$this->err(__d('cake_console', 'The namespace was <error>NOT</error> set'));
				$success = false;
			}

			if ($this->securitySalt($path) === true) {
				$this->out(__d('cake_console', ' * Random hash key created for \'Security.salt\''));
			} else {
				$this->err(__d('cake_console', 'Unable to generate random hash for \'Security.salt\', you should change it in %s', APP . 'Config' . DS . 'app.php'));
				$success = false;
			}

			if ($this->cachePrefix($path)) {
				$this->out(__d('cake_console', ' * Cache prefix set'));
			} else {
				$this->err(__d('cake_console', 'The cache prefix was <error>NOT</error> set'));
				$success = false;
			}

			$hardCode = false;
			if ($this->cakeOnIncludePath()) {
				$this->out(__d('cake_console', '<info>CakePHP is on your `include_path`. CAKE_CORE_INCLUDE_PATH will be set, but commented out.</info>'));
			} else {
				$this->out(__d('cake_console', '<warning>CakePHP is not on your `include_path`, CAKE_CORE_INCLUDE_PATH will be hard coded.</warning>'));
				$this->out(__d('cake_console', 'You can fix this by adding CakePHP to your `include_path`.'));
				$hardCode = true;
			}
			$success = $this->corePath($path, $hardCode) === true;
			if ($success) {
				$this->out(__d('cake_console', ' * CAKE_CORE_INCLUDE_PATH set to %s in %s', CAKE_CORE_INCLUDE_PATH, 'Config/paths.php'));
			} else {
				$this->err(__d('cake_console', 'Unable to set CAKE_CORE_INCLUDE_PATH, you should change it in %s', $path . 'Config' . DS . 'paths.php'));
				$success = false;
			}
			if ($success && $hardCode) {
				$this->out(__d('cake_console', '   * <warning>Remember to check these values after moving to production server</warning>'));
			}

			$Folder = new Folder($path);
			if (!$Folder->chmod($path . 'tmp', 0777)) {
				$this->err(__d('cake_console', 'Could not set permissions on %s', $path . DS . 'tmp'));
				$this->out('chmod -R 0777 ' . $path . DS . 'tmp');
				$success = false;
			}
			if ($success) {
				$this->out(__d('cake_console', '<success>Project baked successfully!</success>'));
			} else {
				$this->out(__d('cake_console', 'Project baked but with <warning>some issues.</warning>.'));
			}
			return $path;
		}
	}

/**
 * Checks PHP's include_path for CakePHP.
 *
 * @return boolean Indicates whether or not CakePHP exists on include_path
 */
	public function cakeOnIncludePath() {
		$paths = explode(PATH_SEPARATOR, ini_get('include_path'));
		foreach ($paths as $path) {
			if (file_exists($path . DS . 'Cake/bootstrap.php')) {
				return true;
			}
		}
		return false;
	}

/**
 * Looks for a skeleton template of a Cake application,
 * and if not found asks the user for a path. When there is a path
 * this method will make a deep copy of the skeleton to the project directory.
 *
 * @param string $path Project path
 * @param string $skel Path to copy from
 * @param string $skip array of directories to skip when copying
 * @return mixed
 */
	public function bake($path, $skel = null, $skip = array('empty')) {
		if (!$skel && !empty($this->params['skel'])) {
			$skel = $this->params['skel'];
		}
		while (!$skel) {
			$skel = $this->in(
				__d('cake_console', "What is the path to the directory layout you wish to copy?"),
				null,
				CAKE . 'Console' . DS . 'Templates' . DS . 'skel'
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

		$app = basename($path);

		$this->out(__d('cake_console', '<info>Skel Directory</info>: ') . $skel);
		$this->out(__d('cake_console', '<info>Will be copied to</info>: ') . $path);
		$this->out(__d('cake_console', '<info>With namespace</info>: ') . $app);
		$this->hr();

		$looksGood = $this->in(__d('cake_console', 'Look okay?'), array('y', 'n', 'q'), 'y');

		switch (strtolower($looksGood)) {
			case 'y':
				$Folder = new Folder($skel);
				if (!empty($this->params['empty'])) {
					$skip = array();
				}

				if ($Folder->copy(array('to' => $path, 'skip' => $skip))) {
					$this->hr();
					$this->out(__d('cake_console', '<success>Created:</success> %s in %s', $app, $path));
					$this->hr();
				} else {
					$this->err(__d('cake_console', "<error>Could not create</error> '%s' properly.", $app));
					return false;
				}

				foreach ($Folder->messages() as $message) {
					$this->out(String::wrap(' * ' . $message), 1, Shell::VERBOSE);
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
 * Writes 'App.namespace' to App/Config/app.php and fixes namespace declarations
 *
 * @param string $path Project path
 * @return boolean Success
 */
	public function appNamespace($path) {
		$namespace = basename($path);

		$File = new File($path . 'Config/app.php');
		$contents = $File->read();
		$contents = preg_replace(
			"/namespace = 'App'/",
			"namespace = '" . $namespace . "'",
			$contents,
			-1,
			$count
		);
		if (!$count || !$File->write($contents)) {
			return false;
		}

		$Folder = new Folder($path);
		$files = $Folder->findRecursive('.*\.php');
		foreach ($files as $filename) {
			$File = new File($filename);
			$contents = $File->read();
			$contents = preg_replace(
				'/namespace App\\\/',
				'namespace ' . $namespace . '\\',
				$contents,
				-1,
				$count
			);
			if ($count && !$File->write($contents)) {
				return false;
			}
		}

		return true;
	}

/**
 * Generates and writes 'Security.salt'
 *
 * @param string $path Project path
 * @return boolean Success
 */
	public function securitySalt($path) {
		$File = new File($path . 'Config/app.php');
		$contents = $File->read();
		$newSalt = Security::generateAuthKey();
		$contents = preg_replace(
			"/('Security.salt',\s+')([^']+)(')/m",
			'${1}' . $newSalt . '\\3',
			$contents,
			-1,
			$count
		);
		if ($count && $File->write($contents)) {
			return true;
		}
		return false;
	}

/**
 * Writes cache prefix using app's name
 *
 * @param string $dir Path to project
 * @return boolean Success
 */
	public function cachePrefix($dir) {
		$app = basename($dir);
		$File = new File($dir . 'Config/cache.php');
		$contents = $File->read();
		if (preg_match('/(\$prefix = \'myapp_\';)/', $contents, $match)) {
			$result = str_replace($match[0], '$prefix = \'' . $app . '_\';', $contents);
			return $File->write($result);
		}
		return false;
	}

/**
 * Generates and writes CAKE_CORE_INCLUDE_PATH
 *
 * @param string $path Project path
 * @param boolean $hardCode Whether or not define calls should be hardcoded.
 * @return boolean Success
 */
	public function corePath($path, $hardCode = true) {
		if (dirname($path) !== CAKE_CORE_INCLUDE_PATH) {
			$filename = $path . 'Config/paths.php';
			if (!$this->_replaceCorePath($filename, $hardCode)) {
				return false;
			}
			return true;
		}
	}

/**
 * Replaces the __CAKE_PATH__ placeholder in the template files.
 *
 * @param string $filename The filename to operate on.
 * @param boolean $hardCode Whether or not the define should be uncommented.
 * @return boolean Success
 */
	protected function _replaceCorePath($filename, $hardCode) {
		$contents = file_get_contents($filename);

		$root = strpos(CAKE_CORE_INCLUDE_PATH, '/') === 0 ? " DS . '" : "'";
		$corePath = $root . str_replace(DS, "' . DS . '", trim(CAKE_CORE_INCLUDE_PATH, DS)) . "'";

		$result = str_replace('__CAKE_PATH__', $corePath, $contents, $count);
		if ($hardCode) {
			$result = str_replace('//define(\'CAKE_CORE', 'define(\'CAKE_CORE', $result);
		}
		if (!file_put_contents($filename, $result)) {
			return false;
		}
		return (bool)$count;
	}

/**
 * Enables Configure::read('Routing.prefixes') in /app/Config/routes.php
 *
 * @param string $name Name to use as admin routing
 * @return boolean Success
 */
	public function cakeAdmin($name) {
		$path = (empty($this->configPath)) ? APP . 'Config/' : $this->configPath;
		$File = new File($path . 'routes.php');
		$contents = $File->read();
		if (preg_match('%(\s*[/]*Configure::write\(\'Routing.prefixes\',[\s\'a-z,\)\(]*\);)%', $contents, $match)) {
			$result = str_replace($match[0], "\n" . 'Configure::write(\'Routing.prefixes\', array(\'' . $name . '\'));', $contents);
			if ($File->write($result)) {
				Configure::write('Routing.prefixes', array($name));
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
			$options = array();
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
					'Configure::write(\'Routing.prefixes\', array(\'admin\'))',
					'/app/Config/core.php'));
			$this->out(__d('cake_console', 'What would you like the prefix route to be?'));
			$this->out(__d('cake_console', 'Example: %s', 'www.example.com/admin/controller'));
			while (!$admin) {
				$admin = $this->in(__d('cake_console', 'Enter a routing prefix:'), null, 'admin');
			}
			if ($this->cakeAdmin($admin) !== true) {
				$this->out(__d('cake_console', '<error>Unable to write to</error> %s.', '/app/Config/core.php'));
				$this->out(__d('cake_console', 'You need to enable %s in %s to use prefix routing.',
					'Configure::write(\'Routing.prefixes\', array(\'admin\'))',
					'/app/Config/core.php'));
				return $this->_stop();
			}
			return $admin . '_';
		}
		return '';
	}

/**
 * get the option parser.
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(
				__d('cake_console', 'Generate a new CakePHP project skeleton.')
			)->addArgument('name', array(
				'help' => __d('cake_console', 'Application directory to make, if it starts with "/" the path is absolute.')
			))->addOption('empty', array(
				'boolean' => true,
				'help' => __d('cake_console', 'Create empty files in each of the directories. Good if you are using git')
			))->addOption('theme', array(
				'short' => 't',
				'help' => __d('cake_console', 'Theme to use when baking code.')
			))->addOption('skel', array(
				'default' => current(App::core('Console')) . 'Templates' . DS . 'skel',
				'help' => __d('cake_console', 'The directory layout to use for the new application skeleton. Defaults to cake/Console/Templates/skel of CakePHP used to create the project.')
			));
	}

}
