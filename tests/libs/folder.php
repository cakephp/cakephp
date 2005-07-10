<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, CakePHP Authors/Developers                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
 * 
 * 
 * @filesource 
 * @author CakePHP Authors/Developers
 * @copyright Copyright (c) 2005, CakePHP Authors/Developers
 * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.tests.libs
 * @since CakePHP v 0.2.9
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

/**
 * 
 */
uses('folder');
/**
 * Enter description here...
 *
 * @package cake
 * @subpackage cake.tests.libs
 * @since CakePHP v .9
 *
 */
class FolderTest extends UnitTestCase
{
/**
 * Enter description here...
 *
 * @var unknown_type
 */
   var $folder;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
   var $testDir;

/**
 * Enter description here...
 *
 * @return FolderTest
 */
   function FolderTest()
   {
      $this->UnitTestCase('Folder test');
   }

/**
 * Enter description here...
 *
 */
   function setUp()
   {
      $this->testDir = ROOT.'tmp'.DS.'tests';

      touch($this->testDir.DS.'.htaccess');
      chmod($this->testDir.DS.'.htaccess', 0777);
      if (!is_dir($this->testDir.DS.'dir1'))
      {
         mkdir($this->testDir.DS.'dir1', 0777);
      }
      touch($this->testDir.DS.'dir1'.DS.'test1.php');
      chmod($this->testDir.DS.'dir1'.DS.'test1.php', 0777);

      if (!is_dir($this->testDir.DS.'dir2'))
      {
         mkdir($this->testDir.DS.'dir2', 0777);
      }
      touch($this->testDir.DS.'dir2'.DS.'test2.php');
      chmod($this->testDir.DS.'dir2'.DS.'test2.php', 0777);


      $this->folder = new Folder($this->testDir);
   }

/**
 * Enter description here...
 *
 */
   function tearDown()
   {
      unlink($this->testDir.DS.'.htaccess');
      unlink($this->testDir.DS.'dir1'.DS.'test1.php');
      unlink($this->testDir.DS.'dir2'.DS.'test2.php');

      rmdir($this->testDir.DS.'dir1');
      rmdir($this->testDir.DS.'dir2');

      unset($this->folder);
   }


/**
 * Enter description here...
 *
 */
   function testLs()
   {
      $result = $this->folder->ls();
      $expected = array (
      array('.svn', 'dir1', 'dir2'),
      array('.htaccess')
      );

      $this->assertEqual($result, $expected, "List directories and files from test dir");
   }

/**
 * Enter description here...
 *
 */
   function testPwd()
   {
      $result = $this->folder->pwd();

      $this->assertEqual($result, $this->testDir, 'Print current path (test dir)');
   }

/**
 * Enter description here...
 *
 */
   function testCd()
   {
      $this->folder->cd($this->testDir);
      $result = $this->folder->pwd();
      $this->assertEqual($result, $this->testDir, 'Change directory to the actual one');

      //THIS ONE IS HACKED... why do i need to give a full path to this method?
      $this->folder->cd($this->testDir.DS.'dir1');
      $result = $this->folder->pwd();

      $this->assertEqual($result, Folder::addPathElement($this->testDir, 'dir1'), 'Change directory to dir1');
   }

/**
 * Enter description here...
 *
 */
   function testFindRecursive()
   {
      $result = $this->folder->findRecursive('.*\.php');
      $expected = array(Folder::addPathElement($this->folder->pwd().DS.'dir1', 'test1.php'), Folder::addPathElement($this->folder->pwd().DS.'dir2', 'test2.php'));

      $this->assertEqual($result, $expected, 'Find .php files');
   }

/**
 * Enter description here...
 *
 */
   function testIsWindowsPath()
   {
      $result = Folder::isWindowsPath('C:\foo');
      $expected = true;
      $this->assertEqual($result, $expected);

      $result = Folder::isWindowsPath('/foo/bar');
      $expected = false;
      $this->assertEqual($result, $expected);
   }

/**
 * Enter description here...
 *
 */
   function testIsAbsolute()
   {
      $result = Folder::isAbsolute('foo/bar');
      $expected = false;
      $this->assertEqual($result, $expected);

      $result = Folder::isAbsolute('c:\foo\bar');
      $expected = true;
      $this->assertEqual($result, $expected);
   }

/**
 * Enter description here...
 *
 */
   function testAddPathElement()
   {
      $result = Folder::addPathElement('c:\foo', 'bar');
      $expected = 'c:\foo\bar';
      $this->assertEqual($result, $expected);

      $result = Folder::addPathElement('C:\foo\bar\\', 'baz');
      $expected = 'C:\foo\bar\baz';
      $this->assertEqual($result, $expected);

      $result = Folder::addPathElement('/foo/bar/', 'baz');
      $expected = '/foo/bar/baz';
      $this->assertEqual($result, $expected);
   }

/**
 * Enter description here...
 *
 */
   function testIsSlashTerm()
   {
      $result = Folder::isSlashTerm('/foo/bar/');
      $this->assertEqual($result, true);

      $result = Folder::isSlashTerm('/foo/bar');
      $this->assertEqual($result, false);
   }

/**
 * Enter description here...
 *
 */
   function testCorrectSlashFor()
   {
      $result = Folder::correctSlashFor('/foo/bar/');
      $this->assertEqual($result, '/');

      $result = Folder::correctSlashFor('C:\foo\bar');
      $this->assertEqual($result, '\\');
   }

/**
 * Enter description here...
 *
 */
   function testSlashTerm()
   {
      $result = Folder::slashTerm('/foo/bar/');
      $this->assertEqual($result, '/foo/bar/');

      $result = Folder::slashTerm('/foo/bar');
      $this->assertEqual($result, '/foo/bar/');

      $result = Folder::slashTerm('C:\foo\bar');
      $this->assertEqual($result, 'C:\foo\bar\\');
   }
}

?>