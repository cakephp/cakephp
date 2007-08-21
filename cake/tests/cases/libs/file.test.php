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
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('file');
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs
 */
class FileTest extends UnitTestCase {

	var $File = null;

	function testBasic() {

		$file = dirname(__FILE__) . DS . basename(__FILE__);

		$this->File =& new File($file);

		$result = $this->File->pwd();
		$expecting = $file;
		$this->assertEqual($result, $expecting);

		$result = $this->File->name;
		$expecting = basename(__FILE__);
		$this->assertEqual($result, $expecting);

		$result = $this->File->info();
		$expecting = array('dirname'=> dirname(__FILE__), 'basename'=> basename(__FILE__), 'extension'=> 'php', 'filename'=>'file.test');
		$this->assertEqual($result, $expecting);

		$result = $this->File->ext();
		$expecting = 'php';
		$this->assertEqual($result, $expecting);

		$result = $this->File->name();
		$expecting = 'file.test';
		$this->assertEqual($result, $expecting);

		$result = $this->File->md5();
		$expecting = md5_file($file);
		$this->assertEqual($result, $expecting);

		$result = $this->File->size();
		$expecting = filesize($file);
		$this->assertEqual($result, $expecting);

		$result = $this->File->owner();
		$expecting = fileowner($file);
		$this->assertEqual($result, $expecting);

		$result = $this->File->group();
		$expecting = filegroup($file);
		$this->assertEqual($result, $expecting);

		$result = $this->File->perms();
		$expecting = '0644';
		$this->assertEqual($result, $expecting);

		$result = $this->File->Folder();
		$this->assertIsA($result, 'Folder');

	}
/*
	function testOperations() {

		$new = TMP . 'test_file_new.php';
		$this->File =& new File($new, true);

		$data = 'hello';
		$result = $this->File->write($data);
		$this->assertTrue($result);

		$result = $this->File->append($data);
		$this->assertTrue($result);

		$result = $this->File->read();
		$expecting = 'hellohello';
		$this->assertEqual($result, $expecting);

		$result = $this->File->write('');
		$this->assertTrue($result);

		$result = $this->File->delete($new);
		$this->assertTrue($result);
	}
*/
}
?>