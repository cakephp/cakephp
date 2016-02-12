<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Utility\Xml;
use Cake\View\Helper;

/**
 * RSS Helper class for easy output RSS structures.
 *
 * @property UrlHelper $Url
 * @property TimeHelper $Time
 * @link http://book.cakephp.org/3.0/en/views/helpers/rss.html
 */
class RssHelper extends Helper
{

    /**
     * Helpers used by RSS Helper
     *
     * @var array
     */
    public $helpers = ['Url', 'Time'];

    /**
     * Base URL
     *
     * @var string
     */
    public $base = null;

    /**
     * URL to current action.
     *
     * @var string
     */
    public $here = null;

    /**
     * Parameter array.
     *
     * @var array
     */
    public $params = [];

    /**
     * Current action.
     *
     * @var string
     */
    public $action = null;

    /**
     * POSTed model data
     *
     * @var array
     */
    public $data = null;

    /**
     * Name of the current model
     *
     * @var string
     */
    public $model = null;

    /**
     * Name of the current field
     *
     * @var string
     */
    public $field = null;

    /**
     * Default spec version of generated RSS
     *
     * @var string
     */
    public $version = '2.0';

    /**
     * Returns an RSS document wrapped in `<rss />` tags
     *
     * @param array $attrib `<rss />` tag attributes
     * @param string|null $content Tag content.
     * @return string An RSS document
     */
    public function document($attrib = [], $content = null)
    {
        if ($content === null) {
            $content = $attrib;
            $attrib = [];
        }
        if (!isset($attrib['version']) || empty($attrib['version'])) {
            $attrib['version'] = $this->version;
        }

        return $this->elem('rss', $attrib, $content);
    }

    /**
     * Returns an RSS `<channel />` element
     *
     * @param array $attrib `<channel />` tag attributes
     * @param array $elements Named array elements which are converted to tags
     * @param string|null $content Content (`<item />`'s belonging to this channel
     * @return string An RSS `<channel />`
     */
    public function channel($attrib = [], $elements = [], $content = null)
    {
        if (!isset($elements['link'])) {
            $elements['link'] = '/';
        }
        if (!isset($elements['title'])) {
            $elements['title'] = '';
        }
        if (!isset($elements['description'])) {
            $elements['description'] = '';
        }
        $elements['link'] = $this->Url->build($elements['link'], true);

        $elems = '';
        foreach ($elements as $elem => $data) {
            $attributes = [];
            if (is_array($data)) {
                if (strtolower($elem) === 'cloud') {
                    $attributes = $data;
                    $data = [];
                } elseif (isset($data['attrib']) && is_array($data['attrib'])) {
                    $attributes = $data['attrib'];
                    unset($data['attrib']);
                } else {
                    $innerElements = '';
                    foreach ($data as $subElement => $value) {
                        $innerElements .= $this->elem($subElement, [], $value);
                    }
                    $data = $innerElements;
                }
            }
            $elems .= $this->elem($elem, $attributes, $data);
        }
        return $this->elem('channel', $attrib, $elems . $content, !($content === null));
    }

    /**
     * Transforms an array of data using an optional callback, and maps it to a set
     * of `<item />` tags
     *
     * @param array $items The list of items to be mapped
     * @param string|array|null $callback A string function name, or array containing an object
     *     and a string method name
     * @return string A set of RSS `<item />` elements
     */
    public function items($items, $callback = null)
    {
        if ($callback) {
            $items = array_map($callback, $items);
        }

        $out = '';
        $c = count($items);

        for ($i = 0; $i < $c; $i++) {
            $out .= $this->item([], $items[$i]);
        }
        return $out;
    }

    /**
     * Converts an array into an `<item />` element and its contents
     *
     * @param array $att The attributes of the `<item />` element
     * @param array $elements The list of elements contained in this `<item />`
     * @return string An RSS `<item />` element
     */
    public function item($att = [], $elements = [])
    {
        $content = null;

        if (isset($elements['link']) && !isset($elements['guid'])) {
            $elements['guid'] = $elements['link'];
        }

        foreach ($elements as $key => $val) {
            $attrib = [];

            $escape = true;
            if (is_array($val) && isset($val['convertEntities'])) {
                $escape = $val['convertEntities'];
                unset($val['convertEntities']);
            }

            switch ($key) {
                case 'pubDate':
                    $val = $this->time($val);
                    break;
                case 'category':
                    if (is_array($val) && !empty($val[0])) {
                        foreach ($val as $category) {
                            $attrib = [];
                            if (is_array($category) && isset($category['domain'])) {
                                $attrib['domain'] = $category['domain'];
                                unset($category['domain']);
                            }
                            $categories[] = $this->elem($key, $attrib, $category);
                        }
                        $elements[$key] = implode('', $categories);
                        continue 2;
                    }
                    if (is_array($val) && isset($val['domain'])) {
                        $attrib['domain'] = $val['domain'];
                    }
                    break;
                case 'link':
                case 'guid':
                case 'comments':
                    if (is_array($val) && isset($val['url'])) {
                        $attrib = $val;
                        unset($attrib['url']);
                        $val = $val['url'];
                    }
                    $val = $this->Url->build($val, true);
                    break;
                case 'source':
                    if (is_array($val) && isset($val['url'])) {
                        $attrib['url'] = $this->Url->build($val['url'], true);
                        $val = $val['title'];
                    } elseif (is_array($val)) {
                        $attrib['url'] = $this->Url->build($val[0], true);
                        $val = $val[1];
                    }
                    break;
                case 'enclosure':
                    if (is_string($val['url']) && is_file(WWW_ROOT . $val['url']) && file_exists(WWW_ROOT . $val['url'])) {
                        if (!isset($val['length']) && strpos($val['url'], '://') === false) {
                            $val['length'] = sprintf("%u", filesize(WWW_ROOT . $val['url']));
                        }
                        if (!isset($val['type']) && function_exists('mime_content_type')) {
                            $val['type'] = mime_content_type(WWW_ROOT . $val['url']);
                        }
                    }
                    $val['url'] = $this->Url->build($val['url'], true);
                    $attrib = $val;
                    $val = null;
                    break;
                default:
                    $attrib = $att;
            }
            if ($val !== null && $escape) {
                $val = h($val);
            }
            $elements[$key] = $this->elem($key, $attrib, $val);
        }
        if (!empty($elements)) {
            $content = implode('', $elements);
        }
        return $this->elem('item', (array)$att, $content, !($content === null));
    }

    /**
     * Converts a time in any format to an RSS time
     *
     * @param int|string|\DateTime $time UNIX timestamp or valid time string or DateTime object.
     * @return string An RSS-formatted timestamp
     * @see TimeHelper::toRSS
     */
    public function time($time)
    {
        return $this->Time->toRSS($time);
    }

    /**
     * Generates an XML element
     *
     * @param string $name The name of the XML element
     * @param array $attrib The attributes of the XML element
     * @param string|array $content XML element content
     * @param bool $endTag Whether the end tag of the element should be printed
     * @return string XML
     */
    public function elem($name, $attrib = [], $content = null, $endTag = true)
    {
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
        $children = [];
        if (is_array($content)) {
            $children = $content;
            $content = null;
        }

        $xml = '<' . $name;
        if (!empty($namespace)) {
            $xml .= ' xmlns';
            if (is_array($namespace)) {
                $xml .= ':' . $namespace['prefix'];
                $namespace = $namespace['url'];
            }
            $xml .= '="' . $namespace . '"';
        }
        $bareName = $name;
        if (strpos($name, ':') !== false) {
            list($prefix, $bareName) = explode(':', $name, 2);
            switch ($prefix) {
                case 'atom':
                    $xml .= ' xmlns:atom="http://www.w3.org/2005/Atom"';
                    break;
            }
        }
        if ($cdata && !empty($content)) {
            $content = '<![CDATA[' . $content . ']]>';
        }
        $xml .= '>' . $content . '</' . $name . '>';
        $elem = Xml::build($xml, ['return' => 'domdocument']);
        $nodes = $elem->getElementsByTagName($bareName);
        if ($attrib) {
            foreach ($attrib as $key => $value) {
                $nodes->item(0)->setAttribute($key, $value);
            }
        }
        foreach ($children as $child) {
            $child = $elem->createElement($name, $child);
            $nodes->item(0)->appendChild($child);
        }

        $xml = $elem->saveXml();
        $xml = trim(substr($xml, strpos($xml, '?>') + 2));
        return $xml;
    }

    /**
     * Event listeners.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [];
    }
}
