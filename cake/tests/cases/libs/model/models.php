<?php
/* SVN FILE: $Id$ */
/**
 * Mock models file
 *
 * Mock classes for use in Model and related test cases
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
 * @since         CakePHP(tm) v 1.2.0.6464
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
/**
 * Test class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Test extends CakeTestModel {
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * name property
 *
 * @var string 'Test'
 * @access public
 */
	var $name = 'Test';
/**
 * schema property
 *
 * @var array
 * @access protected
 */
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
 * TestAlias class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class TestAlias extends CakeTestModel {
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * name property
 *
 * @var string 'TestAlias'
 * @access public
 */
	var $name = 'TestAlias';
/**
 * alias property
 *
 * @var string 'TestAlias'
 * @access public
 */
	var $alias = 'TestAlias';
/**
 * schema property
 *
 * @var array
 * @access protected
 */
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
 * TestValidate class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class TestValidate extends CakeTestModel {
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * name property
 *
 * @var string 'TestValidate'
 * @access public
 */
	var $name = 'TestValidate';
/**
 * schema property
 *
 * @var array
 * @access protected
 */
	var $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'title' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'body' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => ''),
		'number' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'modified' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);
/**
 * validateNumber method
 *
 * @param mixed $value
 * @param mixed $options
 * @access public
 * @return void
 */
	function validateNumber($value, $options) {
		$options = array_merge(array('min' => 0, 'max' => 100), $options);
		$valid = ($value['number'] >= $options['min'] && $value['number'] <= $options['max']);
		return $valid;
	}
/**
 * validateTitle method
 *
 * @param mixed $value
 * @access public
 * @return void
 */
	function validateTitle($value) {
		return (!empty($value) && strpos(low($value['title']), 'title-') === 0);
	}
}
/**
 * User class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class User extends CakeTestModel {
/**
 * name property
 *
 * @var string 'User'
 * @access public
 */
	var $name = 'User';
/**
 * validate property
 *
 * @var array
 * @access public
 */
	var $validate = array('user' => 'notEmpty', 'password' => 'notEmpty');
}
/**
 * Article class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Article extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Article'
 * @access public
 */
	var $name = 'Article';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('User');
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array('Comment' => array('dependent' => true));
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('Tag');
/**
 * validate property
 *
 * @var array
 * @access public
 */
	var $validate = array('user_id' => 'numeric', 'title' => array('allowEmpty' => false, 'rule' => 'notEmpty'), 'body' => 'notEmpty');
/**
 * beforeSaveReturn property
 *
 * @var bool true
 * @access public
 */
	var $beforeSaveReturn = true;
/**
 * beforeSave method
 *
 * @access public
 * @return void
 */
	function beforeSave() {
		return $this->beforeSaveReturn;
	}
/**
 * titleDuplicate method
 *
 * @param mixed $title
 * @access public
 * @return void
 */
	function titleDuplicate ($title) {
		if ($title === 'My Article Title') {
			return false;
		}
		return true;
	}
}
/**
 * NumericArticle class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class NumericArticle extends CakeTestModel {
/**
 * name property
 *
 * @var string 'NumericArticle'
 * @access public
 */
	var $name = 'NumericArticle';
/**
 * useTable property
 *
 * @var string 'numeric_articles'
 * @access public
 */
	var $useTable = 'numeric_articles';
}
/**
 * Article10 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Article10 extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Article10'
 * @access public
 */
	var $name = 'Article10';
/**
 * useTable property
 *
 * @var string 'articles'
 * @access public
 */
	var $useTable = 'articles';
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array('Comment' => array('dependent' => true, 'exclusive' => true));
}
/**
 * ArticleFeatured class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class ArticleFeatured extends CakeTestModel {
/**
 * name property
 *
 * @var string 'ArticleFeatured'
 * @access public
 */
	var $name = 'ArticleFeatured';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('User', 'Category');
/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	var $hasOne = array('Featured');
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array('Comment' => array('className' => 'Comment', 'dependent' => true));
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('Tag');
/**
 * validate property
 *
 * @var array
 * @access public
 */
	var $validate = array('user_id' => 'numeric', 'title' => 'notEmpty', 'body' => 'notEmpty');
}
/**
 * Featured class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Featured extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Featured'
 * @access public
 */
	var $name = 'Featured';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('ArticleFeatured', 'Category');
}
/**
 * Tag class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Tag extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Tag'
 * @access public
 */
	var $name = 'Tag';
}
/**
 * ArticlesTag class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class ArticlesTag extends CakeTestModel {
/**
 * name property
 *
 * @var string 'ArticlesTag'
 * @access public
 */
	var $name = 'ArticlesTag';
}
/**
 * ArticleFeaturedsTag class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class ArticleFeaturedsTag extends CakeTestModel {
/**
 * name property
 *
 * @var string 'ArticleFeaturedsTag'
 * @access public
 */
	var $name = 'ArticleFeaturedsTag';
}
/**
 * Comment class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Comment extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Comment'
 * @access public
 */
	var $name = 'Comment';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('Article', 'User');
/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	var $hasOne = array('Attachment' => array('dependent' => true));
}
/**
 * Modified Comment Class has afterFind Callback
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class ModifiedComment extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Comment'
 * @access public
 */
	var $name = 'Comment';
/**
 * useTable property
 *
 * @var string 'comments'
 * @access public
 */
	var $useTable = 'comments';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('Article');
/**
 * afterFind callback
 *
 * @return void
 **/
	function afterFind($results) {
		if (isset($results[0])) {
			$results[0]['Comment']['callback'] = 'Fire';
		}
		return $results;
	}
}

/**
 * MergeVarPluginAppModel class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class MergeVarPluginAppModel extends AppModel {
/**
 * actsAs parameter
 *
 * @var array
 */
	var $actsAs = array(
		'Containable'
	);
}
/**
 * MergeVarPluginPost class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class MergeVarPluginPost extends MergeVarPluginAppModel {
/**
 * actsAs parameter
 *
 * @var array
 */
	var $actsAs = array(
		'Tree'
	);
/**
 * useTable parameter
 *
 * @var string
 */
	var $useTable = 'posts';
}
/**
 * MergeVarPluginComment class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class MergeVarPluginComment extends MergeVarPluginAppModel {
/**
 * actsAs parameter
 *
 * @var array
 */
	var $actsAs = array(
		'Containable' => array('some_settings')
	);
/**
 * useTable parameter
 *
 * @var string
 */
	var $useTable = 'comments';
}


/**
 * Attachment class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Attachment extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Attachment'
 * @access public
 */
	var $name = 'Attachment';
}
/**
 * Category class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Category extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Category'
 * @access public
 */
	var $name = 'Category';
}
/**
 * CategoryThread class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class CategoryThread extends CakeTestModel {
/**
 * name property
 *
 * @var string 'CategoryThread'
 * @access public
 */
	var $name = 'CategoryThread';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('ParentCategory' => array('className' => 'CategoryThread', 'foreignKey' => 'parent_id'));
}
/**
 * Apple class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Apple extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Apple'
 * @access public
 */
	var $name = 'Apple';
/**
 * validate property
 *
 * @var array
 * @access public
 */
	var $validate = array('name' => 'notEmpty');
/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	var $hasOne = array('Sample');
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array('Child' => array('className' => 'Apple', 'dependent' => true));
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('Parent' => array('className' => 'Apple', 'foreignKey' => 'apple_id'));
}
/**
 * Sample class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Sample extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Sample'
 * @access public
 */
	var $name = 'Sample';
/**
 * belongsTo property
 *
 * @var string 'Apple'
 * @access public
 */
	var $belongsTo = 'Apple';
}
/**
 * AnotherArticle class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class AnotherArticle extends CakeTestModel {
/**
 * name property
 *
 * @var string 'AnotherArticle'
 * @access public
 */
	var $name = 'AnotherArticle';
/**
 * hasMany property
 *
 * @var string 'Home'
 * @access public
 */
	var $hasMany = 'Home';
}
/**
 * Advertisement class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Advertisement extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Advertisement'
 * @access public
 */
	var $name = 'Advertisement';
/**
 * hasMany property
 *
 * @var string 'Home'
 * @access public
 */
	var $hasMany = 'Home';
}
/**
 * Home class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Home extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Home'
 * @access public
 */
	var $name = 'Home';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('AnotherArticle', 'Advertisement');
}
/**
 * Post class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Post extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Post'
 * @access public
 */
	var $name = 'Post';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('Author');

	function beforeFind($queryData) {
		if (isset($queryData['connection'])) {
			$this->useDbConfig = $queryData['connection'];
		}
		return true;
	}

	function afterFind($results) {
		$this->useDbConfig = 'test_suite';
		return $results;
	}
}
/**
 * Author class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Author extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Author'
 * @access public
 */
	var $name = 'Author';
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array('Post');
/**
 * afterFind method
 *
 * @param mixed $results
 * @access public
 * @return void
 */
	function afterFind($results) {
		$results[0]['Author']['test'] = 'working';
		return $results;
	}
}
/**
 * ModifiedAuthor class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class ModifiedAuthor extends Author {
/**
 * name property
 *
 * @var string 'Author'
 * @access public
 */
	var $name = 'Author';
/**
 * afterFind method
 *
 * @param mixed $results
 * @access public
 * @return void
 */
	function afterFind($results) {
		foreach($results as $index => $result) {
			$results[$index]['Author']['user'] .= ' (CakePHP)';
		}
		return $results;
	}
}
/**
 * Project class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Project extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Project'
 * @access public
 */
	var $name = 'Project';
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array('Thread');
}
/**
 * Thread class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Thread extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Thread'
 * @access public
 */
	var $name = 'Thread';
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('Project');
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array('Message');
}
/**
 * Message class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Message extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Message'
 * @access public
 */
	var $name = 'Message';
/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	var $hasOne = array('Bid');
}
/**
 * Bid class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Bid extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Bid'
 * @access public
 */
	var $name = 'Bid';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('Message');
}
/**
 * NodeAfterFind class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class NodeAfterFind extends CakeTestModel {
/**
 * name property
 *
 * @var string 'NodeAfterFind'
 * @access public
 */
	var $name = 'NodeAfterFind';
/**
 * validate property
 *
 * @var array
 * @access public
 */
	var $validate = array('name' => 'notEmpty');
/**
 * useTable property
 *
 * @var string 'apples'
 * @access public
 */
	var $useTable = 'apples';
/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	var $hasOne = array('Sample' => array('className' => 'NodeAfterFindSample'));
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array('Child' => array('className' => 'NodeAfterFind', 'dependent' => true));
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('Parent' => array('className' => 'NodeAfterFind', 'foreignKey' => 'apple_id'));
/**
 * afterFind method
 *
 * @param mixed $results
 * @access public
 * @return void
 */
	function afterFind($results) {
		return $results;
	}
}
/**
 * NodeAfterFindSample class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class NodeAfterFindSample extends CakeTestModel {
/**
 * name property
 *
 * @var string 'NodeAfterFindSample'
 * @access public
 */
	var $name = 'NodeAfterFindSample';
/**
 * useTable property
 *
 * @var string 'samples'
 * @access public
 */
	var $useTable = 'samples';
/**
 * belongsTo property
 *
 * @var string 'NodeAfterFind'
 * @access public
 */
	var $belongsTo = 'NodeAfterFind';
}
/**
 * NodeNoAfterFind class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class NodeNoAfterFind extends CakeTestModel {
/**
 * name property
 *
 * @var string 'NodeAfterFind'
 * @access public
 */
	var $name = 'NodeAfterFind';
/**
 * validate property
 *
 * @var array
 * @access public
 */
	var $validate = array('name' => 'notEmpty');
/**
 * useTable property
 *
 * @var string 'apples'
 * @access public
 */
	var $useTable = 'apples';
/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	var $hasOne = array('Sample' => array('className' => 'NodeAfterFindSample'));
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array('Child' => array('className' => 'NodeAfterFind', 'dependent' => true));
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('Parent' => array('className' => 'NodeAfterFind', 'foreignKey' => 'apple_id'));
}
/**
 * Node class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Node extends CakeTestModel{
/**
 * name property
 *
 * @var string 'Node'
 * @access public
 */
	var $name = 'Node';
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array(
		'ParentNode' => array(
			'className' => 'Node',
			'joinTable' => 'dependency',
			'with' => 'Dependency',
			'foreignKey' => 'child_id',
			'associationForeignKey' => 'parent_id',
		)
	);
}
/**
 * Dependency class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Dependency extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Dependency'
 * @access public
 */
	var $name = 'Dependency';
}
/**
 * ModelA class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class ModelA extends CakeTestModel {
/**
 * name property
 *
 * @var string 'ModelA'
 * @access public
 */
	var $name = 'ModelA';
/**
 * useTable property
 *
 * @var string 'apples'
 * @access public
 */
	var $useTable = 'apples';
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array('ModelB', 'ModelC');
}
/**
 * ModelB class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class ModelB extends CakeTestModel {
/**
 * name property
 *
 * @var string 'ModelB'
 * @access public
 */
	var $name = 'ModelB';
/**
 * useTable property
 *
 * @var string 'messages'
 * @access public
 */
	var $useTable = 'messages';
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array('ModelD');
}
/**
 * ModelC class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class ModelC extends CakeTestModel {
/**
 * name property
 *
 * @var string 'ModelC'
 * @access public
 */
	var $name = 'ModelC';
/**
 * useTable property
 *
 * @var string 'bids'
 * @access public
 */
	var $useTable = 'bids';
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array('ModelD');
}
/**
 * ModelD class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class ModelD extends CakeTestModel {
/**
 * name property
 *
 * @var string 'ModelD'
 * @access public
 */
	var $name = 'ModelD';
/**
 * useTable property
 *
 * @var string 'threads'
 * @access public
 */
	var $useTable = 'threads';
}
/**
 * Something class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Something extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Something'
 * @access public
 */
	var $name = 'Something';
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('SomethingElse' => array('with' => array('JoinThing' => array('doomed'))));
}
/**
 * SomethingElse class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class SomethingElse extends CakeTestModel {
/**
 * name property
 *
 * @var string 'SomethingElse'
 * @access public
 */
	var $name = 'SomethingElse';
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('Something' => array('with' => 'JoinThing'));
}
/**
 * JoinThing class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class JoinThing extends CakeTestModel {
/**
 * name property
 *
 * @var string 'JoinThing'
 * @access public
 */
	var $name = 'JoinThing';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('Something', 'SomethingElse');
}
/**
 * Portfolio class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Portfolio extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Portfolio'
 * @access public
 */
	var $name = 'Portfolio';
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('Item');
}
/**
 * Item class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Item extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Item'
 * @access public
 */
	var $name = 'Item';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('Syfile' => array('counterCache' => true));
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('Portfolio' => array('unique' => false));
}
/**
 * ItemsPortfolio class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class ItemsPortfolio extends CakeTestModel {
/**
 * name property
 *
 * @var string 'ItemsPortfolio'
 * @access public
 */
	var $name = 'ItemsPortfolio';
}
/**
 * Syfile class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Syfile extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Syfile'
 * @access public
 */
	var $name = 'Syfile';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('Image');
}
/**
 * Image class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Image extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Image'
 * @access public
 */
	var $name = 'Image';
}
/**
 * DeviceType class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class DeviceType extends CakeTestModel {
/**
 * name property
 *
 * @var string 'DeviceType'
 * @access public
 */
	var $name = 'DeviceType';
/**
 * order property
 *
 * @var array
 * @access public
 */
	var $order = array('DeviceType.order' => 'ASC');
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array(
		'DeviceTypeCategory', 'FeatureSet', 'ExteriorTypeCategory',
		'Image' => array('className' => 'Document'),
		'Extra1' => array('className' => 'Document'),
		'Extra2' => array('className' => 'Document'));
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array('Device' => array('order' => array('Device.id' => 'ASC')));
}
/**
 * DeviceTypeCategory class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class DeviceTypeCategory extends CakeTestModel {
/**
 * name property
 *
 * @var string 'DeviceTypeCategory'
 * @access public
 */
	var $name = 'DeviceTypeCategory';
}
/**
 * FeatureSet class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class FeatureSet extends CakeTestModel {
/**
 * name property
 *
 * @var string 'FeatureSet'
 * @access public
 */
	var $name = 'FeatureSet';
}
/**
 * ExteriorTypeCategory class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class ExteriorTypeCategory extends CakeTestModel {
/**
 * name property
 *
 * @var string 'ExteriorTypeCategory'
 * @access public
 */
	var $name = 'ExteriorTypeCategory';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('Image' => array('className' => 'Device'));
}
/**
 * Document class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Document extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Document'
 * @access public
 */
	var $name = 'Document';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('DocumentDirectory');
}
/**
 * Device class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Device extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Device'
 * @access public
 */
	var $name = 'Device';
}
/**
 * DocumentDirectory class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class DocumentDirectory extends CakeTestModel {
/**
 * name property
 *
 * @var string 'DocumentDirectory'
 * @access public
 */
	var $name = 'DocumentDirectory';
}
/**
 * PrimaryModel class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class PrimaryModel extends CakeTestModel {
/**
 * name property
 *
 * @var string 'PrimaryModel'
 * @access public
 */
	var $name = 'PrimaryModel';
}
/**
 * SecondaryModel class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class SecondaryModel extends CakeTestModel {
/**
 * name property
 *
 * @var string 'SecondaryModel'
 * @access public
 */
	var $name = 'SecondaryModel';
}
/**
 * JoinA class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class JoinA extends CakeTestModel {
/**
 * name property
 *
 * @var string 'JoinA'
 * @access public
 */
	var $name = 'JoinA';
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('JoinB', 'JoinC');
}
/**
 * JoinB class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class JoinB extends CakeTestModel {
/**
 * name property
 *
 * @var string 'JoinB'
 * @access public
 */
	var $name = 'JoinB';
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('JoinA');
}
/**
 * JoinC class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class JoinC extends CakeTestModel {
/**
 * name property
 *
 * @var string 'JoinC'
 * @access public
 */
	var $name = 'JoinC';
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('JoinA');
}
/**
 * ThePaper class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class ThePaper extends CakeTestModel {
/**
 * name property
 *
 * @var string 'ThePaper'
 * @access public
 */
	var $name = 'ThePaper';
/**
 * useTable property
 *
 * @var string 'apples'
 * @access public
 */
	var $useTable = 'apples';
/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	var $hasOne = array('Itself' => array('className' => 'ThePaper', 'foreignKey' => 'apple_id'));
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('Monkey' => array('joinTable' => 'the_paper_monkies', 'order' => 'id'));
}
/**
 * Monkey class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Monkey extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Monkey'
 * @access public
 */
	var $name = 'Monkey';
/**
 * useTable property
 *
 * @var string 'devices'
 * @access public
 */
	var $useTable = 'devices';
}
/**
 * AssociationTest1 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class AssociationTest1 extends CakeTestModel {
/**
 * useTable property
 *
 * @var string 'join_as'
 * @access public
 */
	var $useTable = 'join_as';
/**
 * name property
 *
 * @var string 'AssociationTest1'
 * @access public
 */
	var $name = 'AssociationTest1';
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('AssociationTest2' => array(
		'unique' => false, 'joinTable' => 'join_as_join_bs', 'foreignKey' => false
	));
}
/**
 * AssociationTest2 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class AssociationTest2 extends CakeTestModel {
/**
 * useTable property
 *
 * @var string 'join_bs'
 * @access public
 */
	var $useTable = 'join_bs';
/**
 * name property
 *
 * @var string 'AssociationTest2'
 * @access public
 */
	var $name = 'AssociationTest2';
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('AssociationTest1' => array(
		'unique' => false, 'joinTable' => 'join_as_join_bs'
	));
}
/**
 * Callback class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Callback extends CakeTestModel {
	
}
/**
 * CallbackPostTestModel class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class CallbackPostTestModel extends CakeTestModel {
	var $useTable = 'posts';
/**
 * variable to control return of beforeValidate
 *
 * @var string
 */
	var $beforeValidateReturn = true;
/**
 * variable to control return of beforeSave
 *
 * @var string
 */
	var $beforeSaveReturn = true;
/**
 * variable to control return of beforeDelete
 *
 * @var string
 */
	var $beforeDeleteReturn = true;
/**
 * beforeSave callback
 *
 * @return void
 **/
	function beforeSave($options) {
		return $this->beforeSaveReturn;
	}
/**
 * beforeValidate callback
 *
 * @return void
 **/
	function beforeValidate($options) {
		return $this->beforeValidateReturn;
	}
/**
 * beforeDelete callback
 *
 * @return void
 **/
	function beforeDelete($cascade = true) {
		return $this->beforeDeleteReturn;
	}
}
/**
 * Uuid class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Uuid extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Uuid'
 * @access public
 */
	var $name = 'Uuid';
}
/**
 * DataTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class DataTest extends CakeTestModel {
/**
 * name property
 *
 * @var string 'DataTest'
 * @access public
 */
	var $name = 'DataTest';
}
/**
 * TheVoid class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class TheVoid extends CakeTestModel {
/**
 * name property
 *
 * @var string 'TheVoid'
 * @access public
 */
	var $name = 'TheVoid';
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
}
/**
 * ValidationTest1 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class ValidationTest1 extends CakeTestModel {
/**
 * name property
 *
 * @var string 'ValidationTest'
 * @access public
 */
	var $name = 'ValidationTest1';
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * schema property
 *
 * @var array
 * @access protected
 */
	var $_schema = array();
/**
 * validate property
 *
 * @var array
 * @access public
 */
	var $validate = array(
		'title' => 'notEmpty',
		'published' => 'customValidationMethod',
		'body' => array(
			'notEmpty',
			'/^.{5,}$/s' => 'no matchy',
			'/^[0-9A-Za-z \\.]{1,}$/s'
		)
	);
/**
 * customValidationMethod method
 *
 * @param mixed $data
 * @access public
 * @return void
 */
	function customValidationMethod($data) {
		return $data === 1;
	}
/**
 * Custom validator with parameters + default values
 *
 * @access public
 * @return array
 */
	function customValidatorWithParams($data, $validator, $or = true, $ignore_on_same = 'id') {
		$this->validatorParams = get_defined_vars();
		unset($this->validatorParams['this']);
		return true;
	}
/**
 * Custom validator with messaage
 *
 * @access public
 * @return array
 */
	function customValidatorWithMessage($data) {
		return 'This field will *never* validate! Muhahaha!';
	}
/**
 * Test validation with many parameters
 *
 * @return void
 */
	function customValidatorWithSixParams($data, $one = 1, $two = 2, $three = 3, $four = 4, $five = 5, $six = 6) {
		$this->validatorParams = get_defined_vars();
		unset($this->validatorParams['this']);
		return true;
	}
}
/**
 * ValidationTest2 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class ValidationTest2 extends CakeTestModel {
/**
 * name property
 *
 * @var string 'ValidationTest2'
 * @access public
 */
	var $name = 'ValidationTest2';
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * validate property
 *
 * @var array
 * @access public
 */
	var $validate = array(
		'title' => 'notEmpty',
		'published' => 'customValidationMethod',
		'body' => array(
			'notEmpty',
			'/^.{5,}$/s' => 'no matchy',
			'/^[0-9A-Za-z \\.]{1,}$/s'
		)
	);
/**
 * customValidationMethod method
 *
 * @param mixed $data
 * @access public
 * @return void
 */
	function customValidationMethod($data) {
		return $data === 1;
	}
/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		return array();
	}
}
/**
 * Person class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Person extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Person'
 * @access public
 */
	var $name = 'Person';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array(
			'Mother' => array(
				'className' => 'Person',
				'foreignKey' => 'mother_id'),
			'Father' => array(
				'className' => 'Person',
				'foreignKey' => 'father_id'));
}
/**
 * UnderscoreField class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class UnderscoreField extends CakeTestModel {
/**
 * name property
 *
 * @var string 'UnderscoreField'
 * @access public
 */
	var $name = 'UnderscoreField';
}
/**
 * Product class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Product extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Product'
 * @access public
 */
	var $name = 'Product';
}
/**
 * Story class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Story extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Story'
 * @access public
 */
	var $name = 'Story';
/**
 * primaryKey property
 *
 * @var string 'story'
 * @access public
 */
	var $primaryKey = 'story';
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('Tag' => array('foreignKey' => 'story'));
/**
 * validate property
 *
 * @var array
 * @access public
 */
	var $validate = array('title' => 'notEmpty');
}
/**
 * Cd class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Cd extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Cd'
 * @access public
 */
	var $name = 'Cd';
/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	var $hasOne = array('OverallFavorite' => array('foreignKey' => 'model_id', 'dependent' => true, 'conditions' => array('model_type' => 'Cd')));
}
/**
 * Book class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Book extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Book'
 * @access public
 */
	var $name = 'Book';
/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	var $hasOne = array('OverallFavorite' => array('foreignKey' => 'model_id', 'dependent' => true, 'conditions' => array('model_type' => 'Book')));
}
/**
 * OverallFavorite class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class OverallFavorite extends CakeTestModel {
/**
 * name property
 *
 * @var string 'OverallFavorite'
 * @access public
 */
	var $name = 'OverallFavorite';
}
/**
 * MyUser class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class MyUser extends CakeTestModel {
/**
 * name property
 *
 * @var string 'MyUser'
 * @access public
 */
	var $name = 'MyUser';
/**
 * undocumented variable
 *
 * @var string
 * @access public
 */
	var $hasAndBelongsToMany = array('MyCategory');
}
/**
 * MyCategory class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class MyCategory extends CakeTestModel {
/**
 * name property
 *
 * @var string 'MyCategory'
 * @access public
 */
	var $name = 'MyCategory';
/**
 * undocumented variable
 *
 * @var string
 * @access public
 */
	var $hasAndBelongsToMany = array('MyProduct', 'MyUser');
}
/**
 * MyProduct class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class MyProduct extends CakeTestModel {
/**
 * name property
 *
 * @var string 'MyProduct'
 * @access public
 */
	var $name = 'MyProduct';
/**
 * undocumented variable
 *
 * @var string
 * @access public
 */
	var $hasAndBelongsToMany = array('MyCategory');
}
/**
 * MyCategoriesMyUser class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class MyCategoriesMyUser extends CakeTestModel {
/**
 * name property
 *
 * @var string 'MyCategoriesMyUser'
 * @access public
 */
	var $name = 'MyCategoriesMyUser';
}
/**
 * MyCategoriesMyProduct class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class MyCategoriesMyProduct extends CakeTestModel {
/**
 * name property
 *
 * @var string 'MyCategoriesMyProduct'
 * @access public
 */
	var $name = 'MyCategoriesMyProduct';
}
/**
 * I18nModel class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class I18nModel extends CakeTestModel {
/**
 * name property
 *
 * @var string 'I18nModel'
 * @access public
 */
	var $name = 'I18nModel';
/**
 * useTable property
 *
 * @var string 'i18n'
 * @access public
 */
	var $useTable = 'i18n';
/**
 * displayField property
 *
 * @var string 'field'
 * @access public
 */
	var $displayField = 'field';
}
/**
 * NumberTree class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class NumberTree extends CakeTestModel {
/**
 * name property
 *
 * @var string 'NumberTree'
 * @access public
 */
	var $name = 'NumberTree';
/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	var $actsAs = array('Tree');
/**
 * initialize method
 *
 * @param int $levelLimit
 * @param int $childLimit
 * @param mixed $currentLevel
 * @param mixed $parent_id
 * @param string $prefix
 * @param bool $hierachial
 * @access public
 * @return void
 */
	function initialize($levelLimit = 3, $childLimit = 3, $currentLevel = null, $parent_id = null, $prefix = '1', $hierachial = true) {
		if (!$parent_id) {
			$db =& ConnectionManager::getDataSource($this->useDbConfig);
			$db->truncate($this->table);
			$this->save(array($this->name => array('name' => '1. Root')));
			$this->initialize($levelLimit, $childLimit, 1, $this->id, '1', $hierachial);
			$this->create(array());
		}

		if (!$currentLevel || $currentLevel > $levelLimit) {
			return;
		}

		for ($i = 1; $i <= $childLimit; $i++) {
			$name = $prefix . '.' . $i;
			$data = array($this->name => array('name' => $name));
			$this->create($data);

			if ($hierachial) {
				if ($this->name == 'UnconventionalTree') {
					$data[$this->name]['join'] = $parent_id;
				} else {
					$data[$this->name]['parent_id'] = $parent_id;
				}
			}
			$this->save($data);
			$this->initialize($levelLimit, $childLimit, $currentLevel + 1, $this->id, $name, $hierachial);
		}
	}
}
/**
 * NumberTreeTwo class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class NumberTreeTwo extends NumberTree {
/**
 * name property
 *
 * @var string 'NumberTree'
 * @access public
 */
	var $name = 'NumberTreeTwo';
/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	var $actsAs = array();
}
/**
 * FlagTree class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class FlagTree extends NumberTree {
/**
 * name property
 *
 * @var string 'FlagTree'
 * @access public
 */
	var $name = 'FlagTree';
}
/**
 * UnconventionalTree class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class UnconventionalTree extends NumberTree {
/**
 * name property
 *
 * @var string 'FlagTree'
 * @access public
 */
	var $name = 'UnconventionalTree';
	var $actsAs = array(
		'Tree' => array(
			'parent' => 'join',
			'left'  => 'left',
			'right' => 'right'
		)
	);
}
/**
 * UuidTree class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class UuidTree extends NumberTree {
/**
 * name property
 *
 * @var string 'FlagTree'
 * @access public
 */
	var $name = 'UuidTree';
}

/**
 * Campaign class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Campaign extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Campaign'
 * @access public
 */
	var $name = 'Campaign';
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array('Ad' => array('fields' => array('id','campaign_id','name')));
}
/**
 * Ad class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Ad extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Ad'
 * @access public
 */
	var $name = 'Ad';
/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	var $actsAs = array('Tree');
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('Campaign');
}
/**
 * AfterTree class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class AfterTree extends NumberTree {
/**
 * name property
 *
 * @var string 'AfterTree'
 * @access public
 */
	var $name = 'AfterTree';
/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	var $actsAs = array('Tree');

	function afterSave($created) {
		if ($created && isset($this->data['AfterTree'])) {
			$this->data['AfterTree']['name'] = 'Six and One Half Changed in AfterTree::afterSave() but not in database';
		}
	}
}
/**
 * Nonconformant Content class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Content extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Content'
 * @access public
 */
	var $name = 'Content';
/**
 * useTable property
 *
 * @var string 'Content'
 * @access public
 */
	var $useTable = 'Content';
/**
 * primaryKey property
 *
 * @var string 'iContentId'
 * @access public
 */
	var $primaryKey = 'iContentId';
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('Account' => array('className' => 'Account', 'with' => 'ContentAccount', 'joinTable' => 'ContentAccounts', 'foreignKey' => 'iContentId', 'associationForeignKey', 'iAccountId'));
}
/**
 * Nonconformant Account class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Account extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Account'
 * @access public
 */
	var $name = 'Account';
/**
 * useTable property
 *
 * @var string 'Account'
 * @access public
 */
	var $useTable = 'Accounts';
/**
 * primaryKey property
 *
 * @var string 'iAccountId'
 * @access public
 */
	var $primaryKey = 'iAccountId';
}
/**
 * Nonconformant ContentAccount class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class ContentAccount extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Account'
 * @access public
 */
	var $name = 'ContentAccount';
/**
 * useTable property
 *
 * @var string 'Account'
 * @access public
 */
	var $useTable = 'ContentAccounts';
/**
 * primaryKey property
 *
 * @var string 'iAccountId'
 * @access public
 */
	var $primaryKey = 'iContentAccountsId';
}
/**
 * FilmFile class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class FilmFile extends CakeTestModel {
	var $name = 'FilmFile';
}
/**
 * Basket test model
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Basket extends CakeTestModel {
	var $name = 'Basket';

	var $belongsTo = array(
		'FilmFile' => array(
			'className' => 'FilmFile',
			'foreignKey' => 'object_id',
			'conditions' => "Basket.type = 'file'",
			'fields' => '',
			'order' => ''
		)
	);
}
/**
 * TestPluginArticle class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class TestPluginArticle extends CakeTestModel {
/**
 * name property
 *
 * @var string 'TestPluginArticle'
 * @access public
 */
	var $name = 'TestPluginArticle';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('User');
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array(
		'TestPluginComment' => array(
			'className' => 'TestPlugin.TestPluginComment',
			'foreignKey' => 'article_id',
			'dependent' => true
		)
	);
}
/**
 * TestPluginComment class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class TestPluginComment extends CakeTestModel {
/**
 * name property
 *
 * @var string 'TestPluginComment'
 * @access public
 */
	var $name = 'TestPluginComment';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array(
		'TestPluginArticle' => array(
			'className' => 'TestPlugin.TestPluginArticle',
			'foreignKey' => 'article_id',
		),
		'User'
	);
}
/**
 * Uuidportfolio class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Uuidportfolio extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Uuidportfolio'
 * @access public
 */
	var $name = 'Uuidportfolio';
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('Uuiditem');
}
/**
 * Uuiditem class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class Uuiditem extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Item'
 * @access public
 */
	var $name = 'Uuiditem';
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('Uuidportfolio' => array('with' => 'UuiditemsUuidportfolioNumericid'));

}
/**
 * UuiditemsPortfolio class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class UuiditemsUuidportfolio extends CakeTestModel {
/**
 * name property
 *
 * @var string 'ItemsPortfolio'
 * @access public
 */
	var $name = 'UuiditemsUuidportfolio';
}
/**
 * UuiditemsPortfolioNumericid class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class UuiditemsUuidportfolioNumericid extends CakeTestModel {
/**
 * name property
 *
 * @var string
 * @access public
 */
	var $name = 'UuiditemsUuidportfolioNumericid';
}
/**
 * TranslateTestModel class.
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class TranslateTestModel extends CakeTestModel {
/**
 * name property
 *
 * @var string 'TranslateTestModel'
 * @access public
 */
	var $name = 'TranslateTestModel';
/**
 * useTable property
 *
 * @var string 'i18n'
 * @access public
 */
	var $useTable = 'i18n';
/**
 * displayField property
 *
 * @var string 'field'
 * @access public
 */
	var $displayField = 'field';
}
/**
 * TranslateTestModel class.
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class TranslateWithPrefix extends CakeTestModel {
/**
 * name property
 *
 * @var string 'TranslateTestModel'
 * @access public
 */
	var $name = 'TranslateWithPrefix';
/**
 * tablePrefix property
 *
 * @var string 'i18n'
 * @access public
 */
	var $tablePrefix = 'i18n_';
/**
 * displayField property
 *
 * @var string 'field'
 * @access public
 */
	var $displayField = 'field';
}
/**
 * TranslatedItem class.
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class TranslatedItem extends CakeTestModel {
/**
 * name property
 *
 * @var string 'TranslatedItem'
 * @access public
 */
	var $name = 'TranslatedItem';
/**
 * cacheQueries property
 *
 * @var bool false
 * @access public
 */
	var $cacheQueries = false;
/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	var $actsAs = array('Translate' => array('content', 'title'));
/**
 * translateModel property
 *
 * @var string 'TranslateTestModel'
 * @access public
 */
	var $translateModel = 'TranslateTestModel';
}
/**
 * TranslatedItem class.
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class TranslatedItem2 extends CakeTestModel {
/**
 * name property
 *
 * @var string 'TranslatedItem'
 * @access public
 */
	var $name = 'TranslatedItem';
/**
 * cacheQueries property
 *
 * @var bool false
 * @access public
 */
	var $cacheQueries = false;
/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	var $actsAs = array('Translate' => array('content', 'title'));
/**
 * translateModel property
 *
 * @var string 'TranslateTestModel'
 * @access public
 */
	var $translateModel = 'TranslateWithPrefix';
}
/**
 * TranslatedItemWithTable class.
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class TranslatedItemWithTable extends CakeTestModel {
/**
 * name property
 *
 * @var string 'TranslatedItemWithTable'
 * @access public
 */
	var $name = 'TranslatedItemWithTable';
/**
 * useTable property
 *
 * @var string 'translated_items'
 * @access public
 */
	var $useTable = 'translated_items';
/**
 * cacheQueries property
 *
 * @var bool false
 * @access public
 */
	var $cacheQueries = false;
/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	var $actsAs = array('Translate' => array('content', 'title'));
/**
 * translateModel property
 *
 * @var string 'TranslateTestModel'
 * @access public
 */
	var $translateModel = 'TranslateTestModel';
/**
 * translateTable property
 *
 * @var string 'another_i18n'
 * @access public
 */
	var $translateTable = 'another_i18n';
}
/**
 * TranslateArticleModel class.
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class TranslateArticleModel extends CakeTestModel {
/**
 * name property
 *
 * @var string 'TranslateArticleModel'
 * @access public
 */
	var $name = 'TranslateArticleModel';
/**
 * useTable property
 *
 * @var string 'article_i18n'
 * @access public
 */
	var $useTable = 'article_i18n';
/**
 * displayField property
 *
 * @var string 'field'
 * @access public
 */
	var $displayField = 'field';
}
/**
 * TranslatedArticle class.
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model
 */
class TranslatedArticle extends CakeTestModel {
/**
 * name property
 *
 * @var string 'TranslatedArticle'
 * @access public
 */
	var $name = 'TranslatedArticle';
/**
 * cacheQueries property
 *
 * @var bool false
 * @access public
 */
	var $cacheQueries = false;
/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	var $actsAs = array('Translate' => array('title', 'body'));
/**
 * translateModel property
 *
 * @var string 'TranslateArticleModel'
 * @access public
 */
	var $translateModel = 'TranslateArticleModel';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('User');
}

class CounterCacheUser extends CakeTestModel {
	var $name = 'CounterCacheUser';
	var $alias = 'User';

	var $hasMany = array('Post' => array(
		'className' => 'CounterCachePost',
		'foreignKey' => 'user_id'
	));
}

class CounterCachePost extends CakeTestModel {
	var $name = 'CounterCachePost';
	var $alias = 'Post';

	var $belongsTo = array('User' => array(
		'className' => 'CounterCacheUser',
		'foreignKey' => 'user_id',
		'counterCache' => true
	));
}

class CounterCacheUserNonstandardPrimaryKey extends CakeTestModel {
	var $name = 'CounterCacheUserNonstandardPrimaryKey';
	var $alias = 'User';
    var $primaryKey = 'uid';

	var $hasMany = array('Post' => array(
		'className' => 'CounterCachePostNonstandardPrimaryKey',
		'foreignKey' => 'uid'
	));
}

class CounterCachePostNonstandardPrimaryKey extends CakeTestModel {
	var $name = 'CounterCachePostNonstandardPrimaryKey';
	var $alias = 'Post';
    var $primaryKey = 'pid';

	var $belongsTo = array('User' => array(
		'className' => 'CounterCacheUserNonstandardPrimaryKey',
		'foreignKey' => 'uid',
		'counterCache' => true
	));
}

class ArticleB extends CakeTestModel {
	var $name = 'ArticleB';
	var $useTable = 'articles';
	var $hasAndBelongsToMany = array(
		'TagB' => array(
			'className' => 'TagB',
			'joinTable' => 'articles_tags',
			'foreignKey' => 'article_id',
			'associationForeignKey' => 'tag_id'
		)
	);
}

class TagB extends CakeTestModel {
	var $name = 'TagB';
	var $useTable = 'tags';
	var $hasAndBelongsToMany = array(
		'ArticleB' => array(
			'className' => 'ArticleB',
			'joinTable' => 'articles_tags',
			'foreignKey' => 'tag_id',
			'associationForeignKey' => 'article_id'
		)
	);
}

class Fruit extends CakeTestModel {
	var $name = 'Fruit';
	var $hasAndBelongsToMany = array(
		'UuidTag' => array(
			'className' => 'UuidTag',
			'joinTable' => 'fruits_uuid_tags',
			'foreignKey' => 'fruit_id',
			'associationForeignKey' => 'uuid_tag_id',
			'with' => 'FruitsUuidTag'
		)
	);
}

class FruitsUuidTag extends CakeTestModel {
	var $name = 'FruitsUuidTag';
	var $primaryKey = false;
	var $belongsTo = array(
		'UuidTag' => array(
			'className' => 'UuidTag',
			'foreignKey' => 'uuid_tag_id',
		),
		'Fruit' => array(
			'className' => 'Fruit',
			'foreignKey' => 'fruit_id',
		)
	);
}

class UuidTag extends CakeTestModel {
	var $name = 'UuidTag';
	var $hasAndBelongsToMany = array(
		'Fruit' => array(
			'className' => 'Fruit',
			'joinTable' => 'fruits_uuid_tags',
			'foreign_key' => 'uuid_tag_id',
			'associationForeignKey' => 'fruit_id',
			'with' => 'FruitsUuidTag'
		)
	);
}

class FruitNoWith extends CakeTestModel {
	var $name = 'Fruit';
	var $useTable = 'fruits';
	var $hasAndBelongsToMany = array(
		'UuidTag' => array(
			'className' => 'UuidTagNoWith',
			'joinTable' => 'fruits_uuid_tags',
			'foreignKey' => 'fruit_id',
			'associationForeignKey' => 'uuid_tag_id',
		)
	);
}
class UuidTagNoWith extends CakeTestModel {
	var $name = 'UuidTag';
	var $useTable = 'uuid_tags';
	var $hasAndBelongsToMany = array(
		'Fruit' => array(
			'className' => 'FruitNoWith',
			'joinTable' => 'fruits_uuid_tags',
			'foreign_key' => 'uuid_tag_id',
			'associationForeignKey' => 'fruit_id',
		)
	);
}

class ProductUpdateAll extends CakeTestModel {
	var $name = 'ProductUpdateAll';
	var $useTable = 'product_update_all';

}

class GroupUpdateAll extends CakeTestModel {
	var $name = 'GroupUpdateAll';
	var $useTable = 'group_update_all';

}

?>