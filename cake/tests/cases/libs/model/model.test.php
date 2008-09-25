<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs.model
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

App::import('Core', array('AppModel', 'Model'));
require_once dirname(__FILE__) . DS . 'models.php';

/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class ModelTest extends CakeTestCase {
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
		'core.category', 'core.category_thread', 'core.user', 'core.my_category', 'core.my_product', 'core.my_user', 'core.my_categories_my_users',
		'core.my_categories_my_products', 'core.article', 'core.featured', 'core.article_featureds_tags',
		'core.article_featured', 'core.articles', 'core.numeric_article', 'core.tag', 'core.articles_tag', 'core.comment', 'core.attachment',
		'core.apple', 'core.sample', 'core.another_article', 'core.advertisement', 'core.home', 'core.post', 'core.author',
		'core.product', 'core.project', 'core.thread', 'core.message', 'core.bid', 'core.portfolio', 'core.item', 'core.items_portfolio',
		'core.syfile', 'core.image', 'core.device_type', 'core.device_type_category', 'core.feature_set', 'core.exterior_type_category',
		'core.document', 'core.device', 'core.document_directory', 'core.primary_model', 'core.secondary_model', 'core.something',
		'core.something_else', 'core.join_thing', 'core.join_a', 'core.join_b', 'core.join_c', 'core.join_a_b', 'core.join_a_c',
		'core.uuid', 'core.data_test', 'core.posts_tag', 'core.the_paper_monkies', 'core.person', 'core.underscore_field',
		'core.node', 'core.dependency', 'core.story', 'core.stories_tag', 'core.cd', 'core.book', 'core.overall_favorite', 'core.account',
		'core.content', 'core.content_account', 'core.film_file', 'core.basket', 'core.test_plugin_article', 'core.test_plugin_comment'
	);
/**
 * start method
 *
 * @access public
 * @return void
 */
	function start() {
		parent::start();
		$this->debug = Configure::read('debug');
		Configure::write('debug', 2);
	}
/**
 * end method
 *
 * @access public
 * @return void
 */
	function end() {
		parent::end();
		Configure::write('debug', $this->debug);
	}
/**
 * testAutoConstructAssociations method
 *
 * @access public
 * @return void
 */
	function testAutoConstructAssociations() {
		$this->loadFixtures('User');
		$TestModel =& new AssociationTest1();

		$result = $TestModel->hasAndBelongsToMany;
		$expected = array('AssociationTest2' => array(
			'unique' => false, 'joinTable' => 'join_as_join_bs', 'foreignKey' => false,
			'className' => 'AssociationTest2', 'with' => 'JoinAsJoinB',
			'associationForeignKey' => 'join_b_id', 'conditions' => '', 'fields' => '',
			'order' => '', 'limit' => '', 'offset' => '', 'finderQuery' => '',
			'deleteQuery' => '', 'insertQuery' => ''
		));
		$this->assertEqual($result, $expected);
	}
/**
 * testColumnTypeFetching method
 *
 * @access public
 * @return void
 */
	function testColumnTypeFetching() {
		$model =& new Test();
		$this->assertEqual($model->getColumnType('id'), 'integer');
		$this->assertEqual($model->getColumnType('notes'), 'text');
		$this->assertEqual($model->getColumnType('updated'), 'datetime');
		$this->assertEqual($model->getColumnType('unknown'), null);

		$model =& new Article();
		$this->assertEqual($model->getColumnType('User.created'), 'datetime');
		$this->assertEqual($model->getColumnType('Tag.id'), 'integer');
		$this->assertEqual($model->getColumnType('Article.id'), 'integer');
	}
/**
 * testMultipleBelongsToWithSameClass method
 *
 * @access public
 * @return void
 */
	function testMultipleBelongsToWithSameClass() {
		$this->loadFixtures('DeviceType', 'DeviceTypeCategory', 'FeatureSet', 'ExteriorTypeCategory', 'Document', 'Device', 'DocumentDirectory');
		$DeviceType =& new DeviceType();

		$DeviceType->recursive = 2;
		$result = $DeviceType->read(null, 1);

		$expected = array(
			'DeviceType' => array(
				'id' => 1, 'device_type_category_id' => 1, 'feature_set_id' => 1, 'exterior_type_category_id' => 1, 'image_id' => 1,
				'extra1_id' => 1, 'extra2_id' => 1, 'name' => 'DeviceType 1', 'order' => 0
			),
			'Image' => array('id' => 1, 'document_directory_id' => 1, 'name' => 'Document 1',
				'DocumentDirectory' => array('id' => 1, 'name' => 'DocumentDirectory 1')),
			'Extra1' => array(
				'id' => 1, 'document_directory_id' => 1, 'name' => 'Document 1',
				'DocumentDirectory' => array('id' => 1, 'name' => 'DocumentDirectory 1')
			),
			'Extra2' => array(
				'id' => 1, 'document_directory_id' => 1, 'name' => 'Document 1',
				'DocumentDirectory' => array('id' => 1, 'name' => 'DocumentDirectory 1')
			),
			'DeviceTypeCategory' => array('id' => 1, 'name' => 'DeviceTypeCategory 1'),
			'FeatureSet' => array('id' => 1, 'name' => 'FeatureSet 1'),
			'ExteriorTypeCategory' => array(
				'id' => 1, 'image_id' => 1, 'name' => 'ExteriorTypeCategory 1',
				'Image' => array('id' => 1, 'device_type_id' => 1, 'name' => 'Device 1', 'typ' => 1)
			),
			'Device' => array(
				array('id' => 1, 'device_type_id' => 1, 'name' => 'Device 1', 'typ' => 1),
				array('id' => 2, 'device_type_id' => 1, 'name' => 'Device 2', 'typ' => 1),
				array('id' => 3, 'device_type_id' => 1, 'name' => 'Device 3', 'typ' => 2)
			)
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testHabtmRecursiveBelongsTo method
 *
 * @access public
 * @return void
 */
	function testHabtmRecursiveBelongsTo() {
		$this->loadFixtures('Portfolio', 'Item', 'ItemsPortfolio', 'Syfile', 'Image');
		$Portfolio =& new Portfolio();

		$result = $Portfolio->find(array('id' => 2), null, null, 3);
		$expected = array('Portfolio' => array(
			'id' => 2, 'seller_id' => 1, 'name' => 'Portfolio 2'),
			'Item' => array(
			array('id' => 2, 'syfile_id' => 2, 'published' => 0, 'name' => 'Item 2',
					'ItemsPortfolio' => array('id' => 2, 'item_id' => 2, 'portfolio_id' => 2),
					'Syfile' => array('id' => 2, 'image_id' => 2, 'name' => 'Syfile 2', 'item_count' => null,
							'Image' => array('id' => 2, 'name' => 'Image 2'))),
			array('id' => 6, 'syfile_id' => 6, 'published' => 0, 'name' => 'Item 6',
					'ItemsPortfolio' => array('id' => 6, 'item_id' => 6, 'portfolio_id' => 2),
					'Syfile' => array('id' => 6, 'image_id' => null, 'name' => 'Syfile 6', 'item_count' => null,
							'Image' => array()))));
		$this->assertEqual($result, $expected);
	}
/**
 * testHabtmFinderQuery method
 *
 * @access public
 * @return void
 */
	function testHabtmFinderQuery() {
		$this->loadFixtures('Article', 'Tag', 'ArticlesTag');
		$Article =& new Article();

		$sql = $this->db->buildStatement(
			array(
				'fields' => $this->db->fields($Article->Tag, null, array('Tag.id', 'Tag.tag', 'ArticlesTag.article_id', 'ArticlesTag.tag_id')),
				'table' => $this->db->fullTableName('tags'),
				'alias' => 'Tag',
				'limit' => null,
				'offset' => null,
				'group' => null,
				'joins' => array(array(
					'alias' => 'ArticlesTag',
					'table' => $this->db->fullTableName('articles_tags'),
					'conditions' => array(
						array("ArticlesTag.article_id" => '{$__cakeID__$}'),
						array("ArticlesTag.tag_id" => $this->db->identifier('Tag.id'))
					)
				)),
				'conditions' => array(),
				'order' => null
			),
			$Article
		);

		$Article->hasAndBelongsToMany['Tag']['finderQuery'] = $sql;
		$result = $Article->find('first');
		$expected = array(array('id' => '1', 'tag' => 'tag1'), array('id' => '2', 'tag' => 'tag2'));
		$this->assertEqual($result['Tag'], $expected);
	}
/**
 * testHabtmLimitOptimization method
 *
 * @access public
 * @return void
 */
	function testHabtmLimitOptimization() {
		$this->loadFixtures('Article', 'User', 'Comment', 'Tag', 'ArticlesTag');
		$TestModel =& new Article();

		$TestModel->hasAndBelongsToMany['Tag']['limit'] = 2;
		$result = $TestModel->read(null, 2);
		$expected = array(
			'Article' => array('id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
			'User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'),
			'Comment' => array(
				array('id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'),
				array('id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31')
			),
			'Tag' => array(
				array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
			)
		);
		$this->assertEqual($result, $expected);

		$TestModel->hasAndBelongsToMany['Tag']['limit'] = 1;
		$result = $TestModel->read(null, 2);
		unset($expected['Tag'][1]);
		$this->assertEqual($result, $expected);
	}
/**
 * testHabtmUniqueKey method
 *
 * @access public
 * @return void
 */
	function testHabtmUniqueKey() {
		$model =& new Item();
		$this->assertFalse($model->hasAndBelongsToMany['Portfolio']['unique']);
	}
/**
 * testHasManyLimitOptimization method
 *
 * @access public
 * @return void
 */
	function testHasManyLimitOptimization() {
		$this->loadFixtures('Project', 'Thread', 'Message', 'Bid');
		$Project =& new Project();
		$Project->recursive = 3;

		$result = $Project->find('all');
		$expected = array(
			array('Project' => array('id' => 1, 'name' => 'Project 1'),
					'Thread' => array(
						array(
							'id' => 1, 'project_id' => 1, 'name' => 'Project 1, Thread 1',
								'Project' => array(
									'id' => 1, 'name' => 'Project 1',
									'Thread' => array(
										array('id' => 1, 'project_id' => 1, 'name' => 'Project 1, Thread 1'),
										array('id' => 2, 'project_id' => 1, 'name' => 'Project 1, Thread 2')
									)
								),
								'Message' => array(
									array(
										'id' => 1, 'thread_id' => 1, 'name' => 'Thread 1, Message 1',
										'Bid' => array('id' => 1, 'message_id' => 1, 'name' => 'Bid 1.1')
									)
								)
						),
						array(
							'id' => 2, 'project_id' => 1, 'name' => 'Project 1, Thread 2',
							'Project' => array(
								'id' => 1, 'name' => 'Project 1',
								'Thread' => array(
									array('id' => 1, 'project_id' => 1, 'name' => 'Project 1, Thread 1'),
									array('id' => 2, 'project_id' => 1, 'name' => 'Project 1, Thread 2')
								)
							),
							'Message' => array(
								array(
									'id' => 2, 'thread_id' => 2, 'name' => 'Thread 2, Message 1',
									'Bid' => array('id' => 4, 'message_id' => 2, 'name' => 'Bid 2.1')
								)
							)
						)
				)
			),
			array('Project' => array('id' => 2, 'name' => 'Project 2'),
					'Thread' => array(
						array(
							'id' => 3, 'project_id' => 2, 'name' => 'Project 2, Thread 1',
							'Project' => array(
								'id' => 2, 'name' => 'Project 2',
								'Thread' => array(
									array('id' => 3, 'project_id' => 2, 'name' => 'Project 2, Thread 1'),
								)
							),
							'Message' => array(
								array(
									'id' => 3, 'thread_id' => 3, 'name' => 'Thread 3, Message 1',
									'Bid' => array('id' => 3, 'message_id' => 3, 'name' => 'Bid 3.1')
								)
							)
						)
					)
			),
			array('Project' => array('id' => 3, 'name' => 'Project 3'),
					'Thread' => array()
				)
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testWithAssociation method
 *
 * @access public
 * @return void
 */
	function testWithAssociation() {
		$this->loadFixtures('Something', 'SomethingElse', 'JoinThing');
		$TestModel =& new Something();
		$result = $TestModel->SomethingElse->find('all');

		$expected = array(
			array('SomethingElse' => array('id' => '1', 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				'Something' => array(array('id' => '3', 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
					'JoinThing' => array('id' => '3', 'something_id' => '3', 'something_else_id' => '1', 'doomed' => '1', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31')))),
			array('SomethingElse' => array('id' => '2', 'title' => 'Second Post', 'body' => 'Second Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
				'Something' => array(array('id' => '1', 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
					'JoinThing' => array('id' => '1', 'something_id' => '1', 'something_else_id' => '2', 'doomed' => '1', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31')))),
			array('SomethingElse' => array('id' => '3', 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'),
				'Something' => array (array('id' => '2', 'title' => 'Second Post', 'body' => 'Second Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
					'JoinThing' => array('id' => '2', 'something_id' => '2', 'something_else_id' => '3', 'doomed' => '0', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31')))));
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all');
		$expected = array(
			array('Something' => array('id' => '1', 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				'SomethingElse' => array(
					array('id' => '2', 'title' => 'Second Post', 'body' => 'Second Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
						'JoinThing' => array('doomed' => '1', 'something_id' => '1', 'something_else_id' => '2')))),
				array('Something' => array('id' => '2', 'title' => 'Second Post', 'body' => 'Second Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
					'SomethingElse' => array(
						array('id' => '3', 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
						'JoinThing' => array('doomed' => '0', 'something_id' => '2', 'something_else_id' => '3')))),
				array('Something' => array('id' => '3', 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'),
					'SomethingElse' => array(
						array('id' => '1', 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
						'JoinThing' => array('doomed' => '1', 'something_id' => '3', 'something_else_id' => '1')))));
		$this->assertEqual($result, $expected);

		$result = $TestModel->findById(1);
		$expected = array(
			'Something' => array('id' => '1', 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				'SomethingElse' => array(array('id' => '2', 'title' => 'Second Post', 'body' => 'Second Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
					'JoinThing' => array('doomed' => '1', 'something_id' => '1', 'something_else_id' => '2'))));
		$this->assertEqual($result, $expected);

		$TestModel->hasAndBelongsToMany['SomethingElse']['unique'] = false;
		$TestModel->create(array(
			'Something' => array('id' => 1),
			'SomethingElse' => array(3, array('something_else_id' => 1, 'doomed' => '1'))
		));
		$ts = date('Y-m-d H:i:s');
		$TestModel->save();

		$TestModel->hasAndBelongsToMany['SomethingElse']['order'] = 'SomethingElse.id ASC';
		$result = $TestModel->findById(1);
		$expected = array(
			'Something' => array('id' => '1', 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => $ts),
				'SomethingElse' => array(
					array('id' => '1', 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
						'JoinThing' => array('doomed' => '1', 'something_id' => '1', 'something_else_id' => '1')),
					array('id' => '2', 'title' => 'Second Post', 'body' => 'Second Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
						'JoinThing' => array('doomed' => '1', 'something_id' => '1', 'something_else_id' => '2')),
					array('id' => '3', 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
						'JoinThing' => array('doomed' => '0', 'something_id' => '1', 'something_else_id' => '3'))));
		$this->assertEqual($result, $expected);
	}
/**
 * testDynamicAssociations method
 *
 * @access public
 * @return void
 */
	function testDynamicAssociations() {
		$this->loadFixtures('Article', 'Comment');
		$TestModel =& new Article();

		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = $TestModel->hasOne = array();
		$TestModel->hasMany['Comment'] = array_merge($TestModel->hasMany['Comment'], array(
			'foreignKey' => false,
			'conditions' => array('Comment.user_id =' => '2')
		));
		$result = $TestModel->find('all');
		$expected = array(
			array(
				'Article' => array('id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				'Comment' => array(
					array('id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
					array('id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31')
				)
			),
			array(
				'Article' => array('id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
				'Comment' => array(
					array('id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
					array('id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31')
				)
			),
			array(
				'Article' => array('id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'),
				'Comment' => array(
					array('id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
					array('id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31')
				)
			)
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testSaveMultipleHabtm method
 *
 * @access public
 * @return void
 */
	function testSaveMultipleHabtm() {
		$this->loadFixtures('JoinA', 'JoinB', 'JoinC', 'JoinAB', 'JoinAC');
		$TestModel = new JoinA();
		$result = $TestModel->findById(1);

		$expected = array(
			'JoinA' => array('id' => 1, 'name' => 'Join A 1', 'body' => 'Join A 1 Body', 'created' => '2008-01-03 10:54:23', 'updated' => '2008-01-03 10:54:23'),
				'JoinB' => array(
					0 => array('id' => 2, 'name' => 'Join B 2', 'created' => '2008-01-03 10:55:02', 'updated' => '2008-01-03 10:55:02',
						'JoinAsJoinB' => array('id' => 1, 'join_a_id' => 1, 'join_b_id' => 2, 'other' => 'Data for Join A 1 Join B 2', 'created' => '2008-01-03 10:56:33', 'updated' => '2008-01-03 10:56:33'))),
				'JoinC' => array(
					0 => array('id' => 2, 'name' => 'Join C 2', 'created' => '2008-01-03 10:56:12', 'updated' => '2008-01-03 10:56:12',
						'JoinAsJoinC' => array('id' => 1, 'join_a_id' => 1, 'join_c_id' => 2, 'other' => 'Data for Join A 1 Join C 2', 'created' => '2008-01-03 10:57:22', 'updated' => '2008-01-03 10:57:22'))));

		$this->assertEqual($result, $expected);

		$ts = date('Y-m-d H:i:s');
		$TestModel->id = 1;
		$data = array(
			'JoinA' => array('id' => '1', 'name' => 'New name for Join A 1', 'updated' => $ts),
			'JoinB' => array(array('id' => 1, 'join_b_id' => 2, 'other' => 'New data for Join A 1 Join B 2', 'created' => $ts, 'updated' => $ts)),
			'JoinC' => array(array('id' => 1, 'join_c_id' => 2, 'other' => 'New data for Join A 1 Join C 2', 'created' => $ts, 'updated' => $ts)));
		$TestModel->set($data);
		$TestModel->save();

		$result = $TestModel->findById(1);
		$expected = array(
			'JoinA' => array('id' => 1, 'name' => 'New name for Join A 1', 'body' => 'Join A 1 Body', 'created' => '2008-01-03 10:54:23', 'updated' => $ts),
				'JoinB' => array(
					0 => array('id' => 2, 'name' => 'Join B 2', 'created' => '2008-01-03 10:55:02', 'updated' => '2008-01-03 10:55:02',
						'JoinAsJoinB' => array('id' => 1, 'join_a_id' => 1, 'join_b_id' => 2, 'other' => 'New data for Join A 1 Join B 2', 'created' => $ts, 'updated' => $ts))),
				'JoinC' => array(
					0 => array('id' => 2, 'name' => 'Join C 2', 'created' => '2008-01-03 10:56:12', 'updated' => '2008-01-03 10:56:12',
						'JoinAsJoinC' => array('id' => 1, 'join_a_id' => 1, 'join_c_id' => 2, 'other' => 'New data for Join A 1 Join C 2', 'created' => $ts, 'updated' => $ts))));
		$this->assertEqual($result, $expected);
	}
/**
 * testFindAllRecursiveSelfJoin method
 *
 * @access public
 * @return void
 */
	function testFindAllRecursiveSelfJoin() {
		$this->loadFixtures('Home', 'AnotherArticle', 'Advertisement');
		$TestModel =& new Home();
		$TestModel->recursive = 2;

		$result = $TestModel->find('all');
		$expected = array(array('Home' => array(
						'id' => '1', 'another_article_id' => '1', 'advertisement_id' => '1', 'title' => 'First Home', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
								'AnotherArticle' => array('id' => '1', 'title' => 'First Article', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
										'Home' => array(array('id' => '1', 'another_article_id' => '1', 'advertisement_id' => '1', 'title' => 'First Home', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'))),
								'Advertisement' => array('id' => '1', 'title' => 'First Ad', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
									'Home' => array(array('id' => '1', 'another_article_id' => '1', 'advertisement_id' => '1', 'title' => 'First Home', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
										array('id' => '2', 'another_article_id' => '3', 'advertisement_id' => '1', 'title' => 'Second Home', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31')))),
				array('Home' => array(
						'id' => '2', 'another_article_id' => '3', 'advertisement_id' => '1', 'title' => 'Second Home', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
								'AnotherArticle' => array('id' => '3', 'title' => 'Third Article', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
										'Home' => array(array('id' => '2', 'another_article_id' => '3', 'advertisement_id' => '1', 'title' => 'Second Home', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'))),
								'Advertisement' => array('id' => '1', 'title' => 'First Ad', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
									'Home' => array(array('id' => '1', 'another_article_id' => '1', 'advertisement_id' => '1', 'title' => 'First Home', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
											array('id' => '2', 'another_article_id' => '3', 'advertisement_id' => '1', 'title' => 'Second Home', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31')))));
		$this->assertEqual($result, $expected);



	}
/**
 * testFindAllRecursiveWithHabtm method
 *
 * @return void
 * @access public
 */
	function testFindAllRecursiveWithHabtm() {
		$this->loadFixtures('MyCategoriesMyUsers', 'MyCategoriesMyProducts', 'MyCategory', 'MyUser', 'MyProduct');
		$MyUser =& new MyUser();
		$MyUser->recursive = 2;

		$result = $MyUser->find('all');
		$expected = array(
			array(
				'MyUser' => array(
					'id' => '1',
					'firstname' => 'userA'
				),
				'MyCategory' => array(
					array(
						'id' => '1',
						'name' => 'A',
						'MyProduct' => array(
							array(
								'id' => '1',
								'name' => 'book'
							)
						)
					),
					array(
						'id' => '3',
						'name' => 'C',
						'MyProduct' => array(
							array(
								'id' => '2',
								'name' => 'computer'
							),
						)
					)
				)
			),
			array(
				'MyUser' => array(
					'id' => '2',
					'firstname' => 'userB'
				),
				'MyCategory' => array(
					array(
						'id' => '1',
						'name' => 'A',
						'MyProduct' => array(
							array(
								'id' => '1',
								'name' => 'book'
							)
						)
					),
					array(
						'id' => '2',
						'name' => 'B',
						'MyProduct' => array(
							array(
								'id' => '1',
								'name' => 'book'
							),
							array(
								'id' => '2',
								'name' => 'computer'
							)
						)
					)
				)
			)
		);
		$this->assertIdentical($result, $expected);
	}
/**
 * testFindSelfAssociations method
 *
 * @access public
 * @return void
 */
	function testFindSelfAssociations() {
		$this->loadFixtures('Person');

		$TestModel =& new Person();
		$TestModel->recursive = 2;
		$result = $TestModel->read(null, 1);
		$expected = array(
				'Person' => array('id' => 1, 'name' => 'person', 'mother_id' => 2, 'father_id' => 3),
				'Mother' => array('id' => 2, 'name' => 'mother', 'mother_id' => 4, 'father_id' => 5,
					'Mother' => array('id' => 4, 'name' => 'mother - grand mother', 'mother_id' => 0, 'father_id' => 0),
					'Father' => array('id' => 5, 'name' => 'mother - grand father', 'mother_id' => 0, 'father_id' => 0)),
				'Father' => array('id' => 3, 'name' => 'father', 'mother_id' => 6, 'father_id' => 7,
					'Father' => array('id' => 7, 'name' => 'father - grand father', 'mother_id' => 0, 'father_id' => 0),
					'Mother' => array('id' => 6, 'name' => 'father - grand mother', 'mother_id' => 0, 'father_id' => 0)));
		$this->assertEqual($result, $expected);

		$TestModel->recursive = 3;
		$result = $TestModel->read(null, 1);
		$expected = array(
				'Person' => array('id' => 1, 'name' => 'person', 'mother_id' => 2, 'father_id' => 3),
				'Mother' => array('id' => 2, 'name' => 'mother', 'mother_id' => 4, 'father_id' => 5,
					'Mother' => array('id' => 4, 'name' => 'mother - grand mother', 'mother_id' => 0, 'father_id' => 0,
						'Mother' => array(),
						'Father' => array()),
					'Father' => array('id' => 5, 'name' => 'mother - grand father', 'mother_id' => 0, 'father_id' => 0,
						'Father' => array(),
						'Mother' => array())),
				'Father' => array('id' => 3, 'name' => 'father', 'mother_id' => 6, 'father_id' => 7,
					'Father' => array('id' => 7, 'name' => 'father - grand father', 'mother_id' => 0, 'father_id' => 0,
						'Father' => array(),
						'Mother' => array()),
					'Mother' => array('id' => 6, 'name' => 'father - grand mother', 'mother_id' => 0, 'father_id' => 0,
						'Mother' => array(),
						'Father' => array())));
		$this->assertEqual($result, $expected);
	}
/**
 * testPluginAssociations method
 *
 * @access public
 * @return void
 */
	function testPluginAssociations() {
		$this->loadFixtures('TestPluginArticle', 'User', 'TestPluginComment');
		$TestModel =& new TestPluginArticle();

		$result = $TestModel->find('all');
		$expected = array(
			array(
				'TestPluginArticle' => array('id' => 1, 'user_id' => 1, 'title' => 'First Plugin Article', 'body' => 'First Plugin Article Body', 'published' => 'Y', 'created' => '2008-09-24 10:39:23', 'updated' => '2008-09-24 10:41:31'),
				'User' => array('id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'TestPluginComment' => array(
					array('id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Plugin Article', 'published' => 'Y', 'created' => '2008-09-24 10:45:23', 'updated' => '2008-09-24 10:47:31'),
					array('id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Plugin Article', 'published' => 'Y', 'created' => '2008-09-24 10:47:23', 'updated' => '2008-09-24 10:49:31'),
					array('id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Plugin Article', 'published' => 'Y', 'created' => '2008-09-24 10:49:23', 'updated' => '2008-09-24 10:51:31'),
					array('id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Plugin Article', 'published' => 'N', 'created' => '2008-09-24 10:51:23', 'updated' => '2008-09-24 10:53:31')
				)
			),
			array(
				'TestPluginArticle' => array('id' => 2, 'user_id' => 3, 'title' => 'Second Plugin Article', 'body' => 'Second Plugin Article Body', 'published' => 'Y', 'created' => '2008-09-24 10:41:23', 'updated' => '2008-09-24 10:43:31'),
				'User' => array('id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'),
				'TestPluginComment' => array(
					array('id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Plugin Article', 'published' => 'Y', 'created' => '2008-09-24 10:53:23', 'updated' => '2008-09-24 10:55:31'),
					array('id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Plugin Article', 'published' => 'Y', 'created' => '2008-09-24 10:55:23', 'updated' => '2008-09-24 10:57:31')
				)
			),
			array(
				'TestPluginArticle' => array('id' => 3,'user_id' => 1, 'title' => 'Third Plugin Article', 'body' => 'Third Plugin Article Body', 'published' => 'Y', 'created' => '2008-09-24 10:43:23', 'updated' => '2008-09-24 10:45:31'),
				'User' => array('id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'TestPluginComment' => array()
			)
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testIdentity method
 *
 * @access public
 * @return void
 */
	function testIdentity() {
		$TestModel =& new Test();
		$result = $TestModel->alias;
		$expected = 'Test';
		$this->assertEqual($result, $expected);

		$TestModel =& new TestAlias();
		$result = $TestModel->alias;
		$expected = 'TestAlias';
		$this->assertEqual($result, $expected);

		$TestModel =& new Test(array('alias' => 'AnotherTest'));
		$result = $TestModel->alias;
		$expected = 'AnotherTest';
		$this->assertEqual($result, $expected);
	}
/**
 * testCreation method
 *
 * @access public
 * @return void
 */
	function testCreation() {
		$this->loadFixtures('Article');
		$TestModel =& new Test();
		$result = $TestModel->create();
		$expected = array('Test' => array('notes' => 'write some notes here'));
		$this->assertEqual($result, $expected);
		$TestModel =& new User();
		$result = $TestModel->schema();

		if (isset($this->db->columns['primary_key']['length'])) {
			$intLength = $this->db->columns['primary_key']['length'];
		} elseif (isset($this->db->columns['integer']['length'])) {
			$intLength = $this->db->columns['integer']['length'];
		} else {
			$intLength = 11;
		}

		$expected = array(
				'id' => array('type' => 'integer',	'null' => false, 'default' => null,	'length' => $intLength, 'key' => 'primary'),
				'user' => array('type' => 'string',		'null' => false, 'default' => '',	'length' => 255),
				'password' => array('type' => 'string',		'null' => false, 'default' => '',	'length' => 255),
				'created' => array('type' => 'datetime',	'null' => true, 'default' => null,	'length' => null),
				'updated'=> array('type' => 'datetime',	'null' => true, 'default' => null,	'length' => null));
		$this->assertEqual($result, $expected);

		$TestModel =& new Article();
		$result = $TestModel->create();
		$expected = array('Article' => array('published' => 'N'));
		$this->assertEqual($result, $expected);

		$FeaturedModel =& new Featured();
		$data = array(
			'article_featured_id' => 1, 'category_id' => 1,
			'published_date' => array('year' => 2008, 'month' => 06, 'day' => 11),
			'end_date' => array('year' => 2008, 'month' => 06, 'day' => 20)
		);
		$expected = array('Featured' => array(
			'article_featured_id' => 1, 'category_id' => 1,
			'published_date' => '2008-6-11 00:00:00', 'end_date' => '2008-6-20 00:00:00'
		));
		$this->assertEqual($FeaturedModel->create($data), $expected);

		$data = array(
			'published_date' => array('year' => 2008, 'month' => 06, 'day' => 11),
			'end_date' => array('year' => 2008, 'month' => 06, 'day' => 20),
			'article_featured_id' => 1, 'category_id' => 1
		);
		$expected = array('Featured' => array(
			'published_date' => '2008-6-11 00:00:00', 'end_date' => '2008-6-20 00:00:00',
			'article_featured_id' => 1, 'category_id' => 1
		));
		$this->assertEqual($FeaturedModel->create($data), $expected);
	}
/**
 * testCreationOfEmptyRecord method
 *
 * @access public
 * @return void
 */
	function testCreationOfEmptyRecord() {
		$this->loadFixtures('Author');
		$TestModel =& new Author();
		$this->assertEqual($TestModel->find('count'), 4);

		$TestModel->deleteAll(true, false, false);
		$this->assertEqual($TestModel->find('count'), 0);

		$result = $TestModel->save();
		$this->assertTrue(isset($result['Author']['created']));
		$this->assertTrue(isset($result['Author']['updated']));
		$this->assertEqual($TestModel->find('count'), 1);
	}
/**
 * testCreateWithPKFiltering method
 *
 * @access public
 * @return void
 */
	function testCreateWithPKFiltering() {
		$TestModel =& new Article();
		$data = array('id' => 5, 'user_id' => 2, 'title' => 'My article', 'body' => 'Some text');

		$result = $TestModel->create($data);
		$expected = array('Article' => array('published' => 'N', 'id' => 5, 'user_id' => 2, 'title' => 'My article', 'body' => 'Some text'));
		$this->assertEqual($result, $expected);
		$this->assertEqual($TestModel->id, 5);

		$result = $TestModel->create($data, true);
		$expected = array('Article' => array('published' => 'N', 'id' => false, 'user_id' => 2, 'title' => 'My article', 'body' => 'Some text'));
		$this->assertEqual($result, $expected);
		$this->assertFalse($TestModel->id);

		$result = $TestModel->create(array('Article' => $data), true);
		$expected = array('Article' => array('published' => 'N', 'id' => false, 'user_id' => 2, 'title' => 'My article', 'body' => 'Some text'));
		$this->assertEqual($result, $expected);
		$this->assertFalse($TestModel->id);
	}
/**
 * testCreationWithMultipleData method
 *
 * @access public
 * @return void
 */
	function testCreationWithMultipleData() {
		$this->loadFixtures('Article', 'Comment');
		$Article =& new Article();
		$Comment =& new Comment();

		$articles = $Article->find('all', array('fields' => array('id','title'), 'recursive' => -1));
		$comments = $Comment->find('all', array('fields' => array('id','article_id','user_id','comment','published'), 'recursive' => -1));
		$this->assertEqual($articles, array(
			array('Article' => array('id' => 1, 'title' => 'First Article')),
			array('Article' => array('id' => 2, 'title' => 'Second Article')),
			array('Article' => array('id' => 3, 'title' => 'Third Article'))));
		$this->assertEqual($comments, array(
			array('Comment' => array('id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article', 'published' => 'Y')),
			array('Comment' => array('id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article', 'published' => 'Y')),
			array('Comment' => array('id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article', 'published' => 'Y')),
			array('Comment' => array('id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article', 'published' => 'N')),
			array('Comment' => array('id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article', 'published' => 'Y')),
			array('Comment' => array('id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article', 'published' => 'Y'))));

		$data = array('Comment' => array('article_id' => 2, 'user_id' => 4, 'comment' => 'Brand New Comment', 'published' => 'N'),
				'Article' => array('id' => 2, 'title' => 'Second Article Modified'));
		$result = $Comment->create($data);
		$this->assertTrue($result);
		$result = $Comment->save();
		$this->assertTrue($result);

		$articles = $Article->find('all', array('fields' => array('id','title'), 'recursive' => -1));
		$comments = $Comment->find('all', array('fields' => array('id','article_id','user_id','comment','published'), 'recursive' => -1));
		$this->assertEqual($articles, array(
			array('Article' => array('id' => 1, 'title' => 'First Article')),
			array('Article' => array('id' => 2, 'title' => 'Second Article')),
			array('Article' => array('id' => 3, 'title' => 'Third Article'))));
		$this->assertEqual($comments, array(
			array('Comment' => array('id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article', 'published' => 'Y')),
			array('Comment' => array('id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article', 'published' => 'Y')),
			array('Comment' => array('id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article', 'published' => 'Y')),
			array('Comment' => array('id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article', 'published' => 'N')),
			array('Comment' => array('id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article', 'published' => 'Y')),
			array('Comment' => array('id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article', 'published' => 'Y')),
			array('Comment' => array('id' => 7, 'article_id' => 2, 'user_id' => 4, 'comment' => 'Brand New Comment', 'published' => 'N'))));
	}
/**
 * testCreationWithMultipleDataSameModel method
 *
 * @access public
 * @return void
 */
	function testCreationWithMultipleDataSameModel() {
		$this->loadFixtures('Article');
		$Article =& new Article();
		$SecondaryArticle =& new Article();

		$result = $Article->field('title', array('id' => 1));
		$this->assertEqual($result, 'First Article');

		$data = array('Article' => array('user_id' => 2, 'title' => 'Brand New Article', 'body' => 'Brand New Article Body', 'published' => 'Y'),
			'SecondaryArticle' => array('id' => 1));
		$Article->create();
		$result = $Article->save($data);
		$this->assertTrue($result);

		$result = $Article->getInsertID();
		$this->assertTrue(!empty($result));

		$result = $Article->field('title', array('id' => 1));
		$this->assertEqual($result, 'First Article');

		$articles = $Article->find('all', array('fields' => array('id','title'), 'recursive' => -1));
		$this->assertEqual($articles, array(
			array('Article' => array('id' => 1, 'title' => 'First Article')),
			array('Article' => array('id' => 2, 'title' => 'Second Article')),
			array('Article' => array('id' => 3, 'title' => 'Third Article')),
			array('Article' => array('id' => 4, 'title' => 'Brand New Article'))));
	}
/**
 * testCreationWithMultipleDataSameModelManualInstances method
 *
 * @access public
 * @return void
 */
	function testCreationWithMultipleDataSameModelManualInstances() {
		$this->loadFixtures('PrimaryModel');
		$Primary =& new PrimaryModel();
		$Secondary =& new PrimaryModel();

		$result = $Primary->field('primary_name', array('id' => 1));
		$this->assertEqual($result, 'Primary Name Existing');

		$data = array('PrimaryModel' => array('primary_name' => 'Primary Name New'),
			'SecondaryModel' => array('id' => array(1)));
		$Primary->create();
		$result = $Primary->save($data);
		$this->assertTrue($result);

		$result = $Primary->field('primary_name', array('id' => 1));
		$this->assertEqual($result, 'Primary Name Existing');

		$result = $Primary->getInsertID();
		$this->assertTrue(!empty($result));

		$result = $Primary->field('primary_name', array('id' => $result));
		$this->assertEqual($result, 'Primary Name New');

		$result = $Primary->find('count');
		$this->assertEqual($result, 2);
	}
/**
 * testReadFakeThread method
 *
 * @access public
 * @return void
 */
	function testReadFakeThread() {
		$this->loadFixtures('CategoryThread');
		$TestModel =& new CategoryThread();

		$fullDebug = $this->db->fullDebug;
		$this->db->fullDebug = true;
		$TestModel->recursive = 6;
		$TestModel->id = 7;
		$result = $TestModel->read();
		$expected = array(
				'CategoryThread' => array('id' => 7, 'parent_id' => 6, 'name' => 'Category 2.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
						'ParentCategory' => array('id' => 6, 'parent_id' => 5, 'name' => 'Category 2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 5, 'parent_id' => 4, 'name' => 'Category 1.1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 4, 'parent_id' => 3, 'name' => 'Category 1.1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 3, 'parent_id' => 2, 'name' => 'Category 1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31')))))));
		$this->db->fullDebug = $fullDebug;
		$this->assertEqual($result, $expected);
	}
/**
 * testFindFakeThread method
 *
 * @access public
 * @return void
 */
	function testFindFakeThread() {
		$this->loadFixtures('CategoryThread');
		$TestModel =& new CategoryThread();

		$fullDebug = $this->db->fullDebug;
		$this->db->fullDebug = true;
		$TestModel->recursive = 6;
		$result = $TestModel->find(array('CategoryThread.id' => 7));

		$expected = array(
				'CategoryThread' => array('id' => 7, 'parent_id' => 6, 'name' => 'Category 2.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
						'ParentCategory' => array('id' => 6, 'parent_id' => 5, 'name' => 'Category 2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 5, 'parent_id' => 4, 'name' => 'Category 1.1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 4, 'parent_id' => 3, 'name' => 'Category 1.1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 3, 'parent_id' => 2, 'name' => 'Category 1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31')))))));
		$this->db->fullDebug = $fullDebug;
		$this->assertEqual($result, $expected);
	}
/**
 * testFindAllFakeThread method
 *
 * @access public
 * @return void
 */
	function testFindAllFakeThread() {
		$this->loadFixtures('CategoryThread');
		$TestModel =& new CategoryThread();

		$fullDebug = $this->db->fullDebug;
		$this->db->fullDebug = true;
		$TestModel->recursive = 6;
		$result = $TestModel->find('all', null, null, 'CategoryThread.id ASC');
		$expected = array(
			array('CategoryThread' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
					'ParentCategory' => array('id' => null, 'parent_id' => null, 'name' => null, 'created' => null, 'updated' => null, 'ParentCategory' => array())),
			array('CategoryThread' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
					'ParentCategory' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array())),
			array('CategoryThread' => array('id' => 3, 'parent_id' => 2, 'name' => 'Category 1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
					'ParentCategory' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array()))),
			array('CategoryThread' => array('id' => 4, 'parent_id' => 3, 'name' => 'Category 1.1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
					'ParentCategory' => array('id' => 3, 'parent_id' => 2, 'name' => 'Category 1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array())))),
			array('CategoryThread' => array('id' => 5, 'parent_id' => 4, 'name' => 'Category 1.1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
					'ParentCategory' => array('id' => 4, 'parent_id' => 3, 'name' => 'Category 1.1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 3, 'parent_id' => 2, 'name' => 'Category 1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array()))))),
			array('CategoryThread' => array('id' => 6, 'parent_id' => 5, 'name' => 'Category 2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
					'ParentCategory' => array('id' => 5, 'parent_id' => 4, 'name' => 'Category 1.1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 4, 'parent_id' => 3, 'name' => 'Category 1.1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 3, 'parent_id' => 2, 'name' => 'Category 1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array())))))),
			array('CategoryThread' => array('id' => 7, 'parent_id' => 6, 'name' => 'Category 2.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
					'ParentCategory' => array('id' => 6, 'parent_id' => 5, 'name' => 'Category 2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 5, 'parent_id' => 4, 'name' => 'Category 1.1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 4, 'parent_id' => 3, 'name' => 'Category 1.1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 3, 'parent_id' => 2, 'name' => 'Category 1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'))))))));
		$this->db->fullDebug = $fullDebug;
		$this->assertEqual($result, $expected);
	}
/**
 * testConditionalNumerics method
 *
 * @access public
 * @return void
 */
	function testConditionalNumerics() {
		$this->loadFixtures('NumericArticle');
		$NumericArticle =& new NumericArticle();
		$data = array('title' => '12345abcde');
		$result = $NumericArticle->find($data);
		$this->assertTrue(!empty($result));

		// @TODO: make this pass in Cake 2.0 with passing the column around in db->value()
		// SELECT * from articles WHERE title = 12345 // will find the article with title = 12345abcde, too : /
		// $data = array('title' => '12345');
		// $result = $NumericArticle->find($data);
		// $this->assertTrue(empty($result));
	}
/**
 * testFindAll method
 *
 * @access public
 * @return void
 */
	function testFindAll() {
		$this->loadFixtures('User');
		$TestModel =& new User();
		$TestModel->cacheQueries = false;

		$result = $TestModel->find('all');
		$expected = array(
				array('User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31')),
				array('User' => array('id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31')),
				array('User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31')),
				array('User' => array('id' => '4', 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31')));
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all', array('conditions' => 'User.id > 2'));
		$expected = array(
				array('User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31')),
				array('User' => array('id' => '4', 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31')));
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all', array('conditions' => array('User.id !=' => '0', 'User.user LIKE' => '%arr%')));
		$expected = array(
				array('User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31')),
				array('User' => array('id' => '4', 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31')));
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all', array('conditions' => array('User.id' => '0')));
		$expected = array();
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all', array('conditions' => array('or' => array('User.id' => '0', 'User.user LIKE' => '%a%'))));
		$expected = array(
				array('User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31')),
				array('User' => array('id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31')),
				array('User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31')),
				array('User' => array('id' => '4', 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31')));
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all', array('fields' => 'User.id, User.user'));
		$expected = array(
				array('User' => array('id' => '1', 'user' => 'mariano')),
				array('User' => array('id' => '2', 'user' => 'nate')),
				array('User' => array('id' => '3', 'user' => 'larry')),
				array('User' => array('id' => '4', 'user' => 'garrett')));
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all', array('fields' => 'User.user', 'order' => 'User.user ASC'));
		$expected = array(
				array('User' => array('user' => 'garrett')),
				array('User' => array('user' => 'larry')),
				array('User' => array('user' => 'mariano')),
				array('User' => array('user' => 'nate')));
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all', array('fields' => 'User.user', 'order' => 'User.user DESC'));
		$expected = array(
				array('User' => array('user' => 'nate')),
				array('User' => array('user' => 'mariano')),
				array('User' => array('user' => 'larry')),
				array('User' => array('user' => 'garrett')));
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all', array('limit' => 3, 'page' => 1));

		$expected = array(
				array('User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31')),
				array('User' => array('id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31')),
				array('User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31')));
		$this->assertEqual($result, $expected);

		$ids = array(4 => 1, 5 => 3);
		$result = $TestModel->find('all', array('conditions' => array('User.id' => $ids)));
		$expected = array(
			array('User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31')),
			array('User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31')),
		);
		$this->assertEqual($result, $expected);

		// These tests are expected to fail on SQL Server since the LIMIT/OFFSET
		// hack can't handle small record counts.
		if ($this->db->config['driver'] != 'mssql') {
			$result = $TestModel->find('all', array('limit' => 3, 'page' => 2));
			$expected = array(
					array('User' => array('id' => '4', 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31')));
			$this->assertEqual($result, $expected);

			$result = $TestModel->find('all', array('limit' => 3, 'page' => 3));
			$expected = array();
			$this->assertEqual($result, $expected);
		}
	}
/**
 * testGenerateList method
 *
 * @access public
 * @return void
 */
	function testGenerateList() {
		$this->loadFixtures('Article', 'Apple', 'Post', 'Author', 'User');

		$TestModel =& new Article();
		$TestModel->displayField = 'title';

		$result = $TestModel->find('list', array('order' => 'Article.title ASC'));
		$expected = array(1 => 'First Article', 2 => 'Second Article', 3 => 'Third Article');
		$this->assertEqual($result, $expected);

		$db =& ConnectionManager::getDataSource('test_suite');
		if ($db->config['driver'] == 'mysql') {
			$result = $TestModel->find('list', array('order' => array('FIELD(Article.id, 3, 2) ASC', 'Article.title ASC')));
			$expected = array(1 => 'First Article', 3 => 'Third Article', 2 => 'Second Article');
			$this->assertEqual($result, $expected);
		}

		$result = Set::combine($TestModel->find('all', array('order' => 'Article.title ASC', 'fields' => array('id', 'title'))), '{n}.Article.id', '{n}.Article.title');
		$expected = array(1 => 'First Article', 2 => 'Second Article', 3 => 'Third Article');
		$this->assertEqual($result, $expected);

		$result = Set::combine($TestModel->find('all', array('order' => 'Article.title ASC')), '{n}.Article.id', '{n}.Article');
		$expected = array(
				1 => array('id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				2 => array('id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
				3 => array('id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'));
		$this->assertEqual($result, $expected);

		$result = Set::combine($TestModel->find('all', array('order' => 'Article.title ASC')), '{n}.Article.id', '{n}.Article', '{n}.Article.user_id');
		$expected = array(1 => array(
						1 => array('id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
						3 => array('id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31')),
				3 => array(2 => array('id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31')));
		$this->assertEqual($result, $expected);

		$result = Set::combine($TestModel->find('all', array('order' => 'Article.title ASC', 'fields' => array('id', 'title', 'user_id'))), '{n}.Article.id', '{n}.Article.title', '{n}.Article.user_id');
		$expected = array(1 => array(1 => 'First Article', 3 => 'Third Article'), 3 => array(2 => 'Second Article'));
		$this->assertEqual($result, $expected);

		$TestModel =& new Apple();
		$expected = array(1 => 'Red Apple 1', 2 => 'Bright Red Apple', 3 => 'green blue', 4 => 'Test Name', 5 => 'Blue Green', 6 => 'My new apple', 7 => 'Some odd color');

		$this->assertEqual($TestModel->find('list'), $expected);
		$this->assertEqual($TestModel->Parent->find('list'), $expected);

		$TestModel =& new Post();
		$result = $TestModel->find('list', array('fields' => 'Post.title'));
		$expected = array(1 => 'First Post', 2 => 'Second Post', 3 => 'Third Post');
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('list', array('fields' => 'title'));
		$expected = array(1 => 'First Post', 2 => 'Second Post', 3 => 'Third Post');
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('list', array('fields' => array('title', 'id')));
		$expected = array('First Post' => '1', 'Second Post' => '2', 'Third Post' => '3');
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('list', array('fields' => array('title', 'id', 'created')));
		$expected = array(
			'2007-03-18 10:39:23' => array('First Post' => '1'),
			'2007-03-18 10:41:23' => array('Second Post' => '2'),
			'2007-03-18 10:43:23' => array('Third Post' => '3'),
		);
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('list', array('fields' => array('Post.body')));
		$expected = array(1 => 'First Post Body', 2 => 'Second Post Body', 3 => 'Third Post Body');
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('list', array('fields' => array('Post.title', 'Post.body')));
		$expected = array('First Post' => 'First Post Body', 'Second Post' => 'Second Post Body', 'Third Post' => 'Third Post Body');
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('list', array('fields' => array('Post.id', 'Post.title', 'Author.user'), 'recursive' => 1));
		$expected = array('mariano' => array(1 => 'First Post', 3 => 'Third Post'), 'larry' => array(2 => 'Second Post'));
		$this->assertEqual($result, $expected);

		$TestModel =& new User();
		$result = $TestModel->find('list', array('fields' => array('User.user', 'User.password')));
		$expected = array('mariano' => '5f4dcc3b5aa765d61d8327deb882cf99', 'nate' => '5f4dcc3b5aa765d61d8327deb882cf99', 'larry' => '5f4dcc3b5aa765d61d8327deb882cf99', 'garrett' => '5f4dcc3b5aa765d61d8327deb882cf99');
		$this->assertEqual($result, $expected);

		$TestModel =& new ModifiedAuthor();
		$result = $TestModel->find('list', array('fields' => array('Author.id', 'Author.user')));
		$expected = array(1 => 'mariano (CakePHP)', 2 => 'nate (CakePHP)', 3 => 'larry (CakePHP)', 4 => 'garrett (CakePHP)');
		$this->assertEqual($result, $expected);
	}
/**
 * testRecordExists method
 *
 * @access public
 * @return void
 */
	function testRecordExists() {
		$this->loadFixtures('User');
		$TestModel =& new User();

		$this->assertFalse($TestModel->exists());
		$TestModel->read(null, 1);
		$this->assertTrue($TestModel->exists());
		$TestModel->create();
		$this->assertFalse($TestModel->exists());
		$TestModel->id = 4;
		$this->assertTrue($TestModel->exists());

		$TestModel =& new TheVoid();
		$this->assertFalse($TestModel->exists());
		$TestModel->id = 5;
		$this->assertFalse($TestModel->exists());
	}
/**
 * testFindField method
 *
 * @access public
 * @return void
 */
	function testFindField() {
		$this->loadFixtures('User');
		$TestModel =& new User();

		$TestModel->id = 1;
		$result = $TestModel->field('user');
		$this->assertEqual($result, 'mariano');

		$result = $TestModel->field('User.user');
		$this->assertEqual($result, 'mariano');

		$TestModel->id = false;
		$result = $TestModel->field('user', array('user' => 'mariano'));
		$this->assertEqual($result, 'mariano');

		$result = $TestModel->field('COUNT(*) AS count', true);
		$this->assertEqual($result, 4);

		$result = $TestModel->field('COUNT(*)', true);
		$this->assertEqual($result, 4);
	}
/**
 * testFindUnique method
 *
 * @access public
 * @return void
 */
	function testFindUnique() {
		$this->loadFixtures('User');
		$TestModel =& new User();

		$this->assertFalse($TestModel->isUnique(array('user' => 'nate')));
		$TestModel->id = 2;
		$this->assertTrue($TestModel->isUnique(array('user' => 'nate')));
		$this->assertFalse($TestModel->isUnique(array('user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99')));
	}
/**
 * testUpdateExisting method
 *
 * @access public
 * @return void
 */
	function testUpdateExisting() {
		$this->loadFixtures('User', 'Article', 'Comment');
		$TestModel =& new User();
		$TestModel->create();

		$TestModel->save(array('User' => array('user' => 'some user', 'password' => 'some password')));
		$this->assertTrue(is_int($TestModel->id) || (intval($TestModel->id) === 5));
		$id = $TestModel->id;

		$TestModel->save(array('User' => array('user' => 'updated user')));
		$this->assertEqual($TestModel->id, $id);

		$result = $TestModel->findById($id);
		$this->assertEqual($result['User']['user'], 'updated user');
		$this->assertEqual($result['User']['password'], 'some password');

		$Article =& new Article();
		$Comment =& new Comment();
		$data = array('Comment' => array('id' => 1, 'comment' => 'First Comment for First Article'),
				'Article' => array('id' => 2, 'title' => 'Second Article'));

		$result = $Article->save($data);
		$this->assertTrue($result);

		$result = $Comment->save($data);
		$this->assertTrue($result);
	}
/**
 * testUpdateMultiple method
 *
 * @access public
 * @return void
 */
	function testUpdateMultiple() {
		$this->loadFixtures('Comment', 'Article', 'User', 'CategoryThread');
		$TestModel =& new Comment();
		$result = Set::extract($TestModel->find('all'), '{n}.Comment.user_id');
		$expected = array('2', '4', '1', '1', '1', '2');
		$this->assertEqual($result, $expected);

		$TestModel->updateAll(array('Comment.user_id' => 5), array('Comment.user_id' => 2));
		$result = Set::combine($TestModel->find('all'), '{n}.Comment.id', '{n}.Comment.user_id');
		$expected = array(1 => 5, 2 => 4, 3 => 1, 4 => 1, 5 => 1, 6 => 5);
		$this->assertEqual($result, $expected);

		$result = $TestModel->updateAll(array('Comment.comment' => "'Updated today'"), array('Comment.user_id' => 5));
		$this->assertTrue($result);
		$result = Set::extract($TestModel->find('all', array('conditions' => array('Comment.user_id' => 5))), '{n}.Comment.comment');
		$expected = array_fill(0, 2, 'Updated today');
		$this->assertEqual($result, $expected);
	}
/**
 * testUpdateWithCalculation method
 *
 * @access public
 * @return void
 */
	function testUpdateWithCalculation() {
		Configure::write('foo', true);

		$this->loadFixtures('DataTest');
		$model =& new DataTest();
		$result = $model->saveAll(array(
			array('count' => 5, 'float' => 1.1),
			array('count' => 3, 'float' => 1.2),
			array('count' => 4, 'float' => 1.3),
			array('count' => 1, 'float' => 2.0),
		));
		$this->assertTrue($result);

		$result = Set::extract('/DataTest/count', $model->find('all', array('fields' => 'count')));
		$this->assertEqual($result, array(5, 3, 4, 1));

		$this->assertTrue($model->updateAll(array('count' => 'count + 2')));
		$result = Set::extract('/DataTest/count', $model->find('all', array('fields' => 'count')));
		$this->assertEqual($result, array(7, 5, 6, 3));

		$this->assertTrue($model->updateAll(array('DataTest.count' => 'DataTest.count - 1')));
		$result = Set::extract('/DataTest/count', $model->find('all', array('fields' => 'count')));
		$this->assertEqual($result, array(6, 4, 5, 2));
	}
/**
 * testBindUnbind method
 *
 * @access public
 * @return void
 */
	function testBindUnbind() {
		$this->loadFixtures('User', 'Comment', 'FeatureSet');
		$TestModel =& new User();

		$result = $TestModel->hasMany;
		$expected = array();
		$this->assertEqual($result, $expected);

		$result = $TestModel->bindModel(array('hasMany' => array('Comment')));
		$this->assertTrue($result);

		$result = $TestModel->find('all', array('fields' => 'User.id, User.user'));
		$expected = array(
			array('User' => array('id' => '1', 'user' => 'mariano'), 'Comment' => array(
				array('id' => '3', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Third Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'),
				array('id' => '4', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Fourth Comment for First Article', 'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'),
				array('id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'))),
			array('User' => array('id' => '2', 'user' => 'nate'), 'Comment' => array(
				array('id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
				array('id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31'))),
			array('User' => array('id' => '3', 'user' => 'larry'), 'Comment' => array()),
			array('User' => array('id' => '4', 'user' => 'garrett'), 'Comment' => array(
				array('id' => '2', 'article_id' => '1', 'user_id' => '4', 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'))));
		$this->assertEqual($result, $expected);

		$TestModel->resetAssociations();
		$result = $TestModel->hasMany;
		$this->assertEqual($result, array());

		$result = $TestModel->bindModel(array('hasMany' => array('Comment')), false);
		$this->assertTrue($result);

		$result = $TestModel->find('all', array('fields' => 'User.id, User.user'));
		$expected = array(
			array('User' => array('id' => '1', 'user' => 'mariano'), 'Comment' => array(
				array('id' => '3', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Third Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'),
				array('id' => '4', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Fourth Comment for First Article', 'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'),
				array('id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'))),
			array('User' => array('id' => '2', 'user' => 'nate'), 'Comment' => array(
				array('id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
				array('id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31'))),
			array('User' => array('id' => '3', 'user' => 'larry'), 'Comment' => array()),
			array('User' => array('id' => '4', 'user' => 'garrett'), 'Comment' => array(
				array('id' => '2', 'article_id' => '1', 'user_id' => '4', 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'))));
		$this->assertEqual($result, $expected);

		$result = $TestModel->hasMany;
		$expected = array('Comment' => array('className' => 'Comment', 'foreignKey' => 'user_id', 'conditions' => null, 'fields' => null, 'order' => null, 'limit' => null, 'offset' => null, 'dependent' => null, 'exclusive' => null, 'finderQuery' => null, 'counterQuery' => null) );
		$this->assertEqual($result, $expected);

		$result = $TestModel->unbindModel(array('hasMany' => array('Comment')));
		$this->assertTrue($result);

		$result = $TestModel->hasMany;
		$expected = array();
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all', array('fields' => 'User.id, User.user'));
		$expected = array(
			array('User' => array('id' => '1', 'user' => 'mariano')),
			array('User' => array('id' => '2', 'user' => 'nate')),
			array('User' => array('id' => '3', 'user' => 'larry')),
			array('User' => array('id' => '4', 'user' => 'garrett')));
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all', array('fields' => 'User.id, User.user'));
		$expected = array(
			array('User' => array('id' => '1', 'user' => 'mariano'), 'Comment' => array(
				array('id' => '3', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Third Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'),
				array('id' => '4', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Fourth Comment for First Article', 'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'),
				array('id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'))),
			array('User' => array('id' => '2', 'user' => 'nate'), 'Comment' => array(
				array('id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
				array('id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31'))),
			array('User' => array('id' => '3', 'user' => 'larry'), 'Comment' => array()),
			array('User' => array('id' => '4', 'user' => 'garrett'), 'Comment' => array(
				array('id' => '2', 'article_id' => '1', 'user_id' => '4', 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'))));
		$this->assertEqual($result, $expected);

		$result = $TestModel->unbindModel(array('hasMany' => array('Comment')), false);
		$this->assertTrue($result);

		$result = $TestModel->find('all', array('fields' => 'User.id, User.user'));
		$expected = array(
			array('User' => array('id' => '1', 'user' => 'mariano')),
			array('User' => array('id' => '2', 'user' => 'nate')),
			array('User' => array('id' => '3', 'user' => 'larry')),
			array('User' => array('id' => '4', 'user' => 'garrett')));
		$this->assertEqual($result, $expected);

		$result = $TestModel->hasMany;
		$expected = array();
		$this->assertEqual($result, $expected);

		$result = $TestModel->bindModel(array('hasMany' => array('Comment' => array('className' => 'Comment', 'conditions' => 'Comment.published = \'Y\'') )));
		$this->assertTrue($result);

		$result = $TestModel->find('all', array('fields' => 'User.id, User.user'));
		$expected = array(
			array('User' => array('id' => '1', 'user' => 'mariano'), 'Comment' => array(
				array('id' => '3', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Third Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'),
				array('id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'))),
			array('User' => array('id' => '2', 'user' => 'nate'), 'Comment' => array(
				array('id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
				array('id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31'))),
			array('User' => array('id' => '3', 'user' => 'larry'), 'Comment' => array()),
			array('User' => array('id' => '4', 'user' => 'garrett'), 'Comment' => array(
				array('id' => '2', 'article_id' => '1', 'user_id' => '4', 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'))));
		$this->assertEqual($result, $expected);

		$TestModel2 =& new DeviceType();

		$expected = array('className' => 'FeatureSet', 'foreignKey' => 'feature_set_id', 'conditions' => '', 'fields' => '', 'order' => '', 'counterCache' => '');
		$this->assertEqual($TestModel2->belongsTo['FeatureSet'], $expected);

		$TestModel2->bind('FeatureSet', array('conditions' => array('active' => true)));
		$expected['conditions'] = array('active' => true);
		$this->assertEqual($TestModel2->belongsTo['FeatureSet'], $expected);

		$TestModel2->bind('FeatureSet', array('foreignKey' => false, 'conditions' => array('Feature.name' => 'DeviceType.name')));
		$expected['conditions'] = array('Feature.name' => 'DeviceType.name');
		$expected['foreignKey'] = false;
		$this->assertEqual($TestModel2->belongsTo['FeatureSet'], $expected);

		$TestModel2->bind('NewFeatureSet', array('type' => 'hasMany', 'className' => 'FeatureSet', 'conditions' => array('active' => true)));
		$expected = array('className' => 'FeatureSet', 'conditions' => array('active' => true), 'foreignKey' => 'device_type_id', 'fields' => '', 'order' => '', 'limit' => '', 'offset' => '', 'dependent' => '', 'exclusive' => '', 'finderQuery' => '', 'counterQuery' => '');
		$this->assertEqual($TestModel2->hasMany['NewFeatureSet'], $expected);
		$this->assertTrue(is_object($TestModel2->NewFeatureSet));
	}
/**
 * testBindMultipleTimes method
 *
 * @access public
 * @return void
 */
	function testBindMultipleTimes() {
		$this->loadFixtures('User', 'Comment', 'Article');
		$TestModel =& new User();

		$result = $TestModel->hasMany;
		$expected = array();
		$this->assertEqual($result, $expected);

		$result = $TestModel->bindModel(array('hasMany' => array('Items' => array('className' => 'Comment'))));
		$this->assertTrue($result);

		$result = $TestModel->find('all', array('fields' => 'User.id, User.user'));
		$expected = array(
			array('User' => array('id' => '1', 'user' => 'mariano'), 'Items' => array(
				array('id' => '3', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Third Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'),
				array('id' => '4', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Fourth Comment for First Article', 'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'),
				array('id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'))),
			array('User' => array('id' => '2', 'user' => 'nate'), 'Items' => array(
				array('id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
				array('id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31'))),
			array('User' => array('id' => '3', 'user' => 'larry'), 'Items' => array()),
			array('User' => array('id' => '4', 'user' => 'garrett'), 'Items' => array(
				array('id' => '2', 'article_id' => '1', 'user_id' => '4', 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'))));
		$this->assertEqual($result, $expected);

		$result = $TestModel->bindModel(array('hasMany' => array('Items' => array('className' => 'Article'))));
		$this->assertTrue($result);

		$result = $TestModel->find('all', array('fields' => 'User.id, User.user'));
		$expected = array(
			array('User' => array('id' => '1', 'user' => 'mariano'), 'Items' => array(
				array('id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				array('id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'))),
			array('User' => array('id' => '2', 'user' => 'nate'), 'Items' => array()),
			array('User' => array('id' => '3', 'user' => 'larry'), 'Items' => array(
				array('id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'))),
			array('User' => array('id' => '4', 'user' => 'garrett'), 'Items' => array()));
		$this->assertEqual($result, $expected);
	}
/**
 * testFindCount method
 *
 * @access public
 * @return void
 */
	function testFindCount() {
		$this->loadFixtures('User', 'Project');

		$TestModel =& new User();
		$result = $TestModel->find('count');
		$this->assertEqual($result, 4);

		$fullDebug = $this->db->fullDebug;
		$this->db->fullDebug = true;
		$TestModel->order = 'User.id';
		$this->db->_queriesLog = array();
		$result = $TestModel->find('count');
		$this->assertEqual($result, 4);

		$this->assertTrue(isset($this->db->_queriesLog[0]['query']));
		$this->assertNoPattern('/ORDER\s+BY/', $this->db->_queriesLog[0]['query']);

		$this->db->_queriesLog = array();
		$this->db->fullDebug = $fullDebug;

		$TestModel =& new Project();
		$TestModel->create(array('name' => 'project')) && $TestModel->save();
		$TestModel->create(array('name' => 'project')) && $TestModel->save();
		$TestModel->create(array('name' => 'project')) && $TestModel->save();

		$result = $TestModel->find('count', array('fields' => 'DISTINCT Project.name'));
		$this->assertEqual($result, 4);
	}
/**
 * testFindMagic method
 *
 * @access public
 * @return void
 */
	function testFindMagic() {
		$this->loadFixtures('User');
		$TestModel =& new User();

		$result = $TestModel->findByUser('mariano');
		$expected = array('User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'));
		$this->assertEqual($result, $expected);

		$result = $TestModel->findByPassword('5f4dcc3b5aa765d61d8327deb882cf99');
		$expected = array('User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'));
		$this->assertEqual($result, $expected);
	}
/**
 * testRead method
 *
 * @access public
 * @return void
 */
	function testRead() {
		$this->loadFixtures('User', 'Article');
		$TestModel =& new User();

		$result = $TestModel->read();
		$this->assertFalse($result);

		$TestModel->id = 2;
		$result = $TestModel->read();
		$expected = array('User' => array('id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'));
		$this->assertEqual($result, $expected);

		$result = $TestModel->read(null, 2);
		$expected = array('User' => array('id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'));
		$this->assertEqual($result, $expected);

		$TestModel->id = 2;
		$result = $TestModel->read(array('id', 'user'));
		$expected = array('User' => array('id' => '2', 'user' => 'nate'));
		$this->assertEqual($result, $expected);

		$result = $TestModel->read('id, user', 2);
		$expected = array('User' => array('id' => '2', 'user' => 'nate'));
		$this->assertEqual($result, $expected);

		$result = $TestModel->bindModel(array('hasMany' => array('Article')));
		$this->assertTrue($result);

		$TestModel->id = 1;
		$result = $TestModel->read('id, user');
		$expected = array('User' => array('id' => '1', 'user' => 'mariano'),
			'Article' => array(
				array('id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31' ),
				array('id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31' )));
		$this->assertEqual($result, $expected);
	}
/**
 * testRecursiveRead method
 *
 * @access public
 * @return void
 */
	function testRecursiveRead() {
		$this->loadFixtures('User', 'Article', 'Comment', 'Tag', 'ArticlesTag', 'Featured', 'ArticleFeatured');
		$TestModel =& new User();

		$result = $TestModel->bindModel(array('hasMany' => array('Article')), false);
		$this->assertTrue($result);

		$TestModel->recursive = 0;
		$result = $TestModel->read('id, user', 1);
		$expected = array(
			'User' => array('id' => '1', 'user' => 'mariano'),
		);
		$this->assertEqual($result, $expected);

		$TestModel->recursive = 1;
		$result = $TestModel->read('id, user', 1);
		$expected = array('User' => array('id' => '1', 'user' => 'mariano'),
			'Article' => array(
				array('id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31' ),
				array('id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31' )));
		$this->assertEqual($result, $expected);

		$TestModel->recursive = 2;
		$result = $TestModel->read('id, user', 3);
		$expected = array('User' => array('id' => '3', 'user' => 'larry'),
			'Article' => array(array('id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
					'User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'),
					'Comment' => array(array('id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'),
						array('id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31')),
					'Tag' => array(
						array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
						array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')))));
		$this->assertEqual($result, $expected);
	}
/**
 * @todo Figure out why Featured is not getting truncated properly
 */
	function testRecursiveFindAll() {
		$this->db->truncate(new Featured());

		$this->loadFixtures('User', 'Article', 'Comment', 'Tag', 'ArticlesTag', 'Attachment', 'ArticleFeatured', 'Featured', 'Category');
		$TestModel =& new Article();

		$result = $TestModel->find('all', array('conditions' => array('Article.user_id' => 1)));
		$expected = array(
			array(
				'Article' => array('id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				'User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'Comment' => array(
					array('id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
					array('id' => '2', 'article_id' => '1', 'user_id' => '4', 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'),
					array('id' => '3', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Third Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'),
					array('id' => '4', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Fourth Comment for First Article', 'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31')
				),
				'Tag' => array(
					array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
					array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31')
				)
			),
			array(
				'Article' => array('id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'),
				'User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'Comment' => array(),
				'Tag' => array()
			)
		);
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all', array('conditions' => array('Article.user_id' => 3), 'limit' => 1, 'recursive' => 2));
		$expected = array(
			array(
				'Article' => array(
					'id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
				),
				'User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'),
				'Comment' => array(
					array(
						'id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
						'Article' => array('id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
						'User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
						'Attachment' => array('id' => '1', 'comment_id' => 5, 'attachment' => 'attachment.zip', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31')
					),
					array(
						'id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31',
						'Article' => array('id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
						'User' => array('id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'),
						'Attachment' => false
					)
				),
				'Tag' => array(
					array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
					array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
				)
			)
		);
		$this->assertEqual($result, $expected);

		$Featured = new Featured();

		$Featured->recursive = 2;
		$Featured->bindModel(array(
			'belongsTo' => array(
				'ArticleFeatured' => array(
					'conditions' => "ArticleFeatured.published = 'Y'",
					'fields' => 'id, title, user_id, published'
				)
			)
		));

		$Featured->ArticleFeatured->unbindModel(array(
			'hasMany' => array('Attachment', 'Comment'),
			'hasAndBelongsToMany' => array('Tag'))
		);

		$orderBy = 'ArticleFeatured.id ASC';
		$result = $Featured->find('all', array('order' => $orderBy, 'limit' => 3));

		$expected = array(
			array('Featured' => array('id' => '1', 'article_featured_id' => '1', 'category_id' => '1', 'published_date' => '2007-03-31 10:39:23', 'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				'ArticleFeatured' => array('id' => '1', 'title' => 'First Article', 'user_id' => '1', 'published' => 'Y',
					'User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
					'Category' => array(),
					'Featured' => array('id' => '1', 'article_featured_id' => '1', 'category_id' => '1', 'published_date' => '2007-03-31 10:39:23', 'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31')),
				'Category' => array('id' => '1', 'parent_id' => '0', 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31')),
			array('Featured' => array('id' => '2', 'article_featured_id' => '2', 'category_id' => '1', 'published_date' => '2007-03-31 10:39:23', 'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				'ArticleFeatured' => array('id' => '2', 'title' => 'Second Article', 'user_id' => '3', 'published' => 'Y',
					'User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'),
					'Category' => array(),
					'Featured' => array('id' => '2', 'article_featured_id' => '2', 'category_id' => '1', 'published_date' => '2007-03-31 10:39:23', 'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31')),
				'Category' => array( 'id' => '1', 'parent_id' => '0', 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31')
			)
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testRecursiveFindAllWithLimit method
 *
 * @access public
 * @return void
 */
	function testRecursiveFindAllWithLimit() {
		$this->loadFixtures('Article', 'User', 'Tag', 'ArticlesTag', 'Comment', 'Attachment');
		$TestModel =& new Article();

		$TestModel->hasMany['Comment']['limit'] = 2;

		$result = $TestModel->find('all', array('conditions' => array('Article.user_id' => 1)));
		$expected = array(
			array(
				'Article' => array('id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				'User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'Comment' => array(
					array('id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
					array('id' => '2', 'article_id' => '1', 'user_id' => '4', 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'),
				),
				'Tag' => array(
					array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
					array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31')
				)
			),
			array(
				'Article' => array('id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'),
				'User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'Comment' => array(),
				'Tag' => array()
			)
		);
		$this->assertEqual($result, $expected);

		$TestModel->hasMany['Comment']['limit'] = 1;

		$result = $TestModel->find('all', array('conditions' => array('Article.user_id' => 3), 'limit' => 1, 'recursive' => 2));
		$expected = array(
			array(
				'Article' => array('id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
				'User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'),
				'Comment' => array(
					array(
						'id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
						'Article' => array('id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
						'User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
						'Attachment' => array('id' => '1', 'comment_id' => 5, 'attachment' => 'attachment.zip', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31')
					)
				),
				'Tag' => array(
					array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
					array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
				)
			)
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testAssociationAfterFind method
 *
 * @access public
 * @return void
 */
	function testAssociationAfterFind() {
		$this->loadFixtures('Post', 'Author', 'Comment');
		$TestModel =& new Post();
		$result = $TestModel->find('all');
		$expected = array(
			array(
				'Post' => array('id' => '1', 'author_id' => '1', 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				'Author' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31', 'test' => 'working'),
			), array(
				'Post' => array('id' => '2', 'author_id' => '3', 'title' => 'Second Post', 'body' => 'Second Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
				'Author' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31', 'test' => 'working'),
			), array(
				'Post' => array('id' => '3', 'author_id' => '1', 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'),
				'Author' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31', 'test' => 'working')
			)
		);
		$this->assertEqual($result, $expected);
		unset($TestModel);

		$Author =& new Author();
		$Author->Post->bindModel(array(
			'hasMany' => array(
				'Comment' => array(
					'className' => 'ModifiedComment',
					'foreignKey' => 'article_id',
				)
		)));
		$result = $Author->find('all', array(
			'conditions' => array('Author.id' => 1),
			'recursive' => 2
		));
		$expected = array(
			'id' => 1,
			'article_id' => 1,
			'user_id' => 2,
			'comment' => 'First Comment for First Article',
			'published' => 'Y',
			'created' => '2007-03-18 10:45:23',
			'updated' => '2007-03-18 10:47:31',
			'callback' => 'Fire'
		);
		$this->assertEqual($result[0]['Post'][0]['Comment'][0], $expected);
	}
/**
 * Tests that callbacks can be properly disabled
 *
 * @access public
 * @return void
 */
	function testCallbackDisabling() {
		$this->loadFixtures('Author');
		$TestModel = new ModifiedAuthor();

		$result = Set::extract($TestModel->find('all'), '/Author/user');
		$expected = array('mariano (CakePHP)', 'nate (CakePHP)', 'larry (CakePHP)', 'garrett (CakePHP)');
		$this->assertEqual($result, $expected);

		$result = Set::extract($TestModel->find('all', array('callbacks' => 'after')), '/Author/user');
		$expected = array('mariano (CakePHP)', 'nate (CakePHP)', 'larry (CakePHP)', 'garrett (CakePHP)');
		$this->assertEqual($result, $expected);

		$result = Set::extract($TestModel->find('all', array('callbacks' => 'before')), '/Author/user');
		$expected = array('mariano', 'nate', 'larry', 'garrett');
		$this->assertEqual($result, $expected);

		$result = Set::extract($TestModel->find('all', array('callbacks' => false)), '/Author/user');
		$expected = array('mariano', 'nate', 'larry', 'garrett');
		$this->assertEqual($result, $expected);
	}
/**
 * testValidatesBackwards method
 *
 * @access public
 * @return void
 */
	function testValidatesBackwards() {
		$TestModel =& new TestValidate();

		$TestModel->validate = array(
			'user_id' => VALID_NUMBER,
			'title' => VALID_NOT_EMPTY,
			'body' => VALID_NOT_EMPTY
		);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => '', 'body' => ''));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 'title', 'body' => ''));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('user_id' => '', 'title' => 'title', 'body' => 'body'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('user_id' => 'not a number', 'title' => 'title', 'body' => 'body'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 'title', 'body' => 'body'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);
	}
/**
 * testValidates method
 *
 * @access public
 * @return void
 */
	function testValidates() {
		$TestModel =& new TestValidate();

		$TestModel->validate = array(
			'user_id' => VALID_NUMBER,
			'title' => array('allowEmpty' => false, 'rule' => VALID_NOT_EMPTY),
			'body' => VALID_NOT_EMPTY
		);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => '', 'body' => 'body'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 'title', 'body' => 'body'));
		$result = $TestModel->create($data) && $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => '0', 'body' => 'body'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate['modified'] = array('allowEmpty' => true, 'rule' => 'date');

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body', 'modified' => ''));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body', 'modified' => '2007-05-01'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body', 'modified' => 'invalid-date-here'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body', 'modified' => 0));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body', 'modified' => '0'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$TestModel->validate['modified'] = array('allowEmpty' => false, 'rule' => 'date');

		$data = array('TestValidate' => array('modified' => null));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('modified' => false));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('modified' => ''));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('modified' => '2007-05-01'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate['slug'] = array('allowEmpty' => false, 'rule' => array('maxLength', 45));

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body', 'slug' => ''));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body', 'slug' => 'slug-right-here'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body', 'slug' => 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$TestModel->validate = array(
			'number' => array('rule' => 'validateNumber', 'min' => 3, 'max' => 5),
			'title' => array('allowEmpty' => false, 'rule' => VALID_NOT_EMPTY)
		);

		$data = array('TestValidate' => array('title' => 'title', 'number' => '0'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'title', 'number' => 0));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'title', 'number' => '3'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array('title' => 'title', 'number' => 3));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate = array(
			'number' => array('rule' => 'validateNumber', 'min' => 5, 'max' => 10),
			'title' => array('allowEmpty' => false, 'rule' => VALID_NOT_EMPTY)
		);

		$data = array('TestValidate' => array('title' => 'title', 'number' => '3'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'title', 'number' => 3));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$TestModel->validate = array(
			'title' => array('allowEmpty' => false, 'rule' => 'validateTitle')
		);

		$data = array('TestValidate' => array('title' => ''));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'new title'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'title-new'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate = array('title' => array('allowEmpty' => true, 'rule' => 'validateTitle'));
		$data = array('TestValidate' => array('title' => ''));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate = array('title' => array('length' => array('allowEmpty' => true, 'rule' => array('maxLength', 10))));
		$data = array('TestValidate' => array('title' => ''));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate = array('title' => array('rule' => array('userDefined', 'Article', 'titleDuplicate')));
		$data = array('TestValidate' => array('title' => 'My Article Title'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'My Article With a Different Title'));
		$result = $TestModel->create($data);
		$this->assertTrue($result);
		$result = $TestModel->validates();
		$this->assertTrue($result);

		$TestModel->validate = array(
			'title' => array(
				'tooShort' => array('rule' => array('minLength', 50)),
				'onlyLetters' => array('rule' => '/^[a-z]+$/i')
			),
		);
		$data = array('TestValidate' => array(
			'title' => 'I am a short string'
		));
		$TestModel->create($data);
		$result = $TestModel->validates();
		$this->assertFalse($result);
		$result = $TestModel->validationErrors;
		$expected = array(
			'title' => 'onlyLetters'
		);
		$this->assertEqual($result, $expected);

		$TestModel->validate = array(
			'title' => array(
				'tooShort' => array('rule' => array('minLength', 50), 'last' => true),
				'onlyLetters' => array('rule' => '/^[a-z]+$/i')
			),
		);
		$data = array('TestValidate' => array(
			'title' => 'I am a short string'
		));
		$TestModel->create($data);
		$result = $TestModel->validates();
		$this->assertFalse($result);
		$result = $TestModel->validationErrors;
		$expected = array(
			'title' => 'tooShort'
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testSaveField method
 *
 * @access public
 * @return void
 */
	function testSaveField() {
		$this->loadFixtures('Article');
		$TestModel =& new Article();

		$TestModel->id = 1;
		$result = $TestModel->saveField('title', 'New First Article');
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body'), 1);
		$expected = array('Article' => array(
			'id' => '1', 'user_id' => '1', 'title' => 'New First Article', 'body' => 'First Article Body'
		));
		$this->assertEqual($result, $expected);

		$TestModel->id = 1;
		$result = $TestModel->saveField('title', '');
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body'), 1);
		$expected = array('Article' => array(
			'id' => '1', 'user_id' => '1', 'title' => '', 'body' => 'First Article Body'
		));
		$result['Article']['title'] = trim($result['Article']['title']);
		$this->assertEqual($result, $expected);

		$TestModel->id = 1;
		$TestModel->set('body', 'Messed up data');
		$this->assertTrue($TestModel->saveField('title', 'First Article'));
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body'), 1);
		$expected = array('Article' => array(
			'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body'
		));
		$this->assertEqual($result, $expected);

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body'), 1);

		$TestModel->id = 1;
		$result = $TestModel->saveField('title', '', true);
		$this->assertFalse($result);

		$this->loadFixtures('Node', 'Dependency');
		$Node =& new Node();
		$Node->set('id', 1);
		$result = $Node->read();
		$this->assertEqual(Set::extract('/ParentNode/name', $result), array('Second'));

		$Node->saveField('state', 10);
		$result = $Node->read();
		$this->assertEqual(Set::extract('/ParentNode/name', $result), array('Second'));
	}
/**
 * testSaveWithCreate method
 *
 * @access public
 * @return void
 */
	function testSaveWithCreate() {
		$this->loadFixtures('User', 'Article', 'User', 'Comment', 'Tag', 'ArticlesTag', 'Attachment');
		$TestModel =& new User();

		$data = array('User' => array('user' => 'user', 'password' => ''));
		$result = $TestModel->save($data);
		$this->assertFalse($result);
		$this->assertTrue(!empty($TestModel->validationErrors));

		$TestModel =& new Article();

		$data = array('Article' => array('user_id' => '', 'title' => '', 'body' => ''));
		$result = $TestModel->create($data) && $TestModel->save();
		$this->assertFalse($result);
		$this->assertTrue(!empty($TestModel->validationErrors));

		$data = array('Article' => array('id' => 1, 'user_id' => '1', 'title' => 'New First Article', 'body' => ''));
		$result = $TestModel->create($data) && $TestModel->save();
		$this->assertFalse($result);

		$data = array('Article' => array('id' => 1, 'title' => 'New First Article'));
		$result = $TestModel->create() && $TestModel->save($data, false);
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 1);
		$expected = array('Article' => array(
			'id' => '1', 'user_id' => '1', 'title' => 'New First Article', 'body' => 'First Article Body', 'published' => 'N'
		));
		$this->assertEqual($result, $expected);

		$data = array('Article' => array('id' => 1, 'user_id' => '2', 'title' => 'First Article', 'body' => 'New First Article Body', 'published' => 'Y'));
		$result = $TestModel->create() && $TestModel->save($data, true, array('id', 'title', 'published'));
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 1);
		$expected = array('Article' => array(
			'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y'
		));
		$this->assertEqual($result, $expected);

		$data = array(
			'Article' => array('user_id' => '2', 'title' => 'New Article', 'body' => 'New Article Body', 'created' => '2007-03-18 14:55:23', 'updated' => '2007-03-18 14:57:31'),
			'Tag' => array('Tag' => array(1, 3))
		);
		$TestModel->create();
		$result = $TestModel->create() && $TestModel->save($data);
		$this->assertTrue($result);

		$TestModel->recursive = 2;
		$result = $TestModel->read(null, 4);
		$expected = array(
			'Article' => array('id' => '4', 'user_id' => '2', 'title' => 'New Article', 'body' => 'New Article Body', 'published' => 'N', 'created' => '2007-03-18 14:55:23', 'updated' => '2007-03-18 14:57:31'),
			'User' => array('id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'),
			'Comment' => array(),
			'Tag' => array(
				array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
			)
		);
		$this->assertEqual($result, $expected);

		$data = array('Comment' => array('article_id' => '4', 'user_id' => '1', 'comment' => 'Comment New Article', 'published' => 'Y', 'created' => '2007-03-18 14:57:23', 'updated' => '2007-03-18 14:59:31'));
		$result = $TestModel->Comment->create() && $TestModel->Comment->save($data);
		$this->assertTrue($result);

		$data = array('Attachment' => array('comment_id' => '7', 'attachment' => 'newattachment.zip', 'created' => '2007-03-18 15:02:23', 'updated' => '2007-03-18 15:04:31'));
		$result = $TestModel->Comment->Attachment->save($data);
		$this->assertTrue($result);

		$TestModel->recursive = 2;
		$result = $TestModel->read(null, 4);
		$expected = array(
			'Article' => array('id' => '4', 'user_id' => '2', 'title' => 'New Article', 'body' => 'New Article Body', 'published' => 'N', 'created' => '2007-03-18 14:55:23', 'updated' => '2007-03-18 14:57:31'),
			'User' => array('id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'),
			'Comment' => array(
				array(
					'id' => '7', 'article_id' => '4', 'user_id' => '1', 'comment' => 'Comment New Article', 'published' => 'Y', 'created' => '2007-03-18 14:57:23', 'updated' => '2007-03-18 14:59:31',
					'Article' => array('id' => '4', 'user_id' => '2', 'title' => 'New Article', 'body' => 'New Article Body', 'published' => 'N', 'created' => '2007-03-18 14:55:23', 'updated' => '2007-03-18 14:57:31'),
					'User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
					'Attachment' => array('id' => '2', 'comment_id' => '7', 'attachment' => 'newattachment.zip', 'created' => '2007-03-18 15:02:23', 'updated' => '2007-03-18 15:04:31')
				)
			),
			'Tag' => array(
				array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
			)
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testSaveWithSet method
 *
 * @access public
 * @return void
 */
	function testSaveWithSet() {
		$this->loadFixtures('Article');
		$TestModel =& new Article();

		// Create record we will be updating later

		$data = array('Article' => array('user_id' => '1', 'title' => 'Fourth Article', 'body' => 'Fourth Article Body', 'published' => 'Y'));
		$result = $TestModel->create() && $TestModel->save($data);
		$this->assertTrue($result);

		// Check record we created

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 4);
		$expected = array('Article' => array('id' => '4', 'user_id' => '1', 'title' => 'Fourth Article', 'body' => 'Fourth Article Body', 'published' => 'Y'));
		$this->assertEqual($result, $expected);

		// Create new record just to overlap Model->id on previously created record

		$data = array('Article' => array('user_id' => '4', 'title' => 'Fifth Article', 'body' => 'Fifth Article Body', 'published' => 'Y'));
		$result = $TestModel->create() && $TestModel->save($data);
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 5);
		$expected = array('Article' => array(
			'id' => '5', 'user_id' => '4', 'title' => 'Fifth Article', 'body' => 'Fifth Article Body', 'published' => 'Y'
		));
		$this->assertEqual($result, $expected);

		// Go back and edit the first article we created, starting by checking it's still there

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 4);
		$expected = array('Article' => array('id' => '4', 'user_id' => '1', 'title' => 'Fourth Article', 'body' => 'Fourth Article Body', 'published' => 'Y'));
		$this->assertEqual($result, $expected);

		// And now do the update with set()

		$data = array('Article' => array('id' => '4', 'title' => 'Fourth Article - New Title', 'published' => 'N'));
		$result = $TestModel->set($data) && $TestModel->save();
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 4);
		$expected = array('Article' => array('id' => '4', 'user_id' => '1', 'title' => 'Fourth Article - New Title', 'body' => 'Fourth Article Body', 'published' => 'N'));
		$this->assertEqual($result, $expected);

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 5);
		$expected = array('Article' => array(
			'id' => '5', 'user_id' => '4', 'title' => 'Fifth Article', 'body' => 'Fifth Article Body', 'published' => 'Y'
		));
		$this->assertEqual($result, $expected);

		$data = array('Article' => array('id' => '5', 'title' => 'Fifth Article - New Title 5'));
		$result = ($TestModel->set($data) && $TestModel->save());
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 5);
		$expected = array('Article' => array('id' => '5', 'user_id' => '4', 'title' => 'Fifth Article - New Title 5', 'body' => 'Fifth Article Body', 'published' => 'Y'));
		$this->assertEqual($result, $expected);

		$TestModel->recursive = -1;
		$result = $TestModel->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('Article' => array('id' => 1, 'title' => 'First Article' )),
			array('Article' => array('id' => 2, 'title' => 'Second Article' )),
			array('Article' => array('id' => 3, 'title' => 'Third Article' )),
			array('Article' => array('id' => 4, 'title' => 'Fourth Article - New Title' )),
			array('Article' => array('id' => 5, 'title' => 'Fifth Article - New Title 5' ))
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testSaveWithNonExistentFields method
 *
 * @access public
 * @return void
 */
	function testSaveWithNonExistentFields() {
		$this->loadFixtures('Article');
		$TestModel =& new Article();
		$TestModel->recursive = -1;

		$data = array(
			'non_existent' => 'This field does not exist',
			'user_id' => '1',
			'title' => 'Fourth Article - New Title',
			'body' => 'Fourth Article Body',
			'published' => 'N'
		);
		$result = $TestModel->create() && $TestModel->save($data);
		$this->assertTrue($result);

		$expected = array('Article' => array(
			'id' => '4',
			'user_id' => '1',
			'title' => 'Fourth Article - New Title',
			'body' => 'Fourth Article Body',
			'published' => 'N'
		));
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 4);
		$this->assertEqual($result, $expected);

		$data = array(
			'user_id' => '1',
			'non_existent' => 'This field does not exist',
			'title' => 'Fiveth Article - New Title',
			'body' => 'Fiveth Article Body',
			'published' => 'N'
		);
		$result = $TestModel->create() && $TestModel->save($data);
		$this->assertTrue($result);

		$expected = array('Article' => array(
			'id' => '5',
			'user_id' => '1',
			'title' => 'Fiveth Article - New Title',
			'body' => 'Fiveth Article Body',
			'published' => 'N'
		));
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 5);
		$this->assertEqual($result, $expected);
	}
/**
 * testSaveFromXml method
 *
 * @access public
 * @return void
 */
	function testSaveFromXml() {
		$this->loadFixtures('Article');
		App::import('Core', 'Xml');

		$Article = new Article();
		$Article->save(new Xml('<article title="test xml" user_id="5" />'));
		$this->assertTrue($Article->save(new Xml('<article title="test xml" user_id="5" />')));

		$results = $Article->find(array('Article.title' => 'test xml'));
		$this->assertTrue($results);
	}
/**
 * testSaveHabtm method
 *
 * @access public
 * @return void
 */
	function testSaveHabtm() {
		$this->loadFixtures('Article', 'User', 'Comment', 'Tag', 'ArticlesTag');
		$TestModel =& new Article();

		$result = $TestModel->findById(2);
		$expected = array(
			'Article' => array('id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
			'User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'),
			'Comment' => array(
				array('id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'),
				array('id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31')
			),
			'Tag' => array(
				array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
			)
		);
		$this->assertEqual($result, $expected);

		$data = array(
			'Article' => array('id' => '2', 'title' => 'New Second Article'),
			'Tag' => array('Tag' => array(1, 2))
		);

		$this->assertTrue($TestModel->set($data));
		$this->assertTrue($TestModel->save());

		$TestModel->unbindModel(array('belongsTo' => array('User'), 'hasMany' => array('Comment')));
		$result = $TestModel->find(array('Article.id' => 2), array('id', 'user_id', 'title', 'body'));
		$expected = array(
			'Article' => array('id' => '2', 'user_id' => '3', 'title' => 'New Second Article', 'body' => 'Second Article Body'),
			'Tag' => array(
				array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31')
			)
		);
		$this->assertEqual($result, $expected);

		$data = array('Article' => array('id' => '2'), 'Tag' => array('Tag' => array(2, 3)));
		$result = $TestModel->set($data);
		$this->assertTrue($result);

		$result = $TestModel->save();
		$this->assertTrue($result);

		$TestModel->unbindModel(array('belongsTo' => array('User'), 'hasMany' => array('Comment')));
		$result = $TestModel->find(array('Article.id'=>2), array('id', 'user_id', 'title', 'body'));
		$expected = array(
			'Article' => array('id' => '2', 'user_id' => '3', 'title' => 'New Second Article', 'body' => 'Second Article Body'),
			'Tag' => array(
				array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31'),
				array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
			)
		);
		$this->assertEqual($result, $expected);

		$data = array('Tag' => array('Tag' => array(1, 2, 3)));

		$result = $TestModel->set($data);
		$this->assertTrue($result);

		$result = $TestModel->save();
		$this->assertTrue($result);

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $TestModel->find(array('Article.id' => 2), array('id', 'user_id', 'title', 'body'));
		$expected = array(
			'Article' => array('id' => '2', 'user_id' => '3', 'title' => 'New Second Article', 'body' => 'Second Article Body'),
			'Tag' => array(
				array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31'),
				array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
			)
		);
		$this->assertEqual($result, $expected);

		$data = array('Tag' => array('Tag' => array()));
		$result = $TestModel->set($data);
		$this->assertTrue($result);

		$result = $TestModel->save();
		$this->assertTrue($result);

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $TestModel->find(array('Article.id'=>2), array('id', 'user_id', 'title', 'body'));
		$expected = array(
			'Article' => array('id' => '2', 'user_id' => '3', 'title' => 'New Second Article', 'body' => 'Second Article Body'),
			'Tag' => array()
		);
		$this->assertEqual($result, $expected);

		$data = array('Tag' => array('Tag' => array(2, 3)));
		$result = $TestModel->set($data);
		$this->assertTrue($result);

		$result = $TestModel->save();
		$this->assertTrue($result);

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $TestModel->find(array('Article.id'=>2), array('id', 'user_id', 'title', 'body'));
		$expected = array(
			'Article' => array(
				'id' => '2', 'user_id' => '3', 'title' => 'New Second Article', 'body' => 'Second Article Body'
			),
			'Tag' => array(
				array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31'),
				array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
			)
		);
		$this->assertEqual($result, $expected);

		$data = array('Tag' => array('Tag' => array(1, 2)), 'Article' => array('id' => '2', 'title' => 'New Second Article'));
		$this->assertTrue($TestModel->set($data));
		$this->assertTrue($TestModel->save());

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $TestModel->find(array('Article.id'=>2), array('id', 'user_id', 'title', 'body'));
		$expected = array(
			'Article' => array(
				'id' => '2', 'user_id' => '3', 'title' => 'New Second Article', 'body' => 'Second Article Body'
			),
			'Tag' => array(
				array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31')
			)
		);
		$this->assertEqual($result, $expected);

		$data = array('Tag' => array('Tag' => array(1, 2)), 'Article' => array('id' => '2', 'title' => 'New Second Article Title'));
		$result = $TestModel->set($data);
		$this->assertTrue($result);
		$this->assertTrue($TestModel->save());

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $TestModel->find(array('Article.id'=>2), array('id', 'user_id', 'title', 'body'));
		$expected = array(
			'Article' => array(
				'id' => '2', 'user_id' => '3', 'title' => 'New Second Article Title', 'body' => 'Second Article Body'
			),
			'Tag' => array(
				array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31')
			)
		);
		$this->assertEqual($result, $expected);

		$data = array('Tag' => array('Tag' => array(2, 3)), 'Article' => array('id' => '2', 'title' => 'Changed Second Article'));
		$this->assertTrue($TestModel->set($data));
		$this->assertTrue($TestModel->save());

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $TestModel->find(array('Article.id'=>2), array('id', 'user_id', 'title', 'body'));
		$expected = array(
			'Article' => array(
				'id' => '2', 'user_id' => '3', 'title' => 'Changed Second Article', 'body' => 'Second Article Body'
			),
			'Tag' => array(
				array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31'),
				array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
			)
		);
		$this->assertEqual($result, $expected);

		$data = array(
			'Tag' => array(
				'Tag' => array( 1, 3 )
			),
			'Article' => array('id' => '2' ),
		);

		$result = $TestModel->set($data);
		$this->assertTrue($result);

		$result = $TestModel->save();
		$this->assertTrue($result);

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $TestModel->find(array('Article.id'=>2), array('id', 'user_id', 'title', 'body'));
		$expected = array(
			'Article' => array(
				'id' => '2', 'user_id' => '3', 'title' => 'Changed Second Article', 'body' => 'Second Article Body'
			),
			'Tag' => array(
				array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
			)
		);
		$this->assertEqual($result, $expected);

		$data = array(
			'Article' => array('id' => 10, 'user_id' => '2', 'title' => 'New Article With Tags and fieldList', 'body' => 'New Article Body with Tags and fieldList', 'created' => '2007-03-18 14:55:23', 'updated' => '2007-03-18 14:57:31'),
			'Tag' => array('Tag' => array(1, 2, 3))
		);
		$result = $TestModel->create() && $TestModel->save($data, true, array('user_id', 'title', 'published'));
		$this->assertTrue($result);

		$TestModel->unbindModel(array('belongsTo' => array('User'), 'hasMany' => array('Comment')));
		$result = $TestModel->read();
		$expected = array(
			'Article' => array('id' => 4, 'user_id' => 2, 'title' => 'New Article With Tags and fieldList', 'body' => '', 'published' => 'N', 'created' => '', 'updated' => ''),
			'Tag' => array(
				0 => array('id' => 1, 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				1 => array('id' => 2, 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31'),
				2 => array('id' => 3, 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
			)
		);
		$this->assertEqual($result, $expected);
	}
	/**
 * testSaveHabtmCustomKeys method
 *
 * @access public
 * @return void
 */
	function testSaveHabtmCustomKeys() {
		$this->loadFixtures('Story', 'StoriesTag', 'Tag');
		$Story =& new Story();

		$data = array('Story' => array('story' => '1'), 'Tag' => array('Tag' => array(2, 3)));
		$result = $Story->set($data);
		$this->assertTrue($result);

		$result = $Story->save();
		$this->assertTrue($result);

		$result = $Story->find('all');
		$expected = array(
			array(
				'Story' => array('story' => 1, 'title' => 'First Story'),
				'Tag' => array(
					array('id' => 2, 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31'),
					array('id' => 3, 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
				)
			),
			array(
				'Story' => array('story' => 2, 'title' => 'Second Story'),
				'Tag' => array()
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * @todo This is technically incorrect (ThePaperMonkies.apple_id should be ThePaperMonkies.the_paper_id),
 * the foreign key name should come from the association name, not the table name... but that's the existing
 * functionality at this point.
 */
	function testHabtmSaveKeyResolution() {
		$this->loadFixtures('Apple', 'Device', 'ThePaperMonkies');
		$ThePaper =& new ThePaper();
		$ThePaper->id = 1;

		$ThePaper->save(array('Monkey' => array(2, 3)));
		$result = $ThePaper->findById(1);
		$expected = array(
			array('id' => '2', 'device_type_id' => '1', 'name' => 'Device 2', 'typ' => '1'),
			array('id' => '3', 'device_type_id' => '1', 'name' => 'Device 3', 'typ' => '2')
		);
		$this->assertEqual($result['Monkey'], $expected);
	}
/**
 * testSaveAll method
 *
 * @access public
 * @return void
 */
	function testSaveAll() {
		$this->loadFixtures('Post', 'Author', 'Comment', 'Attachment');
		$TestModel =& new Post();

		$result = $TestModel->find('all');
		$this->assertEqual(count($result), 3);
		$this->assertFalse(isset($result[3]));
		$ts = date('Y-m-d H:i:s');

		$TestModel->saveAll(array(
			'Post' => array('title' => 'Post with Author', 'body' => 'This post will be saved with an author'),
			'Author' => array('user' => 'bob', 'password' => '5f4dcc3b5aa765d61d8327deb882cf90')
		));

		$result = $TestModel->find('all');
		$expected = array(
			'Post' => array('id' => '4', 'author_id' => '5', 'title' => 'Post with Author', 'body' => 'This post will be saved with an author', 'published' => 'N', 'created' => $ts, 'updated' => $ts),
			'Author' => array('id' => '5', 'user' => 'bob', 'password' => '5f4dcc3b5aa765d61d8327deb882cf90', 'created' => $ts, 'updated' => $ts, 'test' => 'working')
		);
		$this->assertEqual($result[3], $expected);
		$this->assertEqual(count($result), 4);

		$TestModel->deleteAll(true);
		$this->assertEqual($TestModel->find('all'), array());

		// SQLite seems to reset the PK counter when that happens, so we need this to make the tests pass
		$this->db->truncate($TestModel);

		$ts = date('Y-m-d H:i:s');
		$TestModel->saveAll(array(
			array('title' => 'Multi-record post 1', 'body' => 'First multi-record post', 'author_id' => 2),
			array('title' => 'Multi-record post 2', 'body' => 'Second multi-record post', 'author_id' => 2)
		));

		$result = $TestModel->find('all', array('recursive' => -1, 'order' => 'Post.id ASC'));
		$expected = array(
			array('Post' => array('id' => '1', 'author_id' => '2', 'title' => 'Multi-record post 1', 'body' => 'First multi-record post', 'published' => 'N', 'created' => $ts, 'updated' => $ts)),
			array('Post' => array('id' => '2', 'author_id' => '2', 'title' => 'Multi-record post 2', 'body' => 'Second multi-record post', 'published' => 'N', 'created' => $ts, 'updated' => $ts))
		);
		$this->assertEqual($result, $expected);

		$TestModel =& new Comment();
		$ts = date('Y-m-d H:i:s');
		$result = $TestModel->saveAll(array(
			'Comment' => array('article_id' => 2, 'user_id' => 2, 'comment' => 'New comment with attachment', 'published' => 'Y'),
			'Attachment' => array('attachment' => 'some_file.tgz')
		));
		$this->assertTrue($result);

		$result = $TestModel->find('all');
		$expected = array('id' => '7', 'article_id' => '2', 'user_id' => '2', 'comment' => 'New comment with attachment', 'published' => 'Y', 'created' => $ts, 'updated' => $ts);
		$this->assertEqual($result[6]['Comment'], $expected);

		$expected = array('id' => '7', 'article_id' => '2', 'user_id' => '2', 'comment' => 'New comment with attachment', 'published' => 'Y', 'created' => $ts, 'updated' => $ts);
		$this->assertEqual($result[6]['Comment'], $expected);

		$expected = array('id' => '2', 'comment_id' => '7', 'attachment' => 'some_file.tgz', 'created' => $ts, 'updated' => $ts);
		$this->assertEqual($result[6]['Attachment'], $expected);
	}
/**
 * testSaveAllHasOne method
 *
 * @access public
 * @return void
 */
	function testSaveAllHasOne() {
		$model = new Comment();
		$model->deleteAll(true);
		$this->assertEqual($model->find('all'), array());

		$model->Attachment->deleteAll(true);
		$this->assertEqual($model->Attachment->find('all'), array());

		$this->assertTrue($model->saveAll(array(
			'Comment' => array('comment' => 'Comment with attachment', 'article_id' => 1, 'user_id' => 1),
			'Attachment' => array('attachment' => 'some_file.zip')
		)));
		$result = $model->find('all', array('fields' => array(
			'Comment.id', 'Comment.comment', 'Attachment.id', 'Attachment.comment_id', 'Attachment.attachment'
		)));
		$expected = array(array(
			'Comment' => array('id' => '1', 'comment' => 'Comment with attachment'),
			'Attachment' => array('id' => '1', 'comment_id' => '1', 'attachment' => 'some_file.zip')
		));
		$this->assertEqual($result, $expected);
	}
/**
 * testSaveAllBelongsTo method
 *
 * @access public
 * @return void
 */
	function testSaveAllBelongsTo() {
		$model = new Comment();
		$model->deleteAll(true);
		$this->assertEqual($model->find('all'), array());

		$model->Article->deleteAll(true);
		$this->assertEqual($model->Article->find('all'), array());

		$this->assertTrue($model->saveAll(array(
			'Comment' => array('comment' => 'Article comment', 'article_id' => 1, 'user_id' => 1),
			'Article' => array('title' => 'Model Associations 101', 'user_id' => 1)
		)));
		$result = $model->find('all', array('fields' => array(
			'Comment.id', 'Comment.comment', 'Comment.article_id', 'Article.id', 'Article.title'
		)));
		$expected = array(array(
			'Comment' => array('id' => '1', 'article_id' => '1', 'comment' => 'Article comment'),
			'Article' => array('id' => '1', 'title' => 'Model Associations 101')
		));
		$this->assertEqual($result, $expected);
	}
/**
 * testSaveAllHasOneValidation method
 *
 * @access public
 * @return void
 */
	function testSaveAllHasOneValidation() {
		$model = new Comment();
		$model->deleteAll(true);
		$this->assertEqual($model->find('all'), array());

		$model->Attachment->deleteAll(true);
		$this->assertEqual($model->Attachment->find('all'), array());

		$model->validate = array('comment' => VALID_NOT_EMPTY);
		$model->Attachment->validate = array('attachment' => VALID_NOT_EMPTY);
		$model->Attachment->bind('Comment');

		$this->assertFalse($model->saveAll(
			array(
				'Comment' => array('comment' => '', 'article_id' => 1, 'user_id' => 1),
				'Attachment' => array('attachment' => '')
			),
			array('validate' => 'first')
		));
		$expected = array(
			'Comment' => array('comment' => 'This field cannot be left blank'),
			'Attachment' => array('attachment' => 'This field cannot be left blank')
		);
		$this->assertEqual($model->validationErrors, $expected['Comment']);
		$this->assertEqual($model->Attachment->validationErrors, $expected['Attachment']);

		$this->assertFalse($model->saveAll(
			array(
				'Comment' => array('comment' => '', 'article_id' => 1, 'user_id' => 1),
				'Attachment' => array('attachment' => '')
			),
			array('validate' => 'only')
		));
		$this->assertEqual($model->validationErrors, $expected['Comment']);
		$this->assertEqual($model->Attachment->validationErrors, $expected['Attachment']);
	}
/**
 * testSaveAllAtomic method
 *
 * @access public
 * @return void
 */
	function testSaveAllAtomic() {
		$this->loadFixtures('Article', 'User');
		$TestModel =& new Article();

		$result = $TestModel->saveAll(array(
			'Article' => array('title' => 'Post with Author', 'body' => 'This post will be saved with an author', 'user_id' => 2),
			'Comment' => array(array('comment' => 'First new comment', 'user_id' => 2))
		), array('atomic' => false));
		$this->assertIdentical($result, array('Article' => true, 'Comment' => array(true)));

		$result = $TestModel->saveAll(array(
			array('id' => '1', 'title' => 'Baleeted First Post', 'body' => 'Baleeted!', 'published' => 'N'),
			array('id' => '2', 'title' => 'Just update the title'),
			array('title' => 'Creating a fourth post', 'body' => 'Fourth post body', 'user_id' => 2)
		), array('atomic' => false));
		$this->assertIdentical($result, array(true, true, true));

		$TestModel->validate = array('title' => VALID_NOT_EMPTY, 'author_id' => 'numeric');
		$result = $TestModel->saveAll(array(
			array('id' => '1', 'title' => 'Un-Baleeted First Post', 'body' => 'Not Baleeted!', 'published' => 'Y'),
			array('id' => '2', 'title' => '', 'body' => 'Trying to get away with an empty title'),
		), array('atomic' => false));
		$this->assertIdentical($result, array(true, false));

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'user_id' => 1),
				array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
			)
		), array('atomic' => false));
		$this->assertIdentical($result, array('Article' => true, 'Comment' => array(true, true)));
	}
/**
 * testSaveAllHasMany method
 *
 * @access public
 * @return void
 */
	function testSaveAllHasMany() {
		$this->loadFixtures('Article', 'Comment');
		$TestModel =& new Article();
		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = array();

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'user_id' => 1),
				array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
			)
		));
		$this->assertTrue($result);

		$result = $TestModel->findById(2);
		$expected = array('First Comment for Second Article', 'Second Comment for Second Article', 'First new comment', 'Second new comment');
		$this->assertEqual(Set::extract($result['Comment'], '{n}.comment'), $expected);

		$result = $TestModel->saveAll(
			array(
				'Article' => array('id' => 2),
				'Comment' => array(
					array('comment' => 'Third new comment', 'published' => 'Y', 'user_id' => 1),
				)
			),
			array('atomic' => false)
		);
		$this->assertTrue($result);

		$result = $TestModel->findById(2);
		$expected = array('First Comment for Second Article', 'Second Comment for Second Article', 'First new comment', 'Second new comment', 'Third new comment');
		$this->assertEqual(Set::extract($result['Comment'], '{n}.comment'), $expected);

		$TestModel->beforeSaveReturn = false;
		$result = $TestModel->saveAll(
			array(
				'Article' => array('id' => 2),
				'Comment' => array(
					array('comment' => 'Fourth new comment', 'published' => 'Y', 'user_id' => 1),
				)
			),
			array('atomic' => false)
		);
		$this->assertEqual($result, array('Article' => false));

		$result = $TestModel->findById(2);
		$expected = array('First Comment for Second Article', 'Second Comment for Second Article', 'First new comment', 'Second new comment', 'Third new comment');
		$this->assertEqual(Set::extract($result['Comment'], '{n}.comment'), $expected);
	}
	/**
 * testSaveAllHasManyValidation method
 *
 * @access public
 * @return void
 */
	function testSaveAllHasManyValidation() {
		$this->loadFixtures('Article', 'Comment');
		$TestModel =& new Article();
		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = array();
		$TestModel->Comment->validate = array('comment' => VALID_NOT_EMPTY);

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => '', 'published' => 'Y', 'user_id' => 1),
			)
		));
		$expected = array('Comment' => array(false));
		$this->assertEqual($result, $expected);

		$expected = array('Comment' => array(
			array('comment' => 'This field cannot be left blank')
		));
		$this->assertEqual($TestModel->validationErrors, $expected);
		$expected = array(
			array('comment' => 'This field cannot be left blank')
		);
		$this->assertEqual($TestModel->Comment->validationErrors, $expected);

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => '', 'published' => 'Y', 'user_id' => 1),
			)
		), array('validate' => 'only'));
	}
/**
 * testSaveAllTransaction method
 *
 * @access public
 * @return void
 */
	function testSaveAllTransaction() {
		$this->loadFixtures('Post', 'Author', 'Comment', 'Attachment');
		$TestModel =& new Post();

		$TestModel->validate = array('title' => VALID_NOT_EMPTY);
		$data = array(
			array('author_id' => 1, 'title' => 'New Fourth Post'),
			array('author_id' => 1, 'title' => 'New Fifth Post'),
			array('author_id' => 1, 'title' => '')
		);
		$ts = date('Y-m-d H:i:s');
		$this->assertFalse($TestModel->saveAll($data));

		$result = $TestModel->find('all', array('recursive' => -1));
		$expected = array(
			array('Post' => array('id' => '1', 'author_id' => 1, 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31')),
			array('Post' => array('id' => '2', 'author_id' => 3, 'title' => 'Second Post', 'body' => 'Second Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31')),
			array('Post' => array('id' => '3', 'author_id' => 1, 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'))
		);
		if (count($result) != 3) {
			// Database doesn't support transactions
			$expected[] = array('Post' => array('id' => '4', 'author_id' => 1, 'title' => 'New Fourth Post', 'body' => null, 'published' => 'N', 'created' => $ts, 'updated' => $ts));
			$expected[] = array('Post' => array('id' => '5', 'author_id' => 1, 'title' => 'New Fifth Post', 'body' => null, 'published' => 'N', 'created' => $ts, 'updated' => $ts));
			$this->assertEqual($result, $expected);
			// Skip the rest of the transactional tests
			return;
		}

		$this->assertEqual($result, $expected);

		$data = array(
			array('author_id' => 1, 'title' => 'New Fourth Post'),
			array('author_id' => 1, 'title' => ''),
			array('author_id' => 1, 'title' => 'New Sixth Post')
		);
		$ts = date('Y-m-d H:i:s');
		$this->assertFalse($TestModel->saveAll($data));

		$result = $TestModel->find('all', array('recursive' => -1));
		$expected = array(
			array('Post' => array('id' => '1', 'author_id' => 1, 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31')),
			array('Post' => array('id' => '2', 'author_id' => 3, 'title' => 'Second Post', 'body' => 'Second Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31')),
			array('Post' => array('id' => '3', 'author_id' => 1, 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'))
		);
		if (count($result) != 3) {
			// Database doesn't support transactions
			$expected[] = array('Post' => array('id' => '4', 'author_id' => 1, 'title' => 'New Fourth Post', 'body' => 'Third Post Body', 'published' => 'N', 'created' => $ts, 'updated' => $ts));
			$expected[] = array('Post' => array('id' => '5', 'author_id' => 1, 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'N', 'created' => $ts, 'updated' => $ts));
		}
		$this->assertEqual($result, $expected);

		$TestModel->validate = array('title' => VALID_NOT_EMPTY);
		$data = array(
			array('author_id' => 1, 'title' => 'New Fourth Post'),
			array('author_id' => 1, 'title' => 'New Fifth Post'),
			array('author_id' => 1, 'title' => 'New Sixth Post')
		);
		$this->assertTrue($TestModel->saveAll($data));

		$result = $TestModel->find('all', array('recursive' => -1, 'fields' => array('author_id', 'title','body','published')));
		$expected = array(
			array('Post' => array('author_id' => 1, 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y')),
			array('Post' => array('author_id' => 3, 'title' => 'Second Post', 'body' => 'Second Post Body', 'published' => 'Y')),
			array('Post' => array('author_id' => 1, 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'Y')),
			array('Post' => array('author_id' => 1, 'title' => 'New Fourth Post', 'body' => '', 'published' => 'N')),
			array('Post' => array('author_id' => 1, 'title' => 'New Fifth Post', 'body' => '', 'published' => 'N')),
			array('Post' => array('author_id' => 1, 'title' => 'New Sixth Post', 'body' => '', 'published' => 'N'))
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testSaveAllValidation method
 *
 * @access public
 * @return void
 */
	function testSaveAllValidation() {
		$this->loadFixtures('Post', 'Author', 'Comment', 'Attachment');
		$TestModel =& new Post();

		$data = array(
			array('id' => '1', 'title' => 'Baleeted First Post', 'body' => 'Baleeted!', 'published' => 'N'),
			array('id' => '2', 'title' => 'Just update the title'),
			array('title' => 'Creating a fourth post', 'body' => 'Fourth post body', 'author_id' => 2)
		);
		$this->assertTrue($TestModel->saveAll($data));

		$result = $TestModel->find('all', array('recursive' => -1));
		$ts = date('Y-m-d H:i:s');
		$expected = array(
			array('Post' => array('id' => '1', 'author_id' => '1', 'title' => 'Baleeted First Post', 'body' => 'Baleeted!', 'published' => 'N', 'created' => '2007-03-18 10:39:23', 'updated' => $ts)),
			array('Post' => array('id' => '2', 'author_id' => '3', 'title' => 'Just update the title', 'body' => 'Second Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => $ts)),
			array('Post' => array('id' => '3', 'author_id' => '1', 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31')),
			array('Post' => array('id' => '4', 'author_id' => '2', 'title' => 'Creating a fourth post', 'body' => 'Fourth post body', 'published' => 'N', 'created' => $ts, 'updated' => $ts))
		);
		$this->assertEqual($result, $expected);

		$TestModel->validate = array('title' => VALID_NOT_EMPTY, 'author_id' => 'numeric');
		$data = array(
			array('id' => '1', 'title' => 'Un-Baleeted First Post', 'body' => 'Not Baleeted!', 'published' => 'Y'),
			array('id' => '2', 'title' => '', 'body' => 'Trying to get away with an empty title'),
		);
		$result = $TestModel->saveAll($data);
		$this->assertEqual($result, false);

		$result = $TestModel->find('all', array('recursive' => -1));
		$errors = array(1 => array('title' => 'This field cannot be left blank'));
		$transactionWorked = Set::matches('/Post[1][title=Baleeted First Post]', $result);
		if (!$transactionWorked) {
			$this->assertTrue(Set::matches('/Post[1][title=Un-Baleeted First Post]', $result));
			$this->assertTrue(Set::matches('/Post[2][title=Just update the title]', $result));
		}

		$this->assertEqual($TestModel->validationErrors, $errors);

		$TestModel->validate = array('title' => VALID_NOT_EMPTY, 'author_id' => 'numeric');
		$data = array(
			array('id' => '1', 'title' => 'Un-Baleeted First Post', 'body' => 'Not Baleeted!', 'published' => 'Y'),
			array('id' => '2', 'title' => '', 'body' => 'Trying to get away with an empty title'),
		);
		$result = $TestModel->saveAll($data, array('atomic' => false));
		$this->assertEqual($result, array(true, false));
		$result = $TestModel->find('all', array('recursive' => -1));
		$errors = array(1 => array('title' => 'This field cannot be left blank'));
		$newTs = date('Y-m-d H:i:s');
		$expected = array(
			array('Post' => array('id' => '1', 'author_id' => '1', 'title' => 'Un-Baleeted First Post', 'body' => 'Not Baleeted!', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => $newTs)),
			array('Post' => array('id' => '2', 'author_id' => '3', 'title' => 'Just update the title', 'body' => 'Second Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => $ts)),
			array('Post' => array('id' => '3', 'author_id' => '1', 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31')),
			array('Post' => array('id' => '4', 'author_id' => '2', 'title' => 'Creating a fourth post', 'body' => 'Fourth post body', 'published' => 'N', 'created' => $ts, 'updated' => $ts))
		);
		$this->assertEqual($result, $expected);
		$this->assertEqual($TestModel->validationErrors, $errors);

		$data = array(
			array('id' => '1', 'title' => 'Re-Baleeted First Post', 'body' => 'Baleeted!', 'published' => 'N'),
			array('id' => '2', 'title' => '', 'body' => 'Trying to get away with an empty title'),
		);
		$this->assertFalse($TestModel->saveAll($data, array('validate' => 'first')));

		$result = $TestModel->find('all', array('recursive' => -1));
		$this->assertEqual($result, $expected);
		$this->assertEqual($TestModel->validationErrors, $errors);

		$data = array(
			array('title' => 'First new post', 'body' => 'Woohoo!', 'published' => 'Y'),
			array('title' => 'Empty body', 'body' => '')
		);
		$TestModel->validate['body'] = VALID_NOT_EMPTY;
	}
/**
 * testSaveAllValidationOnly method
 *
 * @access public
 * @return void
 */
	function testSaveAllValidationOnly() {
		$TestModel =& new Comment();
		$TestModel->Attachment->validate = array('attachment' => VALID_NOT_EMPTY);

		$data = array(
			'Comment' => array(
				'comment' => 'This is the comment'
			),
			'Attachment' => array(
				'attachment' => ''
			)
		);

		$result = $TestModel->saveAll($data, array('validate' => 'only'));
		$this->assertFalse($result);

		$TestModel =& new Article();
		$TestModel->validate = array('title' => VALID_NOT_EMPTY);
		$result = $TestModel->saveAll(
			array(
				0 => array('title' => ''),
				1 => array('title' => 'title 1'),
				2 => array('title' => 'title 2'),
			),
			array('validate'=>'only')
		);
		$this->assertFalse($result);
		$expected = array(
			0 => array('title' => 'This field cannot be left blank'),
		);
		$this->assertEqual($TestModel->validationErrors, $expected);

		$result = $TestModel->saveAll(
			array(
				0 => array('title' => 'title 0'),
				1 => array('title' => ''),
				2 => array('title' => 'title 2'),
			),
			array('validate'=>'only')
		);
		$this->assertFalse($result);
		$expected = array(
			1 => array('title' => 'This field cannot be left blank'),
		);
		$this->assertEqual($TestModel->validationErrors, $expected);
	}

/**
 * testSaveAllValidateFirst method
 *
 * @access public
 * @return void
 */
	function testSaveAllValidateFirst() {
		$model =& new Article();
		$model->deleteAll(true);

		$model->Comment->validate = array('comment' => VALID_NOT_EMPTY);
		$result = $model->saveAll(array(
			'Article' => array('title' => 'Post with Author', 'body' => 'This post will be saved  author'),
			'Comment' => array(
				array('comment' => 'First new comment'),
				array('comment' => '')
			)
		), array('validate' => 'first'));
		$this->assertFalse($result);

		$result = $model->find('all');
		$this->assertEqual($result, array());
		$expected = array('Comment' => array(1 => array('comment' => 'This field cannot be left blank')));

		$this->assertEqual($model->Comment->validationErrors, $expected['Comment']);

		$this->assertIdentical($model->Comment->find('count'), 0);

		$result = $model->saveAll(
			array(
				'Article' => array('title' => 'Post with Author', 'body' => 'This post will be saved with an author', 'user_id' => 2),
				'Comment' => array(array('comment' => 'Only new comment', 'user_id' => 2))
			),
			array('validate' => 'first')
		);
		$this->assertIdentical($result, true);

		$result = $model->Comment->find('all');
		$this->assertIdentical(count($result), 1);
		$result = Set::extract('/Comment/article_id', $result);
		$this->assertTrue($result[0] === 1 || $result[0] === '1');
	}
/**
 * testSaveWithCounterCache method
 *
 * @access public
 * @return void
 */
	function testSaveWithCounterCache() {
		$this->loadFixtures('Syfile', 'Item');
		$TestModel =& new Syfile();
		$TestModel2 =& new Item();

		$result = $TestModel->findById(1);
		$this->assertIdentical($result['Syfile']['item_count'], null);

		$TestModel2->save(array('name' => 'Item 7', 'syfile_id' => 1, 'published' => false));
		$result = $TestModel->findById(1);
		$this->assertIdentical($result['Syfile']['item_count'], '2');

		$TestModel2->delete(1);
		$result = $TestModel->findById(1);
		$this->assertIdentical($result['Syfile']['item_count'], '1');

		$TestModel2->id = 2;
		$TestModel2->saveField('syfile_id', 1);

		$result = $TestModel->findById(1);
		$this->assertIdentical($result['Syfile']['item_count'], '2');

		$result = $TestModel->findById(2);
		$this->assertIdentical($result['Syfile']['item_count'], null);
	}
/**
 * test Counter Cache With Self Joining table
 *
 * @return void
 * @access public
 */
	function testCounterCacheWithSelfJoin() {
		$this->loadFixtures('CategoryThread');
		$this->db->query('ALTER TABLE '. $this->db->fullTableName('category_threads') . " ADD column child_count INT(11) DEFAULT '0'");
		$Category =& new CategoryThread();
		$result = $Category->updateAll(array('CategoryThread.name' => "'updated'"), array('CategoryThread.parent_id' => 5));
		$this->assertTrue($result);

		$Category =& new CategoryThread();
		$Category->belongsTo['ParentCategory']['counterCache'] = 'child_count';
		$Category->updateCounterCache(array('parent_id' => 5));
		$result = Set::extract($Category->find('all', array('conditions' => array('CategoryThread.id' => 5))), '{n}.CategoryThread.child_count');
		$expected = array_fill(0, 1, 1);
		$this->assertEqual($result, $expected);
	}

/**
 * testSaveWithCounterCacheScope method
 *
 * @access public
 * @return void
 */
	function testSaveWithCounterCacheScope() {
		$this->loadFixtures('Syfile', 'Item');
		$TestModel =& new Syfile();
		$TestModel2 =& new Item();
		$TestModel2->belongsTo['Syfile']['counterCache'] = true;
		$TestModel2->belongsTo['Syfile']['counterScope'] = array('published' => true);

		$result = $TestModel->findById(1);
		$this->assertIdentical($result['Syfile']['item_count'], null);

		$TestModel2->save(array('name' => 'Item 7', 'syfile_id' => 1, 'published'=> true));
		$result = $TestModel->findById(1);
		$this->assertIdentical($result['Syfile']['item_count'], '1');

		$TestModel2->id = 1;
		$TestModel2->saveField('published', true);
		$result = $TestModel->findById(1);
		$this->assertIdentical($result['Syfile']['item_count'], '2');
	}
/**
 * testDel method
 *
 * @access public
 * @return void
 */
	function testDel() {
		$this->loadFixtures('Article');
		$TestModel =& new Article();

		$result = $TestModel->del(2);
		$this->assertTrue($result);

		$result = $TestModel->read(null, 2);
		$this->assertFalse($result);

		$TestModel->recursive = -1;
		$result = $TestModel->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('Article' => array('id' => 1, 'title' => 'First Article' )),
			array('Article' => array('id' => 3, 'title' => 'Third Article' ))
		);
		$this->assertEqual($result, $expected);

		$result = $TestModel->del(3);
		$this->assertTrue($result);

		$result = $TestModel->read(null, 3);
		$this->assertFalse($result);

		$TestModel->recursive = -1;
		$result = $TestModel->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('Article' => array('id' => 1, 'title' => 'First Article' ))
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testDeleteAll method
 *
 * @access public
 * @return void
 */
	function testDeleteAll() {
		$this->loadFixtures('Article');
		$TestModel =& new Article();

		$data = array('Article' => array('user_id' => 2, 'id' => 4, 'title' => 'Fourth Article', 'published' => 'N'));
		$result = $TestModel->set($data) && $TestModel->save();
		$this->assertTrue($result);

		$data = array('Article' => array('user_id' => 2, 'id' => 5, 'title' => 'Fifth Article', 'published' => 'Y'));
		$result = $TestModel->set($data) && $TestModel->save();
		$this->assertTrue($result);

		$data = array('Article' => array('user_id' => 1, 'id' => 6, 'title' => 'Sixth Article', 'published' => 'N'));
		$result = $TestModel->set($data) && $TestModel->save();
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->find('all', array('fields' => array('id', 'user_id', 'title', 'published')));
		$expected = array(
			array('Article' => array('id' => 1, 'user_id' => 1, 'title' => 'First Article', 'published' => 'Y' )),
			array('Article' => array('id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'published' => 'Y' )),
			array('Article' => array('id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'published' => 'Y' )),
			array('Article' => array('id' => 4, 'user_id' => 2, 'title' => 'Fourth Article', 'published' => 'N' )),
			array('Article' => array('id' => 5, 'user_id' => 2, 'title' => 'Fifth Article', 'published' => 'Y' )),
			array('Article' => array('id' => 6, 'user_id' => 1, 'title' => 'Sixth Article', 'published' => 'N' ))
		);
		$this->assertEqual($result, $expected);

		$result = $TestModel->deleteAll(array('Article.published' => 'N'));
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->find('all', array('fields' => array('id', 'user_id', 'title', 'published')));
		$expected = array(
			array('Article' => array('id' => 1, 'user_id' => 1, 'title' => 'First Article', 'published' => 'Y' )),
			array('Article' => array('id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'published' => 'Y' )),
			array('Article' => array('id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'published' => 'Y' )),
			array('Article' => array('id' => 5, 'user_id' => 2, 'title' => 'Fifth Article', 'published' => 'Y' ))
		);
		$this->assertEqual($result, $expected);

		$result = $TestModel->deleteAll(array('Article.user_id' => array(2, 3)));
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->find('all', array('fields' => array('id', 'user_id', 'title', 'published')));
		$expected = array(
			array('Article' => array('id' => 1, 'user_id' => 1, 'title' => 'First Article', 'published' => 'Y' )),
			array('Article' => array('id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'published' => 'Y' ))
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testRecursiveDel method
 *
 * @access public
 * @return void
 */
	function testRecursiveDel() {
		$this->loadFixtures('Article', 'Comment', 'Attachment');
		$TestModel =& new Article();

		$result = $TestModel->del(2);
		$this->assertTrue($result);

		$TestModel->recursive = 2;
		$result = $TestModel->read(null, 2);
		$this->assertFalse($result);

		$result = $TestModel->Comment->read(null, 5);
		$this->assertFalse($result);

		$result = $TestModel->Comment->read(null, 6);
		$this->assertFalse($result);

		$result = $TestModel->Comment->Attachment->read(null, 1);
		$this->assertFalse($result);

		$result = $TestModel->find('count');
		$this->assertEqual($result, 2);

		$result = $TestModel->Comment->find('count');
		$this->assertEqual($result, 4);

		$result = $TestModel->Comment->Attachment->find('count');
		$this->assertEqual($result, 0);
	}
/**
 * testDependentExclusiveDelete method
 *
 * @access public
 * @return void
 */
	function testDependentExclusiveDelete() {
		$this->loadFixtures('Article', 'Comment');
		$TestModel =& new Article10();

		$result = $TestModel->find('all');
		$this->assertEqual(count($result[0]['Comment']), 4);
		$this->assertEqual(count($result[1]['Comment']), 2);
		$this->assertEqual($TestModel->Comment->find('count'), 6);

		$TestModel->delete(1);
		$this->assertEqual($TestModel->Comment->find('count'), 2);
	}
/**
 * testDeleteLinks method
 *
 * @access public
 * @return void
 */
	function testDeleteLinks() {
		$this->loadFixtures('Article', 'ArticlesTag', 'Tag');
		$TestModel =& new Article();

		$result = $TestModel->ArticlesTag->find('all');
		$expected = array(
			array('ArticlesTag' => array('article_id' => '1', 'tag_id' => '1')),
			array('ArticlesTag' => array('article_id' => '1', 'tag_id' => '2')),
			array('ArticlesTag' => array('article_id' => '2', 'tag_id' => '1')),
			array('ArticlesTag' => array('article_id' => '2', 'tag_id' => '3'))
		);
		$this->assertEqual($result, $expected);

		$TestModel->delete(1);
		$result = $TestModel->ArticlesTag->find('all');

		$expected = array(
			array('ArticlesTag' => array('article_id' => '2', 'tag_id' => '1')),
			array('ArticlesTag' => array('article_id' => '2', 'tag_id' => '3'))
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testFindAllThreaded method
 *
 * @access public
 * @return void
 */
	function testFindAllThreaded() {
		$this->loadFixtures('Category');
		$TestModel =& new Category();

		$result = $TestModel->find('threaded');
		$expected = array(
			array(
				'Category' => array(
					'id' => '1',
					'parent_id' => '0',
					'name' => 'Category 1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => '2',
							'parent_id' => '1',
							'name' => 'Category 1.1',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array(
							array('Category' => array(
								'id' => '7',
								'parent_id' => '2',
								'name' => 'Category 1.1.1',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31'),
								'children' => array()),
							array('Category' => array(
								'id' => '8',
								'parent_id' => '2',
								'name' => 'Category 1.1.2',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31'),
								'children' => array()))
					),
					array(
						'Category' => array(
							'id' => '3',
							'parent_id' => '1',
							'name' => 'Category 1.2',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array()
					)
				)
			),
			array(
				'Category' => array(
					'id' => '4',
					'parent_id' => '0',
					'name' => 'Category 2',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'children' => array()
			),
			array(
				'Category' => array(
					'id' => '5',
					'parent_id' => '0',
					'name' => 'Category 3',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => '6',
							'parent_id' => '5',
							'name' => 'Category 3.1',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array()
					)
				)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('threaded', array('conditions' => array('Category.name LIKE' => 'Category 1%')));
		$expected = array(
			array(
				'Category' => array(
					'id' => '1',
					'parent_id' => '0',
					'name' => 'Category 1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => '2',
							'parent_id' => '1',
							'name' => 'Category 1.1',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array(
							array('Category' => array(
								'id' => '7',
								'parent_id' => '2',
								'name' => 'Category 1.1.1',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31'),
								'children' => array()),
							array('Category' => array(
								'id' => '8',
								'parent_id' => '2',
								'name' => 'Category 1.1.2',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31'),
								'children' => array()))
					),
					array(
						'Category' => array(
							'id' => '3',
							'parent_id' => '1',
							'name' => 'Category 1.2',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array()
					)
				)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('threaded', array('fields' => 'id, parent_id, name'));
		$expected = array(
			array(
				'Category' => array(
					'id' => '1',
					'parent_id' => '0',
					'name' => 'Category 1'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => '2',
							'parent_id' => '1',
							'name' => 'Category 1.1'
						),
						'children' => array(
							array('Category' => array(
								'id' => '7',
								'parent_id' => '2',
								'name' => 'Category 1.1.1'),
								'children' => array()),
							array('Category' => array(
								'id' => '8',
								'parent_id' => '2',
								'name' => 'Category 1.1.2'),
								'children' => array()))
					),
					array(
						'Category' => array(
							'id' => '3',
							'parent_id' => '1',
							'name' => 'Category 1.2'
						),
						'children' => array()
					)
				)
			),
			array(
				'Category' => array(
					'id' => '4',
					'parent_id' => '0',
					'name' => 'Category 2'
				),
				'children' => array()
			),
			array(
				'Category' => array(
					'id' => '5',
					'parent_id' => '0',
					'name' => 'Category 3'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => '6',
							'parent_id' => '5',
							'name' => 'Category 3.1'
						),
						'children' => array()
					)
				)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('threaded', array('order' => 'id DESC'));

		$expected = array(
			array(
				'Category' => array(
					'id' => 5,
					'parent_id' => 0,
					'name' => 'Category 3',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => 6,
							'parent_id' => 5,
							'name' => 'Category 3.1',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array()
					)
				)
			),
			array(
				'Category' => array(
					'id' => 4,
					'parent_id' => 0,
					'name' => 'Category 2',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'children' => array()
			),
			array(
				'Category' => array(
					'id' => 1,
					'parent_id' => 0,
					'name' => 'Category 1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => 3,
							'parent_id' => 1,
							'name' => 'Category 1.2',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array()
					),
					array(
						'Category' => array(
							'id' => 2,
							'parent_id' => 1,
							'name' => 'Category 1.1',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array(
							array('Category' => array(
								'id' => '8',
								'parent_id' => '2',
								'name' => 'Category 1.1.2',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31'),
								'children' => array()),
							array('Category' => array(
								'id' => '7',
								'parent_id' => '2',
								'name' => 'Category 1.1.1',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31'),
								'children' => array()))
					)
				)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('threaded', array('conditions' => array('Category.name LIKE' => 'Category 3%')));
		$expected = array(
			array(
				'Category' => array(
					'id' => '5',
					'parent_id' => '0',
					'name' => 'Category 3',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => '6',
							'parent_id' => '5',
							'name' => 'Category 3.1',
							'created' => '2007-03-18 15:30:23',
							'updated' => '2007-03-18 15:32:31'
						),
						'children' => array()
					)
				)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('threaded', array('conditions' => array('Category.name LIKE' => 'Category 1.1%')));
		$expected = array(
				array('Category' =>
					array(
						'id' => '2',
						'parent_id' => '1',
						'name' => 'Category 1.1',
						'created' => '2007-03-18 15:30:23',
						'updated' => '2007-03-18 15:32:31'),
						'children' => array(
							array('Category' => array(
								'id' => '7',
								'parent_id' => '2',
								'name' => 'Category 1.1.1',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31'),
								'children' => array()),
							array('Category' => array(
								'id' => '8',
								'parent_id' => '2',
								'name' => 'Category 1.1.2',
								'created' => '2007-03-18 15:30:23',
								'updated' => '2007-03-18 15:32:31'),
								'children' => array()))));
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('threaded', array('fields' => 'id, parent_id, name', 'conditions' => array('Category.id !=' => 2)));
		$expected = array(
			array(
				'Category' => array(
					'id' => '1',
					'parent_id' => '0',
					'name' => 'Category 1'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => '3',
							'parent_id' => '1',
							'name' => 'Category 1.2'
						),
						'children' => array()
					)
				)
			),
			array(
				'Category' => array(
					'id' => '4',
					'parent_id' => '0',
					'name' => 'Category 2'
				),
				'children' => array()
			),
			array(
				'Category' => array(
					'id' => '5',
					'parent_id' => '0',
					'name' => 'Category 3'
				),
				'children' => array(
					array(
						'Category' => array(
							'id' => '6',
							'parent_id' => '5',
							'name' => 'Category 3.1'
						),
						'children' => array()
					)
				)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('all', array('fields' => 'id, name, parent_id', 'conditions' => array('Category.id !=' => 1)));
		$expected = array (
			array ('Category' => array('id' => '2', 'name' => 'Category 1.1', 'parent_id' => '1' )),
			array ('Category' => array('id' => '3', 'name' => 'Category 1.2', 'parent_id' => '1' )),
			array ('Category' => array('id' => '4', 'name' => 'Category 2', 'parent_id' => '0' )),
			array ('Category' => array('id' => '5', 'name' => 'Category 3', 'parent_id' => '0' )),
			array ('Category' => array('id' => '6', 'name' => 'Category 3.1', 'parent_id' => '5' )),
			array ('Category' => array('id' => '7', 'name' => 'Category 1.1.1', 'parent_id' => '2' )),
			array ('Category' => array('id' => '8', 'name' => 'Category 1.1.2', 'parent_id' => '2' )),
		);
		$this->assertEqual($result, $expected);

		$result = $TestModel->find('threaded', array('fields' => 'id, parent_id, name', 'conditions' => array('Category.id !=' => 1)));
		$expected = array(
			array(
				'Category' => array(
					'id' => '2',
					'parent_id' => '1',
					'name' => 'Category 1.1'
				),
				'children' => array(
					array('Category' => array(
						'id' => '7',
						'parent_id' => '2',
						'name' => 'Category 1.1.1'),
						'children' => array()),
					array('Category' => array(
						'id' => '8',
						'parent_id' => '2',
						'name' => 'Category 1.1.2'),
						'children' => array()))
			),
			array(
				'Category' => array(
					'id' => '3',
					'parent_id' => '1',
					'name' => 'Category 1.2'
				),
				'children' => array()
			)
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testFindNeighbours method
 *
 * @return void
 * @access public
 */
	function testFindNeighbours() {
		$this->loadFixtures('User', 'Article');
		$TestModel =& new Article();

		$TestModel->id = 1;
		$result = $TestModel->find('neighbors', array('fields' => array('id')));
		$expected = array('prev' => null, 'next' => array('Article' => array('id' => 2)));
		$this->assertEqual($result, $expected);

		$TestModel->id = 2;
		$result = $TestModel->find('neighbors', array('fields' => array('id')));
		$expected = array('prev' => array('Article' => array('id' => 1)), 'next' => array('Article' => array('id' => 3)));
		$this->assertEqual($result, $expected);

		$TestModel->id = 3;
		$result = $TestModel->find('neighbors', array('fields' => array('id')));
		$expected = array('prev' => array('Article' => array('id' => 2)), 'next' => null);
		$this->assertEqual($result, $expected);

		$TestModel->id = 1;
		$result = $TestModel->find('neighbors', array('recursive' => -1));
		$expected = array(
			'prev' => null,
			'next' => array(
				'Article' => array(
					'id' => 2,
					'user_id' => 3,
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				)
			)
		);
		$this->assertEqual($result, $expected);

		$TestModel->id = 2;
		$result = $TestModel->find('neighbors', array('recursive' => -1));
		$expected = array(
			'prev' => array(
				'Article' => array(
					'id' => 1,
					'user_id' => 1,
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				)
			),
			'next' => array(
				'Article' => array(
					'id' => 3,
					'user_id' => 1,
					'title' => 'Third Article',
					'body' => 'Third Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				)
			)
		);
		$this->assertEqual($result, $expected);

		$TestModel->id = 3;
		$result = $TestModel->find('neighbors', array('recursive' => -1));
		$expected = array(
			'prev' => array(
				'Article' => array(
					'id' => 2,
					'user_id' => 3,
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				)
			),
			'next' => null
		);
		$this->assertEqual($result, $expected);

		$TestModel->recursive = 0;
		$TestModel->id = 1;
		$one = $TestModel->read();
		$TestModel->id = 2;
		$two = $TestModel->read();
		$TestModel->id = 3;
		$three = $TestModel->read();

		$TestModel->id = 1;
		$result = $TestModel->find('neighbors');
		$expected = array('prev' => null, 'next' => $two);
		$this->assertEqual($result, $expected);

		$TestModel->id = 2;
		$result = $TestModel->find('neighbors');
		$expected = array('prev' => $one, 'next' => $three);
		$this->assertEqual($result, $expected);

		$TestModel->id = 3;
		$result = $TestModel->find('neighbors');
		$expected = array('prev' => $two, 'next' => null);
		$this->assertEqual($result, $expected);

		$TestModel->recursive = 2;
		$TestModel->id = 1;
		$one = $TestModel->read();
		$TestModel->id = 2;
		$two = $TestModel->read();
		$TestModel->id = 3;
		$three = $TestModel->read();

		$TestModel->id = 1;
		$result = $TestModel->find('neighbors', array('recursive' => 2));
		$expected = array('prev' => null, 'next' => $two);
		$this->assertEqual($result, $expected);

		$TestModel->id = 2;
		$result = $TestModel->find('neighbors', array('recursive' => 2));
		$expected = array('prev' => $one, 'next' => $three);
		$this->assertEqual($result, $expected);

		$TestModel->id = 3;
		$result = $TestModel->find('neighbors', array('recursive' => 2));
		$expected = array('prev' => $two, 'next' => null);
		$this->assertEqual($result, $expected);
	}
/**
 * testFindNeighboursLegacy method
 *
 * @return void
 * @access public
 */
	function testFindNeighboursLegacy() {
		$this->loadFixtures('User', 'Article');
		$TestModel =& new Article();

		$result = $TestModel->findNeighbours(null, 'Article.id', '2');
		$expected = array('prev' => array('Article' => array('id' => 1)), 'next' => array('Article' => array('id' => 3)));
		$this->assertEqual($result, $expected);

		$result = $TestModel->findNeighbours(null, 'Article.id', '3');
		$expected = array('prev' => array('Article' => array('id' => 2)), 'next' => array());
		$this->assertEqual($result, $expected);

		$result = $TestModel->findNeighbours(array('User.id' => 1), array('Article.id', 'Article.title'), 2);
		$expected = array(
			'prev' => array('Article' => array('id' => 1, 'title' => 'First Article')),
			'next' => array('Article' => array('id' => 3, 'title' => 'Third Article')),
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testFindCombinedRelations method
 *
 * @access public
 * @return void
 */
	function testFindCombinedRelations() {
		$this->loadFixtures('Apple', 'Sample');
		$TestModel =& new Apple();

		$result = $TestModel->find('all');

		$expected = array(
			array('Apple' => array(
				'id' => '1', 'apple_id' => '2', 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
					'Parent' => array('id' => '2', 'apple_id' => '1', 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
					'Sample' => array('id' => null, 'apple_id' => null, 'name' => null),
					'Child' => array(array('id' => '2', 'apple_id' => '1', 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => '2', 'apple_id' => '1', 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
					'Parent' => array('id' => '1', 'apple_id' => '2', 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
					'Sample' => array('id' => '2', 'apple_id' => '2', 'name' => 'sample2' ),
					'Child' => array(array('id' => '1',	'apple_id' => '2', 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						array('id' => '3', 'apple_id' => '2', 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
						array('id' => '4', 'apple_id' => '2', 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => '3', 'apple_id' => '2', 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
					'Parent' => array('id' => '2', 'apple_id' => '1', 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
					'Sample' => array('id' => '1', 'apple_id' => '3', 'name' => 'sample1'),
					'Child' => array()),
			array('Apple' => array(
				'id' => '4', 'apple_id' => '2', 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
					'Parent' => array('id' => '2', 'apple_id' => '1', 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
					'Sample' => array('id' => '3', 'apple_id' => '4', 'name' => 'sample3'),
					'Child' => array(array('id' => '6', 'apple_id' => '4', 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => '5', 'apple_id' => '5', 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
					'Parent' => array('id' => '5', 'apple_id' => '5', 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
					'Sample' => array('id' => '4', 'apple_id' => '5', 'name' => 'sample4'),
					'Child' => array(array('id' => '5', 'apple_id' => '5', 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => '6', 'apple_id' => '4', 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
					'Parent' => array('id' => '4', 'apple_id' => '2', 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
					'Sample' => array('id' => null, 'apple_id' => null, 'name' => null),
					'Child' => array(array('id' => '7', 'apple_id' => '6', 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => '7', 'apple_id' => '6', 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'),
					'Parent' => array('id' => '6', 'apple_id' => '4', 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
					'Sample' => array('id' => null, 'apple_id' => null, 'name' => null),
					'Child' => array()));
		$this->assertEqual($result, $expected);
	}
/**
 * testSaveEmpty method
 *
 * @access public
 * @return void
 */
	function testSaveEmpty() {
		$this->loadFixtures('Thread');
		$TestModel =& new Thread();
		$data = array();
		$expected = $TestModel->save($data);
		$this->assertFalse($expected);
	}

	// function testBasicValidation() {
	// 	$TestModel =& new ValidationTest();
	// 	$TestModel->testing = true;
	// 	$TestModel->set(array('title' => '', 'published' => 1));
	// 	$this->assertEqual($TestModel->invalidFields(), array('title' => 'This field cannot be left blank'));
	//
	// 	$TestModel->create();
	// 	$TestModel->set(array('title' => 'Hello', 'published' => 0));
	// 	$this->assertEqual($TestModel->invalidFields(), array('published' => 'This field cannot be left blank'));
	//
	// 	$TestModel->create();
	// 	$TestModel->set(array('title' => 'Hello', 'published' => 1, 'body' => ''));
	// 	$this->assertEqual($TestModel->invalidFields(), array('body' => 'This field cannot be left blank'));
	// }
/**
 * testFindAllWithConditionInChildQuery
 *
 * @todo external conditions like this are going to need to be revisited at some point
 * @access public
 * @return void
 */
	function testFindAllWithConditionInChildQuery() {
		$this->loadFixtures('Basket', 'FilmFile');

		$TestModel =& new Basket();
		$recursive = 3;
		$result = $TestModel->find('all', compact('conditions', 'recursive'));
		$expected = array(
			array(
				'Basket' => array(
					'id' => 1,
					'type' => 'nonfile',
					'name' => 'basket1',
					'object_id' => 1,
					'user_id' => 1,
				),
				'FilmFile' => array(
					'id' => '',
					'name' => '',
				)
			),
			array(
				'Basket' => array(
					'id' => 2,
					'type' => 'file',
					'name' => 'basket2',
					'object_id' => 2,
					'user_id' => 1,
				),
				'FilmFile' => array(
					'id' => 2,
					'name' => 'two',
				)
			),
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testFindAllWithConditionsHavingMixedDataTypes method
 *
 * @access public
 * @return void
 */
	function testFindAllWithConditionsHavingMixedDataTypes() {
		$this->loadFixtures('Article');
		$TestModel =& new Article();
		$expected = array(
			array(
				'Article' => array(
					'id' => 1,
					'user_id' => 1,
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				)
			),
			array(
				'Article' => array(
					'id' => 2,
					'user_id' => 3,
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				)
			)
		);
		$conditions = array('id' => array('1', 2));
		$recursive = -1;
		$result = $TestModel->find('all', compact('conditions', 'recursive'));
		$this->assertEqual($result, $expected);


		$conditions = array('id' => array('1', 2, '3.0'));
		$result = $TestModel->find('all', compact('recursive', 'conditions'));
		$expected = array(
			array(
				'Article' => array(
					'id' => 1,
					'user_id' => 1,
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				)
			),
			array(
				'Article' => array(
					'id' => 2,
					'user_id' => 3,
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				)
			),
			array(
				'Article' => array(
					'id' => 3,
					'user_id' => 1,
					'title' => 'Third Article',
					'body' => 'Third Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				)
			)
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testMultipleValidation method
 *
 * @access public
 * @return void
 */
	function testMultipleValidation() {
		$TestModel =& new ValidationTest();
	}
/**
 * Tests validation parameter order in custom validation methods
 *
 * @access public
 * @return void
 */
	function testValidationParams() {
		$TestModel =& new ValidationTest();
		$TestModel->validate['title'] = array('rule' => 'customValidatorWithParams', 'required' => true);
		$TestModel->create(array('title' => 'foo'));
		$TestModel->invalidFields();

		$expected = array(
			'data' => array('title' => 'foo'),
			'validator' => array(
				'rule' => 'customValidatorWithParams', 'on' => null,
				'last' => false, 'allowEmpty' => false, 'required' => true
			),
			'or' => true,
			'ignore_on_same' => 'id'
		);
		$this->assertEqual($TestModel->validatorParams, $expected);

		$TestModel->validate['title'] = array('rule' => 'customValidatorWithMessage', 'required' => true);
		$expected = array('title' => 'This field will *never* validate! Muhahaha!');
		$this->assertEqual($TestModel->invalidFields(), $expected);
	}
/**
 * Tests validation parameter fieldList in invalidFields
 *
 * @access public
 * @return void
 */
	function testInvalidFieldsWithFieldListParams() {
		$TestModel =& new ValidationTest();
		$TestModel->validate = $validate = array(
			'title' => array('rule' => 'customValidator', 'required' => true),
			'name' => array('rule' => 'allowEmpty', 'required' => true),
		);
		$TestModel->invalidFields(array('fieldList' => array('title')));
		$expected = array('title' => 'This field cannot be left blank');
		$this->assertEqual($TestModel->validationErrors, $expected);
		$TestModel->validationErrors = array();

		$TestModel->invalidFields(array('fieldList' => array('name')));
		$expected = array('name' => 'This field cannot be left blank');
		$this->assertEqual($TestModel->validationErrors, $expected);
		$TestModel->validationErrors = array();

		$TestModel->invalidFields(array('fieldList' => array('name', 'title')));
		$expected = array('name' => 'This field cannot be left blank', 'title' => 'This field cannot be left blank');
		$this->assertEqual($TestModel->validationErrors, $expected);

		$this->assertEqual($TestModel->validate, $validate);
	}
/**
 * Tests validation parameter order in custom validation methods
 *
 * @access public
 * @return void
 */
	function testAllowSimulatedFields() {
		$TestModel =& new ValidationTest();

		$TestModel->create(array('title' => 'foo', 'bar' => 'baz'));
		$expected = array('ValidationTest' => array('title' => 'foo', 'bar' => 'baz'));
		$this->assertEqual($TestModel->data, $expected);
	}
/**
 * Tests validation parameter order in custom validation methods
 *
 * @access public
 * @return void
 */
	function testInvalidAssociation() {
		$TestModel =& new ValidationTest();
		$this->assertNull($TestModel->getAssociated('Foo'));
	}
/**
 * testLoadModelSecondIteration method
 *
 * @access public
 * @return void
 */
	function testLoadModelSecondIteration() {
		$model = new ModelA();
		$this->assertIsA($model,'ModelA');

		$this->assertIsA($model->ModelB, 'ModelB');
		$this->assertIsA($model->ModelB->ModelD, 'ModelD');

		$this->assertIsA($model->ModelC, 'ModelC');
		$this->assertIsA($model->ModelC->ModelD, 'ModelD');
	}
/**
 * testRecursiveUnbind method
 *
 * @access public
 * @return void
 */
	function testRecursiveUnbind() {
		$this->loadFixtures('Apple', 'Sample');
		$TestModel =& new Apple();
		$TestModel->recursive = 2;

		$result = $TestModel->find('all');
		$expected = array(
			array('Apple' => array (
				'id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' =>'', 'apple_id' => '', 'name' => ''),
					'Child' => array(
						array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
							'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
								'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
								'Child' => array(
									array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
									array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
									array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))))),
			array('Apple' => array(
				'id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17',
						'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
						'Sample' => array(),
						'Child' => array(
							array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2',
						'Apple' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17')),
					'Child' => array(
						array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17',
							'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
							'Sample' => array(),
							'Child' => array(
								array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'))),
						array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17',
							'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
							'Sample' => array('id' => 1, 'apple_id' => 3, 'name' => 'sample1')),

						array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17',
							'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
							'Sample' => array('id' => 3, 'apple_id' => 4, 'name' => 'sample3'),
							'Child' => array(
								array('id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'))))),
			array('Apple' => array(
				'id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 1, 'apple_id' => 3, 'name' => 'sample1',
						'Apple' => array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17')),
					'Child' => array()),
			array('Apple' => array(
				'id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 3, 'apple_id' => 4, 'name' => 'sample3',
						'Apple' => array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17')),
					'Child' => array(
						array('id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17',
							'Parent' => array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
							'Sample' => array(),
							'Child' => array(
								array('id' => 7, 'apple_id' => 6, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'))))),
			array('Apple' => array(
				'id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17',
						'Parent' => array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 4, 'apple_id' => 5, 'name' => 'sample4'),
						'Child' => array(
							array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 4, 'apple_id' => 5, 'name' => 'sample4',
						'Apple' => array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17')),
					'Child' => array(
						array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17',
							'Parent' => array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
							'Sample' => array('id' => 4, 'apple_id' => 5, 'name' => 'sample4'),
							'Child' => array(
								array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))))),
			array('Apple' => array(
				'id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17',
						'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 3, 'apple_id' => 4, 'name' => 'sample3'),
						'Child' => array(
							array('id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => ''),
					'Child' => array(
						array('id' => 7, 'apple_id' => 6, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17',
							'Parent' => array('id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
							'Sample' => array()))),
			array('Apple' => array(
				'id' => 7, 'apple_id' => 6, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17',
						'Parent' => array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
						'Sample' => array(),
						'Child' => array(
							array('id' => 7, 'apple_id' => 6, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => ''),
					'Child' => array()));
		$this->assertEqual($result, $expected);

		$result = $TestModel->Parent->unbindModel(array('hasOne' => array('Sample')));
		$this->assertTrue($result);

		$result = $TestModel->find('all');
		$expected = array(
			array('Apple' => array(
				'id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' =>'', 'apple_id' => '', 'name' => ''),
					'Child' => array(
						array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
							'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
								'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
								'Child' => array(
									array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
									array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
									array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))))),
			array('Apple' => array(
				'id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17',
						'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2',
						'Apple' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17')),
					'Child' => array(
						array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17',
							'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
							'Sample' => array(),
							'Child' => array(
								array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'))),
						array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17',
							'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
							'Sample' => array('id' => 1, 'apple_id' => 3, 'name' => 'sample1')),

						array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17',
							'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
							'Sample' => array('id' => 3, 'apple_id' => 4, 'name' => 'sample3'),
							'Child' => array(
								array('id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'))))),
			array('Apple' => array(
				'id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 1, 'apple_id' => 3, 'name' => 'sample1',
						'Apple' => array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17')),
					'Child' => array()),
			array('Apple' => array(
				'id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 3, 'apple_id' => 4, 'name' => 'sample3',
						'Apple' => array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17')),
					'Child' => array(
						array('id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17',
							'Parent' => array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
							'Sample' => array(),
							'Child' => array(
								array('id' => 7, 'apple_id' => 6, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'))))),
			array('Apple' => array(
				'id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17',
						'Parent' => array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 4, 'apple_id' => 5, 'name' => 'sample4',
						'Apple' => array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17')),
					'Child' => array(
						array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17',
							'Parent' => array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
							'Sample' => array('id' => 4, 'apple_id' => 5, 'name' => 'sample4'),
							'Child' => array(
								array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))))),
			array('Apple' => array(
				'id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17',
						'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => ''),
					'Child' => array(
						array('id' => 7, 'apple_id' => 6, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17',
							'Parent' => array('id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
							'Sample' => array()))),
			array('Apple' => array(
				'id' => 7, 'apple_id' => 6, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17',
						'Parent' => array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 7, 'apple_id' => 6, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => ''),
					'Child' => array()));
		$this->assertEqual($result, $expected);

		$result = $TestModel->Parent->unbindModel(array('hasOne' => array('Sample')));
		$this->assertTrue($result);

		$result = $TestModel->unbindModel(array('hasMany' => array('Child')));
		$this->assertTrue($result);

		$result = $TestModel->find('all');
		$expected = array(array('Apple' => array (
				'id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' =>'', 'apple_id' => '', 'name' => '')),
			array('Apple' => array(
				'id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17',
						'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2',
						'Apple' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 1, 'apple_id' => 3, 'name' => 'sample1',
						'Apple' => array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 3, 'apple_id' => 4, 'name' => 'sample3',
						'Apple' => array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17',
						'Parent' => array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 4, 'apple_id' => 5, 'name' => 'sample4',
						'Apple' => array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17',
						'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => '')),
			array('Apple' => array(
				'id' => 7, 'apple_id' => 6, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17',
						'Parent' => array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 7, 'apple_id' => 6, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => '')));
		$this->assertEqual($result, $expected);

		$result = $TestModel->unbindModel(array('hasMany' => array('Child')));
		$this->assertTrue($result);

		$result = $TestModel->Sample->unbindModel(array('belongsTo' => array('Apple')));
		$this->assertTrue($result);

		$result = $TestModel->find('all');
		$expected = array(
			array('Apple' => array(
				'id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' =>'', 'apple_id' => '', 'name' => '')),
			array('Apple' => array(
				'id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17',
						'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
						'Sample' => array(),
						'Child' => array(
							array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2')),
			array('Apple' => array(
				'id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 1, 'apple_id' => 3, 'name' => 'sample1')),
			array('Apple' => array(
				'id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 3, 'apple_id' => 4, 'name' => 'sample3')),
			array('Apple' => array(
				'id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17',
						'Parent' => array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 4, 'apple_id' => 5, 'name' => 'sample4'),
						'Child' => array(
							array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 4, 'apple_id' => 5, 'name' => 'sample4')),
			array('Apple' => array(
				'id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17',
						'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 3, 'apple_id' => 4, 'name' => 'sample3'),
						'Child' => array(
							array('id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => '')),
			array('Apple' => array(
				'id' => 7, 'apple_id' => 6, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17',
						'Parent' => array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
						'Sample' => array(),
						'Child' => array(
							array('id' => 7, 'apple_id' => 6, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => '')));
		$this->assertEqual($result, $expected);

		$result = $TestModel->Parent->unbindModel(array('belongsTo' => array('Parent')));
		$this->assertTrue($result);

		$result = $TestModel->unbindModel(array('hasMany' => array('Child')));
		$this->assertTrue($result);

		$result = $TestModel->find('all');
		$expected = array(array('Apple' => array (
				'id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' =>'', 'apple_id' => '', 'name' => '')),
			array('Apple' => array(
				'id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17',
						'Sample' => array(),
						'Child' => array(
							array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2',
						'Apple' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 1, 'apple_id' => 3, 'name' => 'sample1',
						'Apple' => array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 3, 'apple_id' => 4, 'name' => 'sample3',
						'Apple' => array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17',
						'Sample' => array('id' => 4, 'apple_id' => 5, 'name' => 'sample4'),
						'Child' => array(
							array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 4, 'apple_id' => 5, 'name' => 'sample4',
						'Apple' => array('id' => 5, 'apple_id' => 5, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 4, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17',
						'Sample' => array('id' => 3, 'apple_id' => 4, 'name' => 'sample3'),
						'Child' => array(
							array('id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => '')),
			array('Apple' => array(
				'id' => 7, 'apple_id' => 6, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 6, 'apple_id' => 4, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17',
						'Sample' => array(),
						'Child' => array(
							array('id' => 7, 'apple_id' => 6, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => '')));
		$this->assertEqual($result, $expected);
	}
/**
 * testSelfAssociationAfterFind method
 *
 * @access public
 * @return void
 */
	function testSelfAssociationAfterFind() {
		$this->loadFixtures('Apple');
		$afterFindModel = new NodeAfterFind();
		$afterFindModel->recursive = 3;
		$afterFindData = $afterFindModel->find('all');

		$duplicateModel = new NodeAfterFind();
		$duplicateModel->recursive = 3;
		$duplicateModelData = $duplicateModel->find('all');

		$noAfterFindModel = new NodeNoAfterFind();
		$noAfterFindModel->recursive = 3;
		$noAfterFindData = $noAfterFindModel->find('all');

		$this->assertFalse($afterFindModel == $noAfterFindModel);
		// Limitation of PHP 4 and PHP 5 > 5.1.6 when comparing recursive objects
		if (PHP_VERSION === '5.1.6') {
			$this->assertFalse($afterFindModel != $duplicateModel);
		}
		$this->assertEqual($afterFindData, $noAfterFindData);
	}
/**
 * testAutoSaveUuid method
 *
 * @access public
 * @return void
 */
	function testAutoSaveUuid() {
		// SQLite does not support non-integer primary keys, and SQL Server
		// is still having problems with custom PK's
		if ($this->db->config['driver'] == 'sqlite' || $this->db->config['driver'] == 'mssql') {
			return;
		}

		$this->loadFixtures('Uuid');
		$TestModel =& new Uuid();

		$TestModel->save(array('title' => 'Test record'));
		$result = $TestModel->findByTitle('Test record');
		$this->assertEqual(array_keys($result['Uuid']), array('id', 'title', 'count', 'created', 'updated'));
		$this->assertEqual(strlen($result['Uuid']['id']), 36);
	}
/**
 * testZeroDefaultFieldValue method
 *
 * @access public
 * @return void
 */
	function testZeroDefaultFieldValue() {
		$this->skipIf(
			$this->db->config['driver'] == 'sqlite',
			'SQLite uses loose typing, this operation is unsupported'
		);
		$this->loadFixtures('DataTest');
		$TestModel =& new DataTest();

		$TestModel->create(array());
		$TestModel->save();
		$result = $TestModel->findById($TestModel->id);
		$this->assertIdentical($result['DataTest']['count'], '0');
		$this->assertIdentical($result['DataTest']['float'], '0');
	}

/**
 * testNonNumericHabtmJoinKey method
 *
 * @access public
 * @return void
 */
	function testNonNumericHabtmJoinKey() {
		$this->loadFixtures('Post', 'Tag', 'PostsTag');
		$Post =& new Post();
		$Post->bind('Tag', array('type' => 'hasAndBelongsToMany'));
		$Post->Tag->primaryKey = 'tag';

		$result = $Post->find('all');
		$expected = array(
			array(
				'Post' => array('id' => '1', 'author_id' => '1', 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				'Author' => array('id' => null, 'user' => null, 'password' => null, 'created' => null, 'updated' => null, 'test' => 'working'),
				'Tag' => array(
					array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
					array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31'),
				)
			),
			array(
				'Post' => array('id' => '2', 'author_id' => '3', 'title' => 'Second Post', 'body' => 'Second Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
				'Author' => array('id' => null, 'user' => null, 'password' => null, 'created' => null, 'updated' => null, 'test' => 'working'),
				'Tag' => array(
					array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
					array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
				)
			),
			array(
				'Post' => array('id' => '3', 'author_id' => '1', 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'),
				'Author' => array('id' => null, 'user' => null, 'password' => null, 'created' => null, 'updated' => null, 'test' => 'working'),
				'Tag' => array()
			)
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testDeconstructFields method
 *
 * @access public
 * @return void
 */
	function testDeconstructFields() {
		$this->loadFixtures('Apple');
		$TestModel =& new Apple();

		$data['Apple']['created']['year'] = '';
		$data['Apple']['created']['month'] = '';
		$data['Apple']['created']['day'] = '';
		$data['Apple']['created']['hour'] = '';
		$data['Apple']['created']['min'] = '';
		$data['Apple']['created']['sec'] = '';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('created'=> ''));
		$this->assertEqual($TestModel->data, $expected);

		$data = array();
		$data['Apple']['created']['year'] = '2007';
		$data['Apple']['created']['month'] = '08';
		$data['Apple']['created']['day'] = '20';
		$data['Apple']['created']['hour'] = '';
		$data['Apple']['created']['min'] = '';
		$data['Apple']['created']['sec'] = '';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('created'=> '2007-08-20 00:00:00'));
		$this->assertEqual($TestModel->data, $expected);

		$data = array();
		$data['Apple']['created']['year'] = '2007';
		$data['Apple']['created']['month'] = '08';
		$data['Apple']['created']['day'] = '20';
		$data['Apple']['created']['hour'] = '10';
		$data['Apple']['created']['min'] = '12';
		$data['Apple']['created']['sec'] = '';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('created'=> '2007-08-20 10:12:00'));
		$this->assertEqual($TestModel->data, $expected);

		$data = array();
		$data['Apple']['created']['year'] = '2007';
		$data['Apple']['created']['month'] = '';
		$data['Apple']['created']['day'] = '12';
		$data['Apple']['created']['hour'] = '20';
		$data['Apple']['created']['min'] = '';
		$data['Apple']['created']['sec'] = '';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('created'=> ''));
		$this->assertEqual($TestModel->data, $expected);

		$data = array();
		$data['Apple']['created']['hour'] = '20';
		$data['Apple']['created']['min'] = '33';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('created'=> ''));
		$this->assertEqual($TestModel->data, $expected);

		$data = array();
		$data['Apple']['created']['hour'] = '20';
		$data['Apple']['created']['min'] = '33';
		$data['Apple']['created']['sec'] = '33';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('created'=> ''));
		$this->assertEqual($TestModel->data, $expected);

		$data = array();
		$data['Apple']['created']['hour'] = '13';
		$data['Apple']['created']['min'] = '00';
		$data['Apple']['date']['year'] = '2006';
		$data['Apple']['date']['month'] = '12';
		$data['Apple']['date']['day'] = '25';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('created'=> '', 'date'=> '2006-12-25'));
		$this->assertEqual($TestModel->data, $expected);

		$data = array();
		$data['Apple']['created']['year'] = '2007';
		$data['Apple']['created']['month'] = '08';
		$data['Apple']['created']['day'] = '20';
		$data['Apple']['created']['hour'] = '10';
		$data['Apple']['created']['min'] = '12';
		$data['Apple']['created']['sec'] = '09';
		$data['Apple']['date']['year'] = '2006';
		$data['Apple']['date']['month'] = '12';
		$data['Apple']['date']['day'] = '25';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('created'=> '2007-08-20 10:12:09', 'date'=> '2006-12-25'));
		$this->assertEqual($TestModel->data, $expected);

		$data = array();
		$data['Apple']['created']['year'] = '--';
		$data['Apple']['created']['month'] = '--';
		$data['Apple']['created']['day'] = '--';
		$data['Apple']['created']['hour'] = '--';
		$data['Apple']['created']['min'] = '--';
		$data['Apple']['created']['sec'] = '--';
		$data['Apple']['date']['year'] = '--';
		$data['Apple']['date']['month'] = '--';
		$data['Apple']['date']['day'] = '--';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('created'=> '', 'date'=> ''));
		$this->assertEqual($TestModel->data, $expected);

		$data = array();
		$data['Apple']['created']['year'] = '2007';
		$data['Apple']['created']['month'] = '--';
		$data['Apple']['created']['day'] = '20';
		$data['Apple']['created']['hour'] = '10';
		$data['Apple']['created']['min'] = '12';
		$data['Apple']['created']['sec'] = '09';
		$data['Apple']['date']['year'] = '2006';
		$data['Apple']['date']['month'] = '12';
		$data['Apple']['date']['day'] = '25';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('created'=> '', 'date'=> '2006-12-25'));
		$this->assertEqual($TestModel->data, $expected);

		$data = array();
		$data['Apple']['date']['year'] = '2006';
		$data['Apple']['date']['month'] = '12';
		$data['Apple']['date']['day'] = '25';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('date'=> '2006-12-25'));
		$this->assertEqual($TestModel->data, $expected);

		$data = array();
		$data['Apple']['mytime']['hour'] = '03';
		$data['Apple']['mytime']['min'] = '04';
		$data['Apple']['mytime']['sec'] = '04';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('mytime'=> '03:04:04'));
		$this->assertEqual($TestModel->data, $expected);

		$data = array();
		$data['Apple']['mytime']['hour'] = '3';
		$data['Apple']['mytime']['min'] = '4';
		$data['Apple']['mytime']['sec'] = '4';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple' => array('mytime'=> '03:04:04'));
		$this->assertEqual($TestModel->data, $expected);

		$data = array();
		$data['Apple']['mytime']['hour'] = '03';
		$data['Apple']['mytime']['min'] = '4';
		$data['Apple']['mytime']['sec'] = '4';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('mytime'=> '03:04:04'));
		$this->assertEqual($TestModel->data, $expected);
	}
/**
 * testTablePrefixSwitching method
 *
 * @access public
 * @return void
 */
	function testTablePrefixSwitching() {
		ConnectionManager::create('database1', array_merge($this->db->config, array('prefix' => 'aaa_')));
		ConnectionManager::create('database2', array_merge($this->db->config, array('prefix' => 'bbb_')));

		$db1 = ConnectionManager::getDataSource('database1');
		$db2 = ConnectionManager::getDataSource('database2');

		$TestModel = new Apple();
		$TestModel->setDataSource('database1');
		$this->assertEqual($this->db->fullTableName($TestModel, false), 'aaa_apples');
		$this->assertEqual($db1->fullTableName($TestModel, false), 'aaa_apples');
		$this->assertEqual($db2->fullTableName($TestModel, false), 'aaa_apples');

		$TestModel->setDataSource('database2');
		$this->assertEqual($this->db->fullTableName($TestModel, false), 'bbb_apples');
		$this->assertEqual($db1->fullTableName($TestModel, false), 'bbb_apples');
		$this->assertEqual($db2->fullTableName($TestModel, false), 'bbb_apples');

		$TestModel = new Apple();
		$TestModel->tablePrefix = 'custom_';
		$this->assertEqual($this->db->fullTableName($TestModel, false), 'custom_apples');
		$TestModel->setDataSource('database1');
		$this->assertEqual($this->db->fullTableName($TestModel, false), 'custom_apples');
		$this->assertEqual($db1->fullTableName($TestModel, false), 'custom_apples');

		$TestModel = new Apple();
		$TestModel->setDataSource('database1');
		$this->assertEqual($this->db->fullTableName($TestModel, false), 'aaa_apples');
		$TestModel->tablePrefix = '';
		$TestModel->setDataSource('database2');
		$this->assertEqual($db2->fullTableName($TestModel, false), 'apples');
		$this->assertEqual($db1->fullTableName($TestModel, false), 'apples');

		$TestModel->tablePrefix = null;
		$TestModel->setDataSource('database1');
		$this->assertEqual($db2->fullTableName($TestModel, false), 'aaa_apples');
		$this->assertEqual($db1->fullTableName($TestModel, false), 'aaa_apples');

		$TestModel->tablePrefix = false;
		$TestModel->setDataSource('database2');
		$this->assertEqual($db2->fullTableName($TestModel, false), 'apples');
		$this->assertEqual($db1->fullTableName($TestModel, false), 'apples');
	}

/**
 * testDynamicBehaviorAttachment method
 *
 * @access public
 * @return void
 */
	function testDynamicBehaviorAttachment() {
		$this->loadFixtures('Apple');
		$TestModel =& new Apple();
		$this->assertEqual($TestModel->Behaviors->attached(), array());

		$TestModel->Behaviors->attach('Tree', array('left' => 'left_field', 'right' => 'right_field'));
		$this->assertTrue(is_object($TestModel->Behaviors->Tree));
		$this->assertEqual($TestModel->Behaviors->attached(), array('Tree'));

		$expected = array(
			'parent' => 'parent_id', 'left' => 'left_field', 'right' => 'right_field', 'scope' => '1 = 1',
			'type' => 'nested', '__parentChange' => false, 'recursive' => -1
		);
		$this->assertEqual($TestModel->Behaviors->Tree->settings['Apple'], $expected);

		$expected['enabled'] = false;
		$TestModel->Behaviors->attach('Tree', array('enabled' => false));
		$this->assertEqual($TestModel->Behaviors->Tree->settings['Apple'], $expected);
		$this->assertEqual($TestModel->Behaviors->attached(), array('Tree'));

		$TestModel->Behaviors->detach('Tree');
		$this->assertEqual($TestModel->Behaviors->attached(), array());
		$this->assertFalse(isset($TestModel->Behaviors->Tree));
	}

/**
 * Tests cross database joins.  Requires $test and $test2 to both be set in DATABASE_CONFIG
 * NOTE: When testing on MySQL, you must set 'persistent' => false on *both* database connections,
 * or one connection will step on the other.
 */
	function testCrossDatabaseJoins() {
		$config = new DATABASE_CONFIG();

		if (!isset($config->test) || !isset($config->test2)) {
			echo "<br />Primary and secondary test databases not configured, skipping cross-database join tests<br />";
			echo "To run these tests, you must define \$test and \$test2 in your database configuration.<br />";
			return;
		}
		$this->loadFixtures('Article', 'Tag', 'ArticlesTag', 'User', 'Comment');
		$TestModel =& new Article();

		$expected = array(
			array(
				'Article' => array('id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				'User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'Comment' => array(
					array('id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
					array('id' => '2', 'article_id' => '1', 'user_id' => '4', 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'),
					array('id' => '3', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Third Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'),
					array('id' => '4', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Fourth Comment for First Article', 'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31')
				),
				'Tag' => array(
					array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
					array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31')
				)
			),
			array(
				'Article' => array('id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
				'User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'),
				'Comment' => array(
					array('id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'),
					array('id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31')
				),
				'Tag' => array(
					array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
					array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
				)
			),
			array(
				'Article' => array('id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'),
				'User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'Comment' => array(),
				'Tag' => array()
			)
		);
		$this->assertEqual($TestModel->find('all'), $expected);

		$db2 =& ConnectionManager::getDataSource('test2');

		foreach (array('User', 'Comment') as $class) {
			$this->_fixtures[$this->_fixtureClassMap[$class]]->create($db2);
			$this->_fixtures[$this->_fixtureClassMap[$class]]->insert($db2);
			$this->db->truncate(Inflector::pluralize(Inflector::underscore($class)));
		}

		$this->assertEqual($TestModel->User->find('all'), array());
		$this->assertEqual($TestModel->Comment->find('all'), array());
		$this->assertEqual($TestModel->find('count'), 3);

		$TestModel->User->setDataSource('test2');
		$TestModel->Comment->setDataSource('test2');

		$result = Set::extract($TestModel->User->find('all'), '{n}.User.id');
		$this->assertEqual($result, array('1', '2', '3', '4'));
		$this->assertEqual($TestModel->find('all'), $expected);

		$TestModel->Comment->unbindModel(array('hasOne' => array('Attachment')));
		$expected = array(
			array(
				'Comment' => array('id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
				'User' => array('id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'),
				'Article' => array('id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31')
			),
			array(
				'Comment' => array('id' => '2', 'article_id' => '1', 'user_id' => '4', 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'),
				'User' => array('id' => '4', 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'),
				'Article' => array('id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31')
			),
			array(
				'Comment' => array('id' => '3', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Third Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'),
				'User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'Article' => array('id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31')
			),
			array(
				'Comment' => array('id' => '4', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Fourth Comment for First Article', 'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'),
				'User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'Article' => array('id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31')
			),
			array(
				'Comment' => array('id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'),
				'User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'Article' => array('id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31')
			),
			array(
				'Comment' => array('id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31'),
				'User' => array('id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'),
				'Article' => array('id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31')
			)
		);
		$this->assertEqual($TestModel->Comment->find('all'), $expected);

		foreach (array('User', 'Comment') as $class) {
			$this->_fixtures[$this->_fixtureClassMap[$class]]->drop($db2);
		}
	}
/**
 * testDisplayField method
 *
 * @access public
 * @return void
 */
	function testDisplayField() {
		$this->loadFixtures('Post', 'Comment', 'Person');
		$Post = new Post();
		$Comment = new Comment();
		$Person = new Person();

		$this->assertEqual($Post->displayField, 'title');
		$this->assertEqual($Person->displayField, 'name');
		$this->assertEqual($Comment->displayField, 'id');
	}
/**
 * testSchema method
 *
 * @access public
 * @return void
 */
	function testSchema() {
		$Post = new Post();

		$result = $Post->schema();
		$columns = array('id', 'author_id', 'title', 'body', 'published', 'created', 'updated');
		$this->assertEqual(array_keys($result), $columns);

		$types = array('integer', 'integer', 'string', 'text', 'string', 'datetime', 'datetime');
		$this->assertEqual(Set::extract(array_values($result), '{n}.type'), $types);

		$result = $Post->schema('body');
		$this->assertEqual($result['type'], 'text');
		$this->assertNull($Post->schema('foo'));

		$this->assertEqual($Post->getColumnTypes(), array_combine($columns, $types));
	}
/**
 * testOldQuery method
 *
 * @access public
 * @return void
 */
	function testOldQuery() {
		$this->loadFixtures('Article');
		$Article =& new Article();

		$query = 'SELECT title FROM ' . $this->db->fullTableName('articles') . ' WHERE ' . $this->db->fullTableName('articles') . '.id IN (1,2)';
		$results = $Article->query($query);
		$this->assertTrue(is_array($results));
		$this->assertEqual(count($results), 2);

		$query = 'SELECT title, body FROM ' . $this->db->fullTableName('articles') . ' WHERE ' . $this->db->fullTableName('articles') . '.id = 1';
		$results = $Article->query($query, false);
		$this->assertTrue(!isset($this->db->_queryCache[$query]));
		$this->assertTrue(is_array($results));

		$query = 'SELECT title, id FROM ' . $this->db->fullTableName('articles') . ' WHERE ' . $this->db->fullTableName('articles') . '.published = ' . $this->db->value('Y');
		$results = $Article->query($query, true);
		$this->assertTrue(isset($this->db->_queryCache[$query]));
		$this->assertTrue(is_array($results));
	}
/**
 * testPreparedQuery method
 *
 * @access public
 * @return void
 */
	function testPreparedQuery() {
		$this->loadFixtures('Article');
		$Article =& new Article();
		$this->db->_queryCache = array();

		$finalQuery = 'SELECT title, published FROM ' . $this->db->fullTableName('articles') . ' WHERE ' . $this->db->fullTableName('articles') . '.id = ' . $this->db->value(1) . ' AND ' . $this->db->fullTableName('articles') . '.published = ' . $this->db->value('Y');

		$query = 'SELECT title, published FROM ' . $this->db->fullTableName('articles') . ' WHERE ' . $this->db->fullTableName('articles') . '.id = ? AND ' . $this->db->fullTableName('articles') . '.published = ?';

		$params = array(1, 'Y');
		$result = $Article->query($query, $params);
		$expected = array('0' => array($this->db->fullTableName('articles', false) => array('title' => 'First Article', 'published' => 'Y')));
		if (isset($result[0][0])) {
			$expected[0][0] = $expected[0][$this->db->fullTableName('articles', false)];
			unset($expected[0][$this->db->fullTableName('articles', false)]);
		}
		$this->assertEqual($result, $expected);
		$this->assertTrue(isset($this->db->_queryCache[$finalQuery]));

		$finalQuery = 'SELECT id, created FROM ' . $this->db->fullTableName('articles') . ' WHERE ' . $this->db->fullTableName('articles') . '.title = ' . $this->db->value('First Article');
		$query = 'SELECT id, created FROM ' . $this->db->fullTableName('articles') . '  WHERE ' . $this->db->fullTableName('articles') . '.title = ?';
		$params = array('First Article');
		$result = $Article->query($query, $params, false);
		$this->assertTrue(is_array($result));
		$this->assertTrue(isset($result[0][$this->db->fullTableName('articles', false)]) || isset($result[0][0]));
		$this->assertFalse(isset($this->db->_queryCache[$finalQuery]));

		$query = 'SELECT title FROM ' . $this->db->fullTableName('articles') . ' WHERE ' . $this->db->fullTableName('articles') . '.title LIKE ?';
		$params = array('%First%');
		$result = $Article->query($query, $params);
		$this->assertTrue(is_array($result));
		$this->assertTrue(
			isset($result[0][$this->db->fullTableName('articles', false)]['title']) ||
			isset($result[0][0]['title'])
		);

		//related to ticket #5035
		$query = 'SELECT title FROM ' . $this->db->fullTableName('articles') . ' WHERE title = ? AND published = ?';
		$params = array('First? Article', 'Y');
		$Article->query($query, $params);
		$expected = 'SELECT title FROM ' . $this->db->fullTableName('articles') . " WHERE title = 'First? Article' AND published = 'Y'";
		$this->assertTrue(isset($this->db->_queryCache[$expected]));

	}
/**
 * testParameterMismatch method
 *
 * @access public
 * @return void
 */
	function testParameterMismatch() {
		$this->loadFixtures('Article');
		$Article =& new Article();

		$query = 'SELECT * FROM ' . $this->db->fullTableName('articles') . ' WHERE ' . $this->db->fullTableName('articles') . '.published = ? AND ' . $this->db->fullTableName('articles') . '.user_id = ?';
		$params = array('Y');
		$this->expectError();
		ob_start();
		$result = $Article->query($query, $params);
		ob_end_clean();
		$this->assertEqual($result, null);
	}
/**
 * testVeryStrangeUseCase method
 *
 * @access public
 * @return void
 */
	function testVeryStrangeUseCase() {
		if ($this->db->config['driver'] == 'mssql') {
			return;
		}

		$this->loadFixtures('Article');
		$Article =& new Article();

		$query = 'SELECT * FROM ? WHERE ? = ? AND ? = ?';
		$param = array($this->db->fullTableName('articles'), $this->db->fullTableName('articles') . '.user_id', '3', $this->db->fullTableName('articles') . '.published', 'Y');
		$this->expectError();
		ob_start();
		$result = $Article->query($query, $param);
		ob_end_clean();
	}
/**
 * testUnderscoreFieldSave method
 *
 * @access public
 * @return void
 */
	function testUnderscoreFieldSave() {
		$this->loadFixtures('UnderscoreField');
		$UnderscoreField =& new UnderscoreField();

		$currentCount = $UnderscoreField->find('count');
		$this->assertEqual($currentCount, 3);
		$data = array('UnderscoreField' => array(
			'user_id' => '1',
			'my_model_has_a_field' => 'Content here',
			'body' => 'Body',
			'published' => 'Y',
			'another_field' => 4
		));
		$ret = $UnderscoreField->save($data);
		$this->assertTrue($ret);

		$currentCount = $UnderscoreField->find('count');
		$this->assertEqual($currentCount, 4);
	}
/**
 * testGroupBy method
 *
 * @access public
 * @return void
 */
	function testGroupBy() {
		$this->loadFixtures('Project', 'Product', 'Thread', 'Message', 'Bid');
		$Thread =& new Thread();
		$Product =& new Product();

		$result = $Thread->find('all', array(
			'group' => 'Thread.project_id'
		));

		$expected = array(
			array(
				'Thread' => array('id' => 1, 'project_id' => 1, 'name' => 'Project 1, Thread 1'),
				'Project' => array('id' => 1, 'name' => 'Project 1'),
				'Message' => array(array('id' => 1, 'thread_id' => 1, 'name' => 'Thread 1, Message 1')),
			),
			array(
				'Thread' => array('id' => 3, 'project_id' => 2, 'name' => 'Project 2, Thread 1'),
				'Project' => array('id' => 2, 'name' => 'Project 2'),
				'Message' => array(array('id' => 3, 'thread_id' => 3, 'name' => 'Thread 3, Message 1')),
			),
		);
		$this->assertEqual($result, $expected);

		$rows = $Thread->find('all', array(
			'group' => 'Thread.project_id', 'fields' => array('Thread.project_id', 'COUNT(*) AS total')
		));
		$result = array();
		foreach($rows as $row) {
			$result[$row['Thread']['project_id']] = $row[0]['total'];
		}
		$expected = array(
			1 => 2,
			2 => 1
		);
		$this->assertEqual($result, $expected);

		$rows = $Thread->find('all', array(
			'group' => 'Thread.project_id', 'fields' => array('Thread.project_id', 'COUNT(*) AS total'), 'order'=> 'Thread.project_id'
		));
		$result = array();
		foreach($rows as $row) {
			$result[$row['Thread']['project_id']] = $row[0]['total'];
		}
		$expected = array(
			1 => 2,
			2 => 1
		);
		$this->assertEqual($result, $expected);

		$result = $Thread->find('all', array(
			'conditions' => array('Thread.project_id' => 1), 'group' => 'Thread.project_id')
		);
		$expected = array(
			array(
				'Thread' => array('id' => 1, 'project_id' => 1, 'name' => 'Project 1, Thread 1'),
				'Project' => array('id' => 1, 'name' => 'Project 1'),
				'Message' => array(array('id' => 1, 'thread_id' => 1, 'name' => 'Thread 1, Message 1')),
			)
		);
		$this->assertEqual($result, $expected);

		$result = $Thread->find('all', array(
			'conditions' => array('Thread.project_id' => 1), 'group' => 'Thread.project_id, Project.id')
		);
		$this->assertEqual($result, $expected);

		$result = $Thread->find('all', array(
			'conditions' => array('Thread.project_id' => 1), 'group' => 'project_id')
		);
		$this->assertEqual($result, $expected);


		$result = $Thread->find('all', array(
			'conditions' => array('Thread.project_id' => 1), 'group' => array('project_id'))
		);
		$this->assertEqual($result, $expected);


		$result = $Thread->find('all', array(
			'conditions' => array('Thread.project_id' => 1), 'group' => array('project_id', 'Project.id'))
		);
		$this->assertEqual($result, $expected);


		$result = $Thread->find('all', array(
			'conditions' => array('Thread.project_id' => 1), 'group' => array('Thread.project_id', 'Project.id'))
		);
		$this->assertEqual($result, $expected);


		$expected = array(
			array('Product' => array('type' => 'Clothing'), array('price' => 32)),
			array('Product' => array('type' => 'Food'), array('price' => 9)),
			array('Product' => array('type' => 'Music'), array( 'price' => 4)),
			array('Product' => array('type' => 'Toy'), array('price' => 3))
		);
		$result = $Product->find('all',array('fields'=>array('Product.type','MIN(Product.price) as price'), 'group'=> 'Product.type'));
		$this->assertEqual($result, $expected);

		$result = $Product->find('all', array('fields'=>array('Product.type','MIN(Product.price) as price'), 'group'=> array('Product.type')));
		$this->assertEqual($result, $expected);
	}
	/**
 * testSaveDateAsFirstEntry method
 *
 * @access public
 * @return void
 */
	function testSaveDateAsFirstEntry() {
		$this->loadFixtures('Article');

		$Article =& new Article();

		$data = array('Article' => array(
			'created' => array(
				'day' => '1', 'month' => '1', 'year' => '2008'
			),
			'title' => 'Test Title',
			// schreck - Jul 30, 2008 - should this be set to something else?
			'user_id' => 1
		));
		$Article->create();
		$this->assertTrue($Article->save($data));

		$testResult = $Article->find(array('Article.title' => 'Test Title'));

		$this->assertEqual($testResult['Article']['title'], $data['Article']['title']);
		$this->assertEqual($testResult['Article']['created'], '2008-01-01 00:00:00');

	}
/**
 * testDeleteDependentWithConditions method
 *
 * @access public
 * @return void
 */
	function testDeleteDependentWithConditions() {
		$this->loadFixtures('Cd','Book','OverallFavorite');

		$Cd =& new Cd();
		$OverallFavorite =& new OverallFavorite();

		$Cd->del(1);

		$result = $OverallFavorite->find('all', array('fields' => array('model_type', 'model_id', 'priority')));
		$expected = array(array('OverallFavorite' => array('model_type' => 'Book', 'model_id' => 1, 'priority' => 2)));

		$this->assertTrue(is_array($result));
		$this->assertEqual($result, $expected);
	}
/**
 * testSaveAllHasManyValidationOnly method
 *
 * @access public
 * @return void
 */
	function testSaveAllHasManyValidationOnly() {
		$this->loadFixtures('Article', 'Comment');
		$TestModel =& new Article();
		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = array();
		$TestModel->Comment->validate = array('comment' => VALID_NOT_EMPTY);

		$result = $TestModel->saveAll(
			array(
				'Article' => array('id' => 2),
				'Comment' => array(
					array('id' => 1, 'comment' => '', 'published' => 'Y', 'user_id' => 1),
					array('id' => 2, 'comment' => 'comment', 'published' => 'Y', 'user_id' => 1),
				)
			),
			array('validate' => 'only')
		);
		$this->assertFalse($result);

		$result = $TestModel->saveAll(
			array(
				'Article' => array('id' => 2),
				'Comment' => array(
					array('id' => 1, 'comment' => '', 'published' => 'Y', 'user_id' => 1),
					array('id' => 2, 'comment' => 'comment', 'published' => 'Y', 'user_id' => 1),
					array('id' => 3, 'comment' => '', 'published' => 'Y', 'user_id' => 1),
				)
			),
			array('validate' => 'only', 'atomic' => false)
		);
		$expected = array('Article' => true, 'Comment' => array(false, true, false));
		$this->assertIdentical($result, $expected);

		$expected = array('Comment' => array(
			0 => array('comment' => 'This field cannot be left blank'),
			2 => array('comment' => 'This field cannot be left blank')
		));
		$this->assertEqual($TestModel->validationErrors, $expected);

		$expected = array(
			0 => array('comment' => 'This field cannot be left blank'),
			2 => array('comment' => 'This field cannot be left blank')
		);
		$this->assertEqual($TestModel->Comment->validationErrors, $expected);
	}
/**
 * testPkInHabtmLinkModel method
 *
 * @access public
	 * @return void
 */
	function testPkInHabtmLinkModel() {
		//Test Nonconformant Models
		$this->loadFixtures('Content', 'ContentAccount', 'Account');
		$TestModel =& new Content();
		$this->assertEqual($TestModel->ContentAccount->primaryKey, 'iContentAccountsId');

		//test conformant models with no PK in the join table
		$this->loadFixtures('Article', 'Tag');
		$TestModel2 =& new Article();
		$this->assertEqual($TestModel2->ArticlesTag->primaryKey, 'article_id');

		//test conformant models with PK in join table
		$this->loadFixtures('Item', 'Portfolio', 'ItemsPortfolio');
		$TestModel3 =& new Portfolio();
		$this->assertEqual($TestModel3->ItemsPortfolio->primaryKey, 'id');

		//test conformant models with PK in join table - join table contains extra field
		$this->loadFixtures('JoinA', 'JoinB', 'JoinAB');
		$TestModel4 =& new JoinA();
		$this->assertEqual($TestModel4->JoinAsJoinB->primaryKey, 'id');

	}
/**
 * testInsertAnotherHabtmRecordWithSameForeignKey method
 *
 * @access public
 * @return void
 */
	function testInsertAnotherHabtmRecordWithSameForeignKey() {
		$this->loadFixtures('JoinA', 'JoinB', 'JoinAB');
		$TestModel = new JoinA();

		$result = $TestModel->JoinAsJoinB->findById(1);
		$expected = array('JoinAsJoinB' => array('id' => 1, 'join_a_id' => 1, 'join_b_id' => 2, 'other' => 'Data for Join A 1 Join B 2', 'created' => '2008-01-03 10:56:33', 'updated' => '2008-01-03 10:56:33'));
		$this->assertEqual($result, $expected);

		$TestModel->JoinAsJoinB->create();
		$result = $TestModel->JoinAsJoinB->save(array('join_a_id' => 1, 'join_b_id' => 1, 'other' => 'Data for Join A 1 Join B 1', 'created' => '2008-01-03 10:56:44', 'updated' => '2008-01-03 10:56:44'));
		$this->assertTrue($result);
		$lastInsertId = $TestModel->JoinAsJoinB->getLastInsertID();
		$this->assertTrue($lastInsertId != null);

		$result = $TestModel->JoinAsJoinB->findById(1);
		$expected = array('JoinAsJoinB' => array('id' => 1, 'join_a_id' => 1, 'join_b_id' => 2, 'other' => 'Data for Join A 1 Join B 2', 'created' => '2008-01-03 10:56:33', 'updated' => '2008-01-03 10:56:33'));
		$this->assertEqual($result, $expected);

		$updatedValue = 'UPDATED Data for Join A 1 Join B 2';
		$TestModel->JoinAsJoinB->id = 1;
		$result = $TestModel->JoinAsJoinB->saveField('other', $updatedValue, false);
		$this->assertTrue($result);

		$result = $TestModel->JoinAsJoinB->findById(1);
		$this->assertEqual($result['JoinAsJoinB']['other'], $updatedValue);
	}
/**
 * Tests that $cacheSources can only be disabled in the db using model settings, not enabled
 *
 * @access public
 * @return void
 */
	function testCacheSourcesDisabling() {
		$this->db->cacheSources = true;
		$TestModel = new JoinA();
		$TestModel->cacheSources = false;
		$TestModel->setSource('join_as');
		$this->assertFalse($this->db->cacheSources);

		$this->db->cacheSources = false;
		$TestModel = new JoinA();
		$TestModel->cacheSources = true;
		$TestModel->setSource('join_as');
		$this->assertFalse($this->db->cacheSources);
	}
/**
 * endTest method
 *
 * @access public
 * @return void
 */
	function endTest() {
		ClassRegistry::flush();
	}
}

?>