<?php
/* SVN FILE: $Id$ */
/**
 * RSS Helper class file.
 *
 * Simplifies the output of RSS feeds.
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view.helpers
 * @since         CakePHP(tm) v 1.2
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Helper', 'Xml');

/**
 * XML Helper class for easy output of XML structures.
 *
 * XmlHelper encloses all methods needed while working with XML documents.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.view.helpers
 */
class RssHelper extends XmlHelper {
/**
 * Helpers used by RSS Helper
 *
 * @var array
 * @access public
 **/
	var $helpers = array('Time');
/**
 * Base URL
 *
 * @access public
 * @var string
 */
	var $base = null;
/**
 * URL to current action.
 *
 * @access public
 * @var string
 */
	var $here = null;
/**
 * Parameter array.
 *
 * @access public
 * @var array
 */
	var $params = array();
/**
 * Current action.
 *
 * @access public
 * @var string
 */
	var $action = null;
/**
 * POSTed model data
 *
 * @access public
 * @var array
 */
	var $data = null;
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
 * Default spec version of generated RSS
 *
 * @access public
 * @var string
 */
	var $version = '2.0';
/**
 * Returns an RSS document wrapped in <rss /> tags
 *
 * @param  array  $attrib <rss /> tag attributes
 * @return string An RSS document
 */
	function document($attrib = array(), $content = null) {
		if ($content === null) {
			$content = $attrib;
			$attrib = array();
		}
		if (!isset($attrib['version']) || empty($attrib['version'])) {
			$attrib['version'] = $this->version;
		}

		return $this->elem('rss', $attrib, $content);
	}
/**
 * Returns an RSS <channel /> element
 *
 * @param  array  $attrib   <channel /> tag attributes
 * @param  mixed  $elements Named array elements which are converted to tags
 * @param  mixed  $content  Content (<item />'s belonging to this channel
 * @return string An RSS <channel />
 */
	function channel($attrib = array(), $elements = array(), $content = null) {
		$view =& ClassRegistry::getObject('view');

		if (!isset($elements['title']) && !empty($view->pageTitle)) {
			$elements['title'] = $view->pageTitle;
		}
		if (!isset($elements['link'])) {
			$elements['link'] = '/';
		}
		if (!isset($elements['description'])) {
			$elements['description'] = '';
		}
		$elements['link'] = $this->url($elements['link'], true);

		$elems = '';
		foreach ($elements as $elem => $data) {
			$attributes = array();
			if (is_array($data)) {
				if (strtolower($elem) == 'cloud') {
					$attributes = $data;
					$data = array();
				} elseif (isset($data['attrib']) && is_array($data['attrib'])) {
					$attributes = $data['attrib'];
					unset($data['attrib']);
				} else {
					$innerElements = '';
					foreach ($data as $subElement => $value) {
						$innerElements .= $this->elem($subElement, array(), $value);
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
 * of <item /> tags
 *
 * @param  array  $items    The list of items to be mapped
 * @param  mixed  $callback A string function name, or array containing an object
 *                          and a string method name
 * @return string A set of RSS <item /> elements
 */
	function items($items, $callback = null) {
		if ($callback != null) {
			$items = array_map($callback, $items);
		}

		$out = '';
		$c = count($items);

		for ($i = 0; $i < $c; $i++) {
			$out .= $this->item(array(), $items[$i]);
		}
		return $out;
	}
/**
 * Converts an array into an <item /> element and its contents
 *
 * @param  array  $attrib      The attributes of the <item /> element
 * @param  array  $elements    The list of elements contained in this <item />
 * @return string An RSS <item /> element
 */
	function item($att = array(), $elements = array()) {
		$content = null;

		if (isset($elements['link']) && !isset($elements['guid'])) {
			$elements['guid'] = $elements['link'];
		}

		foreach ($elements as $key => $val) {
			$attrib = array();
			switch ($key) {
				case 'pubDate' :
					$val = $this->time($val);
				break;
				case 'category' :
					if (is_array($val) && !empty($val[0])) {
						foreach ($val as $category) {
							$attrib = array();
							if (isset($category['domain'])) {
								$attrib['domain'] = $category['domain'];
								unset($category['domain']);
							}
							$categories[] = $this->elem($key, $attrib, $category);
						}
						$elements[$key] = join('', $categories);
						continue 2;
					} else if (is_array($val) && isset($val['domain'])) {
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
					$val = $this->url($val, true);
				break;
				case 'source':
					if (is_array($val) && isset($val['url'])) {
						$attrib['url'] = $this->url($val['url'], true);
						$val = $val['title'];
					} elseif (is_array($val)) {
						$attrib['url'] = $this->url($val[0], true);
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
					$val['url'] = $this->url($val['url'], true);
					$attrib = $val;
					$val = null;
				break;
			}
			$escape = true;
			if (is_array($val) && isset($val['convertEntities'])) {
				$escape = $val['convertEntities'];
				unset($val['convertEntities']);
			}
			if (!is_null($val) && $escape) {
				$val = h($val);
			}
			$elements[$key] = $this->elem($key, $attrib, $val);
		}
		if (!empty($elements)) {
			$content = join('', $elements);
		}
		return $this->output($this->elem('item', $att, $content, !($content === null)));
	}
/**
 * Converts a time in any format to an RSS time
 *
 * @param  mixed  $time
 * @return string An RSS-formatted timestamp
 * @see TimeHelper::toRSS
 */
	function time($time) {
		return $this->Time->toRSS($time);
	}
}
?>