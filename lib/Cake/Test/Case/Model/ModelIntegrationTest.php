<?php
/**
 * ModelIntegrationTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

require_once dirname(__FILE__) . DS . 'ModelTestBase.php';
App::uses('DboSource', 'Model/Datasource');

/**
 * DboMock class
 * A Dbo Source driver to mock a connection and a identity name() method
 */
class DboMock extends DboSource {

/**
* Returns the $field without modifications
*/
	public function name($field) {
		return $field;
	}
/**
* Returns true to fake a database connection
*/
	public function connect() {
		return true;
	}
}

/**
 * ModelIntegrationTest
 *
 * @package       Cake.Test.Case.Model
 */
class ModelIntegrationTest extends BaseModelTest {

/**
 * testAssociationLazyLoading
 *
 * @group lazyloading
 * @return void
 */
	public function testAssociationLazyLoading() {
		$this->loadFixtures('ArticleFeaturedsTags');
		$Article = new ArticleFeatured();
		$this->assertTrue(isset($Article->belongsTo['User']));
		$this->assertFalse(property_exists($Article, 'User'));
		$this->assertInstanceOf('User', $Article->User);

		$this->assertTrue(isset($Article->belongsTo['Category']));
		$this->assertFalse(property_exists($Article, 'Category'));
		$this->assertTrue(isset($Article->Category));
		$this->assertInstanceOf('Category', $Article->Category);

		$this->assertTrue(isset($Article->hasMany['Comment']));
		$this->assertFalse(property_exists($Article, 'Comment'));
		$this->assertTrue(isset($Article->Comment));
		$this->assertInstanceOf('Comment', $Article->Comment);

		$this->assertTrue(isset($Article->hasAndBelongsToMany['Tag']));
		//There was not enough information to setup the association (joinTable and associationForeignKey)
		//so the model was not lazy loaded
		$this->assertTrue(property_exists($Article, 'Tag'));
		$this->assertTrue(isset($Article->Tag));
		$this->assertInstanceOf('Tag', $Article->Tag);

		$this->assertFalse(property_exists($Article, 'ArticleFeaturedsTag'));
		$this->assertInstanceOf('AppModel', $Article->ArticleFeaturedsTag);
		$this->assertEquals($Article->hasAndBelongsToMany['Tag']['joinTable'], 'article_featureds_tags');
		$this->assertEquals($Article->hasAndBelongsToMany['Tag']['associationForeignKey'], 'tag_id');
	}

/**
 * testAssociationLazyLoadWithHABTM
 *
 * @group lazyloading
 * @return void
 */
	public function testAssociationLazyLoadWithHABTM() {
		$this->loadFixtures('FruitsUuidTag', 'ArticlesTag');
		$this->db->cacheSources = false;
		$Article = new ArticleB();
		$this->assertTrue(isset($Article->hasAndBelongsToMany['TagB']));
		$this->assertFalse(property_exists($Article, 'TagB'));
		$this->assertInstanceOf('TagB', $Article->TagB);

		$this->assertFalse(property_exists($Article, 'ArticlesTag'));
		$this->assertInstanceOf('AppModel', $Article->ArticlesTag);

		$UuidTag = new UuidTag();
		$this->assertTrue(isset($UuidTag->hasAndBelongsToMany['Fruit']));
		$this->assertFalse(property_exists($UuidTag, 'Fruit'));
		$this->assertFalse(property_exists($UuidTag, 'FruitsUuidTag'));
		$this->assertTrue(isset($UuidTag->Fruit));

		$this->assertFalse(property_exists($UuidTag, 'FruitsUuidTag'));
		$this->assertTrue(isset($UuidTag->FruitsUuidTag));
		$this->assertInstanceOf('FruitsUuidTag', $UuidTag->FruitsUuidTag);
	}

/**
 * testAssociationLazyLoadWithBindModel
 *
 * @group lazyloading
 * @return void
 */
	public function testAssociationLazyLoadWithBindModel() {
		$this->loadFixtures('Article', 'User');
		$Article = new ArticleB();

		$this->assertFalse(isset($Article->belongsTo['User']));
		$this->assertFalse(property_exists($Article, 'User'));

		$Article->bindModel(array('belongsTo' => array('User')));
		$this->assertTrue(isset($Article->belongsTo['User']));
		$this->assertFalse(property_exists($Article, 'User'));
		$this->assertInstanceOf('User', $Article->User);
	}

/**
 * Tests that creating a model with no existent database table associated will throw an exception
 *
 * @expectedException MissingTableException
 * @return void
 */
	public function testMissingTable() {
		$Article = new ArticleB(false, uniqid());
		$Article->schema();
	}

/**
 * testPkInHAbtmLinkModelArticleB
 *
 * @return void
 */
	public function testPkInHabtmLinkModelArticleB() {
		$this->loadFixtures('Article', 'Tag', 'ArticlesTag');
		$TestModel2 = new ArticleB();
		$this->assertEqual($TestModel2->ArticlesTag->primaryKey, 'article_id');
	}

/**
 * Tests that $cacheSources can only be disabled in the db using model settings, not enabled
 *
 * @return void
 */
	public function testCacheSourcesDisabling() {
		$this->loadFixtures('JoinA', 'JoinB', 'JoinAB', 'JoinC', 'JoinAC');
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
 * testPkInHabtmLinkModel method
 *
	 * @return void
 */
	public function testPkInHabtmLinkModel() {
		//Test Nonconformant Models
		$this->loadFixtures('Content', 'ContentAccount', 'Account', 'JoinC', 'JoinAC', 'ItemsPortfolio');
		$TestModel = new Content();
		$this->assertEqual($TestModel->ContentAccount->primaryKey, 'iContentAccountsId');

		//test conformant models with no PK in the join table
		$this->loadFixtures('Article', 'Tag');
		$TestModel2 = new Article();
		$this->assertEqual($TestModel2->ArticlesTag->primaryKey, 'article_id');

		//test conformant models with PK in join table
		$TestModel3 = new Portfolio();
		$this->assertEqual($TestModel3->ItemsPortfolio->primaryKey, 'id');

		//test conformant models with PK in join table - join table contains extra field
		$this->loadFixtures('JoinA', 'JoinB', 'JoinAB');
		$TestModel4 = new JoinA();
		$this->assertEqual($TestModel4->JoinAsJoinB->primaryKey, 'id');

	}

/**
 * testDynamicBehaviorAttachment method
 *
 * @return void
 */
	public function testDynamicBehaviorAttachment() {
		$this->loadFixtures('Apple', 'Sample', 'Author');
		$TestModel = new Apple();
		$this->assertEqual($TestModel->Behaviors->attached(), array());

		$TestModel->Behaviors->attach('Tree', array('left' => 'left_field', 'right' => 'right_field'));
		$this->assertTrue(is_object($TestModel->Behaviors->Tree));
		$this->assertEqual($TestModel->Behaviors->attached(), array('Tree'));

		$expected = array(
			'parent' => 'parent_id',
			'left' => 'left_field',
			'right' => 'right_field',
			'scope' => '1 = 1',
			'type' => 'nested',
			'__parentChange' => false,
			'recursive' => -1
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
 * testFindWithJoinsOption method
 *
 * @access public
 * @return void
 */
	function testFindWithJoinsOption() {
		$this->loadFixtures('Article', 'User');
		$TestUser =& new User();

		$options = array (
			'fields' => array(
				'user',
				'Article.published',
			),
			'joins' => array (
				array (
					'table' => 'articles',
					'alias' => 'Article',
					'type'  => 'LEFT',
					'conditions' => array(
						'User.id = Article.user_id',
					),
				),
			),
			'group' => array('User.user', 'Article.published'),
			'recursive' => -1,
			'order' => array('User.user')
		);
		$result = $TestUser->find('all', $options);
		$expected = array(
			array('User' => array('user' => 'garrett'), 'Article' => array('published' => '')),
			array('User' => array('user' => 'larry'), 'Article' => array('published' => 'Y')),
			array('User' => array('user' => 'mariano'), 'Article' => array('published' => 'Y')),
			array('User' => array('user' => 'nate'), 'Article' => array('published' => ''))
		);
		$this->assertEqual($result, $expected);
	}

/**
 * Tests cross database joins.  Requires $test and $test2 to both be set in DATABASE_CONFIG
 * NOTE: When testing on MySQL, you must set 'persistent' => false on *both* database connections,
 * or one connection will step on the other.
 */
	public function testCrossDatabaseJoins() {
		$config = new DATABASE_CONFIG();

		$skip = (!isset($config->test) || !isset($config->test2));
		if ($skip) {
			$this->markTestSkipped('Primary and secondary test databases not configured, skipping cross-database
				join tests.  To run theses tests defined $test and $test2 in your database configuration.'
			);
		}

		$this->loadFixtures('Article', 'Tag', 'ArticlesTag', 'User', 'Comment');
		$TestModel = new Article();

		$expected = array(
			array(
				'Article' => array(
					'id' => '1',
					'user_id' => '1',
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'User' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array(
					array(
						'id' => '1',
						'article_id' => '1',
						'user_id' => '2',
						'comment' => 'First Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:45:23',
						'updated' => '2007-03-18 10:47:31'
					),
					array(
						'id' => '2',
						'article_id' => '1',
						'user_id' => '4',
						'comment' => 'Second Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:47:23',
						'updated' => '2007-03-18 10:49:31'
					),
					array(
						'id' => '3',
						'article_id' => '1',
						'user_id' => '1',
						'comment' => 'Third Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:49:23',
						'updated' => '2007-03-18 10:51:31'
					),
					array(
						'id' => '4',
						'article_id' => '1',
						'user_id' => '1',
						'comment' => 'Fourth Comment for First Article',
						'published' => 'N',
						'created' => '2007-03-18 10:51:23',
						'updated' => '2007-03-18 10:53:31'
				)),
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
			))),
			array(
				'Article' => array(
					'id' => '3',
					'user_id' => '1',
					'title' => 'Third Article',
					'body' => 'Third Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array(),
				'Tag' => array()
		));
		$this->assertEqual($TestModel->find('all'), $expected);

		$db2 = ConnectionManager::getDataSource('test2');
		$this->fixtureManager->loadSingle('User', $db2);
		$this->fixtureManager->loadSingle('Comment', $db2);
		$this->assertEqual($TestModel->find('count'), 3);

		$TestModel->User->setDataSource('test2');
		$TestModel->Comment->setDataSource('test2');

		foreach ($expected as $key => $value) {
			unset($value['Comment'], $value['Tag']);
			$expected[$key] = $value;
		}

		$TestModel->recursive = 0;
		$result = $TestModel->find('all');
		$this->assertEqual($expected, $result);

		foreach ($expected as $key => $value) {
			unset($value['Comment'], $value['Tag']);
			$expected[$key] = $value;
		}

		$TestModel->recursive = 0;
		$result = $TestModel->find('all');
		$this->assertEqual($expected, $result);

		$result = Set::extract($TestModel->User->find('all'), '{n}.User.id');
		$this->assertEqual($result, array('1', '2', '3', '4'));
		$this->assertEqual($TestModel->find('all'), $expected);

		$TestModel->Comment->unbindModel(array('hasOne' => array('Attachment')));
		$expected = array(
			array(
				'Comment' => array(
					'id' => '1',
					'article_id' => '1',
					'user_id' => '2',
					'comment' => 'First Comment for First Article',
					'published' => 'Y',
					'created' => '2007-03-18 10:45:23',
					'updated' => '2007-03-18 10:47:31'
				),
				'User' => array(
					'id' => '2',
					'user' => 'nate',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23',
					'updated' => '2007-03-17 01:20:31'
				),
				'Article' => array(
					'id' => '1',
					'user_id' => '1',
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
			)),
			array(
				'Comment' => array(
					'id' => '2',
					'article_id' => '1',
					'user_id' => '4',
					'comment' => 'Second Comment for First Article',
					'published' => 'Y',
					'created' => '2007-03-18 10:47:23',
					'updated' => '2007-03-18 10:49:31'
				),
				'User' => array(
					'id' => '4',
					'user' => 'garrett',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23',
					'updated' => '2007-03-17 01:24:31'
				),
				'Article' => array(
					'id' => '1',
					'user_id' => '1',
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
			)),
			array(
				'Comment' => array(
					'id' => '3',
					'article_id' => '1',
					'user_id' => '1',
					'comment' => 'Third Comment for First Article',
					'published' => 'Y',
					'created' => '2007-03-18 10:49:23',
					'updated' => '2007-03-18 10:51:31'
				),
				'User' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
				),
				'Article' => array(
					'id' => '1',
					'user_id' => '1',
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
			)),
			array(
				'Comment' => array(
					'id' => '4',
					'article_id' => '1',
					'user_id' => '1',
					'comment' => 'Fourth Comment for First Article',
					'published' => 'N',
					'created' => '2007-03-18 10:51:23',
					'updated' => '2007-03-18 10:53:31'
				),
				'User' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
				),
				'Article' => array(
					'id' => '1',
					'user_id' => '1',
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
			)),
			array(
				'Comment' => array(
					'id' => '5',
					'article_id' => '2',
					'user_id' => '1',
					'comment' => 'First Comment for Second Article',
					'published' => 'Y',
					'created' => '2007-03-18 10:53:23',
					'updated' => '2007-03-18 10:55:31'
				),
				'User' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
				),
				'Article' => array(
					'id' => '2',
					'user_id' => '3',
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
			)),
			array(
				'Comment' => array(
					'id' => '6',
					'article_id' => '2',
					'user_id' => '2',
					'comment' => 'Second Comment for Second Article',
					'published' => 'Y',
					'created' => '2007-03-18 10:55:23',
					'updated' => '2007-03-18 10:57:31'
				),
				'User' => array(
					'id' => '2',
					'user' => 'nate',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23',
					'updated' => '2007-03-17 01:20:31'
				),
				'Article' => array(
					'id' => '2',
					'user_id' => '3',
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
		)));
		$this->assertEqual($TestModel->Comment->find('all'), $expected);
	}

/**
 * testDisplayField method
 *
 * @return void
 */
	public function testDisplayField() {
		$this->loadFixtures('Post', 'Comment', 'Person', 'User');
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
 * @return void
 */
	public function testSchema() {
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
 * test deconstruct() with time fields.
 *
 * @return void
 */
	public function testDeconstructFieldsTime() {
		$this->skipIf($this->db instanceof Sqlserver, 'This test is not compatible with SQL Server.');

		$this->loadFixtures('Apple');
		$TestModel = new Apple();

		$data = array();
		$data['Apple']['mytime']['hour'] = '';
		$data['Apple']['mytime']['min'] = '';
		$data['Apple']['mytime']['sec'] = '';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('mytime'=> ''));
		$this->assertEqual($TestModel->data, $expected);

		$data = array();
		$data['Apple']['mytime']['hour'] = '';
		$data['Apple']['mytime']['min'] = '';
		$data['Apple']['mytime']['meridan'] = '';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('mytime'=> ''));
		$this->assertEqual($TestModel->data, $expected, 'Empty values are not returning properly. %s');

		$data = array();
		$data['Apple']['mytime']['hour'] = '12';
		$data['Apple']['mytime']['min'] = '0';
		$data['Apple']['mytime']['meridian'] = 'am';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('mytime'=> '00:00:00'));
		$this->assertEqual($TestModel->data, $expected, 'Midnight is not returning proper values. %s');

		$data = array();
		$data['Apple']['mytime']['hour'] = '00';
		$data['Apple']['mytime']['min'] = '00';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('mytime'=> '00:00:00'));
		$this->assertEqual($TestModel->data, $expected, 'Midnight is not returning proper values. %s');

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

		$db = ConnectionManager::getDataSource('test');
		$data = array();
		$data['Apple']['mytime'] = $db->expression('NOW()');
		$TestModel->data = null;
		$TestModel->set($data);
		$this->assertEqual($TestModel->data, $data);
	}

/**
 * testDeconstructFields with datetime, timestamp, and date fields
 *
 * @return void
 */
	public function testDeconstructFieldsDateTime() {
		$this->skipIf($this->db instanceof Sqlserver, 'This test is not compatible with SQL Server.');

		$this->loadFixtures('Apple');
		$TestModel = new Apple();

		//test null/empty values first
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
		$data['Apple']['date']['year'] = '';
		$data['Apple']['date']['month'] = '';
		$data['Apple']['date']['day'] = '';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple'=> array('date'=> ''));
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
		$expected = array(
			'Apple'=> array(
			'created'=> '',
			'date'=> '2006-12-25'
		));
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
		$expected = array(
			'Apple'=> array(
				'created'=> '2007-08-20 10:12:09',
				'date'=> '2006-12-25'
		));
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

		$db = ConnectionManager::getDataSource('test');
		$data = array();
		$data['Apple']['modified'] = $db->expression('NOW()');
		$TestModel->data = null;
		$TestModel->set($data);
		$this->assertEqual($TestModel->data, $data);
	}

/**
 * testTablePrefixSwitching method
 *
 * @return void
 */
	public function testTablePrefixSwitching() {
		ConnectionManager::create('database1',
				array_merge($this->db->config, array('prefix' => 'aaa_')
		));
		ConnectionManager::create('database2',
			array_merge($this->db->config, array('prefix' => 'bbb_')
		));

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
 * Tests validation parameter order in custom validation methods
 *
 * @return void
 */
	public function testInvalidAssociation() {
		$TestModel = new ValidationTest1();
		$this->assertNull($TestModel->getAssociated('Foo'));
	}

/**
 * testLoadModelSecondIteration method
 *
 * @return void
 */
	public function testLoadModelSecondIteration() {
		$this->loadFixtures('Apple', 'Message', 'Thread', 'Bid');
		$model = new ModelA();
		$this->assertIsA($model,'ModelA');

		$this->assertIsA($model->ModelB, 'ModelB');
		$this->assertIsA($model->ModelB->ModelD, 'ModelD');

		$this->assertIsA($model->ModelC, 'ModelC');
		$this->assertIsA($model->ModelC->ModelD, 'ModelD');
	}

/**
 * ensure that exists() does not persist between method calls reset on create
 *
 * @return void
 */
	public function testResetOfExistsOnCreate() {
		$this->loadFixtures('Article');
		$Article = new Article();
		$Article->id = 1;
		$Article->saveField('title', 'Reset me');
		$Article->delete();
		$Article->id = 1;
		$this->assertFalse($Article->exists());

		$Article->create();
		$this->assertFalse($Article->exists());
		$Article->id = 2;
		$Article->saveField('title', 'Staying alive');
		$result = $Article->read(null, 2);
		$this->assertEqual($result['Article']['title'], 'Staying alive');
	}

/**
 * testUseTableFalseExistsCheck method
 *
 * @return void
 */
	public function testUseTableFalseExistsCheck() {
		$this->loadFixtures('Article');
		$Article = new Article();
		$Article->id = 1337;
		$result = $Article->exists();
		$this->assertFalse($result);

		$Article->useTable = false;
		$Article->id = null;
		$result = $Article->exists();
		$this->assertFalse($result);

		// An article with primary key of '1' has been loaded by the fixtures.
		$Article->useTable = false;
		$Article->id = 1;
		$result = $Article->exists();
		$this->assertTrue($result);
	}

/**
 * testPluginAssociations method
 *
 * @return void
 */
	public function testPluginAssociations() {
		$this->loadFixtures('TestPluginArticle', 'User', 'TestPluginComment');
		$TestModel = new TestPluginArticle();

		$result = $TestModel->find('all');
		$expected = array(
			array(
				'TestPluginArticle' => array(
					'id' => 1,
					'user_id' => 1,
					'title' => 'First Plugin Article',
					'body' => 'First Plugin Article Body',
					'published' => 'Y',
					'created' => '2008-09-24 10:39:23',
					'updated' => '2008-09-24 10:41:31'
				),
				'User' => array(
					'id' => 1,
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
				),
				'TestPluginComment' => array(
					array(
						'id' => 1,
						'article_id' => 1,
						'user_id' => 2,
						'comment' => 'First Comment for First Plugin Article',
						'published' => 'Y',
						'created' => '2008-09-24 10:45:23',
						'updated' => '2008-09-24 10:47:31'
					),
					array(
						'id' => 2,
						'article_id' => 1,
						'user_id' => 4,
						'comment' => 'Second Comment for First Plugin Article',
						'published' => 'Y',
						'created' => '2008-09-24 10:47:23',
						'updated' => '2008-09-24 10:49:31'
					),
					array(
						'id' => 3,
						'article_id' => 1,
						'user_id' => 1,
						'comment' => 'Third Comment for First Plugin Article',
						'published' => 'Y',
						'created' => '2008-09-24 10:49:23',
						'updated' => '2008-09-24 10:51:31'
					),
					array(
						'id' => 4,
						'article_id' => 1,
						'user_id' => 1,
						'comment' => 'Fourth Comment for First Plugin Article',
						'published' => 'N',
						'created' => '2008-09-24 10:51:23',
						'updated' => '2008-09-24 10:53:31'
			))),
			array(
				'TestPluginArticle' => array(
					'id' => 2,
					'user_id' => 3,
					'title' => 'Second Plugin Article',
					'body' => 'Second Plugin Article Body',
					'published' => 'Y',
					'created' => '2008-09-24 10:41:23',
					'updated' => '2008-09-24 10:43:31'
				),
				'User' => array(
					'id' => 3,
					'user' => 'larry',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23',
					'updated' => '2007-03-17 01:22:31'
				),
				'TestPluginComment' => array(
					array(
						'id' => 5,
						'article_id' => 2,
						'user_id' => 1,
						'comment' => 'First Comment for Second Plugin Article',
						'published' => 'Y',
						'created' => '2008-09-24 10:53:23',
						'updated' => '2008-09-24 10:55:31'
					),
					array(
						'id' => 6,
						'article_id' => 2,
						'user_id' => 2,
						'comment' => 'Second Comment for Second Plugin Article',
						'published' => 'Y',
						'created' => '2008-09-24 10:55:23',
						'updated' => '2008-09-24 10:57:31'
			))),
			array(
				'TestPluginArticle' => array(
					'id' => 3,
					'user_id' => 1,
					'title' => 'Third Plugin Article',
					'body' => 'Third Plugin Article Body',
					'published' => 'Y',
					'created' => '2008-09-24 10:43:23',
					'updated' => '2008-09-24 10:45:31'
				),
				'User' => array(
					'id' => 1,
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
				),
				'TestPluginComment' => array()
		));

		$this->assertEqual($expected, $result);
	}

/**
 * Tests getAssociated method
 *
 * @return void
 */
	public function testGetAssociated() {
		$this->loadFixtures('Article', 'Tag');
		$Article = ClassRegistry::init('Article');

		$assocTypes = array('hasMany', 'hasOne', 'belongsTo', 'hasAndBelongsToMany');
		foreach ($assocTypes as $type) {
			 $this->assertEqual($Article->getAssociated($type), array_keys($Article->{$type}));
		}

		$Article->bindModel(array('hasMany' => array('Category')));
		$this->assertEqual($Article->getAssociated('hasMany'), array('Comment', 'Category'));

		$results = $Article->getAssociated();
		$results = array_keys($results);
		sort($results);
		$this->assertEqual($results, array('Category', 'Comment', 'Tag', 'User'));

		$Article->unbindModel(array('hasAndBelongsToMany' => array('Tag')));
		$this->assertEqual($Article->getAssociated('hasAndBelongsToMany'), array());

		$result = $Article->getAssociated('Category');
		$expected = array(
			'className' => 'Category',
			'foreignKey' => 'article_id',
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'dependent' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => '',
			'association' => 'hasMany',
		);
		$this->assertEqual($expected, $result);
	}

/**
 * testAutoConstructAssociations method
 *
 * @return void
 */
	public function testAutoConstructAssociations() {
		$this->loadFixtures('User', 'ArticleFeatured', 'Featured', 'ArticleFeaturedsTags');
		$TestModel = new AssociationTest1();

		$result = $TestModel->hasAndBelongsToMany;
		$expected = array('AssociationTest2' => array(
				'unique' => false,
				'joinTable' => 'join_as_join_bs',
				'foreignKey' => false,
				'className' => 'AssociationTest2',
				'with' => 'JoinAsJoinB',
				'dynamicWith' => true,
				'associationForeignKey' => 'join_b_id',
				'conditions' => '', 'fields' => '', 'order' => '', 'limit' => '', 'offset' => '',
				'finderQuery' => '', 'deleteQuery' => '', 'insertQuery' => ''
		));
		$this->assertEqual($expected, $result);

		$TestModel = new ArticleFeatured();
		$TestFakeModel = new ArticleFeatured(array('table' => false));

		$expected = array(
			'User' => array(
				'className' => 'User', 'foreignKey' => 'user_id',
				'conditions' => '', 'fields' => '', 'order' => '', 'counterCache' => ''
			),
			'Category' => array(
				'className' => 'Category', 'foreignKey' => 'category_id',
				'conditions' => '', 'fields' => '', 'order' => '', 'counterCache' => ''
			)
		);
		$this->assertIdentical($TestModel->belongsTo, $expected);
		$this->assertIdentical($TestFakeModel->belongsTo, $expected);

		$this->assertEqual($TestModel->User->name, 'User');
		$this->assertEqual($TestFakeModel->User->name, 'User');
		$this->assertEqual($TestModel->Category->name, 'Category');
		$this->assertEqual($TestFakeModel->Category->name, 'Category');

		$expected = array(
			'Featured' => array(
				'className' => 'Featured',
				'foreignKey' => 'article_featured_id',
				'conditions' => '',
				'fields' => '',
				'order' => '',
				'dependent' => ''
		));

		$this->assertIdentical($TestModel->hasOne, $expected);
		$this->assertIdentical($TestFakeModel->hasOne, $expected);

		$this->assertEqual($TestModel->Featured->name, 'Featured');
		$this->assertEqual($TestFakeModel->Featured->name, 'Featured');

		$expected = array(
			'Comment' => array(
				'className' => 'Comment',
				'dependent' => true,
				'foreignKey' => 'article_featured_id',
				'conditions' => '',
				'fields' => '',
				'order' => '',
				'limit' => '',
				'offset' => '',
				'exclusive' => '',
				'finderQuery' => '',
				'counterQuery' => ''
		));

		$this->assertIdentical($TestModel->hasMany, $expected);
		$this->assertIdentical($TestFakeModel->hasMany, $expected);

		$this->assertEqual($TestModel->Comment->name, 'Comment');
		$this->assertEqual($TestFakeModel->Comment->name, 'Comment');

		$expected = array(
			'Tag' => array(
				'className' => 'Tag',
				'joinTable' => 'article_featureds_tags',
				'with' => 'ArticleFeaturedsTag',
				'dynamicWith' => true,
				'foreignKey' => 'article_featured_id',
				'associationForeignKey' => 'tag_id',
				'conditions' => '',
				'fields' => '',
				'order' => '',
				'limit' => '',
				'offset' => '',
				'unique' => true,
				'finderQuery' => '',
				'deleteQuery' => '',
				'insertQuery' => ''
		));

		$this->assertIdentical($TestModel->hasAndBelongsToMany, $expected);
		$this->assertIdentical($TestFakeModel->hasAndBelongsToMany, $expected);

		$this->assertEqual($TestModel->Tag->name, 'Tag');
		$this->assertEqual($TestFakeModel->Tag->name, 'Tag');
	}

/**
 * test creating associations with plugins. Ensure a double alias isn't created
 *
 * @return void
 */
	public function testAutoConstructPluginAssociations() {
		$Comment = ClassRegistry::init('TestPluginComment');

		$this->assertEquals(2, count($Comment->belongsTo), 'Too many associations');
		$this->assertFalse(isset($Comment->belongsTo['TestPlugin.User']));
		$this->assertTrue(isset($Comment->belongsTo['User']), 'Missing association');
		$this->assertTrue(isset($Comment->belongsTo['TestPluginArticle']), 'Missing association');
	}

/**
 * test Model::__construct
 *
 * ensure that $actsAS and $findMethods are merged.
 *
 * @return void
 */
	public function testConstruct() {
		$this->loadFixtures('Post');

		$TestModel = ClassRegistry::init('MergeVarPluginPost');
		$this->assertEqual($TestModel->actsAs, array('Containable' => null, 'Tree' => null));
		$this->assertTrue(isset($TestModel->Behaviors->Containable));
		$this->assertTrue(isset($TestModel->Behaviors->Tree));

		$TestModel = ClassRegistry::init('MergeVarPluginComment');
		$expected = array('Containable' => array('some_settings'));
		$this->assertEqual($TestModel->actsAs, $expected);
		$this->assertTrue(isset($TestModel->Behaviors->Containable));
	}

/**
 * test Model::__construct
 *
 * ensure that $actsAS and $findMethods are merged.
 *
 * @return void
 */
	public function testConstructWithAlternateDataSource() {
		$TestModel = ClassRegistry::init(array(
			'class' => 'DoesntMatter', 'ds' => 'test', 'table' => false
		));
		$this->assertEqual('test', $TestModel->useDbConfig);

		//deprecated but test it anyway
		$NewVoid = new TheVoid(null, false, 'other');
		$this->assertEqual('other', $NewVoid->useDbConfig);
	}

/**
 * testColumnTypeFetching method
 *
 * @return void
 */
	public function testColumnTypeFetching() {
		$model = new Test();
		$this->assertEqual($model->getColumnType('id'), 'integer');
		$this->assertEqual($model->getColumnType('notes'), 'text');
		$this->assertEqual($model->getColumnType('updated'), 'datetime');
		$this->assertEqual($model->getColumnType('unknown'), null);

		$model = new Article();
		$this->assertEqual($model->getColumnType('User.created'), 'datetime');
		$this->assertEqual($model->getColumnType('Tag.id'), 'integer');
		$this->assertEqual($model->getColumnType('Article.id'), 'integer');
	}

/**
 * testHabtmUniqueKey method
 *
 * @return void
 */
	public function testHabtmUniqueKey() {
		$model = new Item();
		$this->assertFalse($model->hasAndBelongsToMany['Portfolio']['unique']);
	}

/**
 * testIdentity method
 *
 * @return void
 */
	public function testIdentity() {
		$TestModel = new Test();
		$result = $TestModel->alias;
		$expected = 'Test';
		$this->assertEqual($expected, $result);

		$TestModel = new TestAlias();
		$result = $TestModel->alias;
		$expected = 'TestAlias';
		$this->assertEqual($expected, $result);

		$TestModel = new Test(array('alias' => 'AnotherTest'));
		$result = $TestModel->alias;
		$expected = 'AnotherTest';
		$this->assertEqual($expected, $result);
	}

/**
 * testWithAssociation method
 *
 * @return void
 */
	public function testWithAssociation() {
		$this->loadFixtures('Something', 'SomethingElse', 'JoinThing');
		$TestModel = new Something();
		$result = $TestModel->SomethingElse->find('all');

		$expected = array(
			array(
				'SomethingElse' => array(
					'id' => '1',
					'title' => 'First Post',
					'body' => 'First Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'Something' => array(
					array(
						'id' => '3',
						'title' => 'Third Post',
						'body' => 'Third Post Body',
						'published' => 'Y',
						'created' => '2007-03-18 10:43:23',
						'updated' => '2007-03-18 10:45:31',
						'JoinThing' => array(
							'id' => '3',
							'something_id' => '3',
							'something_else_id' => '1',
							'doomed' => true,
							'created' => '2007-03-18 10:43:23',
							'updated' => '2007-03-18 10:45:31'
			)))),
			array(
				'SomethingElse' => array(
					'id' => '2',
					'title' => 'Second Post',
					'body' => 'Second Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				),
				'Something' => array(
					array(
						'id' => '1',
						'title' => 'First Post',
						'body' => 'First Post Body',
						'published' => 'Y',
						'created' => '2007-03-18 10:39:23',
						'updated' => '2007-03-18 10:41:31',
						'JoinThing' => array(
							'id' => '1',
							'something_id' => '1',
							'something_else_id' => '2',
							'doomed' => true,
							'created' => '2007-03-18 10:39:23',
							'updated' => '2007-03-18 10:41:31'
			)))),
			array(
				'SomethingElse' => array(
					'id' => '3',
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				),
				'Something' => array(
					array(
						'id' => '2',
						'title' => 'Second Post',
						'body' => 'Second Post Body',
						'published' => 'Y',
						'created' => '2007-03-18 10:41:23',
						'updated' => '2007-03-18 10:43:31',
						'JoinThing' => array(
							'id' => '2',
							'something_id' => '2',
							'something_else_id' => '3',
							'doomed' => false,
							'created' => '2007-03-18 10:41:23',
							'updated' => '2007-03-18 10:43:31'
		)))));
		$this->assertEqual($expected, $result);

		$result = $TestModel->find('all');
		$expected = array(
			array(
				'Something' => array(
					'id' => '1',
					'title' => 'First Post',
					'body' => 'First Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'SomethingElse' => array(
					array(
						'id' => '2',
						'title' => 'Second Post',
						'body' => 'Second Post Body',
						'published' => 'Y',
						'created' => '2007-03-18 10:41:23',
						'updated' => '2007-03-18 10:43:31',
						'JoinThing' => array(
							'doomed' => true,
							'something_id' => '1',
							'something_else_id' => '2'
			)))),
			array(
				'Something' => array(
					'id' => '2',
					'title' => 'Second Post',
					'body' => 'Second Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				),
				'SomethingElse' => array(
					array(
						'id' => '3',
						'title' => 'Third Post',
						'body' => 'Third Post Body',
						'published' => 'Y',
						'created' => '2007-03-18 10:43:23',
						'updated' => '2007-03-18 10:45:31',
						'JoinThing' => array(
							'doomed' => false,
							'something_id' => '2',
							'something_else_id' => '3'
			)))),
			array(
				'Something' => array(
					'id' => '3',
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				),
				'SomethingElse' => array(
					array(
						'id' => '1',
						'title' => 'First Post',
						'body' => 'First Post Body',
						'published' => 'Y',
						'created' => '2007-03-18 10:39:23',
						'updated' => '2007-03-18 10:41:31',
						'JoinThing' => array(
							'doomed' => true,
							'something_id' => '3',
							'something_else_id' => '1'
		)))));
		$this->assertEqual($expected, $result);

		$result = $TestModel->findById(1);
		$expected = array(
			'Something' => array(
				'id' => '1',
				'title' => 'First Post',
				'body' => 'First Post Body',
				'published' => 'Y',
				'created' => '2007-03-18 10:39:23',
				'updated' => '2007-03-18 10:41:31'
			),
			'SomethingElse' => array(
				array(
					'id' => '2',
					'title' => 'Second Post',
					'body' => 'Second Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31',
					'JoinThing' => array(
						'doomed' => true,
						'something_id' => '1',
						'something_else_id' => '2'
		))));
		$this->assertEqual($expected, $result);

		$expected = $TestModel->findById(1);
		$TestModel->set($expected);
		$TestModel->save();
		$result = $TestModel->findById(1);
		$this->assertEqual($expected, $result);

		$TestModel->hasAndBelongsToMany['SomethingElse']['unique'] = false;
		$TestModel->create(array(
			'Something' => array('id' => 1),
			'SomethingElse' => array(3, array(
				'something_else_id' => 1,
				'doomed' => true
		))));

		$ts = date('Y-m-d H:i:s');
		$TestModel->save();

		$TestModel->hasAndBelongsToMany['SomethingElse']['order'] = 'SomethingElse.id ASC';
		$result = $TestModel->findById(1);
		$expected = array(
			'Something' => array(
				'id' => '1',
				'title' => 'First Post',
				'body' => 'First Post Body',
				'published' => 'Y',
				'created' => '2007-03-18 10:39:23'
			),
			'SomethingElse' => array(
				array(
					'id' => '1',
					'title' => 'First Post',
					'body' => 'First Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31',
					'JoinThing' => array(
						'doomed' => true,
						'something_id' => '1',
						'something_else_id' => '1'
				)
			),
				array(
					'id' => '2',
					'title' => 'Second Post',
					'body' => 'Second Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31',
					'JoinThing' => array(
						'doomed' => true,
						'something_id' => '1',
						'something_else_id' => '2'
				)
			),
				array(
					'id' => '3',
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31',
					'JoinThing' => array(
						'doomed' => false,
						'something_id' => '1',
						'something_else_id' => '3')
					)
				)
			);
		$this->assertTrue($result['Something']['updated'] >= $ts);
		unset($result['Something']['updated']);
		$this->assertEqual($expected, $result);
	}

/**
 * testFindSelfAssociations method
 *
 * @return void
 */
	public function testFindSelfAssociations() {
		$this->loadFixtures('Person');

		$TestModel = new Person();
		$TestModel->recursive = 2;
		$result = $TestModel->read(null, 1);
		$expected = array(
			'Person' => array(
				'id' => 1,
				'name' => 'person',
				'mother_id' => 2,
				'father_id' => 3
			),
			'Mother' => array(
				'id' => 2,
				'name' => 'mother',
				'mother_id' => 4,
				'father_id' => 5,
				'Mother' => array(
					'id' => 4,
					'name' => 'mother - grand mother',
					'mother_id' => 0,
					'father_id' => 0
				),
				'Father' => array(
					'id' => 5,
					'name' => 'mother - grand father',
					'mother_id' => 0,
					'father_id' => 0
			)),
			'Father' => array(
				'id' => 3,
				'name' => 'father',
				'mother_id' => 6,
				'father_id' => 7,
				'Father' => array(
					'id' => 7,
					'name' => 'father - grand father',
					'mother_id' => 0,
					'father_id' => 0
				),
				'Mother' => array(
					'id' => 6,
					'name' => 'father - grand mother',
					'mother_id' => 0,
					'father_id' => 0
		)));

		$this->assertEqual($expected, $result);

		$TestModel->recursive = 3;
		$result = $TestModel->read(null, 1);
		$expected = array(
			'Person' => array(
				'id' => 1,
				'name' => 'person',
				'mother_id' => 2,
				'father_id' => 3
			),
			'Mother' => array(
				'id' => 2,
				'name' => 'mother',
				'mother_id' => 4,
				'father_id' => 5,
				'Mother' => array(
					'id' => 4,
					'name' => 'mother - grand mother',
					'mother_id' => 0,
					'father_id' => 0,
					'Mother' => array(),
					'Father' => array()),
				'Father' => array(
					'id' => 5,
					'name' => 'mother - grand father',
					'mother_id' => 0,
					'father_id' => 0,
					'Father' => array(),
					'Mother' => array()
			)),
			'Father' => array(
				'id' => 3,
				'name' => 'father',
				'mother_id' => 6,
				'father_id' => 7,
				'Father' => array(
					'id' => 7,
					'name' => 'father - grand father',
					'mother_id' => 0,
					'father_id' => 0,
					'Father' => array(),
					'Mother' => array()
				),
				'Mother' => array(
					'id' => 6,
					'name' => 'father - grand mother',
					'mother_id' => 0,
					'father_id' => 0,
					'Mother' => array(),
					'Father' => array()
		)));

		$this->assertEqual($expected, $result);
	}

/**
 * testDynamicAssociations method
 *
 * @return void
 */
	public function testDynamicAssociations() {
		$this->loadFixtures('Article', 'Comment');
		$TestModel = new Article();

		$TestModel->belongsTo = $TestModel->hasAndBelongsToMany = $TestModel->hasOne = array();
		$TestModel->hasMany['Comment'] = array_merge($TestModel->hasMany['Comment'], array(
			'foreignKey' => false,
			'conditions' => array('Comment.user_id =' => '2')
		));
		$result = $TestModel->find('all');
		$expected = array(
			array(
				'Article' => array(
					'id' => '1',
					'user_id' => '1',
					'title' => 'First Article',
					'body' => 'First Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'Comment' => array(
					array(
						'id' => '1',
						'article_id' => '1',
						'user_id' => '2',
						'comment' => 'First Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:45:23',
						'updated' => '2007-03-18 10:47:31'
					),
					array(
						'id' => '6',
						'article_id' => '2',
						'user_id' => '2',
						'comment' => 'Second Comment for Second Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:55:23',
						'updated' => '2007-03-18 10:57:31'
			))),
			array(
				'Article' => array(
					'id' => '2',
					'user_id' => '3',
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				),
				'Comment' => array(
					array(
						'id' => '1',
						'article_id' => '1',
						'user_id' => '2',
						'comment' => 'First Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:45:23',
						'updated' => '2007-03-18 10:47:31'
					),
					array(
						'id' => '6',
						'article_id' => '2',
						'user_id' => '2',
						'comment' => 'Second Comment for Second Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:55:23',
						'updated' => '2007-03-18 10:57:31'
			))),
			array(
				'Article' => array(
					'id' => '3',
					'user_id' => '1',
					'title' => 'Third Article',
					'body' => 'Third Article Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31'
				),
				'Comment' => array(
					array(
						'id' => '1',
						'article_id' => '1',
						'user_id' => '2',
						'comment' => 'First Comment for First Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:45:23',
						'updated' => '2007-03-18 10:47:31'
					),
					array(
						'id' => '6',
						'article_id' => '2',
						'user_id' => '2',
						'comment' => 'Second Comment for Second Article',
						'published' => 'Y',
						'created' => '2007-03-18 10:55:23',
						'updated' => '2007-03-18 10:57:31'
		))));

		$this->assertEqual($expected, $result);
	}

/**
 * testCreation method
 *
 * @return void
 */
	public function testCreation() {
		$this->loadFixtures('Article', 'ArticleFeaturedsTags', 'User', 'Featured');
		$TestModel = new Test();
		$result = $TestModel->create();
		$expected = array('Test' => array('notes' => 'write some notes here'));
		$this->assertEqual($expected, $result);
		$TestModel = new User();
		$result = $TestModel->schema();

		if (isset($this->db->columns['primary_key']['length'])) {
			$intLength = $this->db->columns['primary_key']['length'];
		} elseif (isset($this->db->columns['integer']['length'])) {
			$intLength = $this->db->columns['integer']['length'];
		} else {
			$intLength = 11;
		}
		foreach (array('collate', 'charset', 'comment') as $type) {
			foreach ($result as $i => $r) {
				unset($result[$i][$type]);
			}
		}

		$expected = array(
			'id' => array(
				'type' => 'integer',
				'null' => false,
				'default' => null,
				'length' => $intLength,
				'key' => 'primary'
			),
			'user' => array(
				'type' => 'string',
				'null' => false,
				'default' => '',
				'length' => 255
			),
			'password' => array(
				'type' => 'string',
				'null' => false,
				'default' => '',
				'length' => 255
			),
			'created' => array(
				'type' => 'datetime',
				'null' => true,
				'default' => null,
				'length' => null
			),
			'updated'=> array(
				'type' => 'datetime',
				'null' => true,
				'default' => null,
				'length' => null
		));

		$this->assertEqual($expected, $result);

		$TestModel = new Article();
		$result = $TestModel->create();
		$expected = array('Article' => array('published' => 'N'));
		$this->assertEqual($expected, $result);

		$FeaturedModel = new Featured();
		$data = array(
			'article_featured_id' => 1,
			'category_id' => 1,
			'published_date' => array(
				'year' => 2008,
				'month' => 06,
				'day' => 11
			),
			'end_date' => array(
				'year' => 2008,
				'month' => 06,
				'day' => 20
		));

		$expected = array(
			'Featured' => array(
				'article_featured_id' => 1,
				'category_id' => 1,
				'published_date' => '2008-06-11 00:00:00',
				'end_date' => '2008-06-20 00:00:00'
		));

		$this->assertEqual($FeaturedModel->create($data), $expected);

		$data = array(
			'published_date' => array(
				'year' => 2008,
				'month' => 06,
				'day' => 11
			),
			'end_date' => array(
				'year' => 2008,
				'month' => 06,
				'day' => 20
			),
			'article_featured_id' => 1,
			'category_id' => 1
		);

		$expected = array(
			'Featured' => array(
				'published_date' => '2008-06-11 00:00:00',
				'end_date' => '2008-06-20 00:00:00',
				'article_featured_id' => 1,
				'category_id' => 1
		));

		$this->assertEqual($FeaturedModel->create($data), $expected);
	}

/**
 * testEscapeField to prove it escapes the field well even when it has part of the alias on it
 * @see ttp://cakephp.lighthouseapp.com/projects/42648-cakephp-1x/tickets/473-escapefield-doesnt-consistently-prepend-modelname
 *
 * @return void
 */
	public function testEscapeField() {
		$TestModel = new Test();
		$db = $TestModel->getDataSource();

		$result = $TestModel->escapeField('test_field');
		$expected = $db->name('Test.test_field');
		$this->assertEqual($expected, $result);

		$result = $TestModel->escapeField('TestField');
		$expected = $db->name('Test.TestField');
		$this->assertEqual($expected, $result);

		$result = $TestModel->escapeField('DomainHandle', 'Domain');
		$expected = $db->name('Domain.DomainHandle');
		$this->assertEqual($expected, $result);

		ConnectionManager::create('mock', array('datasource' => 'DboMock'));
		$TestModel->setDataSource('mock');
		$db = $TestModel->getDataSource();

		$result = $TestModel->escapeField('DomainHandle', 'Domain');
		$expected = $db->name('Domain.DomainHandle');
		$this->assertEqual($expected, $result);
	}

/**
 * test that model->hasMethod checks self and behaviors.
 *
 * @return void
 */
	public function testHasMethod() {
		$Article = new Article();
		$Article->Behaviors = $this->getMock('BehaviorCollection');

		$Article->Behaviors->expects($this->at(0))
			->method('hasMethod')
			->will($this->returnValue(true));

		$Article->Behaviors->expects($this->at(1))
			->method('hasMethod')
			->will($this->returnValue(false));

		$this->assertTrue($Article->hasMethod('find'));

		$this->assertTrue($Article->hasMethod('pass'));
		$this->assertFalse($Article->hasMethod('fail'));
	}
}
