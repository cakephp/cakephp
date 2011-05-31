<?php
/**
 * XML Helper class file.
 *
 * Simplifies the output of XML documents.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view.helpers
 * @since         CakePHP(tm) v 1.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', array('Xml', 'Set'));

/**
 * XML Helper class for easy output of XML structures.
 *
 * XmlHelper encloses all methods needed while working with XML documents.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.view.helpers
 * @link http://book.cakephp.org/view/1473/XML
 */
class XmlHelper extends AppHelper {

/**
 * Default document encoding
 *
 * @access public
 * @var string
 */
	var $encoding = 'UTF-8';

	var $Xml;
	var $XmlElement;
/**
 * Constructor
 *
 * @return void
 */
	function __construct() {
		parent::__construct();
		$this->Xml =& new Xml();
		$this->Xml->options(array('verifyNs' => false));
	}

/**
 * Returns an XML document header
 *
 * @param array $attrib Header tag attributes
 * @return string XML header
 * @access public
 * @link http://book.cakephp.org/view/1476/header
 */
	function header($attrib = array()) {
		if (Configure::read('App.encoding') !== null) {
			$this->encoding = Configure::read('App.encoding');
		}

		if (is_array($attrib)) {
			$attrib = array_merge(array('encoding' => $this->encoding), $attrib);
		}
		if (is_string($attrib) && strpos($attrib, 'xml') !== 0) {
			$attrib = 'xml ' . $attrib;
		}

		return $this->Xml->header($attrib);
	}

/**
 * Adds a namespace to any documents generated
 *
 * @param string $name The namespace name
 * @param string $url The namespace URI; can be empty if in the default namespace map
 * @return boolean False if no URL is specified, and the namespace does not exist
 *     default namespace map, otherwise true
 * @deprecated
 * @see Xml::addNs()
 */
	function addNs($name, $url = null) {
		return $this->Xml->addNamespace($name, $url);
	}

/**
 * Removes a namespace added in addNs()
 *
 * @param  string  $name The namespace name or URI
 * @deprecated
 * @see Xml::removeNs()
 * @access public
 */
	function removeNs($name) {
		return $this->Xml->removeGlobalNamespace($name);
	}

/**
 * Generates an XML element
 *
 * @param string $name The name of the XML element
 * @param array $attrib The attributes of the XML element
 * @param mixed $content XML element content
 * @param boolean $endTag Whether the end tag of the element should be printed
 * @return string XML
 * @access public
 * @link http://book.cakephp.org/view/1475/elem
 */
	function elem($name, $attrib = array(), $content = null, $endTag = true) {
		$namespace = null;
		if (isset($attrib['namespace'])) {
			$namespace = $attrib['namespace'];
			unset($attrib['namespace']);
		}
		$cdata = false;
		if (is_array($content) && isset($content['cdata'])) {
			$cdata = true;
			unset($content['cdata']);
		}
		if (is_array($content) && array_key_exists('value', $content)) {
			$content = $content['value'];
		}
		$children = array();
		if (is_array($content)) {
			$children = $content;
			$content = null;
		}

		$elem =& $this->Xml->createElement($name, $content, $attrib, $namespace);
		foreach ($children as $child) {
			$elem->createElement($child);
		}
		$out = $elem->toString(array('cdata' => $cdata, 'leaveOpen' => !$endTag));

		if (!$endTag) {
			$this->XmlElement =& $elem;
		}
		return $out;
	}

/**
 * Create closing tag for current element
 *
 * @return string
 * @access public
 */
	function closeElem() {
		$elem = (empty($this->XmlElement)) ? $this->Xml : $this->XmlElement;
		$name = $elem->name();
		if ($parent =& $elem->parent()) {
			$this->XmlElement =& $parent;
		}
		return '</' . $name . '>';
	}

/**
 * Serializes a model resultset into XML
 *
 * @param mixed $data The content to be converted to XML
 * @param array $options The data formatting options.  For a list of valid options, see
 *     Xml::__construct().
 * @return string A copy of $data in XML format
 * @see Xml::__construct()
 * @access public
 * @link http://book.cakephp.org/view/1474/serialize
 */
	function serialize($data, $options = array()) {
		$options += array('attributes' => false, 'format' => 'attributes');
		$data =& new Xml($data, $options);
		return $data->toString($options + array('header' => false));
	}
}
