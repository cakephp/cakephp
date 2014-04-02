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
 * Class PersonFixture
 *
 */
class PersonFixture extends TestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'integer', 'null' => false],
		'name' => ['type' => 'string', 'null' => false, 'length' => 32],
		'mother_id' => ['type' => 'integer', 'null' => false],
		'father_id' => ['type' => 'integer', 'null' => false],
		'_constraints' => [
			'primary' => ['type' => 'primary', 'columns' => ['id']],
			'mother_idx' => ['type' => 'unique', 'columns' => ['mother_id', 'father_id']]
		]
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('name' => 'person', 'mother_id' => 2, 'father_id' => 3),
		array('name' => 'mother', 'mother_id' => 4, 'father_id' => 5),
		array('name' => 'father', 'mother_id' => 6, 'father_id' => 7),
		array('name' => 'mother - grand mother', 'mother_id' => 0, 'father_id' => 0),
		array('name' => 'mother - grand father', 'mother_id' => 0, 'father_id' => 0),
		array('name' => 'father - grand mother', 'mother_id' => 0, 'father_id' => 0),
		array('name' => 'father - grand father', 'mother_id' => 0, 'father_id' => 0)
	);
}
