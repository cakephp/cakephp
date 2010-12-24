<?php
/**
 * The Project Task handles creating the base application
 *
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.console.shells.tasks
 * @since         CakePHP(tm) v 1.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'File');

/**
 * Task class for creating new project apps and plugins
 *
 * @package       cake.console.shells.tasks
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
 * @param string $project Project path
 */
	public function execute() {
		$project = null;
		if (isset($this->args[0])) {
			$project = $this->args[0];
		}

		if ($project && isset($_SERVER['PWD'])) {
			$project = $_SERVER['PWD'] . DS . $project;
		}

		if (empty($this->params['skel'])) {
			$core = App::core('shells');
			$skelPath = dirname($core[0]) . DS . 'templates' . DS . 'skel';
			if (is_dir($skelPath) === true) {
				$this->params['skel'] = $skelPath;
			}
		}

		while (!$project) {
			$prompt = __("What is the full path for this app including the app directory name?\n Example:");
			$default = APP_PATH . 'myapp';
			$project = $this->in($prompt . $default, null, $default);
		}

		if ($project) {
			$response = false;
			while ($response == false && is_dir($project) === true && file_exists($project . 'config' . 'core.php')) {
				$prompt = __('<warning>A project already exists in this location:</warning> %s Overwrite?', $project);
				$response = $this->in($prompt, array('y','n'), 'n');
				if (strtolower($response) === 'n') {
					$response = $project = false;
				}
			}
		}

		$success = true;
		if ($this->bake($project)) {
			$path = Folder::slashTerm($project);
			if ($this->createHome($path)) {
				$this->out(__(' * Welcome page created'));
			} else {
				$this->err(__('The Welcome page was <error>NOT</error> created'));
				$success = false;
			}

			if ($this->securitySalt($path) === true) {
				$this->out(__(' * Random hash key created for \'Security.salt\''));
			} else {
				$this->err(__('Unable to generate random hash for \'Security.salt\', you should change it in %s', CONFIGS . 'core.php'));
				$success = false;
			}

			if ($this->securityCipherSeed($path) === true) {
				$this->out(__(' * Random seed created for \'Security.cipherSeed\''));
			} else {
				$this->err(__('Unable to generate random seed for \'Security.cipherSeed\', you should change it in %s', CONFIGS . 'core.php'));
				$success = false;
			}

			if ($this->corePath($path) === true) {
				$this->out(__(' * CAKE_CORE_INCLUDE_PATH set to %s in webroot/index.php', CAKE_CORE_INCLUDE_PATH));
				$this->out(__(' * CAKE_CORE_INCLUDE_PATH set to %s in webroot/test.php', CAKE_CORE_INCLUDE_PATH));
				$this->out(__('   * <warning>Remember to check these value after moving to production server</warning>'));
			} else {
				$this->err(__('Unable to set CAKE_CORE_INCLUDE_PATH, you should change it in %s', $path . 'webroot' .DS .'index.php'));
				$success = false;
			}
			if ($this->consolePath($path) === true) {
				$this->out(__(' * app/console/cake.php path set.'));
			} else {
				$this->err(__('Unable to set console path for app/console.'));
				$success = false;
			}

			$Folder = new Folder($path);
			if (!$Folder->chmod($path . 'tmp', 0777)) {
				$this->err(__('Could not set permissions on %s', $path . DS .'tmp'));
				$this->out(__('chmod -R 0777 %s', $path . DS .'tmp'));
				$success = false;
			}
			if ($success) {
				$this->out(__('<success>Project baked successfully!</success>'));
			} else {
				$this->out(__('Project baked but with <warning>some issues.</warning>.'));
			}
			return $path;
		}
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
 * @access private
 */
	function bake($path, $skel = null, $skip = array('empty')) {
		if (!$skel) {
			$skel = $this->params['skel'];
		}
		while (!$skel) {
			$skel = $this->in(__("What is the path to the directory layout you wish to copy?\nExample: %s", APP, null, ROOT . DS . 'myapp' . DS));
			if ($skel == '') {
				$this->err(__('The directory path you supplied was empty. Please try again.'));
			} else {
				while (is_dir($skel) === false) {
					$skel = $this->in(__('Directory path does not exist please choose another:'));
				}
			}
		}

		$app = basename($path);

		$this->out(__('<info>Skel Directory</info>: ') . $skel);
		$this->out(__('<info>Will be copied to</info>: ') . $path);
		$this->hr();

		$looksGood = $this->in(__('Look okay?'), array('y', 'n', 'q'), 'y');

		if (strtolower($looksGood) == 'y') {
			$Folder = new Folder($skel);
			if (!empty($this->params['empty'])) {
				$skip = array();
			}

			if ($Folder->copy(array('to' => $path, 'skip' => $skip))) {
				$this->hr();
				$this->out(__('<success>Created:</success> %s in %s', $app, $path));
				$this->hr();
			} else {
				$this->err(__("<error>Could not create</error> '%s' properly.", $app));
				return false;
			}

			foreach ($Folder->messages() as $message) {
				$this->out(String::wrap(' * ' . $message), 1, Shell::VERBOSE);
			}

			return true;
		} elseif (strtolower($looksGood) == 'q') {
			$this->out(__('Bake Aborted.'));
		} else {
			$this->execute(false);
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
		$path = $dir . 'views' . DS . 'pages' . DS;
		$source = CAKE . 'console' . DS . 'templates' . DS .'default' . DS . 'views' . DS . 'home.ctp';
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
		$File = new File($path . 'console' . DS . 'cake.php');
		$contents = $File->read();
		if (preg_match('/(__CAKE_PATH__)/', $contents, $match)) {
			$path = CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS . 'console' . DS;
			$replacement = "'" . str_replace(DS, "' . DIRECTORY_SEPARATOR . '", $path) . "'";
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
		$File = new File($path . 'config' . DS . 'core.php');
		$contents = $File->read();
		if (preg_match('/([\s]*Configure::write\(\'Security.salt\',[\s\'A-z0-9]*\);)/', $contents, $match)) {
			if (!class_exists('Security')) {
				require LIBS . 'security.php';
			}
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
		$File = new File($path . 'config' . DS . 'core.php');
		$contents = $File->read();
		if (preg_match('/([\s]*Configure::write\(\'Security.cipherSeed\',[\s\'A-z0-9]*\);)/', $contents, $match)) {
			if (!class_exists('Security')) {
				require LIBS . 'security.php';
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
 * @return boolean Success
 */
	public function corePath($path) {
		if (dirname($path) !== CAKE_CORE_INCLUDE_PATH) {
			$File = new File($path . 'webroot' . DS . 'index.php');
			$contents = $File->read();
			if (preg_match('/([\s]*define\(\'CAKE_CORE_INCLUDE_PATH\',[\s\'A-z0-9]*\);)/', $contents, $match)) {
				$root = strpos(CAKE_CORE_INCLUDE_PATH, '/') === 0 ? " DS . '" : "'";
				$result = str_replace($match[0], "\t\tdefine('CAKE_CORE_INCLUDE_PATH', " . $root . str_replace(DS, "' . DS . '", trim(CAKE_CORE_INCLUDE_PATH, DS)) . "');", $contents);
				if (!$File->write($result)) {
					return false;
				}
			} else {
				return false;
			}

			$File = new File($path . 'webroot' . DS . 'test.php');
			$contents = $File->read();
			if (preg_match('/([\s]*define\(\'CAKE_CORE_INCLUDE_PATH\',[\s\'A-z0-9]*\);)/', $contents, $match)) {
				$result = str_replace($match[0], "\t\tdefine('CAKE_CORE_INCLUDE_PATH', " . $root . str_replace(DS, "' . DS . '", trim(CAKE_CORE_INCLUDE_PATH, DS)) . "');", $contents);
				if (!$File->write($result)) {
					return false;
				}
			} else {
				return false;
			}
			return true;
		}
	}

/**
 * Enables Configure::read('Routing.prefixes') in /app/config/core.php
 *
 * @param string $name Name to use as admin routing
 * @return boolean Success
 */
	public function cakeAdmin($name) {
		$path = (empty($this->configPath)) ? CONFIGS : $this->configPath;
		$File = new File($path . 'core.php');
		$contents = $File->read();
		if (preg_match('%([/\s]*Configure::write\(\'Routing.prefixes\',[\s\'a-z,\)\(]*\);)%', $contents, $match)) {
			$result = str_replace($match[0], "\t" . 'Configure::write(\'Routing.prefixes\', array(\''.$name.'\'));', $contents);
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
				$this->out(__('You have more than one routing prefix configured'));
			}
			$options = array();
			foreach ($prefixes as $i => $prefix) {
				$options[] = $i + 1;
				if ($this->interactive) {
					$this->out($i + 1 . '. ' . $prefix);
				}
			}
			$selection = $this->in(__('Please choose a prefix to bake with.'), $options, 1);
			return $prefixes[$selection - 1] . '_';
		}
		if ($this->interactive) {
			$this->hr();
			$this->out('You need to enable Configure::write(\'Routing.prefixes\',array(\'admin\')) in /app/config/core.php to use prefix routing.');
			$this->out(__('What would you like the prefix route to be?'));
			$this->out(__('Example: www.example.com/admin/controller'));
			while ($admin == '') {
				$admin = $this->in(__('Enter a routing prefix:'), null, 'admin');
			}
			if ($this->cakeAdmin($admin) !== true) {
				$this->out(__('<error>Unable to write to</error> /app/config/core.php.'));
				$this->out('You need to enable Configure::write(\'Routing.prefixes\',array(\'admin\')) in /app/config/core.php to use prefix routing.');
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
				__('Generate a new CakePHP project skeleton.')
			)->addArgument('name', array(
				'help' => __('Application directory to make, if it starts with "/" the path is absolute.')
			))->addOption('empty', array(
				'help' => __('Create empty files in each of the directories. Good if you are using git')
			))->addOption('skel', array(
				'help' => __('The directory layout to use for the new application skeleton. Defaults to cake/console/templates/skel of CakePHP used to create the project.')
			));
	}

}
