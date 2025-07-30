<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
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
use Cake\Console\Exception\ConsoleException;
use Cake\TestSuite\TestCase;

/**
 * Arguments test case.
 */
class ArgumentsTest extends TestCase
{
    /**
     * Get all arguments
     */
    public function testGetArguments(): void
    {
        $values = ['big', 'brown', 'bear'];
        $args = new Arguments($values, [], []);
        $this->assertSame($values, $args->getArguments());
    }

    /**
     * Get arguments by index.
     */
    public function testGetArgumentAt(): void
    {
        $values = ['big', 'brown', 'bear'];
        $args = new Arguments($values, [], []);
        $this->assertSame($values[0], $args->getArgumentAt(0));
        $this->assertSame($values[1], $args->getArgumentAt(1));
        $this->assertNull($args->getArgumentAt(3));
    }

    /**
     * Test get arguments by index is not a string.
     */
    public function testGetArgumentAtNotString(): void
    {
        $values = [['one', 'two']];
        $args = new Arguments($values, [], []);
        $this->expectException(ConsoleException::class);
        $this->expectExceptionMessage('Argument at index `0` is not of type `string`, use `getArrayArgument()` instead.');
        $args->getArgumentAt(0);
    }

    /**
     * Get array arguments by index.
     */
    public function testGetArrayArgumentAt(): void
    {
        $values = [['one', 'two'], []];
        $args = new Arguments($values, [], []);
        $this->assertSame($values[0], $args->getArrayArgumentAt(0));
        $this->assertSame($values[1], $args->getArrayArgumentAt(1));
        $this->assertNull($args->getArrayArgumentAt(3));
    }

    /**
     * Test get array arguments by index is not an array.
     */
    public function testGetArrayArgumentAtNotArray(): void
    {
        $values = ['one two'];
        $args = new Arguments($values, [], []);
        $this->expectException(ConsoleException::class);
        $this->expectExceptionMessage('Argument at index `0` is not of type `array`, use `getArgument()` instead.');
        $args->getArrayArgumentAt(0);
    }

    /**
     * check arguments by index
     */
    public function testHasArgumentAt(): void
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
     */
    public function testHasArgument(): void
    {
        $values = ['big', 'brown', 'bear'];
        $names = ['size', 'color', 'species', 'odd'];
        $args = new Arguments($values, [], $names);
        $this->assertTrue($args->hasArgument('size'));
        $this->assertTrue($args->hasArgument('color'));
        $this->assertFalse($args->hasArgument('odd'));
        $this->assertFalse($args->hasArgument('undefined'));
    }

    /**
     * get arguments by name
     */
    public function testGetArgument(): void
    {
        $values = ['big', 'brown', 'bear'];
        $names = ['size', 'color', 'species', 'odd'];
        $args = new Arguments($values, [], $names);
        $this->assertSame($values[0], $args->getArgument('size'));
        $this->assertSame($values[1], $args->getArgument('color'));
        $this->assertNull($args->getArgument('odd'));
    }

    /**
     * get arguments missing value
     */
    public function testGetArgumentMissing(): void
    {
        $values = [];
        $names = ['size', 'color'];
        $args = new Arguments($values, [], $names);
        $this->assertNull($args->getArgument('size'));
        $this->assertNull($args->getArgument('color'));
    }

    /**
     * get arguments by name
     */
    public function testGetArgumentInvalid(): void
    {
        $values = [];
        $names = ['size'];
        $args = new Arguments($values, [], $names);

        $this->expectException(ConsoleException::class);
        $this->expectExceptionMessage('Argument `color` is not defined on this Command. Could this be an option maybe?');

        $args->getArgument('color');
    }

    /**
     * Test getArgument() could only return string.
     */
    public function testGetArgumentNotString(): void
    {
        $values = [['one', 'two']];
        $names = ['types'];
        $args = new Arguments($values, [], $names);
        $this->expectException(ConsoleException::class);
        $this->expectExceptionMessage('Argument `types` is not of type `string`, use `getArrayArgument()` instead.');
        $args->getArgument('types');
    }

    /**
     * test getOptions()
     */
    public function testGetOptions(): void
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
     */
    public function testHasOption(): void
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
     */
    public function testGetOption(): void
    {
        $options = [
            'verbose' => true,
            'off' => false,
            'zero' => '0',
            'empty' => '',
        ];
        $args = new Arguments([], $options, []);
        $this->assertTrue($args->getOption('verbose'));
        $this->assertFalse($args->getOption('off'));
        $this->assertSame('', $args->getOption('empty'));
        $this->assertSame('0', $args->getOption('zero'));
        $this->assertNull($args->getOption('undef'));
    }

    /**
     * test getOption() checks types
     */
    public function testGetOptionInvalidType(): void
    {
        $options = [
            'list' => [1, 2],
        ];
        $args = new Arguments([], $options, []);
        $this->expectException(ConsoleException::class);
        $args->getOption('list');
    }

    public function testGetBooleanOption(): void
    {
        $options = [
            'verbose' => true,
        ];
        $args = new Arguments([], $options, []);
        $this->assertTrue($args->getBooleanOption('verbose'));
        $this->assertNull($args->getBooleanOption('missing'));
    }

    /**
     * test getOption() checks types
     */
    public function testGetOptionBooleanInvalidType(): void
    {
        $options = [
            'list' => [1, 2],
        ];
        $args = new Arguments([], $options, []);
        $this->expectException(ConsoleException::class);
        $args->getBooleanOption('list');
    }

    public function testGetMultipleOption(): void
    {
        $this->deprecated(function (): void {
            $options = [
                'types' => ['one', 'two', 'three'],
            ];
            $args = new Arguments([], $options, []);
            $this->assertSame(['one', 'two', 'three'], $args->getMultipleOption('types'));
            $this->assertNull($args->getMultipleOption('missing'));
        });
    }

    /**
     * Test getArrayOption(). Consistent method (alias of getMultipleOption())
     */
    public function testGetArrayOption(): void
    {
        $options = [
            'types' => ['one', 'two', 'three'],
        ];
        $args = new Arguments([], $options, []);
        $this->assertSame(['one', 'two', 'three'], $args->getArrayOption('types'));
        $this->assertNull($args->getArrayOption('missing'));
    }

    public function testGetArrayOptionInvalidType(): void
    {
        $options = [
            'connection' => 'test',
        ];
        $args = new Arguments([], $options, []);
        $this->expectException(ConsoleException::class);
        $args->getArrayOption('connection');
    }

    public function testGetArrayArgumentInvalid(): void
    {
        $values = ['XS'];
        $names = ['size'];
        $args = new Arguments($values, [], $names);
        $this->expectException(ConsoleException::class);
        $this->expectExceptionMessage('Argument `colors` is not defined on this Command. Could this be an option maybe?');
        $args->getArrayArgument('colors');
    }

    public function testGetArrayArgument(): void
    {
        $values = [
            ['one', 'two', 'three'],
        ];
        $names = [
            'types',
            'odd',
        ];
        $args = new Arguments($values, [], $names);
        $this->assertSame(['one', 'two', 'three'], $args->getArrayArgument('types'));
        $this->assertNull($args->getArrayArgument('odd'));
    }

    public function testGetArrayArgumentInvalidType(): void
    {
        $values = [
            'one type',
        ];
        $names = [
            'types',
        ];
        $args = new Arguments($values, [], $names);
        $this->expectException(ConsoleException::class);
        $this->expectExceptionMessage('Argument `types` is not of type `array`, use `getArgument()` instead.');
        $args->getArrayArgument('types');
    }
}
