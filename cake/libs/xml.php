<?php
/* SVN FILE: $Id$ */

/**
 * XML handling for Cake.
 *
 * The methods in these classes enable the datasources that use XML to work.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2005, Cake Software Foundation, Inc. 
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 * 
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright (c) 2005, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.cake.libs
 * @since        CakePHP v .0.10.3.1400
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */
uses('set');

/**
 * XML handling.
 *
 * Operations on XML data.
 *
 * @package    cake
 * @subpackage cake.cake.libs
 * @since      CakePHP v .0.10.3.1400
 */
class XML extends XMLNode {

/**
 * Resource handle to XML parser.
 *
 * @var resource
 */
	var $__parser;
/**
 * File handle to XML indata file.
 *
 * @var resource
 */
	var $__file;
/**
 * Raw XML string data (for loading purposes)
 *
 * @var string
 */
	var $__rawData = null;

/**
 * XML document header
 *
 * @var string
 */
	var $__header = null;

/**
 * XML document version
 *
 * @var string
 */
	var $version = '1.0';

/**
 * XML document encoding
 *
 * @var string
 */
	var $encoding = 'UTF-8';

/**
 * Constructor.  Sets up the XML parser with options, gives it this object as
 * its XML object, and sets some variables.
 *
 * @param string $input
 */
	function __construct($input = null, $options = array()) {
		parent::__construct('root');
		$this->__parser = xml_parser_create_ns();

		xml_set_object($this->__parser, $this);
		xml_parser_set_option($this->__parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($this->__parser, XML_OPTION_SKIP_WHITE, 1);

		$this->childNodes = array();

		if($input != null) {
			$vars = null;
			if (is_string($input)) {
				$this->load($input);
			} elseif (is_array($input)) {
				$vars = $this->__objectToNode(Set::map($input));
			} elseif (is_object($input)) {
				$vars = $this->__objectToNode($input);
			}

			if ($vars != null) {
				$this->childNodes = $vars;
			}

			if (!is_array($this->childNodes)) {
				$this->childNodes = array($this->childNodes);
			}
		}

		foreach ($options as $key => $val) {
			switch ($key) {
				case 'version':
					$this->version = $val;
				break;
				case 'encoding':
					$this->encoding = $val;
				break;
			}
		}
	}

/**
 * Initialize XML object from a given XML string. Returns false on error.
 *
 * @param string $in
 * @return boolean Success
 */
	function load($in) {
		$this->__rawData = null;
		$this->header = null;

		if (is_string($in)) {

			if(strstr($in, "<")) {
				// Input is raw xml data
				$this->__rawData = $in;
			} else {
				// Input is an xml file
				if(strpos($in, '://') || file_exists($in)) {
					$this->__rawData = @file_get_contents($in);
					if ($this->__rawData == null) {
						$this->error("XML file $in is empty or could not be read (possible permissions error).");
						return false;
					}
				} else {
					$this->error("XML file $in does not exist");
					return false;
				}
			}
			return $this->parse();

		} elseif (is_object($in)) {
		
		}
	}
/**
 * Parses and creates XML nodes from the __rawData property.
 *
 * @see load()
 *
 */
	function parse() {
		$this->header = trim(r(a('<'.'?', '?'.'>'), a('', ''), substr(trim($this->__rawData), 0, strpos($this->__rawData, "\n"))));

		xml_parse_into_struct($this->__parser, $this->__rawData, $vals);
		$xml = new XMLNode();

		$count = count($vals);
		for ($i = 0; $i < $count; $i++) {
			$data = $vals[$i];
			switch($data['type']) {
				case "open" :
					$tmpXML = new XMLNode();
					$tmpXML->name = $data['tag'];

					if(isset($data['value'])) {
						$tmpXML->value = $data['value'];
					}
					if(isset($data['attributes'])) {
						$tmpXML->attributes = $data['attributes'];
					}

					$tmpXML->setParent($xml);
					$ct = count($xml->childNodes);
					$xml->childNodes[$ct] = $tmpXML;
					$xml =& $xml->childNodes[$ct];
				break;

				case "close" :
					$xml =& $xml->parentNode();
				break;

				case "complete" :
					$tmpXML = new XMLNode();
					$tmpXML->name = $data['tag'];

					if(isset($data['value'])) {
						$tmpXML->value = $data['value'];
					}
					if(isset($data['attributes'])) {
						$tmpXML->attributes = $data['attributes'];
					}

					$tmpXML->__parentNode =& $xml;
					$xml->childNodes[] = $tmpXML;
				break;
				case 'cdata':
					if (is_string($xml->value)) {
						$xml->value = a($xml->value, $data['value']);
					} else {
						$xml->value[] = $data['value'];
					}
				break;
			}
		}
		$this->childNodes =& $xml->childNodes;
		return true;
	}
/**
 * Returns a string representation of the XML object
 *
 * @param boolean $useHeader Whether to include the XML header with the document (defaults to true)
 * @return string XML data
 */
	function compose($useHeader = true) {
		if (!empty($this->__header)) {
			$header =  '<'.'?'.$this->__header.' ?'.'>'."\n";
		} else {
			$header =  '<'.'?xml version="'.$this->version.'" encoding="'.$this->encoding.'" ?'.'>'."\n";
		}
		if (!$this->hasChildNodes() && !$useHeader) {
			return null;
		} elseif (!$this->hasChildNodes()) {
			return $header;
		}

		$data = '';
		foreach ($this->childNodes as $i => $node) {
			$data .= $this->childNodes[$i]->__toString();
		}

		if ($useHeader) {
			return $header.$data;
		}
		return $data;
	}
/**
 * If DEBUG is on, this method echoes an error message.
 *
 * @param string $msg Error message
 * @param integer $code Error code
 * @param integer $line Line in file
 */
	function error($msg, $code = 0, $line = 0) {
		if(DEBUG) {
			echo $msg . " " . $code . " " . $line;
		}
	}
/**
 * Returns a string with a textual description of the error code, or FALSE if no description was found. 
 *
 * @param integer $code
 * @return string Error message
 */
	function getError($code) {
		$r = @xml_error_string($code);
		return $r;
	}

// Overridden functions from superclass

/**
 * Enter description here...
 *
 * @return unknown
 */
	function &next() {
		return null;
	}
/**
 * Enter description here...
 *
 * @return null
 */
	function &previous() {
		return null;
	}
/**
 * Enter description here...
 *
 * @return null
 */
	function &parent() {
		return null;
	}

	function toString() {
		return $this->compose();
	}

	function __destruct() {
		if (is_resource($this->__parser)) {
			xml_parser_free($this->__parser);
		}
	}
}

/**
 * XML node.
 *
 * Single XML node in an XML tree.
 *
 * @package    cake
 * @subpackage cake.cake.libs
 * @since      CakePHP v .0.10.3.1400
 */
class XMLNode extends Object {
/**
 * Name of node
 *
 * @var string
 */
	var $name = null;
/**
 * Value of node
 *
 * @var string
 */
	var $value;
/**
 * Attributes on this node
 *
 * @var array
 */
	var $attributes = array();
/**
 * This node's children
 *
 * @var array
 */
	var $childNodes = array();
/**
 * Reference to parent node.
 *
 * @var XMLNode
 */
	var $__parentNode = null;
/**
 * Constructor.
 *
 * @param string $name Node name
 * @param array  $attributes Node attributes
 * @param mixed  $value Node contents (text)
 */
	function __construct($name = null, $attributes = array(), $value = null, $children = array()) {
		$this->name = $name;
		$this->attributes = $attributes;
		$this->value = $value;

		$c = count($children);
		for ($i = 0; $i < $c; $i++) {
			if (is_a($children[$i], 'XMLNode') || is_a($children[$i], 'xmlnode')) {
				$this->append($children[$i]);
			} elseif (is_array($children[$i])) {
				$cName = '';
				$cAttr = $cChildren = array();
				list($cName, $cAttr, $cChildren) = $children[$i];
				$node = new XMLNode($name, $cAttr, $cChildren);
				$this->append($node);
				unset($node);
			} else {
				$child = $children[$i];
				$this->append($child);
				unset($child);
			}
		}
	}
/**
 * Gets the XML element properties from an object
 *
 * @param object $object
 * @return array
 */
	function __objectToNode($object) {

		if (is_array($object)) {
			$objects = array();
			foreach ($object as $obj) {
				$objects[] = $this->__objectToNode($obj);
			}
			return $objects;
		}

		if (isset($object->__identity__) && !empty($object->__identity__)) {
			$name = $object->__identity__;
		} elseif (isset($object->name) && $object->name != null) {
			$name = $object->name;
		} else {
			$name = get_class($object);
		}
		if ($name != low($name)) {
			$name = Inflector::underscore($name);
		}

		if (is_object($object)) {
			$attributes = get_object_vars($object);
		} elseif (is_array($object)) {
			$attributes = $object[$name];
			if (is_object($attributes)) {
				$attributes = get_object_vars($attributes);
			}
		}

		$children = array();
		$attr = $attributes;

		foreach ($attr as $key => $val) {
			if (is_array($val)) {
				foreach ($val as $i => $obj2) {
					$children[] = $this->__objectToNode($obj2);
					unset($attributes[$key]);
				}
			} elseif (is_object($val)) {
				$children[] = $this->__objectToNode($val);
				unset($attributes[$key]);
			}
		}
		unset($attributes['__identity__']);

		$node = new XMLNode($name, $attributes, null, $children);
		return $node;
	}
/**
 * Sets the parent node of this XMLNode
 *
 * @return XMLNode
 */
	function setParent(&$parent) {
		$this->__parentNode =& $parent;
	}
/**
 * Returns a copy of self.
 *
 * @return XMLNode
 */
	function cloneNode() {
		return $this;
	}
/**
 * Append given node as a child.
 *
 * @param XMLNode $child
 */
	function &append(&$child) {
		if (is_object($child)) {
			$this->childNodes[] =& $child;
		} elseif (is_string($child)) {
			$attr = array();
			if (func_num_args() >= 2 && is_array(func_get_arg(1))) {
				$attr = func_get_arg(1);
			}
			$tmp = new XMLNode();
			$tmp->name = $child;
			$tmp->attributes = $attr;
		}
		return $tmp;
	}
/**
 * Returns first child node, or null if empty.
 *
 * @return XMLNode
 */
	function &first() {
		if(isset($this->childNodes[0])) {
			return $this->childNodes[0];
		} else {
			return null;
		}
	}
/**
 * Returns last child node, or null if empty.
 *
 * @return XMLNode
 */
	function &last() {
		if(count($this->childNodes) > 0) {
			return $this->childNodes[count($this->childNodes) - 1];
		} else {
			return null;
		}
	}
/**
 * Returns child node with given ID.
 *
 * @param string $id Name of childnode
 * @return XMLNode
 *
 */
	function &child($id) {
		if(is_int($id)) {
			if(isset($this->childNodes[$id])) {
				return $this->childNodes[$id];
			} else {
				return null;
			}
		} elseif(is_string($id)) {
			for($i = 0; $i < count($this->childNodes); $i++) {
				if($this->childNodes[$i]->name == $id) {
					return $this->childNodes[$i];
				}
			}
			return null;
		} else {
			return null;
		}
	}
/**
 * Gets a list of childnodes with the given tag name.
 *
 * @param string $name Tag name of child nodes
 * @return array An array of XMLNodes with the given tag name
 */
	function children($name) {
		$nodes = array();
		$count = count($this->childNodes);
		for($i = 0; $i < $count; $i++) {
			if($this->childNodes[$i]->name == $name) {
				$nodes[] =& $this->childNodes[$i];
			}
		}
		return $nodes;
	}
/**
 * Gets a reference to the next child node in the list of this node's parent
 *
 * @return XMLNode A reference to the XMLNode object
 */
	function &nextSibling() {
		$count = count($this->__parentNode->childNodes);
		for ($i = 0; $i < $count; $i++) {
			if ($this->__parentNode->childNodes === $this) {
				if ($i >= $count - 1 || !isset($this->__parentNode->childNodes[$i + 1])) {
					return null;
				}
				return $this->__parentNode->childNodes[$i + 1];
			}
		}
	}
/**
 * Gets a reference to the previous child node in the list of this node's parent
 *
 * @return XMLNode A reference to the XMLNode object
 */
	function &previousSibling() {
		$count = count($this->__parentNode->childNodes);
		for ($i = 0; $i < $count; $i++) {
			if ($this->__parentNode->childNodes === $this) {
				if ($i == 0 || !isset($this->__parentNode->childNodes[$i - 1])) {
					return null;
				}
				return $this->__parentNode->childNodes[$i - 1];
			}
		}
	}
/**
 * Returns parent node.
 *
 * @return XMLNode
 */
	function &parent() {
		return $this->__parentNode;
	}
/**
 * Returns true if this structure has child nodes.
 *
 * @return boolean
 */
	function hasChildren() {
		if(is_array($this->childNodes) && count($this->childNodes) > 0) {
			return true;
		}
		return false;
	}
/**
 * Returns this XML structure as a string.
 *
 * @return string String representation of the XML structure.
 */
	function toString() {
		$d = '';
		if($this->name != '') {
			$d .= '<' . $this->name;
			if(is_array($this->attributes) && count($this->attributes) > 0) {
				foreach($this->attributes as $key => $val) {
					$d .= " $key=\"$val\"";
				}
			}
		}

		if(!$this->hasChildNodes() && empty($this->value)) {
			if($this->name != '') {
				$d .= " />\n";
			}
		} else {
			if($this->name != '') {
				$d .= ">";
			}
			if($this->hasChildNodes()) {
				if (is_string($this->value) || empty($this->value)) {
					if (!empty($this->value)) {
						$d .= $this->value;
					}
					$count = count($this->childNodes);

					for($i = 0; $i < $count; $i++) {
						$d .= $this->childNodes[$i]->toString();
					}
				} elseif (is_array($this->value)) {
					$count = count($this->value);
					for($i = 0; $i < $count; $i++) {
						$d .= $this->value[$i];
						if (isset($this->childNodes[$i])) {
							$d .= $this->childNodes[$i]->toString();
						}
					}
					$count = count($this->childNodes);
					if ($i < $count) {
						for ($i = $i; $i < $count; $i++) {
							$d .= $this->childNodes[$i]->toString();
						}
					}
				}
			}

			if (is_string($this->value)) {
				$d .= $this->value;
			}

			if($this->name != '' && ($this->hasChildNodes() || !empty($this->value))) {
				$d .= "</" . $this->name . ">\n";
			}
		}
		return $d;
	}
/**
 * Returns data from toString when this object is converted to a string.
 *
 * @return string String representation of this structure.
 */
	function __toString() {
		return $this->toString();
	}
/**
 * Debug method. Deletes the parentNode. Also deletes this node's children,
 * if given the $recursive parameter.
 *
 * @param boolean $recursive
 */
	function __killParent($recursive = true) {
		unset($this->__parentNode);
		if($recursive && $this->hasChildNodes()) {
			for($i = 0; $i < count($this->childNodes); $i++) {
				$this->childNodes[$i]->__killParent(true);
			}
		}
	}
}

?>