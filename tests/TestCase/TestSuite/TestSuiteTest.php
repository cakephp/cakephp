<?php
declare(strict_types=1);

/**
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Filesystem\Filesystem;
use Cake\TestSuite\TestCase;

/**
 * TestSuiteTest
 */
class TestSuiteTest extends TestCase
{
    /**
     * testAddTestDirectory
     */
    public function testAddTestDirectory(): void
    {
        $testFolder = CORE_TEST_CASES . DS . 'TestSuite';
        $count = count(glob($testFolder . DS . '*Test.php'));

        $suite = $this->getMockBuilder('Cake\TestSuite\TestSuite')
            ->onlyMethods(['addTestFile'])
            ->getMock();
        $suite
            ->expects($this->exactly($count))
            ->method('addTestFile');

        $suite->addTestDirectory($testFolder);
    }

    /**
     * testAddTestDirectoryRecursive
     */
    public function testAddTestDirectoryRecursive(): void
    {
        $testFolder = CORE_TEST_CASES . DS . 'Cache';
        $count = count(glob($testFolder . DS . '*Test.php'));
        $count += count(glob($testFolder . DS . 'Engine/*Test.php'));

        $suite = $this->getMockBuilder('Cake\TestSuite\TestSuite')
            ->onlyMethods(['addTestFile'])
            ->getMock();
        $suite
            ->expects($this->exactly($count))
            ->method('addTestFile');

        $suite->addTestDirectoryRecursive($testFolder);
    }

    /**
     * testAddTestDirectoryRecursiveWithHidden
     */
    public function testAddTestDirectoryRecursiveWithHidden(): void
    {
        $this->skipIf(!is_writable(TMP), 'Cant addTestDirectoryRecursiveWithHidden unless the tmp folder is writable.');

        $path = TMP . 'MyTestFolder';
        $fs = new Filesystem();
        $fs->mkdir($path);
        mkdir($path . DS . '.svn', 0777, true);
        touch($path . DS . '.svn/InHiddenFolderTest.php');
        touch($path . DS . 'NotHiddenTest.php');
        touch($path . DS . '.HiddenTest.php');

        $suite = $this->getMockBuilder('Cake\TestSuite\TestSuite')
            ->onlyMethods(['addTestFile'])
            ->getMock();
        $suite
            ->expects($this->exactly(1))
            ->method('addTestFile');

        $suite->addTestDirectoryRecursive($path);

        $fs->deleteDir($path);
    }

    /**
     * testAddTestDirectoryRecursiveWithNonPhp
     */
    public function testAddTestDirectoryRecursiveWithNonPhp(): void
    {
        $this->skipIf(!is_writable(TMP), 'Cant addTestDirectoryRecursiveWithNonPhp unless the tmp folder is writable.');

        $path = TMP . 'MyTestFolder';
        $fs = new Filesystem();
        $fs->mkdir($path);
        touch($path . DS . 'BackupTest.php~');
        touch($path . DS . 'SomeNotesTest.txt');
        touch($path . DS . 'NotHiddenTest.php');

        $suite = $this->getMockBuilder('Cake\TestSuite\TestSuite')
            ->onlyMethods(['addTestFile'])
            ->getMock();
        $suite
            ->expects($this->exactly(1))
            ->method('addTestFile');

        $suite->addTestDirectoryRecursive($path);

        $fs->deleteDir($path);
    }
}
