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
class UuidportfolioFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Uuidportfolio'
 */
	public $name = 'Uuidportfolio';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => false)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('id' => '4806e091-6940-4d2b-b227-303740cf8569', 'name' => 'Portfolio 1'),
		array('id' => '480af662-eb8c-47d3-886b-230540cf8569', 'name' => 'Portfolio 2'),
	);
}
