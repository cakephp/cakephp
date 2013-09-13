<?php
/**
 * PaginatorControllerPost
 *
 * PHP 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.TestApp.Model
 * @since         CakePHP v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace TestApp\Model;

use Cake\TestSuite\Fixture\TestModel;
use Cake\Utility\Hash;

/**
 * PaginatorControllerPost class
 *
 */
class PaginatorControllerPost extends TestModel {

/**
 * name property
 *
 * @var string 'PaginatorControllerPost'
 */
	public $name = 'PaginatorControllerPost';

/**
 * useTable property
 *
 * @var string 'posts'
 */
	public $useTable = 'posts';

/**
 * invalidFields property
 *
 * @var array
 */
	public $invalidFields = array('name' => 'error_msg');

/**
 * lastQueries property
 *
 * @var array
 */
	public $lastQueries = array();

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('PaginatorAuthor' => array('foreignKey' => 'author_id'));

/**
 * beforeFind method
 *
 * @param mixed $query
 * @return void
 */
	public function beforeFind($query) {
		array_unshift($this->lastQueries, $query);
	}

/**
 * find method
 *
 * @param mixed $type
 * @param array $options
 * @return void
 */
	public function find($conditions = null, $fields = array(), $order = null, $recursive = null) {
		if ($conditions == 'popular') {
			$conditions = array($this->name . '.' . $this->primaryKey . ' > ' => '1');
			$options = Hash::merge($fields, compact('conditions'));
			return parent::find('all', $options);
		}
		return parent::find($conditions, $fields);
	}
}
