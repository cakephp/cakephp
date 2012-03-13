<?php
/**
 * Tree behavior class test fixture.
 *
 * Enables a model object to act as a node-based tree.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.5331
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Flag Tree Test Fixture
 *
 * Like Number Tree, but uses a flag for testing scope parameters
 *
 * @package       Cake.Test.Fixture
 */
class FlagTreeFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'FlagTree'
 */
	public $name = 'FlagTree';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id'	=> array('type' => 'integer','key' => 'primary'),
		'name'	=> array('type' => 'string','null' => false),
		'parent_id' => 'integer',
		'lft'	=> array('type' => 'integer','null' => false),
		'rght'	=> array('type' => 'integer','null' => false),
		'flag'	=> array('type' => 'integer','null' => false, 'length' => 1, 'default' => 0)
	);
}
