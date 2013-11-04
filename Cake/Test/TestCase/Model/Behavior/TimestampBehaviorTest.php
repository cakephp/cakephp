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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Model\Behavior;

use Cake\Event\Event;
use Cake\Model\Behavior\TimestampBehavior;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;

/**
 * Behavior test case
 */
class TimestampBehaviorTest extends TestCase {

/**
 * Sanity check Implemented events
 *
 * @return void
 */
	public function testImplementedEventsDefault() {
		$table = $this->getMock('Cake\ORM\Table');
		$this->Behavior = new TimestampBehavior($table);

		$expected = [
			'Model.beforeSave' => 'handleEvent'
		];
		$this->assertEquals($expected, $this->Behavior->implementedEvents());
	}

/**
 * testImplementedEventsCustom
 *
 * The behavior allows for handling any event - test an example
 *
 * @return void
 */
	public function testImplementedEventsCustom() {
		$table = $this->getMock('Cake\ORM\Table');
		$settings = ['events' => ['Something.special' => ['date_specialed' => true]]];
		$this->Behavior = new TimestampBehavior($table, $settings);

		$expected = [
			'Something.special' => 'handleEvent'
		];
		$this->assertEquals($expected, $this->Behavior->implementedEvents());
	}

/**
 * testCreatedAbsent
 *
 * @return void
 */
	public function testCreatedAbsent() {
		$table = $this->getMock('Cake\ORM\Table');
		$this->Behavior = new TimestampBehavior($table, ['refreshTimestamp' => false]);
		$ts = new \DateTime('2000-01-01');
		$this->Behavior->setTimestamp($ts);

		$event = new Event('Model.beforeSave');
		$entity = new Entity(['name' => 'Foo']);

		$return = $this->Behavior->handleEvent($event, $entity);
		$this->assertTrue($return, 'Handle Event is expected to always return true');
		$this->assertSame($ts, $entity->created, 'Created timestamp is expected to be the mocked value');
	}

/**
 * testCreatedPresent
 *
 * @return void
 */
	public function testCreatedPresent() {
		$table = $this->getMock('Cake\ORM\Table');
		$this->Behavior = new TimestampBehavior($table, ['refreshTimestamp' => false]);
		$ts = new \DateTime('2000-01-01');
		$this->Behavior->setTimestamp($ts);

		$event = new Event('Model.beforeSave');
		$existingValue = new \DateTime('2011-11-11');
		$entity = new Entity(['name' => 'Foo', 'created' => $existingValue]);

		$return = $this->Behavior->handleEvent($event, $entity);
		$this->assertTrue($return, 'Handle Event is expected to always return true');
		$this->assertSame($existingValue, $entity->created, 'Created timestamp is expected to be unchanged');
	}

/**
 * testCreatedNotNew
 *
 * @return void
 */
	public function testCreatedNotNew() {
		$table = $this->getMock('Cake\ORM\Table');
		$this->Behavior = new TimestampBehavior($table, ['refreshTimestamp' => false]);
		$ts = new \DateTime('2000-01-01');
		$this->Behavior->setTimestamp($ts);

		$event = new Event('Model.beforeSave');
		$entity = new Entity(['name' => 'Foo']);
		$entity->isNew(false);

		$return = $this->Behavior->handleEvent($event, $entity);
		$this->assertTrue($return, 'Handle Event is expected to always return true');
		$this->assertNull($entity->created, 'Created timestamp is expected to be untouched if the entity is not new');
	}

/**
 * testModifiedAbsent
 *
 * @return void
 */
	public function testModifiedAbsent() {
		$table = $this->getMock('Cake\ORM\Table');
		$this->Behavior = new TimestampBehavior($table, ['refreshTimestamp' => false]);
		$ts = new \DateTime('2000-01-01');
		$this->Behavior->setTimestamp($ts);

		$event = new Event('Model.beforeSave');
		$entity = new Entity(['name' => 'Foo']);
		$entity->isNew(false);

		$return = $this->Behavior->handleEvent($event, $entity);
		$this->assertTrue($return, 'Handle Event is expected to always return true');
		$this->assertSame($ts, $entity->modified, 'Modified timestamp is expected to be the mocked value');
	}

/**
 * testModifiedPresent
 *
 * @return void
 */
	public function testModifiedPresent() {
		$table = $this->getMock('Cake\ORM\Table');
		$this->Behavior = new TimestampBehavior($table, ['refreshTimestamp' => false]);
		$ts = new \DateTime('2000-01-01');
		$this->Behavior->setTimestamp($ts);

		$event = new Event('Model.beforeSave');
		$existingValue = new \DateTime('2011-11-11');
		$entity = new Entity(['name' => 'Foo', 'modified' => $existingValue]);
		$entity->clean();
		$entity->isNew(false);

		$return = $this->Behavior->handleEvent($event, $entity);
		$this->assertTrue($return, 'Handle Event is expected to always return true');
		$this->assertSame($ts, $entity->modified, 'Modified timestamp is expected to be updated');
	}

/**
 * testGetTimestamp
 *
 * @return void
 */
	public function testGetTimestamp() {
		$table = $this->getMock('Cake\ORM\Table');
		$this->Behavior = new TimestampBehavior($table);

		$property = new \ReflectionProperty('Cake\Model\Behavior\TimestampBehavior', '_ts');
		$property->setAccessible(true);

		$this->assertNull($property->getValue($this->Behavior), 'Should be null be default');

		$return = $this->Behavior->getTimestamp();
		$this->assertInstanceOf(
			'DateTime',
			$return,
			'After calling for the first time, should be a date time object'
		);

		return $this->Behavior;
	}

/**
 * testGetTimestampPersists
 *
 * @depends testGetTimestamp
 * @return void
 */
	public function testGetTimestampPersists($behavior) {
		$this->Behavior = $behavior;

		$property = new \ReflectionProperty('Cake\Model\Behavior\TimestampBehavior', '_ts');
		$property->setAccessible(true);

		$initialValue = $property->getValue($this->Behavior);
		$this->Behavior->getTimestamp();
		$postValue = $property->getValue($this->Behavior);

		$this->assertSame(
			$initialValue,
			$postValue,
			'The timestamp should be exactly the same value'
		);
	}

/**
 * testGetTimestampRefreshes
 *
 * @depends testGetTimestamp
 * @return void
 */
	public function testGetTimestampRefreshes($behavior) {
		$this->Behavior = $behavior;

		$property = new \ReflectionProperty('Cake\Model\Behavior\TimestampBehavior', '_ts');
		$property->setAccessible(true);

		$initialValue = $property->getValue($this->Behavior);
		$this->Behavior->getTimestamp(true);
		$postValue = $property->getValue($this->Behavior);

		$this->assertNotSame(
			$initialValue,
			$postValue,
			'The timestamp should be a different object if refreshTimestamp is truthy'
		);
	}

/**
 * testSetTimestampDefault
 *
 * @return void
 */
	public function testSetTimestampDefault() {
		$table = $this->getMock('Cake\ORM\Table');
		$this->Behavior = new TimestampBehavior($table);

		$this->Behavior->setTimestamp();

		$property = new \ReflectionProperty('Cake\Model\Behavior\TimestampBehavior', '_ts');
		$property->setAccessible(true);
		$set = $property->getValue($this->Behavior);

		$this->assertInstanceOf(
			'DateTime',
			$set,
			'After calling for the first time, should be a date time object'
		);
	}

/**
 * testSetTimestampExplicit
 *
 * @return void
 */
	public function testSetTimestampExplicit() {
		$table = $this->getMock('Cake\ORM\Table');
		$this->Behavior = new TimestampBehavior($table);

		$ts = new \DateTime();
		$this->Behavior->setTimestamp($ts);

		$property = new \ReflectionProperty('Cake\Model\Behavior\TimestampBehavior', '_ts');
		$property->setAccessible(true);
		$set = $property->getValue($this->Behavior);

		$this->assertInstanceOf(
			'DateTime',
			$set,
			'After calling for the first time, should be a date time object'
		);

		$this->assertSame(
			$ts,
			$set,
			'Should have set the same object passed in'
		);
	}
}
