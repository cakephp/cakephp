<?php
/**
 * CakePHP(tm) :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @since         2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Log\Engine;

use Cake\Console\ConsoleOutput;
use Cake\Core\Exception\Exception;

/**
 * Console logging. Writes logs to console output.
 */
class ConsoleLog extends BaseLog {

/**
 * Default config for this class
 *
 * @var array
 */
	protected $_defaultConfig = [
		'stream' => 'php://stderr',
		'levels' => null,
		'scopes' => [],
		'outputAs' => 'see constructor'
	];

/**
 * Output stream
 *
 * @var \Cake\Console\ConsoleOutput
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
 * @throws \Cake\Core\Exception\Exception
 */
	public function __construct(array $config = array()) {
		if (DS === '\\' && !(bool)env('ANSICON')) {
			$this->_defaultConfig['outputAs'] = ConsoleOutput::PLAIN;
		} else {
			$this->_defaultConfig['outputAs'] = ConsoleOutput::COLOR;
		}

		parent::__construct($config);

		$config = $this->_config;
		if ($config['stream'] instanceof ConsoleOutput) {
			$this->_output = $config['stream'];
		} elseif (is_string($config['stream'])) {
			$this->_output = new ConsoleOutput($config['stream']);
		} else {
			throw new Exception('`stream` not a ConsoleOutput nor string');
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
 * @return bool success of write.
 */
	public function write($level, $message, $scope = []) {
		$output = date('Y-m-d H:i:s') . ' ' . ucfirst($level) . ': ' . $message . "\n";
		return $this->_output->write(sprintf('<%s>%s</%s>', $level, $output, $level), false);
	}

}
