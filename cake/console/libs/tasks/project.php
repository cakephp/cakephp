<?php
/**
 * The Project Task handles creating the base application
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.console.bake
 * @since         CakePHP(tm) v 1.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
/**
 * Task class for creating new project apps and plugins
 *
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 */
class ProjectTask extends Shell {

/**
 * configs path (used in testing).
 *
 * @var string
 */
	var $configPath = null;

/**
 * Checks that given project path does not already exist, and
 * finds the app directory in it. Then it calls bake() with that information.
 *
 * @param string $project Project path
 * @access public
 */
	function execute($project = null) {
		if ($project === null) {
			if (isset($this->args[0])) {
				$project = $this->args[0];
			}
		}

		if ($project) {
			$this->Dispatch->parseParams(array('-app', $project));
			$project = $this->params['working'];
		}

		if (empty($this->params['skel'])) {
			$this->params['skel'] = '';
			if (is_dir(CAKE . 'console' . DS . 'templates' . DS . 'skel') === true) {
				$this->params['skel'] = CAKE . 'console' . DS . 'templates' . DS . 'skel';
			}
		}

		while (!$project) {
			$prompt = __("What is the full path for this app including the app directory name?\n Example:", true);
			$default = $this->params['working'] . DS . 'myapp';
			$project = $this->in($prompt . $default, null, $default);
		}

		if ($project) {
			$response = false;
			while ($response == false && is_dir($project) === true && file_exists($project . 'config' . 'core.php')) {
				$prompt = sprintf(__('A project already exists in this location: %s Overwrite?', true), $project);
				$response = $this->in($prompt, array('y','n'), 'n');
				if (strtolower($response) === 'n') {
					$response = $project = false;
				}
			}
		}

		if ($this->bake($project)) {
			$path = Folder::slashTerm($project);
			if ($this->createHome($path)) {
				$this->out(__('Welcome page created', true));
			} else {
				$this->out(__('The Welcome page was NOT created', true));
			}

			if ($this->securitySalt($path) === true ) {
				$this->out(__('Random hash key created for \'Security.salt\'', true));
			} else {
				$this->err(sprintf(__('Unable to generate random hash for \'Security.salt\', you should change it in %s', true), CONFIGS . 'core.php'));
			}

			if ($this->securityCipherSeed($path) === true ) {
				$this->out(__('Random seed created for \'Security.cipherSeed\'', true));
			} else {
				$this->err(sprintf(__('Unable to generate random seed for \'Security.cipherSeed\', you should change it in %s', true), CONFIGS . 'core.php'));
			}

			$corePath = $this->corePath($path);
			if ($corePath === true ) {
				$this->out(sprintf(__('CAKE_CORE_INCLUDE_PATH set to %s in webroot/index.php', true), CAKE_CORE_INCLUDE_PATH));
				$this->out(sprintf(__('CAKE_CORE_INCLUDE_PATH set to %s in webroot/test.php', true), CAKE_CORE_INCLUDE_PATH));
				$this->out(__('Remember to check these value after moving to production server', true));
			} elseif ($corePath === false) {
				$this->err(sprintf(__('Unable to set CAKE_CORE_INCLUDE_PATH, you should change it in %s', true), $path . 'webroot' .DS .'index.php'));
			}
			$Folder = new Folder($path);
			if (!$Folder->chmod($path . 'tmp', 0777)) {
				$this->err(sprintf(__('Could not set permissions on %s', true), $path . DS .'tmp'));
				$this->out(sprintf(__('chmod -R 0777 %s', true), $path . DS .'tmp'));
			}

			$this->params['working'] = $path;
			$this->params['app'] = basename($path);
			return true;
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
			$skel = $this->in(sprintf(__("What is the path to the directory layout you wish to copy?\nExample: %s"), APP, null, ROOT . DS . 'myapp' . DS));
			if ($skel == '') {
				$this->out(__('The directory path you supplied was empty. Please try again.', true));
			} else {
				while (is_dir($skel) === false) {
					$skel = $this->in(__('Directory path does not exist please choose another:', true));
				}
			}
		}

		$app = basename($path);

		$this->out(__('Bake Project', true));
		$this->out(__("Skel Directory: ", true) . $skel);
		$this->out(__("Will be copied to: ", true) . $path);
		$this->hr();

		$looksGood = $this->in(__('Look okay?', true), array('y', 'n', 'q'), 'y');

		if (strtolower($looksGood) == 'y') {
			$verbose = $this->in(__('Do you want verbose output?', true), array('y', 'n'), 'n');

			$Folder = new Folder($skel);
			if (!empty($this->params['empty'])) {
				$skip = array();
			}
			if ($Folder->copy(array('to' => $path, 'skip' => $skip))) {
				$this->hr();
				$this->out(sprintf(__("Created: %s in %s", true), $app, $path));
				$this->hr();
			} else {
				$this->err(sprintf(__(" '%s' could not be created properly", true), $app));
				return false;
			}

			if (strtolower($verbose) == 'y') {
				foreach ($Folder->messages() as $message) {
					$this->out($message);
				}
			}

			return true;
		} elseif (strtolower($looksGood) == 'q') {
			$this->out(__('Bake Aborted.', true));
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
 * @access public
 */
	function createHome($dir) {
		$app = basename($dir);
		$path = $dir . 'views' . DS . 'pages' . DS;
		$source = CAKE . 'console' . DS . 'templates' . DS .'default' . DS . 'views' . DS . 'home.ctp';
		include($source);
		return $this->createFile($path.'home.ctp', $output);
	}

/**
 * Generates and writes 'Security.salt'
 *
 * @param string $path Project path
 * @return boolean Success
 * @access public
 */
	function securitySalt($path) {
		$File =& new File($path . 'config' . DS . 'core.php');
		$contents = $File->read();
		if (preg_match('/([\\t\\x20]*Configure::write\\(\\\'Security.salt\\\',[\\t\\x20\'A-z0-9]*\\);)/', $contents, $match)) {
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
	 * @access public
	 */
		function securityCipherSeed($path) {
			$File =& new File($path . 'config' . DS . 'core.php');
			$contents = $File->read();
			if (preg_match('/([\\t\\x20]*Configure::write\\(\\\'Security.cipherSeed\\\',[\\t\\x20\'A-z0-9]*\\);)/', $contents, $match)) {
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
 * @access public
 */
	function corePath($path) {
		if (dirname($path) !== CAKE_CORE_INCLUDE_PATH) {
			$File =& new File($path . 'webroot' . DS . 'index.php');
			$contents = $File->read();
			if (preg_match('/([\\t\\x20]*define\\(\\\'CAKE_CORE_INCLUDE_PATH\\\',[\\t\\x20\'A-z0-9]*\\);)/', $contents, $match)) {
				$root = strpos(CAKE_CORE_INCLUDE_PATH, '/') === 0 ? " DS . '" : "'";
				$result = str_replace($match[0], "\t\tdefine('CAKE_CORE_INCLUDE_PATH', " . $root . str_replace(DS, "' . DS . '", trim(CAKE_CORE_INCLUDE_PATH, DS)) . "');", $contents);
				if (!$File->write($result)) {
					return false;
				}
			} else {
				return false;
			}

			$File =& new File($path . 'webroot' . DS . 'test.php');
			$contents = $File->read();
			if (preg_match('/([\\t\\x20]*define\\(\\\'CAKE_CORE_INCLUDE_PATH\\\',[\\t\\x20\'A-z0-9]*\\);)/', $contents, $match)) {
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
 * @access public
 */
	function cakeAdmin($name) {
		$path = (empty($this->configPath)) ? CONFIGS : $this->configPath;
		$File =& new File($path . 'core.php');
		$contents = $File->read();
		if (preg_match('%([/\\t\\x20]*Configure::write\(\'Routing.prefixes\',[\\t\\x20\'a-z,\)\(]*\\);)%', $contents, $match)) {
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
 * @access public
 */
	function getPrefix() {
		$admin = '';
		$prefixes = Configure::read('Routing.prefixes');
		if (!empty($prefixes)) {
			if ($this->interactive) {
				$this->out();
				$this->out(__('You have more than one routing prefix configured', true));
			}
			if (count($prefixes) == 1) {
				return $prefixes[0] . '_';
			}
			$options = array();
			foreach ($prefixes as $i => $prefix) {
				$options[] = $i + 1;
				if ($this->interactive) {
					$this->out($i + 1 . '. ' . $prefix);
				}
			}
			$selection = $this->in(__('Please choose a prefix to bake with.', true), $options, 1);
			return $prefixes[$selection - 1] . '_';
		}
		if ($this->interactive) {
			$this->hr();
			$this->out('You need to enable Configure::write(\'Routing.prefixes\',array(\'admin\')) in /app/config/core.php to use prefix routing.');
			$this->out(__('What would you like the prefix route to be?', true));
			$this->out(__('Example: www.example.com/admin/controller', true));
			while ($admin == '') {
				$admin = $this->in(__("Enter a routing prefix:", true), null, 'admin');
			}
			if ($this->cakeAdmin($admin) !== true) {
				$this->out(__('Unable to write to /app/config/core.php.', true));
				$this->out('You need to enable Configure::write(\'Routing.prefixes\',array(\'admin\')) in /app/config/core.php to use prefix routing.');
				$this->_stop();
			}
			return $admin . '_';
		}
		return '';
	}

/**
 * Help
 *
 * @return void
 * @access public
 */
	function help() {
		$this->hr();
		$this->out("Usage: cake bake project <arg1>");
		$this->hr();
		$this->out('Commands:');
		$this->out();
		$this->out("project <name>");
		$this->out("\tbakes app directory structure.");
		$this->out("\tif <name> begins with '/' path is absolute.");
		$this->out();
		$this->_stop();
	}

}
?>