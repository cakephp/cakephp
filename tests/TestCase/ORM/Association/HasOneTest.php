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
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->user = TableRegistry::get('Users', [
            'schema' => [
                'id' => ['type' => 'integer'],
                'username' => ['type' => 'string'],
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['id']]
                ]
            ]
        ]);
        $this->profile = TableRegistry::get('Profiles', [
            'schema' => [
                'id' => ['type' => 'integer'],
                'first_name' => ['type' => 'string'],
                'user_id' => ['type' => 'integer'],
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['id']]
                ]
            ]
        ]);
        $this->profilesTypeMap = new TypeMap([
            'Profiles.id' => 'integer',
            'id' => 'integer',
            'Profiles.first_name' => 'string',
            'first_name' => 'string',
            'Profiles.user_id' => 'integer',
            'user_id' => 'integer',
            'Profiles__first_name' => 'string',
            'Profiles__user_id' => 'integer',
            'Profiles__id' => 'integer',
        ]);
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
        $query = $this->getMockBuilder('\Cake\ORM\Query')
            ->setMethods(['join', 'select'])
            ->setConstructorArgs([null, null])
            ->getMock();
        $config = [
            'foreignKey' => 'user_id',
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
            'conditions' => ['Profiles.is_active' => true]
        ];
        $association = new HasOne('Profiles', $config);
        $field = new IdentifierExpression('Profiles.user_id');
        $query->expects($this->once())->method('join')->with([
            'Profiles' => [
                'conditions' => new QueryExpression([
                    'Profiles.is_active' => true,
                    ['Users.id' => $field],
                ], $this->profilesTypeMap),
                'type' => 'LEFT',
                'table' => 'profiles'
            ]
        ]);
        $query->expects($this->once())->method('select')->with([
            'Profiles__id' => 'Profiles.id',
            'Profiles__first_name' => 'Profiles.first_name',
            'Profiles__user_id' => 'Profiles.user_id'
        ]);
        $association->attachTo($query);

        $this->assertEquals(
            'string',
            $query->typeMap()->type('Profiles__first_name'),
            'Associations should map types.'
        );
    }

    /**
     * Tests that it is possible to avoid fields inclusion for the associated table
     *
     * @return void
     */
    public function testAttachToNoFields()
    {
        $query = $this->getMockBuilder('\Cake\ORM\Query')
            ->setMethods(['join', 'select'])
            ->setConstructorArgs([null, null])
            ->getMock();
        $config = [
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
            'conditions' => ['Profiles.is_active' => true]
        ];
        $association = new HasOne('Profiles', $config);
        $field = new IdentifierExpression('Profiles.user_id');
        $query->expects($this->once())->method('join')->with([
            'Profiles' => [
                'conditions' => new QueryExpression([
                    'Profiles.is_active' => true,
                    ['Users.id' => $field],
                ], $this->profilesTypeMap),
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
     * @return void
     */
    public function testAttachToMultiPrimaryKey()
    {
        $query = $this->getMockBuilder('\Cake\ORM\Query')
            ->setMethods(['join', 'select'])
            ->setConstructorArgs([null, null])
            ->getMock();
        $config = [
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
            'conditions' => ['Profiles.is_active' => true],
            'foreignKey' => ['user_id', 'user_site_id']
        ];
        $this->user->primaryKey(['id', 'site_id']);
        $association = new HasOne('Profiles', $config);
        $field1 = new IdentifierExpression('Profiles.user_id');
        $field2 = new IdentifierExpression('Profiles.user_site_id');
        $query->expects($this->once())->method('join')->with([
            'Profiles' => [
                'conditions' => new QueryExpression([
                    'Profiles.is_active' => true,
                    ['Users.id' => $field1, 'Users.site_id' => $field2],
                ], $this->profilesTypeMap),
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
    public function testAttachToMultiPrimaryKeyMistmatch()
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
        $mock = $this->getMockBuilder('Cake\ORM\Table')
            ->disableOriginalConstructor()
            ->getMock();
        $config = [
            'sourceTable' => $this->user,
            'targetTable' => $mock,
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
        $query = $this->getMockBuilder('\Cake\ORM\Query')
            ->setMethods(['join', 'select'])
            ->setConstructorArgs([null, null])
            ->getMock();
        $config = [
            'foreignKey' => 'user_id',
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
        ];
        $listener = $this->getMockBuilder('stdClass')
            ->setMethods(['__invoke'])
            ->getMock();
        $this->profile->eventManager()->attach($listener, 'Model.beforeFind');
        $association = new HasOne('Profiles', $config);
        $listener->expects($this->once())->method('__invoke')
            ->with(
                $this->isInstanceOf('\Cake\Event\Event'),
                $this->isInstanceOf('\Cake\ORM\Query'),
                $this->isInstanceOf('\ArrayObject'),
                false
            );
        $association->attachTo($query);
    }

    /**
     * Tests that attaching an association to a query will trigger beforeFind
     * for the target table
     *
     * @return void
     */
    public function testAttachToBeforeFindExtraOptions()
    {
        $query = $this->getMockBuilder('\Cake\ORM\Query')
            ->setMethods(['join', 'select'])
            ->setConstructorArgs([null, null])
            ->getMock();
        $config = [
            'foreignKey' => 'user_id',
            'sourceTable' => $this->user,
            'targetTable' => $this->profile,
        ];
        $listener = $this->getMockBuilder('stdClass')
            ->setMethods(['__invoke'])
            ->getMock();
        $this->profile->eventManager()->attach($listener, 'Model.beforeFind');
        $association = new HasOne('Profiles', $config);
        $opts = new \ArrayObject(['something' => 'more']);
        $listener->expects($this->once())->method('__invoke')
            ->with(
                $this->isInstanceOf('\Cake\Event\Event'),
                $this->isInstanceOf('\Cake\ORM\Query'),
                $opts,
                false
            );
        $association->attachTo($query, ['queryBuilder' => function ($q) {
            return $q->applyOptions(['something' => 'more']);
        }]);
    }
}
