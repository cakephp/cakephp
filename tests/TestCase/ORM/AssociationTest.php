<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\ORM\Association;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * A Test double used to assert that default tables are created
 *
 */
class TestTable extends Table {

}

/**
 * Tests Association class
 *
 */
class AssociationTest extends \Cake\TestSuite\TestCase {

/**
 * Set up
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->source = new TestTable;
		$config = [
			'className' => '\Cake\Test\TestCase\ORM\TestTable',
			'foreignKey' => 'a_key',
			'conditions' => ['field' => 'value'],
			'dependent' => true,
			'sourceTable' => $this->source,
			'joinType' => 'INNER'
		];
		$this->association = $this->getMock(
			'\Cake\ORM\Association',
			[
				'_options', 'attachTo', '_joinCondition', 'cascadeDelete', 'isOwningSide',
				'save', 'eagerLoader', 'type'
			],
			['Foo', $config]
		);
	}

/**
 * Tear down
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		TableRegistry::clear();
	}

/**
 * Tests that _options acts as a callback where subclasses can add their own
 * initialization code based on the passed configuration array
 *
 * @return void
 */
	public function testOptionsIsCalled() {
		$options = ['foo' => 'bar'];
		$this->association->expects($this->once())->method('_options')->with($options);
		$this->association->__construct('Name', $options);
	}

/**
 * Tests that name() returns the correct configure association name
 *
 * @return void
 */
	public function testName() {
		$this->assertEquals('Foo', $this->association->name());
		$this->association->name('Bar');
		$this->assertEquals('Bar', $this->association->name());
	}

/**
 * Tests that name() returns the correct configured value
 *
 * @return void
 */
	public function testForeignKey() {
		$this->assertEquals('a_key', $this->association->foreignKey());
		$this->association->foreignKey('another_key');
		$this->assertEquals('another_key', $this->association->foreignKey());
	}

/**
 * Tests that conditions() returns the correct configured value
 *
 * @return void
 */
	public function testConditions() {
		$this->assertEquals(['field' => 'value'], $this->association->conditions());
		$conds = ['another_key' => 'another value'];
		$this->association->conditions($conds);
		$this->assertEquals($conds, $this->association->conditions());
	}

/**
 * Tests that canBeJoined() returns the correct configured value
 *
 * @return void
 */
	public function testCanBeJoined() {
		$this->assertTrue($this->association->canBeJoined());
	}

/**
 * Tests that target() returns the correct Table object
 *
 * @return void
 */
	public function testTarget() {
		$table = $this->association->target();
		$this->assertInstanceOf(__NAMESPACE__ . '\TestTable', $table);

		$other = new Table;
		$this->association->target($other);
		$this->assertSame($other, $this->association->target());
	}

/**
 * Tests that source() returns the correct Table object
 *
 * @return void
 */
	public function testSource() {
		$table = $this->association->source();
		$this->assertSame($this->source, $table);

		$other = new Table;
		$this->association->source($other);
		$this->assertSame($other, $this->association->source());
	}

/**
 * Tests joinType method
 *
 * @return void
 */
	public function testJoinType() {
		$this->assertEquals('INNER', $this->association->joinType());
		$this->association->joinType('LEFT');
		$this->assertEquals('LEFT', $this->association->joinType());
	}

/**
 * Tests property method
 *
 * @return void
 */
	public function testProperty() {
		$this->assertEquals('foo', $this->association->property());
		$this->association->property('thing');
		$this->assertEquals('thing', $this->association->property());
	}

/**
 * Tests strategy method
 *
 * @return void
 */
	public function testStrategy() {
		$this->assertEquals('join', $this->association->strategy());
		$this->association->strategy('select');
		$this->assertEquals('select', $this->association->strategy());
		$this->association->strategy('subquery');
		$this->assertEquals('subquery', $this->association->strategy());
	}

/**
 * Tests that providing an invalid strategy throws an exception
 *
 * @expectedException \InvalidArgumentException
 * @return void
 */
	public function testInvalidStrategy() {
		$this->association->strategy('anotherThing');
		$this->assertEquals('subquery', $this->association->strategy());
	}

}
