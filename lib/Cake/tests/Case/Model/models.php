<?php
/**
 * Mock models file
 *
 * Mock classes for use in Model and related test cases
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs.model
 * @since         CakePHP(tm) v 1.2.0.6464
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'DEFAULT');

/**
 * Test class
 *
 * @package       cake.tests.cases.libs.model
 */
class Test extends CakeTestModel {

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * name property
 *
 * @var string 'Test'
 * @access public
 */
	public $name = 'Test';

/**
 * schema property
 *
 * @var array
 * @access protected
 */
	protected $_schema = array(
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
 * @package       cake.tests.cases.libs.model
 */
class TestAlias extends CakeTestModel {

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * name property
 *
 * @var string 'TestAlias'
 * @access public
 */
	public $name = 'TestAlias';

/**
 * alias property
 *
 * @var string 'TestAlias'
 * @access public
 */
	public $alias = 'TestAlias';

/**
 * schema property
 *
 * @var array
 * @access protected
 */
	protected $_schema = array(
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
 * @package       cake.tests.cases.libs.model
 */
class TestValidate extends CakeTestModel {

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * name property
 *
 * @var string 'TestValidate'
 * @access public
 */
	public $name = 'TestValidate';

/**
 * schema property
 *
 * @var array
 * @access protected
 */
	protected $_schema = array(
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
		return (!empty($value) && strpos(strtolower($value['title']), 'title-') === 0);
	}
}

/**
 * User class
 *
 * @package       cake.tests.cases.libs.model
 */
class User extends CakeTestModel {

/**
 * name property
 *
 * @var string 'User'
 * @access public
 */
	public $name = 'User';

/**
 * validate property
 *
 * @var array
 * @access public
 */
	public $validate = array('user' => 'notEmpty', 'password' => 'notEmpty');
}

/**
 * Article class
 *
 * @package       cake.tests.cases.libs.model
 */
class Article extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Article'
 * @access public
 */
	public $name = 'Article';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('User');

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array('Comment' => array('dependent' => true));

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('Tag');

/**
 * validate property
 *
 * @var array
 * @access public
 */
	public $validate = array('user_id' => 'numeric', 'title' => array('allowEmpty' => false, 'rule' => 'notEmpty'), 'body' => 'notEmpty');

/**
 * beforeSaveReturn property
 *
 * @var bool true
 * @access public
 */
	public $beforeSaveReturn = true;

/**
 * beforeSave method
 *
 * @access public
 * @return void
 */
	function beforeSave($options = array()) {
		return $this->beforeSaveReturn;
	}

/**
 * titleDuplicate method
 *
 * @param mixed $title
 * @access public
 * @return void
 */
	static function titleDuplicate ($title) {
		if ($title === 'My Article Title') {
			return false;
		}
		return true;
	}
}

/**
 * Model stub for beforeDelete testing
 *
 * @see #250
 * @package cake.tests
 */
class BeforeDeleteComment extends CakeTestModel {
	var $name = 'BeforeDeleteComment';

	var $useTable = 'comments';

	function beforeDelete($cascade = true) {
		$db = $this->getDataSource();
		$db->delete($this, array($this->alias . '.' . $this->primaryKey => array(1, 3)));
		return true;
	}
}

/**
 * NumericArticle class
 *
 * @package       cake.tests.cases.libs.model
 */
class NumericArticle extends CakeTestModel {

/**
 * name property
 *
 * @var string 'NumericArticle'
 * @access public
 */
	public $name = 'NumericArticle';

/**
 * useTable property
 *
 * @var string 'numeric_articles'
 * @access public
 */
	public $useTable = 'numeric_articles';
}

/**
 * Article10 class
 *
 * @package       cake.tests.cases.libs.model
 */
class Article10 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Article10'
 * @access public
 */
	public $name = 'Article10';

/**
 * useTable property
 *
 * @var string 'articles'
 * @access public
 */
	public $useTable = 'articles';

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array('Comment' => array('dependent' => true, 'exclusive' => true));
}

/**
 * ArticleFeatured class
 *
 * @package       cake.tests.cases.libs.model
 */
class ArticleFeatured extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ArticleFeatured'
 * @access public
 */
	public $name = 'ArticleFeatured';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('User', 'Category');

/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	public $hasOne = array('Featured');

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array('Comment' => array('className' => 'Comment', 'dependent' => true));

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('Tag');

/**
 * validate property
 *
 * @var array
 * @access public
 */
	public $validate = array('user_id' => 'numeric', 'title' => 'notEmpty', 'body' => 'notEmpty');
}

/**
 * Featured class
 *
 * @package       cake.tests.cases.libs.model
 */
class Featured extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Featured'
 * @access public
 */
	public $name = 'Featured';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('ArticleFeatured', 'Category');
}

/**
 * Tag class
 *
 * @package       cake.tests.cases.libs.model
 */
class Tag extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Tag'
 * @access public
 */
	public $name = 'Tag';
}

/**
 * ArticlesTag class
 *
 * @package       cake.tests.cases.libs.model
 */
class ArticlesTag extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ArticlesTag'
 * @access public
 */
	public $name = 'ArticlesTag';
}

/**
 * ArticleFeaturedsTag class
 *
 * @package       cake.tests.cases.libs.model
 */
class ArticleFeaturedsTag extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ArticleFeaturedsTag'
 * @access public
 */
	public $name = 'ArticleFeaturedsTag';
}

/**
 * Comment class
 *
 * @package       cake.tests.cases.libs.model
 */
class Comment extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Comment'
 * @access public
 */
	public $name = 'Comment';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('Article', 'User');

/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	public $hasOne = array('Attachment' => array('dependent' => true));
}

/**
 * Modified Comment Class has afterFind Callback
 *
 * @package       cake.tests.cases.libs.model
 */
class ModifiedComment extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Comment'
 * @access public
 */
	public $name = 'Comment';

/**
 * useTable property
 *
 * @var string 'comments'
 * @access public
 */
	public $useTable = 'comments';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('Article');

/**
 * afterFind callback
 *
 * @return void
 */
	function afterFind($results, $primary = false) {
		if (isset($results[0])) {
			$results[0]['Comment']['callback'] = 'Fire';
		}
		return $results;
	}
}

/**
 * Modified Comment Class has afterFind Callback
 *
 * @package       cake.tests.cases.libs.model
 */
class AgainModifiedComment extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Comment'
 * @access public
 */
	public $name = 'Comment';

/**
 * useTable property
 *
 * @var string 'comments'
 * @access public
 */
	public $useTable = 'comments';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('Article');

/**
 * afterFind callback
 *
 * @return void
 */
	function afterFind($results, $primary = false) {
		if (isset($results[0])) {
			$results[0]['Comment']['querytype'] = $this->findQueryType;
		}
		return $results;
	}
}

/**
 * MergeVarPluginAppModel class
 *
 * @package       cake.tests.cases.libs.model
 */
class MergeVarPluginAppModel extends AppModel {

/**
 * actsAs parameter
 *
 * @var array
 */
	public $actsAs = array(
		'Containable'
	);
}

/**
 * MergeVarPluginPost class
 *
 * @package       cake.tests.cases.libs.model
 */
class MergeVarPluginPost extends MergeVarPluginAppModel {

/**
 * actsAs parameter
 *
 * @var array
 */
	public $actsAs = array(
		'Tree'
	);

/**
 * useTable parameter
 *
 * @var string
 */
	public $useTable = 'posts';
}

/**
 * MergeVarPluginComment class
 *
 * @package       cake.tests.cases.libs.model
 */
class MergeVarPluginComment extends MergeVarPluginAppModel {

/**
 * actsAs parameter
 *
 * @var array
 */
	public $actsAs = array(
		'Containable' => array('some_settings')
	);

/**
 * useTable parameter
 *
 * @var string
 */
	public $useTable = 'comments';
}


/**
 * Attachment class
 *
 * @package       cake.tests.cases.libs.model
 */
class Attachment extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Attachment'
 * @access public
 */
	public $name = 'Attachment';
}

/**
 * Category class
 *
 * @package       cake.tests.cases.libs.model
 */
class Category extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Category'
 * @access public
 */
	public $name = 'Category';
}

/**
 * CategoryThread class
 *
 * @package       cake.tests.cases.libs.model
 */
class CategoryThread extends CakeTestModel {

/**
 * name property
 *
 * @var string 'CategoryThread'
 * @access public
 */
	public $name = 'CategoryThread';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('ParentCategory' => array('className' => 'CategoryThread', 'foreignKey' => 'parent_id'));
}

/**
 * Apple class
 *
 * @package       cake.tests.cases.libs.model
 */
class Apple extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Apple'
 * @access public
 */
	public $name = 'Apple';

/**
 * validate property
 *
 * @var array
 * @access public
 */
	public $validate = array('name' => 'notEmpty');

/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	public $hasOne = array('Sample');

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array('Child' => array('className' => 'Apple', 'dependent' => true));

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('Parent' => array('className' => 'Apple', 'foreignKey' => 'apple_id'));
}

/**
 * Sample class
 *
 * @package       cake.tests.cases.libs.model
 */
class Sample extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Sample'
 * @access public
 */
	public $name = 'Sample';

/**
 * belongsTo property
 *
 * @var string 'Apple'
 * @access public
 */
	public $belongsTo = 'Apple';
}

/**
 * AnotherArticle class
 *
 * @package       cake.tests.cases.libs.model
 */
class AnotherArticle extends CakeTestModel {

/**
 * name property
 *
 * @var string 'AnotherArticle'
 * @access public
 */
	public $name = 'AnotherArticle';

/**
 * hasMany property
 *
 * @var string 'Home'
 * @access public
 */
	public $hasMany = 'Home';
}

/**
 * Advertisement class
 *
 * @package       cake.tests.cases.libs.model
 */
class Advertisement extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Advertisement'
 * @access public
 */
	public $name = 'Advertisement';

/**
 * hasMany property
 *
 * @var string 'Home'
 * @access public
 */
	public $hasMany = 'Home';
}

/**
 * Home class
 *
 * @package       cake.tests.cases.libs.model
 */
class Home extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Home'
 * @access public
 */
	public $name = 'Home';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('AnotherArticle', 'Advertisement');
}

/**
 * Post class
 *
 * @package       cake.tests.cases.libs.model
 */
class Post extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Post'
 * @access public
 */
	public $name = 'Post';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('Author');

	function beforeFind($queryData) {
		if (isset($queryData['connection'])) {
			$this->useDbConfig = $queryData['connection'];
		}
		return true;
	}

	function afterFind($results, $primary = false) {
		$this->useDbConfig = 'test';
		return $results;
	}
}

/**
 * Author class
 *
 * @package       cake.tests.cases.libs.model
 */
class Author extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Author'
 * @access public
 */
	public $name = 'Author';

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array('Post');

/**
 * afterFind method
 *
 * @param mixed $results
 * @access public
 * @return void
 */
	function afterFind($results, $primary = false) {
		$results[0]['Author']['test'] = 'working';
		return $results;
	}
}

/**
 * ModifiedAuthor class
 *
 * @package       cake.tests.cases.libs.model
 */
class ModifiedAuthor extends Author {

/**
 * name property
 *
 * @var string 'Author'
 * @access public
 */
	public $name = 'Author';

/**
 * afterFind method
 *
 * @param mixed $results
 * @access public
 * @return void
 */
	function afterFind($results, $primary = false) {
		foreach($results as $index => $result) {
			$results[$index]['Author']['user'] .= ' (CakePHP)';
		}
		return $results;
	}
}

/**
 * Project class
 *
 * @package       cake.tests.cases.libs.model
 */
class Project extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Project'
 * @access public
 */
	public $name = 'Project';

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array('Thread');
}

/**
 * Thread class
 *
 * @package       cake.tests.cases.libs.model
 */
class Thread extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Thread'
 * @access public
 */
	public $name = 'Thread';

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('Project');

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array('Message');
}

/**
 * Message class
 *
 * @package       cake.tests.cases.libs.model
 */
class Message extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Message'
 * @access public
 */
	public $name = 'Message';

/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	public $hasOne = array('Bid');
}

/**
 * Bid class
 *
 * @package       cake.tests.cases.libs.model
 */
class Bid extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Bid'
 * @access public
 */
	public $name = 'Bid';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('Message');
}

/**
 * NodeAfterFind class
 *
 * @package       cake.tests.cases.libs.model
 */
class NodeAfterFind extends CakeTestModel {

/**
 * name property
 *
 * @var string 'NodeAfterFind'
 * @access public
 */
	public $name = 'NodeAfterFind';

/**
 * validate property
 *
 * @var array
 * @access public
 */
	public $validate = array('name' => 'notEmpty');

/**
 * useTable property
 *
 * @var string 'apples'
 * @access public
 */
	public $useTable = 'apples';

/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	public $hasOne = array('Sample' => array('className' => 'NodeAfterFindSample'));

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array('Child' => array('className' => 'NodeAfterFind', 'dependent' => true));

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('Parent' => array('className' => 'NodeAfterFind', 'foreignKey' => 'apple_id'));

/**
 * afterFind method
 *
 * @param mixed $results
 * @access public
 * @return void
 */
	function afterFind($results, $primary = false) {
		return $results;
	}
}

/**
 * NodeAfterFindSample class
 *
 * @package       cake.tests.cases.libs.model
 */
class NodeAfterFindSample extends CakeTestModel {

/**
 * name property
 *
 * @var string 'NodeAfterFindSample'
 * @access public
 */
	public $name = 'NodeAfterFindSample';

/**
 * useTable property
 *
 * @var string 'samples'
 * @access public
 */
	public $useTable = 'samples';

/**
 * belongsTo property
 *
 * @var string 'NodeAfterFind'
 * @access public
 */
	public $belongsTo = 'NodeAfterFind';
}

/**
 * NodeNoAfterFind class
 *
 * @package       cake.tests.cases.libs.model
 */
class NodeNoAfterFind extends CakeTestModel {

/**
 * name property
 *
 * @var string 'NodeAfterFind'
 * @access public
 */
	public $name = 'NodeAfterFind';

/**
 * validate property
 *
 * @var array
 * @access public
 */
	public $validate = array('name' => 'notEmpty');

/**
 * useTable property
 *
 * @var string 'apples'
 * @access public
 */
	public $useTable = 'apples';

/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	public $hasOne = array('Sample' => array('className' => 'NodeAfterFindSample'));

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array('Child' => array('className' => 'NodeAfterFind', 'dependent' => true));

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('Parent' => array('className' => 'NodeAfterFind', 'foreignKey' => 'apple_id'));
}

/**
 * Node class
 *
 * @package       cake.tests.cases.libs.model
 */
class Node extends CakeTestModel{

/**
 * name property
 *
 * @var string 'Node'
 * @access public
 */
	public $name = 'Node';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array(
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
 * @package       cake.tests.cases.libs.model
 */
class Dependency extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Dependency'
 * @access public
 */
	public $name = 'Dependency';
}

/**
 * ModelA class
 *
 * @package       cake.tests.cases.libs.model
 */
class ModelA extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ModelA'
 * @access public
 */
	public $name = 'ModelA';

/**
 * useTable property
 *
 * @var string 'apples'
 * @access public
 */
	public $useTable = 'apples';

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array('ModelB', 'ModelC');
}

/**
 * ModelB class
 *
 * @package       cake.tests.cases.libs.model
 */
class ModelB extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ModelB'
 * @access public
 */
	public $name = 'ModelB';

/**
 * useTable property
 *
 * @var string 'messages'
 * @access public
 */
	public $useTable = 'messages';

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array('ModelD');
}

/**
 * ModelC class
 *
 * @package       cake.tests.cases.libs.model
 */
class ModelC extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ModelC'
 * @access public
 */
	public $name = 'ModelC';

/**
 * useTable property
 *
 * @var string 'bids'
 * @access public
 */
	public $useTable = 'bids';

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array('ModelD');
}

/**
 * ModelD class
 *
 * @package       cake.tests.cases.libs.model
 */
class ModelD extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ModelD'
 * @access public
 */
	public $name = 'ModelD';

/**
 * useTable property
 *
 * @var string 'threads'
 * @access public
 */
	public $useTable = 'threads';
}

/**
 * Something class
 *
 * @package       cake.tests.cases.libs.model
 */
class Something extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Something'
 * @access public
 */
	public $name = 'Something';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('SomethingElse' => array('with' => array('JoinThing' => array('doomed'))));
}

/**
 * SomethingElse class
 *
 * @package       cake.tests.cases.libs.model
 */
class SomethingElse extends CakeTestModel {

/**
 * name property
 *
 * @var string 'SomethingElse'
 * @access public
 */
	public $name = 'SomethingElse';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('Something' => array('with' => 'JoinThing'));
}

/**
 * JoinThing class
 *
 * @package       cake.tests.cases.libs.model
 */
class JoinThing extends CakeTestModel {

/**
 * name property
 *
 * @var string 'JoinThing'
 * @access public
 */
	public $name = 'JoinThing';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('Something', 'SomethingElse');
}

/**
 * Portfolio class
 *
 * @package       cake.tests.cases.libs.model
 */
class Portfolio extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Portfolio'
 * @access public
 */
	public $name = 'Portfolio';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('Item');
}

/**
 * Item class
 *
 * @package       cake.tests.cases.libs.model
 */
class Item extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Item'
 * @access public
 */
	public $name = 'Item';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('Syfile' => array('counterCache' => true));

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('Portfolio' => array('unique' => false));
}

/**
 * ItemsPortfolio class
 *
 * @package       cake.tests.cases.libs.model
 */
class ItemsPortfolio extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ItemsPortfolio'
 * @access public
 */
	public $name = 'ItemsPortfolio';
}

/**
 * Syfile class
 *
 * @package       cake.tests.cases.libs.model
 */
class Syfile extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Syfile'
 * @access public
 */
	public $name = 'Syfile';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('Image');
}

/**
 * Image class
 *
 * @package       cake.tests.cases.libs.model
 */
class Image extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Image'
 * @access public
 */
	public $name = 'Image';
}

/**
 * DeviceType class
 *
 * @package       cake.tests.cases.libs.model
 */
class DeviceType extends CakeTestModel {

/**
 * name property
 *
 * @var string 'DeviceType'
 * @access public
 */
	public $name = 'DeviceType';

/**
 * order property
 *
 * @var array
 * @access public
 */
	public $order = array('DeviceType.order' => 'ASC');

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array(
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
	public $hasMany = array('Device' => array('order' => array('Device.id' => 'ASC')));
}

/**
 * DeviceTypeCategory class
 *
 * @package       cake.tests.cases.libs.model
 */
class DeviceTypeCategory extends CakeTestModel {

/**
 * name property
 *
 * @var string 'DeviceTypeCategory'
 * @access public
 */
	public $name = 'DeviceTypeCategory';
}

/**
 * FeatureSet class
 *
 * @package       cake.tests.cases.libs.model
 */
class FeatureSet extends CakeTestModel {

/**
 * name property
 *
 * @var string 'FeatureSet'
 * @access public
 */
	public $name = 'FeatureSet';
}

/**
 * ExteriorTypeCategory class
 *
 * @package       cake.tests.cases.libs.model
 */
class ExteriorTypeCategory extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ExteriorTypeCategory'
 * @access public
 */
	public $name = 'ExteriorTypeCategory';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('Image' => array('className' => 'Device'));
}

/**
 * Document class
 *
 * @package       cake.tests.cases.libs.model
 */
class Document extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Document'
 * @access public
 */
	public $name = 'Document';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('DocumentDirectory');
}

/**
 * Device class
 *
 * @package       cake.tests.cases.libs.model
 */
class Device extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Device'
 * @access public
 */
	public $name = 'Device';
}

/**
 * DocumentDirectory class
 *
 * @package       cake.tests.cases.libs.model
 */
class DocumentDirectory extends CakeTestModel {

/**
 * name property
 *
 * @var string 'DocumentDirectory'
 * @access public
 */
	public $name = 'DocumentDirectory';
}

/**
 * PrimaryModel class
 *
 * @package       cake.tests.cases.libs.model
 */
class PrimaryModel extends CakeTestModel {

/**
 * name property
 *
 * @var string 'PrimaryModel'
 * @access public
 */
	public $name = 'PrimaryModel';
}

/**
 * SecondaryModel class
 *
 * @package       cake.tests.cases.libs.model
 */
class SecondaryModel extends CakeTestModel {

/**
 * name property
 *
 * @var string 'SecondaryModel'
 * @access public
 */
	public $name = 'SecondaryModel';
}

/**
 * JoinA class
 *
 * @package       cake.tests.cases.libs.model
 */
class JoinA extends CakeTestModel {

/**
 * name property
 *
 * @var string 'JoinA'
 * @access public
 */
	public $name = 'JoinA';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('JoinB', 'JoinC');
}

/**
 * JoinB class
 *
 * @package       cake.tests.cases.libs.model
 */
class JoinB extends CakeTestModel {

/**
 * name property
 *
 * @var string 'JoinB'
 * @access public
 */
	public $name = 'JoinB';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('JoinA');
}

/**
 * JoinC class
 *
 * @package       cake.tests.cases.libs.model
 */
class JoinC extends CakeTestModel {

/**
 * name property
 *
 * @var string 'JoinC'
 * @access public
 */
	public $name = 'JoinC';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('JoinA');
}

/**
 * ThePaper class
 *
 * @package       cake.tests.cases.libs.model
 */
class ThePaper extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ThePaper'
 * @access public
 */
	public $name = 'ThePaper';

/**
 * useTable property
 *
 * @var string 'apples'
 * @access public
 */
	public $useTable = 'apples';

/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	public $hasOne = array('Itself' => array('className' => 'ThePaper', 'foreignKey' => 'apple_id'));

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('Monkey' => array('joinTable' => 'the_paper_monkies', 'order' => 'id'));
}

/**
 * Monkey class
 *
 * @package       cake.tests.cases.libs.model
 */
class Monkey extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Monkey'
 * @access public
 */
	public $name = 'Monkey';

/**
 * useTable property
 *
 * @var string 'devices'
 * @access public
 */
	public $useTable = 'devices';
}

/**
 * AssociationTest1 class
 *
 * @package       cake.tests.cases.libs.model
 */
class AssociationTest1 extends CakeTestModel {

/**
 * useTable property
 *
 * @var string 'join_as'
 * @access public
 */
	public $useTable = 'join_as';

/**
 * name property
 *
 * @var string 'AssociationTest1'
 * @access public
 */
	public $name = 'AssociationTest1';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('AssociationTest2' => array(
		'unique' => false, 'joinTable' => 'join_as_join_bs', 'foreignKey' => false
	));
}

/**
 * AssociationTest2 class
 *
 * @package       cake.tests.cases.libs.model
 */
class AssociationTest2 extends CakeTestModel {

/**
 * useTable property
 *
 * @var string 'join_bs'
 * @access public
 */
	public $useTable = 'join_bs';

/**
 * name property
 *
 * @var string 'AssociationTest2'
 * @access public
 */
	public $name = 'AssociationTest2';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('AssociationTest1' => array(
		'unique' => false, 'joinTable' => 'join_as_join_bs'
	));
}

/**
 * Callback class
 *
 * @package       cake.tests.cases.libs.model
 */
class Callback extends CakeTestModel {

}
/**
 * CallbackPostTestModel class
 *
 * @package       cake.tests.cases.libs.model
 */
class CallbackPostTestModel extends CakeTestModel {
	public $useTable = 'posts';
/**
 * variable to control return of beforeValidate
 *
 * @var string
 */
	public $beforeValidateReturn = true;
/**
 * variable to control return of beforeSave
 *
 * @var string
 */
	public $beforeSaveReturn = true;
/**
 * variable to control return of beforeDelete
 *
 * @var string
 */
	public $beforeDeleteReturn = true;
/**
 * beforeSave callback
 *
 * @return void
 */
	function beforeSave($options = array()) {
		return $this->beforeSaveReturn;
	}
/**
 * beforeValidate callback
 *
 * @return void
 */
	function beforeValidate($options = array()) {
		return $this->beforeValidateReturn;
	}
/**
 * beforeDelete callback
 *
 * @return void
 */
	function beforeDelete($cascade = true) {
		return $this->beforeDeleteReturn;
	}
}

/**
 * Uuid class
 *
 * @package       cake.tests.cases.libs.model
 */
class Uuid extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Uuid'
 * @access public
 */
	public $name = 'Uuid';
}

/**
 * DataTest class
 *
 * @package       cake.tests.cases.libs.model
 */
class DataTest extends CakeTestModel {

/**
 * name property
 *
 * @var string 'DataTest'
 * @access public
 */
	public $name = 'DataTest';
}

/**
 * TheVoid class
 *
 * @package       cake.tests.cases.libs.model
 */
class TheVoid extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TheVoid'
 * @access public
 */
	public $name = 'TheVoid';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;
}

/**
 * ValidationTest1 class
 *
 * @package       cake.tests.cases.libs.model
 */
class ValidationTest1 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ValidationTest'
 * @access public
 */
	public $name = 'ValidationTest1';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * schema property
 *
 * @var array
 * @access protected
 */
	protected $_schema = array();

/**
 * validate property
 *
 * @var array
 * @access public
 */
	public $validate = array(
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
 * @package       cake.tests.cases.libs.model
 */
class ValidationTest2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ValidationTest2'
 * @access public
 */
	public $name = 'ValidationTest2';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * validate property
 *
 * @var array
 * @access public
 */
	public $validate = array(
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
	public function schema($field = false) {
		return array();
	}
}

/**
 * Person class
 *
 * @package       cake.tests.cases.libs.model
 */
class Person extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Person'
 * @access public
 */
	public $name = 'Person';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array(
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
 * @package       cake.tests.cases.libs.model
 */
class UnderscoreField extends CakeTestModel {

/**
 * name property
 *
 * @var string 'UnderscoreField'
 * @access public
 */
	public $name = 'UnderscoreField';
}

/**
 * Product class
 *
 * @package       cake.tests.cases.libs.model
 */
class Product extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Product'
 * @access public
 */
	public $name = 'Product';
}

/**
 * Story class
 *
 * @package       cake.tests.cases.libs.model
 */
class Story extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Story'
 * @access public
 */
	public $name = 'Story';

/**
 * primaryKey property
 *
 * @var string 'story'
 * @access public
 */
	public $primaryKey = 'story';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('Tag' => array('foreignKey' => 'story'));

/**
 * validate property
 *
 * @var array
 * @access public
 */
	public $validate = array('title' => 'notEmpty');
}

/**
 * Cd class
 *
 * @package       cake.tests.cases.libs.model
 */
class Cd extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Cd'
 * @access public
 */
	public $name = 'Cd';

/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	public $hasOne = array('OverallFavorite' => array('foreignKey' => 'model_id', 'dependent' => true, 'conditions' => array('model_type' => 'Cd')));
}

/**
 * Book class
 *
 * @package       cake.tests.cases.libs.model
 */
class Book extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Book'
 * @access public
 */
	public $name = 'Book';

/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	public $hasOne = array('OverallFavorite' => array('foreignKey' => 'model_id', 'dependent' => true, 'conditions' => 'OverallFavorite.model_type = \'Book\''));
}

/**
 * OverallFavorite class
 *
 * @package       cake.tests.cases.libs.model
 */
class OverallFavorite extends CakeTestModel {

/**
 * name property
 *
 * @var string 'OverallFavorite'
 * @access public
 */
	public $name = 'OverallFavorite';
}

/**
 * MyUser class
 *
 * @package       cake.tests.cases.libs.model
 */
class MyUser extends CakeTestModel {

/**
 * name property
 *
 * @var string 'MyUser'
 * @access public
 */
	public $name = 'MyUser';

/**
 * undocumented variable
 *
 * @var string
 * @access public
 */
	public $hasAndBelongsToMany = array('MyCategory');
}

/**
 * MyCategory class
 *
 * @package       cake.tests.cases.libs.model
 */
class MyCategory extends CakeTestModel {

/**
 * name property
 *
 * @var string 'MyCategory'
 * @access public
 */
	public $name = 'MyCategory';

/**
 * undocumented variable
 *
 * @var string
 * @access public
 */
	public $hasAndBelongsToMany = array('MyProduct', 'MyUser');
}

/**
 * MyProduct class
 *
 * @package       cake.tests.cases.libs.model
 */
class MyProduct extends CakeTestModel {

/**
 * name property
 *
 * @var string 'MyProduct'
 * @access public
 */
	public $name = 'MyProduct';

/**
 * undocumented variable
 *
 * @var string
 * @access public
 */
	public $hasAndBelongsToMany = array('MyCategory');
}

/**
 * MyCategoriesMyUser class
 *
 * @package       cake.tests.cases.libs.model
 */
class MyCategoriesMyUser extends CakeTestModel {

/**
 * name property
 *
 * @var string 'MyCategoriesMyUser'
 * @access public
 */
	public $name = 'MyCategoriesMyUser';
}

/**
 * MyCategoriesMyProduct class
 *
 * @package       cake.tests.cases.libs.model
 */
class MyCategoriesMyProduct extends CakeTestModel {

/**
 * name property
 *
 * @var string 'MyCategoriesMyProduct'
 * @access public
 */
	public $name = 'MyCategoriesMyProduct';
}


/**
 * NumberTree class
 *
 * @package       cake.tests.cases.libs.model
 */
class NumberTree extends CakeTestModel {

/**
 * name property
 *
 * @var string 'NumberTree'
 * @access public
 */
	public $name = 'NumberTree';

/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	public $actsAs = array('Tree');

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
			$db = ConnectionManager::getDataSource($this->useDbConfig);
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
 * @package       cake.tests.cases.libs.model
 */
class NumberTreeTwo extends NumberTree {

/**
 * name property
 *
 * @var string 'NumberTree'
 * @access public
 */
	public $name = 'NumberTreeTwo';

/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	public $actsAs = array();
}

/**
 * FlagTree class
 *
 * @package       cake.tests.cases.libs.model
 */
class FlagTree extends NumberTree {

/**
 * name property
 *
 * @var string 'FlagTree'
 * @access public
 */
	public $name = 'FlagTree';
}

/**
 * UnconventionalTree class
 *
 * @package       cake.tests.cases.libs.model
 */
class UnconventionalTree extends NumberTree {

/**
 * name property
 *
 * @var string 'FlagTree'
 * @access public
 */
	public $name = 'UnconventionalTree';
	public $actsAs = array(
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
 * @package       cake.tests.cases.libs.model
 */
class UuidTree extends NumberTree {

/**
 * name property
 *
 * @var string 'FlagTree'
 * @access public
 */
	public $name = 'UuidTree';
}

/**
 * Campaign class
 *
 * @package       cake.tests.cases.libs.model
 */
class Campaign extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Campaign'
 * @access public
 */
	public $name = 'Campaign';

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array('Ad' => array('fields' => array('id','campaign_id','name')));
}

/**
 * Ad class
 *
 * @package       cake.tests.cases.libs.model
 */
class Ad extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Ad'
 * @access public
 */
	public $name = 'Ad';

/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	public $actsAs = array('Tree');

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('Campaign');
}

/**
 * AfterTree class
 *
 * @package       cake.tests.cases.libs.model
 */
class AfterTree extends NumberTree {

/**
 * name property
 *
 * @var string 'AfterTree'
 * @access public
 */
	public $name = 'AfterTree';

/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	public $actsAs = array('Tree');

	function afterSave($created) {
		if ($created && isset($this->data['AfterTree'])) {
			$this->data['AfterTree']['name'] = 'Six and One Half Changed in AfterTree::afterSave() but not in database';
		}
	}
}

/**
 * Nonconformant Content class
 *
 * @package       cake.tests.cases.libs.model
 */
class Content extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Content'
 * @access public
 */
	public $name = 'Content';

/**
 * useTable property
 *
 * @var string 'Content'
 * @access public
 */
	public $useTable = 'Content';

/**
 * primaryKey property
 *
 * @var string 'iContentId'
 * @access public
 */
	public $primaryKey = 'iContentId';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('Account' => array('className' => 'Account', 'with' => 'ContentAccount', 'joinTable' => 'ContentAccounts', 'foreignKey' => 'iContentId', 'associationForeignKey', 'iAccountId'));
}

/**
 * Nonconformant Account class
 *
 * @package       cake.tests.cases.libs.model
 */
class Account extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Account'
 * @access public
 */
	public $name = 'Account';

/**
 * useTable property
 *
 * @var string 'Account'
 * @access public
 */
	public $useTable = 'Accounts';

/**
 * primaryKey property
 *
 * @var string 'iAccountId'
 * @access public
 */
	public $primaryKey = 'iAccountId';
}

/**
 * Nonconformant ContentAccount class
 *
 * @package       cake.tests.cases.libs.model
 */
class ContentAccount extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Account'
 * @access public
 */
	public $name = 'ContentAccount';

/**
 * useTable property
 *
 * @var string 'Account'
 * @access public
 */
	public $useTable = 'ContentAccounts';

/**
 * primaryKey property
 *
 * @var string 'iAccountId'
 * @access public
 */
	public $primaryKey = 'iContentAccountsId';
}

/**
 * FilmFile class
 *
 * @package       cake.tests.cases.libs.model
 */
class FilmFile extends CakeTestModel {
	public $name = 'FilmFile';
}

/**
 * Basket test model
 *
 * @package       cake.tests.cases.libs.model
 */
class Basket extends CakeTestModel {
	public $name = 'Basket';

	public $belongsTo = array(
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
 * @package       cake.tests.cases.libs.model
 */
class TestPluginArticle extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestPluginArticle'
 * @access public
 */
	public $name = 'TestPluginArticle';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('User');

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array(
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
 * @package       cake.tests.cases.libs.model
 */
class TestPluginComment extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestPluginComment'
 * @access public
 */
	public $name = 'TestPluginComment';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array(
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
 * @package       cake.tests.cases.libs.model
 */
class Uuidportfolio extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Uuidportfolio'
 * @access public
 */
	public $name = 'Uuidportfolio';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('Uuiditem');
}

/**
 * Uuiditem class
 *
 * @package       cake.tests.cases.libs.model
 */
class Uuiditem extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Item'
 * @access public
 */
	public $name = 'Uuiditem';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('Uuidportfolio' => array('with' => 'UuiditemsUuidportfolioNumericid'));

}

/**
 * UuiditemsPortfolio class
 *
 * @package       cake.tests.cases.libs.model
 */
class UuiditemsUuidportfolio extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ItemsPortfolio'
 * @access public
 */
	public $name = 'UuiditemsUuidportfolio';
}

/**
 * UuiditemsPortfolioNumericid class
 *
 * @package       cake.tests.cases.libs.model
 */
class UuiditemsUuidportfolioNumericid extends CakeTestModel {

/**
 * name property
 *
 * @var string
 * @access public
 */
	public $name = 'UuiditemsUuidportfolioNumericid';
}

/**
 * TranslateTestModel class.
 *
 * @package       cake.tests.cases.libs.model
 */
class TranslateTestModel extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TranslateTestModel'
 * @access public
 */
	public $name = 'TranslateTestModel';

/**
 * useTable property
 *
 * @var string 'i18n'
 * @access public
 */
	public $useTable = 'i18n';

/**
 * displayField property
 *
 * @var string 'field'
 * @access public
 */
	public $displayField = 'field';
}

/**
 * TranslateTestModel class.
 *
 * @package       cake.tests.cases.libs.model
 */
class TranslateWithPrefix extends CakeTestModel {
/**
 * name property
 *
 * @var string 'TranslateTestModel'
 * @access public
 */
	public $name = 'TranslateWithPrefix';
/**
 * tablePrefix property
 *
 * @var string 'i18n'
 * @access public
 */
	public $tablePrefix = 'i18n_';
/**
 * displayField property
 *
 * @var string 'field'
 * @access public
 */
	public $displayField = 'field';
}
/**
 * TranslatedItem class.
 *
 * @package       cake.tests.cases.libs.model
 */
class TranslatedItem extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TranslatedItem'
 * @access public
 */
	public $name = 'TranslatedItem';

/**
 * cacheQueries property
 *
 * @var bool false
 * @access public
 */
	public $cacheQueries = false;

/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	public $actsAs = array('Translate' => array('content', 'title'));

/**
 * translateModel property
 *
 * @var string 'TranslateTestModel'
 * @access public
 */
	public $translateModel = 'TranslateTestModel';
}

/**
 * TranslatedItem class.
 *
 * @package       cake.tests.cases.libs.model
 */
class TranslatedItem2 extends CakeTestModel {
/**
 * name property
 *
 * @var string 'TranslatedItem'
 * @access public
 */
	public $name = 'TranslatedItem';
/**
 * cacheQueries property
 *
 * @var bool false
 * @access public
 */
	public $cacheQueries = false;
/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	public $actsAs = array('Translate' => array('content', 'title'));
/**
 * translateModel property
 *
 * @var string 'TranslateTestModel'
 * @access public
 */
	public $translateModel = 'TranslateWithPrefix';
}
/**
 * TranslatedItemWithTable class.
 *
 * @package       cake.tests.cases.libs.model
 */
class TranslatedItemWithTable extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TranslatedItemWithTable'
 * @access public
 */
	public $name = 'TranslatedItemWithTable';

/**
 * useTable property
 *
 * @var string 'translated_items'
 * @access public
 */
	public $useTable = 'translated_items';

/**
 * cacheQueries property
 *
 * @var bool false
 * @access public
 */
	public $cacheQueries = false;

/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	public $actsAs = array('Translate' => array('content', 'title'));

/**
 * translateModel property
 *
 * @var string 'TranslateTestModel'
 * @access public
 */
	public $translateModel = 'TranslateTestModel';

/**
 * translateTable property
 *
 * @var string 'another_i18n'
 * @access public
 */
	public $translateTable = 'another_i18n';
}

/**
 * TranslateArticleModel class.
 *
 * @package       cake.tests.cases.libs.model
 */
class TranslateArticleModel extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TranslateArticleModel'
 * @access public
 */
	public $name = 'TranslateArticleModel';

/**
 * useTable property
 *
 * @var string 'article_i18n'
 * @access public
 */
	public $useTable = 'article_i18n';

/**
 * displayField property
 *
 * @var string 'field'
 * @access public
 */
	public $displayField = 'field';
}

/**
 * TranslatedArticle class.
 *
 * @package       cake.tests.cases.libs.model
 */
class TranslatedArticle extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TranslatedArticle'
 * @access public
 */
	public $name = 'TranslatedArticle';

/**
 * cacheQueries property
 *
 * @var bool false
 * @access public
 */
	public $cacheQueries = false;

/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	public $actsAs = array('Translate' => array('title', 'body'));

/**
 * translateModel property
 *
 * @var string 'TranslateArticleModel'
 * @access public
 */
	public $translateModel = 'TranslateArticleModel';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('User');
}

class CounterCacheUser extends CakeTestModel {
	public $name = 'CounterCacheUser';
	public $alias = 'User';

	public $hasMany = array('Post' => array(
		'className' => 'CounterCachePost',
		'foreignKey' => 'user_id'
	));
}

class CounterCachePost extends CakeTestModel {
	public $name = 'CounterCachePost';
	public $alias = 'Post';

	public $belongsTo = array('User' => array(
		'className' => 'CounterCacheUser',
		'foreignKey' => 'user_id',
		'counterCache' => true
	));
}

class CounterCacheUserNonstandardPrimaryKey extends CakeTestModel {
	public $name = 'CounterCacheUserNonstandardPrimaryKey';
	public $alias = 'User';
    public $primaryKey = 'uid';

	public $hasMany = array('Post' => array(
		'className' => 'CounterCachePostNonstandardPrimaryKey',
		'foreignKey' => 'uid'
	));
}

class CounterCachePostNonstandardPrimaryKey extends CakeTestModel {
	public $name = 'CounterCachePostNonstandardPrimaryKey';
	public $alias = 'Post';
    public $primaryKey = 'pid';

	public $belongsTo = array('User' => array(
		'className' => 'CounterCacheUserNonstandardPrimaryKey',
		'foreignKey' => 'uid',
		'counterCache' => true
	));
}

class ArticleB extends CakeTestModel {
	public $name = 'ArticleB';
	public $useTable = 'articles';
	public $hasAndBelongsToMany = array(
		'TagB' => array(
			'className' => 'TagB',
			'joinTable' => 'articles_tags',
			'foreignKey' => 'article_id',
			'associationForeignKey' => 'tag_id'
		)
	);
}

class TagB extends CakeTestModel {
	public $name = 'TagB';
	public $useTable = 'tags';
	public $hasAndBelongsToMany = array(
		'ArticleB' => array(
			'className' => 'ArticleB',
			'joinTable' => 'articles_tags',
			'foreignKey' => 'tag_id',
			'associationForeignKey' => 'article_id'
		)
	);
}

class Fruit extends CakeTestModel {
	public $name = 'Fruit';
	public $hasAndBelongsToMany = array(
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
	public $name = 'FruitsUuidTag';
	public $primaryKey = false;
	public $belongsTo = array(
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
	public $name = 'UuidTag';
	public $hasAndBelongsToMany = array(
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
	public $name = 'Fruit';
	public $useTable = 'fruits';
	public $hasAndBelongsToMany = array(
		'UuidTag' => array(
			'className' => 'UuidTagNoWith',
			'joinTable' => 'fruits_uuid_tags',
			'foreignKey' => 'fruit_id',
			'associationForeignKey' => 'uuid_tag_id',
		)
	);
}

class UuidTagNoWith extends CakeTestModel {
	public $name = 'UuidTag';
	public $useTable = 'uuid_tags';
	public $hasAndBelongsToMany = array(
		'Fruit' => array(
			'className' => 'FruitNoWith',
			'joinTable' => 'fruits_uuid_tags',
			'foreign_key' => 'uuid_tag_id',
			'associationForeignKey' => 'fruit_id',
		)
	);
}

class ProductUpdateAll extends CakeTestModel {
	public $name = 'ProductUpdateAll';
	public $useTable = 'product_update_all';

}

class GroupUpdateAll extends CakeTestModel {
	public $name = 'GroupUpdateAll';
	public $useTable = 'group_update_all';
}

class TransactionTestModel extends CakeTestModel {
	var $name = 'TransactionTestModel';
	var $useTable = 'samples';

	function afterSave($created) {
		$data = array(
			array('apple_id' => 1, 'name' => 'sample6'),
		);
		$this->saveAll($data, array('atomic' => true, 'callbacks' => false));
	}
}

/**
 * TestModel class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class TestModel extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel'
 * @access public
 */
	public $name = 'TestModel';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * schema property
 *
 * @var array
 * @access protected
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'client_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '11'),
		'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'login' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'passwd' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
		'addr_1' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
		'addr_2' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '25'),
		'zip_code' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'city' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'country' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'phone' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'fax' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'url' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
		'email' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'comments' => array('type' => 'text', 'null' => '1', 'default' => '', 'length' => '155'),
		'last_login' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => ''),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);

/**
 * find method
 *
 * @param mixed $conditions
 * @param mixed $fields
 * @param mixed $order
 * @param mixed $recursive
 * @access public
 * @return void
 */
	function find($conditions = null, $fields = null, $order = null, $recursive = null) {
		return array($conditions, $fields);
	}

/**
 * findAll method
 *
 * @param mixed $conditions
 * @param mixed $fields
 * @param mixed $order
 * @param mixed $recursive
 * @access public
 * @return void
 */
	function findAll($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}
}

/**
 * TestModel2 class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class TestModel2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel2'
 * @access public
 */
	public $name = 'TestModel2';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;
}

/**
 * TestModel4 class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class TestModel3 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel3'
 * @access public
 */
	public $name = 'TestModel3';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;
}

/**
 * TestModel4 class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class TestModel4 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel4'
 * @access public
 */
	public $name = 'TestModel4';

/**
 * table property
 *
 * @var string 'test_model4'
 * @access public
 */
	public $table = 'test_model4';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array(
		'TestModel4Parent' => array(
			'className' => 'TestModel4',
			'foreignKey' => 'parent_id'
		)
	);

/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	public $hasOne = array(
		'TestModel5' => array(
			'className' => 'TestModel5',
			'foreignKey' => 'test_model4_id'
		)
	);

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('TestModel7' => array(
		'className' => 'TestModel7',
		'joinTable' => 'test_model4_test_model7',
		'foreignKey' => 'test_model4_id',
		'associationForeignKey' => 'test_model7_id',
		'with' => 'TestModel4TestModel7'
	));

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * TestModel4TestModel7 class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class TestModel4TestModel7 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel4TestModel7'
 * @access public
 */
	public $name = 'TestModel4TestModel7';

/**
 * table property
 *
 * @var string 'test_model4_test_model7'
 * @access public
 */
	public $table = 'test_model4_test_model7';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'test_model4_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'test_model7_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8')
			);
		}
		return $this->_schema;
	}
}

/**
 * TestModel5 class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class TestModel5 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel5'
 * @access public
 */
	public $name = 'TestModel5';

/**
 * table property
 *
 * @var string 'test_model5'
 * @access public
 */
	public $table = 'test_model5';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('TestModel4' => array(
		'className' => 'TestModel4',
		'foreignKey' => 'test_model4_id'
	));

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array('TestModel6' => array(
		'className' => 'TestModel6',
		'foreignKey' => 'test_model5_id'
	));

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'test_model4_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * TestModel6 class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class TestModel6 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel6'
 * @access public
 */
	public $name = 'TestModel6';

/**
 * table property
 *
 * @var string 'test_model6'
 * @access public
 */
	public $table = 'test_model6';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('TestModel5' => array(
		'className' => 'TestModel5',
		'foreignKey' => 'test_model5_id'
	));

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'test_model5_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * TestModel7 class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class TestModel7 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel7'
 * @access public
 */
	public $name = 'TestModel7';

/**
 * table property
 *
 * @var string 'test_model7'
 * @access public
 */
	public $table = 'test_model7';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * TestModel8 class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class TestModel8 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel8'
 * @access public
 */
	public $name = 'TestModel8';

/**
 * table property
 *
 * @var string 'test_model8'
 * @access public
 */
	public $table = 'test_model8';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	public $hasOne = array(
		'TestModel9' => array(
			'className' => 'TestModel9',
			'foreignKey' => 'test_model8_id',
			'conditions' => 'TestModel9.name != \'mariano\''
		)
	);

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'test_model9_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * TestModel9 class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class TestModel9 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel9'
 * @access public
 */
	public $name = 'TestModel9';

/**
 * table property
 *
 * @var string 'test_model9'
 * @access public
 */
	public $table = 'test_model9';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('TestModel8' => array(
		'className' => 'TestModel8',
		'foreignKey' => 'test_model8_id',
		'conditions' => 'TestModel8.name != \'larry\''
	));

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'test_model8_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '11'),
				'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * Level class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class Level extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Level'
 * @access public
 */
	public $name = 'Level';

/**
 * table property
 *
 * @var string 'level'
 * @access public
 */
	public $table = 'level';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array(
		'Group'=> array(
			'className' => 'Group'
		),
		'User2' => array(
			'className' => 'User2'
		)
	);

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'name' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => '20'),
			);
		}
		return $this->_schema;
	}
}

/**
 * Group class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class Group extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Group'
 * @access public
 */
	public $name = 'Group';

/**
 * table property
 *
 * @var string 'group'
 * @access public
 */
	public $table = 'group';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('Level');

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array('Category2', 'User2');

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'level_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'name' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => '20'),
			);
		}
		return $this->_schema;
	}

}

/**
 * User2 class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class User2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'User2'
 * @access public
 */
	public $name = 'User2';

/**
 * table property
 *
 * @var string 'user'
 * @access public
 */
	public $table = 'user';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array(
		'Group' => array(
			'className' => 'Group'
		),
		'Level' => array(
			'className' => 'Level'
		)
	);

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array(
		'Article2' => array(
			'className' => 'Article2'
		),
	);

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'group_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'level_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'name' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => '20'),
			);
		}
		return $this->_schema;
	}
}

/**
 * Category2 class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class Category2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Category2'
 * @access public
 */
	public $name = 'Category2';

/**
 * table property
 *
 * @var string 'category'
 * @access public
 */
	public $table = 'category';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array(
		'Group' => array(
			'className' => 'Group',
			'foreignKey' => 'group_id'
		),
		'ParentCat' => array(
			'className' => 'Category2',
			'foreignKey' => 'parent_id'
		)
	);

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array(
		'ChildCat' => array(
			'className' => 'Category2',
			'foreignKey' => 'parent_id'
		),
		'Article2' => array(
			'className' => 'Article2',
			'order'=>'Article2.published_date DESC',
			'foreignKey' => 'category_id',
			'limit'=>'3')
	);

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				'group_id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				'parent_id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				'name' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
				'icon' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
				'description' => array('type' => 'text', 'null' => false, 'default' => '', 'length' => null),

			);
		}
		return $this->_schema;
	}
}

/**
 * Article2 class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class Article2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Article2'
 * @access public
 */
	public $name = 'Article2';

/**
 * table property
 *
 * @var string 'article'
 * @access public
 */
	public $table = 'articles';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array(
		'Category2' => array('className' => 'Category2'),
		'User2' => array('className' => 'User2')
	);

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				'category_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'user_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'rate_count' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'rate_sum' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'viewed' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'version' => array('type' => 'string', 'null' => true, 'default' => '', 'length' => '45'),
				'title' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '200'),
				'intro' => array('text' => 'string', 'null' => true, 'default' => '', 'length' => null),
				'comments' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '4'),
				'body' => array('text' => 'string', 'null' => true, 'default' => '', 'length' => null),
				'isdraft' => array('type' => 'boolean', 'null' => false, 'default' => '0', 'length' => '1'),
				'allow_comments' => array('type' => 'boolean', 'null' => false, 'default' => '1', 'length' => '1'),
				'moderate_comments' => array('type' => 'boolean', 'null' => false, 'default' => '1', 'length' => '1'),
				'published' => array('type' => 'boolean', 'null' => false, 'default' => '0', 'length' => '1'),
				'multipage' => array('type' => 'boolean', 'null' => false, 'default' => '0', 'length' => '1'),
				'published_date' => array('type' => 'datetime', 'null' => true, 'default' => '', 'length' => null),
				'created' => array('type' => 'datetime', 'null' => false, 'default' => '0000-00-00 00:00:00', 'length' => null),
				'modified' => array('type' => 'datetime', 'null' => false, 'default' => '0000-00-00 00:00:00', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * CategoryFeatured2 class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class CategoryFeatured2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'CategoryFeatured2'
 * @access public
 */
	public $name = 'CategoryFeatured2';

/**
 * table property
 *
 * @var string 'category_featured'
 * @access public
 */
	public $table = 'category_featured';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				'parent_id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				'name' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
				'icon' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
				'description' => array('text' => 'string', 'null' => false, 'default' => '', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * Featured2 class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class Featured2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Featured2'
 * @access public
 */
	public $name = 'Featured2';

/**
 * table property
 *
 * @var string 'featured2'
 * @access public
 */
	public $table = 'featured2';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array(
		'CategoryFeatured2' => array(
			'className' => 'CategoryFeatured2'
		)
	);

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'article_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'category_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'name' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => '20')
			);
		}
		return $this->_schema;
	}
}

/**
 * Comment2 class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class Comment2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Comment2'
 * @access public
 */
	public $name = 'Comment2';

/**
 * table property
 *
 * @var string 'comment'
 * @access public
 */
	public $table = 'comment';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('ArticleFeatured2', 'User2');

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'article_featured_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'user_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'name' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => '20')
			);
		}
		return $this->_schema;
	}
}

/**
 * ArticleFeatured2 class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class ArticleFeatured2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ArticleFeatured2'
 * @access public
 */
	public $name = 'ArticleFeatured2';

/**
 * table property
 *
 * @var string 'article_featured'
 * @access public
 */
	public $table = 'article_featured';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array(
		'CategoryFeatured2' => array('className' => 'CategoryFeatured2'),
		'User2' => array('className' => 'User2')
	);

/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	public $hasOne = array(
		'Featured2' => array('className' => 'Featured2')
	);

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array(
		'Comment2' => array('className'=>'Comment2', 'dependent' => true)
	);

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'category_featured_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'user_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'title' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => '20'),
				'body' => array('text' => 'string', 'null' => true, 'default' => '', 'length' => null),
				'published' => array('type' => 'boolean', 'null' => false, 'default' => '0', 'length' => '1'),
				'published_date' => array('type' => 'datetime', 'null' => true, 'default' => '', 'length' => null),
				'created' => array('type' => 'datetime', 'null' => false, 'default' => '0000-00-00 00:00:00', 'length' => null),
				'modified' => array('type' => 'datetime', 'null' => false, 'default' => '0000-00-00 00:00:00', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * MysqlTestModel class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class MysqlTestModel extends Model {

/**
 * name property
 *
 * @var string 'MysqlTestModel'
 * @access public
 */
	public $name = 'MysqlTestModel';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * find method
 *
 * @param mixed $conditions
 * @param mixed $fields
 * @param mixed $order
 * @param mixed $recursive
 * @access public
 * @return void
 */
	function find($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}

/**
 * findAll method
 *
 * @param mixed $conditions
 * @param mixed $fields
 * @param mixed $order
 * @param mixed $recursive
 * @access public
 * @return void
 */
	function findAll($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		return array(
			'id'		=> array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'client_id'	=> array('type' => 'integer', 'null' => '', 'default' => '0', 'length' => '11'),
			'name'		=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'login'		=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'passwd'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			'addr_1'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			'addr_2'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '25'),
			'zip_code'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'city'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'country'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'phone'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'fax'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'url'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			'email'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'comments'	=> array('type' => 'text', 'null' => '1', 'default' => '', 'length' => ''),
			'last_login'=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => ''),
			'created'	=> array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
			'updated'	=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
		);
	}
}
