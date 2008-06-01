<?php
/* SVN FILE: $Id$ */
/**
 * Mock models
 *
 * Mock classes for use in Model and related test cases
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
 * @since			CakePHP(tm) v 1.2.0.6464
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}

/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Test extends Model {
	var $useTable = false;
	var $name = 'Test';

	var $_schema = array(
		'id'=> array('type' => 'integer', 'null' => '', 'default' => '1', 'length' => '8', 'key'=>'primary'),
		'name'=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'email'=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'notes'=> array('type' => 'text', 'null' => '1', 'default' => 'write some notes here', 'length' => ''),
		'created'=> array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated'=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);
}

/** 
 * Short description for class. 
 * 
 * @package             cake.tests 
 * @subpackage  cake.tests.cases.libs.model 
 */ 
class TestAlias extends Model { 
   var $useTable = false; 
   var $name = 'TestAlias'; 
   var $alias = 'TestAlias';
   var $_schema = array( 
		'id'=> array('type' => 'integer', 'null' => '', 'default' => '1', 'length' => '8', 'key'=>'primary'), 
		'name'=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'), 
		'email'=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'), 
		'notes'=> array('type' => 'text', 'null' => '1', 'default' => 'write some notes here', 'length' => ''), 
		'created'=> array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''), 
		'updated'=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null) 
	);
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

	var $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'title' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'body' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => ''),
		'number' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'modified' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);

	function validateNumber($value, $options) {
		$options = array_merge(array('min' => 0, 'max' => 100), $options);
		$valid = ($value['number'] >= $options['min'] && $value['number'] <= $options['max']);
		return $valid;
	}

	function validateTitle($value) {
		return (!empty($value) && strpos(low($value['title']), 'title-') === 0);
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
	var $hasMany = array('Comment' => array('dependent' => true));
	var $hasAndBelongsToMany = array('Tag');
	var $validate = array('user_id' => VALID_NUMBER, 'title' => array('allowEmpty' => false, 'rule' => VALID_NOT_EMPTY), 'body' => VALID_NOT_EMPTY);
	var $beforeSaveReturn = true;

	function beforeSave() {
		return $this->beforeSaveReturn;
	}

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
class NumericArticle extends CakeTestModel {
	var $name = 'NumericArticle';
	var $useTable = 'numeric_articles';
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Article10 extends CakeTestModel {
	var $name = 'Article10';
	var $useTable = 'articles';
	var $hasMany = array('Comment' => array('dependent' => true, 'exclusive' => true));
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
	var $hasMany = array('Comment' => array('className' => 'Comment', 'dependent' => true));
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
	var $belongsTo = array('ArticleFeatured', 'Category');
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
	var $hasOne = array('Attachment' => array('dependent' => true));
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
class ModifiedAuthor extends Author {
	var $name = 'Author';

	function afterFind($results) {
		foreach($results as $index => $result) {
			$results[$index]['Author']['user'] .= ' (CakePHP)';
		}
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
	var $hasMany = array('Child' => array('className' => 'NodeAfterFind', 'dependent' => true));
	var $belongsTo = array('Parent' => array('className' => 'NodeAfterFind', 'foreignKey' => 'apple_id'));
}
class Node extends CakeTestModel{
	var $name = 'Node';
	var $hasAndBelongsToMany = array(
		'ParentNode' => array(
			'className' => 'Node',
			'joinTable' => 'dependency',
			'foreignKey' => 'child_id',
			'associationForeignKey' => 'parent_id',
		)
	);
}
class Dependency extends CakeTestModel{
	var $name = 'Dependency';
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
	var $name = 'ModelD';
	var $useTable = 'threads';
}
class Something extends CakeTestModel {
	var $name = 'Something';
	var $hasAndBelongsToMany = array('SomethingElse' => array('with' => array('JoinThing' => array('doomed'))));
}
class SomethingElse extends CakeTestModel {
	var $name = 'SomethingElse';
	var $hasAndBelongsToMany = array('Something' => array('with' => 'JoinThing'));
}
class JoinThing extends CakeTestModel {
	var $name = 'JoinThing';
	var $belongsTo = array('Something', 'SomethingElse');
}
class Portfolio extends CakeTestModel {
	var $name = 'Portfolio';
	var $hasAndBelongsToMany = array('Item');
}
class Item extends CakeTestModel {
	var $name = 'Item';
	var $belongsTo = array('Syfile' => array('counterCache' => true));
	var $hasAndBelongsToMany = array('Portfolio' => array('unique' => false));
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
class JoinA extends CakeTestModel {
	var $name = 'JoinA';
	var $hasAndBelongsToMany = array('JoinB', 'JoinC');
}
class JoinB extends CakeTestModel {
	var $name = 'JoinB';
	var $hasAndBelongsToMany = array('JoinA');
}
class JoinC extends CakeTestModel {
	var $name = 'JoinC';
	var $hasAndBelongsToMany = array('JoinA');
}
class ThePaper extends CakeTestModel {
	var $name = 'ThePaper';
	var $useTable = 'apples';
	var $hasOne = array('Itself' => array('className' => 'ThePaper', 'foreignKey' => 'apple_id'));
	var $hasAndBelongsToMany = array('Monkey' => array('joinTable' => 'the_paper_monkies'));
}
class Monkey extends CakeTestModel {
	var $name = 'Monkey';
	var $useTable = 'devices';
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class AssociationTest1 extends CakeTestModel {
	var $useTable = 'join_as';
	var $name = 'AssociationTest1';

	var $hasAndBelongsToMany = array('AssociationTest2' => array(
		'unique' => false, 'joinTable' => 'join_as_join_bs', 'foreignKey' => false
	));
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class AssociationTest2 extends CakeTestModel {
	var $useTable = 'join_bs';
	var $name = 'AssociationTest2';

	var $hasAndBelongsToMany = array('AssociationTest1' => array(
		'unique' => false, 'joinTable' => 'join_as_join_bs'
	));
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Callback extends CakeTestModel {

}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Uuid extends CakeTestModel {
	var $name = 'Uuid';
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class DataTest extends CakeTestModel {
	var $name = 'DataTest';
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class TheVoid extends CakeTestModel {
	var $name = 'TheVoid';
	var $useTable = false;
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
	var $_schema = array();

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
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Person extends CakeTestModel {
	var $name = 'Person';
	var $belongsTo = array(
			'Mother' => array(
				'className' => 'Person',
				'foreignKey' => 'mother_id'),
			'Father' => array(
				'className' => 'Person',
				'foreignKey' => 'father_id'));
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class UnderscoreField extends CakeTestModel {
	var $name = 'UnderscoreField';	
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Product extends CakeTestModel {
    var $name = 'Product';
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class Story extends CakeTestModel {
	var $name = 'Story';
	var $primaryKey = 'story';
	var $hasAndBelongsToMany = array('Tag' => array('foreignKey' => 'story'));
	var $validate = array('title' => VALID_NOT_EMPTY);
}
?>