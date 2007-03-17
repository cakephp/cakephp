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
	class TestSuiteModel extends Model {
		var $useDbConfig = 'test_suite';
	}
	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.model
	 */
	class User extends TestSuiteModel {
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
	class Article extends TestSuiteModel {
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
	class Tag extends TestSuiteModel {
		var $name = 'Tag';
	}
	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.model
	 */
	class Comment extends TestSuiteModel {
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
	class Attachment extends TestSuiteModel {
		var $name = 'Attachment';
	}
	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.model
	 */
	class Category extends TestSuiteModel {
		var $name = 'Category';
	}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class ModelTest extends UnitTestCase {
	function setUp() {
		if (!isset($this->db) || !$this->db->isConnected()) {
			restore_error_handler();
			@$db =& ConnectionManager::getDataSource('test');
			set_error_handler('simpleTestErrorHandler');
	 		
	 		if (!$db->isConnected()) {
	 			$db =& ConnectionManager::getDataSource('default');
	 		}

	 		$config = $db->config;
	 		$config['prefix'] .= 'test_suite_';
	 		
	 		ConnectionManager::create('test_suite', $config);
	 		
	 		$this->db =& ConnectionManager::getDataSource('test_suite');
			$this->db->fullDebug = false;
		} else {
			$config = $this->db->config;
		}

		$queries = array();

		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'users` VALUES(1, \'mariano\', MD5(\'password\'), \'2007-03-17 01:16:23\', \'2007-03-17 01:18:31\')';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'users` VALUES(2, \'nate\', MD5(\'password\'), \'2007-03-17 01:18:23\', \'2007-03-17 01:20:31\')';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'users` VALUES(3, \'larry\', MD5(\'password\'), \'2007-03-17 01:20:23\', \'2007-03-17 01:22:31\')';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'users` VALUES(4, \'garrett\', MD5(\'password\'), \'2007-03-17 01:22:23\', \'2007-03-17 01:24:31\')';
		
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'articles` VALUES(1, 1, \'First Article\', \'First Article Body\', \'Y\', \'2007-03-18 10:39:23\', \'2007-03-18 10:41:31\')';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'articles` VALUES(2, 3, \'Second Article\', \'Second Article Body\', \'Y\', \'2007-03-18 10:41:23\', \'2007-03-18 10:43:31\')';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'articles` VALUES(3, 1, \'Third Article\', \'Third Article Body\', \'Y\', \'2007-03-18 10:43:23\', \'2007-03-18 10:45:31\')';
		
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'tags` VALUES(1, \'tag1\', \'2007-03-18 12:22:23\', \'2007-03-18 12:24:31\')';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'tags` VALUES(2, \'tag2\', \'2007-03-18 12:24:23\', \'2007-03-18 12:26:31\')';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'tags` VALUES(3, \'tag3\', \'2007-03-18 12:26:23\', \'2007-03-18 12:28:31\')';
		
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'articles_tags` VALUES(1, 1)';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'articles_tags` VALUES(1, 2)';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'articles_tags` VALUES(2, 1)';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'articles_tags` VALUES(2, 3)';
		
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'comments` VALUES(1, 1, 2, \'First Comment for First Article\',  \'Y\', \'2007-03-18 10:45:23\', \'2007-03-18 10:47:31\')';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'comments` VALUES(2, 1, 4, \'Second Comment for First Article\',  \'Y\', \'2007-03-18 10:47:23\', \'2007-03-18 10:49:31\')';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'comments` VALUES(3, 1, 1, \'Third Comment for First Article\',  \'Y\', \'2007-03-18 10:49:23\', \'2007-03-18 10:51:31\')';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'comments` VALUES(4, 1, 1, \'Fourth Comment for First Article\',  \'N\', \'2007-03-18 10:51:23\', \'2007-03-18 10:53:31\')';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'comments` VALUES(5, 2, 1, \'First Comment for Second Article\',  \'Y\', \'2007-03-18 10:53:23\', \'2007-03-18 10:55:31\')';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'comments` VALUES(6, 2, 2, \'Second Comment for Second Article\',  \'Y\', \'2007-03-18 10:55:23\', \'2007-03-18 10:57:31\')';
		
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'attachments` VALUES(1, 5, \'attachment.zip\',  \'2007-03-18 10:51:23\', \'2007-03-18 10:53:31\')';
		
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'categories` VALUES(1, 0, \'Category 1\', \'2007-03-18 15:30:23\', \'2007-03-18 15:32:31\')';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'categories` VALUES(2, 1, \'Category 1.1\', \'2007-03-18 15:30:23\', \'2007-03-18 15:32:31\')';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'categories` VALUES(3, 1, \'Category 1.2\', \'2007-03-18 15:30:23\', \'2007-03-18 15:32:31\')';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'categories` VALUES(4, 0, \'Category 2\', \'2007-03-18 15:30:23\', \'2007-03-18 15:32:31\')';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'categories` VALUES(5, 0, \'Category 3\', \'2007-03-18 15:30:23\', \'2007-03-18 15:32:31\')';
		$queries[] = 'INSERT INTO `' . $config['prefix'] . 'categories` VALUES(6, 5, \'Category 3.1\', \'2007-03-18 15:30:23\', \'2007-03-18 15:32:31\')';
		
		foreach($queries as $query) {
			$this->db->_execute($query);
		}
	}
	
	function tearDown() {
		$config = $this->db->config;
		
		$queries = array();
		
		$queries[] = 'TRUNCATE TABLE `' . $config['prefix'] . 'categories`';
		$queries[] = 'TRUNCATE TABLE `' . $config['prefix'] . 'attachments`';
		$queries[] = 'TRUNCATE TABLE `' . $config['prefix'] . 'comments`';
		$queries[] = 'TRUNCATE TABLE `' . $config['prefix'] . 'tags`';
		$queries[] = 'TRUNCATE TABLE `' . $config['prefix'] . 'articles`';
		$queries[] = 'TRUNCATE TABLE `' . $config['prefix'] . 'users`';
		
		foreach($queries as $query) {
			$this->db->_execute($query);
		}
		
		if (isset($this->model)) {
			unset($this->model);
		}
	}

  /**
   * Leave as first test method, create tables.
   */	
  function testStartup() {
  	$config = $this->db->config;
  	
		$queries = array();
		
		$queries[] = 'CREATE TABLE IF NOT EXISTS `' . $config['prefix'] . 'categories`(
			`id` INT NOT NULL AUTO_INCREMENT,
			`parent_id` INT NOT NULL,
			`name` VARCHAR(255) NOT NULL,
			`created` DATETIME,
			`updated` DATETIME,
			
			PRIMARY KEY(`id`)
		)';
		
		$queries[] = 'CREATE TABLE IF NOT EXISTS `' . $config['prefix'] . 'users`(
			`id` INT NOT NULL AUTO_INCREMENT,
			`user` VARCHAR(255) NOT NULL,
			`password` VARCHAR(255) NOT NULL,
			`created` DATETIME,
			`updated` DATETIME,
			
			PRIMARY KEY(`id`)
		)';
		
		$queries[] = 'CREATE TABLE IF NOT EXISTS `' . $config['prefix'] . 'articles`(
			`id` INT NOT NULL AUTO_INCREMENT,
			`user_id` INT NOT NULL,
			`title` VARCHAR(255) NOT NULL,
			`body` TEXT NOT NULL,
			`published` CHAR(1) DEFAULT \'N\',
			`created` DATETIME,
			`updated` DATETIME,
			
			PRIMARY KEY(`id`)
		)';
		
		$queries[] = 'CREATE TABLE IF NOT EXISTS `' . $config['prefix'] . 'tags`(
			`id` INT NOT NULL AUTO_INCREMENT,
			`tag` VARCHAR(255) NOT NULL,
			`created` DATETIME,
			`updated` DATETIME,
			
			PRIMARY KEY(`id`)
		)';
		
		$queries[] = 'CREATE TABLE IF NOT EXISTS `' . $config['prefix'] . 'articles_tags`(
			`article_id` INT NOT NULL,
			`tag_id` INT NOT NULL,
			
			PRIMARY KEY(`article_id`, `tag_id`)
		)';
		
		$queries[] = 'CREATE TABLE IF NOT EXISTS `' . $config['prefix'] . 'comments`(
			`id` INT NOT NULL AUTO_INCREMENT,
			`article_id` INT NOT NULL,
			`user_id` INT NOT NULL,
			`comment` TEXT NOT NULL,
			`published` CHAR(1) DEFAULT \'N\',
			`created` DATETIME,
			`updated` DATETIME,
			
			PRIMARY KEY(`id`)
		)';
		
		$queries[] = 'CREATE TABLE IF NOT EXISTS `' . $config['prefix'] . 'attachments`(
			`id` INT NOT NULL AUTO_INCREMENT,
			`comment_id` INT NOT NULL,
			`attachment` VARCHAR(255) NOT NULL,
			`created` DATETIME,
			`updated` DATETIME,
			
			PRIMARY KEY(`id`)
		)';
		
		foreach($queries as $query) {
			$this->db->_execute($query);
		}
  	
		return parent::getTests();
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
		$expected = array ( 0 => array ( 'name' => 'id', 'type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, ), 1 => array ( 'name' => 'user', 'type' => 'string', 'null' => false, 'default' => '', 'length' => 255, ), 2 => array ( 'name' => 'password', 'type' => 'string', 'null' => false, 'default' => '', 'length' => 255, ), 3 => array ( 'name' => 'created', 'type' => 'datetime', 'null' => true, 'default' => NULL, 'length' => NULL, ), 4 => array ( 'name' => 'updated', 'type' => 'datetime', 'null' => true, 'default' => NULL, 'length' => NULL));
		$this->assertEqual($result, $expected);
		
		$this->model =& new Article();
		$result = $this->model->create();
		$expected = array ('Article' => array('published' => 'N'));
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
		
		$result = $this->model->hasMany;
		$expected = array();
		$this->assertEqual($result, $expected);
		
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
		
		/*
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
		*/
		
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
		
		/*
		$result = $this->model->hasMany;
		$expected = array();
		$this->assertEqual($result, $expected);
		*/
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

		$db =& ConnectionManager::getDataSource('test_suite');
		$db->fullDebug = true;

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
	
  /**
   * Leave as last test method, drop tables.
   */
  function testFinish() {
		$config = $this->db->config;

		$queries = array();
		
		$queries[] = 'DROP TABLE `' . $config['prefix'] . 'categories`';
		$queries[] = 'DROP TABLE `' . $config['prefix'] . 'attachments`';
		$queries[] = 'DROP TABLE `' . $config['prefix'] . 'comments`';
		$queries[] = 'DROP TABLE `' . $config['prefix'] . 'articles_tags`';
		$queries[] = 'DROP TABLE `' . $config['prefix'] . 'tags`';
		$queries[] = 'DROP TABLE `' . $config['prefix'] . 'articles`';
		$queries[] = 'DROP TABLE `' . $config['prefix'] . 'users`';
		
		foreach($queries as $query) {
			$this->db->_execute($query);
		}
	}
}

function array_diff_recursive($array1, $array2) {

	foreach ($array1 as $key => $value) {
		if (is_array($value)) {
			if (@!is_array($array2[$key])) {
				$difference[$key] = $value;
			} else {
				$new_diff = array_diff_recursive($value, $array2[$key]);
				if ($new_diff != false) {
					$difference[$key] = $new_diff;
				}
			}
		} elseif (!isset($array2[$key]) || $array2[$key] != $value) {
			$difference[$key] = $value;
		}
	}
	return !isset($difference) ? 0 : $difference;
}

?>