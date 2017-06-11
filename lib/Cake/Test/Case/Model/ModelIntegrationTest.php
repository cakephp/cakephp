<?php
/**
 * ModelIntegrationTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

require_once dirname(__FILE__) . DS . 'ModelTestBase.php';

App::uses('DboSource', 'Model/Datasource');
App::uses('DboMock', 'Model/Datasource');

/**
 * DboMock class
 * A Dbo Source driver to mock a connection and a identity name() method
 */
class DboMock extends DboSource {

/**
 * Returns the $field without modifications
 *
 * @return string
 */
	public function name($field) {
		return $field;
	}

/**
 * Returns true to fake a database connection
 *
 * @return bool true
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
		$this->assertEquals('article_featureds_tags', $Article->hasAndBelongsToMany['Tag']['joinTable']);
		$this->assertEquals('tag_id', $Article->hasAndBelongsToMany['Tag']['associationForeignKey']);
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
		$TestModel = new ArticleB();
		$this->assertEquals('article_id', $TestModel->ArticlesTag->primaryKey);
	}

/**
 * Tests that $cacheSources is restored despite the settings on the model.
 *
 * @return void
 */
	public function testCacheSourcesRestored() {
		$this->loadFixtures('JoinA', 'JoinB', 'JoinAB', 'JoinC', 'JoinAC');
		$this->db->cacheSources = true;
		$TestModel = new JoinA();
		$TestModel->cacheSources = false;
		$TestModel->setSource('join_as');
		$this->assertTrue($this->db->cacheSources);

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
		$this->assertEquals('iContentAccountsId', $TestModel->ContentAccount->primaryKey);

		//test conformant models with no PK in the join table
		$this->loadFixtures('Article', 'Tag');
		$TestModel = new Article();
		$this->assertEquals('article_id', $TestModel->ArticlesTag->primaryKey);

		//test conformant models with PK in join table
		$TestModel = new Portfolio();
		$this->assertEquals('id', $TestModel->ItemsPortfolio->primaryKey);

		//test conformant models with PK in join table - join table contains extra field
		$this->loadFixtures('JoinA', 'JoinB', 'JoinAB');
		$TestModel = new JoinA();
		$this->assertEquals('id', $TestModel->JoinAsJoinB->primaryKey);
	}

/**
 * testDynamicBehaviorAttachment method
 *
 * @return void
 */
	public function testDynamicBehaviorAttachment() {
		$this->loadFixtures('Apple', 'Sample', 'Author');
		$TestModel = new Apple();
		$this->assertEquals(array(), $TestModel->Behaviors->loaded());

		$TestModel->Behaviors->load('Tree', array('left' => 'left_field', 'right' => 'right_field'));
		$this->assertTrue(is_object($TestModel->Behaviors->Tree));
		$this->assertEquals(array('Tree'), $TestModel->Behaviors->loaded());

		$expected = array(
			'parent' => 'parent_id',
			'left' => 'left_field',
			'right' => 'right_field',
			'scope' => '1 = 1',
			'type' => 'nested',
			'__parentChange' => false,
			'recursive' => -1,
			'level' => null
		);
		$this->assertEquals($expected, $TestModel->Behaviors->Tree->settings['Apple']);

		$TestModel->Behaviors->load('Tree', array('enabled' => false));
		$this->assertEquals($expected, $TestModel->Behaviors->Tree->settings['Apple']);
		$this->assertEquals(array('Tree'), $TestModel->Behaviors->loaded());

		$TestModel->Behaviors->unload('Tree');
		$this->assertEquals(array(), $TestModel->Behaviors->loaded());
		$this->assertFalse(isset($TestModel->Behaviors->Tree));
	}

/**
 * testTreeWithContainable method
 *
 * @return void
 */
	public function testTreeWithContainable() {
		$this->loadFixtures('Ad', 'Campaign');
		$TestModel = new Ad();
		$TestModel->Behaviors->load('Tree');
		$TestModel->Behaviors->load('Containable');

		$node = $TestModel->findById(2);
		$node['Ad']['parent_id'] = 1;
		$TestModel->save($node);

		$result = $TestModel->getParentNode(array('id' => 2, 'contain' => 'Campaign'));
		$this->assertTrue(array_key_exists('Campaign', $result));

		$result = $TestModel->children(array('id' => 1, 'contain' => 'Campaign'));
		$this->assertTrue(array_key_exists('Campaign', $result[0]));

		$result = $TestModel->getPath(array('id' => 2, 'contain' => 'Campaign'));
		$this->assertTrue(array_key_exists('Campaign', $result[0]));
		$this->assertTrue(array_key_exists('Campaign', $result[1]));
	}

/**
 * testFindWithJoinsOption method
 *
 * @return void
 */
	public function testFindWithJoinsOption() {
		$this->loadFixtures('Article', 'User');
		$TestUser = new User();

		$options = array(
			'fields' => array(
				'user',
				'Article.published',
			),
			'joins' => array(
				array(
					'table' => 'articles',
					'alias' => 'Article',
					'type' => 'LEFT',
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
		$this->assertEquals($expected, $result);
	}

/**
 * Tests cross database joins. Requires $test and $test2 to both be set in DATABASE_CONFIG
 * NOTE: When testing on MySQL, you must set 'persistent' => false on *both* database connections,
 * or one connection will step on the other.
 *
 * @return void
 */
	public function testCrossDatabaseJoins() {
		$config = ConnectionManager::enumConnectionObjects();

		$skip = (!isset($config['test']) || !isset($config['test2']));
		if ($skip) {
			$this->markTestSkipped('Primary and secondary test databases not configured, skipping cross-database
				join tests. To run theses tests defined $test and $test2 in your database configuration.'
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
		$this->assertEquals($expected, $TestModel->find('all'));

		$db2 = ConnectionManager::getDataSource('test2');
		$this->fixtureManager->loadSingle('User', $db2);
		$this->fixtureManager->loadSingle('Comment', $db2);
		$this->assertEquals(3, $TestModel->find('count'));

		$TestModel->User->setDataSource('test2');
		$TestModel->Comment->setDataSource('test2');

		foreach ($expected as $key => $value) {
			unset($value['Comment'], $value['Tag']);
			$expected[$key] = $value;
		}

		$TestModel->recursive = 0;
		$result = $TestModel->find('all');
		$this->assertEquals($expected, $result);

		foreach ($expected as $key => $value) {
			unset($value['Comment'], $value['Tag']);
			$expected[$key] = $value;
		}

		$TestModel->recursive = 0;
		$result = $TestModel->find('all');
		$this->assertEquals($expected, $result);

		$result = Hash::extract($TestModel->User->find('all'), '{n}.User.id');
		$this->assertEquals(array('1', '2', '3', '4'), $result);
		$this->assertEquals($expected, $TestModel->find('all'));

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
		$this->assertEquals($expected, $TestModel->Comment->find('all'));
	}

/**
 * test HABM operations without clobbering existing records #275
 *
 * @return void
 */
	public function testHABTMKeepExisting() {
		$this->loadFixtures('Site', 'Domain', 'DomainsSite');

		$Site = new Site();
		$results = $Site->find('count');
		$expected = 3;
		$this->assertEquals($expected, $results);

		$data = $Site->findById(1);

		// include api.cakephp.org
		$data['Domain'] = array('Domain' => array(1, 2, 3));
		$Site->save($data);

		$Site->id = 1;
		$results = $Site->read();
		$expected = 3; // 3 domains belonging to cakephp
		$this->assertEquals($expected, count($results['Domain']));

		$Site->id = 2;
		$results = $Site->read();
		$expected = 2; // 2 domains belonging to markstory
		$this->assertEquals($expected, count($results['Domain']));

		$Site->id = 3;
		$results = $Site->read();
		$expected = 2;
		$this->assertEquals($expected, count($results['Domain']));
		$results['Domain'] = array('Domain' => array(7));
		$Site->save($results); // remove association from domain 6
		$results = $Site->read();
		$expected = 1; // only 1 domain left belonging to rchavik
		$this->assertEquals($expected, count($results['Domain']));

		// add deleted domain back
		$results['Domain'] = array('Domain' => array(6, 7));
		$Site->save($results);
		$results = $Site->read();
		$expected = 2; // 2 domains belonging to rchavik
		$this->assertEquals($expected, count($results['Domain']));

		$Site->DomainsSite->id = $results['Domain'][0]['DomainsSite']['id'];
		$Site->DomainsSite->saveField('active', true);

		$results = $Site->Domain->DomainsSite->find('count', array(
			'conditions' => array(
				'DomainsSite.active' => true,
			),
		));
		$expected = 5;
		$this->assertEquals($expected, $results);

		// activate api.cakephp.org
		$activated = $Site->DomainsSite->findByDomainId(3);
		$activated['DomainsSite']['active'] = true;
		$Site->DomainsSite->save($activated);

		$results = $Site->DomainsSite->find('count', array(
			'conditions' => array(
				'DomainsSite.active' => true,
			),
		));
		$expected = 6;
		$this->assertEquals($expected, $results);

		// remove 2 previously active domains, and leave $activated alone
		$data = array(
			'Site' => array('id' => 1, 'name' => 'cakephp (modified)'),
			'Domain' => array(
				'Domain' => array(3),
			)
		);
		$Site->create($data);
		$Site->save($data);

		// tests that record is still identical prior to removal
		$Site->id = 1;
		$results = $Site->read();
		unset($results['Domain'][0]['DomainsSite']['updated']);
		unset($activated['DomainsSite']['updated']);
		$this->assertEquals($activated['DomainsSite'], $results['Domain'][0]['DomainsSite']);
	}

/**
 * testHABTMKeepExistingAlternateDataFormat
 *
 * @return void
 */
	public function testHABTMKeepExistingAlternateDataFormat() {
		$this->loadFixtures('Site', 'Domain', 'DomainsSite');

		$Site = new Site();

		$expected = array(
			array(
				'DomainsSite' => array(
					'id' => 1,
					'site_id' => 1,
					'domain_id' => 1,
					'active' => true,
					'created' => '2007-03-17 01:16:23'
				)
			),
			array(
				'DomainsSite' => array(
					'id' => 2,
					'site_id' => 1,
					'domain_id' => 2,
					'active' => true,
					'created' => '2007-03-17 01:16:23'
				)
			)
		);
		$result = $Site->DomainsSite->find('all', array(
			'conditions' => array('DomainsSite.site_id' => 1),
			'fields' => array(
				'DomainsSite.id',
				'DomainsSite.site_id',
				'DomainsSite.domain_id',
				'DomainsSite.active',
				'DomainsSite.created'
			),
			'order' => 'DomainsSite.id'
		));
		$this->assertEquals($expected, $result);

		$time = date('Y-m-d H:i:s');
		$data = array(
			'Site' => array(
				'id' => 1
			),
			'Domain' => array(
				array(
					'site_id' => 1,
					'domain_id'	=> 3,
					'created' => $time,
				),
				array(
					'id' => 2,
					'site_id' => 1,
					'domain_id'	=> 2
				),
			)
		);
		$Site->save($data);
		$expected = array(
			array(
				'DomainsSite' => array(
					'id' => 2,
					'site_id' => 1,
					'domain_id' => 2,
					'active' => true,
					'created' => '2007-03-17 01:16:23'
				)
			),
			array(
				'DomainsSite' => array(
					'id' => 7,
					'site_id' => 1,
					'domain_id' => 3,
					'active' => false,
					'created' => $time
				)
			)
		);
		$result = $Site->DomainsSite->find('all', array(
			'conditions' => array('DomainsSite.site_id' => 1),
			'fields' => array(
				'DomainsSite.id',
				'DomainsSite.site_id',
				'DomainsSite.domain_id',
				'DomainsSite.active',
				'DomainsSite.created'
			),
			'order' => 'DomainsSite.id'
		));
		$this->assertEquals($expected, $result);
	}

/**
 * test HABM operations without clobbering existing records #275
 *
 * @return void
 */
	public function testHABTMKeepExistingWithThreeDbs() {
		$config = ConnectionManager::enumConnectionObjects();
		$this->skipIf($this->db instanceof Sqlite, 'This test is not compatible with Sqlite.');
		$this->skipIf(
			!isset($config['test']) || !isset($config['test2']) || !isset($config['test_database_three']),
			'Primary, secondary, and tertiary test databases not configured, skipping test. To run this test define $test, $test2, and $test_database_three in your database configuration.'
		);

		$this->loadFixtures('Player', 'Guild', 'GuildsPlayer', 'Armor', 'ArmorsPlayer');
		$Player = ClassRegistry::init('Player');
		$Player->bindModel(array(
			'hasAndBelongsToMany' => array(
				'Armor' => array(
					'with' => 'ArmorsPlayer',
					'unique' => 'keepExisting',
				),
			),
		), false);
		$this->assertEquals('test', $Player->useDbConfig);
		$this->assertEquals('test', $Player->Guild->useDbConfig);
		$this->assertEquals('test2', $Player->Guild->GuildsPlayer->useDbConfig);
		$this->assertEquals('test2', $Player->Armor->useDbConfig);
		$this->assertEquals('test_database_three', $Player->ArmorsPlayer->useDbConfig);

		$players = $Player->find('all');
		$this->assertEquals(4, count($players));
		$playersGuilds = Hash::extract($players, '{n}.Guild.{n}.GuildsPlayer');
		$this->assertEquals(3, count($playersGuilds));
		$playersArmors = Hash::extract($players, '{n}.Armor.{n}.ArmorsPlayer');
		$this->assertEquals(3, count($playersArmors));
		unset($players);

		$larry = $Player->findByName('larry');
		$larrysArmor = Hash::extract($larry, 'Armor.{n}.ArmorsPlayer');
		$this->assertEquals(1, count($larrysArmor));

		$larry['Guild']['Guild'] = array(1, 3); // larry joins another guild
		$larry['Armor']['Armor'] = array(2, 3); // purchases chainmail
		$Player->save($larry);
		unset($larry);

		$larry = $Player->findByName('larry');
		$larrysGuild = Hash::extract($larry, 'Guild.{n}.GuildsPlayer');
		$this->assertEquals(2, count($larrysGuild));
		$larrysArmor = Hash::extract($larry, 'Armor.{n}.ArmorsPlayer');
		$this->assertEquals(2, count($larrysArmor));

		$Player->ArmorsPlayer->id = 3;
		$Player->ArmorsPlayer->saveField('broken', true); // larry's cloak broke

		$larry = $Player->findByName('larry');
		$larrysCloak = Hash::extract($larry, 'Armor.{n}.ArmorsPlayer[armor_id=3]', $larry);
		$this->assertNotEmpty($larrysCloak);
		$this->assertTrue($larrysCloak[0]['broken']); // still broken
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

		$this->assertEquals('title', $Post->displayField);
		$this->assertEquals('name', $Person->displayField);
		$this->assertEquals('id', $Comment->displayField);
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
		$this->assertEquals($columns, array_keys($result));

		$types = array('integer', 'integer', 'string', 'text', 'string', 'datetime', 'datetime');
		$this->assertEquals(Hash::extract(array_values($result), '{n}.type'), $types);

		$result = $Post->schema('body');
		$this->assertEquals('text', $result['type']);
		$this->assertNull($Post->schema('foo'));

		$this->assertEquals($Post->getColumnTypes(), array_combine($columns, $types));
	}

/**
 * Check schema() on a model with useTable = false;
 *
 * @return void
 */
	public function testSchemaUseTableFalse() {
		$model = new TheVoid();
		$result = $model->schema();
		$this->assertNull($result);

		$result = $model->create();
		$this->assertEmpty($result);
	}

/**
 * data provider for time tests.
 *
 * @return array
 */
	public static function timeProvider() {
		$db = ConnectionManager::getDataSource('test');
		$now = $db->expression('NOW()');
		return array(
			// blank
			array(
				array('hour' => '', 'min' => '', 'meridian' => ''),
				''
			),
			// missing hour
			array(
				array('hour' => '', 'min' => '00', 'meridian' => 'pm'),
				''
			),
			// all blank
			array(
				array('hour' => '', 'min' => '', 'sec' => ''),
				''
			),
			// set and empty merdian
			array(
				array('hour' => '1', 'min' => '00', 'meridian' => ''),
				''
			),
			// midnight
			array(
				array('hour' => '12', 'min' => '0', 'meridian' => 'am'),
				'00:00:00'
			),
			array(
				array('hour' => '00', 'min' => '00'),
				'00:00:00'
			),
			// 3am
			array(
				array('hour' => '03', 'min' => '04', 'sec' => '04'),
				'03:04:04'
			),
			array(
				array('hour' => '3', 'min' => '4', 'sec' => '4'),
				'03:04:04'
			),
			array(
				array('hour' => '03', 'min' => '4', 'sec' => '4'),
				'03:04:04'
			),
			array(
				$now,
				$now
			)
		);
	}

/**
 * test deconstruct with time fields.
 *
 * @dataProvider timeProvider
 * @return void
 */
	public function testDeconstructFieldsTime($input, $result) {
		$this->skipIf($this->db instanceof Sqlserver, 'This test is not compatible with SQL Server.');

		$this->loadFixtures('Apple');
		$TestModel = new Apple();

		$data = array(
			'Apple' => array(
				'mytime' => $input
			)
		);

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple' => array('mytime' => $result));
		$this->assertEquals($expected, $TestModel->data);
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
		$expected = array('Apple' => array('created' => ''));
		$this->assertEquals($expected, $TestModel->data);

		$data = array();
		$data['Apple']['date']['year'] = '';
		$data['Apple']['date']['month'] = '';
		$data['Apple']['date']['day'] = '';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple' => array('date' => ''));
		$this->assertEquals($expected, $TestModel->data);

		$data = array();
		$data['Apple']['created']['year'] = '2007';
		$data['Apple']['created']['month'] = '08';
		$data['Apple']['created']['day'] = '20';
		$data['Apple']['created']['hour'] = '';
		$data['Apple']['created']['min'] = '';
		$data['Apple']['created']['sec'] = '';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple' => array('created' => '2007-08-20 00:00:00'));
		$this->assertEquals($expected, $TestModel->data);

		$data = array();
		$data['Apple']['created']['year'] = '2007';
		$data['Apple']['created']['month'] = '08';
		$data['Apple']['created']['day'] = '20';
		$data['Apple']['created']['hour'] = '10';
		$data['Apple']['created']['min'] = '12';
		$data['Apple']['created']['sec'] = '';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple' => array('created' => '2007-08-20 10:12:00'));
		$this->assertEquals($expected, $TestModel->data);

		$data = array();
		$data['Apple']['created']['year'] = '2007';
		$data['Apple']['created']['month'] = '';
		$data['Apple']['created']['day'] = '12';
		$data['Apple']['created']['hour'] = '20';
		$data['Apple']['created']['min'] = '';
		$data['Apple']['created']['sec'] = '';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple' => array('created' => ''));
		$this->assertEquals($expected, $TestModel->data);

		$data = array();
		$data['Apple']['created']['hour'] = '20';
		$data['Apple']['created']['min'] = '33';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple' => array('created' => ''));
		$this->assertEquals($expected, $TestModel->data);

		$data = array();
		$data['Apple']['created']['hour'] = '20';
		$data['Apple']['created']['min'] = '33';
		$data['Apple']['created']['sec'] = '33';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple' => array('created' => ''));
		$this->assertEquals($expected, $TestModel->data);

		$data = array();
		$data['Apple']['created']['hour'] = '13';
		$data['Apple']['created']['min'] = '00';
		$data['Apple']['date']['year'] = '2006';
		$data['Apple']['date']['month'] = '12';
		$data['Apple']['date']['day'] = '25';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array(
			'Apple' => array(
			'created' => '',
			'date' => '2006-12-25'
		));
		$this->assertEquals($expected, $TestModel->data);

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
			'Apple' => array(
				'created' => '2007-08-20 10:12:09',
				'date' => '2006-12-25'
		));
		$this->assertEquals($expected, $TestModel->data);

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
		$expected = array('Apple' => array('created' => '', 'date' => ''));
		$this->assertEquals($expected, $TestModel->data);

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
		$expected = array('Apple' => array('created' => '', 'date' => '2006-12-25'));
		$this->assertEquals($expected, $TestModel->data);

		$data = array();
		$data['Apple']['date']['year'] = '2006';
		$data['Apple']['date']['month'] = '12';
		$data['Apple']['date']['day'] = '25';

		$TestModel->data = null;
		$TestModel->set($data);
		$expected = array('Apple' => array('date' => '2006-12-25'));
		$this->assertEquals($expected, $TestModel->data);

		$db = ConnectionManager::getDataSource('test');
		$data = array();
		$data['Apple']['modified'] = $db->expression('NOW()');
		$TestModel->data = null;
		$TestModel->set($data);
		$this->assertEquals($TestModel->data, $data);
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
		$this->assertContains('aaa_apples', $this->db->fullTableName($TestModel));
		$this->assertContains('aaa_apples', $db1->fullTableName($TestModel));
		$this->assertContains('aaa_apples', $db2->fullTableName($TestModel));

		$TestModel->setDataSource('database2');
		$this->assertContains('bbb_apples', $this->db->fullTableName($TestModel));
		$this->assertContains('bbb_apples', $db1->fullTableName($TestModel));
		$this->assertContains('bbb_apples', $db2->fullTableName($TestModel));

		$TestModel = new Apple();
		$TestModel->tablePrefix = 'custom_';
		$this->assertContains('custom_apples', $this->db->fullTableName($TestModel));
		$TestModel->setDataSource('database1');
		$this->assertContains('custom_apples', $this->db->fullTableName($TestModel));
		$this->assertContains('custom_apples', $db1->fullTableName($TestModel));

		$TestModel = new Apple();
		$TestModel->setDataSource('database1');
		$this->assertContains('aaa_apples', $this->db->fullTableName($TestModel));
		$TestModel->tablePrefix = '';
		$TestModel->setDataSource('database2');
		$this->assertContains('apples', $db2->fullTableName($TestModel));
		$this->assertContains('apples', $db1->fullTableName($TestModel));

		$TestModel->tablePrefix = null;
		$TestModel->setDataSource('database1');
		$this->assertContains('aaa_apples', $db2->fullTableName($TestModel));
		$this->assertContains('aaa_apples', $db1->fullTableName($TestModel));

		$TestModel->tablePrefix = false;
		$TestModel->setDataSource('database2');
		$this->assertContains('apples', $db2->fullTableName($TestModel));
		$this->assertContains('apples', $db1->fullTableName($TestModel));
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
		$this->assertInstanceOf('ModelA', $model);

		$this->assertInstanceOf('ModelB', $model->ModelB);
		$this->assertInstanceOf('ModelD', $model->ModelB->ModelD);

		$this->assertInstanceOf('ModelC', $model->ModelC);
		$this->assertInstanceOf('ModelD', $model->ModelC->ModelD);
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
		$this->assertEquals('Staying alive', $result['Article']['title']);
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
		$this->assertFalse($result);
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

		$this->assertEquals($expected, $result);
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
			$this->assertEquals($Article->getAssociated($type), array_keys($Article->{$type}));
		}

		$Article->bindModel(array('hasMany' => array('Category')));
		$this->assertEquals(array('Comment', 'Category'), $Article->getAssociated('hasMany'));

		$results = $Article->getAssociated();
		$results = array_keys($results);
		sort($results);
		$this->assertEquals(array('Category', 'Comment', 'Tag', 'User'), $results);

		$Article->unbindModel(array('hasAndBelongsToMany' => array('Tag')));
		$this->assertEquals(array(), $Article->getAssociated('hasAndBelongsToMany'));

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
		$this->assertEquals($expected, $result);
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
				'finderQuery' => ''
		));
		$this->assertEquals($expected, $result);

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
		$this->assertSame($expected, $TestModel->belongsTo);
		$this->assertSame($expected, $TestFakeModel->belongsTo);

		$this->assertEquals('User', $TestModel->User->name);
		$this->assertEquals('User', $TestFakeModel->User->name);
		$this->assertEquals('Category', $TestModel->Category->name);
		$this->assertEquals('Category', $TestFakeModel->Category->name);

		$expected = array(
			'Featured' => array(
				'className' => 'Featured',
				'foreignKey' => 'article_featured_id',
				'conditions' => '',
				'fields' => '',
				'order' => '',
				'dependent' => ''
		));

		$this->assertSame($expected, $TestModel->hasOne);
		$this->assertSame($expected, $TestFakeModel->hasOne);

		$this->assertEquals('Featured', $TestModel->Featured->name);
		$this->assertEquals('Featured', $TestFakeModel->Featured->name);

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

		$this->assertSame($expected, $TestModel->hasMany);
		$this->assertSame($expected, $TestFakeModel->hasMany);

		$this->assertEquals('Comment', $TestModel->Comment->name);
		$this->assertEquals('Comment', $TestFakeModel->Comment->name);

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
		));

		$this->assertSame($expected, $TestModel->hasAndBelongsToMany);
		$this->assertSame($expected, $TestFakeModel->hasAndBelongsToMany);

		$this->assertEquals('Tag', $TestModel->Tag->name);
		$this->assertEquals('Tag', $TestFakeModel->Tag->name);
	}

/**
 * test creating associations with plugins. Ensure a double alias isn't created
 *
 * @return void
 */
	public function testAutoConstructPluginAssociations() {
		$Comment = ClassRegistry::init('TestPluginComment');

		$this->assertEquals(3, count($Comment->belongsTo), 'Too many associations');
		$this->assertFalse(isset($Comment->belongsTo['TestPlugin.User']));
		$this->assertFalse(isset($Comment->belongsTo['TestPlugin.Source']));
		$this->assertTrue(isset($Comment->belongsTo['User']), 'Missing association');
		$this->assertTrue(isset($Comment->belongsTo['TestPluginArticle']), 'Missing association');
		$this->assertTrue(isset($Comment->belongsTo['Source']), 'Missing association');
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
		$this->assertEquals(array('Containable' => null, 'Tree' => null), $TestModel->actsAs);
		$this->assertTrue(isset($TestModel->Behaviors->Containable));
		$this->assertTrue(isset($TestModel->Behaviors->Tree));

		$TestModel = ClassRegistry::init('MergeVarPluginComment');
		$expected = array('Containable' => array('some_settings'));
		$this->assertEquals($expected, $TestModel->actsAs);
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
		$this->assertEquals('test', $TestModel->useDbConfig);

		//deprecated but test it anyway
		$NewVoid = new TheVoid(null, false, 'other');
		$this->assertEquals('other', $NewVoid->useDbConfig);
	}

/**
 * testColumnTypeFetching method
 *
 * @return void
 */
	public function testColumnTypeFetching() {
		$model = new Test();
		$this->assertEquals('integer', $model->getColumnType('id'));
		$this->assertEquals('text', $model->getColumnType('notes'));
		$this->assertEquals('datetime', $model->getColumnType('updated'));
		$this->assertEquals(null, $model->getColumnType('unknown'));

		$model = new Article();
		$this->assertEquals('datetime', $model->getColumnType('User.created'));
		$this->assertEquals('integer', $model->getColumnType('Tag.id'));
		$this->assertEquals('integer', $model->getColumnType('Article.id'));
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
		$this->assertEquals($expected, $result);

		$TestModel = new TestAlias();
		$result = $TestModel->alias;
		$expected = 'TestAlias';
		$this->assertEquals($expected, $result);

		$TestModel = new Test(array('alias' => 'AnotherTest'));
		$result = $TestModel->alias;
		$expected = 'AnotherTest';
		$this->assertEquals($expected, $result);

		$TestModel = ClassRegistry::init('Test');
		$expected = null;
		$this->assertEquals($expected, $TestModel->plugin);

		$TestModel = ClassRegistry::init('TestPlugin.TestPluginComment');
		$expected = 'TestPlugin';
		$this->assertEquals($expected, $TestModel->plugin);
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
					'updated' => '2007-03-18 10:41:31',
					'afterFind' => 'Successfully added by AfterFind'
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
							'updated' => '2007-03-18 10:45:31',
							'afterFind' => 'Successfully added by AfterFind'
			)))),
			array(
				'SomethingElse' => array(
					'id' => '2',
					'title' => 'Second Post',
					'body' => 'Second Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31',
					'afterFind' => 'Successfully added by AfterFind'
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
							'updated' => '2007-03-18 10:41:31',
							'afterFind' => 'Successfully added by AfterFind'
			)))),
			array(
				'SomethingElse' => array(
					'id' => '3',
					'title' => 'Third Post',
					'body' => 'Third Post Body',
					'published' => 'Y',
					'created' => '2007-03-18 10:43:23',
					'updated' => '2007-03-18 10:45:31',
					'afterFind' => 'Successfully added by AfterFind'
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
							'updated' => '2007-03-18 10:43:31',
							'afterFind' => 'Successfully added by AfterFind'
		)))));
		$this->assertEquals($expected, $result);

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
							'something_else_id' => '2',
							'afterFind' => 'Successfully added by AfterFind'
						),
						'afterFind' => 'Successfully added by AfterFind'
					))),
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
							'something_else_id' => '3',
							'afterFind' => 'Successfully added by AfterFind'
						),
						'afterFind' => 'Successfully added by AfterFind'
					))),
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
							'something_else_id' => '1',
							'afterFind' => 'Successfully added by AfterFind'
						),
						'afterFind' => 'Successfully added by AfterFind'
		))));
		$this->assertEquals($expected, $result);

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
						'something_else_id' => '2',
						'afterFind' => 'Successfully added by AfterFind'
					),
					'afterFind' => 'Successfully added by AfterFind'
		)));
		$this->assertEquals($expected, $result);

		$expected = $TestModel->findById(1);
		$TestModel->set($expected);
		$TestModel->save();
		$result = $TestModel->findById(1);
		$this->assertEquals($expected, $result);

		$TestModel->hasAndBelongsToMany['SomethingElse']['unique'] = false;
		$TestModel->create(array(
			'Something' => array('id' => 1),
			'SomethingElse' => array(3, array(
				'something_else_id' => 1,
				'doomed' => true
		))));

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
						'something_else_id' => '1',
						'afterFind' => 'Successfully added by AfterFind'
					),
					'afterFind' => 'Successfully added by AfterFind'
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
						'something_else_id' => '2',
						'afterFind' => 'Successfully added by AfterFind'
					),
					'afterFind' => 'Successfully added by AfterFind'
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
						'something_else_id' => '3',
						'afterFind' => 'Successfully added by AfterFind'
					),
					'afterFind' => 'Successfully added by AfterFind'
				)
			));
		$this->assertEquals(static::date(), $result['Something']['updated']);
		unset($result['Something']['updated']);
		$this->assertEquals($expected, $result);
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

		$this->assertEquals($expected, $result);

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

		$this->assertEquals($expected, $result);
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

		$this->assertEquals($expected, $result);
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
		$this->assertEquals($expected, $result);
		$TestModel = new User();
		$result = $TestModel->schema();

		if (isset($this->db->columns['primary_key']['length'])) {
			$intLength = $this->db->columns['primary_key']['length'];
		} elseif (isset($this->db->columns['integer']['length'])) {
			$intLength = $this->db->columns['integer']['length'];
		} else {
			$intLength = 11;
		}
		foreach (array('collate', 'charset', 'comment', 'unsigned') as $type) {
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
				'null' => true,
				'default' => '',
				'length' => 255
			),
			'password' => array(
				'type' => 'string',
				'null' => true,
				'default' => '',
				'length' => 255
			),
			'created' => array(
				'type' => 'datetime',
				'null' => true,
				'default' => null,
				'length' => null
			),
			'updated' => array(
				'type' => 'datetime',
				'null' => true,
				'default' => null,
				'length' => null
		));

		$this->assertEquals($expected, $result);

		$TestModel = new Article();
		$result = $TestModel->create();
		$expected = array('Article' => array('published' => 'N'));
		$this->assertEquals($expected, $result);

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

		$this->assertEquals($expected, $FeaturedModel->create($data));

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

		$this->assertEquals($expected, $FeaturedModel->create($data));
	}

/**
 * testEscapeField to prove it escapes the field well even when it has part of the alias on it
 *
 * @return void
 */
	public function testEscapeField() {
		$TestModel = new Test();
		$db = $TestModel->getDataSource();

		$result = $TestModel->escapeField('test_field');
		$expected = $db->name('Test.test_field');
		$this->assertEquals($expected, $result);

		$result = $TestModel->escapeField('TestField');
		$expected = $db->name('Test.TestField');
		$this->assertEquals($expected, $result);

		$result = $TestModel->escapeField('DomainHandle', 'Domain');
		$expected = $db->name('Domain.DomainHandle');
		$this->assertEquals($expected, $result);

		ConnectionManager::create('mock', array('datasource' => 'DboMock'));
		$TestModel->setDataSource('mock');
		$db = $TestModel->getDataSource();

		$result = $TestModel->escapeField('DomainHandle', 'Domain');
		$expected = $db->name('Domain.DomainHandle');
		$this->assertEquals($expected, $result);
		ConnectionManager::drop('mock');
	}

/**
 * testGetID
 *
 * @return void
 */
	public function testGetID() {
		$TestModel = new Test();

		$result = $TestModel->getID();
		$this->assertFalse($result);

		$TestModel->id = 9;
		$result = $TestModel->getID();
		$this->assertEquals(9, $result);

		$TestModel->id = array(10, 9, 8, 7);
		$result = $TestModel->getID(2);
		$this->assertEquals(8, $result);

		$TestModel->id = array(array(), 1, 2, 3);
		$result = $TestModel->getID();
		$this->assertFalse($result);
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

/**
 * testMultischemaFixture
 *
 * @return void
 */
	public function testMultischemaFixture() {
		$config = ConnectionManager::enumConnectionObjects();
		$this->skipIf($this->db instanceof Sqlite, 'This test is not compatible with Sqlite.');
		$this->skipIf(!isset($config['test']) || !isset($config['test2']),
			'Primary and secondary test databases not configured, skipping cross-database join tests. To run these tests define $test and $test2 in your database configuration.'
			);

		$this->loadFixtures('Player', 'Guild', 'GuildsPlayer');

		$Player = ClassRegistry::init('Player');
		$this->assertEquals('test', $Player->useDbConfig);
		$this->assertEquals('test', $Player->Guild->useDbConfig);
		$this->assertEquals('test2', $Player->Guild->GuildsPlayer->useDbConfig);
		$this->assertEquals('test2', $Player->GuildsPlayer->useDbConfig);

		$players = $Player->find('all', array('recursive' => -1));
		$guilds = $Player->Guild->find('all', array('recursive' => -1));
		$guildsPlayers = $Player->GuildsPlayer->find('all', array('recursive' => -1));

		$this->assertEquals(true, count($players) > 1);
		$this->assertEquals(true, count($guilds) > 1);
		$this->assertEquals(true, count($guildsPlayers) > 1);
	}

/**
 * testMultischemaFixtureWithThreeDatabases, three databases
 *
 * @return void
 */
	public function testMultischemaFixtureWithThreeDatabases() {
		$config = ConnectionManager::enumConnectionObjects();
		$this->skipIf($this->db instanceof Sqlite, 'This test is not compatible with Sqlite.');
		$this->skipIf(
			!isset($config['test']) || !isset($config['test2']) || !isset($config['test_database_three']),
			'Primary, secondary, and tertiary test databases not configured, skipping test. To run this test define $test, $test2, and $test_database_three in your database configuration.'
			);

		$this->loadFixtures('Player', 'Guild', 'GuildsPlayer', 'Armor', 'ArmorsPlayer');

		$Player = ClassRegistry::init('Player');
		$Player->bindModel(array(
			'hasAndBelongsToMany' => array(
				'Armor' => array(
					'with' => 'ArmorsPlayer',
					),
				),
			), false);
		$this->assertEquals('test', $Player->useDbConfig);
		$this->assertEquals('test', $Player->Guild->useDbConfig);
		$this->assertEquals('test2', $Player->Guild->GuildsPlayer->useDbConfig);
		$this->assertEquals('test2', $Player->GuildsPlayer->useDbConfig);
		$this->assertEquals('test2', $Player->Armor->useDbConfig);
		$this->assertEquals('test_database_three', $Player->Armor->ArmorsPlayer->useDbConfig);
		$this->assertEquals('test', $Player->getDataSource()->configKeyName);
		$this->assertEquals('test', $Player->Guild->getDataSource()->configKeyName);
		$this->assertEquals('test2', $Player->GuildsPlayer->getDataSource()->configKeyName);
		$this->assertEquals('test2', $Player->Armor->getDataSource()->configKeyName);
		$this->assertEquals('test_database_three', $Player->Armor->ArmorsPlayer->getDataSource()->configKeyName);

		$players = $Player->find('all', array('recursive' => -1));
		$guilds = $Player->Guild->find('all', array('recursive' => -1));
		$guildsPlayers = $Player->GuildsPlayer->find('all', array('recursive' => -1));
		$armorsPlayers = $Player->ArmorsPlayer->find('all', array('recursive' => -1));

		$this->assertEquals(true, count($players) > 1);
		$this->assertEquals(true, count($guilds) > 1);
		$this->assertEquals(true, count($guildsPlayers) > 1);
		$this->assertEquals(true, count($armorsPlayers) > 1);
	}

/**
 * Tests that calling schema() on a model that is not supposed to use a table
 * does not trigger any calls on any datasource
 *
 * @return void
 */
	public function testSchemaNoDB() {
		$model = $this->getMock('Article', array('getDataSource'));
		$model->useTable = false;
		$model->expects($this->never())->method('getDataSource');
		$this->assertEmpty($model->schema());
	}

/**
 * Tests that calling getColumnType() on a model that is not supposed to use a table
 * does not trigger any calls on any datasource
 *
 * @return void
 */
	public function testGetColumnTypeNoDB() {
		$model = $this->getMock('Example', array('getDataSource'));
		$model->expects($this->never())->method('getDataSource');
		$result = $model->getColumnType('filefield');
		$this->assertEquals('string', $result);
	}
}
