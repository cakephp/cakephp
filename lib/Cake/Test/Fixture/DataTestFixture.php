<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.6700
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class DataTestFixture extends CakeTestFixture {

/**
 * Name property
 *
 * @var string 'DataTest'
 */
	public $name = 'DataTest';

/**
 * Fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'count' => array('type' => 'integer', 'default' => 0),
		'float' => array('type' => 'float', 'default' => 0),
		//'timestamp' => array('type' => 'timestamp', 'default' => null, 'null' => true),
		'created' => array('type' => 'datetime', 'default' => null),
		'updated' => array('type' => 'datetime', 'default' => null)
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
