<?php
/**
 * ModelWriteTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('MockTransactionDboSource', 'Model/Datasource');
App::uses('MockTransactionAssociatedDboSource', 'Model/Datasource');
App::uses('MockManyTransactionDboSource', 'Model/Datasource');
App::uses('MockAssociatedTransactionDboSource', 'Model/Datasource');

require_once dirname(__FILE__) . DS . 'ModelTestBase.php';

/**
 * ModelWriteTest
 *
 * @package       Cake.Test.Case.Model
 */
class ModelWriteTest extends BaseModelTest {

/**
 * override locale to the default (eng).
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Config.language', 'eng');
	}

/**
 * Test save() failing when there is no data.
 *
 * @return void
 */
	public function testInsertNoData() {
		$this->loadFixtures('Bid');
		$Bid = ClassRegistry::init('Bid');

		$this->assertFalse($Bid->save());

		$result = $Bid->save(array('Bid' => array()));
		$this->assertFalse($result);

		$result = $Bid->save(array('Bid' => array('not in schema' => 1)));
		$this->assertFalse($result);
	}

/**
 * testInsertAnotherHabtmRecordWithSameForeignKey method
 *
 * @return void
 */
	public function testInsertAnotherHabtmRecordWithSameForeignKey() {
		$this->loadFixtures('JoinA', 'JoinB', 'JoinAB', 'JoinC', 'JoinAC');
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
		$this->assertEquals($expected, $result);

		$TestModel->JoinAsJoinB->create();
		$data = array(
			'join_a_id' => 1,
			'join_b_id' => 1,
			'other' => 'Data for Join A 1 Join B 1',
			'created' => '2008-01-03 10:56:44',
			'updated' => '2008-01-03 10:56:44'
		);
		$result = $TestModel->JoinAsJoinB->save($data);
		$lastInsertId = $TestModel->JoinAsJoinB->getLastInsertID();
		$data['id'] = $lastInsertId;
		$this->assertEquals(array('JoinAsJoinB' => $data), $result);
		$this->assertTrue($lastInsertId > 0);

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
		$this->assertEquals($expected, $result);

		$updatedValue = 'UPDATED Data for Join A 1 Join B 2';
		$TestModel->JoinAsJoinB->id = 1;
		$result = $TestModel->JoinAsJoinB->saveField('other', $updatedValue, false);
		$this->assertFalse(empty($result));

		$result = $TestModel->JoinAsJoinB->findById(1);
		$this->assertEquals($updatedValue, $result['JoinAsJoinB']['other']);
	}

/**
 * testSaveDateAsFirstEntry method
 *
 * @return void
 */
	public function testSaveDateAsFirstEntry() {
		$this->loadFixtures('Article', 'User', 'Comment', 'Attachment', 'Tag', 'ArticlesTag');

		$Article = new Article();

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
		$result = $Article->save($data);
		$this->assertFalse(empty($result));

		$testResult = $Article->find('first', array('conditions' => array('Article.title' => 'Test Title')));

		$this->assertEquals($data['Article']['title'], $testResult['Article']['title']);
		$this->assertEquals('2008-01-01 00:00:00', $testResult['Article']['created']);
	}

/**
 * testUnderscoreFieldSave method
 *
 * @return void
 */
	public function testUnderscoreFieldSave() {
		$this->loadFixtures('UnderscoreField');
		$UnderscoreField = new UnderscoreField();

		$currentCount = $UnderscoreField->find('count');
		$this->assertEquals(3, $currentCount);
		$data = array('UnderscoreField' => array(
			'user_id' => '1',
			'my_model_has_a_field' => 'Content here',
			'body' => 'Body',
			'published' => 'Y',
			'another_field' => 4
		));
		$ret = $UnderscoreField->save($data);
		$this->assertFalse(empty($ret));

		$currentCount = $UnderscoreField->find('count');
		$this->assertEquals(4, $currentCount);
	}

/**
 * testAutoSaveUuid method
 *
 * @return void
 */
	public function testAutoSaveUuid() {
		// SQLite does not support non-integer primary keys
		$this->skipIf($this->db instanceof Sqlite, 'This test is not compatible with SQLite.');

		$this->loadFixtures('Uuid');
		$TestModel = new Uuid();

		$TestModel->save(array('title' => 'Test record'));
		$result = $TestModel->findByTitle('Test record');
		$this->assertEquals(
			array('id', 'title', 'count', 'created', 'updated'),
			array_keys($result['Uuid'])
		);
		$this->assertEquals(36, strlen($result['Uuid']['id']));
	}

/**
 * Ensure that if the id key is null but present the save doesn't fail (with an
 * x sql error: "Column id specified twice")
 *
 * @return void
 */
	public function testSaveUuidNull() {
		// SQLite does not support non-integer primary keys
		$this->skipIf($this->db instanceof Sqlite, 'This test is not compatible with SQLite.');

		$this->loadFixtures('Uuid');
		$TestModel = new Uuid();

		$TestModel->save(array('title' => 'Test record', 'id' => null));
		$result = $TestModel->findByTitle('Test record');
		$this->assertEquals(
			array('id', 'title', 'count', 'created', 'updated'),
			array_keys($result['Uuid'])
		);
		$this->assertEquals(36, strlen($result['Uuid']['id']));
	}

/**
 * testZeroDefaultFieldValue method
 *
 * @return void
 */
	public function testZeroDefaultFieldValue() {
		$this->skipIf($this->db instanceof Sqlite, 'SQLite uses loose typing, this operation is unsupported.');

		$this->loadFixtures('DataTest');
		$TestModel = new DataTest();

		$TestModel->create(array());
		$TestModel->save();
		$result = $TestModel->findById($TestModel->id);
		$this->assertEquals(0, $result['DataTest']['count']);
		$this->assertEquals(0, $result['DataTest']['float']);
	}

/**
 * Tests validation parameter order in custom validation methods
 *
 * @return void
 */
	public function testAllowSimulatedFields() {
		$TestModel = new ValidationTest1();

		$TestModel->create(array(
			'title' => 'foo',
			'bar' => 'baz'
		));
		$expected = array(
			'ValidationTest1' => array(
				'title' => 'foo',
				'bar' => 'baz'
		));
		$this->assertEquals($expected, $TestModel->data);
	}

/**
 * test that Caches are getting cleared on save().
 * ensure that both inflections of controller names are getting cleared
 * as URL for controller could be either overallFavorites/index or overall_favorites/index
 *
 * @return void
 */
	public function testCacheClearOnSave() {
		$_back = array(
			'check' => Configure::read('Cache.check'),
			'disable' => Configure::read('Cache.disable'),
		);
		Configure::write('Cache.check', true);
		Configure::write('Cache.disable', false);

		$this->loadFixtures('OverallFavorite');
		$OverallFavorite = new OverallFavorite();

		touch(CACHE . 'views' . DS . 'some_dir_overallfavorites_index.php');
		touch(CACHE . 'views' . DS . 'some_dir_overall_favorites_index.php');

		$data = array(
			'OverallFavorite' => array(
				'id' => 22,
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
 * test that save() resets whitelist on failed save
 */
	public function testSaveFieldListResetsWhitelistOnFailedSave() {
		$this->loadFixtures('Bidding');
		$model = new Bidding();
		$whitelist = array('title');
		$model->whitelist = $whitelist;
		$result = $model->save(
			array(),
			array('fieldList' => array('body'))
		);
		$this->assertFalse($result);
		$this->assertEquals($whitelist, $model->whitelist);
	}

/**
 * testSaveWithCounterCache method
 *
 * @return void
 */
	public function testSaveWithCounterCache() {
		$this->loadFixtures('Syfile', 'Item', 'Image', 'Portfolio', 'ItemsPortfolio');
		$TestModel = new Syfile();
		$TestModel2 = new Item();

		$result = $TestModel->findById(1);
		$this->assertNull($result['Syfile']['item_count']);

		$TestModel2->save(array(
			'name' => 'Item 7',
			'syfile_id' => 1,
			'published' => false
		));

		$result = $TestModel->findById(1);
		$this->assertEquals(2, $result['Syfile']['item_count']);

		$TestModel2->delete(1);
		$result = $TestModel->findById(1);
		$this->assertEquals(1, $result['Syfile']['item_count']);

		$TestModel2->id = 2;
		$TestModel2->saveField('syfile_id', 1);

		$result = $TestModel->findById(1);
		$this->assertEquals(2, $result['Syfile']['item_count']);

		$result = $TestModel->findById(2);
		$this->assertEquals(0, $result['Syfile']['item_count']);
	}

/**
 * Tests that counter caches are updated when records are added
 *
 * @return void
 */
	public function testCounterCacheIncrease() {
		$this->loadFixtures('CounterCacheUser', 'CounterCachePost');
		$User = new CounterCacheUser();
		$Post = new CounterCachePost();
		$data = array('Post' => array(
			'id' => 22,
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
		$this->assertEquals($expected, $result);
	}

/**
 * Tests that counter caches are updated when records are deleted
 *
 * @return void
 */
	public function testCounterCacheDecrease() {
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
		$this->assertEquals($expected, $result);
	}

/**
 * Tests that counter caches are updated when foreign keys of counted records change
 *
 * @return void
 */
	public function testCounterCacheUpdated() {
		$this->loadFixtures('CounterCacheUser', 'CounterCachePost');
		$User = new CounterCacheUser();
		$Post = new CounterCachePost();

		$data = $Post->find('first', array(
			'conditions' => array('id' => 1),
			'recursive' => -1
		));
		$data[$Post->alias]['user_id'] = 301;
		$Post->save($data);

		$users = $User->find('all', array('order' => 'User.id'));
		$this->assertEquals(1, $users[0]['User']['post_count']);
		$this->assertEquals(2, $users[1]['User']['post_count']);
	}

/**
 * Test counter cache with models that use a non-standard (i.e. not using 'id')
 * as their primary key.
 *
 * @return void
 */
	public function testCounterCacheWithNonstandardPrimaryKey() {
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

		$users = $User->find('all', array('order' => 'User.uid'));
		$this->assertEquals(1, $users[0]['User']['post_count']);
		$this->assertEquals(2, $users[1]['User']['post_count']);
	}

/**
 * test Counter Cache With Self Joining table
 *
 * @return void
 */
	public function testCounterCacheWithSelfJoin() {
		$this->skipIf($this->db instanceof Sqlite, 'SQLite 2.x does not support ALTER TABLE ADD COLUMN');

		$this->loadFixtures('CategoryThread');
		$column = 'COLUMN ';
		if ($this->db instanceof Sqlserver) {
			$column = '';
		}
		$column .= $this->db->buildColumn(array('name' => 'child_count', 'type' => 'integer'));
		$this->db->query('ALTER TABLE ' . $this->db->fullTableName('category_threads') . ' ADD ' . $column);
		$this->db->flushMethodCache();
		$Category = new CategoryThread();
		$result = $Category->updateAll(array('CategoryThread.name' => "'updated'"), array('CategoryThread.parent_id' => 5));
		$this->assertFalse(empty($result));

		$Category = new CategoryThread();
		$Category->belongsTo['ParentCategory']['counterCache'] = 'child_count';
		$Category->updateCounterCache(array('parent_id' => 5));
		$result = Hash::extract($Category->find('all', array('conditions' => array('CategoryThread.id' => 5))), '{n}.CategoryThread.child_count');
		$expected = array(1);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveWithCounterCacheScope method
 *
 * @return void
 */
	public function testSaveWithCounterCacheScope() {
		$this->loadFixtures('Syfile', 'Item', 'Image', 'ItemsPortfolio', 'Portfolio');
		$TestModel = new Syfile();
		$TestModel2 = new Item();
		$TestModel2->belongsTo['Syfile']['counterCache'] = true;
		$TestModel2->belongsTo['Syfile']['counterScope'] = array('published' => true);

		$result = $TestModel->findById(1);
		$this->assertNull($result['Syfile']['item_count']);

		$TestModel2->save(array(
			'name' => 'Item 7',
			'syfile_id' => 1,
			'published' => true
		));

		$result = $TestModel->findById(1);

		$this->assertEquals(1, $result['Syfile']['item_count']);

		$TestModel2->id = 1;
		$TestModel2->saveField('published', true);
		$result = $TestModel->findById(1);
		$this->assertEquals(2, $result['Syfile']['item_count']);

		$TestModel2->save(array(
			'id' => 1,
			'syfile_id' => 1,
			'published' => false
		));

		$result = $TestModel->findById(1);
		$this->assertEquals(1, $result['Syfile']['item_count']);
	}

/**
 * Tests having multiple counter caches for an associated model
 *
 * @return void
 */
	public function testCounterCacheMultipleCaches() {
		$this->loadFixtures('CounterCacheUser', 'CounterCachePost');
		$User = new CounterCacheUser();
		$Post = new CounterCachePost();
		$Post->unbindModel(array('belongsTo' => array('User')), false);
		$Post->bindModel(array(
			'belongsTo' => array(
				'User' => array(
					'className' => 'CounterCacheUser',
					'foreignKey' => 'user_id',
					'counterCache' => array(
						true,
						'posts_published' => array('Post.published' => true)
					)
				)
			)
		), false);

		// Count Increase
		$data = array('Post' => array(
			'id' => 22,
			'title' => 'New Post',
			'user_id' => 66,
			'published' => true
		));
		$Post->save($data);
		$result = $User->find('first', array(
			'conditions' => array('id' => 66),
			'recursive' => -1
		));
		$this->assertEquals(3, $result[$User->alias]['post_count']);
		$this->assertEquals(2, $result[$User->alias]['posts_published']);

		// Count decrease
		$Post->delete(1);
		$result = $User->find('first', array(
			'conditions' => array('id' => 66),
			'recursive' => -1
		));
		$this->assertEquals(2, $result[$User->alias]['post_count']);
		$this->assertEquals(2, $result[$User->alias]['posts_published']);

		// Count update
		$data = $Post->find('first', array(
			'conditions' => array('id' => 1),
			'recursive' => -1
		));
		$data[$Post->alias]['user_id'] = 301;
		$Post->save($data);
		$result = $User->find('all', array('order' => 'User.id'));
		$this->assertEquals(2, $result[0]['User']['post_count']);
		$this->assertEquals(1, $result[1]['User']['posts_published']);
	}

/**
 * Tests that counter caches are unchanged when using 'counterCache' => false
 *
 * @return void
 */
	public function testCounterCacheSkip() {
		$this->loadFixtures('CounterCacheUser', 'CounterCachePost');
		$User = new CounterCacheUser();
		$Post = new CounterCachePost();

		$data = $Post->find('first', array(
			'conditions' => array('id' => 1),
			'recursive' => -1
		));
		$data[$Post->alias]['user_id'] = 301;
		$Post->save($data, array('counterCache' => false));

		$users = $User->find('all', array('order' => 'User.id'));
		$this->assertEquals(2, $users[0]['User']['post_count']);
		$this->assertEquals(1, $users[1]['User']['post_count']);
	}

/**
 * test that beforeValidate returning false can abort saves.
 *
 * @return void
 */
	public function testBeforeValidateSaveAbortion() {
		$this->loadFixtures('Post');
		$Model = new CallbackPostTestModel();
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
	public function testBeforeSaveSaveAbortion() {
		$this->loadFixtures('Post');
		$Model = new CallbackPostTestModel();
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
 * @return void
 */
	public function testSaveField() {
		$this->loadFixtures('Article');
		$TestModel = new Article();

		$TestModel->id = 1;
		$result = $TestModel->saveField('title', 'New First Article');
		$this->assertFalse(empty($result));

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body'), 1);
		$expected = array('Article' => array(
			'id' => '1',
			'user_id' => '1',
			'title' => 'New First Article',
			'body' => 'First Article Body'
		));
		$this->assertEquals($expected, $result);

		$TestModel->id = 1;
		$result = $TestModel->saveField('title', '');
		$this->assertFalse(empty($result));

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body'), 1);
		$expected = array('Article' => array(
			'id' => '1',
			'user_id' => '1',
			'title' => '',
			'body' => 'First Article Body'
		));
		$result['Article']['title'] = trim($result['Article']['title']);
		$this->assertEquals($expected, $result);

		$TestModel->id = 1;
		$TestModel->set('body', 'Messed up data');
		$result = $TestModel->saveField('title', 'First Article');
		$this->assertFalse(empty($result));
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body'), 1);
		$expected = array('Article' => array(
			'id' => '1',
			'user_id' => '1',
			'title' => 'First Article',
			'body' => 'First Article Body'
		));
		$this->assertEquals($expected, $result);

		$TestModel->recursive = -1;
		$TestModel->read(array('id', 'user_id', 'title', 'body'), 1);

		$TestModel->id = 1;
		$result = $TestModel->saveField('title', '', true);
		$this->assertFalse($result);

		$TestModel->recursive = -1;
		$TestModel->id = 1;
		$result = $TestModel->saveField('user_id', 9999);
		$this->assertTrue((bool)$result);

		$result = $TestModel->read(array('id', 'user_id'), 1);
		$expected = array('Article' => array(
			'id' => '1',
			'user_id' => '9999',
		));
		$this->assertEquals($expected, $result);

		$this->loadFixtures('Node', 'Dependency');
		$Node = new Node();
		$Node->set('id', 1);
		$result = $Node->read();
		$this->assertEquals(array('Second'), Hash::extract($result, 'ParentNode.{n}.name'));

		$Node->saveField('state', 10);
		$result = $Node->read();
		$this->assertEquals(array('Second'), Hash::extract($result, 'ParentNode.{n}.name'));
	}

/**
 * testSaveWithCreate method
 *
 * @return void
 */
	public function testSaveWithCreate() {
		$this->loadFixtures(
			'User',
			'Article',
			'User',
			'Comment',
			'Tag',
			'ArticlesTag',
			'Attachment'
		);
		$TestModel = new User();

		$data = array('User' => array(
			'user' => 'user',
			'password' => ''
		));
		$result = $TestModel->save($data);
		$this->assertFalse($result);
		$this->assertTrue(!empty($TestModel->validationErrors));

		$TestModel = new Article();

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
		$this->assertFalse(empty($result));

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 1);
		$expected = array('Article' => array(
			'id' => '1',
			'user_id' => '1',
			'title' => 'New First Article',
			'body' => 'First Article Body',
			'published' => 'N'
		));
		$this->assertEquals($expected, $result);

		$data = array('Article' => array(
			'id' => 1,
			'user_id' => '2',
			'title' => 'First Article',
			'body' => 'New First Article Body',
			'published' => 'Y'
		));
		$result = $TestModel->create() && $TestModel->save($data, true, array('id', 'title', 'published'));
		$this->assertFalse(empty($result));

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 1);
		$expected = array('Article' => array(
			'id' => '1',
			'user_id' => '1',
			'title' => 'First Article',
			'body' => 'First Article Body',
			'published' => 'Y'
		));
		$this->assertEquals($expected, $result);

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
		$this->assertFalse(empty($result));

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
		$this->assertEquals($expected, $result);

		$data = array('Comment' => array(
			'article_id' => '4',
			'user_id' => '1',
			'comment' => 'Comment New Article',
			'published' => 'Y',
			'created' => '2007-03-18 14:57:23',
			'updated' => '2007-03-18 14:59:31'
		));
		$result = $TestModel->Comment->create() && $TestModel->Comment->save($data);
		$this->assertFalse(empty($result));

		$data = array('Attachment' => array(
			'comment_id' => '7',
			'attachment' => 'newattachment.zip',
			'created' => '2007-03-18 15:02:23',
			'updated' => '2007-03-18 15:04:31'
		));
		$result = $TestModel->Comment->Attachment->save($data);
		$this->assertFalse(empty($result));

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

		$this->assertEquals($expected, $result);
	}

/**
 * test that a null Id doesn't cause errors
 *
 * @return void
 */
	public function testSaveWithNullId() {
		$this->loadFixtures('User');
		$User = new User();
		$User->read(null, 1);
		$User->data['User']['id'] = null;
		$result = $User->save(array('password' => 'test'));
		$this->assertFalse(empty($result));
		$this->assertTrue($User->id > 0);

		$User->read(null, 2);
		$User->data['User']['id'] = null;
		$result = $User->save(array('password' => 'test'));
		$this->assertFalse(empty($result));
		$this->assertTrue($User->id > 0);

		$User->data['User'] = array('password' => 'something');
		$result = $User->save();
		$this->assertFalse(empty($result));
		$result = $User->read();
		$this->assertEquals('something', $User->data['User']['password']);
	}

/**
 * testSaveWithSet method
 *
 * @return void
 */
	public function testSaveWithSet() {
		$this->loadFixtures('Article');
		$TestModel = new Article();

		// Create record we will be updating later

		$data = array('Article' => array(
			'user_id' => '1',
			'title' => 'Fourth Article',
			'body' => 'Fourth Article Body',
			'published' => 'Y'
		));
		$result = $TestModel->create() && $TestModel->save($data);
		$this->assertFalse(empty($result));

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
		$this->assertEquals($expected, $result);

		// Create new record just to overlap Model->id on previously created record

		$data = array('Article' => array(
			'user_id' => '4',
			'title' => 'Fifth Article',
			'body' => 'Fifth Article Body',
			'published' => 'Y'
		));
		$result = $TestModel->create() && $TestModel->save($data);
		$this->assertFalse(empty($result));

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 5);
		$expected = array('Article' => array(
			'id' => '5',
			'user_id' => '4',
			'title' => 'Fifth Article',
			'body' => 'Fifth Article Body',
			'published' => 'Y'
		));
		$this->assertEquals($expected, $result);

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
		$this->assertEquals($expected, $result);

		// And now do the update with set()

		$data = array('Article' => array(
			'id' => '4',
			'title' => 'Fourth Article - New Title',
			'published' => 'N'
		));
		$result = $TestModel->set($data) && $TestModel->save();
		$this->assertFalse(empty($result));

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 4);
		$expected = array('Article' => array(
			'id' => '4',
			'user_id' => '1',
			'title' => 'Fourth Article - New Title',
			'body' => 'Fourth Article Body',
			'published' => 'N'
		));
		$this->assertEquals($expected, $result);

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 5);
		$expected = array('Article' => array(
			'id' => '5',
			'user_id' => '4',
			'title' => 'Fifth Article',
			'body' => 'Fifth Article Body',
			'published' => 'Y'
		));
		$this->assertEquals($expected, $result);

		$data = array('Article' => array('id' => '5', 'title' => 'Fifth Article - New Title 5'));
		$result = ($TestModel->set($data) && $TestModel->save());
		$this->assertFalse(empty($result));

		$TestModel->recursive = -1;
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 5);
		$expected = array('Article' => array(
			'id' => '5',
			'user_id' => '4',
			'title' => 'Fifth Article - New Title 5',
			'body' => 'Fifth Article Body',
			'published' => 'Y'
		));
		$this->assertEquals($expected, $result);

		$TestModel->recursive = -1;
		$result = $TestModel->find('all', array(
			'fields' => array('id', 'title'),
			'order' => array('Article.id' => 'ASC')
		));
		$expected = array(
			array('Article' => array('id' => 1, 'title' => 'First Article')),
			array('Article' => array('id' => 2, 'title' => 'Second Article')),
			array('Article' => array('id' => 3, 'title' => 'Third Article')),
			array('Article' => array('id' => 4, 'title' => 'Fourth Article - New Title')),
			array('Article' => array('id' => 5, 'title' => 'Fifth Article - New Title 5'))
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveWithNonExistentFields method
 *
 * @return void
 */
	public function testSaveWithNonExistentFields() {
		$this->loadFixtures('Article');
		$TestModel = new Article();
		$TestModel->recursive = -1;

		$data = array(
			'non_existent' => 'This field does not exist',
			'user_id' => '1',
			'title' => 'Fourth Article - New Title',
			'body' => 'Fourth Article Body',
			'published' => 'N'
		);
		$result = $TestModel->create() && $TestModel->save($data);
		$this->assertFalse(empty($result));

		$expected = array('Article' => array(
			'id' => '4',
			'user_id' => '1',
			'title' => 'Fourth Article - New Title',
			'body' => 'Fourth Article Body',
			'published' => 'N'
		));
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 4);
		$this->assertEquals($expected, $result);

		$data = array(
			'user_id' => '1',
			'non_existent' => 'This field does not exist',
			'title' => 'Fifth Article - New Title',
			'body' => 'Fifth Article Body',
			'published' => 'N'
		);
		$result = $TestModel->create() && $TestModel->save($data);
		$this->assertFalse(empty($result));

		$expected = array('Article' => array(
			'id' => '5',
			'user_id' => '1',
			'title' => 'Fifth Article - New Title',
			'body' => 'Fifth Article Body',
			'published' => 'N'
		));
		$result = $TestModel->read(array('id', 'user_id', 'title', 'body', 'published'), 5);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveFromXml method
 *
 * @return void
 */
	public function testSaveFromXml() {
		$this->markTestSkipped('This feature needs to be fixed or dropped');
		$this->loadFixtures('Article');
		App::uses('Xml', 'Utility');

		$Article = new Article();
		$result = $Article->save(Xml::build('<article title="test xml" user_id="5" />'));
		$this->assertFalse(empty($result));
		$results = $Article->find('first', array('conditions' => array('Article.title' => 'test xml')));
		$this->assertFalse(empty($results));

		$result = $Article->save(Xml::build('<article><title>testing</title><user_id>6</user_id></article>'));
		$this->assertFalse(empty($result));
		$results = $Article->find('first', array('conditions' => array('Article.title' => 'testing')));
		$this->assertFalse(empty($results));

		$result = $Article->save(Xml::build('<article><title>testing with DOMDocument</title><user_id>7</user_id></article>', array('return' => 'domdocument')));
		$this->assertFalse(empty($result));
		$results = $Article->find('first', array('conditions' => array('Article.title' => 'testing with DOMDocument')));
		$this->assertFalse(empty($results));
	}

/**
 * testSaveHabtm method
 *
 * @return void
 */
	public function testSaveHabtm() {
		$this->loadFixtures('Article', 'User', 'Comment', 'Tag', 'ArticlesTag');
		$TestModel = new Article();

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
		$this->assertEquals($expected, $result);

		$data = array(
			'Article' => array(
				'id' => '2',
				'title' => 'New Second Article'
			),
			'Tag' => array('Tag' => array(1, 2))
		);

		$result = $TestModel->set($data);
		$this->assertFalse(empty($result));
		$result = $TestModel->save();
		$this->assertFalse(empty($result));
		$this->assertEquals($data['Tag'], $result['Tag']);

		$TestModel->unbindModel(array('belongsTo' => array('User'), 'hasMany' => array('Comment')));
		$result = $TestModel->find('first', array('fields' => array('id', 'user_id', 'title', 'body'), 'conditions' => array('Article.id' => 2)));
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
		$this->assertEquals($expected, $result);

		$data = array('Article' => array('id' => '2'), 'Tag' => array('Tag' => array(2, 3)));
		$result = $TestModel->set($data);
		$this->assertFalse(empty($result));

		$result = $TestModel->save();
		$this->assertFalse(empty($result));

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $TestModel->find('first', array('fields' => array('id', 'user_id', 'title', 'body'), 'conditions' => array('Article.id' => 2)));
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
		$this->assertEquals($expected, $result);

		$data = array('Tag' => array('Tag' => array(1, 2, 3)));

		$result = $TestModel->set($data);
		$this->assertFalse(empty($result));

		$result = $TestModel->save();
		$this->assertFalse(empty($result));

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $TestModel->find('first', array('fields' => array('id', 'user_id', 'title', 'body'), 'conditions' => array('Article.id' => 2)));
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
		$this->assertEquals($expected, $result);

		$data = array('Tag' => array('Tag' => array()));
		$result = $TestModel->set($data);
		$this->assertFalse(empty($result));

		$result = $TestModel->save();
		$this->assertFalse(empty($result));

		$data = array('Tag' => array('Tag' => ''));
		$result = $TestModel->set($data);
		$this->assertFalse(empty($result));

		$result = $TestModel->save();
		$this->assertFalse(empty($result));

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $TestModel->find('first', array('fields' => array('id', 'user_id', 'title', 'body'), 'conditions' => array('Article.id' => 2)));
		$expected = array(
			'Article' => array(
				'id' => '2',
				'user_id' => '3',
				'title' => 'New Second Article',
				'body' => 'Second Article Body'
			),
			'Tag' => array()
		);
		$this->assertEquals($expected, $result);

		$data = array('Tag' => array('Tag' => array(2, 3)));
		$result = $TestModel->set($data);
		$this->assertFalse(empty($result));

		$result = $TestModel->save();
		$this->assertFalse(empty($result));

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $TestModel->find('first', array('fields' => array('id', 'user_id', 'title', 'body'), 'conditions' => array('Article.id' => 2)));
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
		$this->assertEquals($expected, $result);

		$data = array(
			'Tag' => array(
				'Tag' => array(1, 2)
			),
			'Article' => array(
				'id' => '2',
				'title' => 'New Second Article'
		));
		$result = $TestModel->set($data);
		$this->assertFalse(empty($result));
		$result = $TestModel->save();
		$this->assertFalse(empty($result));

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $TestModel->find('first', array('fields' => array('id', 'user_id', 'title', 'body'), 'conditions' => array('Article.id' => 2)));
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
		$this->assertEquals($expected, $result);

		$data = array(
			'Tag' => array(
				'Tag' => array(1, 2)
			),
			'Article' => array(
				'id' => '2',
				'title' => 'New Second Article Title'
		));
		$result = $TestModel->set($data);
		$this->assertFalse(empty($result));
		$result = $TestModel->save();
		$this->assertFalse(empty($result));

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $TestModel->find('first', array('fields' => array('id', 'user_id', 'title', 'body'), 'conditions' => array('Article.id' => 2)));
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
		$this->assertEquals($expected, $result);

		$data = array(
			'Tag' => array(
				'Tag' => array(2, 3)
			),
			'Article' => array(
				'id' => '2',
				'title' => 'Changed Second Article'
		));
		$result = $TestModel->set($data);
		$this->assertFalse(empty($result));
		$result = $TestModel->save();
		$this->assertFalse(empty($result));

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $TestModel->find('first', array('fields' => array('id', 'user_id', 'title', 'body'), 'conditions' => array('Article.id' => 2)));
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
		$this->assertEquals($expected, $result);

		$data = array(
			'Tag' => array(
				'Tag' => array(1, 3)
			),
			'Article' => array('id' => '2'),
		);

		$result = $TestModel->set($data);
		$this->assertFalse(empty($result));

		$result = $TestModel->save();
		$this->assertFalse(empty($result));

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
		$result = $TestModel->find('first', array('fields' => array('id', 'user_id', 'title', 'body'), 'conditions' => array('Article.id' => 2)));
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
		$this->assertEquals($expected, $result);

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
			)
		);
		$result = $TestModel->create()
				&& $TestModel->save($data, true, array('user_id', 'title', 'published'));
		$this->assertFalse(empty($result));

		$TestModel->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment')
		));
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
		$this->assertEquals($expected, $result);

		$this->loadFixtures('JoinA', 'JoinC', 'JoinAC', 'JoinB', 'JoinAB');
		$TestModel = new JoinA();
		$TestModel->hasBelongsToMany = array('JoinC' => array('unique' => true));
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
		$expected = array(4, 5);
		$this->assertEquals($expected, Hash::extract($result, 'JoinC.{n}.JoinAsJoinC.id'));
		$expected = array('new record', 'new record');
		$this->assertEquals($expected, Hash::extract($result, 'JoinC.{n}.JoinAsJoinC.other'));
	}

/**
 * testSaveHabtmNoPrimaryData method
 *
 * @return void
 */
	public function testSaveHabtmNoPrimaryData() {
		$this->loadFixtures('Article', 'User', 'Comment', 'Tag', 'ArticlesTag');
		$TestModel = new Article();

		$TestModel->unbindModel(array('belongsTo' => array('User'), 'hasMany' => array('Comment')), false);
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
		$this->assertEquals($expected, $result);

		$TestModel->id = 2;
		$data = array('Tag' => array('Tag' => array(2)));
		$result = $TestModel->save($data);

		$this->assertEquals($data['Tag'], $result['Tag']);

		$result = $TestModel->findById(2);
		$expected = array(
			'Article' => array(
				'id' => '2',
				'user_id' => '3',
				'title' => 'Second Article',
				'body' => 'Second Article Body',
				'published' => 'Y',
				'created' => '2007-03-18 10:41:23',
				'updated' => self::date()
			),
			'Tag' => array(
				array(
					'id' => '2',
					'tag' => 'tag2',
					'created' => '2007-03-18 12:24:23',
					'updated' => '2007-03-18 12:26:31'
				)
			)
		);
		$this->assertEquals($expected, $result);

		$this->loadFixtures('Portfolio', 'Item', 'ItemsPortfolio');
		$TestModel = new Portfolio();
		$result = $TestModel->findById(2);
		$expected = array(
			'Portfolio' => array(
				'id' => 2,
				'seller_id' => 1,
				'name' => 'Portfolio 2'
			),
			'Item' => array(
				array(
					'id' => 2,
					'syfile_id' => 2,
					'published' => '',
					'name' => 'Item 2',
					'ItemsPortfolio' => array(
						'id' => 2,
						'item_id' => 2,
						'portfolio_id' => 2
					)
				),
				array(
					'id' => 6,
					'syfile_id' => 6,
					'published' => '',
					'name' => 'Item 6',
					'ItemsPortfolio' => array(
						'id' => 6,
						'item_id' => 6,
						'portfolio_id' => 2
					)
				)
			)
		);
		$this->assertEquals($expected, $result);

		$data = array('Item' => array('Item' => array(1, 2)));
		$TestModel->id = 2;
		$TestModel->save($data);
		$result = $TestModel->findById(2);
		$result['Item'] = Hash::sort($result['Item'], '{n}.id', 'asc');
		$expected = array(
			'Portfolio' => array(
				'id' => 2,
				'seller_id' => 1,
				'name' => 'Portfolio 2'
			),
			'Item' => array(
				array(
					'id' => 1,
					'syfile_id' => 1,
					'published' => '',
					'name' => 'Item 1',
					'ItemsPortfolio' => array(
						'id' => 7,
						'item_id' => 1,
						'portfolio_id' => 2
					)
				),
				array(
					'id' => 2,
					'syfile_id' => 2,
					'published' => '',
					'name' => 'Item 2',
					'ItemsPortfolio' => array(
						'id' => 8,
						'item_id' => 2,
						'portfolio_id' => 2
					)
				)
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveHabtmCustomKeys method
 *
 * @return void
 */
	public function testSaveHabtmCustomKeys() {
		$this->loadFixtures('Story', 'StoriesTag', 'Tag');
		$Story = new Story();

		$data = array(
			'Story' => array('story' => '1'),
			'Tag' => array(
				'Tag' => array(2, 3)
		));
		$result = $Story->set($data);
		$this->assertFalse(empty($result));

		$result = $Story->save();
		$this->assertFalse(empty($result));

		$result = $Story->find('all', array('order' => array('Story.story')));
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
		$this->assertEquals($expected, $result);
	}

/**
 * test that saving habtm records respects conditions set in the 'conditions' key
 * for the association.
 *
 * @return void
 */
	public function testHabtmSaveWithConditionsInAssociation() {
		$this->loadFixtures('JoinThing', 'Something', 'SomethingElse');
		$Something = new Something();
		$Something->unbindModel(array('hasAndBelongsToMany' => array('SomethingElse')), false);

		$Something->bindModel(array(
			'hasAndBelongsToMany' => array(
				'DoomedSomethingElse' => array(
					'className' => 'SomethingElse',
					'joinTable' => 'join_things',
					'conditions' => array('JoinThing.doomed' => true),
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
		$this->assertEquals(1, count($result['DoomedSomethingElse']));

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
		$this->assertFalse(empty($result));

		$result = $Something->read(null, 1);
		$this->assertEquals(2, count($result['NotDoomedSomethingElse']));
		$this->assertEquals(1, count($result['DoomedSomethingElse']));
	}

/**
 * testHabtmSaveKeyResolution method
 *
 * @return void
 */
	public function testHabtmSaveKeyResolution() {
		$this->loadFixtures('Apple', 'Device', 'ThePaperMonkies');
		$ThePaper = new ThePaper();

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
		$this->assertEquals($expected, $result['Monkey']);

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
		$this->assertEquals($expected, $result['Monkey']);

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
		$this->assertEquals($expected, $result['Monkey']);

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
		$this->assertEquals($expected, $result['Monkey']);
	}

/**
 * testCreationOfEmptyRecord method
 *
 * @return void
 */
	public function testCreationOfEmptyRecord() {
		$this->loadFixtures('Author');
		$TestModel = new Author();
		$this->assertEquals(4, $TestModel->find('count'));

		$TestModel->deleteAll(true, false, false);
		$this->assertEquals(0, $TestModel->find('count'));

		$result = $TestModel->save();
		$this->assertTrue(isset($result['Author']['created']));
		$this->assertTrue(isset($result['Author']['updated']));
		$this->assertEquals(1, $TestModel->find('count'));
	}

/**
 * testCreateWithPKFiltering method
 *
 * @return void
 */
	public function testCreateWithPKFiltering() {
		$TestModel = new Article();
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

		$this->assertEquals($expected, $result);
		$this->assertEquals(5, $TestModel->id);

		$result = $TestModel->create($data, true);
		$expected = array(
			'Article' => array(
				'published' => 'N',
				'id' => false,
				'user_id' => 2,
				'title' => 'My article',
				'body' => 'Some text'
		));

		$this->assertEquals($expected, $result);
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

		$this->assertEquals($expected, $result);
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
		$this->assertEquals($expected, $result);
		$this->assertEquals(6, $TestModel->id);

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
		$this->assertEquals($expected, $result);
		$this->assertFalse($TestModel->id);
	}

/**
 * testCreationWithMultipleData method
 *
 * @return void
 */
	public function testCreationWithMultipleData() {
		$this->loadFixtures('Article', 'Comment');
		$Article = new Article();
		$Comment = new Comment();

		$articles = $Article->find('all', array(
			'fields' => array('id', 'title'),
			'recursive' => -1,
			'order' => array('Article.id' => 'ASC')
		));
		$expected = array(
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
		)));
		$this->assertEquals($expected, $articles);

		$comments = $Comment->find('all', array(
			'fields' => array('id', 'article_id', 'user_id', 'comment', 'published'),
			'recursive' => -1,
			'order' => array('Comment.id' => 'ASC')
		));
		$expected = array(
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
		)));
		$this->assertEquals($expected, $comments);

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
		$this->assertFalse(empty($result));

		$result = $Comment->save();
		$this->assertFalse(empty($result));

		$articles = $Article->find('all', array(
			'fields' => array('id', 'title'),
			'recursive' => -1,
			'order' => array('Article.id' => 'ASC')
		));
		$expected = array(
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
		)));
		$this->assertEquals($expected, $articles);

		$comments = $Comment->find('all', array(
			'fields' => array('id', 'article_id', 'user_id', 'comment', 'published'),
			'recursive' => -1,
			'order' => array('Comment.id' => 'ASC')
		));
		$expected = array(
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
		)));
		$this->assertEquals($expected, $comments);
	}

/**
 * testCreationWithMultipleDataSameModel method
 *
 * @return void
 */
	public function testCreationWithMultipleDataSameModel() {
		$this->loadFixtures('Article');
		$Article = new Article();

		$result = $Article->field('title', array('id' => 1));
		$this->assertEquals('First Article', $result);

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
		$this->assertFalse(empty($result));

		$result = $Article->getInsertID();
		$this->assertTrue(!empty($result));

		$result = $Article->field('title', array('id' => 1));
		$this->assertEquals('First Article', $result);

		$articles = $Article->find('all', array(
			'fields' => array('id', 'title'),
			'recursive' => -1,
			'order' => array('Article.id' => 'ASC')
		));
		$expected = array(
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
		)));

		$this->assertEquals($expected, $articles);
	}

/**
 * testCreationWithMultipleDataSameModelManualInstances method
 *
 * @return void
 */
	public function testCreationWithMultipleDataSameModelManualInstances() {
		$this->loadFixtures('PrimaryModel');
		$Primary = new PrimaryModel();

		$result = $Primary->field('primary_name', array('id' => 1));
		$this->assertEquals('Primary Name Existing', $result);

		$data = array(
			'PrimaryModel' => array(
				'primary_name' => 'Primary Name New'
			),
			'SecondaryModel' => array(
				'id' => array(1)
		));

		$Primary->create();
		$result = $Primary->save($data);
		$this->assertFalse(empty($result));

		$result = $Primary->field('primary_name', array('id' => 1));
		$this->assertEquals('Primary Name Existing', $result);

		$result = $Primary->getInsertID();
		$this->assertTrue(!empty($result));

		$result = $Primary->field('primary_name', array('id' => $result));
		$this->assertEquals('Primary Name New', $result);

		$result = $Primary->find('count');
		$this->assertEquals(2, $result);
	}

/**
 * testRecordExists method
 *
 * @return void
 */
	public function testRecordExists() {
		$this->loadFixtures('User');
		$TestModel = new User();

		$this->assertFalse($TestModel->exists());
		$TestModel->read(null, 1);
		$this->assertTrue($TestModel->exists());
		$TestModel->create();
		$this->assertFalse($TestModel->exists());
		$TestModel->id = 4;
		$this->assertTrue($TestModel->exists());

		$TestModel = new TheVoid();
		$this->assertFalse($TestModel->exists());
	}

/**
 * testRecordExistsMissingTable method
 *
 * @expectedException PDOException
 * @return void
 */
	public function testRecordExistsMissingTable() {
		$TestModel = new TheVoid();
		$TestModel->id = 5;
		$TestModel->exists();
	}

/**
 * testUpdateExisting method
 *
 * @return void
 */
	public function testUpdateExisting() {
		$this->loadFixtures('User', 'Article', 'Comment');
		$TestModel = new User();
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
		$this->assertEquals($id, $TestModel->id);

		$result = $TestModel->findById($id);
		$this->assertEquals('updated user', $result['User']['user']);
		$this->assertEquals('some password', $result['User']['password']);

		$Article = new Article();
		$Comment = new Comment();
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
		$this->assertFalse(empty($result));

		$result = $Comment->save($data);
		$this->assertFalse(empty($result));
	}

/**
 * test updating records and saving blank values.
 *
 * @return void
 */
	public function testUpdateSavingBlankValues() {
		$this->loadFixtures('Article');
		$Article = new Article();
		$Article->validate = array();
		$Article->create();
		$result = $Article->save(array(
			'id' => 1,
			'title' => '',
			'body' => ''
		));
		$this->assertTrue((bool)$result);
		$result = $Article->find('first', array('conditions' => array('Article.id' => 1)));
		$this->assertEquals('', $result['Article']['title'], 'Title is not blank');
		$this->assertEquals('', $result['Article']['body'], 'Body is not blank');
	}

/**
 * testUpdateMultiple method
 *
 * @return void
 */
	public function testUpdateMultiple() {
		$this->loadFixtures('Comment', 'Article', 'User', 'CategoryThread');
		$TestModel = new Comment();
		$result = Hash::extract($TestModel->find('all'), '{n}.Comment.user_id');
		$expected = array('2', '4', '1', '1', '1', '2');
		$this->assertEquals($expected, $result);

		$TestModel->updateAll(array('Comment.user_id' => 5), array('Comment.user_id' => 2));
		$result = Hash::combine($TestModel->find('all'), '{n}.Comment.id', '{n}.Comment.user_id');
		$expected = array(1 => 5, 2 => 4, 3 => 1, 4 => 1, 5 => 1, 6 => 5);
		$this->assertEquals($expected, $result);

		$result = $TestModel->updateAll(
			array('Comment.comment' => "'Updated today'"),
			array('Comment.user_id' => 5)
		);
		$this->assertFalse(empty($result));
		$result = Hash::extract(
			$TestModel->find('all', array(
				'conditions' => array(
					'Comment.user_id' => 5
			))),
			'{n}.Comment.comment'
		);
		$expected = array_fill(0, 2, 'Updated today');
		$this->assertEquals($expected, $result);
	}

/**
 * testHabtmUuidWithUuidId method
 *
 * @return void
 */
	public function testHabtmUuidWithUuidId() {
		$this->loadFixtures('Uuidportfolio', 'Uuiditem', 'UuiditemsUuidportfolio', 'UuiditemsUuidportfolioNumericid');
		$TestModel = new Uuidportfolio();

		$data = array('Uuidportfolio' => array('name' => 'Portfolio 3'));
		$data['Uuiditem']['Uuiditem'] = array('483798c8-c7cc-430e-8cf9-4fcc40cf8569');
		$TestModel->create($data);
		$TestModel->save();
		$id = $TestModel->id;
		$result = $TestModel->read(null, $id);
		$this->assertEquals(1, count($result['Uuiditem']));
		$this->assertEquals(36, strlen($result['Uuiditem'][0]['UuiditemsUuidportfolio']['id']));
	}

/**
 * test HABTM saving when join table has no primary key and only 2 columns.
 *
 * @return void
 */
	public function testHabtmSavingWithNoPrimaryKeyUuidJoinTable() {
		$this->loadFixtures('UuidTag', 'Fruit', 'FruitsUuidTag');
		$Fruit = new Fruit();
		$Fruit->FruitsUuidTag->order = null;
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
		$result = $Fruit->save($data);
		$this->assertFalse(empty($result));
	}

/**
 * test HABTM saving when join table has no primary key and only 2 columns, no with model is used.
 *
 * @return void
 */
	public function testHabtmSavingWithNoPrimaryKeyUuidJoinTableNoWith() {
		$this->loadFixtures('UuidTag', 'Fruit', 'FruitsUuidTag');
		$Fruit = new FruitNoWith();
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
		$result = $Fruit->save($data);
		$this->assertFalse(empty($result));
	}

/**
 * testHabtmUuidWithNumericId method
 *
 * @return void
 */
	public function testHabtmUuidWithNumericId() {
		$this->loadFixtures('Uuidportfolio', 'Uuiditem', 'UuiditemsUuidportfolioNumericid');
		$TestModel = new Uuiditem();

		$data = array('Uuiditem' => array('name' => 'Item 7', 'published' => 0));
		$data['Uuidportfolio']['Uuidportfolio'] = array('480af662-eb8c-47d3-886b-230540cf8569');
		$TestModel->create($data);
		$TestModel->save();
		$id = $TestModel->id;
		$result = $TestModel->read(null, $id);
		$this->assertEquals(1, count($result['Uuidportfolio']));
	}

/**
 * testSaveMultipleHabtm method
 *
 * @return void
 */
	public function testSaveMultipleHabtm() {
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

		$this->assertEquals($expected, $result);

		$TestModel->id = 1;
		$data = array(
			'JoinA' => array(
				'id' => '1',
				'name' => 'New name for Join A 1',
				'updated' => self::date()
			),
			'JoinB' => array(
				array(
					'id' => 1,
					'join_b_id' => 2,
					'other' => 'New data for Join A 1 Join B 2',
					'created' => self::date(),
					'updated' => self::date()
			)),
			'JoinC' => array(
				array(
					'id' => 1,
					'join_c_id' => 2,
					'other' => 'New data for Join A 1 Join C 2',
					'created' => self::date(),
					'updated' => self::date()
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
				'updated' => self::date()
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
						'created' => self::date(),
						'updated' => self::date()
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
						'created' => self::date(),
						'updated' => self::date()
		))));

		$this->assertEquals($expected, $result);
	}

/**
 * testSaveAll method
 *
 * @return void
 */
	public function testSaveAll() {
		$this->loadFixtures('Post', 'Author', 'Comment', 'Attachment', 'Article', 'User');
		$TestModel = new Post();

		$result = $TestModel->find('all');
		$this->assertEquals(3, count($result));
		$this->assertFalse(isset($result[3]));

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
				'published' => 'N'
			),
			'Author' => array(
				'id' => '5',
				'user' => 'bob',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf90',
				'test' => 'working'
		));
		$this->assertEquals(self::date(), $result[3]['Post']['created']);
		$this->assertEquals(self::date(), $result[3]['Post']['updated']);
		$this->assertEquals(self::date(), $result[3]['Author']['created']);
		$this->assertEquals(self::date(), $result[3]['Author']['updated']);
		unset($result[3]['Post']['created'], $result[3]['Post']['updated']);
		unset($result[3]['Author']['created'], $result[3]['Author']['updated']);
		$this->assertEquals($expected, $result[3]);
		$this->assertEquals(4, count($result));

		$TestModel->deleteAll(true);
		$this->assertEquals(array(), $TestModel->find('all'));

		// SQLite seems to reset the PK counter when that happens, so we need this to make the tests pass
		$this->db->truncate($TestModel);

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
					'published' => 'N'
			)),
			array(
				'Post' => array(
					'id' => '2',
					'author_id' => '2',
					'title' => 'Multi-record post 2',
					'body' => 'Second multi-record post',
					'published' => 'N'
		)));
		$this->assertEquals(self::date(), $result[0]['Post']['created']);
		$this->assertEquals(self::date(), $result[0]['Post']['updated']);
		$this->assertEquals(self::date(), $result[1]['Post']['created']);
		$this->assertEquals(self::date(), $result[1]['Post']['updated']);
		unset($result[0]['Post']['created'], $result[0]['Post']['updated']);
		unset($result[1]['Post']['created'], $result[1]['Post']['updated']);
		$this->assertEquals($expected, $result);

		$TestModel = new Comment();
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
		$this->assertFalse(empty($result));

		$result = $TestModel->find('all');
		$expected = array(
			'id' => '7',
			'article_id' => '2',
			'user_id' => '2',
			'comment' => 'New comment with attachment',
			'published' => 'Y'
		);
		$this->assertEquals(self::date(), $result[6]['Comment']['created']);
		$this->assertEquals(self::date(), $result[6]['Comment']['updated']);
		unset($result[6]['Comment']['created'], $result[6]['Comment']['updated']);
		$this->assertEquals($expected, $result[6]['Comment']);

		$expected = array(
			'id' => '2',
			'comment_id' => '7',
			'attachment' => 'some_file.tgz'
		);
		$this->assertEquals(self::date(), $result[6]['Attachment']['created']);
		$this->assertEquals(self::date(), $result[6]['Attachment']['updated']);
		unset($result[6]['Attachment']['created'], $result[6]['Attachment']['updated']);
		$this->assertEquals($expected, $result[6]['Attachment']);
	}

/**
 * Test SaveAll with Habtm relations
 *
 * @return void
 */
	public function testSaveAllHabtm() {
		$this->loadFixtures('Article', 'Tag', 'Comment', 'User', 'ArticlesTag');
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
		$Article = new Article();
		$result = $Article->saveAll($data);
		$this->assertFalse(empty($result));

		$result = $Article->read();
		$this->assertEquals(2, count($result['Tag']));
		$this->assertEquals('tag1', $result['Tag'][0]['tag']);
		$this->assertEquals(1, count($result['Comment']));
		$this->assertEquals(1, count($result['Comment'][0]['comment']));
	}

/**
 * Test SaveAll with Habtm relations and extra join table fields
 *
 * @return void
 */
	public function testSaveAllHabtmWithExtraJoinTableFields() {
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

		$Something = new Something();
		$result = $Something->saveAll($data);
		$this->assertFalse(empty($result));
		$result = $Something->read();

		$this->assertEquals(3, count($result['SomethingElse']));
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
 * @return void
 */
	public function testSaveAllHasOne() {
		$model = new Comment();
		$model->deleteAll(true);
		$this->assertEquals(array(), $model->find('all'));

		$model->Attachment->deleteAll(true);
		$this->assertEquals(array(), $model->Attachment->find('all'));

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
		$this->assertEquals($expected, $result);

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
 * @return void
 */
	public function testSaveAllBelongsTo() {
		$model = new Comment();
		$model->deleteAll(true);
		$this->assertEquals(array(), $model->find('all'));

		$model->Article->deleteAll(true);
		$this->assertEquals(array(), $model->Article->find('all'));

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
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveAllHasOneValidation method
 *
 * @return void
 */
	public function testSaveAllHasOneValidation() {
		$model = new Comment();
		$model->deleteAll(true);
		$this->assertEquals(array(), $model->find('all'));

		$model->Attachment->deleteAll(true);
		$this->assertEquals(array(), $model->Attachment->find('all'));

		$model->validate = array('comment' => 'notEmpty');
		$model->Attachment->validate = array('attachment' => 'notEmpty');
		$model->Attachment->bindModel(array('belongsTo' => array('Comment')));

		$result = $model->saveAll(
			array(
				'Comment' => array(
					'comment' => '',
					'article_id' => 1,
					'user_id' => 1
				),
				'Attachment' => array('attachment' => '')
			),
			array('validate' => 'first')
		);
		$this->assertEquals(false, $result);
		$expected = array(
			'comment' => array('This field cannot be left blank'),
			'Attachment' => array(
				'attachment' => array('This field cannot be left blank')
			)
		);
		$this->assertEquals($expected, $model->validationErrors);
		$this->assertEquals($expected['Attachment'], $model->Attachment->validationErrors);
	}

/**
 * testSaveAllAtomic method
 *
 * @return void
 */
	public function testSaveAllAtomic() {
		$this->loadFixtures('Article', 'User', 'Comment');
		$TestModel = new Article();

		$result = $TestModel->saveAll(array(
			'Article' => array(
				'title' => 'Post with Author',
				'body' => 'This post will be saved with an author',
				'user_id' => 2
			),
			'Comment' => array(
				array('comment' => 'First new comment', 'user_id' => 2))
		), array('atomic' => false));

		$this->assertSame($result, array('Article' => true, 'Comment' => array(true)));

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
		$this->assertSame($result, array(true, true, true));

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
		), array('validate' => true, 'atomic' => false));
		$this->assertSame($result, array('Article' => true, 'Comment' => array(true, true)));

		$TestModel->validate = array(
			'title' => 'notEmpty',
			'author_id' => 'numeric'
		);
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
		), array('validate' => true, 'atomic' => false));
		$this->assertSame(array(true, false), $result);
	}

/**
 * testSaveAllDeepAssociated method
 *
 * @return void
 */
	public function testSaveAllDeepAssociated() {
		$this->loadFixtures('Article', 'Comment', 'User', 'Attachment');
		$TestModel = new Article();
		$TestModel->hasMany['Comment']['order'] = array('Comment.created' => 'ASC');
		$TestModel->hasAndBelongsToMany = array();

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'User' => array('user' => 'newuser', 'password' => 'newuserpass')),
				array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
			)
		), array('deep' => true));
		$this->assertTrue($result);

		$result = $TestModel->findById(2);
		$expected = array(
			'First Comment for Second Article',
			'Second Comment for Second Article',
			'First new comment',
			'Second new comment'
		);
		$result = Hash::extract(Hash::sort($result['Comment'], '{n}.id', 'ASC'), '{n}.comment');
		$this->assertEquals($expected, $result);

		$result = $TestModel->Comment->User->field('id', array('user' => 'newuser', 'password' => 'newuserpass'));
		$this->assertEquals(5, $result);
		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'Third new comment', 'published' => 'Y', 'user_id' => 5),
				array('comment' => 'Fourth new comment', 'published' => 'Y', 'user_id' => 2, 'Attachment' => array('attachment' => 'deepsaved'))
			)
		), array('deep' => true));
		$this->assertTrue($result);

		$result = $TestModel->findById(2);
		$expected = array(
			'First Comment for Second Article',
			'Second Comment for Second Article',
			'First new comment',
			'Second new comment',
			'Third new comment',
			'Fourth new comment'
		);
		$result = Hash::extract(Hash::sort($result['Comment'], '{n}.id', 'ASC'), '{n}.comment');
		$this->assertEquals($expected, $result);

		$result = $TestModel->Comment->Attachment->field('id', array('attachment' => 'deepsaved'));
		$this->assertEquals(2, $result);
		$data = array(
			'Attachment' => array(
				'attachment' => 'deepsave insert',
			),
			'Comment' => array(
				'comment' => 'First comment deepsave insert',
				'published' => 'Y',
				'user_id' => 5,
				'Article' => array(
					'title' => 'First Article deepsave insert',
					'body' => 'First Article Body deepsave insert',
					'User' => array(
						'user' => '',
						'password' => 'magic'
					),
				),
			)
		);

		$TestModel->Comment->Attachment->create();
		$result = $TestModel->Comment->Attachment->saveAll($data, array('deep' => true));
		$this->assertFalse($result);

		$expected = array('User' => array('user' => array('This field cannot be left blank')));
		$this->assertEquals($expected, $TestModel->validationErrors);

		$data['Comment']['Article']['User']['user'] = 'deepsave';
		$TestModel->Comment->Attachment->create();
		$result = $TestModel->Comment->Attachment->saveAll($data, array('deep' => true));
		$this->assertTrue($result);

		$result = $TestModel->Comment->Attachment->findById($TestModel->Comment->Attachment->id);
		$expected = array(
			'Attachment' => array(
				'id' => '3',
				'comment_id' => '11',
				'attachment' => 'deepsave insert',
			),
			'Comment' => array(
				'id' => '11',
				'article_id' => '4',
				'user_id' => '5',
				'comment' => 'First comment deepsave insert',
				'published' => 'Y',
			)
		);
		unset($result['Attachment']['created'], $result['Attachment']['updated']);
		$this->assertEquals($expected['Attachment'], $result['Attachment']);

		unset($result['Comment']['created'], $result['Comment']['updated']);
		$this->assertEquals($expected['Comment'], $result['Comment']);

		$result = $TestModel->findById($result['Comment']['article_id']);
		$expected = array(
			'Article' => array(
				'id' => '4',
				'user_id' => '6',
				'title' => 'First Article deepsave insert',
				'body' => 'First Article Body deepsave insert',
				'published' => 'N',
			),
			'User' => array(
				'id' => '6',
				'user' => 'deepsave',
				'password' => 'magic',
			),
			'Comment' => array(
				array(
					'id' => '11',
					'article_id' => '4',
					'user_id' => '5',
					'comment' => 'First comment deepsave insert',
					'published' => 'Y',
				)
			)
		);
		unset(
			$result['Article']['created'], $result['Article']['updated'],
			$result['User']['created'], $result['User']['updated'],
			$result['Comment'][0]['created'], $result['Comment'][0]['updated']
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveAllDeepMany
 * tests the validate methods with deeper recursive data
 *
 * @return void
 */
	public function testSaveAllDeepMany() {
		$this->loadFixtures('Article', 'Comment', 'User', 'Attachment');
		$TestModel = new Article();
		$TestModel->hasMany['Comment']['order'] = array('Comment.created' => 'ASC');
		$TestModel->hasAndBelongsToMany = array();

		$data = array(
			array(
				'Article' => array('id' => 1),
				'Comment' => array(
					array('comment' => 'First comment deepsaved article 1', 'published' => 'Y', 'User' => array('user' => 'savemany', 'password' => 'manysaved')),
					array('comment' => 'Second comment deepsaved article 1', 'published' => 'Y', 'user_id' => 2)
				)
			),
			array(
				'Article' => array('id' => 2),
				'Comment' => array(
					array('comment' => 'First comment deepsaved article 2', 'published' => 'Y', 'User' => array('user' => 'savemore', 'password' => 'moresaved')),
					array('comment' => 'Second comment deepsaved article 2', 'published' => 'Y', 'user_id' => 2)
				)
			)
		);
		$result = $TestModel->saveAll($data, array('deep' => true));
		$this->assertTrue($result);

		$data = array(
			array(
				'id' => 1, 'body' => '',
				'Comment' => array(
					array('comment' => '', 'published' => 'Y', 'User' => array('user' => '', 'password' => 'manysaved')),
					array('comment' => 'Second comment deepsaved article 1', 'published' => 'Y', 'user_id' => 2)
				)
			),
			array(
				'Article' => array('id' => 2),
				'Comment' => array(
					array('comment' => 'First comment deepsaved article 2', 'published' => 'Y', 'User' => array('user' => 'savemore', 'password' => '')),
					array('comment' => '', 'published' => 'Y', 'user_id' => 2)
				)
			)
		);
		$TestModel->Comment->validate['comment'] = 'notEmpty';
		$result = $TestModel->saveAll($data, array('deep' => true));
		$this->assertFalse($result);

		$expected = array(
			0 => array(
				'body' => array('This field cannot be left blank'),
				'Comment' => array(
					0 => array(
						'comment' => array('This field cannot be left blank'),
						'User' => array(
							'user' => array('This field cannot be left blank')
						)
					)
				)
			),
			1 => array(
				'Comment' => array(
					0 => array(
						'User' => array(
							'password' => array('This field cannot be left blank')
						)
					),
					1 => array(
						'comment' => array('This field cannot be left blank')
					)
				)
			)
		);
		$result = $TestModel->validationErrors;
		$this->assertSame($expected, $result);
	}
/**
 * testSaveAllDeepValidateOnly
 * tests the validate methods with deeper recursive data
 *
 * @return void
 */
	public function testSaveAllDeepValidateOnly() {
		$this->loadFixtures('Article', 'Comment', 'User', 'Attachment');
		$TestModel = new Article();
		$TestModel->hasMany['Comment']['order'] = array('Comment.created' => 'ASC');
		$TestModel->hasAndBelongsToMany = array();
		$TestModel->Comment->Attachment->validate['attachment'] = 'notEmpty';
		$TestModel->Comment->validate['comment'] = 'notEmpty';

		$result = $TestModel->saveAll(
			array(
				'Article' => array('id' => 2),
				'Comment' => array(
					array('comment' => 'First new comment', 'published' => 'Y', 'User' => array('user' => 'newuser', 'password' => 'newuserpass')),
					array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
				)
			),
			array('validate' => 'only', 'deep' => true)
		);
		$this->assertTrue($result);

		$result = $TestModel->saveAll(
			array(
				'Article' => array('id' => 2),
				'Comment' => array(
					array('comment' => 'First new comment', 'published' => 'Y', 'User' => array('user' => '', 'password' => 'newuserpass')),
					array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
				)
			),
			array('validate' => 'only', 'deep' => true)
		);
		$this->assertFalse($result);

		$result = $TestModel->saveAll(
			array(
				'Article' => array('id' => 2),
				'Comment' => array(
					array('comment' => 'First new comment', 'published' => 'Y', 'User' => array('user' => 'newuser', 'password' => 'newuserpass')),
					array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
				)
			),
			array('validate' => 'only', 'atomic' => false, 'deep' => true)
		);
		$expected = array(
			'Article' => true,
			'Comment' => array(
				true,
				true
			)
		);
		$this->assertSame($expected, $result);

		$result = $TestModel->saveAll(
			array(
				'Article' => array('id' => 2),
				'Comment' => array(
					array('comment' => 'First new comment', 'published' => 'Y', 'User' => array('user' => '', 'password' => 'newuserpass')),
					array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
				)
			),
			array('validate' => 'only', 'atomic' => false, 'deep' => true)
		);
		$expected = array(
			'Article' => true,
			'Comment' => array(
				false,
				true
			)
		);
		$this->assertSame($expected, $result);

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'Third new comment', 'published' => 'Y', 'user_id' => 5),
				array('comment' => 'Fourth new comment', 'published' => 'Y', 'user_id' => 2, 'Attachment' => array('attachment' => 'deepsaved'))
			)
		),
		array('validate' => 'only', 'deep' => true)
		);
		$this->assertTrue($result);

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'Third new comment', 'published' => 'Y', 'user_id' => 5),
				array('comment' => 'Fourth new comment', 'published' => 'Y', 'user_id' => 2, 'Attachment' => array('attachment' => ''))
			)
		),
		array('validate' => 'only', 'deep' => true)
		);
		$this->assertFalse($result);

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'Third new comment', 'published' => 'Y', 'user_id' => 5),
				array('comment' => 'Fourth new comment', 'published' => 'Y', 'user_id' => 2, 'Attachment' => array('attachment' => 'deepsave'))
			)
		),
		array('validate' => 'only', 'atomic' => false, 'deep' => true)
		);
		$expected = array(
			'Article' => true,
			'Comment' => array(
				true,
				true
			)
		);
		$this->assertSame($expected, $result);

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'Third new comment', 'published' => 'Y', 'user_id' => 5),
				array('comment' => 'Fourth new comment', 'published' => 'Y', 'user_id' => 2, 'Attachment' => array('attachment' => ''))
			)
		),
		array('validate' => 'only', 'atomic' => false, 'deep' => true)
		);
		$expected = array(
			'Article' => true,
			'Comment' => array(
				true,
				false
			)
		);
		$this->assertSame($expected, $result);

		$expected = array(
			'Comment' => array(
				1 => array(
					'Attachment' => array(
						'attachment' => array('This field cannot be left blank')
					)
				)
			)
		);
		$result = $TestModel->validationErrors;
		$this->assertSame($expected, $result);

		$data = array(
			'Attachment' => array(
				'attachment' => 'deepsave insert',
			),
			'Comment' => array(
				'comment' => 'First comment deepsave insert',
				'published' => 'Y',
				'user_id' => 5,
				'Article' => array(
					'title' => 'First Article deepsave insert',
					'body' => 'First Article Body deepsave insert',
					'User' => array(
						'user' => 'deepsave',
						'password' => 'magic'
					),
				),
			)
		);

		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'deep' => true));
		$this->assertTrue($result);

		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => true));
		$expected = array(
			'Attachment' => true,
			'Comment' => true
		);
		$this->assertSame($expected, $result);

		$data = array(
			'Attachment' => array(
				'attachment' => 'deepsave insert',
			),
			'Comment' => array(
				'comment' => 'First comment deepsave insert',
				'published' => 'Y',
				'user_id' => 5,
				'Article' => array(
					'title' => 'First Article deepsave insert',
					'body' => 'First Article Body deepsave insert',
					'User' => array(
						'user' => '',
						'password' => 'magic'
					),
				),
			)
		);

		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'deep' => true));
		$this->assertFalse($result);

		$result = $TestModel->Comment->Attachment->validationErrors;
		$expected = array(
			'Comment' => array(
				'Article' => array(
					'User' => array(
						'user' => array('This field cannot be left blank')
					)
				)
			)
		);
		$this->assertSame($expected, $result);

		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => true));
		$expected = array(
			'Attachment' => true,
			'Comment' => false
		);
		$this->assertEquals($expected, $result);

		$data['Comment']['Article']['body'] = '';
		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'deep' => true));
		$this->assertFalse($result);

		$result = $TestModel->Comment->Attachment->validationErrors;
		$expected = array(
			'Comment' => array(
				'Article' => array(
					'body' => array('This field cannot be left blank'),
					'User' => array(
						'user' => array('This field cannot be left blank')
					)
				)
			)
		);
		$this->assertSame($expected, $result);

		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => true));
		$expected = array(
			'Attachment' => true,
			'Comment' => false
		);
		$this->assertEquals($expected, $result);

		$data['Comment']['comment'] = '';
		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'deep' => true));
		$this->assertFalse($result);

		$result = $TestModel->Comment->Attachment->validationErrors;
		$expected = array(
			'Comment' => array(
				'comment' => array('This field cannot be left blank'),
				'Article' => array(
					'body' => array('This field cannot be left blank'),
					'User' => array(
						'user' => array('This field cannot be left blank')
					)
				)
			)
		);
		$this->assertSame($expected, $result);

		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => true));
		$expected = array(
			'Attachment' => true,
			'Comment' => false
		);
		$this->assertEquals($expected, $result);

		$data['Attachment']['attachment'] = '';
		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'deep' => true));
		$this->assertFalse($result);

		$result = $TestModel->Comment->Attachment->validationErrors;
		$expected = array(
			'attachment' => array('This field cannot be left blank'),
			'Comment' => array(
				'comment' => array('This field cannot be left blank'),
				'Article' => array(
					'body' => array('This field cannot be left blank'),
					'User' => array(
						'user' => array('This field cannot be left blank')
					)
				)
			)
		);
		$this->assertSame($expected, $result);

		$result = $TestModel->Comment->validationErrors;
		$expected = array(
			'comment' => array('This field cannot be left blank'),
			'Article' => array(
				'body' => array('This field cannot be left blank'),
				'User' => array(
					'user' => array('This field cannot be left blank')
				)
			)
		);
		$this->assertSame($expected, $result);

		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => true));
		$expected = array(
			'Attachment' => false,
			'Comment' => false
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveAllNotDeepAssociated method
 * test that only directly associated data gets saved
 *
 * @return void
 */
	public function testSaveAllNotDeepAssociated() {
		$this->loadFixtures('Article', 'Comment', 'User', 'Attachment');
		$TestModel = new Article();
		$TestModel->hasMany['Comment']['order'] = array('Comment.created' => 'ASC');
		$TestModel->hasAndBelongsToMany = array();

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array(
					'comment' => 'First new comment', 'published' => 'Y', 'user_id' => 2,
					'User' => array('user' => 'newuser', 'password' => 'newuserpass')
				),
				array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
			)
		), array('deep' => false));
		$this->assertTrue($result);

		$result = $TestModel->Comment->User->field('id', array('user' => 'newuser', 'password' => 'newuserpass'));
		$this->assertFalse($result);

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'Third new comment', 'published' => 'Y', 'user_id' => 4),
				array('comment' => 'Fourth new comment', 'published' => 'Y', 'user_id' => 2, 'Attachment' => array('attachment' => 'deepsaved'))
			)
		), array('deep' => false));
		$this->assertTrue($result);

		$result = $TestModel->Comment->Attachment->field('id', array('attachment' => 'deepsaved'));
		$this->assertFalse($result);

		$data = array(
			'Attachment' => array(
				'attachment' => 'deepsave insert',
			),
			'Comment' => array(
				'comment' => 'First comment deepsave insert',
				'published' => 'Y',
				'user_id' => 4,
				'article_id' => 1,
				'Article' => array(
					'title' => 'First Article deepsave insert',
					'body' => 'First Article Body deepsave insert',
					'User' => array(
						'user' => 'deepsave',
						'password' => 'magic'
					),
				),
			)
		);
		$expected = $TestModel->User->find('count');

		$TestModel->Comment->Attachment->create();
		$result = $TestModel->Comment->Attachment->saveAll($data, array('deep' => false));
		$this->assertTrue($result);

		$result = $TestModel->User->find('count');
		$this->assertEquals($expected, $result);

		$result = $TestModel->Comment->Attachment->findById($TestModel->Comment->Attachment->id);
		$expected = array(
			'Attachment' => array(
				'id' => '2',
				'comment_id' => '11',
				'attachment' => 'deepsave insert',
			),
			'Comment' => array(
				'id' => '11',
				'article_id' => 1,
				'user_id' => '4',
				'comment' => 'First comment deepsave insert',
				'published' => 'Y',
			)
		);
		unset($result['Attachment']['created'], $result['Attachment']['updated']);
		$this->assertEquals($expected['Attachment'], $result['Attachment']);

		unset($result['Comment']['created'], $result['Comment']['updated']);
		$this->assertEquals($expected['Comment'], $result['Comment']);
	}

/**
 * testSaveAllNotDeepMany
 * tests the save methods to not save deeper recursive data
 *
 * @return void
 */
	public function testSaveAllNotDeepMany() {
		$this->loadFixtures('Article', 'Comment', 'User', 'Attachment');
		$TestModel = new Article();
		$TestModel->hasMany['Comment']['order'] = array('Comment.created' => 'ASC');
		$TestModel->hasAndBelongsToMany = array();

		$data = array(
			array(
				'id' => 1,
				'body' => '',
				'Comment' => array(
					array('comment' => '', 'published' => 'Y', 'User' => array('user' => '', 'password' => 'manysaved')),
					array('comment' => 'Second comment deepsaved article 1', 'published' => 'Y', 'user_id' => 2)
				)
			),
			array(
				'Article' => array('id' => 2),
				'Comment' => array(
					array('comment' => 'First comment deepsaved article 2', 'published' => 'Y', 'User' => array('user' => 'savemore', 'password' => '')),
					array('comment' => '', 'published' => 'Y', 'user_id' => 2)
				)
			)
		);
		$TestModel->Comment->validate['comment'] = 'notEmpty';
		$result = $TestModel->saveAll($data, array('deep' => false));
		$this->assertFalse($result);

		$expected = array(
			0 => array(
				'body' => array('This field cannot be left blank')
			)
		);
		$result = $TestModel->validationErrors;
		$this->assertSame($expected, $result);

		$data = array(
			array(
				'Article' => array('id' => 1, 'body' => 'Ignore invalid comment'),
				'Comment' => array(
					array('comment' => '', 'published' => 'Y', 'user_id' => 2)
				)
			),
			array(
				'Article' => array('id' => 2),
				'Comment' => array(
					array('comment' => '', 'published' => 'Y', 'user_id' => 2)
				)
			)
		);
		$result = $TestModel->saveAll($data, array('deep' => false));
		$this->assertTrue($result);
	}
/**
 * testSaveAllNotDeepValidateOnly
 * tests the validate methods to not validate deeper recursive data
 *
 * @return void
 */
	public function testSaveAllNotDeepValidateOnly() {
		$this->loadFixtures('Article', 'Comment', 'User', 'Attachment');
		$TestModel = new Article();
		$TestModel->hasMany['Comment']['order'] = array('Comment.created' => 'ASC');
		$TestModel->hasAndBelongsToMany = array();
		$TestModel->Comment->Attachment->validate['attachment'] = 'notEmpty';
		$TestModel->Comment->validate['comment'] = 'notEmpty';

		$result = $TestModel->saveAll(
			array(
				'Article' => array('id' => 2, 'body' => ''),
				'Comment' => array(
					array('comment' => 'First new comment', 'published' => 'Y', 'User' => array('user' => '', 'password' => 'newuserpass')),
					array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
				)
			),
			array('validate' => 'only', 'deep' => false)
		);
		$this->assertFalse($result);

		$expected = array('body' => array('This field cannot be left blank'));
		$result = $TestModel->validationErrors;
		$this->assertSame($expected, $result);

		$result = $TestModel->saveAll(
			array(
				'Article' => array('id' => 2, 'body' => 'Ignore invalid user data'),
				'Comment' => array(
					array('comment' => 'First new comment', 'published' => 'Y', 'User' => array('user' => '', 'password' => 'newuserpass')),
					array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
				)
			),
			array('validate' => 'only', 'deep' => false)
		);
		$this->assertTrue($result);

		$result = $TestModel->saveAll(
			array(
				'Article' => array('id' => 2, 'body' => 'Ignore invalid user data'),
				'Comment' => array(
					array('comment' => 'First new comment', 'published' => 'Y', 'User' => array('user' => '', 'password' => 'newuserpass')),
					array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
				)
			),
			array('validate' => 'only', 'atomic' => false, 'deep' => false)
		);
		$expected = array(
			'Article' => true,
			'Comment' => array(
				true,
				true
			)
		);
		$this->assertSame($expected, $result);

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2, 'body' => 'Ignore invalid attachment data'),
			'Comment' => array(
				array('comment' => 'Third new comment', 'published' => 'Y', 'user_id' => 5),
				array('comment' => 'Fourth new comment', 'published' => 'Y', 'user_id' => 2, 'Attachment' => array('attachment' => ''))
			)
		),
		array('validate' => 'only', 'deep' => false)
		);
		$this->assertTrue($result);

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2, 'body' => 'Ignore invalid attachment data'),
			'Comment' => array(
				array('comment' => 'Third new comment', 'published' => 'Y', 'user_id' => 5),
				array('comment' => 'Fourth new comment', 'published' => 'Y', 'user_id' => 2, 'Attachment' => array('attachment' => ''))
			)
		),
		array('validate' => 'only', 'atomic' => false, 'deep' => false)
		);
		$expected = array(
			'Article' => true,
			'Comment' => array(
				true,
				true
			)
		);
		$this->assertSame($expected, $result);

		$expected = array();
		$result = $TestModel->validationErrors;
		$this->assertSame($expected, $result);

		$data = array(
			'Attachment' => array(
				'attachment' => 'deepsave insert',
			),
			'Comment' => array(
				'comment' => 'First comment deepsave insert',
				'published' => 'Y',
				'user_id' => 5,
				'Article' => array(
					'title' => 'First Article deepsave insert ignored',
					'body' => 'First Article Body deepsave insert',
					'User' => array(
						'user' => '',
						'password' => 'magic'
					),
				),
			)
		);

		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'deep' => false));
		$this->assertTrue($result);

		$result = $TestModel->Comment->Attachment->validationErrors;
		$expected = array();
		$this->assertSame($expected, $result);

		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => false));
		$expected = array(
			'Attachment' => true,
			'Comment' => true
		);
		$this->assertEquals($expected, $result);

		$data['Comment']['Article']['body'] = '';
		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'deep' => false));
		$this->assertTrue($result);

		$result = $TestModel->Comment->Attachment->validationErrors;
		$expected = array();
		$this->assertSame($expected, $result);

		$result = $TestModel->Comment->Attachment->saveAll($data, array('validate' => 'only', 'atomic' => false, 'deep' => false));
		$expected = array(
			'Attachment' => true,
			'Comment' => true
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveAllHasMany method
 *
 * @return void
 */
	public function testSaveAllHasMany() {
		$this->loadFixtures('Article', 'Comment');
		$TestModel = new Article();
		$TestModel->hasMany['Comment']['order'] = array('Comment.created' => 'ASC');
		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = array();

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'user_id' => 1),
				array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
			)
		));
		$this->assertFalse(empty($result));

		$result = $TestModel->findById(2);
		$expected = array(
			'First Comment for Second Article',
			'Second Comment for Second Article',
			'First new comment',
			'Second new comment'
		);
		$result = Hash::extract(Hash::sort($result['Comment'], '{n}.id', 'ASC'), '{n}.comment');
		$this->assertEquals($expected, $result);

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
		$this->assertFalse(empty($result));

		$result = $TestModel->findById(2);
		$expected = array(
			'First Comment for Second Article',
			'Second Comment for Second Article',
			'First new comment',
			'Second new comment',
			'Third new comment'
		);
		$result = Hash::extract(Hash::sort($result['Comment'], '{n}.id', 'ASC'), '{n}.comment');
		$this->assertEquals($expected, $result);

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
		$this->assertEquals(array('Article' => false), $result);

		$result = $TestModel->findById(2);
		$expected = array(
			'First Comment for Second Article',
			'Second Comment for Second Article',
			'First new comment',
			'Second new comment',
			'Third new comment'
		);
		$result = Hash::extract(Hash::sort($result['Comment'], '{n}.id', 'ASC'), '{n}.comment');
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveAllHasManyValidation method
 *
 * @return void
 */
	public function testSaveAllHasManyValidation() {
		$this->loadFixtures('Article', 'Comment');
		$TestModel = new Article();
		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = array();
		$TestModel->Comment->validate = array('comment' => 'notEmpty');

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => '', 'published' => 'Y', 'user_id' => 1),
			)
		), array('validate' => true));
		$this->assertFalse($result);

		$expected = array('Comment' => array(
			array('comment' => array('This field cannot be left blank'))
		));
		$this->assertEquals($expected, $TestModel->validationErrors);
		$expected = array(
			array('comment' => array('This field cannot be left blank'))
		);
		$this->assertEquals($expected, $TestModel->Comment->validationErrors);

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array(
					'comment' => '',
					'published' => 'Y',
					'user_id' => 1
			))
		), array('validate' => 'first'));
		$this->assertFalse($result);
	}

/**
 * test saveAll with transactions and ensure there is no missing rollback.
 *
 * @return void
 */
	public function testSaveAllManyRowsTransactionNoRollback() {
		$this->loadFixtures('Post');

		$this->getMock('DboSource', array('connect', 'rollback', 'describe'), array(), 'MockTransactionDboSource');
		$db = ConnectionManager::create('mock_transaction', array(
			'datasource' => 'MockTransactionDboSource',
		));

		$db->expects($this->once())
			->method('describe')
			->will($this->returnValue(array()));
		$db->expects($this->once())->method('rollback');

		$Post = new Post('mock_transaction');

		$Post->validate = array(
			'title' => array('rule' => array('notEmpty'))
		);

		$data = array(
			array('author_id' => 1, 'title' => 'New Fourth Post'),
			array('author_id' => 1, 'title' => '')
		);
		$Post->saveAll($data, array('atomic' => true));
	}

/**
 * test saveAll with transactions and ensure there is no missing rollback.
 *
 * @return void
 */
	public function testSaveAllAssociatedTransactionNoRollback() {
		$testDb = ConnectionManager::getDataSource('test');

		$this->getMock(
			'DboSource',
			array('connect', 'rollback', 'describe', 'create', 'update', 'begin'),
			array(),
			'MockTransactionAssociatedDboSource'
		);
		$db = ConnectionManager::create('mock_transaction_assoc', array(
			'datasource' => 'MockTransactionAssociatedDboSource',
		));
		$this->mockObjects[] = $db;
		$db->columns = $testDb->columns;

		$db->expects($this->once())->method('rollback');
		$db->expects($this->any())->method('describe')
			->will($this->returnValue(array(
				'id' => array('type' => 'integer', 'length' => 11),
				'title' => array('type' => 'string'),
				'body' => array('type' => 'text'),
				'published' => array('type' => 'string')
			)));

		$Post = new Post();
		$Post->useDbConfig = 'mock_transaction_assoc';
		$Post->Author->useDbConfig = 'mock_transaction_assoc';

		$Post->Author->validate = array(
			'user' => array('rule' => array('notEmpty'))
		);

		$data = array(
			'Post' => array(
				'title' => 'New post',
				'body' => 'Content',
				'published' => 'Y'
			),
			'Author' => array(
				'user' => '',
				'password' => "sekret"
			)
		);
		$Post->saveAll($data, array('validate' => true));
	}

/**
 * test saveAll with nested saveAll call.
 *
 * @return void
 */
	public function testSaveAllNestedSaveAll() {
		$this->loadFixtures('Sample');
		$TransactionTestModel = new TransactionTestModel();

		$data = array(
			array('apple_id' => 1, 'name' => 'sample5'),
		);

		$this->assertTrue($TransactionTestModel->saveAll($data, array('atomic' => true)));
	}

/**
 * testSaveAllTransaction method
 *
 * @return void
 */
	public function testSaveAllTransaction() {
		$this->loadFixtures('Post', 'Author', 'Comment', 'Attachment');
		$TestModel = new Post();

		$TestModel->validate = array('title' => 'notEmpty');
		$data = array(
			array('author_id' => 1, 'title' => 'New Fourth Post'),
			array('author_id' => 1, 'title' => 'New Fifth Post'),
			array('author_id' => 1, 'title' => '')
		);
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
					'created' => self::date(),
					'updated' => self::date()
			));

			$expected[] = array(
				'Post' => array(
					'id' => '5',
					'author_id' => 1,
					'title' => 'New Fifth Post',
					'body' => null,
					'published' => 'N',
					'created' => self::date(),
					'updated' => self::date()
			));

			$this->assertEquals($expected, $result);
			// Skip the rest of the transactional tests
			return;
		}

		$this->assertEquals($expected, $result);

		$data = array(
			array('author_id' => 1, 'title' => 'New Fourth Post'),
			array('author_id' => 1, 'title' => ''),
			array('author_id' => 1, 'title' => 'New Sixth Post')
		);
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
					'created' => self::date(),
					'updated' => self::date()
			));

			$expected[] = array(
				'Post' => array(
					'id' => '5',
					'author_id' => 1,
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'N',
					'created' => self::date(),
					'updated' => self::date()
			));
		}
		$this->assertEquals($expected, $result);

		$TestModel->validate = array('title' => 'notEmpty');
		$data = array(
			array('author_id' => 1, 'title' => 'New Fourth Post'),
			array('author_id' => 1, 'title' => 'New Fifth Post'),
			array('author_id' => 1, 'title' => 'New Sixth Post')
		);
		$this->assertTrue($TestModel->saveAll($data));

		$result = $TestModel->find('all', array(
			'recursive' => -1,
			'fields' => array('author_id', 'title', 'body', 'published'),
			'order' => array('Post.created' => 'ASC')
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
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveAllValidation method
 *
 * @return void
 */
	public function testSaveAllValidation() {
		$this->loadFixtures('Post', 'Author', 'Comment', 'Attachment');
		$TestModel = new Post();

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
		$expected = array(
			array(
				'Post' => array(
					'id' => '1',
					'author_id' => '1',
					'title' => 'Baleeted First Post',
					'body' => 'Baleeted!',
					'published' => 'N',
					'created' => '2007-03-18 10:39:23'
			)),
			array(
				'Post' => array(
					'id' => '2',
					'author_id' => '3',
					'title' => 'Just update the title',
					'body' => 'Second Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23'
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
					'published' => 'N'
		)));
		$this->assertEquals(self::date(), $result[0]['Post']['updated']);
		$this->assertEquals(self::date(), $result[1]['Post']['updated']);
		$this->assertEquals(self::date(), $result[3]['Post']['created']);
		$this->assertEquals(self::date(), $result[3]['Post']['updated']);
		unset($result[0]['Post']['updated'], $result[1]['Post']['updated']);
		unset($result[3]['Post']['created'], $result[3]['Post']['updated']);
		$this->assertEquals($expected, $result);

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
		$this->assertFalse($result);

		$result = $TestModel->find('all', array('recursive' => -1, 'order' => 'Post.id ASC'));
		$errors = array(1 => array('title' => array('This field cannot be left blank')));
		$transactionWorked = Set::matches('/Post[1][title=Baleeted First Post]', $result);
		if (!$transactionWorked) {
			$this->assertTrue(Set::matches('/Post[1][title=Un-Baleeted First Post]', $result));
			$this->assertTrue(Set::matches('/Post[2][title=Just update the title]', $result));
		}

		$this->assertEquals($errors, $TestModel->validationErrors);

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
		$result = $TestModel->saveAll($data, array('validate' => true, 'atomic' => false));
		$this->assertEquals(array(true, false), $result);
		$result = $TestModel->find('all', array('recursive' => -1, 'order' => 'Post.id ASC'));
		$errors = array(1 => array('title' => array('This field cannot be left blank')));
		$expected = array(
			array(
				'Post' => array(
					'id' => '1',
					'author_id' => '1',
					'title' => 'Un-Baleeted First Post',
					'body' => 'Not Baleeted!',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23'
				)
			),
			array(
				'Post' => array(
					'id' => '2',
					'author_id' => '3',
					'title' => 'Just update the title',
					'body' => 'Second Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23'
				)
			),
			array(
				'Post' => array(
					'id' => '3',
					'author_id' => '1',
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				)
			),
			array(
				'Post' => array(
					'id' => '4',
					'author_id' => '2',
					'title' => 'Creating a fourth post',
					'body' => 'Fourth post body',
					'published' => 'N'
				)
			)
		);

		$this->assertEquals(self::date(), $result[0]['Post']['updated']);
		$this->assertEquals(self::date(), $result[1]['Post']['updated']);
		$this->assertEquals(self::date(), $result[3]['Post']['updated']);
		$this->assertEquals(self::date(), $result[3]['Post']['created']);
		unset(
			$result[0]['Post']['updated'], $result[1]['Post']['updated'],
			$result[3]['Post']['updated'], $result[3]['Post']['created']
		);
		$this->assertEquals($expected, $result);
		$this->assertEquals($errors, $TestModel->validationErrors);

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
		unset(
			$result[0]['Post']['updated'], $result[1]['Post']['updated'],
			$result[3]['Post']['updated'], $result[3]['Post']['created']
		);
		$this->assertEquals($expected, $result);
		$this->assertEquals($errors, $TestModel->validationErrors);
	}

/**
 * testSaveAllValidationOnly method
 *
 * @return void
 */
	public function testSaveAllValidationOnly() {
		$this->loadFixtures('Comment', 'Attachment');
		$TestModel = new Comment();
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

		$TestModel = new Article();
		$TestModel->validate = array('title' => 'notEmpty');
		$result = $TestModel->saveAll(
			array(
				0 => array('title' => ''),
				1 => array('title' => 'title 1'),
				2 => array('title' => 'title 2'),
			),
			array('validate' => 'only')
		);
		$this->assertFalse($result);
		$expected = array(
			0 => array('title' => array('This field cannot be left blank')),
		);
		$this->assertEquals($expected, $TestModel->validationErrors);

		$result = $TestModel->saveAll(
			array(
				0 => array('title' => 'title 0'),
				1 => array('title' => ''),
				2 => array('title' => 'title 2'),
			),
			array('validate' => 'only')
		);
		$this->assertFalse($result);
		$expected = array(
			1 => array('title' => array('This field cannot be left blank')),
		);
		$this->assertEquals($expected, $TestModel->validationErrors);
	}

/**
 * testSaveAllValidateFirst method
 *
 * @return void
 */
	public function testSaveAllValidateFirst() {
		$this->loadFixtures('Article', 'Comment', 'Attachment', 'User', 'ArticlesTag', 'Tag');
		$model = new Article();
		$model->deleteAll(true);

		$model->Comment->validate = array('comment' => 'notEmpty');
		$result = $model->saveAll(array(
			'Article' => array(
				'title' => 'Post with Author',
				'body' => 'This post will be saved author'
			),
			'Comment' => array(
				array('comment' => 'First new comment'),
				array('comment' => '')
			)
		), array('validate' => 'first'));

		$this->assertFalse($result);

		$result = $model->find('all');
		$this->assertSame(array(), $result);
		$expected = array('Comment' => array(
			1 => array('comment' => array('This field cannot be left blank'))
		));

		$this->assertEquals($expected['Comment'], $model->Comment->validationErrors);

		$this->assertSame($model->Comment->find('count'), 0);

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

		$this->assertTrue($result);

		$result = $model->Comment->find('all');
		$this->assertSame(count($result), 1);
		$result = Hash::extract($result, '{n}.Comment.article_id');
		$this->assertEquals(4, $result[0]);

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
		$this->assertFalse(empty($result));

		$result = $model->find('all');
		$this->assertEquals(
			$result[0]['Article']['title'],
			'Post with Author saveAlled from comment'
		);
		$this->assertEquals('Only new comment', $result[0]['Comment'][0]['comment']);
	}

/**
 * test saveAll()'s return is correct when using atomic = false and validate = first.
 *
 * @return void
 */
	public function testSaveAllValidateFirstAtomicFalse() {
		$this->loadFixtures('Something');
		$Something = new Something();
		$invalidData = array(
			array(
				'title' => 'foo',
				'body' => 'bar',
				'published' => 'baz',
			),
			array(
				'body' => 3,
				'published' => 'sd',
			),
		);
		$Something->create();
		$Something->validate = array(
			'title' => array(
				'rule' => 'alphaNumeric',
				'required' => true,
			),
			'body' => array(
				'rule' => 'alphaNumeric',
				'required' => true,
				'allowEmpty' => true,
			),
		);
		$result = $Something->saveAll($invalidData, array(
			'atomic' => false,
			'validate' => 'first',
		));
		$expected = array(true, false);
		$this->assertEquals($expected, $result);

		$Something = new Something();
		$validData = array(
			array(
				'title' => 'title value',
				'body' => 'body value',
				'published' => 'baz',
			),
			array(
				'title' => 'valid',
				'body' => 'this body',
				'published' => 'sd',
			),
		);
		$Something->create();
		$result = $Something->saveAll($validData, array(
			'atomic' => false,
			'validate' => 'first',
		));
		$expected = array(true, true);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveAllHasManyValidationOnly method
 *
 * @return void
 */
	public function testSaveAllHasManyValidationOnly() {
		$this->loadFixtures('Article', 'Comment', 'Attachment');
		$TestModel = new Article();
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
		$this->assertSame($expected, $result);

		$expected = array('Comment' => array(
			0 => array('comment' => array('This field cannot be left blank')),
			2 => array('comment' => array('This field cannot be left blank'))
		));
		$this->assertEquals($expected, $TestModel->validationErrors);

		$expected = array(
			0 => array('comment' => array('This field cannot be left blank')),
			2 => array('comment' => array('This field cannot be left blank'))
		);
		$this->assertEquals($expected, $TestModel->Comment->validationErrors);
	}

/**
 * test that saveAll still behaves like previous versions (does not necessarily need a first argument)
 *
 * @return void
 */
	public function testSaveAllWithSet() {
		$this->loadFixtures('Article', 'Tag', 'Comment', 'User', 'ArticlesTag');
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
		$Article = new Article();
		$Article->set($data);
		$result = $Article->saveAll();
		$this->assertFalse(empty($result));
	}

/**
 * test that saveAll behaves like plain save() when supplied empty data
 *
 * @link https://cakephp.lighthouseapp.com/projects/42648/tickets/277-test-saveall-with-validation-returns-incorrect-boolean-when-saving-empty-data
 * @return void
 */
	public function testSaveAllEmptyData() {
		$this->skipIf($this->db instanceof Sqlserver, 'This test is not compatible with SQL Server.');

		$this->loadFixtures('Article', 'ProductUpdateAll', 'Comment', 'Attachment');
		$model = new Article();
		$result = $model->saveAll(array(), array('validate' => 'first'));
		$this->assertFalse(empty($result));

		$model = new ProductUpdateAll();
		$result = $model->saveAll();
		$this->assertFalse($result);
	}

/**
 * testSaveAssociated method
 *
 * @return void
 */
	public function testSaveAssociated() {
		$this->loadFixtures('Post', 'Author', 'Comment', 'Attachment', 'Article', 'User');
		$TestModel = new Post();

		$result = $TestModel->find('all');
		$this->assertEquals(3, count($result));
		$this->assertFalse(isset($result[3]));

		$TestModel->saveAssociated(array(
			'Post' => array(
				'title' => 'Post with Author',
				'body' => 'This post will be saved with an author'
			),
			'Author' => array(
				'user' => 'bob',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf90'
		)));

		$result = $TestModel->find('all', array('order' => array('Post.id ' => 'ASC')));
		$expected = array(
			'Post' => array(
				'id' => '4',
				'author_id' => '5',
				'title' => 'Post with Author',
				'body' => 'This post will be saved with an author',
				'published' => 'N'
			),
			'Author' => array(
				'id' => '5',
				'user' => 'bob',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf90',
				'test' => 'working'
		));
		$this->assertEquals(self::date(), $result[3]['Post']['updated']);
		$this->assertEquals(self::date(), $result[3]['Post']['created']);
		$this->assertEquals(self::date(), $result[3]['Author']['created']);
		$this->assertEquals(self::date(), $result[3]['Author']['updated']);
		unset(
			$result[3]['Post']['updated'], $result[3]['Post']['created'],
			$result[3]['Author']['updated'], $result[3]['Author']['created']
		);
		$this->assertEquals($expected, $result[3]);
		$this->assertEquals(4, count($result));

		$TestModel = new Comment();
		$result = $TestModel->saveAssociated(array(
			'Comment' => array(
				'article_id' => 2,
				'user_id' => 2,
				'comment' => 'New comment with attachment',
				'published' => 'Y'
			),
			'Attachment' => array(
				'attachment' => 'some_file.tgz'
			)));
		$this->assertFalse(empty($result));

		$result = $TestModel->find('all');
		$expected = array(
			'id' => '7',
			'article_id' => '2',
			'user_id' => '2',
			'comment' => 'New comment with attachment',
			'published' => 'Y'
		);
		$this->assertEquals(self::date(), $result[6]['Comment']['updated']);
		$this->assertEquals(self::date(), $result[6]['Comment']['created']);
		unset($result[6]['Comment']['updated'], $result[6]['Comment']['created']);
		$this->assertEquals($expected, $result[6]['Comment']);

		$expected = array(
			'id' => '2',
			'comment_id' => '7',
			'attachment' => 'some_file.tgz'
		);
		$this->assertEquals(self::date(), $result[6]['Attachment']['updated']);
		$this->assertEquals(self::date(), $result[6]['Attachment']['created']);
		unset($result[6]['Attachment']['updated'], $result[6]['Attachment']['created']);
		$this->assertEquals($expected, $result[6]['Attachment']);
	}

/**
 * Test that validate = first, atomic = false works when associated records
 * fail validation.
 *
 * @return void
 */
	public function testSaveAssociatedAtomicFalseValidateFirstWithErrors() {
		$this->loadFixtures('Comment', 'Article', 'User');
		$Article = ClassRegistry::init('Article');
		$Article->Comment->validator()->add('comment', array(
			array('rule' => 'notEmpty')
		));

		$data = array(
			'Article' => array(
				'user_id' => 1,
				'title' => 'Foo',
				'body' => 'text',
				'published' => 'N'
			),
			'Comment' => array(
				array(
					'user_id' => 1,
					'comment' => '',
					'published' => 'N',
				)
			),
		);

		$Article->saveAssociated(
			$data,
			array('validate' => 'first', 'atomic' => false)
		);

		$result = $Article->validationErrors;
		$expected = array(
			'Comment' => array(
				array(
					'comment' => array('This field cannot be left blank')
				)
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveMany method
 *
 * @return void
 */
	public function testSaveMany() {
		$this->loadFixtures('Post');
		$TestModel = new Post();
		$TestModel->deleteAll(true);
		$this->assertEquals(array(), $TestModel->find('all'));

		// SQLite seems to reset the PK counter when that happens, so we need this to make the tests pass
		$this->db->truncate($TestModel);

		$TestModel->saveMany(array(
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
					'published' => 'N'
				)
			),
			array(
				'Post' => array(
					'id' => '2',
					'author_id' => '2',
					'title' => 'Multi-record post 2',
					'body' => 'Second multi-record post',
					'published' => 'N'
				)
			)
		);
		$this->assertEquals(self::date(), $result[0]['Post']['updated']);
		$this->assertEquals(self::date(), $result[0]['Post']['created']);
		$this->assertEquals(self::date(), $result[1]['Post']['updated']);
		$this->assertEquals(self::date(), $result[1]['Post']['created']);
		unset($result[0]['Post']['updated'], $result[0]['Post']['created']);
		unset($result[1]['Post']['updated'], $result[1]['Post']['created']);
		$this->assertEquals($expected, $result);
	}

/**
 * Test SaveMany with validate=false.
 *
 * @return void
 */
	public function testSaveManyValidateFalse() {
		$this->loadFixtures('Post');
		$TestModel = new Post();
		$TestModel->deleteAll(true);
		$data = array(
			array('id' => 1, 'author_id' => 1, 'title' => 'hi'),
			array('id' => 2, 'author_id' => 1, 'title' => 'bye')
		);
		$result = $TestModel->saveAll($data, array('validate' => false));
		$this->assertTrue($result);
	}

/**
 * Test SaveAssociated with Habtm relations
 *
 * @return void
 */
	public function testSaveAssociatedHabtm() {
		$this->loadFixtures('Article', 'Tag', 'Comment', 'User', 'ArticlesTag');
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
		$Article = new Article();
		$result = $Article->saveAssociated($data);
		$this->assertFalse(empty($result));

		$result = $Article->read();
		$this->assertEquals(2, count($result['Tag']));
		$this->assertEquals('tag1', $result['Tag'][0]['tag']);
		$this->assertEquals(1, count($result['Comment']));
		$this->assertEquals(1, count($result['Comment'][0]['comment']));
	}

/**
 * Test SaveAssociated with Habtm relations and extra join table fields
 *
 * @return void
 */
	public function testSaveAssociatedHabtmWithExtraJoinTableFields() {
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

		$Something = new Something();
		$result = $Something->saveAssociated($data);
		$this->assertFalse(empty($result));
		$result = $Something->read();

		$this->assertEquals(3, count($result['SomethingElse']));
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
 * testSaveAssociatedHasOne method
 *
 * @return void
 */
	public function testSaveAssociatedHasOne() {
		$model = new Comment();
		$model->deleteAll(true);
		$this->assertEquals(array(), $model->find('all'));

		$model->Attachment->deleteAll(true);
		$this->assertEquals(array(), $model->Attachment->find('all'));

		$this->assertTrue($model->saveAssociated(array(
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
		$this->assertEquals($expected, $result);

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
		$this->assertTrue($model->saveAssociated($data, array('validate' => 'first')));
	}

/**
 * testSaveAssociatedBelongsTo method
 *
 * @return void
 */
	public function testSaveAssociatedBelongsTo() {
		$model = new Comment();
		$model->deleteAll(true);
		$this->assertEquals(array(), $model->find('all'));

		$model->Article->deleteAll(true);
		$this->assertEquals(array(), $model->Article->find('all'));

		$this->assertTrue($model->saveAssociated(array(
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
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveAssociatedHasOneValidation method
 *
 * @return void
 */
	public function testSaveAssociatedHasOneValidation() {
		$model = new Comment();
		$model->deleteAll(true);
		$this->assertEquals(array(), $model->find('all'));

		$model->Attachment->deleteAll(true);
		$this->assertEquals(array(), $model->Attachment->find('all'));

		$model->validate = array('comment' => 'notEmpty');
		$model->Attachment->validate = array('attachment' => 'notEmpty');
		$model->Attachment->bindModel(array('belongsTo' => array('Comment')));

		$result = $model->saveAssociated(
			array(
				'Comment' => array(
					'comment' => '',
					'article_id' => 1,
					'user_id' => 1
				),
				'Attachment' => array('attachment' => '')
			)
		);
		$this->assertFalse($result);
		$expected = array(
			'comment' => array(
				'This field cannot be left blank'
			),
			'Attachment' => array(
				'attachment' => array(
					'This field cannot be left blank'
				)
			)
		);
		$this->assertEquals($expected, $model->validationErrors);
		$this->assertEquals($expected['Attachment'], $model->Attachment->validationErrors);
	}

/**
 * testSaveAssociatedAtomic method
 *
 * @return void
 */
	public function testSaveAssociatedAtomic() {
		$this->loadFixtures('Article', 'User');
		$TestModel = new Article();

		$result = $TestModel->saveAssociated(array(
			'Article' => array(
				'title' => 'Post with Author',
				'body' => 'This post will be saved with an author',
				'user_id' => 2
			),
			'Comment' => array(
				array('comment' => 'First new comment', 'user_id' => 2))
		), array('atomic' => false));

		$this->assertSame($result, array('Article' => true, 'Comment' => array(true)));

		$result = $TestModel->saveAssociated(array(
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
		), array('validate' => true, 'atomic' => false));
		$this->assertSame($result, array('Article' => true, 'Comment' => array(true, true)));
	}

/**
 * testSaveManyAtomic method
 *
 * @return void
 */
	public function testSaveManyAtomic() {
		$this->loadFixtures('Article', 'User');
		$TestModel = new Article();

		$result = $TestModel->saveMany(array(
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
		$this->assertSame($result, array(true, true, true));

		$TestModel->validate = array('title' => 'notEmpty', 'author_id' => 'numeric');
		$result = $TestModel->saveMany(array(
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
		), array('validate' => true, 'atomic' => false));

		$this->assertSame(array(true, false), $result);
	}

/**
 * testSaveAssociatedHasMany method
 *
 * @return void
 */
	public function testSaveAssociatedHasMany() {
		$this->loadFixtures('Article', 'Comment');
		$TestModel = new Article();
		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = array();

		$result = $TestModel->saveAssociated(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'user_id' => 1),
				array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
			)
		));
		$this->assertFalse(empty($result));

		$result = $TestModel->findById(2);
		$expected = array(
			'First Comment for Second Article',
			'Second Comment for Second Article',
			'First new comment',
			'Second new comment'
		);
		$this->assertEquals($expected, Hash::extract($result['Comment'], '{n}.comment'));

		$result = $TestModel->saveAssociated(
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
		$this->assertFalse(empty($result));

		$result = $TestModel->findById(2);
		$expected = array(
			'First Comment for Second Article',
			'Second Comment for Second Article',
			'First new comment',
			'Second new comment',
			'Third new comment'
		);
		$this->assertEquals($expected, Hash::extract($result['Comment'], '{n}.comment'));

		$TestModel->beforeSaveReturn = false;
		$result = $TestModel->saveAssociated(
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
		$this->assertEquals(array('Article' => false), $result);

		$result = $TestModel->findById(2);
		$expected = array(
			'First Comment for Second Article',
			'Second Comment for Second Article',
			'First new comment',
			'Second new comment',
			'Third new comment'
		);
		$this->assertEquals($expected, Hash::extract($result['Comment'], '{n}.comment'));
	}

/**
 * testSaveAssociatedHasManyEmpty method
 *
 * @return void
 */
	public function testSaveAssociatedHasManyEmpty() {
		$this->loadFixtures('Article', 'Comment');
		$TestModel = new Article();
		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = array();
		$TestModel->validate = $TestModel->Comment->validate = array('user_id' => array('notEmpty' => array('rule' => 'notEmpty', 'required' => true)));

		//empty hasMany data is ignored in save
		$result = $TestModel->saveAssociated(array(
			'Article' => array('title' => 'title', 'user_id' => 1),
			'Comment' => array()
		), array('validate' => true));
		$this->assertTrue($result);

		$result = $TestModel->saveAssociated(array(
			'Article' => array('title' => 'title', 'user_id' => 1),
			'Comment' => array()
		), array('validate' => true, 'atomic' => false));
		$this->assertEquals(array('Article' => true), $result);

		//empty primary data is not ignored
		$result = $TestModel->saveAssociated(array('Article' => array()), array('validate' => true));
		$this->assertFalse($result);

		$result = $TestModel->saveAssociated(array('Article' => array()), array('validate' => true, 'atomic' => false));
		$this->assertEquals(array('Article' => false), $result);
	}

/**
 * testSaveAssociatedHasManyValidation method
 *
 * @return void
 */
	public function testSaveAssociatedHasManyValidation() {
		$this->loadFixtures('Article', 'Comment');
		$TestModel = new Article();
		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = array();
		$TestModel->Comment->validate = array('comment' => 'notEmpty');

		$result = $TestModel->saveAssociated(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array('comment' => '', 'published' => 'Y', 'user_id' => 1),
			)
		), array('validate' => true));
		$this->assertFalse($result);

		$expected = array('Comment' => array(
			array('comment' => array('This field cannot be left blank'))
		));
		$this->assertEquals($expected, $TestModel->validationErrors);
		$expected = array(
			array('comment' => array('This field cannot be left blank'))
		);
		$this->assertEquals($expected, $TestModel->Comment->validationErrors);

		$result = $TestModel->saveAssociated(array(
			'Article' => array('id' => 2),
			'Comment' => array(
				array(
					'comment' => '',
					'published' => 'Y',
					'user_id' => 1
			))
		), array('validate' => 'first'));
		$this->assertFalse($result);
	}

/**
 * test saveMany with transactions and ensure there is no missing rollback.
 *
 * @return void
 */
	public function testSaveManyTransactionNoRollback() {
		$this->loadFixtures('Post');

		$this->getMock('DboSource', array('connect', 'rollback', 'describe'), array(), 'MockManyTransactionDboSource');
		$db = ConnectionManager::create('mock_many_transaction', array(
			'datasource' => 'MockManyTransactionDboSource',
		));

		$db->expects($this->once())
			->method('describe')
			->will($this->returnValue(array()));
		$db->expects($this->once())->method('rollback');

		$Post = new Post('mock_many_transaction');

		$Post->validate = array(
			'title' => array('rule' => array('notEmpty'))
		);

		$data = array(
			array('author_id' => 1, 'title' => 'New Fourth Post'),
			array('author_id' => 1, 'title' => '')
		);
		$Post->saveMany($data);
	}

/**
 * test saveAssociated with transactions and ensure there is no missing rollback.
 *
 * @return void
 */
	public function testSaveAssociatedTransactionNoRollback() {
		$testDb = ConnectionManager::getDataSource('test');

		$this->getMock(
			'DboSource',
			array('connect', 'rollback', 'describe', 'create', 'begin'),
			array(),
			'MockAssociatedTransactionDboSource',
			false
		);
		$db = ConnectionManager::create('mock_assoc_transaction', array(
			'datasource' => 'MockAssociatedTransactionDboSource',
		));
		$this->mockObjects[] = $db;
		$db->columns = $testDb->columns;

		$db->expects($this->once())->method('rollback');
		$db->expects($this->any())->method('describe')
			->will($this->returnValue(array(
				'id' => array('type' => 'integer', 'length' => 11),
				'title' => array('type' => 'string'),
				'body' => array('type' => 'text'),
				'published' => array('type' => 'string')
			)));

		$Post = new Post();
		$Post->useDbConfig = 'mock_assoc_transaction';
		$Post->Author->useDbConfig = 'mock_assoc_transaction';

		$Post->Author->validate = array(
			'user' => array('rule' => array('notEmpty'))
		);

		$data = array(
			'Post' => array(
				'title' => 'New post',
				'body' => 'Content',
				'published' => 'Y'
			),
			'Author' => array(
				'user' => '',
				'password' => "sekret"
			)
		);
		$Post->saveAssociated($data, array('validate' => true, 'atomic' => true));
	}

/**
 * test saveMany with nested saveMany call.
 *
 * @return void
 */
	public function testSaveManyNestedSaveMany() {
		$this->loadFixtures('Sample');
		$TransactionManyTestModel = new TransactionManyTestModel();

		$data = array(
			array('apple_id' => 1, 'name' => 'sample5'),
		);

		$this->assertTrue($TransactionManyTestModel->saveMany($data, array('atomic' => true)));
	}

/**
 * testSaveManyTransaction method
 *
 * @return void
 */
	public function testSaveManyTransaction() {
		$this->loadFixtures('Post', 'Author', 'Comment', 'Attachment');
		$TestModel = new Post();

		$TestModel->validate = array('title' => 'notEmpty');
		$data = array(
			array('author_id' => 1, 'title' => 'New Fourth Post'),
			array('author_id' => 1, 'title' => 'New Fifth Post'),
			array('author_id' => 1, 'title' => '')
		);
		$this->assertFalse($TestModel->saveMany($data));

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
					'published' => 'N'
			));

			$expected[] = array(
				'Post' => array(
					'id' => '5',
					'author_id' => 1,
					'title' => 'New Fifth Post',
					'body' => null,
					'published' => 'N',
			));

			$this->assertEquals(self::date(), $result[3]['Post']['created']);
			$this->assertEquals(self::date(), $result[3]['Post']['updated']);
			$this->assertEquals(self::date(), $result[4]['Post']['created']);
			$this->assertEquals(self::date(), $result[4]['Post']['updated']);
			unset($result[3]['Post']['created'], $result[3]['Post']['updated']);
			unset($result[4]['Post']['created'], $result[4]['Post']['updated']);
			$this->assertEquals($expected, $result);
			// Skip the rest of the transactional tests
			return;
		}

		$this->assertEquals($expected, $result);

		$data = array(
			array('author_id' => 1, 'title' => 'New Fourth Post'),
			array('author_id' => 1, 'title' => ''),
			array('author_id' => 1, 'title' => 'New Sixth Post')
		);
		$this->assertFalse($TestModel->saveMany($data));

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
					'published' => 'N'
			));

			$expected[] = array(
				'Post' => array(
					'id' => '5',
					'author_id' => 1,
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'N'
			));
			$this->assertEquals(self::date(), $result[3]['Post']['created']);
			$this->assertEquals(self::date(), $result[3]['Post']['updated']);
			$this->assertEquals(self::date(), $result[4]['Post']['created']);
			$this->assertEquals(self::date(), $result[4]['Post']['updated']);
			unset($result[3]['Post']['created'], $result[3]['Post']['updated']);
			unset($result[4]['Post']['created'], $result[4]['Post']['updated']);
		}
		$this->assertEquals($expected, $result);

		$TestModel->validate = array('title' => 'notEmpty');
		$data = array(
			array('author_id' => 1, 'title' => 'New Fourth Post'),
			array('author_id' => 1, 'title' => 'New Fifth Post'),
			array('author_id' => 1, 'title' => 'New Sixth Post')
		);
		$this->assertTrue($TestModel->saveMany($data));

		$result = $TestModel->find('all', array(
			'recursive' => -1,
			'fields' => array('author_id', 'title', 'body', 'published'),
			'order' => array('Post.created' => 'ASC')
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
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveManyValidation method
 *
 * @return void
 */
	public function testSaveManyValidation() {
		$this->loadFixtures('Post', 'Author', 'Comment', 'Attachment');
		$TestModel = new Post();

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

		$this->assertTrue($TestModel->saveMany($data));

		$result = $TestModel->find('all', array('recursive' => -1, 'order' => 'Post.id ASC'));
		$expected = array(
			array(
				'Post' => array(
					'id' => '1',
					'author_id' => '1',
					'title' => 'Baleeted First Post',
					'body' => 'Baleeted!',
					'published' => 'N',
					'created' => '2007-03-18 10:39:23'
				)
			),
			array(
				'Post' => array(
					'id' => '2',
					'author_id' => '3',
					'title' => 'Just update the title',
					'body' => 'Second Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23'
				)
			),
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
					'published' => 'N'
				)
			)
		);

		$this->assertEquals(self::date(), $result[0]['Post']['updated']);
		$this->assertEquals(self::date(), $result[1]['Post']['updated']);
		$this->assertEquals(self::date(), $result[3]['Post']['created']);
		$this->assertEquals(self::date(), $result[3]['Post']['updated']);
		unset($result[0]['Post']['updated'], $result[1]['Post']['updated']);
		unset($result[3]['Post']['created'], $result[3]['Post']['updated']);
		$this->assertEquals($expected, $result);

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
		$result = $TestModel->saveMany($data);
		$this->assertFalse($result);

		$result = $TestModel->find('all', array('recursive' => -1, 'order' => 'Post.id ASC'));
		$errors = array(1 => array('title' => array('This field cannot be left blank')));
		$transactionWorked = Set::matches('/Post[1][title=Baleeted First Post]', $result);
		if (!$transactionWorked) {
			$this->assertTrue(Set::matches('/Post[1][title=Un-Baleeted First Post]', $result));
			$this->assertTrue(Set::matches('/Post[2][title=Just update the title]', $result));
		}

		$this->assertEquals($errors, $TestModel->validationErrors);

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
		$result = $TestModel->saveMany($data, array('validate' => true, 'atomic' => false));
		$this->assertEquals(array(true, false), $result);

		$result = $TestModel->find('all', array(
			'fields' => array('id', 'author_id', 'title', 'body', 'published'),
			'recursive' => -1,
			'order' => 'Post.id ASC'
		));
		$errors = array(1 => array('title' => array('This field cannot be left blank')));
		$expected = array(
			array(
				'Post' => array(
					'id' => '1',
					'author_id' => '1',
					'title' => 'Un-Baleeted First Post',
					'body' => 'Not Baleeted!',
					'published' => 'Y',
			)),
			array(
				'Post' => array(
					'id' => '2',
					'author_id' => '3',
					'title' => 'Just update the title',
					'body' => 'Second Post Body',
					'published' => 'Y',
			)),
			array(
				'Post' => array(
					'id' => '3',
					'author_id' => '1',
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'Y',
			)),
			array(
				'Post' => array(
					'id' => '4',
					'author_id' => '2',
					'title' => 'Creating a fourth post',
					'body' => 'Fourth post body',
					'published' => 'N',
		)));
		$this->assertEquals($expected, $result);
		$this->assertEquals($errors, $TestModel->validationErrors);

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
		$this->assertFalse($TestModel->saveMany($data, array('validate' => 'first')));

		$result = $TestModel->find('all', array(
			'fields' => array('id', 'author_id', 'title', 'body', 'published'),
			'recursive' => -1,
			'order' => 'Post.id ASC'
		));
		$this->assertEquals($expected, $result);
		$this->assertEquals($errors, $TestModel->validationErrors);
	}

/**
 * testValidateMany method
 *
 * @return void
 */
	public function testValidateMany() {
		$TestModel = new Article();
		$TestModel->validate = array('title' => 'notEmpty');
		$data = array(
				0 => array('title' => ''),
				1 => array('title' => 'title 1'),
				2 => array('title' => 'title 2'),
		);
		$result = $TestModel->validateMany($data);
		$this->assertFalse($result);
		$expected = array(
			0 => array('title' => array('This field cannot be left blank')),
		);
		$this->assertEquals($expected, $TestModel->validationErrors);

		$data = array(
				0 => array('title' => 'title 0'),
				1 => array('title' => ''),
				2 => array('title' => 'title 2'),
		);
		$result = $TestModel->validateMany($data);
		$this->assertFalse($result);
		$expected = array(
			1 => array('title' => array('This field cannot be left blank')),
		);
		$this->assertEquals($expected, $TestModel->validationErrors);
	}

/**
 * testSaveAssociatedValidateFirst method
 *
 * @return void
 */
	public function testSaveAssociatedValidateFirst() {
		$this->loadFixtures('Article', 'Comment', 'Attachment');
		$model = new Article();
		$model->deleteAll(true);

		$model->Comment->validate = array('comment' => 'notEmpty');
		$result = $model->saveAssociated(array(
			'Article' => array(
				'title' => 'Post with Author',
				'body' => 'This post will be saved author'
			),
			'Comment' => array(
				array('comment' => 'First new comment'),
				array('comment' => '')
			)
		), array('validate' => 'first'));

		$this->assertFalse($result);

		$result = $model->find('all');
		$this->assertSame(array(), $result);
		$expected = array('Comment' => array(
			1 => array('comment' => array('This field cannot be left blank'))
		));

		$this->assertEquals($expected['Comment'], $model->Comment->validationErrors);

		$this->assertSame($model->Comment->find('count'), 0);

		$result = $model->saveAssociated(
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

		$this->assertTrue($result);

		$result = $model->Comment->find('all');
		$this->assertSame(count($result), 1);
		$result = Hash::extract($result, '{n}.Comment.article_id');
		$this->assertEquals(4, $result[0]);

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

		$result = $model->Comment->saveAssociated($data, array('validate' => 'first'));
		$this->assertFalse(empty($result));

		$result = $model->find('all');
		$this->assertEquals(
			'Post with Author saveAlled from comment',
			$result[0]['Article']['title']
		);
		$this->assertEquals('Only new comment', $result[0]['Comment'][0]['comment']);
	}

/**
 * test saveMany()'s return is correct when using atomic = false and validate = first.
 *
 * @return void
 */
	public function testSaveManyValidateFirstAtomicFalse() {
		$Something = new Something();
		$invalidData = array(
			array(
				'title' => 'foo',
				'body' => 'bar',
				'published' => 'baz',
			),
			array(
				'body' => 3,
				'published' => 'sd',
			),
		);
		$Something->create();
		$Something->validate = array(
			'title' => array(
				'rule' => 'alphaNumeric',
				'required' => true,
			),
			'body' => array(
				'rule' => 'alphaNumeric',
				'required' => true,
				'allowEmpty' => true,
			),
		);
		$result = $Something->saveMany($invalidData, array(
			'atomic' => false,
			'validate' => 'first',
		));
		$expected = array(true, false);
		$this->assertEquals($expected, $result);

		$Something = new Something();
		$validData = array(
			array(
				'title' => 'title value',
				'body' => 'body value',
				'published' => 'baz',
			),
			array(
				'title' => 'valid',
				'body' => 'this body',
				'published' => 'sd',
			),
		);
		$Something->create();
		$result = $Something->saveMany($validData, array(
			'atomic' => false,
			'validate' => 'first',
		));
		$expected = array(true, true);
		$this->assertEquals($expected, $result);
	}

/**
 * testValidateAssociated method
 *
 * @return void
 */
	public function testValidateAssociated() {
		$this->loadFixtures('Attachment', 'Article', 'Comment');
		$TestModel = new Comment();
		$TestModel->Attachment->validate = array('attachment' => 'notEmpty');

		$data = array(
			'Comment' => array(
				'comment' => 'This is the comment'
			),
			'Attachment' => array(
				'attachment' => ''
			)
		);

		$result = $TestModel->validateAssociated($data);
		$this->assertFalse($result);

		$TestModel = new Article();
		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = array();
		$TestModel->Comment->validate = array('comment' => 'notEmpty');

		$data = array(
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
		)));
		$result = $TestModel->validateAssociated($data);
		$this->assertFalse($result);

		$data = array(
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
		)));
		$result = $TestModel->validateAssociated($data, array(
				'atomic' => false
		));
		$expected = array(
			'Article' => true,
			'Comment' => array(false, true, false)
		);
		$this->assertSame($expected, $result);

		$expected = array('Comment' => array(
			0 => array('comment' => array('This field cannot be left blank')),
			2 => array('comment' => array('This field cannot be left blank'))
		));
		$this->assertEquals($expected, $TestModel->validationErrors);

		$expected = array(
			0 => array('comment' => array('This field cannot be left blank')),
			2 => array('comment' => array('This field cannot be left blank'))
		);
		$this->assertEquals($expected, $TestModel->Comment->validationErrors);
	}

/**
 * test that saveMany behaves like plain save() when suplied empty data
 *
 * @link https://cakephp.lighthouseapp.com/projects/42648/tickets/277-test-saveall-with-validation-returns-incorrect-boolean-when-saving-empty-data
 * @return void
 */
	public function testSaveManyEmptyData() {
		$this->skipIf($this->db instanceof Sqlserver, 'This test is not compatible with SQL Server.');

		$this->loadFixtures('Article', 'ProductUpdateAll', 'Comment', 'Attachment');
		$model = new Article();
		$result = $model->saveMany(array(), array('validate' => true));
		$this->assertFalse(empty($result));

		$model = new ProductUpdateAll();
		$result = $model->saveMany(array());
		$this->assertFalse($result);
	}

/**
 * test that saveAssociated behaves like plain save() when supplied empty data
 *
 * @link https://cakephp.lighthouseapp.com/projects/42648/tickets/277-test-saveall-with-validation-returns-incorrect-boolean-when-saving-empty-data
 * @return void
 */
	public function testSaveAssociatedEmptyData() {
		$this->skipIf($this->db instanceof Sqlserver, 'This test is not compatible with SQL Server.');

		$this->loadFixtures('Article', 'ProductUpdateAll', 'Comment', 'Attachment');
		$model = new Article();
		$result = $model->saveAssociated(array(), array('validate' => true));
		$this->assertFalse(empty($result));

		$model = new ProductUpdateAll();
		$result = $model->saveAssociated(array());
		$this->assertFalse($result);
	}

/**
 * Test that saveAssociated will accept expression object values when saving.
 *
 * @return void
 */
	public function testSaveAssociatedExpressionObjects() {
		$this->loadFixtures('Post', 'Author', 'Comment', 'Attachment', 'Article', 'User');
		$TestModel = new Post();
		$db = $TestModel->getDataSource();

		$TestModel->saveAssociated(array(
			'Post' => array(
				'title' => $db->expression("(SELECT 'Post with Author')"),
				'body' => 'This post will be saved with an author'
			),
			'Author' => array(
				'user' => 'bob',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf90'
		)), array('atomic' => false));

		$result = $TestModel->find('first', array(
			'order' => array('Post.id ' => 'DESC')
		));
		$this->assertEquals('Post with Author', $result['Post']['title']);
	}

/**
 * testUpdateWithCalculation method
 *
 * @return void
 */
	public function testUpdateWithCalculation() {
		$this->loadFixtures('DataTest');
		$model = new DataTest();
		$model->deleteAll(true);
		$result = $model->saveMany(array(
			array('count' => 5, 'float' => 1.1),
			array('count' => 3, 'float' => 1.2),
			array('count' => 4, 'float' => 1.3),
			array('count' => 1, 'float' => 2.0),
		));
		$this->assertFalse(empty($result));

		$result = Hash::extract($model->find('all', array('fields' => 'count')), '{n}.DataTest.count');
		$this->assertEquals(array(5, 3, 4, 1), $result);

		$this->assertTrue($model->updateAll(array('count' => 'count + 2')));
		$result = Hash::extract($model->find('all', array('fields' => 'count')), '{n}.DataTest.count');
		$this->assertEquals(array(7, 5, 6, 3), $result);

		$this->assertTrue($model->updateAll(array('DataTest.count' => 'DataTest.count - 1')));
		$result = Hash::extract($model->find('all', array('fields' => 'count')), '{n}.DataTest.count');
		$this->assertEquals(array(6, 4, 5, 2), $result);
	}

	public function testToggleBoolFields() {
		$this->loadFixtures('CounterCacheUser', 'CounterCachePost');
		$Post = new CounterCachePost();
		$Post->unbindModel(array('belongsTo' => array('User')), true);

		$true = array('Post' => array('published' => true, 'id' => 2));
		$false = array('Post' => array('published' => false, 'id' => 2));
		$fields = array('Post.published', 'Post.id');
		$updateConditions = array('Post.id' => 2);

		// check its true
		$result = $Post->find('first', array('conditions' => $updateConditions, 'fields' => $fields));
		$this->assertEquals($true, $result);

		// Testing without the alias
		$this->assertTrue($Post->updateAll(array('published' => 'NOT published'), $updateConditions));
		$result = $Post->find('first', array('conditions' => $updateConditions, 'fields' => $fields));
		$this->assertEquals($false, $result);

		$this->assertTrue($Post->updateAll(array('published' => 'NOT published'), $updateConditions));
		$result = $Post->find('first', array('conditions' => $updateConditions, 'fields' => $fields));
		$this->assertEquals($true, $result);

		$db = ConnectionManager::getDataSource('test');
		$alias = $db->name('Post.published');

		// Testing with the alias
		$this->assertTrue($Post->updateAll(array('Post.published' => "NOT $alias"), $updateConditions));
		$result = $Post->find('first', array('conditions' => $updateConditions, 'fields' => $fields));
		$this->assertEquals($false, $result);

		$this->assertTrue($Post->updateAll(array('Post.published' => "NOT $alias"), $updateConditions));
		$result = $Post->find('first', array('conditions' => $updateConditions, 'fields' => $fields));
		$this->assertEquals($true, $result);
	}

/**
 * TestFindAllWithoutForeignKey
 *
 * @return void
 */
	public function testFindAllForeignKey() {
		$this->loadFixtures('ProductUpdateAll', 'GroupUpdateAll');
		$ProductUpdateAll = new ProductUpdateAll();

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

		$ProductUpdateAll->bindModel(array('belongsTo' => array('Group')));
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
					'id' => 1,
					'name'	=> 'product one',
					'groupcode' => 120,
					'group_id' => 1),
				'Group' => array(
					'id' => 1,
					'name' => 'group one',
					'code' => 120)
				),
			'1' => array(
				'ProductUpdateAll' => array(
					'id' => 2,
					'name'	=> 'product two',
					'groupcode'	=> 120,
					'group_id'	=> 1),
				'Group' => array(
					'id' => 1,
					'name' => 'group one',
					'code' => 120)
				)

			);
		$this->assertEquals($expected, $results);
		$this->assertEquals($expected, $resultsFkFalse);
	}

/**
 * test updateAll with empty values.
 *
 * @return void
 */
	public function testUpdateAllEmptyValues() {
		$this->skipIf($this->db instanceof Sqlserver || $this->db instanceof Postgres, 'This test is not compatible with Postgres or SQL Server.');

		$this->loadFixtures('Author', 'Post');
		$model = new Author();
		$result = $model->updateAll(array('user' => '""'));
		$this->assertTrue($result);
	}

/**
 * testUpdateAllWithJoins
 *
 * @return void
 */
	public function testUpdateAllWithJoins() {
		$this->skipIf(!$this->db instanceof Mysql, 'Currently, there is no way of doing joins in an update statement in postgresql or sqlite');

		$this->loadFixtures('ProductUpdateAll', 'GroupUpdateAll');
		$ProductUpdateAll = new ProductUpdateAll();

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
					'id' => 1,
					'name' => 'new product',
					'groupcode'	=> 120,
					'group_id' => 1),
				'Group' => array(
					'id' => 1,
					'name' => 'group one',
					'code' => 120)
				),
			'1' => array(
				'ProductUpdateAll' => array(
					'id' => 2,
					'name' => 'new product',
					'groupcode' => 120,
					'group_id' => 1),
				'Group' => array(
					'id' => 1,
					'name' => 'group one',
					'code' => 120)));

		$this->assertEquals($expected, $results);
	}

/**
 * testUpdateAllWithoutForeignKey
 *
 * @return void
 */
	public function testUpdateAllWithoutForeignKey() {
		$this->skipIf(!$this->db instanceof Mysql, 'Currently, there is no way of doing joins in an update statement in postgresql');

		$this->loadFixtures('ProductUpdateAll', 'GroupUpdateAll');
		$ProductUpdateAll = new ProductUpdateAll();

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
		$resultsFkFalse = $ProductUpdateAll->find('all', array('conditions' => array('ProductUpdateAll.name' => 'new product')));
		$expected = array(
			'0' => array(
				'ProductUpdateAll' => array(
					'id' => 1,
					'name' => 'new product',
					'groupcode'	=> 120,
					'group_id' => 1),
				'Group' => array(
					'id' => 1,
					'name' => 'group one',
					'code' => 120)
				),
			'1' => array(
				'ProductUpdateAll' => array(
					'id' => 2,
					'name' => 'new product',
					'groupcode' => 120,
					'group_id' => 1),
				'Group' => array(
					'id' => 1,
					'name' => 'group one',
					'code' => 120)));
		$this->assertEquals($expected, $resultsFkFalse);
	}

/**
 * test writing floats in german locale.
 *
 * @return void
 */
	public function testWriteFloatAsGerman() {
		$restore = setlocale(LC_NUMERIC, 0);

		$this->skipIf(setlocale(LC_NUMERIC, 'de_DE') === false, "The German locale isn't available.");

		$model = new DataTest();
		$result = $model->save(array(
			'count' => 1,
			'float' => 3.14593
		));
		$this->assertTrue((bool)$result);
		setlocale(LC_NUMERIC, $restore);
	}

/**
 * Test returned array contains primary key when save creates a new record
 *
 * @return void
 */
	public function testPkInReturnArrayForCreate() {
		$this->loadFixtures('Article');
		$TestModel = new Article();

		$data = array('Article' => array(
			'user_id' => '1',
			'title' => 'Fourth Article',
			'body' => 'Fourth Article Body',
			'published' => 'Y'
		));
		$result = $TestModel->save($data);
		$this->assertSame($result['Article']['id'], $TestModel->id);
	}

/**
 * testSaveAllFieldListValidateBelongsTo
 *
 * @return void
 */
	public function testSaveAllFieldListValidateBelongsTo() {
		$this->loadFixtures('Post', 'Author', 'Comment', 'Attachment');
		$TestModel = new Post();

		$result = $TestModel->find('all');
		$this->assertEquals(3, count($result));
		$this->assertFalse(isset($result[3]));

		// test belongsTo
		$fieldList = array(
			'Post' => array('title'),
			'Author' => array('user')
		);
		$data = array(
			'Post' => array(
				'title' => 'Post without body',
				'body' => 'This will not be saved',
			),
			'Author' => array(
				'user' => 'bob',
				'test' => 'This will not be saved',

		));
		$TestModel->saveAll($data, array('fieldList' => $fieldList));

		$result = $TestModel->find('all', array(
			'order' => 'Post.id ASC',
		));
		$expected = array(
			'Post' => array(
				'id' => '4',
				'author_id' => '5',
				'title' => 'Post without body',
				'body' => null,
				'published' => 'N',
				'created' => self::date(),
				'updated' => self::date(),
			),
			'Author' => array(
				'id' => '5',
				'user' => 'bob',
				'password' => null,
				'created' => self::date(),
				'updated' => self::date(),
				'test' => 'working',
			),
		);
		$this->assertEquals($expected, $result[3]);
		$this->assertEquals(4, count($result));
		$this->assertEquals('', $result[3]['Post']['body']);
		$this->assertEquals('working', $result[3]['Author']['test']);

		$fieldList = array(
			'Post' => array('title')
		);
		$data = array(
			'Post' => array(
				'title' => 'Post without body 2',
				'body' => 'This will not be saved'
			),
			'Author' => array(
				'user' => 'jack'
			)
		);
		$TestModel->saveAll($data, array('fieldList' => $fieldList));
		$result = $TestModel->find('all', array(
			'order' => 'Post.id ASC',
		));
		$this->assertNull($result[4]['Post']['body']);

		$fieldList = array(
			'Author' => array('password')
		);
		$data = array(
			'Post' => array(
				'id' => '5',
				'title' => 'Post title',
				'body' => 'Post body'
			),
			'Author' => array(
				'id' => '6',
				'user' => 'will not change',
				'password' => 'foobar'
			)
		);
		$result = $TestModel->saveAll($data, array('fieldList' => $fieldList));
		$this->assertTrue($result);

		$result = $TestModel->find('all', array(
			'order' => 'Post.id ASC',
		));
		$expected = array(
			'Post' => array(
				'id' => '5',
				'author_id' => '6',
				'title' => 'Post title',
				'body' => 'Post body',
				'published' => 'N',
				'created' => self::date(),
				'updated' => self::date()
			),
			'Author' => array(
				'id' => '6',
				'user' => 'jack',
				'password' => 'foobar',
				'created' => self::date(),
				'updated' => self::date(),
				'test' => 'working'
			),
		);
		$this->assertEquals($expected, $result[4]);

		// test multirecord
		$this->db->truncate($TestModel);

		$fieldList = array('title', 'author_id');
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
		)), array('fieldList' => $fieldList));

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
					'body' => '',
					'published' => 'N',
					'created' => self::date(),
					'updated' => self::date()
				)
			),
			array(
				'Post' => array(
					'id' => '2',
					'author_id' => '2',
					'title' => 'Multi-record post 2',
					'body' => '',
					'published' => 'N',
					'created' => self::date(),
					'updated' => self::date()
				)
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveAllFieldListHasMany method
 *
 * return @void
 */
	public function testSaveAllFieldListHasMany() {
		$this->loadFixtures('Article', 'Comment');
		$TestModel = new Article();
		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = array();

		$this->db->truncate($TestModel);
		$this->db->truncate(new Comment());

		$data = array(
			'Article' => array('title' => 'I will not save'),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'user_id' => 1),
				array('comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2)
			)
		);

		$fieldList = array(
			'Article' => array('id'),
			'Comment' => array('article_id', 'user_id')
		);
		$TestModel->saveAll($data, array('fieldList' => $fieldList));

		$result = $TestModel->find('all');
		$this->assertEquals('', $result[0]['Article']['title']);
		$this->assertEquals('', $result[0]['Comment'][0]['comment']);
		$this->assertEquals('', $result[0]['Comment'][1]['comment']);

		$fieldList = array(
			'Article' => array('id'),
			'Comment' => array('user_id')
		);
		$TestModel->saveAll($data, array('fieldList' => $fieldList));
		$result = $TestModel->find('all');

		$this->assertEquals('', $result[1]['Article']['title']);
		$this->assertEquals(2, count($result[1]['Comment']));

		$TestModel->whitelist = array('id');
		$TestModel->Comment->whitelist = array('user_id');
		$TestModel->saveAll($data);
		$result = $TestModel->find('all');

		$this->assertEquals('', $result[2]['Article']['title']);
		$this->assertEquals(2, count($result[2]['Comment']));
	}

/**
 * testSaveAllFieldListHasOne method
 *
 * @return void
 */
	public function testSaveAllFieldListHasOne() {
		$this->loadFixtures('Attachment', 'Comment', 'Article', 'User');
		$TestModel = new Comment();

		$TestModel->validate = array('comment' => 'notEmpty');
		$TestModel->Attachment->validate = array('attachment' => 'notEmpty');

		$record = array(
			'Comment' => array(
				'user_id' => 1,
				'article_id' => 1,
				'comment' => '',
			),
			'Attachment' => array(
				'attachment' => ''
			)
		);
		$result = $TestModel->saveAll($record, array('validate' => 'only'));
		$this->assertFalse($result);

		$fieldList = array(
			'Comment' => array('id', 'article_id', 'user_id'),
			'Attachment' => array('comment_id')
		);
		$result = $TestModel->saveAll($record, array(
			'fieldList' => $fieldList, 'validate' => 'only'
		));
		$this->assertTrue($result);
		$this->assertEmpty($TestModel->validationErrors);
	}

/**
 * testSaveAllFieldListHasOneAddFkToWhitelist method
 *
 * @return void
 */
	public function testSaveAllFieldListHasOneAddFkToWhitelist() {
		$this->loadFixtures('ArticleFeatured', 'Featured');
		$Article = new ArticleFeatured();
		$Article->belongsTo = $Article->hasMany = array();
		$Article->Featured->validate = array('end_date' => 'notEmpty');

		$record = array(
			'ArticleFeatured' => array(
				'user_id' => 1,
				'title' => 'First Article',
				'body' => '',
				'published' => 'Y'
			),
			'Featured' => array(
				'category_id' => 1,
				'end_date' => ''
			)
		);
		$result = $Article->saveAll($record, array('validate' => 'only'));
		$this->assertFalse($result);
		$expected = array(
			'body' => array(
				'This field cannot be left blank'
			),
			'Featured' => array(
				'end_date' => array(
					'This field cannot be left blank'
				)
			)
		);
		$this->assertEquals($expected, $Article->validationErrors);

		$fieldList = array(
			'ArticleFeatured' => array('user_id', 'title'),
			'Featured' => array('category_id')
		);

		$result = $Article->saveAll($record, array(
			'fieldList' => $fieldList, 'validate' => 'first'
		));
		$this->assertTrue($result);
		$this->assertEmpty($Article->validationErrors);

		$Article->recursive = 0;
		$result = $Article->find('first', array('order' => array('ArticleFeatured.created' => 'DESC')));
		$this->assertSame($result['ArticleFeatured']['id'], $result['Featured']['article_featured_id']);
	}

/**
 * testSaveAllDeepFieldListValidateBelongsTo
 *
 * @return void
 */
	public function testSaveAllDeepFieldListValidateBelongsTo() {
		$this->loadFixtures('Post', 'Author', 'Comment', 'Attachment', 'Article', 'User');
		$TestModel = new Post();
		$TestModel->Author->bindModel(array('hasMany' => array('Comment' => array('foreignKey' => 'user_id'))), false);
		$TestModel->recursive = 2;

		$result = $TestModel->find('all');
		$this->assertEquals(3, count($result));
		$this->assertFalse(isset($result[3]));

		// test belongsTo
		$fieldList = array(
			'Post' => array('title', 'author_id'),
			'Author' => array('user'),
			'Comment' => array('comment')
		);
		$TestModel->saveAll(array(
			'Post' => array(
				'title' => 'Post without body',
				'body' => 'This will not be saved',
			),
			'Author' => array(
				'user' => 'bob',
				'test' => 'This will not be saved',
				'Comment' => array(
					array('id' => 5, 'comment' => 'I am still published', 'published' => 'N'))

		)), array('fieldList' => $fieldList, 'deep' => true));

		$result = $TestModel->Author->Comment->find('first', array(
			'conditions' => array('Comment.id' => 5),
			'fields' => array('comment', 'published')
		));
		$expected = array(
			'Comment' => array(
				'comment' => 'I am still published',
				'published' => 'Y'
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveAllDeepFieldListHasMany method
 *
 * return @void
 */
	public function testSaveAllDeepFieldListHasMany() {
		$this->loadFixtures('Article', 'Comment', 'User');
		$TestModel = new Article();
		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = array();

		$this->db->truncate($TestModel);
		$this->db->truncate(new Comment());

		$fieldList = array(
			'Article' => array('id'),
			'Comment' => array('article_id', 'user_id'),
			'User' => array('user')
		);

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2, 'title' => 'I will not save'),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'user_id' => 1),
				array(
					'comment' => 'Second new comment', 'published' => 'Y', 'user_id' => 2,
					'User' => array('user' => 'nopassword', 'password' => 'not saved')
				)
			)
		), array('fieldList' => $fieldList, 'deep' => true));

		$result = $TestModel->Comment->User->find('first', array(
			'conditions' => array('User.user' => 'nopassword'),
			'fields' => array('user', 'password')
		));
		$expected = array(
			'User' => array(
				'user' => 'nopassword',
				'password' => ''
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveAllDeepHasManyBelongsTo method
 *
 * return @void
 */
	public function testSaveAllDeepHasManyBelongsTo() {
		$this->loadFixtures('Article', 'Comment', 'User');
		$TestModel = new Article();
		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = array();

		$this->db->truncate($TestModel);
		$this->db->truncate(new Comment());

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2, 'title' => 'The title'),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'user_id' => 1),
				array(
					'comment' => 'belongsto', 'published' => 'Y',
					'User' => array('user' => 'findme', 'password' => 'somepass')
				)
			)
		), array('deep' => true));

		$result = $TestModel->Comment->User->find('first', array(
			'conditions' => array('User.user' => 'findme'),
			'fields' => array('id', 'user', 'password')
		));
		$expected = array(
			'User' => array(
				'id' => 5,
				'user' => 'findme',
				'password' => 'somepass',
			)
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->Comment->find('first', array(
			'conditions' => array('Comment.user_id' => 5),
			'fields' => array('id', 'comment', 'published', 'user_id')
		));
		$expected = array(
			'Comment' => array(
				'id' => 2,
				'comment' => 'belongsto',
				'published' => 'Y',
				'user_id' => 5
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveAllDeepHasManyhasMany method
 *
 * return @void
 */
	public function testSaveAllDeepHasManyHasMany() {
		$this->loadFixtures('Article', 'Comment', 'User', 'Attachment');
		$TestModel = new Article();
		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = $TestModel->Comment->belongsTo = array();
		$TestModel->Comment->unbindModel(array('hasOne' => array('Attachment')), false);
		$TestModel->Comment->bindModel(array('hasMany' => array('Attachment')), false);

		$this->db->truncate($TestModel);
		$this->db->truncate(new Comment());
		$this->db->truncate(new Attachment());

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2, 'title' => 'The title'),
			'Comment' => array(
				array('comment' => 'First new comment', 'published' => 'Y', 'user_id' => 1),
				array(
					'comment' => 'hasmany', 'published' => 'Y', 'user_id' => 5,
					'Attachment' => array(
						array('attachment' => 'first deep attachment'),
						array('attachment' => 'second deep attachment'),
					)
				)
			)
		), array('deep' => true));

		$result = $TestModel->Comment->find('first', array(
			'conditions' => array('Comment.comment' => 'hasmany'),
			'fields' => array('id', 'comment', 'published', 'user_id'),
			'recursive' => -1
		));
		$expected = array(
			'Comment' => array(
				'id' => 2,
				'comment' => 'hasmany',
				'published' => 'Y',
				'user_id' => 5
			)
		);
		$this->assertEquals($expected, $result);

		$result = $TestModel->Comment->Attachment->find('all', array(
			'fields' => array('attachment', 'comment_id'),
			'order' => array('Attachment.id' => 'ASC')
		));
		$expected = array(
			array('Attachment' => array('attachment' => 'first deep attachment', 'comment_id' => 2)),
			array('Attachment' => array('attachment' => 'second deep attachment', 'comment_id' => 2)),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testSaveAllDeepOrderHasManyHasMany method
 *
 * return @void
 */
	public function testSaveAllDeepOrderHasManyHasMany() {
		$this->loadFixtures('Article', 'Comment', 'User', 'Attachment');
		$TestModel = new Article();
		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = $TestModel->Comment->belongsTo = array();
		$TestModel->Comment->unbindModel(array('hasOne' => array('Attachment')), false);
		$TestModel->Comment->bindModel(array('hasMany' => array('Attachment')), false);

		$this->db->truncate($TestModel);
		$this->db->truncate(new Comment());
		$this->db->truncate(new Attachment());

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 2, 'title' => 'Comment has its data after Attachment'),
			'Comment' => array(
				array(
					'Attachment' => array(
						array('attachment' => 'attachment should be created with comment_id'),
						array('attachment' => 'comment should be created with article_id'),
					),
					'comment' => 'after associated data',
					'user_id' => 1
				)
			)
		), array('deep' => true));
		$result = $TestModel->Comment->find('first', array(
			'conditions' => array('Comment.article_id' => 2),
		));

		$this->assertEquals(2, $result['Comment']['article_id']);
		$this->assertEquals(2, count($result['Attachment']));
	}

/**
 * testSaveAllDeepEmptyHasManyHasMany method
 *
 * return @void
 */
	public function testSaveAllDeepEmptyHasManyHasMany() {
		$this->skipIf(!$this->db instanceof Mysql, 'This test is only compatible with Mysql.');

		$this->loadFixtures('Article', 'Comment', 'User', 'Attachment');
		$TestModel = new Article();
		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = $TestModel->Comment->belongsTo = array();
		$TestModel->Comment->unbindModel(array('hasOne' => array('Attachment')), false);
		$TestModel->Comment->bindModel(array('hasMany' => array('Attachment')), false);

		$this->db->truncate($TestModel);
		$this->db->truncate(new Comment());
		$this->db->truncate(new Attachment());

		$result = $TestModel->saveAll(array(
			'Article' => array('id' => 3, 'user_id' => 1, 'title' => 'Comment has no data'),
			'Comment' => array(
				array(
					'user_id' => 1,
					'Attachment' => array(
						array('attachment' => 'attachment should be created with comment_id'),
						array('attachment' => 'comment should be created with article_id'),
					),
				)
			)
		), array('deep' => true));
		$result = $TestModel->Comment->find('first', array(
			'conditions' => array('Comment.article_id' => 3),
		));

		$this->assertEquals(3, $result['Comment']['article_id']);
		$this->assertEquals(2, count($result['Attachment']));
	}

/**
 * testUpdateAllBoolean
 *
 * return @void
 */
	public function testUpdateAllBoolean() {
		$this->loadFixtures('Item', 'Syfile', 'Portfolio', 'Image', 'ItemsPortfolio');
		$TestModel = new Item();
		$result = $TestModel->updateAll(array('published' => true));
		$this->assertTrue($result);

		$result = $TestModel->find('first', array('fields' => array('id', 'published')));
		$this->assertEquals(true, $result['Item']['published']);
	}

/**
 * testUpdateAllBooleanConditions
 *
 * return @void
 */
	public function testUpdateAllBooleanConditions() {
		$this->loadFixtures('Item', 'Syfile', 'Portfolio', 'Image', 'ItemsPortfolio');
		$TestModel = new Item();

		$result = $TestModel->updateAll(array('published' => true), array('Item.id' => 1));
		$this->assertTrue($result);
		$result = $TestModel->find('first', array(
			'fields' => array('id', 'published'),
			'conditions' => array('Item.id' => 1)));
		$this->assertEquals(true, $result['Item']['published']);
	}

/**
 * testUpdateBoolean
 *
 * return @void
 */
	public function testUpdateBoolean() {
		$this->loadFixtures('Item', 'Syfile', 'Portfolio', 'Image', 'ItemsPortfolio');
		$TestModel = new Item();

		$result = $TestModel->save(array('published' => true, 'id' => 1));
		$this->assertTrue((bool)$result);
		$result = $TestModel->find('first', array(
			'fields' => array('id', 'published'),
			'conditions' => array('Item.id' => 1)));
		$this->assertEquals(true, $result['Item']['published']);
	}

/**
 * Test the clear() method.
 *
 * @return void
 */
	public function testClear() {
		$this->loadFixtures('Bid');
		$model = ClassRegistry::init('Bid');
		$model->set(array('name' => 'Testing', 'message_id' => 3));
		$this->assertTrue(isset($model->data['Bid']['name']));
		$this->assertTrue($model->clear());
		$this->assertFalse(isset($model->data['Bid']['name']));
		$this->assertFalse(isset($model->data['Bid']['message_id']));
	}
}
