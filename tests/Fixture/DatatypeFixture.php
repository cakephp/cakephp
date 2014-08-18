<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for class.
 *
 */
class DatatypeFixture extends TestFixture {

/**
 * Fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'integer', 'null' => false],
		'decimal_field' => ['type' => 'decimal', 'length' => '6', 'precision' => 3, 'default' => '0.000'],
		'float_field' => ['type' => 'float', 'length' => '5,2', 'null' => false, 'default' => null],
		'huge_int' => ['type' => 'biginteger'],
		'bool' => ['type' => 'boolean', 'null' => false, 'default' => false],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

/**
 * Records property
 *
 * @var array
 */
	public $records = [
		['float_field' => 42.23, 'huge_int' => '1234567891234567891', 'bool' => 0],
	];

}
