<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
require_once CAKE.'basics.php';
/**
 * BasicsTest class
 *
 * @package              cake.tests
 * @subpackage           cake.tests.cases
 */
class BasicsTest extends CakeTestCase {
/**
 * testHttpBase method
 *
 * @return void
 * @access public
 */
	function testHttpBase() {
		$__SERVER = $_SERVER;

		$_SERVER['HTTP_HOST'] = 'localhost';
		$this->assertEqual(env('HTTP_BASE'), '');

		$_SERVER['HTTP_HOST'] = 'example.com';
		$this->assertEqual(env('HTTP_BASE'), '.example.com');

		$_SERVER['HTTP_HOST'] = 'www.example.com';
		$this->assertEqual(env('HTTP_BASE'), '.example.com');

		$_SERVER['HTTP_HOST'] = 'subdomain.example.com';
		$this->assertEqual(env('HTTP_BASE'), '.example.com');

		$_SERVER['HTTP_HOST'] = 'double.subdomain.example.com';
		$this->assertEqual(env('HTTP_BASE'), '.subdomain.example.com');

		$_SERVER = $__SERVER;
	}
}
?>