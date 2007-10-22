<?php
/* SVN FILE: $Id$ */

/**
 * XML handling for Cake.
 *
 * The methods in these classes enable the datasources that use XML to work.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package      cake
 * @subpackage   cake.cake.libs
 * @since        CakePHP v .0.10.3.1400
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
uses('set');

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
 * @access public
 */
	var $name = null;
/**
 * Value of node
 *
 * @var string
 * @access public
 */
	var $value;
/**
 * Attributes on this node
 *
 * @var array
 * @access public
 */
	var $attributes = array();
/**
 * This node's children
 *
 * @var array
 * @access public
 */
	var $children = array();
/**
 * Reference to parent node.
 *
 * @var XMLNode
 * @access private
 */
	var $__parent = null;
/**
 * Constructor.
 *
 * @param string $name Node name
 * @param array $attributes Node attributes
 * @param mixed $value Node contents (text)
 * @param array $children Node children
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
 * Gets the XML element properties from an object.
 *
 * @param object $object Object to get properties from
 * @return array Properties from object
 * @access private
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
 * Sets the parent node of this XMLNode.
 *
 * @access public
 */
	function setParent(&$parent) {
		$this->__parent =& $parent;
	}
/**
 * Returns a copy of self.
 *
 * @return object Cloned instance
 * @access public
 */
	function cloneNode() {
		return clone($this);
	}
/**
 * Append given node as a child.
 *
 * @param object $child XMLNode with appended child
 * @access public
 */
	function &append(&$child) {
		if (is_object($child)) {
			$this->children[] =& $child;
		} elseif (is_string($child)) {
			$attr = array();
			if (func_num_args() >= 2 && is_array(func_get_arg(1))) {
				$attr = func_get_arg(1);
			}
			$tmp =& new XMLNode();
			$tmp->name = $child;
			$tmp->attributes = $attr;
		}
		return $tmp;
	}
/**
 * Returns first child node, or null if empty.
 *
 * @return object First XMLNode
 * @access public
 */
	function &first() {
		if (isset($this->children[0])) {
			return $this->children[0];
		} else {
			return null;
		}
	}
/**
 * Returns last child node, or null if empty.
 *
 * @return object Last XMLNode
 * @access public
 */
	function &last() {
		if (count($this->children) > 0) {
			return $this->children[count($this->children) - 1];
		} else {
			return null;
		}
	}
/**
 * Returns child node with given ID.
 *
 * @param string $id Name of child node
 * @return object Child XMLNode
 * @access public
 */
	function &child($id) {
		$null = null;

		if (is_int($id)) {
			if (isset($this->children[$id])) {
				return $this->children[$id];
			} else {
				return null;
			}
		} elseif (is_string($id)) {
			for ($i = 0; $i < count($this->children); $i++) {
				if ($this->children[$i]->name == $id) {
					return $this->children[$i];
				}
			}
			return $null;
		} else {
			return $null;
		}
	}
/**
 * Gets a list of childnodes with the given tag name.
 *
 * @param string $name Tag name of child nodes
 * @return array An array of XMLNodes with the given tag name
 * @access public
 */
	function children($name) {
		$nodes = array();
		$count = count($this->children);
		for ($i = 0; $i < $count; $i++) {
			if ($this->children[$i]->name == $name) {
				$nodes[] =& $this->children[$i];
			}
		}
		return $nodes;
	}
/**
 * Gets a reference to the next child node in the list of this node's parent.
 *
 * @return object A reference to the XMLNode object
 * @access public
 */
	function &nextSibling() {
		$count = count($this->__parent->children);
		for ($i = 0; $i < $count; $i++) {
			if ($this->__parent->children === $this) {
				if ($i >= $count - 1 || !isset($this->__parent->children[$i + 1])) {
					return null;
				}
				return $this->__parent->children[$i + 1];
			}
		}
	}
/**
 * Gets a reference to the previous child node in the list of this node's parent.
 *
 * @return object A reference to the XMLNode object
 * @access public
 */
	function &previousSibling() {
		$count = count($this->__parent->children);
		for ($i = 0; $i < $count; $i++) {
			if ($this->__parent->children === $this) {
				if ($i == 0 || !isset($this->__parent->children[$i - 1])) {
					return null;
				}
				return $this->__parent->children[$i - 1];
			}
		}
	}
/**
 * Returns parent node.
 *
 * @return object Parent XMLNode
 * @access public
 */
	function &parent() {
		return $this->__parent;
	}
/**
 * Returns true if this structure has child nodes.
 *
 * @return bool
 * @access public
 */
	function hasChildren() {
		if (is_array($this->children) && count($this->children) > 0) {
			return true;
		}
		return false;
	}
/**
 * Returns this XML structure as a string.
 *
 * @return string String representation of the XML structure.
 * @access public
 */
	function toString() {
		$d = '';
		if ($this->name != '') {
			$d .= '<' . $this->name;
			if (is_array($this->attributes) && count($this->attributes) > 0) {
				foreach ($this->attributes as $key => $val) {
					$d .= " $key=\"$val\"";
				}
			}
		}

		if (!$this->hasChildren() && empty($this->value)) {
			if ($this->name != '') {
				$d .= " />\n";
			}
		} else {
			if ($this->name != '') {
				$d .= ">";
			}
			if ($this->hasChildren()) {
				if (is_string($this->value) || empty($this->value)) {
					if (!empty($this->value)) {
						$d .= $this->value;
					}
					$count = count($this->children);

					for ($i = 0; $i < $count; $i++) {
						$d .= $this->children[$i]->toString();
					}
				} elseif (is_array($this->value)) {
					$count = count($this->value);
					for ($i = 0; $i < $count; $i++) {
						$d .= $this->value[$i];
						if (isset($this->children[$i])) {
							$d .= $this->children[$i]->toString();
						}
					}
					$count = count($this->children);
					if ($i < $count) {
						for ($i = $i; $i < $count; $i++) {
							$d .= $this->children[$i]->toString();
						}
					}
				}
			}

			if (is_string($this->value)) {
				$d .= $this->value;
			}

			if ($this->name != '' && ($this->hasChildren() || !empty($this->value))) {
				$d .= "</" . $this->name . ">\n";
			}
		}
		return $d;
	}
/**
 * Returns data from toString when this object is converted to a string.
 *
 * @return string String representation of this structure.
 * @access private
 */
	function __toString() {
		return $this->toString();
	}
/**
 * Debug method. Deletes the parent. Also deletes this node's children,
 * if given the $recursive parameter.
 *
 * @param bool $recursive Recursively delete elements.
 * @access private
 */
	function __killParent($recursive = true) {
		unset($this->__parent);
		if ($recursive && $this->hasChildren()) {
			for ($i = 0; $i < count($this->children); $i++) {
				$this->children[$i]->__killParent(true);
			}
		}
	}
}

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
 * @access private
 */
	var $__parser;
/**
 * File handle to XML indata file.
 *
 * @var resource
 * @access private
 */
	var $__file;
/**
 * Raw XML string data (for loading purposes)
 *
 * @var string
 * @access private
 */
	var $__rawData = null;

/**
 * XML document header
 *
 * @var string
 * @access private
 */
	var $__header = null;

/**
 * XML document version
 *
 * @var string
 * @access private
 */
	var $version = '1.0';

/**
 * XML document encoding
 *
 * @var string
 * @access private
 */
	var $encoding = 'UTF-8';

/**
 * Constructor.  Sets up the XML parser with options, gives it this object as
 * its XML object, and sets some variables.
 *
 * @param string $input What should be used to set up
 * @param array $options Options to set up with
 */
	function __construct($input = null, $options = array()) {
		parent::__construct('root');
		$this->__parser = xml_parser_create_ns();

		xml_set_object($this->__parser, $this);
		xml_parser_set_option($this->__parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($this->__parser, XML_OPTION_SKIP_WHITE, 1);

		$this->children = array();

		if ($input != null) {
			$vars = null;
			if (is_string($input)) {
				$this->load($input);
			} elseif (is_array($input)) {
				$vars = $this->__objectToNode(Set::map($input));
			} elseif (is_object($input)) {
				$vars = $this->__objectToNode($input);
			}

			if ($vars != null) {
				$this->children = $vars;
			}

			if (!is_array($this->children)) {
				$this->children = array($this->children);
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
 * @param string $in XML string to initialize with
 * @return boolean Success
 * @access public
 */
	function load($in) {
		$this->__rawData = null;
		$this->header = null;

		if (is_string($in)) {

			if (strstr($in, "<")) {
				// Input is raw xml data
				$this->__rawData = $in;
			} else {
				// Input is an xml file
				if (strpos($in, '://') || file_exists($in)) {
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
 * @return boolean Success
 * @access public
 * @see load()
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

					if (isset($data['value'])) {
						$tmpXML->value = $data['value'];
					}
					if (isset($data['attributes'])) {
						$tmpXML->attributes = $data['attributes'];
					}

					$tmpXML->setParent($xml);
					$ct = count($xml->children);
					$xml->children[$ct] = $tmpXML;
					$xml =& $xml->children[$ct];
				break;

				case "close" :
					$xml =& $xml->parent();
				break;

				case "complete" :
					$tmpXML = new XMLNode();
					$tmpXML->name = $data['tag'];

					if (isset($data['value'])) {
						$tmpXML->value = $data['value'];
					}
					if (isset($data['attributes'])) {
						$tmpXML->attributes = $data['attributes'];
					}

					$tmpXML->__parent =& $xml;
					$xml->children[] = $tmpXML;
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
		$this->children =& $xml->children;
		return true;
	}
/**
 * Returns a string representation of the XML object
 *
 * @param bool $useHeader Whether to include the XML header with the document (defaults to true)
 * @return string XML data
 * @access public
 */
	function compose($useHeader = true) {
		if (!empty($this->__header)) {
			$header =  '<'.'?'.$this->__header.' ?'.'>'."\n";
		} else {
			$header =  '<'.'?xml version="'.$this->version.'" encoding="'.$this->encoding.'" ?'.'>'."\n";
		}
		if (!$this->hasChildren() && !$useHeader) {
			return null;
		} elseif (!$this->hasChildren()) {
			return $header;
		}

		$data = '';
		foreach ($this->children as $i => $node) {
			$data .= $this->children[$i]->__toString();
		}

		if ($useHeader) {
			return $header.$data;
		}
		return $data;
	}
/**
 * If debug mode is on, this method echoes an error message.
 *
 * @param string $msg Error message
 * @param integer $code Error code
 * @param integer $line Line in file
 * @access public
 */
	function error($msg, $code = 0, $line = 0) {
		if (Configure::read('debug')) {
			echo $msg . " " . $code . " " . $line;
		}
	}
/**
 * Returns a string with a textual description of the error code, or FALSE if no description was found.
 *
 * @param integer $code Error code
 * @return string Error message
 * @access public
 */
	function getError($code) {
		$r = @xml_error_string($code);
		return $r;
	}

// Overridden functions from superclass

/**
 * Get next element. NOT implemented.
 *
 * @return object
 * @access public
 */
	function &next() {
		return null;
	}
/**
 * Get previous element. NOT implemented.
 *
 * @return object
 * @access public
 */
	function &previous() {
		return null;
	}
/**
 * Get parent element. NOT implemented.
 *
 * @return object
 * @access public
 */
	function &parent() {
		return null;
	}

/**
 * Return string representation of current object.
 *
 * @return string String representation
 * @access public
 */
	function toString() {
		return $this->compose();
	}

/**
 * Destructor, used to free resources.
 *
 * @access private
 */
	function __destruct() {
		if (is_resource($this->__parser)) {
			xml_parser_free($this->__parser);
		}
	}
}

?>