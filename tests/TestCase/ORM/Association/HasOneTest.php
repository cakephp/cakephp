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
namespace Cake\Test\TestCase\ORM\Association;

use ArrayObject;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\TypeMap;
use Cake\ORM\Association\HasOne;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * Tests HasOne class
 */
class HasOneTest extends TestCase
{
    /**
     * Fixtures to load
     *
     * @var array<string>
     */
    protected $fixtures = ['core.Articles', 'core.Authors', 'core.NullableAuthors', 'core.Users', 'core.Profiles'];

    /**
     * @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $user;

    /**
     * @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $profile;

    /**
     * @var bool
     */
    protected $listenerCalled = false;

    /**
     * Set up
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->user = $this->getTableLocator()->get('Users');
        $this->profile = $this->getTableLocator()->get('Profiles');
        $this->listenerCalled = false;
    }

    /**
     * Tests that setForeignKey() returns the correct configured value
     */
    public function testSetForeignKey(): void
    {
        $assoc = new HasOne('Profiles', [
            'sourceTable' => $this->user,
        ]);
        $this->assertSame('user_id', $assoc->getForeignKey());
        $this->assertEquals($assoc, $assoc->setForeignKey('another_key'));
        $this->assertSame('another_key', $assoc->getForeignKey());
    }

    /**
     * Tests that the association reports it can be joined
     */
    public function testCanBeJoined(): void
    {
        $assoc = new HasOne('Test');
        $this->assertTrue($assoc->canBeJoined());
    }

    /**
     * Tests that the correct join and fields are attached to a query depending on
     * the association config
     */
    public function testAttachTo(): void
    {
        $config = [
            'foreignKey' => 'user_id',
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
            'property' => 'profile',
            'joinType' => 'INNER',
            'conditions' => ['Profiles.is_active' => true],
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
     */
    public function testAttachToNoFields(): void
    {
        $config = [
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
            'conditions' => ['Profiles.is_active' => true],
        ];
        $association = new HasOne('Profiles', $config);
        $query = $this->user->query();
        $association->attachTo($query, ['includeFields' => false]);
        $this->assertEmpty($query->clause('select'));
    }

    /**
     * Tests that using hasOne with a table having a multi column primary
     * key will work if the foreign key is passed
     */
    public function testAttachToMultiPrimaryKey(): void
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
            'foreignKey' => ['user_id', 'user_site_id'],
        ];

        $this->user->setPrimaryKey(['id', 'site_id']);
        $association = new HasOne('Profiles', $config);

        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->onlyMethods(['join'])
            ->disableOriginalConstructor()
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
                'table' => 'profiles',
            ],
        ]);
        $association->attachTo($query);
    }

    /**
     * Tests that using hasOne with a table having a multi column primary
     * key will work if the foreign key is passed
     */
    public function testAttachToMultiPrimaryKeyMismatch(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot match provided foreignKey for "Profiles", got "(user_id)" but expected foreign key for "(id, site_id)"');
        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->onlyMethods(['join', 'select'])
            ->disableOriginalConstructor()
            ->getMock();
        $config = [
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
            'conditions' => ['Profiles.is_active' => true],
        ];
        $this->user->setPrimaryKey(['id', 'site_id']);
        $association = new HasOne('Profiles', $config);
        $association->attachTo($query, ['includeFields' => false]);
    }

    /**
     * Test that saveAssociated() ignores non entity values.
     */
    public function testSaveAssociatedOnlyEntities(): void
    {
        $mock = $this->getMockBuilder('Cake\ORM\Table')
            ->addMethods(['saveAssociated'])
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
            'profile' => ['twitter' => '@cakephp'],
        ]);

        $association = new HasOne('Profiles', $config);
        $result = $association->saveAssociated($entity);

        $this->assertSame($result, $entity);
    }

    /**
     * Tests that property is being set using the constructor options.
     */
    public function testPropertyOption(): void
    {
        $config = ['propertyName' => 'thing_placeholder'];
        $association = new HasOne('Thing', $config);
        $this->assertSame('thing_placeholder', $association->getProperty());
    }

    /**
     * Test that plugin names are omitted from property()
     */
    public function testPropertyNoPlugin(): void
    {
        $config = [
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
        ];
        $association = new HasOne('Contacts.Profiles', $config);
        $this->assertSame('profile', $association->getProperty());
    }

    /**
     * Tests that attaching an association to a query will trigger beforeFind
     * for the target table
     */
    public function testAttachToBeforeFind(): void
    {
        $config = [
            'foreignKey' => 'user_id',
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
        ];
        $query = $this->user->query();

        $this->listenerCalled = false;
        $this->profile->getEventManager()->on('Model.beforeFind', function ($event, $query, $options, $primary): void {
            $this->listenerCalled = true;
            $this->assertInstanceOf('Cake\Event\Event', $event);
            $this->assertInstanceOf('Cake\ORM\Query', $query);
            $this->assertInstanceOf('ArrayObject', $options);
            $this->assertFalse($primary);
        });
        $association = new HasOne('Profiles', $config);
        $association->attachTo($query);
        $this->assertTrue($this->listenerCalled, 'beforeFind event not fired.');
    }

    /**
     * Tests that attaching an association to a query will trigger beforeFind
     * for the target table
     */
    public function testAttachToBeforeFindExtraOptions(): void
    {
        $config = [
            'foreignKey' => 'user_id',
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
        ];
        $this->listenerCalled = false;
        $opts = new ArrayObject(['something' => 'more']);
        $this->profile->getEventManager()->on(
            'Model.beforeFind',
            function ($event, $query, $options, $primary) use ($opts): void {
                $this->listenerCalled = true;
                $this->assertInstanceOf('Cake\Event\Event', $event);
                $this->assertInstanceOf('Cake\ORM\Query', $query);
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
     */
    public function testCascadeDelete(): void
    {
        $config = [
            'dependent' => true,
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
            'conditions' => ['Profiles.is_active' => true],
            'cascadeCallbacks' => false,
        ];
        $association = new HasOne('Profiles', $config);

        $this->profile->getEventManager()->on('Model.beforeDelete', function (): void {
            $this->fail('Callbacks should not be triggered when callbacks do not cascade.');
        });

        $entity = new Entity(['id' => 1]);
        $association->cascadeDelete($entity);

        $query = $this->profile->query()->where(['user_id' => 1]);
        $this->assertSame(1, $query->count(), 'Left non-matching row behind');

        $query = $this->profile->query()->where(['user_id' => 3]);
        $this->assertSame(1, $query->count(), 'other records left behind');

        $user = new Entity(['id' => 3]);
        $this->assertTrue($association->cascadeDelete($user));
        $query = $this->profile->query()->where(['user_id' => 3]);
        $this->assertSame(0, $query->count(), 'Matching record was deleted.');
    }

    /**
     * Tests cascading deletes on entities with null binding and foreign key.
     */
    public function testCascadeDeleteNullBindingNullForeign(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Authors = $this->getTableLocator()->get('NullableAuthors');

        $config = [
            'dependent' => true,
            'sourceTable' => $Authors,
            'targetTable' => $Articles,
            'bindingKey' => 'author_id',
            'foreignKey' => 'author_id',
            'cascadeCallbacks' => false,
        ];
        $association = $Authors->hasOne('Articles', $config);

        // create article with null foreign key
        $entity = new Entity(['author_id' => null, 'title' => 'this has no author', 'body' => 'I am abandoned', 'published' => 'N']);
        $Articles->save($entity);

        // get author with null binding key
        $entity = $Authors->get(2, ['contain' => 'Articles']);
        $this->assertNull($entity->article);
        $this->assertTrue($association->cascadeDelete($entity));

        $query = $Articles->query();
        $this->assertSame(4, $query->count(), 'No articles should be deleted');
    }

    /**
     * Test cascading delete with has one.
     */
    public function testCascadeDeleteCallbacks(): void
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
        $this->assertSame(1, $query->count(), 'Left non-matching row behind');

        $query = $this->profile->query()->where(['user_id' => 3]);
        $this->assertSame(1, $query->count(), 'other records left behind');

        $user = new Entity(['id' => 3]);
        $this->assertTrue($association->cascadeDelete($user));
        $query = $this->profile->query()->where(['user_id' => 3]);
        $this->assertSame(0, $query->count(), 'Matching record was deleted.');
    }

    /**
     * Test cascading delete with a rule preventing deletion
     */
    public function testCascadeDeleteCallbacksRuleFailure(): void
    {
        $config = [
            'dependent' => true,
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
            'cascadeCallbacks' => true,
        ];
        $association = new HasOne('Profiles', $config);
        $profiles = $association->getTarget();
        $profiles->getEventManager()->on('Model.buildRules', function ($event, $rules): void {
            $rules->addDelete(function () {
                return false;
            });
        });

        $user = new Entity(['id' => 1]);
        $this->assertFalse($association->cascadeDelete($user));
        $matching = $profiles->find()
            ->where(['Profiles.user_id' => $user->id])
            ->all();
        $this->assertGreaterThan(0, count($matching));
    }
}
