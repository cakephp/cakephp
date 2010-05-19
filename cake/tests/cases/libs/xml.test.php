<?php
/**
 * XmlTest file
 *
 * PHP versions 4 and 5
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
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$manager =& new XmlManager();
		$manager->namespaces = array();
	}

/**
 * testRootTagParsing method
 *
 * @access public
 * @return void
 */
	function testRootTagParsing() {
		$input = '<' . '?xml version="1.0" encoding="UTF-8" ?' . '>' . "\n" .
			'<plugin id="1" version_id="1" name="my_plugin" title="My Plugin" author="Me" author_email="me@cakephp.org" description="My awesome package" created="2008-01-28 18:21:13" updated="2008-01-28 18:21:13">'
			.'<current id="1" plugin_id="1" name="1.0" file="" created="2008-01-28 18:21:13" updated="2008-01-28 18:21:13" />'
			.'<version id="1" plugin_id="1" name="1.0" file="" created="2008-01-28 18:21:13" updated="2008-01-28 18:21:13" />'
			.'</plugin>';
		$xml = new Xml($input);
		$this->assertEqual($xml->children[0]->name, 'plugin');
		$this->assertEqual($xml->children[0]->children[0]->name, 'current');
		$this->assertEqual($xml->toString(true), $input);
	}

/**
 * testSerialization method
 *
 * @access public
 * @return void
 */
	function testSerialization() {
		$input = array(
			array(
				'Project' => array('id' => 1, 'title' => null, 'client_id' => 1, 'show' => 1, 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => 1, 'industry_id' => 1, 'modified' => null, 'created' => null),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 1, 'name' => 'Touch Screen Kiosk'),
				'Industry' => array('id' => 1, 'name' => 'Financial')
			),
			array(
				'Project' => array('id' => 2, 'title' => null, 'client_id' => 2, 'show' => 1, 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => 2, 'industry_id' => 2, 'modified' => '2007-11-26 14:48:36', 'created' => null),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 2, 'name' => 'Awareness Campaign'),
				'Industry' => array('id' => 2, 'name' => 'Education')
			)
		);

		$xml = new Xml($input);
		$result = preg_replace("/\n/",'', $xml->toString(false));
		$expected = '<project id="1" title="" client_id="1" show="1" is_spotlight="" style_id="0" job_type_id="1" industry_id="1" modified="" created=""><style id="" name="" /><job_type id="1" name="Touch Screen Kiosk" /><industry id="1" name="Financial" /></project><project id="2" title="" client_id="2" show="1" is_spotlight="" style_id="0" job_type_id="2" industry_id="2" modified="2007-11-26 14:48:36" created=""><style id="" name="" /><job_type id="2" name="Awareness Campaign" /><industry id="2" name="Education" /></project>';
		$this->assertEqual($result, $expected);

		$input = array(
			'Project' => array('id' => 1, 'title' => null, 'client_id' => 1, 'show' => 1, 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => 1, 'industry_id' => 1, 'modified' => null, 'created' => null),
			'Style' => array('id' => null, 'name' => null),
			'JobType' => array('id' => 1, 'name' => 'Touch Screen Kiosk'),
			'Industry' => array('id' => 1, 'name' => 'Financial')
		);
		$expected = '<project id="1" title="" client_id="1" show="1" is_spotlight="" style_id="0" job_type_id="1" industry_id="1" modified="" created=""><style id="" name="" /><job_type id="1" name="Touch Screen Kiosk" /><industry id="1" name="Financial" /></project>';
		$xml = new Xml($input);
		$result = preg_replace("/\n/",'', $xml->toString(false));
		$this->assertEqual($result, $expected);
	}

/**
 * testSerializeOnMultiDimensionalArray method
 *
 * @access public
 * @return void
 */
	function testSerializeOnMultiDimensionalArray() {
		$data = array(
			'Statuses' => array(
				array('Status' => array('id' => 1)),
				array('Status' => array('id' => 2))
			)
		);
		$result =& new Xml($data, array('format' => 'tags'));
		$expected = '<statuses><status><id>1</id></status><status><id>2</id></status></statuses>';
		$this->assertIdentical($result->toString(), $expected);
	}

/**
 * test serialization of boolean and null values.  false = 0, true = 1, null = ''
 *
 * @return void
 */
	function testSerializationOfBooleanAndBooleanishValues() {
		$xml =& new Xml(array('data' => array('example' => false)));
		$result = $xml->toString(false);
		$expected = '<data example="0" />';
		$this->assertEqual($result, $expected, 'Boolean values incorrectly handled. %s');

		$xml =& new Xml(array('data' => array('example' => true)));
		$result = $xml->toString(false);
		$expected = '<data example="1" />';
		$this->assertEqual($result, $expected, 'Boolean values incorrectly handled. %s');

		$xml =& new Xml(array('data' => array('example' => null)));
		$result = $xml->toString(false);
		$expected = '<data example="" />';
		$this->assertEqual($result, $expected, 'Boolean values incorrectly handled. %s');

		$xml =& new Xml(array('data' => array('example' => 0)));
		$result = $xml->toString(false);
		$expected = '<data example="0" />';
		$this->assertEqual($result, $expected, 'Boolean-ish values incorrectly handled. %s');

		$xml =& new Xml(array('data' => array('example' => 1)));
		$result = $xml->toString(false);
		$expected = '<data example="1" />';
		$this->assertEqual($result, $expected, 'Boolean-ish values incorrectly handled. %s');
	}

/**
 * testSimpleArray method
 *
 * @access public
 * @return void
 */
	function testSimpleArray() {
		$xml = new Xml(array('hello' => 'world'), array('format' => 'tags'));

		$result = $xml->toString(false);
		$expected = '<hello><![CDATA[world]]></hello>';
		$this->assertEqual($expected, $result);
	}

/**
 * testSimpleObject method
 *
 * @access public
 * @return void
 */
	function testSimpleObject() {
		$input = new StdClass();
		$input->hello = 'world';
		$xml = new Xml($input, array('format' => 'tags'));

		$result = $xml->toString(false);
		$expected = '<hello><![CDATA[world]]></hello>';
		$this->assertEqual($expected, $result);
	}

/**
 * testSimpleArrayWithZeroValues method
 *
 * @access public
 * @return void
 */
	function testSimpleArrayWithZeroValues() {
		$xml = new Xml(array('zero_string' => '0', 'zero_integer' => 0), array('format' => 'tags'));

		$result = $xml->toString(false);
		$expected = '<zero_string>0</zero_string><zero_integer>0</zero_integer>';
		$this->assertEqual($expected, $result);

		$data = array(
			'Client' => array(
				'id' => 3,
				'object_id' => 9,
				'key' => 'alt',
				'name' => 'Client Two',
				'created_by' => 4,
				'status' => '0',
				'num_projects' => 0
			)
		);
		$xml = new Xml($data, array('format' => 'tags'));
		$result = $xml->toString(array('format' => 'tags', 'header' => false));
		$this->assertPattern('/<status>0<\/status>/', $result);
		$this->assertPattern('/<num_projects>0<\/num_projects>/', $result);
	}
/**
 * testHeader method
 *
 * @access public
 * @return void
 */
	function testHeader() {
		$input = new stdClass();
		$input->hello = 'world';
		$xml = new Xml($input, array('format' => 'tags'));

		$result = $xml->toString(array('header' => true));
		$expected = '<'.'?xml version="1.0" encoding="UTF-8" ?'.'>'."\n".'<hello><![CDATA[world]]></hello>';
		$this->assertEqual($expected, $result);
	}

/**
 * testOwnerAssignment method
 *
 * @access public
 * @return void
 */
	function testOwnerAssignment() {
		$xml = new Xml();
		$node =& $xml->createElement('hello', 'world');
		$owner =& $node->document();
		$this->assertTrue($xml === $owner);

		$children =& $node->children;
		$childOwner =& $children[0]->document();
		$this->assertTrue($xml === $childOwner);
	}

/**
 * testArraySingleSerialization method
 *
 * @access public
 * @return void
 */
	function testArraySingleSerialization() {
		$input = array(
			'Post' => array(
				'id' => '1', 'author_id' => '1', 'title' => 'First Post',
				'body' => 'First Post Body', 'published' => 'Y',
				'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Author' => array(
				'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31', 'test' => 'working'
			)
		);

		$expected = '<post><id>1</id><author_id>1</author_id><title><![CDATA[First Post]]>';
		$expected .= '</title><body><![CDATA[First Post Body]]></body><published><![CDATA[Y]]>';
		$expected .= '</published><created><![CDATA[2007-03-18 10:39:23]]></created><updated>';
		$expected .= '<![CDATA[2007-03-18 10:41:31]]></updated><author><id>1</id><user>';
		$expected .= '<![CDATA[mariano]]></user><password><![CDATA[5f4dcc3b5aa765d61d8327deb882';
		$expected .= 'cf99]]></password><created><![CDATA[2007-03-17 01:16:23]]></created>';
		$expected .= '<updated><![CDATA[2007-03-17 01:18:31]]></updated><test><![CDATA[working]]>';
		$expected .= '</test></author></post>';

		$xml = new Xml($input, array('format' => 'tags'));
		$result = $xml->toString(false);
		$this->assertEqual($expected, $result);
	}

/**
 * testArraySerialization method
 *
 * @access public
 * @return void
 */
	function testSerializationArray() {
		$input = array(
			array(
				'Project' => array('id' => 1, 'title' => null, 'client_id' => 1, 'show' => 1, 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => 1, 'industry_id' => 1, 'modified' => null, 'created' => null),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 1, 'name' => 'Touch Screen Kiosk'),
				'Industry' => array('id' => 1, 'name' => 'Financial')
			),
			array(
				'Project' => array('id' => 2, 'title' => null, 'client_id' => 2, 'show' => 1, 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => 2, 'industry_id' => 2, 'modified' => '2007-11-26 14:48:36', 'created' => null),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 2, 'name' => 'Awareness Campaign'),
				'Industry' => array('id' => 2, 'name' => 'Education'),
			)
		);
		$expected = '<project><id>1</id><title /><client_id>1</client_id><show>1</show><is_spotlight /><style_id>0</style_id><job_type_id>1</job_type_id><industry_id>1</industry_id><modified /><created /><style><id /><name /></style><job_type><id>1</id><name>Touch Screen Kiosk</name></job_type><industry><id>1</id><name>Financial</name></industry></project><project><id>2</id><title /><client_id>2</client_id><show>1</show><is_spotlight /><style_id>0</style_id><job_type_id>2</job_type_id><industry_id>2</industry_id><modified>2007-11-26 14:48:36</modified><created /><style><id /><name /></style><job_type><id>2</id><name>Awareness Campaign</name></job_type><industry><id>2</id><name>Education</name></industry></project>';

		$xml = new Xml($input, array('format' => 'tags'));
		$result = $xml->toString(array('header' => false, 'cdata' => false));
		$this->assertEqual($expected, $result);
	}

/**
 * testNestedArraySerialization method
 *
 * @access public
 * @return void
 */
	function testSerializationNestedArray() {
		$input = array(
			array(
				'Project' => array('id' => 1, 'title' => null, 'client_id' => 1, 'show' => 1, 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => 1, 'industry_id' => 1, 'modified' => null, 'created' => null),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 1, 'name' => 'Touch Screen Kiosk'),
				'Industry' => array('id' => 1, 'name' => 'Financial'),
				'BusinessSolution' => array(array('id' => 6, 'name' => 'Convert Sales')),
				'MediaType' => array(
					array('id' => 15, 'name' => 'Print'),
					array('id' => 7, 'name' => 'Web Demo'),
					array('id' => 6, 'name' => 'CD-ROM')
				)
			),
			array(
				'Project' => array('id' => 2, 'title' => null, 'client_id' => 2, 'show' => 1, 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => 2, 'industry_id' => 2, 'modified' => '2007-11-26 14:48:36', 'created' => null),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 2, 'name' => 'Awareness Campaign'),
				'Industry' => array('id' => 2, 'name' => 'Education'),
				'BusinessSolution' => array(
					array('id' => 4, 'name' => 'Build Relationship'),
					array('id' => 6, 'name' => 'Convert Sales')
				),
				'MediaType' => array(
					array('id' => 17, 'name' => 'Web'),
					array('id' => 6, 'name' => 'CD-ROM')
				)
			)
		);
		$expected = '<project><id>1</id><title /><client_id>1</client_id><show>1</show><is_spotlight /><style_id>0</style_id><job_type_id>1</job_type_id><industry_id>1</industry_id><modified /><created /><style><id /><name /></style><job_type><id>1</id><name>Touch Screen Kiosk</name></job_type><industry><id>1</id><name>Financial</name></industry><business_solution><id>6</id><name>Convert Sales</name></business_solution><media_type><id>15</id><name>Print</name></media_type><media_type><id>7</id><name>Web Demo</name></media_type><media_type><id>6</id><name>CD-ROM</name></media_type></project><project><id>2</id><title /><client_id>2</client_id><show>1</show><is_spotlight /><style_id>0</style_id><job_type_id>2</job_type_id><industry_id>2</industry_id><modified>2007-11-26 14:48:36</modified><created /><style><id /><name /></style><job_type><id>2</id><name>Awareness Campaign</name></job_type><industry><id>2</id><name>Education</name></industry><business_solution><id>4</id><name>Build Relationship</name></business_solution><business_solution><id>6</id><name>Convert Sales</name></business_solution><media_type><id>17</id><name>Web</name></media_type><media_type><id>6</id><name>CD-ROM</name></media_type></project>';

		$xml = new Xml($input, array('format' => 'tags'));
		$result = $xml->toString(array('header' => false, 'cdata' => false));
		$this->assertEqual($expected, $result);
	}

/**
 * Prove that serialization with a given root node works
 * as expected.
 *
 * @access public
 * @return void
 * @link   https://trac.cakephp.org/ticket/6294
 */
	function testArraySerializationWithRoot() {
		$input = array(
			array('Shirt' => array('id' => 1, 'color' => 'green')),
			array('Shirt' => array('id' => 2, 'color' => 'blue')),
		);
		$expected = '<collection><shirt id="1" color="green" />';
		$expected .= '<shirt id="2" color="blue" /></collection>';

		$Xml = new Xml($input, array('root' => 'collection'));
		$result = $Xml->toString(array('header' => false));
		$this->assertEqual($expected, $result);
	}

/**
 * testCloneNode
 *
 * @access public
 * @return void
 */
	function testCloneNode() {
		$node =& new XmlNode('element', 'myValue');
		$twin =& $node->cloneNode();
		$this->assertEqual($node, $twin);
	}

/**
 * testNextSibling
 *
 * @access public
 * @return void
 */
	function testNextSibling() {
		$input = array(
			array(
				'Project' => array('id' => 1, 'title' => null, 'client_id' => 1, 'show' => '1', 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => '1.89', 'industry_id' => '1.56', 'modified' => null, 'created' => null),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 1, 'name' => 'Touch Screen Kiosk'),
				'Industry' => array('id' => 1, 'name' => 'Financial')
			),
			array(
				'Project' => array('id' => 2, 'title' => null, 'client_id' => 2, 'show' => '1', 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => '2.2', 'industry_id' => 2.2, 'modified' => '2007-11-26 14:48:36', 'created' => null),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 2, 'name' => 'Awareness Campaign'),
				'Industry' => array('id' => 2, 'name' => 'Education'),
			)
		);
		$xml =& new Xml($input, array('format' => 'tags'));
		$node =& $xml->children[0]->children[0];

		$nextSibling =& $node->nextSibling();
		$this->assertEqual($nextSibling, $xml->children[0]->children[1]);

		$nextSibling2 =& $nextSibling->nextSibling();
		$this->assertEqual($nextSibling2, $xml->children[0]->children[2]);

		$noFriends =& $xml->children[0]->children[12];
		$this->assertNull($noFriends->nextSibling());
	}

/**
 * testPreviousSibling
 *
 * @access public
 * @return void
 */
	function testPreviousSibling() {
		$input = array(
			array(
				'Project' => array('id' => 1, 'title' => null, 'client_id' => 1, 'show' => '1', 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => '1.89', 'industry_id' => '1.56', 'modified' => null, 'created' => null),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 1, 'name' => 'Touch Screen Kiosk'),
				'Industry' => array('id' => 1, 'name' => 'Financial')
			),
			array(
				'Project' => array('id' => 2, 'title' => null, 'client_id' => 2, 'show' => '1', 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => '2.2', 'industry_id' => 2.2, 'modified' => '2007-11-26 14:48:36', 'created' => null),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 2, 'name' => 'Awareness Campaign'),
				'Industry' => array('id' => 2, 'name' => 'Education'),
			)
		);
		$xml =& new Xml($input, array('format' => 'tags'));
		$node =& $xml->children[0]->children[1];

		$prevSibling =& $node->previousSibling();
		$this->assertEqual($prevSibling, $xml->children[0]->children[0]);

		$this->assertNull($prevSibling->previousSibling());
	}

/**
 * testAddAndRemoveAttributes
 *
 * @access public
 * @return void
 */
	function testAddAndRemoveAttributes() {
		$node =& new XmlElement('myElement', 'superValue');
		$this->assertTrue(empty($node->attributes));

		$attrs = array(
			'id' => 'test',
			'show' => 1,
			'is_spotlight' => 1,
		);
		$node->addAttribute($attrs);
		$this->assertEqual($node->attributes, $attrs);

		$node =& new XmlElement('myElement', 'superValue');
		$node->addAttribute('test', 'value');
		$this->assertTrue(isset($node->attributes['test']));

		$node =& new XmlElement('myElement', 'superValue');
		$obj =& new StdClass();
		$obj->class = 'info';
		$obj->id = 'primaryInfoBox';
		$node->addAttribute($obj);
		$expected = array(
			'class' => 'info',
			'id' => 'primaryInfoBox',
		);
		$this->assertEqual($node->attributes, $expected);

		$result = $node->removeAttribute('class');
		$this->assertTrue($result);
		$this->assertFalse(isset($node->attributes['class']));

		$result = $node->removeAttribute('missing');
		$this->assertFalse($result);
	}

	/**
	 * Tests that XML documents with non-standard spacing (i.e. leading whitespace, whole document
	 * on one line) still parse properly.
	 *
	 * @return void
	 */
	function testParsingWithNonStandardWhitespace() {
		$raw = '<?xml version="1.0" encoding="ISO-8859-1" ?><prices><price>1.0</price></prices>';
		$array = array('Prices' => array('price' => 1.0));

		$xml = new Xml($raw);
		$this->assertEqual($xml->toArray(), $array);
		$this->assertEqual($xml->__header, 'xml version="1.0" encoding="ISO-8859-1"');

		$xml = new Xml(' ' . $raw);
		$this->assertEqual($xml->toArray(), $array);
		$this->assertEqual($xml->__header, 'xml version="1.0" encoding="ISO-8859-1"');

		$xml = new Xml("\n" . $raw);
		$this->assertEqual($xml->toArray(), $array);
		$this->assertEqual($xml->__header, 'xml version="1.0" encoding="ISO-8859-1"');
	}

	/* Not implemented yet */
	/* function testChildFilter() {
	 	$input = array(
	 		array(
	 			'Project' => array('id' => 1, 'title' => null, 'client_id' => 1, 'show' => 1, 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => 1, 'industry_id' => 1, 'modified' => null, 'created' => null),
	 			'Style' => array('id' => null, 'name' => null),
	 			'JobType' => array('id' => 1, 'name' => 'Touch Screen Kiosk'),
	 			'Industry' => array('id' => 1, 'name' => 'Financial'),
	 			'BusinessSolution' => array(array('id' => 6, 'name' => 'Convert Sales')),
	 			'MediaType' => array(
	 				array('id' => 15, 'name' => 'Print'),
	 				array('id' => 7, 'name' => 'Web Demo'),
	 				array('id' => 6, 'name' => 'CD-ROM')
	 			)
	 		),
	 		array(
	 			'Project' => array('id' => 2, 'title' => null, 'client_id' => 2, 'show' => 1, 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => 2, 'industry_id' => 2, 'modified' => '2007-11-26 14:48:36', 'created' => null),
	 			'Style' => array('id' => null, 'name' => null),
	 			'JobType' => array('id' => 2, 'name' => 'Awareness Campaign'),
	 			'Industry' => array('id' => 2, 'name' => 'Education'),
	 			'BusinessSolution' => array(
	 				array('id' => 4, 'name' => 'Build Relationship'),
	 				array('id' => 6, 'name' => 'Convert Sales')
	 			),
	 			'MediaType' => array(
	 				array('id' => 17, 'name' => 'Web'),
	 				array('id' => 6, 'name' => 'CD-ROM')
	 			)
	 		)
	 	);

	 	$xml = new Xml($input, array('format' => 'tags', 'tags' => array(
	 		'MediaType'	=> array('value' => 'id', 'children' => false),
	 		'JobType'	=> array('children' => array()),
	 		'Industry'	=> array('children' => array('name')),
	 		'show'		=> false
	 	)));

	 	$result = $xml->toString(array('header' => false, 'cdata' => false));
	 	$expected = '<project><id>1</id><title /><client_id>1</client_id><is_spotlight /><style_id>0</style_id><job_type_id>1</job_type_id><industry_id>1</industry_id><modified /><created /><style><id /><name /></style><job_type><id>1</id><name>Touch Screen Kiosk</name></job_type><industry><name>Financial</name></industry><business_solution><id>6</id><name>Convert Sales</name></business_solution><media_type>15</media_type><media_type>7</media_type><media_type>6</media_type></project><project><id>2</id><title /><client_id>2</client_id><is_spotlight /><style_id>0</style_id><job_type_id>2</job_type_id><industry_id>2</industry_id><modified>2007-11-26 14:48:36</modified><created /><style><id /><name /></style><job_type><id>2</id><name>Awareness Campaign</name></job_type><industry><name>Education</name></industry><business_solution><id>4</id><name>Build Relationship</name></business_solution><business_solution><id>6</id><name>Convert Sales</name></business_solution><media_type>17</media_type><media_type>6</media_type></project>';
	 	$this->assertEqual($expected, $result);
	} */

	/* Broken due to a Set class issue */
	/* function testMixedArray() {
	 	$input = array('OptionGroup' => array(
	 		array('name' => 'OptA', 'id' => 12, 'OptA 1', 'OptA 2', 'OptA 3', 'OptA 4', 'OptA 5', 'OptA 6'),
	 		array('name' => 'OptB', 'id' => 12, 'OptB 1', 'OptB 2', 'OptB 3', 'OptB 4', 'OptB 5', 'OptB 6')
	 	));
	 	$expected = '<option_group><name>OptA</name><id>12</id><option_group>OptA 1</option_group><option_group>OptA 2</option_group><option_group>OptA 3</option_group><option_group>OptA 4</option_group><option_group>OptA 5</option_group><option_group>OptA 6</option_group></option_group><option_group><name>OptB</name><id>12</id><option_group>OptB 1</option_group><option_group>OptB 2</option_group><option_group>OptB 3</option_group><option_group>OptB 4</option_group><option_group>OptB 5</option_group><option_group>OptB 6</option_group></option_group>';
	 	$xml = new Xml($input, array('format' => 'tags'));
	 	$result = $xml->toString(array('header' => false, 'cdata' => false));
	 	$this->assertEqual($expected, $result);
	} */

	/* function testMixedNestedArray() {
	 	$input = array(
	 		'OptionA' =>  array(
	 			'name' => 'OptA',
	 			'id' => 12,
	 			'opt' => array('OptA 1', 'OptA 2', 'OptA 3', 'OptA 4', 'OptA 5', 'OptA 6')
	 		),
	 		'OptionB' 	=> array(
	 			'name' => 'OptB',
	 			'id' => 12,
	 			'opt' => array('OptB 1', 'OptB 2', 'OptB 3', 'OptB 4', 'OptB 5', 'OptB 6')
	 		)
	 	);
	 	$expected = '<option_a><name>OptA</name><id>12</id><opt>OptA 1</opt><opt>OptA 2</opt><opt>OptA 3</opt><opt>OptA 4</opt><opt>OptA 5</opt><opt>OptA 6</opt></option_a><option_b><name>OptB</name><id>12</id><opt>OptB 1</opt><opt>OptB 2</opt><opt>OptB 3</opt><opt>OptB 4</opt><opt>OptB 5</opt><opt>OptB 6</opt></option_b>';
	 	$xml = new Xml($input, array('format' => 'tags'));
	 	$result = $xml->toString(array('header' => false, 'cdata' => false));
	 	$this->assertEqual($expected, $result);
	} */

	/* function testMixedArrayAttributes() {
	 	$input = array('OptionGroup' => array(
	 		array(
	 			'name' => 'OptA',
	 			'id' => 12,
	 			array('opt' => 'OptA 1'),
	 			array('opt' => 'OptA 2'),
	 			array('opt' => 'OptA 3'),
	 			array('opt' => 'OptA 4'),
	 			array('opt' => 'OptA 5'),
	 			array('opt' => 'OptA 6')
	 		),
	 		array(
	 			'name' => 'OptB',
	 			'id' => 12,
	 			array('opt' => 'OptB 1'),
	 			array('opt' => 'OptB 2'),
	 			array('opt' => 'OptB 3'),
	 			array('opt' => 'OptB 4'),
	 			array('opt' => 'OptB 5'),
	 			array('opt' => 'OptB 6')
	 		)
	 	));
	 	$expected = '<option_group name="OptA" id="12"><opt>OptA 1</opt><opt>OptA 2</opt><opt>OptA 3</opt><opt>OptA 4</opt><opt>OptA 5</opt><opt>OptA 6</opt></option_group><option_group name="OptB" id="12"><opt>OptB 1</opt><opt>OptB 2</opt><opt>OptB 3</opt><opt>OptB 4</opt><opt>OptB 5</opt><opt>OptB 6</opt></option_group>';

	 	$options = array('tags' => array('option_group' => array('attributes' => array('id', 'name'))));
	 	$xml = new Xml($input, $options);
	 	$result = $xml->toString(false);

	 	$this->assertEqual($expected, $result);
	} */

	 /* Not implemented yet */
	 /* function testTagMap() {
	 	$input = array(
	 		array(
	 			'Project' => array('id' => 1, 'title' => null, 'show' => 1, 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => 1, 'industry_id' => 1, 'modified' => null, 'created' => null),
	 			'Style' => array('id' => null, 'name' => null),
	 			'JobType' => array('id' => 1, 'name' => 'Touch Screen Kiosk'),
	 			'Industry' => array('id' => 1, 'name' => 'Financial')
	 		),
	 		array(
	 			'Project' => array('id' => 2, 'title' => null, 'show' => 1, 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => 2, 'industry_id' => 2, 'modified' => '2007-11-26 14:48:36', 'created' => null),
	 			'Style' => array('id' => null, 'name' => null),
	 			'JobType' => array('id' => 2, 'name' => 'Awareness Campaign'),
	 			'Industry' => array('id' => 2, 'name' => 'Education'),
	 		)
	 	);
	 	$expected = '<project id="1"><title /><show>1</show><is_spotlight /><style_id>0</style_id><job_type_id>1</job_type_id><industry_id>1</industry_id><modified /><created /><style id=""><name /></style><jobtype id="1">Touch Screen Kiosk</jobtype><industry id="1"><name>Financial</name></industry></project><project id="2"><title /><show>1</show><is_spotlight /><style_id>0</style_id><job_type_id>2</job_type_id><industry_id>2</industry_id><modified>2007-11-26 14:48:36</modified><created /><style id=""><name /></style><jobtype id="2">Awareness Campaign</jobtype><industry id="2"><name>Education</name></industry></project>';

	 	$xml = new Xml($input, array('tags' => array(
	 		'Project'	=> array('attributes' => array('id')),
	 		'style'		=> array('attributes' => array('id')),
	 		'JobType'	=> array('name' => 'jobtype', 'attributes' => array('id'), 'value' => 'name'),
	 		'Industry'	=> array('attributes' => array('id'))
	 	)));
	 	$result = $xml->toString(array('header' => false, 'cdata' => false));
	 	$this->assertEqual($expected, $result);
	} */

/**
 * testAllCData method
 *
 * @access public
 * @return void
 */
	function testAllCData() {
		$input = array(
			array(
				'Project' => array('id' => 1, 'title' => null, 'client_id' => 1, 'show' => '1', 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => '1.89', 'industry_id' => '1.56', 'modified' => null, 'created' => null),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 1, 'name' => 'Touch Screen Kiosk'),
				'Industry' => array('id' => 1, 'name' => 'Financial')
			),
			array(
				'Project' => array('id' => 2, 'title' => null, 'client_id' => 2, 'show' => '1', 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => '2.2', 'industry_id' => 2.2, 'modified' => '2007-11-26 14:48:36', 'created' => null),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 2, 'name' => 'Awareness Campaign'),
				'Industry' => array('id' => 2, 'name' => 'Education'),
			)
		);
		$expected = '<project><id>1</id><title /><client_id>1</client_id><show>1</show><is_spotlight /><style_id>0</style_id><job_type_id>1.89</job_type_id><industry_id>1.56</industry_id><modified /><created /><style><id /><name /></style><job_type><id>1</id><name><![CDATA[Touch Screen Kiosk]]></name></job_type><industry><id>1</id><name><![CDATA[Financial]]></name></industry></project><project><id>2</id><title /><client_id>2</client_id><show>1</show><is_spotlight /><style_id>0</style_id><job_type_id>2.2</job_type_id><industry_id>2.2</industry_id><modified><![CDATA[2007-11-26 14:48:36]]></modified><created /><style><id /><name /></style><job_type><id>2</id><name><![CDATA[Awareness Campaign]]></name></job_type><industry><id>2</id><name><![CDATA[Education]]></name></industry></project>';
		$xml = new Xml($input, array('format' => 'tags'));
		$result = $xml->toString(array('header' => false, 'cdata' => true));
		$this->assertEqual($expected, $result);
	}
	/* PHP-native Unicode support pending */
	/* function testConvertEntities() {
	 	$input = array('project' => '&eacute;c&icirc;t');
	 	$xml = new Xml($input);

	 	$result = $xml->toString(array('header' => false, 'cdata' => false, 'convertEntities' => true));
	 	$expected = '<project>&#233;c&#238;t</project>';
	 	$this->assertEqual($result, $expected);
	} */

/**
 * testWhitespace method
 *
 * @access public
 * @return void
 */
	function testWhitespace() {
		$input = array(
			array(
				'Project' => array('id' => 1, 'title' => null, 'client_id' => 1, 'show' => 1, 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => 1, 'industry_id' => 1, 'modified' => null, 'created' => null),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 1, 'name' => 'Touch Screen Kiosk'),
				'Industry' => array('id' => 1, 'name' => 'Financial')
			),
			array(
				'Project' => array('id' => 2, 'title' => null, 'client_id' => 2, 'show' => 1, 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => 2, 'industry_id' => 2, 'modified' => '2007-11-26 14:48:36', 'created' => null),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 2, 'name' => 'Awareness Campaign'),
				'Industry' => array('id' => 2, 'name' => 'Education'),
			)
		);
		$expected = "\n\t<project>\n\t\t<id>\n\t\t\t1\n\t\t</id>\n\t\t<title />\n\t\t<client_id>\n\t\t\t1\n\t\t</client_id>\n\t\t<show>\n\t\t\t1\n\t\t</show>\n\t\t<is_spotlight />\n\t\t<style_id>\n\t\t\t0\n\t\t</style_id>\n\t\t<job_type_id>\n\t\t\t1\n\t\t</job_type_id>\n\t\t<industry_id>\n\t\t\t1\n\t\t</industry_id>\n\t\t<modified />\n\t\t<created />\n\t\t<style>\n\t\t\t<id />\n\t\t\t<name />\n\t\t</style>\n\t\t<job_type>\n\t\t\t<id>\n\t\t\t\t1\n\t\t\t</id>\n\t\t\t<name>\n\t\t\t\tTouch Screen Kiosk\n\t\t\t</name>\n\t\t</job_type>\n\t\t<industry>\n\t\t\t<id>\n\t\t\t\t1\n\t\t\t</id>\n\t\t\t<name>\n\t\t\t\tFinancial\n\t\t\t</name>\n\t\t</industry>\n\t</project>\n\t<project>\n\t\t<id>\n\t\t\t2\n\t\t</id>\n\t\t<title />\n\t\t<client_id>\n\t\t\t2\n\t\t</client_id>\n\t\t<show>\n\t\t\t1\n\t\t</show>\n\t\t<is_spotlight />\n\t\t<style_id>\n\t\t\t0\n\t\t</style_id>\n\t\t<job_type_id>\n\t\t\t2\n\t\t</job_type_id>\n\t\t<industry_id>\n\t\t\t2\n\t\t</industry_id>\n\t\t<modified>\n\t\t\t2007-11-26 14:48:36\n\t\t</modified>\n\t\t<created />\n\t\t<style>\n\t\t\t<id />\n\t\t\t<name />\n\t\t</style>\n\t\t<job_type>\n\t\t\t<id>\n\t\t\t\t2\n\t\t\t</id>\n\t\t\t<name>\n\t\t\t\tAwareness Campaign\n\t\t\t</name>\n\t\t</job_type>\n\t\t<industry>\n\t\t\t<id>\n\t\t\t\t2\n\t\t\t</id>\n\t\t\t<name>\n\t\t\t\tEducation\n\t\t\t</name>\n\t\t</industry>\n\t</project>\n";

		$xml = new Xml($input, array('format' => 'tags'));
		$result = $xml->toString(array('header' => false, 'cdata' => false, 'whitespace' => true));
		$this->assertEqual($expected, $result);
	}

/**
 * testSetSerialization method
 *
 * @access public
 * @return void
 */
	function testSetSerialization() {
		$input = array(
			array(
				'Project' => array('id' => 1, 'title' => null, 'client_id' => 1, 'show' => 1, 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => 1, 'industry_id' => 1, 'modified' => null, 'created' => null),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 1, 'name' => 'Touch Screen Kiosk'),
				'Industry' => array('id' => 1, 'name' => 'Financial')
			),
			array(
				'Project' => array('id' => 2, 'title' => null, 'client_id' => 2, 'show' => 1, 'is_spotlight' => null, 'style_id' => 0, 'job_type_id' => 2, 'industry_id' => 2, 'modified' => '2007-11-26 14:48:36', 'created' => null),
				'Style' => array('id' => null, 'name' => null),
				'JobType' => array('id' => 2, 'name' => 'Awareness Campaign'),
				'Industry' => array('id' => 2, 'name' => 'Education'),
			)
		);
		$expected = '<project><id>1</id><title /><client_id>1</client_id><show>1</show><is_spotlight /><style_id>0</style_id><job_type_id>1</job_type_id><industry_id>1</industry_id><modified /><created /><style><id /><name /></style><job_type><id>1</id><name>Touch Screen Kiosk</name></job_type><industry><id>1</id><name>Financial</name></industry></project><project><id>2</id><title /><client_id>2</client_id><show>1</show><is_spotlight /><style_id>0</style_id><job_type_id>2</job_type_id><industry_id>2</industry_id><modified>2007-11-26 14:48:36</modified><created /><style><id /><name /></style><job_type><id>2</id><name>Awareness Campaign</name></job_type><industry><id>2</id><name>Education</name></industry></project>';

		$xml = new Xml(Set::map($input), array('format' => 'tags'));
		$result = $xml->toString(array('header' => false, 'cdata' => false));
		$this->assertEqual($expected, $result);
	}

/**
 * ensure that normalize does not add _name_ elements that come from Set::map sometimes.
 *
 * @return void
 */
	function testNormalizeNotAdding_name_Element() {
		$input = array(
			'output' => array(
				'Vouchers' => array(
					array('Voucher' => array('id' => 1)),
					array('Voucher' => array('id' => 2)),
				),
			)
		);
		$xml = new Xml($input, array('attributes' => false, 'format' => 'tags'));
		$this->assertFalse(isset($xml->children[0]->children[0]->children[1]), 'Too many children %s');
		$this->assertEqual($xml->children[0]->children[0]->children[0]->name, 'voucher');
	}
/**
 * testSimpleParsing method
 *
 * @access public
 * @return void
 */
	function testSimpleParsing() {
		$source = '<response><hello><![CDATA[happy world]]></hello><goodbye><![CDATA[cruel world]]></goodbye></response>';
		$xml = new Xml($source);
		$result = $xml->toString();
		$this->assertEqual($source, $result);
	}

/**
 * test that elements with empty tag values do not collapse and corrupt data structures
 *
 * @access public
 * @return void
 */
	function testElementCollapsing() {
		$xmlDataThatFails = '<resultpackage>
		<result qid="46b1c46ed6208"><![CDATA[46b1c46ed3af9]]></result>
		<result qid="46b1c46ed332a"><![CDATA[]]></result>
		<result qid="46b1c46ed90e6"><![CDATA[46b1c46ed69d8]]></result>
		<result qid="46b1c46ed71a7"><![CDATA[46b1c46ed5a38]]></result>
		<result qid="46b1c46ed8146"><![CDATA[46b1c46ed98b6]]></result>
		<result qid="46b1c46ed7978"><![CDATA[]]></result>
		<result qid="46b1c46ed4a98"><![CDATA[]]></result>
		<result qid="46b1c46ed42c8"><![CDATA[]]></result>
		<result qid="46b1c46ed5268"><![CDATA[46b1c46ed8917]]></result>
		</resultpackage>';

		$Xml = new Xml();
		$Xml->load('<?xml version="1.0" encoding="UTF-8" ?>' . $xmlDataThatFails);
		$result = $Xml->toArray(false);

		$this->assertTrue(is_array($result));
		$expected = array(
			'resultpackage' => array(
				'result' => array(
					0 => array(
						'value' => '46b1c46ed3af9',
						'qid' => '46b1c46ed6208'),
					1 => array(
						'qid' => '46b1c46ed332a'),
					2 => array(
						'value' => '46b1c46ed69d8',
						'qid' => '46b1c46ed90e6'),
					3 => array(
						'value' => '46b1c46ed5a38',
						'qid' => '46b1c46ed71a7'),
					4 => array(
						'value' => '46b1c46ed98b6',
						'qid' => '46b1c46ed8146'),
					5 => array(
						'qid' => '46b1c46ed7978'),
					6 => array(
						'qid' => '46b1c46ed4a98'),
					7 => array(
						'qid' => '46b1c46ed42c8'),
					8 => array(
						'value' => '46b1c46ed8917',
						'qid' => '46b1c46ed5268'),
				)
		));
		$this->assertEqual(
			count($result['resultpackage']['result']), count($expected['resultpackage']['result']),
			'Incorrect array length %s');

		$this->assertFalse(
			isset($result['resultpackage']['result'][0][0]['qid']), 'Nested array exists, data is corrupt. %s');

		$this->assertEqual($result, $expected);
	}

/**
 * test that empty values do not casefold collapse
 *
 * @see http://code.cakephp.org/tickets/view/8
 * @return void
 */
	function testCaseFoldingWithEmptyValues() {
		$filledValue = '<method name="set_user_settings">
			<title>update user information</title>
			<user>1</user>
			<User>
				<id>1</id>
				<name>varchar(45)</name>
			</User>
		</method>';
		$xml =& new XML($filledValue);
		$expected = array(
			'Method' => array(
				'name' => 'set_user_settings',
				'title' => 'update user information',
				'user' => '1',
				'User' => array(
					'id' => 1,
					'name' => 'varchar(45)',
				),
			)
		);
		$result = $xml->toArray();
		$this->assertEqual($result, $expected);

		$emptyValue ='<method name="set_user_settings">
			<title>update user information</title>
			<user></user>
			<User>
				<id>1</id>
				<name>varchar(45)</name>
			</User>
		</method>';

		$xml =& new XML($emptyValue);
		$expected = array(
			'Method' => array(
				'name' => 'set_user_settings',
				'title' => 'update user information',
				'user' => array(),
				'User' => array(
					'id' => 1,
					'name' => 'varchar(45)',
				),
			)
		);
		$result = $xml->toArray();
		$this->assertEqual($result, $expected);
	}
/**
 * testMixedParsing method
 *
 * @access public
 * @return void
 */
	function testMixedParsing() {
		$source = '<response><body><hello><![CDATA[happy world]]></hello><![CDATA[in between]]><goodbye><![CDATA[cruel world]]></goodbye></body></response>';
		$xml = new Xml($source);
		$result = $xml->toString();
		$this->assertEqual($source, $result);
	}

/**
 * testComplexParsing method
 *
 * @access public
 * @return void
 */
	function testComplexParsing() {
		$source = '<projects><project><id>1</id><title /><client_id>1</client_id><show>1</show><is_spotlight /><style_id>0</style_id><job_type_id>1</job_type_id><industry_id>1</industry_id><modified /><created /><style><id /><name /></style><job_type><id>1</id><name>Touch Screen Kiosk</name></job_type><industry><id>1</id><name>Financial</name></industry></project><project><id>2</id><title /><client_id>2</client_id><show>1</show><is_spotlight /><style_id>0</style_id><job_type_id>2</job_type_id><industry_id>2</industry_id><modified>2007-11-26 14:48:36</modified><created /><style><id /><name /></style><job_type><id>2</id><name>Awareness Campaign</name></job_type><industry><id>2</id><name>Education</name></industry></project></projects>';
		$xml = new Xml($source);
		$result = $xml->toString(array('cdata' => false));
		$this->assertEqual($source, $result);
	}

/**
 * testNamespaceParsing method
 *
 * @access public
 * @return void
 */
	function testNamespaceParsing() {
		$source = '<a:container xmlns:a="http://example.com/a" xmlns:b="http://example.com/b" xmlns:c="http://example.com/c" xmlns:d="http://example.com/d" xmlns:e="http://example.com/e"><b:rule test=""><c:result>value</c:result></b:rule><d:rule test=""><e:result>value</e:result></d:rule></a:container>';
		$xml = new Xml($source);

		$result = $xml->toString(array('cdata' => false));
		$this->assertEqual($source, $result);

		$children = $xml->children('container');
		$this->assertEqual($children[0]->namespace, 'a');

		$children = $children[0]->children('rule');
		$this->assertEqual($children[0]->namespace, 'b');
	}

/**
 * testNamespaces method
 *
 * @access public
 * @return void
 */
	function testNamespaces() {
		$source = '<a:container xmlns:a="http://example.com/a" xmlns:b="http://example.com/b" xmlns:c="http://example.com/c" xmlns:d="http://example.com/d" xmlns:e="http://example.com/e"><b:rule test=""><c:result>value</c:result></b:rule><d:rule test=""><e:result>value</e:result></d:rule></a:container>';
		$xml = new Xml($source);

		$expects = '<a:container xmlns:a="http://example.com/a" xmlns:b="http://example.com/b" xmlns:c="http://example.com/c" xmlns:d="http://example.com/d" xmlns:e="http://example.com/e" xmlns:f="http://example.com/f"><b:rule test=""><c:result>value</c:result></b:rule><d:rule test=""><e:result>value</e:result></d:rule></a:container>';

		$_xml =& XmlManager::getInstance();
		$xml->addNamespace('f', 'http://example.com/f');
		$result = $xml->toString(array('cdata' => false));
		$this->assertEqual($expects, $result);
	}

/**
 * testEscapeCharSerialization method
 *
 * @access public
 * @return void
 */
	function testEscapeCharSerialization() {
		$xml = new Xml(array('text' => 'JavaScript & DHTML'), array('attributes' => false, 'format' => 'attributes'));

		$result = $xml->toString(false);
		$expected = '<std_class text="JavaScript &amp; DHTML" />';
		$this->assertEqual($expected, $result);
	}

/**
 * testCompleteEscapeCharSerialization method
 *
 * @access public
 * @return void
 */
	function testCompleteEscapeCharSerialization() {
		$xml = new Xml(array('text' => '<>&"\''), array('attributes' => false, 'format' => 'attributes'));

		$result = $xml->toString(false);
		$expected = '<std_class text="&lt;&gt;&amp;&quot;&#039;" />';
		$this->assertEqual($expected, $result);
	}

/**
 * testToArray method
 *
 * @access public
 * @return void
 */
	function testToArray() {
		App::import('Set');
		$string = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
		<rss version="2.0">
		<channel>
			<title>Cake PHP Google Group</title>
			<link>http://groups.google.com/group/cake-php</link>
			<description>Search this group before posting anything. There are over 20,000 posts and it&amp;#39;s very likely your question was answered before. Visit the IRC channel #cakephp at irc.freenode.net for live chat with users and developers of Cake. If you post, tell us the version of Cake, PHP, and database.</description>
			<language>en</language>
			<item>
				<title>constructng result array when using findall</title>
				<link>http://groups.google.com/group/cake-php/msg/49bc00f3bc651b4f</link>
				<description>i&#39;m using cakephp to construct a logical data model array that will be &lt;br&gt; passed to a flex app. I have the following model association: &lt;br&gt; ServiceDay-&amp;gt;(hasMany)ServiceTi me-&amp;gt;(hasMany)ServiceTimePrice. So what &lt;br&gt; the current output from my findall is something like this example: &lt;br&gt; &lt;p&gt;Array( &lt;br&gt; [0] =&amp;gt; Array(</description>
				<guid isPermaLink="true">http://groups.google.com/group/cake-php/msg/49bc00f3bc651b4f</guid>
				<author>bmil...@gmail.com(bpscrugs)</author>
				<pubDate>Fri, 28 Dec 2007 00:44:14 UT</pubDate>
			</item>
			<item>
				<title>Re: share views between actions?</title>
				<link>http://groups.google.com/group/cake-php/msg/8b350d898707dad8</link>
				<description>Then perhaps you might do us all a favour and refrain from replying to &lt;br&gt; things you do not understand. That goes especially for asinine comments. &lt;br&gt; Indeed. &lt;br&gt; To sum up: &lt;br&gt; No comment. &lt;br&gt; In my day, a simple &amp;quot;RTFM&amp;quot; would suffice. I&#39;ll keep in mind to ignore any &lt;br&gt; further responses from you. &lt;br&gt; You (and I) were referring to the *online documentation*, not other</description>
				<guid isPermaLink="true">http://groups.google.com/group/cake-php/msg/8b350d898707dad8</guid>
				<author>subtropolis.z...@gmail.com(subtropolis zijn)</author>
				<pubDate>Fri, 28 Dec 2007 00:45:01 UT</pubDate>
			</item>
		</channel>
		</rss>';
		$xml = new Xml($string);
		$result = $xml->toArray();
		$expected = array('Rss' => array(
			'version' => '2.0',
			'Channel' => array(
				'title' => 'Cake PHP Google Group',
				'link' => 'http://groups.google.com/group/cake-php',
				'description' => 'Search this group before posting anything. There are over 20,000 posts and it&#39;s very likely your question was answered before. Visit the IRC channel #cakephp at irc.freenode.net for live chat with users and developers of Cake. If you post, tell us the version of Cake, PHP, and database.',
				'language' => 'en',
				'Item' => array(
					array(
						'title' => 'constructng result array when using findall',
						'link' => 'http://groups.google.com/group/cake-php/msg/49bc00f3bc651b4f',
						'description' => "i'm using cakephp to construct a logical data model array that will be <br> passed to a flex app. I have the following model association: <br> ServiceDay-&gt;(hasMany)ServiceTi me-&gt;(hasMany)ServiceTimePrice. So what <br> the current output from my findall is something like this example: <br><p>Array( <br> [0] =&gt; Array(",
						'guid' => array('isPermaLink' => 'true', 'value' => 'http://groups.google.com/group/cake-php/msg/49bc00f3bc651b4f'),
						'author' => 'bmil...@gmail.com(bpscrugs)',
						'pubDate' => 'Fri, 28 Dec 2007 00:44:14 UT',
					),
					array(
						'title' => 'Re: share views between actions?',
						'link' => 'http://groups.google.com/group/cake-php/msg/8b350d898707dad8',
						'description' => 'Then perhaps you might do us all a favour and refrain from replying to <br> things you do not understand. That goes especially for asinine comments. <br> Indeed. <br> To sum up: <br> No comment. <br> In my day, a simple &quot;RTFM&quot; would suffice. I\'ll keep in mind to ignore any <br> further responses from you. <br> You (and I) were referring to the *online documentation*, not other',
						'guid' => array('isPermaLink' => 'true', 'value' => 'http://groups.google.com/group/cake-php/msg/8b350d898707dad8'),
						'author' => 'subtropolis.z...@gmail.com(subtropolis zijn)',
						'pubDate' => 'Fri, 28 Dec 2007 00:45:01 UT'
					)
				)
			)
		));
		$this->assertEqual($result, $expected);

		$string ='<data><post title="Title of this post" description="cool"/></data>';
		$xml = new Xml($string);
		$result = $xml->toArray();
		$expected = array('Data' => array('Post' => array('title' => 'Title of this post', 'description' => 'cool')));
		$this->assertEqual($result, $expected);

		$xml = new Xml('<example><item><title>An example of a correctly reversed XMLNode</title><desc/></item></example>');
		$result = Set::reverse($xml);
		$expected = array(
			'Example' => array(
				'Item' => array(
					'title' => 'An example of a correctly reversed XMLNode',
					'desc' => array(),
				)
			)
		);
		$this->assertIdentical($result, $expected);

		$xml = new Xml('<example><item attr="123"><titles><title>title1</title><title>title2</title></titles></item></example>');
		$result = $xml->toArray();
		$expected = array(
			'Example' => array(
				'Item' => array(
					'attr' => '123',
					'Titles' => array(
						'Title' => array('title1', 'title2')
					)
				)
			)
		);
		$this->assertIdentical($result, $expected);

		$xml = new Xml('<example attr="ex_attr"><item attr="123"><titles>list</titles>textforitems</item></example>');
		$result = $xml->toArray();
		$expected = array(
			'Example' => array(
				'attr' => 'ex_attr',
				'Item' => array(
					'attr' => '123',
					'titles' => 'list',
					'value'  => 'textforitems'
				)
			)
		);
		$this->assertIdentical($result, $expected);

		$xml = new Xml('<example attr="ex_attr"><item attr="123"><titles>list</titles>textforitems</item></example>');
		$example = $xml->child('example');
		$item = $example->child('item');
		$result = $item->toArray();

		$expected = array(
			'attr' => '123',
			'titles' => 'list',
			'value'  => 'textforitems'
		);
		$this->assertIdentical($result, $expected);

		$string = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
		<rss version="2.0">
		<channel>
			<title>Cake PHP Google Group</title>
			<link>http://groups.google.com/group/cake-php</link>
			<description>Search this group before posting anything. There are over 20,000 posts and it&amp;#39;s very likely your question was answered before. Visit the IRC channel #cakephp at irc.freenode.net for live chat with users and developers of Cake. If you post, tell us the version of Cake, PHP, and database.</description>
			<language>en</language>
			<item>
				<title>constructng result array when using findall</title>
				<link>http://groups.google.com/group/cake-php/msg/49bc00f3bc651b4f</link>
				<description>i&#39;m using cakephp to construct a logical data model array that will be &lt;br&gt; passed to a flex app. I have the following model association: &lt;br&gt; ServiceDay-&amp;gt;(hasMany)ServiceTi me-&amp;gt;(hasMany)ServiceTimePrice. So what &lt;br&gt; the current output from my findall is something like this example: &lt;br&gt; &lt;p&gt;Array( &lt;br&gt; [0] =&amp;gt; Array(</description>
				<dc:creator>cakephp</dc:creator>
				<category><![CDATA[cakephp]]></category>
				<category><![CDATA[model]]></category>
				<guid isPermaLink="true">http://groups.google.com/group/cake-php/msg/49bc00f3bc651b4f</guid>
				<author>bmil...@gmail.com(bpscrugs)</author>
				<pubDate>Fri, 28 Dec 2007 00:44:14 UT</pubDate>
			</item>
			<item>
				<title>Re: share views between actions?</title>
				<link>http://groups.google.com/group/cake-php/msg/8b350d898707dad8</link>
				<description>Then perhaps you might do us all a favour and refrain from replying to &lt;br&gt; things you do not understand. That goes especially for asinine comments. &lt;br&gt; Indeed. &lt;br&gt; To sum up: &lt;br&gt; No comment. &lt;br&gt; In my day, a simple &amp;quot;RTFM&amp;quot; would suffice. I&#39;ll keep in mind to ignore any &lt;br&gt; further responses from you. &lt;br&gt; You (and I) were referring to the *online documentation*, not other</description>
				<dc:creator>cakephp</dc:creator>
				<category><![CDATA[cakephp]]></category>
				<category><![CDATA[model]]></category>
				<guid isPermaLink="true">http://groups.google.com/group/cake-php/msg/8b350d898707dad8</guid>
				<author>subtropolis.z...@gmail.com(subtropolis zijn)</author>
				<pubDate>Fri, 28 Dec 2007 00:45:01 UT</pubDate>
			</item>
		</channel>
		</rss>';

		$xml = new Xml($string);
		$result = $xml->toArray();

		$expected = array('Rss' => array(
			'version' => '2.0',
			'Channel' => array(
				'title' => 'Cake PHP Google Group',
				'link' => 'http://groups.google.com/group/cake-php',
				'description' => 'Search this group before posting anything. There are over 20,000 posts and it&#39;s very likely your question was answered before. Visit the IRC channel #cakephp at irc.freenode.net for live chat with users and developers of Cake. If you post, tell us the version of Cake, PHP, and database.',
				'language' => 'en',
				'Item' => array(
					array(
						'title' => 'constructng result array when using findall',
						'link' => 'http://groups.google.com/group/cake-php/msg/49bc00f3bc651b4f',
						'description' => "i'm using cakephp to construct a logical data model array that will be <br> passed to a flex app. I have the following model association: <br> ServiceDay-&gt;(hasMany)ServiceTi me-&gt;(hasMany)ServiceTimePrice. So what <br> the current output from my findall is something like this example: <br><p>Array( <br> [0] =&gt; Array(",
						'creator' => 'cakephp',
						'Category' => array('cakephp', 'model'),
						'guid' => array('isPermaLink' => 'true', 'value' => 'http://groups.google.com/group/cake-php/msg/49bc00f3bc651b4f'),
						'author' => 'bmil...@gmail.com(bpscrugs)',
						'pubDate' => 'Fri, 28 Dec 2007 00:44:14 UT',
					),
					array(
						'title' => 'Re: share views between actions?',
						'link' => 'http://groups.google.com/group/cake-php/msg/8b350d898707dad8',
						'description' => 'Then perhaps you might do us all a favour and refrain from replying to <br> things you do not understand. That goes especially for asinine comments. <br> Indeed. <br> To sum up: <br> No comment. <br> In my day, a simple &quot;RTFM&quot; would suffice. I\'ll keep in mind to ignore any <br> further responses from you. <br> You (and I) were referring to the *online documentation*, not other',
						'creator' => 'cakephp',
						'Category' => array('cakephp', 'model'),
						'guid' => array('isPermaLink' => 'true', 'value' => 'http://groups.google.com/group/cake-php/msg/8b350d898707dad8'),
						'author' => 'subtropolis.z...@gmail.com(subtropolis zijn)',
						'pubDate' => 'Fri, 28 Dec 2007 00:45:01 UT'
					)
				)
			)
		));
		$this->assertEqual($result, $expected);

		$text = "<?xml version='1.0' encoding='utf-8'?>
		          <course>
		            <comps>
		              <comp>1</comp>
		              <comp>2</comp>
		              <comp>3</comp>
		              <comp>4</comp>
		            </comps>
		          </course>";
		$xml = new Xml($text);
		$result = $xml->toArray();

		$expected = array('Course' => array(
			'Comps' => array(
				'Comp' => array(
					1, 2, 3, 4
				)
			)
		));

		$this->assertEqual($result, $expected);

		$text = '<?xml version="1.0" encoding="UTF-8"?>
		<XRDS xmlns="xri://$xrds">
		<XRD xml:id="oauth" xmlns="xri://$XRD*($v*2.0)" version="2.0">
			<Type>xri://$xrds*simple</Type>
			<Expires>2008-04-13T07:34:58Z</Expires>
			<Service>
				<Type>http://oauth.net/core/1.0/endpoint/authorize</Type>
				<Type>http://oauth.net/core/1.0/parameters/auth-header</Type>
				<Type>http://oauth.net/core/1.0/parameters/uri-query</Type>
				<URI priority="10">https://ma.gnolia.com/oauth/authorize</URI>
				<URI priority="20">http://ma.gnolia.com/oauth/authorize</URI>
			</Service>
		</XRD>
		<XRD xmlns="xri://$XRD*($v*2.0)" version="2.0">
			<Type>xri://$xrds*simple</Type>
				<Service priority="10">
					<Type>http://oauth.net/discovery/1.0</Type>
					<URI>#oauth</URI>
				</Service>
		</XRD>
		</XRDS>';

		$xml = new Xml($text);
		$result = $xml->toArray();

		$expected = array('XRDS' => array(
			'xmlns' => 'xri://$xrds',
			'XRD' => array(
				array(
					'xml:id' => 'oauth',
					'xmlns' => 'xri://$XRD*($v*2.0)',
					'version' => '2.0',
					'Type' => 'xri://$xrds*simple',
					'Expires' => '2008-04-13T07:34:58Z',
					'Service' => array(
						'Type' => array(
							'http://oauth.net/core/1.0/endpoint/authorize',
							'http://oauth.net/core/1.0/parameters/auth-header',
							'http://oauth.net/core/1.0/parameters/uri-query'
						),
						'URI' => array(
							array(
								'value' => 'https://ma.gnolia.com/oauth/authorize',
								'priority' => '10',
							),
							array(
								'value' => 'http://ma.gnolia.com/oauth/authorize',
								'priority' => '20'
							)
						)
					)
				),
				array(
					'xmlns' => 'xri://$XRD*($v*2.0)',
					'version' => '2.0',
					'Type' => 'xri://$xrds*simple',
					'Service' => array(
						'priority' => '10',
						'Type' => 'http://oauth.net/discovery/1.0',
						'URI' => '#oauth'
					)
				)
			)
		));
		$this->assertEqual($result, $expected);

		$text = '<?xml version="1.0" encoding="UTF-8"?>
		<root>
			<child id="1" other="1" />
			<child id="2" other="1" />
			<child id="3" other="1" />
			<child id="4" other="1" />
			<child id="5" other="1" />
		</root>';
		$xml = new Xml($text);
		$result = $xml->toArray();
		$expected = array(
			'Root' => array(
				'Child' => array(
					array('id' => 1, 'other' => 1),
					array('id' => 2, 'other' => 1),
					array('id' => 3, 'other' => 1),
					array('id' => 4, 'other' => 1),
					array('id' => 5, 'other' => 1)
				)
			)
		);
		$this->assertEqual($result, $expected);

		$text = '<main><first label="first type node 1" /><first label="first type node 2" /><second label="second type node" /></main>';
		$xml =  new Xml($text);
		$result = $xml->toArray();
		$expected = array(
		    'Main' => array(
		        'First' => array(
		            array('label' => 'first type node 1'),
		            array('label' => 'first type node 2')
		        ),
		        'Second' => array('label'=>'second type node')
		    )
		);
		$this->assertIdentical($result,$expected);

		$text = '<main><first label="first type node 1" /><first label="first type node 2" /><second label="second type node" /><collection><fifth label="fifth type node"/><third label="third type node 1"/><third label="third type node 2"/><third label="third type node 3"/><fourth label="fourth type node"/></collection></main>';
		$xml =  new Xml($text);
		$result = $xml->toArray();
		$expected = array(
		    'Main' => array(
		        'First' => array(
		            array('label' => 'first type node 1'),
		            array('label' => 'first type node 2')
		        ),
		        'Second' => array('label'=>'second type node'),
				'Collection' => array(
					'Fifth' => array('label' => 'fifth type node'),
					'Third' => array(
						array('label' => 'third type node 1'),
						array('label' => 'third type node 2'),
						array('label' => 'third type node 3'),
					),
					'Fourth' => array('label' => 'fourth type node'),
				)
		    )
		);
		$this->assertIdentical($result,$expected);
	}

/**
 * testAppend method
 *
 * @access public
 * @return void
 */
	function testAppend() {
		$parentNode = new XmlNode('ourParentNode');
		$parentNode->append( new XmlNode('ourChildNode'));
		$first =& $parentNode->first();
		$this->assertEqual($first->name, 'ourChildNode');

		$string = 'ourChildNode';
		$parentNode = new XmlNode('ourParentNode');
		$parentNode->append($string);
		$last =& $parentNode->last();
		$this->assertEqual($last->name, 'ourChildNode');

		$this->expectError();
		$parentNode->append($parentNode);
	}

/**
 * testNamespacing method
 *
 * @access public
 * @return void
 */
	function testNamespacing() {
		$node = new Xml('<xml></xml>');
		$node->addNamespace('cake', 'http://cakephp.org');
		$this->assertEqual($node->toString(), '<xml xmlns:cake="http://cakephp.org" />');

		$this->assertTrue($node->removeNamespace('cake'));
		$this->assertEqual($node->toString(), '<xml />');


		$node = new Xml('<xml xmlns:cake="http://cakephp.org" />');
		$this->assertTrue($node->removeNamespace('cake'));
		$this->assertEqual($node->toString(), '<xml />');

		$node->addNamespace('cake', 'http://cakephp.org');
		$this->assertEqual($node->toString(), '<xml xmlns:cake="http://cakephp.org" />');
	}

/**
 * testCamelize method
 *
 * @access public
 * @return void
 */
	function testCamelize() {
		$xmlString = '<methodCall><methodName>examples.getStateName</methodName>' .
			'<params><param><value><i4>41</i4></value></param></params></methodCall>';

		$Xml = new Xml($xmlString);
		$expected = array(
			'methodCall' => array(
				'methodName' => 'examples.getStateName',
					'params' => array(
						'param' => array('value' => array('i4' => 41)))));
		$this->assertEqual($expected, $Xml->toArray(false));

		$Xml = new Xml($xmlString);
		$expected = array(
			'MethodCall' => array(
				'methodName' => 'examples.getStateName',
					'Params' => array(
						'Param' => array('Value' => array('i4' => 41)))));
		$this->assertEqual($expected, $Xml->toArray());
	}

/**
 * testNumericDataHandling method
 *
 * @access public
 * @return void
 */
	function testNumericDataHandling() {
		$data = '<xml><data>012345</data></xml>';

		$node = new Xml();
		$node->load($data);
		$node->parse();

		$result = $node->first();
		$result = $result->children("data");

		$result = $result[0]->first();
		$this->assertEqual($result->value, '012345');
	}

/**
 * test that creating an xml object does not leak memory
 *
 * @return void
 */
	function testMemoryLeakInConstructor() {
		if ($this->skipIf(!function_exists('memory_get_usage'), 'Cannot test memory leaks without memory_get_usage')) {
			return;
		}
		$data = '<?xml version="1.0" encoding="UTF-8"?><content>TEST</content>';
		$start = memory_get_usage();
		for ($i = 0; $i <= 300; $i++) {
			$test =& new XML($data);
			$test->__destruct();
			unset($test);
		}
		$end = memory_get_usage();
		$this->assertWithinMargin($start, $end, 3600, 'Memory leaked %s');
	}
}
