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
namespace Cake\Test\TestCase\ORM;

use Cake\ORM\Associations;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;

/**
 * Associations test case.
 */
class AssociationsTest extends TestCase {

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->associations = new Associations();
	}

/**
 * Test the simple add/has and get methods.
 *
 * @return void
 */
	public function testAddHasRemoveAndGet() {
		$this->assertFalse($this->associations->has('users'));
		$this->assertFalse($this->associations->has('Users'));

		$this->assertNull($this->associations->get('users'));
		$this->assertNull($this->associations->get('Users'));

		$belongsTo = new BelongsTo([]);
		$this->assertSame($belongsTo, $this->associations->add('Users', $belongsTo));
		$this->assertTrue($this->associations->has('users'));
		$this->assertTrue($this->associations->has('Users'));

		$this->assertSame($belongsTo, $this->associations->get('users'));
		$this->assertSame($belongsTo, $this->associations->get('Users'));

		$this->assertNull($this->associations->remove('Users'));

		$this->assertFalse($this->associations->has('users'));
		$this->assertFalse($this->associations->has('Users'));
		$this->assertNull($this->associations->get('users'));
		$this->assertNull($this->associations->get('Users'));
	}

/**
 * Test getting associations by property.
 *
 * @return void
 */
	public function testGetByProperty() {
		$belongsTo = new BelongsTo('Users', []);
		$this->assertEquals('user', $belongsTo->property());
		$this->associations->add('Users', $belongsTo);
		$this->assertNull($this->associations->get('user'));

		$this->assertSame($belongsTo, $this->associations->getByProperty('user'));
	}

/**
 * Test associations with plugin names.
 *
 * @return void
 */
	public function testAddHasRemoveGetWithPlugin() {
		$this->assertFalse($this->associations->has('Photos.Photos'));
		$this->assertFalse($this->associations->has('Photos'));

		$belongsTo = new BelongsTo([]);
		$this->assertSame($belongsTo, $this->associations->add('Photos.Photos', $belongsTo));
		$this->assertTrue($this->associations->has('Photos'));
		$this->assertFalse($this->associations->has('Photos.Photos'));
	}

/**
 * Test keys()
 *
 * @return void
 */
	public function testKeys() {
		$belongsTo = new BelongsTo([]);
		$this->associations->add('Users', $belongsTo);
		$this->associations->add('Categories', $belongsTo);
		$this->assertEquals(['users', 'categories'], $this->associations->keys());

		$this->associations->remove('Categories');
		$this->assertEquals(['users'], $this->associations->keys());
	}

/**
 * Test getting association names by type.
 */
	public function testType() {
		$belongsTo = new BelongsTo([]);
		$this->associations->add('Users', $belongsTo);

		$this->assertSame([$belongsTo], $this->associations->type('BelongsTo'));
		$this->assertSame([], $this->associations->type('HasMany'));
	}

/**
 * test cascading deletes.
 *
 * @return void
 */
	public function testCascadeDelete() {
		$mockOne = $this->getMock('Cake\ORM\Association\BelongsTo', [], [[]]);
		$mockTwo = $this->getMock('Cake\ORM\Association\HasMany', [], [[]]);

		$entity = new Entity();
		$options = ['option' => 'value'];
		$this->associations->add('One', $mockOne);
		$this->associations->add('Two', $mockTwo);

		$mockOne->expects($this->once())
			->method('cascadeDelete')
			->with($entity, $options);

		$mockTwo->expects($this->once())
			->method('cascadeDelete')
			->with($entity, $options);

		$this->assertNull($this->associations->cascadeDelete($entity, $options));
	}

/**
 * Test saving parent associations
 *
 * @return void
 */
	public function testSaveParents() {
		$table = $this->getMock('Cake\ORM\Table', [], [[]]);
		$mockOne = $this->getMock(
			'Cake\ORM\Association\BelongsTo',
			['save'],
			['Parent', [
				'sourceTable' => $table,
			]]);
		$mockTwo = $this->getMock(
			'Cake\ORM\Association\HasMany',
			['save'],
			['Child', [
				'sourceTable' => $table
			]]);

		$this->associations->add('Parent', $mockOne);
		$this->associations->add('Child', $mockTwo);

		$entity = new Entity();
		$entity->set('parent', ['key' => 'value']);
		$entity->set('child', ['key' => 'value']);

		$options = ['option' => 'value'];

		$mockOne->expects($this->once())
			->method('save')
			->with($entity, $options)
			->will($this->returnValue(true));

		$mockTwo->expects($this->never())
			->method('save');

		$result = $this->associations->saveParents(
			$table,
			$entity,
			['Parent', 'Child'],
			$options
		);
		$this->assertTrue($result, 'Save should work.');
	}

/**
 * Test saving filtered parent associations.
 *
 * @return void
 */
	public function testSaveParentsFiltered() {
		$table = $this->getMock('Cake\ORM\Table', [], [[]]);
		$mockOne = $this->getMock(
			'Cake\ORM\Association\BelongsTo',
			['save'],
			['Parents', [
				'sourceTable' => $table,
			]]);
		$mockTwo = $this->getMock(
			'Cake\ORM\Association\BelongsTo',
			['save'],
			['Categories', [
				'sourceTable' => $table
			]]);

		$this->associations->add('Parents', $mockOne);
		$this->associations->add('Categories', $mockTwo);

		$entity = new Entity();
		$entity->set('parent', ['key' => 'value']);
		$entity->set('category', ['key' => 'value']);

		$options = ['atomic' => true];

		$mockOne->expects($this->once())
			->method('save')
			->with($entity, ['atomic' => true, 'associated' => ['Others']])
			->will($this->returnValue(true));

		$mockTwo->expects($this->never())
			->method('save');

		$result = $this->associations->saveParents(
			$table,
			$entity,
			['Parents' => ['associated' => ['Others']]],
			$options
		);
		$this->assertTrue($result, 'Save should work.');
	}

/**
 * Test saving filtered child associations.
 *
 * @return void
 */
	public function testSaveChildrenFiltered() {
		$table = $this->getMock('Cake\ORM\Table', [], [[]]);
		$mockOne = $this->getMock(
			'Cake\ORM\Association\HasMany',
			['save'],
			['Comments', [
				'sourceTable' => $table,
			]]);
		$mockTwo = $this->getMock(
			'Cake\ORM\Association\HasOne',
			['save'],
			['Profiles', [
				'sourceTable' => $table
			]]);

		$this->associations->add('Comments', $mockOne);
		$this->associations->add('Profiles', $mockTwo);

		$entity = new Entity();
		$entity->set('comments', ['key' => 'value']);
		$entity->set('profile', ['key' => 'value']);

		$options = ['atomic' => true];

		$mockOne->expects($this->once())
			->method('save')
			->with($entity, $options + ['associated' => ['Other']])
			->will($this->returnValue(true));

		$mockTwo->expects($this->never())
			->method('save');

		$result = $this->associations->saveChildren(
			$table,
			$entity,
			['Comments' => ['associated' => ['Other']]],
			$options
		);
		$this->assertTrue($result, 'Should succeed.');
	}

/**
 * Test exceptional case.
 *
 * @expectedException \InvalidArgumentException
 * @expectedExceptionMessage Cannot save Profiles, it is not associated to Users
 */
	public function testErrorOnUnknownAlias() {
		$table = $this->getMock(
			'Cake\ORM\Table',
			['save'],
			[['alias' => 'Users']]);

		$entity = new Entity();
		$entity->set('profile', ['key' => 'value']);

		$this->associations->saveChildren(
			$table,
			$entity,
			['Profiles'],
			['atomic' => true]
		);
	}

/**
 * Tests the normalizeKeys method
 *
 * @return void
 */
	public function testNormalizeKeys() {
		$this->assertSame([], $this->associations->normalizeKeys([]));
		$this->assertSame([], $this->associations->normalizeKeys(false));

		$assocs = ['a', 'b', 'd' => ['something']];
		$expected = ['a' => [], 'b' => [], 'd' => ['something']];
		$this->assertSame($expected, $this->associations->normalizeKeys($assocs));

		$belongsTo = new BelongsTo([]);
		$this->associations->add('users', $belongsTo);
		$this->associations->add('categories', $belongsTo);
		$expected = ['users' => [], 'categories' => []];
		$this->assertSame($expected, $this->associations->normalizeKeys(true));
	}

}
