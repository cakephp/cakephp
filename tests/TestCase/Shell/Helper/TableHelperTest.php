<?php
declare(strict_types=1);

/**
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell\Helper;

use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\Shell\Helper\TableHelper;
use Cake\TestSuite\TestCase;

/**
 * TableHelper test.
 */
class TableHelperTest extends TestCase
{
    /**
     * @var \Cake\Console\TestSuite\StubConsoleOutput
     */
    protected $stub;

    /**
     * @var \Cake\Console\ConsoleIo
     */
    protected $io;

    /**
     * @var \Cake\Shell\Helper\TableHelper
     */
    protected $helper;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->stub = new StubConsoleOutput();
        $this->io = new ConsoleIo($this->stub);
        $this->helper = new TableHelper($this->io);
    }

    /**
     * Test output
     */
    public function testOutputDefaultOutput(): void
    {
        $data = [
            ['Header 1', 'Header', 'Long Header'],
            ['short', 'Longish thing', 'short'],
            ['Longer thing', 'short', 'Longest Value'],
        ];
        $this->helper->output($data);
        $expected = [
            '+--------------+---------------+---------------+',
            '| <info>Header 1</info>     | <info>Header</info>        | <info>Long Header</info>   |',
            '+--------------+---------------+---------------+',
            '| short        | Longish thing | short         |',
            '| Longer thing | short         | Longest Value |',
            '+--------------+---------------+---------------+',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test output with inconsistent keys.
     *
     * When outputting entities or other structured data,
     * headers shouldn't need to have the same keys as it is
     * annoying to use.
     */
    public function testOutputInconsistentKeys(): void
    {
        $data = [
            ['Header 1', 'Header', 'Long Header'],
            ['a' => 'short', 'b' => 'Longish thing', 'c' => 'short'],
            ['c' => 'Longer thing', 'a' => 'short', 'b' => 'Longest Value'],
        ];
        $this->helper->output($data);
        $expected = [
            '+--------------+---------------+---------------+',
            '| <info>Header 1</info>     | <info>Header</info>        | <info>Long Header</info>   |',
            '+--------------+---------------+---------------+',
            '| short        | Longish thing | short         |',
            '| Longer thing | short         | Longest Value |',
            '+--------------+---------------+---------------+',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test that output works when data contains just empty strings.
     */
    public function testOutputEmptyStrings(): void
    {
        $data = [
            ['Header 1', 'Header', 'Empty'],
            ['short', 'Longish thing', ''],
            ['Longer thing', 'short', ''],
        ];
        $this->helper->output($data);
        $expected = [
            '+--------------+---------------+-------+',
            '| <info>Header 1</info>     | <info>Header</info>        | <info>Empty</info> |',
            '+--------------+---------------+-------+',
            '| short        | Longish thing |       |',
            '| Longer thing | short         |       |',
            '+--------------+---------------+-------+',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test that output works when data contains nulls.
     */
    public function testNullValues(): void
    {
        $data = [
            ['Header 1', 'Header', 'Empty'],
            ['short', 'Longish thing', null],
            ['Longer thing', 'short', null],
        ];
        $this->helper->output($data);
        $expected = [
            '+--------------+---------------+-------+',
            '| <info>Header 1</info>     | <info>Header</info>        | <info>Empty</info> |',
            '+--------------+---------------+-------+',
            '| short        | Longish thing |       |',
            '| Longer thing | short         |       |',
            '+--------------+---------------+-------+',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test output with multi-byte characters
     */
    public function testOutputUtf8(): void
    {
        $data = [
            ['Header 1', 'Head', 'Long Header'],
            ['short', 'ÄÄÄÜÜÜ', 'short'],
            ['Longer thing', 'longerish', 'Longest Value'],
        ];
        $this->helper->output($data);
        $expected = [
            '+--------------+-----------+---------------+',
            '| <info>Header 1</info>     | <info>Head</info>      | <info>Long Header</info>   |',
            '+--------------+-----------+---------------+',
            '| short        | ÄÄÄÜÜÜ    | short         |',
            '| Longer thing | longerish | Longest Value |',
            '+--------------+-----------+---------------+',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test output with multi-byte characters
     */
    public function testOutputFullwidth(): void
    {
        $data = [
            ['Header 1', 'Head', 'Long Header'],
            ['short', '竜頭蛇尾', 'short'],
            ['Longer thing', 'longerish', 'Longest Value'],
        ];
        $this->helper->output($data);
        $expected = [
            '+--------------+-----------+---------------+',
            '| <info>Header 1</info>     | <info>Head</info>      | <info>Long Header</info>   |',
            '+--------------+-----------+---------------+',
            '| short        | 竜頭蛇尾  | short         |',
            '| Longer thing | longerish | Longest Value |',
            '+--------------+-----------+---------------+',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test output without headers
     */
    public function testOutputWithoutHeaderStyle(): void
    {
        $data = [
            ['Header 1', 'Header', 'Long Header'],
            ['short', 'Longish thing', 'short'],
            ['Longer thing', 'short', 'Longest Value'],
        ];
        $this->helper->setConfig(['headerStyle' => false]);
        $this->helper->output($data);
        $expected = [
            '+--------------+---------------+---------------+',
            '| Header 1     | Header        | Long Header   |',
            '+--------------+---------------+---------------+',
            '| short        | Longish thing | short         |',
            '| Longer thing | short         | Longest Value |',
            '+--------------+---------------+---------------+',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test output with different header style
     */
    public function testOutputWithDifferentHeaderStyle(): void
    {
        $data = [
            ['Header 1', 'Header', 'Long Header'],
            ['short', 'Longish thing', 'short'],
            ['Longer thing', 'short', 'Longest Value'],
        ];
        $this->helper->setConfig(['headerStyle' => 'error']);
        $this->helper->output($data);
        $expected = [
            '+--------------+---------------+---------------+',
            '| <error>Header 1</error>     | <error>Header</error>        | <error>Long Header</error>   |',
            '+--------------+---------------+---------------+',
            '| short        | Longish thing | short         |',
            '| Longer thing | short         | Longest Value |',
            '+--------------+---------------+---------------+',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test output without table headers
     */
    public function testOutputWithoutHeaders(): void
    {
        $data = [
            ['short', 'Longish thing', 'short'],
            ['Longer thing', 'short', 'Longest Value'],
        ];
        $this->helper->setConfig(['headers' => false]);
        $this->helper->output($data);
        $expected = [
            '+--------------+---------------+---------------+',
            '| short        | Longish thing | short         |',
            '| Longer thing | short         | Longest Value |',
            '+--------------+---------------+---------------+',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test output with formatted cells
     */
    public function testOutputWithFormattedCells(): void
    {
        $data = [
            ['short', 'Longish thing', '<info>short</info>'],
            ['Longer thing', 'short', '<warning>Longest</warning> <error>Value</error>'],
        ];
        $this->helper->setConfig(['headers' => false]);
        $this->helper->output($data);
        $expected = [
            '+--------------+---------------+---------------+',
            '| short        | Longish thing | <info>short</info>         |',
            '| Longer thing | short         | <warning>Longest</warning> <error>Value</error> |',
            '+--------------+---------------+---------------+',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test output with row separator
     */
    public function testOutputWithRowSeparator(): void
    {
        $data = [
            ['Header 1', 'Header', 'Long Header'],
            ['short', 'Longish thing', 'short'],
            ['Longer thing', 'short', 'Longest Value'],
        ];
        $this->helper->setConfig(['rowSeparator' => true]);
        $this->helper->output($data);
        $expected = [
            '+--------------+---------------+---------------+',
            '| <info>Header 1</info>     | <info>Header</info>        | <info>Long Header</info>   |',
            '+--------------+---------------+---------------+',
            '| short        | Longish thing | short         |',
            '+--------------+---------------+---------------+',
            '| Longer thing | short         | Longest Value |',
            '+--------------+---------------+---------------+',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test output with row separator and no headers
     */
    public function testOutputWithRowSeparatorAndHeaders(): void
    {
        $data = [
            ['Header 1', 'Header', 'Long Header'],
            ['short', 'Longish thing', 'short'],
            ['Longer thing', 'short', 'Longest Value'],
        ];
        $this->helper->setConfig(['rowSeparator' => true]);
        $this->helper->output($data);
        $expected = [
            '+--------------+---------------+---------------+',
            '| <info>Header 1</info>     | <info>Header</info>        | <info>Long Header</info>   |',
            '+--------------+---------------+---------------+',
            '| short        | Longish thing | short         |',
            '+--------------+---------------+---------------+',
            '| Longer thing | short         | Longest Value |',
            '+--------------+---------------+---------------+',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test output when there is no data.
     */
    public function testOutputWithNoData(): void
    {
        $this->helper->output([]);
        $this->assertEquals([], $this->stub->messages());
    }

    /**
     * Test output with a header but no data.
     */
    public function testOutputWithHeaderAndNoData(): void
    {
        $data = [
            ['Header 1', 'Header', 'Long Header'],
        ];
        $this->helper->output($data);
        $expected = [
            '+----------+--------+-------------+',
            '| <info>Header 1</info> | <info>Header</info> | <info>Long Header</info> |',
            '+----------+--------+-------------+',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test no data when headers are disabled.
     */
    public function testOutputHeaderDisabledNoData(): void
    {
        $this->helper->setConfig(['header' => false]);
        $this->helper->output([]);
        $this->assertEquals([], $this->stub->messages());
    }

    /**
     * Right-aligned text style test.
     */
    public function testTextRightStyle(): void
    {
        $data = [
            ['Item', 'Price per piece (yen)'],
            ['Apple', '<text-right><info>¥</info> 200</text-right>'],
            ['Orange', '100'],
        ];
        $this->helper->output($data);
        $expected = [
            '+--------+-----------------------+',
            '| <info>Item</info>   | <info>Price per piece (yen)</info> |',
            '+--------+-----------------------+',
            '| Apple  |                 <info>¥</info> 200 |',
            '| Orange | 100                   |',
            '+--------+-----------------------+',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Right-aligned text style test.(If there is text rightside the text-right tag)
     */
    public function testTextRightsideTheTextRightTag(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $data = [
            ['Item', 'Price per piece (yen)'],
            ['Apple', '<text-right>some</text-right>text'],
        ];
        $this->helper->output($data);
    }

    /**
     * Right-aligned text style test.(If there is text leftside the text-right tag)
     */
    public function testTextLeftsideTheTextRightTag(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $data = [
            ['Item', 'Price per piece (yen)'],
            ['Apple', 'text<text-right>some</text-right>'],
        ];
        $this->helper->output($data);
    }

    /**
     * Table row column of type integer should be cast to string
     */
    public function testRowValueInteger(): void
    {
        $data = [
            ['Item', 'Quantity'],
            ['Cakes', 2],
        ];
        $this->helper->output($data);
        $expected = [
            '+-------+----------+',
            '| <info>Item</info>  | <info>Quantity</info> |',
            '+-------+----------+',
            '| Cakes | 2        |',
            '+-------+----------+',
        ];

        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Table row column of type null should be cast to empty string
     */
    public function testRowValueNull(): void
    {
        $data = [
            ['Item', 'Quantity'],
            ['Cakes', null],
        ];
        $this->helper->output($data);
        $expected = [
            '+-------+----------+',
            '| <info>Item</info>  | <info>Quantity</info> |',
            '+-------+----------+',
            '| Cakes |          |',
            '+-------+----------+',
        ];

        $this->assertEquals($expected, $this->stub->messages());
    }
}
