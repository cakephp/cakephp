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
uses('folder');
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs
 */
class FolderTest extends UnitTestCase {

	var $Folder = null;

	function testBasic() {
		$path = dirname(__FILE__);
		$this->Folder =& new Folder($path);

		$result = $this->Folder->pwd();
		$this->assertEqual($result, $path);

		$result = $this->Folder->isWindowsPath($path);
		$expected = (DS == '\\' ? true : false);
		$this->assertEqual($result, $expected);

		$result = $this->Folder->isAbsolute($path);
		$this->assertTrue($result);

		$result = $this->Folder->isSlashTerm($path);
		$this->assertFalse($result);

		$result = $this->Folder->isSlashTerm($path . DS);
		$this->assertTrue($result);

		$result = $this->Folder->addPathElement($path, 'test');
		$expected = $path . DS . 'test';
		$this->assertEqual($result, $expected);

		$result = $this->Folder->cd(ROOT);
		$expected = ROOT;
		$this->assertEqual($result, $expected);
	}

	function testInPath() {
		$path = dirname(dirname(__FILE__));
		$inside = dirname($path) . DS;

		$this->Folder =& new Folder($path);

		$result = $this->Folder->pwd();
		$this->assertEqual($result, $path);

		$result = $this->Folder->isSlashTerm($inside);
		$this->assertTrue($result);

		//$result = $this->Folder->inPath('tests/');
		//$this->assertTrue($result);

		$result = $this->Folder->inPath(DS . 'non-existing' . DS . $inside);
		$this->assertFalse($result);
	}

	function testOperations() {
		$path = CAKE_CORE_INCLUDE_PATH.DS.'cake'.DS.'console'.DS.'libs'.DS.'templates'.DS.'skel';
		$this->Folder =& new Folder($path);

		$result = is_dir($this->Folder->pwd());
		$this->assertTrue($result);

		$new = TMP . 'test_folder_new';
		$result = $this->Folder->create($new);
		$this->assertTrue($result);

		$copy = TMP . 'test_folder_copy';
		$result = $this->Folder->copy($copy);
		$this->assertTrue($result);

		$copy = TMP . 'test_folder_copy';
		$result = $this->Folder->chmod($copy, 0755);
		$this->assertTrue($result);

		$result = $this->Folder->cd($copy);
		$this->assertTrue($result);

		$mv = TMP . 'test_folder_mv';
		$result = $this->Folder->move($mv);
		$this->assertTrue($result);

		$result = $this->Folder->delete($new);
		$this->assertTrue($result);

		$result = $this->Folder->delete($mv);
		$this->assertTrue($result);

		//pr($this->Folder->messages());

		//pr($this->Folder->errors());
	}
}
?>