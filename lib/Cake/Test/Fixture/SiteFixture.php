<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 2.1
 * @license       http://www.opensource.org/licenses/opengroup.php Open Group Test Suite License
 */

/**
 * Class SiteFixture
 *
 * @package       Cake.Test.Fixture
 */
class SiteFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => false),
		'created' => 'datetime',
		'updated' => 'datetime'
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('name' => 'cakephp', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
		array('name' => 'Mark Story\'s sites', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'),
		array('name' => 'rchavik sites', 'created' => '2001-02-03 00:01:02', 'updated' => '2007-03-17 01:22:31'),
	);
}
