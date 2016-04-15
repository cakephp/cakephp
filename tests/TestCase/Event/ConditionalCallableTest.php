<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Event;

use Cake\Event\ConditionalCallable;
use Cake\TestSuite\TestCase;

class ConditionalCallableTest extends TestCase
{
    private $wasCalled = false;
    private $isAdmin = false;
    private $isEurope = false;

    public function setUp()
    {
        parent::setUp();

        $this->wasCalled = false;
        $this->isAdmin = false;
        $this->isEurope = false;
    }

    public function testEmptyConditions()
    {
        $c = new ConditionalCallable([$this, 'aCallable'], []);
        $c();

        $this->assertWasCalled();
    }

    public function testOneTruthfulIf()
    {
        $this->isAdmin = true;

        $c = new ConditionalCallable([$this, 'aCallable'], ['if' => [$this, 'isAdmin']]);
        $c();

        $this->assertWasCalled();
    }

    public function testOneNotTruthfulIf()
    {
        $this->isAdmin = false;

        $c = new ConditionalCallable([$this, 'aCallable'], ['if' => [$this, 'isAdmin']]);
        $c();

        $this->assertNotCalled();
    }

    public function testMultipleTruthfulIfs()
    {
        $this->isAdmin = true;
        $this->isEurope = true;

        $c = new ConditionalCallable([$this, 'aCallable'], ['if' => [[$this, 'isAdmin'], [$this, 'isEurope']]]);
        $c();

        $this->assertWasCalled();
    }

    public function testMultipleNotTruthfulIfs()
    {
        $this->isAdmin = true;
        $this->isEurope = false;

        $c = new ConditionalCallable([$this, 'aCallable'], ['if' => [[$this, 'isAdmin'], [$this, 'isEurope']]]);
        $c();

        $this->assertNotCalled();
    }

    public function testOneTruthfulUnless()
    {
        $this->isEurope = true;

        $c = new ConditionalCallable([$this, 'aCallable'], ['unless' => [$this, 'isEurope']]);
        $c();

        $this->assertNotCalled();
    }

    public function testOneNotTruthfulUnless()
    {
        $this->isEurope = false;

        $c = new ConditionalCallable([$this, 'aCallable'], ['unless' => [$this, 'isEurope']]);
        $c();

        $this->assertWasCalled();
    }

    public function testMultipleTruthfulUnless()
    {
        $this->isAdmin = true;
        $this->isEurope = true;

        $c = new ConditionalCallable([$this, 'aCallable'], ['unless' => [[$this, 'isAdmin'], [$this, 'isEurope']]]);
        $c();

        $this->assertNotCalled();
    }

    public function testMultipleNotTruthfulUnless()
    {
        $this->isAdmin = true;
        $this->isEurope = false;

        $c = new ConditionalCallable([$this, 'aCallable'], ['unless' => [[$this, 'isAdmin'], [$this, 'isEurope']]]);
        $c();

        $this->assertWasCalled();
    }

    public function testBothIfAndUnlessPass()
    {
        $this->isAdmin = true;
        $this->isEurope = false;

        $c = new ConditionalCallable([$this, 'aCallable'], ['if' => [$this, 'isAdmin'], 'unless' => [$this, 'isEurope']]);
        $c();

        $this->assertWasCalled();
    }

    public function testIfPassesAndUnlessFails()
    {
        $this->isAdmin = true;
        $this->isEurope = true;

        $c = new ConditionalCallable([$this, 'aCallable'], ['if' => [$this, 'isAdmin'], 'unless' => [$this, 'isEurope']]);
        $c();

        $this->assertNotCalled();
    }

    public function testIfFailsAndUnlessPasses()
    {
        $this->isAdmin = false;
        $this->isEurope = false;

        $c = new ConditionalCallable([$this, 'aCallable'], ['if' => [$this, 'isAdmin'], 'unless' => [$this, 'isEurope']]);
        $c();

        $this->assertNotCalled();
    }

    public function testBothIfAndUnlessFail()
    {
        $this->isAdmin = false;
        $this->isEurope = true;

        $c = new ConditionalCallable([$this, 'aCallable'], ['if' => [$this, 'isAdmin'], 'unless' => [$this, 'isEurope']]);
        $c();

        $this->assertNotCalled();
    }

    public function testArguments()
    {
        $c = new ConditionalCallable(
            function ($arg1, $arg2) {
                $this->assertEquals(5, $arg1);
                $this->assertEquals('text', $arg2);
                $this->wasCalled = true;
            },
            [
                'if' => function ($arg1, $arg2) {
                    $this->assertEquals(5, $arg1);
                    $this->assertEquals('text', $arg2);
                    return true;
                },
                'unless' => function ($arg1, $arg2) {
                    $this->assertEquals(5, $arg1);
                    $this->assertEquals('text', $arg2);
                    return false;
                }
            ]
        );

        $c(5, 'text');

        $this->assertWasCalled();
    }

    public function aCallable()
    {
        $this->wasCalled = true;
    }

    public function assertWasCalled()
    {
        $this->assertTrue($this->wasCalled, "Callable was expected to be called, but wasn't called");
    }

    public function assertNotCalled()
    {
        $this->assertFalse($this->wasCalled, "Callable was not expected to be called, but was called");
    }

    public function isAdmin()
    {
        return $this->isAdmin;
    }

    public function isEurope()
    {
        return $this->isEurope;
    }
}
