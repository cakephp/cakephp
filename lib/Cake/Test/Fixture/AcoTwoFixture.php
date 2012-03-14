<?php
/**
 * Short description for file.
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
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class AcoTwoFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'AcoTwo'
 */
	public $name = 'AcoTwo';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id'		=> array('type' => 'integer', 'key' => 'primary'),
		'parent_id'	=> array('type' => 'integer', 'length' => 10, 'null' => true),
		'model'		=> array('type' => 'string', 'null' => true),
		'foreign_key' => array('type' => 'integer', 'length' => 10, 'null' => true),
		'alias'		=> array('type' => 'string', 'default' => ''),
		'lft'		=> array('type' => 'integer', 'length' => 10, 'null' => true),
		'rght'		=> array('type' => 'integer', 'length' => 10, 'null' => true)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('parent_id' => null,	'model' => null, 'foreign_key' => null, 'alias' => 'ROOT',		'lft' => 1,  'rght' => 20),
		array('parent_id' => 1,		'model' => null, 'foreign_key' => null, 'alias' => 'tpsReports','lft' => 2,  'rght' => 9),
		array('parent_id' => 2,		'model' => null, 'foreign_key' => null, 'alias' => 'view',		'lft' => 3,  'rght' => 6),
		array('parent_id' => 3,		'model' => null, 'foreign_key' => null, 'alias' => 'current',	'lft' => 4,  'rght' => 5),
		array('parent_id' => 2,		'model' => null, 'foreign_key' => null, 'alias' => 'update',	'lft' => 7,  'rght' => 8),
		array('parent_id' => 1,		'model' => null, 'foreign_key' => null, 'alias' => 'printers',	'lft' => 10, 'rght' => 19),
		array('parent_id' => 6,		'model' => null, 'foreign_key' => null, 'alias' => 'print',		'lft' => 11, 'rght' => 14),
		array('parent_id' => 7,		'model' => null, 'foreign_key' => null, 'alias' => 'lettersize','lft' => 12, 'rght' => 13),
		array('parent_id' => 6,		'model' => null, 'foreign_key' => null, 'alias' => 'refill',	'lft' => 15, 'rght' => 16),
		array('parent_id' => 6,		'model' => null, 'foreign_key' => null, 'alias' => 'smash',		'lft' => 17, 'rght' => 18),
	);
}
