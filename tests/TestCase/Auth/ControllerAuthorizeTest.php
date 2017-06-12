<?php
/**
 * ControllerAuthorizeTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Auth;

use Cake\Auth\ControllerAuthorize;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * ControllerAuthorizeTest
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
        $this->controller = $this->getMockBuilder(Controller::class)
            ->setMethods(['isAuthorized'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->components = $this->getMockBuilder(ComponentRegistry::class)->getMock();
        $this->components->expects($this->any())
            ->method('getController')
            ->will($this->returnValue($this->controller));

        $this->auth = new ControllerAuthorize($this->components);
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
        $request = new ServerRequest('/posts/index');
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
        $request = new ServerRequest('/posts/index');

        $this->controller->expects($this->once())
            ->method('isAuthorized')
            ->with($user)
            ->will($this->returnValue(true));

        $this->assertTrue($this->auth->authorize($user, $request));
    }
}
