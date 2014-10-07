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

use Cake\Core\Plugin;
use Cake\I18n\Time;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Contains regression test for the Query builder
 *
 */
class QueryRegressionTest extends TestCase {

/**
 * Fixture to be used
 *
 * @var array
 */
	public $fixtures = [
		'core.users',
		'core.articles',
		'core.comments',
		'core.tags',
		'core.articles_tags',
		'core.authors',
		'core.special_tags',
		'core.translates',
	];

/**
 * Tear down
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();

		TableRegistry::clear();
	}

/**
 * Test for https://github.com/cakephp/cakephp/issues/3087
 *
 * @return void
 */
	public function testSelectTimestampColumn() {
		$table = TableRegistry::get('users');
		$user = $table->find()->where(['id' => 1])->first();
		$this->assertEquals(new Time('2007-03-17 01:16:23'), $user->created);
		$this->assertEquals(new Time('2007-03-17 01:18:31'), $user->updated);
	}

/**
 * Tests that EagerLoader does not try to create queries for associations having no
 * keys to compare against
 *
 * @return void
 */
	public function testEagerLoadingFromEmptyResults() {
		$table = TableRegistry::get('Articles');
		$table->belongsToMany('ArticlesTags');
		$results = $table->find()->where(['id >' => 100])->contain('ArticlesTags')->toArray();
		$this->assertEmpty($results);
	}

/**
 * Tests that duplicate aliases in contain() can be used, even when they would
 * naturally be attached to the query instead of eagerly loaded. What should
 * happen here is that One of the duplicates will be changed to be loaded using
 * an extra query, but yielding the same results
 *
 * @return void
 */
	public function testDuplicateAttachableAliases() {
		TableRegistry::get('Stuff', ['table' => 'tags']);
		TableRegistry::get('Things', ['table' => 'articles_tags']);

		$table = TableRegistry::get('Articles');
		$table->belongsTo('Authors');
		$table->hasOne('Things', ['propertyName' => 'articles_tag']);
		$table->Authors->target()->hasOne('Stuff', [
			'foreignKey' => 'id',
			'propertyName' => 'favorite_tag'
		]);
		$table->Things->target()->belongsTo('Stuff', [
			'foreignKey' => 'tag_id',
			'propertyName' => 'foo'
		]);

		$results = $table->find()
			->contain(['Authors.Stuff', 'Things.Stuff'])
			->order(['Articles.id' => 'ASC'])
			->toArray();

		$this->assertEquals(1, $results[0]->articles_tag->foo->id);
		$this->assertEquals(1, $results[0]->author->favorite_tag->id);
		$this->assertEquals(2, $results[1]->articles_tag->foo->id);
		$this->assertEquals(1, $results[0]->author->favorite_tag->id);
		$this->assertEquals(1, $results[2]->articles_tag->foo->id);
		$this->assertEquals(3, $results[2]->author->favorite_tag->id);
		$this->assertEquals(3, $results[3]->articles_tag->foo->id);
		$this->assertEquals(3, $results[3]->author->favorite_tag->id);
	}

/**
 * Test for https://github.com/cakephp/cakephp/issues/3410
 *
 * @return void
 */
	public function testNullableTimeColumn() {
		$table = TableRegistry::get('users');
		$entity = $table->newEntity(['username' => 'derp', 'created' => null]);
		$this->assertSame($entity, $table->save($entity));
		$this->assertNull($entity->created);
	}

/**
 * Test for https://github.com/cakephp/cakephp/issues/3626
 *
 * Checks that join data is actually created and not tried to be updated every time
 * @return void
 */
	public function testCreateJointData() {
		$articles = TableRegistry::get('Articles');
		$articles->belongsToMany('Highlights', [
			'className' => 'TestApp\Model\Table\TagsTable',
			'foreignKey' => 'article_id',
			'targetForeignKey' => 'tag_id',
			'through' => 'SpecialTags'
		]);
		$entity = $articles->get(2);
		$data = [
			'id' => 2,
			'highlights' => [
				[
					'name' => 'New Special Tag',
					'_joinData' => ['highlighted' => true, 'highlighted_time' => '2014-06-01 10:10:00']
				]
			]
		];
		$entity = $articles->patchEntity($entity, $data, ['Highlights._joinData']);
		$articles->save($entity);
		$entity = $articles->get(2, ['contain' => ['Highlights']]);
		$this->assertEquals(4, $entity->highlights[0]->_joinData->tag_id);
		$this->assertEquals('2014-06-01', $entity->highlights[0]->_joinData->highlighted_time->format('Y-m-d'));
	}

/**
 * Tests that the junction table instance taken from both sides of a belongsToMany
 * relationship is actually the same object.
 *
 * @return void
 */
	public function testReciprocalBelongsToMany() {
		$articles = TableRegistry::get('Articles');
		$tags = TableRegistry::get('Tags');

		$articles->belongsToMany('Tags');
		$tags->belongsToMany('Articles');

		$left = $articles->Tags->junction();
		$right = $tags->Articles->junction();
		$this->assertSame($left, $right);
	}

/**
 * Test for https://github.com/cakephp/cakephp/issues/4253
 *
 * Makes sure that the belongsToMany association is not overwritten with conflicting information
 * by any of the sides when the junction() function is invoked
 *
 * @return void
 */
	public function testReciprocalBelongsToMany2() {
		$articles = TableRegistry::get('Articles');
		$tags = TableRegistry::get('Tags');

		$articles->belongsToMany('Tags');
		$tags->belongsToMany('Articles');

		$result = $articles->find()->contain(['Tags'])->first();
		$sub = $articles->Tags->find()->select(['id'])->matching('Articles', function ($q) use ($result) {
			return $q->where(['Articles.id' => 1]);
		});

		$query = $articles->Tags->find()->where(['id NOT IN' => $sub]);
		$this->assertEquals(1, $query->count());
	}

/**
 * Returns an array with the saving strategies for a belongsTo association
 *
 * @return array
 */
	public function strategyProvider() {
		return [['append', 'replace']];
	}

/**
 * Test for https://github.com/cakephp/cakephp/issues/3677 and
 * https://github.com/cakephp/cakephp/issues/3714
 *
 * Checks that only relevant associations are passed when saving _joinData
 * Tests that _joinData can also save deeper associations
 *
 * @dataProvider strategyProvider
 * @param string $strategy
 * @return void
 */
	public function testBelongsToManyDeepSave($strategy) {
		$articles = TableRegistry::get('Articles');
		$articles->belongsToMany('Highlights', [
			'className' => 'TestApp\Model\Table\TagsTable',
			'foreignKey' => 'article_id',
			'targetForeignKey' => 'tag_id',
			'through' => 'SpecialTags',
			'saveStrategy' => $strategy
		]);
		$articles->Highlights->junction()->belongsTo('Authors');
		$articles->Highlights->hasOne('Authors', [
			'foreignKey' => 'id'
		]);
		$entity = $articles->get(2, ['contain' => ['Highlights']]);

		$data = [
			'highlights' => [
				[
					'name' => 'New Special Tag',
					'_joinData' => [
						'highlighted' => true,
						'highlighted_time' => '2014-06-01 10:10:00',
						'author' => [
							'name' => 'mariano'
						]
					],
					'author' => ['name' => 'mark']
				]
			]
		];
		$options = [
			'associated' => [
				'Highlights._joinData.Authors', 'Highlights.Authors'
			]
		];
		$entity = $articles->patchEntity($entity, $data, $options);
		$articles->save($entity, $options);
		$entity = $articles->get(2, [
			'contain' => [
				'SpecialTags' => ['sort' => ['SpecialTags.id' => 'ASC']],
				'SpecialTags.Authors',
				'Highlights.Authors'
			]
		]);
		$this->assertEquals('mariano', end($entity->special_tags)->author->name);
		$this->assertEquals('mark', end($entity->highlights)->author->name);
	}

/**
 * Tests that no exceptions are generated becuase of ambiguous column names in queries
 * during a  save operation
 *
 * @see https://github.com/cakephp/cakephp/issues/3803
 * @return void
 */
	public function testSaveWithCallbacks() {
		$articles = TableRegistry::get('Articles');
		$articles->belongsTo('Authors');

		$articles->eventManager()->attach(function ($event, $query) {
			return $query->contain('Authors');
		}, 'Model.beforeFind');

		$article = $articles->newEntity();
		$article->title = 'Foo';
		$article->body = 'Bar';
		$this->assertSame($article, $articles->save($article));
	}

/**
 * Test that save() works with entities containing expressions
 * as properties.
 *
 * @return void
 */
	public function testSaveWithExpressionProperty() {
		$articles = TableRegistry::get('Articles');
		$article = $articles->newEntity();
		$article->title = new \Cake\Database\Expression\QueryExpression("SELECT 'jose'");
		$this->assertSame($article, $articles->save($article));
	}

/**
 * Tests that whe saving deep associations for a belongsToMany property,
 * data is not removed becuase of excesive associations filtering.
 *
 * @see https://github.com/cakephp/cakephp/issues/4009
 * @return void
 */
	public function testBelongsToManyDeepSave2() {
		$articles = TableRegistry::get('Articles');
		$articles->belongsToMany('Highlights', [
			'className' => 'TestApp\Model\Table\TagsTable',
			'foreignKey' => 'article_id',
			'targetForeignKey' => 'tag_id',
			'through' => 'SpecialTags',
		]);
		$articles->Highlights->hasMany('TopArticles', [
			'className' => 'TestApp\Model\Table\ArticlesTable',
			'foreignKey' => 'author_id',
		]);
		$entity = $articles->get(2, ['contain' => ['Highlights']]);

		$data = [
			'highlights' => [
				[
					'name' => 'New Special Tag',
					'_joinData' => [
						'highlighted' => true,
						'highlighted_time' => '2014-06-01 10:10:00',
					],
					'top_articles' => [
						['title' => 'First top article'],
						['title' => 'Second top article'],
					]
				]
			]
		];
		$options = [
			'associated' => [
				'Highlights._joinData', 'Highlights.TopArticles'
			]
		];
		$entity = $articles->patchEntity($entity, $data, $options);
		$articles->save($entity, $options);
		$entity = $articles->get(2, [
			'contain' => [
				'Highlights.TopArticles'
			]
		]);
		$highlights = $entity->highlights[0];
		$this->assertEquals('First top article', $highlights->top_articles[0]->title);
		$this->assertEquals('Second top article', $highlights->top_articles[1]->title);
		$this->assertEquals(
			new Time('2014-06-01 10:10:00'),
			$highlights->_joinData->highlighted_time
		);
	}

/**
 * An integration test that spot checks that associations use the
 * correct alias names to generate queries.
 *
 * @return void
 */
	public function testPluginAssociationQueryGeneration() {
		Plugin::load('TestPlugin');
		$articles = TableRegistry::get('Articles');
		$articles->hasMany('TestPlugin.Comments');
		$articles->belongsTo('TestPlugin.Authors');

		$result = $articles->find()
			->where(['Articles.id' => 2])
			->contain(['Comments', 'Authors'])
			->first();

		$this->assertNotEmpty(
			$result->comments[0]->id,
			'No SQL error and comment exists.'
		);
		$this->assertNotEmpty(
			$result->author->id,
			'No SQL error and author exists.'
		);
	}

/**
 * Tests that loading associations having the same alias in the
 * joinable associations chain is not sensitive to the order in which
 * the associations are selected.
 *
 * @see https://github.com/cakephp/cakephp/issues/4454
 * @return void
 */
	public function testAssociationChainOrder() {
		$articles = TableRegistry::get('Articles');
		$articles->belongsTo('Authors');
		$articles->hasOne('ArticlesTags');

		$articlesTags = TableRegistry::get('ArticlesTags');
		$articlesTags->belongsTo('Authors', [
			'foreignKey' => 'tag_id'
		]);

		$resultA = $articles->find()
			->contain(['ArticlesTags.Authors', 'Authors'])
			->first();

		$resultB = $articles->find()
			->contain(['Authors', 'ArticlesTags.Authors'])
			->first();

		$this->assertEquals($resultA, $resultB);
		$this->assertNotEmpty($resultA->author);
		$this->assertNotEmpty($resultA->articles_tag->author);
	}

/**
 * Test that offset/limit are elided from subquery loads.
 *
 * @return void
 */
	public function testAssociationSubQueryNoOffset() {
		$table = TableRegistry::get('Articles');
		$table->addBehavior('Translate', ['fields' => ['title', 'body']]);
		$table->locale('eng');
		$query = $table->find('translations')->limit(10)->offset(1);
		$result = $query->toArray();
		$this->assertCount(2, $result);
	}

/**
 * Tests that using the subquery strategy in a deep assotiatin returns the right results
 *
 * @see https://github.com/cakephp/cakephp/issues/4484
 * @return void
 */
	public function testDeepBelongsToManySubqueryStrategy() {
		$table = TableRegistry::get('Authors');
		$table->hasMany('Articles');
		$table->Articles->belongsToMany('Tags', [
			'strategy' => 'subquery'
		]);
		$table->Articles->Tags->junction();
		$result = $table->find()->contain(['Articles.Tags'])->toArray();
		$this->assertEquals(
			['tag1', 'tag3'],
			collection($result[2]->articles[0]->tags)->extract('name')->toArray()
		);
	}

/**
 * Tests that getting the count of a query having containments return
 * the correct results
 *
 * @see https://github.com/cakephp/cakephp/issues/4511
 * @return void
 */
	public function testCountWithContain() {
		$table = TableRegistry::get('Articles');
		$table->belongsTo('Authors', ['joinType' => 'inner']);
		$count = $table
			->find()
			->contain(['Authors' => function ($q) {
				return $q->where(['Authors.id' => 1]);
			}])
			->count();
		$this->assertEquals(2, $count);
	}

/**
 * Test that deep containments don't generate empty entities for
 * intermediary relations.
 *
 * @return void
 */
	public function testContainNoEmptyAssociatedObjects() {
		$comments = TableRegistry::get('Comments');
		$comments->belongsTo('Users');
		$users = TableRegistry::get('Users');
		$users->hasMany('Articles', [
			'foreignKey' => 'author_id'
		]);

		$comments->updateAll(['user_id' => 99], ['id' => 1]);

		$result = $comments->find()
			->contain(['Users'])
			->where(['Comments.id' => 1])
			->first();
		$this->assertNull($result->user, 'No record should be null.');

		$result = $comments->find()
			->contain(['Users', 'Users.Articles'])
			->where(['Comments.id' => 1])
			->first();
		$this->assertNull($result->user, 'No record should be null.');
	}

}
