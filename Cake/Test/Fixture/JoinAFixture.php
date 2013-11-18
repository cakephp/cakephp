<?php
/**
 * Short description for file.
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
 * @since         CakePHP(tm) v 1.2.0.6317
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class JoinAFixture
 *
 */
class JoinAFixture extends TestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'integer'],
		'name' => ['type' => 'string', 'default' => ''],
		'body' => ['type' => 'text'],
		'created' => ['type' => 'datetime', 'null' => true],
		'updated' => ['type' => 'datetime', 'null' => true],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('name' => 'Join A 1', 'body' => 'Join A 1 Body', 'created' => '2008-01-03 10:54:23', 'updated' => '2008-01-03 10:54:23'),
		array('name' => 'Join A 2', 'body' => 'Join A 2 Body', 'created' => '2008-01-03 10:54:24', 'updated' => '2008-01-03 10:54:24'),
		array('name' => 'Join A 3', 'body' => 'Join A 2 Body', 'created' => '2008-01-03 10:54:25', 'updated' => '2008-01-03 10:54:24')
	);
}
