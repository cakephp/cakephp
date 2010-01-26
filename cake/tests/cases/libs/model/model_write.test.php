<?php
/* SVN FILE: $Id: model.test.php 8225 2009-07-08 03:25:30Z mark_story $ */

/**
 * ModelWriteTest file
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
 * @subpackage    cake.tests.cases.libs.model
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
require_once dirname(__FILE__) . DS . 'model.test.php';
/**
 * ModelWriteTest
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.operations
 */
class ModelWriteTest extends BaseModelTest {

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
		$expected = array(
			'JoinAsJoinB' => array(
				'id' => 1,
				'join_a_id' => 1,
				'join_b_id' => 2,
				'other' => 'Data for Join A 1 Join B 2',
				'created' => '2008-01-03 10:56:33',
				'updated' => '2008-01-03 10:56:33'
		));
		$this->assertEqual($result, $expected);

		$TestModel->JoinAsJoinB->create();
		$result = $TestModel->JoinAsJoinB->save(array(
			'join_a_id' => 1,
			'join_b_id' => 1,
			'other' => 'Data for Join A 1 Join B 1',
			'created' => '2008-01-03 10:56:44',
			'updated' => '2008-01-03 10:56:44'
		));
		$this->assertTrue($result);
		$lastInsertId = $TestModel->JoinAsJoinB->getLastInsertID();
		$this->assertTrue($lastInsertId != null);

		$result = $TestModel->JoinAsJoinB->findById(1);
		$expected = array(
			'JoinAsJoinB' => array(
				'id' => 1,
				'join_a_id' => 1,
				'join_b_id' => 2,
				'other' => 'Data for Join A 1 Join B 2',
				'created' => '2008-01-03 10:56:33',
				'updated' => '2008-01-03 10:56:33'
		));
		$this->assertEqual($result, $expected);

		$updatedValue = 'UPDATED Data for Join A 1 Join B 2';
		$TestModel->JoinAsJoinB->id = 1;
		$result = $TestModel->JoinAsJoinB->saveField('other', $updatedValue, false);
		$this->assertTrue($result);

		$result = $TestModel->JoinAsJoinB->findById(1);
		$this->assertEqual($result['JoinAsJoinB']['other'], $updatedValue);
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

		$data = array(
			'Article' => array(
				'created' => array(
					'day' => '1',
					'month' => '1',
					'year' => '2008'
				),
				'title' => 'Test Title',
				'user_id' => 1
		));
		$Article->create();
		$this->assertTrue($Article->save($data));

		$testResult = $Article->find(array('Article.title' => 'Test Title'));

		$this->assertEqual($testResult['Article']['title'], $data['Article']['title']);
		$this->assertEqual($testResult['Article']['created'], '2008-01-01 00:00:00');

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
 * testAutoSaveUuid method
 *
 * @access public
 * @return void
 */
	function testAutoSaveUuid() {
		// SQLite does not support non-integer primary keys
		$this->skipIf($this->db->config['driver'] == 'sqlite');

		$this->loadFixtures('Uuid');
		$TestModel =& new Uuid();

		$TestModel->save(array('title' => 'Test record'));
		$result = $TestModel->findByTitle('Test record');
		$this->assertEqual(
			array_keys($result['Uuid']),
			array('id', 'title', 'count', 'created', 'updated')
		);
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
			'%s SQLite uses loose typing, this operation is unsupported'
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
		$Post->bindModel(array(
			'hasAndBelongsToMany' => array('Tag')
		));
		$Post->Tag->primaryKey = 'tag';

		$result = $Post->find('all');
		$expected = array(
			array(
				'Post' => array(
					'id' => '1',
					'author_id' => '1',
					'title' => 'First Post',
					'body' => 'First Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'Author' => array(
					'id' => null,
					'user' => null,
					'password' => null,
					'created' => null,
					'updated' => null,
					'test' => 'working'
				),
				'Tag' => array(
					array(
						'id' => '1',
						'tag' => 'tag1',
						'created' => '2007-03-18 12:22:23',
						'updated' => '2007-03-18 12:24:31'
					),
					array(
						'id' => '2',
						'tag' => 'tag2',
						'created' => '2007-03-18 12:24:23',
						'updated' => '2007-03-18 12:26:31'
			))),
			array(
				'Post' => array(
					'id' => '2',
					'author_id' => '3',
					'title' => 'Second Post',
					'body' => 'Second Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				),
				'Author' => array(
					'id' => null,
					'user' => null,
					'password' => null,
					'created' => null,
					'updated' => null,
					'test' => 'working'
				),
				'Tag' => array(
					array(
						'id' => '1',
						'tag' => 'tag1',
						'created' => '2007-03-18 12:22:23',
						'updated' => '2007-03-18 12:24:31'
						),
					array(
						'id' => '3',
						'tag' => 'tag3',
						'created' => '2007-03-18 12:26:23',
						'updated' => '2007-03-18 12:28:31'
			))),
			array(
				'Post' => array(
					'id' => '3',
					'author_id' => '1',
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				),
				'Author' => array(
					'id' => null,
					'user' => null,
					'password' => null,
					'created' => null,
					'updated' => null,
					'test' => 'working'
				),
				'Tag' => array()
		));
		$this->assertEqual($result, $expected);
	}

/**
 * Tests validation parameter order in custom validation methods
 *
 * @access public
 * @return void
 */
	function testAllowSimulatedFields() {
		$TestModel =& new ValidationTest1();

		$TestModel->create(array(
			'title' => 'foo',
			'bar' => 'baz'
		));
		$expected = array(
			'ValidationTest1' => array(
				'title' => 'foo',
				'bar' => 'baz'
		));
		$this->assertEqual($TestModel->data, $expected);
	}

/**
 * test that Caches are getting cleared on save().
 * ensure that both inflections of controller names are getting cleared
 * as url for controller could be either overallFavorites/index or overall_favorites/index
 *
 * @return void
 */
	function testCacheClearOnSave() {
		$_back = array(
			'check' => Configure::read('Cache.check'),
			'disable' => Configure::read('Cache.disable'),
		);
		Configure::write('Cache.check', true);
		Configure::write('Cache.disable', false);

		$this->loadFixtures('OverallFavorite');
		$OverallFavorite =& new OverallFavorite();

		touch(CACHE . 'views' . DS . 'some_dir_overallfavorites_index.php');
		touch(CACHE . 'views' . DS . 'some_dir_overall_favorites_index.php');

		$data = array(
			'OverallFavorite' => array(
		 		'model_type' => '8-track',
				'model_id' => '3',
				'priority' => '1'
			)
		);
		$OverallFavorite->create($data);
		$OverallFavorite->save();

		$this->assertFalse(file_exists(CACHE . 'views' . DS . 'some_dir_overallfavorites_index.php'));
		$this->assertFalse(file_exists(CACHE . 'views' . DS . 'some_dir_overall_favorites_index.php'));

		Configure::write('Cache.check', $_back['check']);
		Configure::write('Cache.disable', $_back['disable']);
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

		$TestModel2->save(array(
			'name' => 'Item 7',
			'syfile_id' => 1,
			'published' => false
		));

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
		$this->assertIdentical($result['Syfile']['item_count'], '0');
	}

/**
 * Tests that counter caches are updated when records are added
 *
 * @access public
 * @return void
 */
	function testCounterCacheIncrease() {
		$this->loadFixtures('CounterCacheUser', 'CounterCachePost');
		$User = new CounterCacheUser();
		$Post = new CounterCachePost();
		$data = array('Post' => array(
			'title' => 'New Post',
			'user_id' => 66
		));

		$Post->save($data);
		$user = $User->find('first', array(
			'conditions' => array('id' => 66),
			'recursive' => -1
		));

		$result = $user[$User->alias]['post_count'];
		$expected = 3;
		$this->assertEqual($result, $expected);
	}

/**
 * Tests that counter caches are updated when records are deleted
 *
 * @access public
 * @return void
 */
	function testCounterCacheDecrease() {
		$this->loadFixtures('CounterCacheUser', 'CounterCachePost');
		$User = new CounterCacheUser();
		$Post = new CounterCachePost();

		$Post->delete(2);
		$user = $User->find('first', array(
			'conditions' => array('id' => 66),
			'recursive' => -1
		));

		$result = $user[$User->alias]['post_count'];
		$expected = 1;
		$this->assertEqual($result, $expected);
	}

/**
 * Tests that counter caches are updated when foreign keys of counted records change
 *
 * @access public
 * @return void
 */
	function testCounterCacheUpdated() {
		$this->loadFixtures('CounterCacheUser', 'CounterCachePost');
		$User = new CounterCacheUser();
		$Post = new CounterCachePost();

		$data = $Post->find('first', array(
			'conditions' => array('id' => 1),
			'recursive' => -1
		));
		$data[$Post->alias]['user_id'] = 301;
		$Post->save($data);

		$users = $User->find('all',array('order' => 'User.id'));
		$this->assertEqual($users[0]['User']['post_count'], 1);
		$this->assertEqual($users[1]['User']['post_count'], 2);
	}

/**
 * Test counter cache with models that use a non-standard (i.e. not using 'id')
 * as their primary key.
 *
 * @access public
 * @return void
 */
	function testCounterCacheWithNonstandardPrimaryKey() {
		$this->loadFixtures(
			'CounterCacheUserNonstandardPrimaryKey',
			'CounterCachePostNonstandardPrimaryKey'
		);

		$User = new CounterCacheUserNonstandardPrimaryKey();
		$Post = new CounterCachePostNonstandardPrimaryKey();

		$data = $Post->find('first', array(
			'conditions' => array('pid' => 1),
			'recursive' => -1
		));
		$data[$Post->alias]['uid'] = 301;
		$Post->save($data);

		$users = $User->find('all',array('order' => 'User.uid'));
		$this->assertEqual($users[0]['User']['post_count'], 1);
		$this->assertEqual($users[1]['User']['post_count'], 2);
	}

/**
 * test Counter Cache With Self Joining table
 *
 * @return void
 * @access public
 */
	function testCounterCacheWithSelfJoin() {
		$skip = $this->skipIf(
			($this->db->config['driver'] == 'sqlite'),
			'SQLite 2.x does not support ALTER TABLE ADD COLUMN'
		);
		if ($skip) {
			return;
		}

		$this->loadFixtures('CategoryThread');
		$this->db->query('ALTER TABLE '. $this->db->fullTableName('category_threads') . " ADD COLUMN child_count INTEGER");
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

		$TestModel2->save(array(
			'name' => 'Item 7',
			'syfile_id' => 1,
			'published'=> true
		));

		$result = $TestModel->findById(1);
		$this->assertIdentical($result['Syfile']['item_count'], '1');

		$TestModel2->id = 1;
		$TestModel2->saveField('published', true);
		$result = $TestModel->findById(1);
		$this->assertIdentical($result['Syfile']['item_count'], '2');

		$TestModel2->save(array(
			'id' => 1,
			'syfile_id' => 1,
			'published'=> false
		));

		$result = $TestModel->findById(1);
		$this->assertIdentical($result['Syfile']['item_count'], '1');
	}

/**
 * test that beforeValidate returning false can abort saves.
 *
 * @return void
 */
	function testBeforeValidateSaveAbortion() {
		$Model =& new CallbackPostTestModel();
		$Model->beforeValidateReturn = false;

		$data = array(
			'title' => 'new article',
			'body' => 'this is some text.'
		);
		$Model->create();
		$result = $Model->save($data);
		$this->assertFalse($result);
	}
/**
 * test that beforeSave returning false can abort saves.
 *
 * @return void
 */
	function testBeforeSaveSaveAbortion() {
		$Model =& new CallbackPostTestModel();
		$Model->beforeSaveReturn = false;

		$data = array(
			'title' => 'new article',
			'body' => 'this is some text.'
		);
		$Model->create();
		$result = $Model->save($data);
		$this->assertFalse($result);
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
			'id' => '1',
			'user_id' => '1',
			'title' => 'New First Article',
			'body' => 'First Article Body'
		));
		$this->assertEqual($result, $expected);

		$TestModel->id = 1;
		$result = $TestModel->saveField('title', '');
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body'), 1);
		$expected = array('Article' => array(
			'id' => '1',
			'user_id' => '1',
			'title' => '',
			'body' => 'First Article Body'
		));
		$result['Article']['title'] = trim($result['Article']['title']);
		$this->assertEqual($result, $expected);

		$TestModel->id = 1;
		$TestModel->set('body', 'Messed up data');
		$this->assertTrue($TestModel->saveField('title', 'First Article'));
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body'), 1);
		$expected = array('Article' => array(
			'id' => '1',
			'user_id' => '1',
			'title' => 'First Article',
			'body' => 'First Article Body'
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
		$this->loadFixtures(
			'User',
			'Article',
			'User',
			'Comment',
			'Tag',
			'ArticlesTag',
			'Attachment'
		);
		$TestModel =& new User();

		$data = array('User' => array(
			'user' => 'user',
			'password' => ''
		));
		$result = $TestModel->save($data);
		$this->assertFalse($result);
		$this->assertTrue(!empty($TestModel->validationErrors));

		$TestModel =& new Article();

		$data = array('Article' => array(
			'user_id' => '',
			'title' => '',
			'body' => ''
		));
		$result = $TestModel->create($data) && $TestModel->save();
		$this->assertFalse($result);
		$this->assertTrue(!empty($TestModel->validationErrors));

		$data = array('Article' => array(
			'id' => 1,
			'user_id' => '1',
			'title' => 'New First Article',
			'body' => ''
		));
		$result = $TestModel->create($data) && $TestModel->save();
		$this->assertFalse($result);

		$data = array('Article' => array(
			'id' => 1,
			'title' => 'New First Article'
		));
		$result = $TestModel->create() && $TestModel->save($data, false);
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 1);
		$expected = array('Article' => array(
			'id' => '1',
			'user_id' => '1',
			'title' => 'New First Article',
			'body' => 'First Article Body',
			'published' => 'N'
		));
		$this->assertEqual($result, $expected);

		$data = array('Article' => array(
			'id' => 1,
			'user_id' => '2',
			'title' => 'First Article',
			'body' => 'New First Article Body',
			'published' => 'Y'
		));
		$result = $TestModel->create() && $TestModel->save($data, true, array('id', 'title', 'published'));
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 1);
		$expected = array('Article' => array(
			'id' => '1',
			'user_id' => '1',
			'title' => 'First Article',
			'body' => 'First Article Body',
			'published' => 'Y'
		));
		$this->assertEqual($result, $expected);

		$data = array(
			'Article' => array(
				'user_id' => '2',
				'title' => 'New Article',
				'body' => 'New Article Body',
				'created' => '2007-03-18 14:55:23',
				'updated' => '2007-03-18 14:57:31'
			),
			'Tag' => array('Tag' => array(1, 3))
		);
		$TestModel->create();
		$result = $TestModel->create() && $TestModel->save($data);
		$this->assertTrue($result);

		$TestModel->recursive = 2;
		$result = $TestModel->read(null, 4);
		$expected = array(
			'Article' => array(
				'id' => '4',
				'user_id' => '2',
				'title' => 'New Article',
				'body' => 'New Article Body',
				'published' => 'N',
				'created' => '2007-03-18 14:55:23',
				'updated' => '2007-03-18 14:57:31'
			),
			'User' => array(
				'id' => '2',
				'user' => 'nate',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				'created' => '2007-03-17 01:18:23',
				'updated' => '2007-03-17 01:20:31'
			),
			'Comment' => array(),
			'Tag' => array(
				array(
					'id' => '1',
					'tag' => 'tag1',
					'created' => '2007-03-18 12:22:23',
					'updated' => '2007-03-18 12:24:31'
				),
				array(
					'id' => '3',
					'tag' => 'tag3',
					'created' => '2007-03-18 12:26:23',
					'updated' => '2007-03-18 12:28:31'
		)));
		$this->assertEqual($result, $expected);

		$data = array('Comment' => array(
			'article_id' => '4',
			'user_id' => '1',
			'comment' => 'Comment New Article',
			'published' => 'Y',
			'created' => '2007-03-18 14:57:23',
			'updated' => '2007-03-18 14:59:31'
		));
		$result = $TestModel->Comment->create() && $TestModel->Comment->save($data);
		$this->assertTrue($result);

		$data = array('Attachment' => array(
			'comment_id' => '7',
			'attachment' => 'newattachment.zip',
			'created' => '2007-03-18 15:02:23',
			'updated' => '2007-03-18 15:04:31'
		));
		$result = $TestModel->Comment->Attachment->save($data);
		$this->assertTrue($result);

		$TestModel->recursive = 2;
		$result = $TestModel->read(null, 4);
		$expected = array(
			'Article' => array(
				'id' => '4',
				'user_id' => '2',
				'title' => 'New Article',
				'body' => 'New Article Body',
				'published' => 'N',
				'created' => '2007-03-18 14:55:23',
				'updated' => '2007-03-18 14:57:31'
			),
			'User' => array(
				'id' => '2',
				'user' => 'nate',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				'created' => '2007-03-17 01:18:23',
				'updated' => '2007-03-17 01:20:31'
			),
			'Comment' => array(
				array(
					'id' => '7',
					'article_id' => '4',
					'user_id' => '1',
					'comment' => 'Comment New Article',
					'published' => 'Y',
					'created' => '2007-03-18 14:57:23',
					'updated' => '2007-03-18 14:59:31',
					'Article' => array(
						'id' => '4',
						'user_id' => '2',
						'title' => 'New Article',
						'body' => 'New Article Body',
						'published' => 'N',
						'created' => '2007-03-18 14:55:23',
						'updated' => '2007-03-18 14:57:31'
					),
					'User' => array(
						'id' => '1',
						'user' => 'mariano',
						'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:16:23',
						'updated' => '2007-03-17 01:18:31'
					),
					'Attachment' => array(
						'id' => '2',
						'comment_id' => '7',
						'attachment' => 'newattachment.zip',
						'created' => '2007-03-18 15:02:23',
						'updated' => '2007-03-18 15:04:31'
			))),
			'Tag' => array(
				array(
					'id' => '1',
					'tag' => 'tag1',
					'created' => '2007-03-18 12:22:23',
					'updated' => '2007-03-18 12:24:31'
				),
				array(
					'id' => '3',
					'tag' => 'tag3',
					'created' => '2007-03-18 12:26:23',
					'updated' => '2007-03-18 12:28:31'
		)));

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

		$data = array('Article' => array(
			'user_id' => '1',
			'title' => 'Fourth Article',
			'body' => 'Fourth Article Body',
			'published' => 'Y'
		));
		$result = $TestModel->create() && $TestModel->save($data);
		$this->assertTrue($result);

		// Check record we created

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 4);
		$expected = array('Article' => array(
			'id' => '4',
			'user_id' => '1',
			'title' => 'Fourth Article',
			'body' => 'Fourth Article Body',
			'published' => 'Y'
		));
		$this->assertEqual($result, $expected);

		// Create new record just to overlap Model->id on previously created record

		$data = array('Article' => array(
			'user_id' => '4',
			'title' => 'Fifth Article',
			'body' => 'Fifth Article Body',
			'published' => 'Y'
		));
		$result = $TestModel->create() && $TestModel->save($data);
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 5);
		$expected = array('Article' => array(
			'id' => '5',
			'user_id' => '4',
			'title' => 'Fifth Article',
			'body' => 'Fifth Article Body',
			'published' => 'Y'
		));
		$this->assertEqual($result, $expected);

		// Go back and edit the first article we created, starting by checking it's still there

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 4);
		$expected = array('Article' => array(
			'id' => '4',
			'user_id' => '1',
			'title' => 'Fourth Article',
			'body' => 'Fourth Article Body',
			'published' => 'Y'
		));
		$this->assertEqual($result, $expected);

		// And now do the update with set()

		$data = array('Article' => array(
			'id' => '4',
			'title' => 'Fourth Article - New Title',
			'published' => 'N'
		));
		$result = $TestModel->set($data) && $TestModel->save();
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 4);
		$expected = array('Article' => array(
			'id' => '4',
			'user_id' => '1',
			'title' => 'Fourth Article - New Title',
			'body' => 'Fourth Article Body',
			'published' => 'N'
		));
		$this->assertEqual($result, $expected);

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 5);
		$expected = array('Article' => array(
			'id' => '5',
			'user_id' => '4',
			'title' => 'Fifth Article',
			'body' => 'Fifth Article Body',
			'published' => 'Y'
		));
		$this->assertEqual($result, $expected);

		$data = array('Article' => array('id' => '5', 'title' => 'Fifth Article - New Title 5'));
		$result = ($TestModel->set($data) && $TestModel->save());
		$this->assertTrue($result);

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 5);
		$expected = array('Article' => array(
			'id' => '5',
			'user_id' => '4',
			'title' => 'Fifth Article - New Title 5',
			'body' => 'Fifth Article Body',
			'published' => 'Y'
		));
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
			'Article' => array(
				'id' => '2',
				'user_id' => '3',
				'title' => 'Second Article',
				'body' => 'Second Article Body',
				'published' => 'Y',
				'created' => '2007-03-18 10:41:23',
				'updated' => '2007-03-18 10:43:31'
			),
			'User' => array(
				'id' => '3',
				'user' => 'larry',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				'created' => '2007-03-17 01:20:23',
				'updated' => '2007-03-17 01:22:31'
			),
			'Comment' => array(
				array(
					'id' => '5',
					'article_id' => '2',
					'user_id' => '1',
					'comment' => 'First Comment for Second Article',
					'published' => 'Y',
					'created' => '2007-03-18 10:53:23',
					'updated' => '2007-03-18 10:55:31'
				),
				array(
					'id' => '6',
					'article_id' => '2',
					'user_id' => '2',
					'comment' => 'Second Comment for Second Article',
					'published' => 'Y',
					'created' => '2007-03-18 10:55:23',
					'updated' => '2007-03-18 10:57:31'
			)),
			'Tag' => array(
				array(
					'id' => '1',
					'tag' => 'tag1',
					'created' => '2007-03-18 12:22:23',
					'updated' => '2007-03-18 12:24:31'
				),
				array(
					'id' => '3',
					'tag' => 'tag3',
					'created' => '2007-03-18 12:26:23',
					'updated' => '2007-03-18 12:28:31'
				)
			)
		);
		$this->assertEqual($result, $expected);

		$data = array(
			'Article' => array(
				'id' => '2',
				'title' => 'New Second Article'
			),
			'Tag' => array('Tag' => array(1, 2))
		);

		$this->assertTrue($TestModel->set($data));
		$this->assertTrue($TestModel->save());

		$TestModel->unbindModel(array('belongsTo' => array('User'), 'hasMany' => array('Comment')));
		$result = $TestModel->find(array('Article.id' => 2), array('id', 'user_id', 'title', 'body'));
		$expected = array(
			'Article' => array(
				'id' => '2',
				'user_id' => '3',
				'title' => 'New Second Article',
				'body' => 'Second Article Body'
			),
			'Tag' => array(
				array(
					'id' => '1',
					'tag' => 'tag1',
					'created' => '2007-03-18 12:22:23',
					'updated' => '2007-03-18 12:24:31'
				),
				array(
					'id' => '2',
					'tag' => 'tag2',
					'created' => '2007-03-18 12:24:23',
					'updated' => '2007-03-18 12:26:31'
		)));
		$this->assertEqual($result, $expected);

		$data = array('Article' => array('id' => '2'), 'Tag' => array('Tag' => array(2, 3)));
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
				'id' => '2',
				'user_id' => '3',
				'title' => 'New Second Article',
				'body' => 'Second Article Body'
			),
			'Tag' => array(
				array(
					'id' => '2',
					'tag' => 'tag2',
					'created' => '2007-03-18 12:24:23',
					'updated' => '2007-03-18 12:26:31'
				),
				array(
					'id' => '3',
					'tag' => 'tag3',
					'created' => '2007-03-18 12:26:23',
					'updated' => '2007-03-18 12:28:31'
		)));
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
			'Article' => array(
				'id' => '2',
				'user_id' => '3',
				'title' => 'New Second Article',
				'body' => 'Second Article Body'
			),
			'Tag' => array(
				array(
					'id' => '1',
					'tag' => 'tag1',
					'created' => '2007-03-18 12:22:23',
					'updated' => '2007-03-18 12:24:31'
				),
				array(
					'id' => '2',
					'tag' => 'tag2',
					'created' => '2007-03-18 12:24:23',
					'updated' => '2007-03-18 12:26:31'
				),
				array(
					'id' => '3',
					'tag' => 'tag3',
					'created' => '2007-03-18 12:26:23',
					'updated' => '2007-03-18 12:28:31'
		)));
		$this->assertEqual($result, $expected);

		$data = array('Tag' => array('Tag' => array()));
		$result = $TestModel->set($data);
		$this->assertTrue($result);

		$result = $TestModel->save();
		$this->assertTrue($result);

		$data = array('Tag' => array('Tag' => ''));
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
				'id' => '2',
				'user_id' => '3',
				'title' => 'New Second Article',
				'body' => 'Second Article Body'
			),
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
				'id' => '2',
				'user_id' => '3',
				'title' => 'New Second Article',
				'body' => 'Second Article Body'
			),
			'Tag' => array(
				array(
					'id' => '2',
					'tag' => 'tag2',
					'created' => '2007-03-18 12:24:23',
					'updated' => '2007-03-18 12:26:31'
				),
				array(
					'id' => '3',
					'tag' => 'tag3',
					'created' => '2007-03-18 12:26:23',
					'updated' => '2007-03-18 12:28:31'
		)));
		$this->assertEqual($result, $expected);

		$data = array(
			'Tag' => array(
				'Tag' => array(1, 2)
			),
			'Article' => array(
				'id' => '2',
				'title' => 'New Second Article'
		));
		$this->assertTrue($TestModel->set($data));
		$this->assertTrue($TestModel->save());

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $TestModel->find(array('Article.id'=>2), array('id', 'user_id', 'title', 'body'));
		$expected = array(
			'Article' => array(
				'id' => '2',
				'user_id' => '3',
				'title' => 'New Second Article',
				'body' => 'Second Article Body'
			),
			'Tag' => array(
				array(
					'id' => '1',
					'tag' => 'tag1',
					'created' => '2007-03-18 12:22:23',
					'updated' => '2007-03-18 12:24:31'
				),
				array(
					'id' => '2',
					'tag' => 'tag2',
					'created' => '2007-03-18 12:24:23',
					'updated' => '2007-03-18 12:26:31'
		)));
		$this->assertEqual($result, $expected);

		$data = array(
			'Tag' => array(
				'Tag' => array(1, 2)
			),
			'Article' => array(
				'id' => '2',
				'title' => 'New Second Article Title'
		));
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
				'id' => '2',
				'user_id' => '3',
				'title' => 'New Second Article Title',
				'body' => 'Second Article Body'
			),
			'Tag' => array(
				array(
					'id' => '1',
					'tag' => 'tag1',
					'created' => '2007-03-18 12:22:23',
					'updated' => '2007-03-18 12:24:31'
				),
				array(
					'id' => '2',
					'tag' => 'tag2',
					'created' => '2007-03-18 12:24:23',
					'updated' => '2007-03-18 12:26:31'
				)
			)
		);
		$this->assertEqual($result, $expected);

		$data = array(
			'Tag' => array(
				'Tag' => array(2, 3)
			),
			'Article' => array(
				'id' => '2',
				'title' => 'Changed Second Article'
		));
		$this->assertTrue($TestModel->set($data));
		$this->assertTrue($TestModel->save());

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $TestModel->find(array('Article.id'=>2), array('id', 'user_id', 'title', 'body'));
		$expected = array(
			'Article' => array(
				'id' => '2',
				'user_id' => '3',
				'title' => 'Changed Second Article',
				'body' => 'Second Article Body'
			),
			'Tag' => array(
				array(
					'id' => '2',
					'tag' => 'tag2',
					'created' => '2007-03-18 12:24:23',
					'updated' => '2007-03-18 12:26:31'
				),
				array(
					'id' => '3',
					'tag' => 'tag3',
					'created' => '2007-03-18 12:26:23',
					'updated' => '2007-03-18 12:28:31'
				)
			)
		);
		$this->assertEqual($result, $expected);

		$data = array(
			'Tag' => array(
				'Tag' => array(1, 3)
			),
			'Article' => array('id' => '2'),
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
				'id' => '2',
				'user_id' => '3',
				'title' => 'Changed Second Article',
				'body' => 'Second Article Body'
			),
			'Tag' => array(
				array(
					'id' => '1',
					'tag' => 'tag1',
					'created' => '2007-03-18 12:22:23',
					'updated' => '2007-03-18 12:24:31'
				),
				array(
					'id' => '3',
					'tag' => 'tag3',
					'created' => '2007-03-18 12:26:23',
					'updated' => '2007-03-18 12:28:31'
		)));
		$this->assertEqual($result, $expected);

		$data = array(
			'Article' => array(
				'id' => 10,
				'user_id' => '2',
				'title' => 'New Article With Tags and fieldList',
				'body' => 'New Article Body with Tags and fieldList',
				'created' => '2007-03-18 14:55:23',
				'updated' => '2007-03-18 14:57:31'
			),
			'Tag' => array(
				'Tag' => array(1, 2, 3)
		));
		$result =  $TestModel->create()
				&& $TestModel->save($data, true, array('user_id', 'title', 'published'));
		$this->assertTrue($result);

		$TestModel->unbindModel(array('belongsTo' => array('User'), 'hasMany' => array('Comment')));
		$result = $TestModel->read();
		$expected = array(
			'Article' => array(
				'id' => 4,
				'user_id' => 2,
				'title' => 'New Article With Tags and fieldList',
				'body' => '',
				'published' => 'N',
				'created' => '',
				'updated' => ''
			),
			'Tag' => array(
				0 => array(
					'id' => 1,
					'tag' => 'tag1',
					'created' => '2007-03-18 12:22:23',
					'updated' => '2007-03-18 12:24:31'
				),
				1 => array(
					'id' => 2,
					'tag' => 'tag2',
					'created' => '2007-03-18 12:24:23',
					'updated' => '2007-03-18 12:26:31'
				),
				2 => array(
					'id' => 3,
					'tag' => 'tag3',
					'created' => '2007-03-18 12:26:23',
					'updated' => '2007-03-18 12:28:31'
		)));
		$this->assertEqual($result, $expected);


		$this->loadFixtures('JoinA', 'JoinC', 'JoinAC', 'JoinB', 'JoinAB');
		$TestModel = new JoinA();
		$TestModel->hasBelongsToMany['JoinC']['unique'] = true;
		$data = array(
			'JoinA' => array(
				'id' => 1,
				'name' => 'Join A 1',
				'body' => 'Join A 1 Body',
			),
			'JoinC' => array(
				'JoinC' => array(
					array('join_c_id' => 2, 'other' => 'new record'),
					array('join_c_id' => 3, 'other' => 'new record')
				)
			)
		);
		$TestModel->save($data);
		$result = $TestModel->read(null, 1);
		$time = date('Y-M-D H:i:s');
		$expected = array(4, 5);
		$this->assertEqual(Set::extract('/JoinC/JoinAsJoinC/id', $result), $expected);
		$expected = array('new record', 'new record');
		$this->assertEqual(Set::extract('/JoinC/JoinAsJoinC/other', $result), $expected);
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

		$data = array(
			'Story' => array('story' => '1'),
			'Tag' => array(
				'Tag' => array(2, 3)
		));
		$result = $Story->set($data);
		$this->assertTrue($result);

		$result = $Story->save();
		$this->assertTrue($result);

		$result = $Story->find('all');
		$expected = array(
			array(
				'Story' => array(
					'story' => 1,
					'title' => 'First Story'
				),
				'Tag' => array(
					array(
						'id' => 2,
						'tag' => 'tag2',
						'created' => '2007-03-18 12:24:23',
						'updated' => '2007-03-18 12:26:31'
					),
					array(
						'id' => 3,
						'tag' => 'tag3',
						'created' => '2007-03-18 12:26:23',
						'updated' => '2007-03-18 12:28:31'
			))),
			array(
				'Story' => array(
					'story' => 2,
					'title' => 'Second Story'
				),
				'Tag' => array()
		));
		$this->assertEqual($result, $expected);
	}

/**
 * test that saving habtm records respects conditions set in the the 'conditions' key
 * for the association.
 *
 * @return void
 */
	function testHabtmSaveWithConditionsInAssociation() {
		$this->loadFixtures('JoinThing', 'Something', 'SomethingElse');
		$Something =& new Something();
		$Something->unbindModel(array('hasAndBelongsToMany' => array('SomethingElse')), false);

		$Something->bindModel(array(
			'hasAndBelongsToMany' => array(
				'DoomedSomethingElse' => array(
					'className' => 'SomethingElse',
					'joinTable' => 'join_things',
					'conditions' => 'JoinThing.doomed = 1',
					'unique' => true
				),
				'NotDoomedSomethingElse' => array(
					'className' => 'SomethingElse',
					'joinTable' => 'join_things',
					'conditions' => array('JoinThing.doomed' => 0),
					'unique' => true
				)
			)
		), false);
		$result = $Something->read(null, 1);
		$this->assertTrue(empty($result['NotDoomedSomethingElse']));
		$this->assertEqual(count($result['DoomedSomethingElse']), 1);

		$data = array(
			'Something' => array('id' => 1),
			'NotDoomedSomethingElse' => array(
				'NotDoomedSomethingElse' => array(
					array('something_else_id' => 2, 'doomed' => 0),
					array('something_else_id' => 3, 'doomed' => 0)
				)
			)
		);
		$Something->create($data);
		$result = $Something->save();
		$this->assertTrue($result);

		$result = $Something->read(null, 1);
		$this->assertEqual(count($result['NotDoomedSomethingElse']), 2);
		$this->assertEqual(count($result['DoomedSomethingElse']), 1);
	}
/**
 * testHabtmSaveKeyResolution method
 *
 * @access public
 * @return void
 */
	function testHabtmSaveKeyResolution() {
		$this->loadFixtures('Apple', 'Device', 'ThePaperMonkies');
		$ThePaper =& new ThePaper();

		$ThePaper->id = 1;
		$ThePaper->save(array('Monkey' => array(2, 3)));

		$result = $ThePaper->findById(1);
		$expected = array(
			array(
				'id' => '2',
				'device_type_id' => '1',
				'name' => 'Device 2',
				'typ' => '1'
			),
			array(
				'id' => '3',
				'device_type_id' => '1',
				'name' => 'Device 3',
				'typ' => '2'
		));
		$this->assertEqual($result['Monkey'], $expected);

		$ThePaper->id = 2;
		$ThePaper->save(array('Monkey' => array(1, 2, 3)));

		$result = $ThePaper->findById(2);
		$expected = array(
			array(
				'id' => '1',
				'device_type_id' => '1',
				'name' => 'Device 1',
				'typ' => '1'
			),
			array(
				'id' => '2',
				'device_type_id' => '1',
				'name' => 'Device 2',
				'typ' => '1'
			),
			array(
				'id' => '3',
				'device_type_id' => '1',
				'name' => 'Device 3',
				'typ' => '2'
		));
		$this->assertEqual($result['Monkey'], $expected);

		$ThePaper->id = 2;
		$ThePaper->save(array('Monkey' => array(1, 3)));

		$result = $ThePaper->findById(2);
		$expected = array(
			array(
				'id' => '1',
				'device_type_id' => '1',
				'name' => 'Device 1',
				'typ' => '1'
			),
			array(
				'id' => '3',
				'device_type_id' => '1',
				'name' => 'Device 3',
				'typ' => '2'
			));
		$this->assertEqual($result['Monkey'], $expected);

		$result = $ThePaper->findById(1);
		$expected = array(
			array(
				'id' => '2',
				'device_type_id' => '1',
				'name' => 'Device 2',
				'typ' => '1'
			),
			array(
				'id' => '3',
				'device_type_id' => '1',
				'name' => 'Device 3',
				'typ' => '2'
		));
		$this->assertEqual($result['Monkey'], $expected);
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
		$data = array(
			'id' => 5,
			'user_id' => 2,
			'title' => 'My article',
			'body' => 'Some text'
		);

		$result = $TestModel->create($data);
		$expected = array(
			'Article' => array(
				'published' => 'N',
				'id' => 5,
				'user_id' => 2,
				'title' => 'My article',
				'body' => 'Some text'
		));

		$this->assertEqual($result, $expected);
		$this->assertEqual($TestModel->id, 5);

		$result = $TestModel->create($data, true);
		$expected = array(
			'Article' => array(
				'published' => 'N',
				'id' => false,
				'user_id' => 2,
				'title' => 'My article',
				'body' => 'Some text'
		));

		$this->assertEqual($result, $expected);
		$this->assertFalse($TestModel->id);

		$result = $TestModel->create(array('Article' => $data), true);
		$expected = array(
			'Article' => array(
				'published' => 'N',
				'id' => false,
				'user_id' => 2,
				'title' => 'My article',
				'body' => 'Some text'
		));

		$this->assertEqual($result, $expected);
		$this->assertFalse($TestModel->id);

		$data = array(
			'id' => 6,
			'user_id' => 2,
			'title' => 'My article',
			'body' => 'Some text',
			'created' => '1970-01-01 00:00:00',
			'updated' => '1970-01-01 12:00:00',
			'modified' => '1970-01-01 12:00:00'
		);

		$result = $TestModel->create($data);
		$expected = array(
			'Article' => array(
				'published' => 'N',
				'id' => 6,
				'user_id' => 2,
				'title' => 'My article',
				'body' => 'Some text',
				'created' => '1970-01-01 00:00:00',
				'updated' => '1970-01-01 12:00:00',
				'modified' => '1970-01-01 12:00:00'
		));
		$this->assertEqual($result, $expected);
		$this->assertEqual($TestModel->id, 6);

		$result = $TestModel->create(array(
			'Article' => array_diff_key($data, array(
				'created' => true,
				'updated' => true,
				'modified' => true
		))), true);
		$expected = array(
			'Article' => array(
				'published' => 'N',
				'id' => false,
				'user_id' => 2,
				'title' => 'My article',
				'body' => 'Some text'
		));
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

		$articles = $Article->find('all', array(
			'fields' => array('id','title'),
			'recursive' => -1
		));

		$comments = $Comment->find('all', array(
			'fields' => array('id','article_id','user_id','comment','published'), 'recursive' => -1));

		$this->assertEqual($articles, array(
			array('Article' => array(
				'id' => 1,
				'title' => 'First Article'
			)),
			array('Article' => array(
				'id' => 2,
				'title' => 'Second Article'
			)),
			array('Article' => array(
				'id' => 3,
				'title' => 'Third Article'
		))));

		$this->assertEqual($comments, array(
			array('Comment' => array(
				'id' => 1,
				'article_id' => 1,
				'user_id' => 2,
				'comment' => 'First Comment for First Article',
				'published' => 'Y'
			)),
			array('Comment' => array(
				'id' => 2,
				'article_id' => 1,
				'user_id' => 4,
				'comment' => 'Second Comment for First Article',
				'published' => 'Y'
			)),
			array('Comment' => array(
				'id' => 3,
				'article_id' => 1,
				'user_id' => 1,
				'comment' => 'Third Comment for First Article',
				'published' => 'Y'
			)),
			array('Comment' => array(
				'id' => 4,
				'article_id' => 1,
				'user_id' => 1,
				'comment' => 'Fourth Comment for First Article',
				'published' => 'N'
			)),
			array('Comment' => array(
				'id' => 5,
				'article_id' => 2,
				'user_id' => 1,
				'comment' => 'First Comment for Second Article',
				'published' => 'Y'
			)),
			array('Comment' => array(
				'id' => 6,
				'article_id' => 2,
				'user_id' => 2,
				'comment' => 'Second Comment for Second Article',
				'published' => 'Y'
		))));

		$data = array(
			'Comment' => array(
				'article_id' => 2,
				'user_id' => 4,
				'comment' => 'Brand New Comment',
				'published' => 'N'
			),
			'Article' => array(
				'id' => 2,
				'title' => 'Second Article Modified'
		));

		$result = $Comment->create($data);

		$this->assertTrue($result);
		$result = $Comment->save();
		$this->assertTrue($result);

		$articles = $Article->find('all', array(
			'fields' => array('id','title'),
			'recursive' => -1
		));

		$comments = $Comment->find('all', array(
			'fields' => array('id','article_id','user_id','comment','published'),
			'recursive' => -1
		));

		$this->assertEqual($articles, array(
			array('Article' => array(
				'id' => 1,
				'title' => 'First Article'
			)),
			array('Article' => array(
				'id' => 2,
				'title' => 'Second Article'
			)),
			array('Article' => array(
				'id' => 3,
				'title' => 'Third Article'
		))));

		$this->assertEqual($comments, array(
			array('Comment' => array(
				'id' => 1,
				'article_id' => 1,
				'user_id' => 2,
				'comment' => 'First Comment for First Article',
				'published' => 'Y'
			)),
			array('Comment' => array(
				'id' => 2,
				'article_id' => 1,
				'user_id' => 4,
				'comment' => 'Second Comment for First Article',
				'published' => 'Y'
			)),
			array('Comment' => array(
				'id' => 3,
				'article_id' => 1,
				'user_id' => 1,
				'comment' => 'Third Comment for First Article',
				'published' => 'Y'
			)),
			array('Comment' => array(
				'id' => 4,
				'article_id' => 1,
				'user_id' => 1,
				'comment' => 'Fourth Comment for First Article',
				'published' => 'N'
			)),
			array('Comment' => array(
				'id' => 5,
				'article_id' => 2,
				'user_id' => 1,
				'comment' => 'First Comment for Second Article',
				'published' => 'Y'
			)),
			array('Comment' => array(
				'id' => 6,
				'article_id' => 2,
				'user_id' => 2, 'comment' =>
				'Second Comment for Second Article',
				'published' => 'Y'
			)),
			array('Comment' => array(
				'id' => 7,
				'article_id' => 2,
				'user_id' => 4,
				'comment' => 'Brand New Comment',
				'published' => 'N'
	))));

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

		$data = array(
			'Article' => array(
				'user_id' => 2,
				'title' => 'Brand New Article',
				'body' => 'Brand New Article Body',
				'published' => 'Y'
			),
			'SecondaryArticle' => array(
				'id' => 1
		));

		$Article->create();
		$result = $Article->save($data);
		$this->assertTrue($result);

		$result = $Article->getInsertID();
		$this->assertTrue(!empty($result));

		$result = $Article->field('title', array('id' => 1));
		$this->assertEqual($result, 'First Article');

		$articles = $Article->find('all', array(
			'fields' => array('id','title'),
			'recursive' => -1
		));

		$this->assertEqual($articles, array(
			array('Article' => array(
				'id' => 1,
				'title' => 'First Article'
			)),
			array('Article' => array(
				'id' => 2,
				'title' => 'Second Article'
			)),
			array('Article' => array(
				'id' => 3,
				'title' => 'Third Article'
			)),
			array('Article' => array(
				'id' => 4,
				'title' => 'Brand New Article'
		))));
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

		$data = array(
			'PrimaryModel' => array(
				'primary_name' => 'Primary Name New'
			),
			'SecondaryModel' => array(
				'id' => array(1)
		));

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
		$this->expectError();
		ob_start();
		$this->assertFalse($TestModel->exists());
		$output = ob_get_clean();
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

		$TestModel->save(array(
			'User' => array(
				'user' => 'some user',
				'password' => 'some password'
		)));
		$this->assertTrue(is_int($TestModel->id) || (intval($TestModel->id) === 5));
		$id = $TestModel->id;

		$TestModel->save(array(
			'User' => array(
				'user' => 'updated user'
		)));
		$this->assertEqual($TestModel->id, $id);

		$result = $TestModel->findById($id);
		$this->assertEqual($result['User']['user'], 'updated user');
		$this->assertEqual($result['User']['password'], 'some password');

		$Article =& new Article();
		$Comment =& new Comment();
		$data = array(
			'Comment' => array(
				'id' => 1,
				'comment' => 'First Comment for First Article'
			),
			'Article' => array(
				'id' => 2,
				'title' => 'Second Article'
		));

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

		$result = $TestModel->updateAll(
			array('Comment.comment' => "'Updated today'"),
			array('Comment.user_id' => 5)
		);
		$this->assertTrue($result);
		$result = Set::extract(
			$TestModel->find('all', array(
				'conditions' => array(
					'Comment.user_id' => 5
			))),
			'{n}.Comment.comment'
		);
		$expected = array_fill(0, 2, 'Updated today');
		$this->assertEqual($result, $expected);
	}

/**
 * testHabtmUuidWithUuidId method
 *
 * @access public
 * @return void
 */
	function testHabtmUuidWithUuidId() {
		$this->loadFixtures('Uuidportfolio', 'Uuiditem', 'UuiditemsUuidportfolio');
		$TestModel =& new Uuidportfolio();

		$data = array('Uuidportfolio' => array('name' => 'Portfolio 3'));
		$data['Uuiditem']['Uuiditem'] = array('483798c8-c7cc-430e-8cf9-4fcc40cf8569');
		$TestModel->create($data);
		$TestModel->save();
		$id = $TestModel->id;
		$result = $TestModel->read(null, $id);
		$this->assertEqual(1, count($result['Uuiditem']));
		$this->assertEqual(strlen($result['Uuiditem'][0]['UuiditemsUuidportfolio']['id']), 36);
	}

/**
 * test HABTM saving when join table has no primary key and only 2 columns.
 *
 * @return void
 */
	function testHabtmSavingWithNoPrimaryKeyUuidJoinTable() {
		$this->loadFixtures('UuidTag', 'Fruit', 'FruitsUuidTag');
		$Fruit =& new Fruit();
		$data = array(
			'Fruit' => array(
				'color' => 'Red',
				'shape' => 'Heart-shaped',
				'taste' => 'sweet',
				'name' => 'Strawberry',
			),
			'UuidTag' => array(
				'UuidTag' => array(
					'481fc6d0-b920-43e0-e50f-6d1740cf8569'
				)
			)
		);
		$this->assertTrue($Fruit->save($data));
	}

/**
 * test HABTM saving when join table has no primary key and only 2 columns, no with model is used.
 *
 * @return void
 */
	function testHabtmSavingWithNoPrimaryKeyUuidJoinTableNoWith() {
		$this->loadFixtures('UuidTag', 'Fruit', 'FruitsUuidTag');
		$Fruit =& new FruitNoWith();
		$data = array(
			'Fruit' => array(
				'color' => 'Red',
				'shape' => 'Heart-shaped',
				'taste' => 'sweet',
				'name' => 'Strawberry',
			),
			'UuidTag' => array(
				'UuidTag' => array(
					'481fc6d0-b920-43e0-e50f-6d1740cf8569'
				)
			)
		);
		$this->assertTrue($Fruit->save($data));
	}

/**
 * testHabtmUuidWithNumericId method
 *
 * @access public
 * @return void
 */
	function testHabtmUuidWithNumericId() {
		$this->loadFixtures('Uuidportfolio', 'Uuiditem', 'UuiditemsUuidportfolioNumericid');
		$TestModel =& new Uuiditem();

		$data = array('Uuiditem' => array('name' => 'Item 7', 'published' => 0));
		$data['Uuidportfolio']['Uuidportfolio'] = array('480af662-eb8c-47d3-886b-230540cf8569');
		$TestModel->create($data);
		$TestModel->save();
		$id = $TestModel->id;
		$result = $TestModel->read(null, $id);
		$this->assertEqual(1, count($result['Uuidportfolio']));
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
			'JoinA' => array(
				'id' => 1,
				'name' => 'Join A 1',
				'body' => 'Join A 1 Body',
				'created' => '2008-01-03 10:54:23',
				'updated' => '2008-01-03 10:54:23'
			),
			'JoinB' => array(
				0 => array(
					'id' => 2,
					'name' => 'Join B 2',
					'created' => '2008-01-03 10:55:02',
					'updated' => '2008-01-03 10:55:02',
					'JoinAsJoinB' => array(
						'id' => 1,
						'join_a_id' => 1,
						'join_b_id' => 2,
						'other' => 'Data for Join A 1 Join B 2',
						'created' => '2008-01-03 10:56:33',
						'updated' => '2008-01-03 10:56:33'
			))),
			'JoinC' => array(
				0 => array(
					'id' => 2,
					'name' => 'Join C 2',
					'created' => '2008-01-03 10:56:12',
					'updated' => '2008-01-03 10:56:12',
					'JoinAsJoinC' => array(
						'id' => 1,
						'join_a_id' => 1,
						'join_c_id' => 2,
						'other' => 'Data for Join A 1 Join C 2',
						'created' => '2008-01-03 10:57:22',
						'updated' => '2008-01-03 10:57:22'
		))));

		$this->assertEqual($result, $expected);

		$ts = date('Y-m-d H:i:s');
		$TestModel->id = 1;
		$data = array(
			'JoinA' => array(
				'id' => '1',
				'name' => 'New name for Join A 1',
				'updated' => $ts
			),
			'JoinB' => array(
				array(
					'id' => 1,
					'join_b_id' => 2,
					'other' => 'New data for Join A 1 Join B 2',
					'created' => $ts,
					'updated' => $ts
			)),
			'JoinC' => array(
				array(
					'id' => 1,
					'join_c_id' => 2,
					'other' => 'New data for Join A 1 Join C 2',
					'created' => $ts,
					'updated' => $ts
		)));

		$TestModel->set($data);
		$TestModel->save();

		$result = $TestModel->findById(1);
		$expected = array(
			'JoinA' => array(
				'id' => 1,
				'name' => 'New name for Join A 1',
				'body' => 'Join A 1 Body',
				'created' => '2008-01-03 10:54:23',
				'updated' => $ts
			),
			'JoinB' => array(
				0 => array(
					'id' => 2,
					'name' => 'Join B 2',
					'created' => '2008-01-03 10:55:02',
					'updated' => '2008-01-03 10:55:02',
					'JoinAsJoinB' => array(
						'id' => 1,
						'join_a_id' => 1,
						'join_b_id' => 2,
						'other' => 'New data for Join A 1 Join B 2',
						'created' => $ts,
						'updated' => $ts
			))),
			'JoinC' => array(
				0 => array(
					'id' => 2,
					'name' => 'Join C 2',
					'created' => '2008-01-03 10:56:12',
					'updated' => '2008-01-03 10:56:12',
					'JoinAsJoinC' => array(
						'id' => 1,
						'join_a_id' => 1,
						'join_c_id' => 2,
						'other' => 'New data for Join A 1 Join C 2',
						'created' => $ts,
						'updated' => $ts
		))));

		$this->assertEqual($result, $expected);
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
			'Post' => array(
				'title' => 'Post with Author',
				'body' => 'This post will be saved with an author'
			),
			'Author' => array(
				'user' => 'bob',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf90'
		)));

		$result = $TestModel->find('all');
		$expected = array(
			'Post' => array(
				'id' => '4',
				'author_id' => '5',
				'title' => 'Post with Author',
				'body' => 'This post will be saved with an author',
				'published' => 'N',
				'created' => $ts,
				'updated' => $ts
			),
			'Author' => array(
				'id' => '5',
				'user' => 'bob',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf90',
				'created' => $ts,
				'updated' => $ts,
				'test' => 'working'
		));
		$this->assertEqual($result[3], $expected);
		$this->assertEqual(count($result), 4);

		$TestModel->deleteAll(true);
		$this->assertEqual($TestModel->find('all'), array());

		// SQLite seems to reset the PK counter when that happens, so we need this to make the tests pass
		$this->db->truncate($TestModel);

		$ts = date('Y-m-d H:i:s');
		$TestModel->saveAll(array(
			array(
				'title' => 'Multi-record post 1',
				'body' => 'First multi-record post',
				'author_id' => 2
			),
			array(
				'title' => 'Multi-record post 2',
				'body' => 'Second multi-record post',
				'author_id' => 2
		)));

		$result = $TestModel->find('all', array(
			'recursive' => -1,
			'order' => 'Post.id ASC'
		));
		$expected = array(
			array(
				'Post' => array(
					'id' => '1',
					'author_id' => '2',
					'title' => 'Multi-record post 1',
					'body' => 'First multi-record post',
					'published' => 'N',
					'created' => $ts,
					'updated' => $ts
			)),
			array(
				'Post' => array(
					'id' => '2',
					'author_id' => '2',
					'title' => 'Multi-record post 2',
					'body' => 'Second multi-record post',
					'published' => 'N',
					'created' => $ts,
					'updated' => $ts
		)));
		$this->assertEqual($result, $expected);

		$TestModel =& new Comment();
		$ts = date('Y-m-d H:i:s');
		$result = $TestModel->saveAll(array(
			'Comment' => array(
				'article_id' => 2,
				'user_id' => 2,
				'comment' => 'New comment with attachment',
				'published' => 'Y'
			),
			'Attachment' => array(
				'attachment' => 'some_file.tgz'
			)));
		$this->assertTrue($result);

		$result = $TestModel->find('all');
		$expected = array(
			'id' => '7',
			'article_id' => '2',
			'user_id' => '2',
			'comment' => 'New comment with attachment',
			'published' => 'Y',
			'created' => $ts,
			'updated' => $ts
		);
		$this->assertEqual($result[6]['Comment'], $expected);

		$expected = array(
			'id' => '7',
			'article_id' => '2',
			'user_id' => '2',
			'comment' => 'New comment with attachment',
			'published' => 'Y',
			'created' => $ts,
			'updated' => $ts
		);
		$this->assertEqual($result[6]['Comment'], $expected);

		$expected = array(
			'id' => '2',
			'comment_id' => '7',
			'attachment' => 'some_file.tgz',
			'created' => $ts,
			'updated' => $ts
		);
		$this->assertEqual($result[6]['Attachment'], $expected);
	}

/**
 * Test SaveAll with Habtm relations
 *
 * @access public
 * @return void
 */
	function testSaveAllHabtm() {
		$this->loadFixtures('Article', 'Tag', 'Comment', 'User');
		$data = array(
			'Article' => array(
				'user_id' => 1,
				'title' => 'Article Has and belongs to Many Tags'
			),
			'Tag' => array(
				'Tag' => array(1, 2)
			),
			'Comment' => array(
				array(
					'comment' => 'Article comment',
					'user_id' => 1
		)));
		$Article =& new Article();
		$result = $Article->saveAll($data);
		$this->assertTrue($result);

		$result = $Article->read();
		$this->assertEqual(count($result['Tag']), 2);
		$this->assertEqual($result['Tag'][0]['tag'], 'tag1');
		$this->assertEqual(count($result['Comment']), 1);
		$this->assertEqual(count($result['Comment'][0]['comment']['Article comment']), 1);
	}

/**
 * Test SaveAll with Habtm relations and extra join table fields
 *
 * @access public
 * @return void
 */
	function testSaveAllHabtmWithExtraJoinTableFields() {
		$this->loadFixtures('Something', 'SomethingElse', 'JoinThing');

		$data = array(
			'Something' => array(
				'id' => 4,
				'title' => 'Extra Fields',
				'body' => 'Extra Fields Body',
				'published' => '1'
			),
			'SomethingElse' => array(
				array('something_else_id' => 1, 'doomed' => '1'),
				array('something_else_id' => 2, 'doomed' => '0'),
				array('something_else_id' => 3, 'doomed' => '1')
			)
		);

		$Something =& new Something();
		$result = $Something->saveAll($data);
		$this->assertTrue($result);
		$result = $Something->read();

		$this->assertEqual(count($result['SomethingElse']), 3);
		$this->assertTrue(Set::matches('/Something[id=4]', $result));

		$this->assertTrue(Set::matches('/SomethingElse[id=1]', $result));
		$this->assertTrue(Set::matches('/SomethingElse[id=1]/JoinThing[something_else_id=1]', $result));
		$this->assertTrue(Set::matches('/SomethingElse[id=1]/JoinThing[doomed=1]', $result));

		$this->assertTrue(Set::matches('/SomethingElse[id=2]', $result));
		$this->assertTrue(Set::matches('/SomethingElse[id=2]/JoinThing[something_else_id=2]', $result));
		$this->assertTrue(Set::matches('/SomethingElse[id=2]/JoinThing[doomed=0]', $result));

		$this->assertTrue(Set::matches('/SomethingElse[id=3]', $result));
		$this->assertTrue(Set::matches('/SomethingElse[id=3]/JoinThing[something_else_id=3]', $result));
		$this->assertTrue(Set::matches('/SomethingElse[id=3]/JoinThing[doomed=1]', $result));
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
			'Comment' => array(
				'comment' => 'Comment with attachment',
				'article_id' => 1,
				'user_id' => 1
			),
			'Attachment' => array(
				'attachment' => 'some_file.zip'
		))));
		$result = $model->find('all', array('fields' => array(
			'Comment.id', 'Comment.comment', 'Attachment.id',
			'Attachment.comment_id', 'Attachment.attachment'
		)));
		$expected = array(array(
			'Comment' => array(
				'id' => '1',
				'comment' => 'Comment with attachment'
			),
			'Attachment' => array(
				'id' => '1',
				'comment_id' => '1',
				'attachment' => 'some_file.zip'
		)));
		$this->assertEqual($result, $expected);


		$model->Attachment->bindModel(array('belongsTo' => array('Comment')), false);
		$data = array(
			'Comment' => array(
				'comment' => 'Comment with attachment',
				'article_id' => 1,
				'user_id' => 1
			),
			'Attachment' => array(
				'attachment' => 'some_file.zip'
		));
		$this->assertTrue($model->saveAll($data, array('validate' => 'first')));
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
			'Comment' => array(
				'comment' => 'Article comment',
				'article_id' => 1,
				'user_id' => 1
			),
			'Article' => array(
				'title' => 'Model Associations 101',
				'user_id' => 1
		))));
		$result = $model->find('all', array('fields' => array(
			'Comment.id', 'Comment.comment', 'Comment.article_id', 'Article.id', 'Article.title'
		)));
		$expected = array(array(
			'Comment' => array(
				'id' => '1',
				'article_id' => '1',
				'comment' => 'Article comment'
			),
			'Article' => array(
				'id' => '1',
				'title' => 'Model Associations 101'
		)));
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

		$model->validate = array('comment' => 'notEmpty');
		$model->Attachment->validate = array('attachment' => 'notEmpty');
		$model->Attachment->bindModel(array('belongsTo' => array('Comment')));

		$this->assertFalse($model->saveAll(
			array(
				'Comment' => array(
					'comment' => '',
					'article_id' => 1,
					'user_id' => 1
				),
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
			'Article' => array(
				'title' => 'Post with Author',
				'body' => 'This post will be saved with an author',
				'user_id' => 2
			),
			'Comment' => array(
				array('comment' => 'First new comment', 'user_id' => 2))
		), array('atomic' => false));

		$this->assertIdentical($result, array('Article' => true, 'Comment' => array(true)));

		$result = $TestModel->saveAll(array(
			array(
				'id' => '1',
				'title' => 'Baleeted First Post',
				'body' => 'Baleeted!',
				'published' => 'N'
			),
			array(
				'id' => '2',
				'title' => 'Just update the title'
			),
			array(
				'title' => 'Creating a fourth post',
				'body' => 'Fourth post body',
				'user_id' => 2
			)
		), array('atomic' => false));
		$this->assertIdentical($result, array(true, true, true));

		$TestModel->validate = array('title' => 'notEmpty', 'author_id' => 'numeric');
		$result = $TestModel->saveAll(array(
			array(
				'id' => '1',
				'title' => 'Un-Baleeted First Post',
				'body' => 'Not Baleeted!',
				'published' => 'Y'
			),
			array(
				'id' => '2',
				'title' => '',
				'body' => 'Trying to get away with an empty title'
			)
		), array('atomic' => false));
		$this->assertIdentical($result, array(true, false));

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array(
					'comment' => 'First new comment',
					'published' => 'Y',
					'user_id' => 1
				),
				array(
					'comment' => 'Second new comment',
					'published' => 'Y',
					'user_id' => 2
			))
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
		$expected = array(
			'First Comment for Second Article',
			'Second Comment for Second Article',
			'First new comment',
			'Second new comment'
		);
		$this->assertEqual(Set::extract($result['Comment'], '{n}.comment'), $expected);

		$result = $TestModel->saveAll(
			array(
				'Article' => array('id' => 2),
				'Comment' => array(
					array(
						'comment' => 'Third new comment',
						'published' => 'Y',
						'user_id' => 1
			))),
			array('atomic' => false)
		);
		$this->assertTrue($result);

		$result = $TestModel->findById(2);
		$expected = array(
			'First Comment for Second Article',
			'Second Comment for Second Article',
			'First new comment',
			'Second new comment',
			'Third new comment'
		);
		$this->assertEqual(Set::extract($result['Comment'], '{n}.comment'), $expected);

		$TestModel->beforeSaveReturn = false;
		$result = $TestModel->saveAll(
			array(
				'Article' => array('id' => 2),
				'Comment' => array(
					array(
						'comment' => 'Fourth new comment',
						'published' => 'Y',
						'user_id' => 1
			))),
			array('atomic' => false)
		);
		$this->assertEqual($result, array('Article' => false));

		$result = $TestModel->findById(2);
		$expected = array(
			'First Comment for Second Article',
			'Second Comment for Second Article',
			'First new comment',
			'Second new comment',
			'Third new comment'
		);
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
		$TestModel->Comment->validate = array('comment' => 'notEmpty');

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
				array(
					'comment' => '',
					'published' => 'Y',
					'user_id' => 1
			))
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

		$TestModel->validate = array('title' => 'notEmpty');
		$data = array(
			array('author_id' => 1, 'title' => 'New Fourth Post'),
			array('author_id' => 1, 'title' => 'New Fifth Post'),
			array('author_id' => 1, 'title' => '')
		);
		$ts = date('Y-m-d H:i:s');
		$this->assertFalse($TestModel->saveAll($data));

		$result = $TestModel->find('all', array('recursive' => -1));
		$expected = array(
			array('Post' => array(
				'id' => '1',
				'author_id' => 1,
				'title' => 'First Post',
				'body' => 'First Post Body',
				'published' => 'Y',
				'created' => '2007-03-18 10:39:23',
				'updated' => '2007-03-18 10:41:31'
			)),
			array('Post' => array(
				'id' => '2',
				'author_id' => 3,
				'title' => 'Second Post',
				'body' => 'Second Post Body',
				'published' => 'Y',
				'created' => '2007-03-18 10:41:23',
				'updated' => '2007-03-18 10:43:31'
			)),
			array('Post' => array(
				'id' => '3',
				'author_id' => 1,
				'title' => 'Third Post',
				'body' => 'Third Post Body',
				'published' => 'Y',
				'created' => '2007-03-18 10:43:23',
				'updated' => '2007-03-18 10:45:31'
		)));

		if (count($result) != 3) {
			// Database doesn't support transactions
			$expected[] = array(
				'Post' => array(
					'id' => '4',
					'author_id' => 1,
					'title' => 'New Fourth Post',
					'body' => null,
					'published' => 'N',
					'created' => $ts,
					'updated' => $ts
			));

			$expected[] = array(
				'Post' => array(
					'id' => '5',
					'author_id' => 1,
					'title' => 'New Fifth Post',
					'body' => null,
					'published' => 'N',
					'created' => $ts,
					'updated' => $ts
			));

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
			array('Post' => array(
				'id' => '1',
				'author_id' => 1,
				'title' => 'First Post',
				'body' => 'First Post Body',
				'published' => 'Y',
				'created' => '2007-03-18 10:39:23',
				'updated' => '2007-03-18 10:41:31'
			)),
			array('Post' => array(
				'id' => '2',
				'author_id' => 3,
				'title' => 'Second Post',
				'body' => 'Second Post Body',
				'published' => 'Y',
				'created' => '2007-03-18 10:41:23',
				'updated' => '2007-03-18 10:43:31'
			)),
			array('Post' => array(
				'id' => '3',
				'author_id' => 1,
				'title' => 'Third Post',
				'body' => 'Third Post Body',
				'published' => 'Y',
				'created' => '2007-03-18 10:43:23',
				'updated' => '2007-03-18 10:45:31'
		)));

		if (count($result) != 3) {
			// Database doesn't support transactions
			$expected[] = array(
				'Post' => array(
					'id' => '4',
					'author_id' => 1,
					'title' => 'New Fourth Post',
					'body' => 'Third Post Body',
					'published' => 'N',
					'created' => $ts,
					'updated' => $ts
			));

			$expected[] = array(
				'Post' => array(
					'id' => '5',
					'author_id' => 1,
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'N',
					'created' => $ts,
					'updated' => $ts
			));
		}
		$this->assertEqual($result, $expected);

		$TestModel->validate = array('title' => 'notEmpty');
		$data = array(
			array('author_id' => 1, 'title' => 'New Fourth Post'),
			array('author_id' => 1, 'title' => 'New Fifth Post'),
			array('author_id' => 1, 'title' => 'New Sixth Post')
		);
		$this->assertTrue($TestModel->saveAll($data));

		$result = $TestModel->find('all', array(
			'recursive' => -1,
			'fields' => array('author_id', 'title','body','published')
		));

		$expected = array(
			array('Post' => array(
				'author_id' => 1,
				'title' => 'First Post',
				'body' => 'First Post Body',
				'published' => 'Y'
			)),
			array('Post' => array(
				'author_id' => 3,
				'title' => 'Second Post',
				'body' => 'Second Post Body',
				'published' => 'Y'
			)),
			array('Post' => array(
				'author_id' => 1,
				'title' => 'Third Post',
				'body' => 'Third Post Body',
				'published' => 'Y'
			)),
			array('Post' => array(
				'author_id' => 1,
				'title' => 'New Fourth Post',
				'body' => '',
				'published' => 'N'
			)),
			array('Post' => array(
				'author_id' => 1,
				'title' => 'New Fifth Post',
				'body' => '',
				'published' => 'N'
			)),
			array('Post' => array(
				'author_id' => 1,
				'title' => 'New Sixth Post',
				'body' => '',
				'published' => 'N'
		)));
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
			array(
				'id' => '1',
				'title' => 'Baleeted First Post',
				'body' => 'Baleeted!',
				'published' => 'N'
			),
			array(
				'id' => '2',
				'title' => 'Just update the title'
			),
			array(
				'title' => 'Creating a fourth post',
				'body' => 'Fourth post body',
				'author_id' => 2
		));

		$this->assertTrue($TestModel->saveAll($data));

		$result = $TestModel->find('all', array('recursive' => -1, 'order' => 'Post.id ASC'));
		$ts = date('Y-m-d H:i:s');
		$expected = array(
			array(
				'Post' => array(
					'id' => '1',
					'author_id' => '1',
					'title' => 'Baleeted First Post',
					'body' => 'Baleeted!',
					'published' => 'N',
					'created' => '2007-03-18 10:39:23',
					'updated' => $ts
			)),
			array(
				'Post' => array(
					'id' => '2',
					'author_id' => '3',
					'title' => 'Just update the title',
					'body' => 'Second Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23', 'updated' => $ts
			)),
			array(
				'Post' => array(
					'id' => '3',
					'author_id' => '1',
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
			)),
			array(
				'Post' => array(
					'id' => '4',
					'author_id' => '2',
					'title' => 'Creating a fourth post',
					'body' => 'Fourth post body',
					'published' => 'N',
					'created' => $ts,
					'updated' => $ts
		)));
		$this->assertEqual($result, $expected);

		$TestModel->validate = array('title' => 'notEmpty', 'author_id' => 'numeric');
		$data = array(
			array(
				'id' => '1',
				'title' => 'Un-Baleeted First Post',
				'body' => 'Not Baleeted!',
				'published' => 'Y'
			),
			array(
				'id' => '2',
				'title' => '',
				'body' => 'Trying to get away with an empty title'
		));
		$result = $TestModel->saveAll($data);
		$this->assertEqual($result, false);

		$result = $TestModel->find('all', array('recursive' => -1, 'order' => 'Post.id ASC'));
		$errors = array(1 => array('title' => 'This field cannot be left blank'));
		$transactionWorked = Set::matches('/Post[1][title=Baleeted First Post]', $result);
		if (!$transactionWorked) {
			$this->assertTrue(Set::matches('/Post[1][title=Un-Baleeted First Post]', $result));
			$this->assertTrue(Set::matches('/Post[2][title=Just update the title]', $result));
		}

		$this->assertEqual($TestModel->validationErrors, $errors);

		$TestModel->validate = array('title' => 'notEmpty', 'author_id' => 'numeric');
		$data = array(
			array(
				'id' => '1',
				'title' => 'Un-Baleeted First Post',
				'body' => 'Not Baleeted!',
				'published' => 'Y'
			),
			array(
				'id' => '2',
				'title' => '',
				'body' => 'Trying to get away with an empty title'
		));
		$result = $TestModel->saveAll($data, array('atomic' => false));
		$this->assertEqual($result, array(true, false));
		$result = $TestModel->find('all', array('recursive' => -1, 'order' => 'Post.id ASC'));
		$errors = array(1 => array('title' => 'This field cannot be left blank'));
		$newTs = date('Y-m-d H:i:s');
		$expected = array(
			array(
				'Post' => array(
					'id' => '1',
					'author_id' => '1',
					'title' => 'Un-Baleeted First Post',
					'body' => 'Not Baleeted!',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => $newTs
			)),
			array(
				'Post' => array(
					'id' => '2',
					'author_id' => '3',
					'title' => 'Just update the title',
					'body' => 'Second Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => $ts
			)),
			array(
				'Post' => array(
					'id' => '3',
					'author_id' => '1',
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
			)),
			array(
				'Post' => array(
					'id' => '4',
					'author_id' => '2',
					'title' => 'Creating a fourth post',
					'body' => 'Fourth post body',
					'published' => 'N',
					'created' => $ts,
					'updated' => $ts
		)));
		$this->assertEqual($result, $expected);
		$this->assertEqual($TestModel->validationErrors, $errors);

		$data = array(
			array(
				'id' => '1',
				'title' => 'Re-Baleeted First Post',
				'body' => 'Baleeted!',
				'published' => 'N'
			),
			array(
				'id' => '2',
				'title' => '',
				'body' => 'Trying to get away with an empty title'
		));
		$this->assertFalse($TestModel->saveAll($data, array('validate' => 'first')));

		$result = $TestModel->find('all', array('recursive' => -1, 'order' => 'Post.id ASC'));
		$this->assertEqual($result, $expected);
		$this->assertEqual($TestModel->validationErrors, $errors);

		$data = array(
			array(
				'title' => 'First new post',
				'body' => 'Woohoo!',
				'published' => 'Y'
			),
			array(
				'title' => 'Empty body',
				'body' => ''
		));

		$TestModel->validate['body'] = 'notEmpty';
	}

/**
 * testSaveAllValidationOnly method
 *
 * @access public
 * @return void
 */
	function testSaveAllValidationOnly() {
		$TestModel =& new Comment();
		$TestModel->Attachment->validate = array('attachment' => 'notEmpty');

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
		$TestModel->validate = array('title' => 'notEmpty');
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

		$model->Comment->validate = array('comment' => 'notEmpty');
		$result = $model->saveAll(array(
			'Article' => array(
				'title' => 'Post with Author',
				'body' => 'This post will be saved  author'
			),
			'Comment' => array(
				array('comment' => 'First new comment'),
				array('comment' => '')
			)
		), array('validate' => 'first'));

		$this->assertFalse($result);

		$result = $model->find('all');
		$this->assertEqual($result, array());
		$expected = array('Comment' => array(
			1 => array('comment' => 'This field cannot be left blank')
		));

		$this->assertEqual($model->Comment->validationErrors, $expected['Comment']);

		$this->assertIdentical($model->Comment->find('count'), 0);

		$result = $model->saveAll(
			array(
				'Article' => array(
					'title' => 'Post with Author',
					'body' => 'This post will be saved with an author',
					'user_id' => 2
				),
				'Comment' => array(
					array(
						'comment' => 'Only new comment',
						'user_id' => 2
			))),
			array('validate' => 'first')
		);

		$this->assertIdentical($result, true);

		$result = $model->Comment->find('all');
		$this->assertIdentical(count($result), 1);
		$result = Set::extract('/Comment/article_id', $result);
		$this->assertTrue($result[0] === 1 || $result[0] === '1');


		$model->deleteAll(true);
		$data = array(
			'Article' => array(
				'title' => 'Post with Author saveAlled from comment',
				'body' => 'This post will be saved with an author',
				'user_id' => 2
			),
			'Comment' => array(
				'comment' => 'Only new comment', 'user_id' => 2
		));

		$result = $model->Comment->saveAll($data, array('validate' => 'first'));
		$this->assertTrue($result);

		$result = $model->find('all');
		$this->assertEqual(
			$result[0]['Article']['title'],
			'Post with Author saveAlled from comment'
		);
		$this->assertEqual($result[0]['Comment'][0]['comment'], 'Only new comment');
	}

/**
 * testUpdateWithCalculation method
 *
 * @access public
 * @return void
 */
	function testUpdateWithCalculation() {
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
 * testSaveAllHasManyValidationOnly method
 *
 * @access public
 * @return void
 */
	function testSaveAllHasManyValidationOnly() {
		$this->loadFixtures('Article', 'Comment');
		$TestModel =& new Article();
		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = array();
		$TestModel->Comment->validate = array('comment' => 'notEmpty');

		$result = $TestModel->saveAll(
			array(
				'Article' => array('id' => 2),
				'Comment' => array(
					array(
						'id' => 1,
						'comment' => '',
						'published' => 'Y',
						'user_id' => 1),
					array(
						'id' => 2,
						'comment' =>
						'comment',
						'published' => 'Y',
						'user_id' => 1
			))),
			array('validate' => 'only')
		);
		$this->assertFalse($result);

		$result = $TestModel->saveAll(
			array(
				'Article' => array('id' => 2),
				'Comment' => array(
					array(
						'id' => 1,
						'comment' => '',
						'published' => 'Y',
						'user_id' => 1
					),
					array(
						'id' => 2,
						'comment' => 'comment',
						'published' => 'Y',
						'user_id' => 1
					),
					array(
						'id' => 3,
						'comment' => '',
						'published' => 'Y',
						'user_id' => 1
			))),
			array(
				'validate' => 'only',
				'atomic' => false
		));
		$expected = array(
			'Article' => true,
			'Comment' => array(false, true, false)
		);
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
 * TestFindAllWithoutForeignKey
 *
 * @link http://code.cakephp.org/tickets/view/69
 * @access public
 * @return void
 */
	function testFindAllForeignKey() {
		$this->loadFixtures('ProductUpdateAll', 'GroupUpdateAll');
		$ProductUpdateAll =& new ProductUpdateAll();

		$conditions = array('Group.name' => 'group one');

        $ProductUpdateAll->bindModel(array(
			'belongsTo' => array(
				'Group' => array('className' => 'GroupUpdateAll')
			)
		));

        $ProductUpdateAll->belongsTo = array(
			'Group' => array('className' => 'GroupUpdateAll', 'foreignKey' => 'group_id')
		);

        $results = $ProductUpdateAll->find('all', compact('conditions'));
		$this->assertTrue(!empty($results));

        $ProductUpdateAll->bindModel(array('belongsTo'=>array('Group')));
        $ProductUpdateAll->belongsTo = array(
            'Group' => array(
				'className' => 'GroupUpdateAll',
				'foreignKey' => false,
				'conditions' => 'ProductUpdateAll.groupcode = Group.code'
			));

        $resultsFkFalse = $ProductUpdateAll->find('all', compact('conditions'));
        $this->assertTrue(!empty($resultsFkFalse));
        $expected = array(
            '0' => array(
                'ProductUpdateAll' => array(
                    'id'  => 1,
                    'name'  => 'product one',
                    'groupcode'  => 120,
                    'group_id'  => 1),
                'Group' => array(
                    'id' => 1,
                    'name' => 'group one',
                    'code' => 120)
                ),
            '1' => array(
                'ProductUpdateAll' => array(
                    'id'  => 2,
                    'name'  => 'product two',
                    'groupcode'  => 120,
                    'group_id'  => 1),
                'Group' => array(
                    'id' => 1,
                    'name' => 'group one',
                    'code' => 120)
                )

            );
        $this->assertEqual($results, $expected);
        $this->assertEqual($resultsFkFalse, $expected);
    }
/**
 * testProductUpdateAllWithForeignKey
 *
 * @link http://code.cakephp.org/tickets/view/69
 * @access public
 * @return void
 */
    function testProductUpdateAll() {
		$this->loadFixtures('ProductUpdateAll', 'GroupUpdateAll');
		$ProductUpdateAll =& new ProductUpdateAll();

		$conditions = array('Group.name' => 'group one');

        $ProductUpdateAll->bindModel(array('belongsTo' => array(
			'Group' => array('className' => 'GroupUpdateAll')))
		);

        $ProductUpdateAll->updateAll(array('name' => "'new product'"), $conditions);
        $results = $ProductUpdateAll->find('all', array(
			'conditions' => array('ProductUpdateAll.name' => 'new product')
		));
        $expected = array(
            '0' => array(
                'ProductUpdateAll' => array(
                    'id'  => 1,
                    'name'  => 'new product',
                    'groupcode'  => 120,
                    'group_id'  => 1),
                'Group' => array(
                    'id' => 1,
                    'name' => 'group one',
                    'code' => 120)
                ),
            '1' => array(
                'ProductUpdateAll' => array(
                    'id'  => 2,
                    'name'  => 'new product',
                    'groupcode'  => 120,
                    'group_id'  => 1),
                'Group' => array(
                    'id' => 1,
                    'name' => 'group one',
                    'code' => 120)));

        $this->assertEqual($results, $expected);
    }
/**
 * testProductUpdateAllWithoutForeignKey
 *
 * @link http://code.cakephp.org/tickets/view/69
 * @access public
 * @return void
 */
    function testProductUpdateAllWithoutForeignKey() {
		$this->loadFixtures('ProductUpdateAll', 'GroupUpdateAll');
		$ProductUpdateAll =& new ProductUpdateAll();

		$conditions = array('Group.name' => 'group one');

        $ProductUpdateAll->bindModel(array('belongsTo' => array(
			'Group' => array('className' => 'GroupUpdateAll')
		)));

        $ProductUpdateAll->belongsTo = array(
            'Group' => array(
				'className' => 'GroupUpdateAll',
				'foreignKey' => false,
				'conditions' => 'ProductUpdateAll.groupcode = Group.code'
			)
		);

        $ProductUpdateAll->updateAll(array('name' => "'new product'"), $conditions);
        $resultsFkFalse = $ProductUpdateAll->find('all', array('conditions' => array('ProductUpdateAll.name'=>'new product')));
        $expected = array(
            '0' => array(
                'ProductUpdateAll' => array(
                    'id'  => 1,
                    'name'  => 'new product',
                    'groupcode'  => 120,
                    'group_id'  => 1),
                'Group' => array(
                    'id' => 1,
                    'name' => 'group one',
                    'code' => 120)
                ),
            '1' => array(
                'ProductUpdateAll' => array(
                    'id'  => 2,
                    'name'  => 'new product',
                    'groupcode'  => 120,
                    'group_id'  => 1),
                'Group' => array(
                    'id' => 1,
                    'name' => 'group one',
                    'code' => 120)));
        $this->assertEqual($resultsFkFalse, $expected);
    }

}

?>