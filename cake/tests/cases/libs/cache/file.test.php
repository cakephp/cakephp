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
App::import(array('Cache', 'cache' . DS . 'file'));
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs.cache
 */
class FileEngineTest extends CakeTestCase {

	var $config = array();

	function start() {
		$this->config = Cache::config('default');
		$settings = Cache::config('default', array('engine'=> 'File', 'path' => CACHE));
	}

	function end() {
		Cache::config('default', $this->config['settings']);
	}

	function testCacheDirChange() {
		$result = Cache::config('sessions', array('engine'=> 'File', 'path' => TMP . 'sessions'));
		$this->assertEqual($result['settings'], Cache::settings('File'));
		$this->assertNotEqual($result, Cache::settings('File'));

		$result = Cache::config('tests', array('engine'=> 'File', 'path' => TMP . 'tests'));
		$this->assertEqual($result['settings'], Cache::settings('File'));
		$this->assertNotEqual($result, Cache::settings('File'));
	}

	function testReadAndWriteCache() {
		$result = Cache::write(null, 'here');
		$this->assertFalse($result);

		$result = Cache::read('test');
		$expecting = '';
		$this->assertEqual($result, $expecting);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('test', $data, 1);
		$this->assertTrue($result);
		$this->assertTrue(file_exists(CACHE . 'cake_test'));

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

		$result = Cache::delete('delete_test');
		$this->assertFalse($result);

	}

	function testSerialize() {
		Cache::engine('File', array('serialize' => true));
		$data = 'this is a test of the emergency broadcasting system';
		$write = Cache::write('serialize_test', $data, 1);
		$this->assertTrue($write);

		Cache::engine('File', array('serialize' => false));
		$read = Cache::read('serialize_test');

		$newread = Cache::read('serialize_test');

		$delete = Cache::delete('serialize_test');

		$this->assertIdentical($read, serialize($data));

		$this->assertIdentical(unserialize($newread), $data);

	}

	function testClear() {
		$data = 'this is a test of the emergency broadcasting system';
		$write = Cache::write('serialize_test1', $data, 1);
		$write = Cache::write('serialize_test2', $data, 1);
		$write = Cache::write('serialize_test3', $data, 1);
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test1'));
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test2'));
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test3'));
		Cache::engine('File', array('duration' => 1));
		sleep(4);
		$result = Cache::clear(true);
		$this->assertTrue($result);
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test1'));
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test2'));
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test3'));

		$data = 'this is a test of the emergency broadcasting system';
		$write = Cache::write('serialize_test1', $data, 1);
		$write = Cache::write('serialize_test2', $data, 1);
		$write = Cache::write('serialize_test3', $data, 1);
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test1'));
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test2'));
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test3'));

		$result = Cache::clear();
		$this->assertTrue($result);
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test1'));
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test2'));
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test3'));

		$result = Cache::engine('File', array('path' => CACHE . 'views'));

		$data = 'this is a test of the emergency broadcasting system';
		$write = Cache::write('controller_view_1', $data, 1);
		$write = Cache::write('controller_view_2', $data, 1);
		$write = Cache::write('controller_view_3', $data, 1);
		$write = Cache::write('controller_view_10', $data, 1);
		$write = Cache::write('controller_view_11', $data, 1);
		$write = Cache::write('controller_view_12', $data, 1);
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_1'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_2'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_3'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_10'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_11'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_12'));

		clearCache('controller_view_1', 'views', '');
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_1'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_2'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_3'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_10'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_11'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_12'));

		clearCache('controller_view', 'views', '');
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_1'));
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_2'));
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_3'));
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_10'));
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_11'));
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_12'));

		$write = Cache::write('controller_view_1', $data, 1);
		$write = Cache::write('controller_view_2', $data, 1);
		$write = Cache::write('controller_view_3', $data, 1);
		$write = Cache::write('controller_view_10', $data, 1);
		$write = Cache::write('controller_view_11', $data, 1);
		$write = Cache::write('controller_view_12', $data, 1);
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_1'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_2'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_3'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_10'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_11'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_12'));

		clearCache(array('controller_view_2', 'controller_view_11', 'controller_view_12'), 'views', '');
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_1'));
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_2'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_3'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_10'));
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_11'));
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_12'));

		clearCache('controller_view');

		Cache::engine('File', array('path' => CACHE));
	}

	function testKeyPath() {
		$result = Cache::write('views.countries.something', 'here');
		$this->assertTrue($result);
		$this->assertTrue(file_exists(CACHE . 'cake_views_countries_something'));

		$result = Cache::read('views.countries.something');
		$this->assertEqual($result, 'here');

		$result = Cache::clear();
		$this->assertTrue($result);
	}

	function testRemoveWindowsSlashesFromCache() {
		$File = new File(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'tmp' . DS . 'dir_map');
		$File->read(11);

		$data = $File->read(true);
		$data = str_replace('\\\\\\\\', '\\', $data);
		$data = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $data);
		$data = unserialize($data);

		$expected = array (
				'C:\dev\prj2\sites\cake\libs' => array(
					0 => 'C:\dev\prj2\sites\cake\libs', 1 => 'C:\dev\prj2\sites\cake\libs\view',
					2 => 'C:\dev\prj2\sites\cake\libs\view\scaffolds', 3 => 'C:\dev\prj2\sites\cake\libs\view\pages',
					4 => 'C:\dev\prj2\sites\cake\libs\view\layouts', 5 => 'C:\dev\prj2\sites\cake\libs\view\layouts\xml',
					6 => 'C:\dev\prj2\sites\cake\libs\view\layouts\rss', 7 => 'C:\dev\prj2\sites\cake\libs\view\layouts\js',
					8 => 'C:\dev\prj2\sites\cake\libs\view\layouts\email', 9 => 'C:\dev\prj2\sites\cake\libs\view\layouts\email\text',
					10 => 'C:\dev\prj2\sites\cake\libs\view\layouts\email\html', 11 => 'C:\dev\prj2\sites\cake\libs\view\helpers',
					12 => 'C:\dev\prj2\sites\cake\libs\view\errors', 13 => 'C:\dev\prj2\sites\cake\libs\view\elements',
					14 => 'C:\dev\prj2\sites\cake\libs\view\elements\email', 15 => 'C:\dev\prj2\sites\cake\libs\view\elements\email\text',
					16 => 'C:\dev\prj2\sites\cake\libs\view\elements\email\html', 17 => 'C:\dev\prj2\sites\cake\libs\model',
					18 => 'C:\dev\prj2\sites\cake\libs\model\datasources', 19 => 'C:\dev\prj2\sites\cake\libs\model\datasources\dbo',
					20 => 'C:\dev\prj2\sites\cake\libs\model\behaviors', 21 => 'C:\dev\prj2\sites\cake\libs\controller',
					22 => 'C:\dev\prj2\sites\cake\libs\controller\components', 23 => 'C:\dev\prj2\sites\cake\libs\cache'),
				'C:\dev\prj2\sites\main_site\vendors' => array(
					0 => 'C:\dev\prj2\sites\main_site\vendors', 1 => 'C:\dev\prj2\sites\main_site\vendors\shells',
					2 => 'C:\dev\prj2\sites\main_site\vendors\shells\templates', 3 => 'C:\dev\prj2\sites\main_site\vendors\shells\templates\cdc_project',
					4 => 'C:\dev\prj2\sites\main_site\vendors\shells\tasks', 5 => 'C:\dev\prj2\sites\main_site\vendors\js',
					6 => 'C:\dev\prj2\sites\main_site\vendors\css'),
				'C:\dev\prj2\sites\vendors' => array(
					0 => 'C:\dev\prj2\sites\vendors', 1 => 'C:\dev\prj2\sites\vendors\simpletest',
					2 => 'C:\dev\prj2\sites\vendors\simpletest\test', 3 => 'C:\dev\prj2\sites\vendors\simpletest\test\support',
					4 => 'C:\dev\prj2\sites\vendors\simpletest\test\support\collector', 5 => 'C:\dev\prj2\sites\vendors\simpletest\extensions',
					6 => 'C:\dev\prj2\sites\vendors\simpletest\extensions\testdox', 7 => 'C:\dev\prj2\sites\vendors\simpletest\docs',
					8 => 'C:\dev\prj2\sites\vendors\simpletest\docs\fr', 9 => 'C:\dev\prj2\sites\vendors\simpletest\docs\en'),
				'C:\dev\prj2\sites\main_site\views\helpers' => array(
					0 => 'C:\dev\prj2\sites\main_site\views\helpers'));

		$File->close();
		$this->assertEqual($expected, $data);
	}

}
?>