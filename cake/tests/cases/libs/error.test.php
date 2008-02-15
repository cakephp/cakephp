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
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.5432
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
uses('error', 'controller' . DS .'controller');
if (!class_exists('TestAppController')) {
	class TestAppController extends Controller {
		function beforeFilter() {
			$this->cakeError('error404', array('oops' => 'Nothing to see here'));
		}
	}
}
class TestErrorController extends TestAppController {

	var $uses = array();

	function index() {
		$this->autoRender = false;
		return 'what up';
	}

}
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class ErrorHandlerTest extends UnitTestCase {

	function skip() {
		$this->skipif (false, 'ErrorHandlerTest not implemented');
	}

	function testFromBeforeFilter() {
		$Test = new TestErrorController();

		if (!class_exists('dispatcher')) {
			require CAKE . 'dispatcher.php';
		}
		$Dispatcher =& new Dispatcher();

		restore_error_handler();
		ob_start();
		$controller = $Dispatcher->dispatch('/test_error', array('return'=> 1));
		$expected = ob_get_clean();
		set_error_handler('simpleTestErrorHandler');
		$this->assertPattern("/<h2>Not Found<\/h2>/", $expected);
		$this->assertPattern("/<strong>'\/test_error'<\/strong>/", $expected);
	}

	function testError() {

	}

	function testError404() {

	}

	function testMissingController() {

	}

	function testMissingAction() {

	}

	function testPrivateAction() {

	}

	function testMissingTable() {

	}

	function testMissingDatabase() {

	}

	function testMissingView() {

	}

	function testMissingLayout() {

	}

	function testMissingConnection() {

	}

	function testMissingHelperFile() {

	}

	function testMissingHelperClass() {

	}

	function testMissingComponentFile() {

	}

	function testMissingComponentClass() {

	}

	function testMissingModel() {

	}
}
?>