<?php
/**
 * CrudAuthorizeTest file
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
namespace Cake\Test\TestCase\Controller\Component\Auth;

use Cake\Controller\Component\Auth\CrudAuthorize;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * Class CrudAuthorizeTest
 *
 */
class CrudAuthorizeTest extends TestCase {

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Routing.prefixes', array());
		Router::reload();

		$this->Acl = $this->getMock('Cake\Controller\Component\AclComponent', array(), array(), '', false);
		$this->Components = $this->getMock('Cake\Controller\ComponentRegistry');

		$this->auth = new CrudAuthorize($this->Components);
	}

/**
 * setup the mock acl.
 *
 * @return void
 */
	protected function _mockAcl() {
		$this->Components->expects($this->any())
			->method('load')
			->with('Acl')
			->will($this->returnValue($this->Acl));
	}

/**
 * test authorize() without a mapped action, ensure an error is generated.
 *
 * @expectedException PHPUnit_Framework_Error_Warning
 * @return void
 */
	public function testAuthorizeNoMappedAction() {
		$request = new Request('/posts/foobar');
		$request->addParams(array(
			'controller' => 'posts',
			'action' => 'foobar'
		));
		$user = array('User' => array('username' => 'mark'));

		$this->auth->authorize($user, $request);
	}

/**
 * test check() passing
 *
 * @return void
 */
	public function testAuthorizeCheckSuccess() {
		$request = new Request('posts/index');
		$request->addParams(array(
			'controller' => 'posts',
			'action' => 'index'
		));
		$user = array('Users' => array('username' => 'mark'));

		$this->_mockAcl();
		$this->Acl->expects($this->once())
			->method('check')
			->with($user, 'Posts', 'read')
			->will($this->returnValue(true));

		$this->assertTrue($this->auth->authorize($user['Users'], $request));
	}

/**
 * test check() failing
 *
 * @return void
 */
	public function testAuthorizeCheckFailure() {
		$request = new Request('posts/index');
		$request->addParams(array(
			'controller' => 'posts',
			'action' => 'index'
		));
		$user = array('Users' => array('username' => 'mark'));

		$this->_mockAcl();
		$this->Acl->expects($this->once())
			->method('check')
			->with($user, 'Posts', 'read')
			->will($this->returnValue(false));

		$this->assertFalse($this->auth->authorize($user['Users'], $request));
	}

/**
 * test getting actionMap
 *
 * @return void
 */
	public function testMapActionsGet() {
		$result = $this->auth->mapActions();
		$expected = array(
			'create' => 'create',
			'read' => 'read',
			'update' => 'update',
			'delete' => 'delete',
			'index' => 'read',
			'add' => 'create',
			'edit' => 'update',
			'view' => 'read',
			'remove' => 'delete'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test adding into mapActions
 *
 * @return void
 */
	public function testMapActionsSet() {
		$map = array(
			'create' => array('generate'),
			'read' => array('listing', 'show'),
			'update' => array('update'),
			'random' => 'custom'
		);
		$result = $this->auth->mapActions($map);
		$this->assertNull($result);

		$result = $this->auth->mapActions();
		$expected = array(
			'add' => 'create',
			'create' => 'create',
			'read' => 'read',
			'index' => 'read',
			'edit' => 'update',
			'view' => 'read',
			'delete' => 'delete',
			'remove' => 'delete',
			'generate' => 'create',
			'listing' => 'read',
			'show' => 'read',
			'update' => 'update',
			'random' => 'custom',
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test prefix routes getting auto mapped.
 *
 * @return void
 */
	public function testAutoPrefixMapActions() {
		Configure::write('Routing.prefixes', array('admin', 'manager'));
		Router::reload();

		$auth = new CrudAuthorize($this->Components);
		$this->assertTrue((bool)$auth->config('actionMap.admin_index'), 'admin_index should now be a mapped action');
	}

}
