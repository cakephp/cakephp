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
namespace Cake\Test\TestCase\Model\Behavior;

use Cake\Event\Event;
use Cake\Model\Behavior\TranslateBehavior;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Translate behavior test case
 */
class TranslateBehaviorTest extends TestCase {

/**
 * fixtures
 *
 * @var array
 */
	public $fixtures = [
		'core.translate',
		'core.article'
	];

	public function tearDown() {
		parent::tearDown();
		TableRegistry::clear();
	}

/**
 * Tests that fields from a translated model are overriden
 *
 * @return void
 */
	public function testFindSingleLocale() {
		$table = TableRegistry::get('Articles');
		$table->addBehavior('Translate', ['fields' => ['title', 'body']]);
		$table->locale('eng');
		$results = $table->find()->combine('title', 'body', 'id')->toArray();
		$expected = [
			1 => ['Title #1' => 'Content #1'],
			2 => ['Title #2' => 'Content #2'],
			3 => ['Title #3' => 'Content #3'],
		];
		$this->assertSame($expected, $results);
	}

/**
 * Tests that overriding fields with the translate behavior works when
 * using conditions and that all other columns are preserved
 *
 * @return void
 */
	public function testFindSingleLocaleWithConditions() {
		$table = TableRegistry::get('Articles');
		$table->addBehavior('Translate', ['fields' => ['title', 'body']]);
		$table->locale('eng');
		$results = $table->find()
			->where(['Articles.id' => 2])
			->all();

		$this->assertCount(1, $results);
		$row = $results->first();

		$expected = [
			'id' => 2,
			'title' => 'Title #2',
			'body' => 'Content #2',
			'author_id' => 3,
			'published' => 'Y',
			'_locale' => 'eng'
		];
		$this->assertEquals($expected, $row->toArray());
	}

/**
 * Tests that translating fields work when other formatters are used
 *
 * @return void
 */
	public function testFindList() {
		$table = TableRegistry::get('Articles');
		$table->addBehavior('Translate', ['fields' => ['title', 'body']]);
		$table->locale('eng');

		$results = $table->find('list')->toArray();
		$expected = [1 => 'Title #1', 2 => 'Title #2', 3 => 'Title #3'];
		$this->assertSame($expected, $results);
	}

/**
 * Tests that the query count return the correct results
 *
 * @return void
 */
	public function testFindCount() {
		$table = TableRegistry::get('Articles');
		$table->addBehavior('Translate', ['fields' => ['title', 'body']]);
		$table->locale('eng');

		$this->assertEquals(3, $table->find()->count());
	}

/**
 * Tests that it is possible to get all translated fields at once
 *
 * @return void
 */
	public function testFindTranslations() {
		$table = TableRegistry::get('Articles');
		$table->addBehavior('Translate', ['fields' => ['title', 'body']]);
		$results = $table->find('translations');
		$expected = [
			[
				'eng' => ['title' => 'Title #1', 'body' => 'Content #1', 'locale' => 'eng'],
				'deu' => ['title' => 'Titel #1', 'body' => 'Inhalt #1', 'locale' => 'deu'],
				'cze' => ['title' => 'Titulek #1', 'body' => 'Obsah #1', 'locale' => 'cze']
			],
			[
				'eng' => ['title' => 'Title #2', 'body' => 'Content #2', 'locale' => 'eng'],
				'deu' => ['title' => 'Titel #2', 'body' => 'Inhalt #2', 'locale' => 'deu'],
				'cze' => ['title' => 'Titulek #2', 'body' => 'Obsah #2', 'locale' => 'cze']
			],
			[
				'eng' => ['title' => 'Title #3', 'body' => 'Content #3', 'locale' => 'eng'],
				'deu' => ['title' => 'Titel #3', 'body' => 'Inhalt #3', 'locale' => 'deu'],
				'cze' => ['title' => 'Titulek #3', 'body' => 'Obsah #3', 'locale' => 'cze']
			]
		];

		$translations = $results->map(function($row) {
			$translations = $row->get('_translations');
			if (!$translations) {
				return [];
			}
			return array_map(function($t) {
				return $t->toArray();
			}, $translations);
		});
		$this->assertEquals($expected, $translations->toArray());

		$expected = [
			1 => ['First Article' => 'First Article Body'],
			2 => ['Second Article' => 'Second Article Body'],
			3 => ['Third Article' => 'Third Article Body']
		];

		$grouped = $results->combine('title', 'body', 'id');
		$this->assertEquals($expected, $grouped->toArray());
	}

/**
 * Tests that it is possible to request just a few translations
 *
 * @return void
 */
	public function testFindFilteredTranslations() {
		$table = TableRegistry::get('Articles');
		$table->addBehavior('Translate', ['fields' => ['title', 'body']]);
		$results = $table->find('translations', ['locales' => ['deu', 'cze']]);
		$expected = [
			[
				'deu' => ['title' => 'Titel #1', 'body' => 'Inhalt #1', 'locale' => 'deu'],
				'cze' => ['title' => 'Titulek #1', 'body' => 'Obsah #1', 'locale' => 'cze']
			],
			[
				'deu' => ['title' => 'Titel #2', 'body' => 'Inhalt #2', 'locale' => 'deu'],
				'cze' => ['title' => 'Titulek #2', 'body' => 'Obsah #2', 'locale' => 'cze']
			],
			[
				'deu' => ['title' => 'Titel #3', 'body' => 'Inhalt #3', 'locale' => 'deu'],
				'cze' => ['title' => 'Titulek #3', 'body' => 'Obsah #3', 'locale' => 'cze']
			]
		];

		$translations = $results->map(function($row) {
			$translations = $row->get('_translations');
			if (!$translations) {
				return [];
			}
			return array_map(function($t) {
				return $t->toArray();
			}, $translations);
		});
		$this->assertEquals($expected, $translations->toArray());

		$expected = [
			1 => ['First Article' => 'First Article Body'],
			2 => ['Second Article' => 'Second Article Body'],
			3 => ['Third Article' => 'Third Article Body']
		];

		$grouped = $results->combine('title', 'body', 'id');
		$this->assertEquals($expected, $grouped->toArray());
	}

/**
 * Tests that it is possible to combine find('list') and find('translations')
 *
 * @return void
 */
	public function testFindTranslationsList() {
		$table = TableRegistry::get('Articles');
		$table->addBehavior('Translate', ['fields' => ['title', 'body']]);
		$results = $table
			->find('list', [
				'idField' => 'title',
				'valueField' => '_translations.deu.title',
				'groupField' => 'id'
			])
			->find('translations', ['locales' => ['deu']]);

		$expected = [
			1 => ['First Article' => 'Titel #1'],
			2 => ['Second Article' => 'Titel #2'],
			3 => ['Third Article' => 'Titel #3']
		];
		$this->assertEquals($expected, $results->toArray());
	}

}

