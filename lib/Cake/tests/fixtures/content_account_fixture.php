<?php
/**
 * Short description for file.
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
 * @package       cake.tests.fixtures
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       cake.tests.fixtures
 */
class ContentAccountFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Aco'
 * @access public
 */
	public $name = 'ContentAccount';
	public $table = 'ContentAccounts';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	public $fields = array(
		'iContentAccountsId' => array('type' => 'integer', 'key' => 'primary'),
		'iContentId'		=> array('type' => 'integer'),
		'iAccountId'		=> array('type' => 'integer')
	);

/**
 * records property
 *
 * @var array
 * @access public
 */
	public $records = array(
		array('iContentId' => 1, 'iAccountId' => 1),
		array('iContentId' => 2, 'iAccountId' => 2),
		array('iContentId' => 3, 'iAccountId' => 3),
		array('iContentId' => 4, 'iAccountId' => 4),
		array('iContentId' => 1, 'iAccountId' => 2),
		array('iContentId' => 2, 'iAccountId' => 3),
	);
}
