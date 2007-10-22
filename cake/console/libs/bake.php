<?php
/* SVN FILE: $Id$ */
/**
 * Command-line code generation utility to automate programmer chores.
 *
 * Bake is CakePHP's code generation script, which can help you kickstart
 * application development by writing fully functional skeleton controllers,
 * models, and views. Going further, Bake can also write Unit Tests for you.
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
 * @subpackage		cake.cake.console.libs
 * @since			CakePHP(tm) v 1.2.0.5012
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Bake is a command-line code generation utility for automating programmer chores.
 *
 * @package		cake
 * @subpackage	cake.cake.console.libs
 */
class BakeShell extends Shell {
/**
 * Contains tasks to load and instantiate
 *
 * @var array
 * @access public
 */
	var $tasks = array('Project', 'DbConfig', 'Model', 'Controller', 'View');
/**
 * Override main() to handle action
 *
 * @access public
 */
	function main() {

		if (!is_dir(CONFIGS)) {
			$this->Project->execute();
		}

		if (!config('database')) {
			$this->out("Your database configuration was not found. Take a moment to create one.\n");
			$this->args = null;
			return $this->DbConfig->execute();
		}
		$this->out('Interactive Bake Shell');
		$this->hr();
		$this->out('[D]atabase Configuration');
		$this->out('[M]odel');
		$this->out('[V]iew');
		$this->out('[C]ontroller');
		$this->out('[P]roject');
		$this->out('[Q]uit');

		$classToBake = strtoupper($this->in('What would you like to Bake?', array('D', 'M', 'V', 'C', 'P', 'Q')));
		switch($classToBake) {
			case 'D':
				$this->DbConfig->execute();
				break;
			case 'M':
				$this->Model->execute();
				break;
			case 'V':
				$this->View->execute();
				break;
			case 'C':
				$this->Controller->execute();
				break;
			case 'P':
				$this->Project->execute();
				break;
			case 'Q':
				exit(0);
				break;
			default:
				$this->out('You have made an invalid selection. Please choose a type of class to Bake by entering D, M, V, or C.');
		}
		$this->hr();
		$this->main();
	}
/**
 * Displays help contents
 *
 * @access public
 */
	function help() {
		$this->out('CakePHP Bake:');
		$this->hr();
		$this->out('The Bake script generates controllers, views and models for your application.');
		$this->out('If run with no command line arguments, Bake guides the user through the class');
		$this->out('creation process. You can customize the generation process by telling Bake');
		$this->out('where different parts of your application are using command line arguments.');
		$this->hr();
		$this->out("Usage: cake bake <command> <arg1> <arg2>...");
		$this->hr();
		$this->out('Params:');
		$this->out("\t-app <path> Absolute/Relative path to your app folder.\n");
		$this->out('Commands:');
		$this->out("\n\tbake help\n\t\tshows this help message.");
		$this->out("\n\tbake project <path>\n\t\tbakes a new app folder in the path supplied\n\t\tor in current directory if no path is specified");
		$this->out("\n\tbake db_config\n\t\tbakes a database.php file in config directory.");
		$this->out("\n\tbake model\n\t\tbakes a model. run 'bake model help' for more info");
		$this->out("\n\tbake view\n\t\tbakes views. run 'bake view help' for more info");
		$this->out("\n\tbake controller\n\t\tbakes a controller. run 'bake controller help' for more info");
		$this->out("");

	}
}
?>