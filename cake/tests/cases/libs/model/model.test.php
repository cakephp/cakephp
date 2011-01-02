<?php
/**
 * ModelTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs.model
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', array('AppModel', 'Model'));
require_once dirname(__FILE__) . DS . 'models.php';

PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'DEFAULT');

/**
 * ModelBaseTest
 *
 * @package       cake.tests.cases.libs.model
 */
abstract class BaseModelTest extends CakeTestCase {

/**
 * autoFixtures property
 *
 * @var bool false
 * @access public
 */
	public $autoFixtures = false;

/**
 * Whether backup global state for each test method or not
 *
 * @var bool false
 * @access public
 */
	public $backupGlobals = false;
/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	public $fixtures = array(
		'core.category', 'core.category_thread', 'core.user', 'core.my_category', 'core.my_product',
		'core.my_user', 'core.my_categories_my_users', 'core.my_categories_my_products',
		'core.article', 'core.featured', 'core.article_featureds_tags', 'core.article_featured',
		'core.articles', 'core.numeric_article', 'core.tag', 'core.articles_tag', 'core.comment',
		'core.attachment', 'core.apple', 'core.sample', 'core.another_article', 'core.item',
		'core.advertisement', 'core.home', 'core.post', 'core.author', 'core.bid', 'core.portfolio',
		'core.product', 'core.project', 'core.thread', 'core.message', 'core.items_portfolio',
		'core.syfile', 'core.image', 'core.device_type', 'core.device_type_category',
		'core.feature_set', 'core.exterior_type_category', 'core.document', 'core.device',
		'core.document_directory', 'core.primary_model', 'core.secondary_model', 'core.something',
		'core.something_else', 'core.join_thing', 'core.join_a', 'core.join_b', 'core.join_c',
		'core.join_a_b', 'core.join_a_c', 'core.uuid', 'core.data_test', 'core.posts_tag',
		'core.the_paper_monkies', 'core.person', 'core.underscore_field', 'core.node',
		'core.dependency', 'core.story', 'core.stories_tag', 'core.cd', 'core.book', 'core.basket',
		'core.overall_favorite', 'core.account', 'core.content', 'core.content_account',
		'core.film_file', 'core.test_plugin_article', 'core.test_plugin_comment', 'core.uuiditem',
		'core.counter_cache_user', 'core.counter_cache_post',
		'core.counter_cache_user_nonstandard_primary_key',
		'core.counter_cache_post_nonstandard_primary_key', 'core.uuidportfolio',
		'core.uuiditems_uuidportfolio', 'core.uuiditems_uuidportfolio_numericid', 'core.fruit',
		'core.fruits_uuid_tag', 'core.uuid_tag', 'core.product_update_all', 'core.group_update_all'
	);

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		parent::setUp();
		$this->debug = Configure::read('debug');
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		parent::tearDown();
		Configure::write('debug', $this->debug);
		ClassRegistry::flush();
	}
}
