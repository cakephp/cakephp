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

use Cake\Database\Query;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\Model\Behavior\CounterCacheBehavior;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Used for testing counter cache with custom finder
 */
class PostTable extends Table {

	public function findPublished(Query $query, array $options) {
		return $query->where(['published' => true]);
	}

}

/**
 * CounterCacheBehavior test case
 */
class CounterCacheBehaviorTest extends TestCase {

/**
 * Fixture
 *
 * @var array
 */
	public $fixtures = [
		'core.counter_cache_user',
		'core.counter_cache_post'
	];

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->connection = ConnectionManager::get('test');

		$this->user = TableRegistry::get('Users', [
			'table' => 'counter_cache_users',
			'connection' => $this->connection
		]);

		$this->post = new PostTable([
			'alias' => 'Post',
			'table' => 'counter_cache_posts',
			'connection' => $this->connection
		]);
	}

/**
 * teardown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();

		unset($this->user, $this->post);
		TableRegistry::clear();
	}

/**
 * Testing simple counter caching when adding a record
 *
 * @return void
 */
	public function testAdd() {
		$this->post->belongsTo('Users');

		$this->post->addBehavior('CounterCache', [
			'Users' => [
				'post_count'
			]
		]);

		$before = $this->_getUser();
		$entity = $this->_getEntity();
		$this->post->save($entity);
		$after = $this->_getUser();

		$this->assertEquals(2, $before->get('post_count'));
		$this->assertEquals(3, $after->get('post_count'));
	}

/**
 * Testing simple counter caching when adding a record
 *
 * @return void
 */
	public function testAddScope() {
		$this->post->belongsTo('Users');

		$this->post->addBehavior('CounterCache', [
			'Users' => [
				'posts_published' => [
					'conditions' => [
						'published' => true
					]
				]
			]
		]);

		$before = $this->_getUser();
		$entity = $this->_getEntity()->set('published', true);
		$this->post->save($entity);
		$after = $this->_getUser();

		$this->assertEquals(1, $before->get('posts_published'));
		$this->assertEquals(2, $after->get('posts_published'));
	}

/**
 * Testing simple counter caching when deleting a record
 *
 * @return void
 */
	public function testDelete() {
		$this->post->belongsTo('Users');

		$this->post->addBehavior('CounterCache', [
			'Users' => [
				'post_count'
			]
		]);

		$before = $this->_getUser();
		$post = $this->post->find('all')->first();
		$this->post->delete($post);
		$after = $this->_getUser();

		$this->assertEquals(2, $before->get('post_count'));
		$this->assertEquals(1, $after->get('post_count'));
	}

/**
 * Testing counter cache with custom find
 *
 * @return void
 */
	public function testCustomFind() {
		$this->post->belongsTo('Users');

		$this->post->addBehavior('CounterCache', [
			'Users' => [
				'posts_published' => [
					'findType' => 'published'
				]
			]
		]);

		$before = $this->_getUser();
		$entity = $this->_getEntity()->set('published', true);
		$this->post->save($entity);
		$after = $this->_getUser();

		$this->assertEquals(1, $before->get('posts_published'));
		$this->assertEquals(2, $after->get('posts_published'));
	}

/**
 * Testing counter cache with lambda returning number
 *
 * @return void
 */
	public function testLambdaNumber() {
		$this->post->belongsTo('Users');

		$table = $this->post;
		$entity = $this->_getEntity();

		$this->post->addBehavior('CounterCache', [
			'Users' => [
				'posts_published' => function (Event $orgEvent, Entity $orgEntity, Table $orgTable) use ($entity, $table) {
					$this->assertSame($orgTable, $table);
					$this->assertSame($orgEntity, $entity);

					return 2;
				}
			]
		]);

		$before = $this->_getUser();
		$this->post->save($entity);
		$after = $this->_getUser();

		$this->assertEquals(1, $before->get('posts_published'));
		$this->assertEquals(2, $after->get('posts_published'));
	}

/**
 * Testing counter cache with lambda returning subqueryn
 *
 * @return void
 */
	public function testLambdaSubquery() {
		$this->post->belongsTo('Users');

		$this->post->addBehavior('CounterCache', [
			'Users' => [
				'posts_published' => function (Event $event, Entity $entity, Table $table) {
					$query = new Query($this->connection);
					return $query->select(4);
				}
			]
		]);

		$before = $this->_getUser();
		$entity = $this->_getEntity();
		$this->post->save($entity);
		$after = $this->_getUser();

		$this->assertEquals(1, $before->get('posts_published'));
		$this->assertEquals(4, $after->get('posts_published'));
	}

/**
 * Testing multiple counter cache when adding a record
 *
 * @return void
 */
	public function testMultiple() {
		$this->post->belongsTo('Users');

		$this->post->addBehavior('CounterCache', [
			'Users' => [
				'post_count',
				'posts_published' => [
					'conditions' => [
						'published' => true
					]
				]
			]
		]);

		$before = $this->_getUser();
		$entity = $this->_getEntity()->set('published', true);
		$this->post->save($entity);
		$after = $this->_getUser();

		$this->assertEquals(1, $before->get('posts_published'));
		$this->assertEquals(2, $after->get('posts_published'));

		$this->assertEquals(2, $before->get('post_count'));
		$this->assertEquals(3, $after->get('post_count'));
	}

/**
 * Get a new Entity
 *
 * @return Entity
 */
	protected function _getEntity() {
		return new Entity([
			'title' => 'Test 123',
			'user_id' => 1
		]);
	}

/**
 * Returns entity for user 1
 *
 * @return Entity
 */
	protected function _getUser() {
		return $this->user->find('all')->where(['id' => 1])->first();
	}
}
