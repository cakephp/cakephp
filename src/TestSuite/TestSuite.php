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

use Cake\Filesystem\Folder;

/**
 * A class to contain test cases and run them with shared fixtures
 *
 */
class TestSuite extends \PHPUnit_Framework_TestSuite
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
