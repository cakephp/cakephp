<?php
/**
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
    public function testDefaultOutput()
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
     * Test output with multibyte characters
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
}
