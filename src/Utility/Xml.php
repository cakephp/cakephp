<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility;

use Cake\Utility\Exception\XmlException;
use DOMDocument;
use DOMNode;
use DOMText;
use Exception;
use SimpleXMLElement;

/**
 * XML handling for CakePHP.
 *
 * The methods in these classes enable the datasources that use XML to work.
 */
class Xml
{

    /**
     * Initialize SimpleXMLElement or DOMDocument from a given XML string, file path, URL or array.
     *
     * ### Usage:
     *
     * Building XML from a string:
     *
     * ```
     * $xml = Xml::build('<example>text</example>');
     * ```
     *
     * Building XML from string (output DOMDocument):
     *
     * ```
     * $xml = Xml::build('<example>text</example>', ['return' => 'domdocument']);
     * ```
     *
     * Building XML from a file path:
     *
     * ```
     * $xml = Xml::build('/path/to/an/xml/file.xml');
     * ```
     *
     * Building XML from a remote URL:
     *
     * ```
     * use Cake\Http\Client;
     *
     * $http = new Client();
     * $response = $http->get('http://example.com/example.xml');
     * $xml = Xml::build($response->body());
     * ```
     *
     * Building from an array:
     *
     * ```
     *  $value = [
     *      'tags' => [
     *          'tag' => [
     *              [
     *                  'id' => '1',
     *                  'name' => 'defect'
     *              ],
     *              [
     *                  'id' => '2',
     *                  'name' => 'enhancement'
     *              ]
     *          ]
     *      ]
     *  ];
     * $xml = Xml::build($value);
     * ```
     *
     * When building XML from an array ensure that there is only one top level element.
     *
     * ### Options
     *
     * - `return` Can be 'simplexml' to return object of SimpleXMLElement or 'domdocument' to return DOMDocument.
     * - `loadEntities` Defaults to false. Set to true to enable loading of `<!ENTITY` definitions. This
     *   is disabled by default for security reasons.
     * - `readFile` Set to false to disable file reading. This is important to disable when
     *   putting user data into Xml::build(). If enabled local files will be read if they exist.
     *   Defaults to true for backwards compatibility reasons.
     * - `parseHuge` Enable the `LIBXML_PARSEHUGE` flag.
     *
     * If using array as input, you can pass `options` from Xml::fromArray.
     *
     * @param string|array $input XML string, a path to a file, a URL or an array
     * @param array $options The options to use
     * @return \SimpleXMLElement|\DOMDocument SimpleXMLElement or DOMDocument
     * @throws \Cake\Utility\Exception\XmlException
     */
    public static function build($input, array $options = [])
    {
        $defaults = [
            'return' => 'simplexml',
            'loadEntities' => false,
            'readFile' => true,
            'parseHuge' => false,
        ];
        $options += $defaults;

        if (is_array($input) || is_object($input)) {
            return static::fromArray($input, $options);
        }

        if (strpos($input, '<') !== false) {
            return static::_loadXml($input, $options);
        }

        if ($options['readFile'] && file_exists($input)) {
            return static::_loadXml(file_get_contents($input), $options);
        }

        if (!is_string($input)) {
            throw new XmlException('Invalid input.');
        }

        throw new XmlException('XML cannot be read.');
    }

    /**
     * Parse the input data and create either a SimpleXmlElement object or a DOMDocument.
     *
     * @param string $input The input to load.
     * @param array $options The options to use. See Xml::build()
     * @return \SimpleXMLElement|\DOMDocument
     * @throws \Cake\Utility\Exception\XmlException
     */
    protected static function _loadXml($input, $options)
    {
        $hasDisable = function_exists('libxml_disable_entity_loader');
        $internalErrors = libxml_use_internal_errors(true);
        if ($hasDisable && !$options['loadEntities']) {
            libxml_disable_entity_loader(true);
        }
        $flags = 0;
        if (!empty($options['parseHuge'])) {
            $flags |= LIBXML_PARSEHUGE;
        }
        try {
            if ($options['return'] === 'simplexml' || $options['return'] === 'simplexmlelement') {
                $flags |= LIBXML_NOCDATA;
                $xml = new SimpleXMLElement($input, $flags);
            } else {
                $xml = new DOMDocument();
                $xml->loadXML($input, $flags);
            }

            return $xml;
        } catch (Exception $e) {
            throw new XmlException('Xml cannot be read. ' . $e->getMessage(), null, $e);
        } finally {
            if ($hasDisable && !$options['loadEntities']) {
                libxml_disable_entity_loader(false);
            }
            libxml_use_internal_errors($internalErrors);
        }
    }

    /**
     * Transform an array into a SimpleXMLElement
     *
     * ### Options
     *
     * - `format` If create childs ('tags') or attributes ('attributes').
     * - `pretty` Returns formatted Xml when set to `true`. Defaults to `false`
     * - `version` Version of XML document. Default is 1.0.
     * - `encoding` Encoding of XML document. If null remove from XML header. Default is the some of application.
     * - `return` If return object of SimpleXMLElement ('simplexml') or DOMDocument ('domdocument'). Default is SimpleXMLElement.
     *
     * Using the following data:
     *
     * ```
     * $value = [
     *    'root' => [
     *        'tag' => [
     *            'id' => 1,
     *            'value' => 'defect',
     *            '@' => 'description'
     *         ]
     *     ]
     * ];
     * ```
     *
     * Calling `Xml::fromArray($value, 'tags');` Will generate:
     *
     * `<root><tag><id>1</id><value>defect</value>description</tag></root>`
     *
     * And calling `Xml::fromArray($value, 'attributes');` Will generate:
     *
     * `<root><tag id="1" value="defect">description</tag></root>`
     *
     * @param array|\Cake\Collection\Collection $input Array with data or a collection instance.
     * @param string|array $options The options to use or a string to use as format.
     * @return \SimpleXMLElement|\DOMDocument SimpleXMLElement or DOMDocument
     * @throws \Cake\Utility\Exception\XmlException
     */
    public static function fromArray($input, $options = [])
    {
        if (is_object($input) && method_exists($input, 'toArray') && is_callable([$input, 'toArray'])) {
            $input = call_user_func([$input, 'toArray']);
        }
        if (!is_array($input) || count($input) !== 1) {
            throw new XmlException('Invalid input.');
        }
        $key = key($input);
        if (is_int($key)) {
            throw new XmlException('The key of input must be alphanumeric');
        }

        if (!is_array($options)) {
            $options = ['format' => (string)$options];
        }
        $defaults = [
            'format' => 'tags',
            'version' => '1.0',
            'encoding' => mb_internal_encoding(),
            'return' => 'simplexml',
            'pretty' => false
        ];
        $options += $defaults;

        $dom = new DOMDocument($options['version'], $options['encoding']);
        if ($options['pretty']) {
            $dom->formatOutput = true;
        }
        self::_fromArray($dom, $dom, $input, $options['format']);

        $options['return'] = strtolower($options['return']);
        if ($options['return'] === 'simplexml' || $options['return'] === 'simplexmlelement') {
            return new SimpleXMLElement($dom->saveXML());
        }

        return $dom;
    }

    /**
     * Recursive method to create childs from array
     *
     * @param \DOMDocument $dom Handler to DOMDocument
     * @param \DOMElement $node Handler to DOMElement (child)
     * @param array $data Array of data to append to the $node.
     * @param string $format Either 'attributes' or 'tags'. This determines where nested keys go.
     * @return void
     * @throws \Cake\Utility\Exception\XmlException
     */
    protected static function _fromArray($dom, $node, &$data, $format)
    {
        if (empty($data) || !is_array($data)) {
            return;
        }
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                if (is_object($value) && method_exists($value, 'toArray') && is_callable([$value, 'toArray'])) {
                    $value = call_user_func([$value, 'toArray']);
                }

                if (!is_array($value)) {
                    if (is_bool($value)) {
                        $value = (int)$value;
                    } elseif ($value === null) {
                        $value = '';
                    }
                    $isNamespace = strpos($key, 'xmlns:');
                    if ($isNamespace !== false) {
                        $node->setAttributeNS('http://www.w3.org/2000/xmlns/', $key, $value);
                        continue;
                    }
                    if ($key[0] !== '@' && $format === 'tags') {
                        if (!is_numeric($value)) {
                            // Escape special characters
                            // https://www.w3.org/TR/REC-xml/#syntax
                            // https://bugs.php.net/bug.php?id=36795
                            $child = $dom->createElement($key, '');
                            $child->appendChild(new DOMText($value));
                        } else {
                            $child = $dom->createElement($key, $value);
                        }
                        $node->appendChild($child);
                    } else {
                        if ($key[0] === '@') {
                            $key = substr($key, 1);
                        }
                        $attribute = $dom->createAttribute($key);
                        $attribute->appendChild($dom->createTextNode($value));
                        $node->appendChild($attribute);
                    }
                } else {
                    if ($key[0] === '@') {
                        throw new XmlException('Invalid array');
                    }
                    if (is_numeric(implode('', array_keys($value)))) {
// List
                        foreach ($value as $item) {
                            $itemData = compact('dom', 'node', 'key', 'format');
                            $itemData['value'] = $item;
                            static::_createChild($itemData);
                        }
                    } else {
// Struct
                        static::_createChild(compact('dom', 'node', 'key', 'value', 'format'));
                    }
                }
            } else {
                throw new XmlException('Invalid array');
            }
        }
    }

    /**
     * Helper to _fromArray(). It will create childs of arrays
     *
     * @param array $data Array with information to create childs
     * @return void
     */
    protected static function _createChild($data)
    {
        $data += [
            'dom' => null,
            'node' => null,
            'key' => null,
            'value' => null,
            'format' => null,
        ];

        $value = $data['value'];
        $dom = $data['dom'];
        $key = $data['key'];
        $format = $data['format'];
        $node = $data['node'];

        $childNS = $childValue = null;
        if (is_object($value) && method_exists($value, 'toArray') && is_callable([$value, 'toArray'])) {
            $value = call_user_func([$value, 'toArray']);
        }
        if (is_array($value)) {
            if (isset($value['@'])) {
                $childValue = (string)$value['@'];
                unset($value['@']);
            }
            if (isset($value['xmlns:'])) {
                $childNS = $value['xmlns:'];
                unset($value['xmlns:']);
            }
        } elseif (!empty($value) || $value === 0 || $value === '0') {
            $childValue = (string)$value;
        }

        $child = $dom->createElement($key);
        if ($childValue !== null) {
            $child->appendChild($dom->createTextNode($childValue));
        }
        if ($childNS) {
            $child->setAttribute('xmlns', $childNS);
        }

        static::_fromArray($dom, $child, $value, $format);
        $node->appendChild($child);
    }

    /**
     * Returns this XML structure as an array.
     *
     * @param \SimpleXMLElement|\DOMDocument|\DOMNode $obj SimpleXMLElement, DOMDocument or DOMNode instance
     * @return array Array representation of the XML structure.
     * @throws \Cake\Utility\Exception\XmlException
     */
    public static function toArray($obj)
    {
        if ($obj instanceof DOMNode) {
            $obj = simplexml_import_dom($obj);
        }
        if (!($obj instanceof SimpleXMLElement)) {
            throw new XmlException('The input is not instance of SimpleXMLElement, DOMDocument or DOMNode.');
        }
        $result = [];
        $namespaces = array_merge(['' => ''], $obj->getNamespaces(true));
        static::_toArray($obj, $result, '', array_keys($namespaces));

        return $result;
    }

    /**
     * Recursive method to toArray
     *
     * @param \SimpleXMLElement $xml SimpleXMLElement object
     * @param array $parentData Parent array with data
     * @param string $ns Namespace of current child
     * @param array $namespaces List of namespaces in XML
     * @return void
     */
    protected static function _toArray($xml, &$parentData, $ns, $namespaces)
    {
        $data = [];

        foreach ($namespaces as $namespace) {
            foreach ($xml->attributes($namespace, true) as $key => $value) {
                if (!empty($namespace)) {
                    $key = $namespace . ':' . $key;
                }
                $data['@' . $key] = (string)$value;
            }

            foreach ($xml->children($namespace, true) as $child) {
                static::_toArray($child, $data, $namespace, $namespaces);
            }
        }

        $asString = trim((string)$xml);
        if (empty($data)) {
            $data = $asString;
        } elseif (strlen($asString) > 0) {
            $data['@'] = $asString;
        }

        if (!empty($ns)) {
            $ns .= ':';
        }
        $name = $ns . $xml->getName();
        if (isset($parentData[$name])) {
            if (!is_array($parentData[$name]) || !isset($parentData[$name][0])) {
                $parentData[$name] = [$parentData[$name]];
            }
            $parentData[$name][] = $data;
        } else {
            $parentData[$name] = $data;
        }
    }
}
