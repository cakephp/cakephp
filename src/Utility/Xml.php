<?php
declare(strict_types=1);

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
use Closure;
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
     * - `readFile` Set to true to enable file reading. This is disabled by default to prevent
     *   local filesystem access. Only enable this setting when the input is safe.
     * - `parseHuge` Enable the `LIBXML_PARSEHUGE` flag.
     *
     * If using array as input, you can pass `options` from Xml::fromArray.
     *
     * @param string|array|object $input XML string, a path to a file, a URL or an array
     * @param array $options The options to use
     * @return \SimpleXMLElement|\DOMDocument SimpleXMLElement or DOMDocument
     * @throws \Cake\Utility\Exception\XmlException
     */
    public static function build($input, array $options = [])
    {
        $defaults = [
            'return' => 'simplexml',
            'loadEntities' => false,
            'readFile' => false,
            'parseHuge' => false,
        ];
        $options += $defaults;

        if (is_array($input) || is_object($input)) {
            return static::fromArray($input, $options);
        }

        if ($options['readFile'] && file_exists($input)) {
            return static::_loadXml(file_get_contents($input), $options);
        }

        if (!is_string($input)) {
            $type = gettype($input);
            throw new XmlException("Invalid input. {$type} cannot be parsed as XML.");
        }

        if (strpos($input, '<') !== false) {
            return static::_loadXml($input, $options);
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
    protected static function _loadXml(string $input, array $options)
    {
        return static::load(
            $input,
            $options,
            function ($input, $options, $flags) {
                if ($options['return'] === 'simplexml' || $options['return'] === 'simplexmlelement') {
                    $flags |= LIBXML_NOCDATA;
                    $xml = new SimpleXMLElement($input, $flags);
                } else {
                    $xml = new DOMDocument();
                    $xml->loadXML($input, $flags);
                }

                return $xml;
            }
        );
    }

    /**
     * Parse the input html string and create either a SimpleXmlElement object or a DOMDocument.
     *
     * @param string $input The input html string to load.
     * @param array $options The options to use. See Xml::build()
     * @return \SimpleXMLElement|\DOMDocument
     * @throws \Cake\Utility\Exception\XmlException
     */
    public static function loadHtml(string $input, array $options = [])
    {
        $defaults = [
            'return' => 'simplexml',
            'loadEntities' => false,
        ];
        $options += $defaults;

        return static::load(
            $input,
            $options,
            function ($input, $options, $flags) {
                $xml = new DOMDocument();
                $xml->loadHTML($input, $flags);

                if ($options['return'] === 'simplexml' || $options['return'] === 'simplexmlelement') {
                    $xml = simplexml_import_dom($xml);
                }

                return $xml;
            }
        );
    }

    /**
     * Parse the input data and create either a SimpleXmlElement object or a DOMDocument.
     *
     * @param string $input The input to load.
     * @param array $options The options to use. See Xml::build()
     * @param \Closure $callable Closure that should return SimpleXMLElement or DOMDocument instance.
     * @return \SimpleXMLElement|\DOMDocument
     * @throws \Cake\Utility\Exception\XmlException
     */
    protected static function load(string $input, array $options, Closure $callable)
    {
        $flags = 0;
        if (!empty($options['parseHuge'])) {
            $flags |= LIBXML_PARSEHUGE;
        }

        $internalErrors = libxml_use_internal_errors(true);
        if (LIBXML_VERSION < 20900 && !$options['loadEntities']) {
            $previousDisabledEntityLoader = libxml_disable_entity_loader(true);
        } elseif ($options['loadEntities']) {
            $flags |= LIBXML_NOENT;
        }

        try {
            return $callable($input, $options, $flags);
        } catch (Exception $e) {
            throw new XmlException('Xml cannot be read. ' . $e->getMessage(), null, $e);
        } finally {
            if (isset($previousDisabledEntityLoader)) {
                libxml_disable_entity_loader($previousDisabledEntityLoader);
            }
            libxml_use_internal_errors($internalErrors);
        }
    }

    /**
     * Transform an array into a SimpleXMLElement
     *
     * ### Options
     *
     * - `format` If create children ('tags') or attributes ('attributes').
     * - `pretty` Returns formatted Xml when set to `true`. Defaults to `false`
     * - `version` Version of XML document. Default is 1.0.
     * - `encoding` Encoding of XML document. If null remove from XML header.
     *    Defaults to the application's encoding
     * - `return` If return object of SimpleXMLElement ('simplexml')
     *   or DOMDocument ('domdocument'). Default is SimpleXMLElement.
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
     * @param array|object $input Array with data or a collection instance.
     * @param array $options The options to use.
     * @return \SimpleXMLElement|\DOMDocument SimpleXMLElement or DOMDocument
     * @throws \Cake\Utility\Exception\XmlException
     */
    public static function fromArray($input, array $options = [])
    {
        if (is_object($input) && method_exists($input, 'toArray') && is_callable([$input, 'toArray'])) {
            $input = $input->toArray();
        }
        if (!is_array($input) || count($input) !== 1) {
            throw new XmlException('Invalid input.');
        }
        $key = key($input);
        if (is_int($key)) {
            throw new XmlException('The key of input must be alphanumeric');
        }

        $defaults = [
            'format' => 'tags',
            'version' => '1.0',
            'encoding' => mb_internal_encoding(),
            'return' => 'simplexml',
            'pretty' => false,
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
     * Recursive method to create children from array
     *
     * @param \DOMDocument $dom Handler to DOMDocument
     * @param \DOMDocument|\DOMElement $node Handler to DOMElement (child)
     * @param array $data Array of data to append to the $node.
     * @param string $format Either 'attributes' or 'tags'. This determines where nested keys go.
     * @return void
     * @throws \Cake\Utility\Exception\XmlException
     */
    protected static function _fromArray(DOMDocument $dom, $node, &$data, $format): void
    {
        if (empty($data) || !is_array($data)) {
            return;
        }
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                if (is_object($value) && method_exists($value, 'toArray') && is_callable([$value, 'toArray'])) {
                    $value = $value->toArray();
                }

                if (!is_array($value)) {
                    if (is_bool($value)) {
                        $value = (int)$value;
                    } elseif ($value === null) {
                        $value = '';
                    }
                    $isNamespace = strpos($key, 'xmlns:');
                    if ($isNamespace !== false) {
                        /** @psalm-suppress PossiblyUndefinedMethod */
                        $node->setAttributeNS('http://www.w3.org/2000/xmlns/', $key, (string)$value);
                        continue;
                    }
                    if ($key[0] !== '@' && $format === 'tags') {
                        if (!is_numeric($value)) {
                            // Escape special characters
                            // https://www.w3.org/TR/REC-xml/#syntax
                            // https://bugs.php.net/bug.php?id=36795
                            $child = $dom->createElement($key, '');
                            $child->appendChild(new DOMText((string)$value));
                        } else {
                            $child = $dom->createElement($key, (string)$value);
                        }
                        $node->appendChild($child);
                    } else {
                        if ($key[0] === '@') {
                            $key = substr($key, 1);
                        }
                        $attribute = $dom->createAttribute($key);
                        $attribute->appendChild($dom->createTextNode((string)$value));
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
     * Helper to _fromArray(). It will create children of arrays
     *
     * @param array $data Array with information to create children
     * @return void
     */
    protected static function _createChild(array $data): void
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
            $value = $value->toArray();
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
    public static function toArray($obj): array
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
     * @param string[] $namespaces List of namespaces in XML
     * @return void
     */
    protected static function _toArray(SimpleXMLElement $xml, array &$parentData, string $ns, array $namespaces): void
    {
        $data = [];

        foreach ($namespaces as $namespace) {
            /** @psalm-suppress PossiblyNullIterator */
            foreach ($xml->attributes($namespace, true) as $key => $value) {
                if (!empty($namespace)) {
                    $key = $namespace . ':' . $key;
                }
                $data['@' . $key] = (string)$value;
            }

            foreach ($xml->children($namespace, true) as $child) {
                /** @psalm-suppress PossiblyNullArgument */
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
