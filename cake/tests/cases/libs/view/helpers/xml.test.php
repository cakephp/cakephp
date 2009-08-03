<?php
/* SVN FILE: $Id$ */
/**
 * XmlHelperTest file
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 * @since         CakePHP(tm) v 1.2.0.4206
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
App::import('Helper', 'Xml');
/**
 * TestXml class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class TestXml extends Object {
/**
 * content property
 *
 * @var string ''
 * @access public
 */
	var $content = '';
/**
 * construct method
 *
 * @param mixed $content
 * @access private
 * @return void
 */
	function __construct($content) {
		$this->content = $content;
	}
/**
 * toString method
 *
 * @access public
 * @return void
 */
	function toString() {
		return $this->content;
	}
}
/**
 * XmlHelperTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class XmlHelperTest extends CakeTestCase {
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->Xml =& new XmlHelper();
		$this->Xml->beforeRender();
		$manager =& XmlManager::getInstance();
		$manager->namespaces = array();
	}
/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		unset($this->Xml);
	}
/**
 * testAddNamespace method
 *
 * @access public
 * @return void
 */
	function testAddNamespace() {
		$this->Xml->addNs('custom', 'http://example.com/dtd.xml');
		$manager =& XmlManager::getInstance();

		$expected = array('custom' => 'http://example.com/dtd.xml');
		$this->assertEqual($manager->namespaces, $expected);
	}
/**
 * testRemoveNamespace method
 *
 * @access public
 * @return void
 */
	function testRemoveNamespace() {
		$this->Xml->addNs('custom', 'http://example.com/dtd.xml');
		$this->Xml->addNs('custom2', 'http://example.com/dtd2.xml');
		$manager =& XmlManager::getInstance();

		$expected = array('custom' => 'http://example.com/dtd.xml', 'custom2' => 'http://example.com/dtd2.xml');
		$this->assertEqual($manager->namespaces, $expected);

		$this->Xml->removeNs('custom');
		$expected = array('custom2' => 'http://example.com/dtd2.xml');
		$this->assertEqual($manager->namespaces, $expected);
	}
/**
 * testRenderZeroElement method
 *
 * @access public
 * @return void
 */
	function testRenderZeroElement() {
		$result = $this->Xml->elem('count', null, 0);
		$expected = '<count>0</count>';
		$this->assertEqual($result, $expected);
	}
/**
 * testRenderElementWithNamespace method
 *
 * @access public
 * @return void
 */
	function testRenderElementWithNamespace() {
		$result = $this->Xml->elem('count', array('namespace' => 'myNameSpace'), 'content');
		$expected = '<myNameSpace:count>content</myNameSpace:count>';
		$this->assertEqual($result, $expected);

		$result = $this->Xml->elem('count', array('namespace' => 'myNameSpace'), 'content', false);
		$expected = '<myNameSpace:count>content';
		$this->assertEqual($result, $expected);

		$expected .= '</myNameSpace:count>';
		$result .= $this->Xml->closeElem();
		$this->assertEqual($result, $expected);
	}
	/**
 * testRenderElementWithComplexContent method
 *
 * @access public
 * @return void
 */
	function testRenderElementWithComplexContent() {
		$result = $this->Xml->elem('count', array('namespace' => 'myNameSpace'), array('contrived' => 'content'));
		$expected = '<myNameSpace:count><content /></myNameSpace:count>';
		$this->assertEqual($result, $expected);

		$result = $this->Xml->elem('count', array('namespace' => 'myNameSpace'), array('cdata' => true, 'value' => 'content'));
		$expected = '<myNameSpace:count><![CDATA[content]]></myNameSpace:count>';
		$this->assertEqual($result, $expected);
	}
/**
 * testSerialize method
 *
 * @access public
 * @return void
 */
	function testSerialize() {
		$data = array(
			'test1' => 'test with no quotes',
			'test2' => 'test with "double quotes"'
		);
		$result = $this->Xml->serialize($data);
		$expected = '<std_class test1="test with no quotes" test2="test with &quot;double quotes&quot;" />';
		$this->assertIdentical($result, $expected);

		$data = array(
			'test1' => 'test with no quotes',
			'test2' => 'test without double quotes'
		);
		$result = $this->Xml->serialize($data);
		$expected = '<std_class test1="test with no quotes" test2="test without double quotes" />';
		$this->assertIdentical($result, $expected);

		$data = array(
			'ServiceDay' => array('ServiceTime' => array('ServiceTimePrice' => array('dollar' => 1, 'cents' => '2')))
		);
		$result = $this->Xml->serialize($data);
		$expected = '<service_day><service_time><service_time_price dollar="1" cents="2" /></service_time></service_day>';
		$this->assertIdentical($result, $expected);

		$data = array(
			'ServiceDay' => array('ServiceTime' => array('ServiceTimePrice' => array('dollar' => 1, 'cents' => '2')))
		);
		$result = $this->Xml->serialize($data, array('format' => 'tags'));
		$expected = '<service_day><service_time><service_time_price><dollar>1</dollar><cents>2</cents></service_time_price></service_time></service_day>';
		$this->assertIdentical($result, $expected);
		
		$data = array(
			'Pages' => array('id' => 2, 'url' => 'http://www.url.com/rb/153/?id=bbbb&t=access')
		);
		$result = $this->Xml->serialize($data);
		$expected = '<pages id="2" url="http://www.url.com/rb/153/?id=bbbb&amp;t=access" />';
		$this->assertIdentical($result, $expected);
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
		$result = $this->Xml->serialize($data, array('format' => 'tags'));
		$expected = '<statuses><status><id>1</id></status><status><id>2</id></status></statuses>';
		$this->assertIdentical($result, $expected);

	}
/**
 * testHeader method
 *
 * @access public
 * @return void
 */
	function testHeader() {
		$expectedDefaultEncoding = Configure::read('App.encoding');
		if (empty($expectedDefaultEncoding)) {
			$expectedDefaultEncoding = 'UTF-8';
		}
		$attrib = array();
		$result = $this->Xml->header($attrib);
		$expected = '<?xml version="1.0" encoding="'.$expectedDefaultEncoding.'" ?>';
		$this->assertIdentical($result, $expected);

		$attrib = array(
			'encoding' => 'UTF-8',
			'version' => '1.1'
		);
		$result = $this->Xml->header($attrib);
		$expected = '<?xml version="1.1" encoding="UTF-8" ?>';
		$this->assertIdentical($result, $expected);

		$attrib = array(
			'encoding' => 'UTF-8',
			'version' => '1.2',
			'additional' => 'attribute'
		);
		$result = $this->Xml->header($attrib);
		$expected = '<?xml version="1.2" encoding="UTF-8" additional="attribute" ?>';
		$this->assertIdentical($result, $expected);

		$attrib = 'encoding="UTF-8" someOther="value"';
		$result = $this->Xml->header($attrib);
		$expected = '<?xml encoding="UTF-8" someOther="value" ?>';
		$this->assertIdentical($result, $expected);
	}
}
?>