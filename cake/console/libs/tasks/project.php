<?php
/* SVN FILE: $Id$ */
/**
 * The Project Task handles creating the base application
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.scripts.bake
 * @since         CakePHP(tm) v 1.2
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
if (!class_exists('File')) {
	uses('file');
}
/**
 * Task class for creating new project apps and plugins
 *
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 */
class ProjectTask extends Shell {
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
				$this->Dispatch->shiftArgs();
			}
		}

		if ($project) {
			$this->Dispatch->parseParams(array('-app', $project));
			$project = $this->params['working'];
		}

		if (empty($this->params['skel'])) {
			$this->params['skel'] = '';
			if (is_dir(CAKE_CORE_INCLUDE_PATH.DS.'cake'.DS.'console'.DS.'libs'.DS.'templates'.DS.'skel') === true) {
				$this->params['skel'] = CAKE_CORE_INCLUDE_PATH.DS.'cake'.DS.'console'.DS.'libs'.DS.'templates'.DS.'skel';
			}
		}

		while (!$project) {
			$project = $this->in("What is the full path for this app including the app directory name?\nExample: ".$this->params['working'] . DS . "myapp", null, $this->params['working'] . DS . 'myapp');
		}

		if ($project) {
			$response = false;
			while ($response == false && is_dir($project) === true && file_exists($project . 'config' . 'core.php')) {
				$response = $this->in('A project already exists in this location: '.$project.' Overwrite?', array('y','n'), 'n');
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

		$this->out('Bake Project');
		$this->out("Skel Directory: $skel");
		$this->out("Will be copied to: {$path}");
		$this->hr();

		$looksGood = $this->in('Look okay?', array('y', 'n', 'q'), 'y');

		if (strtolower($looksGood) == 'y' || strtolower($looksGood) == 'yes') {
			$verbose = $this->in(__('Do you want verbose output?', true), array('y', 'n'), 'n');

			$Folder = new Folder($skel);
			if ($Folder->copy(array('to' => $path, 'skip' => $skip))) {
				$this->hr();
				$this->out(sprintf(__("Created: %s in %s", true), $app, $path));
				$this->hr();
			} else {
				$this->err(" '" . $app . "' could not be created properly");
				return false;
			}

			if (strtolower($verbose) == 'y' || strtolower($verbose) == 'yes') {
				foreach ($Folder->messages() as $message) {
					$this->out($message);
				}
			}

			return true;
		} elseif (strtolower($looksGood) == 'q' || strtolower($looksGood) == 'quit') {
			$this->out('Bake Aborted.');
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
		include(CAKE_CORE_INCLUDE_PATH.DS.'cake'.DS.'console'.DS.'libs'.DS.'templates'.DS.'views'.DS.'home.ctp');
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
				uses('Security');
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
				$result = str_replace($match[0], "\t\tdefine('CAKE_CORE_INCLUDE_PATH', '" . CAKE_CORE_INCLUDE_PATH . "');", $contents);
				if (!$File->write($result)) {
					return false;
				}
			} else {
				return false;
			}

			$File =& new File($path . 'webroot' . DS . 'test.php');
			$contents = $File->read();
			if (preg_match('/([\\t\\x20]*define\\(\\\'CAKE_CORE_INCLUDE_PATH\\\',[\\t\\x20\'A-z0-9]*\\);)/', $contents, $match)) {
				$result = str_replace($match[0], "\t\tdefine('CAKE_CORE_INCLUDE_PATH', '" . CAKE_CORE_INCLUDE_PATH . "');", $contents);
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
 * Enables Configure::read('Routing.admin') in /app/config/core.php
 *
 * @param string $name Name to use as admin routing
 * @return boolean Success
 * @access public
 */
	function cakeAdmin($name) {
		$File =& new File(CONFIGS . 'core.php');
		$contents = $File->read();
		if (preg_match('%([/\\t\\x20]*Configure::write\(\'Routing.admin\',[\\t\\x20\'a-z]*\\);)%', $contents, $match)) {
			$result = str_replace($match[0], "\t" . 'Configure::write(\'Routing.admin\', \''.$name.'\');', $contents);
			if ($File->write($result)) {
				Configure::write('Routing.admin', $name);
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
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
		$this->out("\n\tproject <name>\n\t\tbakes app directory structure.\n\t\tif <name> begins with '/' path is absolute.");
		$this->out("");
		$this->_stop();
	}

}
?>