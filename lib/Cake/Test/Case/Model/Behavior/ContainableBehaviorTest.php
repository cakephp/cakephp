<?php
/**
 * ContainableBehaviorTest file
 *
 * PHP 5
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
 * @package       Cake.Test.Case.Model.Behavior
 * @since         CakePHP(tm) v 1.2.0.5669
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
require_once dirname(dirname(__FILE__)) . DS . 'models.php';

/**
 * ContainableTest class
 *
 * @package       Cake.Test.Case.Model.Behavior
 */
class ContainableBehaviorTest extends CakeTestCase {

/**
 * Fixtures associated with this test case
 *
 * @var array
 */
	public $fixtures = array(
		'core.article', 'core.article_featured', 'core.article_featureds_tags',
		'core.articles_tag', 'core.attachment', 'core.category',
		'core.comment', 'core.featured', 'core.tag', 'core.user',
		'core.join_a', 'core.join_b', 'core.join_c', 'core.join_a_c', 'core.join_a_b'
	);

/**
 * Method executed before each test
 *
 */
	public function setUp() {
		parent::setUp();
		$this->User = ClassRegistry::init('User');
		$this->Article = ClassRegistry::init('Article');
		$this->Tag = ClassRegistry::init('Tag');

		$this->User->bindModel(array(
			'hasMany' => array('Article', 'ArticleFeatured', 'Comment')
		), false);
		$this->User->ArticleFeatured->unbindModel(array('belongsTo' => array('Category')), false);
		$this->User->ArticleFeatured->hasMany['Comment']['foreignKey'] = 'article_id';

		$this->Tag->bindModel(array(
			'hasAndBelongsToMany' => array('Article')
		), false);

		$this->User->Behaviors->attach('Containable');
		$this->Article->Behaviors->attach('Containable');
		$this->Tag->Behaviors->attach('Containable');
	}

/**
 * Method executed after each test
 *
 */
	public function tearDown() {
		unset($this->Article);
		unset($this->User);
		unset($this->Tag);
		parent::tearDown();
	}

/**
 * testContainments method
 *
 * @return void
 */
	public function testContainments() {
		$r = $this->_containments($this->Article, array('Comment' => array('conditions' => array('Comment.user_id' => 2))));
		$this->assertTrue(Set::matches('/Article/keep/Comment/conditions[Comment.user_id=2]', $r));

		$r = $this->_containments($this->User, array(
			'ArticleFeatured' => array(
				'Featured' => array(
					'id',
					'Category' => 'name'
				)
		)));
		$this->assertEquals(array('id'), Hash::extract($r, 'ArticleFeatured.keep.Featured.fields'));

		$r = $this->_containments($this->Article, array(
			'Comment' => array(
				'User',
				'conditions' => array('Comment' => array('user_id' => 2)),
			),
		));
		$this->assertTrue(Set::matches('/User', $r));
		$this->assertTrue(Set::matches('/Comment', $r));
		$this->assertTrue(Set::matches('/Article/keep/Comment/conditions/Comment[user_id=2]', $r));

		$r = $this->_containments($this->Article, array('Comment(comment, published)' => 'Attachment(attachment)', 'User(user)'));
		$this->assertTrue(Set::matches('/Comment', $r));
		$this->assertTrue(Set::matches('/User', $r));
		$this->assertTrue(Set::matches('/Article/keep/Comment', $r));
		$this->assertTrue(Set::matches('/Article/keep/User', $r));
		$this->assertEquals(array('comment', 'published'), Hash::extract($r, 'Article.keep.Comment.fields'));
		$this->assertEquals(array('user'), Hash::extract($r, 'Article.keep.User.fields'));
		$this->assertTrue(Set::matches('/Comment/keep/Attachment', $r));
		$this->assertEquals(array('attachment'), Hash::extract($r, 'Comment.keep.Attachment.fields'));

		$r = $this->_containments($this->Article, array('Comment' => array('limit' => 1)));
		$this->assertEquals(array('Comment', 'Article'), array_keys($r));
		$result = Hash::extract($r, 'Comment[keep]');
		$this->assertEquals(array('keep' => array()), array_shift($result));
		$this->assertTrue(Set::matches('/Article/keep/Comment', $r));
		$result = Hash::extract($r, 'Article.keep');
		$this->assertEquals(array('limit' => 1), array_shift($result));

		$r = $this->_containments($this->Article, array('Comment.User'));
		$this->assertEquals(array('User', 'Comment', 'Article'), array_keys($r));

		$result = Hash::extract($r, 'User[keep]');
		$this->assertEquals(array('keep' => array()), array_shift($result));

		$result = Hash::extract($r, 'Comment[keep]');
		$this->assertEquals(array('keep' => array('User' => array())), array_shift($result));

		$result = Hash::extract($r, 'Article[keep]');
		$this->assertEquals(array('keep' => array('Comment' => array())), array_shift($result));

		$r = $this->_containments($this->Tag, array('Article' => array('User' => array('Comment' => array(
			'Attachment' => array('conditions' => array('Attachment.id >' => 1))
		)))));
		$this->assertTrue(Set::matches('/Attachment', $r));
		$this->assertTrue(Set::matches('/Comment/keep/Attachment/conditions', $r));
		$this->assertEquals(array('Attachment.id >' => 1), $r['Comment']['keep']['Attachment']['conditions']);
		$this->assertTrue(Set::matches('/User/keep/Comment', $r));
		$this->assertTrue(Set::matches('/Article/keep/User', $r));
		$this->assertTrue(Set::matches('/Tag/keep/Article', $r));
	}

/**
 * testInvalidContainments method
 *
 * @expectedException PHPUnit_Framework_Error
 * @return void
 */
	public function testInvalidContainments() {
		$this->_containments($this->Article, array('Comment', 'InvalidBinding'));
	}

/**
 * testInvalidContainments method with suppressing error notices
 *
 * @return void
 */
	public function testInvalidContainmentsNoNotices() {
		$this->Article->Behaviors->attach('Containable', array('notices' => false));
		$this->_containments($this->Article, array('Comment', 'InvalidBinding'));
	}

/**
 * testBeforeFind method
 *
 * @return void
 */
	public function testBeforeFind() {
		$r = $this->Article->find('all', array('contain' => array('Comment')));
		$this->assertFalse(Set::matches('/User', $r));
		$this->assertTrue(Set::matches('/Comment', $r));
		$this->assertFalse(Set::matches('/Comment/User', $r));

		$r = $this->Article->find('all', array('contain' => 'Comment.User'));
		$this->assertTrue(Set::matches('/Comment/User', $r));
		$this->assertFalse(Set::matches('/Comment/Article', $r));

		$r = $this->Article->find('all', array('contain' => array('Comment' => array('User', 'Article'))));
		$this->assertTrue(Set::matches('/Comment/User', $r));
		$this->assertTrue(Set::matches('/Comment/Article', $r));

		$r = $this->Article->find('all', array('contain' => array('Comment' => array('conditions' => array('Comment.user_id' => 2)))));
		$this->assertFalse(Set::matches('/Comment[user_id!=2]', $r));
		$this->assertTrue(Set::matches('/Comment[user_id=2]', $r));

		$r = $this->Article->find('all', array('contain' => array('Comment.user_id = 2')));
		$this->assertFalse(Set::matches('/Comment[user_id!=2]', $r));

		$r = $this->Article->find('all', array('contain' => 'Comment.id DESC'));
		$ids = $descIds = Hash::extract($r, 'Comment[1].id');
		rsort($descIds);
		$this->assertEquals($ids, $descIds);

		$r = $this->Article->find('all', array('contain' => 'Comment'));
		$this->assertTrue(Set::matches('/Comment[user_id!=2]', $r));

		$r = $this->Article->find('all', array('contain' => array('Comment' => array('fields' => 'comment'))));
		$this->assertFalse(Set::matches('/Comment/created', $r));
		$this->assertTrue(Set::matches('/Comment/comment', $r));
		$this->assertFalse(Set::matches('/Comment/updated', $r));

		$r = $this->Article->find('all', array('contain' => array('Comment' => array('fields' => array('comment', 'updated')))));
		$this->assertFalse(Set::matches('/Comment/created', $r));
		$this->assertTrue(Set::matches('/Comment/comment', $r));
		$this->assertTrue(Set::matches('/Comment/updated', $r));

		$r = $this->Article->find('all', array('contain' => array('Comment' => array('comment', 'updated'))));
		$this->assertFalse(Set::matches('/Comment/created', $r));
		$this->assertTrue(Set::matches('/Comment/comment', $r));
		$this->assertTrue(Set::matches('/Comment/updated', $r));

		$r = $this->Article->find('all', array('contain' => array('Comment(comment,updated)')));
		$this->assertFalse(Set::matches('/Comment/created', $r));
		$this->assertTrue(Set::matches('/Comment/comment', $r));
		$this->assertTrue(Set::matches('/Comment/updated', $r));

		$r = $this->Article->find('all', array('contain' => 'Comment.created'));
		$this->assertTrue(Set::matches('/Comment/created', $r));
		$this->assertFalse(Set::matches('/Comment/comment', $r));

		$r = $this->Article->find('all', array('contain' => array('User.Article(title)', 'Comment(comment)')));
		$this->assertFalse(Set::matches('/Comment/Article', $r));
		$this->assertFalse(Set::matches('/Comment/User', $r));
		$this->assertTrue(Set::matches('/Comment/comment', $r));
		$this->assertFalse(Set::matches('/Comment/created', $r));
		$this->assertTrue(Set::matches('/User/Article/title', $r));
		$this->assertFalse(Set::matches('/User/Article/created', $r));

		$r = $this->Article->find('all', array('contain' => array()));
		$this->assertFalse(Set::matches('/User', $r));
		$this->assertFalse(Set::matches('/Comment', $r));
	}

/**
 * testBeforeFindWithNonExistingBinding method
 *
 * @expectedException PHPUnit_Framework_Error
 * @return void
 */
	public function testBeforeFindWithNonExistingBinding() {
		$this->Article->find('all', array('contain' => array('Comment' => 'NonExistingBinding')));
	}

/**
 * testContain method
 *
 * @return void
 */
	public function testContain() {
		$this->Article->contain('Comment.User');
		$r = $this->Article->find('all');
		$this->assertTrue(Set::matches('/Comment/User', $r));
		$this->assertFalse(Set::matches('/Comment/Article', $r));

		$r = $this->Article->find('all');
		$this->assertFalse(Set::matches('/Comment/User', $r));
	}

/**
 * testContainFindList method
 *
 * @return void
 */
	public function testContainFindList() {
		$this->Article->contain('Comment.User');
		$result = $this->Article->find('list');
		$expected = array(
			1 => 'First Article',
			2 => 'Second Article',
			3 => 'Third Article'
		);
		$this->assertEquals($expected, $result);

		$result = $this->Article->find('list', array('fields' => array('Article.id', 'User.id'), 'contain' => array('User')));
		$expected = array(
			1 => '1',
			2 => '3',
			3 => '1'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that mixing contain() and the contain find option.
 *
 * @return void
 */
	public function testContainAndContainOption() {
		$this->Article->contain();
		$r = $this->Article->find('all', array(
			'contain' => array('Comment')
		));
		$this->assertTrue(isset($r[0]['Comment']), 'No comment returned');
	}

/**
 * testFindEmbeddedNoBindings method
 *
 * @return void
 */
	public function testFindEmbeddedNoBindings() {
		$result = $this->Article->find('all', array('contain' => false));
		$expected = array(
			array('Article' => array(
				'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
				'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)),
			array('Article' => array(
				'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
				'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
			)),
			array('Article' => array(
				'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
				'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
			))
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testFindFirstLevel method
 *
 * @return void
 */
	public function testFindFirstLevel() {
		$this->Article->contain('User');
		$result = $this->Article->find('all', array('recursive' => 1));
		$expected = array(
			array(
				'Article' => array(
					'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			),
			array(
				'Article' => array(
					'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
				),
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				)
			),
			array(
				'Article' => array(
					'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			)
		);
		$this->assertEquals($expected, $result);

		$this->Article->contain('User', 'Comment');
		$result = $this->Article->find('all', array('recursive' => 1));
		$expected = array(
			array(
				'Article' => array(
					'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array(
					array(
						'id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'
					),
					array(
						'id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'
					),
					array(
						'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'
					),
					array(
						'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
						'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
					)
				)
			),
			array(
				'Article' => array(
					'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
				),
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'Comment' => array(
					array(
						'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
						'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'
					),
					array(
						'id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article',
						'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31'
					)
				)
			),
			array(
				'Article' => array(
					'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array()
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testFindEmbeddedFirstLevel method
 *
 * @return void
 */
	public function testFindEmbeddedFirstLevel() {
		$result = $this->Article->find('all', array('contain' => array('User')));
		$expected = array(
			array(
				'Article' => array(
					'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			),
			array(
				'Article' => array(
					'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
				),
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				)
			),
			array(
				'Article' => array(
					'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			)
		);
		$this->assertEquals($expected, $result);

		$result = $this->Article->find('all', array('contain' => array('User', 'Comment')));
		$expected = array(
			array(
				'Article' => array(
					'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array(
					array(
						'id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'
					),
					array(
						'id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'
					),
					array(
						'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'
					),
					array(
						'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
						'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
					)
				)
			),
			array(
				'Article' => array(
					'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
				),
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'Comment' => array(
					array(
						'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
						'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'
					),
					array(
						'id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article',
						'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31'
					)
				)
			),
			array(
				'Article' => array(
					'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array()
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testFindSecondLevel method
 *
 * @return void
 */
	public function testFindSecondLevel() {
		$this->Article->contain(array('Comment' => 'User'));
		$result = $this->Article->find('all', array('recursive' => 2));
		$expected = array(
			array(
				'Article' => array(
					'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
				),
				'Comment' => array(
					array(
						'id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31',
						'User' => array(
							'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
						)
					),
					array(
						'id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31',
						'User' => array(
							'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
						)
					),
					array(
						'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31',
						'User' => array(
							'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
						)
					),
					array(
						'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
						'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31',
						'User' => array(
							'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
						)
					)
				)
			),
			array(
				'Article' => array(
					'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
				),
				'Comment' => array(
					array(
						'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
						'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
						'User' => array(
							'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
						)
					),
					array(
						'id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article',
						'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31',
						'User' => array(
							'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
						)
					)
				)
			),
			array(
				'Article' => array(
					'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'Comment' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$this->Article->contain(array('User' => 'ArticleFeatured'));
		$result = $this->Article->find('all', array('recursive' => 2));
		$expected = array(
			array(
				'Article' => array(
					'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'ArticleFeatured' => array(
						array(
							'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
						),
						array(
							'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
						)
					)
				)
			),
			array(
				'Article' => array(
					'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
				),
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31',
					'ArticleFeatured' => array(
						array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
						)
					)
				)
			),
			array(
				'Article' => array(
					'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'ArticleFeatured' => array(
						array(
							'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
						),
						array(
							'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
						)
					)
				)
			)
		);
		$this->assertEquals($expected, $result);

		$this->Article->contain(array('User' => array('ArticleFeatured', 'Comment')));
		$result = $this->Article->find('all', array('recursive' => 2));
		$expected = array(
			array(
				'Article' => array(
					'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'ArticleFeatured' => array(
						array(
							'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
						),
						array(
							'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
						)
					),
					'Comment' => array(
						array(
							'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
							'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'
						),
						array(
							'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
							'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
						),
						array(
							'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
							'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'
						)
					)
				)
			),
			array(
				'Article' => array(
					'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
				),
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31',
					'ArticleFeatured' => array(
						array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
						)
					),
					'Comment' => array()
				)
			),
			array(
				'Article' => array(
					'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'ArticleFeatured' => array(
						array(
							'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
						),
						array(
							'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
						)
					),
					'Comment' => array(
						array(
							'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
							'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'
						),
						array(
							'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
							'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
						),
						array(
							'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
							'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'
						)
					)
				)
			)
		);
		$this->assertEquals($expected, $result);

		$this->Article->contain(array('User' => array('ArticleFeatured')), 'Tag', array('Comment' => 'Attachment'));
		$result = $this->Article->find('all', array('recursive' => 2));
		$expected = array(
			array(
				'Article' => array(
					'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'ArticleFeatured' => array(
						array(
							'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
						),
						array(
							'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
						)
					)
				),
				'Comment' => array(
					array(
						'id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31',
						'Attachment' => array()
					),
					array(
						'id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31',
						'Attachment' => array()
					),
					array(
						'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31',
						'Attachment' => array()
					),
					array(
						'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
						'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31',
						'Attachment' => array()
					)
				),
				'Tag' => array(
					array('id' => 1, 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
					array('id' => 2, 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31')
				)
			),
			array(
				'Article' => array(
					'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
				),
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31',
					'ArticleFeatured' => array(
						array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
						)
					)
				),
				'Comment' => array(
					array(
						'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
						'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
						'Attachment' => array(
							'id' => 1, 'comment_id' => 5, 'attachment' => 'attachment.zip',
							'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
						)
					),
					array(
						'id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article',
						'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31',
						'Attachment' => array()
					)
				),
				'Tag' => array(
					array('id' => 1, 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
					array('id' => 3, 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
				)
			),
			array(
				'Article' => array(
					'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'ArticleFeatured' => array(
						array(
							'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
						),
						array(
							'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
						)
					)
				),
				'Comment' => array(),
				'Tag' => array()
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testFindEmbeddedSecondLevel method
 *
 * @return void
 */
	public function testFindEmbeddedSecondLevel() {
		$result = $this->Article->find('all', array('contain' => array('Comment' => 'User')));
		$expected = array(
			array(
				'Article' => array(
					'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
				),
				'Comment' => array(
					array(
						'id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31',
						'User' => array(
							'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
						)
					),
					array(
						'id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31',
						'User' => array(
							'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
						)
					),
					array(
						'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31',
						'User' => array(
							'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
						)
					),
					array(
						'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
						'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31',
						'User' => array(
							'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
						)
					)
				)
			),
			array(
				'Article' => array(
					'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
				),
				'Comment' => array(
					array(
						'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
						'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
						'User' => array(
							'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
						)
					),
					array(
						'id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article',
						'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31',
						'User' => array(
							'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
						)
					)
				)
			),
			array(
				'Article' => array(
					'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'Comment' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$result = $this->Article->find('all', array('contain' => array('User' => 'ArticleFeatured')));
		$expected = array(
			array(
				'Article' => array(
					'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'ArticleFeatured' => array(
						array(
							'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
						),
						array(
							'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
						)
					)
				)
			),
			array(
				'Article' => array(
					'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
				),
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31',
					'ArticleFeatured' => array(
						array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
						)
					)
				)
			),
			array(
				'Article' => array(
					'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'ArticleFeatured' => array(
						array(
							'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
						),
						array(
							'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
						)
					)
				)
			)
		);
		$this->assertEquals($expected, $result);

		$result = $this->Article->find('all', array('contain' => array('User' => array('ArticleFeatured', 'Comment'))));
		$expected = array(
			array(
				'Article' => array(
					'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'ArticleFeatured' => array(
						array(
							'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
						),
						array(
							'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
						)
					),
					'Comment' => array(
						array(
							'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
							'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'
						),
						array(
							'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
							'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
						),
						array(
							'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
							'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'
						)
					)
				)
			),
			array(
				'Article' => array(
					'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
				),
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31',
					'ArticleFeatured' => array(
						array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
						)
					),
					'Comment' => array()
				)
			),
			array(
				'Article' => array(
					'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'ArticleFeatured' => array(
						array(
							'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
						),
						array(
							'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
						)
					),
					'Comment' => array(
						array(
							'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
							'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'
						),
						array(
							'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
							'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
						),
						array(
							'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
							'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'
						)
					)
				)
			)
		);
		$this->assertEquals($expected, $result);

		$result = $this->Article->find('all', array('contain' => array('User' => 'ArticleFeatured', 'Tag', 'Comment' => 'Attachment')));
		$expected = array(
			array(
				'Article' => array(
					'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'ArticleFeatured' => array(
						array(
							'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
						),
						array(
							'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
						)
					)
				),
				'Comment' => array(
					array(
						'id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31',
						'Attachment' => array()
					),
					array(
						'id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31',
						'Attachment' => array()
					),
					array(
						'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31',
						'Attachment' => array()
					),
					array(
						'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
						'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31',
						'Attachment' => array()
					)
				),
				'Tag' => array(
					array('id' => 1, 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
					array('id' => 2, 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31')
				)
			),
			array(
				'Article' => array(
					'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
				),
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31',
					'ArticleFeatured' => array(
						array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
						)
					)
				),
				'Comment' => array(
					array(
						'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
						'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
						'Attachment' => array(
							'id' => 1, 'comment_id' => 5, 'attachment' => 'attachment.zip',
							'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
						)
					),
					array(
						'id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article',
						'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31',
						'Attachment' => array()
					)
				),
				'Tag' => array(
					array('id' => 1, 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
					array('id' => 3, 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
				)
			),
			array(
				'Article' => array(
					'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'ArticleFeatured' => array(
						array(
							'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
						),
						array(
							'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
						)
					)
				),
				'Comment' => array(),
				'Tag' => array()
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testFindThirdLevel method
 *
 * @return void
 */
	public function testFindThirdLevel() {
		$this->User->contain(array('ArticleFeatured' => array('Featured' => 'Category')));
		$result = $this->User->find('all', array('recursive' => 3));
		$expected = array(
			array(
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
						'Featured' => array(
							'id' => 1, 'article_featured_id' => 1, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						)
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
						'Featured' => array()
					)
				)
			),
			array(
				'User' => array(
					'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
				),
				'ArticleFeatured' => array()
			),
			array(
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
						'Featured' => array(
							'id' => 2, 'article_featured_id' => 2, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						)
					)
				)
			),
			array(
				'User' => array(
					'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
				),
				'ArticleFeatured' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$this->User->contain(array('ArticleFeatured' => array('Featured' => 'Category', 'Comment' => array('Article', 'Attachment'))));
		$result = $this->User->find('all', array('recursive' => 3));
		$expected = array(
			array(
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
						'Featured' => array(
							'id' => 1, 'article_featured_id' => 1, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							),
							array(
								'id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							),
							array(
								'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							),
							array(
								'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
								'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							)
						)
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
						'Featured' => array(),
						'Comment' => array()
					)
				)
			),
			array(
				'User' => array(
					'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
				),
				'ArticleFeatured' => array()
			),
			array(
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
						'Featured' => array(
							'id' => 2, 'article_featured_id' => 2, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
								'Article' => array(
									'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
								),
								'Attachment' => array(
									'id' => 1, 'comment_id' => 5, 'attachment' => 'attachment.zip',
									'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
								)
							),
							array(
								'id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31',
								'Article' => array(
									'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
								),
								'Attachment' => array()
							)
						)
					)
				)
			),
			array(
				'User' => array(
					'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
				),
				'ArticleFeatured' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$this->User->contain(array('ArticleFeatured' => array('Featured' => 'Category', 'Comment' => 'Attachment'), 'Article'));
		$result = $this->User->find('all', array('recursive' => 3));
		$expected = array(
			array(
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Article' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
					)
				),
				'ArticleFeatured' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
						'Featured' => array(
							'id' => 1, 'article_featured_id' => 1, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31',
								'Attachment' => array()
							),
							array(
								'id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31',
								'Attachment' => array()
							),
							array(
								'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31',
								'Attachment' => array()
							),
							array(
								'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
								'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31',
								'Attachment' => array()
							)
						)
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
						'Featured' => array(),
						'Comment' => array()
					)
				)
			),
			array(
				'User' => array(
					'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
				),
				'Article' => array(),
				'ArticleFeatured' => array()
			),
			array(
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'Article' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
					)
				),
				'ArticleFeatured' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
						'Featured' => array(
							'id' => 2, 'article_featured_id' => 2, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
								'Attachment' => array(
									'id' => 1, 'comment_id' => 5, 'attachment' => 'attachment.zip',
									'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
								)
							),
							array(
								'id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31',
								'Attachment' => array()
							)
						)
					)
				)
			),
			array(
				'User' => array(
					'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
				),
				'Article' => array(),
				'ArticleFeatured' => array()
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testFindEmbeddedThirdLevel method
 *
 * @return void
 */
	public function testFindEmbeddedThirdLevel() {
		$result = $this->User->find('all', array('contain' => array('ArticleFeatured' => array('Featured' => 'Category'))));
		$expected = array(
			array(
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
						'Featured' => array(
							'id' => 1, 'article_featured_id' => 1, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						)
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
						'Featured' => array()
					)
				)
			),
			array(
				'User' => array(
					'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
				),
				'ArticleFeatured' => array()
			),
			array(
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
						'Featured' => array(
							'id' => 2, 'article_featured_id' => 2, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						)
					)
				)
			),
			array(
				'User' => array(
					'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
				),
				'ArticleFeatured' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$result = $this->User->find('all', array('contain' => array('ArticleFeatured' => array('Featured' => 'Category', 'Comment' => array('Article', 'Attachment')))));
		$expected = array(
			array(
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
						'Featured' => array(
							'id' => 1, 'article_featured_id' => 1, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							),
							array(
								'id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							),
							array(
								'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							),
							array(
								'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
								'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							)
						)
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
						'Featured' => array(),
						'Comment' => array()
					)
				)
			),
			array(
				'User' => array(
					'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
				),
				'ArticleFeatured' => array()
			),
			array(
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
						'Featured' => array(
							'id' => 2, 'article_featured_id' => 2, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
								'Article' => array(
									'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
								),
								'Attachment' => array(
									'id' => 1, 'comment_id' => 5, 'attachment' => 'attachment.zip',
									'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
								)
							),
							array(
								'id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31',
								'Article' => array(
									'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
								),
								'Attachment' => array()
							)
						)
					)
				)
			),
			array(
				'User' => array(
					'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
				),
				'ArticleFeatured' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$result = $this->User->find('all', array('contain' => array('ArticleFeatured' => array('Featured' => 'Category', 'Comment' => 'Attachment'), 'Article')));
		$expected = array(
			array(
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Article' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
					)
				),
				'ArticleFeatured' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
						'Featured' => array(
							'id' => 1, 'article_featured_id' => 1, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31',
								'Attachment' => array()
							),
							array(
								'id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31',
								'Attachment' => array()
							),
							array(
								'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31',
								'Attachment' => array()
							),
							array(
								'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
								'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31',
								'Attachment' => array()
							)
						)
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
						'Featured' => array(),
						'Comment' => array()
					)
				)
			),
			array(
				'User' => array(
					'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
				),
				'Article' => array(),
				'ArticleFeatured' => array()
			),
			array(
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'Article' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
					)
				),
				'ArticleFeatured' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
						'Featured' => array(
							'id' => 2, 'article_featured_id' => 2, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
								'Attachment' => array(
									'id' => 1, 'comment_id' => 5, 'attachment' => 'attachment.zip',
									'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
								)
							),
							array(
								'id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31',
								'Attachment' => array()
							)
						)
					)
				)
			),
			array(
				'User' => array(
					'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
				),
				'Article' => array(),
				'ArticleFeatured' => array()
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testSettingsThirdLevel method
 *
 * @return void
 */
	public function testSettingsThirdLevel() {
		$result = $this->User->find('all', array('contain' => array('ArticleFeatured' => array('Featured' => array('Category' => array('id', 'name'))))));
		$expected = array(
			array(
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
						'Featured' => array(
							'id' => 1, 'article_featured_id' => 1, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'name' => 'Category 1'
							)
						)
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
						'Featured' => array()
					)
				)
			),
			array(
				'User' => array(
					'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
				),
				'ArticleFeatured' => array()
			),
			array(
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
						'Featured' => array(
							'id' => 2, 'article_featured_id' => 2, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'name' => 'Category 1'
							)
						)
					)
				)
			),
			array(
				'User' => array(
					'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
				),
				'ArticleFeatured' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$r = $this->User->find('all', array('contain' => array(
			'ArticleFeatured' => array(
				'id', 'title',
				'Featured' => array(
					'id', 'category_id',
					'Category' => array('id', 'name')
				)
			)
		)));

		$this->assertTrue(Set::matches('/User[id=1]', $r));
		$this->assertFalse(Set::matches('/Article', $r) || Set::matches('/Comment', $r));
		$this->assertTrue(Set::matches('/ArticleFeatured', $r));
		$this->assertFalse(Set::matches('/ArticleFeatured/User', $r) || Set::matches('/ArticleFeatured/Comment', $r) || Set::matches('/ArticleFeatured/Tag', $r));
		$this->assertTrue(Set::matches('/ArticleFeatured/Featured', $r));
		$this->assertFalse(Set::matches('/ArticleFeatured/Featured/ArticleFeatured', $r));
		$this->assertTrue(Set::matches('/ArticleFeatured/Featured/Category', $r));
		$this->assertTrue(Set::matches('/ArticleFeatured/Featured[id=1]', $r));
		$this->assertTrue(Set::matches('/ArticleFeatured/Featured[id=1]/Category[id=1]', $r));
		$this->assertTrue(Set::matches('/ArticleFeatured/Featured[id=1]/Category[name=Category 1]', $r));

		$r = $this->User->find('all', array('contain' => array(
			'ArticleFeatured' => array(
				'title',
				'Featured' => array(
					'id',
					'Category' => 'name'
				)
			)
		)));

		$this->assertTrue(Set::matches('/User[id=1]', $r));
		$this->assertFalse(Set::matches('/Article', $r) || Set::matches('/Comment', $r));
		$this->assertTrue(Set::matches('/ArticleFeatured', $r));
		$this->assertFalse(Set::matches('/ArticleFeatured/User', $r) || Set::matches('/ArticleFeatured/Comment', $r) || Set::matches('/ArticleFeatured/Tag', $r));
		$this->assertTrue(Set::matches('/ArticleFeatured/Featured', $r));
		$this->assertFalse(Set::matches('/ArticleFeatured/Featured/ArticleFeatured', $r));
		$this->assertTrue(Set::matches('/ArticleFeatured/Featured/Category', $r));
		$this->assertTrue(Set::matches('/ArticleFeatured/Featured[id=1]', $r));
		$this->assertTrue(Set::matches('/ArticleFeatured/Featured[id=1]/Category[name=Category 1]', $r));

		$result = $this->User->find('all', array('contain' => array(
			'ArticleFeatured' => array(
				'title',
				'Featured' => array(
					'category_id',
					'Category' => 'name'
				)
			)
		)));
		$expected = array(
			array(
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'ArticleFeatured' => array(
					array(
						'title' => 'First Article', 'id' => 1, 'user_id' => 1,
						'Featured' => array(
							'category_id' => 1, 'id' => 1,
							'Category' => array(
								'name' => 'Category 1'
							)
						)
					),
					array(
						'title' => 'Third Article', 'id' => 3, 'user_id' => 1,
						'Featured' => array()
					)
				)
			),
			array(
				'User' => array(
					'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
				),
				'ArticleFeatured' => array()
			),
			array(
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'ArticleFeatured' => array(
					array(
						'title' => 'Second Article', 'id' => 2, 'user_id' => 3,
						'Featured' => array(
							'category_id' => 1, 'id' => 2,
							'Category' => array(
								'name' => 'Category 1'
							)
						)
					)
				)
			),
			array(
				'User' => array(
					'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
				),
				'ArticleFeatured' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$orders = array(
			'title DESC', 'title DESC, published DESC',
			array('title' => 'DESC'), array('title' => 'DESC', 'published' => 'DESC'),
		);
		foreach ($orders as $order) {
			$result = $this->User->find('all', array('contain' => array(
				'ArticleFeatured' => array(
					'title', 'order' => $order,
					'Featured' => array(
						'category_id',
						'Category' => 'name'
					)
				)
			)));
			$expected = array(
				array(
					'User' => array(
						'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
					),
					'ArticleFeatured' => array(
						array(
							'title' => 'Third Article', 'id' => 3, 'user_id' => 1,
							'Featured' => array()
						),
						array(
							'title' => 'First Article', 'id' => 1, 'user_id' => 1,
							'Featured' => array(
								'category_id' => 1, 'id' => 1,
								'Category' => array(
									'name' => 'Category 1'
								)
							)
						)
					)
				),
				array(
					'User' => array(
						'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
					),
					'ArticleFeatured' => array()
				),
				array(
					'User' => array(
						'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
					),
					'ArticleFeatured' => array(
						array(
							'title' => 'Second Article', 'id' => 2, 'user_id' => 3,
							'Featured' => array(
								'category_id' => 1, 'id' => 2,
								'Category' => array(
									'name' => 'Category 1'
								)
							)
						)
					)
				),
				array(
					'User' => array(
						'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
					),
					'ArticleFeatured' => array()
				)
			);
			$this->assertEquals($expected, $result);
		}
	}

/**
 * testFindThirdLevelNonReset method
 *
 * @return void
 */
	public function testFindThirdLevelNonReset() {
		$this->User->contain(false, array('ArticleFeatured' => array('Featured' => 'Category')));
		$result = $this->User->find('all', array('recursive' => 3));
		$expected = array(
			array(
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
						'Featured' => array(
							'id' => 1, 'article_featured_id' => 1, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						)
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
						'Featured' => array()
					)
				)
			),
			array(
				'User' => array(
					'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
				),
				'ArticleFeatured' => array()
			),
			array(
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
						'Featured' => array(
							'id' => 2, 'article_featured_id' => 2, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						)
					)
				)
			),
			array(
				'User' => array(
					'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
				),
				'ArticleFeatured' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$this->User->resetBindings();

		$this->User->contain(false, array('ArticleFeatured' => array('Featured' => 'Category', 'Comment' => array('Article', 'Attachment'))));
		$result = $this->User->find('all', array('recursive' => 3));
		$expected = array(
			array(
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
						'Featured' => array(
							'id' => 1, 'article_featured_id' => 1, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							),
							array(
								'id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							),
							array(
								'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							),
							array(
								'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
								'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							)
						)
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
						'Featured' => array(),
						'Comment' => array()
					)
				)
			),
			array(
				'User' => array(
					'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
				),
				'ArticleFeatured' => array()
			),
			array(
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
						'Featured' => array(
							'id' => 2, 'article_featured_id' => 2, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
								'Article' => array(
									'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
								),
								'Attachment' => array(
									'id' => 1, 'comment_id' => 5, 'attachment' => 'attachment.zip',
									'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
								)
							),
							array(
								'id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31',
								'Article' => array(
									'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
								),
								'Attachment' => array()
							)
						)
					)
				)
			),
			array(
				'User' => array(
					'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
				),
				'ArticleFeatured' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$this->User->resetBindings();

		$this->User->contain(false, array('ArticleFeatured' => array('Featured' => 'Category', 'Comment' => 'Attachment'), 'Article'));
		$result = $this->User->find('all', array('recursive' => 3));
		$expected = array(
			array(
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Article' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
					)
				),
				'ArticleFeatured' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
						'Featured' => array(
							'id' => 1, 'article_featured_id' => 1, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31',
								'Attachment' => array()
							),
							array(
								'id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31',
								'Attachment' => array()
							),
							array(
								'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31',
								'Attachment' => array()
							),
							array(
								'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
								'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31',
								'Attachment' => array()
							)
						)
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
						'Featured' => array(),
						'Comment' => array()
					)
				)
			),
			array(
				'User' => array(
					'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
				),
				'Article' => array(),
				'ArticleFeatured' => array()
			),
			array(
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'Article' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
					)
				),
				'ArticleFeatured' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
						'Featured' => array(
							'id' => 2, 'article_featured_id' => 2, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
								'Attachment' => array(
									'id' => 1, 'comment_id' => 5, 'attachment' => 'attachment.zip',
									'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
								)
							),
							array(
								'id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31',
								'Attachment' => array()
							)
						)
					)
				)
			),
			array(
				'User' => array(
					'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
				),
				'Article' => array(),
				'ArticleFeatured' => array()
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testFindEmbeddedThirdLevelNonReset method
 *
 * @return void
 */
	public function testFindEmbeddedThirdLevelNonReset() {
		$result = $this->User->find('all', array('reset' => false, 'contain' => array('ArticleFeatured' => array('Featured' => 'Category'))));
		$expected = array(
			array(
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
						'Featured' => array(
							'id' => 1, 'article_featured_id' => 1, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						)
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
						'Featured' => array()
					)
				)
			),
			array(
				'User' => array(
					'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
				),
				'ArticleFeatured' => array()
			),
			array(
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
						'Featured' => array(
							'id' => 2, 'article_featured_id' => 2, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						)
					)
				)
			),
			array(
				'User' => array(
					'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
				),
				'ArticleFeatured' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$this->_assertBindings($this->User, array('hasMany' => array('ArticleFeatured')));
		$this->_assertBindings($this->User->ArticleFeatured, array('hasOne' => array('Featured')));
		$this->_assertBindings($this->User->ArticleFeatured->Featured, array('belongsTo' => array('Category')));

		$this->User->resetBindings();

		$this->_assertBindings($this->User, array('hasMany' => array('Article', 'ArticleFeatured', 'Comment')));
		$this->_assertBindings($this->User->ArticleFeatured, array('belongsTo' => array('User'), 'hasOne' => array('Featured'), 'hasMany' => array('Comment'), 'hasAndBelongsToMany' => array('Tag')));
		$this->_assertBindings($this->User->ArticleFeatured->Featured, array('belongsTo' => array('ArticleFeatured', 'Category')));
		$this->_assertBindings($this->User->ArticleFeatured->Comment, array('belongsTo' => array('Article', 'User'), 'hasOne' => array('Attachment')));

		$result = $this->User->find('all', array('reset' => false, 'contain' => array('ArticleFeatured' => array('Featured' => 'Category', 'Comment' => array('Article', 'Attachment')))));
		$expected = array(
			array(
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
						'Featured' => array(
							'id' => 1, 'article_featured_id' => 1, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							),
							array(
								'id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							),
							array(
								'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							),
							array(
								'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
								'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							)
						)
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
						'Featured' => array(),
						'Comment' => array()
					)
				)
			),
			array(
				'User' => array(
					'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
				),
				'ArticleFeatured' => array()
			),
			array(
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
						'Featured' => array(
							'id' => 2, 'article_featured_id' => 2, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
								'Article' => array(
									'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
								),
								'Attachment' => array(
									'id' => 1, 'comment_id' => 5, 'attachment' => 'attachment.zip',
									'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
								)
							),
							array(
								'id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31',
								'Article' => array(
									'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
								),
								'Attachment' => array()
							)
						)
					)
				)
			),
			array(
				'User' => array(
					'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
				),
				'ArticleFeatured' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$this->_assertBindings($this->User, array('hasMany' => array('ArticleFeatured')));
		$this->_assertBindings($this->User->ArticleFeatured, array('hasOne' => array('Featured'), 'hasMany' => array('Comment')));
		$this->_assertBindings($this->User->ArticleFeatured->Featured, array('belongsTo' => array('Category')));
		$this->_assertBindings($this->User->ArticleFeatured->Comment, array('belongsTo' => array('Article'), 'hasOne' => array('Attachment')));

		$this->User->resetBindings();
		$this->_assertBindings($this->User, array('hasMany' => array('Article', 'ArticleFeatured', 'Comment')));
		$this->_assertBindings($this->User->ArticleFeatured, array('belongsTo' => array('User'), 'hasOne' => array('Featured'), 'hasMany' => array('Comment'), 'hasAndBelongsToMany' => array('Tag')));
		$this->_assertBindings($this->User->ArticleFeatured->Featured, array('belongsTo' => array('ArticleFeatured', 'Category')));
		$this->_assertBindings($this->User->ArticleFeatured->Comment, array('belongsTo' => array('Article', 'User'), 'hasOne' => array('Attachment')));

		$result = $this->User->find('all', array('contain' => array('ArticleFeatured' => array('Featured' => 'Category', 'Comment' => array('Article', 'Attachment')), false)));
		$expected = array(
			array(
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
						'Featured' => array(
							'id' => 1, 'article_featured_id' => 1, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							),
							array(
								'id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							),
							array(
								'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							),
							array(
								'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
								'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31',
								'Article' => array(
									'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
								),
								'Attachment' => array()
							)
						)
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
						'Featured' => array(),
						'Comment' => array()
					)
				)
			),
			array(
				'User' => array(
					'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
				),
				'ArticleFeatured' => array()
			),
			array(
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'ArticleFeatured' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
						'Featured' => array(
							'id' => 2, 'article_featured_id' => 2, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
								'Article' => array(
									'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
								),
								'Attachment' => array(
									'id' => 1, 'comment_id' => 5, 'attachment' => 'attachment.zip',
									'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
								)
							),
							array(
								'id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31',
								'Article' => array(
									'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
									'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
								),
								'Attachment' => array()
							)
						)
					)
				)
			),
			array(
				'User' => array(
					'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
				),
				'ArticleFeatured' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$this->_assertBindings($this->User, array('hasMany' => array('ArticleFeatured')));
		$this->_assertBindings($this->User->ArticleFeatured, array('hasOne' => array('Featured'), 'hasMany' => array('Comment')));
		$this->_assertBindings($this->User->ArticleFeatured->Featured, array('belongsTo' => array('Category')));
		$this->_assertBindings($this->User->ArticleFeatured->Comment, array('belongsTo' => array('Article'), 'hasOne' => array('Attachment')));

		$this->User->resetBindings();
		$this->_assertBindings($this->User, array('hasMany' => array('Article', 'ArticleFeatured', 'Comment')));
		$this->_assertBindings($this->User->ArticleFeatured, array('belongsTo' => array('User'), 'hasOne' => array('Featured'), 'hasMany' => array('Comment'), 'hasAndBelongsToMany' => array('Tag')));
		$this->_assertBindings($this->User->ArticleFeatured->Featured, array('belongsTo' => array('ArticleFeatured', 'Category')));
		$this->_assertBindings($this->User->ArticleFeatured->Comment, array('belongsTo' => array('Article', 'User'), 'hasOne' => array('Attachment')));

		$result = $this->User->find('all', array('reset' => false, 'contain' => array('ArticleFeatured' => array('Featured' => 'Category', 'Comment' => 'Attachment'), 'Article')));
		$expected = array(
			array(
				'User' => array(
					'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Article' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
					)
				),
				'ArticleFeatured' => array(
					array(
						'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
						'Featured' => array(
							'id' => 1, 'article_featured_id' => 1, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31',
								'Attachment' => array()
							),
							array(
								'id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31',
								'Attachment' => array()
							),
							array(
								'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31',
								'Attachment' => array()
							),
							array(
								'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
								'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31',
								'Attachment' => array()
							)
						)
					),
					array(
						'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31',
						'Featured' => array(),
						'Comment' => array()
					)
				)
			),
			array(
				'User' => array(
					'id' => 2, 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
				),
				'Article' => array(),
				'ArticleFeatured' => array()
			),
			array(
				'User' => array(
					'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'Article' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
					)
				),
				'ArticleFeatured' => array(
					array(
						'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
						'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
						'Featured' => array(
							'id' => 2, 'article_featured_id' => 2, 'category_id' => 1, 'published_date' => '2007-03-31 10:39:23',
							'end_date' => '2007-05-15 10:39:23', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
							'Category' => array(
								'id' => 1, 'parent_id' => 0, 'name' => 'Category 1',
								'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'
							)
						),
						'Comment' => array(
							array(
								'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
								'Attachment' => array(
									'id' => 1, 'comment_id' => 5, 'attachment' => 'attachment.zip',
									'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
								)
							),
							array(
								'id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31',
								'Attachment' => array()
							)
						)
					)
				)
			),
			array(
				'User' => array(
					'id' => 4, 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'
				),
				'Article' => array(),
				'ArticleFeatured' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$this->_assertBindings($this->User, array('hasMany' => array('Article', 'ArticleFeatured')));
		$this->_assertBindings($this->User->Article);
		$this->_assertBindings($this->User->ArticleFeatured, array('hasOne' => array('Featured'), 'hasMany' => array('Comment')));
		$this->_assertBindings($this->User->ArticleFeatured->Featured, array('belongsTo' => array('Category')));
		$this->_assertBindings($this->User->ArticleFeatured->Comment, array('hasOne' => array('Attachment')));

		$this->User->resetBindings();
		$this->_assertBindings($this->User, array('hasMany' => array('Article', 'ArticleFeatured', 'Comment')));
		$this->_assertBindings($this->User->Article, array('belongsTo' => array('User'), 'hasMany' => array('Comment'), 'hasAndBelongsToMany' => array('Tag')));
		$this->_assertBindings($this->User->ArticleFeatured, array('belongsTo' => array('User'), 'hasOne' => array('Featured'), 'hasMany' => array('Comment'), 'hasAndBelongsToMany' => array('Tag')));
		$this->_assertBindings($this->User->ArticleFeatured->Featured, array('belongsTo' => array('ArticleFeatured', 'Category')));
		$this->_assertBindings($this->User->ArticleFeatured->Comment, array('belongsTo' => array('Article', 'User'), 'hasOne' => array('Attachment')));
	}

/**
 * testEmbeddedFindFields method
 *
 * @return void
 */
	public function testEmbeddedFindFields() {
		$result = $this->Article->find('all', array(
			'contain' => array('User(user)'),
			'fields' => array('title'),
			'order' => array('Article.id' => 'ASC')
		));
		$expected = array(
			array('Article' => array('title' => 'First Article'), 'User' => array('user' => 'mariano', 'id' => 1)),
			array('Article' => array('title' => 'Second Article'), 'User' => array('user' => 'larry', 'id' => 3)),
			array('Article' => array('title' => 'Third Article'), 'User' => array('user' => 'mariano', 'id' => 1)),
		);
		$this->assertEquals($expected, $result);

		$result = $this->Article->find('all', array(
			'contain' => array('User(id, user)'),
			'fields' => array('title'),
			'order' => array('Article.id' => 'ASC')
		));
		$expected = array(
			array('Article' => array('title' => 'First Article'), 'User' => array('user' => 'mariano', 'id' => 1)),
			array('Article' => array('title' => 'Second Article'), 'User' => array('user' => 'larry', 'id' => 3)),
			array('Article' => array('title' => 'Third Article'), 'User' => array('user' => 'mariano', 'id' => 1)),
		);
		$this->assertEquals($expected, $result);

		$result = $this->Article->find('all', array(
			'contain' => array(
				'Comment(comment, published)' => 'Attachment(attachment)', 'User(user)'
			),
			'fields' => array('title'),
			'order' => array('Article.id' => 'ASC')
		));
		if (!empty($result)) {
			foreach ($result as $i => $article) {
				foreach ($article['Comment'] as $j => $comment) {
					$result[$i]['Comment'][$j] = array_diff_key($comment, array('id' => true));
				}
			}
		}
		$expected = array(
			array(
				'Article' => array('title' => 'First Article', 'id' => 1),
				'User' => array('user' => 'mariano', 'id' => 1),
				'Comment' => array(
					array('comment' => 'First Comment for First Article', 'published' => 'Y', 'article_id' => 1, 'Attachment' => array()),
					array('comment' => 'Second Comment for First Article', 'published' => 'Y', 'article_id' => 1, 'Attachment' => array()),
					array('comment' => 'Third Comment for First Article', 'published' => 'Y', 'article_id' => 1, 'Attachment' => array()),
					array('comment' => 'Fourth Comment for First Article', 'published' => 'N', 'article_id' => 1, 'Attachment' => array()),
				)
			),
			array(
				'Article' => array('title' => 'Second Article', 'id' => 2),
				'User' => array('user' => 'larry', 'id' => 3),
				'Comment' => array(
					array('comment' => 'First Comment for Second Article', 'published' => 'Y', 'article_id' => 2, 'Attachment' => array(
						'attachment' => 'attachment.zip', 'id' => 1
					)),
					array('comment' => 'Second Comment for Second Article', 'published' => 'Y', 'article_id' => 2, 'Attachment' => array())
				)
			),
			array(
				'Article' => array('title' => 'Third Article', 'id' => 3),
				'User' => array('user' => 'mariano', 'id' => 1),
				'Comment' => array()
			),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test that hasOne and belongsTo fields act the same in a contain array.
 *
 * @return void
 */
	public function testHasOneFieldsInContain() {
		$this->Article->unbindModel(array(
			'hasMany' => array('Comment')
		), true);
		unset($this->Article->Comment);
		$this->Article->bindModel(array(
			'hasOne' => array('Comment')
		));

		$result = $this->Article->find('all', array(
			'fields' => array('title', 'body'),
			'contain' => array(
				'Comment' => array(
					'fields' => array('comment')
				),
				'User' => array(
					'fields' => array('user')
				)
			),
			'order' => 'Article.id ASC',
		));
		$this->assertTrue(isset($result[0]['Article']['title']), 'title missing %s');
		$this->assertTrue(isset($result[0]['Article']['body']), 'body missing %s');
		$this->assertTrue(isset($result[0]['Comment']['comment']), 'comment missing %s');
		$this->assertTrue(isset($result[0]['User']['user']), 'body missing %s');
		$this->assertFalse(isset($result[0]['Comment']['published']), 'published found %s');
		$this->assertFalse(isset($result[0]['User']['password']), 'password found %s');
	}

/**
 * testFindConditionalBinding method
 *
 * @return void
 */
	public function testFindConditionalBinding() {
		$this->Article->contain(array(
			'User(user)',
			'Tag' => array(
				'fields' => array('tag', 'created'),
				'conditions' => array('created >=' => '2007-03-18 12:24')
			)
		));
		$result = $this->Article->find('all', array(
			'fields' => array('title'),
			'order' => array('Article.id' => 'ASC')
		));
		$expected = array(
			array(
				'Article' => array('id' => 1, 'title' => 'First Article'),
				'User' => array('id' => 1, 'user' => 'mariano'),
				'Tag' => array(array('tag' => 'tag2', 'created' => '2007-03-18 12:24:23'))
			),
			array(
				'Article' => array('id' => 2, 'title' => 'Second Article'),
				'User' => array('id' => 3, 'user' => 'larry'),
				'Tag' => array(array('tag' => 'tag3', 'created' => '2007-03-18 12:26:23'))
			),
			array(
				'Article' => array('id' => 3, 'title' => 'Third Article'),
				'User' => array('id' => 1, 'user' => 'mariano'),
				'Tag' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$this->Article->contain(array('User(id,user)', 'Tag' => array('fields' => array('tag', 'created'))));
		$result = $this->Article->find('all', array('fields' => array('title'), 'order' => array('Article.id' => 'ASC')));
		$expected = array(
			array(
				'Article' => array('id' => 1, 'title' => 'First Article'),
				'User' => array('id' => 1, 'user' => 'mariano'),
				'Tag' => array(
					array('tag' => 'tag1', 'created' => '2007-03-18 12:22:23'),
					array('tag' => 'tag2', 'created' => '2007-03-18 12:24:23')
				)
			),
			array(
				'Article' => array('id' => 2, 'title' => 'Second Article'),
				'User' => array('id' => 3, 'user' => 'larry'),
				'Tag' => array(
					array('tag' => 'tag1', 'created' => '2007-03-18 12:22:23'),
					array('tag' => 'tag3', 'created' => '2007-03-18 12:26:23')
				)
			),
			array(
				'Article' => array('id' => 3, 'title' => 'Third Article'),
				'User' => array('id' => 1, 'user' => 'mariano'),
				'Tag' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$result = $this->Article->find('all', array(
			'fields' => array('title'),
			'contain' => array('User(id,user)', 'Tag' => array('fields' => array('tag', 'created'))),
			'order' => array('Article.id' => 'ASC')
		));
		$expected = array(
			array(
				'Article' => array('id' => 1, 'title' => 'First Article'),
				'User' => array('id' => 1, 'user' => 'mariano'),
				'Tag' => array(
					array('tag' => 'tag1', 'created' => '2007-03-18 12:22:23'),
					array('tag' => 'tag2', 'created' => '2007-03-18 12:24:23')
				)
			),
			array(
				'Article' => array('id' => 2, 'title' => 'Second Article'),
				'User' => array('id' => 3, 'user' => 'larry'),
				'Tag' => array(
					array('tag' => 'tag1', 'created' => '2007-03-18 12:22:23'),
					array('tag' => 'tag3', 'created' => '2007-03-18 12:26:23')
				)
			),
			array(
				'Article' => array('id' => 3, 'title' => 'Third Article'),
				'User' => array('id' => 1, 'user' => 'mariano'),
				'Tag' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$this->Article->contain(array(
			'User(id,user)',
			'Tag' => array(
				'fields' => array('tag', 'created'),
				'conditions' => array('created >=' => '2007-03-18 12:24')
			)
		));
		$result = $this->Article->find('all', array('fields' => array('title'), 'order' => array('Article.id' => 'ASC')));
		$expected = array(
			array(
				'Article' => array('id' => 1, 'title' => 'First Article'),
				'User' => array('id' => 1, 'user' => 'mariano'),
				'Tag' => array(array('tag' => 'tag2', 'created' => '2007-03-18 12:24:23'))
			),
			array(
				'Article' => array('id' => 2, 'title' => 'Second Article'),
				'User' => array('id' => 3, 'user' => 'larry'),
				'Tag' => array(array('tag' => 'tag3', 'created' => '2007-03-18 12:26:23'))
			),
			array(
				'Article' => array('id' => 3, 'title' => 'Third Article'),
				'User' => array('id' => 1, 'user' => 'mariano'),
				'Tag' => array()
			)
		);
		$this->assertEquals($expected, $result);

		$this->assertTrue(empty($this->User->Article->hasAndBelongsToMany['Tag']['conditions']));

		$result = $this->User->find('all', array('contain' => array(
			'Article.Tag' => array('conditions' => array('created >=' => '2007-03-18 12:24'))
		)));

		$this->assertTrue(Set::matches('/User[id=1]', $result));
		$this->assertFalse(Set::matches('/Article[id=1]/Tag[id=1]', $result));
		$this->assertTrue(Set::matches('/Article[id=1]/Tag[id=2]', $result));
		$this->assertTrue(empty($this->User->Article->hasAndBelongsToMany['Tag']['conditions']));

		$this->assertTrue(empty($this->User->Article->hasAndBelongsToMany['Tag']['order']));

		$result = $this->User->find('all', array('contain' => array(
			'Article.Tag' => array('order' => 'created DESC')
		)));

		$this->assertTrue(Set::matches('/User[id=1]', $result));
		$this->assertTrue(Set::matches('/Article[id=1]/Tag[id=1]', $result));
		$this->assertTrue(Set::matches('/Article[id=1]/Tag[id=2]', $result));
		$this->assertTrue(empty($this->User->Article->hasAndBelongsToMany['Tag']['order']));
	}

/**
 * testOtherFinds method
 *
 * @return void
 */
	public function testOtherFinds() {
		$result = $this->Article->find('count');
		$expected = 3;
		$this->assertEquals($expected, $result);

		$result = $this->Article->find('count', array('conditions' => array('Article.id >' => '1')));
		$expected = 2;
		$this->assertEquals($expected, $result);

		$result = $this->Article->find('count', array('contain' => array()));
		$expected = 3;
		$this->assertEquals($expected, $result);

		$this->Article->contain(array('User(id,user)', 'Tag' => array('fields' => array('tag', 'created'), 'conditions' => array('created >=' => '2007-03-18 12:24'))));
		$result = $this->Article->find('first', array('fields' => array('title')));
		$expected = array(
			'Article' => array('id' => 1, 'title' => 'First Article'),
			'User' => array('id' => 1, 'user' => 'mariano'),
			'Tag' => array(array('tag' => 'tag2', 'created' => '2007-03-18 12:24:23'))
		);
		$this->assertEquals($expected, $result);

		$this->Article->contain(array('User(id,user)', 'Tag' => array('fields' => array('tag', 'created'))));
		$result = $this->Article->find('first', array('fields' => array('title')));
		$expected = array(
			'Article' => array('id' => 1, 'title' => 'First Article'),
			'User' => array('id' => 1, 'user' => 'mariano'),
			'Tag' => array(
				array('tag' => 'tag1', 'created' => '2007-03-18 12:22:23'),
				array('tag' => 'tag2', 'created' => '2007-03-18 12:24:23')
			)
		);
		$this->assertEquals($expected, $result);

		$result = $this->Article->find('first', array(
			'fields' => array('title'),
			'order' => 'Article.id DESC',
			'contain' => array('User(id,user)', 'Tag' => array('fields' => array('tag', 'created')))
		));
		$expected = array(
			'Article' => array('id' => 3, 'title' => 'Third Article'),
			'User' => array('id' => 1, 'user' => 'mariano'),
			'Tag' => array()
		);
		$this->assertEquals($expected, $result);

		$result = $this->Article->find('list', array(
			'contain' => array('User(id,user)'),
			'fields' => array('Article.id', 'Article.title')
		));
		$expected = array(
			1 => 'First Article',
			2 => 'Second Article',
			3 => 'Third Article'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testOriginalAssociations method
 *
 * @return void
 */
	public function testOriginalAssociations() {
		$this->Article->Comment->Behaviors->attach('Containable');

		$options = array(
			'conditions' => array(
				'Comment.published' => 'Y',
			),
			'contain' => 'User',
			'recursive' => 1
		);

		$firstResult = $this->Article->Comment->find('all', $options);

		$this->Article->Comment->find('all', array(
			'conditions' => array(
				'User.user' => 'mariano'
			),
			'fields' => array('User.password'),
			'contain' => array('User.password'),
		));

		$result = $this->Article->Comment->find('all', $options);
		$this->assertEquals($firstResult, $result);

		$this->Article->unbindModel(array('hasMany' => array('Comment'), 'belongsTo' => array('User'), 'hasAndBelongsToMany' => array('Tag')), false);
		$this->Article->bindModel(array('hasMany' => array('Comment'), 'belongsTo' => array('User')), false);

		$r = $this->Article->find('all', array('contain' => array('Comment(comment)', 'User(user)'), 'fields' => array('title')));
		$this->assertTrue(Set::matches('/Article[id=1]', $r));
		$this->assertTrue(Set::matches('/User[id=1]', $r));
		$this->assertTrue(Set::matches('/Comment[article_id=1]', $r));
		$this->assertFalse(Set::matches('/Comment[id=1]', $r));

		$r = $this->Article->find('all');
		$this->assertTrue(Set::matches('/Article[id=1]', $r));
		$this->assertTrue(Set::matches('/User[id=1]', $r));
		$this->assertTrue(Set::matches('/Comment[article_id=1]', $r));
		$this->assertTrue(Set::matches('/Comment[id=1]', $r));

		$this->Article->bindModel(array('hasAndBelongsToMany' => array('Tag')), false);

		$this->Article->contain(false, array('User(id,user)', 'Comment' => array('fields' => array('comment'), 'conditions' => array('created >=' => '2007-03-18 10:49'))));
		$result = $this->Article->find('all', array('fields' => array('title'), 'limit' => 1, 'page' => 1, 'order' => 'Article.id ASC'));
		$expected = array(array(
			'Article' => array('id' => 1, 'title' => 'First Article'),
			'User' => array('id' => 1, 'user' => 'mariano'),
			'Comment' => array(
				array('comment' => 'Third Comment for First Article', 'article_id' => 1),
				array('comment' => 'Fourth Comment for First Article', 'article_id' => 1)
			)
		));
		$this->assertEquals($expected, $result);

		$result = $this->Article->find('all', array('fields' => array('title', 'User.id', 'User.user'), 'limit' => 1, 'page' => 2, 'order' => 'Article.id ASC'));
		$expected = array(array(
			'Article' => array('id' => 2, 'title' => 'Second Article'),
			'User' => array('id' => 3, 'user' => 'larry'),
			'Comment' => array(
				array('comment' => 'First Comment for Second Article', 'article_id' => 2),
				array('comment' => 'Second Comment for Second Article', 'article_id' => 2)
			)
		));
		$this->assertEquals($expected, $result);

		$result = $this->Article->find('all', array('fields' => array('title', 'User.id', 'User.user'), 'limit' => 1, 'page' => 3, 'order' => 'Article.id ASC'));
		$expected = array(array(
			'Article' => array('id' => 3, 'title' => 'Third Article'),
			'User' => array('id' => 1, 'user' => 'mariano'),
			'Comment' => array()
		));
		$this->assertEquals($expected, $result);

		$this->Article->contain(false, array('User' => array('fields' => 'user'), 'Comment'));
		$result = $this->Article->find('all');
		$this->assertTrue(Set::matches('/Article[id=1]', $result));
		$this->assertTrue(Set::matches('/User[user=mariano]', $result));
		$this->assertTrue(Set::matches('/Comment[article_id=1]', $result));
		$this->Article->resetBindings();

		$this->Article->contain(false, array('User' => array('fields' => array('user')), 'Comment'));
		$result = $this->Article->find('all');
		$this->assertTrue(Set::matches('/Article[id=1]', $result));
		$this->assertTrue(Set::matches('/User[user=mariano]', $result));
		$this->assertTrue(Set::matches('/Comment[article_id=1]', $result));
		$this->Article->resetBindings();
	}

/**
 * testResetAddedAssociation method
 *
 */
	public function testResetAddedAssociation() {
		$this->assertTrue(empty($this->Article->hasMany['ArticlesTag']));

		$this->Article->bindModel(array(
			'hasMany' => array('ArticlesTag')
		));
		$this->assertTrue(!empty($this->Article->hasMany['ArticlesTag']));

		$result = $this->Article->find('first', array(
			'conditions' => array('Article.id' => 1),
			'contain' => array('ArticlesTag')
		));

		$expected = array('Article', 'ArticlesTag');
		$this->assertTrue(!empty($result));
		$this->assertEquals('First Article', $result['Article']['title']);
		$this->assertTrue(!empty($result['ArticlesTag']));
		$this->assertEquals($expected, array_keys($result));

		$this->assertTrue(empty($this->Article->hasMany['ArticlesTag']));

		$this->JoinA = ClassRegistry::init('JoinA');
		$this->JoinB = ClassRegistry::init('JoinB');
		$this->JoinC = ClassRegistry::init('JoinC');

		$this->JoinA->Behaviors->attach('Containable');
		$this->JoinB->Behaviors->attach('Containable');
		$this->JoinC->Behaviors->attach('Containable');

		$this->JoinA->JoinB->find('all', array('contain' => array('JoinA')));
		$this->JoinA->bindModel(array('hasOne' => array('JoinAsJoinC' => array('joinTable' => 'as_cs'))), false);
		$result = $this->JoinA->hasOne;
		$this->JoinA->find('all');
		$resultAfter = $this->JoinA->hasOne;
		$this->assertEquals($result, $resultAfter);
	}

/**
 * testResetAssociation method
 *
 */
	public function testResetAssociation() {
		$this->Article->Behaviors->attach('Containable');
		$this->Article->Comment->Behaviors->attach('Containable');
		$this->Article->User->Behaviors->attach('Containable');

		$initialOptions = array(
			'conditions' => array(
				'Comment.published' => 'Y',
			),
			'contain' => 'User',
			'recursive' => 1,
		);

		$initialModels = $this->Article->Comment->find('all', $initialOptions);

		$findOptions = array(
			'conditions' => array(
				'User.user' => 'mariano',
			),
			'fields' => array('User.password'),
			'contain' => array('User.password')
		);
		$result = $this->Article->Comment->find('all', $findOptions);
		$result = $this->Article->Comment->find('all', $initialOptions);
		$this->assertEquals($initialModels, $result);
	}

/**
 * testResetDeeperHasOneAssociations method
 *
 */
	public function testResetDeeperHasOneAssociations() {
		$this->Article->User->unbindModel(array(
			'hasMany' => array('ArticleFeatured', 'Comment')
		), false);
		$userHasOne = array('hasOne' => array('ArticleFeatured', 'Comment'));

		$this->Article->User->bindModel($userHasOne, false);
		$expected = $this->Article->User->hasOne;
		$this->Article->find('all');
		$this->assertEquals($expected, $this->Article->User->hasOne);

		$this->Article->User->bindModel($userHasOne, false);
		$expected = $this->Article->User->hasOne;
		$this->Article->find('all', array(
			'contain' => array(
				'User' => array('ArticleFeatured', 'Comment')
			)
		));
		$this->assertEquals($expected, $this->Article->User->hasOne);

		$this->Article->User->bindModel($userHasOne, false);
		$expected = $this->Article->User->hasOne;
		$this->Article->find('all', array(
			'contain' => array(
				'User' => array(
					'ArticleFeatured',
					'Comment' => array('fields' => array('created'))
				)
			)
		));
		$this->assertEquals($expected, $this->Article->User->hasOne);

		$this->Article->User->bindModel($userHasOne, false);
		$expected = $this->Article->User->hasOne;
		$this->Article->find('all', array(
			'contain' => array(
				'User' => array(
					'Comment' => array('fields' => array('created'))
				)
			)
		));
		$this->assertEquals($expected, $this->Article->User->hasOne);

		$this->Article->User->bindModel($userHasOne, false);
		$expected = $this->Article->User->hasOne;
		$this->Article->find('all', array(
			'contain' => array(
				'User.ArticleFeatured' => array(
					'conditions' => array('ArticleFeatured.published' => 'Y')
				),
				'User.Comment'
			)
		));
		$this->assertEquals($expected, $this->Article->User->hasOne);
	}

/**
 * testResetMultipleHabtmAssociations method
 *
 */
	public function testResetMultipleHabtmAssociations() {
		$articleHabtm = array(
			'hasAndBelongsToMany' => array(
				'Tag' => array(
					'className' => 'Tag',
					'joinTable' => 'articles_tags',
					'foreignKey' => 'article_id',
					'associationForeignKey' => 'tag_id'
				),
				'ShortTag' => array(
					'className' => 'Tag',
					'joinTable' => 'articles_tags',
					'foreignKey' => 'article_id',
					'associationForeignKey' => 'tag_id',
					// LENGHT function mysql-only, using LIKE does almost the same
					'conditions' => "ShortTag.tag LIKE '???'"
				)
			)
		);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all');
		$this->assertEquals($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => 'Tag.tag'));
		$this->assertEquals($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => 'Tag'));
		$this->assertEquals($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => array('Tag' => array('fields' => array(null)))));
		$this->assertEquals($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => array('Tag' => array('fields' => array('Tag.tag')))));
		$this->assertEquals($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => array('Tag' => array('fields' => array('Tag.tag', 'Tag.created')))));
		$this->assertEquals($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => 'ShortTag.tag'));
		$this->assertEquals($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => 'ShortTag'));
		$this->assertEquals($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => array('ShortTag' => array('fields' => array(null)))));
		$this->assertEquals($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => array('ShortTag' => array('fields' => array('ShortTag.tag')))));
		$this->assertEquals($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => array('ShortTag' => array('fields' => array('ShortTag.tag', 'ShortTag.created')))));
		$this->assertEquals($expected, $this->Article->hasAndBelongsToMany);
	}

/**
 * test that bindModel and unbindModel work with find() calls in between.
 */
	public function testBindMultipleTimesWithFind() {
		$binding = array(
			'hasOne' => array(
				'ArticlesTag' => array(
					'foreignKey' => false,
					'type' => 'INNER',
					'conditions' => array(
						'ArticlesTag.article_id = Article.id'
					)
				),
				'Tag' => array(
					'type' => 'INNER',
					'foreignKey' => false,
					'conditions' => array(
						'ArticlesTag.tag_id = Tag.id'
					)
				)
			)
		);
		$this->Article->unbindModel(array('hasAndBelongsToMany' => array('Tag')));
		$this->Article->bindModel($binding);
		$result = $this->Article->find('all', array('limit' => 1, 'contain' => array('ArticlesTag', 'Tag')));

		$this->Article->unbindModel(array('hasAndBelongsToMany' => array('Tag')));
		$this->Article->bindModel($binding);
		$result = $this->Article->find('all', array('limit' => 1, 'contain' => array('ArticlesTag', 'Tag')));

		$associated = $this->Article->getAssociated();
		$this->assertEquals('hasAndBelongsToMany', $associated['Tag']);
		$this->assertFalse(isset($associated['ArticleTag']));
	}

/**
 * test that autoFields doesn't splice in fields from other databases.
 *
 * @return void
 */
	public function testAutoFieldsWithMultipleDatabases() {
		$config = new DATABASE_CONFIG();

		$this->skipIf(
			!isset($config->test) || !isset($config->test2),
			'Primary and secondary test databases not configured, ' .
			'skipping cross-database join tests. ' .
			' To run these tests, you must define $test and $test2 ' .
			'in your database configuration.'
		);

		$db = ConnectionManager::getDataSource('test2');
		$this->fixtureManager->loadSingle('User', $db);

		$this->Article->User->setDataSource('test2');

		$result = $this->Article->find('all', array(
			'fields' => array('Article.title'),
			'contain' => array('User')
		));
		$this->assertTrue(isset($result[0]['Article']));
		$this->assertTrue(isset($result[0]['User']));
	}

/**
 * test that autoFields doesn't splice in columns that aren't part of the join.
 *
 * @return void
 */
	public function testAutoFieldsWithRecursiveNegativeOne() {
		$this->Article->recursive = -1;
		$result = $this->Article->field('title', array('Article.title' => 'First Article'));
		$this->assertNoErrors();
		$this->assertEquals('First Article', $result, 'Field is wrong');
	}

/**
 * test that find(all) doesn't return incorrect values when mixed with containable.
 *
 * @return void
 */
	public function testFindAllReturn() {
		$result = $this->Article->find('all', array(
			'conditions' => array('Article.id' => 999999999)
		));
		$this->assertEmpty($result, 'Should be empty.');
	}

/**
 * testLazyLoad method
 *
 * @return void
 */
	public function testLazyLoad() {
		// Local set up
		$this->User = ClassRegistry::init('User');
		$this->User->bindModel(array(
			'hasMany' => array('Article', 'ArticleFeatured', 'Comment')
		), false);

		try {
			$this->User->find('first', array(
				'contain' => 'Comment',
				'lazyLoad' => true
			));
		} catch (Exception $e) {
			$exceptions = true;
		}
		$this->assertTrue(empty($exceptions));
	}

/**
 * _containments method
 *
 * @param Model $Model
 * @param array $contain
 * @return void
 */
	protected function _containments($Model, $contain = array()) {
		if (!is_array($Model)) {
			$result = $Model->containments($contain);
			return $this->_containments($result['models']);
		} else {
			$result = $Model;
			foreach ($result as $i => $containment) {
				$result[$i] = array_diff_key($containment, array('instance' => true));
			}
		}
		return $result;
	}

/**
 * _assertBindings method
 *
 * @param Model $Model
 * @param array $expected
 * @return void
 */
	protected function _assertBindings(Model $Model, $expected = array()) {
		$expected = array_merge(array(
			'belongsTo' => array(),
			'hasOne' => array(),
			'hasMany' => array(),
			'hasAndBelongsToMany' => array()
		), $expected);
		foreach ($expected as $binding => $expect) {
			$this->assertEquals(array_keys($Model->$binding), $expect);
		}
	}
}
