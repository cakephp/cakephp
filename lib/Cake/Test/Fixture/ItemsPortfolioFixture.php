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
class ItemsPortfolioFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'ItemsPortfolio'
 */
	public $name = 'ItemsPortfolio';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'item_id' => array('type' => 'integer', 'null' => false),
		'portfolio_id' => array('type' => 'integer', 'null' => false)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('item_id' => 1, 'portfolio_id' => 1),
		array('item_id' => 2, 'portfolio_id' => 2),
		array('item_id' => 3, 'portfolio_id' => 1),
		array('item_id' => 4, 'portfolio_id' => 1),
		array('item_id' => 5, 'portfolio_id' => 1),
		array('item_id' => 6, 'portfolio_id' => 2)
	);
}
