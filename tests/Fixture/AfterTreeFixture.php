<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://www.cakephp.org
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AdFixture class
 *
 */
class AfterTreeFixture extends TestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'integer'],
		'parent_id' => ['type' => 'integer'],
		'lft' => ['type' => 'integer'],
		'rght' => ['type' => 'integer'],
		'name' => ['type' => 'string', 'length' => 255, 'null' => false],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('parent_id' => null, 'lft' => 1, 'rght' => 2, 'name' => 'One'),
		array('parent_id' => null, 'lft' => 3, 'rght' => 4, 'name' => 'Two'),
		array('parent_id' => null, 'lft' => 5, 'rght' => 6, 'name' => 'Three'),
		array('parent_id' => null, 'lft' => 7, 'rght' => 12, 'name' => 'Four'),
		array('parent_id' => null, 'lft' => 8, 'rght' => 9, 'name' => 'Five'),
		array('parent_id' => null, 'lft' => 10, 'rght' => 11, 'name' => 'Six'),
		array('parent_id' => null, 'lft' => 13, 'rght' => 14, 'name' => 'Seven')
	);
}
