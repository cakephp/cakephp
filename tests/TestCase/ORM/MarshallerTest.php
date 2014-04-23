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

use Cake\ORM\Entity;
use Cake\ORM\Marshaller;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Time;

/**
 * Test entity for mass assignment.
 */
class OpenEntity extends Entity {

	protected $_accessible = [
		'*' => true,
	];

}

/**
 * Test entity for mass assignment.
 */
class ProtectedArticle extends Entity {

	protected $_accessible = [
		'title' => true,
		'body' => true
	];

}

/**
 * Marshaller test case
 */
class MarshallerTest extends TestCase {

	public $fixtures = ['core.tag', 'core.articles_tag', 'core.article', 'core.user', 'core.comment'];

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$articles = TableRegistry::get('Articles');
		$articles->belongsTo('Users');
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
	}

/**
 * Teardown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		TableRegistry::clear();
		unset($this->articles, $this->comments);
	}

/**
 * Test one() in a simple use.
 *
 * @return void
 */
	public function testOneSimple() {
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
		$this->assertNull($result->isNew(), 'Should be detached');
		$this->assertEquals('Articles', $result->source());
	}

/**
 * Test marshalling datetime/date field.
 *
 * @return void
 */
	public function testOneWithDatetimeField() {
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
 * Test one() follows mass-assignment rules.
 *
 * @return void
 */
	public function testOneAccessibleProperties() {
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
 * test one() with a wrapping model name.
 *
 * @return void
 */
	public function testOneWithAdditionalName() {
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
		$result = $marshall->one($data, ['Users']);

		$this->assertInstanceOf('Cake\ORM\Entity', $result);
		$this->assertTrue($result->dirty(), 'Should be a dirty entity.');
		$this->assertNull($result->isNew(), 'Should be detached');
		$this->assertEquals($data['Articles']['title'], $result->title);
		$this->assertEquals($data['Articles']['user']['username'], $result->user->username);
	}

/**
 * test one() with association data.
 *
 * @return void
 */
	public function testOneAssociationsSingle() {
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
		$result = $marshall->one($data, ['Users']);

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
	public function testOneAssociationsMany() {
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
		$result = $marshall->one($data, ['Comments']);

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
	public function testOneBelongsToManyJoinData() {
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
			'Tags' => [
				'associated' => ['_joinData']
			]
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
 * Test marshalling nested associations on the _joinData structure.
 *
 * @return void
 */
	public function testOneBelongsToManyJoinDataAssociated() {
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
		$result = $marshall->one($data, [
			'Tags' => [
				'associated' => [
					'_joinData' => ['associated' => ['Users']]
				]
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
	}

/**
 * Test one() with deeper associations.
 *
 * @return void
 */
	public function testOneDeepAssociations() {
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
		$result = $marshall->one($data, ['Articles' => ['associated' => ['Users']]]);

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
	public function testManySimple() {
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
 * test many() with nested associations.
 *
 * @return void
 */
	public function testManyAssociations() {
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
		$result = $marshall->many($data, ['Users']);

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
	public function testOneGenerateBelongsToManyEntitiesFromIds() {
		$data = [
			'title' => 'Haz tags',
			'body' => 'Some content here',
			'tags' => ['_ids' => '']
		];
		$marshall = new Marshaller($this->articles);
		$result = $marshall->one($data, ['Tags']);

		$this->assertCount(0, $result->tags);

		$data = [
			'title' => 'Haz tags',
			'body' => 'Some content here',
			'tags' => ['_ids' => [1, 2, 3]]
		];
		$marshall = new Marshaller($this->articles);
		$result = $marshall->one($data, ['Tags']);

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
	public function testMergeSimple() {
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
 * Tests that merge respects the entity accessible methods
 *
 * @return void
 */
	public function testMergeWhitelist() {
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
 * Tests that fields with the same value are not marked as dirty
 *
 * @return void
 */
	public function testMergeDirty() {
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
	public function testMergeWithSingleAssociation() {
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
			'user' => [
				'password' => 'not a secret'
			]
		];
		$marshall = new Marshaller($this->articles);
		$marshall->merge($entity, $data, ['Users']);
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
	public function testMergeCreateAssociation() {
		$entity = new Entity([
			'tile' => 'My Title'
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
		$marshall->merge($entity, $data, ['Users']);
		$this->assertEquals('My Content', $entity->body);
		$this->assertInstanceOf('Cake\ORM\Entity', $entity->user);
		$this->assertEquals('mark', $entity->user->username);
		$this->assertEquals('not a secret', $entity->user->password);
		$this->assertTrue($entity->dirty('user'));
		$this->assertNull($entity->user->isNew());
	}

/**
 * Tests merging one to many associations
 *
 * @return void
 */
	public function testMergeMultipleAssociations() {
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
				['comment' => 'Extra comment 2']
			]
		];
		$marshall = new Marshaller($this->articles);
		$result = $marshall->merge($entity, $data, ['Users', 'Comments']);
		$this->assertSame($entity, $result);
		$this->assertSame($user, $result->user);
		$this->assertEquals('not so secret', $entity->user->password);
		$this->assertSame($comment1, $entity->comments[0]);
		$this->assertSame($comment2, $entity->comments[1]);
		$this->assertEquals('Altered comment 1', $entity->comments[0]->comment);
		$this->assertEquals('Altered comment 2', $entity->comments[1]->comment);
		$this->assertEquals(
			['comment' => 'Extra comment 3', 'id' => 3],
			$entity->comments[2]->toArray()
		);
		$this->assertEquals(
			['comment' => 'Extra comment 1'],
			$entity->comments[3]->toArray()
		);
		$this->assertEquals(
			['comment' => 'Extra comment 2'],
			$entity->comments[4]->toArray()
		);
	}

/**
 * Tests that merging data to an entity containing belongsToMany and _ids
 * will just overwrite the data
 *
 * @return void
 */
	public function testMergeBelongsToManyEntitiesFromIds() {
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
		$result = $marshall->merge($entity, $data, ['Tags']);

		$this->assertCount(3, $result->tags);
		$this->assertInstanceOf('Cake\ORM\Entity', $result->tags[0]);
		$this->assertInstanceOf('Cake\ORM\Entity', $result->tags[1]);
		$this->assertInstanceOf('Cake\ORM\Entity', $result->tags[2]);
	}

/**
 * Test merging the _joinData entity for belongstomany associations.
 *
 * @return void
 */
	public function testMergeBelongsToManyJoinData() {
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

		$options = ['Tags' => ['associated' => ['_joinData']]];
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
	public function testMergeJoinDataAssociations() {
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

		$options = [
			'Tags' => [
				'associated' => [
					'_joinData' => ['associated' => ['Users']]
				]
			]
		];
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
	public function testMergeManySimple() {
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
 * Tests that only records found in the data array are returned, those that cannot
 * be matched are discarded
 *
 * @return void
 */
	public function testMergeManyWithAppend() {
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
	}

/**
 * Tests merge with data types that need to be marshalled
 *
 * @return void
 */
	public function testMergeComplexType() {
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

}
