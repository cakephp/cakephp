<?php
/**
 * Test Plugin Post Model
 *
 *
 *
 * PHP 5
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
 * @package       Cake.Test.test_app.Plugin.TestPlugin.Model
 * @since         CakePHP v 1.2.0.4487
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TestPluginPost extends TestPluginAppModel {

/**
 * Name property
 *
 * @var string
 */
	public $name = 'Post';

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'posts';

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array(
		'title' => array(
			'rule' => array('custom', '.*'),
			'allowEmpty' => true,
			'required' => false,
			'message' => 'Post title is required'
		),
		'body' => array(
			'first_rule' => array(
				'rule' => array('custom', '.*'),
				'allowEmpty' => true,
				'required' => false,
				'message' => 'Post body is required'
			),
			'Post body is super required' => array(
				'rule' => array('custom', '.*'),
				'allowEmpty' => true,
				'required' => false,
			)
		),
	);

/**
 * Translation domain to use for validation messages
 *
 * @var string
 */
	public $validationDomain = 'test_plugin';

}
