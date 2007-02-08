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
 * @subpackage		cake.tests.cases.libs.view.helpers
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
	require_once LIBS.'../app_helper.php';
	require_once LIBS.DS.'view'.DS.'helper.php';
	require_once LIBS.DS.'view'.DS.'helpers'.DS.'ajax.php';
	require_once LIBS.DS.'view'.DS.'helpers'.DS.'html.php';
	require_once LIBS.DS.'view'.DS.'helpers'.DS.'form.php';
	require_once LIBS.DS.'view'.DS.'helpers'.DS.'javascript.php';
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.view.helpers
 */
class AjaxTest extends UnitTestCase {

	function setUp() {
		$this->ajax = new AjaxHelper();
		$this->ajax->Html = new HtmlHelper();
		$this->ajax->Form = new FormHelper();
		$this->ajax->Javascript = new JavascriptHelper();
	}

	function testEvalScripts() {
		$result = $this->ajax->link('Test Link', '/', array('id' => 'link1', 'update' => 'content', 'evalScripts' => false));
		$expected = '<a href="/"  id="link1" onclick=" return false;">Test Link</a><script type="text/javascript">Event.observe(\'link1\', \'click\', function(event){ new Ajax.Updater(\'content\',\'/\', {asynchronous:true, evalScripts:false, requestHeaders:[\'X-Update\', \'content\']}) }, false);</script>';
		$this->assertEqual($result, $expected);

		$result = $this->ajax->link('Test Link', '/', array('id' => 'link1', 'update' => 'content'));
		$expected = '<a href="/"  id="link1" onclick=" return false;">Test Link</a><script type="text/javascript">Event.observe(\'link1\', \'click\', function(event){ new Ajax.Updater(\'content\',\'/\', {asynchronous:true, evalScripts:true, requestHeaders:[\'X-Update\', \'content\']}) }, false);</script>';
		$this->assertEqual($result, $expected);
	}

	function testAutoComplete() {
		//$result = $this->ajax->autoComplete('Post/title' , '/posts', array('minChars' => 2));
		//pr($result);
	}

	function testAsynchronous() {
		$result = $this->ajax->link('Test Link', '/', array('id' => 'link1', 'update' => 'content', 'type' => 'synchronous'));
		$expected = '<a href="/"  id="link1" onclick=" return false;">Test Link</a><script type="text/javascript">Event.observe(\'link1\', \'click\', function(event){ new Ajax.Updater(\'content\',\'/\', {asynchronous:false, evalScripts:true, requestHeaders:[\'X-Update\', \'content\']}) }, false);</script>';
		$this->assertEqual($result, $expected);
	}

	function testDraggable() {
		$result = $this->ajax->drag('id', array('handle' => 'other_id'));
		$expected = '<script type="text/javascript">new Draggable(\'id\', {handle:\'other_id\'});</script>';
		$this->assertEqual($result, $expected);
	}

	function testDroppable() {
		$result = $this->ajax->drop('droppable', array('accept' => 'crap'));
		$expected = '<script type="text/javascript">Droppables.add(\'droppable\', {accept:\'crap\'});</script>';
		$this->assertEqual($result, $expected);
	}

	function tearDown() {
		unset($this->ajax);
	}
}
?>