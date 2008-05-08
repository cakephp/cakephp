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
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs.view.helpers
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', array('Helper', 'AppHelper', 'ClassRegistry', 'Controller', 'Model'));
App::import('Helper', array('Html', 'Form'));

class TheHtmlTestController extends Controller {
	var $name = 'TheTest';
	var $uses = null;
}

class HtmlHelperTest extends CakeTestCase {
	var $html = null;

	function setUp() {
		$this->Html =& new HtmlHelper();
		$view =& new View(new TheHtmlTestController());
		ClassRegistry::addObject('view', $view);
	}

	function testDocType() {
		$result = $this->Html->docType();
		$expected = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
		$this->assertEqual($result, $expected);
		
		$result = $this->Html->docType('html4-strict');
		$expected = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
		$this->assertEqual($result, $expected);
	}

	function testLink() {
		$result = $this->Html->link('/home');
		$expected = array('a' => array('href' => '/home'), 'preg:/\/home/', '/a');
		$this->assertTags($result, $expected);

		$result = $this->Html->link('Home', '/home', array('confirm' => 'Are you sure you want to do this?'));
		$expected = array(
			'a' => array('href' => '/home', 'onclick' => "return confirm(&#039;Are you sure you want to do this?&#039;);"),
			'Home',
			'/a'
		);
		$this->assertTags($result, $expected, true);

		$result = $this->Html->link('Home', '/home', array('default' => false));
		$expected = array(
			'a' => array('href' => '/home', 'onclick' => "event.returnValue = false; return false;"),
			'Home',
			'/a'
		);
		$this->assertTags($result, $expected);
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

		$result = $this->Html->image('test.gif', array('url' => '#'));
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

		Configure::write('Asset.timestamp', true);
		$result = $this->Html->image('logo.gif');
		$this->assertPattern('/^<img src=".*img\/logo\.gif\?"[^<>]+\/>$/', $result);
		Configure::write('Asset.timestamp', false);
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
		$expected = array(
			'link' => array('rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'preg:/.*css\/screen\.css/')
		);
		$this->assertTags($result, $expected);

		$result = $this->Html->css('screen.css');
		$this->assertTags($result, $expected);

		$result = $this->Html->css('screen.css?1234');
		$expected['link']['href'] = 'preg:/.*css\/screen\.css\?1234/';
		$this->assertTags($result, $expected);

		$result = $this->Html->css('http://whatever.com/screen.css?1234');
		$expected['link']['href'] = 'preg:/http:\/\/.*\/screen\.css\?1234/';
		$this->assertTags($result, $expected);

		Configure::write('Asset.timestamp', true);
		$result = $this->Html->css('cake.generic');
		$expected['link']['href'] = 'preg:/.*css\/cake\.generic\.css\?[0-9]+/';
		$this->assertTags($result, $expected);

		$debug = Configure::read('debug');
		Configure::write('debug', 0);
		$result = $this->Html->css('cake.generic');
		$expected['link']['href'] = 'preg:/.*css\/cake\.generic\.css/';
		$this->assertTags($result, $expected);

		Configure::write('Asset.timestamp', 'force');
		$result = $this->Html->css('cake.generic');
		$expected['link']['href'] = 'preg:/.*css\/cake\.generic\.css\?[0-9]+/';
		$this->assertTags($result, $expected);

		Configure::write('Asset.timestamp', false);
		Configure::write('debug', $debug);

		Configure::write('Asset.filter.css', 'css.php');
		$result = $this->Html->css('cake.generic');
		$expected['link']['href'] = 'preg:/.*ccss\/cake\.generic\.css/';
		$this->assertTags($result, $expected);
		Configure::write('Asset.filter.css', false);

		$result = explode("\n", trim($this->Html->css(array('cake.generic', 'vendor.generic'))));
		$expected['link']['href'] = 'preg:/.*css\/cake\.generic\.css/';
		$this->assertTags($result[0], $expected);
		$expected['link']['href'] = 'preg:/.*css\/vendor\.generic\.css/';
		$this->assertTags($result[1], $expected);
		$this->assertEqual(count($result), 2);
	}

	function testCharsetTag() {
		Configure::write('App.encoding', null);
		$this->assertPattern('/charset=utf-8"/', $this->Html->charset());

		Configure::write('App.encoding', 'ISO-8859-1');
		$this->assertPattern('/charset=iso-8859-1"/', $this->Html->charset());
		$this->assertPattern('/charset=UTF-7"/', $this->Html->charset('UTF-7'));
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

	function testMeta() {

		$result = $this->Html->meta('this is an rss feed', array('controller'=> 'posts', 'ext' => 'rss'));
		$this->assertPattern('/^<link[^<>]+href=".*\/posts\.rss"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<link[^<>]+rel="alternate"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<link[^<>]+type="application\/rss\+xml"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<link[^<>]+title="this is an rss feed"\/>$/', $result);

		$result = $this->Html->meta('rss', array('controller'=> 'posts', 'ext' => 'rss'), array('title' => 'this is an rss feed'));
		$this->assertPattern('/^<link[^<>]+href=".*\/posts\.rss"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<link[^<>]+rel="alternate"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<link[^<>]+type="application\/rss\+xml"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<link[^<>]+title="this is an rss feed"\/>$/', $result);

		$result = $this->Html->meta('icon', 'favicon.ico');
		$this->assertPattern('/^<link[^<>]+href=".*favicon\.ico"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<link[^<>]+type="image\/x-icon"[^<>]+/', $result);
		$this->assertPattern('/^<link[^<>]+rel="icon"\/>[^<>]*/', $result);
		$this->assertPattern('/<link[^<>]+rel="shortcut icon"\/>[^<>]*/', $result);

		$result = $this->Html->meta('keywords', 'these, are, some, meta, keywords');
		$this->assertPattern('/^<meta[^<>]+name="keywords"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<meta[^<>]+content="these, are, some, meta, keywords"\/>$/', $result);

		$result = $this->Html->meta('description', 'this is the meta description');
		$this->assertPattern('/^<meta[^<>]+name="description"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<meta[^<>]+content="this is the meta description"\/>$/', $result);

		$result = $this->Html->meta(array('name' => 'ROBOTS', 'content' => 'ALL'));
		$this->assertPattern('/^<meta[^<>]+name="ROBOTS"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<meta[^<>]+content="ALL"\/>$/', $result);

		$this->assertNull($this->Html->meta(array('name' => 'ROBOTS', 'content' => 'ALL'), null, array(), false));
		$view =& ClassRegistry::getObject('view');
		$result = $view->__scripts[0];

		$this->assertPattern('/^<meta[^<>]+name="ROBOTS"[^<>]+\/>$/', $result);
		$this->assertPattern('/^<meta[^<>]+content="ALL"\/>$/', $result);
	}

	function testTableHeaders() {
		$result = $this->Html->tableHeaders(array('ID', 'Name', 'Date'));
		$expected = array('<tr', '<th', 'ID', '/th', '<th', 'Name', '/th', '<th', 'Date', '/th', '/tr');
		$this->assertTags($result, $expected);
	}

	function testTableCells() {
		$tr = array(
			'td content 1',
			array('td content 2', array("width" => "100px")),
        	array('td content 3', "width=100px")
		);
		$result = $this->Html->tableCells($tr);
		$this->assertEqual('<tr><td>td content 1</td> <td width="100px">td content 2</td> <td width=100px>td content 3</td></tr>', $result);


		$tr = array('td content 1', 'td content 2', 'td content 3');
		$result = $this->Html->tableCells($tr, null, null, true);
		$this->assertEqual('<tr><td class="column-1">td content 1</td> <td class="column-2">td content 2</td> <td class="column-3">td content 3</td></tr>', $result);


		$tr = array('td content 1', 'td content 2', 'td content 3');
		$result = $this->Html->tableCells($tr, true);
		$this->assertEqual('<tr><td class="column-1">td content 1</td> <td class="column-2">td content 2</td> <td class="column-3">td content 3</td></tr>', $result);
	}

	function testDiv() {
		$result = $this->Html->div('class-name');
		$this->assertEqual($result, '<div class="class-name">');

		$result = $this->Html->div('class-name', 'text');
		$this->assertEqual($result, '<div class="class-name">text</div>');

		$result = $this->Html->div('class-name', '<text>', array(), true);
		$this->assertEqual($result, '<div class="class-name">&lt;text&gt;</div>');
	}


	function testPara() {
		$result = $this->Html->para('class-name');
		$this->assertEqual($result, '<p class="class-name">');

		$result = $this->Html->para('class-name', 'text');
		$this->assertEqual($result, '<p class="class-name">text</p>');

		$result = $this->Html->para('class-name', '<text>', array(), true);
		$this->assertEqual($result, '<p class="class-name">&lt;text&gt;</p>');
	}

	function tearDown() {
		unset($this->Html);
	}
}
?>