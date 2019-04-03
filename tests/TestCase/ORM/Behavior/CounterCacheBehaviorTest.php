<?php
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
namespace Cake\Test\TestCase\ORM\Behavior;

use Cake\Database\Query;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Used for testing counter cache with custom finder
 */
class PostTable extends Table
{

    public function findPublished(Query $query, array $options)
    {
        return $query->where(['published' => true]);
    }
}

/**
 * CounterCacheBehavior test case
 */
class CounterCacheBehaviorTest extends TestCase
{

    /**
     * Fixture
     *
     * @var array
     */
    public $fixtures = [
        'core.counter_cache_categories',
        'core.counter_cache_posts',
        'core.counter_cache_comments',
        'core.counter_cache_users',
        'core.counter_cache_user_category_posts'
    ];

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');

        $this->user = TableRegistry::get('Users', [
            'table' => 'counter_cache_users',
            'connection' => $this->connection
        ]);

        $this->category = TableRegistry::get('Categories', [
            'table' => 'counter_cache_categories',
            'connection' => $this->connection
        ]);

        $this->comment = TableRegistry::get('Comments', [
            'alias' => 'Comment',
            'table' => 'counter_cache_comments',
            'connection' => $this->connection
        ]);

        $this->post = new PostTable([
            'alias' => 'Post',
            'table' => 'counter_cache_posts',
            'connection' => $this->connection
        ]);

        $this->userCategoryPosts = new Table([
            'alias' => 'UserCategoryPosts',
            'table' => 'counter_cache_user_category_posts',
            'connection' => $this->connection
        ]);
    }

    /**
     * teardown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        unset($this->user, $this->post);
        TableRegistry::clear();
    }

    /**
     * Testing simple counter caching when adding a record
     *
     * @return void
     */
    public function testAdd()
    {
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
    public function testAddIgnore()
    {
        $this->post->belongsTo('Users');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'post_count'
            ]
        ]);

        $before = $this->_getUser();
        $entity = $this->_getEntity();
        $this->post->save($entity, ['ignoreCounterCache' => true]);
        $after = $this->_getUser();

        $this->assertEquals(2, $before->get('post_count'));
        $this->assertEquals(2, $after->get('post_count'));
    }

    /**
     * Testing simple counter caching when adding a record
     *
     * @return void
     */
    public function testAddScope()
    {
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
    public function testDelete()
    {
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
     * Testing simple counter caching when deleting a record
     *
     * @return void
     */
    public function testDeleteIgnore()
    {
        $this->post->belongsTo('Users');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'post_count'
            ]
        ]);

        $before = $this->_getUser();
        $post = $this->post->find('all')
            ->first();
        $this->post->delete($post, ['ignoreCounterCache' => true]);
        $after = $this->_getUser();

        $this->assertEquals(2, $before->get('post_count'));
        $this->assertEquals(2, $after->get('post_count'));
    }

    /**
     * Testing update simple counter caching when updating a record association
     *
     * @return void
     */
    public function testUpdate()
    {
        $this->post->belongsTo('Users');
        $this->post->belongsTo('Categories');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'post_count'
            ],
            'Categories' => [
                'post_count'
            ],
        ]);

        $user1 = $this->_getUser(1);
        $user2 = $this->_getUser(2);
        $category1 = $this->_getCategory(1);
        $category2 = $this->_getCategory(2);
        $post = $this->post->find('all')->first();
        $this->assertEquals(2, $user1->get('post_count'));
        $this->assertEquals(1, $user2->get('post_count'));
        $this->assertEquals(1, $category1->get('post_count'));
        $this->assertEquals(2, $category2->get('post_count'));

        $entity = $this->post->patchEntity($post, ['user_id' => 2, 'category_id' => 2]);
        $this->post->save($entity);

        $user1 = $this->_getUser(1);
        $user2 = $this->_getUser(2);
        $category1 = $this->_getCategory(1);
        $category2 = $this->_getCategory(2);
        $this->assertEquals(1, $user1->get('post_count'));
        $this->assertEquals(2, $user2->get('post_count'));
        $this->assertEquals(0, $category1->get('post_count'));
        $this->assertEquals(3, $category2->get('post_count'));
    }

    /**
     * Testing counter cache with custom find
     *
     * @return void
     */
    public function testCustomFind()
    {
        $this->post->belongsTo('Users');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'posts_published' => [
                    'finder' => 'published'
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
    public function testLambdaNumber()
    {
        $this->post->belongsTo('Users');

        $table = $this->post;
        $entity = $this->_getEntity();

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'posts_published' => function (Event $orgEvent, EntityInterface $orgEntity, Table $orgTable) use ($entity, $table) {
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
     * Testing counter cache with lambda returning number and changing of related ID
     *
     * @return void
     */
    public function testLambdaNumberUpdate()
    {
        $this->post->belongsTo('Users');

        $table = $this->post;
        $entity = $this->_getEntity();

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'posts_published' => function (Event $orgEvent, EntityInterface $orgEntity, Table $orgTable, $original) use ($entity, $table) {
                    $this->assertSame($orgTable, $table);
                    $this->assertSame($orgEntity, $entity);

                    if (!$original) {
                        return 2;
                    } else {
                        return 1;
                    }
                }
            ]
        ]);

        $this->post->save($entity);
        $between = $this->_getUser();
        $entity->user_id = 2;
        $this->post->save($entity);
        $afterUser1 = $this->_getUser(1);
        $afterUser2 = $this->_getUser(2);

        $this->assertEquals(2, $between->get('posts_published'));
        $this->assertEquals(1, $afterUser1->get('posts_published'));
        $this->assertEquals(2, $afterUser2->get('posts_published'));
    }

    /**
     * Testing counter cache with lambda returning a subquery
     *
     * @return void
     */
    public function testLambdaSubquery()
    {
        $this->post->belongsTo('Users');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'posts_published' => function (Event $event, EntityInterface $entity, Table $table) {
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
    public function testMultiple()
    {
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
     * Tests to see that the binding key configuration is respected.
     *
     * @return void
     */
    public function testBindingKey()
    {
        $this->post->hasMany('UserCategoryPosts', [
            'bindingKey' => ['category_id', 'user_id'],
            'foreignKey' => ['category_id', 'user_id']
        ]);
        $this->post->association('UserCategoryPosts')->target($this->userCategoryPosts);
        $this->post->addBehavior('CounterCache', [
            'UserCategoryPosts' => ['post_count']
        ]);

        $before = $this->userCategoryPosts->find()
            ->where(['user_id' => 1, 'category_id' => 2])
            ->first();
        $entity = $this->_getEntity()->set('category_id', 2);
        $this->post->save($entity);
        $after = $this->userCategoryPosts->find()
            ->where(['user_id' => 1, 'category_id' => 2])
            ->first();

        $this->assertEquals(1, $before->get('post_count'));
        $this->assertEquals(2, $after->get('post_count'));
    }

    /**
     * Testing the ignore if dirty option
     *
     * @return void
     */
    public function testIgnoreDirty()
    {
        $this->post->belongsTo('Users');
        $this->comment->belongsTo('Users');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'post_count' => [
                    'ignoreDirty' => true
                ],
                'comment_count' => [
                    'ignoreDirty' => true
                ],
            ]
        ]);

        $user = $this->_getUser(1);
        $this->assertSame(2, $user->get('post_count'));
        $this->assertSame(2, $user->get('comment_count'));
        $this->assertSame(1, $user->get('posts_published'));

        $post = $this->post->find('all')
            ->contain('Users')
            ->where(['title' => 'Rock and Roll'])
            ->first();
        $post = $this->post->patchEntity($post, [
            'posts_published' => true,
            'user' => [
                'id' => 1,
                'post_count' => 10,
                'comment_count' => 10
            ]
        ]);
        $save = $this->post->save($post);

        $user = $this->_getUser(1);
        $this->assertSame(10, $user->get('post_count'));
        $this->assertSame(10, $user->get('comment_count'));
        $this->assertSame(1, $user->get('posts_published'));
    }

    /**
     * Testing the ignore if dirty option with just one field set to ignoreDirty
     *
     * @return void
     */
    public function testIgnoreDirtyMixed()
    {
        $this->post->belongsTo('Users');
        $this->comment->belongsTo('Users');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'post_count' => [
                    'ignoreDirty' => true
                ]
            ]
        ]);

        $user = $this->_getUser(1);
        $this->assertSame(2, $user->get('post_count'));
        $this->assertSame(2, $user->get('comment_count'));
        $this->assertSame(1, $user->get('posts_published'));

        $post = $this->post->find('all')
            ->contain('Users')
            ->where(['title' => 'Rock and Roll'])
            ->first();
        $post = $this->post->patchEntity($post, [
            'posts_published' => true,
            'user' => [
                'id' => 1,
                'post_count' => 10
            ]
        ]);
        $save = $this->post->save($post);

        $user = $this->_getUser(1);
        $this->assertSame(10, $user->get('post_count'));
        $this->assertSame(2, $user->get('comment_count'));
        $this->assertSame(1, $user->get('posts_published'));
    }

    /**
     * Get a new Entity
     *
     * @return Entity
     */
    protected function _getEntity()
    {
        return new Entity([
            'title' => 'Test 123',
            'user_id' => 1
        ]);
    }

    /**
     * Returns entity for user
     *
     * @return Entity
     */
    protected function _getUser($id = 1)
    {
        return $this->user->get($id);
    }

    /**
     * Returns entity for category
     *
     * @return Entity
     */
    protected function _getCategory($id = 1)
    {
        return $this->category->find('all')->where(['id' => $id])->first();
    }
}
