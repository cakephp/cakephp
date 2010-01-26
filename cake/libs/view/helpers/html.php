<?php
/**
 * Html Helper class file.
 *
 * Simplifies the construction of HTML elements.
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
 * @subpackage    cake.cake.libs.view.helpers
 * @since         CakePHP(tm) v 0.9.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
/**
 * Html Helper class for easy use of HTML widgets.
 *
 * HtmlHelper encloses all methods needed while working with HTML pages.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.view.helpers
 */
class HtmlHelper extends AppHelper {
/**
 * html tags used by this helper.
 *
 * @var array
 * @access public
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
		'submit' => '<input %s/>',
		'submitimage' => '<input type="image" src="%s" %s/>',
		'button' => '<button type="%s"%s>%s</button>',
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
		'error' => '<div%s>%s</div>',
		'javascriptblock' => '<script type="text/javascript"%s>%s</script>',
		'javascriptstart' => '<script type="text/javascript">',
		'javascriptlink' => '<script type="text/javascript" src="%s"%s></script>',
		'javascriptend' => '</script>'
	);

/**
 * Breadcrumbs.
 *
 * @var array
 * @access protected
 */
	var $_crumbs = array();

/**
 * Names of script files that have been included once
 *
 * @var array
 * @access private
 */
	var $__includedScripts = array();
/**
 * Options for the currently opened script block buffer if any.
 *
 * @var array
 * @access protected
 */
	var $_scriptBlockOptions = array();
/**
 * Document type definitions
 *
 * @var array
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
 * @return void
 * @see HtmlHelper::link() for details on $options that can be used.
 * @access public
 */
	function addCrumb($name, $link = null, $options = null) {
		$this->_crumbs[] = array($name, $link, $options);
	}

/**
 * Returns a doctype string.
 *
 * Possible doctypes:
 *
 *  - html4-strict:  HTML4 Strict.
 *  - html4-trans:  HTML4 Transitional.
 *  - html4-frame:  HTML4 Frameset.
 *  - xhtml-strict: XHTML1 Strict.
 *  - xhtml-trans: XHTML1 Transitional.
 *  - xhtml-frame: XHTML1 Frameset.
 *  - xhtml11: XHTML1.1.
 *
 * @param string $type Doctype to use.
 * @return string Doctype string
 * @access public
 */
	function docType($type = 'xhtml-strict') {
		if (isset($this->__docTypes[$type])) {
			return $this->__docTypes[$type];
		}
		return null;
	}

/**
 * Creates a link to an external resource and handles basic meta tags
 *
 * ### Options
 *
 * - `inline` Whether or not the link element should be output inline, or in scripts_for_layout.
 *
 * @param string $type The title of the external resource
 * @param mixed $url The address of the external resource or string for content attribute
 * @param array $options Other attributes for the generated tag. If the type attribute is html,
 *    rss, atom, or icon, the mime-type is returned.
 * @return string A completed `<link />` element.
 * @access public
 */
	function meta($type, $url = null, $options = array()) {
		$inline = isset($options['inline']) ? $options['inline'] : true;
		unset($options['inline']);

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
			} elseif (!isset($options['type']) && $url !== null) {
				if (is_array($url) && isset($url['ext'])) {
					$type = $types[$url['ext']];
				} else {
					$type = $types['rss'];
				}
			} elseif (isset($options['type']) && isset($types[$options['type']])) {
				$type = $types[$options['type']];
				unset($options['type']);
			} else {
				$type = array();
			}
		} elseif ($url !== null) {
			$inline = $url;
		}
		$options = array_merge($type, $options);
		$out = null;

		if (isset($options['link'])) {
			if (isset($options['rel']) && $options['rel'] === 'icon') {
				$out = sprintf($this->tags['metalink'], $options['link'], $this->_parseAttributes($options, array('link'), ' ', ' '));
				$options['rel'] = 'shortcut icon';
			} else {
				$options['link'] = $this->url($options['link'], true);
			}
			$out .= sprintf($this->tags['metalink'], $options['link'], $this->_parseAttributes($options, array('link'), ' ', ' '));
		} else {
			$out = sprintf($this->tags['meta'], $this->_parseAttributes($options, array('type')));
		}

		if ($inline) {
			return $out;
		} else {
			$view =& ClassRegistry::getObject('view');
			$view->addScript($out);
		}
	}

/**
 * Returns a charset META-tag.
 *
 * @param string $charset The character set to be used in the meta tag. If empty,
 *  The App.encoding value will be used. Example: "utf-8".
 * @return string A meta tag containing the specified character set.
 * @access public
 */
	function charset($charset = null) {
		if (empty($charset)) {
			$charset = strtolower(Configure::read('App.encoding'));
		}
		return sprintf($this->tags['charset'], (!empty($charset) ? $charset : 'utf-8'));
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
 * ### Options
 *
 * - `escape` Set to false to disable escaping of title and attributes.
 *
 * @param string $title The content to be wrapped by <a> tags.
 * @param mixed $url Cake-relative URL or array of URL parameters, or external URL (starts with http://)
 * @param array $options Array of HTML attributes.
 * @param string $confirmMessage JavaScript confirmation message.
 * @return string An `<a />` element.
 * @access public
 */
	function link($title, $url = null, $options = array(), $confirmMessage = false) {
		$escapeTitle = true;
		if ($url !== null) {
			$url = $this->url($url);
		} else {
			$url = $this->url($title);
			$title = $url;
			$escapeTitle = false;
		}

		if (isset($options['escape'])) {
			$escapeTitle = $options['escape'];
		}

		if ($escapeTitle === true) {
			$title = h($title);
		} elseif (is_string($escapeTitle)) {
			$title = htmlentities($title, ENT_QUOTES, $escapeTitle);
		}

		if (!empty($options['confirm'])) {
			$confirmMessage = $options['confirm'];
			unset($options['confirm']);
		}
		if ($confirmMessage) {
			$confirmMessage = str_replace("'", "\'", $confirmMessage);
			$confirmMessage = str_replace('"', '\"', $confirmMessage);
			$options['onclick'] = "return confirm('{$confirmMessage}');";
		} elseif (isset($options['default']) && $options['default'] == false) {
			if (isset($options['onclick'])) {
				$options['onclick'] .= ' event.returnValue = false; return false;';
			} else {
				$options['onclick'] = 'event.returnValue = false; return false;';
			}
			unset($options['default']);
		}
		return sprintf($this->tags['link'], $url, $this->_parseAttributes($options), $title);
	}

/**
 * Creates a link element for CSS stylesheets.
 *
 * ### Options
 *
 * - `inline` If set to false, the generated tag appears in the head tag of the layout. Defaults to true
 *
 * @param mixed $path The name of a CSS style sheet or an array containing names of
 *   CSS stylesheets. If `$path` is prefixed with '/', the path will be relative to the webroot
 *   of your application. Otherwise, the path will be relative to your CSS path, usually webroot/css.
 * @param string $rel Rel attribute. Defaults to "stylesheet". If equal to 'import' the stylesheet will be imported.
 * @param array $options Array of HTML attributes.
 * @return string CSS <link /> or <style /> tag, depending on the type of link.
 * @access public
 */
	function css($path, $rel = null, $options = array()) {
		$options += array('inline' => true);
		if (is_array($path)) {
			$out = '';
			foreach ($path as $i) {
				$out .= "\n\t" . $this->css($i, $rel, $options);
			}
			if ($options['inline'])  {
				return $out . "\n";
			}
			return;
		}

		if (strpos($path, '://') !== false) {
			$url = $path;
		} else {
			if ($path[0] !== '/') {
				$path = CSS_URL . $path;
			}

			if (strpos($path, '?') === false) {
				if (substr($path, -4) !== '.css') {
					$path .= '.css';
				}
			}
			$url = $this->webroot($this->assetTimestamp($path));

			if (Configure::read('Asset.filter.css')) {
				$pos = strpos($url, CSS_URL);
				if ($pos !== false) {
					$url = substr($url, 0, $pos) . 'ccss/' . substr($url, $pos + strlen(CSS_URL));
				}
			}
		}

		if ($rel == 'import') {
			$out = sprintf($this->tags['style'], $this->_parseAttributes($options, array('inline'), '', ' '), '@import url(' . $url . ');');
		} else {
			if ($rel == null) {
				$rel = 'stylesheet';
			}
			$out = sprintf($this->tags['css'], $rel, $url, $this->_parseAttributes($options, array('inline'), '', ' '));
		}

		if ($options['inline']) {
			return $out;
		} else {
			$view =& ClassRegistry::getObject('view');
			$view->addScript($out);
		}
	}

/**
 * Returns one or many `<script>` tags depending on the number of scripts given.
 *
 * If the filename is prefixed with "/", the path will be relative to the base path of your
 * application.  Otherwise, the path will be relative to your JavaScript path, usually webroot/js.
 *
 * Can include one or many Javascript files.
 *
 * ### Options
 *
 * - `inline` - Whether script should be output inline or into scripts_for_layout.
 * - `once` - Whether or not the script should be checked for uniqueness. If true scripts will only be
 *   included once, use false to allow the same script to be included more than once per request.
 *
 * @param mixed $url String or array of javascript files to include
 * @param mixed $options Array of options, and html attributes see above. If boolean sets $options['inline'] = value
 * @return mixed String of `<script />` tags or null if $inline is false or if $once is true and the file has been
 *   included before.
 * @access public
 */
	function script($url, $options = array()) {
		if (is_bool($options)) {
			list($inline, $options) = array($options, array());
			$options['inline'] = $inline;
		}
		$options = array_merge(array('inline' => true, 'once' => true), $options);
		if (is_array($url)) {
			$out = '';
			foreach ($url as $i) {
				$out .= "\n\t" . $this->script($i, $options);
			}
			if ($options['inline'])  {
				return $out . "\n";
			}
			return null;
		}
		if ($options['once'] && isset($this->__includedScripts[$url])) {
			return null;
		}
		$this->__includedScripts[$url] = true;

		if (strpos($url, '://') === false) {
			if ($url[0] !== '/') {
				$url = JS_URL . $url;
			}
			if (strpos($url, '?') === false && strpos($url, '.js') === false) {
				$url .= '.js';
			}
			$url = $this->webroot($this->assetTimestamp($url));

			if (Configure::read('Asset.filter.js')) {
				$url = str_replace(JS_URL, 'cjs/', $url);
			}
		}
		$attributes = $this->_parseAttributes($options, array('inline', 'once'), ' ');
		$out = sprintf($this->tags['javascriptlink'], $url, $attributes);

		if ($options['inline']) {
			return $out;
		} else {
			$view =& ClassRegistry::getObject('view');
			$view->addScript($out);
		}
	}

/**
 * Wrap $script in a script tag.
 *
 * ### Options
 *
 * - `safe` (boolean) Whether or not the $script should be wrapped in <![CDATA[ ]]>
 * - `inline` (boolean) Whether or not the $script should be added to $scripts_for_layout or output inline
 *
 * @param string $script The script to wrap
 * @param array $options The options to use.
 * @return mixed string or null depending on the value of `$options['inline']`
 * @access public
 */
	function scriptBlock($script, $options = array()) {
		$options += array('safe' => true, 'inline' => true);
		if ($options['safe']) {
			$script  = "\n" . '//<![CDATA[' . "\n" . $script . "\n" . '//]]>' . "\n";
		}
		$inline = $options['inline'];
		unset($options['inline'], $options['safe']);
		$attributes = $this->_parseAttributes($options, ' ', ' ');
		if ($inline) {
			return sprintf($this->tags['javascriptblock'], $attributes, $script);
		} else {
			$view =& ClassRegistry::getObject('view');
			$view->addScript(sprintf($this->tags['javascriptblock'], $attributes, $script));
			return null;
		}
	}

/**
 * Begin a script block that captures output until HtmlHelper::scriptEnd()
 * is called. This capturing block will capture all output between the methods
 * and create a scriptBlock from it.
 *
 * ### Options
 *
 * - `safe` Whether the code block should contain a CDATA
 * - `inline` Should the generated script tag be output inline or in `$scripts_for_layout`
 *
 * @param array $options Options for the code block.
 * @return void
 * @access public
 */
	function scriptStart($options = array()) {
		$options += array('safe' => true, 'inline' => true);
		$this->_scriptBlockOptions = $options;
		ob_start();
		return null;
	}

/**
 * End a Buffered section of Javascript capturing.
 * Generates a script tag inline or in `$scripts_for_layout` depending on the settings
 * used when the scriptBlock was started
 *
 * @return mixed depending on the settings of scriptStart() either a script tag or null
 * @access public
 */
	function scriptEnd() {
		$buffer = ob_get_clean();
		$options = $this->_scriptBlockOptions;
		$this->_scriptBlockOptions = array();
		return $this->scriptBlock($buffer, $options);
	}

/**
 * Builds CSS style data from an array of CSS properties
 *
 * ### Usage:
 *
 * {{{
 * echo $html->style(array('margin' => '10px', 'padding' => '10px'), true);
 * 
 * // creates
 * 'margin:10px;padding:10px;'
 * }}}
 *
 * @param array $data Style data array, keys will be used as property names, values as property values.
 * @param boolean $oneline Whether or not the style block should be displayed on one line.
 * @return string CSS styling data
 * @access public
 */
	function style($data, $oneline = true) {
		if (!is_array($data)) {
			return $data;
		}
		$out = array();
		foreach ($data as $key=> $value) {
			$out[] = $key.':'.$value.';';
		}
		if ($oneline) {
			return join(' ', $out);
		}
		return implode("\n", $out);
	}

/**
 * Returns the breadcrumb trail as a sequence of &raquo;-separated links.
 *
 * @param string $separator Text to separate crumbs.
 * @param string $startText This will be the first crumb, if false it defaults to first crumb in array
 * @return string Composed bread crumbs
 * @access public
 */
	function getCrumbs($separator = '&raquo;', $startText = false) {
		if (!empty($this->_crumbs)) {
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
			return join($separator, $out);
		} else {
			return null;
		}
	}

/**
 * Creates a formatted IMG element. If `$options['url']` is provided, an image link will be
 * generated with the link pointed at `$options['url']`.  This method will set an empty
 * alt attribute if one is not supplied.
 *
 * ### Usage
 *
 * Create a regular image:
 *
 * `echo $html->image('cake_icon.png', array('alt' => 'CakePHP'));`
 *
 * Create an image link:
 *
 * `echo $html->image('cake_icon.png', array('alt' => 'CakePHP', 'url' => 'http://cakephp.org'));`
 *
 * @param string $path Path to the image file, relative to the app/webroot/img/ directory.
 * @param array $options Array of HTML attributes.
 * @return string completed img tag
 * @access public
 */
	function image($path, $options = array()) {
		if (is_array($path)) {
			$path = $this->url($path);
		} elseif (strpos($path, '://') === false) {
			if ($path[0] !== '/') {
				$path = IMAGES_URL . $path;
			}
			$path = $this->webroot($this->assetTimestamp($path));
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
			return sprintf($this->tags['link'], $this->url($url), null, $image);
		}
		return $image;
	}

/**
 * Returns a row of formatted and named TABLE headers.
 *
 * @param array $names Array of tablenames.
 * @param array $trOptions HTML options for TR elements.
 * @param array $thOptions HTML options for TH elements.
 * @return string Completed table headers
 * @access public
 */
	function tableHeaders($names, $trOptions = null, $thOptions = null) {
		$out = array();
		foreach ($names as $arg) {
			$out[] = sprintf($this->tags['tableheader'], $this->_parseAttributes($thOptions), $arg);
		}
		return sprintf($this->tags['tablerow'], $this->_parseAttributes($trOptions), join(' ', $out));
	}

/**
 * Returns a formatted string of table rows (TR's with TD's in them).
 *
 * @param array $data Array of table data
 * @param array $oddTrOptions HTML options for odd TR elements if true useCount is used
 * @param array $evenTrOptions HTML options for even TR elements
 * @param bool $useCount adds class "column-$i"
 * @param bool $continueOddEven If false, will use a non-static $count variable,
 *    so that the odd/even count is reset to zero just for that call.
 * @return string Formatted HTML
 * @access public
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
			$out[] = sprintf($this->tags['tablerow'], $options, implode(' ', $cellsOut));
		}
		return implode("\n", $out);
	}

/**
 * Returns a formatted block tag, i.e DIV, SPAN, P.
 *
 * ### Options
 *
 * - `escape` Whether or not the contents should be html_entity escaped.
 *
 * @param string $name Tag name.
 * @param string $text String content that will appear inside the div element.
 *   If null, only a start tag will be printed
 * @param array $options Additional HTML attributes of the DIV tag, see above.
 * @return string The formatted tag element
 * @access public
 */
	function tag($name, $text = null, $options = array()) {
		if (is_array($options) && isset($options['escape']) && $options['escape']) {
			$text = h($text);
			unset($options['escape']);
		}
		if (!is_array($options)) {
			$options = array('class' => $options);
		}
		if ($text === null) {
			$tag = 'tagstart';
		} else {
			$tag = 'tag';
		}
		return sprintf($this->tags[$tag], $name, $this->_parseAttributes($options, null, ' ', ''), $text, $name);
	}

/**
 * Returns a formatted DIV tag for HTML FORMs.
 *
 * ### Options
 *
 * - `escape` Whether or not the contents should be html_entity escaped.
 *
 * @param string $class CSS class name of the div element.
 * @param string $text String content that will appear inside the div element.
 *   If null, only a start tag will be printed
 * @param array $options Additional HTML attributes of the DIV tag
 * @return string The formatted DIV element
 * @access public
 */
	function div($class = null, $text = null, $options = array()) {
		if (!empty($class)) {
			$options['class'] = $class;
		}
		return $this->tag('div', $text, $options);
	}

/**
 * Returns a formatted P tag.
 *
 * ### Options
 *
 * - `escape` Whether or not the contents should be html_entity escaped.
 *
 * @param string $class CSS class name of the p element.
 * @param string $text String content that will appear inside the p element.
 * @param array $options Additional HTML attributes of the P tag
 * @return string The formatted P element
 * @access public
 */
	function para($class, $text, $options = array()) {
		if (isset($options['escape'])) {
			$text = h($text);
		}
		if ($class != null && !empty($class)) {
			$options['class'] = $class;
		}
		if ($text === null) {
			$tag = 'parastart';
		} else {
			$tag = 'para';
		}
		return sprintf($this->tags[$tag], $this->_parseAttributes($options, null, ' ', ''), $text);
	}

/**
 * Build a nested list (UL/OL) out of an associative array.
 *
 * @param array $list Set of elements to list
 * @param array $options Additional HTML attributes of the list (ol/ul) tag or if ul/ol use that as tag
 * @param array $itemOptions Additional HTML attributes of the list item (LI) tag
 * @param string $tag Type of list tag to use (ol/ul)
 * @return string The nested list
 * @access public
 */
	function nestedList($list, $options = array(), $itemOptions = array(), $tag = 'ul') {
		if (is_string($options)) {
			$tag = $options;
			$options = array();
		}
		$items = $this->__nestedListItem($list, $options, $itemOptions, $tag);
		return sprintf($this->tags[$tag], $this->_parseAttributes($options, null, ' ', ''), $items);
	}

/**
 * Internal function to build a nested list (UL/OL) out of an associative array.
 *
 * @param array $list Set of elements to list
 * @param array $options Additional HTML attributes of the list (ol/ul) tag
 * @param array $itemOptions Additional HTML attributes of the list item (LI) tag
 * @param string $tag Type of list tag to use (ol/ul)
 * @return string The nested list element
 * @access private
 * @see HtmlHelper::nestedList()
 */
	function __nestedListItem($items, $options, $itemOptions, $tag) {
		$out = '';

		$index = 1;
		foreach ($items as $key => $item) {
			if (is_array($item)) {
				$item = $key . $this->nestedList($item, $options, $itemOptions, $tag);
			}
			if (isset($itemOptions['even']) && $index % 2 == 0) {
				$itemOptions['class'] = $itemOptions['even'];
			} else if (isset($itemOptions['odd']) && $index % 2 != 0) {
				$itemOptions['class'] = $itemOptions['odd'];
			}
			$out .= sprintf($this->tags['li'], $this->_parseAttributes($itemOptions, array('even', 'odd'), ' ', ''), $item);
			$index++;
		}
		return $out;
	}
}
?>