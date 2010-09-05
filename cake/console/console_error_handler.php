<?php
/**
 * ErrorHandler for Console Shells
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
 * @subpackage    cake.cake.console
 * @since         CakePHP(tm) v 1.2.0.5074
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Core', 'ErrorHandler');

/**
 * Error Handler for Cake console. Does simple printing of the 
 * exception that occurred and the stack trace of the error.
 *
 * @package       cake
 * @subpackage    cake.cake.console
 */
class ConsoleErrorHandler extends ErrorHandler {

/**
 * Standard error stream.
 *
 * @var filehandle
 * @access public
 */
	public $stderr;

/**
 * Class constructor.
 *
 * @param Exception $error Exception to handle.
 * @param array $messages Error messages
 */
	function __construct($error) {
		$this->stderr = fopen('php://stderr', 'w');
		parent::__construct($error);
	}

/**
 * Handle a exception in the console environment.
 *
 * @return void
 */
	public static function handleException($exception) {
		$error = new ConsoleErrorHandler($exception);
		$error->render();
	}

/**
 * Do nothing, no controllers are needed in the console.
 *
 * @return void
 */
	protected function _getController($exception) {
		return null;
	}

/**
 * Overwrite how _cakeError behaves for console.  There is no reason
 * to prepare urls as they are not relevant for this.
 *
 * @param $error Exception Exception being handled.
 * @return void
 */
	protected function _cakeError($error) {
		$this->_outputMessage();
	}

/**
 * Override error404 method
 *
 * @param Exception $error Exception
 * @return void
 */
	public function error400($error) {
		$this->_outputMessage();
	}

/**
 * Override error500 method
 *
 * @param Exception $error Exception
 * @return void
 */
	public function error500($error) {
		$this->_outputMessage();
	}

/**
 * Outputs the exception to STDERR.
 *
 * @param string $template The name of the template to render.
 * @return void
 */
	public function _outputMessage($template = null) {
		$this->stderr($this->error->getMessage());
	}

/**
 * Outputs to the stderr filehandle.
 *
 * @param string $string Error text to output.
 */
	public function stderr($string) {
		fwrite($this->stderr, "Error: ". $string . "\n");
	}
}
