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

use Cake\TestSuite\TestCase;
use org\bovigo\vfs\vfsStream;

/**
 * TestSuiteTest
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
        $ds = [
            '.svn' => [
                'InHiddenFolderTest.php' => '',
            ],
            'NotHiddenTest.php' => '',
            '.HiddenTest.php' => '',
        ];
        $vfs = vfsStream::setup('root', 444, $ds);

        $suite = $this->getMockBuilder('Cake\TestSuite\TestSuite')
            ->setMethods(['addTestFile'])
            ->getMock();
        $suite
            ->expects($this->exactly(1))
            ->method('addTestFile');

        $suite->addTestDirectoryRecursive($vfs->url());
    }

    /**
     * testAddTestDirectoryRecursiveWithNonPhp
     *
     * @return void
     */
    public function testAddTestDirectoryRecursiveWithNonPhp()
    {
        $ds = [
            'BackupTest.php~' => '',
            'SomeNotesTest.txt' => '',
            'NotHiddenTest.php' => '',
        ];
        $vfs = vfsStream::setup('root', 444, $ds);

        $suite = $this->getMockBuilder('Cake\TestSuite\TestSuite')
            ->setMethods(['addTestFile'])
            ->getMock();
        $suite
            ->expects($this->exactly(1))
            ->method('addTestFile');

        $suite->addTestDirectoryRecursive($vfs->url());
    }
}
