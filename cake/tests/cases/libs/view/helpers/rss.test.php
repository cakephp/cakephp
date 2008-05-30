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
App::import('Helper', array('Rss', 'Time'));

/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.view.helpers
 */
class RssTest extends CakeTestCase {

	function setUp() {
		$this->Rss =& new RssHelper();
		$this->Rss->Time = new TimeHelper();
	}

	function tearDown() {
		unset($this->Rss);
	}

	function testDocument() {
		$res = $this->Rss->document();
		$this->assertPattern('/^<rss version="2.0" \/>$/', $res);

		$res = $this->Rss->document(array('contrived' => 'parameter'));
		$this->assertPattern('/^<rss version="2.0"><parameter \/><\/rss>$/', $res);

		$res = $this->Rss->document(null, 'content');
		$this->assertPattern('/^<rss version="2.0">content<\/rss>$/', $res);

		$res = $this->Rss->document(array('contrived' => 'parameter'), 'content');
		$this->assertPattern('/^<rss[^<>]+version="2.0"[^<>]*>/', $res);
		$this->assertPattern('/<rss[^<>]+contrived="parameter"[^<>]*>/', $res);
		$this->assertNoPattern('/<rss[^<>]+[^version|contrived]=[^<>]*>/', $res);
	}

	function testChannel() {
		$attrib = array('a' => '1', 'b' => '2');
		$elements['title'] = 'title';
		$content = 'content';
		$res = $this->Rss->channel($attrib, $elements, $content);
		$this->assertPattern('/^<channel[^<>]+a="1"[^<>]*>/', $res);
		$this->assertPattern('/^<channel[^<>]+b="2"[^<>]*>/', $res);
		$this->assertNoPattern('/^<channel[^<>]+[^a|b]=[^<>]*/', $res);
		$this->assertPattern('/<title>title<\/title>/', $res);
		$this->assertPattern('/<link>'.str_replace('/', '\/', RssHelper::url('/', true)).'<\/link>/', $res);
		$this->assertPattern('/<description \/>/', $res);
		$this->assertPattern('/content<\/channel>$/', $res);
	}

	function testChannelElementLevelAttrib() {
		$attrib = array();
		$elements['title'] = 'title';
		$elements['image'] = array('myImage', 'attrib' => array('href' => 'http://localhost'));
		$content = 'content';
		$res = $this->Rss->channel($attrib, $elements, $content);
		$this->assertPattern('/^<channel>/', $res);
		$this->assertPattern('/<title>title<\/title>/', $res);
		$this->assertPattern('/<image[^<>]+href="http:\/\/localhost"><myImage \/><\/image>/', $res);
		$this->assertPattern('/<link>'.str_replace('/', '\/', RssHelper::url('/', true)).'<\/link>/', $res);
		$this->assertPattern('/<description \/>/', $res);
		$this->assertPattern('/content<\/channel>$/', $res);
	}

	function testItems() {
		$items = array(
			array('title' => 'title1', 'guid' => 'http://www.example.com/guid1', 'link' => 'http://www.example.com/link1', 'description' => 'description1'),
			array('title' => 'title2', 'guid' => 'http://www.example.com/guid2', 'link' => 'http://www.example.com/link2', 'description' => 'description2'),
			array('title' => 'title3', 'guid' => 'http://www.example.com/guid3', 'link' => 'http://www.example.com/link3', 'description' => 'description3')
		);

		$result = $this->Rss->items($items);
		$this->assertPattern('/^<item>.*<\/item><item>.*<\/item><item>.*<\/item>$/', $result);
		$this->assertPattern('/<item>.*<title>title1<\/title>.*<\/item>/', $result);
		$this->assertPattern('/<item>.*<guid>' . str_replace('/', '\/', 'http://www.example.com/guid1') . '<\/guid>.*<\/item>/', $result);
		$this->assertPattern('/<item>.*<link>' . str_replace('/', '\/', 'http://www.example.com/link1') . '<\/link>.*<\/item>/', $result);
		$this->assertPattern('/<item>.*<description>description1<\/description>.*<\/item>/', $result);
		$this->assertPattern('/<item>.*<title>title2<\/title>.*<\/item>/', $result);
		$this->assertPattern('/<item>.*<guid>' . str_replace('/', '\/', 'http://www.example.com/guid2') . '<\/guid>.*<\/item>/', $result);
		$this->assertPattern('/<item>.*<link>' . str_replace('/', '\/', 'http://www.example.com/link2') . '<\/link>.*<\/item>/', $result);
		$this->assertPattern('/<item>.*<description>description2<\/description>.*<\/item>/', $result);
		$this->assertPattern('/<item>.*<title>title3<\/title>.*<\/item>/', $result);
		$this->assertPattern('/<item>.*<guid>' . str_replace('/', '\/', 'http://www.example.com/guid3') . '<\/guid>.*<\/item>/', $result);
		$this->assertPattern('/<item>.*<link>' . str_replace('/', '\/', 'http://www.example.com/link3') . '<\/link>.*<\/item>/', $result);
		$this->assertPattern('/<item>.*<description>description3<\/description>.*<\/item>/', $result);

		$result = $this->Rss->items(array());
		$this->assertEqual($result, '');
	}

	function testItem() {
		$result = $this->Rss->item(null, array("title"=>"My title","description"=>"My description","link"=>"http://www.google.com/"));
		$expecting = '<item><title>My title</title><description>My description</description><link>http://www.google.com/</link><guid>http://www.google.com/</guid></item>';
		$this->assertEqual($result, $expecting);
		
		$item = array(
			'title' => array(
				'value' => 'My Title',
				'cdata' => true,
			),
			'link' => 'http://www.example.com/1',
			'description' => array(
				'value' => 'descriptive words',
				'cdata' => true,
			 ),
			'pubDate' => '2008-05-31 12:00:00',
			'guid' => 'http://www.example.com/1'
		);
		$result = $this->Rss->item(null, $item);
		$expected = array(
			'<item',
			'<title',
			'<![CDATA[My Title]]',
			'/title',
			'<link',
			'http://www.example.com/1',
			'/link',
			'<description',
			'<![CDATA[descriptive words]]',
			'/description',
			'<pubDate',
			'Sat, 31 May 2008 12:00:00 -0400',
			'/pubDate',
			'<guid',
			'http://www.example.com/1',
			'/guid',
			'/item'
		);
		$this->assertTags($result, $expected);
		
		$item = array(
			'title' => array(
				'value' => 'My Title & more',
				'cdata' => true
			)
		);
		$result = $this->Rss->item(null, $item);
		$expected = array(
			'<item',
			'<title',
			'<![CDATA[My Title &amp; more]]',
			'/title',
			'/item'
		);
		$this->assertTags($result, $expected);
		
		$item = array(
			'title' => array(
				'value' => 'My Title & more',
				'strip' => false
			)
		);
		$result = $this->Rss->item(null, $item);
		$expected = array(
			'<item',
			'<title',
			'My Title & more',
			'/title',
			'/item'
		);
		$this->assertTags($result, $expected);
	}

	function testTime() {
	}

	function testElementAttrNotInParent() {
		$attributes = array('title' => 'Some Title', 'link' => 'http://link.com', 'description' => 'description');
		$elements = array('enclosure' => array('url' => 'http://test.com'));

		$result = $this->Rss->item($attributes, $elements);
		$expected = array(
			'item' => array('title' => 'Some Title', 'link' => 'http://link.com', 'description' => 'description'),
			'enclosure' => array('url' => 'http://test.com'),
			'/item'
		);
		$this->assertTags($result, $expected);
	}
}
?>