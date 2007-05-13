<?php
/* SVN FILE: $Id$ */
/**
 * ErrorHandler for Console Shells
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
 * @subpackage		cake.cake.console
 * @since			CakePHP(tm) v 1.2.0.5074
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package		cake
 * @subpackage	cake.cake.console
 */
class ErrorHandler extends Object {
/**
 * Standard output stream.
 *
 * @var filehandle
 */
	var $stdout;
/**
 * Standard error stream.
 *
 * @var filehandle
 */
	var $stderr;
/**
 * Class constructor.
 *
 * @param string $method
 * @param array $messages
 * @return unknown
 */
	function __construct($method, $messages) {
		$this->stdout = fopen('php://stdout', 'w');
		$this->stderr = fopen('php://stderr', 'w');
		if (Configure::read() > 0 || $method == 'error') {
			call_user_func_array(array(&$this, $method), $messages);
		} else {
			call_user_func_array(array(&$this, 'error404'), $messages);
		}
	}
/**
 * Displays an error page (e.g. 404 Not found).
 *
 * @param array $params
 */
	function error($params) {
		extract($params, EXTR_OVERWRITE);
		$this->stderr($code . $name . $message."\n");
		exit();
	}
/**
 * Convenience method to display a 404 page.
 *
 * @param array $params
 */
	function error404($params) {
		extract($params, EXTR_OVERWRITE);
		$this->error(array('code' => '404',
							'name' => 'Not found',
							'message' => sprintf(__("The requested address %s was not found on this server.", true), $url, $message)));
		exit();
	}
/**
 * Renders the Missing Controller web page.
 *
 * @param array $params
 */
	function missingController($params) {
		extract($params, EXTR_OVERWRITE);
		$controllerName = str_replace('Controller', '', $className);
		$this->stderr("Missing Controller '".$controllerName."'\n");

		exit();
	}
/**
 * Renders the Missing Action web page.
 *
 * @param array $params
 */
	function missingAction($params) {
		extract($params, EXTR_OVERWRITE);
		$this->stderr("Missing Method '".$action."' in '".$className."'\n");
		exit();
	}
/**
 * Renders the Private Action web page.
 *
 * @param array $params
 */
	function privateAction($params) {
		extract($params, EXTR_OVERWRITE);
		$this->stderr("Trying to access private method '".$action."' in '".$className."'\n");
		exit();
	}
/**
 * Renders the Missing Table web page.
 *
 * @param array $params
 */
	function missingTable($params) {
		extract($params, EXTR_OVERWRITE);
		$this->stderr("Missing database table '". $table ."' for model '" . $className."'\n");
		exit();
	}
/**
 * Renders the Missing Database web page.
 *
 * @param array $params
 */
	function missingDatabase($params = array()) {
		extract($params, EXTR_OVERWRITE);
		$this->stderr("Missing database\n");
		exit();
	}
/**
 * Renders the Missing View web page.
 *
 * @param array $params
 */
	function missingView($params) {
		extract($params, EXTR_OVERWRITE);
		$this->stderr("Missing View '".$file."' for '".$action."' in '".$className."'\n");
		exit();
	}
/**
 * Renders the Missing Layout web page.
 *
 * @param array $params
 */
	function missingLayout($params) {
		extract($params, EXTR_OVERWRITE);
		$this->stderr("Missing Layout '".$file."'\n");
		exit();
	}
/**
 * Renders the Database Connection web page.
 *
 * @param array $params
 */
	function missingConnection($params) {
		extract($params, EXTR_OVERWRITE);
		$this->stderr("Missing Database Connection. Try 'cake bake'");
		exit();
	}
/**
 * Renders the Missing Helper file web page.
 *
 * @param array $params
 */
	function missingHelperFile($params) {
		extract($params, EXTR_OVERWRITE);
		$this->stderr("Missing Helper file '".$file."' for '".Inflector::camelize($helper)."'\n");
		exit();
	}
/**
 * Renders the Missing Helper class web page.
 *
 * @param array $params
 */
	function missingHelperClass($params) {
		extract($params, EXTR_OVERWRITE);
		$this->stderr("Missing Helper class ".Inflector::camelize($helper)."' in '".$file."'\n");
		exit();
	}
/**
 * Renders the Missing Component file web page.
 *
 * @param array $params
 */
	function missingComponentFile($params) {
		extract($params, EXTR_OVERWRITE);
		$this->stderr("Missing Component file '".$file."' for '".Inflector::camelize($component)."' in '".$className."'\n");
		exit();
	}
/**
 * Renders the Missing Component class web page.
 *
 * @param array $params
 */
	function missingComponentClass($params) {
		extract($params, EXTR_OVERWRITE);
		$this->stderr("Missing Component class ".Inflector::camelize($component)."' in '".$file."'\n");
		exit();
	}
/**
 * Renders the Missing Model class web page.
 *
 * @param unknown_type $params
 */
	function missingModel($params) {
		$this->stderr("Missing model '" . $params['className']."'\n");
		exit();
	}
/**
 * Outputs to the stdout filehandle.
 *
 * @param string $string String to output.
 * @param boolean $newline If true, the outputs gets an added newline.
 */
	function stdout($string, $newline = true) {
		if ($newline) {
			fwrite($this->stdout, $string . "\n");
		} else {
			fwrite($this->stdout, $string);
		}
	}
/**
 * Outputs to the stderr filehandle.
 *
 * @param string $string Error text to output.
 */
	function stderr($string) {
		fwrite($this->stderr, "Error: ". $string . "\n");
	}
}
?>