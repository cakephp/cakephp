<?php
/**
 * Short description for file.
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * MyCategoriesMyProductsFixture
 *
 * @package       Cake.Test.Fixture
 */
class MyCategoriesMyProductsFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'my_category_id' => array('type' => 'integer'),
		'my_product_id' => array('type' => 'integer'),
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('my_category_id' => 1, 'my_product_id' => 1),
		array('my_category_id' => 2, 'my_product_id' => 1),
		array('my_category_id' => 2, 'my_product_id' => 2),
		array('my_category_id' => 3, 'my_product_id' => 2),
	);
}
