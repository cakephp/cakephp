<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs.cache
 * @since			CakePHP(tm) v 1.2.0.5434
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('cache', 'cache' . DS . 'model');

class CacheTestModel extends CakeTestModel {

	var $name = 'CacheTestModel';
}
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs.cache
 */
class ModelEngineTest extends CakeTestCase {

	var $fixtures = array('core.cache_test_model');

	function skip() {
		$this->skipif (false, 'ModelEngineTest not implemented');
	}
	function setUp() {
		Cache::config('model');
	}
	function start() {
		parent::start();
		Cache::config('model', array('engine' => 'Model', //[required]
	 								'duration'=> 3600, //[optional]
	 									'probability'=> 100, //[optional]
	  									'className' => 'CacheTestModel', //[optional]
	  									'fields' => array('data', 'expires'), //[optional]
	  									'serialize' => true, //[optional]
	 							)
	 				);

	}

	function testSettings() {
		$settings = Cache::settings();
		$expecting = array('duration'=> 3600, //[optional]
							'probability'=> 100, //[optional]
							'className' => 'CacheTestModel', //[optional]
							'fields' => array('data', 'expires'), //[optional]
							'serialize' => true, //[optional]
						'engine' => 'Model'
						);
		$this->assertEqual($settings, $expecting);
	}

	function testReadAndWriteCache() {
		$result = Cache::read('test');
		$expecting = '';
		$this->assertEqual($result, $expecting);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('test', $data, 1);
		$this->assertTrue($result);

		$result = Cache::read('test');
		$expecting = $data;
		$this->assertEqual($result, $expecting);
	}

	function testExpiry() {
		sleep(2);
		$result = Cache::read('test');
		$this->assertFalse($result);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('other_test', $data, 1);
		$this->assertTrue($result);

		sleep(2);
		$result = Cache::read('other_test');
		$this->assertFalse($result);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('other_test', $data, "+1 second");
		$this->assertTrue($result);

		sleep(2);
		$result = Cache::read('other_test');
		$this->assertFalse($result);
	}

	function testDeleteCache() {
		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('delete_test', $data);
		$this->assertTrue($result);

		$result = Cache::delete('delete_test');
		$this->assertTrue($result);
	}

	function testDeleteAllCache() {
		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('delete_test_1', $data);
		$this->assertTrue($result);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('delete_test_2', $data);
		$this->assertTrue($result);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('delete_test_3', $data);
		$this->assertTrue($result);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('delete_test_4', $data);
		$this->assertTrue($result);

		$result = Cache::clear();
		$this->assertTrue($result);
	}

	function tearDown() {
		Cache::config('default');
	}
}
?>