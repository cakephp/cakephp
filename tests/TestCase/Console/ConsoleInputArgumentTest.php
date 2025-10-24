<?php
declare(strict_types=1);

/**
 * ConsoleInputArgumentTest file
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\ConsoleInputArgument;
use Cake\Console\Exception\ConsoleException;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use SimpleXMLElement;

/**
 * ConsoleInputArgumentTest
 */
class ConsoleInputArgumentTest extends TestCase
{
    /**
     * @var \Cake\Console\ConsoleInputArgument|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $input;

    public static function dataProperties(): array
    {
        return [
            // Test name()
            [['color'], 'name', 'color'],
            // Test defaultValue()
            [['qty', '', false, [], '1'], 'defaultValue', '1'],
            // Test isRequired() as bool
            [['verbose', '', true, []], 'isRequired', true],
            // Test separator()
            [['color', '', false, [], null, ';'], 'separator', ';'],
            // Test separator() more than one character
            [['color', '', false, [], null, '\"'], 'separator', '\"'],
        ];
    }

    /**
     * Test properties setters and getters.
     *
     * @param array $args
     * @param string $method
     * @param mixed $expected
     */
    #[DataProvider('dataProperties')]
    public function testProperties(array $args, string $method, mixed $expected): void
    {
        $input = new ConsoleInputArgument(...$args);
        $result = $input->$method();
        $this->assertSame($expected, $result);
    }

    /**
     * Test separator must not contain space.
     */
    public function testNoSpaceInSeparator(): void
    {
        $this->expectException(ConsoleException::class);
        $this->expectExceptionMessage('The argument separator must not contain spaces for `colors`.');
        new ConsoleInputArgument(
            'colors',
            '',
            false,
            ['red', 'blue'],
            'red',
            ' ',
        );
    }

    /**
     * Test help.
     */
    public function testHelp(): void
    {
        $input = new ConsoleInputArgument(
            'colors',
            'help message',
            true,
            ['red', 'blue'],
            'red',
            ';',
        );
        $output = $input->help(72);
        $this->assertStringStartsWith('colors ', $output);
        $this->assertStringContainsString(' help message ', $output);
        $this->assertStringContainsString(' <comment>(choices: red|blue)</comment>', $output);
        $this->assertStringContainsString(' <comment>default: "red"</comment>', $output);
        $this->assertStringContainsString(' <comment>(separator: ";")</comment>', $output);
    }

    /**
     * Test usage.
     */
    public function testUsage(): void
    {
        $input = new ConsoleInputArgument(
            'color',
            '',
            false,
            ['red', 'blue'],
            'red',
        );
        $output = $input->usage();
        $this->assertEquals('[<red|blue>]', $output);
    }

    /**
     * Test usage.
     */
    public function testUsageRequired(): void
    {
        $input = new ConsoleInputArgument(
            'color',
            '',
            true,
            ['red', 'blue'],
            'red',
        );
        $output = $input->usage();
        $this->assertEquals('<red|blue>', $output);
    }

    /**
     * Test valid choice empty.
     */
    public function testValidChoiceEmpty(): void
    {
        $input = new ConsoleInputArgument(
            'color',
            '',
            false,
            [],
        );
        $this->assertTrue($input->validChoice('yellow'));
    }

    /**
     * Test valid choice empty.
     */
    public function testValidChoiceFail(): void
    {
        $input = new ConsoleInputArgument(
            'color',
            '',
            false,
            ['red', 'blue'],
        );
        $this->expectException(ConsoleException::class);
        $this->expectExceptionMessage('`yellow` is not a valid value for `color`. Please use one of `red|blue`');
        $input->validChoice('yellow');
    }

    /**
     * Test valid choice.
     */
    public function testValidChoiceSuccess(): void
    {
        $input = new ConsoleInputArgument(
            'color',
            '',
            false,
            ['red', 'blue'],
        );
        $this->assertTrue($input->validChoice('red'));
    }

    /**
     * @return array
     */
    public static function dataValidChoiceSeparatorSuccess(): array
    {
        return [
            [['red', 'blue', 'green'], null, 'blue'],
            [['blue,red', 'green,yellow'], null, 'blue,red'],
            [['red', 'blue', 'green'], ';', 'blue;red'],
        ];
    }

    /**
     * Test valid choice with value contain multiple and separator.
     *
     * @param array $choices
     * @param string|null $separator
     * @param string $value
     */
    #[DataProvider('dataValidChoiceSeparatorSuccess')]
    public function testValidChoiceSeparatorSuccess(array $choices, ?string $separator, string $value): void
    {
        $input = new ConsoleInputArgument(
            'colors',
            '',
            false,
            $choices,
            null,
            $separator,
        );

        $success = $input->validChoice($value);
        $this->assertTrue($success);
    }

    public static function dataValidChoiceSeparatorFail(): array
    {
        return [
            [['red', 'blue', 'green'], null, 'blue,yellow'],
            [['red', 'blue', 'green'], ';', 'blue;yellow'],
        ];
    }

    /**
     * Test valid choice with value contain multiple and separator.
     *
     * @param array $choices
     * @param string|null $separator
     * @param string $value
     */
    #[DataProvider('dataValidChoiceSeparatorFail')]
    public function testValidChoiceSeparatorFail(array $choices, ?string $separator, string $value): void
    {
        $input = new ConsoleInputArgument(
            'colors',
            '',
            false,
            $choices,
            null,
            $separator,
        );

        $this->expectException(ConsoleException::class);
        $input->validChoice($value);
    }

    /**
     * Test xml.
     */
    public function testXml(): void
    {
        $input = new ConsoleInputArgument(
            'colors',
            'flower colors',
            true,
            ['red', 'blue'],
            'red',
            ',',
        );
        $parent = new SimpleXMLElement('<options></options>');
        $xml = $input->xml($parent);

        $expected = <<<XML
<?xml version="1.0"?>
<options><argument name="colors" help="flower colors" required="1" separator="," default="red"><choices><choice>red</choice><choice>blue</choice></choices></argument></options>

XML;

        $this->assertEquals($expected, (string)$xml->asXML());
    }
}
