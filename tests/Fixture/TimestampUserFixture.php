<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class TimestampUserFixture
 *
 */
class TimestampUserFixture extends TestFixture {

/**
 * table property
 *
 * @var string
 */
	public $table = 'timestamp_users';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'integer'],
		'username' => ['type' => 'string', 'null' => true],
		'password' => ['type' => 'string', 'null' => true],
		'created' => ['type' => 'integer'],
		'updated' => 'datetime',
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('username' => 'mariano', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO', 'created' => 1174094183, 'updated' => '2007-03-17 01:18:31'),
		array('username' => 'nate', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO', 'created' => 1205716703, 'updated' => '2008-03-17 01:20:31'),
		array('username' => 'larry', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO', 'created' => 1273454423, 'updated' => '2010-05-10 01:22:31'),
		array('username' => 'garrett', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO', 'created' => 1339291343, 'updated' => '2012-06-12 01:24:31'),
	);
}
