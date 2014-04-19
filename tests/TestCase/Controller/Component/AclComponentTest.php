<?php
/**
 * AclComponentTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\AclComponent;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * Test Case for AclComponent
 *
 */
class AclComponentTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		if (!class_exists('MockAclImplementation', false)) {
			$this->getMock('Cake\Controller\Component\Acl\AclInterface', array(), array(), 'MockAclImplementation');
		}
		Configure::write('Acl.classname', '\MockAclImplementation');
		$Collection = new ComponentRegistry();
		$this->Acl = new AclComponent($Collection);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Acl);
	}

/**
 * test that constructor throws an exception when Acl.classname is a
 * non-existent class
 *
 * @expectedException \Cake\Error\Exception
 * @return void
 */
	public function testConstrutorException() {
		Configure::write('Acl.classname', 'AclClassNameThatDoesNotExist');
		$Collection = new ComponentRegistry();
		new AclComponent($Collection);
	}

/**
 * test that adapter() allows control of the internal implementation AclComponent uses.
 *
 * @return void
 */
	public function testAdapter() {
		$Adapter = $this->getMock('Cake\Controller\Component\Acl\AclInterface');
		$Adapter->expects($this->once())->method('initialize')->with($this->Acl);

		$this->assertNull($this->Acl->adapter($Adapter));
		$this->assertEquals($this->Acl->adapter(), $Adapter, 'Returned object is different %s');
	}

/**
 * test that adapter() whines when the class does not implement AclInterface
 *
 * @expectedException \Cake\Error\Exception
 * @return void
 */
	public function testAdapterException() {
		$thing = new \StdClass();
		$this->Acl->adapter($thing);
	}

}
