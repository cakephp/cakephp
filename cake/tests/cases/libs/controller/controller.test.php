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
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs.controller
 * @since			CakePHP(tm) v 1.2.0.5436
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('controller' . DS . 'controller');

class ControllerPost extends CakeTestModel {
	var $name = 'ControllerPost';
	var $useTable = 'posts';
}
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs.controller
 */
class ControllerTest extends CakeTestCase {

	var $fixtures = array('core.post');

	function testCleanUpFields() {
		$Controller =& new Controller();
		$Controller->modelClass = 'ControllerPost';
		$Controller->ControllerPost =& new ControllerPost();

		$Controller->data['ControllerPost']['created_year'] = '';
		$Controller->data['ControllerPost']['created_month'] = '';
		$Controller->data['ControllerPost']['created_day'] = '';
		$Controller->data['ControllerPost']['created_hour'] = '';
		$Controller->data['ControllerPost']['created_min'] = '';
		$Controller->data['ControllerPost']['created_sec'] = '';

		$Controller->cleanUpFields();
		$expected = array('ControllerPost'=> array('created'=> ''));
		$this->assertEqual($Controller->data, $expected);

		$Controller->data['ControllerPost']['created_year'] = '2007';
		$Controller->data['ControllerPost']['created_month'] = '08';
		$Controller->data['ControllerPost']['created_day'] = '20';
		$Controller->data['ControllerPost']['created_hour'] = '';
		$Controller->data['ControllerPost']['created_min'] = '';
		$Controller->data['ControllerPost']['created_sec'] = '';

		$Controller->cleanUpFields();
		$expected = array('ControllerPost'=> array('created'=> '2007-08-20'));
		$this->assertEqual($Controller->data, $expected);

		$Controller->data['ControllerPost']['created_year'] = '2007';
		$Controller->data['ControllerPost']['created_month'] = '08';
		$Controller->data['ControllerPost']['created_day'] = '20';
		$Controller->data['ControllerPost']['created_hour'] = '10';
		$Controller->data['ControllerPost']['created_min'] = '12';
		$Controller->data['ControllerPost']['created_sec'] = '';

		$Controller->cleanUpFields();
		$expected = array('ControllerPost'=> array('created'=> '2007-08-20 10:12'));
		$this->assertEqual($Controller->data, $expected);

		$Controller->data['ControllerPost']['created_year'] = '2007';
		$Controller->data['ControllerPost']['created_month'] = '';
		$Controller->data['ControllerPost']['created_day'] = '12';
		$Controller->data['ControllerPost']['created_hour'] = '20';
		$Controller->data['ControllerPost']['created_min'] = '';
		$Controller->data['ControllerPost']['created_sec'] = '';

		$Controller->cleanUpFields();
		$expected = array('ControllerPost'=> array('created'=> ''));
		$this->assertEqual($Controller->data, $expected);
	}
}
?>