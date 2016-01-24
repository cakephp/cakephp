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
namespace Cake\Test\TestCase\ORM;

use Cake\Database\Expression\IdentifierExpression;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\ORM\Marshaller;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;

/**
 * Test entity for mass assignment.
 */
class OpenEntity extends Entity
{

    protected $_accessible = [
        '*' => true,
    ];
}

/**
 * Test entity for mass assignment.
 */
class Tag extends Entity
{

    protected $_accessible = [
        'tag' => true,
    ];
}

/**
 * Test entity for mass assignment.
 */
class ProtectedArticle extends Entity
{

    protected $_accessible = [
        'title' => true,
        'body' => true
    ];
}

/**
 * Test stub for greedy find operations.
 */
class GreedyCommentsTable extends Table
{
    /**
     * initialize hook
     *
     * @param $config Config data.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->table('comments');
        $this->alias('Comments');
    }

    /**
     * Overload find to cause issues.
     *
     * @param string $type Find type
     * @param array $options find options
     * @return object
     */
    public function find($type = 'all', $options = [])
    {
        if (empty($options['conditions'])) {
            $options['conditions'] = [];
        }
        $options['conditions'] = array_merge($options['conditions'], ['Comments.published' => 'Y']);
        return parent::find($type, $options);
    }
}

/**
 * Marshaller test case
 */
class MarshallerTest extends TestCase
{

    public $fixtures = [
        'core.articles',
        'core.articles_tags',
        'core.comments',
        'core.special_tags',
        'core.tags',
        'core.users'
    ];

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $articles = TableRegistry::get('Articles');
        $articles->belongsTo('Users', [
            'foreignKey' => 'author_id'
        ]);
        $articles->hasMany('Comments');
        $articles->belongsToMany('Tags');

        $comments = TableRegistry::get('Comments');
        $users = TableRegistry::get('Users');
        $tags = TableRegistry::get('Tags');
        $articleTags = TableRegistry::get('ArticlesTags');

        $comments->belongsTo('Articles');
        $comments->belongsTo('Users');

        $articles->entityClass(__NAMESPACE__ . '\OpenEntity');
        $comments->entityClass(__NAMESPACE__ . '\OpenEntity');
        $users->entityClass(__NAMESPACE__ . '\OpenEntity');
        $tags->entityClass(__NAMESPACE__ . '\OpenEntity');
        $articleTags->entityClass(__NAMESPACE__ . '\OpenEntity');

        $this->articles = $articles;
        $this->comments = $comments;
        $this->users = $users;
        $this->tags = $tags;
    }

    /**
     * Teardown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
        unset($this->articles, $this->comments, $this->users);
    }

    /**
     * Test one() in a simple use.
     *
     * @return void
     */
    public function testOneSimple()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'not_in_schema' => true
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, []);

        $this->assertInstanceOf('Cake\ORM\Entity', $result);
        $this->assertEquals($data, $result->toArray());
        $this->assertTrue($result->dirty(), 'Should be a dirty entity.');
        $this->assertTrue($result->isNew(), 'Should be new');
        $this->assertEquals('Articles', $result->source());
    }

    /**
     * Test that marshalling an entity with '' for pk values results
     * in no pk value being set.
     *
     * @return void
     */
    public function testOneEmptyStringPrimaryKey()
    {
        $data = [
            'id' => '',
            'username' => 'superuser',
            'password' => 'root',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00')
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, []);

        $this->assertFalse($result->dirty('id'));
        $this->assertNull($result->id);
    }

    /**
     * Test marshalling datetime/date field.
     *
     * @return void
     */
    public function testOneWithDatetimeField()
    {
        $data = [
            'comment' => 'My Comment text',
            'created' => [
                'year' => '2014',
                'month' => '2',
                'day' => 14
            ]
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->one($data, []);

        $this->assertEquals(new Time('2014-02-14 00:00:00'), $result->created);

        $data['created'] = [
            'year' => '2014',
            'month' => '2',
            'day' => 14,
            'hour' => 9,
            'minute' => 25,
            'meridian' => 'pm'
        ];
        $result = $marshall->one($data, []);
        $this->assertEquals(new Time('2014-02-14 21:25:00'), $result->created);

        $data['created'] = [
            'year' => '2014',
            'month' => '2',
            'day' => 14,
            'hour' => 9,
            'minute' => 25,
        ];
        $result = $marshall->one($data, []);
        $this->assertEquals(new Time('2014-02-14 09:25:00'), $result->created);

        $data['created'] = '2014-02-14 09:25:00';
        $result = $marshall->one($data, []);
        $this->assertEquals(new Time('2014-02-14 09:25:00'), $result->created);

        $data['created'] = 1392387900;
        $result = $marshall->one($data, []);
        $this->assertEquals($data['created'], $result->created->getTimestamp());
    }

    /**
     * Ensure that marshalling casts reasonably.
     *
     * @return void
     */
    public function testOneOnlyCastMatchingData()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 'derp',
            'created' => 'fale'
        ];
        $this->articles->entityClass(__NAMESPACE__ . '\OpenEntity');
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, []);

        $this->assertSame($data['title'], $result->title);
        $this->assertNull($result->author_id, 'No cast on bad data.');
        $this->assertSame($data['created'], $result->created, 'No cast on bad data.');
    }

    /**
     * Test one() follows mass-assignment rules.
     *
     * @return void
     */
    public function testOneAccessibleProperties()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'not_in_schema' => true
        ];
        $this->articles->entityClass(__NAMESPACE__ . '\ProtectedArticle');
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, []);

        $this->assertInstanceOf(__NAMESPACE__ . '\ProtectedArticle', $result);
        $this->assertNull($result->author_id);
        $this->assertNull($result->not_in_schema);
    }

    /**
     * Test one() supports accessibleFields option
     *
     * @return void
     */
    public function testOneAccessibleFieldsOption()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'not_in_schema' => true
        ];
        $this->articles->entityClass(__NAMESPACE__ . '\ProtectedArticle');

        $marshall = new Marshaller($this->articles);

        $result = $marshall->one($data, ['accessibleFields' => ['body' => false]]);
        $this->assertNull($result->body);

        $result = $marshall->one($data, ['accessibleFields' => ['author_id' => true]]);
        $this->assertEquals($data['author_id'], $result->author_id);
        $this->assertNull($result->not_in_schema);

        $result = $marshall->one($data, ['accessibleFields' => ['*' => true]]);
        $this->assertEquals($data['author_id'], $result->author_id);
        $this->assertTrue($result->not_in_schema);
    }

    /**
     * Test one() supports accessibleFields option for associations
     *
     * @return void
     */
    public function testOneAccessibleFieldsOptionForAssociations()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'user' => [
                'id' => 1,
                'username' => 'mark',
            ]
        ];
        $this->articles->entityClass(__NAMESPACE__ . '\ProtectedArticle');
        $this->users->entityClass(__NAMESPACE__ . '\ProtectedArticle');

        $marshall = new Marshaller($this->articles);

        $result = $marshall->one($data, [
            'associated' => [
                'Users' => ['accessibleFields' => ['id' => true]]
            ],
            'accessibleFields' => ['body' => false, 'user' => true]
        ]);
        $this->assertNull($result->body);
        $this->assertNull($result->user->username);
        $this->assertEquals(1, $result->user->id);
    }

    /**
     * test one() with a wrapping model name.
     *
     * @return void
     */
    public function testOneWithAdditionalName()
    {
        $data = [
            'Articles' => [
                'title' => 'My title',
                'body' => 'My content',
                'author_id' => 1,
                'not_in_schema' => true,
                'user' => [
                    'username' => 'mark',
                ]
            ]
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['associated' => ['Users']]);

        $this->assertInstanceOf('Cake\ORM\Entity', $result);
        $this->assertTrue($result->dirty(), 'Should be a dirty entity.');
        $this->assertTrue($result->isNew(), 'Should be new');
        $this->assertEquals($data['Articles']['title'], $result->title);
        $this->assertEquals($data['Articles']['user']['username'], $result->user->username);
    }

    /**
     * test one() with association data.
     *
     * @return void
     */
    public function testOneAssociationsSingle()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'comments' => [
                ['comment' => 'First post', 'user_id' => 2],
                ['comment' => 'Second post', 'user_id' => 2],
            ],
            'user' => [
                'username' => 'mark',
                'password' => 'secret'
            ]
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['associated' => ['Users']]);

        $this->assertEquals($data['title'], $result->title);
        $this->assertEquals($data['body'], $result->body);
        $this->assertEquals($data['author_id'], $result->author_id);

        $this->assertInternalType('array', $result->comments);
        $this->assertEquals($data['comments'], $result->comments);

        $this->assertInstanceOf('Cake\ORM\Entity', $result->user);
        $this->assertEquals($data['user']['username'], $result->user->username);
        $this->assertEquals($data['user']['password'], $result->user->password);
    }

    /**
     * test one() with association data.
     *
     * @return void
     */
    public function testOneAssociationsMany()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'comments' => [
                ['comment' => 'First post', 'user_id' => 2],
                ['comment' => 'Second post', 'user_id' => 2],
            ],
            'user' => [
                'username' => 'mark',
                'password' => 'secret'
            ]
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['associated' => ['Comments']]);

        $this->assertEquals($data['title'], $result->title);
        $this->assertEquals($data['body'], $result->body);
        $this->assertEquals($data['author_id'], $result->author_id);

        $this->assertInternalType('array', $result->comments);
        $this->assertCount(2, $result->comments);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->comments[0]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->comments[1]);
        $this->assertEquals($data['comments'][0]['comment'], $result->comments[0]->comment);

        $this->assertInternalType('array', $result->user);
        $this->assertEquals($data['user'], $result->user);
    }

    /**
     * Test building the _joinData entity for belongstomany associations.
     *
     * @return void
     */
    public function testOneBelongsToManyJoinData()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'tags' => [
                ['tag' => 'news', '_joinData' => ['active' => 1]],
                ['tag' => 'cakephp', '_joinData' => ['active' => 0]],
            ],
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, [
            'associated' => ['Tags']
        ]);

        $this->assertEquals($data['title'], $result->title);
        $this->assertEquals($data['body'], $result->body);

        $this->assertInternalType('array', $result->tags);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]);
        $this->assertEquals($data['tags'][0]['tag'], $result->tags[0]->tag);

        $this->assertInstanceOf(
            'Cake\ORM\Entity',
            $result->tags[0]->_joinData,
            '_joinData should be an entity.'
        );
        $this->assertEquals(
            $data['tags'][0]['_joinData']['active'],
            $result->tags[0]->_joinData->active,
            '_joinData should be an entity.'
        );
    }

    /**
     * Test that the onlyIds option restricts to only accepting ids for belongs to many associations.
     *
     * @return void
     */
    public function testOneBelongsToManyOnlyIdsRejectArray()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'tags' => [
                ['tag' => 'news'],
                ['tag' => 'cakephp'],
            ],
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, [
            'associated' => ['Tags' => ['onlyIds' => true]]
        ]);
        $this->assertEmpty($result->tags, 'Only ids should be marshalled.');
    }

    /**
     * Test that the onlyIds option restricts to only accepting ids for belongs to many associations.
     *
     * @return void
     */
    public function testOneBelongsToManyOnlyIdsWithIds()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'tags' => [
                '_ids' => [1, 2],
                ['tag' => 'news'],
            ],
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, [
            'associated' => ['Tags' => ['onlyIds' => true]]
        ]);
        $this->assertCount(2, $result->tags, 'Ids should be marshalled.');
    }

    /**
     * Test marshalling nested associations on the _joinData structure.
     *
     * @return void
     */
    public function testOneBelongsToManyJoinDataAssociated()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'tags' => [
                [
                    'tag' => 'news',
                    '_joinData' => [
                        'active' => 1,
                        'user' => ['username' => 'Bill'],
                    ]
                ],
                [
                    'tag' => 'cakephp',
                    '_joinData' => [
                        'active' => 0,
                        'user' => ['username' => 'Mark'],
                    ]
                ],
            ],
        ];

        $articlesTags = TableRegistry::get('ArticlesTags');
        $articlesTags->belongsTo('Users');

        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['associated' => ['Tags._joinData.Users']]);
        $this->assertInstanceOf(
            'Cake\ORM\Entity',
            $result->tags[0]->_joinData->user,
            'joinData should contain a user entity.'
        );
        $this->assertEquals('Bill', $result->tags[0]->_joinData->user->username);
        $this->assertInstanceOf(
            'Cake\ORM\Entity',
            $result->tags[1]->_joinData->user,
            'joinData should contain a user entity.'
        );
        $this->assertEquals('Mark', $result->tags[1]->_joinData->user->username);
    }

    /**
     * Test one() with with id and _joinData.
     *
     * @return void
     */
    public function testOneBelongsToManyJoinDataAssociatedWithIds()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'tags' => [
                3 => [
                    'id' => 1,
                    '_joinData' => [
                        'active' => 1,
                        'user' => ['username' => 'MyLux'],
                    ]
                ],
                5 => [
                    'id' => 2,
                    '_joinData' => [
                        'active' => 0,
                        'user' => ['username' => 'IronFall'],
                    ]
                ],
            ],
        ];

        $articlesTags = TableRegistry::get('ArticlesTags');
        $tags = TableRegistry::get('Tags');
        $t1 = $tags->find('all')->where(['id' => 1])->first();
        $t2 = $tags->find('all')->where(['id' => 2])->first();
        $articlesTags->belongsTo('Users');

        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['associated' => ['Tags._joinData.Users']]);
        $this->assertInstanceOf(
            'Cake\ORM\Entity',
            $result->tags[0]
        );
        $this->assertInstanceOf(
            'Cake\ORM\Entity',
            $result->tags[1]
        );

        $this->assertInstanceOf(
            'Cake\ORM\Entity',
            $result->tags[0]->_joinData->user
        );

        $this->assertInstanceOf(
            'Cake\ORM\Entity',
            $result->tags[1]->_joinData->user
        );
        $this->assertFalse($result->tags[0]->isNew(), 'Should not be new, as id is in db.');
        $this->assertFalse($result->tags[1]->isNew(), 'Should not be new, as id is in db.');
        $this->assertEquals($t1->tag, $result->tags[0]->tag);
        $this->assertEquals($t2->tag, $result->tags[1]->tag);
        $this->assertEquals($data['tags'][3]['_joinData']['user']['username'], $result->tags[0]->_joinData->user->username);
        $this->assertEquals($data['tags'][5]['_joinData']['user']['username'], $result->tags[1]->_joinData->user->username);
    }

    /**
     * Test belongsToMany association with mixed data and _joinData
     *
     * @return void
     */
    public function testOneBelongsToManyWithMixedJoinData()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'tags' => [
                [
                    'id' => 1,
                    '_joinData' => [
                        'active' => 0,
                    ]
                ],
                [
                    'name' => 'tag5',
                    '_joinData' => [
                        'active' => 1,
                    ]
                ]
            ]
        ];
        $marshall = new Marshaller($this->articles);

        $result = $marshall->one($data, ['associated' => ['Tags._joinData']]);

        $this->assertEquals($data['tags'][0]['id'], $result->tags[0]->id);
        $this->assertEquals($data['tags'][1]['name'], $result->tags[1]->name);
        $this->assertEquals(0, $result->tags[0]->_joinData->active);
        $this->assertEquals(1, $result->tags[1]->_joinData->active);
    }

    public function testOneBelongsToManyWithNestedAssociations()
    {
        $this->tags->belongsToMany('Articles');
        $data = [
            'name' => 'new tag',
            'articles' => [
                // This nested article exists, and we want to update it.
                [
                    'id' => 1,
                    'title' => 'New tagged article',
                    'body' => 'New tagged article',
                    'user' => [
                        'id' => 1,
                        'username' => 'newuser'
                    ],
                    'comments' => [
                        ['comment' => 'New comment', 'user_id' => 1],
                        ['comment' => 'Second comment', 'user_id' => 1],
                    ]
                ]
            ]
        ];
        $marshaller = new Marshaller($this->tags);
        $tag = $marshaller->one($data, ['associated' => ['Articles.Users', 'Articles.Comments']]);

        $this->assertNotEmpty($tag->articles);
        $this->assertCount(1, $tag->articles);
        $this->assertInstanceOf('Cake\ORM\Entity', $tag->articles[0]);
        $this->assertSame('New tagged article', $tag->articles[0]->title);

        $this->assertNotEmpty($tag->articles[0]->user);
        $this->assertInstanceOf('Cake\ORM\Entity', $tag->articles[0]->user);
        $this->assertSame('newuser', $tag->articles[0]->user->username);

        $this->assertNotEmpty($tag->articles[0]->comments);
        $this->assertCount(2, $tag->articles[0]->comments);
        $this->assertInstanceOf('Cake\ORM\Entity', $tag->articles[0]->comments[0]);
        $this->assertInstanceOf('Cake\ORM\Entity', $tag->articles[0]->comments[1]);
    }

    /**
     * Test belongsToMany association with mixed data and _joinData
     *
     * @return void
     */
    public function testBelongsToManyAddingNewExisting()
    {
        $this->tags->entityClass(__NAMESPACE__ . '\Tag');
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'tags' => [
                [
                    'id' => 1,
                    '_joinData' => [
                        'active' => 0,
                    ]
                ],
            ]
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['associated' => ['Tags._joinData']]);
        $data = [
            'title' => 'New Title',
            'tags' => [
                [
                    'id' => 1,
                    '_joinData' => [
                        'active' => 0,
                    ]
                ],
                [
                    'id' => 2,
                    '_joinData' => [
                        'active' => 1,
                    ]
                ]
            ]
        ];
        $result = $marshall->merge($result, $data, ['associated' => ['Tags._joinData']]);

        $this->assertEquals($data['title'], $result->title);
        $this->assertEquals($data['tags'][0]['id'], $result->tags[0]->id);
        $this->assertEquals($data['tags'][1]['id'], $result->tags[1]->id);
        $this->assertNotEmpty($result->tags[0]->_joinData);
        $this->assertNotEmpty($result->tags[1]->_joinData);
        $this->assertEquals(0, $result->tags[0]->_joinData->active);
        $this->assertEquals(1, $result->tags[1]->_joinData->active);
    }

    /**
     * Test belongsToMany association with mixed data and _joinData
     *
     * @return void
     */
    public function testBelongsToManyWithMixedJoinDataOutOfOrder()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'tags' => [
                [
                    'name' => 'tag5',
                    '_joinData' => [
                        'active' => 1,
                    ]
                ],
                [
                    'id' => 1,
                    '_joinData' => [
                        'active' => 0,
                    ]
                ],
                [
                    'name' => 'tag3',
                    '_joinData' => [
                        'active' => 1,
                    ]
                ],
            ]
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['associated' => ['Tags._joinData']]);

        $this->assertEquals($data['tags'][0]['name'], $result->tags[0]->name);
        $this->assertEquals($data['tags'][1]['id'], $result->tags[1]->id);
        $this->assertEquals($data['tags'][2]['name'], $result->tags[2]->name);

        $this->assertEquals(1, $result->tags[0]->_joinData->active);
        $this->assertEquals(0, $result->tags[1]->_joinData->active);
        $this->assertEquals(1, $result->tags[2]->_joinData->active);
    }

    /**
     * Test belongsToMany association with scalars
     *
     * @return void
     */
    public function testBelongsToManyInvalidData()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'tags' => [
                'id' => 1
            ]
        ];

        $article = $this->articles->newEntity($data, [
            'associated' => ['Tags']
        ]);
        $this->assertEmpty($article->tags, 'No entity should be created');

        $data['tags'] = 1;
        $article = $this->articles->newEntity($data, [
            'associated' => ['Tags']
        ]);
        $this->assertEmpty($article->tags, 'No entity should be created');
    }

    /**
     * Test belongsToMany association with mixed data array
     *
     * @return void
     */
    public function testBelongsToManyWithMixedData()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'tags' => [
                [
                    'name' => 'tag4'
                ],
                [
                    'name' => 'tag5'
                ],
                [
                    'id' => 1
                ]
            ]
        ];

        $tags = TableRegistry::get('Tags');

        $marshaller = new Marshaller($this->articles);
        $article = $marshaller->one($data, ['associated' => ['Tags']]);

        $this->assertEquals($data['tags'][0]['name'], $article->tags[0]->name);
        $this->assertEquals($data['tags'][1]['name'], $article->tags[1]->name);
        $this->assertEquals($article->tags[2], $tags->get(1));

        $this->assertEquals($article->tags[0]->isNew(), true);
        $this->assertEquals($article->tags[1]->isNew(), true);
        $this->assertEquals($article->tags[2]->isNew(), false);

        $tagCount = $tags->find()->count();
        $this->articles->save($article);

        $this->assertEquals($tagCount + 2, $tags->find()->count());
    }

    /**
     * Test HasMany association with _ids attribute
     *
     * @return void
     */
    public function testOneHasManyWithIds()
    {
        $data = [
            'title' => 'article',
            'body' => 'some content',
            'comments' => [
                '_ids' => [1, 2]
            ]
        ];

        $marshaller = new Marshaller($this->articles);
        $article = $marshaller->one($data, ['associated' => ['Comments']]);

        $this->assertEquals($article->comments[0], $this->comments->get(1));
        $this->assertEquals($article->comments[1], $this->comments->get(2));
    }

    /**
     * Test that the onlyIds option restricts to only accepting ids for hasmany associations.
     *
     * @return void
     */
    public function testOneHasManyOnlyIdsRejectArray()
    {
        $data = [
            'title' => 'article',
            'body' => 'some content',
            'comments' => [
                ['comment' => 'first comment'],
                ['comment' => 'second comment'],
            ]
        ];

        $marshaller = new Marshaller($this->articles);
        $article = $marshaller->one($data, [
            'associated' => ['Comments' => ['onlyIds' => true]]
        ]);
        $this->assertEmpty($article->comments);
    }

    /**
     * Test that the onlyIds option restricts to only accepting ids for hasmany associations.
     *
     * @return void
     */
    public function testOneHasManyOnlyIdsWithIds()
    {
        $data = [
            'title' => 'article',
            'body' => 'some content',
            'comments' => [
                '_ids' => [1, 2],
                ['comment' => 'first comment'],
            ]
        ];

        $marshaller = new Marshaller($this->articles);
        $article = $marshaller->one($data, [
            'associated' => ['Comments' => ['onlyIds' => true]]
        ]);
        $this->assertCount(2, $article->comments);
    }

    /**
     * Test HasMany association with invalid data
     *
     * @return void
     */
    public function testOneHasManyInvalidData()
    {
        $data = [
            'title' => 'new title',
            'body' => 'some content',
            'comments' => [
                'id' => 1
            ]
        ];

        $marshaller = new Marshaller($this->articles);
        $article = $marshaller->one($data, ['associated' => ['Comments']]);
        $this->assertEmpty($article->comments);

        $data['comments'] = 1;
        $article = $marshaller->one($data, ['associated' => ['Comments']]);
        $this->assertEmpty($article->comments);
    }

    /**
     * Test one() with deeper associations.
     *
     * @return void
     */
    public function testOneDeepAssociations()
    {
        $data = [
            'comment' => 'First post',
            'user_id' => 2,
            'article' => [
                'title' => 'Article title',
                'body' => 'Article body',
                'user' => [
                    'username' => 'mark',
                    'password' => 'secret'
                ],
            ]
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->one($data, ['associated' => ['Articles.Users']]);

        $this->assertEquals(
            $data['article']['title'],
            $result->article->title
        );
        $this->assertEquals(
            $data['article']['user']['username'],
            $result->article->user->username
        );
    }

    /**
     * Test many() with a simple set of data.
     *
     * @return void
     */
    public function testManySimple()
    {
        $data = [
            ['comment' => 'First post', 'user_id' => 2],
            ['comment' => 'Second post', 'user_id' => 2],
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->many($data);

        $this->assertCount(2, $result);
        $this->assertInstanceOf('Cake\ORM\Entity', $result[0]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result[1]);
        $this->assertEquals($data[0]['comment'], $result[0]->comment);
        $this->assertEquals($data[1]['comment'], $result[1]->comment);
    }

    /**
     * Test many() with some invalid data
     *
     * @return void
     */
    public function testManyInvalidData()
    {
        $data = [
            ['id' => 2, 'comment' => 'Changed 2', 'user_id' => 2],
            ['id' => 1, 'comment' => 'Changed 1', 'user_id' => 1],
            '_csrfToken' => 'abc123',
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->many($data);

        $this->assertCount(2, $result);
    }

    /**
     * test many() with nested associations.
     *
     * @return void
     */
    public function testManyAssociations()
    {
        $data = [
            [
                'comment' => 'First post',
                'user_id' => 2,
                'user' => [
                    'username' => 'mark',
                ],
            ],
            [
                'comment' => 'Second post',
                'user_id' => 2,
                'user' => [
                    'username' => 'jose',
                ],
            ],
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->many($data, ['associated' => ['Users']]);

        $this->assertCount(2, $result);
        $this->assertInstanceOf('Cake\ORM\Entity', $result[0]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result[1]);
        $this->assertEquals(
            $data[0]['user']['username'],
            $result[0]->user->username
        );
        $this->assertEquals(
            $data[1]['user']['username'],
            $result[1]->user->username
        );
    }

    /**
     * Test generating a list of entities from a list of ids.
     *
     * @return void
     */
    public function testOneGenerateBelongsToManyEntitiesFromIds()
    {
        $data = [
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => ['_ids' => '']
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['associated' => ['Tags']]);
        $this->assertCount(0, $result->tags);

        $data = [
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => ['_ids' => false]
        ];
        $result = $marshall->one($data, ['associated' => ['Tags']]);
        $this->assertCount(0, $result->tags);

        $data = [
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => ['_ids' => null]
        ];
        $result = $marshall->one($data, ['associated' => ['Tags']]);
        $this->assertCount(0, $result->tags);

        $data = [
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => ['_ids' => []]
        ];
        $result = $marshall->one($data, ['associated' => ['Tags']]);
        $this->assertCount(0, $result->tags);

        $data = [
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => ['_ids' => [1, 2, 3]]
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['associated' => ['Tags']]);

        $this->assertCount(3, $result->tags);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[1]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[2]);
    }

    /**
     * Test merge() in a simple use.
     *
     * @return void
     */
    public function testMergeSimple()
    {
        $data = [
            'title' => 'My title',
            'author_id' => 1,
            'not_in_schema' => true
        ];
        $marshall = new Marshaller($this->articles);
        $entity = new Entity([
            'title' => 'Foo',
            'body' => 'My Content'
        ]);
        $entity->accessible('*', true);
        $entity->isNew(false);
        $entity->clean();
        $result = $marshall->merge($entity, $data, []);

        $this->assertSame($entity, $result);
        $this->assertEquals($data + ['body' => 'My Content'], $result->toArray());
        $this->assertTrue($result->dirty(), 'Should be a dirty entity.');
        $this->assertFalse($result->isNew(), 'Should not change the entity state');
    }

    /**
     * Test merge() with accessibleFields options
     *
     * @return void
     */
    public function testMergeAccessibleFields()
    {
        $data = [
            'title' => 'My title',
            'body' => 'New content',
            'author_id' => 1,
            'not_in_schema' => true
        ];
        $marshall = new Marshaller($this->articles);
        $entity = new Entity([
            'title' => 'Foo',
            'body' => 'My Content'
        ]);
        $entity->accessible('*', false);
        $entity->isNew(false);
        $entity->clean();
        $result = $marshall->merge($entity, $data, ['accessibleFields' => ['body' => true]]);

        $this->assertSame($entity, $result);
        $this->assertEquals(['title' => 'Foo', 'body' => 'New content'], $result->toArray());
        $this->assertTrue($entity->accessible('body'));
    }

    /**
     * Provides empty values.
     *
     * @return array
     */
    public function emptyProvider()
    {
        return [
            [0],
            ['0'],
        ];
    }

    /**
     * Test merging empty values into an entity.
     *
     * @dataProvider emptyProvider
     * @return void
     */
    public function testMergeFalseyValues($value)
    {
        $marshall = new Marshaller($this->articles);
        $entity = new Entity();
        $entity->accessible('*', true);

        $entity = $marshall->merge($entity, ['author_id' => $value]);
        $this->assertTrue($entity->dirty('author_id'), 'Field should be dirty');
        $this->assertSame(0, $entity->get('author_id'), 'Value should be zero');
    }

    /**
     * Tests that merge respects the entity accessible methods
     *
     * @return void
     */
    public function testMergeWhitelist()
    {
        $data = [
            'title' => 'My title',
            'author_id' => 1,
            'not_in_schema' => true
        ];
        $marshall = new Marshaller($this->articles);
        $entity = new Entity([
            'title' => 'Foo',
            'body' => 'My Content'
        ]);
        $entity->accessible('*', false);
        $entity->accessible('author_id', true);
        $entity->isNew(false);
        $result = $marshall->merge($entity, $data, []);

        $expected = [
            'title' => 'Foo',
            'body' => 'My Content',
            'author_id' => 1
        ];
        $this->assertEquals($expected, $result->toArray());
    }

    /**
     * Test merge when fieldList contains an association.
     *
     * @return void
     */
    public function testMergeWithSingleAssociationAndFieldLists()
    {
        $user = new Entity([
           'username' => 'user',
        ]);
        $article = new Entity([
           'title' => 'title for post',
           'body' => 'body',
           'user' => $user,
        ]);

        $user->accessible('*', true);
        $article->accessible('*', true);

        $data = [
            'title' => 'Chelsea',
            'user' => [
                'username' => 'dee'
            ]
        ];

        $marshall = new Marshaller($this->articles);
        $marshall->merge($article, $data, [
            'fieldList' => ['title', 'user'],
            'associated' => ['Users' => []]
        ]);
        $this->assertSame($user, $article->user);
    }

    /**
     * Tests that fields with the same value are not marked as dirty
     *
     * @return void
     */
    public function testMergeDirty()
    {
        $marshall = new Marshaller($this->articles);
        $entity = new Entity([
            'title' => 'Foo',
            'author_id' => 1
        ]);
        $data = [
            'title' => 'Foo',
            'author_id' => 1,
            'crazy' => true
        ];
        $entity->accessible('*', true);
        $entity->clean();
        $result = $marshall->merge($entity, $data, []);

        $expected = [
            'title' => 'Foo',
            'author_id' => 1,
            'crazy' => true
        ];
        $this->assertEquals($expected, $result->toArray());
        $this->assertFalse($entity->dirty('title'));
        $this->assertFalse($entity->dirty('author_id'));
        $this->assertTrue($entity->dirty('crazy'));
    }

    /**
     * Tests merging data into an associated entity
     *
     * @return void
     */
    public function testMergeWithSingleAssociation()
    {
        $user = new Entity([
            'username' => 'mark',
            'password' => 'secret'
        ]);
        $entity = new Entity([
            'title' => 'My Title',
            'user' => $user
        ]);
        $user->accessible('*', true);
        $entity->accessible('*', true);

        $data = [
            'body' => 'My Content',
            'user' => [
                'password' => 'not a secret'
            ]
        ];
        $marshall = new Marshaller($this->articles);
        $marshall->merge($entity, $data, ['associated' => ['Users']]);
        $this->assertEquals('My Content', $entity->body);
        $this->assertSame($user, $entity->user);
        $this->assertEquals('mark', $entity->user->username);
        $this->assertEquals('not a secret', $entity->user->password);
        $this->assertTrue($entity->dirty('user'));
    }

    /**
     * Tests that new associated entities can be created when merging data into
     * a parent entity
     *
     * @return void
     */
    public function testMergeCreateAssociation()
    {
        $entity = new Entity([
            'title' => 'My Title'
        ]);
        $entity->accessible('*', true);
        $data = [
            'body' => 'My Content',
            'user' => [
                'username' => 'mark',
                'password' => 'not a secret'
            ]
        ];
        $marshall = new Marshaller($this->articles);
        $marshall->merge($entity, $data, ['associated' => ['Users']]);
        $this->assertEquals('My Content', $entity->body);
        $this->assertInstanceOf('Cake\ORM\Entity', $entity->user);
        $this->assertEquals('mark', $entity->user->username);
        $this->assertEquals('not a secret', $entity->user->password);
        $this->assertTrue($entity->dirty('user'));
        $this->assertTrue($entity->user->isNew());
    }

    /**
     * Tests merging one to many associations
     *
     * @return void
     */
    public function testMergeMultipleAssociations()
    {
        $user = new Entity(['username' => 'mark', 'password' => 'secret']);
        $comment1 = new Entity(['id' => 1, 'comment' => 'A comment']);
        $comment2 = new Entity(['id' => 2, 'comment' => 'Another comment']);
        $entity = new Entity([
            'title' => 'My Title',
            'user' => $user,
            'comments' => [$comment1, $comment2]
        ]);

        $user->accessible('*', true);
        $comment1->accessible('*', true);
        $comment2->accessible('*', true);
        $entity->accessible('*', true);

        $data = [
            'title' => 'Another title',
            'user' => ['password' => 'not so secret'],
            'comments' => [
                ['comment' => 'Extra comment 1'],
                ['id' => 2, 'comment' => 'Altered comment 2'],
                ['id' => 1, 'comment' => 'Altered comment 1'],
                ['id' => 3, 'comment' => 'Extra comment 3'],
                ['id' => 4, 'comment' => 'Extra comment 4'],
                ['comment' => 'Extra comment 2']
            ]
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->merge($entity, $data, ['associated' => ['Users', 'Comments']]);
        $this->assertSame($entity, $result);
        $this->assertSame($user, $result->user);
        $this->assertEquals('not so secret', $entity->user->password);
        $this->assertSame($comment1, $entity->comments[0]);
        $this->assertSame($comment2, $entity->comments[1]);
        $this->assertEquals('Altered comment 1', $entity->comments[0]->comment);
        $this->assertEquals('Altered comment 2', $entity->comments[1]->comment);

        $thirdComment = $this->articles->Comments
            ->find()
            ->where(['id' => 3])
            ->hydrate(false)
            ->first();

        $this->assertEquals(
            ['comment' => 'Extra comment 3'] + $thirdComment,
            $entity->comments[2]->toArray()
        );

        $forthComment = $this->articles->Comments
            ->find()
            ->where(['id' => 4])
            ->hydrate(false)
            ->first();

        $this->assertEquals(
            ['comment' => 'Extra comment 4'] + $forthComment,
            $entity->comments[3]->toArray()
        );

        $this->assertEquals(
            ['comment' => 'Extra comment 1'],
            $entity->comments[4]->toArray()
        );
        $this->assertEquals(
            ['comment' => 'Extra comment 2'],
            $entity->comments[5]->toArray()
        );
    }

    /**
     * Tests that merging data to an entity containing belongsToMany and _ids
     * will just overwrite the data
     *
     * @return void
     */
    public function testMergeBelongsToManyEntitiesFromIds()
    {
        $entity = new Entity([
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => [
                new Entity(['id' => 1, 'name' => 'Cake']),
                new Entity(['id' => 2, 'name' => 'PHP'])
            ]
        ]);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => ['_ids' => [1, 2, 3]]
        ];
        $entity->accessible('*', true);
        $marshall = new Marshaller($this->articles);
        $result = $marshall->merge($entity, $data, ['associated' => ['Tags']]);

        $this->assertCount(3, $result->tags);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[1]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[2]);
    }

    /**
     * Tests that merging data to an entity containing belongsToMany and _ids
     * will not generate conflicting queries when associations are automatically selected
     *
     * @return void
     */
    public function testMergeFromIdsWithAutoAssociation()
    {
        $entity = new Entity([
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => [
                new Entity(['id' => 1, 'name' => 'Cake']),
                new Entity(['id' => 2, 'name' => 'PHP'])
            ]
        ]);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => ['_ids' => [1, 2, 3]]
        ];
        $entity->accessible('*', true);

        // Adding a forced join to have another table with the same column names
        $this->articles->Tags->eventManager()->attach(function ($e, $query) {
            $left = new IdentifierExpression('Tags.id');
            $right = new IdentifierExpression('a.id');
            $query->leftJoin(['a' => 'tags'], $query->newExpr()->eq($left, $right));
        }, 'Model.beforeFind');

        $marshall = new Marshaller($this->articles);
        $result = $marshall->merge($entity, $data, ['associated' => ['Tags']]);

        $this->assertCount(3, $result->tags);
    }

    /**
     * Tests that merging data to an entity containing belongsToMany and _ids
     * with additional association conditions works.
     *
     * @return void
     */
    public function testMergeBelongsToManyFromIdsWithConditions()
    {
        $this->articles->belongsToMany('Tags', [
            'conditions' => ['ArticleTags.article_id' => 1]
        ]);

        $entity = new Entity([
            'title' => 'No tags',
            'body' => 'Some content here',
            'tags' => []
        ]);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => ['_ids' => [1, 2, 3]]
        ];
        $entity->accessible('*', true);
        $marshall = new Marshaller($this->articles);
        $result = $marshall->merge($entity, $data, ['associated' => ['Tags']]);

        $this->assertCount(3, $result->tags);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[1]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[2]);
    }

    /**
     * Tests that merging data to an entity containing belongsToMany as an array
     * with additional association conditions works.
     *
     * @return void
     */
    public function testMergeBelongsToManyFromArrayWithConditions()
    {
        $this->articles->belongsToMany('Tags', [
            'conditions' => ['ArticleTags.article_id' => 1]
        ]);

        $this->articles->Tags->eventManager()
            ->on('Model.beforeFind', function ($event, $query) use (&$called) {
                $called = true;
                return $query->where(['Tags.id >=' => 1]);
            });

        $entity = new Entity([
            'title' => 'No tags',
            'body' => 'Some content here',
            'tags' => []
        ]);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => [
                ['id' => 1],
                ['id' => 2]
            ]
        ];
        $entity->accessible('*', true);
        $marshall = new Marshaller($this->articles);
        $result = $marshall->merge($entity, $data, ['associated' => ['Tags']]);

        $this->assertCount(2, $result->tags);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[1]);
        $this->assertTrue($called);
    }

    /**
     * Tests that merging data to an entity containing belongsToMany and _ids
     * will ignore empty values.
     *
     * @return void
     */
    public function testMergeBelongsToManyEntitiesFromIdsEmptyValue()
    {
        $entity = new Entity([
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => [
                new Entity(['id' => 1, 'name' => 'Cake']),
                new Entity(['id' => 2, 'name' => 'PHP'])
            ]
        ]);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => ['_ids' => '']
        ];
        $entity->accessible('*', true);
        $marshall = new Marshaller($this->articles);
        $result = $marshall->merge($entity, $data, ['associated' => ['Tags']]);
        $this->assertCount(0, $result->tags);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => ['_ids' => false]
        ];
        $result = $marshall->merge($entity, $data, ['associated' => ['Tags']]);
        $this->assertCount(0, $result->tags);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => ['_ids' => null]
        ];
        $result = $marshall->merge($entity, $data, ['associated' => ['Tags']]);
        $this->assertCount(0, $result->tags);
    }

    /**
     * Test that the ids option restricts to only accepting ids for belongs to many associations.
     *
     * @return void
     */
    public function testMergeBelongsToManyOnlyIdsRejectArray()
    {
        $entity = new Entity([
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => [
                new Entity(['id' => 1, 'name' => 'Cake']),
                new Entity(['id' => 2, 'name' => 'PHP'])
            ]
        ]);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => [
                ['name' => 'new'],
                ['name' => 'awesome']
            ]
        ];
        $entity->accessible('*', true);
        $marshall = new Marshaller($this->articles);
        $result = $marshall->merge($entity, $data, [
            'associated' => ['Tags' => ['onlyIds' => true]]
        ]);
        $this->assertCount(0, $result->tags);
    }

    /**
     * Test that the ids option restricts to only accepting ids for belongs to many associations.
     *
     * @return void
     */
    public function testMergeBelongsToManyOnlyIdsWithIds()
    {
        $entity = new Entity([
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => [
                new Entity(['id' => 1, 'name' => 'Cake']),
                new Entity(['id' => 2, 'name' => 'PHP'])
            ]
        ]);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => [
                '_ids' => [3]
            ]
        ];
        $entity->accessible('*', true);
        $marshall = new Marshaller($this->articles);
        $result = $marshall->merge($entity, $data, [
            'associated' => ['Tags' => ['ids' => true]]
        ]);
        $this->assertCount(1, $result->tags);
        $this->assertEquals('tag3', $result->tags[0]->name);
    }

    /**
     * Test that invalid _joinData (scalar data) is not marshalled.
     *
     * @return void
     */
    public function testMergeBelongsToManyJoinDataScalar()
    {
        TableRegistry::clear();
        $articles = TableRegistry::get('Articles');
        $articles->belongsToMany('Tags', [
            'through' => 'SpecialTags'
        ]);

        $entity = $articles->get(1, ['contain' => 'Tags']);
        $data = [
            'title' => 'Haz data',
            'tags' => [
                ['id' => 3, 'tag' => 'Cake', '_joinData' => 'Invalid'],
            ]
        ];
        $marshall = new Marshaller($articles);
        $result = $marshall->merge($entity, $data, ['associated' => 'Tags._joinData']);

        $articles->save($entity, ['associated' => ['Tags._joinData']]);
        $this->assertFalse($entity->tags[0]->dirty('_joinData'));
        $this->assertEmpty($entity->tags[0]->_joinData);
    }

    /**
     * Test merging the _joinData entity for belongstomany associations when * is not
     * accessible.
     *
     * @return void
     */
    public function testMergeBelongsToManyJoinDataNotAccessible()
    {
        TableRegistry::clear();
        $articles = TableRegistry::get('Articles');
        $articles->belongsToMany('Tags', [
            'through' => 'SpecialTags'
        ]);

        $entity = $articles->get(1, ['contain' => 'Tags']);
        $data = [
            'title' => 'Haz data',
            'tags' => [
                ['id' => 3, 'tag' => 'Cake', '_joinData' => ['highlighted' => '1', 'author_id' => '99']],
            ]
        ];
        // Make only specific fields accessible, but not _joinData.
        $entity->tags[0]->accessible('*', false);
        $entity->tags[0]->accessible(['article_id', 'tag_id'], true);

        $marshall = new Marshaller($articles);
        $result = $marshall->merge($entity, $data, ['associated' => 'Tags._joinData']);

        $this->assertTrue($entity->tags[0]->dirty('_joinData'));
        $this->assertTrue($result->tags[0]->_joinData->dirty('author_id'), 'Field not modified');
        $this->assertTrue($result->tags[0]->_joinData->dirty('highlighted'), 'Field not modified');
        $this->assertSame(99, $result->tags[0]->_joinData->author_id);
        $this->assertTrue($result->tags[0]->_joinData->highlighted);
    }

    /**
     * Test that _joinData is marshalled consistently with both
     * new and existing records
     *
     * @return void
     */
    public function testMergeBelongsToManyHandleJoinDataConsistently()
    {
        TableRegistry::clear();
        $articles = TableRegistry::get('Articles');
        $articles->belongsToMany('Tags', [
            'through' => 'SpecialTags'
        ]);

        $entity = $articles->get(1);
        $data = [
            'title' => 'Haz data',
            'tags' => [
                ['id' => 3, 'tag' => 'Cake', '_joinData' => ['highlighted' => true]],
            ]
        ];
        $marshall = new Marshaller($articles);
        $result = $marshall->merge($entity, $data, ['associated' => 'Tags']);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]->_joinData);
        $this->assertTrue($result->tags[0]->_joinData->highlighted);

        // Also ensure merge() overwrites existing data.
        $entity = $articles->get(1, ['contain' => 'Tags']);
        $data = [
            'title' => 'Haz data',
            'tags' => [
                ['id' => 3, 'tag' => 'Cake', '_joinData' => ['highlighted' => true]],
            ]
        ];
        $marshall = new Marshaller($articles);
        $result = $marshall->merge($entity, $data, ['associated' => 'Tags']);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]->_joinData);
        $this->assertTrue($result->tags[0]->_joinData->highlighted);
    }

    /**
     * Test merging belongsToMany data doesn't create 'new' entities.
     *
     * @return void
     */
    public function testMergeBelongsToManyJoinDataAssociatedWithIds()
    {
        $data = [
            'title' => 'My title',
            'tags' => [
                [
                    'id' => 1,
                    '_joinData' => [
                        'active' => 1,
                        'user' => ['username' => 'MyLux'],
                    ]
                ],
                [
                    'id' => 2,
                    '_joinData' => [
                        'active' => 0,
                        'user' => ['username' => 'IronFall'],
                    ]
                ],
            ],
        ];
        $articlesTags = TableRegistry::get('ArticlesTags');
        $articlesTags->belongsTo('Users');

        $marshall = new Marshaller($this->articles);
        $article = $this->articles->get(1, ['associated' => 'Tags']);
        $result = $marshall->merge($article, $data, ['associated' => ['Tags._joinData.Users']]);

        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[1]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]->_joinData->user);

        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[1]->_joinData->user);
        $this->assertFalse($result->tags[0]->isNew(), 'Should not be new, as id is in db.');
        $this->assertFalse($result->tags[1]->isNew(), 'Should not be new, as id is in db.');
        $this->assertEquals(1, $result->tags[0]->id);
        $this->assertEquals(2, $result->tags[1]->id);

        $this->assertEquals(1, $result->tags[0]->_joinData->active);
        $this->assertEquals(0, $result->tags[1]->_joinData->active);

        $this->assertEquals(
            $data['tags'][0]['_joinData']['user']['username'],
            $result->tags[0]->_joinData->user->username
        );
        $this->assertEquals(
            $data['tags'][1]['_joinData']['user']['username'],
            $result->tags[1]->_joinData->user->username
        );
    }

    /**
     * Test merging the _joinData entity for belongstomany associations.
     *
     * @return void
     */
    public function testMergeBelongsToManyJoinData()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'tags' => [
                [
                    'id' => 1,
                    'tag' => 'news',
                    '_joinData' => [
                        'active' => 0
                    ]
                ],
                [
                    'id' => 2,
                    'tag' => 'cakephp',
                    '_joinData' => [
                        'active' => 0
                    ]
                ],
            ],
        ];

        $options = ['associated' => ['Tags._joinData']];
        $marshall = new Marshaller($this->articles);
        $entity = $marshall->one($data, $options);
        $entity->accessible('*', true);

        $data = [
            'title' => 'Haz data',
            'tags' => [
                ['id' => 1, 'tag' => 'Cake', '_joinData' => ['foo' => 'bar']],
                ['tag' => 'new tag', '_joinData' => ['active' => 1, 'foo' => 'baz']]
            ]
        ];

        $tag1 = $entity->tags[0];
        $result = $marshall->merge($entity, $data, $options);
        $this->assertEquals($data['title'], $result->title);
        $this->assertEquals('My content', $result->body);
        $this->assertSame($tag1, $entity->tags[0]);
        $this->assertSame($tag1->_joinData, $entity->tags[0]->_joinData);
        $this->assertSame(
            ['active' => 0, 'foo' => 'bar'],
            $entity->tags[0]->_joinData->toArray()
        );
        $this->assertSame(
            ['active' => 1, 'foo' => 'baz'],
            $entity->tags[1]->_joinData->toArray()
        );
        $this->assertEquals('new tag', $entity->tags[1]->tag);
        $this->assertTrue($entity->tags[0]->dirty('_joinData'));
        $this->assertTrue($entity->tags[1]->dirty('_joinData'));
    }

    /**
     * Test merging associations inside _joinData
     *
     * @return void
     */
    public function testMergeJoinDataAssociations()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'tags' => [
                [
                    'id' => 1,
                    'tag' => 'news',
                    '_joinData' => [
                        'active' => 0,
                        'user' => ['username' => 'Bill']
                    ]
                ],
                [
                    'id' => 2,
                    'tag' => 'cakephp',
                    '_joinData' => [
                        'active' => 0
                    ]
                ],
            ]
        ];

        $articlesTags = TableRegistry::get('ArticlesTags');
        $articlesTags->belongsTo('Users');

        $options = ['associated' => ['Tags._joinData.Users']];
        $marshall = new Marshaller($this->articles);
        $entity = $marshall->one($data, $options);
        $entity->accessible('*', true);

        $data = [
            'title' => 'Haz data',
            'tags' => [
                [
                    'id' => 1,
                    'tag' => 'news',
                    '_joinData' => [
                        'foo' => 'bar',
                        'user' => ['password' => 'secret']
                    ]
                ],
                [
                    'id' => 2,
                    '_joinData' => [
                        'active' => 1,
                        'foo' => 'baz',
                        'user' => ['username' => 'ber']
                    ]
                ]
            ]
        ];

        $tag1 = $entity->tags[0];
        $result = $marshall->merge($entity, $data, $options);
        $this->assertEquals($data['title'], $result->title);
        $this->assertEquals('My content', $result->body);
        $this->assertSame($tag1, $entity->tags[0]);
        $this->assertSame($tag1->_joinData, $entity->tags[0]->_joinData);
        $this->assertEquals('Bill', $entity->tags[0]->_joinData->user->username);
        $this->assertEquals('secret', $entity->tags[0]->_joinData->user->password);
        $this->assertEquals('ber', $entity->tags[1]->_joinData->user->username);
    }

    /**
     * Test mergeMany() with a simple set of data.
     *
     * @return void
     */
    public function testMergeManySimple()
    {
        $entities = [
            new OpenEntity(['id' => 1, 'comment' => 'First post', 'user_id' => 2]),
            new OpenEntity(['id' => 2, 'comment' => 'Second post', 'user_id' => 2])
        ];
        $entities[0]->clean();
        $entities[1]->clean();

        $data = [
            ['id' => 2, 'comment' => 'Changed 2', 'user_id' => 2],
            ['id' => 1, 'comment' => 'Changed 1', 'user_id' => 1]
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->mergeMany($entities, $data);

        $this->assertSame($entities[0], $result[0]);
        $this->assertSame($entities[1], $result[1]);
        $this->assertEquals('Changed 1', $result[0]->comment);
        $this->assertEquals(1, $result[0]->user_id);
        $this->assertEquals('Changed 2', $result[1]->comment);
        $this->assertTrue($result[0]->dirty('user_id'));
        $this->assertFalse($result[1]->dirty('user_id'));
    }

    /**
     * Test mergeMany() with some invalid data
     *
     * @return void
     */
    public function testMergeManyInvalidData()
    {
        $entities = [
            new OpenEntity(['id' => 1, 'comment' => 'First post', 'user_id' => 2]),
            new OpenEntity(['id' => 2, 'comment' => 'Second post', 'user_id' => 2])
        ];
        $entities[0]->clean();
        $entities[1]->clean();

        $data = [
            ['id' => 2, 'comment' => 'Changed 2', 'user_id' => 2],
            ['id' => 1, 'comment' => 'Changed 1', 'user_id' => 1],
            '_csrfToken' => 'abc123',
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->mergeMany($entities, $data);

        $this->assertSame($entities[0], $result[0]);
        $this->assertSame($entities[1], $result[1]);
    }

    /**
     * Tests that only records found in the data array are returned, those that cannot
     * be matched are discarded
     *
     * @return void
     */
    public function testMergeManyWithAppend()
    {
        $entities = [
            new OpenEntity(['comment' => 'First post', 'user_id' => 2]),
            new OpenEntity(['id' => 2, 'comment' => 'Second post', 'user_id' => 2])
        ];
        $entities[0]->clean();
        $entities[1]->clean();

        $data = [
            ['id' => 2, 'comment' => 'Changed 2', 'user_id' => 2],
            ['id' => 1, 'comment' => 'Comment 1', 'user_id' => 1]
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->mergeMany($entities, $data);

        $this->assertCount(2, $result);
        $this->assertNotSame($entities[0], $result[0]);
        $this->assertSame($entities[1], $result[0]);
        $this->assertEquals('Changed 2', $result[0]->comment);

        $this->assertEquals('Comment 1', $result[1]->comment);
    }

    /**
     * Test that mergeMany() handles composite key associations properly.
     *
     * The articles_tags table has a composite primary key, and should be
     * handled correctly.
     *
     * @return void
     */
    public function testMergeManyCompositeKey()
    {
        $articlesTags = TableRegistry::get('ArticlesTags');

        $entities = [
            new OpenEntity(['article_id' => 1, 'tag_id' => 2]),
            new OpenEntity(['article_id' => 1, 'tag_id' => 1]),
        ];
        $entities[0]->clean();
        $entities[1]->clean();

        $data = [
            ['article_id' => 1, 'tag_id' => 1],
            ['article_id' => 1, 'tag_id' => 2]
        ];
        $marshall = new Marshaller($articlesTags);
        $result = $marshall->mergeMany($entities, $data);

        $this->assertCount(2, $result, 'Should have two records');
        $this->assertSame($entities[0], $result[0], 'Should retain object');
        $this->assertSame($entities[1], $result[1], 'Should retain object');
    }

    /**
     * Test mergeMany() when the exist check returns nothing.
     *
     * @return void
     */
    public function testMergeManyExistQueryFails()
    {
        $entities = [
            new Entity(['id' => 1, 'comment' => 'First post', 'user_id' => 2]),
            new Entity(['id' => 2, 'comment' => 'Second post', 'user_id' => 2])
        ];
        $entities[0]->clean();
        $entities[1]->clean();

        $data = [
            ['id' => 2, 'comment' => 'Changed 2', 'user_id' => 2],
            ['id' => 1, 'comment' => 'Changed 1', 'user_id' => 1],
            ['id' => 3, 'comment' => 'New 1'],
        ];
        $comments = TableRegistry::get('GreedyComments', [
            'className' => __NAMESPACE__ . '\\GreedyCommentsTable'
        ]);
        $marshall = new Marshaller($comments);
        $result = $marshall->mergeMany($entities, $data);

        $this->assertCount(3, $result);
        $this->assertEquals('Changed 1', $result[0]->comment);
        $this->assertEquals(1, $result[0]->user_id);
        $this->assertEquals('Changed 2', $result[1]->comment);
        $this->assertEquals('New 1', $result[2]->comment);
    }

    /**
     * Tests merge with data types that need to be marshalled
     *
     * @return void
     */
    public function testMergeComplexType()
    {
        $entity = new Entity(
            ['comment' => 'My Comment text'],
            ['markNew' => false, 'markClean' => true]
        );
        $data = [
            'created' => [
                'year' => '2014',
                'month' => '2',
                'day' => 14
            ]
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->merge($entity, $data);
        $this->assertInstanceOf('DateTime', $entity->created);
        $this->assertEquals('2014-02-14', $entity->created->format('Y-m-d'));
    }

    /**
     * Tests that it is possible to pass a fieldList option to the marshaller
     *
     * @return void
     */
    public function testOneWithFieldList()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => null
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['fieldList' => ['title', 'author_id']]);

        $this->assertInstanceOf('Cake\ORM\Entity', $result);
        unset($data['body']);
        $this->assertEquals($data, $result->toArray());
    }

    /**
     * Tests that it is possible to pass a fieldList option to the merge method
     *
     * @return void
     */
    public function testMergeWithFieldList()
    {
        $data = [
            'title' => 'My title',
            'body' => null,
            'author_id' => 1
        ];
        $marshall = new Marshaller($this->articles);
        $entity = new Entity([
            'title' => 'Foo',
            'body' => 'My content',
            'author_id' => 2
        ]);
        $entity->accessible('*', false);
        $entity->isNew(false);
        $entity->clean();
        $result = $marshall->merge($entity, $data, ['fieldList' => ['title', 'body']]);

        $expected = [
            'title' => 'My title',
            'body' => null,
            'author_id' => 2
        ];

        $this->assertSame($entity, $result);
        $this->assertEquals($expected, $result->toArray());
        $this->assertFalse($entity->accessible('*'));
    }

    /**
     * Test that many() also receives a fieldList option
     *
     * @return void
     */
    public function testManyFieldList()
    {
        $data = [
            ['comment' => 'First post', 'user_id' => 2, 'foo' => 'bar'],
            ['comment' => 'Second post', 'user_id' => 2, 'foo' => 'bar'],
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->many($data, ['fieldList' => ['comment', 'user_id']]);

        $this->assertCount(2, $result);
        unset($data[0]['foo'], $data[1]['foo']);
        $this->assertEquals($data[0], $result[0]->toArray());
        $this->assertEquals($data[1], $result[1]->toArray());
    }

    /**
     * Test that many() also receives a fieldList option
     *
     * @return void
     */
    public function testMergeManyFieldList()
    {
        $entities = [
            new OpenEntity(['id' => 1, 'comment' => 'First post', 'user_id' => 2]),
            new OpenEntity(['id' => 2, 'comment' => 'Second post', 'user_id' => 2])
        ];
        $entities[0]->clean();
        $entities[1]->clean();

        $data = [
            ['id' => 2, 'comment' => 'Changed 2', 'user_id' => 10],
            ['id' => 1, 'comment' => 'Changed 1', 'user_id' => 20]
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->mergeMany($entities, $data, ['fieldList' => ['id', 'comment']]);

        $this->assertSame($entities[0], $result[0]);
        $this->assertSame($entities[1], $result[1]);

        $expected = ['id' => 2, 'comment' => 'Changed 2', 'user_id' => 2];
        $this->assertEquals($expected, $entities[1]->toArray());

        $expected = ['id' => 1, 'comment' => 'Changed 1', 'user_id' => 2];
        $this->assertEquals($expected, $entities[0]->toArray());
    }

    /**
     * test marshalling association data while passing a fieldList
     *
     * @return void
     */
    public function testAssociatoinsFieldList()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'user' => [
                'username' => 'mark',
                'password' => 'secret',
                'foo' => 'bar'
            ]
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, [
            'fieldList' => ['title', 'body', 'user'],
            'associated' => [
                'Users' => ['fieldList' => ['username', 'foo']]
            ]
        ]);

        $this->assertEquals($data['title'], $result->title);
        $this->assertEquals($data['body'], $result->body);
        $this->assertNull($result->author_id);

        $this->assertInstanceOf('Cake\ORM\Entity', $result->user);
        $this->assertEquals($data['user']['username'], $result->user->username);
        $this->assertNull($result->user->password);
    }

    /**
     * Tests merging associated data with a fieldList
     *
     * @return void
     */
    public function testMergeAssociationWithfieldList()
    {
        $user = new Entity([
            'username' => 'mark',
            'password' => 'secret'
        ]);
        $entity = new Entity([
            'tile' => 'My Title',
            'user' => $user
        ]);
        $user->accessible('*', true);
        $entity->accessible('*', true);

        $data = [
            'body' => 'My Content',
            'something' => 'else',
            'user' => [
                'password' => 'not a secret',
                'extra' => 'data'
            ]
        ];
        $marshall = new Marshaller($this->articles);
        $marshall->merge($entity, $data, [
            'fieldList' => ['something'],
            'associated' => ['Users' => ['fieldList' => ['extra']]]
        ]);
        $this->assertNull($entity->body);
        $this->assertEquals('else', $entity->something);
        $this->assertSame($user, $entity->user);
        $this->assertEquals('mark', $entity->user->username);
        $this->assertEquals('secret', $entity->user->password);
        $this->assertEquals('data', $entity->user->extra);
        $this->assertTrue($entity->dirty('user'));
    }

    /**
     * Test marshalling nested associations on the _joinData structure
     * while having a fieldList
     *
     * @return void
     */
    public function testJoinDataWhiteList()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'tags' => [
                [
                    'tag' => 'news',
                    '_joinData' => [
                        'active' => 1,
                        'crazy' => 'data',
                        'user' => ['username' => 'Bill'],
                    ]
                ],
                [
                    'tag' => 'cakephp',
                    '_joinData' => [
                        'active' => 0,
                        'crazy' => 'stuff',
                        'user' => ['username' => 'Mark'],
                    ]
                ],
            ],
        ];

        $articlesTags = TableRegistry::get('ArticlesTags');
        $articlesTags->belongsTo('Users');

        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, [
            'associated' => [
                'Tags._joinData' => ['fieldList' => ['active', 'user']],
                'Tags._joinData.Users'
            ]
        ]);
        $this->assertInstanceOf(
            'Cake\ORM\Entity',
            $result->tags[0]->_joinData->user,
            'joinData should contain a user entity.'
        );
        $this->assertEquals('Bill', $result->tags[0]->_joinData->user->username);
        $this->assertInstanceOf(
            'Cake\ORM\Entity',
            $result->tags[1]->_joinData->user,
            'joinData should contain a user entity.'
        );
        $this->assertEquals('Mark', $result->tags[1]->_joinData->user->username);

        $this->assertNull($result->tags[0]->_joinData->crazy);
        $this->assertNull($result->tags[1]->_joinData->crazy);
    }

    /**
     * Test merging the _joinData entity for belongstomany associations
     * while passing a whitelist
     *
     * @return void
     */
    public function testMergeJoinDataWithFieldList()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'tags' => [
                [
                    'id' => 1,
                    'tag' => 'news',
                    '_joinData' => [
                        'active' => 0
                    ]
                ],
                [
                    'id' => 2,
                    'tag' => 'cakephp',
                    '_joinData' => [
                        'active' => 0
                    ]
                ],
            ],
        ];

        $options = ['associated' => ['Tags' => ['associated' => ['_joinData']]]];
        $marshall = new Marshaller($this->articles);
        $entity = $marshall->one($data, $options);
        $entity->accessible('*', true);

        $data = [
            'title' => 'Haz data',
            'tags' => [
                ['id' => 1, 'tag' => 'Cake', '_joinData' => ['foo' => 'bar', 'crazy' => 'something']],
                ['tag' => 'new tag', '_joinData' => ['active' => 1, 'foo' => 'baz']]
            ]
        ];

        $tag1 = $entity->tags[0];
        $result = $marshall->merge($entity, $data, [
            'associated' => ['Tags._joinData' => ['fieldList' => ['foo']]]
        ]);
        $this->assertEquals($data['title'], $result->title);
        $this->assertEquals('My content', $result->body);
        $this->assertSame($tag1, $entity->tags[0]);
        $this->assertSame($tag1->_joinData, $entity->tags[0]->_joinData);
        $this->assertSame(
            ['active' => 0, 'foo' => 'bar'],
            $entity->tags[0]->_joinData->toArray()
        );
        $this->assertSame(
            ['foo' => 'baz'],
            $entity->tags[1]->_joinData->toArray()
        );
        $this->assertEquals('new tag', $entity->tags[1]->tag);
        $this->assertTrue($entity->tags[0]->dirty('_joinData'));
        $this->assertTrue($entity->tags[1]->dirty('_joinData'));
    }

    /**
     * Tests marshalling with validation errors
     *
     * @return void
     */
    public function testValidationFail()
    {
        $data = [
            'title' => 'Thing',
            'body' => 'hey'
        ];

        $this->articles->validator()->requirePresence('thing');
        $marshall = new Marshaller($this->articles);
        $entity = $marshall->one($data);
        $this->assertNotEmpty($entity->errors('thing'));
    }

    /**
     * Test that invalid validate options raise exceptions
     *
     * @expectedException \RuntimeException
     * @return void
     */
    public function testValidateInvalidType()
    {
        $data = ['title' => 'foo'];
        $marshaller = new Marshaller($this->articles);
        $marshaller->one($data, [
            'validate' => ['derp'],
        ]);
    }

    /**
     * Tests that associations are validated and custom validators can be used
     *
     * @return void
     */
    public function testValidateWithAssociationsAndCustomValidator()
    {
        $data = [
            'title' => 'foo',
            'body' => 'bar',
            'user' => [
                'name' => 'Susan'
            ],
            'comments' => [
                [
                    'comment' => 'foo'
                ]
            ]
        ];
        $validator = (new Validator)->add('body', 'numeric', ['rule' => 'numeric']);
        $this->articles->validator('custom', $validator);

        $validator2 = (new Validator)->requirePresence('thing');
        $this->articles->Users->validator('customThing', $validator2);

        $this->articles->Comments->validator('default', $validator2);

        $entity = (new Marshaller($this->articles))->one($data, [
            'validate' => 'custom',
            'associated' => ['Users', 'Comments']
        ]);
        $this->assertNotEmpty($entity->errors('body'), 'custom was not used');
        $this->assertNull($entity->body);
        $this->assertEmpty($entity->user->errors('thing'));
        $this->assertNotEmpty($entity->comments[0]->errors('thing'));

        $entity = (new Marshaller($this->articles))->one($data, [
            'validate' => 'custom',
            'associated' => ['Users' => ['validate' => 'customThing'], 'Comments']
        ]);
        $this->assertNotEmpty($entity->errors('body'));
        $this->assertNull($entity->body);
        $this->assertNotEmpty($entity->user->errors('thing'), 'customThing was not used');
        $this->assertNotEmpty($entity->comments[0]->errors('thing'));
    }

    /**
     * Tests that validation can be bypassed
     *
     * @return void
     */
    public function testSkipValidation()
    {
        $data = [
            'title' => 'foo',
            'body' => 'bar',
            'user' => [
                'name' => 'Susan'
            ],
        ];
        $validator = (new Validator)->requirePresence('thing');
        $this->articles->validator('default', $validator);
        $this->articles->Users->validator('default', $validator);

        $entity = (new Marshaller($this->articles))->one($data, [
            'validate' => false,
            'associated' => ['Users']
        ]);
        $this->assertEmpty($entity->errors('thing'));
        $this->assertNotEmpty($entity->user->errors('thing'));

        $entity = (new Marshaller($this->articles))->one($data, [
            'associated' => ['Users' => ['validate' => false]]
        ]);
        $this->assertNotEmpty($entity->errors('thing'));
        $this->assertEmpty($entity->user->errors('thing'));
    }

    /**
     * Tests that it is possible to pass a validator directly in the options
     *
     * @return void
     */
    public function testPassingCustomValidator()
    {
        $data = [
            'title' => 'Thing',
            'body' => 'hey'
        ];

        $validator = clone $this->articles->validator();
        $validator->requirePresence('thing');
        $marshall = new Marshaller($this->articles);
        $entity = $marshall->one($data, ['validate' => $validator]);
        $this->assertNotEmpty($entity->errors('thing'));
    }

    /**
     * Test merge with validation error
     *
     * @return void
     */
    public function testMergeWithValidation()
    {
        $data = [
            'title' => 'My title',
            'author_id' => 'foo',
        ];
        $marshall = new Marshaller($this->articles);
        $entity = new Entity([
            'id' => 1,
            'title' => 'Foo',
            'body' => 'My Content',
            'author_id' => 1
        ]);
        $entity->accessible('*', true);
        $entity->isNew(false);
        $entity->clean();

        $this->articles->validator()
            ->requirePresence('thing', 'update')
            ->requirePresence('id', 'update')
            ->add('author_id', 'numeric', ['rule' => 'numeric'])
            ->add('id', 'numeric', ['rule' => 'numeric', 'on' => 'update']);

        $expected = clone $entity;
        $result = $marshall->merge($expected, $data, []);

        $this->assertSame($expected, $result);
        $this->assertSame(1, $result->author_id);
        $this->assertNotEmpty($result->errors('thing'));
        $this->assertEmpty($result->errors('id'));

        $this->articles->validator()->requirePresence('thing', 'create');
        $result = $marshall->merge($entity, $data, []);
        $this->assertEmpty($result->errors('thing'));
    }

    /**
     * Test merge with validation and create or update validation rules
     *
     * @return void
     */
    public function testMergeWithCreate()
    {
        $data = [
            'title' => 'My title',
            'author_id' => 'foo',
        ];
        $marshall = new Marshaller($this->articles);
        $entity = new Entity([
            'title' => 'Foo',
            'body' => 'My Content',
            'author_id' => 1
        ]);
        $entity->accessible('*', true);
        $entity->isNew(true);
        $entity->clean();

        $this->articles->validator()
            ->requirePresence('thing', 'update')
            ->add('author_id', 'numeric', ['rule' => 'numeric', 'on' => 'update']);

        $expected = clone $entity;
        $result = $marshall->merge($expected, $data, []);

        $this->assertEmpty($result->errors('author_id'));
        $this->assertEmpty($result->errors('thing'));

        $entity->clean();
        $entity->isNew(false);
        $result = $marshall->merge($entity, $data, []);
        $this->assertNotEmpty($result->errors('author_id'));
        $this->assertNotEmpty($result->errors('thing'));
    }

    /**
     * Test Model.beforeMarshal event.
     *
     * @return void
     */
    public function testBeforeMarshalEvent()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'user' => [
                'name' => 'Robert',
                'username' => 'rob'
            ]
        ];

        $marshall = new Marshaller($this->articles);

        $this->articles->eventManager()->attach(function ($e, $data, $options) {
            $data['title'] = 'Modified title';
            $data['user']['username'] = 'robert';

            $options['associated'] = ['Users'];
        }, 'Model.beforeMarshal');

        $entity = $marshall->one($data);

        $this->assertEquals('Modified title', $entity->title);
        $this->assertEquals('My content', $entity->body);
        $this->assertEquals('Robert', $entity->user->name);
        $this->assertEquals('robert', $entity->user->username);
    }

    /**
     * Test Model.beforeMarshal event on associated tables.
     *
     * @return void
     */
    public function testBeforeMarshalEventOnAssociations()
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'user' => [
                'username' => 'mark',
                'password' => 'secret'
            ],
            'comments' => [
                ['comment' => 'First post', 'user_id' => 2],
                ['comment' => 'Second post', 'user_id' => 2],
            ],
            'tags' => [
                ['tag' => 'news', '_joinData' => ['active' => 1]],
                ['tag' => 'cakephp', '_joinData' => ['active' => 0]],
            ],
        ];

        $marshall = new Marshaller($this->articles);

        $this->articles->users->eventManager()->attach(function ($e, $data) {
            $data['secret'] = 'h45h3d';
        }, 'Model.beforeMarshal');

        $this->articles->comments->eventManager()->attach(function ($e, $data) {
            $data['comment'] .= ' (modified)';
        }, 'Model.beforeMarshal');

        $this->articles->tags->eventManager()->attach(function ($e, $data) {
            $data['tag'] .= ' (modified)';
        }, 'Model.beforeMarshal');

        $this->articles->tags->junction()->eventManager()->attach(function ($e, $data) {
            $data['modified_by'] = 1;
        }, 'Model.beforeMarshal');

        $entity = $marshall->one($data, [
            'associated' => ['Users', 'Comments', 'Tags']
        ]);

        $this->assertEquals('h45h3d', $entity->user->secret);
        $this->assertEquals('First post (modified)', $entity->comments[0]->comment);
        $this->assertEquals('Second post (modified)', $entity->comments[1]->comment);
        $this->assertEquals('news (modified)', $entity->tags[0]->tag);
        $this->assertEquals('cakephp (modified)', $entity->tags[1]->tag);
        $this->assertEquals(1, $entity->tags[0]->_joinData->modified_by);
        $this->assertEquals(1, $entity->tags[1]->_joinData->modified_by);
    }

    /**
     * Tests that patching an association resulting in no changes, will
     * not mark the parent entity as dirty
     *
     * @return void
     */
    public function testAssociationNoChanges()
    {
        $options = ['markClean' => true, 'isNew' => false];
        $entity = new Entity([
            'title' => 'My Title',
            'user' => new Entity([
                'username' => 'mark',
                'password' => 'not a secret'
            ], $options)
        ], $options);

        $data = [
            'body' => 'My Content',
            'user' => [
                'username' => 'mark',
                'password' => 'not a secret'
            ]
        ];
        $marshall = new Marshaller($this->articles);
        $marshall->merge($entity, $data, ['associated' => ['Users']]);
        $this->assertEquals('My Content', $entity->body);
        $this->assertInstanceOf('Cake\ORM\Entity', $entity->user);
        $this->assertEquals('mark', $entity->user->username);
        $this->assertEquals('not a secret', $entity->user->password);
        $this->assertFalse($entity->dirty('user'));
        $this->assertTrue($entity->user->isNew());
    }
}
