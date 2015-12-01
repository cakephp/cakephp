<?php
App::uses("ProgressShellHelper", "Console/Helper");
App::uses("ConsoleOutputStub", "TestSuite/Stub");

/**
 * ProgressHelper test.
 * @property ConsoleOutputStub $consoleOutput
 * @property ProgressShellHelper $helper
 */
class ProgressShellHelperTest extends CakeTestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->consoleOutput = new ConsoleOutputStub();
        $this->helper = new ProgressShellHelper($this->consoleOutput);
    }

    /**
     * Test that a callback is required.*
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
        $this->assertEquals($expected, $this->consoleOutput->messages());
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
        $this->assertEquals($expected, $this->consoleOutput->messages());
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
        $this->assertEquals($expected, $this->consoleOutput->messages());
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
        $this->assertEquals($expected, $this->consoleOutput->messages());
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
        $this->helper->increment(4);
        $this->helper->draw();
        $this->helper->increment(4);
        $this->helper->draw();
        $this->helper->increment(4);
        $this->helper->draw();

        $expected = [
            '',
            '=====>           40%',
            '',
            '===========>     80%',
            '',
            '==============> 100%',
        ];
        $this->assertEquals($expected, $this->consoleOutput->messages());
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
        $this->assertEquals($expected, $this->consoleOutput->messages());
    }
}