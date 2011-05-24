<?php
/**
 * Short description for file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.fixtures
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.tests.fixtures
 */
class DomainsSiteFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Domain'
 * @access public
 */
	var $name = 'DomainsSite';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'domain_id' => array('type' => 'integer', 'null' => false),
		'site_id' => array('type' => 'integer', 'null' => false),
		'active' => array('type' => 'boolean', 'null' => false),
		'created' => 'datetime',
		'updated' => 'datetime',
	);

/**
 * records property
 *
 * @var array
 * @access public
 */
	var $records = array(
		array('site_id' => 1, 'domain_id' => 10, 'active' => true, 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
		array('site_id' => 1, 'domain_id' => 11, 'active' => true, 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
		array('site_id' => 2, 'domain_id' => 20, 'active' => true, 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'),
		array('site_id' => 2, 'domain_id' => 21, 'active' => true, 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'),
		array('site_id' => 3, 'domain_id' => 30, 'active' => true, 'created' => '2001-02-03 00:01:02', 'updated' => '2007-03-17 01:22:31'),
		array('site_id' => 3, 'domain_id' => 31, 'active' => false, 'created' => '2001-02-03 00:01:02', 'updated' => '2007-03-17 01:22:31'),
	);
}
