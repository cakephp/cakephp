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
namespace Cake\Test\TestCase\ORM\Behavior;

use Cake\Database\Driver\Sqlserver;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use TestApp\Model\Table\PublishedPostsTable;

/**
 * CounterCacheBehavior test case
 */
class CounterCacheBehaviorTest extends TestCase
{
    /**
     * @var \TestApp\Model\Table\PublishedPostsTable
     */
    protected $post;

    /**
     * @var \TestApp\Model\Table\PublishedPostsTable
     */
    protected $user;

    /**
     * @var \TestApp\Model\Table\PublishedPostsTable
     */
    protected $category;

    /**
     * @var \TestApp\Model\Table\PublishedPostsTable
     */
    protected $comment;

    /**
     * @var \TestApp\Model\Table\PublishedPostsTable
     */
    protected $userCategoryPosts;

    /**
     * @var \Cake\Datasource\ConnectionInterface
     */
    protected $connection;

    /**
     * Fixture
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'core.CounterCacheCategories',
        'core.CounterCachePosts',
        'core.CounterCacheComments',
        'core.CounterCacheUsers',
        'core.CounterCacheUserCategoryPosts',
    ];

    /**
     * setup
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');

        $this->user = $this->getTableLocator()->get('Users', [
            'table' => 'counter_cache_users',
            'connection' => $this->connection,
        ]);

        $this->category = $this->getTableLocator()->get('Categories', [
            'table' => 'counter_cache_categories',
            'connection' => $this->connection,
        ]);

        $this->comment = $this->getTableLocator()->get('Comments', [
            'alias' => 'Comment',
            'table' => 'counter_cache_comments',
            'connection' => $this->connection,
        ]);

        $this->post = new PublishedPostsTable([
            'alias' => 'Post',
            'table' => 'counter_cache_posts',
            'connection' => $this->connection,
        ]);

        $this->userCategoryPosts = new Table([
            'alias' => 'UserCategoryPosts',
            'table' => 'counter_cache_user_category_posts',
            'connection' => $this->connection,
        ]);
    }

    /**
     * teardown
     */
    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->user, $this->post);
    }

    /**
     * Testing simple counter caching when adding a record
     */
    public function testAdd(): void
    {
        $this->skipIf(
            $this->connection->getDriver() instanceof Sqlserver,
            'This test fails sporadically in SQLServer'
        );

        $this->post->belongsTo('Users');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'post_count',
            ],
        ]);

        $before = $this->_getUser();
        $entity = $this->_getEntity();
        $this->post->save($entity);
        $after = $this->_getUser();

        $this->assertSame(2, $before->get('post_count'));
        $this->assertSame(3, $after->get('post_count'));
    }

    /**
     * Testing simple counter caching when adding a record
     */
    public function testAddIgnore(): void
    {
        $this->post->belongsTo('Users');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'post_count',
            ],
        ]);

        $before = $this->_getUser();
        $entity = $this->_getEntity();
        $this->post->save($entity, ['ignoreCounterCache' => true]);
        $after = $this->_getUser();

        $this->assertSame(2, $before->get('post_count'));
        $this->assertSame(2, $after->get('post_count'));
    }

    /**
     * Testing simple counter caching when adding a record
     */
    public function testAddScope(): void
    {
        $this->post->belongsTo('Users');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'posts_published' => [
                    'conditions' => [
                        'published' => true,
                    ],
                ],
            ],
        ]);

        $before = $this->_getUser();
        $entity = $this->_getEntity()->set('published', true);
        $this->post->save($entity);
        $after = $this->_getUser();

        $this->assertSame(1, $before->get('posts_published'));
        $this->assertSame(2, $after->get('posts_published'));
    }

    public function testSaveWithNullForeignKey(): void
    {
        $this->comment->belongsTo('Users');

        $this->comment->addBehavior('CounterCache', [
            'Users' => [
                'comment_count',
            ],
        ]);

        $entity = new Entity([
            'title' => 'Orphan comment',
            'user_id' => null,
        ]);
        $this->comment->saveOrFail($entity);
        $this->assertTrue(true);
    }

    /**
     * Testing simple counter caching when deleting a record
     */
    public function testDelete(): void
    {
        $this->post->belongsTo('Users');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'post_count',
            ],
        ]);

        $before = $this->_getUser();
        $post = $this->post->find('all')->first();
        $this->post->delete($post);
        $after = $this->_getUser();

        $this->assertSame(2, $before->get('post_count'));
        $this->assertSame(1, $after->get('post_count'));
    }

    /**
     * Testing simple counter caching when deleting a record
     */
    public function testDeleteIgnore(): void
    {
        $this->post->belongsTo('Users');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'post_count',
            ],
        ]);

        $before = $this->_getUser();
        $post = $this->post->find('all')
            ->first();
        $this->post->delete($post, ['ignoreCounterCache' => true]);
        $after = $this->_getUser();

        $this->assertSame(2, $before->get('post_count'));
        $this->assertSame(2, $after->get('post_count'));
    }

    /**
     * Testing update simple counter caching when updating a record association
     */
    public function testUpdate(): void
    {
        $this->post->belongsTo('Users');
        $this->post->belongsTo('Categories');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'post_count',
            ],
            'Categories' => [
                'post_count',
            ],
        ]);

        $user1 = $this->_getUser(1);
        $user2 = $this->_getUser(2);
        $category1 = $this->_getCategory(1);
        $category2 = $this->_getCategory(2);
        $post = $this->post->find('all')->first();
        $this->assertSame(2, $user1->get('post_count'));
        $this->assertSame(1, $user2->get('post_count'));
        $this->assertSame(1, $category1->get('post_count'));
        $this->assertSame(2, $category2->get('post_count'));

        $entity = $this->post->patchEntity($post, ['user_id' => 2, 'category_id' => 2]);
        $this->post->save($entity);

        $user1 = $this->_getUser(1);
        $user2 = $this->_getUser(2);
        $category1 = $this->_getCategory(1);
        $category2 = $this->_getCategory(2);
        $this->assertSame(1, $user1->get('post_count'));
        $this->assertSame(2, $user2->get('post_count'));
        $this->assertSame(0, $category1->get('post_count'));
        $this->assertSame(3, $category2->get('post_count'));

        $entity = $this->post->patchEntity($post, ['user_id' => null, 'category_id' => null]);
        $this->post->save($entity);

        $user2 = $this->_getUser(2);
        $category2 = $this->_getCategory(2);
        $this->assertSame(1, $user2->get('post_count'));
        $this->assertSame(2, $category2->get('post_count'));

        $entity = $this->post->patchEntity($post, ['user_id' => 2, 'category_id' => 2]);
        $this->post->save($entity);

        $user2 = $this->_getUser(2);
        $category2 = $this->_getCategory(2);
        $this->assertSame(2, $user2->get('post_count'));
        $this->assertSame(3, $category2->get('post_count'));
    }

    /**
     * Testing counter cache with custom find
     */
    public function testCustomFind(): void
    {
        $this->post->belongsTo('Users');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'posts_published' => [
                    'finder' => 'published',
                ],
            ],
        ]);

        $before = $this->_getUser();
        $entity = $this->_getEntity()->set('published', true);
        $this->post->save($entity);
        $after = $this->_getUser();

        $this->assertSame(1, $before->get('posts_published'));
        $this->assertSame(2, $after->get('posts_published'));
    }

    /**
     * Testing counter cache with lambda returning number
     */
    public function testLambdaNumber(): void
    {
        $this->post->belongsTo('Users');

        $table = $this->post;
        $entity = $this->_getEntity();

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'posts_published' => function (EventInterface $orgEvent, EntityInterface $orgEntity, Table $orgTable) use ($entity, $table) {
                    $this->assertSame($orgTable, $table);
                    $this->assertSame($orgEntity, $entity);

                    return 2;
                },
            ],
        ]);

        $before = $this->_getUser();
        $this->post->save($entity);
        $after = $this->_getUser();

        $this->assertSame(1, $before->get('posts_published'));
        $this->assertSame(2, $after->get('posts_published'));
    }

    /**
     * Testing counter cache with lambda returning false
     */
    public function testLambdaFalse(): void
    {
        $this->post->belongsTo('Users');

        $table = $this->post;
        $entity = $this->_getEntity();

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'posts_published' => function (EventInterface $orgEvent, EntityInterface $orgEntity, Table $orgTable) use ($entity, $table) {
                    $this->assertSame($orgTable, $table);
                    $this->assertSame($orgEntity, $entity);

                    return false;
                },
            ],
        ]);

        $before = $this->_getUser();
        $this->post->save($entity);
        $after = $this->_getUser();

        $this->assertSame(1, $before->get('posts_published'));
        $this->assertSame(1, $after->get('posts_published'));
    }

    /**
     * Testing counter cache with lambda returning number and changing of related ID
     */
    public function testLambdaNumberUpdate(): void
    {
        $this->post->belongsTo('Users');

        $table = $this->post;
        $entity = $this->_getEntity();

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'posts_published' => function (EventInterface $orgEvent, EntityInterface $orgEntity, Table $orgTable, $original) use ($entity, $table) {
                    $this->assertSame($orgTable, $table);
                    $this->assertSame($orgEntity, $entity);

                    if (!$original) {
                        return 2;
                    }

                    return 1;
                },
            ],
        ]);

        $this->post->save($entity);
        $between = $this->_getUser();
        $entity->user_id = 2;
        $this->post->save($entity);
        $afterUser1 = $this->_getUser(1);
        $afterUser2 = $this->_getUser(2);

        $this->assertSame(2, $between->get('posts_published'));
        $this->assertSame(1, $afterUser1->get('posts_published'));
        $this->assertSame(2, $afterUser2->get('posts_published'));
    }

    /**
     * Testing counter cache with lambda returning a subquery
     */
    public function testLambdaSubquery(): void
    {
        $this->post->belongsTo('Users');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'posts_published' => function (EventInterface $event, EntityInterface $entity, Table $table) {
                    return $table->getConnection()->selectQuery(4);
                },
            ],
        ]);

        $before = $this->_getUser();
        $entity = $this->_getEntity();
        $this->post->save($entity);
        $after = $this->_getUser();

        $this->assertSame(1, $before->get('posts_published'));
        $this->assertSame(4, $after->get('posts_published'));
    }

    /**
     * Testing multiple counter cache when adding a record
     */
    public function testMultiple(): void
    {
        $this->post->belongsTo('Users');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'post_count',
                'posts_published' => [
                    'conditions' => [
                        'published' => true,
                    ],
                ],
            ],
        ]);

        $before = $this->_getUser();
        $entity = $this->_getEntity()->set('published', true);
        $this->post->save($entity);
        $after = $this->_getUser();

        $this->assertSame(1, $before->get('posts_published'));
        $this->assertSame(2, $after->get('posts_published'));

        $this->assertSame(2, $before->get('post_count'));
        $this->assertSame(3, $after->get('post_count'));
    }

    /**
     * Tests to see that the binding key configuration is respected.
     */
    public function testBindingKey(): void
    {
        $this->post->hasMany('UserCategoryPosts', [
            'bindingKey' => ['category_id', 'user_id'],
            'foreignKey' => ['category_id', 'user_id'],
        ]);
        $this->post->getAssociation('UserCategoryPosts')->setTarget($this->userCategoryPosts);
        $this->post->addBehavior('CounterCache', [
            'UserCategoryPosts' => ['post_count'],
        ]);

        $before = $this->userCategoryPosts->find()
            ->where(['user_id' => 1, 'category_id' => 2])
            ->first();
        $entity = $this->_getEntity()->set('category_id', 2);
        $this->post->save($entity);
        $after = $this->userCategoryPosts->find()
            ->where(['user_id' => 1, 'category_id' => 2])
            ->first();

        $this->assertSame(1, $before->get('post_count'));
        $this->assertSame(2, $after->get('post_count'));
    }

    /**
     * Testing the ignore if dirty option
     */
    public function testIgnoreDirty(): void
    {
        $this->post->belongsTo('Users');
        $this->comment->belongsTo('Users');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'post_count' => [
                    'ignoreDirty' => true,
                ],
                'comment_count' => [
                    'ignoreDirty' => true,
                ],
            ],
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
                'comment_count' => 10,
            ],
        ]);
        $this->post->save($post);

        $user = $this->_getUser(1);
        $this->assertSame(10, $user->get('post_count'));
        $this->assertSame(10, $user->get('comment_count'));
        $this->assertSame(1, $user->get('posts_published'));
    }

    /**
     * Testing the ignore if dirty option with just one field set to ignoreDirty
     */
    public function testIgnoreDirtyMixed(): void
    {
        $this->post->belongsTo('Users');
        $this->comment->belongsTo('Users');

        $this->post->addBehavior('CounterCache', [
            'Users' => [
                'post_count' => [
                    'ignoreDirty' => true,
                ],
            ],
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
            ],
        ]);
        $this->post->save($post);

        $user = $this->_getUser(1);
        $this->assertSame(10, $user->get('post_count'));
        $this->assertSame(2, $user->get('comment_count'));
        $this->assertSame(1, $user->get('posts_published'));
    }

    /**
     * Get a new Entity
     */
    protected function _getEntity(): Entity
    {
        return new Entity([
            'title' => 'Test 123',
            'user_id' => 1,
        ]);
    }

    /**
     * Returns entity for user
     */
    protected function _getUser(int $id = 1): Entity
    {
        return $this->user->get($id);
    }

    /**
     * Returns entity for category
     */
    protected function _getCategory(int $id = 1): Entity
    {
        return $this->category->find('all')->where(['id' => $id])->first();
    }
}
