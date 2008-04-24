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
	var $autoFixtures = false;

	var $fixtures = array(
		'core.category', 'core.category_thread', 'core.user', 'core.article', 'core.featured', 'core.article_featureds_tags',
		'core.article_featured', 'core.articles', 'core.tag', 'core.articles_tag', 'core.comment', 'core.attachment',
		'core.apple', 'core.sample', 'core.another_article', 'core.advertisement', 'core.home', 'core.post', 'core.author',
		'core.project', 'core.thread', 'core.message', 'core.bid', 'core.portfolio', 'core.item', 'core.items_portfolio',
		'core.syfile', 'core.image', 'core.device_type', 'core.device_type_category', 'core.feature_set', 'core.exterior_type_category',
		'core.document', 'core.device', 'core.document_directory', 'core.primary_model', 'core.secondary_model', 'core.something',
		'core.something_else', 'core.join_thing', 'core.join_a', 'core.join_b', 'core.join_c', 'core.join_a_b', 'core.join_a_c',
		'core.uuid', 'core.data_test', 'core.posts_tag', 'core.the_paper_monkies', 'core.person'
	);

	function start() {
		parent::start();
		$this->debug = Configure::read('debug');
		Configure::write('debug', 2);
	}

	function end() {
		parent::end();
		Configure::write('debug', $this->debug);
	}

	function testAutoConstructAssociations() {
		$this->loadFixtures('User');
		$this->model =& new AssociationTest1();

		$result = $this->model->hasAndBelongsToMany;
		$expected = array('AssociationTest2' => array(
			'unique' => false, 'joinTable' => 'join_as_join_bs', 'foreignKey' => false,
			'className' => 'AssociationTest2', 'with' => 'JoinAsJoinB',
			'associationForeignKey' => 'join_b_id', 'conditions' => '', 'fields' => '',
			'order' => '', 'limit' => '', 'offset' => '', 'finderQuery' => '',
			'deleteQuery' => '', 'insertQuery' => ''
		));
		$this->assertEqual($result, $expected);
	}

	function testMultipleBelongsToWithSameClass() {
		$this->loadFixtures('DeviceType', 'DeviceTypeCategory', 'FeatureSet', 'ExteriorTypeCategory', 'Document', 'Device', 'DocumentDirectory');
		$this->DeviceType =& new DeviceType();

		$this->DeviceType->recursive = 2;
		$result = $this->DeviceType->read(null, 1);

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
		unset($this->DeviceType);
	}

	function testHabtmRecursiveBelongsTo() {
		$this->loadFixtures('Portfolio', 'Item', 'ItemsPortfolio', 'Syfile', 'Image');
		$this->Portfolio =& new Portfolio();

		$result = $this->Portfolio->find(array('id' => 2), null, null, 3);
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
		unset($this->Portfolio);
	}

	function testHabtmFinderQuery() {
		$this->loadFixtures('Article', 'Tag', 'ArticlesTag');
		$this->Article =& new Article();

		$db =& ConnectionManager::getDataSource('test_suite');
		$sql = $db->buildStatement(
			array(
				'fields' => $db->fields($this->Article->Tag, null, array('Tag.id', 'Tag.tag', 'ArticlesTag.article_id', 'ArticlesTag.tag_id')),
				'table' => $db->fullTableName('tags'),
				'alias' => 'Tag',
				'limit' => null,
				'offset' => null,
				'joins' => array(array(
					'alias' => 'ArticlesTag',
					'table' => $db->fullTableName('articles_tags'),
					'conditions' => array(
						array("ArticlesTag.article_id" => '{$__cakeID__$}'),
						array("ArticlesTag.tag_id" => '{$__cakeIdentifier[Tag.id]__$}')
					)
				)),
				'conditions' => array(),
				'order' => null
			),
			$this->Article
		);

		$this->Article->hasAndBelongsToMany['Tag']['finderQuery'] = $sql;
		$result = $this->Article->find('first');
		$expected = array(array('id' => '1', 'tag' => 'tag1'), array('id' => '2', 'tag' => 'tag2'));
		$this->assertEqual($result['Tag'], $expected);
	}

	function testHabtmLimitOptimization() {
		$this->loadFixtures('Article', 'User', 'Comment', 'Tag', 'ArticlesTag');
		$this->model =& new Article();

		$this->model->hasAndBelongsToMany['Tag']['limit'] = 2;
		$result = $this->model->read(null, 2);
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

		$this->model->hasAndBelongsToMany['Tag']['limit'] = 1;
		$result = $this->model->read(null, 2);
		unset($expected['Tag'][1]);
		$this->assertEqual($result, $expected);
	}

	function testHasManyLimitOptimization() {
		$this->loadFixtures('Project', 'Thread', 'Message', 'Bid');
		$this->Project =& new Project();
		$this->Project->recursive = 3;

		$result = $this->Project->find('all');
		$expected = array(
			array('Project' => array('id' => 1, 'name' => 'Project 1'),
				'Thread' => array(array('id' => 1, 'project_id' => 1, 'name' => 'Project 1, Thread 1',
					'Message' => array(array('id' => 1, 'thread_id' => 1, 'name' => 'Thread 1, Message 1',
						'Bid' => array('id' => 1, 'message_id' => 1, 'name' => 'Bid 1.1')))),
				array('id' => 2, 'project_id' => 1, 'name' => 'Project 1, Thread 2',
					'Message' => array(array('id' => 2, 'thread_id' => 2, 'name' => 'Thread 2, Message 1',
						'Bid' => array('id' => 4, 'message_id' => 2, 'name' => 'Bid 2.1')))))),
			array('Project' => array('id' => 2, 'name' => 'Project 2'),
				'Thread' => array(array('id' => 3, 'project_id' => 2, 'name' => 'Project 2, Thread 1',
					'Message' => array(array('id' => 3, 'thread_id' => 3, 'name' => 'Thread 3, Message 1',
						'Bid' => array('id' => 3, 'message_id' => 3, 'name' => 'Bid 3.1')))))),
			array('Project' => array('id' => 3, 'name' => 'Project 3'),
					'Thread' => array()));
		$this->assertEqual($result, $expected);
		unset($this->Project);
	}

	function testWithAssociation() {
		$this->loadFixtures('Something', 'SomethingElse', 'JoinThing');
		$this->model =& new Something();
		$result = $this->model->SomethingElse->find('all');

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

		$result = $this->model->find('all');
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

		$result = $this->model->findById(1);
		$expected = array(
			'Something' => array('id' => '1', 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				'SomethingElse' => array(array('id' => '2', 'title' => 'Second Post', 'body' => 'Second Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
					'JoinThing' => array('doomed' => '1', 'something_id' => '1', 'something_else_id' => '2'))));
		$this->assertEqual($result, $expected);

		$this->model->hasAndBelongsToMany['SomethingElse']['unique'] = false;
		$this->model->create(array(
			'Something' => array('id' => 1),
			'SomethingElse' => array(3, array('something_else_id' => 1, 'doomed' => '1'))
		));
		$ts = date('Y-m-d H:i:s');
		$this->model->save();

		$this->model->hasAndBelongsToMany['SomethingElse']['order'] = 'SomethingElse.id ASC';
		$result = $this->model->findById(1);
		$expected = array(
			'Something' => array('id' => '1', 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => $ts),
				'SomethingElse' => array(
					array('id' => '1', 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
						'JoinThing' => array('doomed' => '1', 'something_id' => '1', 'something_else_id' => '1')),
					array('id' => '2', 'title' => 'Second Post', 'body' => 'Second Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
						'JoinThing' => array('doomed' => '1', 'something_id' => '1', 'something_else_id' => '2')),
					array('id' => '3', 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
						'JoinThing' => array('doomed' => null, 'something_id' => '1', 'something_else_id' => '3'))));
		$this->assertEqual($result, $expected);
	}

	function testDynamicAssociations() {
		$this->loadFixtures('Article', 'Comment');
		$this->model =& new Article();

		$this->model->belongsTo = $this->model->hasAndBelongsToMany = $this->model->hasOne = array();
		$this->model->hasMany['Comment'] = array_merge($this->model->hasMany['Comment'], array(
			'foreignKey' => false,
			'conditions' => array('Comment.user_id' => '= 2')
		));
		$result = $this->model->find('all');
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

	function testSaveMultipleHabtm() {
		$this->loadFixtures('JoinA', 'JoinB', 'JoinC', 'JoinAB', 'JoinAC');
		$this->model = new JoinA();
		$result = $this->model->findById(1);

		$expected = array(
			'JoinA' => array('id' => 1, 'name' => 'Join A 1', 'body' => 'Join A 1 Body', 'created' => '2008-01-03 10:54:23', 'updated' => '2008-01-03 10:54:23'),
				'JoinB' => array(
					0 => array('id' => 2, 'name' => 'Join B 2', 'created' => '2008-01-03 10:55:02', 'updated' => '2008-01-03 10:55:02',
						'JoinAsJoinB' => array('id' => 1, 'join_a_id' => 1, 'join_b_id' => 2, 'other' => 'Data for Join A 1 Join B 2', 'created' => '2008-01-03 10:56:33', 'updated' => '2008-01-03 10:56:33'))),
				'JoinC' => array(
					0 => array('id' => 2, 'name' => 'Join C 2', 'created' => '2008-01-03 10:56:12', 'updated' => '2008-01-03 10:56:12',
						'JoinAsJoinC' => array('id' => 1, 'join_a_id' => 1, 'join_c_id' => 2, 'other' => 'Data for Join A 1 Join C 2', 'created' => '2008-01-03 10:57:22', 'updated' => '2008-01-03 10:57:22'))));

		$this->assertEqual($result, $expected);

		$this->model->id = 1;
		$data = array(
			'JoinA' => array('id' => '1', 'name' => 'New name for Join A 1'),
			'JoinB' => array(array('id' => 1, 'join_b_id' => 2, 'other' => 'New data for Join A 1 Join B 2')),
			'JoinC' => array(array('id' => 1, 'join_c_id' => 2, 'other' => 'New data for Join A 1 Join C 2')));
		$this->model->set($data);
		$this->model->save();
		$ts = date('Y-m-d H:i:s');

		$result = $this->model->findById(1);
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

	function testFindAllRecursiveSelfJoin() {
		$this->loadFixtures('Home', 'AnotherArticle', 'Advertisement');
		$this->model =& new Home();
		$this->model->recursive = 2;

		$result = $this->model->findAll();
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

	function testFindSelfAssociations() {
		$this->loadFixtures('Person');

		$this->model =& new Person();
		$this->model->recursive = 2;
		$result = $this->model->read(null, 1);
		$expected = array(
				'Person' => array('id' => 1, 'name' => 'person', 'mother_id' => 2, 'father_id' => 3),
				'Mother' => array('id' => 2, 'name' => 'mother', 'mother_id' => 4, 'father_id' => 5,
					'Mother' => array('id' => 4, 'name' => 'mother - grand mother', 'mother_id' => 0, 'father_id' => 0),
					'Father' => array('id' => 5, 'name' => 'mother - grand father', 'mother_id' => 0, 'father_id' => 0)),
				'Father' => array('id' => 3, 'name' => 'father', 'mother_id' => 6, 'father_id' => 7,
					'Father' => array('id' => 7, 'name' => 'father - grand father', 'mother_id' => 0, 'father_id' => 0),
					'Mother' => array('id' => 6, 'name' => 'father - grand mother', 'mother_id' => 0, 'father_id' => 0)));
		$this->assertEqual($result, $expected);

		$this->model->recursive = 3;
		$result = $this->model->read(null, 1);
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

	function testIdentity() {
		$this->model =& new Test();
		$result = $this->model->alias;
		$expected = 'Test';
		$this->assertEqual($result, $expected);
	}

	function testCreation() {
		$this->loadFixtures('Article');
		$this->model =& new Test();
		$result = $this->model->create();
		$expected = array('Test' => array('notes' => 'write some notes here'));
		$this->assertEqual($result, $expected);
		$this->model =& new User();
		$result = $this->model->schema();

		$db =& ConnectionManager::getDataSource('test_suite');
		if (isset($db->columns['primary_key']['length'])) {
			$intLength = $db->columns['primary_key']['length'];
		} elseif (isset($db->columns['integer']['length'])) {
			$intLength = $db->columns['integer']['length'];
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

		$this->model =& new Article();
		$result = $this->model->create();
		$expected = array('Article' => array('published' => 'N'));
		$this->assertEqual($result, $expected);
	}

	function testCreationOfEmptyRecord() {
		$this->loadFixtures('Author');
		$this->model =& new Author();
		$this->assertEqual($this->model->find('count'), 4);

		$this->model->deleteAll(true, false, false);
		$this->assertEqual($this->model->find('count'), 0);

		$result = $this->model->save();
		$this->assertTrue(isset($result['Author']['created']));
		$this->assertTrue(isset($result['Author']['updated']));
		$this->assertEqual($this->model->find('count'), 1);
	}

	function testCreateWithPKFiltering() {
		$this->model =& new Article();
		$data = array('id' => 5, 'user_id' => 2, 'title' => 'My article', 'body' => 'Some text');

		$result = $this->model->create($data);
		$expected = array('Article' => array('published' => 'N', 'id' => 5, 'user_id' => 2, 'title' => 'My article', 'body' => 'Some text'));
		$this->assertEqual($result, $expected);
		$this->assertEqual($this->model->id, 5);

		$result = $this->model->create($data, true);
		$expected = array('Article' => array('published' => 'N', 'id' => false, 'user_id' => 2, 'title' => 'My article', 'body' => 'Some text'));
		$this->assertEqual($result, $expected);
		$this->assertFalse($this->model->id);

		$result = $this->model->create(array('Article' => $data), true);
		$expected = array('Article' => array('published' => 'N', 'id' => false, 'user_id' => 2, 'title' => 'My article', 'body' => 'Some text'));
		$this->assertEqual($result, $expected);
		$this->assertFalse($this->model->id);
	}

	function testCreationWithMultipleData() {
		$this->loadFixtures('Article', 'Comment');
		$this->Article =& new Article();
		$this->Comment =& new Comment();

		$articles = $this->Article->find('all', array('fields' => array('id','title'), 'recursive' => -1));
		$comments = $this->Comment->find('all', array('fields' => array('id','article_id','user_id','comment','published'), 'recursive' => -1));
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
		$result = $this->Comment->create($data);
		$this->assertTrue($result);
		$result = $this->Comment->save();
		$this->assertTrue($result);

		$articles = $this->Article->find('all', array('fields' => array('id','title'), 'recursive' => -1));
		$comments = $this->Comment->find('all', array('fields' => array('id','article_id','user_id','comment','published'), 'recursive' => -1));
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

	function testCreationWithMultipleDataSameModel() {
		$this->loadFixtures('Article');
		$this->Article =& new Article();
		$this->SecondaryArticle =& new Article();

		$result = $this->Article->field('title', array('id' => 1));
		$this->assertEqual($result, 'First Article');

		$data = array('Article' => array('user_id' => 2, 'title' => 'Brand New Article', 'body' => 'Brand New Article Body', 'published' => 'Y'),
			'SecondaryArticle' => array('id' => 1));
		$this->Article->create();
		$result = $this->Article->save($data);
		$this->assertTrue($result);

		$result = $this->Article->getInsertID();
		$this->assertTrue(!empty($result));

		$result = $this->Article->field('title', array('id' => 1));
		$this->assertEqual($result, 'First Article');

		$articles = $this->Article->find('all', array('fields' => array('id','title'), 'recursive' => -1));
		$this->assertEqual($articles, array(
			array('Article' => array('id' => 1, 'title' => 'First Article')),
			array('Article' => array('id' => 2, 'title' => 'Second Article')),
			array('Article' => array('id' => 3, 'title' => 'Third Article')),
			array('Article' => array('id' => 4, 'title' => 'Brand New Article'))));
	}

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

		$result = $Primary->findCount();
		$this->assertEqual($result, 2);
	}

	function testReadFakeThread() {
		$this->loadFixtures('CategoryThread');
		$this->model =& new CategoryThread();

		$this->db->fullDebug = true;
		$this->model->recursive = 6;
		$this->model->id = 7;
		$result = $this->model->read();
		$expected = array(
				'CategoryThread' => array('id' => 7, 'parent_id' => 6, 'name' => 'Category 2.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
						'ParentCategory' => array('id' => 6, 'parent_id' => 5, 'name' => 'Category 2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 5, 'parent_id' => 4, 'name' => 'Category 1.1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 4, 'parent_id' => 3, 'name' => 'Category 1.1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 3, 'parent_id' => 2, 'name' => 'Category 1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31')))))));
		$this->assertEqual($result, $expected);
	}

	function testFindFakeThread() {
		$this->loadFixtures('CategoryThread');
		$this->model =& new CategoryThread();

		$this->db->fullDebug = true;
		$this->model->recursive = 6;
		$result = $this->model->find(array('CategoryThread.id' => 7));

		$expected = array(
				'CategoryThread' => array('id' => 7, 'parent_id' => 6, 'name' => 'Category 2.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
						'ParentCategory' => array('id' => 6, 'parent_id' => 5, 'name' => 'Category 2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 5, 'parent_id' => 4, 'name' => 'Category 1.1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 4, 'parent_id' => 3, 'name' => 'Category 1.1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 3, 'parent_id' => 2, 'name' => 'Category 1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
						'ParentCategory' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31')))))));
		$this->assertEqual($result, $expected);
	}

	function testFindAllFakeThread() {
		$this->loadFixtures('CategoryThread');
		$this->model =& new CategoryThread();

		$this->db->fullDebug = true;
		$this->model->recursive = 6;
		$result = $this->model->findAll(null, null, 'CategoryThread.id ASC');
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
		$this->assertEqual($result, $expected);
	}

	function testFindAll() {
		$this->loadFixtures('User');
		$this->model =& new User();

		$result = $this->model->findAll();
		$expected = array(
				array('User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31')),
				array('User' => array('id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31')),
				array('User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31')),
				array('User' => array('id' => '4', 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31')));
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll('User.id > 2');
		$expected = array(
				array('User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31')),
				array('User' => array('id' => '4', 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31')));
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(array('User.id' => '!= 0', 'User.user' => 'LIKE %arr%'));
		$expected = array(
				array('User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31')),
				array('User' => array('id' => '4', 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31')));
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(array('User.id' => '0'));
		$expected = array();
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(array('or' => array('User.id' => '0', 'User.user' => 'LIKE %a%')));
		$expected = array(
				array('User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31')),
				array('User' => array('id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31')),
				array('User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31')),
				array('User' => array('id' => '4', 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31')));
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, 'User.id, User.user');
		$expected = array(
				array('User' => array('id' => '1', 'user' => 'mariano')),
				array('User' => array('id' => '2', 'user' => 'nate')),
				array('User' => array('id' => '3', 'user' => 'larry')),
				array('User' => array('id' => '4', 'user' => 'garrett')));
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, 'User.user', 'User.user ASC');
		$expected = array(
				array('User' => array('user' => 'garrett')),
				array('User' => array('user' => 'larry')),
				array('User' => array('user' => 'mariano')),
				array('User' => array('user' => 'nate')));
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, 'User.user', 'User.user ASC');
		$expected = array(
				array('User' => array('user' => 'garrett')),
				array('User' => array('user' => 'larry')),
				array('User' => array('user' => 'mariano')),
				array('User' => array('user' => 'nate')));
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, 'User.user', 'User.user DESC');
		$expected = array(
				array('User' => array('user' => 'nate')),
				array('User' => array('user' => 'mariano')),
				array('User' => array('user' => 'larry')),
				array('User' => array('user' => 'garrett')));
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, null, null, 3, 1);
		$expected = array(
				array('User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31')),
				array('User' => array('id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31')),
				array('User' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31')));
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, null, null, 3, 2);
		$expected = array(
				array('User' => array('id' => '4', 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31')));
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, null, null, 3, 3);
		$expected = array();
		$this->assertEqual($result, $expected);
	}

	function testGenerateList() {
		$this->loadFixtures('Article', 'Apple', 'Post', 'Author', 'User');

		$this->model =& new Article();
		$this->model->displayField = 'title';

		$result = $this->model->find('list', array('order' => 'Article.title ASC'));
		$expected = array(1 => 'First Article', 2 => 'Second Article', 3 => 'Third Article');
		$this->assertEqual($result, $expected);

		$result = Set::combine($this->model->find('all', array('order' => 'Article.title ASC', 'fields' => array('id', 'title'))), '{n}.Article.id', '{n}.Article.title');
		$expected = array(1 => 'First Article', 2 => 'Second Article', 3 => 'Third Article');
		$this->assertEqual($result, $expected);

		$result = Set::combine($this->model->find('all', array('order' => 'Article.title ASC')), '{n}.Article.id', '{n}.Article');
		$expected = array(
				1 => array('id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				2 => array('id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
				3 => array('id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'));
		$this->assertEqual($result, $expected);

		$result = Set::combine($this->model->find('all', array('order' => 'Article.title ASC')), '{n}.Article.id', '{n}.Article', '{n}.Article.user_id');
		$expected = array(1 => array(
						1 => array('id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
						3 => array('id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31')),
				3 => array(2 => array('id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31')));
		$this->assertEqual($result, $expected);

		$result = Set::combine($this->model->find('all', array('order' => 'Article.title ASC', 'fields' => array('id', 'title', 'user_id'))), '{n}.Article.id', '{n}.Article.title', '{n}.Article.user_id');
		$expected = array(1 => array(1 => 'First Article', 3 => 'Third Article'), 3 => array(2 => 'Second Article'));
		$this->assertEqual($result, $expected);

		$this->model =& new Apple();
		$expected = array(1 => 'Red Apple 1', 2 => 'Bright Red Apple', 3 => 'green blue', 4 => 'Test Name', 5 => 'Blue Green', 6 => 'My new apple', 7 => 'Some odd color');

		$this->assertEqual($this->model->find('list'), $expected);
		$this->assertEqual($this->model->Parent->find('list'), $expected);

		$this->model =& new Post();
		$result = $this->model->find('list', array('fields' => 'Post.title'));
		$expected = array(1 => 'First Post', 2 => 'Second Post', 3 => 'Third Post');
		$this->assertEqual($result, $expected);

		$result = $this->model->find('list', array('fields' => array('Post.body')));
		$expected = array(1 => 'First Post Body', 2 => 'Second Post Body', 3 => 'Third Post Body');
		$this->assertEqual($result, $expected);

		$result = $this->model->find('list', array('fields' => array('Post.title', 'Post.body')));
		$expected = array('First Post' => 'First Post Body', 'Second Post' => 'Second Post Body', 'Third Post' => 'Third Post Body');
		$this->assertEqual($result, $expected);

		$result = $this->model->find('list', array('fields' => array('Post.id', 'Post.title', 'Author.user'), 'recursive' => 1));
		$expected = array('mariano' => array(1 => 'First Post', 3 => 'Third Post'), 'larry' => array(2 => 'Second Post'));
		$this->assertEqual($result, $expected);

		$this->model =& new User();
		$result = $this->model->find('list', array('fields' => array('User.user', 'User.password')));
		$expected = array('mariano' => '5f4dcc3b5aa765d61d8327deb882cf99', 'nate' => '5f4dcc3b5aa765d61d8327deb882cf99', 'larry' => '5f4dcc3b5aa765d61d8327deb882cf99', 'garrett' => '5f4dcc3b5aa765d61d8327deb882cf99');
		$this->assertEqual($result, $expected);

		$this->model =& new ModifiedAuthor();
		$result = $this->model->find('list', array('fields' => array('Author.id', 'Author.user')));
		$expected = array(1 => 'mariano (CakePHP)', 2 => 'nate (CakePHP)', 3 => 'larry (CakePHP)', 4 => 'garrett (CakePHP)');
		$this->assertEqual($result, $expected);
	}

	function testRecordExists() {
		$this->loadFixtures('User');
		$this->model =& new User();

		$this->assertFalse($this->model->exists());
		$this->model->read(null, 1);
		$this->assertTrue($this->model->exists());
		$this->model->create();
		$this->assertFalse($this->model->exists());
		$this->model->id = 4;
		$this->assertTrue($this->model->exists());

		$this->model =& new TheVoid();
		$this->assertFalse($this->model->exists());
		$this->model->id = 5;
		$this->assertFalse($this->model->exists());
	}

	function testFindField() {
		$this->loadFixtures('User');
		$this->model =& new User();

		$this->model->id = 1;
		$result = $this->model->field('user');
		$this->assertEqual($result, 'mariano');

		$result = $this->model->field('User.user');
		$this->assertEqual($result, 'mariano');

		$this->model->id = false;
		$result = $this->model->field('user', array('user' => 'mariano'));
		$this->assertEqual($result, 'mariano');

		$result = $this->model->field('COUNT(*) AS count', true);
		$this->assertEqual($result, 4);

		$result = $this->model->field('COUNT(*)', true);
		$this->assertEqual($result, 4);
	}

	function testFindUnique() {
		$this->loadFixtures('User');
		$this->model =& new User();

		$this->assertFalse($this->model->isUnique(array('user' => 'nate')));
		$this->model->id = 2;
		$this->assertTrue($this->model->isUnique(array('user' => 'nate')));
		$this->assertFalse($this->model->isUnique(array('user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99')));
	}

	function testUpdateExisting() {
		$this->loadFixtures('User', 'Article', 'Comment');
		$this->model =& new User();
		$this->model->id = $id = 1000;
		$this->model->delete();

		$this->model->save(array('User' => array('id' => $id, 'user' => 'some user', 'password' => 'some password')));
		$this->assertEqual($this->model->id, $id);

		$this->model->save(array('User' => array('user' => 'updated user')));
		$this->assertEqual($this->model->id, $id);

		$this->Article =& new Article();
		$this->Comment =& new Comment();
		$data = array('Comment' => array('id' => 1, 'comment' => 'First Comment for First Article'),
				'Article' => array('id' => 2, 'title' => 'Second Article'));

		$result = $this->Article->save($data);
		$this->assertTrue($result);

		$result = $this->Comment->save($data);
		$this->assertTrue($result);
	}

	function testUpdateMultiple() {
		$this->loadFixtures('Comment', 'Article', 'User', 'Attachment');
		$this->model =& new Comment();
		$result = Set::extract($this->model->find('all'), '{n}.Comment.user_id');
		$expected = array('2', '4', '1', '1', '1', '2');
		$this->assertEqual($result, $expected);

		$this->model->updateAll(array('Comment.user_id' => 5), array('Comment.user_id' => 2));
		$result = Set::extract($this->model->find('all'), '{n}.Comment.user_id');
		$expected = array('5', '4', '1', '1', '1', '5');
		$this->assertEqual($result, $expected);
	}

	function testBindUnbind() {
		$this->loadFixtures('User', 'Comment', 'FeatureSet');
		$this->model =& new User();

		$result = $this->model->hasMany;
		$expected = array();
		$this->assertEqual($result, $expected);

		$result = $this->model->bindModel(array('hasMany' => array('Comment')));
		$this->assertTrue($result);

		$result = $this->model->findAll(null, 'User.id, User.user');
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

		$this->model->__resetAssociations();
		$result = $this->model->hasMany;
		$this->assertEqual($result, array());

		$result = $this->model->bindModel(array('hasMany' => array('Comment')), false);
		$this->assertTrue($result);

		$result = $this->model->findAll(null, 'User.id, User.user');
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

		$result = $this->model->hasMany;
		$expected = array('Comment' => array('className' => 'Comment', 'foreignKey' => 'user_id', 'conditions' => null, 'fields' => null, 'order' => null, 'limit' => null, 'offset' => null, 'dependent' => null, 'exclusive' => null, 'finderQuery' => null, 'counterQuery' => null) );
		$this->assertEqual($result, $expected);

		$result = $this->model->unbindModel(array('hasMany' => array('Comment')));
		$this->assertTrue($result);

		$result = $this->model->hasMany;
		$expected = array();
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, 'User.id, User.user');
		$expected = array(
			array('User' => array('id' => '1', 'user' => 'mariano')),
			array('User' => array('id' => '2', 'user' => 'nate')),
			array('User' => array('id' => '3', 'user' => 'larry')),
			array('User' => array('id' => '4', 'user' => 'garrett')));
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, 'User.id, User.user');
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

		$result = $this->model->unbindModel(array('hasMany' => array('Comment')), false);
		$this->assertTrue($result);

		$result = $this->model->findAll(null, 'User.id, User.user');
		$expected = array(
			array('User' => array('id' => '1', 'user' => 'mariano')),
			array('User' => array('id' => '2', 'user' => 'nate')),
			array('User' => array('id' => '3', 'user' => 'larry')),
			array('User' => array('id' => '4', 'user' => 'garrett')));
		$this->assertEqual($result, $expected);

		$result = $this->model->hasMany;
		$expected = array();
		$this->assertEqual($result, $expected);

		$result = $this->model->bindModel(array('hasMany' => array('Comment' => array('className' => 'Comment', 'conditions' => 'Comment.published = \'Y\'') )));
		$this->assertTrue($result);

		$result = $this->model->findAll(null, 'User.id, User.user');
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

		$this->model2 =& new DeviceType();

		$expected = array('className' => 'FeatureSet', 'foreignKey' => 'feature_set_id', 'conditions' => '', 'fields' => '', 'order' => '', 'counterCache' => '');
		$this->assertEqual($this->model2->belongsTo['FeatureSet'], $expected);

		$this->model2->bind('FeatureSet', array('conditions' => array('active' => true)));
		$expected['conditions'] = array('active' => true);
		$this->assertEqual($this->model2->belongsTo['FeatureSet'], $expected);

		$this->model2->bind('FeatureSet', array('foreignKey' => false, 'conditions' => array('Feature.name' => 'DeviceType.name')));
		$expected['conditions'] = array('Feature.name' => 'DeviceType.name');
		$expected['foreignKey'] = false;
		$this->assertEqual($this->model2->belongsTo['FeatureSet'], $expected);

		$this->model2->bind('NewFeatureSet', array('type' => 'hasMany', 'className' => 'FeatureSet', 'conditions' => array('active' => true)));
		$expected = array('className' => 'FeatureSet', 'conditions' => array('active' => true), 'foreignKey' => 'device_type_id', 'fields' => '', 'order' => '', 'limit' => '', 'offset' => '', 'dependent' => '', 'exclusive' => '', 'finderQuery' => '', 'counterQuery' => '');
		$this->assertEqual($this->model2->hasMany['NewFeatureSet'], $expected);
		$this->assertTrue(is_object($this->model2->NewFeatureSet));
	}

	function testFindCount() {
		$this->loadFixtures('User');
		$this->model =& new User();
		$result = $this->model->findCount();
		$this->assertEqual($result, 4);

		$this->db->fullDebug = true;
		$this->model->order = 'User.id';
		$this->db->_queriesLog = array();
		$result = $this->model->findCount();
		$this->assertEqual($result, 4);

		$this->assertTrue(isset($this->db->_queriesLog[0]['query']));
		$this->assertNoPattern('/ORDER\s+BY/', $this->db->_queriesLog[0]['query']);

		$this->db->_queriesLog = array();
		$this->db->fullDebug = false;
	}

	function testFindMagic() {
		$this->loadFixtures('User');
		$this->model =& new User();

		$result = $this->model->findByUser('mariano');
		$expected = array('User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'));
		$this->assertEqual($result, $expected);

		$result = $this->model->findByPassword('5f4dcc3b5aa765d61d8327deb882cf99');
		$expected = array('User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'));
		$this->assertEqual($result, $expected);
	}

	function testRead() {
		$this->loadFixtures('User', 'Article');
		$this->model =& new User();

		$result = $this->model->read();
		$this->assertFalse($result);

		$this->model->id = 2;
		$result = $this->model->read();
		$expected = array('User' => array('id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'));
		$this->assertEqual($result, $expected);

		$result = $this->model->read(null, 2);
		$expected = array('User' => array('id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'));
		$this->assertEqual($result, $expected);

		$this->model->id = 2;
		$result = $this->model->read(array('id', 'user'));
		$expected = array('User' => array('id' => '2', 'user' => 'nate'));
		$this->assertEqual($result, $expected);

		$result = $this->model->read('id, user', 2);
		$expected = array('User' => array('id' => '2', 'user' => 'nate'));
		$this->assertEqual($result, $expected);

		$result = $this->model->bindModel(array('hasMany' => array('Article')));
		$this->assertTrue($result);

		$this->model->id = 1;
		$result = $this->model->read('id, user');
		$expected = array('User' => array('id' => '1', 'user' => 'mariano'),
			'Article' => array(
				array('id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31' ),
				array('id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31' )));
		$this->assertEqual($result, $expected);
	}

	function testRecursiveRead() {
		$this->loadFixtures('User', 'Article', 'Comment', 'Tag', 'ArticlesTag', 'Featured', 'ArticleFeatured');
		$this->model =& new User();

		$result = $this->model->bindModel(array('hasMany' => array('Article')), false);
		$this->assertTrue($result);

		$this->model->recursive = 0;
		$result = $this->model->read('id, user', 1);
		$expected = array(
			'User' => array('id' => '1', 'user' => 'mariano'),
		);
		$this->assertEqual($result, $expected);

		$this->model->recursive = 1;
		$result = $this->model->read('id, user', 1);
		$expected = array('User' => array('id' => '1', 'user' => 'mariano'),
			'Article' => array(
				array('id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31' ),
				array('id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31' )));
		$this->assertEqual($result, $expected);

		$this->model->recursive = 2;
		$result = $this->model->read('id, user', 3);
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
		$db =& ConnectionManager::getDataSource('test_suite');
		$db->truncate(new Featured());

		$this->loadFixtures('User', 'Article', 'Comment', 'Tag', 'ArticlesTag', 'Attachment', 'ArticleFeatured', 'Featured', 'Category');
		$this->model =& new Article();

		$result = $this->model->findAll(array('Article.user_id' => 1));
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

		$result = $this->model->findAll(array('Article.user_id' => 3), null, null, null, 1, 2);
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

		$this->Featured = new Featured();

		$this->Featured->recursive = 2;
		$this->Featured->bindModel(array(
			'belongsTo' => array(
				'ArticleFeatured' => array(
					'conditions' => "ArticleFeatured.published = 'Y'",
					'fields' => 'id, title, user_id, published'
				)
			)
		));

		$this->Featured->ArticleFeatured->unbindModel(array(
			'hasMany' => array('Attachment', 'Comment'),
			'hasAndBelongsToMany' => array('Tag'))
		);

		$orderBy = 'ArticleFeatured.id ASC';
		$result = $this->Featured->findAll(null, null, $orderBy, 3);

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

	function testRecursiveFindAllWithLimit() {
		$this->loadFixtures('Article', 'User', 'Tag', 'ArticlesTag', 'Comment', 'Attachment');
		$this->model =& new Article();

		$this->model->hasMany['Comment']['limit'] = 2;

		$result = $this->model->findAll(array('Article.user_id' => 1));
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

		$this->model->hasMany['Comment']['limit'] = 1;

		$result = $this->model->findAll(array('Article.user_id' => 3), null, null, null, 1, 2);
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

	function testAssociationAfterFind() {
		$this->loadFixtures('Post', 'Author');
		$this->model =& new Post();
		$result = $this->model->findAll();
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
	}

	function testValidatesBackwards() {
		$this->model =& new TestValidate();

		$this->model->validate = array(
			'user_id' => VALID_NUMBER,
			'title' => VALID_NOT_EMPTY,
			'body' => VALID_NOT_EMPTY
		);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => '', 'body' => ''));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 'title', 'body' => ''));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('user_id' => '', 'title' => 'title', 'body' => 'body'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('user_id' => 'not a number', 'title' => 'title', 'body' => 'body'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 'title', 'body' => 'body'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertTrue($result);
	}

	function testValidates() {
		$this->model =& new TestValidate();

		$this->model->validate = array(
			'user_id' => VALID_NUMBER,
			'title' => array('allowEmpty' => false, 'rule' => VALID_NOT_EMPTY),
			'body' => VALID_NOT_EMPTY
		);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => '', 'body' => 'body'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 'title', 'body' => 'body'));
		$result = $this->model->create($data) && $this->model->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => '0', 'body' => 'body'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertTrue($result);

		$this->model->validate['modified'] = array('allowEmpty' => true, 'rule' => 'date');

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body', 'modified' => ''));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body', 'modified' => '2007-05-01'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body', 'modified' => 'invalid-date-here'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body', 'modified' => 0));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body', 'modified' => '0'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$this->model->validate['modified'] = array('allowEmpty' => false, 'rule' => 'date');

		$data = array('TestValidate' => array('modified' => null));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('modified' => false));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('modified' => ''));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('modified' => '2007-05-01'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertTrue($result);

		$this->model->validate['slug'] = array('allowEmpty' => false, 'rule' => array('maxLength', 45));

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body', 'slug' => ''));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body', 'slug' => 'slug-right-here'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array('user_id' => '1', 'title' => 0, 'body' => 'body', 'slug' => 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$this->model->validate = array(
			'number' => array('rule' => 'validateNumber', 'min' => 3, 'max' => 5),
			'title' => array('allowEmpty' => false, 'rule' => VALID_NOT_EMPTY)
		);

		$data = array('TestValidate' => array('title' => 'title', 'number' => '0'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'title', 'number' => 0));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'title', 'number' => '3'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertTrue($result);

		$data = array('TestValidate' => array('title' => 'title', 'number' => 3));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertTrue($result);

		$this->model->validate = array(
			'number' => array('rule' => 'validateNumber', 'min' => 5, 'max' => 10),
			'title' => array('allowEmpty' => false, 'rule' => VALID_NOT_EMPTY)
		);

		$data = array('TestValidate' => array('title' => 'title', 'number' => '3'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'title', 'number' => 3));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$this->model->validate = array(
			'title' => array('allowEmpty' => false, 'rule' => 'validateTitle')
		);

		$data = array('TestValidate' => array('title' => ''));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'new title'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'title-new'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertTrue($result);

		$this->model->validate = array('title' => array('allowEmpty' => true, 'rule' => 'validateTitle'));

		$data = array('TestValidate' => array('title' => ''));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertTrue($result);

		$this->model->validate = array('title' => array('rule' => array('userDefined', 'Article', 'titleDuplicate')));

		$data = array('TestValidate' => array('title' => 'My Article Title'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertFalse($result);

		$data = array('TestValidate' => array('title' => 'My Article With a Different Title'));
		$result = $this->model->create($data);
		$this->assertTrue($result);
		$result = $this->model->validates();
		$this->assertTrue($result);
	}

	function testSaveField() {
		$this->loadFixtures('Article');
		$this->model =& new Article();

		$this->model->id = 1;
		$result = $this->model->saveField('title', 'New First Article');
		$this->assertTrue($result);

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body'), 1);
		$expected = array('Article' => array(
			'id' => '1', 'user_id' => '1', 'title' => 'New First Article', 'body' => 'First Article Body'
		));
		$this->assertEqual($result, $expected);

		$this->model->id = 1;
		$result = $this->model->saveField('title', '');
		$this->assertTrue($result);

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body'), 1);
		$expected = array('Article' => array(
			'id' => '1', 'user_id' => '1', 'title' => '', 'body' => 'First Article Body'
		));
		$this->assertEqual($result, $expected);

		$this->model->id = 1;
		$this->model->set('body', 'Messed up data');
		$this->assertTrue($this->model->saveField('title', 'First Article'));
		$result = $this->model->read(array('id', 'user_id', 'title', 'body'), 1);
		$expected = array('Article' => array(
			'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body'
		));
		$this->assertEqual($result, $expected);

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body'), 1);

		$this->model->id = 1;
		$result = $this->model->saveField('title', '', true);
		$this->assertFalse($result);
	}

	function testSaveWithCreate() {
		$this->loadFixtures('User', 'Article', 'User', 'Comment', 'Tag', 'ArticlesTag', 'Attachment');
		$this->model =& new User();

		$data = array('User' => array('user' => 'user', 'password' => ''));
		$result = $this->model->save($data);
		$this->assertFalse($result);
		$this->assertTrue(!empty($this->model->validationErrors));

		$this->model =& new Article();

		$data = array('Article' => array('user_id' => '', 'title' => '', 'body' => ''));
		$result = $this->model->create($data) && $this->model->save();
		$this->assertFalse($result);
		$this->assertTrue(!empty($this->model->validationErrors));

		$data = array('Article' => array('id' => 1, 'user_id' => '1', 'title' => 'New First Article', 'body' => ''));
		$result = $this->model->create($data) && $this->model->save();
		$this->assertFalse($result);

		$data = array('Article' => array('id' => 1, 'title' => 'New First Article'));
		$result = $this->model->create() && $this->model->save($data, false);
		$this->assertTrue($result);

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body', 'published'), 1);
		$expected = array('Article' => array(
			'id' => '1', 'user_id' => '1', 'title' => 'New First Article', 'body' => 'First Article Body', 'published' => 'N'
		));
		$this->assertEqual($result, $expected);

		$data = array('Article' => array('id' => 1, 'user_id' => '2', 'title' => 'First Article', 'body' => 'New First Article Body', 'published' => 'Y'));
		$result = $this->model->create() && $this->model->save($data, true, array('id', 'title', 'published'));
		$this->assertTrue($result);

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body', 'published'), 1);
		$expected = array('Article' => array(
			'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y'
		));
		$this->assertEqual($result, $expected);

		$data = array(
			'Article' => array('user_id' => '2', 'title' => 'New Article', 'body' => 'New Article Body', 'created' => '2007-03-18 14:55:23', 'updated' => '2007-03-18 14:57:31'),
			'Tag' => array('Tag' => array(1, 3))
		);
		$this->model->create();
		$result = $this->model->create() && $this->model->save($data);
		$this->assertTrue($result);

		$this->model->recursive = 2;
		$result = $this->model->read(null, 4);
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
		$result = $this->model->Comment->create() && $this->model->Comment->save($data);
		$this->assertTrue($result);

		$data = array('Attachment' => array('comment_id' => '7', 'attachment' => 'newattachment.zip', 'created' => '2007-03-18 15:02:23', 'updated' => '2007-03-18 15:04:31'));
		$result = $this->model->Comment->Attachment->save($data);
		$this->assertTrue($result);

		$this->model->recursive = 2;
		$result = $this->model->read(null, 4);
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

	function testSaveWithSet() {
		$this->loadFixtures('Article');
		$this->model =& new Article();

		// Create record we will be updating later

		$data = array('Article' => array('user_id' => '1', 'title' => 'Fourth Article', 'body' => 'Fourth Article Body', 'published' => 'Y'));
		$result = $this->model->create() && $this->model->save($data);
		$this->assertTrue($result);

		// Check record we created

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body', 'published'), 4);
		$expected = array('Article' => array('id' => '4', 'user_id' => '1', 'title' => 'Fourth Article', 'body' => 'Fourth Article Body', 'published' => 'Y'));
		$this->assertEqual($result, $expected);

		// Create new record just to overlap Model->id on previously created record

		$data = array('Article' => array('user_id' => '4', 'title' => 'Fifth Article', 'body' => 'Fifth Article Body', 'published' => 'Y'));
		$result = $this->model->create() && $this->model->save($data);
		$this->assertTrue($result);

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body', 'published'), 5);
		$expected = array('Article' => array(
			'id' => '5', 'user_id' => '4', 'title' => 'Fifth Article', 'body' => 'Fifth Article Body', 'published' => 'Y'
		));
		$this->assertEqual($result, $expected);

		// Go back and edit the first article we created, starting by checking it's still there

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body', 'published'), 4);
		$expected = array('Article' => array('id' => '4', 'user_id' => '1', 'title' => 'Fourth Article', 'body' => 'Fourth Article Body', 'published' => 'Y'));
		$this->assertEqual($result, $expected);

		// And now do the update with set()

		$data = array('Article' => array('id' => '4', 'title' => 'Fourth Article - New Title', 'published' => 'N'));
		$result = $this->model->set($data) && $this->model->save();
		$this->assertTrue($result);

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body', 'published'), 4);
		$expected = array('Article' => array('id' => '4', 'user_id' => '1', 'title' => 'Fourth Article - New Title', 'body' => 'Fourth Article Body', 'published' => 'N'));
		$this->assertEqual($result, $expected);

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body', 'published'), 5);
		$expected = array('Article' => array(
			'id' => '5', 'user_id' => '4', 'title' => 'Fifth Article', 'body' => 'Fifth Article Body', 'published' => 'Y'
		));
		$this->assertEqual($result, $expected);

		$data = array('Article' => array('id' => '5', 'title' => 'Fifth Article - New Title 5'));
		$result = ($this->model->set($data) && $this->model->save());
		$this->assertTrue($result);

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body', 'published'), 5);
		$expected = array('Article' => array('id' => '5', 'user_id' => '4', 'title' => 'Fifth Article - New Title 5', 'body' => 'Fifth Article Body', 'published' => 'Y'));
		$this->assertEqual($result, $expected);

		$this->model->recursive = -1;
		$result = $this->model->findAll(null, array('id', 'title'));
		$expected = array(
			array('Article' => array('id' => 1, 'title' => 'First Article' )),
			array('Article' => array('id' => 2, 'title' => 'Second Article' )),
			array('Article' => array('id' => 3, 'title' => 'Third Article' )),
			array('Article' => array('id' => 4, 'title' => 'Fourth Article - New Title' )),
			array('Article' => array('id' => 5, 'title' => 'Fifth Article - New Title 5' ))
		);
		$this->assertEqual($result, $expected);
	}

	function testSaveFromXml() {
		$this->loadFixtures('Article');
		App::import('Core', 'Xml');

		$Article = new Article();
		$Article->save(new Xml('<article title="test xml" user_id="5" />'));
		$this->assertTrue($Article->save(new Xml('<article title="test xml" user_id="5" />')));

		$results = $Article->find(array('Article.title' => 'test xml'));
		$this->assertTrue($results);
	}

	function testSaveHabtm() {
		$this->loadFixtures('Article', 'User', 'Comment', 'Tag', 'ArticlesTag');
		$this->model =& new Article();

		$result = $this->model->findById(2);
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

		// Save with parent model data

		$data = array(
			'Article' => array('id' => '2', 'title' => 'New Second Article'),
			'Tag' => array('Tag' => array(1, 2))
		);

		$this->assertTrue($this->model->set($data));
		$this->assertTrue($this->model->save());

		$this->model->unbindModel(array('belongsTo' => array('User'), 'hasMany' => array('Comment')));
		$result = $this->model->find(array('Article.id' => 2), array('id', 'user_id', 'title', 'body'));
		$expected = array(
			'Article' => array('id' => '2', 'user_id' => '3', 'title' => 'New Second Article', 'body' => 'Second Article Body'),
			'Tag' => array(
				array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31')
			)
		);
		$this->assertEqual($result, $expected);

		$data = array('Article' => array('id' => '2'), 'Tag' => array('Tag' => array(2, 3)));
		$result = $this->model->set($data);
		$this->assertTrue($result);

		$result = $this->model->save();
		$this->assertTrue($result);

		$this->model->unbindModel(array('belongsTo' => array('User'), 'hasMany' => array('Comment')));
		$result = $this->model->find(array('Article.id'=>2), array('id', 'user_id', 'title', 'body'));
		$expected = array(
			'Article' => array('id' => '2', 'user_id' => '3', 'title' => 'New Second Article', 'body' => 'Second Article Body'),
			'Tag' => array(
				array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31'),
				array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
			)
		);
		$this->assertEqual($result, $expected);

		$data = array('Tag' => array('Tag' => array(1, 2, 3)));

		$result = $this->model->set($data);
		$this->assertTrue($result);

		$result = $this->model->save();
		$this->assertTrue($result);

		$this->model->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $this->model->find(array('Article.id' => 2), array('id', 'user_id', 'title', 'body'));
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
		$result = $this->model->set($data);
		$this->assertTrue($result);

		$result = $this->model->save();
		$this->assertTrue($result);

		$this->model->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $this->model->find(array('Article.id'=>2), array('id', 'user_id', 'title', 'body'));
		$expected = array(
			'Article' => array('id' => '2', 'user_id' => '3', 'title' => 'New Second Article', 'body' => 'Second Article Body'),
			'Tag' => array()
		);
		$this->assertEqual($result, $expected);

		$data = array('Tag' => array('Tag' => array(2, 3)));
		$result = $this->model->set($data);
		$this->assertTrue($result);

		$result = $this->model->save();
		$this->assertTrue($result);

		$this->model->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $this->model->find(array('Article.id'=>2), array('id', 'user_id', 'title', 'body'));
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

		// Parent data after HABTM data

		$data = array('Tag' => array('Tag' => array(1, 2)), 'Article' => array('id' => '2', 'title' => 'New Second Article'));
		$this->assertTrue($this->model->set($data));
		$this->assertTrue($this->model->save());

		$this->model->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $this->model->find(array('Article.id'=>2), array('id', 'user_id', 'title', 'body'));
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
		$result = $this->model->set($data);
		$this->assertTrue($result);
		$this->assertTrue($this->model->save());

		$this->model->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $this->model->find(array('Article.id'=>2), array('id', 'user_id', 'title', 'body'));
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
		$this->assertTrue($this->model->set($data));
		$this->assertTrue($this->model->save());

		$this->model->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $this->model->find(array('Article.id'=>2), array('id', 'user_id', 'title', 'body'));
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

		$result = $this->model->set($data);
		$this->assertTrue($result);

		$result = $this->model->save();
		$this->assertTrue($result);

		$this->model->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $this->model->find(array('Article.id'=>2), array('id', 'user_id', 'title', 'body'));
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
		$result = $this->model->create() && $this->model->save($data, true, array('user_id', 'title', 'published'));
		$this->assertTrue($result);

		$this->model->unbindModel(array('belongsTo' => array('User'), 'hasMany' => array('Comment')));
		$result = $this->model->read();
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
 * @todo This is technically incorrect (ThePaperMonkies.apple_id should be ThePaperMonkies.the_paper_id),
 * the foreign key name should come from the association name, not the table name... but that's the existing
 * functionality at this point.
 */
	function testHabtmSaveKeyResolution() {
		$this->loadFixtures('Apple', 'Device', 'ThePaperMonkies');
		$this->ThePaper =& new ThePaper();
		$this->ThePaper->id = 1;

		$this->ThePaper->save(array('Monkey' => array(2, 3)));
		$result = $this->ThePaper->findById(1);
		$expected = array(
			array('id' => '2', 'device_type_id' => '1', 'name' => 'Device 2', 'typ' => '1'),
			array('id' => '3', 'device_type_id' => '1', 'name' => 'Device 3', 'typ' => '2')
		);
		$this->assertEqual($result['Monkey'], $expected);
	}

	function testSaveAll() {
		$this->loadFixtures('Post', 'Author', 'Comment', 'Attachment');
		$this->model =& new Post();

		$result = $this->model->find('all');
		$this->assertEqual(count($result), 3);
		$this->assertFalse(isset($result[3]));
		$ts = date('Y-m-d H:i:s');

		$this->model->saveAll(array(
			'Post' => array('title' => 'Post with Author', 'body' => 'This post will be saved with an author'),
			'Author' => array('user' => 'bob', 'password' => '5f4dcc3b5aa765d61d8327deb882cf90')
		));

		$result = $this->model->find('all');
		$expected = array(
			'Post' => array('id' => '4', 'author_id' => '5', 'title' => 'Post with Author', 'body' => 'This post will be saved with an author', 'published' => 'N', 'created' => $ts, 'updated' => $ts),
			'Author' => array('id' => '5', 'user' => 'bob', 'password' => '5f4dcc3b5aa765d61d8327deb882cf90', 'created' => $ts, 'updated' => $ts, 'test' => 'working')
		);
		$this->assertEqual($result[3], $expected);
		$this->assertEqual(count($result), 4);

		$this->model->deleteAll(true);
		$this->assertEqual($this->model->find('all'), array());

		// SQLite seems to reset the PK counter when that happens, so we need this to make the tests pass
		$db =& ConnectionManager::getDataSource('test_suite');
		$db->truncate($this->model);

		$ts = date('Y-m-d H:i:s');
		$this->model->saveAll(array(
			array('title' => 'Multi-record post 1', 'body' => 'First multi-record post', 'author_id' => 2),
			array('title' => 'Multi-record post 2', 'body' => 'Second multi-record post', 'author_id' => 2)
		));

		$result = $this->model->find('all', array('recursive' => -1, 'order' => 'Post.id ASC'));
		$expected = array(
			array('Post' => array('id' => '1', 'author_id' => '2', 'title' => 'Multi-record post 1', 'body' => 'First multi-record post', 'published' => 'N', 'created' => $ts, 'updated' => $ts)),
			array('Post' => array('id' => '2', 'author_id' => '2', 'title' => 'Multi-record post 2', 'body' => 'Second multi-record post', 'published' => 'N', 'created' => $ts, 'updated' => $ts))
		);
		$this->assertEqual($result, $expected);

		$this->model =& new Comment();
		$ts = date('Y-m-d H:i:s');
		$result = $this->model->saveAll(array(
			'Comment' => array('article_id' => 2, 'user_id' => 2, 'comment' => 'New comment with attachment', 'published' => 'Y'),
			'Attachment' => array('attachment' => 'some_file.tgz')
		));
		$this->assertTrue($result);

		$result = $this->model->find('all');
	    $expected = array('id' => '7', 'article_id' => '2', 'user_id' => '2', 'comment' => 'New comment with attachment', 'published' => 'Y', 'created' => $ts, 'updated' => $ts);
		$this->assertEqual($result[6]['Comment'], $expected);

	    $expected = array('id' => '7', 'article_id' => '2', 'user_id' => '2', 'comment' => 'New comment with attachment', 'published' => 'Y', 'created' => $ts, 'updated' => $ts);
		$this->assertEqual($result[6]['Comment'], $expected);

		$expected = array('id' => '2', 'comment_id' => '7', 'attachment' => 'some_file.tgz', 'created' => $ts, 'updated' => $ts);
		$this->assertEqual($result[6]['Attachment'], $expected);
	}

	function testSaveAllAtomic()
	{
		$this->model =& new Article();

		$result = $this->model->saveAll(array(
			'Article' => array('title' => 'Post with Author', 'body' => 'This post will be saved with an author'),
			'Comment' => array('comment' => 'First new comment')
		), array('atomic' => false));
		$this->assertIdentical($result, array('Article' => array(true), 'Comment' => array(true)));

		$result = $this->model->saveAll(array(
			array('id' => '1', 'title' => 'Baleeted First Post', 'body' => 'Baleeted!', 'published' => 'N'),
			array('id' => '2', 'title' => 'Just update the title'),
			array('title' => 'Creating a fourth post', 'body' => 'Fourth post body', 'author_id' => 2)
		), array('atomic' => false));
		$this->assertIdentical($result, array(true, true, true));

		$this->model->validate = array('title' => VALID_NOT_EMPTY, 'author_id' => 'numeric');
		$result = $this->model->saveAll(array(
			array('id' => '1', 'title' => 'Un-Baleeted First Post', 'body' => 'Not Baleeted!', 'published' => 'Y'),
			array('id' => '2', 'title' => '', 'body' => 'Trying to get away with an empty title'),
		), array('atomic' => false));
		$this->assertIdentical($result, array(true, false));

		$result = $this->model->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'user_id' => 1),
				array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
			)
		), array('atomic' => false));
		$this->assertIdentical($result, array('Article' => array(true), 'Comment' => array(true, true)));
	}

	function testSaveAllHasMany() {
		$this->loadFixtures('Article', 'Comment');
		$this->model =& new Article();
		$this->model->belongsTo = $this->model->hasAndBelongsToMany = array();

		$result = $this->model->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'user_id' => 1),
				array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
			)
		));
		$this->assertTrue($result);

		$result = $this->model->findById(2);
		$expected = array('First Comment for Second Article', 'Second Comment for Second Article', 'First new comment', 'Second new comment');
		$this->assertEqual(Set::extract($result['Comment'], '{n}.comment'), $expected);
	}

	function testSaveAllTransaction() {
		$this->loadFixtures('Post', 'Author', 'Comment', 'Attachment');
		$this->model =& new Post();

		$this->model->validate = array('title' => VALID_NOT_EMPTY);
		$data = array(
			array('author_id' => 1, 'title' => 'New Fourth Post'),
			array('author_id' => 1, 'title' => 'New Fifth Post'),
			array('author_id' => 1, 'title' => '')
		);
		$ts = date('Y-m-d H:i:s');
		$this->assertFalse($this->model->saveAll($data));

		$result = $this->model->find('all', array('recursive' => -1));
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
		$this->assertFalse($this->model->saveAll($data));

		$result = $this->model->find('all', array('recursive' => -1));
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

		$this->model->validate = array('title' => VALID_NOT_EMPTY);
		$data = array(
			array('author_id' => 1, 'title' => 'New Fourth Post'),
			array('author_id' => 1, 'title' => 'New Fifth Post'),
			array('author_id' => 1, 'title' => 'New Sixth Post')
		);
		$this->assertTrue($this->model->saveAll($data));

		$result = $this->model->find('all', array('recursive' => -1, 'fields' => array('author_id', 'title','body','published')));
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

	function testSaveAllValidation() {
		$this->loadFixtures('Post', 'Author', 'Comment', 'Attachment');
		$this->model =& new Post();

		$data = array(
			array('id' => '1', 'title' => 'Baleeted First Post', 'body' => 'Baleeted!', 'published' => 'N'),
			array('id' => '2', 'title' => 'Just update the title'),
			array('title' => 'Creating a fourth post', 'body' => 'Fourth post body', 'author_id' => 2)
		);
		$ts = date('Y-m-d H:i:s');
		$this->assertTrue($this->model->saveAll($data));

		$result = $this->model->find('all', array('recursive' => -1));
		$expected = array(
			array('Post' => array('id' => '1', 'author_id' => '1', 'title' => 'Baleeted First Post', 'body' => 'Baleeted!', 'published' => 'N', 'created' => '2007-03-18 10:39:23', 'updated' => $ts)),
			array('Post' => array('id' => '2', 'author_id' => '3', 'title' => 'Just update the title', 'body' => 'Second Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => $ts)),
			array('Post' => array('id' => '3', 'author_id' => '1', 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31')),
			array('Post' => array('id' => '4', 'author_id' => '2', 'title' => 'Creating a fourth post', 'body' => 'Fourth post body', 'published' => 'N', 'created' => $ts, 'updated' => $ts))
		);
		$this->assertEqual($result, $expected);

		$this->model->validate = array('title' => VALID_NOT_EMPTY, 'author_id' => 'numeric');
		$data = array(
			array('id' => '1', 'title' => 'Un-Baleeted First Post', 'body' => 'Not Baleeted!', 'published' => 'Y'),
			array('id' => '2', 'title' => '', 'body' => 'Trying to get away with an empty title'),
		);
		$ts = date('Y-m-d H:i:s');
		$this->assertFalse($this->model->saveAll($data));

		$expected[0]['Post'] = array_merge($expected[0]['Post'], $data[0], array('updated' => $ts));
		$result = $this->model->find('all', array('recursive' => -1));
		$errors = array(2 => array('title' => 'This field cannot be left blank'));

		$this->assertEqual($result, $expected);
		$this->assertEqual($this->model->validationErrors, $errors);

		$data = array(
			array('id' => '1', 'title' => 'Re-Baleeted First Post', 'body' => 'Baleeted!', 'published' => 'N'),
			array('id' => '2', 'title' => '', 'body' => 'Trying to get away with an empty title'),
		);
		$this->assertFalse($this->model->saveAll($data, array('validate' => 'first')));

		$result = $this->model->find('all', array('recursive' => -1));
		$this->assertEqual($result, $expected);
		$this->assertEqual($this->model->validationErrors, $errors);

		$data = array(
			array('title' => 'First new post', 'body' => 'Woohoo!', 'published' => 'Y'),
			array('title' => 'Empty body', 'body' => '')
		);
		$this->model->validate['body'] = VALID_NOT_EMPTY;
	}

	function testSaveWithCounterCache() {
		$this->loadFixtures('Syfile', 'Item');
		$this->model =& new Syfile();
		$this->model2 =& new Item();

		$result = $this->model->findById(1);
		$this->assertIdentical($result['Syfile']['item_count'], null);

		$this->model2->save(array('name' => 'Item 7', 'syfile_id' => 1, 'published' => false));
		$result = $this->model->findById(1);
		$this->assertIdentical($result['Syfile']['item_count'], '2');

		$this->model2->delete(1);
		$result = $this->model->findById(1);
		$this->assertIdentical($result['Syfile']['item_count'], '1');

		$this->model2->id = 2;
		$this->model2->saveField('syfile_id', 1);

		$result = $this->model->findById(1);
		$this->assertIdentical($result['Syfile']['item_count'], '2');

		$result = $this->model->findById(2);
		$this->assertIdentical($result['Syfile']['item_count'], null);
	}

    function testSaveWithCounterCacheScope() {
		$this->loadFixtures('Syfile', 'Item');
		$this->model =& new Syfile();
		$this->model2 =& new Item();
		$this->model2->belongsTo['Syfile']['counterCache'] = true;
		$this->model2->belongsTo['Syfile']['counterScope'] = array('published' => true);

		$result = $this->model->findById(1);
		$this->assertIdentical($result['Syfile']['item_count'], null);

		$this->model2->save(array('name' => 'Item 7', 'syfile_id' => 1, 'published'=> true));
		$result = $this->model->findById(1);
		$this->assertIdentical($result['Syfile']['item_count'], '1');

		$this->model2->id = 1;
		$this->model2->saveField('published', true);
		$result = $this->model->findById(1);
		$this->assertIdentical($result['Syfile']['item_count'], '2');
	}

	function testDel() {
		$this->loadFixtures('Article');
		$this->model =& new Article();

		$result = $this->model->del(2);
		$this->assertTrue($result);

		$result = $this->model->read(null, 2);
		$this->assertFalse($result);

		$this->model->recursive = -1;
		$result = $this->model->findAll(null, array('id', 'title'));
		$expected = array(
			array('Article' => array('id' => 1, 'title' => 'First Article' )),
			array('Article' => array('id' => 3, 'title' => 'Third Article' ))
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->del(3);
		$this->assertTrue($result);

		$result = $this->model->read(null, 3);
		$this->assertFalse($result);

		$this->model->recursive = -1;
		$result = $this->model->findAll(null, array('id', 'title'));
		$expected = array(
			array('Article' => array('id' => 1, 'title' => 'First Article' ))
		);
		$this->assertEqual($result, $expected);
	}

	function testDeleteAll() {
		$this->loadFixtures('Article');
		$this->model =& new Article();

		$data = array('Article' => array('user_id' => 2, 'id' => 4, 'title' => 'Fourth Article', 'published' => 'N'));
		$result = $this->model->set($data) && $this->model->save();
		$this->assertTrue($result);

		$data = array('Article' => array('user_id' => 2, 'id' => 5, 'title' => 'Fifth Article', 'published' => 'Y'));
		$result = $this->model->set($data) && $this->model->save();
		$this->assertTrue($result);

		$data = array('Article' => array('user_id' => 1, 'id' => 6, 'title' => 'Sixth Article', 'published' => 'N'));
		$result = $this->model->set($data) && $this->model->save();
		$this->assertTrue($result);

		$this->model->recursive = -1;
		$result = $this->model->findAll(null, array('id', 'user_id', 'title', 'published'));
		$expected = array(
			array('Article' => array('id' => 1, 'user_id' => 1, 'title' => 'First Article', 'published' => 'Y' )),
			array('Article' => array('id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'published' => 'Y' )),
			array('Article' => array('id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'published' => 'Y' )),
			array('Article' => array('id' => 4, 'user_id' => 2, 'title' => 'Fourth Article', 'published' => 'N' )),
			array('Article' => array('id' => 5, 'user_id' => 2, 'title' => 'Fifth Article', 'published' => 'Y' )),
			array('Article' => array('id' => 6, 'user_id' => 1, 'title' => 'Sixth Article', 'published' => 'N' ))
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->deleteAll(array('Article.published' => 'N'));
		$this->assertTrue($result);

		$this->model->recursive = -1;
		$result = $this->model->findAll(null, array('id', 'user_id', 'title', 'published'));
		$expected = array(
			array('Article' => array('id' => 1, 'user_id' => 1, 'title' => 'First Article', 'published' => 'Y' )),
			array('Article' => array('id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'published' => 'Y' )),
			array('Article' => array('id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'published' => 'Y' )),
			array('Article' => array('id' => 5, 'user_id' => 2, 'title' => 'Fifth Article', 'published' => 'Y' ))
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->deleteAll(array('Article.user_id' => array(2, 3)));
		$this->assertTrue($result);

		$this->model->recursive = -1;
		$result = $this->model->findAll(null, array('id', 'user_id', 'title', 'published'));
		$expected = array(
			array('Article' => array('id' => 1, 'user_id' => 1, 'title' => 'First Article', 'published' => 'Y' )),
			array('Article' => array('id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'published' => 'Y' ))
		);
		$this->assertEqual($result, $expected);
	}

	function testRecursiveDel() {
		$this->loadFixtures('Article', 'Comment', 'Attachment');
		$this->model =& new Article();

		$result = $this->model->del(2);
		$this->assertTrue($result);

		$this->model->recursive = 2;
		$result = $this->model->read(null, 2);
		$this->assertFalse($result);

		$result = $this->model->Comment->read(null, 5);
		$this->assertFalse($result);

		$result = $this->model->Comment->read(null, 6);
		$this->assertFalse($result);

		$result = $this->model->Comment->Attachment->read(null, 1);
		$this->assertFalse($result);

		$result = $this->model->findCount();
		$this->assertEqual($result, 2);

		$result = $this->model->Comment->findCount();
		$this->assertEqual($result, 4);

		$result = $this->model->Comment->Attachment->findCount();
		$this->assertEqual($result, 0);
	}

	function testDependentExclusiveDelete() {
		$this->loadFixtures('Article', 'Comment');
		$this->model =& new Article10();

		$result = $this->model->find('all');
		$this->assertEqual(count($result[0]['Comment']), 4);
		$this->assertEqual(count($result[1]['Comment']), 2);
		$this->assertEqual($this->model->Comment->find('count'), 6);

		$this->model->delete(1);
		$this->assertEqual($this->model->Comment->find('count'), 2);
	}

	function testDeleteLinks() {
		$this->loadFixtures('Article', 'ArticlesTag', 'Tag');
		$this->model =& new Article();

		$result = $this->model->ArticlesTag->find('all');
		$expected = array(
			array('ArticlesTag' => array('article_id' => '1', 'tag_id' => '1')),
			array('ArticlesTag' => array('article_id' => '1', 'tag_id' => '2')),
			array('ArticlesTag' => array('article_id' => '2', 'tag_id' => '1')),
			array('ArticlesTag' => array('article_id' => '2', 'tag_id' => '3'))
		);
		$this->assertEqual($result, $expected);

		$this->Article->delete(1);
		$result = $this->model->ArticlesTag->find('all');

		$expected = array(
			array('ArticlesTag' => array('article_id' => '2', 'tag_id' => '1')),
			array('ArticlesTag' => array('article_id' => '2', 'tag_id' => '3'))
		);
		$this->assertEqual($result, $expected);
	}

	function testFindAllThreaded() {
		$this->loadFixtures('Category');
		$this->model =& new Category();

		$result = $this->model->findAllThreaded();
		$expected = array(
			array(
				'Category' => array('id' => '1', 'parent_id' => '0', 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
				'children' => array(
					array(
						'Category' => array('id' => '2', 'parent_id' => '1', 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
						'children' => array()
					),
					array(
						'Category' => array('id' => '3', 'parent_id' => '1', 'name' => 'Category 1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
						'children' => array()
					)
				)
			),
			array(
				'Category' => array('id' => '4', 'parent_id' => '0', 'name' => 'Category 2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
				'children' => array()
			),
			array(
				'Category' => array('id' => '5', 'parent_id' => '0', 'name' => 'Category 3', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
				'children' => array(
					array(
						'Category' => array('id' => '6', 'parent_id' => '5', 'name' => 'Category 3.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
						'children' => array()
					)
				)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findAllThreaded(array('Category.name' => 'LIKE Category 1%'));
		$expected = array(
			array(
				'Category' => array('id' => '1', 'parent_id' => '0', 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
				'children' => array(
					array(
						'Category' => array('id' => '2', 'parent_id' => '1', 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
						'children' => array()
					),
					array(
						'Category' => array('id' => '3', 'parent_id' => '1', 'name' => 'Category 1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
						'children' => array()
					)
				)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findAllThreaded(null, 'id, parent_id, name');
		$expected = array(
			array(
				'Category' => array('id' => '1', 'parent_id' => '0', 'name' => 'Category 1'),
				'children' => array(
					array(
						'Category' => array('id' => '2', 'parent_id' => '1', 'name' => 'Category 1.1'),
						'children' => array()
					),
					array(
						'Category' => array('id' => '3', 'parent_id' => '1', 'name' => 'Category 1.2'),
						'children' => array()
					)
				)
			),
			array(
				'Category' => array('id' => '4', 'parent_id' => '0', 'name' => 'Category 2'),
				'children' => array()
			),
			array(
				'Category' => array('id' => '5', 'parent_id' => '0', 'name' => 'Category 3'),
				'children' => array(
					array(
						'Category' => array('id' => '6', 'parent_id' => '5', 'name' => 'Category 3.1'),
						'children' => array()
					)
				)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findAllThreaded(null, null, 'id DESC');
		$expected = array(
			array(
				'Category' => array('id' => 5, 'parent_id' => 0, 'name' => 'Category 3', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
				'children' => array(
					array(
						'Category' => array('id' => 6, 'parent_id' => 5, 'name' => 'Category 3.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
						'children' => array()
					)
				)
			),
			array(
				'Category' => array('id' => 4, 'parent_id' => 0, 'name' => 'Category 2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
				'children' => array()
			),
			array(
				'Category' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
				'children' => array(
					array(
						'Category' => array('id' => 3, 'parent_id' => 1, 'name' => 'Category 1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
						'children' => array()
					),
					array(
						'Category' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
						'children' => array()
					)
				)
			)
		);
		$this->assertEqual($result, $expected);
	}

	function testDoThread() {
		$this->model =& new Category();
		$this->db->fullDebug = true;
		
		$result = array(
			array('Category' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1')),
			array('Category' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1')),
			array('Category' => array('id' => 3, 'parent_id' => 1, 'name' => 'Category 1.2')),
			array('Category' => array('id' => 4, 'parent_id' => 2, 'name' => 'Category 1.1.1')),
			array('Category' => array('id' => 5, 'parent_id' => 0, 'name' => 'Category 2')),
			array('Category' => array('id' => 6, 'parent_id' => 5, 'name' => 'Category 2.1')),
			array('Category' => array('id' => 7, 'parent_id' => 6, 'name' => 'Category 2.1.1'))
		);
		$result = $this->model->__doThread($result, null);
		
		$expected = array(
			array(
				'Category' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1'),
				'children' => array(
					array(
						'Category' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1'),
						'children' => array(
							array(
								'Category' => array('id' => 4, 'parent_id' => 2, 'name' => 'Category 1.1.1'),
								'children' => array()
							)
						)
					),
					array(
						'Category' => array('id' => 3, 'parent_id' => 1, 'name' => 'Category 1.2'),
						'children' => array()
					)
				)
			),
			array(
				'Category' => array('id' => 5, 'parent_id' => 0, 'name' => 'Category 2'),
				'children' => array(
					array(
						'Category' => array('id' => 6, 'parent_id' => 5, 'name' => 'Category 2.1'),
						'children' => array(
							array(
								'Category' => array('id' => 7, 'parent_id' => 6, 'name' => 'Category 2.1.1'),
								'children' => array()
							)
						)
					)
				)
			)
		);
		
		$this->assertEqual($result, $expected);
	}
	
	function testDoThreadOrdered() {
		$this->model =& new Category();
		$this->db->fullDebug = true;
		
		$result = array(
			array('Category' => array('id' => 7, 'parent_id' => 6, 'name' => 'Category 2.1.1')),
			array('Category' => array('id' => 6, 'parent_id' => 5, 'name' => 'Category 2.1')),	
			array('Category' => array('id' => 5, 'parent_id' => 0, 'name' => 'Category 2')),
			array('Category' => array('id' => 4, 'parent_id' => 2, 'name' => 'Category 1.1.1')),
			array('Category' => array('id' => 3, 'parent_id' => 1, 'name' => 'Category 1.2')),
			array('Category' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1')),
			array('Category' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1'))
		);
		$result = $this->model->__doThread($result, null);
		
		$expected = array(
			array(
				'Category' => array('id' => 5, 'parent_id' => 0, 'name' => 'Category 2'),
				'children' => array(
					array(
						'Category' => array('id' => 6, 'parent_id' => 5, 'name' => 'Category 2.1'),
						'children' => array(
							array(
								'Category' => array('id' => 7, 'parent_id' => 6, 'name' => 'Category 2.1.1'),
								'children' => array()
							)
						)
					)
				)
			),
			array(
				'Category' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1'),
				'children' => array(
					array(
						'Category' => array('id' => 3, 'parent_id' => 1, 'name' => 'Category 1.2'),
						'children' => array()
					),
					array(
						'Category' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1'),
						'children' => array(
							array(
								'Category' => array('id' => 4, 'parent_id' => 2, 'name' => 'Category 1.1.1'),
								'children' => array()
							)
						)
					)
				)
			)
		);
		
		$this->assertEqual($result, $expected);
	}

	function testFindNeighbours() {
		$this->loadFixtures('User', 'Article');
		$this->model =& new Article();

		$result = $this->model->findNeighbours(null, 'Article.id', '2');
		$expected = array('prev' => array('Article' => array('id' => 1)), 'next' => array('Article' => array('id' => 3)));
		$this->assertEqual($result, $expected);

		$result = $this->model->findNeighbours(null, 'Article.id', '3');
		$expected = array('prev' => array('Article' => array('id' => 2)), 'next' => array());
		$this->assertEqual($result, $expected);

		$result = $this->model->findNeighbours(array('User.id' => 1), array('Article.id', 'Article.title'), 2);
		$expected = array(
			'prev' => array('Article' => array('id' => 1, 'title' => 'First Article')),
			'next' => array('Article' => array('id' => 3, 'title' => 'Third Article')),
		);
		$this->assertEqual($result, $expected);
	}

	function testFindCombinedRelations() {
		$this->loadFixtures('Apple', 'Sample');
		$this->model =& new Apple();

		$result = $this->model->findAll();

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

	function testSaveEmpty() {
		$this->loadFixtures('Thread');
		$this->model =& new Thread();
		$data = array();
		$expected = $this->model->save($data);
		$this->assertFalse($expected);
	}

	// function testBasicValidation() {
	// 	$this->model =& new ValidationTest();
	// 	$this->model->testing = true;
	// 	$this->model->set(array('title' => '', 'published' => 1));
	// 	$this->assertEqual($this->model->invalidFields(), array('title' => 'This field cannot be left blank'));
	//
	// 	$this->model->create();
	// 	$this->model->set(array('title' => 'Hello', 'published' => 0));
	// 	$this->assertEqual($this->model->invalidFields(), array('published' => 'This field cannot be left blank'));
	//
	// 	$this->model->create();
	// 	$this->model->set(array('title' => 'Hello', 'published' => 1, 'body' => ''));
	// 	$this->assertEqual($this->model->invalidFields(), array('body' => 'This field cannot be left blank'));
	// }

	function testMultipleValidation() {
		$this->model =& new ValidationTest();
	}

	function testLoadModelSecondIteration() {
		$model = new ModelA();
		$this->assertIsA($model,'ModelA');

		$this->assertIsA($model->ModelB, 'ModelB');
		$this->assertIsA($model->ModelB->ModelD, 'ModelD');

		$this->assertIsA($model->ModelC, 'ModelC');
		$this->assertIsA($model->ModelC->ModelD, 'ModelD');
	}

	function testRecursiveUnbind() {
		$this->loadFixtures('Apple', 'Sample');
		$this->model =& new Apple();
		$this->model->recursive = 2;

		$result = $this->model->findAll();
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

		$result = $this->model->Parent->unbindModel(array('hasOne' => array('Sample')));
		$this->assertTrue($result);

		$result = $this->model->findAll();
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

		$result = $this->model->Parent->unbindModel(array('hasOne' => array('Sample')));
		$this->assertTrue($result);

		$result = $this->model->unbindModel(array('hasMany' => array('Child')));
		$this->assertTrue($result);

		$result = $this->model->findAll();
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

		$result = $this->model->unbindModel(array('hasMany' => array('Child')));
		$this->assertTrue($result);

		$result = $this->model->Sample->unbindModel(array('belongsTo' => array('Apple')));
		$this->assertTrue($result);

		$result = $this->model->findAll();
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

		$result = $this->model->Parent->unbindModel(array('belongsTo' => array('Parent')));
		$this->assertTrue($result);

		$result = $this->model->unbindModel(array('hasMany' => array('Child')));
		$this->assertTrue($result);

		$result = $this->model->findAll();
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

	function testAutoSaveUuid() {
		// SQLite does not support non-integer primary keys
		$db =& ConnectionManager::getDataSource('test_suite');
		if ($db->config['driver'] == 'sqlite') {
			return;
		}

		$this->loadFixtures('Uuid');
		$this->model =& new Uuid();

		$this->model->save(array('title' => 'Test record'));
		$result = $this->model->findByTitle('Test record');
		$this->assertEqual(array_keys($result['Uuid']), array('id', 'title', 'count', 'created', 'updated'));
		$this->assertEqual(strlen($result['Uuid']['id']), 36);
	}

	function testZeroDefaultFieldValue() {
		$this->loadFixtures('DataTest');
		$this->model =& new DataTest();

		$this->model->create(array('float' => '')) && $this->model->save();
		$result = $this->model->findById($this->model->id);
		$this->assertIdentical($result['DataTest']['count'], '0');
		$this->assertIdentical($result['DataTest']['float'], '0');
	}

	function testNonNumericHabtmJoinKey() {
		$this->loadFixtures('Post', 'Tag', 'PostsTag');
		$this->Post =& new Post();
		$this->Post->bind('Tag', array('type' => 'hasAndBelongsToMany'));
		$this->Post->Tag->primaryKey = 'tag';

		$result = $this->Post->find('all');
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

	function testAfterFindAssociation() {

	}

	function testDeconstructFields() {
		$this->loadFixtures('Apple');
		$this->model =& new Apple();

		$data['Apple']['created']['year'] = '';
		$data['Apple']['created']['month'] = '';
		$data['Apple']['created']['day'] = '';
		$data['Apple']['created']['hour'] = '';
		$data['Apple']['created']['min'] = '';
		$data['Apple']['created']['sec'] = '';

		$this->model->data = null;
		$this->model->set($data);
		$expected = array('Apple'=> array('created'=> ''));
		$this->assertEqual($this->model->data, $expected);

		$data = array();
		$data['Apple']['created']['year'] = '2007';
		$data['Apple']['created']['month'] = '08';
		$data['Apple']['created']['day'] = '20';
		$data['Apple']['created']['hour'] = '';
		$data['Apple']['created']['min'] = '';
		$data['Apple']['created']['sec'] = '';

		$this->model->data = null;
		$this->model->set($data);
		$expected = array('Apple'=> array('created'=> '2007-08-20 00:00:00'));
		$this->assertEqual($this->model->data, $expected);

		$data = array();
		$data['Apple']['created']['year'] = '2007';
		$data['Apple']['created']['month'] = '08';
		$data['Apple']['created']['day'] = '20';
		$data['Apple']['created']['hour'] = '10';
		$data['Apple']['created']['min'] = '12';
		$data['Apple']['created']['sec'] = '';

		$this->model->data = null;
		$this->model->set($data);
		$expected = array('Apple'=> array('created'=> '2007-08-20 10:12:00'));
		$this->assertEqual($this->model->data, $expected);

		$data = array();
		$data['Apple']['created']['year'] = '2007';
		$data['Apple']['created']['month'] = '';
		$data['Apple']['created']['day'] = '12';
		$data['Apple']['created']['hour'] = '20';
		$data['Apple']['created']['min'] = '';
		$data['Apple']['created']['sec'] = '';

		$this->model->data = null;
		$this->model->set($data);
		$expected = array('Apple'=> array('created'=> ''));
		$this->assertEqual($this->model->data, $expected);

		$data = array();
		$data['Apple']['created']['hour'] = '20';
		$data['Apple']['created']['min'] = '33';

		$this->model->data = null;
		$this->model->set($data);
		$expected = array('Apple'=> array('created'=> ''));
		$this->assertEqual($this->model->data, $expected);

		$data = array();
		$data['Apple']['created']['hour'] = '20';
		$data['Apple']['created']['min'] = '33';
		$data['Apple']['created']['sec'] = '33';

		$this->model->data = null;
		$this->model->set($data);
		$expected = array('Apple'=> array('created'=> ''));
		$this->assertEqual($this->model->data, $expected);

		$data = array();
		$data['Apple']['created']['hour'] = '13';
		$data['Apple']['created']['min'] = '00';
		$data['Apple']['date']['year'] = '2006';
		$data['Apple']['date']['month'] = '12';
		$data['Apple']['date']['day'] = '25';

		$this->model->data = null;
		$this->model->set($data);
		$expected = array('Apple'=> array('created'=> '', 'date'=> '2006-12-25'));
		$this->assertEqual($this->model->data, $expected);

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

		$this->model->data = null;
		$this->model->set($data);
		$expected = array('Apple'=> array('created'=> '2007-08-20 10:12:09', 'date'=> '2006-12-25'));
		$this->assertEqual($this->model->data, $expected);

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

		$this->model->data = null;
		$this->model->set($data);
		$expected = array('Apple'=> array('created'=> '', 'date'=> ''));
		$this->assertEqual($this->model->data, $expected);

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

		$this->model->data = null;
		$this->model->set($data);
		$expected = array('Apple'=> array('created'=> '', 'date'=> '2006-12-25'));
		$this->assertEqual($this->model->data, $expected);

		$data = array();
		$data['Apple']['date']['year'] = '2006';
		$data['Apple']['date']['month'] = '12';
		$data['Apple']['date']['day'] = '25';

		$this->model->data = null;
		$this->model->set($data);
		$expected = array('Apple'=> array('date'=> '2006-12-25'));
		$this->assertEqual($this->model->data, $expected);

		$data = array();
		$data['Apple']['mytime']['hour'] = '03';
		$data['Apple']['mytime']['min'] = '04';
		$data['Apple']['mytime']['sec'] = '04';

		$this->model->data = null;
		$this->model->set($data);
		$expected = array('Apple'=> array('mytime'=> '03:04:04'));
		$this->assertEqual($this->model->data, $expected);

		$data = array();
		$data['Apple']['mytime']['hour'] = '3';
		$data['Apple']['mytime']['min'] = '4';
		$data['Apple']['mytime']['sec'] = '4';

		$this->model->data = null;
		$this->model->set($data);
		$expected = array('Apple' => array('mytime'=> '03:04:04'));
		$this->assertEqual($this->model->data, $expected);

		$data = array();
		$data['Apple']['mytime']['hour'] = '03';
		$data['Apple']['mytime']['min'] = '4';
		$data['Apple']['mytime']['sec'] = '4';

		$this->model->data = null;
		$this->model->set($data);
		$expected = array('Apple'=> array('mytime'=> '03:04:04'));
		$this->assertEqual($this->model->data, $expected);
	}

	function testTablePrefixSwitching() {
		$db =& ConnectionManager::getDataSource('test_suite');
		ConnectionManager::create('database1', array_merge($db->config, array('prefix' => 'aaa_')));
		ConnectionManager::create('database2', array_merge($db->config, array('prefix' => 'bbb_')));

		$db1 = ConnectionManager::getDataSource('database1');
		$db2 = ConnectionManager::getDataSource('database2');

		$this->model = new Apple();
		$this->model->setDataSource('database1');
		$this->assertEqual($db->fullTableName($this->model, false), 'aaa_apples');
		$this->assertEqual($db1->fullTableName($this->model, false), 'aaa_apples');
		$this->assertEqual($db2->fullTableName($this->model, false), 'aaa_apples');

		$this->model->setDataSource('database2');
		$this->assertEqual($db->fullTableName($this->model, false), 'bbb_apples');
		$this->assertEqual($db1->fullTableName($this->model, false), 'bbb_apples');
		$this->assertEqual($db2->fullTableName($this->model, false), 'bbb_apples');

		$this->model = new Apple();
		$this->model->tablePrefix = 'custom_';
		$this->assertEqual($db->fullTableName($this->model, false), 'custom_apples');
		$this->model->setDataSource('database1');
		$this->assertEqual($db->fullTableName($this->model, false), 'custom_apples');
		$this->assertEqual($db1->fullTableName($this->model, false), 'custom_apples');
	}

	function testDynamicBehaviorAttachment() {
		$this->loadFixtures('Apple');
		$this->model =& new Apple();
		$this->assertEqual($this->model->Behaviors->attached(), array());

		$this->model->Behaviors->attach('Tree', array('left' => 'left_field', 'right' => 'right_field'));
		$this->assertTrue(is_object($this->model->Behaviors->Tree));
		$this->assertEqual($this->model->Behaviors->attached(), array('Tree'));

		$expected = array('parent' => 'parent_id', 'left' => 'left_field', 'right' => 'right_field', 'scope' => '1 = 1', 'type' => 'nested', '__parentChange' => false);
		$this->assertEqual($this->model->Behaviors->Tree->settings['Apple'], $expected);

		$expected['enabled'] = false;
		$this->model->Behaviors->attach('Tree', array('enabled' => false));
		$this->assertEqual($this->model->Behaviors->Tree->settings['Apple'], $expected);
		$this->assertEqual($this->model->Behaviors->attached(), array('Tree'));

		$this->model->Behaviors->detach('Tree');
		$this->assertEqual($this->model->Behaviors->attached(), array());
		$this->assertFalse(isset($this->model->Behaviors->Tree));
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
			return;
		}
		$this->loadFixtures('Article', 'Tag', 'ArticlesTag', 'User', 'Comment');
		$this->model =& new Article();

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
		$this->assertEqual($this->model->find('all'), $expected);

		$db2 =& ConnectionManager::getDataSource('test2');

		foreach (array('User', 'Comment') as $class) {
			$this->_fixtures[$this->_fixtureClassMap[$class]]->create($db2);
			$this->_fixtures[$this->_fixtureClassMap[$class]]->insert($db2);
			$this->db->truncate(Inflector::pluralize(Inflector::underscore($class)));
		}

		$this->assertEqual($this->model->User->find('all'), array());
		$this->assertEqual($this->model->Comment->find('all'), array());
		$this->assertEqual($this->model->find('count'), 3);

		$this->model->User->setDataSource('test2');
		$this->model->Comment->setDataSource('test2');

		$result = Set::extract($this->model->User->find('all'), '{n}.User.id');
		$this->assertEqual($result, array('1', '2', '3', '4'));
		$this->assertEqual($this->model->find('all'), $expected);

		$this->model->Comment->unbindModel(array('hasOne' => array('Attachment')));
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
		$this->assertEqual($this->model->Comment->find('all'), $expected);

		foreach (array('User', 'Comment') as $class) {
			$this->_fixtures[$this->_fixtureClassMap[$class]]->drop($db2);
		}
	}

    function testDisplayField() {
        $this->loadFixtures('Post', 'Comment', 'Person');
        $this->Post = new Post();
        $this->Comment = new Comment();
        $this->Person = new Person();

        $this->assertEqual($this->Post->displayField, 'title');
        $this->assertEqual($this->Person->displayField, 'name');
        $this->assertEqual($this->Comment->displayField, 'id');
    }


	function testOldQuery() {
		$this->loadFixtures('Article');
		$this->Article =& new Article();

		$query = "SELECT title FROM articles WHERE articles.id IN (1,2)";
		$results = $this->Article->query($query);
		$this->assertTrue(is_array($results));
		$this->assertEqual(count($results), 2);

		$query = "SELECT title, body FROM articles WHERE articles.id = 1";
		$results = $this->Article->query($query, false);
		$db =& ConnectionManager::getDataSource($this->Article->useDbConfig);
		$this->assertTrue(!isset($db->_queryCache[$query]));
		$this->assertTrue(is_array($results));

		$query = "SELECT title, id FROM articles WHERE articles.published = 'Y'";
		$results = $this->Article->query($query, true);
		$this->assertTrue(isset($db->_queryCache[$query]));
		$this->assertTrue(is_array($results));
	}

	function testPreparedQuery() {
		$this->loadFixtures('Article');
		$this->Article =& new Article();
		$db =& ConnectionManager::getDataSource($this->Article->useDbConfig);

		$finalQuery = "SELECT title, published FROM articles WHERE articles.id = 1 AND articles.published = 'Y'";
		$query = "SELECT title, published FROM articles WHERE articles.id = ? AND articles.published = ?";
		$params = array(1, 'Y');
		$result = $this->Article->query($query, $params);
		$expected = array('0' => array('articles' => array('title' => 'First Article', 'published' => 'Y')));
		$this->assertEqual($result, $expected);
		$this->assertTrue(isset($db->_queryCache[$finalQuery]));

		$finalQuery = "SELECT id, created FROM articles WHERE articles.title = 'First Article'";
		$query = "SELECT id, created FROM articles  WHERE articles.title = ?";
		$params = array('First Article');
		$result = $this->Article->query($query, $params, false);
		$this->assertTrue(is_array($result));
		$this->assertTrue(isset($result[0]['articles']));
		$this->assertFalse(isset($db->_queryCache[$finalQuery]));

		$query = "SELECT title FROM articles WHERE articles.title LIKE ?";
		$params = array('%First%');
		$result = $this->Article->query($query, $params);
		$this->assertTrue(is_array($result));
		$this->assertTrue(isset($result[0]['articles']['title']));
	}

	function testParameterMismatch() {
		$this->loadFixtures('Article');
		$this->Article =& new Article();

		$query = "SELECT * FROM articles WHERE articles.published = ? AND articles.user_id = ?";
		$params = array('Y');
		$result = $this->Article->query($query, $params);
		$this->assertEqual($result, null);
	}

	function testVeryStrangeUseCase() {
		$this->loadFixtures('Article');
		$this->Article =& new Article();

		$query = "SELECT * FROM ? WHERE ? = ? AND ? = ?";
		$param = array('articles', 'articles.user_id', '3', 'articles.published', 'Y');
		$this->expectError();
		ob_start();
		$result = $this->Article->query($query, $param);
		ob_end_clean();
	}

	function endTest() {
		ClassRegistry::flush();
	}
}
?>