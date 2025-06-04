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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Command\Helper;

use Cake\Command\Helper\TreeHelper;
use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\TestSuite\TestCase;
use Stringable;
use TestApp\Model\Enum\Gender;
use TestApp\Model\Enum\NonBacked;
use TestApp\Model\Enum\Priority;

/**
 * TreeHelper test.
 */
class TreeHelperTest extends TestCase
{
    /**
     * @var \Cake\Command\Helper\TreeHelper
     */
    protected TreeHelper $helper;

    /**
     * @var \Cake\Console\TestSuite\StubConsoleOutput
     */
    protected StubConsoleOutput $stub;

    /**
     * @var \Cake\Console\ConsoleIo
     */
    protected ConsoleIo $io;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->stub = new StubConsoleOutput();
        $this->io = new ConsoleIo($this->stub);
        $this->helper = new TreeHelper($this->io);
    }

    public function testEmptyTree(): void
    {
        $this->helper->output([]);
        $this->assertEquals([], $this->stub->messages());
    }

    public function testOnedimensionList(): void
    {
        $this->helper->output(['one', 'two', 'three']);
        $this->assertEquals([
            'one',
            'two',
            'three',
        ], $this->stub->messages());
    }

    public function testOneDimensionArray(): void
    {
        $this->helper->output(['one', 'key' => ['two']]);
        $this->assertEquals([
            'one',
            'key',
            '└── two',
        ], $this->stub->messages());
    }

    public function testTwoDimensionValue(): void
    {
        $this->helper->output([
            'one' => [
                'one_1' => ['one_1_1' => 'one_1_1_1'],
            ],
            'two' => [
                'two_1' => ['two_1_1' => 'two_1_1_1'],
            ],
        ]);
        $this->assertEquals([
            'one',
            '└── one_1',
            '    └── one_1_1',
            '        └── one_1_1_1',
            'two',
            '└── two_1',
            '    └── two_1_1',
            '        └── two_1_1_1',
        ], $this->stub->messages());
    }

    public function testTwoDimensionList(): void
    {
        $this->helper->output([
            'one' => [
                'one_1' => ['one_1_1' => ['one_1_1_1', 'one_1_1_2']],
            ],
            'two' => [
                'two_1' => ['two_1_1' => ['two_1_1_1', 'two_1_1_2']],
            ],
        ]);
        $this->assertEquals([
            'one',
            '└── one_1',
            '    └── one_1_1',
            '        ├── one_1_1_1',
            '        └── one_1_1_2',
            'two',
            '└── two_1',
            '    └── two_1_1',
            '        ├── two_1_1_1',
            '        └── two_1_1_2',
        ], $this->stub->messages());
    }

    public function testClosureValue(): void
    {
        $this->helper->output([fn() => 'from closure']);
        $this->assertEquals([
            'from closure',
        ], $this->stub->messages());
    }

    public function testEnumValue(): void
    {
        $this->helper->output([NonBacked::Basic, Gender::NoSelection, Priority::Low]);
        $this->assertEquals([
            'Basic',
            '',
            'Is Low',
        ], $this->stub->messages());
    }

    public function testBoolValue(): void
    {
        $this->helper->output([true, false]);
        $this->assertEquals([
            'true',
            'false',
        ], $this->stub->messages());
    }

    public function testStringbleValue(): void
    {
        $c = new class () implements Stringable {
            public function __toString(): string
            {
                return 'from stringable';
            }
        };

        $this->helper->output([new $c()]);
        $this->assertEquals([
            'from stringable',
        ], $this->stub->messages());
    }

    public function testInitialIndent(): void
    {
        $this->helper->setConfig('initialIndent', 5);
        $this->helper->output(['one', 'two' => ['two_1']]);
        $this->assertEquals([
            '     one',
            '     two',
            '     └── two_1',
        ], $this->stub->messages());
    }
}
