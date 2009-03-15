<?php
/**
 * JqueryEngineTestCase
 *
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright       Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link            http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package         cake.tests
 * @subpackage      cake.tests.cases.views.helpers
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Helper', array('Html', 'Js', 'MootoolsEngine'));

class JqueryEngineHelperTestCase extends CakeTestCase {
/**
 * startTest
 *
 * @return void
 **/
	function startTest() {
		$this->Moo =& new MootoolsEngineHelper();
	}
/**
 * end test
 *
 * @return void
 **/
	function endTest() {
		unset($this->Moo);
	}
/**
 * test selector method
 *
 * @return void
 **/
	function testSelector() {

	}
/**
 * test event binding
 *
 * @return void
 **/
	function testEvent() {

	}
/**
 * test dom ready event creation
 *
 * @return void
 **/
	function testDomReady() {

	}
/**
 * test Each method
 *
 * @return void
 **/
	function testEach() {

	}
/**
 * test Effect generation
 *
 * @return void
 **/
	function testEffect() {

	}
/**
 * Test Request Generation
 *
 * @return void
 **/
	function testRequest() {

	}
}
?>