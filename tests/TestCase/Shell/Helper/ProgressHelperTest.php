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
use Cake\Shell\Helper\ProgressHelper;
use Cake\TestSuite\TestCase;

/**
 * ProgressHelper test.
 */
class ProgressHelperTest extends TestCase
{
    /**
     * @var \Cake\Shell\Helper\ProgressHelper
     */
    protected $helper;

    /**
     * @var \Cake\Console\TestSuite\StubConsoleOutput
     */
    protected $stub;

    /**
     * @var \Cake\Console\ConsoleIo
     */
    protected $io;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->stub = new StubConsoleOutput();
        $this->io = new ConsoleIo($this->stub);
        $this->helper = new ProgressHelper($this->io);
    }

    /**
     * Test using the helper manually.
     */
    public function testInit(): void
    {
        $helper = $this->helper->init([
            'total' => 200,
            'width' => 50,
        ]);
        $this->assertSame($helper, $this->helper, 'Should be chainable');
    }

    public function testIncrementWithoutInit(): void
    {
        $this->helper->increment(10);
        $this->helper->draw();
        $expected = [
            '',
            '======>                                                                      10%',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test that a callback is required.
     */
    public function testOutputFailure(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->helper->output(['not a callback']);
    }

    /**
     * Test that the callback is invoked until 100 is reached.
     */
    public function testOutputSuccess(): void
    {
        $this->helper->output([function (ProgressHelper $progress): void {
            $progress->increment(20);
        }]);
        $expected = [
            '',
            '',
            '==============>                                                              20%',
            '',
            '=============================>                                               40%',
            '',
            '============================================>                                60%',
            '',
            '===========================================================>                 80%',
            '',
            '==========================================================================> 100%',
            '',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test output with options
     */
    public function testOutputSuccessOptions(): void
    {
        $this->helper->output([
            'total' => 10,
            'width' => 20,
            'callback' => function (ProgressHelper $progress): void {
                $progress->increment(2);
            },
        ]);
        $expected = [
            '',
            '',
            '==>              20%',
            '',
            '=====>           40%',
            '',
            '========>        60%',
            '',
            '===========>     80%',
            '',
            '==============> 100%',
            '',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test using the helper manually.
     */
    public function testIncrementAndRender(): void
    {
        $this->helper->init();

        $this->helper->increment(20);
        $this->helper->draw();

        $this->helper->increment(40.0);
        $this->helper->draw();

        $this->helper->increment(40);
        $this->helper->draw();

        $expected = [
            '',
            '==============>                                                              20%',
            '',
            '============================================>                                60%',
            '',
            '==========================================================================> 100%',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test using the helper chained.
     */
    public function testIncrementAndRenderChained(): void
    {
        $this->helper->init()
            ->increment(20)
            ->draw()
            ->increment(40)
            ->draw()
            ->increment(40)
            ->draw();

        $expected = [
            '',
            '==============>                                                              20%',
            '',
            '============================================>                                60%',
            '',
            '==========================================================================> 100%',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test negative numbers
     */
    public function testIncrementWithNegatives(): void
    {
        $this->helper->init();

        $this->helper->increment(40);
        $this->helper->draw();

        $this->helper->increment(-60);
        $this->helper->draw();

        $this->helper->increment(80);
        $this->helper->draw();

        $expected = [
            '',
            '=============================>                                               40%',
            '',
            '                                                                              0%',
            '',
            '===========================================================>                 80%',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test increment and draw with options
     */
    public function testIncrementWithOptions(): void
    {
        $this->helper->init([
            'total' => 10,
            'width' => 20,
        ]);
        $expected = [
            '',
            '=====>           40%',
            '',
            '===========>     80%',
            '',
            '==============> 100%',
        ];
        $this->helper->increment(4);
        $this->helper->draw();
        $this->helper->increment(4);
        $this->helper->draw();
        $this->helper->increment(4);
        $this->helper->draw();

        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test increment and draw with value that makes the pad
     * be a float
     */
    public function testIncrementFloatPad(): void
    {
        $this->helper->init([
            'total' => 50,
        ]);
        $expected = [
            '',
            '=========>                                                                   14%',
            '',
            '====================>                                                        28%',
            '',
            '==============================>                                              42%',
            '',
            '=========================================>                                   56%',
            '',
            '===================================================>                         70%',
            '',
            '========================================================>                    76%',
            '',
            '==============================================================>              84%',
            '',
            '==========================================================================> 100%',
        ];
        $this->helper->increment(7);
        $this->helper->draw();
        $this->helper->increment(7);
        $this->helper->draw();
        $this->helper->increment(7);
        $this->helper->draw();
        $this->helper->increment(7);
        $this->helper->draw();
        $this->helper->increment(7);
        $this->helper->draw();
        $this->helper->increment(3);
        $this->helper->draw();
        $this->helper->increment(4);
        $this->helper->draw();
        $this->helper->increment(8);
        $this->helper->draw();

        $this->assertEquals($expected, $this->stub->messages());
    }
}
