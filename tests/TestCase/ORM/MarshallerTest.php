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
namespace Cake\Test\TestCase\ORM;

use Cake\Database\Expression\IdentifierExpression;
use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;
use Cake\ORM\Marshaller;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use InvalidArgumentException;
use RuntimeException;
use TestApp\Model\Entity\OpenArticleEntity;
use TestApp\Model\Entity\OpenTag;
use TestApp\Model\Entity\ProtectedArticle;
use TestApp\Model\Table\GreedyCommentsTable;

/**
 * Marshaller test case
 */
class MarshallerTest extends TestCase
{
    protected $fixtures = [
        'core.Articles',
        'core.Tags',
        'core.ArticlesTags',
        'core.Comments',
        'core.SpecialTags',
        'core.Users',
    ];

    /**
     * @var \Cake\ORM\Table
     */
    protected $articles;

    /**
     * @var \Cake\ORM\Table
     */
    protected $comments;

    /**
     * @var \Cake\ORM\Table
     */
    protected $users;

    /**
     * @var \Cake\ORM\Table
     */
    protected $tags;

    /**
     * @var \Cake\ORM\Table
     */
    protected $articleTags;

    /**
     * setup
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->articles = $this->getTableLocator()->get('Articles');
        $this->articles->belongsTo('Users', [
            'foreignKey' => 'author_id',
        ]);
        $this->articles->hasMany('Comments');
        $this->articles->belongsToMany('Tags');

        $this->comments = $this->getTableLocator()->get('Comments');
        $this->users = $this->getTableLocator()->get('Users');
        $this->tags = $this->getTableLocator()->get('Tags');
        $this->articleTags = $this->getTableLocator()->get('ArticlesTags');

        $this->comments->belongsTo('Articles');
        $this->comments->belongsTo('Users');

        $this->articles->setEntityClass(OpenArticleEntity::class);
        $this->comments->setEntityClass(OpenArticleEntity::class);
        $this->users->setEntityClass(OpenArticleEntity::class);
        $this->tags->setEntityClass(OpenArticleEntity::class);
        $this->articleTags->setEntityClass(OpenArticleEntity::class);
    }

    /**
     * Teardown
     */
    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->articles, $this->comments, $this->users, $this->tags);
    }

    /**
     * Test one() in a simple use.
     */
    public function testOneSimple(): void
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'not_in_schema' => true,
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, []);

        $this->assertInstanceOf('Cake\ORM\Entity', $result);
        $this->assertEquals($data, $result->toArray());
        $this->assertTrue($result->isDirty(), 'Should be a dirty entity.');
        $this->assertTrue($result->isNew(), 'Should be new');
        $this->assertSame('Articles', $result->getSource());
    }

    /**
     * Test that marshalling an entity with numeric key in data array
     */
    public function testOneWithNumericField(): void
    {
        $data = [
            'sample',
            'username' => 'test',
            'password' => 'secret',
            1,
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, []);
        $this->assertSame($data[0], $result->get('0'));
        $this->assertSame($data[1], $result->get('1'));
    }

    /**
     * Test that marshalling an entity with '' for pk values results
     * in no pk value being set.
     */
    public function testOneEmptyStringPrimaryKey(): void
    {
        $data = [
            'id' => '',
            'username' => 'superuser',
            'password' => 'root',
            'created' => new FrozenTime('2013-10-10 00:00'),
            'updated' => new FrozenTime('2013-10-10 00:00'),
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, []);

        $this->assertFalse($result->isDirty('id'));
        $this->assertNull($result->id);
    }

    /**
     * Test marshalling datetime/date field.
     */
    public function testOneWithDatetimeField(): void
    {
        $data = [
            'comment' => 'My Comment text',
            'created' => [
                'year' => '2014',
                'month' => '2',
                'day' => 14,
            ],
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->one($data, []);

        $this->assertEquals(new FrozenTime('2014-02-14 00:00:00'), $result->created);

        $data['created'] = [
            'year' => '2014',
            'month' => '2',
            'day' => 14,
            'hour' => 9,
            'minute' => 25,
            'meridian' => 'pm',
        ];
        $result = $marshall->one($data, []);
        $this->assertEquals(new FrozenTime('2014-02-14 21:25:00'), $result->created);

        $data['created'] = [
            'year' => '2014',
            'month' => '2',
            'day' => 14,
            'hour' => 9,
            'minute' => 25,
        ];
        $result = $marshall->one($data, []);
        $this->assertEquals(new FrozenTime('2014-02-14 09:25:00'), $result->created);

        $data['created'] = '2014-02-14 09:25:00';
        $result = $marshall->one($data, []);
        $this->assertEquals(new FrozenTime('2014-02-14 09:25:00'), $result->created);

        $data['created'] = 1392387900;
        $result = $marshall->one($data, []);
        $this->assertSame($data['created'], $result->created->getTimestamp());
    }

    public function testOneWithFieldMatchingTableAlias(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->getSchema()->addColumn('Articles', ['type' => 'string']);

        $data = ['Articles' => 'a title', 'title' => 'First post', 'body' => 'Content here', 'author_id' => 1];
        $marshall = new Marshaller($articles);
        $result = $marshall->one($data);

        $this->assertEquals($data['Articles'], $result->Articles);
    }

    /**
     * Ensure that marshalling casts reasonably.
     */
    public function testOneOnlyCastMatchingData(): void
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 'derp',
            'created' => 'fale',
        ];
        $this->articles->setEntityClass(OpenArticleEntity::class);
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, []);

        $this->assertSame($data['title'], $result->title);
        $this->assertNull($result->author_id, 'No cast on bad data.');
        $this->assertSame($data['created'], $result->created, 'No cast on bad data.');
    }

    /**
     * Test one() follows mass-assignment rules.
     */
    public function testOneAccessibleProperties(): void
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'not_in_schema' => true,
        ];
        $this->articles->setEntityClass(ProtectedArticle::class);
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, []);

        $this->assertInstanceOf(ProtectedArticle::class, $result);
        $this->assertNull($result->author_id);
        $this->assertNull($result->not_in_schema);
    }

    /**
     * Test one() supports accessibleFields option
     */
    public function testOneAccessibleFieldsOption(): void
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'not_in_schema' => true,
        ];
        $this->articles->setEntityClass(ProtectedArticle::class);

        $marshall = new Marshaller($this->articles);

        $result = $marshall->one($data, ['accessibleFields' => ['body' => false]]);
        $this->assertNull($result->body);

        $result = $marshall->one($data, ['accessibleFields' => ['author_id' => true]]);
        $this->assertSame($data['author_id'], $result->author_id);
        $this->assertNull($result->not_in_schema);

        $result = $marshall->one($data, ['accessibleFields' => ['*' => true]]);
        $this->assertSame($data['author_id'], $result->author_id);
        $this->assertTrue($result->not_in_schema);
    }

    /**
     * Test one() with an invalid association
     */
    public function testOneInvalidAssociation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot marshal data for "Derp" association. It is not associated with "Articles".');
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'derp' => [
                'id' => 1,
                'username' => 'mark',
            ],
        ];
        $marshall = new Marshaller($this->articles);
        $marshall->one($data, [
            'associated' => ['Derp'],
        ]);
    }

    /**
     * Test that one() correctly handles an association beforeMarshal
     * making the association empty.
     */
    public function testOneAssociationBeforeMarshalMutation(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $articles = $this->getTableLocator()->get('Articles');

        $users->hasOne('Articles', [
            'foreignKey' => 'author_id',
        ]);
        $articles->getEventManager()->on('Model.beforeMarshal', function ($event, $data, $options): void {
            // Blank the association, so it doesn't become dirty.
            unset($data['not_a_real_field']);
        });

        $data = [
            'username' => 'Jen',
            'article' => [
                'not_a_real_field' => 'whatever',
            ],
        ];
        $marshall = new Marshaller($users);
        $entity = $marshall->one($data, ['associated' => ['Articles']]);
        $this->assertTrue($entity->isDirty('username'));
        $this->assertFalse($entity->isDirty('article'));

        // Ensure consistency with merge()
        $entity = new Entity([
            'username' => 'Jenny',
        ]);
        // Make the entity think it is new.
        $entity->setAccess('*', true);
        $entity->clean();
        $entity = $marshall->merge($entity, $data, ['associated' => ['Articles']]);
        $this->assertTrue($entity->isDirty('username'));
        $this->assertFalse($entity->isDirty('article'));
    }

    /**
     * Test one() supports accessibleFields option for associations
     */
    public function testOneAccessibleFieldsOptionForAssociations(): void
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'user' => [
                'id' => 1,
                'username' => 'mark',
            ],
        ];
        $this->articles->setEntityClass(ProtectedArticle::class);
        $this->users->setEntityClass(ProtectedArticle::class);

        $marshall = new Marshaller($this->articles);

        $result = $marshall->one($data, [
            'associated' => [
                'Users' => ['accessibleFields' => ['id' => true]],
            ],
            'accessibleFields' => ['body' => false, 'user' => true],
        ]);
        $this->assertNull($result->body);
        $this->assertNull($result->user->username);
        $this->assertSame(1, $result->user->id);
    }

    /**
     * test one() with a wrapping model name.
     */
    public function testOneWithAdditionalName(): void
    {
        $data = [
            'title' => 'Original Title',
            'Articles' => [
                'title' => 'My title',
                'body' => 'My content',
                'author_id' => 1,
                'not_in_schema' => true,
                'user' => [
                    'username' => 'mark',
                ],
            ],
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['associated' => ['Users']]);

        $this->assertInstanceOf('Cake\ORM\Entity', $result);
        $this->assertTrue($result->isDirty(), 'Should be a dirty entity.');
        $this->assertTrue($result->isNew(), 'Should be new');
        $this->assertFalse($result->has('Articles'), 'No prefixed field.');
        $this->assertSame($data['title'], $result->title, 'Data from prefix should be merged.');
        $this->assertSame($data['Articles']['user']['username'], $result->user->username);
    }

    /**
     * test one() with association data.
     */
    public function testOneAssociationsSingle(): void
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
                'password' => 'secret',
            ],
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['associated' => ['Users']]);

        $this->assertSame($data['title'], $result->title);
        $this->assertSame($data['body'], $result->body);
        $this->assertSame($data['author_id'], $result->author_id);

        $this->assertIsArray($result->comments);
        $this->assertEquals($data['comments'], $result->comments);
        $this->assertTrue($result->isDirty('comments'));

        $this->assertInstanceOf('Cake\ORM\Entity', $result->user);
        $this->assertTrue($result->isDirty('user'));
        $this->assertSame($data['user']['username'], $result->user->username);
        $this->assertSame($data['user']['password'], $result->user->password);
    }

    /**
     * test one() with association data.
     */
    public function testOneAssociationsMany(): void
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
                'password' => 'secret',
            ],
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['associated' => ['Comments']]);

        $this->assertSame($data['title'], $result->title);
        $this->assertSame($data['body'], $result->body);
        $this->assertSame($data['author_id'], $result->author_id);

        $this->assertIsArray($result->comments);
        $this->assertCount(2, $result->comments);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->comments[0]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->comments[1]);
        $this->assertSame($data['comments'][0]['comment'], $result->comments[0]->comment);

        $this->assertIsArray($result->user);
        $this->assertEquals($data['user'], $result->user);
    }

    /**
     * Test building the _joinData entity for belongstomany associations.
     */
    public function testOneBelongsToManyJoinData(): void
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
            'associated' => ['Tags'],
        ]);

        $this->assertSame($data['title'], $result->title);
        $this->assertSame($data['body'], $result->body);

        $this->assertIsArray($result->tags);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]);
        $this->assertSame($data['tags'][0]['tag'], $result->tags[0]->tag);

        $this->assertInstanceOf(
            'Cake\ORM\Entity',
            $result->tags[0]->_joinData,
            '_joinData should be an entity.'
        );
        $this->assertSame(
            $data['tags'][0]['_joinData']['active'],
            $result->tags[0]->_joinData->active,
            '_joinData should be an entity.'
        );
    }

    /**
     * Test that the onlyIds option restricts to only accepting ids for belongs to many associations.
     */
    public function testOneBelongsToManyOnlyIdsRejectArray(): void
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
            'associated' => ['Tags' => ['onlyIds' => true]],
        ]);
        $this->assertEmpty($result->tags, 'Only ids should be marshalled.');
    }

    /**
     * Test that the onlyIds option restricts to only accepting ids for belongs to many associations.
     */
    public function testOneBelongsToManyOnlyIdsWithIds(): void
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
            'associated' => ['Tags' => ['onlyIds' => true]],
        ]);
        $this->assertCount(2, $result->tags, 'Ids should be marshalled.');
    }

    /**
     * Test marshalling nested associations on the _joinData structure.
     */
    public function testOneBelongsToManyJoinDataAssociated(): void
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
                    ],
                ],
                [
                    'tag' => 'cakephp',
                    '_joinData' => [
                        'active' => 0,
                        'user' => ['username' => 'Mark'],
                    ],
                ],
            ],
        ];

        $articlesTags = $this->getTableLocator()->get('ArticlesTags');
        $articlesTags->belongsTo('Users');

        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['associated' => ['Tags._joinData.Users']]);
        $this->assertInstanceOf(
            'Cake\ORM\Entity',
            $result->tags[0]->_joinData->user,
            'joinData should contain a user entity.'
        );
        $this->assertSame('Bill', $result->tags[0]->_joinData->user->username);
        $this->assertInstanceOf(
            'Cake\ORM\Entity',
            $result->tags[1]->_joinData->user,
            'joinData should contain a user entity.'
        );
        $this->assertSame('Mark', $result->tags[1]->_joinData->user->username);
    }

    /**
     * Test one() with with id and _joinData.
     */
    public function testOneBelongsToManyJoinDataAssociatedWithIds(): void
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
                    ],
                ],
                5 => [
                    'id' => 2,
                    '_joinData' => [
                        'active' => 0,
                        'user' => ['username' => 'IronFall'],
                    ],
                ],
            ],
        ];

        $articlesTags = $this->getTableLocator()->get('ArticlesTags');
        $tags = $this->getTableLocator()->get('Tags');
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
        $this->assertSame($data['tags'][3]['_joinData']['user']['username'], $result->tags[0]->_joinData->user->username);
        $this->assertSame($data['tags'][5]['_joinData']['user']['username'], $result->tags[1]->_joinData->user->username);
    }

    /**
     * Test belongsToMany association with mixed data and _joinData
     */
    public function testOneBelongsToManyWithMixedJoinData(): void
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
                    ],
                ],
                [
                    'name' => 'tag5',
                    '_joinData' => [
                        'active' => 1,
                    ],
                ],
            ],
        ];
        $marshall = new Marshaller($this->articles);

        $result = $marshall->one($data, ['associated' => ['Tags._joinData']]);

        $this->assertSame($data['tags'][0]['id'], $result->tags[0]->id);
        $this->assertSame($data['tags'][1]['name'], $result->tags[1]->name);
        $this->assertSame(0, $result->tags[0]->_joinData->active);
        $this->assertSame(1, $result->tags[1]->_joinData->active);
    }

    public function testOneBelongsToManyWithNestedAssociations(): void
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
                        'username' => 'newuser',
                    ],
                    'comments' => [
                        ['comment' => 'New comment', 'user_id' => 1],
                        ['comment' => 'Second comment', 'user_id' => 1],
                    ],
                ],
            ],
        ];
        $marshaller = new Marshaller($this->tags);
        $tag = $marshaller->one($data, ['associated' => ['Articles.Users', 'Articles.Comments']]);

        $this->assertNotEmpty($tag->articles);
        $this->assertCount(1, $tag->articles);
        $this->assertTrue($tag->isDirty('articles'), 'Updated prop should be dirty');
        $this->assertInstanceOf('Cake\ORM\Entity', $tag->articles[0]);
        $this->assertSame('New tagged article', $tag->articles[0]->title);
        $this->assertFalse($tag->articles[0]->isNew());

        $this->assertNotEmpty($tag->articles[0]->user);
        $this->assertInstanceOf('Cake\ORM\Entity', $tag->articles[0]->user);
        $this->assertTrue($tag->articles[0]->isDirty('user'), 'Updated prop should be dirty');
        $this->assertSame('newuser', $tag->articles[0]->user->username);
        $this->assertTrue($tag->articles[0]->user->isNew());

        $this->assertNotEmpty($tag->articles[0]->comments);
        $this->assertCount(2, $tag->articles[0]->comments);
        $this->assertTrue($tag->articles[0]->isDirty('comments'), 'Updated prop should be dirty');
        $this->assertInstanceOf('Cake\ORM\Entity', $tag->articles[0]->comments[0]);
        $this->assertTrue($tag->articles[0]->comments[0]->isNew());
        $this->assertTrue($tag->articles[0]->comments[1]->isNew());
    }

    /**
     * Test belongsToMany association with mixed data and _joinData
     */
    public function testBelongsToManyAddingNewExisting(): void
    {
        $this->tags->setEntityClass(OpenTag::class);
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'tags' => [
                [
                    'id' => 1,
                    '_joinData' => [
                        'active' => 0,
                    ],
                ],
            ],
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
                    ],
                ],
                [
                    'id' => 2,
                    '_joinData' => [
                        'active' => 1,
                    ],
                ],
            ],
        ];
        $result = $marshall->merge($result, $data, ['associated' => ['Tags._joinData']]);

        $this->assertSame($data['title'], $result->title);
        $this->assertSame($data['tags'][0]['id'], $result->tags[0]->id);
        $this->assertSame($data['tags'][1]['id'], $result->tags[1]->id);
        $this->assertNotEmpty($result->tags[0]->_joinData);
        $this->assertNotEmpty($result->tags[1]->_joinData);
        $this->assertTrue($result->isDirty('tags'), 'Modified prop should be dirty');
        $this->assertSame(0, $result->tags[0]->_joinData->active);
        $this->assertSame(1, $result->tags[1]->_joinData->active);
    }

    /**
     * Test belongsToMany association with mixed data and _joinData
     */
    public function testBelongsToManyWithMixedJoinDataOutOfOrder(): void
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
                    ],
                ],
                [
                    'id' => 1,
                    '_joinData' => [
                        'active' => 0,
                    ],
                ],
                [
                    'name' => 'tag3',
                    '_joinData' => [
                        'active' => 1,
                    ],
                ],
            ],
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['associated' => ['Tags._joinData']]);

        $this->assertSame($data['tags'][0]['name'], $result->tags[0]->name);
        $this->assertSame($data['tags'][1]['id'], $result->tags[1]->id);
        $this->assertSame($data['tags'][2]['name'], $result->tags[2]->name);

        $this->assertSame(1, $result->tags[0]->_joinData->active);
        $this->assertSame(0, $result->tags[1]->_joinData->active);
        $this->assertSame(1, $result->tags[2]->_joinData->active);
    }

    /**
     * Test belongsToMany association with scalars
     */
    public function testBelongsToManyInvalidData(): void
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'tags' => [
                'id' => 1,
            ],
        ];

        $article = $this->articles->newEntity($data, [
            'associated' => ['Tags'],
        ]);
        $this->assertEmpty($article->tags, 'No entity should be created');

        $data['tags'] = 1;
        $article = $this->articles->newEntity($data, [
            'associated' => ['Tags'],
        ]);
        $this->assertEmpty($article->tags, 'No entity should be created');
    }

    /**
     * Test belongsToMany association with mixed data array
     */
    public function testBelongsToManyWithMixedData(): void
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'tags' => [
                [
                    'name' => 'tag4',
                ],
                [
                    'name' => 'tag5',
                ],
                [
                    'id' => 1,
                ],
            ],
        ];

        $tags = $this->getTableLocator()->get('Tags');

        $marshaller = new Marshaller($this->articles);
        $article = $marshaller->one($data, ['associated' => ['Tags']]);

        $this->assertSame($data['tags'][0]['name'], $article->tags[0]->name);
        $this->assertSame($data['tags'][1]['name'], $article->tags[1]->name);
        $this->assertEquals($article->tags[2], $tags->get(1));

        $this->assertTrue($article->tags[0]->isNew());
        $this->assertTrue($article->tags[1]->isNew());
        $this->assertFalse($article->tags[2]->isNew());

        $tagCount = $tags->find()->count();
        $this->articles->save($article);

        $this->assertSame($tagCount + 2, $tags->find()->count());
    }

    /**
     * Test belongsToMany association with the ForceNewTarget to force saving
     * new records on the target tables with BTM relationships when the primaryKey(s)
     * of the target table is specified.
     */
    public function testBelongsToManyWithForceNew(): void
    {
        $data = [
            'title' => 'Fourth Article',
            'body' => 'Fourth Article Body',
            'author_id' => 1,
            'tags' => [
                [
                    'id' => 3,
                ],
                [
                    'id' => 4,
                    'name' => 'tag4',
                ],
            ],
        ];

        $marshaller = new Marshaller($this->articles);
        $article = $marshaller->one($data, [
            'associated' => ['Tags'],
            'forceNew' => true,
        ]);

        $this->assertFalse($article->tags[0]->isNew(), 'The tag should not be new');
        $this->assertTrue($article->tags[1]->isNew(), 'The tag should be new');
        $this->assertSame('tag4', $article->tags[1]->name, 'Property should match request data.');
    }

    /**
     * Test HasMany association with _ids attribute
     */
    public function testOneHasManyWithIds(): void
    {
        $data = [
            'title' => 'article',
            'body' => 'some content',
            'comments' => [
                '_ids' => [1, 2],
            ],
        ];

        $marshaller = new Marshaller($this->articles);
        $article = $marshaller->one($data, ['associated' => ['Comments']]);

        $this->assertEquals($article->comments[0], $this->comments->get(1));
        $this->assertEquals($article->comments[1], $this->comments->get(2));
    }

    /**
     * Test that the onlyIds option restricts to only accepting ids for hasmany associations.
     */
    public function testOneHasManyOnlyIdsRejectArray(): void
    {
        $data = [
            'title' => 'article',
            'body' => 'some content',
            'comments' => [
                ['comment' => 'first comment'],
                ['comment' => 'second comment'],
            ],
        ];

        $marshaller = new Marshaller($this->articles);
        $article = $marshaller->one($data, [
            'associated' => ['Comments' => ['onlyIds' => true]],
        ]);
        $this->assertEmpty($article->comments);
    }

    /**
     * Test that the onlyIds option restricts to only accepting ids for hasmany associations.
     */
    public function testOneHasManyOnlyIdsWithIds(): void
    {
        $data = [
            'title' => 'article',
            'body' => 'some content',
            'comments' => [
                '_ids' => [1, 2],
                ['comment' => 'first comment'],
            ],
        ];

        $marshaller = new Marshaller($this->articles);
        $article = $marshaller->one($data, [
            'associated' => ['Comments' => ['onlyIds' => true]],
        ]);
        $this->assertCount(2, $article->comments);
    }

    /**
     * Test HasMany association with invalid data
     */
    public function testOneHasManyInvalidData(): void
    {
        $data = [
            'title' => 'new title',
            'body' => 'some content',
            'comments' => [
                'id' => 1,
            ],
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
     */
    public function testOneDeepAssociations(): void
    {
        $data = [
            'comment' => 'First post',
            'user_id' => 2,
            'article' => [
                'title' => 'Article title',
                'body' => 'Article body',
                'user' => [
                    'username' => 'mark',
                    'password' => 'secret',
                ],
            ],
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->one($data, ['associated' => ['Articles.Users']]);

        $this->assertSame(
            $data['article']['title'],
            $result->article->title
        );
        $this->assertSame(
            $data['article']['user']['username'],
            $result->article->user->username
        );
    }

    /**
     * Test many() with a simple set of data.
     */
    public function testManySimple(): void
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
        $this->assertSame($data[0]['comment'], $result[0]->comment);
        $this->assertSame($data[1]['comment'], $result[1]->comment);
    }

    /**
     * Test many() with some invalid data
     */
    public function testManyInvalidData(): void
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
     */
    public function testManyAssociations(): void
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
        $this->assertSame(
            $data[0]['user']['username'],
            $result[0]->user->username
        );
        $this->assertSame(
            $data[1]['user']['username'],
            $result[1]->user->username
        );
    }

    /**
     * Test if exception is raised when called with [associated => NonExistentAssociation]
     * Previously such association were simply ignored
     */
    public function testManyInvalidAssociation(): void
    {
        $this->expectException(InvalidArgumentException::class);
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
        $marshall->many($data, ['associated' => ['Users', 'People']]);
    }

    /**
     * Test generating a list of entities from a list of ids.
     */
    public function testOneGenerateBelongsToManyEntitiesFromIds(): void
    {
        $data = [
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => ['_ids' => ''],
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['associated' => ['Tags']]);
        $this->assertCount(0, $result->tags);

        $data = [
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => ['_ids' => false],
        ];
        $result = $marshall->one($data, ['associated' => ['Tags']]);
        $this->assertCount(0, $result->tags);

        $data = [
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => ['_ids' => null],
        ];
        $result = $marshall->one($data, ['associated' => ['Tags']]);
        $this->assertCount(0, $result->tags);

        $data = [
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => ['_ids' => []],
        ];
        $result = $marshall->one($data, ['associated' => ['Tags']]);
        $this->assertCount(0, $result->tags);

        $data = [
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => ['_ids' => [1, 2, 3]],
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
     */
    public function testMergeSimple(): void
    {
        $data = [
            'title' => 'My title',
            'author_id' => 1,
            'not_in_schema' => true,
        ];
        $marshall = new Marshaller($this->articles);
        $entity = new Entity([
            'title' => 'Foo',
            'body' => 'My Content',
        ]);
        $entity->setAccess('*', true);
        $entity->setNew(false);
        $entity->clean();
        $result = $marshall->merge($entity, $data, []);

        $this->assertSame($entity, $result);
        $this->assertEquals($data + ['body' => 'My Content'], $result->toArray());
        $this->assertTrue($result->isDirty(), 'Should be a dirty entity.');
        $this->assertFalse($result->isNew(), 'Should not change the entity state');
    }

    /**
     * Test merge() with accessibleFields options
     */
    public function testMergeAccessibleFields(): void
    {
        $data = [
            'title' => 'My title',
            'body' => 'New content',
            'author_id' => 1,
            'not_in_schema' => true,
        ];
        $marshall = new Marshaller($this->articles);
        $entity = new Entity([
            'title' => 'Foo',
            'body' => 'My Content',
        ]);
        $entity->setAccess('*', false);
        $entity->setNew(false);
        $entity->clean();
        $result = $marshall->merge($entity, $data, ['accessibleFields' => ['body' => true]]);

        $this->assertSame($entity, $result);
        $this->assertEquals(['title' => 'Foo', 'body' => 'New content'], $result->toArray());
        $this->assertTrue($entity->isAccessible('body'));
    }

    /**
     * Provides empty values.
     *
     * @return array
     */
    public function emptyProvider(): array
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
     * @param mixed $value
     */
    public function testMergeFalseyValues($value): void
    {
        $marshall = new Marshaller($this->articles);
        $entity = new Entity();
        $entity->setAccess('*', true);
        $entity->clean();

        $entity = $marshall->merge($entity, ['author_id' => $value]);
        $this->assertTrue($entity->isDirty('author_id'), 'Field should be dirty');
        $this->assertSame(0, $entity->get('author_id'), 'Value should be zero');
    }

    /**
     * Test merge() doesn't dirty values that were null and are null again.
     */
    public function testMergeUnchangedNullValue(): void
    {
        $data = [
            'title' => 'My title',
            'author_id' => 1,
            'body' => null,
        ];
        $marshall = new Marshaller($this->articles);
        $entity = new Entity([
            'title' => 'Foo',
            'body' => null,
        ]);
        $entity->setAccess('*', true);
        $entity->setNew(false);
        $entity->clean();
        $result = $marshall->merge($entity, $data, []);

        $this->assertFalse($entity->isDirty('body'), 'unchanged null should not be dirty');
    }

    /**
     * Test merge() doesn't dirty objects which are equal.
     */
    public function testMergeWithSameObjectValue(): void
    {
        $created = new FrozenTime('2020-10-29');
        $entity = new Entity([
            'comment' => 'foo',
            'created' => $created,
        ]);
        $entity->setAccess('*', true);
        $entity->setNew(false);
        $entity->clean();

        $data = [
            'comment' => 'bar',
            'created' => clone $created,
        ];
        $marshall = new Marshaller($this->comments);
        $marshall->merge($entity, $data);

        $this->assertFalse($entity->isDirty('created'));
    }

    /**
     * Tests that merge respects the entity accessible methods
     */
    public function testMergeWhitelist(): void
    {
        $data = [
            'title' => 'My title',
            'author_id' => 1,
            'not_in_schema' => true,
        ];
        $marshall = new Marshaller($this->articles);
        $entity = new Entity([
            'title' => 'Foo',
            'body' => 'My Content',
        ]);
        $entity->setAccess('*', false);
        $entity->setAccess('author_id', true);
        $entity->setNew(false);
        $entity->clean();

        $result = $marshall->merge($entity, $data, []);

        $expected = [
            'title' => 'Foo',
            'body' => 'My Content',
            'author_id' => 1,
        ];
        $this->assertEquals($expected, $result->toArray());
    }

    /**
     * Test merge() with an invalid association
     */
    public function testMergeInvalidAssociation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot marshal data for "Derp" association. It is not associated with "Articles".');
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'derp' => [
                'id' => 1,
                'username' => 'mark',
            ],
        ];
        $article = new Entity([
           'title' => 'title for post',
           'body' => 'body',
        ]);
        $marshall = new Marshaller($this->articles);
        $marshall->merge($article, $data, [
            'associated' => ['Derp'],
        ]);
    }

    /**
     * Test merge when fields contains an association.
     */
    public function testMergeWithSingleAssociationAndFields(): void
    {
        $user = new Entity([
           'username' => 'user',
        ]);
        $article = new Entity([
           'title' => 'title for post',
           'body' => 'body',
           'user' => $user,
        ]);

        $user->setAccess('*', true);
        $article->setAccess('*', true);

        $data = [
            'title' => 'Chelsea',
            'user' => [
                'username' => 'dee',
            ],
        ];

        $marshall = new Marshaller($this->articles);
        $marshall->merge($article, $data, [
            'fields' => ['title', 'user'],
            'associated' => ['Users' => []],
        ]);
        $this->assertSame($user, $article->user);
        $this->assertTrue($article->isDirty('user'));
    }

    /**
     * Tests that fields with the same value are not marked as dirty
     */
    public function testMergeDirty(): void
    {
        $marshall = new Marshaller($this->articles);
        $entity = new Entity([
            'title' => 'Foo',
            'author_id' => 1,
        ]);
        $data = [
            'title' => 'Foo',
            'author_id' => 1,
            'crazy' => true,
        ];
        $entity->setAccess('*', true);
        $entity->clean();
        $result = $marshall->merge($entity, $data, []);

        $expected = [
            'title' => 'Foo',
            'author_id' => 1,
            'crazy' => true,
        ];
        $this->assertEquals($expected, $result->toArray());
        $this->assertFalse($entity->isDirty('title'));
        $this->assertFalse($entity->isDirty('author_id'));
        $this->assertTrue($entity->isDirty('crazy'));
    }

    /**
     * Tests merging data into an associated entity
     */
    public function testMergeWithSingleAssociation(): void
    {
        $user = new Entity([
            'username' => 'mark',
            'password' => 'secret',
        ]);
        $entity = new Entity([
            'title' => 'My Title',
            'user' => $user,
        ]);
        $user->setAccess('*', true);
        $entity->setAccess('*', true);
        $entity->clean();

        $data = [
            'body' => 'My Content',
            'user' => [
                'password' => 'not a secret',
            ],
        ];
        $marshall = new Marshaller($this->articles);
        $marshall->merge($entity, $data, ['associated' => ['Users']]);

        $this->assertTrue($entity->isDirty('user'), 'association should be dirty');
        $this->assertTrue($entity->isDirty('body'), 'body should be dirty');
        $this->assertSame('My Content', $entity->body);
        $this->assertSame($user, $entity->user);
        $this->assertSame('mark', $entity->user->username);
        $this->assertSame('not a secret', $entity->user->password);
    }

    /**
     * Tests that new associated entities can be created when merging data into
     * a parent entity
     */
    public function testMergeCreateAssociation(): void
    {
        $entity = new Entity([
            'title' => 'My Title',
        ]);
        $entity->setAccess('*', true);
        $entity->clean();

        $data = [
            'body' => 'My Content',
            'user' => [
                'username' => 'mark',
                'password' => 'not a secret',
            ],
        ];
        $marshall = new Marshaller($this->articles);
        $marshall->merge($entity, $data, ['associated' => ['Users']]);

        $this->assertSame('My Content', $entity->body);
        $this->assertInstanceOf('Cake\ORM\Entity', $entity->user);
        $this->assertSame('mark', $entity->user->username);
        $this->assertSame('not a secret', $entity->user->password);
        $this->assertTrue($entity->isDirty('user'));
        $this->assertTrue($entity->isDirty('body'));
        $this->assertTrue($entity->user->isNew());
    }

    /**
     * Test merge when an association has been replaced with null
     */
    public function testMergeAssociationNullOut(): void
    {
        $user = new Entity([
            'id' => 1,
            'username' => 'user',
        ]);
        $article = new Entity([
           'title' => 'title for post',
           'user_id' => 1,
           'user' => $user,
        ]);

        $user->setAccess('*', true);
        $article->setAccess('*', true);

        $data = [
            'title' => 'Chelsea',
            'user_id' => '',
            'user' => '',
        ];

        $marshall = new Marshaller($this->articles);
        $marshall->merge($article, $data, [
            'associated' => ['Users'],
        ]);
        $this->assertNull($article->user);
        $this->assertSame('', $article->user_id);
        $this->assertTrue($article->isDirty('user'));
    }

    /**
     * Tests merging one to many associations
     */
    public function testMergeMultipleAssociations(): void
    {
        $user = new Entity(['username' => 'mark', 'password' => 'secret']);
        $comment1 = new Entity(['id' => 1, 'comment' => 'A comment']);
        $comment2 = new Entity(['id' => 2, 'comment' => 'Another comment']);
        $entity = new Entity([
            'title' => 'My Title',
            'user' => $user,
            'comments' => [$comment1, $comment2],
        ]);

        $user->setAccess('*', true);
        $comment1->setAccess('*', true);
        $comment2->setAccess('*', true);
        $entity->setAccess('*', true);
        $entity->clean();

        $data = [
            'title' => 'Another title',
            'user' => ['password' => 'not so secret'],
            'comments' => [
                ['comment' => 'Extra comment 1'],
                ['id' => 2, 'comment' => 'Altered comment 2'],
                ['id' => 1, 'comment' => 'Altered comment 1'],
                ['id' => 3, 'comment' => 'Extra comment 3'],
                ['id' => 4, 'comment' => 'Extra comment 4'],
                ['comment' => 'Extra comment 2'],
            ],
        ];
        $marshall = new Marshaller($this->articles);

        $result = $marshall->merge($entity, $data, ['associated' => ['Users', 'Comments']]);
        $this->assertSame($entity, $result);
        $this->assertSame($user, $result->user);
        $this->assertTrue($result->isDirty('user'), 'association should be dirty');
        $this->assertSame('not so secret', $entity->user->password);

        $this->assertTrue($result->isDirty('comments'));
        $this->assertSame($comment1, $entity->comments[0]);
        $this->assertSame($comment2, $entity->comments[1]);
        $this->assertSame('Altered comment 1', $entity->comments[0]->comment);
        $this->assertSame('Altered comment 2', $entity->comments[1]->comment);

        $thirdComment = $this->articles->Comments
            ->find()
            ->where(['id' => 3])
            ->enableHydration(false)
            ->first();

        $this->assertEquals(
            ['comment' => 'Extra comment 3'] + $thirdComment,
            $entity->comments[2]->toArray()
        );

        $forthComment = $this->articles->Comments
            ->find()
            ->where(['id' => 4])
            ->enableHydration(false)
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
     * Tests that merging data to a hasMany association with _ids works.
     */
    public function testMergeHasManyEntitiesFromIds(): void
    {
        $entity = $this->articles->get(1, ['contain' => ['Comments']]);
        $this->assertNotEmpty($entity->comments);

        $marshall = new Marshaller($this->articles);
        $data = ['comments' => ['_ids' => [1, 2, 3]]];
        $result = $marshall->merge($entity, $data, ['associated' => ['Comments']]);

        $this->assertCount(3, $result->comments);
        $this->assertTrue($result->isDirty('comments'), 'Updated prop should be dirty');
        $this->assertInstanceOf(Entity::class, $result->comments[0]);
        $this->assertSame(1, $result->comments[0]->id);
        $this->assertInstanceOf(Entity::class, $result->comments[1]);
        $this->assertSame(2, $result->comments[1]->id);
        $this->assertInstanceOf(Entity::class, $result->comments[2]);
        $this->assertSame(3, $result->comments[2]->id);
    }

    /**
     * Tests that merging data to a hasMany association using onlyIds restricts operations.
     */
    public function testMergeHasManyEntitiesFromIdsOnlyIds(): void
    {
        $entity = $this->articles->get(1, ['contain' => ['Comments']]);
        $this->assertNotEmpty($entity->comments);

        $marshall = new Marshaller($this->articles);
        $data = [
            'comments' => [
                '_ids' => [1],
                [
                    'comment' => 'Nope',
                ],
            ],
        ];
        $result = $marshall->merge($entity, $data, ['associated' => ['Comments' => ['onlyIds' => true]]]);

        $this->assertCount(1, $result->comments);
        $this->assertTrue($result->isDirty('comments'), 'Updated prop should be dirty');
        $this->assertInstanceOf(Entity::class, $result->comments[0]);
        $this->assertNotEquals('Nope', $result->comments[0]);
    }

    /**
     * Tests that merging data to an entity containing belongsToMany and _ids
     * will just overwrite the data
     */
    public function testMergeBelongsToManyEntitiesFromIds(): void
    {
        $entity = new Entity([
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => [
                new Entity(['id' => 1, 'name' => 'Cake']),
                new Entity(['id' => 2, 'name' => 'PHP']),
            ],
        ]);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => ['_ids' => [1, 2, 3]],
        ];
        $entity->setAccess('*', true);
        $entity->clean();

        $marshall = new Marshaller($this->articles);
        $result = $marshall->merge($entity, $data, ['associated' => ['Tags']]);

        $this->assertCount(3, $result->tags);
        $this->assertTrue($result->isDirty('tags'), 'Updated prop should be dirty');
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[1]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[2]);
    }

    /**
     * Tests that merging data to an entity containing belongsToMany and _ids
     * will not generate conflicting queries when associations are automatically selected
     */
    public function testMergeFromIdsWithAutoAssociation(): void
    {
        $entity = new Entity([
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => [
                new Entity(['id' => 1, 'name' => 'Cake']),
                new Entity(['id' => 2, 'name' => 'PHP']),
            ],
        ]);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => ['_ids' => [1, 2, 3]],
        ];
        $entity->setAccess('*', true);
        $entity->clean();

        // Adding a forced join to have another table with the same column names
        $this->articles->Tags->getEventManager()->on('Model.beforeFind', function ($e, $query): void {
            $left = new IdentifierExpression('Tags.id');
            $right = new IdentifierExpression('a.id');
            $query->leftJoin(['a' => 'tags'], $query->newExpr()->eq($left, $right));
        });

        $marshall = new Marshaller($this->articles);
        $result = $marshall->merge($entity, $data, ['associated' => ['Tags']]);

        $this->assertCount(3, $result->tags);
        $this->assertTrue($result->isDirty('tags'));
    }

    /**
     * Tests that merging data to an entity containing belongsToMany and _ids
     * with additional association conditions works.
     */
    public function testMergeBelongsToManyFromIdsWithConditions(): void
    {
        $this->articles->belongsToMany('Tags', [
            'conditions' => ['ArticleTags.article_id' => 1],
        ]);

        $entity = new Entity([
            'title' => 'No tags',
            'body' => 'Some content here',
            'tags' => [],
        ]);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => ['_ids' => [1, 2, 3]],
        ];
        $entity->setAccess('*', true);
        $entity->clean();

        $marshall = new Marshaller($this->articles);
        $result = $marshall->merge($entity, $data, ['associated' => ['Tags']]);

        $this->assertCount(3, $result->tags);
        $this->assertTrue($result->isDirty('tags'));
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[1]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[2]);
    }

    /**
     * Tests that merging data to an entity containing belongsToMany as an array
     * with additional association conditions works.
     */
    public function testMergeBelongsToManyFromArrayWithConditions(): void
    {
        $this->articles->belongsToMany('Tags', [
            'conditions' => ['ArticleTags.article_id' => 1],
        ]);

        $this->articles->Tags->getEventManager()
            ->on('Model.beforeFind', function (EventInterface $event, $query) use (&$called) {
                $called = true;

                return $query->where(['Tags.id >=' => 1]);
            });

        $entity = new Entity([
            'title' => 'No tags',
            'body' => 'Some content here',
            'tags' => [],
        ]);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => [
                ['id' => 1],
                ['id' => 2],
            ],
        ];
        $entity->setAccess('*', true);
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
     */
    public function testMergeBelongsToManyEntitiesFromIdsEmptyValue(): void
    {
        $entity = new Entity([
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => [
                new Entity(['id' => 1, 'name' => 'Cake']),
                new Entity(['id' => 2, 'name' => 'PHP']),
            ],
        ]);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => ['_ids' => ''],
        ];
        $entity->setAccess('*', true);
        $marshall = new Marshaller($this->articles);
        $result = $marshall->merge($entity, $data, ['associated' => ['Tags']]);
        $this->assertCount(0, $result->tags);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => ['_ids' => false],
        ];
        $result = $marshall->merge($entity, $data, ['associated' => ['Tags']]);
        $this->assertCount(0, $result->tags);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => ['_ids' => null],
        ];
        $result = $marshall->merge($entity, $data, ['associated' => ['Tags']]);
        $this->assertCount(0, $result->tags);
        $this->assertTrue($result->isDirty('tags'));
    }

    /**
     * Test that the ids option restricts to only accepting ids for belongs to many associations.
     */
    public function testMergeBelongsToManyOnlyIdsRejectArray(): void
    {
        $entity = new Entity([
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => [
                new Entity(['id' => 1, 'name' => 'Cake']),
                new Entity(['id' => 2, 'name' => 'PHP']),
            ],
        ]);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => [
                ['name' => 'new'],
                ['name' => 'awesome'],
            ],
        ];
        $entity->setAccess('*', true);
        $marshall = new Marshaller($this->articles);
        $result = $marshall->merge($entity, $data, [
            'associated' => ['Tags' => ['onlyIds' => true]],
        ]);
        $this->assertCount(0, $result->tags);
        $this->assertTrue($result->isDirty('tags'));
    }

    /**
     * Test that the ids option restricts to only accepting ids for belongs to many associations.
     */
    public function testMergeBelongsToManyOnlyIdsWithIds(): void
    {
        $entity = new Entity([
            'title' => 'Haz tags',
            'body' => 'Some content here',
            'tags' => [
                new Entity(['id' => 1, 'name' => 'Cake']),
                new Entity(['id' => 2, 'name' => 'PHP']),
            ],
        ]);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => [
                '_ids' => [3],
            ],
        ];
        $entity->setAccess('*', true);
        $marshall = new Marshaller($this->articles);
        $result = $marshall->merge($entity, $data, [
            'associated' => ['Tags' => ['ids' => true]],
        ]);
        $this->assertCount(1, $result->tags);
        $this->assertSame('tag3', $result->tags[0]->name);
        $this->assertTrue($result->isDirty('tags'));
    }

    /**
     * Test that invalid _joinData (scalar data) is not marshalled.
     */
    public function testMergeBelongsToManyJoinDataScalar(): void
    {
        $this->getTableLocator()->clear();
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsToMany('Tags', [
            'through' => 'SpecialTags',
        ]);

        $entity = $articles->get(1, ['contain' => 'Tags']);
        $data = [
            'title' => 'Haz data',
            'tags' => [
                ['id' => 3, 'tag' => 'Cake', '_joinData' => 'Invalid'],
            ],
        ];
        $marshall = new Marshaller($articles);
        $result = $marshall->merge($entity, $data, ['associated' => 'Tags._joinData']);

        $articles->save($entity, ['associated' => ['Tags._joinData']]);
        $this->assertFalse($entity->tags[0]->isDirty('_joinData'));
        $this->assertEmpty($entity->tags[0]->_joinData);
    }

    /**
     * Test merging the _joinData entity for belongstomany associations when * is not
     * accessible.
     */
    public function testMergeBelongsToManyJoinDataNotAccessible(): void
    {
        $this->getTableLocator()->clear();
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsToMany('Tags', [
            'through' => 'SpecialTags',
        ]);

        $entity = $articles->get(1, ['contain' => 'Tags']);
        // Make only specific fields accessible, but not _joinData.
        $entity->tags[0]->setAccess('*', false);
        $entity->tags[0]->setAccess(['article_id', 'tag_id'], true);

        $data = [
            'title' => 'Haz data',
            'tags' => [
                ['id' => 3, 'tag' => 'Cake', '_joinData' => ['highlighted' => '1', 'author_id' => '99']],
            ],
        ];
        $marshall = new Marshaller($articles);
        $result = $marshall->merge($entity, $data, ['associated' => 'Tags._joinData']);

        $this->assertTrue($entity->isDirty('tags'), 'Association data changed');
        $this->assertTrue($entity->tags[0]->isDirty('_joinData'));
        $this->assertTrue($result->tags[0]->_joinData->isDirty('author_id'), 'Field not modified');
        $this->assertTrue($result->tags[0]->_joinData->isDirty('highlighted'), 'Field not modified');
        $this->assertSame(99, $result->tags[0]->_joinData->author_id);
        $this->assertTrue($result->tags[0]->_joinData->highlighted);
    }

    /**
     * Test that _joinData is marshalled consistently with both
     * new and existing records
     */
    public function testMergeBelongsToManyHandleJoinDataConsistently(): void
    {
        $this->getTableLocator()->clear();
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsToMany('Tags', [
            'through' => 'SpecialTags',
        ]);

        $entity = $articles->get(1);
        $data = [
            'title' => 'Haz data',
            'tags' => [
                ['id' => 3, 'tag' => 'Cake', '_joinData' => ['highlighted' => true]],
            ],
        ];
        $marshall = new Marshaller($articles);
        $result = $marshall->merge($entity, $data, ['associated' => 'Tags']);

        $this->assertTrue($entity->isDirty('tags'));
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]->_joinData);
        $this->assertTrue($result->tags[0]->_joinData->highlighted);

        // Also ensure merge() overwrites existing data.
        $entity = $articles->get(1, ['contain' => 'Tags']);
        $data = [
            'title' => 'Haz data',
            'tags' => [
                ['id' => 3, 'tag' => 'Cake', '_joinData' => ['highlighted' => true]],
            ],
        ];
        $marshall = new Marshaller($articles);
        $result = $marshall->merge($entity, $data, ['associated' => 'Tags']);

        $this->assertTrue($entity->isDirty('tags'), 'association data changed');
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]->_joinData);
        $this->assertTrue($result->tags[0]->_joinData->highlighted);
    }

    /**
     * Test merging belongsToMany data doesn't create 'new' entities.
     */
    public function testMergeBelongsToManyJoinDataAssociatedWithIds(): void
    {
        $data = [
            'title' => 'My title',
            'tags' => [
                [
                    'id' => 1,
                    '_joinData' => [
                        'active' => 1,
                        'user' => ['username' => 'MyLux'],
                    ],
                ],
                [
                    'id' => 2,
                    '_joinData' => [
                        'active' => 0,
                        'user' => ['username' => 'IronFall'],
                    ],
                ],
            ],
        ];
        $articlesTags = $this->getTableLocator()->get('ArticlesTags');
        $articlesTags->belongsTo('Users');

        $marshall = new Marshaller($this->articles);
        $article = $this->articles->get(1, ['associated' => 'Tags']);
        $result = $marshall->merge($article, $data, ['associated' => ['Tags._joinData.Users']]);

        $this->assertTrue($result->isDirty('tags'));
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[1]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]->_joinData->user);

        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[1]->_joinData->user);
        $this->assertFalse($result->tags[0]->isNew(), 'Should not be new, as id is in db.');
        $this->assertFalse($result->tags[1]->isNew(), 'Should not be new, as id is in db.');
        $this->assertSame(1, $result->tags[0]->id);
        $this->assertSame(2, $result->tags[1]->id);

        $this->assertSame(1, $result->tags[0]->_joinData->active);
        $this->assertSame(0, $result->tags[1]->_joinData->active);

        $this->assertSame(
            $data['tags'][0]['_joinData']['user']['username'],
            $result->tags[0]->_joinData->user->username
        );
        $this->assertSame(
            $data['tags'][1]['_joinData']['user']['username'],
            $result->tags[1]->_joinData->user->username
        );
    }

    /**
     * Test merging the _joinData entity for belongstomany associations.
     */
    public function testMergeBelongsToManyJoinData(): void
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
                    ],
                ],
                [
                    'id' => 2,
                    'tag' => 'cakephp',
                    '_joinData' => [
                        'active' => 0,
                    ],
                ],
            ],
        ];

        $options = ['associated' => ['Tags._joinData']];
        $marshall = new Marshaller($this->articles);
        $entity = $marshall->one($data, $options);
        $entity->setAccess('*', true);

        $data = [
            'title' => 'Haz data',
            'tags' => [
                ['id' => 1, 'tag' => 'Cake', '_joinData' => ['foo' => 'bar']],
                ['tag' => 'new tag', '_joinData' => ['active' => 1, 'foo' => 'baz']],
            ],
        ];
        $tag1 = $entity->tags[0];
        $result = $marshall->merge($entity, $data, $options);

        $this->assertSame($data['title'], $result->title);
        $this->assertSame('My content', $result->body);
        $this->assertTrue($result->isDirty('tags'));
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
        $this->assertSame('new tag', $entity->tags[1]->tag);
        $this->assertTrue($entity->tags[0]->isDirty('_joinData'));
        $this->assertTrue($entity->tags[1]->isDirty('_joinData'));
    }

    /**
     * Test merging associations inside _joinData
     */
    public function testMergeJoinDataAssociations(): void
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
                        'user' => ['username' => 'Bill'],
                    ],
                ],
                [
                    'id' => 2,
                    'tag' => 'cakephp',
                    '_joinData' => [
                        'active' => 0,
                    ],
                ],
            ],
        ];

        $articlesTags = $this->getTableLocator()->get('ArticlesTags');
        $articlesTags->belongsTo('Users');

        $options = ['associated' => ['Tags._joinData.Users']];
        $marshall = new Marshaller($this->articles);
        $entity = $marshall->one($data, $options);
        $entity->setAccess('*', true);

        $data = [
            'title' => 'Haz data',
            'tags' => [
                [
                    'id' => 1,
                    'tag' => 'news',
                    '_joinData' => [
                        'foo' => 'bar',
                        'user' => ['password' => 'secret'],
                    ],
                ],
                [
                    'id' => 2,
                    '_joinData' => [
                        'active' => 1,
                        'foo' => 'baz',
                        'user' => ['username' => 'ber'],
                    ],
                ],
            ],
        ];
        $tag1 = $entity->tags[0];
        $result = $marshall->merge($entity, $data, $options);

        $this->assertSame($data['title'], $result->title);
        $this->assertSame('My content', $result->body);
        $this->assertTrue($entity->isDirty('tags'));
        $this->assertSame($tag1, $entity->tags[0]);

        $this->assertTrue($tag1->isDirty('_joinData'));
        $this->assertSame($tag1->_joinData, $entity->tags[0]->_joinData);
        $this->assertSame('Bill', $entity->tags[0]->_joinData->user->username);
        $this->assertSame('secret', $entity->tags[0]->_joinData->user->password);
        $this->assertSame('ber', $entity->tags[1]->_joinData->user->username);
    }

    /**
     * Tests that merging belongsToMany association doesn't erase _joinData
     * on existing objects.
     */
    public function testMergeBelongsToManyIdsRetainJoinData(): void
    {
        $this->articles->belongsToMany('Tags');
        $entity = $this->articles->get(1, ['contain' => ['Tags']]);
        $entity->setAccess('*', true);
        $original = $entity->tags[0]->_joinData;

        $this->assertInstanceOf('Cake\ORM\Entity', $entity->tags[0]->_joinData);

        $data = [
            'title' => 'Haz moar tags',
            'tags' => [
                ['id' => 1],
            ],
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->merge($entity, $data, ['associated' => ['Tags']]);

        $this->assertCount(1, $result->tags);
        $this->assertTrue($result->isDirty('tags'));
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]);
        $this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]->_joinData);
        $this->assertSame($original, $result->tags[0]->_joinData, 'Should be same object');
    }

    /**
     * Test mergeMany() with a simple set of data.
     */
    public function testMergeManySimple(): void
    {
        $entities = [
            new OpenArticleEntity(['id' => 1, 'comment' => 'First post', 'user_id' => 2]),
            new OpenArticleEntity(['id' => 2, 'comment' => 'Second post', 'user_id' => 2]),
        ];
        $entities[0]->clean();
        $entities[1]->clean();

        $data = [
            ['id' => 2, 'comment' => 'Changed 2', 'user_id' => 2],
            ['id' => 1, 'comment' => 'Changed 1', 'user_id' => 1],
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->mergeMany($entities, $data);

        $this->assertSame($entities[0], $result[0]);
        $this->assertSame($entities[1], $result[1]);
        $this->assertSame('Changed 1', $result[0]->comment);
        $this->assertSame(1, $result[0]->user_id);
        $this->assertSame('Changed 2', $result[1]->comment);
        $this->assertTrue($result[0]->isDirty('user_id'));
        $this->assertFalse($result[1]->isDirty('user_id'));
    }

    /**
     * Test mergeMany() with some invalid data
     */
    public function testMergeManyInvalidData(): void
    {
        $entities = [
            new OpenArticleEntity(['id' => 1, 'comment' => 'First post', 'user_id' => 2]),
            new OpenArticleEntity(['id' => 2, 'comment' => 'Second post', 'user_id' => 2]),
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
     */
    public function testMergeManyWithAppend(): void
    {
        $entities = [
            new OpenArticleEntity(['comment' => 'First post', 'user_id' => 2]),
            new OpenArticleEntity(['id' => 2, 'comment' => 'Second post', 'user_id' => 2]),
        ];
        $entities[0]->clean();
        $entities[1]->clean();

        $data = [
            ['id' => 2, 'comment' => 'Changed 2', 'user_id' => 2],
            ['id' => 1, 'comment' => 'Comment 1', 'user_id' => 1],
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->mergeMany($entities, $data);

        $this->assertCount(2, $result);
        $this->assertNotSame($entities[0], $result[0]);
        $this->assertSame($entities[1], $result[0]);
        $this->assertSame('Changed 2', $result[0]->comment);

        $this->assertSame('Comment 1', $result[1]->comment);
    }

    /**
     * Test that mergeMany() handles composite key associations properly.
     *
     * The articles_tags table has a composite primary key, and should be
     * handled correctly.
     */
    public function testMergeManyCompositeKey(): void
    {
        $articlesTags = $this->getTableLocator()->get('ArticlesTags');

        $entities = [
            new OpenArticleEntity(['article_id' => 1, 'tag_id' => 2]),
            new OpenArticleEntity(['article_id' => 1, 'tag_id' => 1]),
        ];
        $entities[0]->clean();
        $entities[1]->clean();

        $data = [
            ['article_id' => 1, 'tag_id' => 1],
            ['article_id' => 1, 'tag_id' => 2],
        ];
        $marshall = new Marshaller($articlesTags);
        $result = $marshall->mergeMany($entities, $data);

        $this->assertCount(2, $result, 'Should have two records');
        $this->assertSame($entities[0], $result[0], 'Should retain object');
        $this->assertSame($entities[1], $result[1], 'Should retain object');
    }

    /**
     * Test mergeMany() with forced contain to ensure aliases are used in queries.
     */
    public function testMergeManyExistingQueryAliases(): void
    {
        $entities = [
            new OpenArticleEntity(['id' => 1, 'comment' => 'First post', 'user_id' => 2], ['markClean' => true]),
        ];

        $data = [
            ['id' => 1, 'comment' => 'Changed 1', 'user_id' => 1],
            ['id' => 2, 'comment' => 'Changed 2', 'user_id' => 2],
        ];
        $this->comments->getEventManager()->on('Model.beforeFind', function (EventInterface $event, $query) {
            return $query->contain(['Articles']);
        });
        $marshall = new Marshaller($this->comments);
        $result = $marshall->mergeMany($entities, $data);

        $this->assertSame($entities[0], $result[0]);
    }

    /**
     * Test mergeMany() when the exist check returns nothing.
     */
    public function testMergeManyExistQueryFails(): void
    {
        $entities = [
            new Entity(['id' => 1, 'comment' => 'First post', 'user_id' => 2]),
            new Entity(['id' => 2, 'comment' => 'Second post', 'user_id' => 2]),
        ];
        $entities[0]->clean();
        $entities[1]->clean();

        $data = [
            ['id' => 2, 'comment' => 'Changed 2', 'user_id' => 2],
            ['id' => 1, 'comment' => 'Changed 1', 'user_id' => 1],
            ['id' => 3, 'comment' => 'New 1'],
        ];
        $comments = $this->getTableLocator()->get('GreedyComments', [
            'className' => GreedyCommentsTable::class,
        ]);
        $marshall = new Marshaller($comments);
        $result = $marshall->mergeMany($entities, $data);

        $this->assertCount(3, $result);
        $this->assertSame('Changed 1', $result[0]->comment);
        $this->assertSame(1, $result[0]->user_id);
        $this->assertSame('Changed 2', $result[1]->comment);
        $this->assertSame('New 1', $result[2]->comment);
    }

    /**
     * Tests merge with data types that need to be marshalled
     */
    public function testMergeComplexType(): void
    {
        $entity = new Entity(
            ['comment' => 'My Comment text'],
            ['markNew' => false, 'markClean' => true]
        );
        $data = [
            'created' => [
                'year' => '2014',
                'month' => '2',
                'day' => 14,
            ],
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->merge($entity, $data);
        $this->assertInstanceOf(FrozenTime::class, $entity->created);
        $this->assertSame('2014-02-14', $entity->created->format('Y-m-d'));
    }

    /**
     * Tests that it is possible to pass a fields option to the marshaller
     */
    public function testOneWithFields(): void
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => null,
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['fields' => ['title', 'author_id']]);

        $this->assertInstanceOf('Cake\ORM\Entity', $result);
        unset($data['body']);
        $this->assertEquals($data, $result->toArray());
    }

    /**
     * Test one() with translations
     */
    public function testOneWithTranslations(): void
    {
        $this->articles->addBehavior('Translate', [
            'fields' => ['title', 'body'],
        ]);

        $data = [
            'author_id' => 1,
            '_translations' => [
                'en' => [
                    'title' => 'English Title',
                    'body' => 'English Content',
                ],
                'es' => [
                    'title' => 'Titulo Español',
                    'body' => 'Contenido Español',
                ],
            ],
            'user' => [
                'id' => 1,
                'username' => 'mark',
            ],
        ];

        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['associated' => ['Users']]);
        $this->assertEmpty($result->getErrors());
        $this->assertSame(1, $result->author_id);
        $this->assertInstanceOf(OpenArticleEntity::class, $result->user);
        $this->assertSame('mark', $result->user->username);

        $translations = $result->get('_translations');
        $this->assertCount(2, $translations);
        $this->assertInstanceOf(OpenArticleEntity::class, $translations['en']);
        $this->assertInstanceOf(OpenArticleEntity::class, $translations['es']);
        $this->assertEquals($data['_translations']['en'], $translations['en']->toArray());
    }

    /**
     * Tests that it is possible to pass a fields option to the merge method
     */
    public function testMergeWithFields(): void
    {
        $data = [
            'title' => 'My title',
            'body' => null,
            'author_id' => 1,
        ];
        $marshall = new Marshaller($this->articles);
        $entity = new Entity([
            'title' => 'Foo',
            'body' => 'My content',
            'author_id' => 2,
        ]);
        $entity->setAccess('*', false);
        $entity->setNew(false);
        $entity->clean();
        $result = $marshall->merge($entity, $data, ['fields' => ['title', 'body']]);

        $expected = [
            'title' => 'My title',
            'body' => null,
            'author_id' => 2,
        ];

        $this->assertSame($entity, $result);
        $this->assertEquals($expected, $result->toArray());
        $this->assertFalse($entity->isAccessible('*'));
    }

    /**
     * Test that many() also receives a fields option
     */
    public function testManyFields(): void
    {
        $data = [
            ['comment' => 'First post', 'user_id' => 2, 'foo' => 'bar'],
            ['comment' => 'Second post', 'user_id' => 2, 'foo' => 'bar'],
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->many($data, ['fields' => ['comment', 'user_id']]);

        $this->assertCount(2, $result);
        unset($data[0]['foo'], $data[1]['foo']);
        $this->assertEquals($data[0], $result[0]->toArray());
        $this->assertEquals($data[1], $result[1]->toArray());
    }

    /**
     * Test that many() also receives a fields option
     */
    public function testMergeManyFields(): void
    {
        $entities = [
            new OpenArticleEntity(['id' => 1, 'comment' => 'First post', 'user_id' => 2]),
            new OpenArticleEntity(['id' => 2, 'comment' => 'Second post', 'user_id' => 2]),
        ];
        $entities[0]->clean();
        $entities[1]->clean();

        $data = [
            ['id' => 2, 'comment' => 'Changed 2', 'user_id' => 10],
            ['id' => 1, 'comment' => 'Changed 1', 'user_id' => 20],
        ];
        $marshall = new Marshaller($this->comments);
        $result = $marshall->mergeMany($entities, $data, ['fields' => ['id', 'comment']]);

        $this->assertSame($entities[0], $result[0]);
        $this->assertSame($entities[1], $result[1]);

        $expected = ['id' => 2, 'comment' => 'Changed 2', 'user_id' => 2];
        $this->assertEquals($expected, $entities[1]->toArray());

        $expected = ['id' => 1, 'comment' => 'Changed 1', 'user_id' => 2];
        $this->assertEquals($expected, $entities[0]->toArray());
    }

    /**
     * test marshalling association data while passing a fields
     */
    public function testAssociationsFields(): void
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'user' => [
                'username' => 'mark',
                'password' => 'secret',
                'foo' => 'bar',
            ],
        ];
        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, [
            'fields' => ['title', 'body', 'user'],
            'associated' => [
                'Users' => ['fields' => ['username', 'foo']],
            ],
        ]);

        $this->assertSame($data['title'], $result->title);
        $this->assertSame($data['body'], $result->body);
        $this->assertNull($result->author_id);

        $this->assertInstanceOf('Cake\ORM\Entity', $result->user);
        $this->assertSame($data['user']['username'], $result->user->username);
        $this->assertNull($result->user->password);
    }

    /**
     * Tests merging associated data with a fields
     */
    public function testMergeAssociationWithfields(): void
    {
        $user = new Entity([
            'username' => 'mark',
            'password' => 'secret',
        ]);
        $entity = new Entity([
            'tile' => 'My Title',
            'user' => $user,
        ]);
        $user->setAccess('*', true);
        $entity->setAccess('*', true);

        $data = [
            'body' => 'My Content',
            'something' => 'else',
            'user' => [
                'password' => 'not a secret',
                'extra' => 'data',
            ],
        ];
        $marshall = new Marshaller($this->articles);
        $marshall->merge($entity, $data, [
            'fields' => ['something'],
            'associated' => ['Users' => ['fields' => ['extra']]],
        ]);
        $this->assertNull($entity->body);
        $this->assertSame('else', $entity->something);
        $this->assertSame($user, $entity->user);
        $this->assertSame('mark', $entity->user->username);
        $this->assertSame('secret', $entity->user->password);
        $this->assertSame('data', $entity->user->extra);
        $this->assertTrue($entity->isDirty('user'));
    }

    /**
     * Test marshalling nested associations on the _joinData structure
     * while having a fields
     */
    public function testJoinDataWhiteList(): void
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
                    ],
                ],
                [
                    'tag' => 'cakephp',
                    '_joinData' => [
                        'active' => 0,
                        'crazy' => 'stuff',
                        'user' => ['username' => 'Mark'],
                    ],
                ],
            ],
        ];

        $articlesTags = $this->getTableLocator()->get('ArticlesTags');
        $articlesTags->belongsTo('Users');

        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, [
            'associated' => [
                'Tags._joinData' => ['fields' => ['active', 'user']],
                'Tags._joinData.Users',
            ],
        ]);
        $this->assertInstanceOf(
            'Cake\ORM\Entity',
            $result->tags[0]->_joinData->user,
            'joinData should contain a user entity.'
        );
        $this->assertSame('Bill', $result->tags[0]->_joinData->user->username);
        $this->assertInstanceOf(
            'Cake\ORM\Entity',
            $result->tags[1]->_joinData->user,
            'joinData should contain a user entity.'
        );
        $this->assertSame('Mark', $result->tags[1]->_joinData->user->username);

        $this->assertNull($result->tags[0]->_joinData->crazy);
        $this->assertNull($result->tags[1]->_joinData->crazy);
    }

    /**
     * Test merging the _joinData entity for belongstomany associations
     * while passing a whitelist
     */
    public function testMergeJoinDataWithFields(): void
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
                    ],
                ],
                [
                    'id' => 2,
                    'tag' => 'cakephp',
                    '_joinData' => [
                        'active' => 0,
                    ],
                ],
            ],
        ];

        $options = ['associated' => ['Tags' => ['associated' => ['_joinData']]]];
        $marshall = new Marshaller($this->articles);
        $entity = $marshall->one($data, $options);
        $entity->setAccess('*', true);

        $data = [
            'title' => 'Haz data',
            'tags' => [
                ['id' => 1, 'tag' => 'Cake', '_joinData' => ['foo' => 'bar', 'crazy' => 'something']],
                ['tag' => 'new tag', '_joinData' => ['active' => 1, 'foo' => 'baz']],
            ],
        ];

        $tag1 = $entity->tags[0];
        $result = $marshall->merge($entity, $data, [
            'associated' => ['Tags._joinData' => ['fields' => ['foo']]],
        ]);
        $this->assertSame($data['title'], $result->title);
        $this->assertSame('My content', $result->body);
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
        $this->assertSame('new tag', $entity->tags[1]->tag);
        $this->assertTrue($entity->tags[0]->isDirty('_joinData'));
        $this->assertTrue($entity->tags[1]->isDirty('_joinData'));
    }

    /**
     * Tests marshalling with validation errors
     */
    public function testValidationFail(): void
    {
        $data = [
            'title' => 'Thing',
            'body' => 'hey',
        ];

        $this->articles->getValidator()->requirePresence('thing');
        $marshall = new Marshaller($this->articles);
        $entity = $marshall->one($data);
        $this->assertNotEmpty($entity->getError('thing'));
    }

    /**
     * Test that invalid validate options raise exceptions
     */
    public function testValidateInvalidType(): void
    {
        $this->expectException(RuntimeException::class);
        $data = ['title' => 'foo'];
        $marshaller = new Marshaller($this->articles);
        $marshaller->one($data, [
            'validate' => ['derp'],
        ]);
    }

    /**
     * Tests that associations are validated and custom validators can be used
     */
    public function testValidateWithAssociationsAndCustomValidator(): void
    {
        $data = [
            'title' => 'foo',
            'body' => 'bar',
            'user' => [
                'name' => 'Susan',
            ],
            'comments' => [
                [
                    'comment' => 'foo',
                ],
            ],
        ];
        $validator = (new Validator())->add('body', 'numeric', ['rule' => 'numeric']);
        $this->articles->setValidator('custom', $validator);

        $validator2 = (new Validator())->requirePresence('thing');
        $this->articles->Users->setValidator('customThing', $validator2);

        $this->articles->Comments->setValidator('default', $validator2);

        $entity = (new Marshaller($this->articles))->one($data, [
            'validate' => 'custom',
            'associated' => ['Users', 'Comments'],
        ]);
        $this->assertNotEmpty($entity->getError('body'), 'custom was not used');
        $this->assertNull($entity->body);
        $this->assertEmpty($entity->user->getError('thing'));
        $this->assertNotEmpty($entity->comments[0]->getError('thing'));

        $entity = (new Marshaller($this->articles))->one($data, [
            'validate' => 'custom',
            'associated' => ['Users' => ['validate' => 'customThing'], 'Comments'],
        ]);
        $this->assertNotEmpty($entity->getError('body'));
        $this->assertNull($entity->body);
        $this->assertNotEmpty($entity->user->getError('thing'), 'customThing was not used');
        $this->assertNotEmpty($entity->comments[0]->getError('thing'));
    }

    /**
     * Tests that validation can be bypassed
     */
    public function testSkipValidation(): void
    {
        $data = [
            'title' => 'foo',
            'body' => 'bar',
            'user' => [
                'name' => 'Susan',
            ],
        ];
        $validator = (new Validator())->requirePresence('thing');
        $this->articles->setValidator('default', $validator);
        $this->articles->Users->setValidator('default', $validator);

        $entity = (new Marshaller($this->articles))->one($data, [
            'validate' => false,
            'associated' => ['Users'],
        ]);
        $this->assertEmpty($entity->getError('thing'));
        $this->assertNotEmpty($entity->user->getError('thing'));

        $entity = (new Marshaller($this->articles))->one($data, [
            'associated' => ['Users' => ['validate' => false]],
        ]);
        $this->assertNotEmpty($entity->getError('thing'));
        $this->assertEmpty($entity->user->getError('thing'));
    }

    /**
     * Tests that it is possible to pass a validator directly in the options
     *
     * @deprecated
     */
    public function testPassingCustomValidator(): void
    {
        $this->deprecated(function () {
            $data = [
                'title' => 'Thing',
                'body' => 'hey',
            ];

            $validator = clone $this->articles->getValidator();
            $validator->requirePresence('thing');
            $marshall = new Marshaller($this->articles);
            $entity = $marshall->one($data, ['validate' => $validator]);
            $this->assertNotEmpty($entity->getError('thing'));
        });
    }

    /**
     * Tests that invalid property is being filled when data cannot be patched into an entity.
     */
    public function testValidationWithInvalidFilled(): void
    {
        $data = [
            'title' => 'foo',
            'number' => 'bar',
        ];
        $this->articles->setValidator(
            'custom',
            (new Validator())->add('number', 'numeric', ['rule' => 'numeric'])
        );
        $marshall = new Marshaller($this->articles);
        $entity = $marshall->one($data, ['validate' => 'custom']);
        $this->assertNotEmpty($entity->getError('number'));
        $this->assertNull($entity->number);
        $this->assertSame(['number' => 'bar'], $entity->getInvalid());
    }

    /**
     * Test merge with validation error
     */
    public function testMergeWithValidation(): void
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
            'author_id' => 1,
        ]);
        $this->assertEmpty($entity->getInvalid());

        $entity->setAccess('*', true);
        $entity->setNew(false);
        $entity->clean();

        $this->articles->getValidator()
            ->requirePresence('thing', 'update')
            ->requirePresence('id', 'update')
            ->add('author_id', 'numeric', ['rule' => 'numeric'])
            ->add('id', 'numeric', ['rule' => 'numeric', 'on' => 'update']);

        $expected = clone $entity;
        $result = $marshall->merge($expected, $data, []);

        $this->assertSame($expected, $result);
        $this->assertSame(1, $result->author_id);
        $this->assertNotEmpty($result->getError('thing'));
        $this->assertEmpty($result->getError('id'));

        $this->articles->getValidator()->requirePresence('thing', 'create');
        $result = $marshall->merge($entity, $data, []);

        $this->assertEmpty($result->getError('thing'));
        $this->assertSame(['author_id' => 'foo'], $result->getInvalid());
    }

    /**
     * Test merge with validation and create or update validation rules
     */
    public function testMergeWithCreate(): void
    {
        $data = [
            'title' => 'My title',
            'author_id' => 'foo',
        ];
        $marshall = new Marshaller($this->articles);
        $entity = new Entity([
            'title' => 'Foo',
            'body' => 'My Content',
            'author_id' => 1,
        ]);
        $entity->setAccess('*', true);
        $entity->setNew(true);
        $entity->clean();

        $this->articles->getValidator()
            ->requirePresence('thing', 'update')
            ->add('author_id', 'numeric', ['rule' => 'numeric', 'on' => 'update']);

        $expected = clone $entity;
        $result = $marshall->merge($expected, $data, []);

        $this->assertEmpty($result->getError('author_id'));
        $this->assertEmpty($result->getError('thing'));

        $entity->clean();
        $entity->setNew(false);
        $result = $marshall->merge($entity, $data, []);
        $this->assertNotEmpty($result->getError('author_id'));
        $this->assertNotEmpty($result->getError('thing'));
    }

    /**
     * Test merge() with translate behavior integration
     */
    public function testMergeWithTranslations(): void
    {
        $this->articles->addBehavior('Translate', [
            'fields' => ['title', 'body'],
        ]);

        $data = [
            'author_id' => 1,
            '_translations' => [
                'en' => [
                    'title' => 'English Title',
                    'body' => 'English Content',
                ],
                'es' => [
                    'title' => 'Titulo Español',
                    'body' => 'Contenido Español',
                ],
            ],
        ];

        $marshall = new Marshaller($this->articles);
        $entity = $this->articles->newEmptyEntity();
        $result = $marshall->merge($entity, $data, []);

        $this->assertSame($entity, $result);
        $this->assertEmpty($result->getErrors());
        $this->assertTrue($result->isDirty('_translations'));

        $translations = $result->get('_translations');
        $this->assertCount(2, $translations);
        $this->assertInstanceOf(OpenArticleEntity::class, $translations['en']);
        $this->assertInstanceOf(OpenArticleEntity::class, $translations['es']);

        /** @var \Cake\Datasource\EntityInterface $translation */
        $translation = $translations['en'];
        $this->assertEquals($data['_translations']['en'], $translation->toArray());
    }

    /**
     * Test Model.beforeMarshal event.
     */
    public function testBeforeMarshalEvent(): void
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'user' => [
                'name' => 'Robert',
                'username' => 'rob',
            ],
        ];

        $marshall = new Marshaller($this->articles);

        $this->articles->getEventManager()->on(
            'Model.beforeMarshal',
            function ($e, $data, $options): void {
                $this->assertArrayHasKey('validate', $options);
                $data['title'] = 'Modified title';
                $data['user']['username'] = 'robert';

                $options['associated'] = ['Users'];
            }
        );

        $entity = $marshall->one($data);

        $this->assertSame('Modified title', $entity->title);
        $this->assertSame('My content', $entity->body);
        $this->assertSame('Robert', $entity->user->name);
        $this->assertSame('robert', $entity->user->username);
    }

    /**
     * Test Model.beforeMarshal event on associated tables.
     */
    public function testBeforeMarshalEventOnAssociations(): void
    {
        $data = [
            'title' => 'My title',
            'body' => 'My content',
            'author_id' => 1,
            'user' => [
                'username' => 'mark',
                'password' => 'secret',
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

        // Assert event options are correct
        $this->articles->Users->getEventManager()->on(
            'Model.beforeMarshal',
            function ($e, $data, $options): void {
                $this->assertArrayHasKey('validate', $options);
                $this->assertTrue($options['validate']);

                $this->assertArrayHasKey('associated', $options);
                $this->assertSame([], $options['associated']);

                $this->assertArrayHasKey('association', $options);
                $this->assertInstanceOf('Cake\ORM\Association', $options['association']);
            }
        );

        $this->articles->Users->getEventManager()->on(
            'Model.beforeMarshal',
            function ($e, $data, $options): void {
                $data['secret'] = 'h45h3d';
            }
        );

        $this->articles->Comments->getEventManager()->on(
            'Model.beforeMarshal',
            function ($e, $data): void {
                $data['comment'] .= ' (modified)';
            }
        );

        $this->articles->Tags->getEventManager()->on(
            'Model.beforeMarshal',
            function ($e, $data): void {
                $data['tag'] .= ' (modified)';
            }
        );

        $this->articles->Tags->junction()->getEventManager()->on(
            'Model.beforeMarshal',
            function ($e, $data): void {
                $data['modified_by'] = 1;
            }
        );

        $entity = $marshall->one($data, [
            'associated' => ['Users', 'Comments', 'Tags'],
        ]);

        $this->assertSame('h45h3d', $entity->user->secret);
        $this->assertSame('First post (modified)', $entity->comments[0]->comment);
        $this->assertSame('Second post (modified)', $entity->comments[1]->comment);
        $this->assertSame('news (modified)', $entity->tags[0]->tag);
        $this->assertSame('cakephp (modified)', $entity->tags[1]->tag);
        $this->assertSame(1, $entity->tags[0]->_joinData->modified_by);
        $this->assertSame(1, $entity->tags[1]->_joinData->modified_by);
    }

    /**
     * Test Model.afterMarshal event.
     */
    public function testAfterMarshalEvent(): void
    {
        $data = [
            'title' => 'original title',
            'body' => 'original content',
            'user' => [
                'name' => 'Robert',
                'username' => 'rob',
            ],
        ];

        $marshall = new Marshaller($this->articles);

        $this->articles->getEventManager()->on(
            'Model.afterMarshal',
            function ($e, $entity, $data, $options): void {
                $this->assertInstanceOf('Cake\ORM\Entity', $entity);
                $this->assertArrayHasKey('validate', $options);
                $this->assertFalse($options['isMerge']);

                $data['title'] = 'Modified title';
                $data['user']['username'] = 'robert';
                $options['associated'] = ['Users'];

                $entity->body = 'Modified body';
            }
        );

        $entity = $marshall->one($data);

        $this->assertSame('original title', $entity->title, '$data is immutable');
        $this->assertSame('Modified body', $entity->body);
        // both $options and $data are unchangeable
        $this->assertIsArray($entity->user, '$options[\'associated\'] is ignored');
        $this->assertSame('Robert', $entity->user['name']);
        $this->assertSame('rob', $entity->user['username']);
    }

    /**
     * Test Model.afterMarshal event on patchEntity.
     * when $options['fields'] is set and is empty
     */
    public function testAfterMarshalEventOnPatchEntity(): void
    {
        $data = [
            'title' => 'original title',
            'body' => 'original content',
            'user' => [
                'name' => 'Robert',
                'username' => 'rob',
            ],
        ];

        $marshall = new Marshaller($this->articles);

        $this->articles->getEventManager()->on(
            'Model.afterMarshal',
            function ($e, $entity, $data, $options): void {
                $this->assertInstanceOf('Cake\ORM\Entity', $entity);
                $this->assertArrayHasKey('validate', $options);
                $this->assertTrue($options['isMerge']);

                $data['title'] = 'Modified title';
                $data['user']['username'] = 'robert';
                $options['associated'] = ['Users'];

                $entity->body = 'options[fields] is empty';
                if (isset($options['fields'])) {
                    $entity->body = 'options[fields] is set';
                }
            }
        );

        //test when $options['fields'] is empty
        $entity = $this->articles->newEmptyEntity();
        $result = $marshall->merge($entity, $data, []);

        $this->assertSame('original title', $entity->title, '$data is immutable');
        $this->assertSame('options[fields] is empty', $entity->body);
        // both $options and $data are unchangeable
        $this->assertIsArray($entity->user, '$options[\'associated\'] is ignored');
        $this->assertSame('Robert', $entity->user['name']);
        $this->assertSame('rob', $entity->user['username']);

        //test when $options['fields'] is set
        $entity = $this->articles->newEmptyEntity();
        $result = $marshall->merge($entity, $data, ['fields' => ['title', 'body']]);

        $this->assertSame('original title', $entity->title, '$data is immutable');
        $this->assertSame('options[fields] is set', $entity->body);
    }

    /**
     * Tests that patching an association resulting in no changes, will
     * not mark the parent entity as dirty
     */
    public function testAssociationNoChanges(): void
    {
        $options = ['markClean' => true, 'isNew' => false];
        $entity = new Entity([
            'title' => 'My Title',
            'user' => new Entity([
                'username' => 'mark',
                'password' => 'not a secret',
            ], $options),
        ], $options);

        $data = [
            'body' => 'My Content',
            'user' => [
                'username' => 'mark',
                'password' => 'not a secret',
            ],
        ];
        $marshall = new Marshaller($this->articles);
        $marshall->merge($entity, $data, ['associated' => ['Users']]);
        $this->assertSame('My Content', $entity->body);
        $this->assertInstanceOf('Cake\ORM\Entity', $entity->user);
        $this->assertSame('mark', $entity->user->username);
        $this->assertSame('not a secret', $entity->user->password);
        $this->assertFalse($entity->isDirty('user'));
        $this->assertTrue($entity->user->isNew());
    }

    /**
     * Test that primary key meta data is being read from the table
     * and not the schema reflection when handling belongsToMany associations.
     */
    public function testEnsurePrimaryKeyBeingReadFromTableForHandlingEmptyStringPrimaryKey(): void
    {
        $data = [
            'id' => '',
        ];

        $articles = $this->getTableLocator()->get('Articles');
        $articles->getSchema()->dropConstraint('primary');
        $articles->setPrimaryKey('id');

        $marshall = new Marshaller($articles);
        $result = $marshall->one($data);

        $this->assertFalse($result->isDirty('id'));
        $this->assertNull($result->id);
    }

    /**
     * Test that primary key meta data is being read from the table
     * and not the schema reflection when handling belongsToMany associations.
     */
    public function testEnsurePrimaryKeyBeingReadFromTableWhenLoadingBelongsToManyRecordsByPrimaryKey(): void
    {
        $data = [
            'tags' => [
                [
                    'id' => 1,
                ],
                [
                    'id' => 2,
                ],
            ],
        ];

        $tags = $this->getTableLocator()->get('Tags');
        $tags->getSchema()->dropConstraint('primary');
        $tags->setPrimaryKey('id');

        $marshall = new Marshaller($this->articles);
        $result = $marshall->one($data, ['associated' => ['Tags']]);

        $expected = [
            'tags' => [
                [
                    'id' => 1,
                    'name' => 'tag1',
                    'description' => 'A big description',
                    'created' => new FrozenTime('2016-01-01 00:00'),
                ],
                [
                    'id' => 2,
                    'name' => 'tag2',
                    'description' => 'Another big description',
                    'created' => new FrozenTime('2016-01-01 00:00'),
                ],
            ],
        ];
        $this->assertEquals($expected, $result->toArray());
    }

    /**
     * Tests that ID values are being bound with the correct type when loading associated records.
     */
    public function testInvalidTypesWhenLoadingAssociatedByIds(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot convert value of type `string` to integer');

        $data = [
            'title' => 'article',
            'body' => 'some content',
            'comments' => [
                '_ids' => ['foobar'],
            ],
        ];

        $marshaller = new Marshaller($this->articles);
        $marshaller->one($data, ['associated' => ['Comments']]);
    }

    /**
     * Tests that composite ID values are being bound with the correct type when loading associated records.
     */
    public function testInvalidTypesWhenLoadingAssociatedByCompositeIds(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot convert value of type `string` to integer');

        $data = [
            'title' => 'article',
            'body' => 'some content',
            'comments' => [
                '_ids' => [['foo', 'bar']],
            ],
        ];

        $this->articles->Comments->setPrimaryKey(['id', 'article_id']);

        $marshaller = new Marshaller($this->articles);
        $marshaller->one($data, ['associated' => ['Comments']]);
    }
}
