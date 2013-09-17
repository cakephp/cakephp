<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace TestApp\Model;

use Cake\TestSuite\Fixture\TestModel;

/**
 * Article class
 *
 */
class Article extends TestModel {

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
	public $validate = array(
		'user_id' => 'numeric',
		'title' => array('required' => false, 'rule' => 'notEmpty'),
		'body' => array('required' => false, 'rule' => 'notEmpty'),
	);

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
