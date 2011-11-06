<?php
/**
 * Mock models file
 *
 * Mock classes for use in Model and related test cases
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
 * @since         CakePHP(tm) v 1.2.0.6464
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Test class
 *
 * @package       Cake.Test.Case.Model
 */
class Test extends CakeTestModel {

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * name property
 *
 * @var string 'Test'
 */
	public $name = 'Test';

/**
 * schema property
 *
 * @var array
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
 * @package       Cake.Test.Case.Model
 */
class TestAlias extends CakeTestModel {

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * name property
 *
 * @var string 'TestAlias'
 */
	public $name = 'TestAlias';

/**
 * alias property
 *
 * @var string 'TestAlias'
 */
	public $alias = 'TestAlias';

/**
 * schema property
 *
 * @var array
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
 * @package       Cake.Test.Case.Model
 */
class TestValidate extends CakeTestModel {

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * name property
 *
 * @var string 'TestValidate'
 */
	public $name = 'TestValidate';

/**
 * schema property
 *
 * @var array
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
 * @return void
 */
	public function validateNumber($value, $options) {
		$options = array_merge(array('min' => 0, 'max' => 100), $options);
		$valid = ($value['number'] >= $options['min'] && $value['number'] <= $options['max']);
		return $valid;
	}

/**
 * validateTitle method
 *
 * @param mixed $value
 * @return void
 */
	public function validateTitle($value) {
		return (!empty($value) && strpos(strtolower($value['title']), 'title-') === 0);
	}
}

/**
 * User class
 *
 * @package       Cake.Test.Case.Model
 */
class User extends CakeTestModel {

/**
 * name property
 *
 * @var string 'User'
 */
	public $name = 'User';

/**
 * validate property
 *
 * @var array
 */
	public $validate = array('user' => 'notEmpty', 'password' => 'notEmpty');

/**
 * beforeFind() callback used to run ContainableBehaviorTest::testLazyLoad()
 *
 * @return bool
*/
	public function beforeFind ($queryData) {
		if (!empty($queryData['lazyLoad'])) {
			if (!isset($this->Article, $this->Comment, $this->ArticleFeatured)) {
				throw new Exception('Unavailable associations');
			}
		}
		return true;
	}
}

/**
 * Article class
 *
 * @package       Cake.Test.Case.Model
 */
class Article extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Article'
 */
	public $name = 'Article';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('User');

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array('Comment' => array('dependent' => true));

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('Tag');

/**
 * validate property
 *
 * @var array
 */
	public $validate = array('user_id' => 'numeric', 'title' => array('allowEmpty' => false, 'rule' => 'notEmpty'), 'body' => 'notEmpty');

/**
 * beforeSaveReturn property
 *
 * @var bool true
 */
	public $beforeSaveReturn = true;

/**
 * beforeSave method
 *
 * @return void
 */
	public function beforeSave($options = array()) {
		return $this->beforeSaveReturn;
	}

/**
 * titleDuplicate method
 *
 * @param mixed $title
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
 * @package       Cake.Test.Case.Model
 */
class BeforeDeleteComment extends CakeTestModel {
	public $name = 'BeforeDeleteComment';

	public $useTable = 'comments';

	public function beforeDelete($cascade = true) {
		$db = $this->getDataSource();
		$db->delete($this, array($this->alias . '.' . $this->primaryKey => array(1, 3)));
		return true;
	}
}

/**
 * NumericArticle class
 *
 * @package       Cake.Test.Case.Model
 */
class NumericArticle extends CakeTestModel {

/**
 * name property
 *
 * @var string 'NumericArticle'
 */
	public $name = 'NumericArticle';

/**
 * useTable property
 *
 * @var string 'numeric_articles'
 */
	public $useTable = 'numeric_articles';
}

/**
 * Article10 class
 *
 * @package       Cake.Test.Case.Model
 */
class Article10 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Article10'
 */
	public $name = 'Article10';

/**
 * useTable property
 *
 * @var string 'articles'
 */
	public $useTable = 'articles';

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array('Comment' => array('dependent' => true, 'exclusive' => true));
}

/**
 * ArticleFeatured class
 *
 * @package       Cake.Test.Case.Model
 */
class ArticleFeatured extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ArticleFeatured'
 */
	public $name = 'ArticleFeatured';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('User', 'Category');

/**
 * hasOne property
 *
 * @var array
 */
	public $hasOne = array('Featured');

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array('Comment' => array('className' => 'Comment', 'dependent' => true));

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('Tag');

/**
 * validate property
 *
 * @var array
 */
	public $validate = array('user_id' => 'numeric', 'title' => 'notEmpty', 'body' => 'notEmpty');
}

/**
 * Featured class
 *
 * @package       Cake.Test.Case.Model
 */
class Featured extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Featured'
 */
	public $name = 'Featured';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('ArticleFeatured', 'Category');
}

/**
 * Tag class
 *
 * @package       Cake.Test.Case.Model
 */
class Tag extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Tag'
 */
	public $name = 'Tag';
}

/**
 * ArticlesTag class
 *
 * @package       Cake.Test.Case.Model
 */
class ArticlesTag extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ArticlesTag'
 */
	public $name = 'ArticlesTag';
}

/**
 * ArticleFeaturedsTag class
 *
 * @package       Cake.Test.Case.Model
 */
class ArticleFeaturedsTag extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ArticleFeaturedsTag'
 */
	public $name = 'ArticleFeaturedsTag';
}

/**
 * Comment class
 *
 * @package       Cake.Test.Case.Model
 */
class Comment extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Comment'
 */
	public $name = 'Comment';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('Article', 'User');

/**
 * hasOne property
 *
 * @var array
 */
	public $hasOne = array('Attachment' => array('dependent' => true));
}

/**
 * Modified Comment Class has afterFind Callback
 *
 * @package       Cake.Test.Case.Model
 */
class ModifiedComment extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Comment'
 */
	public $name = 'Comment';

/**
 * useTable property
 *
 * @var string 'comments'
 */
	public $useTable = 'comments';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('Article');

/**
 * afterFind callback
 *
 * @return void
 */
	public function afterFind($results, $primary = false) {
		if (isset($results[0])) {
			$results[0]['Comment']['callback'] = 'Fire';
		}
		return $results;
	}
}

/**
 * Modified Comment Class has afterFind Callback
 *
 * @package       Cake.Test.Case.Model
 */
class AgainModifiedComment extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Comment'
 */
	public $name = 'Comment';

/**
 * useTable property
 *
 * @var string 'comments'
 */
	public $useTable = 'comments';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('Article');

/**
 * afterFind callback
 *
 * @return void
 */
	public function afterFind($results, $primary = false) {
		if (isset($results[0])) {
			$results[0]['Comment']['querytype'] = $this->findQueryType;
		}
		return $results;
	}
}

/**
 * MergeVarPluginAppModel class
 *
 * @package       Cake.Test.Case.Model
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
 * @package       Cake.Test.Case.Model
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
 * @package       Cake.Test.Case.Model
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
 * @package       Cake.Test.Case.Model
 */
class Attachment extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Attachment'
 */
	public $name = 'Attachment';
}

/**
 * Category class
 *
 * @package       Cake.Test.Case.Model
 */
class Category extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Category'
 */
	public $name = 'Category';
}

/**
 * CategoryThread class
 *
 * @package       Cake.Test.Case.Model
 */
class CategoryThread extends CakeTestModel {

/**
 * name property
 *
 * @var string 'CategoryThread'
 */
	public $name = 'CategoryThread';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('ParentCategory' => array('className' => 'CategoryThread', 'foreignKey' => 'parent_id'));
}

/**
 * Apple class
 *
 * @package       Cake.Test.Case.Model
 */
class Apple extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Apple'
 */
	public $name = 'Apple';

/**
 * validate property
 *
 * @var array
 */
	public $validate = array('name' => 'notEmpty');

/**
 * hasOne property
 *
 * @var array
 */
	public $hasOne = array('Sample');

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array('Child' => array('className' => 'Apple', 'dependent' => true));

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('Parent' => array('className' => 'Apple', 'foreignKey' => 'apple_id'));
}

/**
 * Sample class
 *
 * @package       Cake.Test.Case.Model
 */
class Sample extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Sample'
 */
	public $name = 'Sample';

/**
 * belongsTo property
 *
 * @var string 'Apple'
 */
	public $belongsTo = 'Apple';
}

/**
 * AnotherArticle class
 *
 * @package       Cake.Test.Case.Model
 */
class AnotherArticle extends CakeTestModel {

/**
 * name property
 *
 * @var string 'AnotherArticle'
 */
	public $name = 'AnotherArticle';

/**
 * hasMany property
 *
 * @var string 'Home'
 */
	public $hasMany = 'Home';
}

/**
 * Advertisement class
 *
 * @package       Cake.Test.Case.Model
 */
class Advertisement extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Advertisement'
 */
	public $name = 'Advertisement';

/**
 * hasMany property
 *
 * @var string 'Home'
 */
	public $hasMany = 'Home';
}

/**
 * Home class
 *
 * @package       Cake.Test.Case.Model
 */
class Home extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Home'
 */
	public $name = 'Home';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('AnotherArticle', 'Advertisement');
}

/**
 * Post class
 *
 * @package       Cake.Test.Case.Model
 */
class Post extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Post'
 */
	public $name = 'Post';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('Author');

	public function beforeFind($queryData) {
		if (isset($queryData['connection'])) {
			$this->useDbConfig = $queryData['connection'];
		}
		return true;
	}

	public function afterFind($results, $primary = false) {
		$this->useDbConfig = 'test';
		return $results;
	}
}

/**
 * Author class
 *
 * @package       Cake.Test.Case.Model
 */
class Author extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Author'
 */
	public $name = 'Author';

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array('Post');

/**
 * afterFind method
 *
 * @param mixed $results
 * @return void
 */
	public function afterFind($results, $primary = false) {
		$results[0]['Author']['test'] = 'working';
		return $results;
	}
}

/**
 * ModifiedAuthor class
 *
 * @package       Cake.Test.Case.Model
 */
class ModifiedAuthor extends Author {

/**
 * name property
 *
 * @var string 'Author'
 */
	public $name = 'Author';

/**
 * afterFind method
 *
 * @param mixed $results
 * @return void
 */
	public function afterFind($results, $primary = false) {
		foreach($results as $index => $result) {
			$results[$index]['Author']['user'] .= ' (CakePHP)';
		}
		return $results;
	}
}

/**
 * Project class
 *
 * @package       Cake.Test.Case.Model
 */
class Project extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Project'
 */
	public $name = 'Project';

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array('Thread');
}

/**
 * Thread class
 *
 * @package       Cake.Test.Case.Model
 */
class Thread extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Thread'
 */
	public $name = 'Thread';

/**
 * hasMany property
 *
 * @var array
 */
	public $belongsTo = array('Project');

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array('Message');
}

/**
 * Message class
 *
 * @package       Cake.Test.Case.Model
 */
class Message extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Message'
 */
	public $name = 'Message';

/**
 * hasOne property
 *
 * @var array
 */
	public $hasOne = array('Bid');
}

/**
 * Bid class
 *
 * @package       Cake.Test.Case.Model
 */
class Bid extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Bid'
 */
	public $name = 'Bid';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('Message');
}

/**
 * NodeAfterFind class
 *
 * @package       Cake.Test.Case.Model
 */
class NodeAfterFind extends CakeTestModel {

/**
 * name property
 *
 * @var string 'NodeAfterFind'
 */
	public $name = 'NodeAfterFind';

/**
 * validate property
 *
 * @var array
 */
	public $validate = array('name' => 'notEmpty');

/**
 * useTable property
 *
 * @var string 'apples'
 */
	public $useTable = 'apples';

/**
 * hasOne property
 *
 * @var array
 */
	public $hasOne = array('Sample' => array('className' => 'NodeAfterFindSample'));

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array('Child' => array('className' => 'NodeAfterFind', 'dependent' => true));

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('Parent' => array('className' => 'NodeAfterFind', 'foreignKey' => 'apple_id'));

/**
 * afterFind method
 *
 * @param mixed $results
 * @return void
 */
	public function afterFind($results, $primary = false) {
		return $results;
	}
}

/**
 * NodeAfterFindSample class
 *
 * @package       Cake.Test.Case.Model
 */
class NodeAfterFindSample extends CakeTestModel {

/**
 * name property
 *
 * @var string 'NodeAfterFindSample'
 */
	public $name = 'NodeAfterFindSample';

/**
 * useTable property
 *
 * @var string 'samples'
 */
	public $useTable = 'samples';

/**
 * belongsTo property
 *
 * @var string 'NodeAfterFind'
 */
	public $belongsTo = 'NodeAfterFind';
}

/**
 * NodeNoAfterFind class
 *
 * @package       Cake.Test.Case.Model
 */
class NodeNoAfterFind extends CakeTestModel {

/**
 * name property
 *
 * @var string 'NodeAfterFind'
 */
	public $name = 'NodeAfterFind';

/**
 * validate property
 *
 * @var array
 */
	public $validate = array('name' => 'notEmpty');

/**
 * useTable property
 *
 * @var string 'apples'
 */
	public $useTable = 'apples';

/**
 * hasOne property
 *
 * @var array
 */
	public $hasOne = array('Sample' => array('className' => 'NodeAfterFindSample'));

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array('Child' => array('className' => 'NodeAfterFind', 'dependent' => true));

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('Parent' => array('className' => 'NodeAfterFind', 'foreignKey' => 'apple_id'));
}

/**
 * Node class
 *
 * @package       Cake.Test.Case.Model
 */
class Node extends CakeTestModel{

/**
 * name property
 *
 * @var string 'Node'
 */
	public $name = 'Node';

/**
 * hasAndBelongsToMany property
 *
 * @var array
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
 * @package       Cake.Test.Case.Model
 */
class Dependency extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Dependency'
 */
	public $name = 'Dependency';
}

/**
 * ModelA class
 *
 * @package       Cake.Test.Case.Model
 */
class ModelA extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ModelA'
 */
	public $name = 'ModelA';

/**
 * useTable property
 *
 * @var string 'apples'
 */
	public $useTable = 'apples';

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array('ModelB', 'ModelC');
}

/**
 * ModelB class
 *
 * @package       Cake.Test.Case.Model
 */
class ModelB extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ModelB'
 */
	public $name = 'ModelB';

/**
 * useTable property
 *
 * @var string 'messages'
 */
	public $useTable = 'messages';

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array('ModelD');
}

/**
 * ModelC class
 *
 * @package       Cake.Test.Case.Model
 */
class ModelC extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ModelC'
 */
	public $name = 'ModelC';

/**
 * useTable property
 *
 * @var string 'bids'
 */
	public $useTable = 'bids';

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array('ModelD');
}

/**
 * ModelD class
 *
 * @package       Cake.Test.Case.Model
 */
class ModelD extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ModelD'
 */
	public $name = 'ModelD';

/**
 * useTable property
 *
 * @var string 'threads'
 */
	public $useTable = 'threads';
}

/**
 * Something class
 *
 * @package       Cake.Test.Case.Model
 */
class Something extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Something'
 */
	public $name = 'Something';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('SomethingElse' => array('with' => array('JoinThing' => array('doomed'))));
}

/**
 * SomethingElse class
 *
 * @package       Cake.Test.Case.Model
 */
class SomethingElse extends CakeTestModel {

/**
 * name property
 *
 * @var string 'SomethingElse'
 */
	public $name = 'SomethingElse';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('Something' => array('with' => 'JoinThing'));
}

/**
 * JoinThing class
 *
 * @package       Cake.Test.Case.Model
 */
class JoinThing extends CakeTestModel {

/**
 * name property
 *
 * @var string 'JoinThing'
 */
	public $name = 'JoinThing';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('Something', 'SomethingElse');
}

/**
 * Portfolio class
 *
 * @package       Cake.Test.Case.Model
 */
class Portfolio extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Portfolio'
 */
	public $name = 'Portfolio';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('Item');
}

/**
 * Item class
 *
 * @package       Cake.Test.Case.Model
 */
class Item extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Item'
 */
	public $name = 'Item';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('Syfile' => array('counterCache' => true));

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('Portfolio' => array('unique' => false));
}

/**
 * ItemsPortfolio class
 *
 * @package       Cake.Test.Case.Model
 */
class ItemsPortfolio extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ItemsPortfolio'
 */
	public $name = 'ItemsPortfolio';
}

/**
 * Syfile class
 *
 * @package       Cake.Test.Case.Model
 */
class Syfile extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Syfile'
 */
	public $name = 'Syfile';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('Image');
}

/**
 * Image class
 *
 * @package       Cake.Test.Case.Model
 */
class Image extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Image'
 */
	public $name = 'Image';
}

/**
 * DeviceType class
 *
 * @package       Cake.Test.Case.Model
 */
class DeviceType extends CakeTestModel {

/**
 * name property
 *
 * @var string 'DeviceType'
 */
	public $name = 'DeviceType';

/**
 * order property
 *
 * @var array
 */
	public $order = array('DeviceType.order' => 'ASC');

/**
 * belongsTo property
 *
 * @var array
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
 */
	public $hasMany = array('Device' => array('order' => array('Device.id' => 'ASC')));
}

/**
 * DeviceTypeCategory class
 *
 * @package       Cake.Test.Case.Model
 */
class DeviceTypeCategory extends CakeTestModel {

/**
 * name property
 *
 * @var string 'DeviceTypeCategory'
 */
	public $name = 'DeviceTypeCategory';
}

/**
 * FeatureSet class
 *
 * @package       Cake.Test.Case.Model
 */
class FeatureSet extends CakeTestModel {

/**
 * name property
 *
 * @var string 'FeatureSet'
 */
	public $name = 'FeatureSet';
}

/**
 * ExteriorTypeCategory class
 *
 * @package       Cake.Test.Case.Model
 */
class ExteriorTypeCategory extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ExteriorTypeCategory'
 */
	public $name = 'ExteriorTypeCategory';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('Image' => array('className' => 'Device'));
}

/**
 * Document class
 *
 * @package       Cake.Test.Case.Model
 */
class Document extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Document'
 */
	public $name = 'Document';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('DocumentDirectory');
}

/**
 * Device class
 *
 * @package       Cake.Test.Case.Model
 */
class Device extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Device'
 */
	public $name = 'Device';
}

/**
 * DocumentDirectory class
 *
 * @package       Cake.Test.Case.Model
 */
class DocumentDirectory extends CakeTestModel {

/**
 * name property
 *
 * @var string 'DocumentDirectory'
 */
	public $name = 'DocumentDirectory';
}

/**
 * PrimaryModel class
 *
 * @package       Cake.Test.Case.Model
 */
class PrimaryModel extends CakeTestModel {

/**
 * name property
 *
 * @var string 'PrimaryModel'
 */
	public $name = 'PrimaryModel';
}

/**
 * SecondaryModel class
 *
 * @package       Cake.Test.Case.Model
 */
class SecondaryModel extends CakeTestModel {

/**
 * name property
 *
 * @var string 'SecondaryModel'
 */
	public $name = 'SecondaryModel';
}

/**
 * JoinA class
 *
 * @package       Cake.Test.Case.Model
 */
class JoinA extends CakeTestModel {

/**
 * name property
 *
 * @var string 'JoinA'
 */
	public $name = 'JoinA';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('JoinB', 'JoinC');
}

/**
 * JoinB class
 *
 * @package       Cake.Test.Case.Model
 */
class JoinB extends CakeTestModel {

/**
 * name property
 *
 * @var string 'JoinB'
 */
	public $name = 'JoinB';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('JoinA');
}

/**
 * JoinC class
 *
 * @package       Cake.Test.Case.Model
 */
class JoinC extends CakeTestModel {

/**
 * name property
 *
 * @var string 'JoinC'
 */
	public $name = 'JoinC';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('JoinA');
}

/**
 * ThePaper class
 *
 * @package       Cake.Test.Case.Model
 */
class ThePaper extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ThePaper'
 */
	public $name = 'ThePaper';

/**
 * useTable property
 *
 * @var string 'apples'
 */
	public $useTable = 'apples';

/**
 * hasOne property
 *
 * @var array
 */
	public $hasOne = array('Itself' => array('className' => 'ThePaper', 'foreignKey' => 'apple_id'));

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('Monkey' => array('joinTable' => 'the_paper_monkies', 'order' => 'id'));
}

/**
 * Monkey class
 *
 * @package       Cake.Test.Case.Model
 */
class Monkey extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Monkey'
 */
	public $name = 'Monkey';

/**
 * useTable property
 *
 * @var string 'devices'
 */
	public $useTable = 'devices';
}

/**
 * AssociationTest1 class
 *
 * @package       Cake.Test.Case.Model
 */
class AssociationTest1 extends CakeTestModel {

/**
 * useTable property
 *
 * @var string 'join_as'
 */
	public $useTable = 'join_as';

/**
 * name property
 *
 * @var string 'AssociationTest1'
 */
	public $name = 'AssociationTest1';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('AssociationTest2' => array(
		'unique' => false, 'joinTable' => 'join_as_join_bs', 'foreignKey' => false
	));
}

/**
 * AssociationTest2 class
 *
 * @package       Cake.Test.Case.Model
 */
class AssociationTest2 extends CakeTestModel {

/**
 * useTable property
 *
 * @var string 'join_bs'
 */
	public $useTable = 'join_bs';

/**
 * name property
 *
 * @var string 'AssociationTest2'
 */
	public $name = 'AssociationTest2';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('AssociationTest1' => array(
		'unique' => false, 'joinTable' => 'join_as_join_bs'
	));
}

/**
 * Callback class
 *
 * @package       Cake.Test.Case.Model
 */
class Callback extends CakeTestModel {

}
/**
 * CallbackPostTestModel class
 *
 * @package       Cake.Test.Case.Model
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
	public function beforeSave($options = array()) {
		return $this->beforeSaveReturn;
	}
/**
 * beforeValidate callback
 *
 * @return void
 */
	public function beforeValidate($options = array()) {
		return $this->beforeValidateReturn;
	}
/**
 * beforeDelete callback
 *
 * @return void
 */
	public function beforeDelete($cascade = true) {
		return $this->beforeDeleteReturn;
	}
}

/**
 * Uuid class
 *
 * @package       Cake.Test.Case.Model
 */
class Uuid extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Uuid'
 */
	public $name = 'Uuid';
}

/**
 * DataTest class
 *
 * @package       Cake.Test.Case.Model
 */
class DataTest extends CakeTestModel {

/**
 * name property
 *
 * @var string 'DataTest'
 */
	public $name = 'DataTest';
}

/**
 * TheVoid class
 *
 * @package       Cake.Test.Case.Model
 */
class TheVoid extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TheVoid'
 */
	public $name = 'TheVoid';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;
}

/**
 * ValidationTest1 class
 *
 * @package       Cake.Test.Case.Model
 */
class ValidationTest1 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ValidationTest'
 */
	public $name = 'ValidationTest1';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * schema property
 *
 * @var array
 */
	protected $_schema = array();

/**
 * validate property
 *
 * @var array
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
 * @return void
 */
	public function customValidationMethod($data) {
		return $data === 1;
	}

/**
 * Custom validator with parameters + default values
 *
 * @return array
 */
	public function customValidatorWithParams($data, $validator, $or = true, $ignore_on_same = 'id') {
		$this->validatorParams = get_defined_vars();
		unset($this->validatorParams['this']);
		return true;
	}

/**
 * Custom validator with messaage
 *
 * @return array
 */
	public function customValidatorWithMessage($data) {
		return 'This field will *never* validate! Muhahaha!';
	}
/**
 * Test validation with many parameters
 *
 * @return void
 */
	public function customValidatorWithSixParams($data, $one = 1, $two = 2, $three = 3, $four = 4, $five = 5, $six = 6) {
		$this->validatorParams = get_defined_vars();
		unset($this->validatorParams['this']);
		return true;
	}
}

/**
 * ValidationTest2 class
 *
 * @package       Cake.Test.Case.Model
 */
class ValidationTest2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ValidationTest2'
 */
	public $name = 'ValidationTest2';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * validate property
 *
 * @var array
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
 * @return void
 */
	public function customValidationMethod($data) {
		return $data === 1;
	}

/**
 * schema method
 *
 * @return void
 */
	public function schema($field = false) {
		return array();
	}
}

/**
 * Person class
 *
 * @package       Cake.Test.Case.Model
 */
class Person extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Person'
 */
	public $name = 'Person';

/**
 * belongsTo property
 *
 * @var array
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
 * @package       Cake.Test.Case.Model
 */
class UnderscoreField extends CakeTestModel {

/**
 * name property
 *
 * @var string 'UnderscoreField'
 */
	public $name = 'UnderscoreField';
}

/**
 * Product class
 *
 * @package       Cake.Test.Case.Model
 */
class Product extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Product'
 */
	public $name = 'Product';
}

/**
 * Story class
 *
 * @package       Cake.Test.Case.Model
 */
class Story extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Story'
 */
	public $name = 'Story';

/**
 * primaryKey property
 *
 * @var string 'story'
 */
	public $primaryKey = 'story';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('Tag' => array('foreignKey' => 'story'));

/**
 * validate property
 *
 * @var array
 */
	public $validate = array('title' => 'notEmpty');
}

/**
 * Cd class
 *
 * @package       Cake.Test.Case.Model
 */
class Cd extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Cd'
 */
	public $name = 'Cd';

/**
 * hasOne property
 *
 * @var array
 */
	public $hasOne = array('OverallFavorite' => array('foreignKey' => 'model_id', 'dependent' => true, 'conditions' => array('model_type' => 'Cd')));
}

/**
 * Book class
 *
 * @package       Cake.Test.Case.Model
 */
class Book extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Book'
 */
	public $name = 'Book';

/**
 * hasOne property
 *
 * @var array
 */
	public $hasOne = array('OverallFavorite' => array('foreignKey' => 'model_id', 'dependent' => true, 'conditions' => 'OverallFavorite.model_type = \'Book\''));
}

/**
 * OverallFavorite class
 *
 * @package       Cake.Test.Case.Model
 */
class OverallFavorite extends CakeTestModel {

/**
 * name property
 *
 * @var string 'OverallFavorite'
 */
	public $name = 'OverallFavorite';
}

/**
 * MyUser class
 *
 * @package       Cake.Test.Case.Model
 */
class MyUser extends CakeTestModel {

/**
 * name property
 *
 * @var string 'MyUser'
 */
	public $name = 'MyUser';

/**
 * undocumented variable
 *
 * @var string
 */
	public $hasAndBelongsToMany = array('MyCategory');
}

/**
 * MyCategory class
 *
 * @package       Cake.Test.Case.Model
 */
class MyCategory extends CakeTestModel {

/**
 * name property
 *
 * @var string 'MyCategory'
 */
	public $name = 'MyCategory';

/**
 * undocumented variable
 *
 * @var string
 */
	public $hasAndBelongsToMany = array('MyProduct', 'MyUser');
}

/**
 * MyProduct class
 *
 * @package       Cake.Test.Case.Model
 */
class MyProduct extends CakeTestModel {

/**
 * name property
 *
 * @var string 'MyProduct'
 */
	public $name = 'MyProduct';

/**
 * undocumented variable
 *
 * @var string
 */
	public $hasAndBelongsToMany = array('MyCategory');
}

/**
 * MyCategoriesMyUser class
 *
 * @package       Cake.Test.Case.Model
 */
class MyCategoriesMyUser extends CakeTestModel {

/**
 * name property
 *
 * @var string 'MyCategoriesMyUser'
 */
	public $name = 'MyCategoriesMyUser';
}

/**
 * MyCategoriesMyProduct class
 *
 * @package       Cake.Test.Case.Model
 */
class MyCategoriesMyProduct extends CakeTestModel {

/**
 * name property
 *
 * @var string 'MyCategoriesMyProduct'
 */
	public $name = 'MyCategoriesMyProduct';
}


/**
 * NumberTree class
 *
 * @package       Cake.Test.Case.Model
 */
class NumberTree extends CakeTestModel {

/**
 * name property
 *
 * @var string 'NumberTree'
 */
	public $name = 'NumberTree';

/**
 * actsAs property
 *
 * @var array
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
 * @return void
 */
	public function initialize($levelLimit = 3, $childLimit = 3, $currentLevel = null, $parent_id = null, $prefix = '1', $hierachial = true) {
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
 * @package       Cake.Test.Case.Model
 */
class NumberTreeTwo extends NumberTree {

/**
 * name property
 *
 * @var string 'NumberTree'
 */
	public $name = 'NumberTreeTwo';

/**
 * actsAs property
 *
 * @var array
 */
	public $actsAs = array();
}

/**
 * FlagTree class
 *
 * @package       Cake.Test.Case.Model
 */
class FlagTree extends NumberTree {

/**
 * name property
 *
 * @var string 'FlagTree'
 */
	public $name = 'FlagTree';
}

/**
 * UnconventionalTree class
 *
 * @package       Cake.Test.Case.Model
 */
class UnconventionalTree extends NumberTree {

/**
 * name property
 *
 * @var string 'FlagTree'
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
 * @package       Cake.Test.Case.Model
 */
class UuidTree extends NumberTree {

/**
 * name property
 *
 * @var string 'FlagTree'
 */
	public $name = 'UuidTree';
}

/**
 * Campaign class
 *
 * @package       Cake.Test.Case.Model
 */
class Campaign extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Campaign'
 */
	public $name = 'Campaign';

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array('Ad' => array('fields' => array('id','campaign_id','name')));
}

/**
 * Ad class
 *
 * @package       Cake.Test.Case.Model
 */
class Ad extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Ad'
 */
	public $name = 'Ad';

/**
 * actsAs property
 *
 * @var array
 */
	public $actsAs = array('Tree');

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('Campaign');
}

/**
 * AfterTree class
 *
 * @package       Cake.Test.Case.Model
 */
class AfterTree extends NumberTree {

/**
 * name property
 *
 * @var string 'AfterTree'
 */
	public $name = 'AfterTree';

/**
 * actsAs property
 *
 * @var array
 */
	public $actsAs = array('Tree');

	public function afterSave($created) {
		if ($created && isset($this->data['AfterTree'])) {
			$this->data['AfterTree']['name'] = 'Six and One Half Changed in AfterTree::afterSave() but not in database';
		}
	}
}

/**
 * Nonconformant Content class
 *
 * @package       Cake.Test.Case.Model
 */
class Content extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Content'
 */
	public $name = 'Content';

/**
 * useTable property
 *
 * @var string 'Content'
 */
	public $useTable = 'Content';

/**
 * primaryKey property
 *
 * @var string 'iContentId'
 */
	public $primaryKey = 'iContentId';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('Account' => array('className' => 'Account', 'with' => 'ContentAccount', 'joinTable' => 'ContentAccounts', 'foreignKey' => 'iContentId', 'associationForeignKey', 'iAccountId'));
}

/**
 * Nonconformant Account class
 *
 * @package       Cake.Test.Case.Model
 */
class Account extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Account'
 */
	public $name = 'Account';

/**
 * useTable property
 *
 * @var string 'Account'
 */
	public $useTable = 'Accounts';

/**
 * primaryKey property
 *
 * @var string 'iAccountId'
 */
	public $primaryKey = 'iAccountId';
}

/**
 * Nonconformant ContentAccount class
 *
 * @package       Cake.Test.Case.Model
 */
class ContentAccount extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Account'
 */
	public $name = 'ContentAccount';

/**
 * useTable property
 *
 * @var string 'Account'
 */
	public $useTable = 'ContentAccounts';

/**
 * primaryKey property
 *
 * @var string 'iAccountId'
 */
	public $primaryKey = 'iContentAccountsId';
}

/**
 * FilmFile class
 *
 * @package       Cake.Test.Case.Model
 */
class FilmFile extends CakeTestModel {
	public $name = 'FilmFile';
}

/**
 * Basket test model
 *
 * @package       Cake.Test.Case.Model
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
 * @package       Cake.Test.Case.Model
 */
class TestPluginArticle extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestPluginArticle'
 */
	public $name = 'TestPluginArticle';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('User');

/**
 * hasMany property
 *
 * @var array
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
 * @package       Cake.Test.Case.Model
 */
class TestPluginComment extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestPluginComment'
 */
	public $name = 'TestPluginComment';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array(
		'TestPluginArticle' => array(
			'className' => 'TestPlugin.TestPluginArticle',
			'foreignKey' => 'article_id',
		),
		'TestPlugin.User'
	);
}

/**
 * Uuidportfolio class
 *
 * @package       Cake.Test.Case.Model
 */
class Uuidportfolio extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Uuidportfolio'
 */
	public $name = 'Uuidportfolio';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('Uuiditem');
}

/**
 * Uuiditem class
 *
 * @package       Cake.Test.Case.Model
 */
class Uuiditem extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Item'
 */
	public $name = 'Uuiditem';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('Uuidportfolio' => array('with' => 'UuiditemsUuidportfolioNumericid'));

}

/**
 * UuiditemsPortfolio class
 *
 * @package       Cake.Test.Case.Model
 */
class UuiditemsUuidportfolio extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ItemsPortfolio'
 */
	public $name = 'UuiditemsUuidportfolio';
}

/**
 * UuiditemsPortfolioNumericid class
 *
 * @package       Cake.Test.Case.Model
 */
class UuiditemsUuidportfolioNumericid extends CakeTestModel {

/**
 * name property
 *
 * @var string
 */
	public $name = 'UuiditemsUuidportfolioNumericid';
}

/**
 * TranslateTestModel class.
 *
 * @package       Cake.Test.Case.Model
 */
class TranslateTestModel extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TranslateTestModel'
 */
	public $name = 'TranslateTestModel';

/**
 * useTable property
 *
 * @var string 'i18n'
 */
	public $useTable = 'i18n';

/**
 * displayField property
 *
 * @var string 'field'
 */
	public $displayField = 'field';
}

/**
 * TranslateTestModel class.
 *
 * @package       Cake.Test.Case.Model
 */
class TranslateWithPrefix extends CakeTestModel {
/**
 * name property
 *
 * @var string 'TranslateTestModel'
 */
	public $name = 'TranslateWithPrefix';
/**
 * tablePrefix property
 *
 * @var string 'i18n'
 */
	public $tablePrefix = 'i18n_';
/**
 * displayField property
 *
 * @var string 'field'
 */
	public $displayField = 'field';
}
/**
 * TranslatedItem class.
 *
 * @package       Cake.Test.Case.Model
 */
class TranslatedItem extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TranslatedItem'
 */
	public $name = 'TranslatedItem';

/**
 * cacheQueries property
 *
 * @var bool false
 */
	public $cacheQueries = false;

/**
 * actsAs property
 *
 * @var array
 */
	public $actsAs = array('Translate' => array('content', 'title'));

/**
 * translateModel property
 *
 * @var string 'TranslateTestModel'
 */
	public $translateModel = 'TranslateTestModel';
}

/**
 * TranslatedItem class.
 *
 * @package       Cake.Test.Case.Model
 */
class TranslatedItem2 extends CakeTestModel {
/**
 * name property
 *
 * @var string 'TranslatedItem'
 */
	public $name = 'TranslatedItem';
/**
 * cacheQueries property
 *
 * @var bool false
 */
	public $cacheQueries = false;
/**
 * actsAs property
 *
 * @var array
 */
	public $actsAs = array('Translate' => array('content', 'title'));
/**
 * translateModel property
 *
 * @var string 'TranslateTestModel'
 */
	public $translateModel = 'TranslateWithPrefix';
}
/**
 * TranslatedItemWithTable class.
 *
 * @package       Cake.Test.Case.Model
 */
class TranslatedItemWithTable extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TranslatedItemWithTable'
 */
	public $name = 'TranslatedItemWithTable';

/**
 * useTable property
 *
 * @var string 'translated_items'
 */
	public $useTable = 'translated_items';

/**
 * cacheQueries property
 *
 * @var bool false
 */
	public $cacheQueries = false;

/**
 * actsAs property
 *
 * @var array
 */
	public $actsAs = array('Translate' => array('content', 'title'));

/**
 * translateModel property
 *
 * @var string 'TranslateTestModel'
 */
	public $translateModel = 'TranslateTestModel';

/**
 * translateTable property
 *
 * @var string 'another_i18n'
 */
	public $translateTable = 'another_i18n';
}

/**
 * TranslateArticleModel class.
 *
 * @package       Cake.Test.Case.Model
 */
class TranslateArticleModel extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TranslateArticleModel'
 */
	public $name = 'TranslateArticleModel';

/**
 * useTable property
 *
 * @var string 'article_i18n'
 */
	public $useTable = 'article_i18n';

/**
 * displayField property
 *
 * @var string 'field'
 */
	public $displayField = 'field';
}

/**
 * TranslatedArticle class.
 *
 * @package       Cake.Test.Case.Model
 */
class TranslatedArticle extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TranslatedArticle'
 */
	public $name = 'TranslatedArticle';

/**
 * cacheQueries property
 *
 * @var bool false
 */
	public $cacheQueries = false;

/**
 * actsAs property
 *
 * @var array
 */
	public $actsAs = array('Translate' => array('title', 'body'));

/**
 * translateModel property
 *
 * @var string 'TranslateArticleModel'
 */
	public $translateModel = 'TranslateArticleModel';

/**
 * belongsTo property
 *
 * @var array
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
	public $name = 'TransactionTestModel';
	public $useTable = 'samples';

	public function afterSave($created) {
		$data = array(
			array('apple_id' => 1, 'name' => 'sample6'),
		);
		$this->saveAll($data, array('atomic' => true, 'callbacks' => false));
	}
}

class TransactionManyTestModel extends CakeTestModel {
	public $name = 'TransactionManyTestModel';
	public $useTable = 'samples';

	public function afterSave($created) {
		$data = array(
			array('apple_id' => 1, 'name' => 'sample6'),
		);
		$this->saveMany($data, array('atomic' => true, 'callbacks' => false));
	}
}

/**
 * TestModel class
 *
 * @package       Cake.Test.Case.Model
 */
class TestModel extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel'
 */
	public $name = 'TestModel';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * schema property
 *
 * @var array
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
 * @return void
 */
	public function find($conditions = null, $fields = null, $order = null, $recursive = null) {
		return array($conditions, $fields);
	}

/**
 * findAll method
 *
 * @param mixed $conditions
 * @param mixed $fields
 * @param mixed $order
 * @param mixed $recursive
 * @return void
 */
	public function findAll($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}
}

/**
 * TestModel2 class
 *
 * @package       Cake.Test.Case.Model
 */
class TestModel2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel2'
 */
	public $name = 'TestModel2';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;
}

/**
 * TestModel4 class
 *
 * @package       Cake.Test.Case.Model
 */
class TestModel3 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel3'
 */
	public $name = 'TestModel3';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;
}

/**
 * TestModel4 class
 *
 * @package       Cake.Test.Case.Model
 */
class TestModel4 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel4'
 */
	public $name = 'TestModel4';

/**
 * table property
 *
 * @var string 'test_model4'
 */
	public $table = 'test_model4';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
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
 * @package       Cake.Test.Case.Model
 */
class TestModel4TestModel7 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel4TestModel7'
 */
	public $name = 'TestModel4TestModel7';

/**
 * table property
 *
 * @var string 'test_model4_test_model7'
 */
	public $table = 'test_model4_test_model7';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * schema method
 *
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
 * @package       Cake.Test.Case.Model
 */
class TestModel5 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel5'
 */
	public $name = 'TestModel5';

/**
 * table property
 *
 * @var string 'test_model5'
 */
	public $table = 'test_model5';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('TestModel4' => array(
		'className' => 'TestModel4',
		'foreignKey' => 'test_model4_id'
	));

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array('TestModel6' => array(
		'className' => 'TestModel6',
		'foreignKey' => 'test_model5_id'
	));

/**
 * schema method
 *
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
 * @package       Cake.Test.Case.Model
 */
class TestModel6 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel6'
 */
	public $name = 'TestModel6';

/**
 * table property
 *
 * @var string 'test_model6'
 */
	public $table = 'test_model6';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('TestModel5' => array(
		'className' => 'TestModel5',
		'foreignKey' => 'test_model5_id'
	));

/**
 * schema method
 *
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
 * @package       Cake.Test.Case.Model
 */
class TestModel7 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel7'
 */
	public $name = 'TestModel7';

/**
 * table property
 *
 * @var string 'test_model7'
 */
	public $table = 'test_model7';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * schema method
 *
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
 * @package       Cake.Test.Case.Model
 */
class TestModel8 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel8'
 */
	public $name = 'TestModel8';

/**
 * table property
 *
 * @var string 'test_model8'
 */
	public $table = 'test_model8';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * hasOne property
 *
 * @var array
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
 * @package       Cake.Test.Case.Model
 */
class TestModel9 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel9'
 */
	public $name = 'TestModel9';

/**
 * table property
 *
 * @var string 'test_model9'
 */
	public $table = 'test_model9';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('TestModel8' => array(
		'className' => 'TestModel8',
		'foreignKey' => 'test_model8_id',
		'conditions' => 'TestModel8.name != \'larry\''
	));

/**
 * schema method
 *
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
 * @package       Cake.Test.Case.Model
 */
class Level extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Level'
 */
	public $name = 'Level';

/**
 * table property
 *
 * @var string 'level'
 */
	public $table = 'level';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * hasMany property
 *
 * @var array
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
 * @package       Cake.Test.Case.Model
 */
class Group extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Group'
 */
	public $name = 'Group';

/**
 * table property
 *
 * @var string 'group'
 */
	public $table = 'group';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('Level');

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array('Category2', 'User2');

/**
 * schema method
 *
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
 * @package       Cake.Test.Case.Model
 */
class User2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'User2'
 */
	public $name = 'User2';

/**
 * table property
 *
 * @var string 'user'
 */
	public $table = 'user';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
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
 */
	public $hasMany = array(
		'Article2' => array(
			'className' => 'Article2'
		),
	);

/**
 * schema method
 *
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
 * @package       Cake.Test.Case.Model
 */
class Category2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Category2'
 */
	public $name = 'Category2';

/**
 * table property
 *
 * @var string 'category'
 */
	public $table = 'category';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
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
 * @package       Cake.Test.Case.Model
 */
class Article2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Article2'
 */
	public $name = 'Article2';

/**
 * table property
 *
 * @var string 'article'
 */
	public $table = 'articles';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array(
		'Category2' => array('className' => 'Category2'),
		'User2' => array('className' => 'User2')
	);

/**
 * schema method
 *
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
 * @package       Cake.Test.Case.Model
 */
class CategoryFeatured2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'CategoryFeatured2'
 */
	public $name = 'CategoryFeatured2';

/**
 * table property
 *
 * @var string 'category_featured'
 */
	public $table = 'category_featured';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * schema method
 *
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
 * @package       Cake.Test.Case.Model
 */
class Featured2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Featured2'
 */
	public $name = 'Featured2';

/**
 * table property
 *
 * @var string 'featured2'
 */
	public $table = 'featured2';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array(
		'CategoryFeatured2' => array(
			'className' => 'CategoryFeatured2'
		)
	);

/**
 * schema method
 *
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
 * @package       Cake.Test.Case.Model
 */
class Comment2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Comment2'
 */
	public $name = 'Comment2';

/**
 * table property
 *
 * @var string 'comment'
 */
	public $table = 'comment';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('ArticleFeatured2', 'User2');

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * schema method
 *
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
 * @package       Cake.Test.Case.Model
 */
class ArticleFeatured2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ArticleFeatured2'
 */
	public $name = 'ArticleFeatured2';

/**
 * table property
 *
 * @var string 'article_featured'
 */
	public $table = 'article_featured';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array(
		'CategoryFeatured2' => array('className' => 'CategoryFeatured2'),
		'User2' => array('className' => 'User2')
	);

/**
 * hasOne property
 *
 * @var array
 */
	public $hasOne = array(
		'Featured2' => array('className' => 'Featured2')
	);

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array(
		'Comment2' => array('className'=>'Comment2', 'dependent' => true)
	);

/**
 * schema method
 *
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
 * @package       Cake.Test.Case.Model
 */
class MysqlTestModel extends Model {

/**
 * name property
 *
 * @var string 'MysqlTestModel'
 */
	public $name = 'MysqlTestModel';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * find method
 *
 * @param mixed $conditions
 * @param mixed $fields
 * @param mixed $order
 * @param mixed $recursive
 * @return void
 */
	public function find($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}

/**
 * findAll method
 *
 * @param mixed $conditions
 * @param mixed $fields
 * @param mixed $order
 * @param mixed $recursive
 * @return void
 */
	public function findAll($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}

/**
 * schema method
 *
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

/**
 * Test model for datasource prefixes
 *
 */
class PrefixTestModel extends CakeTestModel {
}
class PrefixTestUseTableModel extends CakeTestModel {
       public $name = 'PrefixTest';
       public $useTable = 'prefix_tests';
}

/**
 * ScaffoldMock class
 *
 * @package       Cake.Test.Case.Controller
 */
class ScaffoldMock extends CakeTestModel {

/**
 * useTable property
 *
 * @var string 'posts'
 */
	public $useTable = 'articles';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array(
		'User' => array(
			'className' => 'ScaffoldUser',
			'foreignKey' => 'user_id',
		)
	);

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array(
		'Comment' => array(
			'className' => 'ScaffoldComment',
			'foreignKey' => 'article_id',
		)
	);
/**
 * hasAndBelongsToMany property
 *
 * @var string
 */
	public $hasAndBelongsToMany = array(
		'ScaffoldTag' => array(
			'className' => 'ScaffoldTag',
			'foreignKey' => 'something_id',
			'associationForeignKey' => 'something_else_id',
			'joinTable' => 'join_things'
		)
	);
}

/**
 * ScaffoldUser class
 *
 * @package       Cake.Test.Case.Controller
 */
class ScaffoldUser extends CakeTestModel {

/**
 * useTable property
 *
 * @var string 'posts'
 */
	public $useTable = 'users';

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array(
		'Article' => array(
			'className' => 'ScaffoldMock',
			'foreignKey' => 'article_id',
		)
	);
}

/**
 * ScaffoldComment class
 *
 * @package       Cake.Test.Case.Controller
 */
class ScaffoldComment extends CakeTestModel {

/**
 * useTable property
 *
 * @var string 'posts'
 */
	public $useTable = 'comments';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array(
		'Article' => array(
			'className' => 'ScaffoldMock',
			'foreignKey' => 'article_id',
		)
	);
}

/**
 * ScaffoldTag class
 *
 * @package       Cake.Test.Case.Controller
 */
class ScaffoldTag extends CakeTestModel {
/**
 * useTable property
 *
 * @var string 'posts'
 */
	public $useTable = 'tags';
}
