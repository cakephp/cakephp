<?php
/**
 * Test App Comment Model
 *
 *
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
 * @package       Cake.Test.test_app.Model
 * @since         CakePHP v 1.2.0.7726
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class PersisterOne extends AppModel {
	public $useTable = 'posts';
	public $name = 'PersisterOne';

	public $actsAs = array('PersisterOneBehavior', 'TestPlugin.TestPluginPersisterOne');

	public $hasMany = array('Comment', 'TestPlugin.TestPluginComment');
	public $validate = array(
		'title' => array(
			'custom' => array(
				'rule' => array('custom', '.*'),
				'allowEmpty' => true,
				'required' => false,
				'message' => 'Post title is required'
			),
			'between' => array(
				'rule' => array('between', 5, 15),
				'message' => array('You may enter up to %s chars (minimum is %s chars)', 14, 6)
			)
		),
		'body' => array(
			'first_rule' => array(
				'rule' => array('custom', '.*'),
				'allowEmpty' => true,
				'required' => false,
				'message' => 'Post body is required'
			),
			'second_rule' => array(
				'rule' => array('custom', '.*'),
				'allowEmpty' => true,
				'required' => false,
				'message' => 'Post body is super required'
			)
		),
	);
}
