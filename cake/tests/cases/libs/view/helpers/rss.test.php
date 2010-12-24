<?php
/**
 * RssHelperTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs.view.helpers
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('View', 'View');
App::import('Helper', array('Rss', 'Time'));

/**
 * RssHelperTest class
 *
 * @package       cake.tests.cases.libs.view.helpers
 */
class RssHelperTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		parent::setUp();
		$controller = null;
		$this->View = new View($controller);
		$this->Rss = new RssHelper($this->View);
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		parent::tearDown();
		unset($this->Rss);
	}

/**
 * testDocument method
 *
 * @access public
 * @return void
 */
	function testDocument() {
		$result = $this->Rss->document();
		$expected = array(
			'rss' => array(
				'version' => '2.0'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Rss->document(null, 'content');
		$expected = array(
			'rss' => array(
				'version' => '2.0'
			),
			'content'
		);
		$this->assertTags($result, $expected);

		$result = $this->Rss->document(array('contrived' => 'parameter'), 'content');
		$expected = array(
			'rss' => array(
				'contrived' => 'parameter',
				'version' => '2.0'
			),
			'content'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testChannel method
 *
 * @access public
 * @return void
 */
	function testChannel() {
		$attrib = array('a' => '1', 'b' => '2');
		$elements = array('title' => 'title');
		$content = 'content';

		$result = $this->Rss->channel($attrib, $elements, $content);
		$expected = array(
			'channel' => array(
				'a' => '1',
				'b' => '2'
			),
			'<title',
			'title',
			'/title',
			'<link',
			$this->Rss->url('/', true),
			'/link',
			'<description',
			'content',
			'/channel'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test correct creation of channel sub elements.
 *
 * @access public
 * @return void
 */
	function testChannelElements() {
		$attrib = array();
		$elements = array(
			'title' => 'Title of RSS Feed',
			'link' => 'http://example.com',
			'description' => 'Description of RSS Feed',
			'image' => array(
				'title' => 'Title of image',
				'url' => 'http://example.com/example.png',
				'link' => 'http://example.com'
			),
			'cloud' => array(
				'domain' => "rpc.sys.com",
				'port' => "80",
				'path' =>"/RPC2",
				'registerProcedure' => "myCloud.rssPleaseNotify",
				'protocol' => "xml-rpc"
			)
		);
		$content = 'content-here';
		$result = $this->Rss->channel($attrib, $elements, $content);
		$expected = array(
			'<channel',
				'<title', 'Title of RSS Feed', '/title',
				'<link', 'http://example.com', '/link',
				'<description', 'Description of RSS Feed', '/description',
				'<image',
					'<title', 'Title of image', '/title',
					'<url', 'http://example.com/example.png', '/url',
					'<link', 'http://example.com', '/link',
				'/image',
				'cloud' => array(
					'domain' => "rpc.sys.com",
					'port' => "80",
					'path' =>"/RPC2",
					'registerProcedure' => "myCloud.rssPleaseNotify",
					'protocol' => "xml-rpc"
				),
			'content-here',
			'/channel',
		);
		$this->assertTags($result, $expected);
	}

	function testChannelElementAttributes() {
		$attrib = array();
		$elements = array(
			'title' => 'Title of RSS Feed',
			'link' => 'http://example.com',
			'description' => 'Description of RSS Feed',
			'image' => array(
				'title' => 'Title of image',
				'url' => 'http://example.com/example.png',
				'link' => 'http://example.com'
			),
			'atom:link' => array(
				'attrib' => array(
					'href' => 'http://www.example.com/rss.xml',
					'rel' => 'self',
					'type' => 'application/rss+xml')
			)
		);
		$content = 'content-here';
		$result = $this->Rss->channel($attrib, $elements, $content);
		$expected = array(
			'<channel',
				'<title', 'Title of RSS Feed', '/title',
				'<link', 'http://example.com', '/link',
				'<description', 'Description of RSS Feed', '/description',
				'<image',
					'<title', 'Title of image', '/title',
					'<url', 'http://example.com/example.png', '/url',
					'<link', 'http://example.com', '/link',
				'/image',
				'atom:link' => array(
					'xmlns:atom' => 'http://www.w3.org/2005/Atom',
					'href' => "http://www.example.com/rss.xml",
					'rel' => "self",
					'type' =>"application/rss+xml"
				),
			'content-here',
			'/channel',
		);
		$this->assertTags($result, $expected);
	}

/**
 * testItems method
 *
 * @access public
 * @return void
 */
	function testItems() {
		$items = array(
			array('title' => 'title1', 'guid' => 'http://www.example.com/guid1', 'link' => 'http://www.example.com/link1', 'description' => 'description1'),
			array('title' => 'title2', 'guid' => 'http://www.example.com/guid2', 'link' => 'http://www.example.com/link2', 'description' => 'description2'),
			array('title' => 'title3', 'guid' => 'http://www.example.com/guid3', 'link' => 'http://www.example.com/link3', 'description' => 'description3')
		);

		$result = $this->Rss->items($items);
		$expected = array(
			'<item',
				'<title', 'title1', '/title',
				'<guid', 'http://www.example.com/guid1', '/guid',
				'<link', 'http://www.example.com/link1', '/link',
				'<description', 'description1', '/description',
			'/item',
			'<item',
				'<title', 'title2', '/title',
				'<guid', 'http://www.example.com/guid2', '/guid',
				'<link', 'http://www.example.com/link2', '/link',
				'<description', 'description2', '/description',
			'/item',
			'<item',
				'<title', 'title3', '/title',
				'<guid', 'http://www.example.com/guid3', '/guid',
				'<link', 'http://www.example.com/link3', '/link',
				'<description', 'description3', '/description',
			'/item'
		);
		$this->assertTags($result, $expected);

		$result = $this->Rss->items(array());
		$expected = '';
		$this->assertEqual($result, $expected);
	}

/**
 * testItem method
 *
 * @access public
 * @return void
 */
	function testItem() {
		$item = array(
			'title' => 'My title',
			'description' => 'My description',
			'link' => 'http://www.google.com/'
		);
		$result = $this->Rss->item(null, $item);
		$expected = array(
			'<item',
			'<title',
			'My title',
			'/title',
			'<description',
			'My description',
			'/description',
			'<link',
			'http://www.google.com/',
			'/link',
			'<guid',
			'http://www.google.com/',
			'/guid',
			'/item'
		);
		$this->assertTags($result, $expected);

		$item = array(
			'title' => 'My Title',
			'link' => 'http://www.example.com/1',
			'description' => 'descriptive words',
			'pubDate' => '2008-05-31 12:00:00',
			'guid' => 'http://www.example.com/1'
		);
		$result = $this->Rss->item(null, $item);

		$expected = array(
			'<item',
			'<title',
			'My Title',
			'/title',
			'<link',
			'http://www.example.com/1',
			'/link',
			'<description',
			'descriptive words',
			'/description',
			'<pubDate',
			date('r', strtotime('2008-05-31 12:00:00')),
			'/pubDate',
			'<guid',
			'http://www.example.com/1',
			'/guid',
			'/item'
		);
		$this->assertTags($result, $expected);

		$item = array(
			'title' => 'My Title & more'
		);
		$result = $this->Rss->item(null, $item);
		$expected = array(
			'<item',
			'<title', 'My Title &amp; more', '/title',
			'/item'
		);
		$this->assertTags($result, $expected);

		$item = array(
			'title' => 'Foo bar',
			'link' => array(
				'url' => 'http://example.com/foo?a=1&b=2',
				'convertEntities' => false
			),
			'description' =>  array(
				'value' => 'descriptive words',
				'cdata' => true,
			),
			'pubDate' => '2008-05-31 12:00:00'
		);
		$result = $this->Rss->item(null, $item);
		$expected = array(
			'<item',
			'<title',
			'Foo bar',
			'/title',
			'<link',
			'http://example.com/foo?a=1&amp;b=2',
			'/link',
			'<description',
			'<![CDATA[descriptive words]]',
			'/description',
			'<pubDate',
			date('r', strtotime('2008-05-31 12:00:00')),
			'/pubDate',
			'<guid',
			'http://example.com/foo?a=1&amp;b=2',
			'/guid',
			'/item'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test item() with cdata blocks.
 *
 * @return void
 */
	function testItemCdata() {
		$item = array(
			'title' => array(
				'value' => 'My Title & more',
				'cdata' => true,
				'convertEntities' => false,
			)
		);
		$result = $this->Rss->item(null, $item);
		$expected = array(
			'<item',
			'<title',
			'<![CDATA[My Title & more]]',
			'/title',
			'/item'
		);
		$this->assertTags($result, $expected);

		$item = array(
			'category' => array(
				'value' => 'CakePHP',
				'cdata' => true,
				'domain' => 'http://www.cakephp.org',
			)
		);
		$result = $this->Rss->item(null, $item);
		$expected = array(
			'<item',
			'category' => array('domain' => 'http://www.cakephp.org'),
			'<![CDATA[CakePHP]]',
			'/category',
			'/item'
		);
		$this->assertTags($result, $expected);

		$item = array(
			'category' => array(
				array(
					'value' => 'CakePHP',
					'cdata' => true,
					'domain' => 'http://www.cakephp.org'
				),
				array(
					'value' => 'Bakery',
					'cdata' => true
				)
			)
		);
		$result = $this->Rss->item(null, $item);
		$expected = array(
			'<item',
			'category' => array('domain' => 'http://www.cakephp.org'),
			'<![CDATA[CakePHP]]',
			'/category',
			'<category',
			'<![CDATA[Bakery]]',
			'/category',
			'/item'
		);
		$this->assertTags($result, $expected);

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
			'enclosure' => array(
				'url' => '/test.flv'
			),
			'pubDate' => '2008-05-31 12:00:00',
			'guid' => 'http://www.example.com/1',
			'category' => array(
				array(
					'value' => 'CakePHP',
					'cdata' => true,
					'domain' => 'http://www.cakephp.org'
				),
				array(
					'value' => 'Bakery',
					'cdata' => true
				)
			)
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
			'enclosure' => array('url' => $this->Rss->url('/test.flv', true)),
			'<pubDate',
			date('r', strtotime('2008-05-31 12:00:00')),
			'/pubDate',
			'<guid',
			'http://www.example.com/1',
			'/guid',
			'category' => array('domain' => 'http://www.cakephp.org'),
			'<![CDATA[CakePHP]]',
			'/category',
			'<category',
			'<![CDATA[Bakery]]',
			'/category',
			'/item'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testTime method
 *
 * @access public
 * @return void
 */
	function testTime() {
	}

/**
 * testElementAttrNotInParent method
 *
 * @access public
 * @return void
 */
	function testElementAttrNotInParent() {
		$attributes = array(
			'title' => 'Some Title',
			'link' => 'http://link.com',
			'description' => 'description'
		);
		$elements = array('enclosure' => array('url' => 'http://test.com'));

		$result = $this->Rss->item($attributes, $elements);
		$expected = array(
			'item' => array(
				'title' => 'Some Title',
				'link' => 'http://link.com',
				'description' => 'description'
			),
			'enclosure' => array(
				'url' => 'http://test.com'
			),
			'/item'
		);
		$this->assertTags($result, $expected);
	}
}
