<?php
declare(strict_types=1);

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

use Cake\Database\TypeFactory;
use Cake\Event\Event;
use Cake\I18n\FrozenTime;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use DateTime as NativeDateTime;
use RuntimeException;
use UnexpectedValueException;

/**
 * Behavior test case
 */
class TimestampBehaviorTest extends TestCase
{
    /**
     * fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'core.Users',
    ];

    /**
     * @var \Cake\ORM\Behavior\TimestampBehavior
     */
    protected $Behavior;

    /**
     * Sanity check Implemented events
     */
    public function testImplementedEventsDefault(): void
    {
        $table = $this->getTableInstance();
        $this->Behavior = new TimestampBehavior($table);

        $expected = [
            'Model.beforeSave' => 'handleEvent',
        ];
        $this->assertEquals($expected, $this->Behavior->implementedEvents());
    }

    /**
     * testImplementedEventsCustom
     *
     * The behavior allows for handling any event - test an example
     */
    public function testImplementedEventsCustom(): void
    {
        $table = $this->getTableInstance();
        $settings = ['events' => ['Something.special' => ['date_specialed' => 'always']]];
        $this->Behavior = new TimestampBehavior($table, $settings);

        $expected = [
            'Something.special' => 'handleEvent',
        ];
        $this->assertEquals($expected, $this->Behavior->implementedEvents());
    }

    /**
     * testCreatedAbsent
     *
     * @triggers Model.beforeSave
     */
    public function testCreatedAbsent(): void
    {
        $table = $this->getTableInstance();
        $this->Behavior = new TimestampBehavior($table);
        $ts = new NativeDateTime('2000-01-01');
        $this->Behavior->timestamp($ts);

        $event = new Event('Model.beforeSave');
        $entity = new Entity(['name' => 'Foo']);

        $return = $this->Behavior->handleEvent($event, $entity);
        $this->assertTrue($return, 'Handle Event is expected to always return true');
        $this->assertInstanceOf(FrozenTime::class, $entity->created);
        $this->assertSame($ts->format('c'), $entity->created->format('c'), 'Created timestamp is not the same');
    }

    /**
     * testCreatedPresent
     *
     * @triggers Model.beforeSave
     */
    public function testCreatedPresent(): void
    {
        $table = $this->getTableInstance();
        $this->Behavior = new TimestampBehavior($table);
        $ts = new NativeDateTime('2000-01-01');
        $this->Behavior->timestamp($ts);

        $event = new Event('Model.beforeSave');
        $existingValue = new NativeDateTime('2011-11-11');
        $entity = new Entity(['name' => 'Foo', 'created' => $existingValue]);

        $return = $this->Behavior->handleEvent($event, $entity);
        $this->assertTrue($return, 'Handle Event is expected to always return true');
        $this->assertSame($existingValue, $entity->created, 'Created timestamp is expected to be unchanged');
    }

    /**
     * testCreatedNotNew
     *
     * @triggers Model.beforeSave
     */
    public function testCreatedNotNew(): void
    {
        $table = $this->getTableInstance();
        $this->Behavior = new TimestampBehavior($table);
        $ts = new NativeDateTime('2000-01-01');
        $this->Behavior->timestamp($ts);

        $event = new Event('Model.beforeSave');
        $entity = new Entity(['name' => 'Foo']);
        $entity->setNew(false);

        $return = $this->Behavior->handleEvent($event, $entity);
        $this->assertTrue($return, 'Handle Event is expected to always return true');
        $this->assertNull($entity->created, 'Created timestamp is expected to be untouched if the entity is not new');
    }

    /**
     * testModifiedAbsent
     *
     * @triggers Model.beforeSave
     */
    public function testModifiedAbsent(): void
    {
        $table = $this->getTableInstance();
        $this->Behavior = new TimestampBehavior($table);
        $ts = new NativeDateTime('2000-01-01');
        $this->Behavior->timestamp($ts);

        $event = new Event('Model.beforeSave');
        $entity = new Entity(['name' => 'Foo']);
        $entity->setNew(false);

        $return = $this->Behavior->handleEvent($event, $entity);
        $this->assertTrue($return, 'Handle Event is expected to always return true');
        $this->assertInstanceOf(FrozenTime::class, $entity->modified);
        $this->assertSame($ts->format('c'), $entity->modified->format('c'), 'Modified timestamp is not the same');
    }

    /**
     * testModifiedPresent
     *
     * @triggers Model.beforeSave
     */
    public function testModifiedPresent(): void
    {
        $table = $this->getTableInstance();
        $this->Behavior = new TimestampBehavior($table);
        $ts = new NativeDateTime('2000-01-01');
        $this->Behavior->timestamp($ts);

        $event = new Event('Model.beforeSave');
        $existingValue = new NativeDateTime('2011-11-11');
        $entity = new Entity(['name' => 'Foo', 'modified' => $existingValue]);
        $entity->clean();
        $entity->setNew(false);

        $return = $this->Behavior->handleEvent($event, $entity);
        $this->assertTrue($return, 'Handle Event is expected to always return true');
        $this->assertInstanceOf(FrozenTime::class, $entity->modified);
        $this->assertSame($ts->format('c'), $entity->modified->format('c'), 'Modified timestamp is expected to be updated');
    }

    /**
     * test that timestamp creation doesn't fail on missing columns
     */
    public function testModifiedMissingColumn(): void
    {
        $table = $this->getTableInstance();
        $table->getSchema()->removeColumn('created')->removeColumn('modified');
        $this->Behavior = new TimestampBehavior($table);
        $ts = new NativeDateTime('2000-01-01');
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
     * @triggers Model.beforeSave
     */
    public function testUseImmutable(): void
    {
        $this->deprecated(function () {
            $table = $this->getTableInstance();
            $this->Behavior = new TimestampBehavior($table);
            $entity = new Entity();
            $event = new Event('Model.beforeSave');

            $entity->clean();
            $this->Behavior->handleEvent($event, $entity);
            $this->assertInstanceOf('Cake\I18n\FrozenTime', $entity->modified);

            TypeFactory::build('timestamp')->useMutable();
            $entity->clean();
            $this->Behavior->handleEvent($event, $entity);
            $this->assertInstanceOf('Cake\I18n\Time', $entity->modified);
            // Revert back to using immutable class to avoid causing problems in
            // other test cases when running full test suite.
            TypeFactory::build('timestamp')->useImmutable();
        });
    }

    /**
     * tests using non-DateTimeType throws runtime exception
     */
    public function testNonDateTimeTypeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TimestampBehavior only supports columns of type DateTimeType.');

        $table = $this->getTableInstance();
        $this->Behavior = new TimestampBehavior($table, [
            'events' => [
                'Model.beforeSave' => [
                    'timestamp_str' => 'always',
                ],
            ],
        ]);

        $entity = new Entity();
        $event = new Event('Model.beforeSave');
        $this->Behavior->handleEvent($event, $entity);
        $this->assertIsString($entity->timestamp_str);
    }

    /**
     * testInvalidEventConfig
     *
     * @triggers Model.beforeSave
     */
    public function testInvalidEventConfig(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('When should be one of "always", "new" or "existing". The passed value "fat fingers" is invalid');
        $table = $this->getTableInstance();
        $settings = ['events' => ['Model.beforeSave' => ['created' => 'fat fingers']]];
        $this->Behavior = new TimestampBehavior($table, $settings);

        $event = new Event('Model.beforeSave');
        $entity = new Entity(['name' => 'Foo']);
        $this->Behavior->handleEvent($event, $entity);
    }

    /**
     * testGetTimestamp
     */
    public function testGetTimestamp(): void
    {
        $table = $this->getTableInstance();
        $behavior = new TimestampBehavior($table);

        $return = $behavior->timestamp();
        $this->assertInstanceOf(
            'DateTimeImmutable',
            $return,
            'Should return a timestamp object'
        );

        $now = FrozenTime::now();
        $this->assertEquals($now, $return);
    }

    /**
     * testGetTimestampPersists
     */
    public function testGetTimestampPersists(): void
    {
        $table = $this->getTableInstance();
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
     */
    public function testGetTimestampRefreshes(): void
    {
        $table = $this->getTableInstance();
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
     */
    public function testSetTimestampExplicit(): void
    {
        $table = $this->getTableInstance();
        $this->Behavior = new TimestampBehavior($table);

        $ts = new NativeDateTime();
        $this->Behavior->timestamp($ts);
        $return = $this->Behavior->timestamp();

        $this->assertSame(
            $ts->format('c'),
            $return->format('c'),
            'Should return the same value as initially set'
        );
    }

    /**
     * testTouch
     */
    public function testTouch(): void
    {
        $table = $this->getTableInstance();
        $this->Behavior = new TimestampBehavior($table);
        $ts = new NativeDateTime('2000-01-01');
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
     */
    public function testTouchNoop(): void
    {
        $table = $this->getTableInstance();
        $config = [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                ],
            ],
        ];

        $this->Behavior = new TimestampBehavior($table, $config);
        $ts = new NativeDateTime('2000-01-01');
        $this->Behavior->timestamp($ts);

        $entity = new Entity(['username' => 'timestamp test']);
        $return = $this->Behavior->touch($entity);
        $this->assertFalse($return, 'touch is expected to do nothing and return false');
        $this->assertNull($entity->modified, 'Modified field is NOT expected to change');
        $this->assertNull($entity->created, 'Created field is NOT expected to change');
    }

    /**
     * testTouchCustomEvent
     */
    public function testTouchCustomEvent(): void
    {
        $table = $this->getTableInstance();
        $settings = ['events' => ['Something.special' => ['date_specialed' => 'always']]];
        $this->Behavior = new TimestampBehavior($table, $settings);
        $ts = new NativeDateTime('2000-01-01');
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
     */
    public function testSaveTriggersInsert(): void
    {
        $table = $this->getTableLocator()->get('users');
        $table->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                    'updated' => 'always',
                ],
            ],
        ]);

        $entity = new Entity(['username' => 'timestamp test']);
        $return = $table->save($entity);
        $this->assertSame($entity, $return, 'The returned object is expected to be the same entity object');

        $row = $table->find('all')->where(['id' => $entity->id])->first();

        $now = FrozenTime::now();
        $this->assertSame($now->toDateTimeString(), $row->created->toDateTimeString());
        $this->assertSame($now->toDateTimeString(), $row->updated->toDateTimeString());
    }

    /**
     * Helper method to get Table instance with created/modified column
     */
    protected function getTableInstance(): Table
    {
        $schema = [
            'created' => ['type' => 'datetime'],
            'modified' => ['type' => 'timestamp'],
            'date_specialed' => ['type' => 'datetime'],
            'timestamp_str' => ['type' => 'string'],
        ];

        return new Table([
            'alias' => 'Articles',
            'schema' => $schema,
        ]);
    }
}
