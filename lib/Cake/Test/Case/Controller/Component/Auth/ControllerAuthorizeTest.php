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
 * @package       Cake.Test.Case.Controller.Component.Auth
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');
App::uses('ControllerAuthorize', 'Controller/Component/Auth');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

/**
 * ControllerAuthorizeTest
 *
 * @package       Cake.Test.Case.Controller.Component.Auth
 */
class ControllerAuthorizeTest extends CakeTestCase {

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->controller = $this->getMock('Controller', array('isAuthorized'), array(), '', false);
		$this->components = $this->getMock('ComponentCollection');
		$this->components->expects($this->any())
			->method('getController')
			->will($this->returnValue($this->controller));

		$this->auth = new ControllerAuthorize($this->components);
	}

/**
 * testControllerTypeError
 *
 * @expectedException PHPUnit_Framework_Error
 * @return void
 * @throws PHPUnit_Framework_Error
 */
	public function testControllerTypeError() {
		try {
			$this->auth->controller(new StdClass());
			$this->fail('No exception thrown');
		} catch (TypeError $e) {
			throw new PHPUnit_Framework_Error('Raised an error', 100, __FILE__, __LINE__);
		}
	}

/**
 * testControllerErrorOnMissingMethod
 *
 * @expectedException CakeException
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
		$request = new CakeRequest('/posts/index', false);
		$this->assertFalse($this->auth->authorize($user, $request));
	}

/**
 * test isAuthorized working.
 *
 * @return void
 */
	public function testAuthorizeSuccess() {
		$user = array('User' => array('username' => 'mark'));
		$request = new CakeRequest('/posts/index', false);

		$this->controller->expects($this->once())
			->method('isAuthorized')
			->with($user)
			->will($this->returnValue(true));

		$this->assertTrue($this->auth->authorize($user, $request));
	}

}
