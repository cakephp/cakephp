<?php
/**
 * Mock models file
 *
 * Mock classes for use in Model and related test cases
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
 * @package       Cake.Test.Case.Model
 * @since         CakePHP(tm) v 1.2.0.6464
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Model', 'Model');

/**
 * AppModel class
 *
 * @package       Cake.Test.Case.Model
 */
class AppModel extends Model {

/**
 * findMethods property
 *
 * @var array
 */
	public $findMethods = array('published' => true);

/**
 * useDbConfig property
 *
 * @var array
 */
	public $useDbConfig = 'test';

/**
 * _findPublished custom find
 *
 * @return array
 */
	protected function _findPublished($state, $query, $results = array()) {
		if ($state === 'before') {
			$query['conditions']['published'] = 'Y';
			return $query;
		}
		return $results;
	}

}

/**
 * Test class
 *
 * @package       Cake.Test.Case.Model
 */
class Test extends CakeTestModel {

/**
 * useTable property
 *
 * @var boolean
 */
	public $useTable = false;

/**
 * name property
 *
 * @var string
 */
	public $name = 'Test';

/**
 * schema property
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '1', 'length' => '8', 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'email' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'notes' => array('type' => 'text', 'null' => '1', 'default' => 'write some notes here', 'length' => ''),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
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
 * @var boolean
 */
	public $useTable = false;

/**
 * name property
 *
 * @var string
 */
	public $name = 'TestAlias';

/**
 * schema property
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '1', 'length' => '8', 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'email' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'notes' => array('type' => 'text', 'null' => '1', 'default' => 'write some notes here', 'length' => ''),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
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
 * @var boolean
 */
	public $useTable = false;

/**
 * name property
 *
 * @var string
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
		$options += array('min' => 0, 'max' => 100);
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
 * @var string
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
 * @return boolean
 * @throws Exception
 */
	public function beforeFind($queryData) {
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
 * @var string
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
	public $validate = array(
		'user_id' => 'numeric',
		'title' => array('required' => false, 'rule' => 'notEmpty'),
		'body' => array('required' => false, 'rule' => 'notEmpty'),
	);

/**
 * beforeSaveReturn property
 *
 * @var boolean
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
 * @param string $title
 * @return void
 */
	public static function titleDuplicate($title) {
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
 * @var string
 */
	public $name = 'NumericArticle';

/**
 * useTable property
 *
 * @var string
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
 * @var string
 */
	public $name = 'Article10';

/**
 * useTable property
 *
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
 */
	public $name = 'Comment';

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'comments';

/**
 * Property used to toggle filtering of results
 *
 * @var boolean
 */
	public $remove = false;

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
		if ($this->remove) {
			return array();
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
 * @var string
 */
	public $name = 'Comment';

/**
 * useTable property
 *
 * @var string
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
 * @var string
 */
	public $name = 'Attachment';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('Comment');
}

/**
 * ModifiedAttachment class
 *
 * @package       Cake.Test.Case.Model
 */
class ModifiedAttachment extends CakeTestModel {

/**
 * name property
 *
 * @var string
 */
	public $name = 'ModifiedAttachment';

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'attachments';

/**
 * afterFind callback
 *
 * @return void
 */
	public function afterFind($results, $primary = false) {
		if (isset($results['id'])) {
			$results['callback'] = 'Fired';
		}
		return $results;
	}

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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
 */
	public $name = 'Sample';

/**
 * belongsTo property
 *
 * @var string
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
 * @var string
 */
	public $name = 'AnotherArticle';

/**
 * hasMany property
 *
 * @var string
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
 * @var string
 */
	public $name = 'Advertisement';

/**
 * hasMany property
 *
 * @var string
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
 * @var string
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
 * @var string
 */
	public $name = 'Post';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('Author');

/**
 * @param array $queryData
 * @return boolean true
 */
	public function beforeFind($queryData) {
		if (isset($queryData['connection'])) {
			$this->useDbConfig = $queryData['connection'];
		}
		return true;
	}

/**
 * @param array $results
 * @param boolean $primary
 * @return array $results
 */
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
 * @var string
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
 * @param array $results
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
 * @var string
 */
	public $name = 'Author';

/**
 * afterFind method
 *
 * @param array $results
 * @return void
 */
	public function afterFind($results, $primary = false) {
		foreach ($results as $index => $result) {
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * BiddingMessage class
 *
 * @package       Cake.Test.Case.Model
 */
class BiddingMessage extends CakeTestModel {

/**
 * name property
 *
 * @var string
 */
	public $name = 'BiddingMessage';

/**
 * primaryKey property
 *
 * @var string
 */
	public $primaryKey = 'bidding';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array(
		'Bidding' => array(
			'foreignKey' => false,
			'conditions' => array('BiddingMessage.bidding = Bidding.bid')
		)
	);
}

/**
 * Bidding class
 *
 * @package       Cake.Test.Case.Model
 */
class Bidding extends CakeTestModel {

/**
 * name property
 *
 * @var string
 */
	public $name = 'Bidding';

/**
 * hasOne property
 *
 * @var array
 */
	public $hasOne = array(
		'BiddingMessage' => array(
			'foreignKey' => false,
			'conditions' => array('BiddingMessage.bidding = Bidding.bid'),
			'dependent' => true
		)
	);
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
 * @var string
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
 * @var string
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
 * @return array
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
 * @var string
 */
	public $name = 'NodeAfterFindSample';

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'samples';

/**
 * belongsTo property
 *
 * @var string
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
 * @var string
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
 * @var string
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
class Node extends CakeTestModel {

/**
 * name property
 *
 * @var string
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
 * @var string
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
 * @var string
 */
	public $name = 'ModelA';

/**
 * useTable property
 *
 * @var string
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
 * @var string
 */
	public $name = 'ModelB';

/**
 * useTable property
 *
 * @var string
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
 * @var string
 */
	public $name = 'ModelC';

/**
 * useTable property
 *
 * @var string
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
 * @var string
 */
	public $name = 'ModelD';

/**
 * useTable property
 *
 * @var string
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
 * @var string
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
 * @var string
 */
	public $name = 'SomethingElse';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('Something' => array('with' => 'JoinThing'));

/**
 * afterFind callBack
 *
 * @param array $results
 * @param bool $primary
 * @return array
 */
	public function afterFind($results, $primary = false) {
		foreach ($results as $key => $result) {
			if (!empty($result[$this->alias]) && is_array($result[$this->alias])) {
				$results[$key][$this->alias]['afterFind'] = 'Successfully added by AfterFind';
			}
		}
		return $results;
	}

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
 * @var string
 */
	public $name = 'JoinThing';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('Something', 'SomethingElse');

/**
 * afterFind callBack
 *
 * @param array $results
 * @param bool $primary
 * @return array
 */
	public function afterFind($results, $primary = false) {
		foreach ($results as $key => $result) {
			if (!empty($result[$this->alias]) && is_array($result[$this->alias])) {
				$results[$key][$this->alias]['afterFind'] = 'Successfully added by AfterFind';
			}
		}
		return $results;
	}

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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
 */
	public $name = 'ThePaper';

/**
 * useTable property
 *
 * @var string
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
 * @var string
 */
	public $name = 'Monkey';

/**
 * useTable property
 *
 * @var string
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
 * @var string
 */
	public $useTable = 'join_as';

/**
 * name property
 *
 * @var string
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
 * @var string
 */
	public $useTable = 'join_bs';

/**
 * name property
 *
 * @var string
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
 * @var boolean
 */
	public $beforeValidateReturn = true;

/**
 * variable to control return of beforeSave
 *
 * @var boolean
 */
	public $beforeSaveReturn = true;

/**
 * variable to control return of beforeDelete
 *
 * @var boolean
 */
	public $beforeDeleteReturn = true;

/**
 * beforeSave callback
 *
 * @return boolean
 */
	public function beforeSave($options = array()) {
		return $this->beforeSaveReturn;
	}

/**
 * beforeValidate callback
 *
 * @param array $options Options passed from Model::save().
 * @return boolean True if validate operation should continue, false to abort
 * @see Model::save()
 */
	public function beforeValidate($options = array()) {
		return $this->beforeValidateReturn;
	}

/**
 * beforeDelete callback
 *
 * @return boolean
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
 * @var string
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
 * @var string
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
 * @var string
 */
	public $name = 'TheVoid';

/**
 * useTable property
 *
 * @var boolean
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
 * @var string
 */
	public $name = 'ValidationTest1';

/**
 * useTable property
 *
 * @var boolean
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
	public function customValidatorWithParams($data, $validator, $or = true, $ignoreOnSame = 'id') {
		$this->validatorParams = get_defined_vars();
		unset($this->validatorParams['this']);
		return true;
	}

/**
 * Custom validator with message
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
 * @var string
 */
	public $name = 'ValidationTest2';

/**
 * useTable property
 *
 * @var boolean
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
 * @var string
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
			'foreignKey' => 'mother_id'
		),
		'Father' => array(
			'className' => 'Person',
			'foreignKey' => 'father_id'
		)
	);
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
 * @var string
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
 * @var string
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
 * @var string
 */
	public $name = 'Story';

/**
 * primaryKey property
 *
 * @var string
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
 * @var string
 */
	public $name = 'Cd';

/**
 * hasOne property
 *
 * @var array
 */
	public $hasOne = array(
		'OverallFavorite' => array(
			'foreignKey' => 'model_id',
			'dependent' => true,
			'conditions' => array('model_type' => 'Cd')
		)
	);

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
 * @var string
 */
	public $name = 'Book';

/**
 * hasOne property
 *
 * @var array
 */
	public $hasOne = array(
		'OverallFavorite' => array(
			'foreignKey' => 'model_id',
			'dependent' => true,
			'conditions' => 'OverallFavorite.model_type = \'Book\''
		)
	);

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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @param integer $levelLimit
 * @param integer $childLimit
 * @param mixed $currentLevel
 * @param mixed $parent_id
 * @param string $prefix
 * @param boolean $hierarchal
 * @return void
 */
	public function initialize($levelLimit = 3, $childLimit = 3, $currentLevel = null, $parentId = null, $prefix = '1', $hierarchal = true) {
		if (!$parentId) {
			$db = ConnectionManager::getDataSource($this->useDbConfig);
			$db->truncate($this->table);
			$this->save(array($this->name => array('name' => '1. Root')));
			$this->initialize($levelLimit, $childLimit, 1, $this->id, '1', $hierarchal);
			$this->create(array());
		}

		if (!$currentLevel || $currentLevel > $levelLimit) {
			return;
		}

		for ($i = 1; $i <= $childLimit; $i++) {
			$name = $prefix . '.' . $i;
			$data = array($this->name => array('name' => $name));
			$this->create($data);

			if ($hierarchal) {
				if ($this->name === 'UnconventionalTree') {
					$data[$this->name]['join'] = $parentId;
				} else {
					$data[$this->name]['parent_id'] = $parentId;
				}
			}
			$this->save($data);
			$this->initialize($levelLimit, $childLimit, $currentLevel + 1, $this->id, $name, $hierarchal);
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
 * @var string
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
 * @var string
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
 * @var string
 */
	public $name = 'UnconventionalTree';

	public $actsAs = array(
		'Tree' => array(
			'parent' => 'join',
			'left' => 'left',
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
 * @var string
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
 * @var string
 */
	public $name = 'Campaign';

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array('Ad' => array('fields' => array('id', 'campaign_id', 'name')));
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
 * @var string
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
 * @var string
 */
	public $name = 'AfterTree';

/**
 * actsAs property
 *
 * @var array
 */
	public $actsAs = array('Tree');

/**
 * @param boolean $created
 * @param array $options
 * @return void
 */
	public function afterSave($created, $options = array()) {
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
 * @var string
 */
	public $name = 'Content';

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'Content';

/**
 * primaryKey property
 *
 * @var string
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
 * @var string
 */
	public $name = 'Account';

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'Accounts';

/**
 * primaryKey property
 *
 * @var string
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
 * @var string
 */
	public $name = 'ContentAccount';

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'ContentAccounts';

/**
 * primaryKey property
 *
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
 */
	public $name = 'TranslateTestModel';

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'i18n';

/**
 * displayField property
 *
 * @var string
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
 * @var string
 */
	public $name = 'TranslateWithPrefix';

/**
 * tablePrefix property
 *
 * @var string
 */
	public $tablePrefix = 'i18n_';

/**
 * displayField property
 *
 * @var string
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
 * @var string
 */
	public $name = 'TranslatedItem';

/**
 * cacheQueries property
 *
 * @var boolean
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
 * @var string
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
 * @var string
 */
	public $name = 'TranslatedItem';

/**
 * cacheQueries property
 *
 * @var boolean
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
 * @var string
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
 * @var string
 */
	public $name = 'TranslatedItemWithTable';

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'translated_items';

/**
 * cacheQueries property
 *
 * @var boolean
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
 * @var string
 */
	public $translateModel = 'TranslateTestModel';

/**
 * translateTable property
 *
 * @var string
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
 * @var string
 */
	public $name = 'TranslateArticleModel';

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'article_i18n';

/**
 * displayField property
 *
 * @var string
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
 * @var string
 */
	public $name = 'TranslatedArticle';

/**
 * cacheQueries property
 *
 * @var boolean
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
 * @var string
 */
	public $translateModel = 'TranslateArticleModel';

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
	public $hasMany = array('TranslatedItem');

}

class CounterCacheUser extends CakeTestModel {

	public $name = 'CounterCacheUser';

	public $alias = 'User';

	public $hasMany = array(
		'Post' => array(
			'className' => 'CounterCachePost',
			'foreignKey' => 'user_id'
		)
	);
}

class CounterCachePost extends CakeTestModel {

	public $name = 'CounterCachePost';

	public $alias = 'Post';

	public $belongsTo = array(
		'User' => array(
			'className' => 'CounterCacheUser',
			'foreignKey' => 'user_id',
			'counterCache' => true
		)
	);
}

class CounterCacheUserNonstandardPrimaryKey extends CakeTestModel {

	public $name = 'CounterCacheUserNonstandardPrimaryKey';

	public $alias = 'User';

	public $primaryKey = 'uid';

	public $hasMany = array(
		'Post' => array(
			'className' => 'CounterCachePostNonstandardPrimaryKey',
			'foreignKey' => 'uid'
		)
	);

}

class CounterCachePostNonstandardPrimaryKey extends CakeTestModel {

	public $name = 'CounterCachePostNonstandardPrimaryKey';

	public $alias = 'Post';

	public $primaryKey = 'pid';

	public $belongsTo = array(
		'User' => array(
			'className' => 'CounterCacheUserNonstandardPrimaryKey',
			'foreignKey' => 'uid',
			'counterCache' => true
		)
	);

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

	public function afterSave($created, $options = array()) {
		$data = array(
			array('apple_id' => 1, 'name' => 'sample6'),
		);
		$this->saveAll($data, array('atomic' => true, 'callbacks' => false));
	}

}

class TransactionManyTestModel extends CakeTestModel {

	public $name = 'TransactionManyTestModel';

	public $useTable = 'samples';

	public function afterSave($created, $options = array()) {
		$data = array(
			array('apple_id' => 1, 'name' => 'sample6'),
		);
		$this->saveMany($data, array('atomic' => true, 'callbacks' => false));
	}

}

class Site extends CakeTestModel {

	public $name = 'Site';

	public $useTable = 'sites';

	public $hasAndBelongsToMany = array(
		'Domain' => array('unique' => 'keepExisting'),
	);
}

class Domain extends CakeTestModel {

	public $name = 'Domain';

	public $useTable = 'domains';

	public $hasAndBelongsToMany = array(
		'Site' => array('unique' => 'keepExisting'),
	);
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
 * @var string
 */
	public $name = 'TestModel';

/**
 * useTable property
 *
 * @var boolean
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
 * @var string
 */
	public $name = 'TestModel2';

/**
 * useTable property
 *
 * @var boolean
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
 * @var string
 */
	public $name = 'TestModel3';

/**
 * useTable property
 *
 * @var boolean
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
 * @var string
 */
	public $name = 'TestModel4';

/**
 * table property
 *
 * @var string
 */
	public $table = 'test_model4';

/**
 * useTable property
 *
 * @var boolean
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
 * @var string
 */
	public $name = 'TestModel4TestModel7';

/**
 * table property
 *
 * @var string
 */
	public $table = 'test_model4_test_model7';

/**
 * useTable property
 *
 * @var boolean
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
 * @var string
 */
	public $name = 'TestModel5';

/**
 * table property
 *
 * @var string
 */
	public $table = 'test_model5';

/**
 * useTable property
 *
 * @var boolean
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
 * @var string
 */
	public $name = 'TestModel6';

/**
 * table property
 *
 * @var string
 */
	public $table = 'test_model6';

/**
 * useTable property
 *
 * @var boolean
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array(
		'TestModel5' => array(
			'className' => 'TestModel5',
			'foreignKey' => 'test_model5_id'
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
 * @var string
 */
	public $name = 'TestModel7';

/**
 * table property
 *
 * @var string
 */
	public $table = 'test_model7';

/**
 * useTable property
 *
 * @var boolean
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
 * @var string
 */
	public $name = 'TestModel8';

/**
 * table property
 *
 * @var string
 */
	public $table = 'test_model8';

/**
 * useTable property
 *
 * @var boolean
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
 * @var string
 */
	public $name = 'TestModel9';

/**
 * table property
 *
 * @var string
 */
	public $table = 'test_model9';

/**
 * useTable property
 *
 * @var boolean
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array(
		'TestModel8' => array(
			'className' => 'TestModel8',
			'foreignKey' => 'test_model8_id',
			'conditions' => 'TestModel8.name != \'larry\''
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
 * @var string
 */
	public $name = 'Level';

/**
 * table property
 *
 * @var string
 */
	public $table = 'level';

/**
 * useTable property
 *
 * @var boolean
 */
	public $useTable = false;

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array(
		'Group' => array(
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
 * @var string
 */
	public $name = 'Group';

/**
 * table property
 *
 * @var string
 */
	public $table = 'group';

/**
 * useTable property
 *
 * @var boolean
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
 * @var string
 */
	public $name = 'User2';

/**
 * table property
 *
 * @var string
 */
	public $table = 'user';

/**
 * useTable property
 *
 * @var boolean
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
 * @var string
 */
	public $name = 'Category2';

/**
 * table property
 *
 * @var string
 */
	public $table = 'category';

/**
 * useTable property
 *
 * @var boolean
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
			'order' => 'Article2.published_date DESC',
			'foreignKey' => 'category_id',
			'limit' => '3')
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
 * @var string
 */
	public $name = 'Article2';

/**
 * table property
 *
 * @var string
 */
	public $table = 'articles';

/**
 * useTable property
 *
 * @var boolean
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
 * @var string
 */
	public $name = 'CategoryFeatured2';

/**
 * table property
 *
 * @var string
 */
	public $table = 'category_featured';

/**
 * useTable property
 *
 * @var boolean
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
 * @var string
 */
	public $name = 'Featured2';

/**
 * table property
 *
 * @var string
 */
	public $table = 'featured2';

/**
 * useTable property
 *
 * @var boolean
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
 * @var string
 */
	public $name = 'Comment2';

/**
 * table property
 *
 * @var string
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
 * @var boolean
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
 * @var string
 */
	public $name = 'ArticleFeatured2';

/**
 * table property
 *
 * @var string
 */
	public $table = 'article_featured';

/**
 * useTable property
 *
 * @var boolean
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
		'Comment2' => array('className' => 'Comment2', 'dependent' => true)
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
 * @var string
 */
	public $name = 'MysqlTestModel';

/**
 * useTable property
 *
 * @var boolean
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
			'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'client_id' => array('type' => 'integer', 'null' => '', 'default' => '0', 'length' => '11'),
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
			'comments' => array('type' => 'text', 'null' => '1', 'default' => '', 'length' => ''),
			'last_login' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => ''),
			'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
			'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
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
 * @var string
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
 * @var string
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
 * @var string
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
 * @var string
 */
	public $useTable = 'tags';

}

/**
 * Player class
 *
 * @package       Cake.Test.Case.Model
 */
class Player extends CakeTestModel {

	public $hasAndBelongsToMany = array(
		'Guild' => array(
			'with' => 'GuildsPlayer',
			'unique' => true,
		),
	);

}

/**
 * Guild class
 *
 * @package       Cake.Test.Case.Model
 */
class Guild extends CakeTestModel {

	public $hasAndBelongsToMany = array(
		'Player' => array(
			'with' => 'GuildsPlayer',
			'unique' => true,
		),
	);

}

/**
 * GuildsPlayer class
 *
 * @package       Cake.Test.Case.Model
 */
class GuildsPlayer extends CakeTestModel {

	public $useDbConfig = 'test2';

	public $belongsTo = array(
		'Player',
		'Guild',
		);
}

/**
 * Armor class
 *
 * @package       Cake.Test.Case.Model
 */
class Armor extends CakeTestModel {

	public $useDbConfig = 'test2';

	public $hasAndBelongsToMany = array(
		'Player' => array('with' => 'ArmorsPlayer'),
		);
}

/**
 * ArmorsPlayer class
 *
 * @package       Cake.Test.Case.Model
 */
class ArmorsPlayer extends CakeTestModel {

	public $useDbConfig = 'test_database_three';

}

/**
 * CustomArticle class
 *
 * @package       Cake.Test.Case.Model
 */
class CustomArticle extends AppModel {

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'articles';

/**
 * findMethods property
 *
 * @var array
 */
	public $findMethods = array('unPublished' => true);

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('User');

/**
 * _findUnPublished custom find
 *
 * @return array
 */
	protected function _findUnPublished($state, $query, $results = array()) {
		if ($state === 'before') {
			$query['conditions']['published'] = 'N';
			return $query;
		}
		return $results;
	}

/**
 * Alters title data
 *
 * @param array $options Options passed from Model::save().
 * @return boolean True if validate operation should continue, false to abort
 * @see Model::save()
 */
	public function beforeValidate($options = array()) {
		$this->data[$this->alias]['title'] = 'foo';
		if ($this->findMethods['unPublished'] === true) {
			$this->findMethods['unPublished'] = false;
		} else {
			$this->findMethods['unPublished'] = 'true again';
		}
	}

}
