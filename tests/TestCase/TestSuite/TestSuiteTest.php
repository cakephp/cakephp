<?php
/**
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Filesystem\Folder;
use Cake\TestSuite\TestCase;

/**
 * TestSuiteTest
 *
 */
class TestSuiteTest extends TestCase
{

    /**
     * testAddTestDirectory
     *
     * @return void
     */
    public function testAddTestDirectory()
    {
        $testFolder = CORE_TEST_CASES . DS . 'TestSuite';
        $count = count(glob($testFolder . DS . '*Test.php'));

        $suite = $this->getMockBuilder('Cake\TestSuite\TestSuite')
            ->setMethods(['addTestFile'])
            ->getMock();
        $suite
            ->expects($this->exactly($count))
            ->method('addTestFile');

        $suite->addTestDirectory($testFolder);
    }

    /**
     * testAddTestDirectoryRecursive
     *
     * @return void
     */
    public function testAddTestDirectoryRecursive()
    {
        $testFolder = CORE_TEST_CASES . DS . 'Cache';
        $count = count(glob($testFolder . DS . '*Test.php'));
        $count += count(glob($testFolder . DS . 'Engine/*Test.php'));

        $suite = $this->getMockBuilder('Cake\TestSuite\TestSuite')
            ->setMethods(['addTestFile'])
            ->getMock();
        $suite
            ->expects($this->exactly($count))
            ->method('addTestFile');

        $suite->addTestDirectoryRecursive($testFolder);
    }

    /**
     * testAddTestDirectoryRecursiveWithHidden
     *
     * @return void
     */
    public function testAddTestDirectoryRecursiveWithHidden()
    {
        $this->skipIf(!is_writable(TMP), 'Cant addTestDirectoryRecursiveWithHidden unless the tmp folder is writable.');

        $Folder = new Folder(TMP . 'MyTestFolder', true, 0777);
        mkdir($Folder->path . DS . '.svn', 0777, true);
        touch($Folder->path . DS . '.svn/InHiddenFolderTest.php');
        touch($Folder->path . DS . 'NotHiddenTest.php');
        touch($Folder->path . DS . '.HiddenTest.php');

        $suite = $this->getMockBuilder('Cake\TestSuite\TestSuite')
            ->setMethods(['addTestFile'])
            ->getMock();
        $suite
            ->expects($this->exactly(1))
            ->method('addTestFile');

        $suite->addTestDirectoryRecursive($Folder->pwd());

        $Folder->delete();
    }

    /**
     * testAddTestDirectoryRecursiveWithNonPhp
     *
     * @return void
     */
    public function testAddTestDirectoryRecursiveWithNonPhp()
    {
        $this->skipIf(!is_writable(TMP), 'Cant addTestDirectoryRecursiveWithNonPhp unless the tmp folder is writable.');

        $Folder = new Folder(TMP . 'MyTestFolder', true, 0777);
        touch($Folder->path . DS . 'BackupTest.php~');
        touch($Folder->path . DS . 'SomeNotesTest.txt');
        touch($Folder->path . DS . 'NotHiddenTest.php');

        $suite = $this->getMockBuilder('Cake\TestSuite\TestSuite')
            ->setMethods(['addTestFile'])
            ->getMock();
        $suite
            ->expects($this->exactly(1))
            ->method('addTestFile');

        $suite->addTestDirectoryRecursive($Folder->pwd());

        $Folder->delete();
    }
}
