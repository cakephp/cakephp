<?php
/**
 * CounterCacheBehaviorTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Behavior
 * @since         CakePHP(tm) v 1.2.0.5669
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
require_once dirname(dirname(__FILE__)) . DS . 'models.php';

/**
 * CounterCacheTest class
 *
 * @package       Cake.Test.Case.Model.Behavior
 */
class CounterCacheBehaviorTest extends CakeTestCase {

/**
 * Fixtures associated with this test case
 *
 * @var array
 */
	public $fixtures = array(
		'core.syfile', 'core.item', 'core.image', 'core.portfolio',
		'core.items_portfolio', 'core.counter_cache_user',
		'core.counter_cache_post', 'core.user', 'core.post',
		'core.counter_cache_user_nonstandard_primary_key',
		'core.counter_cache_post_nonstandard_primary_key',
		'core.category_thread'
	);

/**
 * testSaveWithCounterCache method
 *
 * @return void
 */
	public function testSaveWithCounterCache() {
		$this->loadFixtures('Syfile', 'Item', 'Image', 'Portfolio', 'ItemsPortfolio');
		$TestModel = new Syfile();
		$TestModel2 = new Item();

		$result = $TestModel->findById(1);
		$this->assertSame($result['Syfile']['item_count'], null);

		$TestModel2->save(array(
			'name' => 'Item 7',
			'syfile_id' => 1,
			'published' => false
		));

		$result = $TestModel->findById(1);
		$this->assertEquals(2, $result['Syfile']['item_count']);

		$TestModel2->delete(1);
		$result = $TestModel->findById(1);
		$this->assertEquals(1, $result['Syfile']['item_count']);

		$TestModel2->id = 2;
		$TestModel2->saveField('syfile_id', 1);

		$result = $TestModel->findById(1);
		$this->assertEquals(2, $result['Syfile']['item_count']);

		$result = $TestModel->findById(2);
		$this->assertEquals(0, $result['Syfile']['item_count']);
	}

/**
 * Tests that counter caches are updated when records are added
 *
 * @return void
 */
	public function testCounterCacheIncrease() {
		$this->loadFixtures('CounterCacheUser', 'CounterCachePost');
		$User = new CounterCacheUser();
		$Post = new CounterCachePost();
		$data = array('Post' => array(
			'id' => 22,
			'title' => 'New Post',
			'user_id' => 66
		));

		$Post->save($data);
		$user = $User->find('first', array(
			'conditions' => array('id' => 66),
			'recursive' => -1
		));

		$result = $user[$User->alias]['post_count'];
		$expected = 3;
		$this->assertEquals($expected, $result);
	}

/**
 * Tests that counter caches are updated when records are deleted
 *
 * @return void
 */
	public function testCounterCacheDecrease() {
		$this->loadFixtures('CounterCacheUser', 'CounterCachePost');
		$User = new CounterCacheUser();
		$Post = new CounterCachePost();

		$Post->delete(2);
		$user = $User->find('first', array(
			'conditions' => array('id' => 66),
			'recursive' => -1
		));

		$result = $user[$User->alias]['post_count'];
		$expected = 1;
		$this->assertEquals($expected, $result);
	}

/**
 * Tests that counter caches are updated when foreign keys of counted records change
 *
 * @return void
 */
	public function testCounterCacheUpdated() {
		$this->loadFixtures('CounterCacheUser', 'CounterCachePost');
		$User = new CounterCacheUser();
		$Post = new CounterCachePost();

		$data = $Post->find('first', array(
			'conditions' => array('id' => 1),
			'recursive' => -1
		));
		$data[$Post->alias]['user_id'] = 301;
		$Post->save($data);

		$users = $User->find('all', array('order' => 'User.id'));
		$this->assertEquals(1, $users[0]['User']['post_count']);
		$this->assertEquals(2, $users[1]['User']['post_count']);
	}

/**
 * Test counter cache with models that use a non-standard (i.e. not using 'id')
 * as their primary key.
 *
 * @return void
 */
	public function testCounterCacheWithNonstandardPrimaryKey() {
		$this->loadFixtures(
			'CounterCacheUserNonstandardPrimaryKey',
			'CounterCachePostNonstandardPrimaryKey'
		);

		$User = new CounterCacheUserNonstandardPrimaryKey();
		$Post = new CounterCachePostNonstandardPrimaryKey();

		$data = $Post->find('first', array(
			'conditions' => array('pid' => 1),
			'recursive' => -1
		));
		$data[$Post->alias]['uid'] = 301;
		$Post->save($data);

		$users = $User->find('all', array('order' => 'User.uid'));
		$this->assertEquals(1, $users[0]['User']['post_count']);
		$this->assertEquals(2, $users[1]['User']['post_count']);
	}

/**
 * test Counter Cache With Self Joining table
 *
 * @return void
 */
	public function testCounterCacheWithSelfJoin() {
		$this->skipIf($this->db instanceof Sqlite, 'SQLite 2.x does not support ALTER TABLE ADD COLUMN');

		$this->loadFixtures('CategoryThread');
		$column = 'COLUMN ';
		if ($this->db instanceof Sqlserver) {
			$column = '';
		}
		$column .= $this->db->buildColumn(array('name' => 'child_count', 'type' => 'integer'));
		$this->db->query('ALTER TABLE ' . $this->db->fullTableName('category_threads') . ' ADD ' . $column);
		$this->db->flushMethodCache();
		$Category = new CategoryThread();
		$result = $Category->updateAll(array('CategoryThread.name' => "'updated'"), array('CategoryThread.parent_id' => 5));
		$this->assertFalse(empty($result));

		$Category = new CategoryThread();
		$Category->belongsTo['ParentCategory']['counterCache'] = 'child_count';
		$Category->updateCounterCache(array('parent_id' => 5));
		$result = Hash::extract($Category->find('all', array('conditions' => array('CategoryThread.id' => 5))), '{n}.CategoryThread.child_count');
		$expected = array(1);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveWithCounterCacheScope method
 *
 * @return void
 */
	public function testSaveWithCounterCacheScope() {
		$this->loadFixtures('Syfile', 'Item', 'Image', 'ItemsPortfolio', 'Portfolio');
		$TestModel = new Syfile();
		$TestModel2 = new Item();
		$TestModel2->belongsTo['Syfile']['counterCache'] = true;
		$TestModel2->belongsTo['Syfile']['counterScope'] = array('published' => true);

		$result = $TestModel->findById(1);
		$this->assertSame($result['Syfile']['item_count'], null);

		$TestModel2->save(array(
			'name' => 'Item 7',
			'syfile_id' => 1,
			'published' => true
		));

		$result = $TestModel->findById(1);

		$this->assertEquals(1, $result['Syfile']['item_count']);

		$TestModel2->id = 1;
		$TestModel2->saveField('published', true);
		$result = $TestModel->findById(1);
		$this->assertEquals(2, $result['Syfile']['item_count']);

		$TestModel2->save(array(
			'id' => 1,
			'syfile_id' => 1,
			'published' => false
		));

		$result = $TestModel->findById(1);
		$this->assertEquals(1, $result['Syfile']['item_count']);
	}

/**
 * Tests having multiple counter caches for an associated model
 *
 * @access public
 * @return void
 */
	public function testCounterCacheMultipleCaches() {
		$this->loadFixtures('CounterCacheUser', 'CounterCachePost');
		$User = new CounterCacheUser();
		$Post = new CounterCachePost();
		$Post->unbindModel(array('belongsTo' => array('User')), false);
		$Post->bindModel(array(
			'belongsTo' => array(
				'User' => array(
					'className' => 'CounterCacheUser',
					'foreignKey' => 'user_id',
					'counterCache' => array(
						true,
						'posts_published' => array('Post.published' => true)
					)
				)
			)
		), false);

		// Count Increase
		$data = array('Post' => array(
			'id' => 22,
			'title' => 'New Post',
			'user_id' => 66,
			'published' => true
		));
		$Post->save($data);
		$result = $User->find('first', array(
			'conditions' => array('id' => 66),
			'recursive' => -1
		));
		$this->assertEquals(3, $result[$User->alias]['post_count']);
		$this->assertEquals(2, $result[$User->alias]['posts_published']);

		// Count decrease
		$Post->delete(1);
		$result = $User->find('first', array(
			'conditions' => array('id' => 66),
			'recursive' => -1
		));
		$this->assertEquals(2, $result[$User->alias]['post_count']);
		$this->assertEquals(2, $result[$User->alias]['posts_published']);

		// Count update
		$data = $Post->find('first', array(
			'conditions' => array('id' => 1),
			'recursive' => -1
		));
		$data[$Post->alias]['user_id'] = 301;
		$Post->save($data);
		$result = $User->find('all', array('order' => 'User.id'));
		$this->assertEquals(2, $result[0]['User']['post_count']);
		$this->assertEquals(1, $result[1]['User']['posts_published']);
	}

/**
 * Tests that counter caches are unchanged when using 'counterCache' => false
 *
 * @return void
 */
	public function testCounterCacheSkip() {
		$this->loadFixtures('CounterCacheUser', 'CounterCachePost');
		$User = new CounterCacheUser();
		$Post = new CounterCachePost();

		$data = $Post->find('first', array(
			'conditions' => array('id' => 1),
			'recursive' => -1
		));
		$data[$Post->alias]['user_id'] = 301;
		$Post->save($data, array('counterCache' => false));

		$users = $User->find('all', array('order' => 'User.id'));
		$this->assertEquals(2, $users[0]['User']['post_count']);
		$this->assertEquals(1, $users[1]['User']['post_count']);
	}

/**
 * Tests counterCacheKeys
 *
 * @return void
 */
	public function testCounterCacheKeys() {
		$this->loadFixtures('CounterCachePost', 'Post');

		$Post = new CounterCachePost();
		$expected = array('User' => 'user_id');
		$result = $Post->counterCacheKeys();
		$this->assertSame($expected, $result);

		$Post = new Post();
		$expected = array();
		$result = $Post->counterCacheKeys();
		$this->assertSame($expected, $result);
	}

/**
 * Tests hasCounterCache
 *
 * @return void
 */
	public function testHasCounterCache() {
		$this->loadFixtures('CounterCachePost', 'Post');

		$Post = new CounterCachePost();
		$result = $Post->hasCounterCache();
		$this->assertTrue($result);

		$Post = new Post();
		$result = $Post->hasCounterCache();
		$this->assertFalse($result);
	}

}
