<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP Test Suite <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright (c) 2006, Larry E. Masters Shorewood, IL. 60431
 * Author(s): Larry E. Masters aka PhpNut <phpnut@gmail.com>
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @author       Larry E. Masters aka PhpNut <phpnut@gmail.com>
 * @copyright    Copyright (c) 2006, Larry E. Masters Shorewood, IL. 60431
 * @link         http://www.phpnut.com/projects/
 * @package      test_suite
 * @subpackage   test_suite.cases.app
 * @since        CakePHP Test Suite v 1.0.0.0
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('view'.DS.'helpers'.DS.'app_helper', 'class_registry', 'controller'.DS.'controller', 'model'.DS.'model', 'view'.DS.'helper',
	'view'.DS.'helpers'.DS.'html', 'view'.DS.'helpers'.DS.'form');

class TheHtmlTestController extends Controller {
	var $name = 'TheTest';
	var $uses = null;
}

class HtmlHelperTest extends UnitTestCase {
	var $html = null;

	function setUp() {
		$this->Html =& new HtmlHelper();
		$view =& new View(new TheHtmlTestController());
		ClassRegistry::addObject('view', $view);
	}

	function testLinkEscape() {
		$result = $this->Html->link('Next >', '#');
		$expected = '/^<a href="#">Next &gt;<\/a>$/';
		$this->assertPattern($expected, $result);

		$result = $this->Html->link('Next >', '#', array('escape' => false));
		$this->assertPattern('/^<a href="#">Next ><\/a>$/', $result);
	}

	function testImageLink() {
		$result = $this->Html->link($this->Html->image('test.gif'), '#', array(), false, false, false);
		$this->assertPattern('/^<a href="#"><img\s+src="img\/test.gif"\s+alt=""\s+\/><\/a>$/', $result);
	}

	function testImageTag() {
		$result = $this->Html->image('test.gif');
		$this->assertPattern('/src="img\/test.gif"/', $result);

		$result = $this->Html->image('http://google.com/logo.gif');
		$this->assertPattern('/src="http:\/\/google.com\/logo\.gif"/', $result);

		$result = $this->Html->image(array('controller' => 'test', 'action' => 'view', 1, 'ext' => 'gif'));
		$this->assertPattern('/src="\/test\/view\/1.gif"/', $result);

		$result = $this->Html->image('/test/view/1.gif');
		$this->assertPattern('/src="\/test\/view\/1.gif"/', $result);
	}

	function testStyle() {
		$result = $this->Html->style(array('display'=> 'none', 'margin'=>'10px'));
		$expected = 'display:none; margin:10px;';
		$this->assertEqual($expected, $result);

		$result = $this->Html->style(array('display'=> 'none', 'margin'=>'10px'), false);
		$expected = "display:none;\nmargin:10px;";
		$this->assertEqual($expected, $result);
	}

	function testCssLink() {
		$result = $this->Html->css('screen');
		$this->assertPattern('/^<link[^<>]+\/>$/', $result);
		$this->assertPattern('/^<link[^<>]+rel="stylesheet"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<link[^<>]+type="text\/css"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<link[^<>]+href=".*css\/screen\.css"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<link[^<>]+[^rel|type|href]=[^<>]*>/', $result);

		$result = $this->Html->css('screen.css');
		$this->assertPattern('/^<link[^<>]+\/>$/', $result);
		$this->assertPattern('/^<link[^<>]+rel="stylesheet"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<link[^<>]+type="text\/css"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<link[^<>]+href=".*css\/screen\.css"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<link[^<>]+[^rel|type|href]=[^<>]*>/', $result);

		$result = $this->Html->css('screen.css?1234');
		$this->assertPattern('/^<link[^<>]+\/>$/', $result);
		$this->assertPattern('/^<link[^<>]+rel="stylesheet"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<link[^<>]+type="text\/css"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<link[^<>]+href=".*css\/screen\.css\?1234"[^<>]+\/>$/', $result);
		$this->assertNoPattern('/^<link[^<>]+[^rel|type|href]=[^<>]*>/', $result);
	}

	function testBreadcrumb() {
		$this->Html->addCrumb('First', '#first');
		$this->Html->addCrumb('Second', '#second');
		$this->Html->addCrumb('Third', '#third');

		$result = $this->Html->getCrumbs();
		$this->assertPattern('/^<a[^<>]+>First<\/a>&raquo;<a[^<>]+>Second<\/a>&raquo;<a[^<>]+>Third<\/a>$/', $result);
		$this->assertPattern('/<a\s+href=["\']+\#first["\']+[^<>]*>First<\/a>/', $result);
		$this->assertPattern('/<a\s+href=["\']+\#second["\']+[^<>]*>Second<\/a>/', $result);
		$this->assertPattern('/<a\s+href=["\']+\#third["\']+[^<>]*>Third<\/a>/', $result);
		$this->assertNoPattern('/<a[^<>]+[^href]=[^<>]*>/', $result);

		$result = $this->Html->getCrumbs(' &gt; ');
		$this->assertPattern('/^<a[^<>]+>First<\/a> &gt; <a[^<>]+>Second<\/a> &gt; <a[^<>]+>Third<\/a>$/', $result);
		$this->assertPattern('/<a\s+href=["\']+\#first["\']+[^<>]*>First<\/a>/', $result);
		$this->assertPattern('/<a\s+href=["\']+\#second["\']+[^<>]*>Second<\/a>/', $result);
		$this->assertPattern('/<a\s+href=["\']+\#third["\']+[^<>]*>Third<\/a>/', $result);
		$this->assertNoPattern('/<a[^<>]+[^href]=[^<>]*>/', $result);

		$this->Html->addCrumb('Fourth', null);

		$result = $this->Html->getCrumbs();
		$this->assertPattern('/^<a[^<>]+>First<\/a>&raquo;<a[^<>]+>Second<\/a>&raquo;<a[^<>]+>Third<\/a>&raquo;Fourth$/', $result);
		$this->assertPattern('/<a\s+href=["\']+\#first["\']+[^<>]*>First<\/a>/', $result);
		$this->assertPattern('/<a\s+href=["\']+\#second["\']+[^<>]*>Second<\/a>/', $result);
		$this->assertPattern('/<a\s+href=["\']+\#third["\']+[^<>]*>Third<\/a>/', $result);
		$this->assertNoPattern('/<a[^<>]+[^href]=[^<>]*>/', $result);
	}

	function testNestedList() {
		$list = array(
			'Item 1',
			'Item 2' => array(
				'Item 2.1'
			),
			'Item 3',
			'Item 4' => array(
				'Item 4.1',
				'Item 4.2',
				'Item 4.3' => array(
					'Item 4.3.1',
					'Item 4.3.2'
				)
			),
			'Item 5' => array(
				'Item 5.1',
				'Item 5.2'
			)
		);

		$result = $this->Html->nestedList($list);
		$this->assertPattern('/^<ul>\s*<li>Item 1<\/li>\s*<li>Item 2.+<\/li><li>Item 3<\/li>\s*<li>Item 4.+<\/li><li>Item 5.+<\/li><\/ul>$/', $result);
		$this->assertPattern('/<li>Item 2\s*<ul>\s*<li>Item 2.1<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li>Item 4\s*<ul>\s*<li>Item 4.1<\/li>\s*<li>Item 4.2<\/li>\s*<li>Item 4.3.+<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li>Item 4.3\s*<ul>\s*<li>Item 4.3.1<\/li>\s*<li>Item 4.3.2<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li>Item 5\s*<ul>\s*<li>Item 5.1<\/li>\s*<li>Item 5.2<\/li>\s*<\/ul>\s*<\/li>/', $result);

		$result = $this->Html->nestedList($list, null);
		$this->assertPattern('/^<ul>\s*<li>Item 1<\/li>\s*<li>Item 2.+<\/li><li>Item 3<\/li>\s*<li>Item 4.+<\/li><li>Item 5.+<\/li><\/ul>$/', $result);
		$this->assertPattern('/<li>Item 2\s*<ul>\s*<li>Item 2.1<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li>Item 4\s*<ul>\s*<li>Item 4.1<\/li>\s*<li>Item 4.2<\/li>\s*<li>Item 4.3.+<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li>Item 4.3\s*<ul>\s*<li>Item 4.3.1<\/li>\s*<li>Item 4.3.2<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li>Item 5\s*<ul>\s*<li>Item 5.1<\/li>\s*<li>Item 5.2<\/li>\s*<\/ul>\s*<\/li>/', $result);


		$result = $this->Html->nestedList($list, array(), array(), 'ol');
		$this->assertPattern('/^<ol>\s*<li>Item 1<\/li>\s*<li>Item 2.+<\/li><li>Item 3<\/li>\s*<li>Item 4.+<\/li><li>Item 5.+<\/li><\/ol>$/', $result);
		$this->assertPattern('/<li>Item 2\s*<ol>\s*<li>Item 2.1<\/li>\s*<\/ol>\s*<\/li>/', $result);
		$this->assertPattern('/<li>Item 4\s*<ol>\s*<li>Item 4.1<\/li>\s*<li>Item 4.2<\/li>\s*<li>Item 4.3.+<\/li>\s*<\/ol>\s*<\/li>/', $result);
		$this->assertPattern('/<li>Item 4.3\s*<ol>\s*<li>Item 4.3.1<\/li>\s*<li>Item 4.3.2<\/li>\s*<\/ol>\s*<\/li>/', $result);
		$this->assertPattern('/<li>Item 5\s*<ol>\s*<li>Item 5.1<\/li>\s*<li>Item 5.2<\/li>\s*<\/ol>\s*<\/li>/', $result);

		$result = $this->Html->nestedList($list, 'ol');
		$this->assertPattern('/^<ol>\s*<li>Item 1<\/li>\s*<li>Item 2.+<\/li><li>Item 3<\/li>\s*<li>Item 4.+<\/li><li>Item 5.+<\/li><\/ol>$/', $result);
		$this->assertPattern('/<li>Item 2\s*<ol>\s*<li>Item 2.1<\/li>\s*<\/ol>\s*<\/li>/', $result);
		$this->assertPattern('/<li>Item 4\s*<ol>\s*<li>Item 4.1<\/li>\s*<li>Item 4.2<\/li>\s*<li>Item 4.3.+<\/li>\s*<\/ol>\s*<\/li>/', $result);
		$this->assertPattern('/<li>Item 4.3\s*<ol>\s*<li>Item 4.3.1<\/li>\s*<li>Item 4.3.2<\/li>\s*<\/ol>\s*<\/li>/', $result);
		$this->assertPattern('/<li>Item 5\s*<ol>\s*<li>Item 5.1<\/li>\s*<li>Item 5.2<\/li>\s*<\/ol>\s*<\/li>/', $result);

		$result = $this->Html->nestedList($list, array('class'=>'list'));
		$this->assertPattern('/^<ul[^<>]*class="list"[^<>]*>\s*<li>Item 1<\/li>\s*<li>Item 2.+<\/li><li>Item 3<\/li>\s*<li>Item 4.+<\/li><li>Item 5.+<\/li><\/ul>$/', $result);
		$this->assertPattern('/<li>Item 2\s*<ul[^<>]*class="list"[^<>]*>\s*<li>Item 2.1<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li>Item 4\s*<ul[^<>]*class="list"[^<>]*>\s*<li>Item 4.1<\/li>\s*<li>Item 4.2<\/li>\s*<li>Item 4.3.+<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li>Item 4.3\s*<ul[^<>]*class="list"[^<>]*>\s*<li>Item 4.3.1<\/li>\s*<li>Item 4.3.2<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li>Item 5\s*<ul[^<>]*class="list"[^<>]*>\s*<li>Item 5.1<\/li>\s*<li>Item 5.2<\/li>\s*<\/ul>\s*<\/li>/', $result);

		$result = $this->Html->nestedList($list, array(), array('class' => 'item'));
		$this->assertPattern('/^<ul>\s*<li[^<>]*class="item"[^<>]*>Item 1<\/li>\s*<li[^<>]*class="item"[^<>]*>Item 2.+<\/li><li[^<>]*class="item"[^<>]*>Item 3<\/li>\s*<li[^<>]*class="item"[^<>]*>Item 4.+<\/li><li[^<>]*class="item"[^<>]*>Item 5.+<\/li><\/ul>$/', $result);
		$this->assertPattern('/<li[^<>]*class="item"[^<>]*>Item 2\s*<ul>\s*<li[^<>]*class="item"[^<>]*>Item 2.1<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li[^<>]*class="item"[^<>]*>Item 4\s*<ul>\s*<li[^<>]*class="item"[^<>]*>Item 4.1<\/li>\s*<li[^<>]*class="item"[^<>]*>Item 4.2<\/li>\s*<li[^<>]*class="item"[^<>]*>Item 4.3.+<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li[^<>]*class="item"[^<>]*>Item 4.3\s*<ul>\s*<li[^<>]*class="item"[^<>]*>Item 4.3.1<\/li>\s*<li[^<>]*class="item"[^<>]*>Item 4.3.2<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li[^<>]*class="item"[^<>]*>Item 5\s*<ul>\s*<li[^<>]*class="item"[^<>]*>Item 5.1<\/li>\s*<li[^<>]*class="item"[^<>]*>Item 5.2<\/li>\s*<\/ul>\s*<\/li>/', $result);

		$result = $this->Html->nestedList($list, array(), array('even' => 'even', 'odd' => 'odd'));
		$this->assertPattern('/^<ul>\s*<li[^<>]*class="odd"[^<>]*>Item 1<\/li>\s*<li[^<>]*class="even"[^<>]*>Item 2.+<\/li><li[^<>]*class="odd"[^<>]*>Item 3<\/li>\s*<li[^<>]*class="even"[^<>]*>Item 4.+<\/li><li[^<>]*class="odd"[^<>]*>Item 5.+<\/li><\/ul>$/', $result);
		$this->assertPattern('/<li[^<>]*class="even"[^<>]*>Item 2\s*<ul>\s*<li[^<>]*class="odd"[^<>]*>Item 2.1<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li[^<>]*class="even"[^<>]*>Item 4\s*<ul>\s*<li[^<>]*class="odd"[^<>]*>Item 4.1<\/li>\s*<li[^<>]*class="even"[^<>]*>Item 4.2<\/li>\s*<li[^<>]*class="odd"[^<>]*>Item 4.3.+<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li[^<>]*class="odd"[^<>]*>Item 4.3\s*<ul>\s*<li[^<>]*class="odd"[^<>]*>Item 4.3.1<\/li>\s*<li[^<>]*class="even"[^<>]*>Item 4.3.2<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li[^<>]*class="odd"[^<>]*>Item 5\s*<ul>\s*<li[^<>]*class="odd"[^<>]*>Item 5.1<\/li>\s*<li[^<>]*class="even"[^<>]*>Item 5.2<\/li>\s*<\/ul>\s*<\/li>/', $result);

		$result = $this->Html->nestedList($list, array('class'=>'list'), array('class' => 'item'));
		$this->assertPattern('/^<ul[^<>]*class="list"[^<>]*>\s*<li[^<>]*class="item"[^<>]*>Item 1<\/li>\s*<li[^<>]*class="item"[^<>]*>Item 2.+<\/li><li[^<>]*class="item"[^<>]*>Item 3<\/li>\s*<li[^<>]*class="item"[^<>]*>Item 4.+<\/li><li[^<>]*class="item"[^<>]*>Item 5.+<\/li><\/ul>$/', $result);
		$this->assertPattern('/<li[^<>]*class="item"[^<>]*>Item 2\s*<ul[^<>]*class="list"[^<>]*>\s*<li[^<>]*class="item"[^<>]*>Item 2.1<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li[^<>]*class="item"[^<>]*>Item 4\s*<ul[^<>]*class="list"[^<>]*>\s*<li[^<>]*class="item"[^<>]*>Item 4.1<\/li>\s*<li[^<>]*class="item"[^<>]*>Item 4.2<\/li>\s*<li[^<>]*class="item"[^<>]*>Item 4.3.+<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li[^<>]*class="item"[^<>]*>Item 4.3\s*<ul[^<>]*class="list"[^<>]*>\s*<li[^<>]*class="item"[^<>]*>Item 4.3.1<\/li>\s*<li[^<>]*class="item"[^<>]*>Item 4.3.2<\/li>\s*<\/ul>\s*<\/li>/', $result);
		$this->assertPattern('/<li[^<>]*class="item"[^<>]*>Item 5\s*<ul[^<>]*class="list"[^<>]*>\s*<li[^<>]*class="item"[^<>]*>Item 5.1<\/li>\s*<li[^<>]*class="item"[^<>]*>Item 5.2<\/li>\s*<\/ul>\s*<\/li>/', $result);
	}

	function tearDown() {
		unset($this->Html);
	}
}
?>