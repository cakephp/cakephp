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

	function startTest() {
		Cache::config();
	}

	function testCacheDirChange() {
		$result = Cache::config('sessions', array('engine'=> 'File', 'path' => TMP . 'sessions'));
		$this->assertEqual($result['settings'], Cache::settings('File'));
		$this->assertNotEqual($result, Cache::settings('File'));

		$result = Cache::config('tests', array('engine'=> 'File', 'path' => TMP . 'tests'));
		$this->assertEqual($result['settings'], Cache::settings('File'));
		$this->assertNotEqual($result, Cache::settings('File'));

	}

	function testCacheName() {
		Cache::config();
		$cache =& Cache::getInstance();
		$engine = $cache->_Engine['File'];

		$result = $engine->fullpath('models' . DS . 'default_posts');
		$expecting = CACHE . 'models' . DS .'cake_default_posts';
		$this->assertEqual($result, $expecting);

		$engine = $cache->_Engine['File'];
		$result = $engine->fullpath('default_posts');
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
		$this->assertFalse(file_exists(TMP . 'tests' . DS . 'delete_test'));
	}

	function testSerialize() {
		Cache::engine('File', array('serialize' => true));
		$data = 'this is a test of the emergency broadcasting system';
		$write = Cache::write('seriailze_test', $data, 1);
		$this->assertTrue($write);

		Cache::engine('File', array('serialize' => false));
		$read = Cache::read('seriailze_test');

		$newread = Cache::read('seriailze_test');

		$delete = Cache::delete('seriailze_test');

		$this->assertIdentical($read, serialize($data));

		$this->assertIdentical($newread, $data);

	}
	
	function tearDown() {
		Cache::config('default');
	}

}
?>