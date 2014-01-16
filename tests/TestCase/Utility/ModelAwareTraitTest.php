<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Cake\Utility\ModelAwareTrait;

/**
 * Testing stub.
 */
class Stub {

	use ModelAwareTrait;

	public function setProps($name) {
		$this->_setModelClass($name);
	}

}

/**
 * ModelAwareTrait test case
 */
class ModelAwareTraitTest extends TestCase {

/**
 * Test set modelClass
 *
 * @return void
 */
	public function testSetModelClass() {
		$stub = new Stub();
		$this->assertNull($stub->modelClass);

		$stub->setProps('StubArticles');
		$this->assertEquals('StubArticles', $stub->modelClass);
	}

/**
 * test loadModel()
 *
 * @return void
 */
	public function testLoadModel() {
		$stub = new Stub();
		$stub->setProps('Articles');
		$stub->modelFactory('Table', ['\Cake\ORM\TableRegistry', 'get']);

		$this->assertTrue($stub->loadModel());
		$this->assertInstanceOf('Cake\ORM\Table', $stub->Articles);

		$this->assertTrue($stub->loadModel('Comments'));
		$this->assertInstanceOf('Cake\ORM\Table', $stub->Comments);
	}

/**
 * test alternate model factories.
 *
 * @return void
 */
	public function testModelFactory() {
		$stub = new Stub();
		$stub->setProps('Articles');

		$stub->modelFactory('Test', function($name) {
			$mock = new \StdClass();
			$mock->name = $name;
			return $mock;
		});

		$result = $stub->loadModel('Magic', 'Test');
		$this->assertTrue($result);
		$this->assertInstanceOf('\StdClass', $stub->Magic);
		$this->assertEquals('Magic', $stub->Magic->name);
	}

}
