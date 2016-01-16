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
use Cake\Shell\Helper\ProgressHelper;
use Cake\TestSuite\Stub\ConsoleOutput;
use Cake\TestSuite\TestCase;

/**
 * ProgressHelper test.
 */
class ProgressHelperTest extends TestCase
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
        $this->helper = new ProgressHelper($this->io);
    }

    /**
     * Test that a callback is required.
     *
     * @expectedException \RuntimeException
     */
    public function testOutputFailure()
    {
        $this->helper->output(['not a callback']);
    }

    /**
     * Test that the callback is invoked until 100 is reached.
     *
     * @return void
     */
    public function testOutputSuccess()
    {
        $this->helper->output([function ($progress) {
            $progress->increment(20);
        }]);
        $expected = [
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
     *
     * @return void
     */
    public function testOutputSuccessOptions()
    {
        $this->helper->output([
            'total' => 10,
            'width' => 20,
            'callback' => function ($progress) {
                $progress->increment(2);
            }
        ]);
        $expected = [
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
     *
     * @return void
     */
    public function testIncrementAndRender()
    {
        $this->helper->init();

        $this->helper->increment(20);
        $this->helper->draw();

        $this->helper->increment(40);
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
     * Test negative numbers
     *
     * @return void
     */
    public function testIncrementWithNegatives()
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
     *
     * @return void
     */
    public function testIncrementWithOptions()
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
     *
     * @return void
     */
    public function testIncrementFloatPad()
    {
        $this->helper->init([
            'total' => 50
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
