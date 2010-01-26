<?php
/**
 * TranslateBehaviorTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.behaviors
 * @since         CakePHP(tm) v 1.2.0.5669
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}

App::import('Core', array('AppModel', 'Model'));
require_once(dirname(dirname(__FILE__)) . DS . 'models.php');

/**
 * TranslateBehaviorTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.behaviors
 */
class TranslateBehaviorTest extends CakeTestCase {

/**
 * autoFixtures property
 *
 * @var bool false
 * @access public
 */
	var $autoFixtures = false;

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	var $fixtures = array(
		'core.translated_item', 'core.translate', 'core.translate_table',
		'core.translated_article', 'core.translate_article', 'core.user', 'core.comment', 'core.tag', 'core.articles_tag',
		'core.translate_with_prefix'
	);

/**
 * endTest method
 *
 * @access public
 * @return void
 */
	function endTest() {
		ClassRegistry::flush();
	}

/**
 * testTranslateModel method
 *
 * @access public
 * @return void
 */
	function testTranslateModel() {
		$TestModel =& new Tag();
		$TestModel->translateTable = 'another_i18n';
		$TestModel->Behaviors->attach('Translate', array('title'));
		$translateModel =& $TestModel->Behaviors->Translate->translateModel($TestModel);
		$this->assertEqual($translateModel->name, 'I18nModel');
		$this->assertEqual($translateModel->useTable, 'another_i18n');

		$TestModel =& new User();
		$TestModel->Behaviors->attach('Translate', array('title'));
		$translateModel =& $TestModel->Behaviors->Translate->translateModel($TestModel);
		$this->assertEqual($translateModel->name, 'I18nModel');
		$this->assertEqual($translateModel->useTable, 'i18n');

		$TestModel =& new TranslatedArticle();
		$translateModel =& $TestModel->Behaviors->Translate->translateModel($TestModel);
		$this->assertEqual($translateModel->name, 'TranslateArticleModel');
		$this->assertEqual($translateModel->useTable, 'article_i18n');

		$TestModel =& new TranslatedItem();
		$translateModel =& $TestModel->Behaviors->Translate->translateModel($TestModel);
		$this->assertEqual($translateModel->name, 'TranslateTestModel');
		$this->assertEqual($translateModel->useTable, 'i18n');
	}

/**
 * testLocaleFalsePlain method
 *
 * @access public
 * @return void
 */
	function testLocaleFalsePlain() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel =& new TranslatedItem();
		$TestModel->locale = false;

		$result = $TestModel->read(null, 1);
		$expected = array('TranslatedItem' => array('id' => 1, 'slug' => 'first_translated'));
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all', array('fields' => array('slug')));
		$expected = array(
			array('TranslatedItem' => array('slug' => 'first_translated')),
			array('TranslatedItem' => array('slug' => 'second_translated')),
			array('TranslatedItem' => array('slug' => 'third_translated'))
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testLocaleFalseAssociations method
 *
 * @access public
 * @return void
 */
	function testLocaleFalseAssociations() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel =& new TranslatedItem();
		$TestModel->locale = false;
		$TestModel->unbindTranslation();
		$translations = array('title' => 'Title', 'content' => 'Content');
		$TestModel->bindTranslation($translations, false);

		$result = $TestModel->read(null, 1);
		$expected = array(
			'TranslatedItem' => array('id' => 1, 'slug' => 'first_translated'),
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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);
	}

/**
 * testLocaleSingle method
 *
 * @access public
 * @return void
 */
	function testLocaleSingle() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel =& new TranslatedItem();
		$TestModel->locale = 'eng';
		$result = $TestModel->read(null, 1);
		$expected = array(
			'TranslatedItem' => array(
				'id' => 1,
				'slug' => 'first_translated',
				'locale' => 'eng',
				'title' => 'Title #1',
				'content' => 'Content #1'
			)
		);
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all');
		$expected = array(
			array(
				'TranslatedItem' => array(
					'id' => 1,
					'slug' => 'first_translated',
					'locale' => 'eng',
					'title' => 'Title #1',
					'content' => 'Content #1'
				)
			),
			array(
				'TranslatedItem' => array(
					'id' => 2,
					'slug' => 'second_translated',
					'locale' => 'eng',
					'title' => 'Title #2',
					'content' => 'Content #2'
				)
			),
			array(
				'TranslatedItem' => array(
					'id' => 3,
					'slug' => 'third_translated',
					'locale' => 'eng',
					'title' => 'Title #3',
					'content' => 'Content #3'
				)
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testLocaleSingleWithConditions method
 *
 * @access public
 * @return void
 */
	function testLocaleSingleWithConditions() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel =& new TranslatedItem();
		$TestModel->locale = 'eng';
		$result = $TestModel->find('all', array('conditions' => array('slug' => 'first_translated')));
		$expected = array(
			array(
				'TranslatedItem' => array(
					'id' => 1,
					'slug' => 'first_translated',
					'locale' => 'eng',
					'title' => 'Title #1',
					'content' => 'Content #1'
				)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all', array('conditions' => "TranslatedItem.slug = 'first_translated'"));
		$expected = array(
			array(
				'TranslatedItem' => array(
					'id' => 1,
					'slug' => 'first_translated',
					'locale' => 'eng',
					'title' => 'Title #1',
					'content' => 'Content #1'
				)
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testLocaleSingleAssociations method
 *
 * @access public
 * @return void
 */
	function testLocaleSingleAssociations() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel =& new TranslatedItem();
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
				'content' => 'Content #1'
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
		$this->assertEqual($result, $expected);

		$TestModel->hasMany['Title']['fields'] = $TestModel->hasMany['Content']['fields'] = array('content');
		$TestModel->hasMany['Title']['conditions']['locale'] = $TestModel->hasMany['Content']['conditions']['locale'] = 'eng';

		$result = $TestModel->find('all', array('fields' => array('TranslatedItem.title')));
		$expected = array(
			array(
				'TranslatedItem' => array('id' => 1, 'locale' => 'eng', 'title' => 'Title #1'),
				'Title' => array(array('foreign_key' => 1, 'content' => 'Title #1')),
				'Content' => array(array('foreign_key' => 1, 'content' => 'Content #1'))
			),
			array(
				'TranslatedItem' => array('id' => 2, 'locale' => 'eng', 'title' => 'Title #2'),
				'Title' => array(array('foreign_key' => 2, 'content' => 'Title #2')),
				'Content' => array(array('foreign_key' => 2, 'content' => 'Content #2'))
			),
			array(
				'TranslatedItem' => array('id' => 3, 'locale' => 'eng', 'title' => 'Title #3'),
				'Title' => array(array('foreign_key' => 3, 'content' => 'Title #3')),
				'Content' => array(array('foreign_key' => 3, 'content' => 'Content #3'))
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testLocaleMultiple method
 *
 * @access public
 * @return void
 */
	function testLocaleMultiple() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel =& new TranslatedItem();
		$TestModel->locale = array('deu', 'eng', 'cze');
		$delete = array(
			array('locale' => 'deu'),
			array('foreign_key' => 1, 'field' => 'title', 'locale' => 'eng'),
			array('foreign_key' => 1, 'field' => 'content', 'locale' => 'cze'),
			array('foreign_key' => 2, 'field' => 'title', 'locale' => 'cze'),
			array('foreign_key' => 2, 'field' => 'content', 'locale' => 'eng'),
			array('foreign_key' => 3, 'field' => 'title')
		);
		$I18nModel =& ClassRegistry::getObject('TranslateTestModel');
		$I18nModel->deleteAll(array('or' => $delete));

		$result = $TestModel->read(null, 1);
		$expected = array(
			'TranslatedItem' => array(
				'id' => 1,
				'slug' => 'first_translated',
				'locale' => 'deu',
				'title' => 'Titulek #1',
				'content' => 'Content #1'
			)
		);
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all', array('fields' => array('slug', 'title', 'content')));
		$expected = array(
			array(
				'TranslatedItem' => array(
					'slug' => 'first_translated',
					'locale' => 'deu',
					'title' => 'Titulek #1',
					'content' => 'Content #1'
				)
			),
			array(
				'TranslatedItem' => array(
					'slug' => 'second_translated',
					'locale' => 'deu',
					'title' => 'Title #2',
					'content' => 'Obsah #2'
				)
			),
			array(
				'TranslatedItem' => array(
					'slug' => 'third_translated',
					'locale' => 'deu',
					'title' => '',
					'content' => 'Content #3'
				)
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testMissingTranslation method
 *
 * @access public
 * @return void
 */
	function testMissingTranslation() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel =& new TranslatedItem();
		$TestModel->locale = 'rus';
		$result = $TestModel->read(null, 1);
		$this->assertFalse($result);

		$TestModel->locale = array('rus');
		$result = $TestModel->read(null, 1);
		$expected = array(
			'TranslatedItem' => array(
				'id' => 1,
				'slug' => 'first_translated',
				'locale' => 'rus',
				'title' => '',
				'content' => ''
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testTranslatedFindList method
 *
 * @access public
 * @return void
 */
	function testTranslatedFindList() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel =& new TranslatedItem();
		$TestModel->locale = 'deu';
		$TestModel->displayField = 'title';
		$result = $TestModel->find('list', array('recursive' => 1));
		$expected = array(1 => 'Titel #1', 2 => 'Titel #2', 3 => 'Titel #3');
		$this->assertEqual($result, $expected);

		// MSSQL trigger an error and stops the page even if the debug = 0
		if ($this->db->config['driver'] != 'mssql') {
			$debug = Configure::read('debug');
			Configure::write('debug', 0);

			$result = $TestModel->find('list', array('recursive' => 1, 'callbacks' => false));
			$this->assertEqual($result, array());

			$result = $TestModel->find('list', array('recursive' => 1, 'callbacks' => 'after'));
			$this->assertEqual($result, array());
			Configure::write('debug', $debug);
		}

		$result = $TestModel->find('list', array('recursive' => 1, 'callbacks' => 'before'));
		$expected = array(1 => null, 2 => null, 3 => null);
		$this->assertEqual($result, $expected);
	}

/**
 * testReadSelectedFields method
 *
 * @access public
 * @return void
 */
	function testReadSelectedFields() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel =& new TranslatedItem();
		$TestModel->locale = 'eng';
		$result = $TestModel->find('all', array('fields' => array('slug', 'TranslatedItem.content')));
		$expected = array(
			array('TranslatedItem' => array('slug' => 'first_translated', 'locale' => 'eng', 'content' => 'Content #1')),
			array('TranslatedItem' => array('slug' => 'second_translated', 'locale' => 'eng', 'content' => 'Content #2')),
			array('TranslatedItem' => array('slug' => 'third_translated', 'locale' => 'eng', 'content' => 'Content #3'))
		);
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all', array('fields' => array('TranslatedItem.slug', 'content')));
		$this->assertEqual($result, $expected);

		$TestModel->locale = array('eng', 'deu', 'cze');
		$delete = array(array('locale' => 'deu'), array('field' => 'content', 'locale' => 'eng'));
		$I18nModel =& ClassRegistry::getObject('TranslateTestModel');
		$I18nModel->deleteAll(array('or' => $delete));

		$result = $TestModel->find('all', array('fields' => array('title', 'content')));
		$expected = array(
			array('TranslatedItem' => array('locale' => 'eng', 'title' => 'Title #1', 'content' => 'Obsah #1')),
			array('TranslatedItem' => array('locale' => 'eng', 'title' => 'Title #2', 'content' => 'Obsah #2')),
			array('TranslatedItem' => array('locale' => 'eng', 'title' => 'Title #3', 'content' => 'Obsah #3'))
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testSaveCreate method
 *
 * @access public
 * @return void
 */
	function testSaveCreate() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel =& new TranslatedItem();
		$TestModel->locale = 'spa';
		$data = array('slug' => 'fourth_translated', 'title' => 'Leyenda #4', 'content' => 'Contenido #4');
		$TestModel->create($data);
		$TestModel->save();
		$result = $TestModel->read();
		$expected = array('TranslatedItem' => array_merge($data, array('id' => $TestModel->id, 'locale' => 'spa')));
		$this->assertEqual($result, $expected);
	}

/**
 * testSaveUpdate method
 *
 * @access public
 * @return void
 */
	function testSaveUpdate() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel =& new TranslatedItem();
		$TestModel->locale = 'spa';
		$oldData = array('slug' => 'fourth_translated', 'title' => 'Leyenda #4');
		$TestModel->create($oldData);
		$TestModel->save();
		$id = $TestModel->id;
		$newData = array('id' => $id, 'content' => 'Contenido #4');
		$TestModel->create($newData);
		$TestModel->save();
		$result = $TestModel->read(null, $id);
		$expected = array('TranslatedItem' => array_merge($oldData, $newData, array('locale' => 'spa')));
		$this->assertEqual($result, $expected);
	}

/**
 * testMultipleCreate method
 *
 * @access public
 * @return void
 */
	function testMultipleCreate() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel =& new TranslatedItem();
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
			'TranslatedItem' => array('id' => 4, 'slug' => 'new_translated', 'locale' => 'eng', 'title' => 'New title', 'content' => 'New content'),
			'Title' => array(
				array('id' => 21, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 4, 'field' => 'title', 'content' => 'New title'),
				array('id' => 22, 'locale' => 'spa', 'model' => 'TranslatedItem', 'foreign_key' => 4, 'field' => 'title', 'content' => 'Nuevo leyenda')
			),
			'Content' => array(
				array('id' => 19, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 4, 'field' => 'content', 'content' => 'New content'),
				array('id' => 20, 'locale' => 'spa', 'model' => 'TranslatedItem', 'foreign_key' => 4, 'field' => 'content', 'content' => 'Nuevo contenido')
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testMultipleUpdate method
 *
 * @access public
 * @return void
 */
	function testMultipleUpdate() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel =& new TranslatedItem();
		$TestModel->locale = 'eng';
		$TestModel->validate['title'] = 'notEmpty';
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
			'TranslatedItem' => array('id' => '1', 'slug' => 'first_translated', 'locale' => 'eng', 'title' => 'New Title #1', 'content' => 'New Content #1'),
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
		$this->assertEqual($result, $expected);

		$TestModel->unbindTranslation($translations);
		$TestModel->bindTranslation(array('title', 'content'), false);
	}

/**
 * testMixedCreateUpdateWithArrayLocale method
 *
 * @access public
 * @return void
 */
	function testMixedCreateUpdateWithArrayLocale() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel =& new TranslatedItem();
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
		$expected = array(
			'TranslatedItem' => array('id' => 1, 'slug' => 'first_translated', 'locale' => 'cze', 'title' => 'Titulek #1', 'content' => 'Upraveny obsah #1'),
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
		$this->assertEqual($result, $expected);
	}

/**
 * testValidation method
 *
 * @access public
 * @return void
 */
	function testValidation() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel =& new TranslatedItem();
		$TestModel->locale = 'eng';
		$TestModel->validate['title'] = '/Only this title/';
		$data = array('TranslatedItem' => array(
			'id' => 1,
			'title' => array('eng' => 'New Title #1', 'deu' => 'Neue Titel #1', 'cze' => 'Novy Titulek #1'),
			'content' => array('eng' => 'New Content #1', 'deu' => 'Neue Inhalt #1', 'cze' => 'Novy Obsah #1')
		));
		$TestModel->create();
		$this->assertFalse($TestModel->save($data));
		$this->assertEqual($TestModel->validationErrors['title'], 'This field cannot be left blank');

		$TestModel->locale = 'eng';
		$TestModel->validate['title'] = '/Only this title/';
		$data = array('TranslatedItem' => array(
			'id' => 1,
			'title' => array('eng' => 'Only this title', 'deu' => 'Neue Titel #1', 'cze' => 'Novy Titulek #1'),
			'content' => array('eng' => 'New Content #1', 'deu' => 'Neue Inhalt #1', 'cze' => 'Novy Obsah #1')
		));
		$TestModel->create();
		$this->assertTrue($TestModel->save($data));
	}

/**
 * testAttachDetach method
 *
 * @access public
 * @return void
 */
	function testAttachDetach() {
		$this->loadFixtures('Translate', 'TranslatedItem');

		$TestModel =& new TranslatedItem();
		$Behavior =& $this->Model->Behaviors->Translate;

		$TestModel->unbindTranslation();
		$translations = array('title' => 'Title', 'content' => 'Content');
		$TestModel->bindTranslation($translations, false);

		$result = array_keys($TestModel->hasMany);
		$expected = array('Title', 'Content');
		$this->assertEqual($result, $expected);

		$TestModel->Behaviors->detach('Translate');
		$result = array_keys($TestModel->hasMany);
		$expected = array();
		$this->assertEqual($result, $expected);

		$result = isset($TestModel->Behaviors->Translate);
		$this->assertFalse($result);

		$result = isset($Behavior->settings[$TestModel->alias]);
		$this->assertFalse($result);

		$result = isset($Behavior->runtime[$TestModel->alias]);
		$this->assertFalse($result);

		$TestModel->Behaviors->attach('Translate', array('title' => 'Title', 'content' => 'Content'));
		$result = array_keys($TestModel->hasMany);
		$expected = array('Title', 'Content');
		$this->assertEqual($result, $expected);

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
 * @access public
 * @return void
 */
	function testAnotherTranslateTable() {
		$this->loadFixtures('Translate', 'TranslatedItem', 'TranslateTable');

		$TestModel =& new TranslatedItemWithTable();
		$TestModel->locale = 'eng';
		$result = $TestModel->read(null, 1);
		$expected = array(
			'TranslatedItemWithTable' => array(
				'id' => 1,
				'slug' => 'first_translated',
				'locale' => 'eng',
				'title' => 'Another Title #1',
				'content' => 'Another Content #1'
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testTranslateWithAssociations method
 *
 * @access public
 * @return void
 */
	function testTranslateWithAssociations() {
		$this->loadFixtures('TranslateArticle', 'TranslatedArticle', 'User', 'Comment', 'ArticlesTag', 'Tag');

		$TestModel =& new TranslatedArticle();
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
			)
		);
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);
		$this->assertEqual($TestModel->recursive, $recursive);

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
		$this->assertEqual($result, $expected);
	}
/**
 * testTranslateTableWithPrefix method
 * Tests that is possible to have a translation model with a custom tablePrefix
 *
 * @access public
 * @return void
 */
	function testTranslateTableWithPrefix() {
		$this->loadFixtures('TranslateWithPrefix', 'TranslatedItem');
		$TestModel =& new TranslatedItem2;
		$TestModel->locale = 'eng';
		$result = $TestModel->read(null, 1);
		$expected = array('TranslatedItem' => array(
			'id' => 1,
			'slug' => 'first_translated',
			'locale' => 'eng',
			'content' => 'Content #1',
			'title' => 'Title #1'
		));
		$this->assertEqual($result, $expected);
	}
}
?>