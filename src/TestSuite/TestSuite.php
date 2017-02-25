<?php
/**
 * A class to contain test cases and run them with shared fixtures
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

if (class_exists('PHPUnit_Runner_Version') && !class_exists('PHPUnit\Framework\TestSuite')) {
    if (version_compare(\PHPUnit_Runner_Version::id(), '5.7', '<')) {
        trigger_error(sprintf('Your PHPUnit Version must be at least 5.7.0 to use CakePHP Testsuite, found %s', \PHPUnit_Runner_Version::id()), E_USER_ERROR);
    }
    class_alias('PHPUnit_Framework_TestSuite', 'PHPUnit\Framework\TestSuite');
}

use Cake\Filesystem\Folder;
use PHPUnit\Framework\TestSuite as BaseTestSuite;

/**
 * A class to contain test cases and run them with shared fixtures
 */
class TestSuite extends BaseTestSuite
{

    /**
     * Adds all the files in a directory to the test suite. Does not recursive through directories.
     *
     * @param string $directory The directory to add tests from.
     * @return void
     */
    public function addTestDirectory($directory = '.')
    {
        $Folder = new Folder($directory);
        list(, $files) = $Folder->read(true, true, true);

        foreach ($files as $file) {
            if (substr($file, -4) === '.php') {
                $this->addTestFile($file);
            }
        }
    }

    /**
     * Recursively adds all the files in a directory to the test suite.
     *
     * @param string $directory The directory subtree to add tests from.
     * @return void
     */
    public function addTestDirectoryRecursive($directory = '.')
    {
        $Folder = new Folder($directory);
        $files = $Folder->tree(null, true, 'files');

        foreach ($files as $file) {
            if (substr($file, -4) === '.php') {
                $this->addTestFile($file);
            }
        }
    }
}
