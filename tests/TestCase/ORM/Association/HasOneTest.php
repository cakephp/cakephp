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
namespace Cake\Test\TestCase\ORM\Association;

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\TypeMap;
use Cake\ORM\Association\HasOne;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Tests HasOne class
 */
class HasOneTest extends TestCase
{
    /**
     * Fixtures to load
     *
     * @var array
     */
    public $fixtures = ['core.users', 'core.profiles'];

    /**
     * @var bool
     */
    protected $listenerCalled = false;

    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->user = TableRegistry::get('Users');
        $this->profile = TableRegistry::get('Profiles');
        $this->listenerCalled = false;
    }

    /**
     * Tear down
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
    }

    /**
     * Tests that foreignKey() returns the correct configured value
     *
     * @return void
     */
    public function testForeignKey()
    {
        $assoc = new HasOne('Profiles', [
            'sourceTable' => $this->user
        ]);
        $this->assertEquals('user_id', $assoc->foreignKey());
        $this->assertEquals('another_key', $assoc->foreignKey('another_key'));
        $this->assertEquals('another_key', $assoc->foreignKey());
    }

    /**
     * Tests that the association reports it can be joined
     *
     * @return void
     */
    public function testCanBeJoined()
    {
        $assoc = new HasOne('Test');
        $this->assertTrue($assoc->canBeJoined());
    }

    /**
     * Tests that the correct join and fields are attached to a query depending on
     * the association config
     *
     * @return void
     */
    public function testAttachTo()
    {
        $config = [
            'foreignKey' => 'user_id',
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
            'property' => 'profile',
            'joinType' => 'INNER',
            'conditions' => ['Profiles.is_active' => true]
        ];
        $association = new HasOne('Profiles', $config);
        $query = $this->user->find();
        $association->attachTo($query);

        $results = $query->order('Users.id')->toArray();
        $this->assertCount(1, $results, 'Only one record because of conditions & join type');
        $this->assertSame('masters', $results[0]->Profiles['last_name']);
    }

    /**
     * Tests that it is possible to avoid fields inclusion for the associated table
     *
     * @return void
     */
    public function testAttachToNoFields()
    {
        $config = [
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
            'conditions' => ['Profiles.is_active' => true]
        ];
        $association = new HasOne('Profiles', $config);
        $query = $this->user->query();
        $association->attachTo($query, ['includeFields' => false]);
        $this->assertEmpty($query->clause('select'));
    }

    /**
     * Tests that using hasOne with a table having a multi column primary
     * key will work if the foreign key is passed
     *
     * @return void
     */
    public function testAttachToMultiPrimaryKey()
    {
        $selectTypeMap = new TypeMap([
            'Profiles.id' => 'integer',
            'id' => 'integer',
            'Profiles.first_name' => 'string',
            'first_name' => 'string',
            'Profiles.user_id' => 'integer',
            'user_id' => 'integer',
            'Profiles__first_name' => 'string',
            'Profiles__user_id' => 'integer',
            'Profiles__id' => 'integer',
            'Profiles__last_name' => 'string',
            'Profiles.last_name' => 'string',
            'last_name' => 'string',
            'Profiles__is_active' => 'boolean',
            'Profiles.is_active' => 'boolean',
            'is_active' => 'boolean',
        ]);
        $config = [
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
            'conditions' => ['Profiles.is_active' => true],
            'foreignKey' => ['user_id', 'user_site_id']
        ];

        $this->user->primaryKey(['id', 'site_id']);
        $association = new HasOne('Profiles', $config);

        $query = $this->getMockBuilder('\Cake\ORM\Query')
            ->setMethods(['join', 'select'])
            ->setConstructorArgs([null, null])
            ->getMock();
        $field1 = new IdentifierExpression('Profiles.user_id');
        $field2 = new IdentifierExpression('Profiles.user_site_id');
        $query->expects($this->once())->method('join')->with([
            'Profiles' => [
                'conditions' => new QueryExpression([
                    'Profiles.is_active' => true,
                    ['Users.id' => $field1, 'Users.site_id' => $field2],
                ], $selectTypeMap),
                'type' => 'LEFT',
                'table' => 'profiles'
            ]
        ]);
        $query->expects($this->never())->method('select');
        $association->attachTo($query, ['includeFields' => false]);
    }

    /**
     * Tests that using hasOne with a table having a multi column primary
     * key will work if the foreign key is passed
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot match provided foreignKey for "Profiles", got "(user_id)" but expected foreign key for "(id, site_id)"
     * @return void
     */
    public function testAttachToMultiPrimaryKeyMismatch()
    {
        $query = $this->getMockBuilder('\Cake\ORM\Query')
            ->setMethods(['join', 'select'])
            ->setConstructorArgs([null, null])
            ->getMock();
        $config = [
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
            'conditions' => ['Profiles.is_active' => true],
        ];
        $this->user->primaryKey(['id', 'site_id']);
        $association = new HasOne('Profiles', $config);
        $association->attachTo($query, ['includeFields' => false]);
    }

    /**
     * Test that saveAssociated() ignores non entity values.
     *
     * @return void
     */
    public function testSaveAssociatedOnlyEntities()
    {
        $mock = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['saveAssociated'])
            ->disableOriginalConstructor()
            ->getMock();
        $config = [
            'sourceTable' => $this->user,
            'targetTable' => $mock,
        ];
        $mock->expects($this->never())
            ->method('saveAssociated');

        $entity = new Entity([
            'username' => 'Mark',
            'email' => 'mark@example.com',
            'profile' => ['twitter' => '@cakephp']
        ]);

        $association = new HasOne('Profiles', $config);
        $result = $association->saveAssociated($entity);

        $this->assertSame($result, $entity);
    }

    /**
     * Tests that property is being set using the constructor options.
     *
     * @return void
     */
    public function testPropertyOption()
    {
        $config = ['propertyName' => 'thing_placeholder'];
        $association = new hasOne('Thing', $config);
        $this->assertEquals('thing_placeholder', $association->property());
    }

    /**
     * Test that plugin names are omitted from property()
     *
     * @return void
     */
    public function testPropertyNoPlugin()
    {
        $config = [
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
        ];
        $association = new HasOne('Contacts.Profiles', $config);
        $this->assertEquals('profile', $association->property());
    }

    /**
     * Tests that attaching an association to a query will trigger beforeFind
     * for the target table
     *
     * @return void
     */
    public function testAttachToBeforeFind()
    {
        $config = [
            'foreignKey' => 'user_id',
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
        ];
        $query = $this->user->query();

        $this->listenerCalled = false;
        $this->profile->eventManager()->on('Model.beforeFind', function ($event, $query, $options, $primary) {
            $this->listenerCalled = true;
            $this->assertInstanceOf('\Cake\Event\Event', $event);
            $this->assertInstanceOf('\Cake\ORM\Query', $query);
            $this->assertInstanceOf('\ArrayObject', $options);
            $this->assertFalse($primary);
        });
        $association = new HasOne('Profiles', $config);
        $association->attachTo($query);
        $this->assertTrue($this->listenerCalled, 'beforeFind event not fired.');
    }

    /**
     * Tests that attaching an association to a query will trigger beforeFind
     * for the target table
     *
     * @return void
     */
    public function testAttachToBeforeFindExtraOptions()
    {
        $config = [
            'foreignKey' => 'user_id',
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
        ];
        $this->listenerCalled = false;
        $opts = new \ArrayObject(['something' => 'more']);
        $this->profile->eventManager()->on(
            'Model.beforeFind',
            function ($event, $query, $options, $primary) use ($opts) {
                $this->listenerCalled = true;
                $this->assertInstanceOf('\Cake\Event\Event', $event);
                $this->assertInstanceOf('\Cake\ORM\Query', $query);
                $this->assertEquals($options, $opts);
                $this->assertFalse($primary);
            }
        );
        $association = new HasOne('Profiles', $config);
        $query = $this->user->find();
        $association->attachTo($query, ['queryBuilder' => function ($q) {
            return $q->applyOptions(['something' => 'more']);
        }]);
        $this->assertTrue($this->listenerCalled, 'Event not fired');
    }

    /**
     * Test cascading deletes.
     *
     * @return void
     */
    public function testCascadeDelete()
    {
        $config = [
            'dependent' => true,
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
            'conditions' => ['Profiles.is_active' => true],
            'cascadeCallbacks' => false,
        ];
        $association = new HasOne('Profiles', $config);

        $this->profile->eventManager()->on('Model.beforeDelete', function () {
            $this->fail('Callbacks should not be triggered when callbacks do not cascade.');
        });

        $entity = new Entity(['id' => 1]);
        $association->cascadeDelete($entity);

        $query = $this->profile->query()->where(['user_id' => 1]);
        $this->assertEquals(1, $query->count(), 'Left non-matching row behind');

        $query = $this->profile->query()->where(['user_id' => 3]);
        $this->assertEquals(1, $query->count(), 'other records left behind');

        $user = new Entity(['id' => 3]);
        $this->assertTrue($association->cascadeDelete($user));
        $query = $this->profile->query()->where(['user_id' => 3]);
        $this->assertEquals(0, $query->count(), 'Matching record was deleted.');
    }

    /**
     * Test cascading delete with has many.
     *
     * @return void
     */
    public function testCascadeDeleteCallbacks()
    {
        $config = [
            'dependent' => true,
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
            'conditions' => ['Profiles.is_active' => true],
            'cascadeCallbacks' => true,
        ];
        $association = new HasOne('Profiles', $config);

        $user = new Entity(['id' => 1]);
        $this->assertTrue($association->cascadeDelete($user));

        $query = $this->profile->query()->where(['user_id' => 1]);
        $this->assertEquals(1, $query->count(), 'Left non-matching row behind');

        $query = $this->profile->query()->where(['user_id' => 3]);
        $this->assertEquals(1, $query->count(), 'other records left behind');

        $user = new Entity(['id' => 3]);
        $this->assertTrue($association->cascadeDelete($user));
        $query = $this->profile->query()->where(['user_id' => 3]);
        $this->assertEquals(0, $query->count(), 'Matching record was deleted.');
    }
}
