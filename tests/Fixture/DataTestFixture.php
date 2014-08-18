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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for class.
 *
 */
class DataTestFixture extends TestFixture {

/**
 * Fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'integer'],
		'count' => ['type' => 'integer', 'default' => 0],
		'float' => ['type' => 'float', 'default' => 0],
		'created' => ['type' => 'datetime', 'default' => null],
		'updated' => ['type' => 'datetime', 'default' => null],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

/**
 * Records property
 *
 * @var array
 */
	public $records = array(
		array(
			'count' => 2,
			'float' => 2.4,
			'created' => '2010-09-06 12:28:00',
			'updated' => '2010-09-06 12:28:00'
		)
	);
}
