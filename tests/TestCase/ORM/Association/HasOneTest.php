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
namespace Cake\Test\TestCase\ORM\Association;

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Association\HasOne;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * Tests HasOne class
 *
 */
class HasOneTest extends \Cake\TestSuite\TestCase {

/**
 * Set up
 *
 * @return void
 */
	public function setUp() {
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
 * Tests that the association reports it can be joined
 *
 * @return void
 */
	public function testCanBeJoined() {
		$assoc = new HasOne('Test');
		$this->assertTrue($assoc->canBeJoined());
	}

/**
 * Tests that the correct join and fields are attached to a query depending on
 * the association config
 *
 * @return void
 */
	public function testAttachTo() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
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
				]),
				'type' => 'INNER',
				'table' => 'profiles'
			]
		]);
		$query->expects($this->once())->method('select')->with([
			'Profiles__id' => 'Profiles.id',
			'Profiles__first_name' => 'Profiles.first_name',
			'Profiles__user_id' => 'Profiles.user_id'
		]);
		$association->attachTo($query);
	}

/**
 * Tests that default config defined in the association can be overridden
 *
 * @return void
 */
	public function testAttachToConfigOverride() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
		$config = [
			'foreignKey' => 'user_id',
			'sourceTable' => $this->user,
			'targetTable' => $this->profile,
			'conditions' => ['Profiles.is_active' => true]
		];
		$association = new HasOne('Profiles', $config);
		$query->expects($this->once())->method('join')->with([
			'Profiles' => [
				'conditions' => new QueryExpression([
					'Profiles.is_active' => false
				]),
				'type' => 'INNER',
				'table' => 'profiles'
			]
		]);
		$query->expects($this->once())->method('select')->with([
			'Profiles__first_name' => 'Profiles.first_name'
		]);

		$override = [
			'conditions' => ['Profiles.is_active' => false],
			'foreignKey' => false,
			'fields' => ['first_name']
		];
		$association->attachTo($query, $override);
	}

/**
 * Tests that it is possible to avoid fields inclusion for the associated table
 *
 * @return void
 */
	public function testAttachToNoFields() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
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
				]),
				'type' => 'INNER',
				'table' => 'profiles'
			]
		]);
		$query->expects($this->never())->method('select');
		$association->attachTo($query, ['includeFields' => false]);
	}

/**
 * Tests that by supplying a query builder function, it is possible to add fields
 * and conditions to an association
 *
 * @return void
 */
	public function testAttachToWithQueryBuilder() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
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
					new QueryExpression(['a' => 1])
				]),
				'type' => 'INNER',
				'table' => 'profiles'
			]
		]);
		$query->expects($this->once())->method('select')
			->with([
				'Profiles__a' => 'Profiles.a',
				'Profiles__b' => 'Profiles.b'
			]);
		$builder = function($q) {
			return $q->select(['a', 'b'])->where(['a' => 1]);
		};
		$association->attachTo($query, ['queryBuilder' => $builder]);
	}

/**
 * Tests that using hasOne with a table having a multi column primary
 * key will work if the foreign key is passed
 *
 * @return void
 */
	public function testAttachToMultiPrimaryKey() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
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
				]),
				'type' => 'INNER',
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
 * @expectedExceptionMessage Cannot match provided foreignKey, got 1 columns expected 2
 * @return void
 */
	public function testAttachToMultiPrimaryKeyMistmatch() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
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
 * Test that save() ignores non entity values.
 *
 * @return void
 */
	public function testSaveOnlyEntities() {
		$mock = $this->getMock('Cake\ORM\Table', [], [], '', false);
		$config = [
			'sourceTable' => $this->user,
			'targetTable' => $mock,
		];
		$mock->expects($this->never())
			->method('save');

		$entity = new Entity([
			'username' => 'Mark',
			'email' => 'mark@example.com',
			'profile' => ['twitter' => '@cakephp']
		]);

		$association = new HasOne('Profiles', $config);
		$result = $association->save($entity);

		$this->assertSame($result, $entity);
	}

/**
 * Test that plugin names are omitted from property()
 *
 * @return void
 */
	public function testPropertyNoPlugin() {
		$mock = $this->getMock('Cake\ORM\Table', [], [], '', false);
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
	public function testAttachToBeforeFind() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
		$config = [
			'foreignKey' => 'user_id',
			'sourceTable' => $this->user,
			'targetTable' => $this->profile,
		];
		$listener = $this->getMock('stdClass', ['__invoke']);
		$this->profile->getEventManager()->attach($listener, 'Model.beforeFind');
		$association = new HasOne('Profiles', $config);
		$listener->expects($this->once())->method('__invoke')
			->with(
				$this->isInstanceOf('\Cake\Event\Event'),
				$this->isInstanceOf('\Cake\ORM\Query'),
				[],
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
	public function testAttachToBeforeFindExtraOptions() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
		$config = [
			'foreignKey' => 'user_id',
			'sourceTable' => $this->user,
			'targetTable' => $this->profile,
		];
		$listener = $this->getMock('stdClass', ['__invoke']);
		$this->profile->getEventManager()->attach($listener, 'Model.beforeFind');
		$association = new HasOne('Profiles', $config);
		$opts = ['something' => 'more'];
		$listener->expects($this->once())->method('__invoke')
			->with(
				$this->isInstanceOf('\Cake\Event\Event'),
				$this->isInstanceOf('\Cake\ORM\Query'),
				$opts,
				false
			);
		$association->attachTo($query, ['queryBuilder' => function($q) {
			return $q->applyOptions(['something' => 'more']);
		}]);
	}

}
