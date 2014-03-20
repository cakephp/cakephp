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
		$countDirect = $this->table->childCount(1, true);
		$this->assertEquals(2, $countDirect);

		// counts all the children of root
		$count = $this->table->childCount(1, false);
		$this->assertEquals(9, $count);

		// counts direct children
		$count = $this->table->childCount(2, false);
		$this->assertEquals(3, $count);

		// count children for a middle-node
		$count = $this->table->childCount(6, false);
		$this->assertEquals(4, $count);

		// count leaf children
		$count = $this->table->childCount(10, false);
		$this->assertEquals(0, $count);

		// test scoping
		$table = TableRegistry::get('MenuLinkTrees');
		$table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
		$count = $table->childCount(3, false);
		$this->assertEquals(2, $count);
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
		$count = $table->childCount(1, false);
		$this->assertEquals(4, $count);
	}
}
