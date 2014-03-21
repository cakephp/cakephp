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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Model\Behavior;

use Cake\Event\Event;
use Cake\Model\Behavior\TimestampBehavior;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Behavior test case
 */
class TimestampBehaviorTest extends TestCase {

/**
 * autoFixtures
 *
 * Don't load fixtures for all tests
 *
 * @var bool
 */
	public $autoFixtures = false;

/**
 * fixtures
 *
 * @var array
 */
	public $fixtures = [
		'core.user'
	];

/**
 * Set up the table to be used for the behavior
 *
 * @return void
 */
	public function setUp() {
		$this->loadFixtures('User');
		$this->table = TableRegistry::get('users');
		parent::setUp();
	}

/**
 * Sanity check Implemented events
 *
 * @return void
 */
	public function testImplementedEventsDefault() {
		$this->Behavior = new TimestampBehavior($this->table);

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
		$settings = ['events' => ['Something.special' => ['date_specialed' => 'always']]];
		$this->Behavior = new TimestampBehavior($this->table, $settings);

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
		$this->Behavior = new TimestampBehavior($this->table);
		$ts = new \DateTime('2000-01-01');
		$this->Behavior->timestamp($ts);

		$event = new Event('Model.beforeSave');
		$entity = new Entity(['name' => 'Foo']);

		$return = $this->Behavior->handleEvent($event, $entity);
		$this->assertTrue($return, 'Handle Event is expected to always return true');
		$this->assertSame($ts->format('U'), $entity->created, 'Created timestamp is expected to be the mocked value');
	}

/**
 * testCreatedPresent
 *
 * @return void
 */
	public function testCreatedPresent() {
		$this->Behavior = new TimestampBehavior($this->table);
		$ts = new \DateTime('2000-01-01');
		$this->Behavior->timestamp($ts);

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
		$this->Behavior = new TimestampBehavior($this->table);
		$ts = new \DateTime('2000-01-01');
		$this->Behavior->timestamp($ts);

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
		$this->Behavior = new TimestampBehavior($this->table);
		$ts = new \DateTime('2000-01-01');
		$this->Behavior->timestamp($ts);

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
		$this->Behavior = new TimestampBehavior($this->table);
		$ts = new \DateTime('2000-01-01');
		$this->Behavior->timestamp($ts);

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
 * testInvalidEventConfig
 *
 * @expectedException UnexpectedValueException
 * @expectedExceptionMessage When should be one of "always", "new" or "existing". The passed value "fat fingers" is invalid
 * @return void
 */
	public function testInvalidEventConfig() {
		$settings = ['events' => ['Model.beforeSave' => ['created' => 'fat fingers']]];
		$this->Behavior = new TimestampBehavior($this->table, $settings);

		$event = new Event('Model.beforeSave');
		$entity = new Entity(['name' => 'Foo']);
		$this->Behavior->handleEvent($event, $entity);
	}

/**
 * testGetTimestamp
 *
 * @return void
 */
	public function testGetTimestamp() {
		$this->Behavior = new TimestampBehavior($this->table);

		$return = $this->Behavior->timestamp();
		$this->assertInstanceOf(
			'DateTime',
			$return,
			'Should return a timestamp object'
		);

		$now = time();
		$ts = $return->getTimestamp();

		$this->assertLessThan(3, abs($now - $ts), "Timestamp is expected to within 3 seconds of the current timestamp");

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

		$initialValue = $this->Behavior->timestamp();
		$postValue = $this->Behavior->timestamp();

		$this->assertSame(
			$initialValue,
			$postValue,
			'The timestamp should be exactly the same object'
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

		$initialValue = $this->Behavior->timestamp();
		$postValue = $this->Behavior->timestamp(null, true);

		$this->assertNotSame(
			$initialValue,
			$postValue,
			'The timestamp should be a different object if refreshTimestamp is truthy'
		);
	}

/**
 * testSetTimestampExplicit
 *
 * @return void
 */
	public function testSetTimestampExplicit() {
		$this->Behavior = new TimestampBehavior($this->table);

		$ts = new \DateTime();
		$this->Behavior->timestamp($ts);
		$return = $this->Behavior->timestamp();

		$this->assertSame(
			$ts,
			$return,
			'Should return the same value as initially set'
		);
	}

/**
 * testTouch
 *
 * @return void
 */
	public function testTouch() {
		$this->Behavior = new TimestampBehavior($this->table);
		$ts = new \DateTime('2000-01-01');
		$this->Behavior->timestamp($ts);

		$entity = new Entity(['username' => 'timestamp test']);
		$return = $this->Behavior->touch($entity);
		$this->assertTrue($return, 'touch is expected to return true if it sets a field value');
		$this->assertSame(
			$ts->format('Y-m-d H:i:s'),
			$entity->modified->format('Y-m-d H:i:s'),
			'Modified field is expected to be updated'
		);
		$this->assertNull($entity->created, 'Created field is NOT expected to change');
	}

/**
 * testTouchNoop
 *
 * @return void
 */
	public function testTouchNoop() {
		$config = [
			'events' => [
				'Model.beforeSave' => [
					'created' => 'new',
				]
			]
		];

		$this->Behavior = new TimestampBehavior($this->table, $config);
		$ts = new \DateTime('2000-01-01');
		$this->Behavior->timestamp($ts);

		$entity = new Entity(['username' => 'timestamp test']);
		$return = $this->Behavior->touch($entity);
		$this->assertFalse($return, 'touch is expected to do nothing and return false');
		$this->assertNull($entity->modified, 'Modified field is NOT expected to change');
		$this->assertNull($entity->created, 'Created field is NOT expected to change');
	}

/**
 * testTouchCustomEvent
 *
 * @return void
 */
	public function testTouchCustomEvent() {
		$settings = ['events' => ['Something.special' => ['date_specialed' => 'always']]];
		$this->Behavior = new TimestampBehavior($this->table, $settings);
		$ts = new \DateTime('2000-01-01');
		$this->Behavior->timestamp($ts);

		$entity = new Entity(['username' => 'timestamp test']);
		$return = $this->Behavior->touch($entity, 'Something.special');
		$this->assertTrue($return, 'touch is expected to return true if it sets a field value');
		$this->assertSame(
			$ts->format('Y-m-d H:i:s'),
			$entity->date_specialed->format('Y-m-d H:i:s'),
			'Modified field is expected to be updated'
		);
		$this->assertNull($entity->created, 'Created field is NOT expected to change');
	}

/**
 * Test that calling save, triggers an insert including the created and updated field values
 *
 * @return void
 */
	public function testSaveTriggersInsert() {
		$this->loadFixtures('User');

		$table = TableRegistry::get('users');
		$table->addBehavior('Timestamp', [
			'events' => [
				'Model.beforeSave' => [
					'created' => 'new',
					'updated' => 'always'
				]
			]
		]);

		$entity = new Entity(['username' => 'timestamp test']);
		$return = $table->save($entity);
		$this->assertSame($entity, $return, 'The returned object is expected to be the same entity object');

		$row = $table->find('all')->where(['id' => $entity->id])->first();

		$now = time();

		$storedValue = $row->created;
		$this->assertLessThan(3, abs($storedValue - $now), "The stored created timestamp is expected to within 3 seconds of the current timestamp");

		$storedValue = $row->updated->getTimestamp();
		$this->assertLessThan(3, abs($storedValue - $now), "The stored updated timestamp is expected to within 3 seconds of the current timestamp");
	}
}
