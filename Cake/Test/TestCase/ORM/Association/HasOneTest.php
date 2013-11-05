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

use Cake\Database\Expression\FieldExpression;
use Cake\ORM\Association\HasOne;
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
		$this->user = TableRegistry::get('User', [
			'schema' => [
				'id' => ['type' => 'integer'],
				'username' => ['type' => 'string'],
			]
		]);
		$this->profile = TableRegistry::get('Profile', [
			'schema' => [
				'id' => ['type' => 'integer'],
				'first_name' => ['type' => 'string'],
				'user_id' => ['type' => 'integer'],
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
			'conditions' => ['Profile.is_active' => true]
		];
		$association = new HasOne('Profile', $config);
		$field = new FieldExpression('Profile.user_id');
		$query->expects($this->once())->method('join')->with([
			'Profile' => [
				'conditions' => [
					'Profile.is_active' => true,
					['User.id' => $field],
				],
				'type' => 'INNER',
				'table' => 'profiles'
			]
		]);
		$query->expects($this->once())->method('select')->with([
			'Profile__id' => 'Profile.id',
			'Profile__first_name' => 'Profile.first_name',
			'Profile__user_id' => 'Profile.user_id'
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
			'conditions' => ['Profile.is_active' => true]
		];
		$association = new HasOne('Profile', $config);
		$query->expects($this->once())->method('join')->with([
			'Profile' => [
				'conditions' => [
					'Profile.is_active' => false
				],
				'type' => 'INNER',
				'table' => 'profiles'
			]
		]);
		$query->expects($this->once())->method('select')->with([
			'Profile__first_name' => 'Profile.first_name'
		]);

		$override = [
			'conditions' => ['Profile.is_active' => false],
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
			'conditions' => ['Profile.is_active' => true]
		];
		$association = new HasOne('Profile', $config);
		$field = new FieldExpression('Profile.user_id');
		$query->expects($this->once())->method('join')->with([
			'Profile' => [
				'conditions' => [
					'Profile.is_active' => true,
					['User.id' => $field],
				],
				'type' => 'INNER',
				'table' => 'profiles'
			]
		]);
		$query->expects($this->never())->method('select');
		$association->attachTo($query, ['includeFields' => false]);
	}

}
