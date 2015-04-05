<?php
/**
 * ControllerAuthorizeTest file
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Auth;

use Cake\Auth\ControllerAuthorize;
use Cake\Controller\Controller;
use Cake\Network\Request;
use Cake\TestSuite\TestCase;

/**
 * Class ControllerAuthorizeTest
 *
 */
class ControllerAuthorizeTest extends TestCase
{

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->controller = $this->getMock('Cake\Controller\Controller', ['isAuthorized'], [], '', false);
        $this->components = $this->getMock('Cake\Controller\ComponentRegistry');
        $this->components->expects($this->any())
            ->method('getController')
            ->will($this->returnValue($this->controller));

        $this->auth = new ControllerAuthorize($this->components);
    }

    /**
     * @return void
     */
    public function testControllerTypeError()
    {
        $this->skipIf(PHP_VERSION_ID >= 70000);

        $message = '/^Argument 1 passed to Cake\\\Auth\\\ControllerAuthorize::controller\(\) must be an instance of Cake\\\Controller\\\Controller, instance of stdClass given.*/';
        $this->setExpectedExceptionRegExp('PHPUnit_Framework_Error', $message);
        $this->auth->controller(new \stdClass());
    }

    /**
     * @return void
     */
    public function testControllerTypeErrorPhp7()
    {
        $this->skipIf(PHP_VERSION_ID < 70000);

        try {
            $this->auth->controller(new \stdClass());
            $this->fail();
        } catch (\BaseException $e) {
            $expectedMessage = 'Argument 1 passed to Cake\Auth\ControllerAuthorize::controller() must be an instance of Cake\Controller\Controller, instance of stdClass given';
            $this->assertContains($expectedMessage, $e->getMessage());
        }
    }

    /**
     * @expectedException \Cake\Core\Exception\Exception
     * @return void
     */
    public function testControllerErrorOnMissingMethod()
    {
        $this->auth->controller(new Controller());
    }

    /**
     * test failure
     *
     * @return void
     */
    public function testAuthorizeFailure()
    {
        $user = [];
        $request = new Request('/posts/index');
        $this->assertFalse($this->auth->authorize($user, $request));
    }

    /**
     * test isAuthorized working.
     *
     * @return void
     */
    public function testAuthorizeSuccess()
    {
        $user = ['User' => ['username' => 'mark']];
        $request = new Request('/posts/index');

        $this->controller->expects($this->once())
            ->method('isAuthorized')
            ->with($user)
            ->will($this->returnValue(true));

        $this->assertTrue($this->auth->authorize($user, $request));
    }
}
