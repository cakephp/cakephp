<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM\Behavior;

use Cake\Database\Type;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Error\Deprecated;

/**
 * Behavior test case
 */
class TimestampBehaviorTest extends TestCase
{

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
        'core.users'
    ];

    /**
     * Sanity check Implemented events
     *
     * @return void
     */
    public function testImplementedEventsDefault()
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
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
    public function testImplementedEventsCustom()
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
        $settings = ['events' => ['Something.special' => ['date_specialed' => 'always']]];
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
     * @triggers Model.beforeSave
     */
    public function testCreatedAbsent()
    {
        $table = $this->getTable();
        $this->Behavior = new TimestampBehavior($table);
        $ts = new \DateTime('2000-01-01');
        $this->Behavior->timestamp($ts);

        $event = new Event('Model.beforeSave');
        $entity = new Entity(['name' => 'Foo']);

        $return = $this->Behavior->handleEvent($event, $entity);
        $this->assertTrue($return, 'Handle Event is expected to always return true');
        $this->assertInstanceOf('Cake\I18n\Time', $entity->created);
        $this->assertSame($ts->format('c'), $entity->created->format('c'), 'Created timestamp is not the same');
    }

    /**
     * testCreatedPresent
     *
     * @return void
     * @triggers Model.beforeSave
     */
    public function testCreatedPresent()
    {
        $table = $this->getTable();
        $this->Behavior = new TimestampBehavior($table);
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
     * @triggers Model.beforeSave
     */
    public function testCreatedNotNew()
    {
        $table = $this->getTable();
        $this->Behavior = new TimestampBehavior($table);
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
     * @triggers Model.beforeSave
     */
    public function testModifiedAbsent()
    {
        $table = $this->getTable();
        $this->Behavior = new TimestampBehavior($table);
        $ts = new \DateTime('2000-01-01');
        $this->Behavior->timestamp($ts);

        $event = new Event('Model.beforeSave');
        $entity = new Entity(['name' => 'Foo']);
        $entity->isNew(false);

        $return = $this->Behavior->handleEvent($event, $entity);
        $this->assertTrue($return, 'Handle Event is expected to always return true');
        $this->assertInstanceOf('Cake\I18n\Time', $entity->modified);
        $this->assertSame($ts->format('c'), $entity->modified->format('c'), 'Modified timestamp is not the same');
    }

    /**
     * testModifiedPresent
     *
     * @return void
     * @triggers Model.beforeSave
     */
    public function testModifiedPresent()
    {
        $table = $this->getTable();
        $this->Behavior = new TimestampBehavior($table);
        $ts = new \DateTime('2000-01-01');
        $this->Behavior->timestamp($ts);

        $event = new Event('Model.beforeSave');
        $existingValue = new \DateTime('2011-11-11');
        $entity = new Entity(['name' => 'Foo', 'modified' => $existingValue]);
        $entity->clean();
        $entity->isNew(false);

        $return = $this->Behavior->handleEvent($event, $entity);
        $this->assertTrue($return, 'Handle Event is expected to always return true');
        $this->assertInstanceOf('Cake\I18n\Time', $entity->modified);
        $this->assertSame($ts->format('c'), $entity->modified->format('c'), 'Modified timestamp is expected to be updated');
    }

    /**
     * test that timestamp creation doesn't fail on missing columns
     *
     * @return void
     */
    public function testModifiedMissingColumn()
    {
        $table = $this->getTable();
        $table->getSchema()->removeColumn('created')->removeColumn('modified');
        $this->Behavior = new TimestampBehavior($table);
        $ts = new \DateTime('2000-01-01');
        $this->Behavior->timestamp($ts);

        $event = new Event('Model.beforeSave');
        $entity = new Entity(['name' => 'Foo']);

        $return = $this->Behavior->handleEvent($event, $entity);
        $this->assertTrue($return, 'Handle Event is expected to always return true');
        $this->assertNull($entity->created);
        $this->assertNull($entity->modified);
    }

    /**
     * testUseImmutable
     *
     * @return void
     * @triggers Model.beforeSave
     */
    public function testUseImmutable()
    {
        $table = $this->getTable();
        $this->Behavior = new TimestampBehavior($table);
        $entity = new Entity();
        $event = new Event('Model.beforeSave');

        Type::build('timestamp')->useImmutable();
        $entity->clean();
        $this->Behavior->handleEvent($event, $entity);
        $this->assertInstanceOf('Cake\I18n\FrozenTime', $entity->modified);

        Type::build('timestamp')->useMutable();
        $entity->clean();
        $this->Behavior->handleEvent($event, $entity);
        $this->assertInstanceOf('Cake\I18n\Time', $entity->modified);
    }

    /**
     * tests using non-DateTimeType throws deprecation warning
     *
     * @return void
     */
    public function testNonDateTimeTypeDeprecated()
    {
        $this->expectException(Deprecated::class);
        $this->expectExceptionMessage('TimestampBehavior support for column types other than DateTimeType will be removed in 4.0.');

        $table = $this->getTable();
        $this->Behavior = new TimestampBehavior($table, [
            'events' => [
                'Model.beforeSave' => [
                    'timestamp_str' => 'always',
                ]
            ],
        ]);

        $entity = new Entity();
        $event = new Event('Model.beforeSave');
        $this->Behavior->handleEvent($event, $entity);
        $this->assertInternalType('string', $entity->timestamp_str);
    }

    /**
     * testInvalidEventConfig
     *
     * @return void
     * @triggers Model.beforeSave
     */
    public function testInvalidEventConfig()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('When should be one of "always", "new" or "existing". The passed value "fat fingers" is invalid');
        $table = $this->getTable();
        $settings = ['events' => ['Model.beforeSave' => ['created' => 'fat fingers']]];
        $this->Behavior = new TimestampBehavior($table, $settings);

        $event = new Event('Model.beforeSave');
        $entity = new Entity(['name' => 'Foo']);
        $this->Behavior->handleEvent($event, $entity);
    }

    /**
     * testGetTimestamp
     *
     * @return void
     */
    public function testGetTimestamp()
    {
        $table = $this->getTable();
        $behavior = new TimestampBehavior($table);

        $return = $behavior->timestamp();
        $this->assertInstanceOf(
            'DateTime',
            $return,
            'Should return a timestamp object'
        );

        $now = Time::now();
        $this->assertEquals($now, $return);
    }

    /**
     * testGetTimestampPersists
     *
     * @return void
     */
    public function testGetTimestampPersists()
    {
        $table = $this->getTable();
        $behavior = new TimestampBehavior($table);

        $initialValue = $behavior->timestamp();
        $postValue = $behavior->timestamp();

        $this->assertSame(
            $initialValue,
            $postValue,
            'The timestamp should be exactly the same object'
        );
    }

    /**
     * testGetTimestampRefreshes
     *
     * @return void
     */
    public function testGetTimestampRefreshes()
    {
        $table = $this->getTable();
        $behavior = new TimestampBehavior($table);

        $initialValue = $behavior->timestamp();
        $postValue = $behavior->timestamp(null, true);

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
    public function testSetTimestampExplicit()
    {
        $table = $this->getTable();
        $this->Behavior = new TimestampBehavior($table);

        $ts = new \DateTime();
        $this->Behavior->timestamp($ts);
        $return = $this->Behavior->timestamp();

        $this->assertEquals(
            $ts->format('c'),
            $return->format('c'),
            'Should return the same value as initially set'
        );
    }

    /**
     * testTouch
     *
     * @return void
     */
    public function testTouch()
    {
        $table = $this->getTable();
        $this->Behavior = new TimestampBehavior($table);
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
    public function testTouchNoop()
    {
        $table = $this->getTable();
        $config = [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                ]
            ]
        ];

        $this->Behavior = new TimestampBehavior($table, $config);
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
    public function testTouchCustomEvent()
    {
        $table = $this->getTable();
        $settings = ['events' => ['Something.special' => ['date_specialed' => 'always']]];
        $this->Behavior = new TimestampBehavior($table, $settings);
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
    public function testSaveTriggersInsert()
    {
        $this->loadFixtures('Users');

        $table = $this->getTableLocator()->get('users');
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

        $now = Time::now();
        $this->assertEquals($now->toDateTimeString(), $row->created->toDateTimeString());
        $this->assertEquals($now->toDateTimeString(), $row->updated->toDateTimeString());
    }

    /**
     * Helper method to get Table instance with created/modified column
     *
     * @return \Cake\ORM\Table
     */
    protected function getTable()
    {
        $schema = [
            'created' => ['type' => 'datetime'],
            'modified' => ['type' => 'timestamp'],
            'date_specialed' => ['type' => 'datetime'],
            'timestamp_str' => ['type' => 'string'],
        ];
        $table = new Table(['schema' => $schema]);

        return $table;
    }
}
