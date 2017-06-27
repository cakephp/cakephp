<?php
/**
 * Console Logging
 *
 * CakePHP(tm) :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       Cake.Log.Engine
 * @since         CakePHP(tm) v 2.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('BaseLog', 'Log/Engine');
App::uses('ConsoleOutput', 'Console');

/**
 * Console logging. Writes logs to console output.
 *
 * @package       Cake.Log.Engine
 */
class ConsoleLog extends BaseLog {

/**
 * Output stream
 *
 * @var ConsoleOutput
 */
	protected $_output = null;

/**
 * Constructs a new Console Logger.
 *
 * Config
 *
 * - `types` string or array, levels the engine is interested in
 * - `scopes` string or array, scopes the engine is interested in
 * - `stream` the path to save logs on.
 * - `outputAs` integer or ConsoleOutput::[RAW|PLAIN|COLOR]
 *
 * @param array $config Options for the FileLog, see above.
 * @throws CakeLogException
 */
	public function __construct($config = array()) {
		parent::__construct($config);
		if ((DS === '\\' && !(bool)env('ANSICON') && env('ConEmuANSI') !== 'ON') ||
			(function_exists('posix_isatty') && !posix_isatty($this->_output))
		) {
			$outputAs = ConsoleOutput::PLAIN;
		} else {
			$outputAs = ConsoleOutput::COLOR;
		}
		$config = Hash::merge(array(
			'stream' => 'php://stderr',
			'types' => null,
			'scopes' => array(),
			'outputAs' => $outputAs,
			), $this->_config);
		$config = $this->config($config);
		if ($config['stream'] instanceof ConsoleOutput) {
			$this->_output = $config['stream'];
		} elseif (is_string($config['stream'])) {
			$this->_output = new ConsoleOutput($config['stream']);
		} else {
			throw new CakeLogException('`stream` not a ConsoleOutput nor string');
		}
		$this->_output->outputAs($config['outputAs']);
	}

/**
 * Implements writing to console.
 *
 * @param string $type The type of log you are making.
 * @param string $message The message you want to log.
 * @return bool success of write.
 */
	public function write($type, $message) {
		$output = date('Y-m-d H:i:s') . ' ' . ucfirst($type) . ': ' . $message . "\n";
		return $this->_output->write(sprintf('<%s>%s</%s>', $type, $output, $type), false);
	}

}
