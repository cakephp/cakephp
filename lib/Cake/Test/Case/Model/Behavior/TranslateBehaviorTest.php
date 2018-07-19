<?php
/**
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         CakePHP(tm) v 1.2.0.5669
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');

require_once dirname(dirname(__FILE__)) . DS . 'models.php';

/**
 * TranslateBehaviorTest class
 *
 * @package       Cake.Test.Case.Model.Behavior
 */
class TranslateBehaviorTest extends CakeTestCase {

/**
 * autoFixtures property
 *
 * @var bool
 */
	public $autoFixtures = false;

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array(
		'core.translated_item', 'core.translate', 'core.translate_table',
		'core.translated_article', 'core.translate_article', 'core.user', 'core.comment', 'core.tag', 'core.articles_tag',
		'core.translate_with_prefix'
	);

/**
 * Test that count queries with conditions get the correct joins
 *
 * @return void
 */
	public function testCountWithConditions() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$Model = new TranslatedItem();
		$Model->locale = 'eng';
		$result = $Model->find('count', array(
			'conditions' => array(
				'I18n__content.locale' => 'eng'
			)
		));
		$this->assertEquals(3, $result);
	}

/**
 * testTranslateModel method
 *
 * @return void
 */
	public function testTranslateModel() {
		$this->loadFixtures('TranslateTable', 'Tag', 'TranslatedItem', 'Translate', 'User', 'TranslatedArticle', 'TranslateArticle');
		$TestModel = new Tag();
		$TestModel->translateTable = 'another_i18n';
		$TestModel->Behaviors->load('Translate', array('title'));
		$translateModel = $TestModel->Behaviors->Translate->translateModel($TestModel);
		$this->assertEquals('I18nModel', $translateModel->name);
		$this->assertEquals('another_i18n', $translateModel->useTable);

		$TestModel = new User();
		$TestModel->Behaviors->load('Translate', array('title'));
		$translateModel = $TestModel->Behaviors->Translate->translateModel($TestModel);
		$this->assertEquals('I18nModel', $translateModel->name);
		$this->assertEquals('i18n', $translateModel->useTable);

		$TestModel = new TranslatedArticle();
		$translateModel = $TestModel->Behaviors->Translate->translateModel($TestModel);
		$this->assertEquals('TranslateArticleModel', $translateModel->name);
		$this->assertEquals('article_i18n', $translateModel->useTable);

		$TestModel = new TranslatedItem();
		$translateModel = $TestModel->Behaviors->Translate->translateModel($TestModel);
		$this->assertEquals('TranslateTestModel', $translateModel->name);
		$this->assertEquals('i18n', $translateModel->useTable);
	}

/**
 * testLocaleFalsePlain method
 *
 * @return void
 */
	public function testLocaleFalsePlain() {
		$this->loadFixtures('Translate', 'TranslatedItem', 'User');

		$TestModel = new TranslatedItem();
		$TestModel->locale = false;

		$result = $TestModel->read(null, 1);
		$expected = array('TranslatedItem' => array(
			'id' => 1,
			'slug' => 'first_translated',
			'translated_article_id' => 1,
		));
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array('fields' => array('slug')));
		$expected = array(
			array('TranslatedItem' => array('slug' => 'first_translated')),
			array('TranslatedItem' => array('slug' => 'second_translated')),
			array('TranslatedItem' => array('slug' => 'third_translated'))
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testLocaleFalseAssociations method
 *
 * @return void
 */
	public function testLocaleFalseAssociations() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->locale = false;
		$TestModel->unbindTranslation();
		$translations = array('title' => 'Title', 'content' => 'Content');
		$TestModel->bindTranslation($translations, false);

		$result = $TestModel->read(null, 1);
		$expected = array(
			'TranslatedItem' => array('id' => 1, 'slug' => 'first_translated', 'translated_article_id' => 1),
			'Title' => array(
				array('id' => 1, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Title #1'),
				array('id' => 3, 'locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Titel #1'),
				array('id' => 5, 'locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Titulek #1')
			),
			'Content' => array(
				array('id' => 2, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Content #1'),
				array('id' => 4, 'locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Inhalt #1'),
				array('id' => 6, 'locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Obsah #1')
			)
		);
		$this->assertEquals($expected, $result);

		$TestModel->hasMany['Title']['fields'] = $TestModel->hasMany['Content']['fields'] = array('content');
		$TestModel->hasMany['Title']['conditions']['locale'] = $TestModel->hasMany['Content']['conditions']['locale'] = 'eng';

		$result = $TestModel->find('all', array('fields' => array('TranslatedItem.slug')));
		$expected = array(
			array(
				'TranslatedItem' => array('id' => 1, 'slug' => 'first_translated'),
				'Title' => array(array('foreign_key' => 1, 'content' => 'Title #1')),
				'Content' => array(array('foreign_key' => 1, 'content' => 'Content #1'))
			),
			array(
				'TranslatedItem' => array('id' => 2, 'slug' => 'second_translated'),
				'Title' => array(array('foreign_key' => 2, 'content' => 'Title #2')),
				'Content' => array(array('foreign_key' => 2, 'content' => 'Content #2'))
			),
			array(
				'TranslatedItem' => array('id' => 3, 'slug' => 'third_translated'),
				'Title' => array(array('foreign_key' => 3, 'content' => 'Title #3')),
				'Content' => array(array('foreign_key' => 3, 'content' => 'Content #3'))
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testLocaleSingle method
 *
 * @return void
 */
	public function testLocaleSingle() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->locale = 'eng';

		$result = $TestModel->read(null, 1);
		$expected = array(
			'TranslatedItem' => array(
				'id' => 1,
				'slug' => 'first_translated',
				'locale' => 'eng',
				'title' => 'Title #1',
				'content' => 'Content #1',
				'translated_article_id' => 1,
			)
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all');
		$expected = array(
			array(
				'TranslatedItem' => array(
					'id' => 1,
					'slug' => 'first_translated',
					'locale' => 'eng',
					'title' => 'Title #1',
					'content' => 'Content #1',
					'translated_article_id' => 1,
				)
			),
			array(
				'TranslatedItem' => array(
					'id' => 2,
					'slug' => 'second_translated',
					'locale' => 'eng',
					'title' => 'Title #2',
					'content' => 'Content #2',
					'translated_article_id' => 1,
				)
			),
			array(
				'TranslatedItem' => array(
					'id' => 3,
					'slug' => 'third_translated',
					'locale' => 'eng',
					'title' => 'Title #3',
					'content' => 'Content #3',
					'translated_article_id' => 1,
				)
			)
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->field('title', array('TranslatedItem.id' => 1));
		$expected = 'Title #1';
		$this->assertEquals($expected, $result);

		$result = $TestModel->read('title', 1);
		$expected = array(
			'TranslatedItem' => array(
				'id' => 1,
				'slug' => 'first_translated',
				'locale' => 'eng',
				'title' => 'Title #1',
				'translated_article_id' => 1,
			)
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->read('id, title', 1);
		$expected = array(
			'TranslatedItem' => array(
				'id' => 1,
				'locale' => 'eng',
				'title' => 'Title #1',
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testLocaleSingleWithConditions method
 *
 * @return void
 */
	public function testLocaleSingleWithConditions() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->locale = 'eng';
		$result = $TestModel->find('all', array('conditions' => array('slug' => 'first_translated')));
		$expected = array(
			array(
				'TranslatedItem' => array(
					'id' => 1,
					'slug' => 'first_translated',
					'locale' => 'eng',
					'title' => 'Title #1',
					'content' => 'Content #1',
					'translated_article_id' => 1,
				)
			)
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array('conditions' => "TranslatedItem.slug = 'first_translated'"));
		$expected = array(
			array(
				'TranslatedItem' => array(
					'id' => 1,
					'slug' => 'first_translated',
					'locale' => 'eng',
					'title' => 'Title #1',
					'content' => 'Content #1',
					'translated_article_id' => 1,
				)
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testLocaleSingleCountWithConditions method
 *
 * @return void
 */
	public function testLocaleSingleCountWithConditions() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->locale = 'eng';
		$result = $TestModel->find('all', array(
			'conditions' => array('slug' => 'first_translated')
		));
		$expected = array(
			array(
				'TranslatedItem' => array(
					'id' => 1,
					'slug' => 'first_translated',
					'locale' => 'eng',
					'title' => 'Title #1',
					'content' => 'Content #1',
					'translated_article_id' => 1,
				)
			)
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('count', array(
			'conditions' => array('slug' => 'first_translated')
		));
		$expected = 1;
		$this->assertEquals($expected, $result);
	}

/**
 * testLocaleSingleAssociations method
 *
 * @return void
 */
	public function testLocaleSingleAssociations() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->locale = 'eng';
		$TestModel->unbindTranslation();
		$translations = array('title' => 'Title', 'content' => 'Content');
		$TestModel->bindTranslation($translations, false);

		$result = $TestModel->read(null, 1);
		$expected = array(
			'TranslatedItem' => array(
				'id' => 1,
				'slug' => 'first_translated',
				'locale' => 'eng',
				'title' => 'Title #1',
				'content' => 'Content #1',
				'translated_article_id' => 1,
			),
			'Title' => array(
				array('id' => 1, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Title #1'),
				array('id' => 3, 'locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Titel #1'),
				array('id' => 5, 'locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Titulek #1')
			),
			'Content' => array(
				array('id' => 2, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Content #1'),
				array('id' => 4, 'locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Inhalt #1'),
				array('id' => 6, 'locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Obsah #1')
			)
		);
		$this->assertEquals($expected, $result);

		$TestModel->hasMany['Title']['fields'] = $TestModel->hasMany['Content']['fields'] = array('content');
		$TestModel->hasMany['Title']['conditions']['locale'] = $TestModel->hasMany['Content']['conditions']['locale'] = 'eng';

		$result = $TestModel->find('all', array('fields' => array('TranslatedItem.title')));
		$expected = array(
			array(
				'TranslatedItem' => array(
					'id' => 1,
					'locale' => 'eng',
					'title' => 'Title #1',
					'slug' => 'first_translated',
					'translated_article_id' => 1,
				),
				'Title' => array(array('foreign_key' => 1, 'content' => 'Title #1')),
				'Content' => array(array('foreign_key' => 1, 'content' => 'Content #1'))
			),
			array(
				'TranslatedItem' => array(
					'id' => 2,
					'locale' => 'eng',
					'title' => 'Title #2',
					'slug' => 'second_translated',
					'translated_article_id' => 1,
				),
				'Title' => array(array('foreign_key' => 2, 'content' => 'Title #2')),
				'Content' => array(array('foreign_key' => 2, 'content' => 'Content #2'))
			),
			array(
				'TranslatedItem' => array(
					'id' => 3,
					'locale' => 'eng',
					'title' => 'Title #3',
					'slug' => 'third_translated',
					'translated_article_id' => 1,
				),
				'Title' => array(array('foreign_key' => 3, 'content' => 'Title #3')),
				'Content' => array(array('foreign_key' => 3, 'content' => 'Content #3'))
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test loading fields with 0 as the translated value.
 *
 * @return void
 */
	public function testFetchTranslationsWithZero() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$model = new TranslatedItem();
		$translateModel = $model->translateModel();
		$translateModel->updateAll(array('content' => "'0'"));
		$model->locale = 'eng';

		$result = $model->read(null, 1);
		$expected = array(
			'TranslatedItem' => array(
				'id' => 1,
				'slug' => 'first_translated',
				'locale' => 'eng',
				'title' => '0',
				'content' => '0',
				'translated_article_id' => 1,
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testLocaleMultiple method
 *
 * @return void
 */
	public function testLocaleMultiple() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->locale = array('deu', 'eng', 'cze');

		$result = $TestModel->read(null, 1);
		$expected = array(
			'TranslatedItem' => array(
				'id' => 1,
				'slug' => 'first_translated',
				'locale' => 'deu',
				'title' => 'Titel #1',
				'content' => 'Inhalt #1',
				'translated_article_id' => 1,
			)
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array('fields' => array('slug', 'title', 'content')));
		$expected = array(
			array(
				'TranslatedItem' => array(
					'slug' => 'first_translated',
					'locale' => 'deu',
					'content' => 'Inhalt #1',
					'title' => 'Titel #1',
				)
			),
			array(
				'TranslatedItem' => array(
					'slug' => 'second_translated',
					'locale' => 'deu',
					'title' => 'Titel #2',
					'content' => 'Inhalt #2',
				)
			),
			array(
				'TranslatedItem' => array(
					'slug' => 'third_translated',
					'locale' => 'deu',
					'title' => 'Titel #3',
					'content' => 'Inhalt #3',
				)
			)
		);
		$this->assertEquals($expected, $result);

		$TestModel = new TranslatedItem();
		$TestModel->locale = array('pt-br');
		$result = $TestModel->find('all');
		$this->assertCount(3, $result, '3 records should have been found, no SQL error.');
	}

/**
 * testMissingTranslation method
 *
 * @return void
 */
	public function testMissingTranslation() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->locale = 'rus';
		$result = $TestModel->read(null, 1);
		$this->assertSame(array(), $result);

		$TestModel->locale = array('rus');
		$result = $TestModel->read(null, 1);
		$expected = array(
			'TranslatedItem' => array(
				'id' => 1,
				'slug' => 'first_translated',
				'locale' => 'rus',
				'title' => '',
				'content' => '',
				'translated_article_id' => 1,
			)
		);
		$this->assertEquals($expected, $result);
	}

	public function testMissingTranslationLeftJoin() {
		$this->loadFixtures('Translate', 'TranslatedItem');
		$expected = array(
			'TranslatedItem' => Array (
				'id' => '1',
				'translated_article_id' => '1',
				'slug' => 'first_translated',
				'locale' => 'rus',
				'content' => '',
				'title' => '',
			),
		);

		$TestModel = new TranslatedItemLeftJoin();
		$TestModel->locale = 'rus';
		$result = $TestModel->read(null, 1);
		$this->assertEquals($expected, $result);

		$TestModel->locale = array('rus');
		$result = $TestModel->read(null, 1);
		$this->assertEquals($expected, $result);
	}

/**
 * testTranslatedFindList method
 *
 * @return void
 */
	public function testTranslatedFindList() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->locale = 'deu';
		$TestModel->displayField = 'title';
		$result = $TestModel->find('list', array('recursive' => 1));
		$expected = array(1 => 'Titel #1', 2 => 'Titel #2', 3 => 'Titel #3');
		$this->assertEquals($expected, $result);

		// SQL Server trigger an error and stops the page even if the debug = 0
		if ($this->db instanceof Sqlserver) {
			$debug = Configure::read('debug');
			Configure::write('debug', 0);

			$result = $TestModel->find('list', array('recursive' => 1, 'callbacks' => false));
			$this->assertSame(array(), $result);

			$result = $TestModel->find('list', array('recursive' => 1, 'callbacks' => 'after'));
			$this->assertSame(array(), $result);
			Configure::write('debug', $debug);
		}

		$result = $TestModel->find('list', array('recursive' => 1, 'callbacks' => 'before'));
		$expected = array(1 => null, 2 => null, 3 => null);
		$this->assertEquals($expected, $result);
	}

/**
 * testReadSelectedFields method
 *
 * @return void
 */
	public function testReadSelectedFields() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->locale = 'eng';
		$result = $TestModel->find('all', array('fields' => array('slug', 'TranslatedItem.content')));
		$expected = array(
			array('TranslatedItem' => array('slug' => 'first_translated', 'locale' => 'eng', 'content' => 'Content #1')),
			array('TranslatedItem' => array('slug' => 'second_translated', 'locale' => 'eng', 'content' => 'Content #2')),
			array('TranslatedItem' => array('slug' => 'third_translated', 'locale' => 'eng', 'content' => 'Content #3'))
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array('fields' => array('TranslatedItem.slug', 'content')));
		$this->assertEquals($expected, $result);

		$TestModel->locale = array('eng', 'deu', 'cze');
		$delete = array(array('locale' => 'deu'), array('field' => 'content', 'locale' => 'eng'));
		$I18nModel = ClassRegistry::getObject('TranslateTestModel');
		$I18nModel->deleteAll(array('or' => $delete));

		$result = $TestModel->find('all', array('fields' => array('title', 'content')));
		$expected = array(
			array('TranslatedItem' => array('locale' => 'eng', 'title' => 'Title #1', 'content' => 'Obsah #1')),
			array('TranslatedItem' => array('locale' => 'eng', 'title' => 'Title #2', 'content' => 'Obsah #2')),
			array('TranslatedItem' => array('locale' => 'eng', 'title' => 'Title #3', 'content' => 'Obsah #3'))
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveCreate method
 *
 * @return void
 */
	public function testSaveCreate() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->locale = 'spa';
		$data = array(
			'slug' => 'fourth_translated',
			'title' => 'Leyenda #4',
			'content' => 'Contenido #4',
			'translated_article_id' => 1,
		);
		$TestModel->create($data);
		$TestModel->save();
		$result = $TestModel->read();
		$expected = array('TranslatedItem' => array_merge($data, array('id' => $TestModel->id, 'locale' => 'spa')));
		$this->assertEquals($expected, $result);
	}

/**
 * test saving/deleting with an alias, uses the model name.
 *
 * @return void
 */
	public function testSaveDeleteIgnoreAlias() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem(array('alias' => 'SomethingElse'));
		$TestModel->locale = 'spa';
		$data = array(
			'slug' => 'fourth_translated',
			'title' => 'Leyenda #4',
			'content' => 'Contenido #4',
			'translated_article_id' => 1,
		);
		$TestModel->create($data);
		$TestModel->save();
		$id = $TestModel->id;
		$result = $TestModel->read();
		$expected = array($TestModel->alias => array_merge($data, array('id' => $id, 'locale' => 'spa')));
		$this->assertEquals($expected, $result);

		$TestModel->delete($id);
		$result = $TestModel->translateModel()->find('count', array(
			'conditions' => array('foreign_key' => $id)
		));
		$this->assertEquals(0, $result);
	}

/**
 * test save multiple locales method
 *
 * @return void
 */
	public function testSaveMultipleLocales() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$data = array(
			'slug' => 'fourth_translated',
			'title' => array(
				'eng' => 'Title #4',
				'spa' => 'Leyenda #4',
			),
			'content' => array(
				'eng' => 'Content #4',
				'spa' => 'Contenido #4',
			),
			'translated_article_id' => 1,
		);
		$TestModel->create();
		$TestModel->save($data);

		$translations = array('title' => 'Title', 'content' => 'Content');
		$TestModel->bindTranslation($translations, false);
		$TestModel->locale = array('eng', 'spa');
		$result = $TestModel->read();

		$this->assertCount(2, $result['Title']);
		$this->assertEquals($result['Title'][0]['locale'], 'eng');
		$this->assertEquals($result['Title'][0]['content'], 'Title #4');
		$this->assertEquals($result['Title'][1]['locale'], 'spa');
		$this->assertEquals($result['Title'][1]['content'], 'Leyenda #4');

		$this->assertCount(2, $result['Content']);
	}

/**
 * testSaveAssociatedCreate method
 *
 * @return void
 */
	public function testSaveAssociatedMultipleLocale() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$data = array(
			'slug' => 'fourth_translated',
			'title' => array(
				'eng' => 'Title #4',
				'spa' => 'Leyenda #4',
			),
			'content' => array(
				'eng' => 'Content #4',
				'spa' => 'Contenido #4',
			),
			'translated_article_id' => 1,
		);
		$TestModel->create();
		$TestModel->saveAssociated($data);

		$translations = array('title' => 'Title', 'content' => 'Content');
		$TestModel->bindTranslation($translations, false);
		$TestModel->locale = array('eng', 'spa');
		$result = $TestModel->read();
		$this->assertCount(2, $result['Title']);
		$this->assertCount(2, $result['Content']);
	}

/**
 * testSaveAssociatedAtomic method
 *
 * @return void
 */
	public function testSaveAssociatedAtomic() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$data = array(
			'slug' => 'fourth_translated',
			'title' => array(
				'eng' => 'Title #4'
			),
			'content' => array(
				'eng' => 'Content #4'
			),
			'translated_article_id' => 1,
		);
		$Mock = $this->getMockForModel('TranslateTestModel', array('save'));
		$TestModel->Behaviors->Translate->runtime[$TestModel->alias]['model'] = $Mock;

		$with = array(
			'TranslateTestModel' => array (
				'model' => 'TranslatedItem',
				'foreign_key' => '4',
				'field' => 'content',
				'locale' => 'eng',
				'content' => 'Content #4',
			)
		);
		$Mock->expects($this->at(0))->method('save')->with($with, array('atomic' => false));

		$with = array(
			'TranslateTestModel' => array (
				'model' => 'TranslatedItem',
				'foreign_key' => '4',
				'field' => 'title',
				'locale' => 'eng',
				'content' => 'Title #4',
			)
		);
		$Mock->expects($this->at(1))->method('save')->with($with, array('atomic' => false));

		$TestModel->create();
		$TestModel->saveAssociated($data, array('atomic' => false));
	}

/**
 * Test that saving only some of the translated fields allows the record to be found again.
 *
 * @return void
 */
	public function testSavePartialFields() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->locale = 'spa';
		$data = array(
			'slug' => 'fourth_translated',
			'title' => 'Leyenda #4',
		);
		$TestModel->create($data);
		$TestModel->save();
		$result = $TestModel->read();
		$expected = array(
			'TranslatedItem' => array(
				'id' => $TestModel->id,
				'translated_article_id' => null,
				'locale' => 'spa',
				'content' => '',
			) + $data
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that all fields are create with partial data + multiple locales.
 *
 * @return void
 */
	public function testSavePartialFieldMultipleLocales() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->locale = 'eng';
		$data = array(
			'slug' => 'fifth_translated',
			'title' => array('eng' => 'Title #5', 'spa' => 'Leyenda #5'),
		);
		$TestModel->create($data);
		$TestModel->save();
		$TestModel->unbindTranslation();

		$translations = array('title' => 'Title', 'content' => 'Content');
		$TestModel->bindTranslation($translations, false);
		$result = $TestModel->read(null, $TestModel->id);
		$expected = array(
			'TranslatedItem' => array(
				'id' => '4',
				'translated_article_id' => null,
				'slug' => 'fifth_translated',
				'locale' => 'eng',
				'title' => 'Title #5',
				'content' => ''
			),
			'Title' => array(
				0 => array(
					'id' => '19',
					'locale' => 'eng',
					'model' => 'TranslatedItem',
					'foreign_key' => '4',
					'field' => 'title',
					'content' => 'Title #5'
				),
				1 => array(
					'id' => '20',
					'locale' => 'spa',
					'model' => 'TranslatedItem',
					'foreign_key' => '4',
					'field' => 'title',
					'content' => 'Leyenda #5'
				)
			),
			'Content' => array(
				0 => array(
					'id' => '21',
					'locale' => 'eng',
					'model' => 'TranslatedItem',
					'foreign_key' => '4',
					'field' => 'content',
					'content' => ''
				),
				1 => array(
					'id' => '22',
					'locale' => 'spa',
					'model' => 'TranslatedItem',
					'foreign_key' => '4',
					'field' => 'content',
					'content' => ''
				)
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveUpdate method
 *
 * @return void
 */
	public function testSaveUpdate() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->locale = 'spa';
		$oldData = array('slug' => 'fourth_translated', 'title' => 'Leyenda #4', 'translated_article_id' => 1);
		$TestModel->create($oldData);
		$TestModel->save();
		$id = $TestModel->id;
		$newData = array('id' => $id, 'content' => 'Contenido #4');
		$TestModel->create($newData);
		$TestModel->save();
		$result = $TestModel->read(null, $id);
		$expected = array('TranslatedItem' => array_merge($oldData, $newData, array('locale' => 'spa')));
		$this->assertEquals($expected, $result);
	}

/**
 * testMultipleCreate method
 *
 * @return void
 */
	public function testMultipleCreate() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->locale = 'deu';
		$data = array(
			'slug' => 'new_translated',
			'title' => array('eng' => 'New title', 'spa' => 'Nuevo leyenda'),
			'content' => array('eng' => 'New content', 'spa' => 'Nuevo contenido')
		);
		$TestModel->create($data);
		$TestModel->save();

		$TestModel->unbindTranslation();
		$translations = array('title' => 'Title', 'content' => 'Content');
		$TestModel->bindTranslation($translations, false);
		$TestModel->locale = array('eng', 'spa');

		$result = $TestModel->read();
		$expected = array(
			'TranslatedItem' => array(
				'id' => 4,
				'slug' => 'new_translated',
				'locale' => 'eng',
				'title' => 'New title',
				'content' => 'New content',
				'translated_article_id' => null,
			),
			'Title' => array(
				array('id' => 21, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 4, 'field' => 'title', 'content' => 'New title'),
				array('id' => 22, 'locale' => 'spa', 'model' => 'TranslatedItem', 'foreign_key' => 4, 'field' => 'title', 'content' => 'Nuevo leyenda')
			),
			'Content' => array(
				array('id' => 19, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 4, 'field' => 'content', 'content' => 'New content'),
				array('id' => 20, 'locale' => 'spa', 'model' => 'TranslatedItem', 'foreign_key' => 4, 'field' => 'content', 'content' => 'Nuevo contenido')
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testMultipleUpdate method
 *
 * @return void
 */
	public function testMultipleUpdate() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->locale = 'eng';
		$TestModel->validate['title'] = 'notBlank';
		$data = array('TranslatedItem' => array(
			'id' => 1,
			'title' => array('eng' => 'New Title #1', 'deu' => 'Neue Titel #1', 'cze' => 'Novy Titulek #1'),
			'content' => array('eng' => 'New Content #1', 'deu' => 'Neue Inhalt #1', 'cze' => 'Novy Obsah #1')
		));
		$TestModel->create();
		$TestModel->save($data);

		$TestModel->unbindTranslation();
		$translations = array('title' => 'Title', 'content' => 'Content');
		$TestModel->bindTranslation($translations, false);
		$result = $TestModel->read(null, 1);
		$expected = array(
			'TranslatedItem' => array(
				'id' => '1',
				'slug' => 'first_translated',
				'locale' => 'eng',
				'title' => 'New Title #1',
				'content' => 'New Content #1',
				'translated_article_id' => 1,
			),
			'Title' => array(
				array('id' => 1, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'New Title #1'),
				array('id' => 3, 'locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Neue Titel #1'),
				array('id' => 5, 'locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Novy Titulek #1')
			),
			'Content' => array(
				array('id' => 2, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'New Content #1'),
				array('id' => 4, 'locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Neue Inhalt #1'),
				array('id' => 6, 'locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Novy Obsah #1')
			)
		);
		$this->assertEquals($expected, $result);

		$TestModel->unbindTranslation($translations);
		$TestModel->bindTranslation(array('title', 'content'), false);
	}

/**
 * testMixedCreateUpdateWithArrayLocale method
 *
 * @return void
 */
	public function testMixedCreateUpdateWithArrayLocale() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->locale = array('cze', 'deu');
		$data = array('TranslatedItem' => array(
			'id' => 1,
			'title' => array('eng' => 'Updated Title #1', 'spa' => 'Nuevo leyenda #1'),
			'content' => 'Upraveny obsah #1'
		));
		$TestModel->create();
		$TestModel->save($data);

		$TestModel->unbindTranslation();
		$translations = array('title' => 'Title', 'content' => 'Content');
		$TestModel->bindTranslation($translations, false);
		$result = $TestModel->read(null, 1);
		$result['Title'] = Hash::sort($result['Title'], '{n}.id', 'asc');
		$result['Content'] = Hash::sort($result['Content'], '{n}.id', 'asc');
		$expected = array(
			'TranslatedItem' => array(
				'id' => 1,
				'slug' => 'first_translated',
				'locale' => 'cze',
				'title' => 'Titulek #1',
				'content' => 'Upraveny obsah #1',
				'translated_article_id' => 1,
			),
			'Title' => array(
				array('id' => 1, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Updated Title #1'),
				array('id' => 3, 'locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Titel #1'),
				array('id' => 5, 'locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Titulek #1'),
				array('id' => 19, 'locale' => 'spa', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Nuevo leyenda #1')
			),
			'Content' => array(
				array('id' => 2, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Content #1'),
				array('id' => 4, 'locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Inhalt #1'),
				array('id' => 6, 'locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Upraveny obsah #1')
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that saveAll() works with hasMany associations that contain
 * translations.
 *
 * @return void
 */
	public function testSaveAllTranslatedAssociations() {
		$this->loadFixtures('Translate', 'TranslateArticle', 'TranslatedItem', 'TranslatedArticle', 'User');
		$Model = new TranslatedArticle();
		$Model->locale = 'eng';

		$data = array(
			'TranslatedArticle' => array(
				'id' => 4,
				'user_id' => 1,
				'published' => 'Y',
				'title' => 'Title (eng) #1',
				'body' => 'Body (eng) #1'
			),
			'TranslatedItem' => array(
				array(
					'slug' => '',
					'title' => 'Nuevo leyenda #1',
					'content' => 'Upraveny obsah #1'
				),
				array(
					'slug' => '',
					'title' => 'New Title #2',
					'content' => 'New Content #2'
				),
			)
		);
		$result = $Model->saveAll($data);
		$this->assertTrue($result);

		$result = $Model->TranslatedItem->find('all', array(
			'conditions' => array('translated_article_id' => $Model->id)
		));
		$this->assertCount(2, $result);
		$this->assertEquals($data['TranslatedItem'][0]['title'], $result[0]['TranslatedItem']['title']);
		$this->assertEquals($data['TranslatedItem'][1]['title'], $result[1]['TranslatedItem']['title']);
	}

/**
 * testValidation method
 *
 * @return void
 */
	public function testValidation() {
		Configure::write('Config.language', 'eng');
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->locale = 'eng';
		$TestModel->validate['title'] = '/Only this title/';
		$data = array(
			'TranslatedItem' => array(
				'id' => 1,
				'title' => array('eng' => 'New Title #1', 'deu' => 'Neue Titel #1', 'cze' => 'Novy Titulek #1'),
				'content' => array('eng' => 'New Content #1', 'deu' => 'Neue Inhalt #1', 'cze' => 'Novy Obsah #1')
			)
		);
		$TestModel->create();
		$this->assertFalse($TestModel->save($data));
		$this->assertEquals(array('This field cannot be left blank'), $TestModel->validationErrors['title']);

		$TestModel->locale = 'eng';
		$TestModel->validate['title'] = '/Only this title/';
		$data = array('TranslatedItem' => array(
			'id' => 1,
			'title' => array('eng' => 'Only this title', 'deu' => 'Neue Titel #1', 'cze' => 'Novy Titulek #1'),
			'content' => array('eng' => 'New Content #1', 'deu' => 'Neue Inhalt #1', 'cze' => 'Novy Obsah #1')
		));
		$TestModel->create();
		$result = $TestModel->save($data);
		$this->assertFalse(empty($result));
	}

/**
 * test restoring fields after temporary binds method
 *
 * @return void
 */
	public function testFieldsRestoreAfterBind() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();

		$translations = array('title' => 'Title');
		$TestModel->bindTranslation($translations);

		$result = $TestModel->find('first');
		$TestModel->find('first', array(
			'fields' => array(
				'TranslatedItem.title',
			),
		));
		$TestModel->find('first', array(
			'fields' => array(
				'TranslatedItem.title',
			),
		));
		$this->assertArrayHasKey('Title', $result);
		$this->assertArrayHasKey('content', $result['Title'][0]);
		$this->assertArrayNotHasKey('title', $result);

		$result = $TestModel->find('first');
		$this->assertArrayNotHasKey('Title', $result);
		$this->assertEquals('Title #1', $result['TranslatedItem']['title']);
	}

/**
 * testAttachDetach method
 *
 * @return void
 */
	public function testAttachDetach() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();

		$TestModel->unbindTranslation();
		$translations = array('title' => 'Title', 'content' => 'Content');
		$TestModel->bindTranslation($translations, false);

		$result = array_keys($TestModel->hasMany);
		$expected = array('Title', 'Content');
		$this->assertEquals($expected, $result);

		$TestModel->Behaviors->unload('Translate');
		$result = array_keys($TestModel->hasMany);
		$expected = array();
		$this->assertEquals($expected, $result);

		$result = isset($TestModel->Behaviors->Translate);
		$this->assertFalse($result);

		$result = isset($Behavior->settings[$TestModel->alias]);
		$this->assertFalse($result);

		$result = isset($Behavior->runtime[$TestModel->alias]);
		$this->assertFalse($result);

		$TestModel->Behaviors->load('Translate', array('title' => 'Title', 'content' => 'Content'));
		$result = array_keys($TestModel->hasMany);
		$expected = array('Title', 'Content');
		$this->assertEquals($expected, $result);

		$result = isset($TestModel->Behaviors->Translate);
		$this->assertTrue($result);

		$Behavior = $TestModel->Behaviors->Translate;

		$result = isset($Behavior->settings[$TestModel->alias]);
		$this->assertTrue($result);

		$result = isset($Behavior->runtime[$TestModel->alias]);
		$this->assertTrue($result);
	}

/**
 * testAnotherTranslateTable method
 *
 * @return void
 */
	public function testAnotherTranslateTable() {
		$this->loadFixtures('Translate', 'TranslatedItem', 'TranslateTable');

		$TestModel = new TranslatedItemWithTable();
		$TestModel->locale = 'eng';
		$result = $TestModel->read(null, 1);
		$expected = array(
			'TranslatedItemWithTable' => array(
				'id' => 1,
				'slug' => 'first_translated',
				'locale' => 'eng',
				'title' => 'Another Title #1',
				'content' => 'Another Content #1',
				'translated_article_id' => 1,
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testTranslateWithAssociations method
 *
 * @return void
 */
	public function testTranslateWithAssociations() {
		$this->loadFixtures('TranslateArticle', 'TranslatedArticle', 'TranslatedItem', 'User', 'Comment', 'ArticlesTag', 'Tag');

		$TestModel = new TranslatedArticle();
		$TestModel->locale = 'eng';
		$recursive = $TestModel->recursive;

		$result = $TestModel->read(null, 1);
		$expected = array(
			'TranslatedArticle' => array(
				'id' => 1,
				'user_id' => 1,
				'published' => 'Y',
				'created' => '2007-03-18 10:39:23',
				'updated' => '2007-03-18 10:41:31',
				'locale' => 'eng',
				'title' => 'Title (eng) #1',
				'body' => 'Body (eng) #1'
			),
			'User' => array(
				'id' => 1,
				'user' => 'mariano',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				'created' => '2007-03-17 01:16:23',
				'updated' => '2007-03-17 01:18:31'
			),
			'TranslatedItem' => array(
				array(
					'id' => 1,
					'translated_article_id' => 1,
					'slug' => 'first_translated'
				),
				array(
					'id' => 2,
					'translated_article_id' => 1,
					'slug' => 'second_translated'
				),
				array(
					'id' => 3,
					'translated_article_id' => 1,
					'slug' => 'third_translated'
				),
			)
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->find('all', array('recursive' => -1));
		$expected = array(
			array(
				'TranslatedArticle' => array(
					'id' => 1,
					'user_id' => 1,
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31',
					'locale' => 'eng',
					'title' => 'Title (eng) #1',
					'body' => 'Body (eng) #1'
				)
			),
			array(
				'TranslatedArticle' => array(
					'id' => 2,
					'user_id' => 3,
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31',
					'locale' => 'eng',
					'title' => 'Title (eng) #2',
					'body' => 'Body (eng) #2'
				)
			),
			array(
				'TranslatedArticle' => array(
					'id' => 3,
					'user_id' => 1,
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31',
					'locale' => 'eng',
					'title' => 'Title (eng) #3',
					'body' => 'Body (eng) #3'
				)
			)
		);
		$this->assertEquals($expected, $result);
		$this->assertEquals($TestModel->recursive, $recursive);

		$TestModel->recursive = -1;
		$result = $TestModel->read(null, 1);
		$expected = array(
			'TranslatedArticle' => array(
				'id' => 1,
				'user_id' => 1,
				'published' => 'Y',
				'created' => '2007-03-18 10:39:23',
				'updated' => '2007-03-18 10:41:31',
				'locale' => 'eng',
				'title' => 'Title (eng) #1',
				'body' => 'Body (eng) #1'
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testTranslateTableWithPrefix method
 * Tests that is possible to have a translation model with a custom tablePrefix
 *
 * @return void
 */
	public function testTranslateTableWithPrefix() {
		$this->loadFixtures('TranslateWithPrefix', 'TranslatedItem');
		$TestModel = new TranslatedItem2;
		$TestModel->locale = 'eng';
		$result = $TestModel->read(null, 1);
		$expected = array('TranslatedItem' => array(
			'id' => 1,
			'slug' => 'first_translated',
			'locale' => 'eng',
			'content' => 'Content #1',
			'title' => 'Title #1',
			'translated_article_id' => 1,
		));
		$this->assertEquals($expected, $result);
	}

/**
 * Test infinite loops not occurring with unbindTranslation()
 *
 * @return void
 */
	public function testUnbindTranslationInfinteLoop() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel = new TranslatedItem();
		$TestModel->Behaviors->unload('Translate');
		$TestModel->actsAs = array();
		$TestModel->Behaviors->load('Translate');
		$TestModel->bindTranslation(array('title', 'content'), true);
		$result = $TestModel->unbindTranslation();

		$this->assertFalse($result);
	}

/**
 * Test that an exception is raised when you try to over-write the name attribute.
 *
 * @expectedException CakeException
 * @return void
 */
	public function testExceptionOnNameTranslation() {
		$this->loadFixtures('Translate', 'TranslatedItem');
		$TestModel = new TranslatedItem();
		$TestModel->bindTranslation(array('name' => 'name'));
	}

/**
 * Test that translations can be bound and unbound dynamically.
 *
 * @return void
 */
	public function testUnbindTranslation() {
		$this->loadFixtures('Translate', 'TranslatedItem');
		$Model = new TranslatedItem();
		$Model->unbindTranslation();
		$Model->bindTranslation(array('body', 'slug'), false);

		$result = $Model->Behaviors->Translate->settings['TranslatedItem'];
		$this->assertEquals(array('body', 'slug'), $result);

		$Model->unbindTranslation(array('body'));
		$result = $Model->Behaviors->Translate->settings['TranslatedItem'];
		$this->assertNotContains('body', $result);

		$Model->unbindTranslation('slug');
		$result = $Model->Behaviors->Translate->settings['TranslatedItem'];
		$this->assertNotContains('slug', $result);
	}

/**
 * Test that additional records are not inserted for associated translations.
 *
 * @return void
 */
	public function testNoExtraRowsForAssociatedTranslations() {
		$this->loadFixtures('Translate', 'TranslatedItem');
		$TestModel = new TranslatedItem();
		$TestModel->locale = 'spa';
		$TestModel->unbindTranslation();
		$TestModel->bindTranslation(array('name' => 'nameTranslate'));

		$data = array(
			'TranslatedItem' => array(
				'slug' => 'spanish-name',
				'name' => 'Spanish name',
			),
		);
		$TestModel->create($data);
		$TestModel->save();

		$Translate = $TestModel->translateModel();
		$results = $Translate->find('all', array(
			'conditions' => array(
				'locale' => $TestModel->locale,
				'foreign_key' => $TestModel->id
			)
		));
		$this->assertCount(1, $results, 'Only one field should be saved');
		$this->assertEquals('name', $results[0]['TranslateTestModel']['field']);
	}

	public function testBeforeFindAllI18nConditions() {
		$this->skipIf(!$this->db instanceof Mysql, 'This test is only compatible with Mysql.');
		$this->loadFixtures('TranslateArticle', 'TranslatedArticle', 'User');
		$TestModel = new TranslatedArticle();
		$TestModel->cacheQueries = false;
		$TestModel->locale = 'eng';
		$expected = array(
			'conditions' => array(
				'NOT' => array('I18n__title.content' => ''),
			),
			'fields' => null,
			'joins' => array(
				array(
					'type' => 'INNER',
					'alias' => 'I18n__title',
					'table' => (object)array(
						'tablePrefix' => '',
						'table' => 'article_i18n',
						'schemaName' => 'cakephp_test',
					),
					'conditions' => array(
						'TranslatedArticle.id' => (object)array(
							'type' => 'identifier',
							'value' => 'I18n__title.foreign_key',
						),
						'I18n__title.model' => 'TranslatedArticle',
						'I18n__title.field' => 'title',
						'I18n__title.locale' => 'eng',
					),
				),
				array(
					'type' => 'INNER',
					'alias' => 'I18n__body',
					'table' => (object)array(
						'tablePrefix' => '',
						'table' => 'article_i18n',
						'schemaName' => 'cakephp_test',
					),
					'conditions' => array(
						'TranslatedArticle.id' => (object)array(
							'type' => 'identifier',
							'value' => 'I18n__body.foreign_key',
						),
						'I18n__body.model' => 'TranslatedArticle',
						'I18n__body.field' => 'body',
						'I18n__body.locale' => 'eng',
					),
				),
			),
			'limit' => 2,
			'offset' => null,
			'order' => array(
				'TranslatedArticle.id' => 'ASC',
			),
			'page' => 1,
			'group' => null,
			'callbacks' => true,
			'recursive' => 0,
		);
		$query = array(
			'conditions' => array(
				'NOT' => array(
					'I18n__title.content' => '',
				),
			),
			'fields' => null,
			'joins' => array(),
			'limit' => 2,
			'offset' => null,
			'order' => array(
				'TranslatedArticle.id' => 'ASC',
			),
			'page' => 1,
			'group' => null,
			'callbacks' => true,
			'recursive' => 0,
		);
		$TranslateBehavior = ClassRegistry::getObject('TranslateBehavior');
		$result = $TranslateBehavior->beforeFind($TestModel, $query);
		$this->assertEquals($expected, $result);
	}

	public function testBeforeFindCountI18nConditions() {
		$this->skipIf(!$this->db instanceof Mysql, 'This test is only compatible with Mysql.');
		$this->loadFixtures('TranslateArticle', 'TranslatedArticle', 'User');
		$TestModel = new TranslatedArticle();
		$TestModel->cacheQueries = false;
		$TestModel->locale = 'eng';
		$expected = array(
			'conditions' => array(
				'NOT' => array('I18n__title.content' => ''),
			),
			'fields' => 'COUNT(DISTINCT(`TranslatedArticle`.`id`)) AS count',
			'joins' => array(
				array(
					'type' => 'INNER',
					'alias' => 'TranslateArticleModel',
					'table' => (object)array(
						'tablePrefix' => '',
						'table' => 'article_i18n',
						'schemaName' => 'cakephp_test',
					),
					'conditions' => array(
						'`TranslatedArticle`.`id`' => (object)array(
							'type' => 'identifier',
							'value' => '`TranslateArticleModel`.`foreign_key`',
						),
						'`TranslateArticleModel`.`model`' => 'TranslatedArticle',
						'`TranslateArticleModel`.`locale`' => 'eng',
					),
				),
				array(
					'type' => 'INNER',
					'alias' => 'I18n__title',
					'table' => (object)array(
						'tablePrefix' => '',
						'table' => 'article_i18n',
						'schemaName' => 'cakephp_test',
					),
					'conditions' => array(
						'TranslatedArticle.id' => (object)array(
							'type' => 'identifier',
							'value' => 'I18n__title.foreign_key',
						),
						'I18n__title.model' => 'TranslatedArticle',
						'I18n__title.field' => 'title',
						'I18n__title.locale' => 'eng',
					),
				),
			),
			'limit' => 2,
			'offset' => null,
			'order' => array(
				0 => false,
			),
			'page' => 1,
			'group' => null,
			'callbacks' => true,
			'recursive' => 0,
		);
		$query = array(
			'conditions' => array(
				'NOT' => array(
					'I18n__title.content' => '',
				)
			),
			'fields' => 'COUNT(*) AS `count`',
			'joins' => array(),
			'limit' => 2,
			'offset' => null,
			'order' => array(
				0 => false
			),
			'page' => 1,
			'group' => null,
			'callbacks' => true,
			'recursive' => 0,
		);
		$TranslateBehavior = ClassRegistry::getObject('TranslateBehavior');
		$result = $TranslateBehavior->beforeFind($TestModel, $query);
		$this->assertEquals($expected, $result);
	}
}
