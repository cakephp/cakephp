<?php
/* SVN FILE: $Id$ */
/**
 * The Project Task handles creating the base application
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.scripts.bake
 * @since			CakePHP(tm) v 1.2
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
if (!class_exists('File')) {
	uses('file');
}
/**
 * Task class for creating new project apps and plugins
 *
 * @package		cake
 * @subpackage	cake.cake.console.libs.tasks
 */
class ProjectTask extends Shell {

/**
 * Override
 *
 * @return void
 */
	function initialize() {}

/**
 * Override
 *
 * @return void
 */
	function startup() {}

/**
 * Checks that given project path does not already exist, and
 * finds the app directory in it. Then it calls __buildDirLayout() with that information.
 *
 * @return bool
 */
	function execute($project = null) {
		if ($project === null) {
			if (isset($this->args[0])) {
				$project = $this->args[0];
				$this->Dispatch->shiftArgs();
			}
		}

		if($project) {
			if($project{0} == '/' || $project{0} == DS) {
				$this->Dispatch->parseParams(array('-working', $project));
			} else {
				$this->Dispatch->parseParams(array('-app', $project));
			}
		}

		$project = $this->params['working'];

		if (empty($this->params['skel'])) {
			$this->params['skel'] = '';
			if (is_dir(CAKE_CORE_INCLUDE_PATH.DS.'cake'.DS.'console'.DS.'libs'.DS.'templates'.DS.'skel') === true) {
				$this->params['skel'] = CAKE_CORE_INCLUDE_PATH.DS.'cake'.DS.'console'.DS.'libs'.DS.'templates'.DS.'skel';
			}
		}

		if ($project) {
			$response = false;
			while ($response == false && is_dir($project) === true && config('core') === true) {
				$response = $this->in('A project already exists in this location: '.$project.' Overwrite?', array('y','n'), 'n');
				if (low($response) === 'n') {
					$this->out('Bake Aborted');
					exit();
				}
			}
		}

		while (!$project) {
			$project = $this->in("What is the full path for this app including the app directory name?\nExample: ".$this->params['root'] . DS . "myapp", null, $this->params['root'] . DS . 'myapp');
			$this->execute($project);
			exit();
		}

		if (!is_dir($this->params['root'])) {
			$this->err('The directory path you supplied was not found. Please try again.');
		}

		$this->__buildDirLayout($project);
		exit();
	}

/**
 * Looks for a skeleton template of a Cake application,
 * and if not found asks the user for a path. When there is a path
 * this method will make a deep copy of the skeleton to the project directory.
 * A default home page will be added, and the tmp file storage will be chmod'ed to 0777.
 *
 * @param string $path
 */
	function __buildDirLayout($path) {
		$skel = $this->params['skel'];
		while ($skel == '') {
			$skel = $this->in("What is the path to the app directory you wish to copy?\nExample: ".APP, null, ROOT.DS.'myapp'.DS);
			if ($skel == '') {
				$this->out('The directory path you supplied was empty. Please try again.');
			} else {
				while (is_dir($skel) === false) {
					$skel = $this->in('Directory path does not exist please choose another:');
				}
			}
		}

		$app = basename($path);
		$this->out('Bake Project');
		$this->out("Skel Directory: $skel");
		$this->out("Will be copied to: {$path}");
		$this->hr();
		$looksGood = $this->in('Look okay?', array('y', 'n', 'q'), 'y');

		if (low($looksGood) == 'y' || low($looksGood) == 'yes') {
			$verboseOuptut = $this->in('Do you want verbose output?', array('y', 'n'), 'n');
			$verbose = false;

			if (low($verboseOuptut) == 'y' || low($verboseOuptut) == 'yes') {
				$verbose = true;
			}

			$Folder = new Folder($skel);
			if ($Folder->copy($path)) {
				$path = $Folder->slashTerm($path);
				$this->hr();
				$this->out(sprintf(__("Created: %s in %s", true), $app, $path));
				$this->hr();

				if ($this->createHome($path, $app)) {
					$this->out('Welcome page created');
				} else {
					$this->out('The Welcome page was NOT created');
				}

				if ($this->securitySalt($path) === true ) {
					$this->out('Random hash key created for \'Security.salt\'');
				} else {
					$this->err('Unable to generate random hash for \'Security.salt\', please change this yourself in ' . CONFIGS . 'core.php');
				}

				$corePath = $this->corePath($path);
				if ($corePath === true ) {
					$this->out('CAKE_CORE_INCLUDE_PATH set to ' . CAKE_CORE_INCLUDE_PATH);
				} elseif ($corePath === false) {
					$this->err('Unable to to set CAKE_CORE_INCLUDE_PATH, please change this yourself in ' . $path . 'webroot' .DS .'index.php');
				}

				if (!$Folder->chmod($path . 'tmp', 0777)) {
					$this->err('Could not set permissions on '. $path . DS .'tmp');
					$this->out('You must manually check that these directories can be wrote to by the server');
				}
			} else {
				$this->err(" '".$app."' could not be created properly");
			}

			if ($verbose) {
				foreach ($Folder->messages() as $message) {
					$this->out($message);
				}
			}

			return;
		} elseif (low($looksGood) == 'q' || low($looksGood) == 'quit') {
			$this->out('Bake Aborted.');
		} else {
			$this->execute(false);
		}
	}
/**
 * Writes a file with a default home page to the project.
 *
 * @param string $dir
 * @param string $app
 */
	function createHome($dir, $app) {
		$path = $dir . 'views' . DS . 'pages' . DS;
		include(CAKE_CORE_INCLUDE_PATH.DS.'cake'.DS.'console'.DS.'libs'.DS.'templates'.DS.'views'.DS.'home.ctp');
		return $this->createFile($path.'home.ctp', $output);
	}
/**
 * generates and writes 'Security.salt'
 *
 * @return bool
 */
	function securitySalt($path) {
		$File =& new File($path . 'config' . DS . 'core.php');
		$contents = $File->read();
		if (preg_match('/([\\t\\x20]*Configure::write\\(\\\'Security.salt\\\',[\\t\\x20\'A-z0-9]*\\);)/', $contents, $match)) {
			uses('Security');
			$string = Security::generateAuthKey();
			$result = str_replace($match[0], "\t" . 'Configure::write(\'Security.salt\', \''.$string.'\');', $contents);
			if ($File->write($result)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
/**
 * generates and writes CAKE_CORE_INCLUDE_PATH
 *
 * @return bool
 */
	function corePath($path) {
		if (dirname($path) !== CAKE_CORE_INCLUDE_PATH) {
			$File =& new File($path . 'webroot' . DS . 'index.php');
			$contents = $File->read();
			if (preg_match('/([\\t\\x20]*define\\(\\\'CAKE_CORE_INCLUDE_PATH\\\',[\\t\\x20\'A-z0-9]*\\);)/', $contents, $match)) {
				$result = str_replace($match[0], "\t\tdefine('CAKE_CORE_INCLUDE_PATH', '".CAKE_CORE_INCLUDE_PATH."');", $contents);
				if ($File->write($result)) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
	}
/**
 * Enables Configure::read('Routing.admin') in /app/config/core.php
 *
 * @return bool
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
}
?>