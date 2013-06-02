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
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class DomainsSiteFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Domain'
 * @access public
 */
	public $name = 'DomainsSite';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'domain_id' => array('type' => 'integer', 'null' => false),
		'site_id' => array('type' => 'integer', 'null' => false),
		'active' => array('type' => 'boolean', 'null' => false, 'default' => false),
		'created' => 'datetime',
		'updated' => 'datetime',
	);

/**
 * records property
 *
 * @var array
 * @access public
 */
	public $records = array(
		array('site_id' => 1, 'domain_id' => 1, 'active' => true, 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
		array('site_id' => 1, 'domain_id' => 2, 'active' => true, 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
		array('site_id' => 2, 'domain_id' => 4, 'active' => true, 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'),
		array('site_id' => 2, 'domain_id' => 5, 'active' => true, 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'),
		array('site_id' => 3, 'domain_id' => 6, 'active' => true, 'created' => '2001-02-03 00:01:02', 'updated' => '2007-03-17 01:22:31'),
		array('site_id' => 3, 'domain_id' => 7, 'active' => false, 'created' => '2001-02-03 00:01:02', 'updated' => '2007-03-17 01:22:31'),
	);
}
