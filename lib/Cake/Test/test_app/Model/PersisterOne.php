<?php
/**
 * Test App Comment Model
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.TestApp.Model
 * @since         CakePHP v 1.2.0.7726
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * PersisterOne
 *
 * @package       Cake.Test.TestApp.Model
 */
class PersisterOne extends AppModel {

	public $useTable = 'posts';

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
				'rule' => array('lengthBetween', 5, 15),
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
