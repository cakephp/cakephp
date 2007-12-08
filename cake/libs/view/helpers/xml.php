<?php
/* SVN FILE: $Id$ */
/**
 * XML Helper class file.
 *
 * Simplifies the output of XML documents.
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.view.helpers
 * @since			CakePHP(tm) v 1.2
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
uses('set');

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
 * Map of common namespace URIs
 *
 * @access private
 * @var array
 */
	var $__defaultNamespaceMap = array(
		'dc'     => 'http://purl.org/dc/elements/1.1/',            // Dublin Core
		'dct'    => 'http://purl.org/dc/terms/',                   // Dublin Core Terms
		'g'      => 'http://base.google.com/ns/1.0',               // Google Base
		'rc'     => 'http://purl.org/rss/1.0/modules/content/',    // RSS 1.0 Content Module
		'wf'     => 'http://wellformedweb.org/CommentAPI/',        // Well-Formed Web Comment API
		'fb'     => 'http://rssnamespace.org/feedburner/ext/1.0',  // FeedBurner extensions
		'lj'     => 'http://www.livejournal.org/rss/lj/1.0/',      // Live Journal
		'itunes' => 'http://www.itunes.com/dtds/podcast-1.0.dtd',  // iTunes
		'xhtml'  => 'http://www.w3.org/1999/xhtml'                 // XHTML
	);
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
		$attrib = array_merge(array('version' => '1.0', 'encoding' => $this->encoding), $attrib);
		return $this->output('<' . '?xml' . $this->__composeAttributes($attrib) . ' ?' . '>');
	}
/**
 * Adds a namespace to any documents generated
 *
 * @param  string  $name The namespace name
 * @param  string  $url  The namespace URI; can be empty if in the default namespace map
 * @return boolean False if no URL is specified, and the namespace does not exist
 *                 default namespace map, otherwise true
 */
	function addNs($name, $url = null) {
		if ($url == null && in_array($name, array_keys($this->__defaultNamespaceMap))) {
			$url = $this->__defaultNamespaceMap[$name];
		} elseif ($url == null) {
			return false;
		}

		if (!strpos($url, '://') && in_array($name, array_keys($this->__defaultNamespaceMap))) {
			$_url = $this->__defaultNamespaceMap[$name];
			$name = $url;
			$url = $_url;
		}
		$this->__namespaces[$name] = $url;
		return true;
	}
/**
 * Removes a namespace added in addNs()
 *
 * @param  string  $name The namespace name or URI
 */
	function removeNs($name) {
		if (in_array($name, array_keys($this->__namespaces))) {
			unset($this->__namespaces[$name]);
		} elseif (in_array($name, $this->__namespaces)) {
			$keys = array_keys($this->__namespaces);
			$count = count($keys);
			for ($i = 0; $i < $count; $i++) {
				if ($this->__namespaces[$keys[$i]] == $name) {
					unset($this->__namespaces[$keys[$i]]);
					return;
				}
			}
		}
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

		if (empty($content) && $endTag) {
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
				if (is_numeric($keys[$i])) {
					$out .= $this->__composeContent($content[$keys[$i]]);
				} elseif (is_array($content[$keys[$i]])) {
					$attr = $child = array();
					if (Set::countDim($content[$keys[$i]]) >= 2) {

					} else {

					}
					//$out .= $this->elem($keys[$i]
				}
			}
			return $out;
		} elseif (is_object($content) && (is_a($content, 'XMLNode') || is_a($content, 'xmlnode'))) {
			return $content->toString();
		} elseif (is_object($content) && method_exists($content, 'toString')) {
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
		if (!class_exists('XML') && !class_exists('xml')) {
			uses('xml');
		}
		$options = array_merge(array('attributes' => false, 'format' => 'xml'), $options);

		switch ($options['format']) {
			case 'xml':
			break;
			case 'attributes':
			break;
		}

		$data = new XML($data);
		return $data->compose(false);
	}
}

?>