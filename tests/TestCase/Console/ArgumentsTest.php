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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

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
        $args = new Arguments($values, [], []);
        $this->assertSame($values, $args->getArguments());
    }

    /**
     * Get arguments by index
     *
     * @return void
     */
    public function testGetArgumentAt()
    {
        $values = ['big', 'brown', 'bear'];
        $args = new Arguments($values, [], []);
        $this->assertSame($values[0], $args->getArgumentAt(0));
        $this->assertSame($values[1], $args->getArgumentAt(1));
        $this->assertNull($args->getArgumentAt(3));
    }

    /**
     * check arguments by index
     *
     * @return void
     */
    public function testHasArgumentAt()
    {
        $values = ['big', 'brown', 'bear'];
        $args = new Arguments($values, [], []);
        $this->assertTrue($args->hasArgumentAt(0));
        $this->assertTrue($args->hasArgumentAt(1));
        $this->assertFalse($args->hasArgumentAt(3));
        $this->assertFalse($args->hasArgumentAt(-1));
    }

    /**
     * check arguments by name
     *
     * @return void
     */
    public function testHasArgument()
    {
        $values = ['big', 'brown', 'bear'];
        $names = ['size', 'color', 'species', 'odd'];
        $args = new Arguments($values, [], $names);
        $this->assertTrue($args->hasArgument('size'));
        $this->assertTrue($args->hasArgument('color'));
        $this->assertFalse($args->hasArgument('hair'));
        $this->assertFalse($args->hasArgument('Hair'), 'casing matters');
        $this->assertFalse($args->hasArgument('odd'));
    }

    /**
     * get arguments by name
     *
     * @return void
     */
    public function testGetArgument()
    {
        $values = ['big', 'brown', 'bear'];
        $names = ['size', 'color', 'species', 'odd'];
        $args = new Arguments($values, [], $names);
        $this->assertSame($values[0], $args->getArgument('size'));
        $this->assertSame($values[1], $args->getArgument('color'));
        $this->assertNull($args->getArgument('Color'));
        $this->assertNull($args->getArgument('hair'));
    }

    /**
     * get arguments missing value
     *
     * @return void
     */
    public function testGetArgumentMissing()
    {
        $values = [];
        $names = ['size', 'color'];
        $args = new Arguments($values, [], $names);
        $this->assertNull($args->getArgument('size'));
        $this->assertNull($args->getArgument('color'));
    }

    /**
     * test getOptions()
     *
     * @return void
     */
    public function testGetOptions()
    {
        $options = [
            'verbose' => true,
            'off' => false,
            'empty' => '',
        ];
        $args = new Arguments([], $options, []);
        $this->assertSame($options, $args->getOptions());
    }

    /**
     * test hasOption()
     *
     * @return void
     */
    public function testHasOption()
    {
        $options = [
            'verbose' => true,
            'off' => false,
            'zero' => 0,
            'empty' => '',
        ];
        $args = new Arguments([], $options, []);
        $this->assertTrue($args->hasOption('verbose'));
        $this->assertTrue($args->hasOption('off'));
        $this->assertTrue($args->hasOption('empty'));
        $this->assertTrue($args->hasOption('zero'));
        $this->assertFalse($args->hasOption('undef'));
    }

    /**
     * test getOption()
     *
     * @return void
     */
    public function testGetOption()
    {
        $options = [
            'verbose' => true,
            'off' => false,
            'zero' => 0,
            'empty' => '',
        ];
        $args = new Arguments([], $options, []);
        $this->assertTrue($args->getOption('verbose'));
        $this->assertFalse($args->getOption('off'));
        $this->assertSame('', $args->getOption('empty'));
        $this->assertSame(0, $args->getOption('zero'));
        $this->assertNull($args->getOption('undef'));
    }
}
