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

use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Time;

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
	public $fixtures = ['core.user', 'core.article', 'core.tag', 'core.articles_tag', 'core.author'];

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
			'propertyName' => 'foo']
		);

		$results = $table->find()
			->contain(['Authors.Stuff', 'Things.Stuff'])
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

}
