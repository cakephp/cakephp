<?php
/**
 * XML handling for Cake.
 *
 * The methods in these classes enable the datasources that use XML to work.
 *
 * PHP 5
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

class Xml {

/**
 * Initialize SimpleXMLElement from a given XML string, file path, URL or array.
 *
 * ### Usage:
 *
 * Building XML from a string:
 *
 * `$xml = Xml::build('<example>text</example>');`
 *
 * Building XML from a file path:
 *
 * `$xml = Xml::build('/path/to/an/xml/file.xml');`
 *
 * Building from a remote URL:
 *
 * `$xml = Xml::build('http://example.com/example.xml');`
 *
 * Building from an array:
 *
 * {{{
 * 	$value = array(
 * 		'tags' => array(
 * 			'tag' => array(
 * 				array(
 * 					'id' => '1',
 * 					'name' => 'defect'
 * 				),
 * 				array(
 * 					'id' => '2',
 * 					'name' => 'enhancement'
 *				)
 * 			)
 * 		)
 * 	);
 * $xml = Xml::build($value);
 * }}}
 * 
 * When building XML from an array ensure that there is only one top level element.
 *
 * @param mixed $input XML string, a path to a file, an URL or an array
 * @return object SimpleXMLElement
 * @throws Exception
 */
	public static function build($input) {
		if (is_array($input) || is_object($input)) {
			return self::fromArray((array)$input);
		} elseif (strstr($input, "<")) {
			return new SimpleXMLElement($input);
		} elseif (file_exists($input) || strpos($input, 'http://') === 0 || strpos($input, 'https://') === 0 ) {
			return new SimpleXMLElement($input, null, true);
		} elseif (!is_string($input)) {
			throw new Exception(__('Invalid input.'));
		}
		throw new Exception(__('XML cannot be read.'));
	}

/**
 * Transform an array into a SimpleXMLElement
 *
 * Using the following data:
 * 
 * {{{
 * $value = array(
 *    'root' => array(
 *        'tag' => array(
 *            'id' => 1,
 *            'value' => 'defect',
 *            '@' => 'description'
 *         )
 *     )
 * );
 * }}}
 *
 * Calling `Xml::fromArray($value, 'tags');`  Will generate:
 *
 * `<root><tag><id>1</id><value>defect</value>description</tag></root>`
 *
 * And calling `Xml::fromArray($value, 'attribute');` Will generate:
 *
 * `<root><tag id="1" value="defect">description</tag></root>`
 *
 * @param array $input Array with data
 * @param string $format If create childs ('tags') or attributes ('attribute').
 * @return object SimpleXMLElement
 */
	public static function fromArray($input, $format = 'tags') {
		if (!is_array($input) || count($input) !== 1) {
			throw new Exception(__('Invalid input.'));
		}
		$key = key($input);
		if (is_integer($key)) {
			throw new Exception(__('The key of input must be alphanumeric'));
		}
		if (is_array($input[$key])) {
			if (array_key_exists('@', $input[$key])) {
				$simpleXml = new SimpleXMLElement('<' . '?xml version="1.0"?' . '><' . $key . '>' . $input[$key]['@'] . '</' . $key . '>');
				unset($input[$key]['@']);
			} else {
				$simpleXml = new SimpleXMLElement('<' . '?xml version="1.0"?' . '><' . $key . ' />');
			}
			self::_fromArrayRecursive($simpleXml, $input[$key], $format);
		} else {
			$simpleXml = new SimpleXMLElement('<' . '?xml version="1.0"?' . '><' . $key . '>' . $input[$key] . '</' . $key . '>');
		}
		return $simpleXml;
	}

/**
 * Recursive method to create SimpleXMLElement from array
 *
 * @param object $node Handler to SimpleXMLElement
 * @param array $array Array of data to append to the $node.
 * @param string $format Either 'attribute' or 'tags'.  This determines where nested keys go.
 * @return void
 */
	protected static function _fromArrayRecursive(&$node, &$array, $format = 'attribute') {
		if (empty($array) || !is_array($array)) {
			return;
		}
		foreach ($array as $key => $value) {
			if (is_string($key)) {
				if (!is_array($value)) {
					if (is_bool($value)) {
						$value = (int)$value;
					} elseif ($value === null) {
						$value = '';
					}
					if ($key[0] !== '@' && $format === 'tags') {
						$node->addChild($key, $value);
					} else {
						if ($key[0] === '@') {
							$key = substr($key, 1);
						}
						$node->addAttribute($key, $value);
					}
				} else {
					if ($key[0] === '@') {
						throw new Exception(__('Invalid array'));
					}
					if (array_keys($value) === range(0, count($value) - 1)) { // List
						foreach ($value as $item) {
							if (array_key_exists('@', $item)) {
								$child = $node->addChild($key, (string)$item['@']);
								unset($item['@']);
							} else {
								$child = $node->addChild($key);
							}
							self::_fromArrayRecursive($child, $item, $format);
						}
					} else { // Struct
						if (array_key_exists('@', $value)) {
							$child = $node->addChild($key, (string)$value['@']);
							unset($value['@']);
						} else {
							$child = $node->addChild($key);
						}
						self::_fromArrayRecursive($child, $value, $format);
					}
				}
			} else {
				throw new Exception(__('Invalid array'));
			}
		}
	}

/**
 * Returns this XML structure as a array.
 *
 * @param object $simpleXML SimpleXMLElement instance
 * @return array Array representation of the XML structure.
 */
	public static function toArray($simpleXML) {
		if (!($simpleXML instanceof SimpleXMLElement)) {
			throw new Exception(__('The input is not instance of SimpleXMLElement.'));
		}
		$result = array();
		$namespaces = array_merge(array('' => ''), $simpleXML->getNamespaces(true));
		self::_toArray($simpleXML, $result, '', array_keys($namespaces));
		return $result;
	}

/**
 * Recursive method to toArray
 *
 * @param object $xml SimpleXMLElement object
 * @param array $parentData Parent array with data
 * @param string $ns Namespace of current child
 * @param array $namespaces List of namespaces in XML
 * @return void
 */
	protected static function _toArray($xml, &$parentData, $ns, $namespaces) {
		$data = array();

		foreach ($namespaces as $namespace) {
			foreach ($xml->attributes($namespace, true) as $key => $value) {
				if (!empty($namespace)) {
					$key = $namespace . ':' . $key;
				}
				$data['@' . $key] = (string)$value;
			}

			foreach ($xml->children($namespace, true) as $child) {
				self::_toArray($child, $data, $namespace, $namespaces);
			}
		}

		$asString = trim((string)$xml);
		if (empty($data)) {
			$data = $asString;
		} elseif (!empty($asString)) {
			$data['@'] = $asString;
		}

		if (!empty($ns)) {
			$ns .= ':';
		}
		$name = $ns . $xml->getName();
		if (isset($parentData[$name])) {
			if (!is_array($parentData[$name]) || !isset($parentData[$name][0])) {
				$parentData[$name] = array($parentData[$name]);
			}
			$parentData[$name][] = $data;
		} else {
			$parentData[$name] = $data;
		}
	}

}