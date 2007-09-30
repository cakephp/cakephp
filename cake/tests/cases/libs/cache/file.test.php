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
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs.cache
 * @since			CakePHP(tm) v 1.2.0.5434
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('cache', 'cache' . DS . 'file');
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs.cache
 */
class FileEngineTest extends UnitTestCase {

	function setUp() {
		Cache::engine();
	}

	function testSettings() {
		Cache::engine('File', array('path' => TMP . 'tests'));
		$settings = Cache::settings();
		$expecting = array('duration'=> 3600,
						'probability' => 100,
						'path'=> TMP . 'tests',
						'prefix'=> 'cake_',
						'lock' => false,
						'serialize'=> true,
						'name' => 'File'
						);
		$this->assertEqual($settings, $expecting);
	}

	function testCacheName() {
		$cache =& Cache::getInstance();
		$result = $cache->_Engine->fullpath('models' . DS . 'default_posts');
		$expecting = CACHE . 'models' . DS .'cake_default_posts';
		$this->assertEqual($result, $expecting);

		$result = $cache->_Engine->fullpath('default_posts');
		$expecting = CACHE . 'cake_default_posts';
		$this->assertEqual($result, $expecting);

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

	function testSerialize() {
		Cache::engine('File', array('serialize' => true));
		$data = 'this is a test of the emergency broadcasting system';
		$write = Cache::write('seriailze_test', $data, 1);

		Cache::engine('File', array('serialize' => false));
		$read = Cache::read('seriailze_test');

		$result = Cache::delete('seriailze_test');

		$this->assertNotIdentical($write, $read);
	}

}
?>