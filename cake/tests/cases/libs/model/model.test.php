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

App::import('Core', array('AppModel', 'Model', 'DataSource', 'DboSource', 'DboMysql'));

/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Test extends Model {
	var $useTable = false;
	var $name = 'Test';

	function schema() {
		return array(
			'id'=> array('type' => 'integer', 'null' => '', 'default' => '1', 'length' => '8', 'key'=>'primary'),
			'name'=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'email'=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'notes'=> array('type' => 'text', 'null' => '1', 'default' => 'write some notes here', 'length' => ''),
			'created'=> array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
			'updated'=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null));
	}
}

/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class TestValidate extends Model {
	var $useTable = false;
	var $name = 'TestValidate';

	function validateNumber($value, $options) {
		$options = am(array('min' => 0, 'max' => 100), $options);
		$valid = ($value >= $options['min'] && $value <= $options['max']);
		return $valid;
	}

	function validateTitle($title) {
		if (!empty($title) && strpos(low($title), 'title-') === 0) {
			return true;
		}
		return false;
	}

	function schema() {
		return array(
			'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'title' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'body' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => ''),
			'number' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
			'modified' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
		);
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
	var $validate = array('user' => VALID_NOT_EMPTY, 'password' => VALID_NOT_EMPTY);
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
	var $hasMany = array('Comment' => array('className'=>'Comment', 'dependent' => true));
	var $hasAndBelongsToMany = array('Tag');
	var $validate = array('user_id' => VALID_NUMBER, 'title' => array('allowEmpty' => false, 'rule' => VALID_NOT_EMPTY), 'body' => VALID_NOT_EMPTY);

	function titleDuplicate ($title) {
		if ($title === 'My Article Title') {
			return false;
		}
		return true;
	}
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
	var $hasMany = array('Comment' => array('className'=>'Comment', 'dependent' => true));
	var $hasAndBelongsToMany = array('Tag');
	var $validate = array('user_id' => VALID_NUMBER, 'title' => VALID_NOT_EMPTY, 'body' => VALID_NOT_EMPTY);
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
class ArticlesTag extends CakeTestModel {
	var $name = 'ArticlesTag';
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class ArticleFeaturedsTag extends CakeTestModel {
	var $name = 'ArticleFeaturedsTag';
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
	var $hasOne = array('Attachment' => array('className'=>'Attachment', 'dependent' => true));
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
	var $belongsTo = array('ParentCategory' => array('className' => 'CategoryThread', 'foreignKey' => 'parent_id'));
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Apple extends CakeTestModel {
	var $name = 'Apple';
	var $validate = array('name' => VALID_NOT_EMPTY);
	var $hasOne = array('Sample');
	var $hasMany = array('Child' => array('className' => 'Apple', 'dependent' => true));
	var $belongsTo = array('Parent' => array('className' => 'Apple', 'foreignKey' => 'apple_id'));
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Sample extends CakeTestModel {
	var $name = 'Sample';
	var $belongsTo = 'Apple';
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class AnotherArticle extends CakeTestModel {
	var $name = 'AnotherArticle';
	var $hasMany = 'Home';
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Advertisement extends CakeTestModel {
	var $name = 'Advertisement';
	var $hasMany = 'Home';
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Home extends CakeTestModel {
	var $name = 'Home';
	var $belongsTo = array('AnotherArticle', 'Advertisement');
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Post extends CakeTestModel {
	var $name = 'Post';
	var $belongsTo = array('Author');
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Author extends CakeTestModel {
	var $name = 'Author';
	var $hasMany = array('Post');

	function afterFind($results) {
		$results[0]['Author']['test'] = 'working';
		return $results;
	}
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Project extends CakeTestModel {
	var $name = 'Project';
	var $hasMany = array('Thread');
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Thread extends CakeTestModel {
	var $name = 'Thread';
	var $hasMany = array('Message');
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Message extends CakeTestModel {
	var $name = 'Message';
	var $hasOne = array('Bid');
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Bid extends CakeTestModel {
	var $name = 'Bid';
	var $belongsTo = array('Message');
}
class NodeAfterFind extends CakeTestModel {
	var $name = 'NodeAfterFind';
	var $validate = array('name' => VALID_NOT_EMPTY);
	var $useTable = 'apples';
	var $hasOne = array('Sample' => array('className' => 'NodeAfterFindSample'));
	var $hasMany = array('Child' => array('className' => 'NodeAfterFind', 'dependent' => true));
	var $belongsTo = array('Parent' => array('className' => 'NodeAfterFind', 'foreignKey' => 'apple_id'));

	function afterFind($results) {
		return $results;
	}
}
class NodeAfterFindSample extends CakeTestModel {
	var $name = 'NodeAfterFindSample';
	var $useTable = 'samples';
	var $belongsTo = 'NodeAfterFind';
}
class NodeNoAfterFind extends CakeTestModel {
	var $name = 'NodeAfterFind';
	var $validate = array('name' => VALID_NOT_EMPTY);
	var $useTable = 'apples';
	var $hasOne = array('Sample' => array('className' => 'NodeAfterFindSample'));
	var $hasMany = array('Child' => array( 'className' => 'NodeAfterFind', 'dependent' => true));
	var $belongsTo = array('Parent' => array('className' => 'NodeAfterFind', 'foreignKey' => 'apple_id'));
}
class ModelA extends CakeTestModel {
	var $name = 'ModelA';
	var $useTable = 'apples';
	var $hasMany = array('ModelB', 'ModelC');
}
class ModelB extends CakeTestModel {
	var $name = 'ModelB';
	var $useTable = 'messages';
	var $hasMany = array('ModelD');
}
class ModelC extends CakeTestModel {
	var $name = 'ModelC';
	var $useTable = 'bids';
	var $hasMany = array('ModelD');
}
class ModelD extends CakeTestModel {
	var $useTable = 'threads';
}
class Portfolio extends CakeTestModel {
	var $name = 'Portfolio';
	var $hasAndBelongsToMany = array('Item');
}
class Item extends CakeTestModel {
	var $name = 'Item';
	var $belongsTo = array('Syfile');
	var $hasAndBelongsToMany = array('Portfolio');
}
class ItemsPortfolio extends CakeTestModel {
	var $name = 'ItemsPortfolio';
}
class Syfile extends CakeTestModel {
	var $name = 'Syfile';
	var $belongsTo = array('Image');
}
class Image extends CakeTestModel {
	var $name = 'Image';
}
class DeviceType extends CakeTestModel {
	var $name = 'DeviceType';
	var $order = array('DeviceType.order' => 'ASC');
	var $belongsTo = array(
		'DeviceTypeCategory', 'FeatureSet', 'ExteriorTypeCategory',
		'Image' => array('className' => 'Document'),
		'Extra1' => array('className' => 'Document'),
		'Extra2' => array('className' => 'Document'));
	var $hasMany = array('Device' => array('order' => array('Device.id' => 'ASC')));
}
class DeviceTypeCategory extends CakeTestModel {
	var $name = 'DeviceTypeCategory';
}
class FeatureSet extends CakeTestModel {
	var $name = 'FeatureSet';
}
class ExteriorTypeCategory extends CakeTestModel {
	var $name = 'ExteriorTypeCategory';
	var $belongsTo = array('Image' => array('className' => 'Device'));
}
class Document extends CakeTestModel {
	var $name = 'Document';
	var $belongsTo = array('DocumentDirectory');
}
class Device extends CakeTestModel {
	var $name = 'Device';
}
class DocumentDirectory extends CakeTestModel {
	var $name = 'DocumentDirectory';
}
class PrimaryModel extends CakeTestModel {
	var $name = 'PrimaryModel';
}
class SecondaryModel extends CakeTestModel {
	var $name = 'SecondaryModel';
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class ModelTest extends CakeTestCase {

	var $fixtures = array(
		'core.category', 'core.category_thread', 'core.user', 'core.article', 'core.featured', 'core.article_featureds_tags',
		'core.article_featured', 'core.articles', 'core.tag', 'core.articles_tag', 'core.comment', 'core.attachment',
		'core.apple', 'core.sample', 'core.another_article', 'core.advertisement', 'core.home', 'core.post', 'core.author',
		'core.project', 'core.thread', 'core.message', 'core.bid',
		'core.portfolio', 'core.item', 'core.items_portfolio', 'core.syfile', 'core.image',
		'core.device_type', 'core.device_type_category', 'core.feature_set', 'core.exterior_type_category', 'core.document', 'core.device', 'core.document_directory',
		'core.primary_model', 'core.secondary_model'
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

	function testMultipleBelongsToWithSameClass() {
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
		$this->Portfolio =& new Portfolio();

		$result = $this->Portfolio->find(array('id' => 2), null, null, 3);
		$expected = array('Portfolio' => array(
						'id' => 2, 'seller_id' => 1, 'name' => 'Portfolio 2' ),
						'Item' => array(
						array('id' => 2, 'syfile_id' => 2, 'name' => 'Item 2',
								'ItemsPortfolio' => array('id' => 2, 'item_id' => 2, 'portfolio_id' => 2),
								'Syfile' => array('id' => 2, 'image_id' => 2, 'name' => 'Syfile 2',
										'Image' => array('id' => 2, 'name' => 'Image 2'))),

						array('id' => 6, 'syfile_id' => 6, 'name' => 'Item 6',
								'ItemsPortfolio' => array('id' => 6, 'item_id' => 6, 'portfolio_id' => 2),
								'Syfile' => array('id' => 6, 'image_id' => null, 'name' => 'Syfile 6',
										'Image' => array()))));
		$this->assertEqual($result, $expected);
		unset($this->Portfolio);
	}

	function testHasManyOptimization() {
		$this->Project =& new Project();
		$this->Project->recursive = 3;

		$result = $this->Project->findAll();
		$expected = array(array('Project' => array('id' => 1, 'name' => 'Project 1'),
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

	function testFindAllRecursiveSelfJoin() {
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

	function testIdentity() {
		$this->model =& new Test();
		$result = $this->model->alias;
		$expected = 'Test';
		$this->assertEqual($result, $expected);
	}

	function testCreation() {
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
				'id' => array('type' => 'integer',	'null' => false, 'default' => null,	'length' => $intLength, 'key' => 'primary', 'extra' => 'auto_increment'),
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
		$this->model =& new Author();
		$this->assertEqual($this->model->find('count'), 4);

		$this->model->deleteAll(true, false, false);
		$this->assertEqual($this->model->find('count'), 0);

		$result = $this->model->save();
		$this->assertTrue(isset($result['Author']['created']));
		$this->assertTrue(isset($result['Author']['updated']));
		$this->assertEqual($this->model->find('count'), 1);
	}

	function testCreationWithMultipleData() {
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
		$this->model =& new CategoryThread();

		$this->db->fullDebug = true;
		$this->model->recursive = 6;
		$result = $this->model->findAll(null, null, 'CategoryThread.id ASC');

		$expected = array(
			array('CategoryThread' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
					'ParentCategory' => array('id' => null, 'parent_id' => null, 'name' => null, 'created' => null, 'updated' => null)),
			array('CategoryThread' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
					'ParentCategory' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31')),
			array('CategoryThread' => array('id' => 3, 'parent_id' => 2, 'name' => 'Category 1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
					'ParentCategory' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'))),
			array('CategoryThread' => array('id' => 4, 'parent_id' => 3, 'name' => 'Category 1.1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
					'ParentCategory' => array('id' => 3, 'parent_id' => 2, 'name' => 'Category 1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31')))),
			array('CategoryThread' => array('id' => 5, 'parent_id' => 4, 'name' => 'Category 1.1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
					'ParentCategory' => array('id' => 4, 'parent_id' => 3, 'name' => 'Category 1.1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 3, 'parent_id' => 2, 'name' => 'Category 1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'))))),
			array('CategoryThread' => array('id' => 6, 'parent_id' => 5, 'name' => 'Category 2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
					'ParentCategory' => array('id' => 5, 'parent_id' => 4, 'name' => 'Category 1.1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 4, 'parent_id' => 3, 'name' => 'Category 1.1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 3, 'parent_id' => 2, 'name' => 'Category 1.1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31',
					'ParentCategory' => array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31')))))),
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
		$this->model =& new Article();
		$this->model->displayField = 'title';

		$result = $this->model->generateList(null, 'Article.title ASC');
		$expected = array(1 => 'First Article', 2 => 'Second Article', 3 => 'Third Article');
		$this->assertEqual($result, $expected);

		$result = $this->model->generateList(null, 'Article.title ASC', null, '{n}.Article.id');
		$expected = array(1 => 'First Article', 2 => 'Second Article', 3 => 'Third Article');
		$this->assertEqual($result, $expected);

		$result = $this->model->generateList(null, 'Article.title ASC', null, '{n}.Article.id', '{n}.Article');
		$expected = array(
				1 => array('id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				2 => array('id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
				3 => array('id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'));
		$this->assertEqual($result, $expected);

		$result = $this->model->generateList(null, 'Article.title ASC', null, '{n}.Article.id', '{n}.Article.title');
		$expected = array(1 => 'First Article', 2 => 'Second Article', 3 => 'Third Article');
		$this->assertEqual($result, $expected);

		$result = $this->model->generateList(null, 'Article.title ASC', null, '{n}.Article.id', '{n}.Article', '{n}.Article.user_id');
		$expected = array(1 => array(
						1 => array('id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
						3 => array('id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31')),
				3 => array(2 => array('id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31')));
		$this->assertEqual($result, $expected);

		$result = $this->model->generateList(null, 'Article.title ASC', null, '{n}.Article.id', '{n}.Article.title', '{n}.Article.user_id');
		$expected = array(1 => array(1 => 'First Article', 3 => 'Third Article'),
				3 => array(2 => 'Second Article'));
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

	function testUpdateExisting() {
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

	function testBindUnbind() {
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
	}

	function testFindCount() {
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
		$this->model =& new User();

		$result = $this->model->findByUser('mariano');
		$expected = array('User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'));
		$this->assertEqual($result, $expected);

		$result = $this->model->findByPassword('5f4dcc3b5aa765d61d8327deb882cf99');
		$expected = array('User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'));
		$this->assertEqual($result, $expected);
	}

	function testRead() {
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

	function testRecursiveFindAll() {
		$this->model =& new Article();

		$result = $this->model->findAll(array('Article.user_id' => 1));
		$expected = array(
			array(
				'Article' => array(
					'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
				),
				'User' => array(
					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
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
				'Article' => array(
					'id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
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
				'User' => array(
					'id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'Comment' => array(
					array(
						'id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
						'Article' => array(
							'id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
						),
						'User' => array(
							'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
						),
						'Attachment' => array(
							'id' => '1', 'comment_id' => 5, 'attachment' => 'attachment.zip', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
						)
					),
					array(
						'id' => '6', 'article_id' => '2', 'user_id' => '2', 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31',
						'Article' => array(
							'id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
						),
						'User' => array(
							'id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
						),
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
					'conditions' => 'ArticleFeatured.published = \'Y\'',
					'fields' => 'id, title, user_id, published'
				)
			)
		));

		$this->Featured->ArticleFeatured->unbindModel(array(
			'hasMany' => array('Attachment', 'Comment'),
			'hasAndBelongsToMany'=>array('Tag'))
		);

		$orderBy = 'ArticleFeatured.id ASC';
		$result = $this->Featured->findAll(null, null, $orderBy, 3);

		$expected = array(
			array(
				'Featured' => array(
					'id' => '1',
					'article_featured_id' => '1',
					'category_id' => '1',
					'published_date' => '2007-03-31 10:39:23',
					'end_date' => '2007-05-15 10:39:23',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'ArticleFeatured' => array(
					'id' => '1',
					'title' => 'First Article',
					'user_id' => '1',
					'published' => 'Y',
					'User' => array(
						'id' => '1',
						'user' => 'mariano',
						'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:16:23',
						'updated' => '2007-03-17 01:18:31'
					),
					'Category' => array(),
					'Featured' => array(
						'id' => '1',
						'article_featured_id' => '1',
						'category_id' => '1',
						'published_date' => '2007-03-31 10:39:23',
						'end_date' => '2007-05-15 10:39:23',
						'created' => '2007-03-18 10:39:23',
						'updated' => '2007-03-18 10:41:31'
					)
				),
				'Category' => array(
					'id' => '1',
					'parent_id' => '0',
					'name' => 'Category 1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				)
			),
			array(
				'Featured' => array(
					'id' => '2',
					'article_featured_id' => '2',
					'category_id' => '1',
					'published_date' => '2007-03-31 10:39:23',
					'end_date' => '2007-05-15 10:39:23',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'ArticleFeatured' => array(
					'id' => '2',
					'title' => 'Second Article',
					'user_id' => '3',
					'published' => 'Y',
					'User' => array(
						'id' => '3',
						'user' => 'larry',
						'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:20:23',
						'updated' => '2007-03-17 01:22:31'
					),
					'Category' => array(),
					'Featured' => array(
						'id' => '2',
						'article_featured_id' => '2',
						'category_id' => '1',
						'published_date' => '2007-03-31 10:39:23',
						'end_date' => '2007-05-15 10:39:23',
						'created' => '2007-03-18 10:39:23',
						'updated' => '2007-03-18 10:41:31'
					)
				),
				'Category' => array(
					'id' => '1',
					'parent_id' => '0',
					'name' => 'Category 1',
					'created' => '2007-03-18 15:30:23',
					'updated' => '2007-03-18 15:32:31'
				)
			)
		);

		$this->assertEqual($result, $expected);
	}

	function testRecursiveFindAllWithLimit() {
		$this->model =& new Article();

		$this->model->hasMany['Comment']['limit'] = 2;

		$result = $this->model->findAll(array('Article.user_id' => 1));
		$expected = array(
			array(
				'Article' => array(
					'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
				),
				'User' => array(
					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
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
				'Article' => array(
					'id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array(),
				'Tag' => array()
			)
		);
		$this->assertEqual($result, $expected);

		$this->model->hasMany['Comment']['limit'] = 1;

		$result = $this->model->findAll(array('Article.user_id' => 3), null, null, null, 1, 2);
		$expected = array(
			array(
				'Article' => array(
					'id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
				),
				'User' => array(
					'id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
				),
				'Comment' => array(
					array(
						'id' => '5', 'article_id' => '2', 'user_id' => '1', 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31',
						'Article' => array(
							'id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
						),
						'User' => array(
							'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
						),
						'Attachment' => array(
							'id' => '1', 'comment_id' => 5, 'attachment' => 'attachment.zip', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
						)
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
			'number' => array(
				'rule' => 'validateNumber',
				'min' => 3,
				'max' => 5
			),
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
			'number' => array(
				'rule' => 'validateNumber',
				'min' => 5,
				'max' => 10
			),
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

		$this->model->validate = array(
			'title' => array('allowEmpty' => true, 'rule' => 'validateTitle')
		);

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
			'Article' => array(
				'user_id' => '2', 'title' => 'New Article', 'body' => 'New Article Body', 'created' => '2007-03-18 14:55:23', 'updated' => '2007-03-18 14:57:31'
			),
			'Tag' => array(
				'Tag' => array(1, 3)
			)
		);
		$this->model->create();
		$result = $this->model->create() && $this->model->save($data);
		$this->assertTrue($result);

		$this->model->recursive = 2;
		$result = $this->model->read(null, 4);
		$expected = array(
			'Article' => array(
				'id' => '4', 'user_id' => '2', 'title' => 'New Article', 'body' => 'New Article Body', 'published' => 'N', 'created' => '2007-03-18 14:55:23', 'updated' => '2007-03-18 14:57:31'
			),
			'User' => array(
				'id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
			),
			'Comment' => array(),
			'Tag' => array(
				array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
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
		$expected = array(
			'Article' => array(
				'id' => '4', 'user_id' => '2', 'title' => 'New Article', 'body' => 'New Article Body', 'published' => 'N', 'created' => '2007-03-18 14:55:23', 'updated' => '2007-03-18 14:57:31'
			),
			'User' => array(
				'id' => '2', 'user' => 'nate', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'
			),
			'Comment' => array(
				array(
					'id' => '7', 'article_id' => '4', 'user_id' => '1', 'comment' => 'Comment New Article', 'published' => 'Y', 'created' => '2007-03-18 14:57:23', 'updated' => '2007-03-18 14:59:31',
					'Article' => array(
						'id' => '4', 'user_id' => '2', 'title' => 'New Article', 'body' => 'New Article Body', 'published' => 'N', 'created' => '2007-03-18 14:55:23', 'updated' => '2007-03-18 14:57:31'
					),
					'User' => array(
						'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
					),
					'Attachment' => array(
						'id' => '2', 'comment_id' => '7', 'attachment' => 'newattachment.zip', 'created' => '2007-03-18 15:02:23', 'updated' => '2007-03-18 15:04:31'
					)
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
		$this->model =& new Article();

		// Create record we will be updating later

		$data = array('Article' => array('user_id' => '1', 'title' => 'Fourth Article', 'body' => 'Fourth Article Body', 'published' => 'Y'));
		$result = $this->model->create() && $this->model->save($data);
		$this->assertTrue($result);

		// Check record we created

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body', 'published'), 4);
		$expected = array('Article' => array(
			'id' => '4', 'user_id' => '1', 'title' => 'Fourth Article', 'body' => 'Fourth Article Body', 'published' => 'Y'
		));
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
		$expected = array('Article' => array(
			'id' => '4', 'user_id' => '1', 'title' => 'Fourth Article', 'body' => 'Fourth Article Body', 'published' => 'Y'
		));
		$this->assertEqual($result, $expected);

		// And now do the update with set()

		$data = array('Article' => array('id' => '4', 'title' => 'Fourth Article - New Title', 'published' => 'N'));

		$result = $this->model->set($data) && $this->model->save();

		// THIS WORKS, but it just looks awful and should not be needed
		// $result = $this->model->set($data) && $this->model->save($data);

		// THIS WORKS, but should not be used for editing since create() uses default DB values for fields I am not editing:
		// $result = $this->model->create() && $this->model->save($data);

		$this->assertTrue($result);

		// And see if it got edited

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body', 'published'), 4);
		$expected = array('Article' => array(
			'id' => '4', 'user_id' => '1', 'title' => 'Fourth Article - New Title', 'body' => 'Fourth Article Body', 'published' => 'N'
		));
		$this->assertEqual($result, $expected);

		// Make sure article we created to overlap is still intact

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body', 'published'), 5);
		$expected = array('Article' => array(
			'id' => '5', 'user_id' => '4', 'title' => 'Fifth Article', 'body' => 'Fifth Article Body', 'published' => 'Y'
		));
		$this->assertEqual($result, $expected);

		// Edit new this overlapped article

		$data = array('Article' => array('id' => '5', 'title' => 'Fifth Article - New Title 5'));

		$result = $this->model->set($data) && $this->model->save();
		$this->assertTrue($result);

		// Check it's now updated

		$this->model->recursive = -1;
		$result = $this->model->read(array('id', 'user_id', 'title', 'body', 'published'), 5);
		$expected = array('Article' => array(
			'id' => '5', 'user_id' => '4', 'title' => 'Fifth Article - New Title 5', 'body' => 'Fifth Article Body', 'published' => 'Y'
		));
		$this->assertEqual($result, $expected);

		// And now do a final check on all article titles

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

	function testSaveHabtm() {
		$this->model =& new Article();

		$result = $this->model->findById(2);
		$expected = array(
			'Article' => array(
				'id' => '2', 'user_id' => '3', 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
			),
			'User' => array(
				'id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
			),
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
				array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31')
			)
		);
		$this->assertEqual($result, $expected);

		// Setting just parent ID

		$data = array(
			'Article' => array('id' => '2' ),
			'Tag' => array(
				'Tag' => array( 2, 3 )
			)
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
				'id' => '2', 'user_id' => '3', 'title' => 'New Second Article', 'body' => 'Second Article Body'
			),
			'Tag' => array(
				array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31'),
				array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
			)
		);
		$this->assertEqual($result, $expected);

		// Setting no parent data

		$data = array(
			'Tag' => array(
				'Tag' => array( 1, 2, 3 )
			)
		);

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
			'Article' => array(
				'id' => '2', 'user_id' => '3', 'title' => 'New Second Article', 'body' => 'Second Article Body'
			),
			'Tag' => array(
				array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31'),
				array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
			)
		);
		$this->assertEqual($result, $expected);

		$data = array(
			'Tag' => array(
				'Tag' => array( )
			)
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
				'id' => '2', 'user_id' => '3', 'title' => 'New Second Article', 'body' => 'Second Article Body'
			),
			'Tag' => array()
		);
		$this->assertEqual($result, $expected);

		$data = array(
			'Tag' => array(
				'Tag' => array( 2, 3 )
			)
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
				'id' => '2', 'user_id' => '3', 'title' => 'New Second Article', 'body' => 'Second Article Body'
			),
			'Tag' => array(
				array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31'),
				array('id' => '3', 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')
			)
		);
		$this->assertEqual($result, $expected);

		// Parent data after HABTM data

		$data = array(
			'Tag' => array(
				'Tag' => array( 1, 2 )
			),
			'Article' => array('id' => '2', 'title' => 'New Second Article' ),
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
				'id' => '2', 'user_id' => '3', 'title' => 'New Second Article', 'body' => 'Second Article Body'
			),
			'Tag' => array(
				array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31')
			)
		);
		$this->assertEqual($result, $expected);

		$data = array(
			'Tag' => array(
				'Tag' => array( 1, 2 )
			),
			'Article' => array('id' => '2', 'title' => 'New Second Article Title' ),
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
				'id' => '2', 'user_id' => '3', 'title' => 'New Second Article Title', 'body' => 'Second Article Body'
			),
			'Tag' => array(
				array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
				array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31')
			)
		);
		$this->assertEqual($result, $expected);

		$data = array(
			'Tag' => array(
				'Tag' => array( 2, 3 )
			),
			'Article' => array('id' => '2', 'title' => 'Changed Second Article' ),
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

		$data = array('Article' => array('id' => 10, 'user_id' => '2', 'title' => 'New Article With Tags and fieldList',
									'body' => 'New Article Body with Tags and fieldList', 'created' => '2007-03-18 14:55:23',
									'updated' => '2007-03-18 14:57:31'),
							'Tag' => array('Tag' => array(1, 2, 3)));
		$result = $this->model->create() && $this->model->save($data, true, array('user_id', 'title', 'published'));
		$this->assertTrue($result);

		$this->model->unbindModel(array('belongsTo' => array('User'), 'hasMany' => array('Comment')));
		$result = $this->model->read();
		$expected = array('Article' => array('id' => 4,
									'user_id' => 2, 'title' => 'New Article With Tags and fieldList',
									'body' => '', 'published' => 'N', 'created' => '', 'updated' => ''),
								'Tag' => array(
									0 => array('id' => 1, 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
									1 => array('id' => 2, 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31'),
									2 => array('id' => 3, 'tag' => 'tag3', 'created' => '2007-03-18 12:26:23', 'updated' => '2007-03-18 12:28:31')));
		$this->assertEqual($result, $expected);
	}

	function testDel() {
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
		$this->model =& new Article();

		// Add some more articles

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

		// Delete with conditions

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
	}

	function testFindNeighbours() {
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
		$this->model =& new Apple();

		$result = $this->model->findAll();

		$expected = array(array('Apple' => array(
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
						array('id' => '6', 'apple_id' => '2', 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => '3', 'apple_id' => '2', 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
					'Parent' => array('id' => '2', 'apple_id' => '1', 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
					'Sample' => array('id' => '1', 'apple_id' => '3', 'name' => 'sample1'),
					'Child' => array()),
			array('Apple' => array(
				'id' => '6', 'apple_id' => '2', 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
					'Parent' => array('id' => '2', 'apple_id' => '1', 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
					'Sample' => array('id' => '3', 'apple_id' => '6', 'name' => 'sample3'),
					'Child' => array(array('id' => '8', 'apple_id' => '6', 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => '7', 'apple_id' => '7', 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
					'Parent' => array('id' => '7', 'apple_id' => '7', 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
					'Sample' => array('id' => '4', 'apple_id' => '7', 'name' => 'sample4'),
					'Child' => array(array('id' => '7', 'apple_id' => '7', 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => '8', 'apple_id' => '6', 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
					'Parent' => array('id' => '6', 'apple_id' => '2', 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
					'Sample' => array('id' => null, 'apple_id' => null, 'name' => null ),
					'Child' => array(array('id' => '9', 'apple_id' => '8', 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => '9', 'apple_id' => '8', 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'),
					'Parent' => array('id' => '8', 'apple_id' => '6', 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
					'Sample' => array('id' => null, 'apple_id' => null, 'name' => null),
					'Child' => array()));
		$this->assertEqual($result, $expected);
	}

	function testSaveEmpty() {
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
		$this->model =& new Apple();
		$this->model->recursive = 2;

		$result = $this->model->findAll();
		$expected = array(array('Apple' => array (
				'id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' =>'', 'apple_id' => '', 'name' => ''),
					'Child' => array(
						array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
							'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
								'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
								'Child' => array(
									array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
									array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
									array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))))),
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

						array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17',
							'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
							'Sample' => array('id' => 3, 'apple_id' => 6, 'name' => 'sample3'),
							'Child' => array(
								array('id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'))))),
			array('Apple' => array(
				'id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 1, 'apple_id' => 3, 'name' => 'sample1',
						'Apple' => array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17')),
					'Child' => array()),
			array('Apple' => array(
				'id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 3, 'apple_id' => 6, 'name' => 'sample3',
						'Apple' => array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17')),
					'Child' => array(
						array('id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17',
							'Parent' => array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
							'Sample' => array(),
							'Child' => array(
								array('id' => 9, 'apple_id' => 8, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'))))),
			array('Apple' => array(
				'id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17',
						'Parent' => array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 4, 'apple_id' => 7, 'name' => 'sample4'),
						'Child' => array(
							array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 4, 'apple_id' => 7, 'name' => 'sample4',
						'Apple' => array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17')),
					'Child' => array(
						array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17',
							'Parent' => array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
							'Sample' => array('id' => 4, 'apple_id' => 7, 'name' => 'sample4'),
							'Child' => array(
								array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))))),
			array('Apple' => array(
				'id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17',
						'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 3, 'apple_id' => 6, 'name' => 'sample3'),
						'Child' => array(
							array('id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => ''),
					'Child' => array(
						array('id' => 9, 'apple_id' => 8, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17',
							'Parent' => array('id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
							'Sample' => array()))),
			array('Apple' => array(
				'id' => 9, 'apple_id' => 8, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17',
						'Parent' => array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
						'Sample' => array(),
						'Child' => array(
							array('id' => 9, 'apple_id' => 8, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => ''),
					'Child' => array()));
		$this->assertEqual($result, $expected);

		$result = $this->model->Parent->unbindModel(array('hasOne' => array('Sample')));
		$this->assertTrue($result);

		$result = $this->model->findAll();
		$expected = array(array('Apple' => array (
				'id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' =>'', 'apple_id' => '', 'name' => ''),
					'Child' => array(
						array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
							'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
								'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
								'Child' => array(
									array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
									array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
									array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))))),
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

						array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17',
							'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
							'Sample' => array('id' => 3, 'apple_id' => 6, 'name' => 'sample3'),
							'Child' => array(
								array('id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'))))),
			array('Apple' => array(
				'id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 1, 'apple_id' => 3, 'name' => 'sample1',
						'Apple' => array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17')),
					'Child' => array()),
			array('Apple' => array(
				'id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 3, 'apple_id' => 6, 'name' => 'sample3',
						'Apple' => array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17')),
					'Child' => array(
						array('id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17',
							'Parent' => array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
							'Sample' => array(),
							'Child' => array(
								array('id' => 9, 'apple_id' => 8, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'))))),
			array('Apple' => array(
				'id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17',
						'Parent' => array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 4, 'apple_id' => 7, 'name' => 'sample4',
						'Apple' => array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17')),
					'Child' => array(
						array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17',
							'Parent' => array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
							'Sample' => array('id' => 4, 'apple_id' => 7, 'name' => 'sample4'),
							'Child' => array(
								array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))))),
			array('Apple' => array(
				'id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17',
						'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => ''),
					'Child' => array(
						array('id' => 9, 'apple_id' => 8, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17',
							'Parent' => array('id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
							'Sample' => array()))),
			array('Apple' => array(
				'id' => 9, 'apple_id' => 8, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17',
						'Parent' => array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 9, 'apple_id' => 8, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'))),
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
							array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
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
							array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 1, 'apple_id' => 3, 'name' => 'sample1',
						'Apple' => array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 3, 'apple_id' => 6, 'name' => 'sample3',
						'Apple' => array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17',
						'Parent' => array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 4, 'apple_id' => 7, 'name' => 'sample4',
						'Apple' => array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17',
						'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => '')),
			array('Apple' => array(
				'id' => 9, 'apple_id' => 8, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17',
						'Parent' => array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
						'Child' => array(
							array('id' => 9, 'apple_id' => 8, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => '')));
		$this->assertEqual($result, $expected);

		$result = $this->model->unbindModel(array('hasMany' => array('Child')));
		$this->assertTrue($result);

		$result = $this->model->Sample->unbindModel(array('belongsTo' => array('Apple')));
		$this->assertTrue($result);

		$result = $this->model->findAll();
		$expected = array(array('Apple' => array (
				'id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
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
							array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 1, 'apple_id' => 3, 'name' => 'sample1')),
			array('Apple' => array(
				'id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Parent' => array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 3, 'apple_id' => 6, 'name' => 'sample3')),
			array('Apple' => array(
				'id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17',
						'Parent' => array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 4, 'apple_id' => 7, 'name' => 'sample4'),
						'Child' => array(
							array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 4, 'apple_id' => 7, 'name' => 'sample4')),
			array('Apple' => array(
				'id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17',
						'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
						'Sample' => array('id' => 3, 'apple_id' => 6, 'name' => 'sample3'),
						'Child' => array(
							array('id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => '')),
			array('Apple' => array(
				'id' => 9, 'apple_id' => 8, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17',
						'Parent' => array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
						'Sample' => array(),
						'Child' => array(
							array('id' => 9, 'apple_id' => 8, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'))),
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
							array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
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
							array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 1, 'apple_id' => 3, 'name' => 'sample1',
						'Apple' => array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 2, 'apple_id' => 1, 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17',
						'Sample' => array('id' => 2, 'apple_id' => 2, 'name' => 'sample2'),
						'Child' => array(
							array('id' => 1, 'apple_id' => 2, 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
							array('id' => 3, 'apple_id' => 2, 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
							array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 3, 'apple_id' => 6, 'name' => 'sample3',
						'Apple' => array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17',
						'Sample' => array('id' => 4, 'apple_id' => 7, 'name' => 'sample4'),
						'Child' => array(
							array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => 4, 'apple_id' => 7, 'name' => 'sample4',
						'Apple' => array('id' => 7, 'apple_id' => 7, 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'))),
			array('Apple' => array(
				'id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 6, 'apple_id' => 2, 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17',
						'Sample' => array('id' => 3, 'apple_id' => 6, 'name' => 'sample3'),
						'Child' => array(
							array('id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => '')),
			array('Apple' => array(
				'id' => 9, 'apple_id' => 8, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'),
					'Parent' => array('id' => 8, 'apple_id' => 6, 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17',
						'Sample' => array(),
						'Child' => array(
							array('id' => 9, 'apple_id' => 8, 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17'))),
					'Sample' => array('id' => '', 'apple_id' => '', 'name' => '')));
		$this->assertEqual($result, $expected);
	}

	function testSelfAssociationAfterFind() {
		$afterFindModel = new NodeAfterFind();
		$afterFindModel->recursive = 3;
		$afterFindData = $afterFindModel->findAll();

		$duplicateModel = new NodeAfterFind();
		$duplicateModel->recursive = 3;
		$duplicateModelData = $duplicateModel->findAll();

		$noAfterFindModel = new NodeNoAfterFind();
		$noAfterFindModel->recursive = 3;
		$noAfterFindData = $noAfterFindModel->findAll();

		$this->assertFalse($afterFindModel == $noAfterFindModel);
		// Limitation of PHP 4 and PHP 5 > 5.1.6 when comparing recursive objects
		if (PHP_VERSION === '5.1.6') {
			$this->assertFalse($afterFindModel != $duplicateModel);
		}

		$this->assertEqual($afterFindData, $noAfterFindData);
	}

	function testDeconstructFields() {
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
		$expected = array('Apple'=> array('mytime'=> '03:04:04'));
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

	function endTest() {
		ClassRegistry::flush();
	}
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class ValidationTest extends CakeTestModel {
	var $name = 'ValidationTest';
	var $useTable = false;

	var $validate = array(
		'title' => VALID_NOT_EMPTY,
		'published' => 'customValidationMethod',
		'body' => array(
			VALID_NOT_EMPTY,
			'/^.{5,}$/s' => 'no matchy',
			'/^[0-9A-Za-z \\.]{1,}$/s'
		)
	);

	function customValidationMethod($data) {
		return $data === 1;
	}

	function schema() {
		return array();
	}
}

/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class ValidationTest2 extends CakeTestModel {
	var $name = 'ValidationTest2';
	var $useTable = false;

	var $validate = array(
		'title' => VALID_NOT_EMPTY,
		'published' => 'customValidationMethod',
		'body' => array(
			VALID_NOT_EMPTY,
			'/^.{5,}$/s' => 'no matchy',
			'/^[0-9A-Za-z \\.]{1,}$/s'
		)
	);

	function customValidationMethod($data) {
		return $data === 1;
	}

	function schema() {
		return array();
	}
}

?>