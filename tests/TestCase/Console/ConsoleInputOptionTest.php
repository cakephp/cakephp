<?php
declare(strict_types=1);

/**
 * ConsoleInputOptionTest file
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\ConsoleInputOption;
use Cake\Console\Exception\ConsoleException;
use Cake\TestSuite\TestCase;

/**
 * ConsoleInputOptionTest
 */
class ConsoleInputOptionTest extends TestCase
{
    /**
     * @var \Cake\Console\ConsoleInputOption|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $input;

    public static function dataProperties()
    {
        return [
            // Test name()
            [['color'], 'name', 'color'],
            // Test short()
            [['color', 'c'], 'short', 'c'],
            // Test defaultValue()
            [['qty', '', '', false, '1'], 'defaultValue', '1'],
            // Test defaultValue() as bool
            [['verbose', '', '', true, '1'], 'defaultValue', true],
            // Test isRequired() as bool
            [['verbose', '', '', false, null, [], false, true], 'isRequired', true],
            // Test isBoolean()
            [['verbose', '', '', true], 'isBoolean', true],
            // Test acceptsMultiple()
            [['verbose', '', '', false, null, [], true], 'acceptsMultiple', true],
            // Test choices()
            [['color', '', '', false, null, ['red', 'blue'], true], 'choices', ['red', 'blue']],
            // Test prompt()
            [['color', '', '', false, null, [], false, false, 'color ?'], 'prompt', 'color ?'],
        ];
    }

    /**
     * Test properties setters and getters.
     * @dataProvider dataProperties
     */
    public function testProperties(array $args, $method, $expected): void
    {
        $input = new ConsoleInputOption(...$args);
        $result = $input->$method();
        $this->assertSame($expected, $result);
    }

    /**
     * Test short option too long.
     */
    public function testShortOptionTooLong(): void
    {
        $this->expectException(ConsoleException::class);
        $this->expectExceptionMessage('Short option `col` is invalid, short options must be one letter.');
        new ConsoleInputOption('color', 'col');
    }

    /**
     * Test default and prompt can't be set together.
     */
    public function testSetDefaultAndPrompt(): void
    {
        $this->expectException(ConsoleException::class);
        $this->expectExceptionMessage('You cannot set both `prompt` and `default` options. Use either a static `default` or interactive `prompt`');
        new ConsoleInputOption('color', '', '', false, 'red', [], false, false, 'color ?');
    }

    /**
     * Test help.
     */
    public function testHelp(): void
    {
        $input = new ConsoleInputOption(
            'color',
            'c',
            'help message',
            false,
            'red',
            ['red', 'blue'],
            false,
            true,
        );
        $output = $input->help(72);
        $this->assertStringStartsWith('--color, -c ', $output);
        $this->assertStringContainsString(' help message <comment>(default: red)</comment>', $output);
        $this->assertStringContainsString(' <comment>(choices: red|blue)</comment>', $output);
        $this->assertStringEndsWith(' <comment>(required)</comment>', $output);
    }

    /**
     * Test usage.
     */
    public function testUsage(): void
    {
        $input = new ConsoleInputOption(
            'color',
            '',
            '',
            false,
            'red',
            ['red', 'blue'],
        );
        $output = $input->usage();
        $this->assertEquals('[--color red|blue]', $output);
    }

    /**
     * Test usage.
     */
    public function testUsageRequired(): void
    {
        $input = new ConsoleInputOption(
            'color',
            '',
            '',
            false,
            '',
            [],
            false,
            true,
        );
        $output = $input->usage();
        $this->assertEquals('--color', $output);
    }

    /**
     * Test valid choice empty.
     */
    public function testValidChoiceEmpty(): void
    {
        $input = new ConsoleInputOption(
            'color',
            '',
            '',
            false,
            '',
            []
        );
        $this->assertTrue($input->validChoice('yellow'));
    }

    /**
     * Test valid choice empty.
     */
    public function testValidChoiceFail(): void
    {
        $input = new ConsoleInputOption(
            'color',
            '',
            '',
            false,
            '',
            ['red', 'blue']
        );
        $this->expectException(ConsoleException::class);
        $this->expectExceptionMessage('`yellow` is not a valid value for `--color`. Please use one of `red, blue`');
        $input->validChoice('yellow');
    }

    /**
     * Test valid choice.
     */
    public function testValidChoiceSuccess(): void
    {
        $input = new ConsoleInputOption(
            'color',
            '',
            '',
            false,
            '',
            ['red', 'blue']
        );
        $this->assertTrue($input->validChoice('red'));
    }

    /**
     * Test xml.
     */
    public function testXml()
    {
        $input = new ConsoleInputOption(
            'colors',
            'c',
            'flower colors',
            false,
            'red',
            ['red', 'blue'],
            true,
            true
        );
        $parent = new \SimpleXMLElement('<options></options>');
        $xml = $input->xml($parent);

        $expected = <<<XML
<?xml version="1.0"?>
<options><option name="--colors" short="-c" help="flower colors" boolean="0" required="1"><default>red</default><choices><choice>red</choice><choice>blue</choice></choices></option></options>

XML;

        $this->assertEquals($expected, (string)$xml->asXML());
    }

     /**
     * Test xml default as true
     */
    public function testXmlDefaultTrue()
    {
$input = new ConsoleInputOption(
            'verbose',
            '',
            '',
            true,
            true,
        );
        $parent = new \SimpleXMLElement('<options></options>');
        $xml = $input->xml($parent);

        $expected = <<<XML
<?xml version="1.0"?>
<options><option name="--verbose" short="" help="" boolean="1" required="0"><default>true</default><choices/></option></options>

XML;

        $this->assertEquals($expected, (string)$xml->asXML());
    }

     /**
     * Test xml default as true
     */
    public function testXmlDefaultFalse()
    {
$input = new ConsoleInputOption(
            'verbose',
            '',
            '',
            true,
            false,
        );
        $parent = new \SimpleXMLElement('<options></options>');
        $xml = $input->xml($parent);

        $expected = <<<XML
<?xml version="1.0"?>
<options><option name="--verbose" short="" help="" boolean="1" required="0"><default>false</default><choices/></option></options>

XML;

        $this->assertEquals($expected, (string)$xml->asXML());
    }
}
