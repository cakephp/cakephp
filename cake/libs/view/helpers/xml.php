<?php
/* SVN FILE: $Id$ */
/**
 * XML Helper class file.
 *
 * Simplifies the output of XML documents.
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.view.helpers
 * @since			CakePHP(tm) v 1.2
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Core', array('Xml', 'Set'));

/**
 * XML Helper class for easy output of XML structures.
 *
 * XmlHelper encloses all methods needed while working with XML documents.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.view.helpers
 */
class XmlHelper extends AppHelper {

/**
 * Name of the current model
 *
 * @access public
 * @var string
 */
	var $model = null;
/**
 * Name of the current field
 *
 * @access public
 * @var string
 */
	var $field = null;
/**
 * Namespaces to be utilized by default when generating documents
 *
 * @access private
 * @var array
 */
	var $__namespaces = array();
/**
 * Default document encoding
 *
 * @access public
 * @var string
 */
	var $encoding = 'UTF-8';
/**
 * Returns an XML document header
 *
 * @param  array $attrib Header tag attributes
 * @return string XML header
 */
	function header($attrib = array()) {
		if (Configure::read('App.encoding') !== null) {
			$this->encoding = Configure::read('App.encoding');
		}

		if (is_array($attrib)) {
			$attrib = array_merge(array('version' => '1.0', 'encoding' => $this->encoding), $attrib);
		}

		return $this->output('<' . '?xml' . $this->__composeAttributes($attrib) . ' ?' . '>');
	}
/**
 * Adds a namespace to any documents generated
 *
 * @param  string  $name The namespace name
 * @param  string  $url  The namespace URI; can be empty if in the default namespace map
 * @return boolean False if no URL is specified, and the namespace does not exist
 *                 default namespace map, otherwise true
 * @deprecated
 * @see Xml::addNs()
 */
	function addNs($name, $url = null) {
		return Xml::addNamespace($name, $url);
	}
/**
 * Removes a namespace added in addNs()
 *
 * @param  string  $name The namespace name or URI
 * @deprecated
 * @see Xml::removeNs()
 */
	function removeNs($name) {
		Xml::removeGlobalNamespace($name);
	}
/**
 * Prepares the current set of namespaces for output in elem() / __composeAttributes()
 *
 * @return array The contents of $__namespaces, with all keys prefixed with 'xmlns:'
 */
	function __prepareNamespaces() {
		if (!empty($this->__namespaces)) {
			$keys = array_keys($this->__namespaces);
			$count = count($keys);
			for ($i = 0; $i < $count; $i++) {
				$keys[$i] = 'xmlns:' . $keys[$i];
			}
			return array_combine($keys, array_values($this->__namespaces));
		}
		return array();
	}
/**
 * Generates an XML element
 *
 * @param  string   $name The name of the XML element
 * @param  array    $attrib The attributes of the XML element
 * @param  mixed    $content XML element content
 * @param  boolean  $endTag Whether the end tag of the element should be printed
 * @return string XML
 */
	function elem($name, $attrib = array(), $content = null, $endTag = true) {
		$ns = null;

		if (isset($attrib['namespace'])) {
			$ns = $attrib['namespace'] . ':';
			unset($attrib['namespace']);
		}
		$out = "<{$ns}{$name}" . $this->__composeAttributes($attrib);

		if ((empty($content) && $content !== 0) && $endTag) {
			$out .= ' />';
		} else {
			$out .= '>' . $this->__composeContent($content);
			if ($endTag) {
				$out .= "</{$name}>";
			}
		}
		return $this->output($out);
	}
/**
 * Generates XML element attributes
 *
 * @param  mixed  $attributes
 * @return string Formatted XML attributes for inclusion in an XML element
 */
	function __composeAttributes($attributes = array()) {
		$out = '';
		if (is_array($attributes) && !empty($attributes)) {
			$attr = array();
			$keys = array_keys($attributes);
			$count = count($keys);
			for ($i = 0; $i < $count; $i++) {
				$attr[] = $keys[$i] . '="' . h($attributes[$keys[$i]]) . '"';
			}
			$out .= ' ' . join(' ', $attr);
		} elseif (is_string($attributes) && !empty($attributes)) {
			$out .= ' ' . $attributes;
		}
		return $out;
	}
/**
 * Generates XML content based on the type of variable or object passed
 *
 * @param  mixed  $content The content to be converted to XML
 * @return string XML
 */
	function __composeContent($content) {
		if (is_string($content)) {
			return $content;
		} elseif (is_array($content)) {
			$out = '';
			$keys = array_keys($content);
			$count = count($keys);

			for ($i = 0; $i < $count; $i++) {
				if (is_numeric($content[$keys[$i]])) {
					$out .= $this->__composeContent($content[$keys[$i]]);
				} elseif (is_array($content[$keys[$i]])) {
					$attr = $child = array();
					if (Set::countDim($content[$keys[$i]]) >= 2) {
						trigger_error(__('Dimension for XmlHelper::__composeContent is too high (>= 2). Please use an array with less dimension.', true), E_USER_WARNING);
					} else {
						$out .= $this->__composeContent($content[$keys[$i]]);
					}
				} elseif (is_string($content[$keys[$i]])) {
					$out .= $this->elem($content[$keys[$i]]);
				}
			}
			return $out;
		} elseif (is_object($content) && (is_a($content, 'XmlNode') || is_a($content, 'xmlnode'))) {
			return $content->toString();
		} elseif (is_object($content) && method_exists($content, 'toString')) {
			return $content->toString();
		} else {
			return $content;
		}
	}
/**
 * Serializes a model resultset into XML
 *
 * @param  mixed  $data The content to be converted to XML
 * @param  array  $options The data formatting options
 * @return string A copy of $data in XML format
 */
	function serialize($data, $options = array()) {
		$data = new Xml($data, array_merge(array('attributes' => false, 'format' => 'attributes'), $options));
		return $data->toString(array_merge(array('header' => false), $options));
	}
}

?>