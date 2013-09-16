<?php
/**
 * PHP Version 5.4
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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\ORM;

use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;

/**
 * Entity test case.
 */
class EntityTest extends TestCase {

/**
 * Tests setting a single property in an entity without custom setters
 *
 * @return void
 */
	public function testSetOneParamNoSetters() {
		$entity = new Entity;
		$entity->set('foo', 'bar');
		$this->assertEquals('bar', $entity->foo);

		$entity->set('foo', 'baz');
		$this->assertEquals('baz', $entity->foo);

		$entity->set('id', 1);
		$this->assertSame(1, $entity->id);
	}

/**
 * Tests setting multiple properties without custom setters
 *
 * @return void
 */
	public function testSetMultiplePropertiesNOSetters() {
		$entity = new Entity;
		$entity->set(['foo' => 'bar', 'id' => 1]);
		$this->assertEquals('bar', $entity->foo);
		$this->assertSame(1, $entity->id);

		$entity->set(['foo' => 'baz', 'id' => 2, 'thing' => 3]);
		$this->assertEquals('baz', $entity->foo);
		$this->assertSame(2, $entity->id);
		$this->assertSame(3, $entity->thing);
	}

/**
 * Tests setting a single property using a setter function
 *
 * @return void
 */
	public function testSetOneParamWithSetter() {
		$entity = $this->getMock('\Cake\ORM\Entity', ['setName']);
		$entity->expects($this->once())->method('setName')
			->with('Jones')
			->will($this->returnCallback(function($name) {
				$this->assertEquals('Jones', $name);
				return 'Dr. ' . $name;
			}));
		$entity->set('name', 'Jones');
		$this->assertEquals('Dr. Jones', $entity->name);
	}

/**
 * Tests setting multiple properties using a setter function
 *
 * @return void
 */
	public function testMultipleWithSetter() {
		$entity = $this->getMock('\Cake\ORM\Entity', ['setName', 'setStuff']);
		$entity->expects($this->once())->method('setName')
			->with('Jones')
			->will($this->returnCallback(function($name) {
				$this->assertEquals('Jones', $name);
				return 'Dr. ' . $name;
			}));
		$entity->expects($this->once())->method('setStuff')
			->with(['a', 'b'])
			->will($this->returnCallback(function($stuff) {
				$this->assertEquals(['a', 'b'], $stuff);
				return ['c', 'd'];
			}));
		$entity->set(['name' => 'Jones', 'stuff' => ['a', 'b']]);
		$this->assertEquals('Dr. Jones', $entity->name);
		$this->assertEquals(['c', 'd'], $entity->stuff);
	}

}
