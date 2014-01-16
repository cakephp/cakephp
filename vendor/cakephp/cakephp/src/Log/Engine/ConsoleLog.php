<?php
/**
 * Console Logging
 *
 * CakePHP(tm) :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Log\Engine;

use Cake\Console\ConsoleOutput;
use Cake\Error;
use Cake\Utility\Hash;

/**
 * Console logging. Writes logs to console output.
 */
class ConsoleLog extends BaseLog {

/**
 * Output stream
 *
 * @var Cake\Console\ConsoleOutput
 */
	protected $_output = null;

/**
 * Constructs a new Console Logger.
 *
 * Config
 *
 * - `levels` string or array, levels the engine is interested in
 * - `scopes` string or array, scopes the engine is interested in
 * - `stream` the path to save logs on.
 * - `outputAs` integer or ConsoleOutput::[RAW|PLAIN|COLOR]
 *
 * @param array $config Options for the FileLog, see above.
 * @throws Cake\Error\Exception
 */
	public function __construct($config = array()) {
		parent::__construct($config);
		if (DS === '\\' && !(bool)env('ANSICON')) {
			$outputAs = ConsoleOutput::PLAIN;
		} else {
			$outputAs = ConsoleOutput::COLOR;
		}
		$config = Hash::merge(array(
			'stream' => 'php://stderr',
			'levels' => null,
			'scopes' => array(),
			'outputAs' => $outputAs,
			), $this->_config);
		$config = $this->config($config);
		if ($config['stream'] instanceof ConsoleOutput) {
			$this->_output = $config['stream'];
		} elseif (is_string($config['stream'])) {
			$this->_output = new ConsoleOutput($config['stream']);
		} else {
			throw new Error\Exception('`stream` not a ConsoleOutput nor string');
		}
		$this->_output->outputAs($config['outputAs']);
	}

/**
 * Implements writing to console.
 *
 * @param string $level The severity level of log you are making.
 * @param string $message The message you want to log.
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See Cake\Log\Log::config() for more information on logging scopes.
 * @return boolean success of write.
 */
	public function write($level, $message, $scope = []) {
		$output = date('Y-m-d H:i:s') . ' ' . ucfirst($level) . ': ' . $message . "\n";
		return $this->_output->write(sprintf('<%s>%s</%s>', $level, $output, $level), false);
	}

}
