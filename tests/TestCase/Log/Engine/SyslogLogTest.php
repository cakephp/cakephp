<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         2.4.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Log\Engine;

use Cake\TestSuite\TestCase;

/**
 * SyslogLogTest class
 */
class SyslogLogTest extends TestCase {

/**
 * Tests that the connection to the logger is open with the right arguments
 *
 * @return void
 */
	public function testOpenLog() {
		$log = $this->getMock('Cake\Log\Engine\SyslogLog', array('_open', '_write'));
		$log->expects($this->once())->method('_open')->with('', LOG_ODELAY, LOG_USER);
		$log->log('debug', 'message');

		$log = $this->getMock('Cake\Log\Engine\SyslogLog', array('_open', '_write'));
		$log->config(array(
			'prefix' => 'thing',
			'flag' => LOG_NDELAY,
			'facility' => LOG_MAIL,
			'format' => '%s: %s'
		));
		$log->expects($this->once())->method('_open')
			->with('thing', LOG_NDELAY, LOG_MAIL);
		$log->log('debug', 'message');
	}

/**
 * Tests that single lines are written to syslog
 *
 * @dataProvider typesProvider
 * @return void
 */
	public function testWriteOneLine($type, $expected) {
		$log = $this->getMock('Cake\Log\Engine\SyslogLog', array('_open', '_write'));
		$log->expects($this->once())->method('_write')->with($expected, $type . ': Foo');
		$log->log($type, 'Foo');
	}

/**
 * Tests that multiple lines are split and logged separately
 *
 * @return void
 */
	public function testWriteMultiLine() {
		$log = $this->getMock('Cake\Log\Engine\SyslogLog', array('_open', '_write'));
		$log->expects($this->at(1))->method('_write')->with(LOG_DEBUG, 'debug: Foo');
		$log->expects($this->at(2))->method('_write')->with(LOG_DEBUG, 'debug: Bar');
		$log->expects($this->exactly(2))->method('_write');
		$log->log('debug', "Foo\nBar");
	}

/**
 * Data provider for the write function test
 *
 * @return array
 */
	public function typesProvider() {
		return array(
			array('emergency', LOG_EMERG),
			array('alert', LOG_ALERT),
			array('critical', LOG_CRIT),
			array('error', LOG_ERR),
			array('warning', LOG_WARNING),
			array('notice', LOG_NOTICE),
			array('info', LOG_INFO),
			array('debug', LOG_DEBUG)
		);
	}

}
