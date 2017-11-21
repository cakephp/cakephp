<?php
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
use Cake\Shell\Helper\TableHelper;
use Cake\TestSuite\Stub\ConsoleOutput;
use Cake\TestSuite\TestCase;

/**
 * TableHelper test.
 */
class TableHelperTest extends TestCase
{
    /**
     * @var \Cake\Console\ConsoleOutput
     */
    public $stub;

    /**
     * @var \Cake\Console\ConsoleIo
     */
    public $io;

    /**
     * @var \Cake\Shell\Helper\TableHelper
     */
    public $helper;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->stub = new ConsoleOutput();
        $this->io = new ConsoleIo($this->stub);
        $this->helper = new TableHelper($this->io);
    }

    /**
     * Test output
     *
     * @return void
     */
    public function testOutputDefaultOutput()
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
     *
     * @return void
     */
    public function testOutputInconsistentKeys()
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
     *
     * @return void
     */
    public function testOutputEmptyStrings()
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
    public function testNullValues()
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
     *
     * @return void
     */
    public function testOutputUtf8()
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
     *
     * @return void
     */
    public function testOutputFullwidth()
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
     *
     * @return void
     */
    public function testOutputWithoutHeaderStyle()
    {
        $data = [
            ['Header 1', 'Header', 'Long Header'],
            ['short', 'Longish thing', 'short'],
            ['Longer thing', 'short', 'Longest Value'],
        ];
        $this->helper->config(['headerStyle' => false]);
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
     *
     * @return void
     */
    public function testOutputWithDifferentHeaderStyle()
    {
        $data = [
            ['Header 1', 'Header', 'Long Header'],
            ['short', 'Longish thing', 'short'],
            ['Longer thing', 'short', 'Longest Value'],
        ];
        $this->helper->config(['headerStyle' => 'error']);
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
     *
     * @return void
     */
    public function testOutputWithoutHeaders()
    {
        $data = [
            ['short', 'Longish thing', 'short'],
            ['Longer thing', 'short', 'Longest Value'],
        ];
        $this->helper->config(['headers' => false]);
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
     * Test output with row separator
     *
     * @return void
     */
    public function testOutputWithRowSeparator()
    {
        $data = [
            ['Header 1', 'Header', 'Long Header'],
            ['short', 'Longish thing', 'short'],
            ['Longer thing', 'short', 'Longest Value']
        ];
        $this->helper->config(['rowSeparator' => true]);
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
     *
     * @return void
     */
    public function testOutputWithRowSeparatorAndHeaders()
    {
        $data = [
            ['Header 1', 'Header', 'Long Header'],
            ['short', 'Longish thing', 'short'],
            ['Longer thing', 'short', 'Longest Value'],
        ];
        $this->helper->config(['rowSeparator' => true]);
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
    public function testOutputWithNoData()
    {
        $this->helper->output([]);
        $this->assertEquals([], $this->stub->messages());
    }

    /**
     * Test output with a header but no data.
     */
    public function testOutputWithHeaderAndNoData()
    {
        $data = [
            ['Header 1', 'Header', 'Long Header']
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
    public function testOutputHeaderDisabledNoData()
    {
        $this->helper->config(['header' => false]);
        $this->helper->output([]);
        $this->assertEquals([], $this->stub->messages());
    }
}
