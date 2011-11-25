<?php
/**
 * The Project Task handles creating the base application
 *
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppShell', 'Console/Command');
App::uses('File', 'Utility');
App::uses('Folder', 'Utility');
App::uses('String', 'Utility');
App::uses('Security', 'Utility');

/**
 * Task class for creating new project apps and plugins
 *
 * @package       Cake.Console.Command.Task
 */
class ProjectTask extends AppShell {

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
		}

		while (!$project) {
			$prompt = __d('cake_console', "What is the path to the project you want to bake?");
			$project = $this->in($prompt, null, APP . 'myapp');
		}


		if ($project && !Folder::isAbsolute($project) && isset($_SERVER['PWD'])) {
			$project = $_SERVER['PWD'] . DS . $project;
		}

		$response = false;
		while ($response == false && is_dir($project) === true && file_exists($project . 'Config' . 'core.php')) {
			$prompt = __d('cake_console', '<warning>A project already exists in this location:</warning> %s Overwrite?', $project);
			$response = $this->in($prompt, array('y','n'), 'n');
			if (strtolower($response) === 'n') {
				$response = $project = false;
			}
		}

		$success = true;
		if ($this->bake($project)) {
			$path = Folder::slashTerm($project);
			if ($this->createHome($path)) {
				$this->out(__d('cake_console', ' * Welcome page created'));
			} else {
				$this->err(__d('cake_console', 'The Welcome page was <error>NOT</error> created'));
				$success = false;
			}

			if ($this->securitySalt($path) === true) {
				$this->out(__d('cake_console', ' * Random hash key created for \'Security.salt\''));
			} else {
				$this->err(__d('cake_console', 'Unable to generate random hash for \'Security.salt\', you should change it in %s', APP . 'Config' . DS . 'core.php'));
				$success = false;
			}

			if ($this->securityCipherSeed($path) === true) {
				$this->out(__d('cake_console', ' * Random seed created for \'Security.cipherSeed\''));
			} else {
				$this->err(__d('cake_console', 'Unable to generate random seed for \'Security.cipherSeed\', you should change it in %s', APP . 'Config' . DS . 'core.php'));
				$success = false;
			}

			if ($this->consolePath($path) === true) {
				$this->out(__d('cake_console', ' * app/Console/cake.php path set.'));
			} else {
				$this->err(__d('cake_console', 'Unable to set console path for app/Console.'));
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
				$this->out(__d('cake_console', ' * CAKE_CORE_INCLUDE_PATH set to %s in webroot/index.php', CAKE_CORE_INCLUDE_PATH));
				$this->out(__d('cake_console', ' * CAKE_CORE_INCLUDE_PATH set to %s in webroot/test.php', CAKE_CORE_INCLUDE_PATH));
			} else {
				$this->err(__d('cake_console', 'Unable to set CAKE_CORE_INCLUDE_PATH, you should change it in %s', $path . 'webroot' .DS .'index.php'));
				$success = false;
			}
			if ($success && $hardCode) {
				$this->out(__d('cake_console', '   * <warning>Remember to check these values after moving to production server</warning>'));
			}

			$Folder = new Folder($path);
			if (!$Folder->chmod($path . 'tmp', 0777)) {
				$this->err(__d('cake_console', 'Could not set permissions on %s', $path . DS .'tmp'));
				$this->out(__d('cake_console', 'chmod -R 0777 %s', $path . DS .'tmp'));
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
			if (file_exists($path . DS . 'Cake' . DS . 'bootstrap.php')) {
				return true;
			}
		}
		return false;
	}

/**
 * Looks for a skeleton template of a Cake application,
 * and if not found asks the user for a path. When there is a path
 * this method will make a deep copy of the skeleton to the project directory.
 * A default home page will be added, and the tmp file storage will be chmod'ed to 0777.
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
 * Writes a file with a default home page to the project.
 *
 * @param string $dir Path to project
 * @return boolean Success
 */
	public function createHome($dir) {
		$app = basename($dir);
		$path = $dir . 'View' . DS . 'Pages' . DS;
		$source = CAKE . 'Console' . DS . 'Templates' . DS .'default' . DS . 'views' . DS . 'home.ctp';
		include($source);
		return $this->createFile($path.'home.ctp', $output);
	}

/**
 * Generates the correct path to the CakePHP libs that are generating the project
 * and points app/console/cake.php to the right place
 *
 * @param string $path Project path.
 * @return boolean success
 */
	public function consolePath($path) {
		$File = new File($path . 'Console' . DS . 'cake.php');
		$contents = $File->read();
		if (preg_match('/(__CAKE_PATH__)/', $contents, $match)) {
			$root = strpos(CAKE_CORE_INCLUDE_PATH, '/') === 0 ? " \$ds . '" : "'";
			$replacement = $root . str_replace(DS, "' . \$ds . '", trim(CAKE_CORE_INCLUDE_PATH, DS)) . "'";
			$result = str_replace($match[0], $replacement, $contents);
			if ($File->write($result)) {
				return true;
			}
			return false;
		}
		return false;
	}

/**
 * Generates and writes 'Security.salt'
 *
 * @param string $path Project path
 * @return boolean Success
 */
	public function securitySalt($path) {
		$File = new File($path . 'Config' . DS . 'core.php');
		$contents = $File->read();
		if (preg_match('/([\s]*Configure::write\(\'Security.salt\',[\s\'A-z0-9]*\);)/', $contents, $match)) {
			$string = Security::generateAuthKey();
			$result = str_replace($match[0], "\t" . 'Configure::write(\'Security.salt\', \''.$string.'\');', $contents);
			if ($File->write($result)) {
				return true;
			}
			return false;
		}
		return false;
	}

/**
 * Generates and writes 'Security.cipherSeed'
 *
 * @param string $path Project path
 * @return boolean Success
	 */
	public function securityCipherSeed($path) {
		$File = new File($path . 'Config' . DS . 'core.php');
		$contents = $File->read();
		if (preg_match('/([\s]*Configure::write\(\'Security.cipherSeed\',[\s\'A-z0-9]*\);)/', $contents, $match)) {
			if (!class_exists('Security')) {
				require CAKE . 'Utility' . DS . 'security.php';
			}
			$string = substr(bin2hex(Security::generateAuthKey()), 0, 30);
			$result = str_replace($match[0], "\t" . 'Configure::write(\'Security.cipherSeed\', \''.$string.'\');', $contents);
			if ($File->write($result)) {
				return true;
			}
			return false;
		}
		return false;
	}

/**
 * Generates and writes CAKE_CORE_INCLUDE_PATH
 *
 * @param string $path Project path
 * @param boolean $hardCode Wether or not define calls should be hardcoded.
 * @return boolean Success
 */
	public function corePath($path, $hardCode = true) {
		if (dirname($path) !== CAKE_CORE_INCLUDE_PATH) {
			$filename = $path . 'webroot' . DS . 'index.php';
			if (!$this->_replaceCorePath($filename, $hardCode)) {
				return false;
			}
			$filename = $path . 'webroot' . DS . 'test.php';
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
		if ($count == 0) {
			return false;
		}
		return true;
	}

/**
 * Enables Configure::read('Routing.prefixes') in /app/Config/core.php
 *
 * @param string $name Name to use as admin routing
 * @return boolean Success
 */
	public function cakeAdmin($name) {
		$path = (empty($this->configPath)) ? APP . 'Config' . DS : $this->configPath;
		$File = new File($path . 'core.php');
		$contents = $File->read();
		if (preg_match('%(\s*[/]*Configure::write\(\'Routing.prefixes\',[\s\'a-z,\)\(]*\);)%', $contents, $match)) {
			$result = str_replace($match[0], "\n" . 'Configure::write(\'Routing.prefixes\', array(\''.$name.'\'));', $contents);
			if ($File->write($result)) {
				Configure::write('Routing.prefixes', array($name));
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
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
			if (count($prefixes) == 1) {
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
			$this->out(__d('cake_console', 'You need to enable Configure::write(\'Routing.prefixes\',array(\'admin\')) in /app/Config/core.php to use prefix routing.'));
			$this->out(__d('cake_console', 'What would you like the prefix route to be?'));
			$this->out(__d('cake_console', 'Example: www.example.com/admin/controller'));
			while ($admin == '') {
				$admin = $this->in(__d('cake_console', 'Enter a routing prefix:'), null, 'admin');
			}
			if ($this->cakeAdmin($admin) !== true) {
				$this->out(__d('cake_console', '<error>Unable to write to</error> /app/Config/core.php.'));
				$this->out(__d('cake_console', 'You need to enable Configure::write(\'Routing.prefixes\',array(\'admin\')) in /app/Config/core.php to use prefix routing.'));
				$this->_stop();
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
				'help' => __d('cake_console', 'Create empty files in each of the directories. Good if you are using git')
			))->addOption('skel', array(
				'default' => current(App::core('Console')) . 'Templates' . DS . 'skel',
				'help' => __d('cake_console', 'The directory layout to use for the new application skeleton. Defaults to cake/Console/Templates/skel of CakePHP used to create the project.')
			));
	}

}
