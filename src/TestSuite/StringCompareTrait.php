<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use function Cake\Core\env;

/**
 * Compare a string to the contents of a file
 *
 * Implementing objects are expected to modify the `$_compareBasePath` property
 * before use.
 */
trait StringCompareTrait
{
    /**
     * The base path for output comparisons
     *
     * Must be initialized before use
     *
     * @var string
     */
    protected string $_compareBasePath = '';

    /**
     * Update comparisons to match test changes
     *
     * Initialized with the env variable UPDATE_TEST_COMPARISON_FILES
     *
     * @var bool
     */
    protected bool $_updateComparisons;

    /**
     * Compare the result to the contents of the file
     *
     * @param string $path partial path to test comparison file
     * @param string $result test result as a string
     * @return void
     */
    public function assertSameAsFile(string $path, string $result): void
    {
        if (!file_exists($path)) {
            $path = $this->_compareBasePath . $path;
        }

        $this->_updateComparisons ??= (bool)env('UPDATE_TEST_COMPARISON_FILES') ?: false;

        if ($this->_updateComparisons) {
            file_put_contents($path, $result);
        }

        $expected = file_get_contents($path);
        $this->assertTextEquals($expected, $result, 'Content does not match file ' . $path);
    }
}
