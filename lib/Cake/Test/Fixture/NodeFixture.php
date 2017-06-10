<?php
/**
 * Short description for file.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.6879 //Correct version number as needed**
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * NodeFixture
 *
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.6879 //Correct version number as needed**
 */
class NodeFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => 'string',
		'state' => 'integer'
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('id' => 1, 'name' => 'First', 'state' => 50),
		array('id' => 2, 'name' => 'Second', 'state' => 60),
	);
}
