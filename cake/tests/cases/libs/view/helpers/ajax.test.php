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
	if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
		define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
	}

	require_once LIBS.'../app_helper.php';
	require_once LIBS.DS.'model'.DS.'model.php';
	require_once LIBS.DS.'view'.DS.'helper.php';
	require_once LIBS.DS.'view'.DS.'helpers'.DS.'ajax.php';
	require_once LIBS.DS.'view'.DS.'helpers'.DS.'html.php';
	require_once LIBS.DS.'view'.DS.'helpers'.DS.'form.php';
	require_once LIBS.DS.'view'.DS.'helpers'.DS.'javascript.php';
	require_once LIBS.DS.'controller'.DS.'controller.php';

	if (!class_exists('TheTestController')) {
		class TheTestController extends Controller {
			var $name = 'TheTest';
			var $uses = null;
		}
	}

	if (!class_exists('Post')) {
		class Post extends Model {
		
			var $primaryKey = 'id';
			var $useTable = false;
		
			function loadInfo() {
				return new Set(array(
					array('name' => 'id', 'type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
					array('name' => 'name', 'type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
					array('name' => 'created', 'type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
					array('name' => 'updated', 'type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
				));
			}
		}
	}

/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.view.helpers
 */
class AjaxTest extends UnitTestCase {

	function setUp() {
		$this->Ajax = new AjaxHelper();
		$this->Ajax->Html = new HtmlHelper();
		$this->Ajax->Form = new FormHelper();
		$this->Ajax->Javascript = new JavascriptHelper();
		$this->Ajax->Form->Html =& $this->Ajax->Html;
		$view = new View(new TheTestController());
		ClassRegistry::addObject('view', $view);
		ClassRegistry::addObject('Post', new Post());
	}

	function testEvalScripts() {
		$result = $this->Ajax->link('Test Link', '/', array('id' => 'link1', 'update' => 'content', 'evalScripts' => false));
		$expected = '<a href="/"  id="link1" onclick=" return false;">Test Link</a><script type="text/javascript">Event.observe(\'link1\', \'click\', function(event){ new Ajax.Updater(\'content\',\'/\', {asynchronous:true, evalScripts:false, requestHeaders:[\'X-Update\', \'content\']}) }, false);</script>';
		$this->assertEqual($result, $expected);

		$result = $this->Ajax->link('Test Link', '/', array('id' => 'link1', 'update' => 'content'));
		$expected = '<a href="/"  id="link1" onclick=" return false;">Test Link</a><script type="text/javascript">Event.observe(\'link1\', \'click\', function(event){ new Ajax.Updater(\'content\',\'/\', {asynchronous:true, evalScripts:true, requestHeaders:[\'X-Update\', \'content\']}) }, false);</script>';
		$this->assertEqual($result, $expected);
	}

	function testAutoComplete() {
		$result = $this->Ajax->autoComplete('Post/title' , '/posts', array('minChars' => 2));
		
		$this->assertPattern('/^<input[^<>]+name="data\[Post\]\[title\]"[^<>]+autocomplete="off"[^<>]+\/>/', $result);
		$this->assertPattern('/<div[^<>]+id="PostTitle_autoComplete"[^<>]*><\/div>/', $result);
		$this->assertPattern('/<div[^<>]+class="auto_complete"[^<>]*><\/div>/', $result);
		$this->assertPattern('/<\/div>\s+<script type="text\/javascript">new Ajax\.Autocompleter\(\'PostTitle\', \'PostTitle_autoComplete\', \'\/posts\',/', $result);
		$this->assertPattern('/<script(.*)>(.*){minChars:2}\);/', $result);
		$this->assertPattern('/<\/script>$/', $result);
	}

	function testAsynchronous() {
		$result = $this->Ajax->link('Test Link', '/', array('id' => 'link1', 'update' => 'content', 'type' => 'synchronous'));
		$expected = '<a href="/"  id="link1" onclick=" return false;">Test Link</a><script type="text/javascript">Event.observe(\'link1\', \'click\', function(event){ new Ajax.Updater(\'content\',\'/\', {asynchronous:false, evalScripts:true, requestHeaders:[\'X-Update\', \'content\']}) }, false);</script>';
		$this->assertEqual($result, $expected);
	}

	function testDraggable() {
		$result = $this->Ajax->drag('id', array('handle' => 'other_id'));
		$expected = '<script type="text/javascript">new Draggable(\'id\', {handle:\'other_id\'});</script>';
		$this->assertEqual($result, $expected);
	}

	function testDroppable() {
		$result = $this->Ajax->drop('droppable', array('accept' => 'crap'));
		$expected = '<script type="text/javascript">Droppables.add(\'droppable\', {accept:\'crap\'});</script>';
		$this->assertEqual($result, $expected);

		$result = $this->Ajax->dropRemote('droppable', array('accept' => 'crap'), array('url' => '/posts'));
		$expected = '<script type="text/javascript">Droppables.add(\'droppable\', {accept:\'crap\', onDrop:function(element, droppable){new Ajax.Request(\'/posts\', {asynchronous:true, evalScripts:true})}});</script>';
		$this->assertEqual($result, $expected);
	}

	function testSubmitWithIndicator() {
		$result = $this->Ajax->submit('Add', array('div' => false, 'url' => "/controller/action", 'indicator' => 'loading', 'loading' => "doSomething()", 'complete' => 'doSomethingElse() '));
		$this->assertPattern('/onLoading:function\(request\){doSomething\(\);\s+Element.show\(\'loading\'\);}/', $result);
		$this->assertPattern('/onComplete:function\(request, json\){doSomethingElse\(\) ;\s+Element.hide\(\'loading\'\);}/', $result);
	}

	function tearDown() {
		unset($this->Ajax);
	}
}
?>