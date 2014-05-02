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
namespace Cake\Test\TestCase\Model\Behavior;

use Cake\Collection\Collection;
use Cake\Event\Event;
use Cake\Model\Behavior\TranslateBehavior;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Translate behavior test case
 */
class TreeBehaviorTest extends TestCase {

/**
 * fixtures
 *
 * @var array
 */
	public $fixtures = [
		'core.number_tree',
		'core.menu_link_tree'
	];

	public function setUp() {
		parent::setUp();
		$this->table = TableRegistry::get('NumberTrees');
		$this->table->addBehavior('Tree');
	}

	public function tearDown() {
		parent::tearDown();
		TableRegistry::clear();
	}

/**
 * Tests the find('path') method
 *
 * @return void
 */
	public function testFindPath() {
		$nodes = $this->table->find('path', ['for' => 9]);
		$this->assertEquals([1, 6, 9], $nodes->extract('id')->toArray());

		$nodes = $this->table->find('path', ['for' => 10]);
		$this->assertEquals([1, 6, 10], $nodes->extract('id')->toArray());

		$nodes = $this->table->find('path', ['for' => 5]);
		$this->assertEquals([1, 2, 5], $nodes->extract('id')->toArray());

		$nodes = $this->table->find('path', ['for' => 1]);
		$this->assertEquals([1], $nodes->extract('id')->toArray());

		// find path with scope
		$table = TableRegistry::get('MenuLinkTrees');
		$table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
		$nodes = $table->find('path', ['for' => 5]);
		$this->assertEquals([1, 3, 4, 5], $nodes->extract('id')->toArray());
	}

/**
 * Tests the childCount() method
 *
 * @return void
 */
	public function testChildCount() {
		// direct children for the root node
		$table = $this->table;
		$countDirect = $this->table->childCount($table->get(1), true);
		$this->assertEquals(2, $countDirect);

		// counts all the children of root
		$count = $this->table->childCount($table->get(1), false);
		$this->assertEquals(9, $count);

		// counts direct children
		$count = $this->table->childCount($table->get(2), false);
		$this->assertEquals(3, $count);

		// count children for a middle-node
		$count = $this->table->childCount($table->get(6), false);
		$this->assertEquals(4, $count);

		// count leaf children
		$count = $this->table->childCount($table->get(10), false);
		$this->assertEquals(0, $count);

		// test scoping
		$table = TableRegistry::get('MenuLinkTrees');
		$table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
		$count = $table->childCount($table->get(3), false);
		$this->assertEquals(2, $count);
	}

/**
 * Tests that childCount will provide the correct lft and rght values
 *
 * @return void
 */
	public function testChildCountNoTreeColumns() {
		$table = $this->table;
		$node = $table->get(6);
		$node->unsetProperty('lft');
		$node->unsetProperty('rght');
		$count = $this->table->childCount($node, false);
		$this->assertEquals(4, $count);
	}

/**
 * Tests the childCount() plus callable scoping
 *
 * @return void
 */
	public function testCallableScoping() {
		$table = TableRegistry::get('MenuLinkTrees');
		$table->addBehavior('Tree', [
			'scope' => function ($query) {
				return $query->where(['menu' => 'main-menu']);
			}
		]);
		$count = $table->childCount($table->get(1), false);
		$this->assertEquals(4, $count);
	}

/**
 * Tests the find('children') method
 *
 * @return void
 */
	public function testFindChildren() {
		$table = TableRegistry::get('MenuLinkTrees');
		$table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);

		// root
		$nodeIds = [];
		$nodes = $table->find('children', ['for' => 1])->all();
		$this->assertEquals([2, 3, 4, 5], $nodes->extract('id')->toArray());

		// leaf
		$nodeIds = [];
		$nodes = $table->find('children', ['for' => 5])->all();
		$this->assertEquals(0, count($nodes->extract('id')->toArray()));

		// direct children
		$nodes = $table->find('children', ['for' => 1, 'direct' => true])->all();
		$this->assertEquals([2, 3], $nodes->extract('id')->toArray());
	}

/**
 * Tests that find('children') will throw an exception if the node was not found
 *
 * @expectedException \Cake\ORM\Error\RecordNotFoundException
 * @return void
 */
	public function testFindChildrenException() {
		$table = TableRegistry::get('MenuLinkTrees');
		$table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
		$query = $table->find('children', ['for' => 500]);
	}

/**
 * Tests the find('treeList') method
 *
 * @return void
 */
	public function testFindTreeList() {
		$table = TableRegistry::get('MenuLinkTrees');
		$table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
		$result = $table->find('treeList')->toArray();
		$expected = [
			1 => 'Link 1',
			2 => '_Link 2',
			3 => '_Link 3',
			4 => '__Link 4',
			5 => '___Link 5',
			6 => 'Link 6',
			7 => '_Link 7',
			8 => 'Link 8'
		];
		$this->assertEquals($expected, $result);
	}

/**
 * Tests the find('treeList') method with custom options
 *
 * @return void
 */
	public function testFindTreeListCustom() {
		$table = TableRegistry::get('MenuLinkTrees');
		$table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
		$result = $table
			->find('treeList', ['keyPath' => 'url', 'valuePath' => 'id', 'spacer' => ' '])
			->toArray();
		$expected = [
			'/link1.html' => '1',
			'http://example.com' => ' 2',
			'/what/even-more-links.html' => ' 3',
			'/lorem/ipsum.html' => '  4',
			'/what/the.html' => '   5',
			'/yeah/another-link.html' => '6',
			'http://cakephp.org' => ' 7',
			'/page/who-we-are.html' => '8'
		];
		$this->assertEquals($expected, $result);
	}

/**
 * Tests the moveUp() method
 *
 * @return void
 */
	public function testMoveUp() {
		$table = TableRegistry::get('MenuLinkTrees');
		$table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);

		// top level, wont move
		$node = $this->table->moveUp($table->get(1), 10);
		$this->assertEquals(['lft' => 1, 'rght' => 10], $node->extract(['lft', 'rght']));

		// edge cases
		$this->assertFalse($this->table->moveUp($table->get(1), 0));
		$node = $this->table->moveUp($table->get(1), -10);
		$this->assertEquals(['lft' => 1, 'rght' => 10], $node->extract(['lft', 'rght']));

		// move inner node
		$node = $table->moveUp($table->get(3), 1);
		$nodes = $table->find('children', ['for' => 1])->all();
		$this->assertEquals([3, 4, 5, 2], $nodes->extract('id')->toArray());
		$this->assertEquals(['lft' => 2, 'rght' => 7], $node->extract(['lft', 'rght']));
	}

/**
 * Tests moving a node with no siblings
 *
 * @return void
 */
	public function testMoveLeaf() {
		$table = TableRegistry::get('MenuLinkTrees');
		$table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
		$node = $table->moveUp($table->get(5), 1);
		$this->assertEquals(['lft' => 6, 'rght' => 7], $node->extract(['lft', 'rght']));
	}

/**
 * Tests moving a node to the top
 *
 * @return void
 */
	public function testMoveTop() {
		$table = TableRegistry::get('MenuLinkTrees');
		$table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
		$node = $table->moveUp($table->get(8), true);
		$this->assertEquals(['lft' => 1, 'rght' => 2], $node->extract(['lft', 'rght']));
		$nodes = $table->find()
			->select(['id'])
			->where(function($exp) {
				return $exp->isNull('parent_id');
			})
			->where(['menu' => 'main-menu'])
			->order(['lft' => 'ASC'])
			->all();
		$this->assertEquals([8, 1, 6], $nodes->extract('id')->toArray());
	}

/**
 * Tests moving a node with no lft and rght
 *
 * @return void
 */
	public function testMoveNoTreeColumns() {
		$table = TableRegistry::get('MenuLinkTrees');
		$table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
		$node = $table->get(8);
		$node->unsetProperty('lft');
		$node->unsetProperty('rght');
		$node = $table->moveUp($node, true);
		$this->assertEquals(['lft' => 1, 'rght' => 2], $node->extract(['lft', 'rght']));
		$nodes = $table->find()
			->select(['id'])
			->where(function($exp) {
				return $exp->isNull('parent_id');
			})
			->where(['menu' => 'main-menu'])
			->order(['lft' => 'ASC'])
			->all();
		$this->assertEquals([8, 1, 6], $nodes->extract('id')->toArray());
	}

/**
 * Tests the moveDown() method
 *
 * @return void
 */
	public function testMoveDown() {
		$table = TableRegistry::get('MenuLinkTrees');
		$table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
		// latest node, wont move
		$node = $this->table->moveDown($table->get(8), 10);
		$this->assertEquals(['lft' => 21, 'rght' => 22], $node->extract(['lft', 'rght']));

		// edge cases
		$this->assertFalse($this->table->moveDown($table->get(8), 0));

		// move inner node
		$node = $table->moveDown($table->get(2), 1);
		$nodes = $table->find('children', ['for' => 1])->all();
		$this->assertEquals([3, 4, 5, 2], $nodes->extract('id')->toArray());
		$this->assertEquals(['lft' => 11, 'rght' => 12], $node->extract(['lft', 'rght']));
	}

/**
 * Tests moving a node that has no siblings
 *
 * @return void
 */
	public function testMoveLeafDown() {
		$table = TableRegistry::get('MenuLinkTrees');
		$table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
		$node = $table->moveDown($table->get(5), 1);
		$this->assertEquals(['lft' => 6, 'rght' => 7], $node->extract(['lft', 'rght']));
	}

/**
 * Tests moving a node to the bottom
 *
 * @return void
 */
	public function testMoveToBottom() {
		$table = TableRegistry::get('MenuLinkTrees');
		$table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
		$node = $table->moveDown($table->get(1), true);
		$this->assertEquals(['lft' => 7, 'rght' => 16], $node->extract(['lft', 'rght']));
		$nodes = $table->find()
			->select(['id'])
			->where(function($exp) {
				return $exp->isNull('parent_id');
			})
			->where(['menu' => 'main-menu'])
			->order(['lft' => 'ASC'])
			->all();
		$this->assertEquals([6, 8, 1], $nodes->extract('id')->toArray());
	}

/**
 * Tests moving a node with no lft and rght columns
 *
 * @return void
 */
	public function testMoveDownNoTreeColumns() {
		$table = TableRegistry::get('MenuLinkTrees');
		$table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
		$node = $table->get(1);
		$node->unsetProperty('lft');
		$node->unsetProperty('rght');
		$node = $table->moveDown($node, true);
		$this->assertEquals(['lft' => 7, 'rght' => 16], $node->extract(['lft', 'rght']));
		$nodes = $table->find()
			->select(['id'])
			->where(function($exp) {
				return $exp->isNull('parent_id');
			})
			->where(['menu' => 'main-menu'])
			->order(['lft' => 'ASC'])
			->all();
		$this->assertEquals([6, 8, 1], $nodes->extract('id')->toArray());
	}

/**
 * Tests the recover function
 *
 * @return void
 */
	public function testRecover() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$expected = $table->find()->order('lft')->hydrate(false)->toArray();
		$table->updateAll(['lft' => null, 'rght' => null], []);
		$table->recover();
		$result = $table->find()->order('lft')->hydrate(false)->toArray();
		$this->assertEquals($expected, $result);
	}

/**
 * Tests the recover function with a custom scope
 *
 * @return void
 */
	public function testRecoverScoped() {
		$table = TableRegistry::get('MenuLinkTrees');
		$table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
		$expected = $table->find()
			->where(['menu' => 'main-menu'])
			->order('lft')
			->hydrate(false)
			->toArray();

		$expected2 = $table->find()
			->where(['menu' => 'categories'])
			->order('lft')
			->hydrate(false)
			->toArray();

		$table->updateAll(['lft' => null, 'rght' => null], ['menu' => 'main-menu']);
		$table->recover();
		$result = $table->find()
			->where(['menu' => 'main-menu'])
			->order('lft')
			->hydrate(false)
			->toArray();
		$this->assertEquals($expected, $result);

		$result2 = $table->find()
			->where(['menu' => 'categories'])
			->order('lft')
			->hydrate(false)
			->toArray();
		$this->assertEquals($expected2, $result2);
	}

/**
 * Tests adding a new orphan node
 *
 * @return void
 */
	public function testAddOrphan() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = new Entity(
			['name' => 'New Orphan', 'parent_id' => null],
			['markNew' => true]
		);
		$expected = $table->find()->order('lft')->hydrate(false)->toArray();
		$this->assertSame($entity, $table->save($entity));
		$this->assertEquals(23, $entity->lft);
		$this->assertEquals(24, $entity->rght);

		$expected[] = $entity->toArray();
		$results = $table->find()->order('lft')->hydrate(false)->toArray();
		$this->assertEquals($expected, $results);
	}

/**
 * Tests that adding a child node as a decendant of one of the roots works
 *
 * @return void
 */
	public function testAddMiddle() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = new Entity(
			['name' => 'laptops', 'parent_id' => 1],
			['markNew' => true]
		);
		$this->assertSame($entity, $table->save($entity));
		$this->assertEquals(20, $entity->lft);
		$this->assertEquals(21, $entity->rght);

		$result = $table->find()->order('lft')->hydrate(false)->toArray();
		$table->recover();
		$expected = $table->find()->order('lft')->hydrate(false)->toArray();
		$this->assertEquals($expected, $result);
	}

/**
 * Tests adding a leaf to the tree
 *
 * @return void
 */
	public function testAddLeaf() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = new Entity(
			['name' => 'laptops', 'parent_id' => 2],
			['markNew' => true]
		);
		$this->assertSame($entity, $table->save($entity));
		$this->assertEquals(9, $entity->lft);
		$this->assertEquals(10, $entity->rght);

		$results = $table->find()->order('lft')->hydrate(false)->toArray();
		$table->recover();
		$expected = $table->find()->order('lft')->hydrate(false)->toArray();
		$this->assertEquals($expected, $results);
	}

/**
 * Tests moving a subtree to the right
 *
 * @return void
 */
	public function testReParentSubTreeRight() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = $table->get(2);
		$entity->parent_id = 6;
		$this->assertSame($entity, $table->save($entity));
		$this->assertEquals(11, $entity->lft);
		$this->assertEquals(18, $entity->rght);

		$result = $table->find()->order('lft')->hydrate(false);
		$expected = [1, 6, 7, 8, 9, 10, 2, 3, 4, 5, 11];
		$this->assertTreeNumbers($expected, $table);
	}

/**
 * Tests moving a subtree to the left
 *
 * @return void
 */
	public function testReParentSubTreeLeft() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = $table->get(6);
		$entity->parent_id = 2;
		$this->assertSame($entity, $table->save($entity));
		$this->assertEquals(9, $entity->lft);
		$this->assertEquals(18, $entity->rght);

		$result = $table->find()->order('lft')->hydrate(false)->toArray();
		$table->recover();
		$expected = $table->find()->order('lft')->hydrate(false)->toArray();
		$this->assertEquals($expected, $result);
	}

/**
 * Test moving a leaft to the left
 *
 * @return void
 */
	public function testReParentLeafLeft() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = $table->get(10);
		$entity->parent_id = 2;
		$this->assertSame($entity, $table->save($entity));
		$this->assertEquals(9, $entity->lft);
		$this->assertEquals(10, $entity->rght);

		$result = $table->find()->order('lft')->hydrate(false)->toArray();
		$table->recover();
		$expected = $table->find()->order('lft')->hydrate(false)->toArray();
		$this->assertEquals($expected, $result);
	}

/**
 * Test moving a leaf to the left
 *
 * @return void
 */
	public function testReParentLeafRight() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = $table->get(5);
		$entity->parent_id = 6;
		$this->assertSame($entity, $table->save($entity));
		$this->assertEquals(17, $entity->lft);
		$this->assertEquals(18, $entity->rght);

		$result = $table->find()->order('lft')->hydrate(false);
		$expected = [1, 2, 3, 4, 6, 7, 8, 9, 10, 5, 11];
		$this->assertTreeNumbers($expected, $table);
	}

/**
 * Tests moving a subtree with a node having no lft and rght columns
 *
 * @return void
 */
	public function testReParentNoTreeColumns() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = $table->get(6);
		$entity->unsetProperty('lft');
		$entity->unsetProperty('rght');
		$entity->parent_id = 2;
		$this->assertSame($entity, $table->save($entity));
		$this->assertEquals(9, $entity->lft);
		$this->assertEquals(18, $entity->rght);

		$result = $table->find()->order('lft')->hydrate(false)->toArray();
		$table->recover();
		$expected = $table->find()->order('lft')->hydrate(false)->toArray();
		$this->assertEquals($expected, $result);
	}

/**
 * Tests moving a subtree as a new root
 *
 * @return void
 */
	public function testRootingSubTree() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = $table->get(2);
		$entity->parent_id = null;
		$this->assertSame($entity, $table->save($entity));
		$this->assertEquals(15, $entity->lft);
		$this->assertEquals(22, $entity->rght);

		$result = $table->find()->order('lft')->hydrate(false);
		$expected = [1, 6, 7, 8, 9, 10, 11, 2, 3, 4, 5];
		$this->assertTreeNumbers($expected, $table);
	}

/**
 * Tests moving a subtree with no tree columns
 *
 * @return void
 */
	public function testRootingNoTreeColumns() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = $table->get(2);
		$entity->unsetProperty('lft');
		$entity->unsetProperty('rght');
		$entity->parent_id = null;
		$this->assertSame($entity, $table->save($entity));
		$this->assertEquals(15, $entity->lft);
		$this->assertEquals(22, $entity->rght);

		$result = $table->find()->order('lft')->hydrate(false);
		$expected = [1, 6, 7, 8, 9, 10, 11, 2, 3, 4, 5];
		$this->assertTreeNumbers($expected, $table);
	}

/**
 * Tests that trying to create a cycle throws an exception
 *
 * @expectedException RuntimeException
 * @expectedExceptionMessage Cannot use node "5" as parent for entity "2"
 * @return void
 */
	public function testReparentCycle() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = $table->get(2);
		$entity->parent_id = 5;
		$table->save($entity);
	}

/**
 * Tests deleting a leaf in the tree
 *
 * @return void
 */
	public function testDeleteLeaf() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = $table->get(4);
		$this->assertTrue($table->delete($entity));
		$result = $table->find()->order('lft')->hydrate(false)->toArray();
		$table->recover();
		$expected = $table->find()->order('lft')->hydrate(false)->toArray();
		$this->assertEquals($expected, $result);
	}

/**
 * Tests deleting a subtree
 *
 * @return void
 */
	public function testDeleteSubTree() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = $table->get(6);
		$this->assertTrue($table->delete($entity));
		$result = $table->find()->order('lft')->hydrate(false)->toArray();
		$table->recover();
		$expected = $table->find()->order('lft')->hydrate(false)->toArray();
		$this->assertEquals($expected, $result);
	}

/**
 * Test deleting a root node
 *
 * @return void
 */
	public function testDeleteRoot() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = $table->get(1);
		$this->assertTrue($table->delete($entity));
		$result = $table->find()->order('lft')->hydrate(false)->toArray();
		$table->recover();
		$expected = $table->find()->order('lft')->hydrate(false)->toArray();
		$this->assertEquals($expected, $result);
	}

/**
 * Test deleting a node with no tree columns
 *
 * @return void
 */
	public function testDeleteRootNoTreeColumns() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = $table->get(1);
		$entity->unsetProperty('lft');
		$entity->unsetProperty('rght');
		$this->assertTrue($table->delete($entity));
		$result = $table->find()->order('lft')->hydrate(false)->toArray();
		$table->recover();
		$expected = $table->find()->order('lft')->hydrate(false)->toArray();
		$this->assertEquals($expected, $result);
	}

/**
 * Tests that a leaf can be taken out of the tree and put in as a root
 *
 * @return void
 */
	public function testRemoveFromLeafFromTree() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = $table->get(10);
		$this->assertSame($entity, $table->removeFromTree($entity));
		$this->assertEquals(21, $entity->lft);
		$this->assertEquals(22, $entity->rght);
		$this->assertEquals(null, $entity->parent_id);
		$result = $table->find()->order('lft')->hydrate(false);
		$expected = [1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 10];
		$this->assertTreeNumbers($expected, $table);
	}

/**
 * Test removing a middle node from a tree
 *
 * @return void
 */
	public function testRemoveMiddleNodeFromTree() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = $table->get(6);
		$this->assertSame($entity, $table->removeFromTree($entity));
		$result = $table->find('threaded')->order('lft')->hydrate(false)->toArray();
		$this->assertEquals(21, $entity->lft);
		$this->assertEquals(22, $entity->rght);
		$this->assertEquals(null, $entity->parent_id);
		$result = $table->find()->order('lft')->hydrate(false);
		$expected = [1, 2, 3, 4, 5, 7, 8, 9, 10, 11, 6];
		$this->assertTreeNumbers($expected, $table);
	}

/**
 * Tests removing the root of a tree
 *
 * @return void
 */
	public function testRemoveRootFromTree() {
		$table = TableRegistry::get('NumberTrees');
		$table->addBehavior('Tree');
		$entity = $table->get(1);
		$this->assertSame($entity, $table->removeFromTree($entity));
		$result = $table->find('threaded')->order('lft')->hydrate(false)->toArray();
		$this->assertEquals(21, $entity->lft);
		$this->assertEquals(22, $entity->rght);
		$this->assertEquals(null, $entity->parent_id);
		$expected = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 1];
		$this->assertTreeNumbers($expected, $table);
	}

/**
 * Custom assertion use to verify tha a tree is returned in the expected order
 * and that it is still valid
 *
 * @param array $expected The list of ids in the order they are expected
 * @param \Cake\ORM\Table the table instance to use for comparing
 * @return void
 */
	public function assertTreeNumbers($expected, $table) {
		$result = $table->find()->order('lft')->hydrate(false);
		$this->assertEquals($expected, $result->extract('id')->toArray());
		$numbers = [];
		$result->each(function($v) use (&$numbers) {
			$numbers[] = $v['lft'];
			$numbers[] = $v['rght'];
		});
		sort($numbers);
		$this->assertEquals(range(1, 22), $numbers);
	}

}
