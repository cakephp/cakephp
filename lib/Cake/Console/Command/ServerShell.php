<?php
/**
 * built-in Server Shell
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppShell', 'Console/Command');

/**
 * built-in Server Shell
 *
 * @package       Cake.Console.Command
 */
class ServerShell extends AppShell {

/**
 * Default ServerHost
 */
	const DEFAULT_HOST = 'localhost';

/**
 * Default ListenPort
 */
	const DEFAULT_PORT = 80;

/**
 * server host
 *
 * @var string
 */
	protected $_host = null;

/**
 * listen port
 *
 * @var string
 */
	protected $_port = null;

/**
 * document root
 *
 * @var string
 */
	protected $_documentRoot = null;

/**
 * Override initialize of the Shell
 *
 * @return void
 */
	public function initialize() {
		$this->_host = self::DEFAULT_HOST;
		$this->_port = self::DEFAULT_PORT;
		$this->_documentRoot = WWW_ROOT;
	}

/**
 * Starts up the Shell and displays the welcome message.
 * Allows for checking and configuring prior to command or main execution
 *
 * Override this method if you want to remove the welcome information,
 * or otherwise modify the pre-command flow.
 *
 * @return void
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::startup
 */
	public function startup() {
		if (!empty($this->params['host'])) {
			$this->_host = $this->params['host'];
		}
		if (!empty($this->params['port'])) {
			$this->_port = $this->params['port'];
		}
		if (!empty($this->params['document_root'])) {
			$this->_documentRoot = $this->params['document_root'];
		}

		// for windows
		if (substr($this->_documentRoot, -1, 1) == DIRECTORY_SEPARATOR) {
			$this->_documentRoot = substr($this->_documentRoot, 0, strlen($this->_documentRoot) - 1);
		}
		if (preg_match("/^([a-z]:)[\\\]+(.+)$/i", $this->_documentRoot, $m)) {
			$this->_documentRoot = $m[1] . '\\' . $m[2];
		}

		parent::startup();
	}

/**
 * Displays a header for the shell
 *
 * @return void
 */
	protected function _welcome() {
		$this->out();
		$this->out(__d('cake_console', '<info>Welcome to CakePHP %s Console</info>', 'v' . Configure::version()));
		$this->hr();
		$this->out(__d('cake_console', 'App : %s', APP_DIR));
		$this->out(__d('cake_console', 'Path: %s', APP));
		$this->out(__d('cake_console', 'DocumentRoot: %s', $this->_documentRoot));
		$this->hr();
	}

/**
 * Override main() to handle action
 *
 * @return void
 */
	public function main() {
		if (version_compare(PHP_VERSION, '5.4.0') < 0) {
			$this->out(__d('cake_console', '<warning>This command is available on PHP5.4 or above</warning>'));
			return;
		}

		$command = sprintf("php -S %s:%d -t %s %s",
			$this->_host,
			$this->_port,
			$this->_documentRoot,
			WWW_ROOT . '/index.php'
		);

		$port = ($this->_port == self::DEFAULT_PORT) ? '' : ':' . $this->_port;
		$this->out(__d('cake_console', 'built-in server is running in http://%s%s/', $this->_host, $port));
		system($command);
	}

/**
 * Get and configure the optionparser.
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->addOption('host', array(
			'short' => 'H',
			'help' => __d('cake_console', 'ServerHost')
		));
		$parser->addOption('port', array(
			'short' => 'p',
			'help' => __d('cake_console', 'ListenPort')
		));
		$parser->addOption('document_root', array(
			'short' => 'd',
			'help' => __d('cake_console', 'DocumentRoot')
		));

		$parser->description(array(
			__d('cake_console', 'PHP Built-in Server for CakePHP'),
			__d('cake_console', '<warning>[WARN] Don\'t use this at the production environment</warning>'),
		));

		return $parser;
	}
}
