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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell\Macro;

use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Shell\Macro\TableMacro;
use Cake\TestSuite\TestCase;

/**
 * StubOutput makes testing easier.
 */
class StubOutput extends ConsoleOutput
{
    protected $_out = [];

    public function write($message, $newlines = 1)
    {
        $this->_out[] = $message;
    }

    public function messages()
    {
        return $this->_out;
    }
}

/**
 * TableMacro test.
 */
class TableMacroTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->stub = new StubOutput();
        $this->io = new ConsoleIo($this->stub);
        $this->macro = new TableMacro($this->io);
    }

    /**
     * Test output
     *
     * @return voi
     */
    public function testOutput()
    {
        $data = [
            ['Header 1', 'Header', 'Long Header'],
            ['short', 'Longish thing', 'short'],
            ['Longer thing', 'short', 'Longest Value'],
        ];
        $this->macro->output($data);
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
}
