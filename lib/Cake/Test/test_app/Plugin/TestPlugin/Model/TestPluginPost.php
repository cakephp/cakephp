<?php
/**
 * Test Plugin Post Model
 *
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.TestApp.Plugin.TestPlugin.Model
 * @since         CakePHP v 1.2.0.4487
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestPluginPost
 *
 * @package       Cake.Test.TestApp.Plugin.TestPlugin.Model
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
