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
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs.model
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
	if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
		define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
	}
	require_once LIBS.'model'.DS.'model.php';
	require_once LIBS.'model'.DS.'datasources'.DS.'datasource.php';
	require_once LIBS.'model'.DS.'datasources'.DS.'dbo_source.php';
	require_once LIBS.'model'.DS.'datasources'.DS.'dbo'.DS.'dbo_mysql.php';

	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.model
	 */
	class Test extends Model {
		var $useTable = false;
		var $name = 'Test';

		function loadInfo() {
			return new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => '', 'default' => '1', 'length' => '8'),
				array('name' => 'name', 'type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				array('name' => 'email', 'type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
				array('name' => 'notes', 'type' => 'text', 'null' => '1', 'default' => 'write some notes here', 'length' => ''),
				array('name' => 'created', 'type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				array('name' => 'updated', 'type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			));
		}
	}

	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.model
	 */
	class User extends CakeTestModel {
		var $name = 'User';
		var $validate = array(
			'user' => VALID_NOT_EMPTY,
			'password' => VALID_NOT_EMPTY
		);
	}
	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.model
	 */
	class Article extends CakeTestModel {
		var $name = 'Article';
		var $belongsTo = array('User');
		var $hasMany = array(
			'Comment' => array('className'=>'Comment', 'dependent' => true)
		);
		var $hasAndBelongsToMany = array('Tag');
		var $validate = array(
			'user_id' => VALID_NUMBER,
			'title' => VALID_NOT_EMPTY,
			'body' => VALID_NOT_EMPTY
		);
	}
	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.model
	 */
	class ArticleFeatured extends CakeTestModel {
		var $name = 'ArticleFeatured';
		var $belongsTo = array('User', 'Category');
		var $hasOne = array('Featured');
		var $hasMany = array(
			'Comment' => array('className'=>'Comment', 'dependent' => true)
		);
		var $hasAndBelongsToMany = array('Tag');
		var $validate = array(
			'user_id' => VALID_NUMBER,
			'title' => VALID_NOT_EMPTY,
			'body' => VALID_NOT_EMPTY
		);
	}
	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.model
	 */
	class Featured extends CakeTestModel {
		var $name = 'Featured';
		var $belongsTo = array(
			'ArticleFeatured'=> array('className' => 'ArticleFeatured'),
			'Category'=> array('className' => 'Category')
		);
	}

	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.model
	 */
	class Tag extends CakeTestModel {
		var $name = 'Tag';
	}
	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.model
	 */
	class Comment extends CakeTestModel {
		var $name = 'Comment';
		var $belongsTo = array('Article', 'User');
		var $hasOne = array(
			'Attachment' => array('className'=>'Attachment', 'dependent' => true)
		);
	}
	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.model
	 */
	class Attachment extends CakeTestModel {
		var $name = 'Attachment';
	}
	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.model
	 */
	class Category extends CakeTestModel {
		var $name = 'Category';
	}
	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.model
	 */
	class CategoryThread extends CakeTestModel {
		var $name = 'CategoryThread';
		var $belongsTo = array(
			'ParentCategory' => array(
				'className' => 'CategoryThread',
				'foreignKey' => 'parent_id'
			)
		);
	}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class ModelTest extends CakeTestCase {
	var $fixtures = array( 'core.category', 'core.category_thread', 'core.user', 'core.article', 'core.featured', 'core.article_featured', 'core.tag', 'core.articles_tag', 'core.comment', 'core.attachment' );

	function start() {
		parent::start();
		Configure::write('debug', 2);
	}

	function end() {
		parent::end();
		Configure::write('debug', DEBUG);
	}

	function testIdentity() {
		$this->model =& new Test();
		$result = $this->model->name;
		$expected = 'Test';
		$this->assertEqual($result, $expected);
	}

	function testCreation() {
		$this->model =& new Test();
		$result = $this->model->create();
		$expected = array('Test' => array('notes' => 'write some notes here'));
		$this->assertEqual($result, $expected);

		$this->model =& new User();
		$result = $this->model->_tableInfo->value;
		$expected = array (
			array('name' => 'id', 		'type' => 'integer',	'null' => false, 'default' => null,	'length' => 11),
			array('name' => 'user', 	'type' => 'string',		'null' => false, 'default' => '',	'length' => 255),
			array('name' => 'password',	'type' => 'string',		'null' => false, 'default' => '',	'length' => 255),
			array('name' => 'created',	'type' => 'datetime',	'null' => true, 'default' => null,	'length' => null),
			array('name' => 'updated',	'type' => 'datetime',	'null' => true, 'default' => null,	'length' => null)
		);
		$this->assertEqual($result, $expected);

		$this->model =& new Article();
		$result = $this->model->create();
		$expected = array ('Article' => array('published' => 'N'));
		$this->assertEqual($result, $expected);
	}

	function testFindAllFakeThread() {
		$this->model =& new CategoryThread();

		$this->db->fullDebug = true;
		$this->model->recursive = 6;
		$this->model->id = 7;
		$result = $this->model->read();
		$expected = array('CategoryThread' => array('id' => 7, 'parent_id' => 6, 'name' => 'Category 2.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
		'ParentCategory' => array('id' => 6, 'parent_id' => 5, 'name' => 'Category 2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
		'ParentCategory' => array('id' => 5, 'parent_id' => 4, 'name' => 'Category 1.1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
		'ParentCategory' => array('id' => 4, 'parent_id' => 3, 'name' => 'Category 1.1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
		'ParentCategory' => array('id' => 3, 'parent_id' => 2, 'name' => 'Category 1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
		'ParentCategory' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
		'ParentCategory' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31')))))));
		$this->assertEqual($result, $expected);
	}

	function testFindAll() {
		$this->model =& new User();

		$result = $this->model->findAll();
		$expected = array(
			array ( 'User' => array ( 'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31')),
			array ( 'User' => array ( 'id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31')),
			array ( 'User' => array ( 'id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31')),
			array ( 'User' => array ( 'id' => '4', 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll('User.id > 2');
		$expected = array(
			array ( 'User' => array ( 'id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31')),
			array ( 'User' => array ( 'id' => '4', 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(array('User.id' => '!= 0', 'User.user' => 'LIKE %arr%'));
		$expected = array(
			array ( 'User' => array ( 'id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31')),
			array ( 'User' => array ( 'id' => '4', 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(array('User.id' => '0'));
		$expected = array();
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(array('or' => array('User.id' => '0', 'User.user' => 'LIKE %a%')));
		$expected = array(
			array ( 'User' => array ( 'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31')),
			array ( 'User' => array ( 'id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31')),
			array ( 'User' => array ( 'id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31')),
			array ( 'User' => array ( 'id' => '4', 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, 'User.id, User.user');
		$expected = array(
			array ( 'User' => array ( 'id' => '1', 'user' => 'mariano')),
			array ( 'User' => array ( 'id' => '2', 'user' => 'nate')),
			array ( 'User' => array ( 'id' => '3', 'user' => 'larry')),
			array ( 'User' => array ( 'id' => '4', 'user' => 'garrett'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, 'User.user', 'User.user ASC');
		$expected = array(
			array ( 'User' => array ( 'user' => 'garrett')),
			array ( 'User' => array ( 'user' => 'larry')),
			array ( 'User' => array ( 'user' => 'mariano')),
			array ( 'User' => array ( 'user' => 'nate'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, 'User.user', 'User.user ASC');
		$expected = array(
			array ( 'User' => array ( 'user' => 'garrett')),
			array ( 'User' => array ( 'user' => 'larry')),
			array ( 'User' => array ( 'user' => 'mariano')),
			array ( 'User' => array ( 'user' => 'nate'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, 'User.user', 'User.user DESC');
		$expected = array(
			array ( 'User' => array ( 'user' => 'nate')),
			array ( 'User' => array ( 'user' => 'mariano')),
			array ( 'User' => array ( 'user' => 'larry')),
			array ( 'User' => array ( 'user' => 'garrett'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, null, null, 3, 1);
		$expected = array(
			array ( 'User' => array ( 'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31')),
			array ( 'User' => array ( 'id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31')),
			array ( 'User' => array ( 'id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, null, null, 3, 2);
		$expected = array(
			array ( 'User' => array ( 'id' => '4', 'user' => 'garrett', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:22:23', 'updated' => '2007-03-17 01:24:31'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, null, null, 3, 3);
		$expected = array();
		$this->assertEqual($result, $expected);
	}

	function testFindField() {
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

	function testBindUnbind() {
		$this->model =& new User();

		$result = $this->model->hasMany;
		$expected = array();
		$this->assertEqual($result, $expected);

		$result = $this->model->bindModel(array('hasMany' => array('Comment')));
		$this->assertTrue($result);

		$result = $this->model->findAll(null, 'User.id, User.user');
		$expected = array(
			array ( 'User' => array ( 'id' => '1', 'user' => 'mariano'), 'Comment' => array(
				array( 'id' => '3', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Third Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'),
				array( 'id' => '4', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Fourth Comment for First Article', 'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'),
				array( 'id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31')
			)),
			array ( 'User' => array ( 'id' => '2', 'user' => 'nate'), 'Comment' => array(
				array( 'id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
				array( 'id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31')
			)),
			array ( 'User' => array ( 'id' => '3', 'user' => 'larry'), 'Comment' => array()),
			array ( 'User' => array ( 'id' => '4', 'user' => 'garrett'), 'Comment' => array(
				array( 'id' => '2', 'article_id' => '1', 'user_id' => '4', 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31')
			))
		);
		$this->assertEqual($result, $expected);

		$this->model->__resetAssociations();
		$result = $this->model->hasMany;
		$this->assertEqual($result, array());

		$result = $this->model->bindModel(array('hasMany' => array('Comment')), false);
		$this->assertTrue($result);

		$result = $this->model->findAll(null, 'User.id, User.user');
		$expected = array(
			array ( 'User' => array ( 'id' => '1', 'user' => 'mariano'), 'Comment' => array(
				array( 'id' => '3', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Third Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'),
				array( 'id' => '4', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Fourth Comment for First Article', 'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'),
				array( 'id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31')
			)),
			array ( 'User' => array ( 'id' => '2', 'user' => 'nate'), 'Comment' => array(
				array( 'id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
				array( 'id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31')
			)),
			array ( 'User' => array ( 'id' => '3', 'user' => 'larry'), 'Comment' => array()),
			array ( 'User' => array ( 'id' => '4', 'user' => 'garrett'), 'Comment' => array(
				array( 'id' => '2', 'article_id' => '1', 'user_id' => '4', 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31')
			))
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->hasMany;
		$expected = array( 'Comment' => array('className' => 'Comment', 'foreignKey' => 'user_id', 'conditions' => null, 'fields' => null, 'order' => null, 'limit' => null, 'offset' => null, 'dependent' => null, 'exclusive' => null, 'finderQuery' => null, 'counterQuery' => null) );
		$this->assertEqual($result, $expected);

		$result = $this->model->unbindModel(array('hasMany' => array('Comment')));
		$this->assertTrue($result);

		$result = $this->model->hasMany;
		$expected = array();
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, 'User.id, User.user');
		$expected = array(
			array ( 'User' => array ( 'id' => '1', 'user' => 'mariano')),
			array ( 'User' => array ( 'id' => '2', 'user' => 'nate')),
			array ( 'User' => array ( 'id' => '3', 'user' => 'larry')),
			array ( 'User' => array ( 'id' => '4', 'user' => 'garrett'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(null, 'User.id, User.user');
		$expected = array(
			array ( 'User' => array ( 'id' => '1', 'user' => 'mariano'), 'Comment' => array(
				array( 'id' => '3', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Third Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'),
				array( 'id' => '4', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Fourth Comment for First Article', 'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'),
				array( 'id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31')
			)),
			array ( 'User' => array ( 'id' => '2', 'user' => 'nate'), 'Comment' => array(
				array( 'id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
				array( 'id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31')
			)),
			array ( 'User' => array ( 'id' => '3', 'user' => 'larry'), 'Comment' => array()),
			array ( 'User' => array ( 'id' => '4', 'user' => 'garrett'), 'Comment' => array(
				array( 'id' => '2', 'article_id' => '1', 'user_id' => '4', 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31')
			))
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->unbindModel(array('hasMany' => array('Comment')), false);
		$this->assertTrue($result);

		$result = $this->model->findAll(null, 'User.id, User.user');
		$expected = array(
			array ( 'User' => array ( 'id' => '1', 'user' => 'mariano')),
			array ( 'User' => array ( 'id' => '2', 'user' => 'nate')),
			array ( 'User' => array ( 'id' => '3', 'user' => 'larry')),
			array ( 'User' => array ( 'id' => '4', 'user' => 'garrett'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->hasMany;
		$expected = array();
		$this->assertEqual($result, $expected);

		$result = $this->model->bindModel(array('hasMany' => array('Comment' => array('className' => 'Comment', 'conditions' => 'Comment.published = \'Y\'') )));
		$this->assertTrue($result);

		$result = $this->model->findAll(null, 'User.id, User.user');
		$expected = array(
			array ( 'User' => array ( 'id' => '1', 'user' => 'mariano'), 'Comment' => array(
				array( 'id' => '3', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Third Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'),
				array( 'id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31')
			)),
			array ( 'User' => array ( 'id' => '2', 'user' => 'nate'), 'Comment' => array(
				array( 'id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
				array( 'id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31')
			)),
			array ( 'User' => array ( 'id' => '3', 'user' => 'larry'), 'Comment' => array()),
			array ( 'User' => array ( 'id' => '4', 'user' => 'garrett'), 'Comment' => array(
				array( 'id' => '2', 'article_id' => '1', 'user_id' => '4', 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31')
			))
		);
		$this->assertEqual($result, $expected);
	}

	function testFindCount() {
		$this->model =& new User();
		$result = $this->model->findCount();
		$this->assertEqual($result, 4);

		$this->db->fullDebug = true;
		$this->model->order = 'User.id';
		$result = $this->model->findCount();
		$this->assertEqual($result, 4);

		$this->assertTrue(isset($this->db->_queriesLog[0]['query']));
		$this->assertNoPattern('/ORDER\s+BY/', $this->db->_queriesLog[0]['query']);

		$this->db->_queriesLog = array();
		$this->db->fullDebug = false;
	}

	function testFindMagic() {
		$this->model =& new User();

		$result = $this->model->findByUser('mariano');
		$expected = array ( 'User' => array (
			'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
		));
		$this->assertEqual($result, $expected);

		$result = $this->model->findByPassword('5f4dcc3b5aa765d61d8327deb882cf99');
		$expected = array ( 'User' => array (
			'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
		));
		$this->assertEqual($result, $expected);
	}

	function testRead() {
		$this->model =& new User();

		$result = $this->model->read();
		$this->assertFalse($result);

		$this->model->id = 2;
		$result = $this->model->read();
		$expected = array('User' => array ( 'id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'));
		$this->assertEqual($result, $expected);

		$result = $this->model->read(null, 2);
		$expected = array('User' => array ( 'id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'));
		$this->assertEqual($result, $expected);

		$this->model->id = 2;
		$result = $this->model->read(array('id', 'user'));
		$expected = array('User' => array ( 'id' => '2', 'user' => 'nate'));
		$this->assertEqual($result, $expected);

		$result = $this->model->read('id, user', 2);
		$expected = array('User' => array ( 'id' => '2', 'user' => 'nate'));
		$this->assertEqual($result, $expected);

		$result = $this->model->bindModel(array('hasMany' => array('Article')));
		$this->assertTrue($result);

		$this->model->id = 1;
		$result = $this->model->read('id, user');
		$expected = array(
			'User' => array ( 'id' => '1', 'user' => 'mariano'),
			'Article' => array(
				array( 'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31' ),
				array( 'id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31' )
			)
		);
		$this->assertEqual($result, $expected);
	}

	function testRecursiveRead() {
		$this->model =& new User();

		$result = $this->model->bindModel(array('hasMany' => array('Article')), false);
		$this->assertTrue($result);

		$this->model->recursive = 0;
		$result = $this->model->read('id, user', 1);
		$expected = array(
			'User' => array ( 'id' => '1', 'user' => 'mariano'),
		);
		$this->assertEqual($result, $expected);

		$this->model->recursive = 1;
		$result = $this->model->read('id, user', 1);
		$expected = array(
			'User' => array ( 'id' => '1', 'user' => 'mariano'),
			'Article' => array(
				array( 'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31' ),
				array( 'id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31' )
			)
		);
		$this->assertEqual($result, $expected);

		$this->model->recursive = 2;
		$result = $this->model->read('id, user', 3);
		$expected = array(
			'User' => array ( 'id' => '3', 'user' => 'larry'),
			'Article' => array(
				array(
					'id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
					'User' => array (
						'id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
					),
					'Comment' => array(
						array( 'id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'),
						array( 'id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31')
					),
					'Tag' => array(
						array( 'id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
						array( 'id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31'),
					)
				)
			)
		);
		$this->assertEqual($result, $expected);
	}

	function testRecursiveFindAll() {
		$this->model =& new Article();

		$result = $this->model->findAll(array('Article.user_id' => 1));
		$expected = array (
			array (
				'Article' => array (
					'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
				),
				'User' => array (
					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array (
					array ( 'id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
					array ( 'id' => '2', 'article_id' => '1', 'user_id' => '4', 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'),
					array ( 'id' => '3', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Third Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'),
					array ( 'id' => '4', 'article_id' => '1', 'user_id' => '1', 'comment' => 'Fourth Comment for First Article', 'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31')
				),
				'Tag' => array (
					array ( 'id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
					array ( 'id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31')
				)
			),
			array (
				'Article' => array (
					'id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array (
					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array ( ),
				'Tag' => array ( )
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findAll(array('Article.user_id' => 3), null, null, null, 1, 2);
		$expected = array (
			array (
				'Article' => array (
					'id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
				),
				'User' => array (
					'id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'Comment' => array (
					array (
						'id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
						'Article' => array (
							'id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
						),
						'User' => array (
							'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
						),
						'Attachment' => array(
							'id' => '1', 'comment_id' => 5, 'attachment' => 'attachment.zip', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
						)
					),
					array (
						'id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31',
						'Article' => array (
							'id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
						),
						'User' => array (
							'id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
						),
						'Attachment' => false
					)
				),
				'Tag' => array (
					array ( 'id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
					array ( 'id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
				)
			)
		);
		$this->assertEqual($result, $expected);

		$this->Featured = new Featured();

		$this->Featured->recursive = 2;
		$this->Featured->bindModel(array(
			'belongsTo' => array(
				'ArticleFeatured' => array(
					'conditions' => 'ArticleFeatured.published = \'Y\'',
					'fields' => 'id, title, user_id, published'
				)
			)
		));

		$this->Featured->ArticleFeatured->unbindModel(array(
			'hasMany' => array('Attachment', 'Comment'),
			'hasAndBelongsToMany'=>array('Tag'))
		);

		// UNCOMMENT THE FOLLOWING LINE TO MAKE TEST SUCCEED:
		//
		// $this->Featured->ArticleFeatured->unbindModel(array('belongsTo'=>array('Category')));

		$orderBy = 'ArticleFeatured.id ASC';
		$result = $this->Featured->findAll(null, null, $orderBy, 3);

		$expected = array (
			array (
				'Featured' => array (
					'id' => '1',
					'article_featured_id' => '1',
					'category_id' => '1',
					'published_date' => '2007-03-31 10:39:23',
					'end_date' => '2007-05-15 10:39:23',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'ArticleFeatured' => array (
					'id' => '1',
					'title' => 'First Article',
					'user_id' => '1',
					'published' => 'Y',
					'User' => array (
						'id' => '1',
						'user' => 'mariano',
						'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:16:23',
						'updated' => '2007-03-17 01:18:31'
					),
					'Featured' => array (
						'id' => '1',
						'article_featured_id' => '1',
						'category_id' => '1',
						'published_date' => '2007-03-31 10:39:23',
						'end_date' => '2007-05-15 10:39:23',
						'created' => '2007-03-18 10:39:23',
						'updated' => '2007-03-18 10:41:31'
					)
				),
				'Category' => array (
					'id' => '1',
					'parent_id' => '0',
					'name' => 'Category 1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				)
			),
			array (
				'Featured' => array (
					'id' => '2',
					'article_featured_id' => '2',
					'category_id' => '1',
					'published_date' => '2007-03-31 10:39:23',
					'end_date' => '2007-05-15 10:39:23',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'ArticleFeatured' => array (
					'id' => '2',
					'title' => 'Second Article',
					'user_id' => '3',
					'published' => 'Y',
					'User' => array (
						'id' => '3',
						'user' => 'larry',
						'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:20:23',
						'updated' => '2007-03-17 01:22:31'
					),
					'Featured' => array (
						'id' => '2',
						'article_featured_id' => '2',
						'category_id' => '1',
						'published_date' => '2007-03-31 10:39:23',
						'end_date' => '2007-05-15 10:39:23',
						'created' => '2007-03-18 10:39:23',
						'updated' => '2007-03-18 10:41:31'
					)
				),
				'Category' => array (
					'id' => '1',
					'parent_id' => '0',
					'name' => 'Category 1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				)
			)
		);

		$this->assertEqual($result, $expected);
		debug($result);
		debug($expected);
	}

	function testSaveField() {
		$this->model =& new Article();

		$this->model->id = 1;
		$result = $this->model->saveField('title', 'New First Article');
		$this->assertTrue($result);

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body'), 1);
		$expected = array('Article' => array (
			'id' => '1', 'user_id' => '1', 'title' => 'New First Article', 'body' => 'First Article Body'
		));

		$this->model->id = 1;
		$result = $this->model->saveField('title', '');
		$this->assertTrue($result);

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body'), 1);
		$expected = array('Article' => array (
			'id' => '1', 'user_id' => '1', 'title' => '', 'body' => 'First Article Body'
		));

		$this->model->id = 1;
		$result = $this->model->saveField('title', 'First Article');
		$this->assertTrue($result);

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body'), 1);
		$expected = array('Article' => array (
			'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body'
		));

		$this->model->id = 1;
		$result = $this->model->saveField('title', '', true);
		$this->assertFalse($result);
	}

	function testSave() {
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
		$expected = array('Article' => array (
			'id' => '1', 'user_id' => '1', 'title' => 'New First Article', 'body' => 'First Article Body', 'published' => 'N'
		));
		$this->assertEqual($result, $expected);

		$data = array('Article' => array('id' => 1, 'user_id' => '2', 'title' => 'First Article', 'body' => 'New First Article Body', 'published' => 'Y'));
		$result = $this->model->create() && $this->model->save($data, true, array('title', 'published'));
		$this->assertTrue($result);

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body', 'published'), 1);
		$expected = array('Article' => array (
			'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y'
		));
		$this->assertEqual($result, $expected);

		$data = array(
			'Article' => array(
				'user_id' => '2', 'title' => 'New Article', 'body' => 'New Article Body', 'created' => '2007-03-18 14:55:23', 'updated' => '2007-03-18 14:57:31'
			),
			'Tag' => array(
				'Tag' => array(1, 3)
			)
		);
		$result = $this->model->create() && $this->model->save($data);
		$this->assertTrue($result);

		$this->model->recursive = 2;
		$result = $this->model->read(null, 4);
		$expected = array (
			'Article' => array (
				'id' => '4', 'user_id' => '2', 'title' => 'New Article', 'body' => 'New Article Body', 'published' => 'N', 'created' => '2007-03-18 14:55:23', 'updated' => '2007-03-18 14:57:31'
			),
			'User' => array(
				'id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
			),
			'Comment' => array ( ),
			'Tag' => array (
				array ( 'id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				array ( 'id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
			)
		);
		$this->assertEqual($result, $expected);

		$data = array('Comment' => array(
				'article_id' => '4', 'user_id' => '1', 'comment' => 'Comment New Article', 'published' => 'Y', 'created' => '2007-03-18 14:57:23', 'updated' => '2007-03-18 14:59:31'
		));
		$result = $this->model->Comment->create() && $this->model->Comment->save($data);
		$this->assertTrue($result);

		$data = array('Attachment' => array(
				'comment_id' => '7', 'attachment' => 'newattachment.zip', 'created' => '2007-03-18 15:02:23', 'updated' => '2007-03-18 15:04:31'
		));
		$result = $this->model->Comment->Attachment->save($data);
		$this->assertTrue($result);

		$this->model->recursive = 2;
		$result = $this->model->read(null, 4);
		$expected = array (
			'Article' => array (
				'id' => '4', 'user_id' => '2', 'title' => 'New Article', 'body' => 'New Article Body', 'published' => 'N', 'created' => '2007-03-18 14:55:23', 'updated' => '2007-03-18 14:57:31'
			),
			'User' => array(
				'id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
			),
			'Comment' => array (
				array (
					'id' => '7', 'article_id' => '4', 'user_id' => '1', 'comment' => 'Comment New Article', 'published' => 'Y', 'created' => '2007-03-18 14:57:23', 'updated' => '2007-03-18 14:59:31',
					'Article' => array (
						'id' => '4', 'user_id' => '2', 'title' => 'New Article', 'body' => 'New Article Body', 'published' => 'N', 'created' => '2007-03-18 14:55:23', 'updated' => '2007-03-18 14:57:31'
					),
					'User' => array (
						'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
					),
					'Attachment' => array(
						'id' => '2', 'comment_id' => '7', 'attachment' => 'newattachment.zip', 'created' => '2007-03-18 15:02:23', 'updated' => '2007-03-18 15:04:31'
					)
				)
			),
			'Tag' => array (
				array ( 'id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				array ( 'id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
			)
		);
		$this->assertEqual($result, $expected);
	}

	function testRecursiveDel() {
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

	function testFindAllThreaded() {
		$this->model =& new Category();

		$result = $this->model->findAllThreaded();
		$expected = array (
			array (
				'Category' => array ( 'id' => '1', 'parent_id' => '0', 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
				'children' => array (
					array (
						'Category' => array ( 'id' => '2', 'parent_id' => '1', 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
						'children' => array ( )
					),
					array (
						'Category' => array ( 'id' => '3', 'parent_id' => '1', 'name' => 'Category 1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
						'children' => array ( )
					)
				)
			),
			array (
				'Category' => array ( 'id' => '4', 'parent_id' => '0', 'name' => 'Category 2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
				'children' => array ( )
			),
			array (
				'Category' => array ( 'id' => '5', 'parent_id' => '0', 'name' => 'Category 3', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
				'children' => array (
					array (
						'Category' => array ( 'id' => '6', 'parent_id' => '5', 'name' => 'Category 3.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
						'children' => array ( )
					)
				)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findAllThreaded(array('Category.name' => 'LIKE Category 1%'));
		$expected = array (
			array (
				'Category' => array ( 'id' => '1', 'parent_id' => '0', 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
				'children' => array (
					array (
						'Category' => array ( 'id' => '2', 'parent_id' => '1', 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
						'children' => array ( )
					),
					array (
						'Category' => array ( 'id' => '3', 'parent_id' => '1', 'name' => 'Category 1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
						'children' => array ( )
					)
				)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findAllThreaded(null, 'id, parent_id, name');
		$expected = array (
			array (
				'Category' => array ( 'id' => '1', 'parent_id' => '0', 'name' => 'Category 1'),
				'children' => array (
					array (
						'Category' => array ( 'id' => '2', 'parent_id' => '1', 'name' => 'Category 1.1'),
						'children' => array ( )
					),
					array (
						'Category' => array ( 'id' => '3', 'parent_id' => '1', 'name' => 'Category 1.2'),
						'children' => array ( )
					)
				)
			),
			array (
				'Category' => array ( 'id' => '4', 'parent_id' => '0', 'name' => 'Category 2'),
				'children' => array ( )
			),
			array (
				'Category' => array ( 'id' => '5', 'parent_id' => '0', 'name' => 'Category 3'),
				'children' => array (
					array (
						'Category' => array ( 'id' => '6', 'parent_id' => '5', 'name' => 'Category 3.1'),
						'children' => array ( )
					)
				)
			)
		);
		$this->assertEqual($result, $expected);
	}

	function testFindNeighbours() {
		$this->model =& new Article();

		$result = $this->model->findNeighbours(null, 'Article.id', '2');
		$expected = array(
			'prev' => array(
				'Article' => array('id' => 1)
			),
			'next' => array(
				'Article' => array('id' => 3)
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->model->findNeighbours(null, 'Article.id', '3');
		$expected = array(
			'prev' => array(
				'Article' => array('id' => 2)
			),
			'next' => array()
		);
		$this->assertEqual($result, $expected);
	}
}

?>
