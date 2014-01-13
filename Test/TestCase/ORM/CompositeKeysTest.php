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
namespace Cake\Test\TestCase\ORM;

use Cake\Database\ConnectionManager;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Integration tetss for table operations involving composite keys
 */
class CompositeKeyTest extends TestCase {

/**
 * Fixture to be used
 *
 * @var array
 */
	public $fixtures = ['core.site_article', 'core.site_author'];

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->connection = ConnectionManager::get('test');
	}

/**
 * Data provider for the two types of strategies HasMany implements
 *
 * @return void
 */
	public function strategiesProvider() {
		return [['subquery'], ['select']];
	}

/**
 * Tests that HasMany associations are correctly eager loaded and results
 * correctly nested when multiple foreignKeys are used
 *
 * @dataProvider strategiesProvider
 * @return void
 */
	public function testHasManyEagerCompositeKeys($strategy) {
		$table = TableRegistry::get('SiteAuthors');
		TableRegistry::get('SiteArticles');
		$table->hasMany('SiteArticles', [
			'propertyName' => 'articles',
			'strategy' => $strategy,
			'sort' => ['SiteArticles.id' => 'asc'],
			'foreignKey' => ['author_id', 'site_id']
		]);
		$query = new Query($this->connection, $table);

		$results = $query->select()
			->contain('SiteArticles')
			->hydrate(false)
			->toArray();
		$expected = [
			[
				'id' => 1,
				'name' => 'mark',
				'site_id' => 1,
				'articles' => [
					[
						'id' => 1,
						'title' => 'First Article',
						'body' => 'First Article Body',
						'author_id' => 1,
						'site_id' => 1
					]
				]
			],
			[
				'id' => 2,
				'name' => 'juan',
				'site_id' => 2
			],
			[
				'id' => 3,
				'name' => 'jose',
				'site_id' => 2,
				'articles' => [
					[
						'id' => 2,
						'title' => 'Second Article',
						'body' => 'Second Article Body',
						'author_id' => 3,
						'site_id' => 2
					]
				]
			],
			[
				'id' => 4,
				'name' => 'andy',
				'site_id' => 1
			]
		];
		$this->assertEquals($expected, $results);

		$results = $query->repository($table)
			->select()
			->contain(['SiteArticles' => ['conditions' => ['id' => 2]]])
			->hydrate(false)
			->toArray();
		unset($expected[0]['articles']);
		$this->assertEquals($expected, $results);
		$this->assertEquals($table->association('SiteArticles')->strategy(), $strategy);
	}

}
