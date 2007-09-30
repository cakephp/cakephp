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
class ControllerComment extends CakeTestModel {
	var $name = 'ControllerComment';
	var $useTable = 'comments';
}
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs.controller
 */
class ControllerTest extends CakeTestCase {

	var $fixtures = array('core.post', 'core.comment');

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

		$Controller->data['ControllerPost']['created_hour'] = '20';
		$Controller->data['ControllerPost']['created_min'] = '33';

		$Controller->cleanUpFields();
		$expected = array('ControllerPost'=> array('created'=> '20:33'));
		$this->assertEqual($Controller->data, $expected);

		$Controller->data['ControllerPost']['created_hour'] = '20';
		$Controller->data['ControllerPost']['created_min'] = '33';
		$Controller->data['ControllerPost']['created_sec'] = '33';

		$Controller->cleanUpFields();
		$expected = array('ControllerPost'=> array('created'=> '20:33:33'));
		$this->assertEqual($Controller->data, $expected);

		$Controller->data['ControllerPost']['created_hour'] = '13';
		$Controller->data['ControllerPost']['created_min'] = '00';
		$Controller->data['ControllerPost']['updated_hour'] = '14';
		$Controller->data['ControllerPost']['updated_min'] = '40';

		$Controller->cleanUpFields();
		$expected = array('ControllerPost'=> array('created'=> '13:00', 'updated'=> '14:40'));
		$this->assertEqual($Controller->data, $expected);

		$Controller->data['ControllerPost']['created_hour'] = '13';
		$Controller->data['ControllerPost']['created_min'] = '0';
		$Controller->data['ControllerPost']['updated_hour'] = '14';
		$Controller->data['ControllerPost']['updated_min'] = '40';

		$Controller->cleanUpFields();
		$expected = array('ControllerPost'=> array('created'=> '13:00', 'updated'=> '14:40'));
		$this->assertEqual($Controller->data, $expected);


		unset($Controller);
	}

	function testConstructClasses() {
		$Controller =& new Controller();
		$Controller->modelClass = 'ControllerPost';
		$Controller->passedArgs[] = '1';
		$Controller->constructClasses();
		$this->assertEqual($Controller->ControllerPost->id, 1);

		unset($Controller);

		$Controller =& new Controller();
		$Controller->uses = array('ControllerPost', 'ControllerComment');
		$Controller->passedArgs[] = '1';
		$Controller->constructClasses();
		$this->assertTrue($Controller->ControllerPost == new ControllerPost());
		$this->assertTrue($Controller->ControllerComment == new ControllerComment());
		$this->assertFalse($Controller->ControllerComment != new ControllerComment());

		unset($Controller);

	}

	function testPersistent() {

		$Controller =& new Controller();
		$Controller->modelClass = 'ControllerPost';
		$Controller->persistModel = true;
		$Controller->constructClasses();
		$this->assertTrue(file_exists(CACHE . 'persistent' . DS .'controllerpost.php'));
		$this->assertTrue($Controller->ControllerPost == new ControllerPost());
		unlink(CACHE . 'persistent' . DS . 'controllerpost.php');
		unlink(CACHE . 'persistent' . DS . 'controllerpostregistry.php');

		unset($Controller);
	}
}
?>