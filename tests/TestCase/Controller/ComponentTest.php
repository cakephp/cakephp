<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use TestApp\Controller\ComponentTestController;
use TestApp\Controller\Component\AppleComponent;

/**
 * ComponentTest class
 *
 */
class ComponentTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');

        $this->_pluginPaths = App::path('Plugin');
    }

    /**
     * test accessing inner components.
     *
     * @return void
     */
    public function testInnerComponentConstruction()
    {
        $Collection = new ComponentRegistry();
        $Component = new AppleComponent($Collection);

        $this->assertInstanceOf('TestApp\Controller\Component\OrangeComponent', $Component->Orange, 'class is wrong');
    }

    /**
     * test component loading
     *
     * @return void
     */
    public function testNestedComponentLoading()
    {
        $Collection = new ComponentRegistry();
        $Apple = new AppleComponent($Collection);

        $this->assertInstanceOf('TestApp\Controller\Component\OrangeComponent', $Apple->Orange, 'class is wrong');
        $this->assertInstanceOf('TestApp\Controller\Component\BananaComponent', $Apple->Orange->Banana, 'class is wrong');
        $this->assertTrue(empty($Apple->Session));
        $this->assertTrue(empty($Apple->Orange->Session));
    }

    /**
     * test that component components are not enabled in the collection.
     *
     * @return void
     */
    public function testInnerComponentsAreNotEnabled()
    {
        $mock = $this->getMockBuilder('Cake\Event\EventManager')->getMock();
        $controller = new Controller();
        $controller->eventManager($mock);

        $mock->expects($this->once())
            ->method('on')
            ->with($this->isInstanceOf('TestApp\Controller\Component\AppleComponent'));

        $Collection = new ComponentRegistry($controller);
        $Apple = $Collection->load('Apple');

        $this->assertInstanceOf('TestApp\Controller\Component\OrangeComponent', $Apple->Orange, 'class is wrong');
    }

    /**
     * test a component being used more than once.
     *
     * @return void
     */
    public function testMultipleComponentInitialize()
    {
        $Collection = new ComponentRegistry();
        $Banana = $Collection->load('Banana');
        $Orange = $Collection->load('Orange');

        $this->assertSame($Banana, $Orange->Banana, 'Should be references');
        $Banana->testField = 'OrangeField';

        $this->assertSame($Banana->testField, $Orange->Banana->testField, 'References are broken');
    }

    /**
     * Test a duplicate component being loaded more than once with same and differing configurations.
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage The "Banana" alias has already been loaded with the following config:
     * @return void
     */
    public function testDuplicateComponentInitialize()
    {
        $Collection = new ComponentRegistry();
        $Collection->load('Banana', ['property' => ['closure' => function () {
        }]]);
        $Collection->load('Banana', ['property' => ['closure' => function () {
        }]]);

        $this->assertInstanceOf('TestApp\Controller\Component\BananaComponent', $Collection->Banana, 'class is wrong');

        $Collection->load('Banana', ['property' => ['differs']]);
    }

    /**
     * Test mutually referencing components.
     *
     * @return void
     */
    public function testSomethingReferencingCookieComponent()
    {
        $Controller = new ComponentTestController();
        $Controller->loadComponent('SomethingWithCookie');
        $Controller->startupProcess();

        $this->assertInstanceOf('TestApp\Controller\Component\SomethingWithCookieComponent', $Controller->SomethingWithCookie);
        $this->assertInstanceOf('Cake\Controller\Component\CookieComponent', $Controller->SomethingWithCookie->Cookie);
    }

    /**
     * Tests __debugInfo
     *
     * @return void
     */
    public function testDebugInfo()
    {
        $Collection = new ComponentRegistry();
        $Component = new AppleComponent($Collection);

        $expected = [
            'components' => [
                'Orange'
            ],
            'implementedEvents' => [
                'Controller.startup' => 'startup'
            ],
            '_config' => []
        ];
        $result = $Component->__debugInfo();
        $this->assertEquals($expected, $result);
    }
}
