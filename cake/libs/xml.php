<?php
/**
 * XML handling for Cake.
 *
 * The methods in these classes enable the datasources that use XML to work.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP v .0.10.3.1400
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'Set');

/**
 * XML node.
 *
 * Single XML node in an XML tree.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP v .0.10.3.1400
 */
class XmlNode extends Object {

/**
 * Name of node
 *
 * @var string
 * @access public
 */
	var $name = null;

/**
 * Node namespace
 *
 * @var string
 * @access public
 */
	var $namespace = null;

/**
 * Namespaces defined for this node and all child nodes
 *
 * @var array
 * @access public
 */
	var $namespaces = array();

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
 * @var XmlNode
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
	function __construct($name = null, $value = null, $namespace = null) {
		if (strpos($name, ':') !== false) {
			list($prefix, $name) = explode(':', $name);
			if (!$namespace) {
				$namespace = $prefix;
			}
		}
		$this->name = $name;
		if ($namespace) {
			$this->namespace = $namespace;
		}

		if (is_array($value) || is_object($value)) {
			$this->normalize($value);
		} elseif (!empty($value) || $value === 0 || $value === '0') {
			$this->createTextNode($value);
		}
	}
/**
 * Adds a namespace to the current node
 *
 * @param string $prefix The namespace prefix
 * @param string $url The namespace DTD URL
 * @return void
 */
	function addNamespace($prefix, $url) {
		if ($ns = Xml::addGlobalNs($prefix, $url)) {
			$this->namespaces = array_merge($this->namespaces, $ns);
			return true;
		}
		return false;
	}

/**
 * Adds a namespace to the current node
 *
 * @param string $prefix The namespace prefix
 * @param string $url The namespace DTD URL
 * @return void
 */
	function removeNamespace($prefix) {
		if (Xml::removeGlobalNs($prefix)) {
			return true;
		}
		return false;
	}

/**
 * Creates an XmlNode object that can be appended to this document or a node in it
 *
 * @param string $name Node name
 * @param string $value Node value
 * @param string $namespace Node namespace
 * @return object XmlNode
 */
	function &createNode($name = null, $value = null, $namespace = false) {
		$node =& new XmlNode($name, $value, $namespace);
		$node->setParent($this);
		return $node;
	}

/**
 * Creates an XmlElement object that can be appended to this document or a node in it
 *
 * @param string $name Element name
 * @param string $value Element value
 * @param array $attributes Element attributes
 * @param string $namespace Node namespace
 * @return object XmlElement
 */
	function &createElement($name = null, $value = null, $attributes = array(), $namespace = false) {
		$element =& new XmlElement($name, $value, $attributes, $namespace);
		$element->setParent($this);
		return $element;
	}

/**
 * Creates an XmlTextNode object that can be appended to this document or a node in it
 *
 * @param string $value Node value
 * @return object XmlTextNode
 */
	function &createTextNode($value = null) {
		$node = new XmlTextNode($value);
		$node->setParent($this);
		return $node;
	}

/**
 * Gets the XML element properties from an object.
 *
 * @param object $object Object to get properties from
 * @return array Properties from object
 * @access public
 */
	function normalize($object, $keyName = null, $options = array()) {
		if (is_a($object, 'XmlNode')) {
			return $object;
		}
		$name = null;
		$options += array('format' => 'attributes');

		if ($keyName !== null && !is_numeric($keyName)) {
			$name = $keyName;
		} elseif (!empty($object->_name_)) {
			$name = $object->_name_;
		} elseif (isset($object->name)) {
			$name = $object->name;
		} elseif ($options['format'] == 'attributes') {
			$name = get_class($object);
		}

		$tagOpts = $this->__tagOptions($name);

		if ($tagOpts === false) {
			return;
		}

		if (isset($tagOpts['name'])) {
			$name = $tagOpts['name'];
		} elseif ($name != strtolower($name)) {
			$name = Inflector::slug(Inflector::underscore($name));
		}

		if (!empty($name)) {
			$node =& $this->createElement($name);
		} else {
			$node =& $this;
		}

		$namespace = array();
		$attributes = array();
		$children = array();
		$chldObjs = array();

		if (is_object($object)) {
			$chldObjs = get_object_vars($object);
		} elseif (is_array($object)) {
			$chldObjs = $object;
		} elseif (!empty($object) || $object === 0 || $object === '0') {
			$node->createTextNode($object);
		}
		$attr = array();

		if (isset($tagOpts['attributes'])) {
			$attr = $tagOpts['attributes'];
		}
		if (isset($tagOpts['value']) && isset($chldObjs[$tagOpts['value']])) {
			$node->createTextNode($chldObjs[$tagOpts['value']]);
			unset($chldObjs[$tagOpts['value']]);
		}

		$n = $name;
		if (isset($chldObjs['_name_'])) {
			$n = null;
			unset($chldObjs['_name_']);
		}
		$c = 0;

		foreach ($chldObjs as $key => $val) {
			if (in_array($key, $attr) && !is_object($val) && !is_array($val)) {
				$attributes[$key] = $val;
			} else {
				if (!isset($tagOpts['children']) || $tagOpts['children'] === array() || (is_array($tagOpts['children']) && in_array($key, $tagOpts['children']))) {
					if (!is_numeric($key)) {
						$n = $key;
					}
					if (is_array($val)) {
						foreach ($val as $n2 => $obj2) {
							if (is_numeric($n2)) {
								$n2 = $n;
							}
							$node->normalize($obj2, $n2, $options);
						}
					} else {
						if (is_object($val)) {

							$node->normalize($val, $n, $options);
						} elseif ($options['format'] == 'tags' && $this->__tagOptions($key) !== false) {
							$tmp =& $node->createElement($key);
							if (!empty($val) || $val === 0 || $val === '0') {
								$tmp->createTextNode($val);
							}
						} elseif ($options['format'] == 'attributes') {
							$node->addAttribute($key, $val);
						}
					}
				}
			}
			$c++;
		}
		if (!empty($name)) {
			return $node;
		}
		return $children;
	}

/**
 * Gets the tag-specific options for the given node name
 *
 * @param string $name XML tag name
 * @param string $option The specific option to query.  Omit for all options
 * @return mixed A specific option value if $option is specified, otherwise an array of all options
 * @access private
 */
	function __tagOptions($name, $option = null) {
		if (isset($this->__tags[$name])) {
			$tagOpts = $this->__tags[$name];
		} elseif (isset($this->__tags[strtolower($name)])) {
			$tagOpts = $this->__tags[strtolower($name)];
		} else {
			return null;
		}
		if ($tagOpts === false) {
			return false;
		}
		if (empty($option)) {
			return $tagOpts;
		}
		if (isset($tagOpts[$option])) {
			return $tagOpts[$option];
		}
		return null;
	}

/**
 * Returns the fully-qualified XML node name, with namespace
 *
 * @access public
 */
	function name() {
		if (!empty($this->namespace)) {
			$_this =& XmlManager::getInstance();
			if (!isset($_this->options['verifyNs']) || !$_this->options['verifyNs'] || in_array($this->namespace, array_keys($_this->namespaces))) {
				return $this->namespace . ':' . $this->name;
			}
		}
		return $this->name;
	}

/**
 * Sets the parent node of this XmlNode.
 *
 * @access public
 */
	function setParent(&$parent) {
		if (strtolower(get_class($this)) == 'xml') {
			return;
		}
		if (isset($this->__parent) && is_object($this->__parent)) {
			if ($this->__parent->compare($parent)) {
				return;
			}
			foreach ($this->__parent->children as $i => $child) {
				if ($this->compare($child)) {
					array_splice($this->__parent->children, $i, 1);
					break;
				}
			}
		}
		if ($parent == null) {
			unset($this->__parent);
		} else {
			$parent->children[] =& $this;
			$this->__parent =& $parent;
		}
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
 * Compares $node to this XmlNode object
 *
 * @param object An XmlNode or subclass instance
 * @return boolean True if the nodes match, false otherwise
 * @access public
 */
	function compare($node) {
		$keys = array(get_object_vars($this), get_object_vars($node));
		return ($keys[0] === $keys[1]);
	}

/**
 * Append given node as a child.
 *
 * @param object $child XmlNode with appended child
 * @param array $options XML generator options for objects and arrays
 * @return object A reference to the appended child node
 * @access public
 */
	function &append(&$child, $options = array()) {
		if (empty($child)) {
			$return = false;
			return $return;
		}

		if (is_object($child)) {
			if ($this->compare($child)) {
				trigger_error(__('Cannot append a node to itself.', true));
				$return = false;
				return $return;
			}
		} else if (is_array($child)) {
			$child = Set::map($child);
			if (is_array($child)) {
				if (!is_a(current($child), 'XmlNode')) {
					foreach ($child as $i => $childNode) {
						$child[$i] = $this->normalize($childNode, null, $options);
					}
				} else {
					foreach ($child as $childNode) {
						$this->append($childNode, $options);
					}
				}
				return $child;
			}
		} else {
			$attributes = array();
			if (func_num_args() >= 2) {
				$attributes = func_get_arg(1);
			}
			$child =& $this->createNode($child, null, $attributes);
		}

		$child = $this->normalize($child, null, $options);

		if (empty($child->namespace) && !empty($this->namespace)) {
			$child->namespace = $this->namespace;
		}

		if (is_a($child, 'XmlNode')) {
			$child->setParent($this);
		}

		return $child;
	}

/**
 * Returns first child node, or null if empty.
 *
 * @return object First XmlNode
 * @access public
 */
	function &first() {
		if (isset($this->children[0])) {
			return $this->children[0];
		} else {
			$return = null;
			return $return;
		}
	}

/**
 * Returns last child node, or null if empty.
 *
 * @return object Last XmlNode
 * @access public
 */
	function &last() {
		if (count($this->children) > 0) {
			return $this->children[count($this->children) - 1];
		} else {
			$return = null;
			return $return;
		}
	}

/**
 * Returns child node with given ID.
 *
 * @param string $id Name of child node
 * @return object Child XmlNode
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
		}
		return $null;
	}

/**
 * Gets a list of childnodes with the given tag name.
 *
 * @param string $name Tag name of child nodes
 * @return array An array of XmlNodes with the given tag name
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
 * @return object A reference to the XmlNode object
 * @access public
 */
	function &nextSibling() {
		$null = null;
		$count = count($this->__parent->children);
		for ($i = 0; $i < $count; $i++) {
			if ($this->__parent->children[$i] == $this) {
				if ($i >= $count - 1 || !isset($this->__parent->children[$i + 1])) {
					return $null;
				}
				return $this->__parent->children[$i + 1];
			}
		}
		return $null;
	}

/**
 * Gets a reference to the previous child node in the list of this node's parent.
 *
 * @return object A reference to the XmlNode object
 * @access public
 */
	function &previousSibling() {
		$null = null;
		$count = count($this->__parent->children);
		for ($i = 0; $i < $count; $i++) {
			if ($this->__parent->children[$i] == $this) {
				if ($i == 0 || !isset($this->__parent->children[$i - 1])) {
					return $null;
				}
				return $this->__parent->children[$i - 1];
			}
		}
		return $null;
	}

/**
 * Returns parent node.
 *
 * @return object Parent XmlNode
 * @access public
 */
	function &parent() {
		return $this->__parent;
	}

/**
 * Returns the XML document to which this node belongs
 *
 * @return object Parent XML object
 * @access public
 */
	function &document() {
		$document =& $this;
		while (true) {
			if (get_class($document) == 'Xml' || $document == null) {
				break;
			}
			$document =& $document->parent();
		}
		return $document;
	}

/**
 * Returns true if this structure has child nodes.
 *
 * @return bool
 * @access public
 */
	function hasChildren() {
		if (is_array($this->children) && !empty($this->children)) {
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
	function toString($options = array(), $depth = 0) {
		if (is_int($options)) {
			$depth = $options;
			$options = array();
		}
		$defaults = array('cdata' => true, 'whitespace' => false, 'convertEntities' => false, 'showEmpty' => true, 'leaveOpen' => false);
		$options = array_merge($defaults, Xml::options(), $options);
		$tag = !(strpos($this->name, '#') === 0);
		$d = '';

		if ($tag) {
			if ($options['whitespace']) {
				$d .= str_repeat("\t", $depth);
			}

			$d .= '<' . $this->name();
			if (!empty($this->namespaces) > 0) {
				foreach ($this->namespaces as $key => $val) {
					$val = str_replace('"', '\"', $val);
					$d .= ' xmlns:' . $key . '="' . $val . '"';
				}
			}

			$parent =& $this->parent();
			if ($parent->name === '#document' && !empty($parent->namespaces)) {
				foreach ($parent->namespaces as $key => $val) {
					$val = str_replace('"', '\"', $val);
					$d .= ' xmlns:' . $key . '="' . $val . '"';
				}
			}

			if (is_array($this->attributes) && !empty($this->attributes)) {
				foreach ($this->attributes as $key => $val) {
					if (is_bool($val) && $val === false) {
						$val = 0;
					}
					$d .= ' ' . $key . '="' . htmlspecialchars($val, ENT_QUOTES, Configure::read('App.encoding')) . '"';
				}
			}
		}

		if (!$this->hasChildren() && empty($this->value) && $this->value !== 0 && $tag) {
			if (!$options['leaveOpen']) {
				$d .= ' />';
			}
			if ($options['whitespace']) {
				$d .= "\n";
			}
		} elseif ($tag || $this->hasChildren()) {
			if ($tag) {
				$d .= '>';
			}
			if ($this->hasChildren()) {
				if ($options['whitespace']) {
					$d .= "\n";
				}
				$count = count($this->children);
				$cDepth = $depth + 1;
				for ($i = 0; $i < $count; $i++) {
					$d .= $this->children[$i]->toString($options, $cDepth);
				}
				if ($tag) {
					if ($options['whitespace'] && $tag) {
						$d .= str_repeat("\t", $depth);
					}
					if (!$options['leaveOpen']) {
						$d .= '</' . $this->name() . '>';
					}
					if ($options['whitespace']) {
						$d .= "\n";
					}
				}
			}
		}
		return $d;
	}

/**
 * Return array representation of current object.
 *
 * @param boolean $camelize true will camelize child nodes, false will not alter node names
 * @return array Array representation
 * @access public
 */
	function toArray($camelize = true) {
		$out = $this->attributes;
		$multi = null;

		foreach ($this->children as $child) {
			$key = $camelize ? Inflector::camelize($child->name) : $child->name;

			if (is_a($child, 'XmlTextNode')) {
				$out['value'] = $child->value;
				continue;
			} elseif (isset($child->children[0]) && is_a($child->children[0], 'XmlTextNode')) {
				$value = $child->children[0]->value;
				if ($child->attributes) {
					$value = array_merge(array('value' => $value), $child->attributes);
				}
				if (isset($out[$child->name]) || isset($multi[$key])) {
					if (!isset($multi[$key])) {
						$multi[$key] = array($out[$child->name]);
						unset($out[$child->name]);
					}
					$multi[$key][] = $value;
				} else {
					$out[$child->name] = $value;
				}
				continue;
			} elseif (count($child->children) === 0 && $child->value == '') {
				$value = $child->attributes;
				if (isset($out[$key]) || isset($multi[$key])) {
					if (!isset($multi[$key])) {
						$multi[$key] = array($out[$key]);
						unset($out[$key]);
					}
					$multi[$key][] = $value;
				} elseif (!empty($value)) {
					$out[$key] = $value;
				} else {
					$out[$child->name] = $value;
				}
				continue;
			} else {
				$value = $child->toArray($camelize);
			}

			if (!isset($out[$key])) {
				$out[$key] = $value;
			} else {
				if (!is_array($out[$key]) || !isset($out[$key][0])) {
					$out[$key] = array($out[$key]);
				}
				$out[$key][] = $value;
			}
		}

		if (isset($multi)) {
			$out = array_merge($out, $multi);
		}
		return $out;
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
 * @param boolean $recursive Recursively delete elements.
 * @access protected
 */
	function _killParent($recursive = true) {
		unset($this->__parent, $this->_log);
		if ($recursive && $this->hasChildren()) {
			for ($i = 0; $i < count($this->children); $i++) {
				$this->children[$i]->_killParent(true);
			}
		}
	}
}

/**
 * Main XML class.
 *
 * Parses and stores XML data, representing the root of an XML document
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP v .0.10.3.1400
 */
class Xml extends XmlNode {

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
 * Default array keys/object properties to use as tag names when converting objects or array
 * structures to XML. Set by passing $options['tags'] to this object's constructor.
 *
 * @var array
 * @access private
 */
	var $__tags = array();

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
 * ### Options
 * - 'root': The name of the root element, defaults to '#document'
 * - 'version': The XML version, defaults to '1.0'
 * - 'encoding': Document encoding, defaults to 'UTF-8'
 * - 'namespaces': An array of namespaces (as strings) used in this document
 * - 'format': Specifies the format this document converts to when parsed or
 *    rendered out as text, either 'attributes' or 'tags', defaults to 'attributes'
 * - 'tags': An array specifying any tag-specific formatting options, indexed
 *    by tag name.  See XmlNode::normalize().
 * @param mixed $input The content with which this XML document should be initialized.  Can be a
 *    string, array or object.  If a string is specified, it may be a literal XML
 *    document, or a URL or file path to read from.
 * @param array $options Options to set up with, for valid options see above:
 * @see XmlNode::normalize()
 */
	function __construct($input = null, $options = array()) {
		$defaults = array(
			'root' => '#document', 'tags' => array(), 'namespaces' => array(),
			'version' => '1.0', 'encoding' => 'UTF-8', 'format' => 'attributes'
		);
		$options = array_merge($defaults, Xml::options(), $options);

		foreach (array('version', 'encoding', 'namespaces') as $key) {
			$this->{$key} = $options[$key];
		}
		$this->__tags = $options['tags'];
		parent::__construct('#document');

		if ($options['root'] !== '#document') {
			$Root = $this->createNode($options['root']);
		} else {
			$Root =& $this;
		}

		if (!empty($input)) {
			if (is_string($input)) {
				$Root->load($input);
			} elseif (is_array($input) || is_object($input)) {
				$Root->append($input, $options);
			}
		}
	}

/**
 * Initialize XML object from a given XML string. Returns false on error.
 *
 * @param string $input XML string, a path to a file, or an HTTP resource to load
 * @return boolean Success
 * @access public
 */
	function load($input) {
		if (!is_string($input)) {
			return false;
		}
		$this->__rawData = null;
		$this->__header = null;

		if (strstr($input, "<")) {
			$this->__rawData = $input;
		} elseif (strpos($input, 'http://') === 0 || strpos($input, 'https://') === 0) {
			App::import('Core', 'HttpSocket');
			$socket = new HttpSocket();
			$this->__rawData = $socket->get($input);
		} elseif (file_exists($input)) {
			$this->__rawData = file_get_contents($input);
		} else {
			trigger_error(__('XML cannot be read', true));
			return false;
		}
		return $this->parse();
	}

/**
 * Parses and creates XML nodes from the __rawData property.
 *
 * @return boolean Success
 * @access public
 * @see Xml::load()
 * @todo figure out how to link attributes and namespaces
 */
	function parse() {
		$this->__initParser();
		$this->__rawData = trim($this->__rawData);
		$this->__header = trim(str_replace(
			array('<' . '?', '?' . '>'),
			array('', ''),
			substr($this->__rawData, 0, strpos($this->__rawData, '?' . '>'))
		));

		xml_parse_into_struct($this->__parser, $this->__rawData, $vals);
		$xml =& $this;
		$count = count($vals);

		for ($i = 0; $i < $count; $i++) {
			$data = $vals[$i];
			$data += array('tag' => null, 'value' => null, 'attributes' => array());
			switch ($data['type']) {
				case "open" :
					$xml =& $xml->createElement($data['tag'], $data['value'], $data['attributes']);
				break;
				case "close" :
					$xml =& $xml->parent();
				break;
				case "complete" :
					$xml->createElement($data['tag'], $data['value'], $data['attributes']);
				break;
				case 'cdata':
					$xml->createTextNode($data['value']);
				break;
			}
		}
		return true;
	}

/**
 * Initializes the XML parser resource
 *
 * @return void
 * @access private
 */
	function __initParser() {
		if (empty($this->__parser)) {
			$this->__parser = xml_parser_create();
			xml_set_object($this->__parser, $this);
			xml_parser_set_option($this->__parser, XML_OPTION_CASE_FOLDING, 0);
			xml_parser_set_option($this->__parser, XML_OPTION_SKIP_WHITE, 1);
		}
	}

/**
 * Returns a string representation of the XML object
 *
 * @param mixed $options If boolean: whether to include the XML header with the document
 *        (defaults to true); if an array, overrides the default XML generation options
 * @return string XML data
 * @access public
 * @deprecated
 * @see Xml::toString()
 */
	function compose($options = array()) {
		return $this->toString($options);
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
		$return = null;
		return $return;
	}

/**
 * Get previous element. NOT implemented.
 *
 * @return object
 * @access public
 */
	function &previous() {
		$return = null;
		return $return;
	}

/**
 * Get parent element. NOT implemented.
 *
 * @return object
 * @access public
 */
	function &parent() {
		$return = null;
		return $return;
	}

/**
 * Adds a namespace to the current document
 *
 * @param string $prefix The namespace prefix
 * @param string $url The namespace DTD URL
 * @return void
 */
	function addNamespace($prefix, $url) {
		if ($count = count($this->children)) {
			for ($i = 0; $i < $count; $i++) {
				$this->children[$i]->addNamespace($prefix, $url);
			}
			return true;
		}
		return parent::addNamespace($prefix, $url);
	}

/**
 * Removes a namespace to the current document
 *
 * @param string $prefix The namespace prefix
 * @return void
 */
	function removeNamespace($prefix) {
		if ($count = count($this->children)) {
			for ($i = 0; $i < $count; $i++) {
				$this->children[$i]->removeNamespace($prefix);
			}
			return true;
		}
		return parent::removeNamespace($prefix);
	}

/**
 * Return string representation of current object.
 *
 * @return string String representation
 * @access public
 */
	function toString($options = array()) {
		if (is_bool($options)) {
			$options = array('header' => $options);
		}

		$defaults = array('header' => false, 'encoding' => $this->encoding);
		$options = array_merge($defaults, Xml::options(), $options);
		$data = parent::toString($options, 0);

		if ($options['header']) {
			if (!empty($this->__header)) {
				return $this->header($this->__header)  . "\n" . $data;
			}
			return $this->header()  . "\n" . $data;
		}

		return $data;
	}

/**
 * Return a header used on the first line of the xml file
 *
 * @param  mixed  $attrib attributes of the header element
 * @return string formated header
 */
	function header($attrib = array()) {
		$header = 'xml';
		if (is_string($attrib)) {
			$header = $attrib;
		} else {

			$attrib = array_merge(array('version' => $this->version, 'encoding' => $this->encoding), $attrib);
			foreach ($attrib as $key=>$val) {
				$header .= ' ' . $key . '="' . $val . '"';
			}
		}
		return '<' . '?' . $header . ' ?' . '>';
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
		$this->_killParent(true);
	}

/**
 * Adds a namespace to any XML documents generated or parsed
 *
 * @param  string  $name The namespace name
 * @param  string  $url  The namespace URI; can be empty if in the default namespace map
 * @return boolean False if no URL is specified, and the namespace does not exist
 *                 default namespace map, otherwise true
 * @access public
 * @static
 */
	function addGlobalNs($name, $url = null) {
		$_this =& XmlManager::getInstance();
		if ($ns = Xml::resolveNamespace($name, $url)) {
			$_this->namespaces = array_merge($_this->namespaces, $ns);
			return $ns;
		}
		return false;
	}

/**
 * Resolves current namespace
 *
 * @param  string  $name
 * @param  string  $url
 * @return array
 */
	function resolveNamespace($name, $url) {
		$_this =& XmlManager::getInstance();
		if ($url == null && isset($_this->defaultNamespaceMap[$name])) {
			$url = $_this->defaultNamespaceMap[$name];
		} elseif ($url == null) {
			return false;
		}

		if (!strpos($url, '://') && isset($_this->defaultNamespaceMap[$name])) {
			$_url = $_this->defaultNamespaceMap[$name];
			$name = $url;
			$url = $_url;
		}
		return array($name => $url);
	}

/**
 * Alias to Xml::addNs
 *
 * @access public
 * @static
 */
	function addGlobalNamespace($name, $url = null) {
		return Xml::addGlobalNs($name, $url);
	}

/**
 * Removes a namespace added in addNs()
 *
 * @param  string  $name The namespace name or URI
 * @access public
 * @static
 */
	function removeGlobalNs($name) {
		$_this =& XmlManager::getInstance();
		if (isset($_this->namespaces[$name])) {
			unset($_this->namespaces[$name]);
			unset($this->namespaces[$name]);
			return true;
		} elseif (in_array($name, $_this->namespaces)) {
			$keys = array_keys($_this->namespaces);
			$count = count($keys);
			for ($i = 0; $i < $count; $i++) {
				if ($_this->namespaces[$keys[$i]] == $name) {
					unset($_this->namespaces[$keys[$i]]);
					unset($this->namespaces[$keys[$i]]);
					return true;
				}
			}
		}
		return false;
	}

/**
 * Alias to Xml::removeNs
 *
 * @access public
 * @static
 */
	function removeGlobalNamespace($name) {
		return Xml::removeGlobalNs($name);
	}

/**
 * Sets/gets global XML options
 *
 * @param array $options
 * @return array
 * @access public
 * @static
 */
	function options($options = array()) {
		$_this =& XmlManager::getInstance();
		$_this->options = array_merge($_this->options, $options);
		return $_this->options;
	}
}

/**
 * The XML Element
 *
 */
class XmlElement extends XmlNode {

/**
 * Construct an Xml element
 *
 * @param  string  $name name of the node
 * @param  string  $value value of the node
 * @param  array  $attributes
 * @param  string  $namespace
 * @return string A copy of $data in XML format
 */
	function __construct($name = null, $value = null, $attributes = array(), $namespace = false) {
		parent::__construct($name, $value, $namespace);
		$this->addAttribute($attributes);
	}

/**
 * Get all the attributes for this element
 *
 * @return array
 */
	function attributes() {
		return $this->attributes;
	}

/**
 * Add attributes to this element
 *
 * @param  string  $name name of the node
 * @param  string  $value value of the node
 * @return boolean
 */
	function addAttribute($name, $val = null) {
		if (is_object($name)) {
			$name = get_object_vars($name);
		}
		if (is_array($name)) {
			foreach ($name as $key => $val) {
				$this->addAttribute($key, $val);
			}
			return true;
		}
		if (is_numeric($name)) {
			$name = $val;
			$val = null;
		}
		if (!empty($name)) {
			if (strpos($name, 'xmlns') === 0) {
				if ($name == 'xmlns') {
					$this->namespace = $val;
				} else {
					list($pre, $prefix) = explode(':', $name);
					$this->addNamespace($prefix, $val);
					return true;
				}
			}
			$this->attributes[$name] = $val;
			return true;
		}
		return false;
	}

/**
 * Remove attributes to this element
 *
 * @param  string  $name name of the node
 * @return boolean
 */
	function removeAttribute($attr) {
		if (array_key_exists($attr, $this->attributes)) {
			unset($this->attributes[$attr]);
			return true;
		}
		return false;
	}
}

/**
 * XML text or CDATA node
 *
 * Stores XML text data according to the encoding of the parent document
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP v .1.2.6000
 */
class XmlTextNode extends XmlNode {

/**
 * Harcoded XML node name, represents this object as a text node
 *
 * @var string
 */
	var $name = '#text';

/**
 * The text/data value which this node contains
 *
 * @var string
 */
	var $value = null;

/**
 * Construct text node with the given parent object and data
 *
 * @param object $parent Parent XmlNode/XmlElement object
 * @param mixed $value Node value
 */
	function __construct($value = null) {
		$this->value = $value;
	}

/**
 * Looks for child nodes in this element
 *
 * @return boolean False - not supported
 */
	function hasChildren() {
		return false;
	}

/**
 * Append an XML node: XmlTextNode does not support this operation
 *
 * @return boolean False - not supported
 * @todo make convertEntities work without mb support, convert entities to number entities
 */
	function append() {
		return false;
	}

/**
 * Return string representation of current text node object.
 *
 * @return string String representation
 * @access public
 */
	function toString($options = array(), $depth = 0) {
		if (is_int($options)) {
			$depth = $options;
			$options = array();
		}

		$defaults = array('cdata' => true, 'whitespace' => false, 'convertEntities'	=> false);
		$options = array_merge($defaults, Xml::options(), $options);
		$val = $this->value;

		if ($options['convertEntities'] && function_exists('mb_convert_encoding')) {
			$val = mb_convert_encoding($val,'UTF-8', 'HTML-ENTITIES');
		}

		if ($options['cdata'] === true && !is_numeric($val)) {
			$val = '<![CDATA[' . $val . ']]>';
		}

		if ($options['whitespace']) {
			return str_repeat("\t", $depth) . $val . "\n";
		}
		return $val;
	}
}

/**
 * Manages application-wide namespaces and XML parsing/generation settings.
 * Private class, used exclusively within scope of XML class.
 *
 * @access private
 */
class XmlManager {

/**
 * Global XML namespaces.  Used in all XML documents processed by this application
 *
 * @var array
 * @access public
 */
	var $namespaces = array();

/**
 * Global XML document parsing/generation settings.
 *
 * @var array
 * @access public
 */
	var $options = array();

/**
 * Map of common namespace URIs
 *
 * @access private
 * @var array
 */
	var $defaultNamespaceMap = array(
		'dc'     => 'http://purl.org/dc/elements/1.1/',					// Dublin Core
		'dct'    => 'http://purl.org/dc/terms/',						// Dublin Core Terms
		'g'			=> 'http://base.google.com/ns/1.0',					// Google Base
		'rc'		=> 'http://purl.org/rss/1.0/modules/content/',		// RSS 1.0 Content Module
		'wf'		=> 'http://wellformedweb.org/CommentAPI/',			// Well-Formed Web Comment API
		'fb'		=> 'http://rssnamespace.org/feedburner/ext/1.0',	// FeedBurner extensions
		'lj'		=> 'http://www.livejournal.org/rss/lj/1.0/',		// Live Journal
		'itunes'	=> 'http://www.itunes.com/dtds/podcast-1.0.dtd',	// iTunes
		'xhtml'		=> 'http://www.w3.org/1999/xhtml',					// XHTML,
		'atom'	 	=> 'http://www.w3.org/2005/Atom'					// Atom
	);

/**
 * Returns a reference to the global XML object that manages app-wide XML settings
 *
 * @return object
 * @access public
 */
	function &getInstance() {
		static $instance = array();

		if (!$instance) {
			$instance[0] =& new XmlManager();
		}
		return $instance[0];
	}
}
?>