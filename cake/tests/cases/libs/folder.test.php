<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP Test Suite <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright (c) 2006, Larry E. Masters Shorewood, IL. 60431
 * Author(s): Larry E. Masters aka PhpNut <phpnut@gmail.com>
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @author       Larry E. Masters aka PhpNut <phpnut@gmail.com>
 * @copyright    Copyright (c) 2006, Larry E. Masters Shorewood, IL. 60431
 * @link         http://www.phpnut.com/projects/
 * @package      test_suite
 * @subpackage   test_suite.cases.app
 * @since        CakePHP Test Suite v 1.0.0.0
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
	require_once LIBS.'folder.php';
/**
 * Short description for class.
 *
 * @package    test_suite
 * @subpackage test_suite.cases.libs
 * @since      CakePHP Test Suite v 1.0.0.0
 */
class FolderTest extends UnitTestCase {
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
		
		$result = $this->Folder->inPath($inside);
		$this->assertTrue($result);
		
		$result = $this->Folder->inPath(DS . 'non-existing' . DS . $inside);
		$this->assertFalse($result);
	}
}
?>