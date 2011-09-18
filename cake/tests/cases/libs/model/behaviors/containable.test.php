<?php
/**
 * ContainableBehaviorTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.behaviors
 * @since         CakePHP(tm) v 1.2.0.5669
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', array('AppModel', 'Model'));
require_once(dirname(dirname(__FILE__)) . DS . 'models.php');

/**
 * ContainableTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.behaviors
 */
class ContainableBehaviorTest extends CakeTestCase {

/**
 * Fixtures associated with this test case
 *
 * @var array
 * @access public
 */
	var $fixtures = array(
		'core.article', 'core.article_featured', 'core.article_featureds_tags', 'core.articles_tag', 'core.attachment', 'core.category',
		'core.comment', 'core.featured', 'core.tag', 'core.user', 'core.join_a', 'core.join_b', 'core.join_c', 'core.join_a_c', 'core.join_a_b'
	);

/**
 * Method executed before each test
 *
 * @access public
 */
	function startTest() {
		$this->User =& ClassRegistry::init('User');
		$this->Article =& ClassRegistry::init('Article');
		$this->Tag =& ClassRegistry::init('Tag');

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
 * @access public
 */
	function endTest() {
		unset($this->Article);
		unset($this->User);
		unset($this->Tag);

		ClassRegistry::flush();
	}

/**
 * testContainments method
 *
 * @access public
 * @return void
 */
	function testContainments() {
		$r = $this->__containments($this->Article, array('Comment' => array('conditions' => array('Comment.user_id' => 2))));
		$this->assertTrue(Set::matches('/Article/keep/Comment/conditions[Comment.user_id=2]', $r));

		$r = $this->__containments($this->User, array(
			'ArticleFeatured' => array(
				'Featured' => array(
					'id',
					'Category' => 'name'
				)
		)));
		$this->assertEqual(Set::extract('/ArticleFeatured/keep/Featured/fields', $r), array('id'));

		$r = $this->__containments($this->Article, array(
			'Comment' => array(
				'User',
				'conditions' => array('Comment' => array('user_id' => 2)),
			),
		));
		$this->assertTrue(Set::matches('/User', $r));
		$this->assertTrue(Set::matches('/Comment', $r));
		$this->assertTrue(Set::matches('/Article/keep/Comment/conditions/Comment[user_id=2]', $r));

		$r = $this->__containments($this->Article, array('Comment(comment, published)' => 'Attachment(attachment)', 'User(user)'));
		$this->assertTrue(Set::matches('/Comment', $r));
		$this->assertTrue(Set::matches('/User', $r));
		$this->assertTrue(Set::matches('/Article/keep/Comment', $r));
		$this->assertTrue(Set::matches('/Article/keep/User', $r));
		$this->assertEqual(Set::extract('/Article/keep/Comment/fields', $r), array('comment', 'published'));
		$this->assertEqual(Set::extract('/Article/keep/User/fields', $r), array('user'));
		$this->assertTrue(Set::matches('/Comment/keep/Attachment', $r));
		$this->assertEqual(Set::extract('/Comment/keep/Attachment/fields', $r), array('attachment'));

		$r = $this->__containments($this->Article, array('Comment' => array('limit' => 1)));
		$this->assertEqual(array_keys($r), array('Comment', 'Article'));
		$this->assertEqual(array_shift(Set::extract('/Comment/keep', $r)), array('keep' => array()));
		$this->assertTrue(Set::matches('/Article/keep/Comment', $r));
		$this->assertEqual(array_shift(Set::extract('/Article/keep/Comment/.', $r)), array('limit' => 1));

		$r = $this->__containments($this->Article, array('Comment.User'));
		$this->assertEqual(array_keys($r), array('User', 'Comment', 'Article'));
		$this->assertEqual(array_shift(Set::extract('/User/keep', $r)), array('keep' => array()));
		$this->assertEqual(array_shift(Set::extract('/Comment/keep', $r)), array('keep' => array('User' => array())));
		$this->assertEqual(array_shift(Set::extract('/Article/keep', $r)), array('keep' => array('Comment' => array())));

		$r = $this->__containments($this->Tag, array('Article' => array('User' => array('Comment' => array(
			'Attachment' => array('conditions' => array('Attachment.id >' => 1))
		)))));
		$this->assertTrue(Set::matches('/Attachment', $r));
		$this->assertTrue(Set::matches('/Comment/keep/Attachment/conditions', $r));
		$this->assertEqual($r['Comment']['keep']['Attachment']['conditions'], array('Attachment.id >' => 1));
		$this->assertTrue(Set::matches('/User/keep/Comment', $r));
		$this->assertTrue(Set::matches('/Article/keep/User', $r));
		$this->assertTrue(Set::matches('/Tag/keep/Article', $r));
	}

/**
 * testInvalidContainments method
 *
 * @access public
 * @return void
 */
	function testInvalidContainments() {
		$this->expectError();
		$r = $this->__containments($this->Article, array('Comment', 'InvalidBinding'));

		$this->Article->Behaviors->attach('Containable', array('notices' => false));
		$r = $this->__containments($this->Article, array('Comment', 'InvalidBinding'));
	}

/**
 * testBeforeFind method
 *
 * @access public
 * @return void
 */
	function testBeforeFind() {
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
		$ids = $descIds = Set::extract('/Comment[1]/id', $r);
		rsort($descIds);
		$this->assertEqual($ids, $descIds);

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

		$this->expectError();
		$r = $this->Article->find('all', array('contain' => array('Comment' => 'NonExistingBinding')));
	}

/**
 * testContain method
 *
 * @access public
 * @return void
 */
	function testContain() {
		$this->Article->contain('Comment.User');
		$r = $this->Article->find('all');
		$this->assertTrue(Set::matches('/Comment/User', $r));
		$this->assertFalse(Set::matches('/Comment/Article', $r));

		$r = $this->Article->find('all');
		$this->assertFalse(Set::matches('/Comment/User', $r));
	}

/**
 * testFindEmbeddedNoBindings method
 *
 * @access public
 * @return void
 */
	function testFindEmbeddedNoBindings() {
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
		$this->assertEqual($result, $expected);
	}

/**
 * testFindFirstLevel method
 *
 * @access public
 * @return void
 */
	function testFindFirstLevel() {
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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);
	}

/**
 * testFindEmbeddedFirstLevel method
 *
 * @access public
 * @return void
 */
	function testFindEmbeddedFirstLevel() {
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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);
	}

/**
 * testFindSecondLevel method
 *
 * @access public
 * @return void
 */
	function testFindSecondLevel() {
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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);
	}

/**
 * testFindEmbeddedSecondLevel method
 *
 * @access public
 * @return void
 */
	function testFindEmbeddedSecondLevel() {
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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);
	}

/**
 * testFindThirdLevel method
 *
 * @access public
 * @return void
 */
	function testFindThirdLevel() {
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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);
	}

/**
 * testFindEmbeddedThirdLevel method
 *
 * @access public
 * @return void
 */
	function testFindEmbeddedThirdLevel() {
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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);
	}

/**
 * testSettingsThirdLevel method
 *
 * @access public
 * @return void
 */
	function testSettingsThirdLevel() {
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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);

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
			$this->assertEqual($result, $expected);
		}
	}

/**
 * testFindThirdLevelNonReset method
 *
 * @access public
 * @return void
 */
	function testFindThirdLevelNonReset() {
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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);
	}

/**
 * testFindEmbeddedThirdLevelNonReset method
 *
 * @access public
 * @return void
 */
	function testFindEmbeddedThirdLevelNonReset() {
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
		$this->assertEqual($result, $expected);

		$this->__assertBindings($this->User, array('hasMany' => array('ArticleFeatured')));
		$this->__assertBindings($this->User->ArticleFeatured, array('hasOne' => array('Featured')));
		$this->__assertBindings($this->User->ArticleFeatured->Featured, array('belongsTo' => array('Category')));

		$this->User->resetBindings();

		$this->__assertBindings($this->User, array('hasMany' => array('Article', 'ArticleFeatured', 'Comment')));
		$this->__assertBindings($this->User->ArticleFeatured, array('belongsTo' => array('User'), 'hasOne' => array('Featured'), 'hasMany' => array('Comment'), 'hasAndBelongsToMany' => array('Tag')));
		$this->__assertBindings($this->User->ArticleFeatured->Featured, array('belongsTo' => array('ArticleFeatured', 'Category')));
		$this->__assertBindings($this->User->ArticleFeatured->Comment, array('belongsTo' => array('Article', 'User'), 'hasOne' => array('Attachment')));

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
		$this->assertEqual($result, $expected);

		$this->__assertBindings($this->User, array('hasMany' => array('ArticleFeatured')));
		$this->__assertBindings($this->User->ArticleFeatured, array('hasOne' => array('Featured'), 'hasMany' => array('Comment')));
		$this->__assertBindings($this->User->ArticleFeatured->Featured, array('belongsTo' => array('Category')));
		$this->__assertBindings($this->User->ArticleFeatured->Comment, array('belongsTo' => array('Article'), 'hasOne' => array('Attachment')));

		$this->User->resetBindings();
		$this->__assertBindings($this->User, array('hasMany' => array('Article', 'ArticleFeatured', 'Comment')));
		$this->__assertBindings($this->User->ArticleFeatured, array('belongsTo' => array('User'), 'hasOne' => array('Featured'), 'hasMany' => array('Comment'), 'hasAndBelongsToMany' => array('Tag')));
		$this->__assertBindings($this->User->ArticleFeatured->Featured, array('belongsTo' => array('ArticleFeatured', 'Category')));
		$this->__assertBindings($this->User->ArticleFeatured->Comment, array('belongsTo' => array('Article', 'User'), 'hasOne' => array('Attachment')));

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
		$this->assertEqual($result, $expected);

		$this->__assertBindings($this->User, array('hasMany' => array('ArticleFeatured')));
		$this->__assertBindings($this->User->ArticleFeatured, array('hasOne' => array('Featured'), 'hasMany' => array('Comment')));
		$this->__assertBindings($this->User->ArticleFeatured->Featured, array('belongsTo' => array('Category')));
		$this->__assertBindings($this->User->ArticleFeatured->Comment, array('belongsTo' => array('Article'), 'hasOne' => array('Attachment')));

		$this->User->resetBindings();
		$this->__assertBindings($this->User, array('hasMany' => array('Article', 'ArticleFeatured', 'Comment')));
		$this->__assertBindings($this->User->ArticleFeatured, array('belongsTo' => array('User'), 'hasOne' => array('Featured'), 'hasMany' => array('Comment'), 'hasAndBelongsToMany' => array('Tag')));
		$this->__assertBindings($this->User->ArticleFeatured->Featured, array('belongsTo' => array('ArticleFeatured', 'Category')));
		$this->__assertBindings($this->User->ArticleFeatured->Comment, array('belongsTo' => array('Article', 'User'), 'hasOne' => array('Attachment')));

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
		$this->assertEqual($result, $expected);

		$this->__assertBindings($this->User, array('hasMany' => array('Article', 'ArticleFeatured')));
		$this->__assertBindings($this->User->Article);
		$this->__assertBindings($this->User->ArticleFeatured, array('hasOne' => array('Featured'), 'hasMany' => array('Comment')));
		$this->__assertBindings($this->User->ArticleFeatured->Featured, array('belongsTo' => array('Category')));
		$this->__assertBindings($this->User->ArticleFeatured->Comment, array('hasOne' => array('Attachment')));

		$this->User->resetBindings();
		$this->__assertBindings($this->User, array('hasMany' => array('Article', 'ArticleFeatured', 'Comment')));
		$this->__assertBindings($this->User->Article, array('belongsTo' => array('User'), 'hasMany' => array('Comment'), 'hasAndBelongsToMany' => array('Tag')));
		$this->__assertBindings($this->User->ArticleFeatured, array('belongsTo' => array('User'), 'hasOne' => array('Featured'), 'hasMany' => array('Comment'), 'hasAndBelongsToMany' => array('Tag')));
		$this->__assertBindings($this->User->ArticleFeatured->Featured, array('belongsTo' => array('ArticleFeatured', 'Category')));
		$this->__assertBindings($this->User->ArticleFeatured->Comment, array('belongsTo' => array('Article', 'User'), 'hasOne' => array('Attachment')));
	}

/**
 * testEmbeddedFindFields method
 *
 * @access public
 * @return void
 */
	function testEmbeddedFindFields() {
		$result = $this->Article->find('all', array(
			'contain' => array('User(user)'),
			'fields' => array('title')
		));
		$expected = array(
			array('Article' => array('title' => 'First Article'), 'User' => array('user' => 'mariano', 'id' => 1)),
			array('Article' => array('title' => 'Second Article'), 'User' => array('user' => 'larry', 'id' => 3)),
			array('Article' => array('title' => 'Third Article'), 'User' => array('user' => 'mariano', 'id' => 1)),
		);
		$this->assertEqual($result, $expected);

		$result = $this->Article->find('all', array(
			'contain' => array('User(id, user)'),
			'fields' => array('title')
		));
		$expected = array(
			array('Article' => array('title' => 'First Article'), 'User' => array('user' => 'mariano', 'id' => 1)),
			array('Article' => array('title' => 'Second Article'), 'User' => array('user' => 'larry', 'id' => 3)),
			array('Article' => array('title' => 'Third Article'), 'User' => array('user' => 'mariano', 'id' => 1)),
		);
		$this->assertEqual($result, $expected);

		$result = $this->Article->find('all', array(
			'contain' => array(
				'Comment(comment, published)' => 'Attachment(attachment)', 'User(user)'
			),
			'fields' => array('title')
		));
		if (!empty($result)) {
			foreach($result as $i=>$article) {
				foreach($article['Comment'] as $j=>$comment) {
					$result[$i]['Comment'][$j] = array_diff_key($comment, array('id'=>true));
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
		$this->assertEqual($result, $expected);
	}

/**
 * test that hasOne and belongsTo fields act the same in a contain array.
 *
 * @return void
 */
	function testHasOneFieldsInContain() {
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
			)
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
 * @access public
 * @return void
 */
	function testFindConditionalBinding() {
		$this->Article->contain(array(
			'User(user)',
			'Tag' => array(
				'fields' => array('tag', 'created'),
				'conditions' => array('created >=' => '2007-03-18 12:24')
			)
		));
		$result = $this->Article->find('all', array('fields' => array('title')));
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
		$this->assertEqual($result, $expected);

		$this->Article->contain(array('User(id,user)', 'Tag' => array('fields' => array('tag', 'created'))));
		$result = $this->Article->find('all', array('fields' => array('title')));
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
		$this->assertEqual($result, $expected);

		$result = $this->Article->find('all', array(
			'fields' => array('title'),
			'contain' => array('User(id,user)', 'Tag' => array('fields' => array('tag', 'created')))
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
		$this->assertEqual($result, $expected);

		$this->Article->contain(array(
			'User(id,user)',
			'Tag' => array(
				'fields' => array('tag', 'created'),
				'conditions' => array('created >=' => '2007-03-18 12:24')
			)
		));
		$result = $this->Article->find('all', array('fields' => array('title')));
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
		$this->assertEqual($result, $expected);

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
 * @access public
 * @return void
 */
	function testOtherFinds() {
		$result = $this->Article->find('count');
		$expected = 3;
		$this->assertEqual($result, $expected);

		$result = $this->Article->find('count', array('conditions' => array('Article.id >' => '1')));
		$expected = 2;
		$this->assertEqual($result, $expected);

		$result = $this->Article->find('count', array('contain' => array()));
		$expected = 3;
		$this->assertEqual($result, $expected);

		$this->Article->contain(array('User(id,user)', 'Tag' => array('fields' => array('tag', 'created'), 'conditions' => array('created >=' => '2007-03-18 12:24'))));
		$result = $this->Article->find('first', array('fields' => array('title')));
		$expected = array(
			'Article' => array('id' => 1, 'title' => 'First Article'),
			'User' => array('id' => 1, 'user' => 'mariano'),
			'Tag' => array(array('tag' => 'tag2', 'created' => '2007-03-18 12:24:23'))
		);
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);

		$result = $this->Article->find('list', array(
			'contain' => array('User(id,user)'),
			'fields' => array('Article.id', 'Article.title')
		));
		$expected = array(
			1 => 'First Article',
			2 => 'Second Article',
			3 => 'Third Article'
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testPaginate method
 *
 * @access public
 * @return void
 */
	function testPaginate() {
		$Controller =& new Controller();
		$Controller->uses = array('Article');
		$Controller->passedArgs[] = '1';
		$Controller->params['url'] = array();
		$Controller->constructClasses();

		$Controller->paginate = array('Article' => array('fields' => array('title'), 'contain' => array('User(user)')));
		$result = $Controller->paginate('Article');
		$expected = array(
			array('Article' => array('title' => 'First Article'), 'User' => array('user' => 'mariano', 'id' => 1)),
			array('Article' => array('title' => 'Second Article'), 'User' => array('user' => 'larry', 'id' => 3)),
			array('Article' => array('title' => 'Third Article'), 'User' => array('user' => 'mariano', 'id' => 1)),
		);
		$this->assertEqual($result, $expected);

		$r = $Controller->Article->find('all');
		$this->assertTrue(Set::matches('/Article[id=1]', $r));
		$this->assertTrue(Set::matches('/User[id=1]', $r));
		$this->assertTrue(Set::matches('/Tag[id=1]', $r));

		$Controller->paginate = array('Article' => array('contain' => array('Comment(comment)' => 'User(user)'), 'fields' => array('title')));
		$result = $Controller->paginate('Article');
		$expected = array(
			array(
				'Article' => array('title' => 'First Article', 'id' => 1),
				'Comment' => array(
					array(
						'comment' => 'First Comment for First Article',
						'user_id' => 2,
						'article_id' => 1,
						'User' => array('user' => 'nate')
					),
					array(
						'comment' => 'Second Comment for First Article',
						'user_id' => 4,
						'article_id' => 1,
						'User' => array('user' => 'garrett')
					),
					array(
						'comment' => 'Third Comment for First Article',
						'user_id' => 1,
						'article_id' => 1,
						'User' => array('user' => 'mariano')
					),
					array(
						'comment' => 'Fourth Comment for First Article',
						'user_id' => 1,
						'article_id' => 1,
						'User' => array('user' => 'mariano')
					)
				)
			),
			array(
				'Article' => array('title' => 'Second Article', 'id' => 2),
				'Comment' => array(
					array(
						'comment' => 'First Comment for Second Article',
						'user_id' => 1,
						'article_id' => 2,
						'User' => array('user' => 'mariano')
					),
					array(
						'comment' => 'Second Comment for Second Article',
						'user_id' => 2,
						'article_id' => 2,
						'User' => array('user' => 'nate')
					)
				)
			),
			array(
				'Article' => array('title' => 'Third Article', 'id' => 3),
				'Comment' => array()
			),
		);
		$this->assertEqual($result, $expected);

		$r = $Controller->Article->find('all');
		$this->assertTrue(Set::matches('/Article[id=1]', $r));
		$this->assertTrue(Set::matches('/User[id=1]', $r));
		$this->assertTrue(Set::matches('/Tag[id=1]', $r));

		$Controller->Article->unbindModel(array('hasMany' => array('Comment'), 'belongsTo' => array('User'), 'hasAndBelongsToMany' => array('Tag')), false);
		$Controller->Article->bindModel(array('hasMany' => array('Comment'), 'belongsTo' => array('User')), false);

		$Controller->paginate = array('Article' => array('contain' => array('Comment(comment)', 'User(user)'), 'fields' => array('title')));
		$r = $Controller->paginate('Article');
		$this->assertTrue(Set::matches('/Article[id=1]', $r));
		$this->assertTrue(Set::matches('/User[id=1]', $r));
		$this->assertTrue(Set::matches('/Comment[article_id=1]', $r));
		$this->assertFalse(Set::matches('/Comment[id=1]', $r));

		$r = $this->Article->find('all');
		$this->assertTrue(Set::matches('/Article[id=1]', $r));
		$this->assertTrue(Set::matches('/User[id=1]', $r));
		$this->assertTrue(Set::matches('/Comment[article_id=1]', $r));
		$this->assertTrue(Set::matches('/Comment[id=1]', $r));
	}

/**
 * testOriginalAssociations method
 *
 * @access public
 * @return void
 */
	function testOriginalAssociations() {
		$this->Article->Comment->Behaviors->attach('Containable');

		$options = array(
			'conditions' => array(
				'Comment.published' => 'Y',
			),
			'contain' => 'User',
			'recursive' => 1
		);

		$firstResult = $this->Article->Comment->find('all', $options);

		$dummyResult = $this->Article->Comment->find('all', array(
			'conditions' => array(
				'User.user' => 'mariano'
			),
			'fields' => array('User.password'),
			'contain' => array('User.password'),
		));

		$result = $this->Article->Comment->find('all', $options);
		$this->assertEqual($result, $firstResult);

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
		$this->assertEqual($result, $expected);

		$result = $this->Article->find('all', array('fields' => array('title', 'User.id', 'User.user'), 'limit' => 1, 'page' => 2, 'order' => 'Article.id ASC'));
		$expected = array(array(
			'Article' => array('id' => 2, 'title' => 'Second Article'),
			'User' => array('id' => 3, 'user' => 'larry'),
			'Comment' => array(
				array('comment' => 'First Comment for Second Article', 'article_id' => 2),
				array('comment' => 'Second Comment for Second Article', 'article_id' => 2)
			)
		));
		$this->assertEqual($result, $expected);

		$result = $this->Article->find('all', array('fields' => array('title', 'User.id', 'User.user'), 'limit' => 1, 'page' => 3, 'order' => 'Article.id ASC'));
		$expected = array(array(
			'Article' => array('id' => 3, 'title' => 'Third Article'),
			'User' => array('id' => 1, 'user' => 'mariano'),
			'Comment' => array()
		));
		$this->assertEqual($result, $expected);

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
 * @access public
 */
	function testResetAddedAssociation() {
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
		$this->assertEqual('First Article', $result['Article']['title']);
		$this->assertTrue(!empty($result['ArticlesTag']));
		$this->assertEqual($expected, array_keys($result));

		$this->assertTrue(empty($this->Article->hasMany['ArticlesTag']));
		
		$this->JoinA =& ClassRegistry::init('JoinA');
		$this->JoinB =& ClassRegistry::init('JoinB');
		$this->JoinC =& ClassRegistry::init('JoinC');
		
		$this->JoinA->Behaviors->attach('Containable');
		$this->JoinB->Behaviors->attach('Containable');
		$this->JoinC->Behaviors->attach('Containable');
		
		$this->JoinA->JoinB->find('all', array('contain' => array('JoinA')));
		$this->JoinA->bindModel(array('hasOne' => array('JoinAsJoinC' => array('joinTable' => 'as_cs'))), false);
		$result = $this->JoinA->hasOne;
		$this->JoinA->find('all');
		$resultAfter = $this->JoinA->hasOne;
		$this->assertEqual($result, $resultAfter);
	}

/**
 * testResetAssociation method
 *
 * @access public
 */
	function testResetAssociation() {
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
		$this->assertEqual($result, $initialModels);
	}

/**
 * testResetDeeperHasOneAssociations method
 *
 * @access public
 */
	function testResetDeeperHasOneAssociations() {
		$this->Article->User->unbindModel(array(
			'hasMany' => array('ArticleFeatured', 'Comment')
		), false);
		$userHasOne = array('hasOne' => array('ArticleFeatured', 'Comment'));

		$this->Article->User->bindModel($userHasOne, false);
		$expected = $this->Article->User->hasOne;
		$this->Article->find('all');
		$this->assertEqual($expected, $this->Article->User->hasOne);

		$this->Article->User->bindModel($userHasOne, false);
		$expected = $this->Article->User->hasOne;
		$this->Article->find('all', array(
			'contain' => array(
				'User' => array('ArticleFeatured', 'Comment')
			)
		));
		$this->assertEqual($expected, $this->Article->User->hasOne);

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
		$this->assertEqual($expected, $this->Article->User->hasOne);

		$this->Article->User->bindModel($userHasOne, false);
		$expected = $this->Article->User->hasOne;
		$this->Article->find('all', array(
			'contain' => array(
				'User' => array(
					'Comment' => array('fields' => array('created'))
				)
			)
		));
		$this->assertEqual($expected, $this->Article->User->hasOne);

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
		$this->assertEqual($expected, $this->Article->User->hasOne);
	}

/**
 * testResetMultipleHabtmAssociations method
 *
 * @access public
 */
	function testResetMultipleHabtmAssociations() {
		$articleHabtm = array(
			'hasAndBelongsToMany' => array(
				'Tag' => array(
					'className'				=> 'Tag',
					'joinTable'				=> 'articles_tags',
					'foreignKey'			=> 'article_id',
					'associationForeignKey' => 'tag_id'
				),
				'ShortTag' => array(
					'className'				=> 'Tag',
					'joinTable'				=> 'articles_tags',
					'foreignKey'			=> 'article_id',
					'associationForeignKey' => 'tag_id',
					// LENGHT function mysql-only, using LIKE does almost the same
					'conditions' 			=> 'ShortTag.tag LIKE "???"'
				)
			)
		);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all');
		$this->assertEqual($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => 'Tag.tag'));
		$this->assertEqual($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => 'Tag'));
		$this->assertEqual($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => array('Tag' => array('fields' => array(null)))));
		$this->assertEqual($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => array('Tag' => array('fields' => array('Tag.tag')))));
		$this->assertEqual($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => array('Tag' => array('fields' => array('Tag.tag', 'Tag.created')))));
		$this->assertEqual($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => 'ShortTag.tag'));
		$this->assertEqual($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => 'ShortTag'));
		$this->assertEqual($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => array('ShortTag' => array('fields' => array(null)))));
		$this->assertEqual($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => array('ShortTag' => array('fields' => array('ShortTag.tag')))));
		$this->assertEqual($expected, $this->Article->hasAndBelongsToMany);

		$this->Article->resetBindings();
		$this->Article->bindModel($articleHabtm, false);
		$expected = $this->Article->hasAndBelongsToMany;
		$this->Article->find('all', array('contain' => array('ShortTag' => array('fields' => array('ShortTag.tag', 'ShortTag.created')))));
		$this->assertEqual($expected, $this->Article->hasAndBelongsToMany);
	}

/**
 * test that bindModel and unbindModel work with find() calls in between.
 */
	function testBindMultipleTimesWithFind() {
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
		$this->assertEqual('hasAndBelongsToMany', $associated['Tag']);
		$this->assertFalse(isset($associated['ArticleTag']));
	}

/**
 * test that autoFields doesn't splice in fields from other databases.
 *
 * @return void
 */
	function testAutoFieldsWithMultipleDatabases() {
		$config = new DATABASE_CONFIG();

		$skip = $this->skipIf(
			!isset($config->test) || !isset($config->test2),
			 '%s Primary and secondary test databases not configured, skipping cross-database '
			.'join tests.'
			.' To run these tests, you must define $test and $test2 in your database configuration.'
		);
		if ($skip) {
			return;
		}

		$db =& ConnectionManager::getDataSource('test2');
		$this->_fixtures[$this->_fixtureClassMap['User']]->create($db);
		$this->_fixtures[$this->_fixtureClassMap['User']]->insert($db);

		$this->Article->User->setDataSource('test2');

		$result = $this->Article->find('all', array(
			'fields' => array('Article.title'),
			'contain' => array('User')
		));
		$this->assertTrue(isset($result[0]['Article']));
		$this->assertTrue(isset($result[0]['User']));

		$this->_fixtures[$this->_fixtureClassMap['User']]->drop($db);
	}
/**
 * test that autoFields doesn't splice in columns that aren't part of the join.
 *
 * @return void
 */
	function testAutoFieldsWithRecursiveNegativeOne() {
		$this->Article->recursive = -1;
		$result = $this->Article->field('title', array('Article.title' => 'First Article'));
		$this->assertNoErrors();
		$this->assertEqual($result, 'First Article', 'Field is wrong');
	}

/**
 * test that find(all) doesn't return incorrect values when mixed with containable.
 *
 * @return void
 */
	function testFindAllReturn() {
		$result = $this->Article->find('all', array(
			'conditions' => array('Article.id' => 999999999)
		));
		$this->assertEqual($result, array(), 'Should be empty.');
	}

/**
 * containments method
 *
 * @param mixed $Model
 * @param array $contain
 * @access private
 * @return void
 */
	function __containments(&$Model, $contain = array()) {
		if (!is_array($Model)) {
			$result = $Model->containments($contain);
			return $this->__containments($result['models']);
		} else {
			$result = $Model;
			foreach($result as $i => $containment) {
				$result[$i] = array_diff_key($containment, array('instance' => true));
			}
		}

		return $result;
	}

/**
 * assertBindings method
 *
 * @param mixed $Model
 * @param array $expected
 * @access private
 * @return void
 */
	function __assertBindings(&$Model, $expected = array()) {
		$expected = array_merge(array('belongsTo' => array(), 'hasOne' => array(), 'hasMany' => array(), 'hasAndBelongsToMany' => array()), $expected);

		foreach($expected as $binding => $expect) {
			$this->assertEqual(array_keys($Model->$binding), $expect);
		}
	}

/**
 * bindings method
 *
 * @param mixed $Model
 * @param array $extra
 * @param bool $output
 * @access private
 * @return void
 */
	function __bindings(&$Model, $extra = array(), $output = true) {
		$relationTypes = array('belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany');

		$debug = '[';
		$lines = array();
		foreach($relationTypes as $binding) {
			if (!empty($Model->$binding)) {
				$models = array_keys($Model->$binding);
				foreach($models as $linkedModel) {
					$line = $linkedModel;
					if (!empty($extra) && !empty($Model->{$binding}[$linkedModel])) {
						$extraData = array();
						foreach(array_intersect_key($Model->{$binding}[$linkedModel], array_flip($extra)) as $key => $value) {
							$extraData[] = $key . ': ' . (is_array($value) ? '(' . implode(', ', $value) . ')' : $value);
						}
						$line .= ' {' . implode(' - ', $extraData) . '}';
					}
					$lines[] = $line;
				}
			}
		}
		$debug .= implode(' | ' , $lines);
		$debug .=  ']';
		$debug = '<strong>' . $Model->alias . '</strong>: ' . $debug . '<br />';

		if ($output) {
			echo $debug;
		}

		return $debug;
	}
}
