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
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class ProductFixture
 *
 */
class ProductFixture extends TestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'integer'],
		'name' => ['type' => 'string', 'length' => 255, 'null' => false],
		'type' => ['type' => 'string', 'length' => 255, 'null' => false],
		'price' => ['type' => 'integer', 'null' => false],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('name' => 'Park\'s Great Hits', 'type' => 'Music', 'price' => 19),
		array('name' => 'Silly Puddy', 'type' => 'Toy', 'price' => 3),
		array('name' => 'Playstation', 'type' => 'Toy', 'price' => 89),
		array('name' => 'Men\'s T-Shirt', 'type' => 'Clothing', 'price' => 32),
		array('name' => 'Blouse', 'type' => 'Clothing', 'price' => 34),
		array('name' => 'Electronica 2002', 'type' => 'Music', 'price' => 4),
		array('name' => 'Country Tunes', 'type' => 'Music', 'price' => 21),
		array('name' => 'Watermelon', 'type' => 'Food', 'price' => 9)
	);
}
