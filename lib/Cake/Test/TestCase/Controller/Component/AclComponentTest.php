<?php
/**
 * AclComponentTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 1.2.0.5435
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentCollection;
use Cake\Controller\Component\AclComponent;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * Test Case for AclComponent
 *
 * @package       Cake.Test.Case.Controller.Component
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
		$Collection = new ComponentCollection();
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
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testConstrutorException() {
		Configure::write('Acl.classname', 'AclClassNameThatDoesNotExist');
		$Collection = new ComponentCollection();
		new AclComponent($Collection);
	}

/**
 * test that adapter() allows control of the internal implementation AclComponent uses.
 *
 * @return void
 */
	public function testAdapter() {
		$implementation = new \MockAclImplementation();
		$implementation->expects($this->once())->method('initialize')->with($this->Acl);
		$this->assertNull($this->Acl->adapter($implementation));

		$this->assertEquals($this->Acl->adapter(), $implementation, 'Returned object is different %s');
	}

/**
 * test that adapter() whines when the class is not an AclBase
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testAdapterException() {
		$thing = new \StdClass();
		$this->Acl->adapter($thing);
	}

}
