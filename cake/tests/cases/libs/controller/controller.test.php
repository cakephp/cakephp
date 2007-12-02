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
		$this->assertTrue(is_a($Controller->ControllerPost, 'ControllerPost'));
		$this->assertTrue(is_a($Controller->ControllerComment, 'ControllerComment'));

		unset($Controller);
	}

	function testPersistent() {
		$Controller =& new Controller();
		$Controller->modelClass = 'ControllerPost';
		$Controller->persistModel = true;
		$Controller->constructClasses();
		$this->assertTrue(file_exists(CACHE . 'persistent' . DS .'controllerpost.php'));
		$this->assertTrue(is_a($Controller->ControllerPost, 'ControllerPost'));
		unlink(CACHE . 'persistent' . DS . 'controllerpost.php');
		unlink(CACHE . 'persistent' . DS . 'controllerpostregistry.php');

		unset($Controller);
	}

	function testPaginate() {
		$Controller =& new Controller();
		$Controller->uses = array('ControllerPost', 'ControllerComment');
		$Controller->passedArgs[] = '1';
		$Controller->params['url'] = array();
		$Controller->constructClasses();
		$Controller->modelClass = null;

		$results = Set::extract($Controller->paginate('ControllerPost'), '{n}.ControllerPost.id');
		$this->assertEqual($results, array(1, 2, 3));

		$results = Set::extract($Controller->paginate('ControllerComment'), '{n}.ControllerComment.id');
		$this->assertEqual($results, array(1, 2, 3, 4, 5, 6));

		$Controller->uses[0] = 'Plugin.ControllerPost';
		$results = Set::extract($Controller->paginate(), '{n}.ControllerPost.id');
		$this->assertEqual($results, array(1, 2, 3));
	}
}

?>