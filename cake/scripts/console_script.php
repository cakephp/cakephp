<?php

/**
 * Base class for command-line utilities for automating programmer chores.
 *
 * @package		cake
 * @subpackage	cake.cake.scripts
 */
class ConsoleScript extends Object {

/**
 * ConsoleDispatcher object
 *
 * @var object An instance of the ConsoleDispatcher object that loaded this script
 */
	var $dispatch = null;
/**
 * If true, the script will ask for permission to perform actions.
 *
 * @var boolean
 */
	var $interactive = true;
/**
 * Holds the DATABASE_CONFIG object for the app. Null if database.php could not be found,
 * or the app does not exist.
 *
 * @var object
 */
	var $dbConfig = null;
/**
 * Contains command switches parsed from the command line.
 *
 * @var array
 */
	var $params = array();
/**
 * Contains arguments parsed from the command line.
 *
 * @var array
 */
	var $args = array();
/**
 * Initializes this CakeConsoleScript instance.
 *
 */
	function __construct(&$dispatch) {
		$this->dispatch =& $dispatch;
		$this->params = $this->dispatch->params;
		$this->args = $this->dispatch->args;
		if(file_exists(CONFIGS.'database.php')) {
			require_once (CONFIGS . 'database.php');
			$this->dbConfig = new DATABASE_CONFIG();
		}
	}
	
/**
 * Main-loop method.
 *
 */
	function main() {

		$this->out('');
		$this->out('');
		$this->out('Baking...');
		$this->hr();
		$this->out('Name: '. APP_DIR);
		$this->out('Path: '. ROOT.DS.APP_DIR);
		$this->hr();

		if(empty($this->dbConfig)) {
			$this->out('');
			$this->out('Your database configuration was not found. Take a moment to create one:');
		}
		require_once (CONFIGS . 'database.php');
		

		$this->stdout('[M]odel');
		$this->stdout('[C]ontroller');
		$this->stdout('[V]iew');
		$invalidSelection = true;

		while ($invalidSelection) {
			$classToBake = strtoupper($this->in('What would you like to Bake?', array('M', 'V', 'C')));
			switch($classToBake) {
				case 'M':
					$invalidSelection = false;
					$this->doModel();
					break;
				case 'V':
					$invalidSelection = false;
					$this->doView();
					break;
				case 'C':
					$invalidSelection = false;
					$this->doController();
					break;
				default:
					$this->stdout('You have made an invalid selection. Please choose a type of class to Bake by entering M, V, or C.');
			}
		}
	}
/**
 * Prompts the user for input, and returns it.
 *
 * @param string $prompt Prompt text.
 * @param mixed $options Array or string of options.
 * @param string $default Default input value.
 * @return Either the default value, or the user-provided input.
 */
	function in($prompt, $options = null, $default = null) {
		return $this->dispatch->getInput($prompt, $options, $default);
	}
/**
 * Outputs to the stdout filehandle.
 *
 * @param string $string String to output.
 * @param boolean $newline If true, the outputs gets an added newline.
 */
	function out($string, $newline = true) {
		return $this->dispatch->stdout($string, $newline);
	}
/**
 * Outputs to the stderr filehandle.
 *
 * @param string $string Error text to output.
 */
	function err($string) {
		return $this->dispatch->stderr($string);
	}
/**
 * Outputs a series of minus characters to the standard output, acts as a visual separator.
 *
 */
	function hr() {
		$this->out('---------------------------------------------------------------');
	}
/**
 * Creates a file at given path.
 *
 * @param string $path		Where to put the file.
 * @param string $contents Content to put in the file.
 * @return Success
 */
	function createFile ($path, $contents) {
		$path = str_replace(DS . DS, DS, $path);
		echo "\nCreating file $path\n";
		if (is_file($path) && $this->interactive === true) {
			fwrite($this->stdout, __("File exists, overwrite?", true). " {$path} (y/n/q):");
			$key = trim(fgets($this->stdin));

			if ($key == 'q') {
				fwrite($this->stdout, __("Quitting.", true) ."\n");
				exit;
			} elseif ($key == 'a') {
				$this->dont_ask = true;
			} elseif ($key == 'y') {
			} else {
				fwrite($this->stdout, __("Skip", true) ." {$path}\n");
				return false;
			}
		}

		if ($f = fopen($path, 'w')) {
			fwrite($f, $contents);
			fclose($f);
			fwrite($this->stdout, __("Wrote", true) ."{$path}\n");
			return true;
		} else {
			fwrite($this->stderr, __("Error! Could not write to", true)." {$path}.\n");
			return false;
		}
	}


/**
 * Outputs usage text on the standard output.
 *
 */
	function help() {
		$this->stdout('CakePHP Console:');
		$this->hr();
		$this->stdout('The Bake script generates controllers, views and models for your application.');
		$this->stdout('If run with no command line arguments, Bake guides the user through the class');
		$this->stdout('creation process. You can customize the generation process by telling Bake');
		$this->stdout('where different parts of your application are using command line arguments.');
		$this->stdout('');
		$this->hr('');
		$this->stdout('usage: php bake.php [command] [path...]');
		$this->stdout('');
		$this->stdout('commands:');
		$this->stdout('   -app [path...] Absolute path to Cake\'s app Folder.');
		$this->stdout('   -core [path...] Absolute path to Cake\'s cake Folder.');
		$this->stdout('   -help Shows this help message.');
		$this->stdout('   -project [path...]  Generates a new app folder in the path supplied.');
		$this->stdout('   -root [path...] Absolute path to Cake\'s \app\webroot Folder.');
		$this->stdout('');
	}
/**
 * Returns true if given path is a directory.
 *
 * @param string $path
 * @return True if given path is a directory.
 */
	function isDir($path) {
		if(is_dir($path)) {
			return true;
		} else {
			return false;
		}
	}
/**
 * Recursive directory copy.
 *
 * @param string $fromDir
 * @param string $toDir
 * @param octal $chmod
 * @param boolean	 $verbose
 * @return Success.
 */
	function copyDir($fromDir, $toDir, $chmod = 0755, $verbose = false) {
		$errors = array();
		$messages = array();

		if (!is_dir($toDir)) {
			uses('folder');
			$folder = new Folder();
			$folder->mkdirr($toDir, 0755);
		}

		if (!is_writable($toDir)) {
			$errors[] = 'target '.$toDir.' is not writable';
		}

		if (!is_dir($fromDir)) {
			$errors[] = 'source '.$fromDir.' is not a directory';
		}

		if (!empty($errors)) {
			if ($verbose) {
				foreach($errors as $err) {
					$this->stdout('Error: '.$err);
				}
			}
			return false;
		}
		$exceptions = array('.','..','.svn');
		$handle = opendir($fromDir);

		while (false !== ($item = readdir($handle))) {
			if (!in_array($item,$exceptions)) {
				$from = str_replace('//','/',$fromDir.'/'.$item);
				$to = str_replace('//','/',$toDir.'/'.$item);
				if (is_file($from)) {
					if (@copy($from, $to)) {
						chmod($to, $chmod);
						touch($to, filemtime($from));
						$messages[] = 'File copied from '.$from.' to '.$to;
					} else {
						$errors[] = 'cannot copy file from '.$from.' to '.$to;
					}
				}

				if (is_dir($from)) {
					if (@mkdir($to)) {
						chmod($to, $chmod);
						$messages[] = 'Directory created: '.$to;
					} else {
						$errors[] = 'cannot create directory '.$to;
					}
					$this->copyDir($from,$to,$chmod,$verbose);
				}
			}
		}
		closedir($handle);

		if ($verbose) {
			foreach($errors as $err) {
				$this->stdout('Error: '.$err);
			}
			foreach($messages as $msg) {
				$this->stdout($msg);
			}
		}
		return true;
	}

	function __addAdminRoute($name){
		$file = file_get_contents(CONFIGS.'core.php');
		if (preg_match('%([/\\t\\x20]*define\\(\'CAKE_ADMIN\',[\\t\\x20\'a-z]*\\);)%', $file, $match)) {
			$result = str_replace($match[0], 'define(\'CAKE_ADMIN\', \''.$name.'\');', $file);

			if(file_put_contents(CONFIGS.'core.php', $result)){
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