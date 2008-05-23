<?php
/* SVN FILE: $Id$ */
/**
 * Html Helper class file.
 *
 * Simplifies the construction of HTML elements.
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
 * @since			CakePHP(tm) v 0.9.1
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Html Helper class for easy use of HTML widgets.
 *
 * HtmlHelper encloses all methods needed while working with HTML pages.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.view.helpers
 */
class HtmlHelper extends AppHelper {
/*************************************************************************
 * Public variables
 *************************************************************************/
/**#@+
 * @access public
 */
/**
 * html tags used by this helper.
 *
 * @var array
 */
	var $tags = array(
		'meta' => '<meta%s/>',
		'metalink' => '<link href="%s"%s/>',
		'link' => '<a href="%s"%s>%s</a>',
		'mailto' => '<a href="mailto:%s" %s>%s</a>',
		'form' => '<form %s>',
		'formend' => '</form>',
		'input' => '<input name="%s" %s/>',
		'textarea' => '<textarea name="%s" %s>%s</textarea>',
		'hidden' => '<input type="hidden" name="%s" %s/>',
		'checkbox' => '<input type="checkbox" name="%s" %s/>',
		'checkboxmultiple' => '<input type="checkbox" name="%s[]"%s />',
		'radio' => '<input type="radio" name="%s" id="%s" %s />%s',
		'selectstart' => '<select name="%s"%s>',
		'selectmultiplestart' => '<select name="%s[]"%s>',
		'selectempty' => '<option value=""%s>&nbsp;</option>',
		'selectoption' => '<option value="%s"%s>%s</option>',
		'selectend' => '</select>',
		'optiongroup' => '<optgroup label="%s"%s>',
		'optiongroupend' => '</optgroup>',
		'checkboxmultiplestart' => '',
		'checkboxmultipleend' => '',
		'password' => '<input type="password" name="%s" %s/>',
		'file' => '<input type="file" name="%s" %s/>',
		'file_no_model' => '<input type="file" name="%s" %s/>',
		'submit' => '<input type="submit" %s/>',
		'submitimage' => '<input type="image" src="%s" %s/>',
		'button' => '<input type="%s" %s/>',
		'image' => '<img src="%s" %s/>',
		'tableheader' => '<th%s>%s</th>',
		'tableheaderrow' => '<tr%s>%s</tr>',
		'tablecell' => '<td%s>%s</td>',
		'tablerow' => '<tr%s>%s</tr>',
		'block' => '<div%s>%s</div>',
		'blockstart' => '<div%s>',
		'blockend' => '</div>',
		'tag' => '<%s%s>%s</%s>',
		'tagstart' => '<%s%s>',
		'tagend' => '</%s>',
		'para' => '<p%s>%s</p>',
		'parastart' => '<p%s>',
		'label' => '<label for="%s"%s>%s</label>',
		'fieldset' => '<fieldset%s>%s</fieldset>',
		'fieldsetstart' => '<fieldset><legend>%s</legend>',
		'fieldsetend' => '</fieldset>',
		'legend' => '<legend>%s</legend>',
		'css' => '<link rel="%s" type="text/css" href="%s" %s/>',
		'style' => '<style type="text/css"%s>%s</style>',
		'charset' => '<meta http-equiv="Content-Type" content="text/html; charset=%s" />',
		'ul' => '<ul%s>%s</ul>',
		'ol' => '<ol%s>%s</ol>',
		'li' => '<li%s>%s</li>',
		'error' => '<div%s>%s</div>'
	);
/**
 * Base URL
 *
 * @var string
 */
	var $base = null;
/**
 * URL to current action.
 *
 * @var string
 */
	var $here = null;
/**
 * Parameter array.
 *
 * @var array
 */
	var $params = array();
/**
 * Current action.
 *
 * @var string
 */
	var $action = null;
/**
 * Enter description here...
 *
 * @var array
 */
	var $data = null;
/**#@-*/
/*************************************************************************
 * Private variables
 *************************************************************************/
/**#@+
 * @access private
 */
/**
 * Breadcrumbs.
 *
 * @var	array
 * @access private
 */
	var $_crumbs = array();
/**
 * Document type definitions
 *
 * @var	array
 * @access private
 */
	var $__docTypes = array(
		'html4-strict'  => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
		'html4-trans'  => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
		'html4-frame'  => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">',
		'xhtml-strict' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
		'xhtml-trans' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
		'xhtml-frame' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
		'xhtml11' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'
	);
/**
 * Adds a link to the breadcrumbs array.
 *
 * @param string $name Text for link
 * @param string $link URL for link (if empty it won't be a link)
 * @param mixed $options Link attributes e.g. array('id'=>'selected')
 */
	function addCrumb($name, $link = null, $options = null) {
		$this->_crumbs[] = array($name, $link, $options);
	}
/**
 * Returns a doctype string.
 *
 * Possible doctypes:
 *   + html4-strict:  HTML4 Strict.
 *   + html4-trans:  HTML4 Transitional.
 *   + html4-frame:  HTML4 Frameset.
 *   + xhtml-strict: XHTML1 Strict.
 *   + xhtml-trans: XHTML1 Transitional.
 *   + xhtml-frame: XHTML1 Frameset.
 *   + xhtml11: XHTML1.1.
 *
 * @param  string $type Doctype to use.
 * @return string Doctype.
 */
	function docType($type = 'xhtml-strict') {
		if (isset($this->__docTypes[$type])) {
			return $this->output($this->__docTypes[$type]);
		}
		return null;
	}
/**
 * Creates a link to an external resource and handles basic meta tags
 *
 * @param  string  $title The title of the external resource
 * @param  mixed   $url   The address of the external resource or string for content attribute
 * @param  array   $attributes Other attributes for the generated tag. If the type attribute is html, rss, atom, or icon, the mime-type is returned.
 * @param  boolean $inline If set to false, the generated tag appears in the head tag of the layout.
 * @return string
 */
	function meta($type, $url = null, $attributes = array(), $inline = true) {
		if (!is_array($type)) {
			$types = array(
				'rss'	=> array('type' => 'application/rss+xml', 'rel' => 'alternate', 'title' => $type, 'link' => $url),
				'atom'	=> array('type' => 'application/atom+xml', 'title' => $type, 'link' => $url),
				'icon'	=> array('type' => 'image/x-icon', 'rel' => 'icon', 'link' => $url),
				'keywords' => array('name' => 'keywords', 'content' => $url),
				'description' => array('name' => 'description', 'content' => $url),
			);

			if ($type === 'icon' && $url === null) {
				$types['icon']['link'] = $this->webroot('favicon.ico');
			}

			if (isset($types[$type])) {
				$type = $types[$type];
			} elseif (!isset($attributes['type']) && $url !== null) {
				if (is_array($url) && isset($url['ext'])) {
					$type = $types[$url['ext']];
				} else {
					$type = $types['rss'];
				}
			} elseif (isset($attributes['type']) && isset($types[$attributes['type']])) {
				$type = $types[$attributes['type']];
				unset($attributes['type']);
			}
		} elseif ($url !== null) {
			$inline = $url;
		}
		$attributes = array_merge($type, $attributes);
		$out = null;

 		if (isset($attributes['link'])) {
			if (isset($attributes['rel']) && $attributes['rel'] === 'icon') {
				$out = sprintf($this->tags['metalink'], $attributes['link'], $this->_parseAttributes($attributes, array('link')));
				$attributes['rel'] = 'shortcut icon';
			} else {
				$attributes['link'] = $this->url($attributes['link'], true);
			}
			$out .= sprintf($this->tags['metalink'], $attributes['link'], $this->_parseAttributes($attributes, array('link')));
		} else {
			$out = sprintf($this->tags['meta'], $this->_parseAttributes($attributes, array('type')));
		}

		if ($inline) {
			return $this->output($out);
		} else {
			$view =& ClassRegistry::getObject('view');
			$view->addScript($out);
		}
	}
/**
 * Returns a charset META-tag.
 *
 * @param  string  $charset The character set to be used in the meta tag. Example: "utf-8".
 * @return string A meta tag containing the specified character set.
 */
	function charset($charset = null) {
		$charset = current(array_filter(array($charset, strtolower(Configure::read('App.encoding')), 'utf-8')));
		return $this->output(sprintf($this->tags['charset'], $charset));
	}
/**
 * Creates an HTML link.
 *
 * If $url starts with "http://" this is treated as an external link. Else,
 * it is treated as a path to controller/action and parsed with the
 * HtmlHelper::url() method.
 *
 * If the $url is empty, $title is used instead.
 *
 * @param  string  $title The content to be wrapped by <a> tags.
 * @param  mixed   $url Cake-relative URL or array of URL parameters, or external URL (starts with http://)
 * @param  array   $htmlAttributes Array of HTML attributes.
 * @param  string  $confirmMessage JavaScript confirmation message.
 * @param  boolean $escapeTitle	Whether or not $title should be HTML escaped.
 * @return string	An <a /> element.
 */
	function link($title, $url = null, $htmlAttributes = array(), $confirmMessage = false, $escapeTitle = true) {
		if ($url !== null) {
			$url = $this->url($url);
		} else {
			$url = $this->url($title);
			$title = $url;
			$escapeTitle = false;
		}

		if (isset($htmlAttributes['escape'])) {
			$escapeTitle = $htmlAttributes['escape'];
			unset($htmlAttributes['escape']);
		}
		if ($escapeTitle === true) {
			$title = htmlspecialchars($title, ENT_QUOTES);
		} elseif (is_string($escapeTitle)) {
			$title = htmlentities($title, ENT_QUOTES, $escapeTitle);
		}

		if (!empty($htmlAttributes['confirm'])) {
			$confirmMessage = $htmlAttributes['confirm'];
			unset($htmlAttributes['confirm']);
		}
		if ($confirmMessage) {
			$confirmMessage = str_replace("'", "\'", $confirmMessage);
			$confirmMessage = str_replace('"', '\"', $confirmMessage);
			$htmlAttributes['onclick'] = "return confirm('{$confirmMessage}');";
		} elseif (isset($htmlAttributes['default']) && $htmlAttributes['default'] == false) {
			if (isset($htmlAttributes['onclick'])) {
				$htmlAttributes['onclick'] .= ' event.returnValue = false; return false;';
			} else {
				$htmlAttributes['onclick'] = 'event.returnValue = false; return false;';
			}
			unset($htmlAttributes['default']);
		}
		return $this->output(sprintf($this->tags['link'], $url, $this->_parseAttributes($htmlAttributes), $title));
	}
/**
 * Creates a link element for CSS stylesheets.
 *
 * @param mixed $path The name of a CSS style sheet in /app/webroot/css, or an array containing names of CSS stylesheets in that directory.
 * @param string $rel Rel attribute. Defaults to "stylesheet".
 * @param array $htmlAttributes Array of HTML attributes.
 * @param boolean $inline If set to false, the generated tag appears in the head tag of the layout.
 * @return string CSS <link /> or <style /> tag, depending on the type of link.
 */
	function css($path, $rel = null, $htmlAttributes = array(), $inline = true) {
		if (is_array($path)) {
			$out = '';
			foreach ($path as $i) {
				$out .= "\n\t" . $this->css($i, $rel, $htmlAttributes, $inline);
			}
			if ($inline)  {
				return $out . "\n";
			}
			return;
		}

		if (strpos($path, '://') !== false) {
			$url = $path;
		} else {
			if ($path{0} !== '/') {
				$path = CSS_URL . $path;
			}

			if (strpos($path, '?') === false) {
				if (strpos($path, '.css') === false) {
			 		$path .= '.css';
				}
				if ((Configure::read('Asset.timestamp') === true && Configure::read() > 0) || Configure::read('Asset.timestamp') === 'force') {
					$path .= '?' . @filemtime(WWW_ROOT . str_replace('/', DS, $path));
				}
			}

			if (Configure::read('Asset.filter.css')) {
				$path = str_replace(CSS_URL, 'ccss/', $path);
			}
			$url = $this->webroot($path);
		}

		if ($rel == 'import') {
			$out = sprintf($this->tags['style'], $this->_parseAttributes($htmlAttributes, null, '', ' '), '@import url(' . $url . ');');
		} else {
			if ($rel == null) {
				$rel = 'stylesheet';
			}
			$out = sprintf($this->tags['css'], $rel, $url, $this->_parseAttributes($htmlAttributes, null, '', ' '));
		}
		$out = $this->output($out);

		if ($inline) {
			return $out;
		} else {
			$view =& ClassRegistry::getObject('view');
			$view->addScript($out);
		}
	}
/**
 * Builds CSS style data from an array of CSS properties
 *
 * @param array $data
 * @return string CSS styling data
 */
	function style($data, $inline = true) {
		if (!is_array($data)) {
			return $data;
		}
		$out = array();
		foreach ($data as $key=> $value) {
			$out[] = $key.':'.$value.';';
		}
		if ($inline) {
			return join(' ', $out);
		}
		return join("\n", $out);
	}
/**
 * Returns the breadcrumb trail as a sequence of &raquo;-separated links.
 *
 * @param  string  $separator Text to separate crumbs.
 * @param  string  $startText This will be the first crumb, if false it defaults to first crumb in array
 * @return string
 */
	function getCrumbs($separator = '&raquo;', $startText = false) {
		if (count($this->_crumbs)) {
			$out = array();
			if ($startText) {
				$out[] = $this->link($startText, '/');
			}

			foreach ($this->_crumbs as $crumb) {
				if (!empty($crumb[1])) {
					$out[] = $this->link($crumb[0], $crumb[1], $crumb[2]);
				} else {
					$out[] = $crumb[0];
				}
			}
			return $this->output(join($separator, $out));
		} else {
			return null;
		}
	}
/**
 * Creates a formatted IMG element.
 *
 * @param string $path Path to the image file, relative to the app/webroot/img/ directory.
 * @param array	$htmlAttributes Array of HTML attributes.
 * @return string
 */
	function image($path, $options = array()) {
		if (is_array($path)) {
			$path = Router::url($path);
		} elseif ($path{0} === '/') {
			$path = $this->webroot($path);
		} elseif (strpos($path, '://') !== false) {
			$path = $path;
		} else {
			if (Configure::read('Asset.timestamp') == true && Configure::read() > 0) {
				$path .= '?' . @filemtime(str_replace('/', DS, WWW_ROOT . IMAGES_URL . $path));
			}
			$path = $this->webroot(IMAGES_URL . $path);
		}

		if (!isset($options['alt'])) {
			$options['alt'] = '';
		}

		$url = false;
		if (!empty($options['url'])) {
			$url = $options['url'];
			unset($options['url']);
		}

		$image = sprintf($this->tags['image'], $path, $this->_parseAttributes($options, null, '', ' '));

		if ($url) {
			return $this->output(sprintf($this->tags['link'], $this->url($url), null, $image));
		}

		return $this->output($image);
	}
/**
 * Returns a row of formatted and named TABLE headers.
 *
 * @param array $names		Array of tablenames.
 * @param array $trOptions	HTML options for TR elements.
 * @param array $thOptions	HTML options for TH elements.
 * @return string
 */
	function tableHeaders($names, $trOptions = null, $thOptions = null) {
		$out = array();
		foreach ($names as $arg) {
			$out[] = sprintf($this->tags['tableheader'], $this->_parseAttributes($thOptions), $arg);
		}
		$data = sprintf($this->tags['tablerow'], $this->_parseAttributes($trOptions), join(' ', $out));
		return $this->output($data);
	}
/**
 * Returns a formatted string of table rows (TR's with TD's in them).
 *
 * @param array $data		Array of table data
 * @param array $oddTrOptions HTML options for odd TR elements if true useCount is used
 * @param array $evenTrOptions HTML options for even TR elements
 * @param bool $useCount adds class "column-$i"
 * @param bool $continueOddEven If false, will use a non-static $count variable, so that the odd/even count is reset to zero just for that call
 * @return string	Formatted HTML
 */
	function tableCells($data, $oddTrOptions = null, $evenTrOptions = null, $useCount = false, $continueOddEven = true) {
		if (empty($data[0]) || !is_array($data[0])) {
			$data = array($data);
		}

		if ($oddTrOptions === true) {
			$useCount = true;
			$oddTrOptions = null;
		}

		if ($evenTrOptions === false) {
			$continueOddEven = false;
			$evenTrOptions = null;
		}

		if ($continueOddEven) {
			static $count = 0;
		} else {
			$count = 0;
	    }

		foreach ($data as $line) {
			$count++;
			$cellsOut = array();
			$i = 0;
			foreach ($line as $cell) {
				$cellOptions = array();

				if (is_array($cell)) {
					$cellOptions = $cell[1];
					$cell = $cell[0];
				} elseif ($useCount) {
					$cellOptions['class'] = 'column-' . ++$i;
				}
				$cellsOut[] = sprintf($this->tags['tablecell'], $this->_parseAttributes($cellOptions), $cell);
			}
			$options = $this->_parseAttributes($count % 2 ? $oddTrOptions : $evenTrOptions);
			$out[] = sprintf($this->tags['tablerow'], $options, join(' ', $cellsOut));
		}
		return $this->output(join("\n", $out));
	}
/**
 * Returns a formatted block tag, i.e DIV, SPAN, P.
 *
 * @param string $name Tag name.
 * @param string $text String content that will appear inside the div element.
 *			If null, only a start tag will be printed
 * @param array $attributes Additional HTML attributes of the DIV tag
 * @param boolean $escape If true, $text will be HTML-escaped
 * @return string The formatted tag element
 */
	function tag($name, $text = null, $attributes = array(), $escape = false) {
		if ($escape) {
			$text = h($text);
		}
		if (!is_array($attributes)) {
		  $attributes = array('class' => $attributes);
		}
		if ($text === null) {
			$tag = 'tagstart';
		} else {
			$tag = 'tag';
		}
		return $this->output(sprintf($this->tags[$tag], $name, $this->_parseAttributes($attributes, null, ' ', ''), $text, $name));
    }
/**
 * Returns a formatted DIV tag for HTML FORMs.
 *
 * @param string $class CSS class name of the div element.
 * @param string $text String content that will appear inside the div element.
 *			If null, only a start tag will be printed
 * @param array $attributes Additional HTML attributes of the DIV tag
 * @param boolean $escape If true, $text will be HTML-escaped
 * @return string The formatted DIV element
 */
	function div($class = null, $text = null, $attributes = array(), $escape = false) {
		if ($class != null && !empty($class)) {
			$attributes['class'] = $class;
		}
	    return $this->tag('div', $text, $attributes, $escape);
	}
/**
 * Returns a formatted P tag.
 *
 * @param string $class CSS class name of the p element.
 * @param string $text String content that will appear inside the p element.
 * @param array $attributes Additional HTML attributes of the P tag
 * @param boolean $escape If true, $text will be HTML-escaped
 * @return string The formatted P element
 */
	function para($class, $text, $attributes = array(), $escape = false) {
		if ($escape) {
			$text = h($text);
		}
		if ($class != null && !empty($class)) {
			$attributes['class'] = $class;
		}
		if ($text === null) {
			$tag = 'parastart';
		} else {
			$tag = 'para';
		}
		return $this->output(sprintf($this->tags[$tag], $this->_parseAttributes($attributes, null, ' ', ''), $text));
	}
/**
 * Build a nested list (UL/OL) out of an associative array.
 *
 * @param array $list Set of elements to list
 * @param array $attributes Additional HTML attributes of the list (ol/ul) tag or if ul/ol use that as tag
 * @param array $itemAttributes Additional HTML attributes of the list item (LI) tag
 * @param string $tag Type of list tag to use (ol/ul)
 * @return string The nested list
 * @access public
 */
	function nestedList($list, $attributes = array(), $itemAttributes = array(), $tag = 'ul') {
		if (is_string($attributes)) {
			$tag = $attributes;
			$attributes = array();
		}
		$items = $this->__nestedListItem($list, $attributes, $itemAttributes, $tag);
		return sprintf($this->tags[$tag], $this->_parseAttributes($attributes, null, ' ', ''), $items);
	}
/**
 * Internal function to build a nested list (UL/OL) out of an associative array.
 *
 * @param array $list Set of elements to list
 * @param array $attributes Additional HTML attributes of the list (ol/ul) tag
 * @param array $itemAttributes Additional HTML attributes of the list item (LI) tag
 * @param string $tag Type of list tag to use (ol/ul)
 * @return string The nested list element
 * @access private
 * @see nestedList()
 */
	function __nestedListItem($items, $attributes, $itemAttributes, $tag) {
		$out = '';

		$index = 1;
		foreach($items as $key => $item) {
			if (is_array($item)) {
				$item = $key . $this->nestedList($item, $attributes, $itemAttributes, $tag);
			}
			if (isset($itemAttributes['even']) && $index % 2 == 0) {
				$itemAttributes['class'] = $itemAttributes['even'];
			} else if (isset($itemAttributes['odd']) && $index % 2 != 0) {
				$itemAttributes['class'] = $itemAttributes['odd'];
			}
			$out .= sprintf($this->tags['li'], $this->_parseAttributes(array_diff_key($itemAttributes, array_flip(array('even', 'odd'))), null, ' ', ''), $item);
			$index++;
		}
		return $out;
	}
/**
 * Creates a password input widget.
 *
 * @deprecated 1.2.0.5147
 * @see FormHelper::input or FormHelper::password
 * @codeCoverageIgnoreStart
 */
	function password($fieldName, $htmlAttributes = array()) {
		trigger_error(sprintf(__('Method password() is deprecated in %s: see FormHelper::input or FormHelper::password', true), get_class($this)), E_USER_NOTICE);
		$htmlAttributes = $this->value($htmlAttributes, $fieldName);
		$htmlAttributes = $this->domId($htmlAttributes);
		if ($this->tagIsInvalid()) {
			$htmlAttributes = $this->addClass($htmlAttributes, 'form_error');
		}
		return $this->output(sprintf($this->tags['password'], $this->model(), $this->field(), $this->_parseAttributes($htmlAttributes, null, ' ', ' ')));
	}
/**
 * Creates a set of radio widgets.
 *
 * @deprecated
 */
	function radio($fieldName, $options, $inbetween = null, $htmlAttributes = array()) {
		trigger_error(__('(HtmlHelper::radio) Deprecated: Use FormHelper::radio instead', true), E_USER_WARNING);

		$this->setEntity($fieldName);
		$value = isset($htmlAttributes['value']) ? $htmlAttributes['value'] : $this->value($fieldName);
		$out = array();

		foreach ($options as $optValue => $optTitle) {
			$optionsHere = array('value' => $optValue);
			if (!empty($value) && $optValue == $value) {
				$optionsHere['checked'] = 'checked';
			}
			$parsedOptions = $this->_parseAttributes(array_merge($htmlAttributes, $optionsHere), null, '', ' ');
			$individualTagName = $this->field() . "_{$optValue}";
			$out[] = sprintf($this->tags['radio'], $this->model(), $this->field(), $individualTagName, $parsedOptions, $optTitle);
		}

		$out = join($inbetween, $out);
		return $this->output($out ? $out : null);
	}
/**
 * Creates a textarea widget.
 *
 * @deprecated 1.2.0.5147
 * @see FormHelper::input or FormHelper::textarea
 */
	function textarea($fieldName, $htmlAttributes = array()) {
		trigger_error(sprintf(__('Method textarea() is deprecated in %s: see FormHelper::input or FormHelper::textarea', true), get_class($this)), E_USER_NOTICE);
		$htmlAttributes = $this->value($htmlAttributes, $fieldName);

		$value = null;
		if (isset($htmlAttributes['value']) && !empty($htmlAttributes['value'])) {
			$value = $htmlAttributes['value'];
			unset($htmlAttributes['value']);
		}
		$htmlAttributes = $this->domId($htmlAttributes);

		if ($this->tagIsInvalid()) {
			$htmlAttributes = $this->addClass($htmlAttributes, 'form_error');
		}
		return $this->output(sprintf($this->tags['textarea'], $this->model(), $this->field(), $this->_parseAttributes($htmlAttributes, null, ' '), $value));
	}
/**
 * Creates a checkbox widget.
 *
 * @deprecated 1.2.0.5147
 * @see FormHelper::input or FormHelper::checkbox
 */
	function checkbox($fieldName, $title = null, $htmlAttributes = array()) {
		trigger_error(sprintf(__('Method checkbox() is deprecated in %s: see FormHelper::input or FormHelper::checkbox', true), get_class($this)), E_USER_NOTICE);
		$value = $this->tagValue($fieldName);
		$notCheckedValue = 0;

		if (isset($htmlAttributes['checked'])) {
			if ($htmlAttributes['checked'] == 'checked' || intval($htmlAttributes['checked']) === 1 || $htmlAttributes['checked'] === true) {
				$htmlAttributes['checked'] = 'checked';
			} else {
				$htmlAttributes['checked'] = null;
				$notCheckedValue = -1;
			}
		} else {
			$model = $this->model();
			if (isset($htmlAttributes['value']) || (!class_exists($model) && !App::import('Model', $model))) {
				if (isset($htmlAttributes['value']) && $htmlAttributes['value'] == $value) {
					$htmlAttributes['checked'] = 'checked';
				} else {
					$htmlAttributes['checked'] = null;
				}
				if (isset($htmlAttributes['value']) && $htmlAttributes['value'] == '0') {
					$notCheckedValue = -1;
				}
			} else {
				$model = new $model;
				$db =& ConnectionManager::getDataSource($model->useDbConfig);
				$value = $db->boolean($value);
				$htmlAttributes['checked'] = $value ? 'checked' : null;
				$htmlAttributes['value'] = 1;
			}
		}
		$htmlAttributes = $this->domId($htmlAttributes);
		$output = $this->hidden($fieldName, array('value' => $notCheckedValue, 'id' => $htmlAttributes['id'] . '_'), true);
		$output .= sprintf($this->tags['checkbox'], $this->model(), $this->field(), $this->_parseAttributes($htmlAttributes, null, '', ' '));
		return $this->output($output);
	}
/**
 * Creates a hidden input field.
 *
 * @deprecated 1.2.0.5147
 * @see FormHelper::input or FormHelper::hidden
 */
	function hidden($fieldName, $htmlAttributes = array()) {
		trigger_error(sprintf(__('Method hidden() is deprecated in %s: see FormHelper::input or FormHelper::hidden', true), get_class($this)), E_USER_NOTICE);
		$htmlAttributes = $this->value($htmlAttributes, $fieldName);
		$htmlAttributes = $this->domId($htmlAttributes);
		return $this->output(sprintf($this->tags['hidden'], $this->model(), $this->field(), $this->_parseAttributes($htmlAttributes, null, ' ', ' ')));
	}
/**
 * Creates a text input widget.
 *
 * @deprecated 1.2.0.5147
 * @see FormHelper::input or FormHelper::text
 */
	function input($fieldName, $htmlAttributes = array()) {
		trigger_error(sprintf(__('Method input() is deprecated in %s: see FormHelper::input or FormHelper::text', true), get_class($this)), E_USER_NOTICE);
		$htmlAttributes = $this->value($htmlAttributes, $fieldName);
		$htmlAttributes = $this->domId($htmlAttributes);

		if (!isset($htmlAttributes['type'])) {
			$htmlAttributes['type'] = 'text';
		}

		if ($this->tagIsInvalid()) {
			$htmlAttributes = $this->addClass($htmlAttributes, 'form_error');
		}
		return $this->output(sprintf($this->tags['input'], $this->model(), $this->field(), $this->_parseAttributes($htmlAttributes, null, ' ', ' ')));
	}

/**
 * Returns value of $fieldName. False if the tag does not exist.
 *
 * @deprecated 1.2.0.5147
 * @see Helper::value
 */
	function tagValue($fieldName) {
	    trigger_error(sprintf(__('Method tagValue() is deprecated in %s: see Helper::value', true), get_class($this)), E_USER_NOTICE);
		$this->setEntity($fieldName);
		if (isset($this->data[$this->model()][$this->field()])) {
			return h($this->data[$this->model()][$this->field()]);
		}
		return false;
	}
/**
 * Returns number of errors in a submitted FORM.
 *
 * @deprecated 1.2.0.5147
 * @see FormHelper::errors
 */
	function validate() {
		trigger_error(sprintf(__('Method validate() is deprecated in %s: see FormHelper::errors', true), get_class($this)), E_USER_NOTICE);
		$args = func_get_args();
		$errors = call_user_func_array(array(&$this, 'validateErrors'),  $args);
		return count($errors);
	}
/**
 * Validates a FORM according to the rules set up in the Model.
 *
 * @deprecated 1.2.0.5147
 * @see FormHelper::errors
 */
	function validateErrors() {
		trigger_error(sprintf(__('Method validateErrors() is deprecated in %s: see FormHelper::errors', true), get_class($this)), E_USER_NOTICE);
		$objects = func_get_args();
		if (!count($objects)) {
			return false;
		}

		$errors = array();
		foreach ($objects as $object) {
			$errors = array_merge($errors, $object->invalidFields($object->data));
		}
		return $this->validationErrors = (count($errors) ? $errors : false);
	}
/**
 * Returns a formatted error message for given FORM field, NULL if no errors.
 *
 * @deprecated 1.2.0.5147
 * @see FormHelper::error
 */
	function tagErrorMsg($field, $text) {
		trigger_error(sprintf(__('Method tagErrorMsg() is deprecated in %s: see FormHelper::error', true), get_class($this)), E_USER_NOTICE);
		$error = 1;
		$this->setEntity($field);
		if ($error == $this->tagIsInvalid()) {
			return sprintf('<div class="error-message">%s</div>', is_array($text) ? (empty($text[$error - 1]) ? 'Error in field' : $text[$error - 1]) : $text);
		} else {
			return null;
		}
	}
/*
 * @codeCoverageIgnoreEnd
 */
}
?>