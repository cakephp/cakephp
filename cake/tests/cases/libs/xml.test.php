<?php
/**
 * XmlTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'Xml');

/**
 * XmlTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class XmlTest extends CakeTestCase {

/**
 * testBuild method
 *
 * @access public
 * @return void
 */
	function testBuild() {
		$xml = '<tag>value</tag>';
		$obj = Xml::build($xml);
		$this->assertTrue($obj instanceof SimpleXMLElement);
		$this->assertEqual((string)$obj->getName(), 'tag');
		$this->assertEqual((string)$obj, 'value');

		$xml = '<?xml version="1.0"?><tag>value</tag>';
		$this->assertEqual($obj, Xml::build($xml));

		$xml = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'fixtures' . DS . 'sample.xml';
		$obj = Xml::build($xml);
		$this->assertEqual($obj->getName(), 'tags');
		$this->assertEqual(count($obj), 2);

		$this->assertEqual(Xml::build($xml), Xml::build(file_get_contents($xml)));

		$xml = array('tag' => 'value');
		$obj = Xml::build($xml);
		$this->assertEqual($obj->getName(), 'tag');
		$this->assertEqual((string)$obj, 'value');
	}

/**
 * data provider function for testBuildInvalidData
 *
 * @return array
 */
	public static function invalidDataProvider() {
		return array(
			array(null),
			array(false),
			array(''),
			array('<tag>')
		);
	}

/**
 * testBuildInvalidData
 *
 * @dataProvider invalidDataProvider
 * @expectedException Exception
 * return void
 */
	function testBuildInvalidData($value) {
		Xml::build($value);
	}

/**
 * testFromArray method
 *
 * @access public
 * @return void
 */
	function testFromArray() {
		$xml = array('tag' => 'value');
		$obj = Xml::fromArray($xml);
		$this->assertEqual($obj->getName(), 'tag');
		$this->assertEqual((string)$obj, 'value');

		$xml = array('tag' => null);
		$obj = Xml::fromArray($xml);
		$this->assertEqual($obj->getName(), 'tag');
		$this->assertEqual((string)$obj, '');

		$xml = array('tag' => array('@' => 'value'));
		$obj = Xml::fromArray($xml);
		$this->assertEqual($obj->getName(), 'tag');
		$this->assertEqual((string)$obj, 'value');

		$xml = array(
			'tags' => array(
				'tag' => array(
					array(
						'id' => '1',
						'name' => 'defect'
					),
					array(
						'id' => '2',
						'name' => 'enhancement'
					)
				)
			)
		);
		$obj = Xml::fromArray($xml);
		$this->assertTrue($obj instanceof SimpleXMLElement);
		$this->assertEqual($obj->getName(), 'tags');
		$this->assertEqual(count($obj), 2);
		$xmlText = '<' . '?xml version="1.0"?><tags><tag id="1" name="defect"/><tag id="2" name="enhancement"/></tags>';
		$this->assertEqual(str_replace(array("\r", "\n"), '', $obj->asXML()), $xmlText);

		$obj = Xml::fromArray($xml, 'tags');
		$this->assertTrue($obj instanceof SimpleXMLElement);
		$this->assertEqual($obj->getName(), 'tags');
		$this->assertEqual(count($obj), 2);
		$xmlText = '<' . '?xml version="1.0"?><tags><tag><id>1</id><name>defect</name></tag><tag><id>2</id><name>enhancement</name></tag></tags>';
		$this->assertEqual(str_replace(array("\r", "\n"), '', $obj->asXML()), $xmlText);

		$xml = array(
			'tags' => array(
			)
		);
		$obj = Xml::fromArray($xml);
		$this->assertEqual($obj->getName(), 'tags');
		$this->assertEqual((string)$obj, '');

		$xml = array(
			'tags' => array(
				'bool' => true,
				'int' => 1,
				'float' => 10.2,
				'string' => 'ok',
				'null' => null,
				'array' => array()
			)
		);
		$obj = Xml::fromArray($xml, 'tags');
		$this->assertEqual(count($obj), 6);
		$this->assertIdentical((string)$obj->bool, '1');
		$this->assertIdentical((string)$obj->int, '1');
		$this->assertIdentical((string)$obj->float, '10.2');
		$this->assertIdentical((string)$obj->string, 'ok');
		$this->assertIdentical((string)$obj->null, '');
		$this->assertIdentical((string)$obj->array, '');

		$xml = array(
			'tags' => array(
				'tag' => array(
					array(
						'@id' => '1',
						'name' => 'defect'
					),
					array(
						'@id' => '2',
						'name' => 'enhancement'
					)
				)
			)
		);
		$obj = Xml::fromArray($xml, 'tags');
		$xmlText = '<' . '?xml version="1.0"?><tags><tag id="1"><name>defect</name></tag><tag id="2"><name>enhancement</name></tag></tags>';
		$this->assertEqual(str_replace(array("\r", "\n"), '', $obj->asXML()), $xmlText);

		$xml = array(
			'tags' => array(
				'tag' => array(
					array(
						'@id' => '1',
						'name' => 'defect',
						'@' => 'Tag 1'
					),
					array(
						'@id' => '2',
						'name' => 'enhancement'
					),
				),
				'@' => 'All tags'
			)
		);
		$obj = Xml::fromArray($xml, 'tags');
		$xmlText = '<' . '?xml version="1.0"?><tags>All tags<tag id="1">Tag 1<name>defect</name></tag><tag id="2"><name>enhancement</name></tag></tags>';
		$this->assertEqual(str_replace(array("\r", "\n"), '', $obj->asXML()), $xmlText);

		$xml = array(
			'tags' => array(
				'tag' => array(
					'id' => 1,
					'@' => 'defect'
				)
			)
		);
		$obj = Xml::fromArray($xml);
		$xmlText = '<' . '?xml version="1.0"?><tags><tag id="1">defect</tag></tags>';
		$this->assertEqual(str_replace(array("\r", "\n"), '', $obj->asXML()), $xmlText);
	}

/**
 * data provider for fromArray() failures
 *
 * @return array
 */
	public static function invalidArrayDataProvider() {
		return array(
			array(''),
			array(null),
			array(false),
			array(array()),
			array(array('numeric key as root')),
			array(array('item1' => '', 'item2' => '')),
			array(array('items' => array('item1', 'item2'))),
			array(array(
				'tags' => array(
					'tag' => array(
						array(
							array(
								'string'
							)
						)
					)
				)
			)),
			array(array(
				'tags' => array(
					'@tag' => array(
						array(
							'@id' => '1',
							'name' => 'defect'
						),
						array(
							'@id' => '2',
							'name' => 'enhancement'
						)
					)
				)
			)),
			array(new DateTime())
		);
	}

/**
 * testFromArrayFail method
 *
 * @dataProvider invalidArrayDataProvider
 * @expectedException Exception
 */
	function testFromArrayFail($value) {
		Xml::fromArray($value);
	}

/**
 * testToArray method
 *
 * @access public
 * @return void
 */
	function testToArray() {
		$xml = '<tag>name</tag>';
		$obj = Xml::build($xml);
		$this->assertEqual(Xml::toArray($obj), array('tag' => 'name'));

		$xml = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'fixtures' . DS . 'sample.xml';
		$obj = Xml::build($xml);
		$expected = array(
			'tags' => array(
				'tag' => array(
					array(
						'@id' => '1',
						'name' => 'defect'
					),
					array(
						'@id' => '2',
						'name' => 'enhancement'
					)
				)
			)
		);
		$this->assertEqual(Xml::toArray($obj), $expected);

		$array = array(
			'tags' => array(
				'tag' => array(
					array(
						'id' => '1',
						'name' => 'defect'
					),
					array(
						'id' => '2',
						'name' => 'enhancement'
					)
				)
			)
		);
		$this->assertEqual(Xml::toArray(Xml::fromArray($array, 'tags')), $array);

		$expected = array(
			'tags' => array(
				'tag' => array(
					array(
						'@id' => '1',
						'@name' => 'defect'
					),
					array(
						'@id' => '2',
						'@name' => 'enhancement'
					)
				)
			)
		);
		$this->assertEqual(Xml::toArray(Xml::fromArray($array)), $expected);
		$this->assertEqual(Xml::toArray(Xml::fromArray($array, 'tags')), $array);

		$array = array(
			'tags' => array(
				'tag' => array(
					'id' => '1',
					'posts' => array(
						array('id' => '1'),
						array('id' => '2')
					)
				),
				'tagOther' => array(
					'subtag' => array(
						'id' => '1'
					)
				)
			)
		);
		$expected = array(
			'tags' => array(
				'tag' => array(
					'@id' => '1',
					'posts' => array(
						array('@id' => '1'),
						array('@id' => '2')
					)
				),
				'tagOther' => array(
					'subtag' => array(
						'@id' => '1'
					)
				)
			)
		);
		$this->assertEqual(Xml::toArray(Xml::fromArray($array)), $expected);

		$xml = '<root>';
		$xml .= '<tag id="1">defect</tag>';
		$xml .= '</root>';
		$obj = Xml::build($xml);

		$expected = array(
			'root' => array(
				'tag' => array(
					'@id' => 1,
					'@' => 'defect'
				)
			)
		);
		$this->assertEqual(Xml::toArray($obj), $expected);

		$xml = '<root>';
		$xml .= '<table xmlns="http://www.w3.org/TR/html4/"><tr><td>Apples</td><td>Bananas</td></tr></table>';
		$xml .= '<table xmlns="http://www.cakephp.org"><name>CakePHP</name><license>MIT</license></table>';
		$xml .= '<table>The book is on the table.</table>';
		$xml .= '</root>';
		$obj = Xml::build($xml);

		$expected = array(
			'root' => array(
				'table' => array(
					array('tr' => array('td' => array('Apples', 'Bananas'))),
					array('name' => 'CakePHP', 'license' => 'MIT'),
					'The book is on the table.'
				)
			)
		);
		$this->assertEqual(Xml::toArray($obj), $expected);

		$xml = '<root xmlns:cake="http://www.cakephp.org/">';
		$xml .= '<tag>defect</tag>';
		$xml .= '<cake:bug>1</cake:bug>';
		$xml .= '</root>';
		$obj = Xml::build($xml);

		$expected = array(
			'root' => array(
				'tag' => 'defect',
				'bug' => 1
			)
		);
		$this->assertEqual(Xml::toArray($obj), $expected);
	}

/**
 * testToArrayRss
 *
 * @return void
 */
	function testToArrayRss() {
		$rss = <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<atom:link href="http://bakery.cakephp.org/articles/rss" rel="self" type="application/rss+xml" />
		<title>The Bakery: </title>
		<link>http://bakery.cakephp.org/</link>
		<description>Recent  Articles at The Bakery.</description>
		<language>en-us</language>
		<pubDate>Wed, 01 Sep 2010 12:09:25 -0500</pubDate>
		<docs>http://validator.w3.org/feed/docs/rss2.html</docs>
		<generator>CakePHP Bakery</generator>
		<managingEditor>mariano@cricava.com (Mariano Iglesias)</managingEditor>
		<webMaster>gwoo@cakephp.org (Garrett Woodworth)</webMaster>
		<item>
			<title>EpisodeCMS</title>
			<link>http://bakery.cakephp.org/articles/view/episodecms</link>
			<description>EpisodeCMS is CakePHP based content management system.
Features: control panel, events API, module management, multilanguage and translations, themes
http://episodecms.com/

Please help me to improve it. Thanks.</description>
			<pubDate>Tue, 31 Aug 2010 02:07:02 -0500</pubDate>
			<guid>http://bakery.cakephp.org/articles/view/episodecms</guid>
		</item>
		<item>
			<title>Alertpay automated sales via IPN</title>
			<link>http://bakery.cakephp.org/articles/view/alertpay-automated-sales-via-ipn</link>
			<description>I&#039;m going to show you how I implemented a payment module via the Alertpay payment processor.</description>
			<pubDate>Tue, 31 Aug 2010 01:42:00 -0500</pubDate>
			<guid>http://bakery.cakephp.org/articles/view/alertpay-automated-sales-via-ipn</guid>
		</item>
	</channel>
</rss>
EOF;
		$rssAsArray = Xml::toArray(Xml::build($rss));
		$this->assertEqual($rssAsArray['rss']['@version'], '2.0');
		$this->assertEqual(count($rssAsArray['rss']['channel']['item']), 2);

		$expected = array(
			'title' => 'Alertpay automated sales via IPN',
			'link' => 'http://bakery.cakephp.org/articles/view/alertpay-automated-sales-via-ipn',
			'description' => 'I\'m going to show you how I implemented a payment module via the Alertpay payment processor.',
			'pubDate' => 'Tue, 31 Aug 2010 01:42:00 -0500',
			'guid' => 'http://bakery.cakephp.org/articles/view/alertpay-automated-sales-via-ipn'
		);
		$this->assertIdentical($rssAsArray['rss']['channel']['item'][1], $expected);
	}

/**
 * data provider for toArray() failures
 *
 * @return array
 */
	public static function invalidToArrayDataProvider() {
		return array(
			array(new DateTime()),
			array(array())
		);
	}

/**
 * testToArrayFail method
 *
 * @dataProvider invalidToArrayDataProvider
 * @expectedException Exception
 */
	function testToArrayFail($value) {
		Xml::toArray($value);
	}

}
