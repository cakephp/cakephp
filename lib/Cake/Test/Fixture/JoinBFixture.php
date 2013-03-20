<?php
/**
 * Short description for file.
 *
 * PHP 5
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
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.6317
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class JoinBFixture extends TestFixture {

/**
 * name property
 *
 * @var string 'JoinB'
 */
	public $name = 'JoinB';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'default' => ''),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('name' => 'Join B 1', 'created' => '2008-01-03 10:55:01', 'modified' => '2008-01-03 10:55:01'),
		array('name' => 'Join B 2', 'created' => '2008-01-03 10:55:02', 'modified' => '2008-01-03 10:55:02'),
		array('name' => 'Join B 3', 'created' => '2008-01-03 10:55:03', 'modified' => '2008-01-03 10:55:03')
	);
}
