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
class ControllerAuthorizeTest extends TestCase {

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->controller = $this->getMock('Cake\Controller\Controller', array('isAuthorized'), array(), '', false);
		$this->components = $this->getMock('Cake\Controller\ComponentRegistry');
		$this->components->expects($this->any())
			->method('getController')
			->will($this->returnValue($this->controller));

		$this->auth = new ControllerAuthorize($this->components);
	}

/**
 * @expectedException \PHPUnit_Framework_Error
 * @return void
 */
	public function testControllerTypeError() {
		$this->auth->controller(new \StdClass());
	}

/**
 * @expectedException \Cake\Error\Exception
 * @return void
 */
	public function testControllerErrorOnMissingMethod() {
		$this->auth->controller(new Controller());
	}

/**
 * test failure
 *
 * @return void
 */
	public function testAuthorizeFailure() {
		$user = array();
		$request = new Request('/posts/index');
		$this->assertFalse($this->auth->authorize($user, $request));
	}

/**
 * test isAuthorized working.
 *
 * @return void
 */
	public function testAuthorizeSuccess() {
		$user = array('User' => array('username' => 'mark'));
		$request = new Request('/posts/index');

		$this->controller->expects($this->once())
			->method('isAuthorized')
			->with($user)
			->will($this->returnValue(true));

		$this->assertTrue($this->auth->authorize($user, $request));
	}

}
