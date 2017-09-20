<?php
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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestSuite\Console;

use Cake\Console\Arguments;
use Cake\TestSuite\TestCase;

/**
 * Arguments test case.
 */
class ArgumentsTest extends TestCase
{
    /**
     * Get all arguments
     *
     * @return void
     */
    public function testGetArguments()
    {
        $values = ['big', 'brown', 'bear'];
        $args = new Arguments($values, []);
        $this->assertSame($values, $args->getArguments());
    }

    /**
     * Get arguments by index
     *
     * @return void
     */
    public function testGetArgument()
    {
        $values = ['big', 'brown', 'bear'];
        $args = new Arguments($values, []);
        $this->assertSame($values[0], $args->getArgument(0));
        $this->assertSame($values[1], $args->getArgument(1));
        $this->assertNull($args->getArgument(3));
    }

    /**
     * check arguments by index
     *
     * @return void
     */
    public function testHasArgument()
    {
        $values = ['big', 'brown', 'bear'];
        $args = new Arguments($values, []);
        $this->assertTrue($args->hasArgument(0));
        $this->assertTrue($args->hasArgument(1));
        $this->assertFalse($args->hasArgument(3));
    }
}
