<?php
/**
 * LogEngineCollectionTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Log
 * @since         CakePHP(tm) v 2.4
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('LogEngineCollection', 'Log');
App::uses('FileLog', 'Log/Engine');

/**
 * LoggerEngineLog class
 */
class LoggerEngineLog extends FileLog {
}

/**
 * LogEngineCollectionTest class
 *
 * @package       Cake.Test.Case.Log
 */
class LogEngineCollectionTest extends CakeTestCase {

	public $Collection;

/**
 * Start test callback
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$this->Collection = new LogEngineCollection();
	}

/**
 * test load
 *
 * @return void
 */
	public function testLoad() {
		$result = $this->Collection->load('key', array('engine' => 'File'));
		$this->assertInstanceOf('CakeLogInterface', $result);
	}

/**
 * test load with deprecated Log suffix
 *
 * @return void
 */
	public function testLoadWithSuffix() {
		$result = $this->Collection->load('key', array('engine' => 'FileLog'));
		$this->assertInstanceOf('CakeLogInterface', $result);
	}

/**
 * test that engines starting with Log also work properly
 *
 * @return void
 */
	public function testLoadWithSuffixAtBeginning() {
		$result = $this->Collection->load('key', array('engine' => 'LoggerEngine'));
		$this->assertInstanceOf('CakeLogInterface', $result);
	}

/**
 * test load with invalid Log
 *
 * @return void
 * @expectedException CakeLogException
 */
	public function testLoadInvalid() {
		$result = $this->Collection->load('key', array('engine' => 'ImaginaryFile'));
		$this->assertInstanceOf('CakeLogInterface', $result);
	}

}
