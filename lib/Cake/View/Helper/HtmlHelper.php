<?php
/**
 * Html Helper class file.
 *
 * Simplifies the construction of HTML elements.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Helper
 * @since         CakePHP(tm) v 0.9.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppHelper', 'View/Helper');

/**
 * Html Helper class for easy use of HTML widgets.
 *
 * HtmlHelper encloses all methods needed while working with HTML pages.
 *
 * @package       Cake.View.Helper
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html
 */
class HtmlHelper extends AppHelper {
/**
 * html tags used by this helper.
 *
 * @var array
 */
	protected $_tags = array(
		'meta' => '<meta%s/>',
		'metalink' => '<link href="%s"%s/>',
		'link' => '<a href="%s"%s>%s</a>',
		'mailto' => '<a href="mailto:%s" %s>%s</a>',
		'form' => '<form action="%s"%s>',
		'formend' => '</form>',
		'input' => '<input name="%s"%s/>',
		'textarea' => '<textarea name="%s"%s>%s</textarea>',
		'hidden' => '<input type="hidden" name="%s"%s/>',
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
 * Minimized attributes
 *
 * @var array
 */
	protected $_minimizedAttributes = array(
		'compact', 'checked', 'declare', 'readonly', 'disabled', 'selected',
		'defer', 'ismap', 'nohref', 'noshade', 'nowrap', 'multiple', 'noresize'
	);

/**
 * Format to attribute
 *
 * @var string
 */
	protected $_attributeFormat = '%s="%s"';

/**
 * Format to attribute
 *
 * @var string
 */
	protected $_minimizedAttributeFormat = '%s="%s"';

/**
 * Breadcrumbs.
 *
 * @var array
 */
	protected $_crumbs = array();

/**
 * Names of script files that have been included once
 *
 * @var array
 */
	protected $_includedScripts = array();
/**
 * Options for the currently opened script block buffer if any.
 *
 * @var array
 */
	protected $_scriptBlockOptions = array();
/**
 * Document type definitions
 *
 * @var array
 */
	protected $_docTypes = array(
		'html4-strict'  => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
		'html4-trans'  => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
		'html4-frame'  => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">',
		'html5' => '<!DOCTYPE html>',
		'xhtml-strict' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
		'xhtml-trans' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
		'xhtml-frame' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
		'xhtml11' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'
	);

/**
 * Constructor
 *
 * ### Settings
 *
 * - `configFile` A file containing an array of tags you wish to redefine.
 *
 * ### Customizing tag sets
 *
 * Using the `configFile` option you can redefine the tag HtmlHelper will use.
 * The file named should be compatible with HtmlHelper::loadConfig().
 *
 * @param View $View The View this helper is being attached to.
 * @param array $settings Configuration settings for the helper.
 */
	public function __construct(View $View, $settings = array()) {
		parent::__construct($View, $settings);
		if (!empty($settings['configFile'])) {
			$this->loadConfig($settings['configFile']);
		}
	}

/**
 * Adds a link to the breadcrumbs array.
 *
 * @param string $name Text for link
 * @param string $link URL for link (if empty it won't be a link)
 * @param mixed $options Link attributes e.g. array('id'=>'selected')
 * @return void
 * @see HtmlHelper::link() for details on $options that can be used.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#creating-breadcrumb-trails-with-htmlhelper
 */
	public function addCrumb($name, $link = null, $options = null) {
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
 *  - html5: HTML5.
 *  - xhtml-strict: XHTML1 Strict.
 *  - xhtml-trans: XHTML1 Transitional.
 *  - xhtml-frame: XHTML1 Frameset.
 *  - xhtml11: XHTML1.1.
 *
 * @param string $type Doctype to use.
 * @return string Doctype string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::docType
 */
	public function docType($type = 'xhtml-strict') {
		if (isset($this->_docTypes[$type])) {
			return $this->_docTypes[$type];
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::meta
 */
	public function meta($type, $url = null, $options = array()) {
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
				$out = sprintf($this->_tags['metalink'], $options['link'], $this->_parseAttributes($options, array('link'), ' ', ' '));
				$options['rel'] = 'shortcut icon';
			} else {
				$options['link'] = $this->url($options['link'], true);
			}
			$out .= sprintf($this->_tags['metalink'], $options['link'], $this->_parseAttributes($options, array('link'), ' ', ' '));
		} else {
			$out = sprintf($this->_tags['meta'], $this->_parseAttributes($options, array('type'), ' ', ' '));
		}

		if ($inline) {
			return $out;
		} else {
			$this->_View->addScript($out);
		}
	}

/**
 * Returns a charset META-tag.
 *
 * @param string $charset The character set to be used in the meta tag. If empty,
 *  The App.encoding value will be used. Example: "utf-8".
 * @return string A meta tag containing the specified character set.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::charset
 */
	public function charset($charset = null) {
		if (empty($charset)) {
			$charset = strtolower(Configure::read('App.encoding'));
		}
		return sprintf($this->_tags['charset'], (!empty($charset) ? $charset : 'utf-8'));
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
 * - `confirm` JavaScript confirmation message.
 *
 * @param string $title The content to be wrapped by <a> tags.
 * @param mixed $url Cake-relative URL or array of URL parameters, or external URL (starts with http://)
 * @param array $options Array of HTML attributes.
 * @param string $confirmMessage JavaScript confirmation message.
 * @return string An `<a />` element.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::link
 */
	public function link($title, $url = null, $options = array(), $confirmMessage = false) {
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
		return sprintf($this->_tags['link'], $url, $this->_parseAttributes($options), $title);
	}

/**
 * Creates a link element for CSS stylesheets.
 *
 * ### Usage
 *
 * Include one CSS file:
 *
 * `echo $this->Html->css('styles.css');`
 *
 * Include multiple CSS files:
 *
 * `echo $this->Html->css(array('one.css', 'two.css'));`
 *
 * Add the stylesheet to the `$scripts_for_layout` layout var:
 *
 * `$this->Html->css('styles.css', null, array('inline' => false));`
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::css
 */
	public function css($path, $rel = null, $options = array()) {
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

		if (strpos($path, '//') !== false) {
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
			$url = $this->assetTimestamp($this->webroot($path));

			if (Configure::read('Asset.filter.css')) {
				$pos = strpos($url, CSS_URL);
				if ($pos !== false) {
					$url = substr($url, 0, $pos) . 'ccss/' . substr($url, $pos + strlen(CSS_URL));
				}
			}
		}

		if ($rel == 'import') {
			$out = sprintf($this->_tags['style'], $this->_parseAttributes($options, array('inline'), '', ' '), '@import url(' . $url . ');');
		} else {
			if ($rel == null) {
				$rel = 'stylesheet';
			}
			$out = sprintf($this->_tags['css'], $rel, $url, $this->_parseAttributes($options, array('inline'), '', ' '));
		}

		if ($options['inline']) {
			return $out;
		} else {
			$this->_View->addScript($out);
		}
	}

/**
 * Returns one or many `<script>` tags depending on the number of scripts given.
 *
 * If the filename is prefixed with "/", the path will be relative to the base path of your
 * application.  Otherwise, the path will be relative to your JavaScript path, usually webroot/js.
 *
 *
 * ### Usage
 *
 * Include one script file:
 *
 * `echo $this->Html->script('styles.js');`
 *
 * Include multiple script files:
 *
 * `echo $this->Html->script(array('one.js', 'two.js'));`
 *
 * Add the script file to the `$scripts_for_layout` layout var:
 *
 * `$this->Html->script('styles.js', null, array('inline' => false));`
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::script
 */
	public function script($url, $options = array()) {
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
		if ($options['once'] && isset($this->_includedScripts[$url])) {
			return null;
		}
		$this->_includedScripts[$url] = true;

		if (strpos($url, '//') === false) {
			if ($url[0] !== '/') {
				$url = JS_URL . $url;
			}
			if (strpos($url, '?') === false && substr($url, -3) !== '.js') {
				$url .= '.js';
			}
			$url = $this->assetTimestamp($this->webroot($url));

			if (Configure::read('Asset.filter.js')) {
				$url = str_replace(JS_URL, 'cjs/', $url);
			}
		}
		$attributes = $this->_parseAttributes($options, array('inline', 'once'), ' ');
		$out = sprintf($this->_tags['javascriptlink'], $url, $attributes);

		if ($options['inline']) {
			return $out;
		} else {
			$this->_View->addScript($out);
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::scriptBlock
 */
	public function scriptBlock($script, $options = array()) {
		$options += array('safe' => true, 'inline' => true);
		if ($options['safe']) {
			$script  = "\n" . '//<![CDATA[' . "\n" . $script . "\n" . '//]]>' . "\n";
		}
		$inline = $options['inline'];
		unset($options['inline'], $options['safe']);
		$attributes = $this->_parseAttributes($options, ' ', ' ');
		if ($inline) {
			return sprintf($this->_tags['javascriptblock'], $attributes, $script);
		} else {
			$this->_View->addScript(sprintf($this->_tags['javascriptblock'], $attributes, $script));
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::scriptStart
 */
	public function scriptStart($options = array()) {
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::scriptEnd
 */
	public function scriptEnd() {
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::style
 */
	public function style($data, $oneline = true) {
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#creating-breadcrumb-trails-with-htmlhelper
 */
	public function getCrumbs($separator = '&raquo;', $startText = false) {
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
 * Returns breadcrumbs as a (x)html list
 *
 * This method uses HtmlHelper::tag() to generate list and its elements. Works
 * similiary to HtmlHelper::getCrumbs(), so it uses options which every
 * crumb was added with.
 *
 * @param array $options Array of html attributes to apply to the generated list elements.
 * @return string breadcrumbs html list
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#creating-breadcrumb-trails-with-htmlhelper
 */
	public function getCrumbList($options = array()) {
		if (!empty($this->_crumbs)) {
			$result = '';
			$crumbCount = count($this->_crumbs);
			$ulOptions = $options;
			foreach ($this->_crumbs as $which => $crumb) {
				$options = array();
				if (empty($crumb[1])) {
					$elementContent = $crumb[0];
				} else {
					$elementContent = $this->link($crumb[0], $crumb[1], $crumb[2]);
				}
				if ($which == 0) {
					$options['class'] = 'first';
				} elseif ($which == $crumbCount - 1) {
					$options['class'] = 'last';
				}
				$result .= $this->tag('li', $elementContent, $options);
			}
			return $this->tag('ul', $result, $ulOptions);
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::image
 */
	public function image($path, $options = array()) {
		if (is_array($path)) {
			$path = $this->url($path);
		} elseif (strpos($path, '://') === false) {
			if ($path[0] !== '/') {
				$path = IMAGES_URL . $path;
			}
			$path = $this->assetTimestamp($this->webroot($path));
		}

		if (!isset($options['alt'])) {
			$options['alt'] = '';
		}

		$url = false;
		if (!empty($options['url'])) {
			$url = $options['url'];
			unset($options['url']);
		}

		$image = sprintf($this->_tags['image'], $path, $this->_parseAttributes($options, null, '', ' '));

		if ($url) {
			return sprintf($this->_tags['link'], $this->url($url), null, $image);
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::tableHeaders
 */
	public function tableHeaders($names, $trOptions = null, $thOptions = null) {
		$out = array();
		foreach ($names as $arg) {
			$out[] = sprintf($this->_tags['tableheader'], $this->_parseAttributes($thOptions), $arg);
		}
		return sprintf($this->_tags['tablerow'], $this->_parseAttributes($trOptions), join(' ', $out));
	}

/**
 * Returns a formatted string of table rows (TR's with TD's in them).
 *
 * @param array $data Array of table data
 * @param array $oddTrOptions HTML options for odd TR elements if true useCount is used
 * @param array $evenTrOptions HTML options for even TR elements
 * @param boolean $useCount adds class "column-$i"
 * @param boolean $continueOddEven If false, will use a non-static $count variable,
 *    so that the odd/even count is reset to zero just for that call.
 * @return string Formatted HTML
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::tableCells
 */
	public function tableCells($data, $oddTrOptions = null, $evenTrOptions = null, $useCount = false, $continueOddEven = true) {
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
				$cellsOut[] = sprintf($this->_tags['tablecell'], $this->_parseAttributes($cellOptions), $cell);
			}
			$options = $this->_parseAttributes($count % 2 ? $oddTrOptions : $evenTrOptions);
			$out[] = sprintf($this->_tags['tablerow'], $options, implode(' ', $cellsOut));
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::tag
 */
	public function tag($name, $text = null, $options = array()) {
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
		return sprintf($this->_tags[$tag], $name, $this->_parseAttributes($options, null, ' ', ''), $text, $name);
	}

/**
 * Returns a formatted existent block of $tags
 *
 * @param string $tag Tag name
 * @return string Formatted block
 */
	public function useTag($tag) {
		if (!isset($this->_tags[$tag])) {
			return '';
		}
		$args = func_get_args();
		array_shift($args);
		foreach ($args as &$arg) {
			if (is_array($arg)) {
				$arg = $this->_parseAttributes($arg, null, ' ', '');
			}
		}
		return vsprintf($this->_tags[$tag], $args);
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::div
 */
	public function div($class = null, $text = null, $options = array()) {
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::para
 */
	public function para($class, $text, $options = array()) {
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
		return sprintf($this->_tags[$tag], $this->_parseAttributes($options, null, ' ', ''), $text);
	}

/**
 * Build a nested list (UL/OL) out of an associative array.
 *
 * @param array $list Set of elements to list
 * @param array $options Additional HTML attributes of the list (ol/ul) tag or if ul/ol use that as tag
 * @param array $itemOptions Additional HTML attributes of the list item (LI) tag
 * @param string $tag Type of list tag to use (ol/ul)
 * @return string The nested list
 */
	public function nestedList($list, $options = array(), $itemOptions = array(), $tag = 'ul') {
		if (is_string($options)) {
			$tag = $options;
			$options = array();
		}
		$items = $this->_nestedListItem($list, $options, $itemOptions, $tag);
		return sprintf($this->_tags[$tag], $this->_parseAttributes($options, null, ' ', ''), $items);
	}

/**
 * Internal function to build a nested list (UL/OL) out of an associative array.
 *
 * @param array $items Set of elements to list
 * @param array $options Additional HTML attributes of the list (ol/ul) tag
 * @param array $itemOptions Additional HTML attributes of the list item (LI) tag
 * @param string $tag Type of list tag to use (ol/ul)
 * @return string The nested list element
 * @see HtmlHelper::nestedList()
 */
	protected function _nestedListItem($items, $options, $itemOptions, $tag) {
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
			$out .= sprintf($this->_tags['li'], $this->_parseAttributes($itemOptions, array('even', 'odd'), ' ', ''), $item);
			$index++;
		}
		return $out;
	}

/**
 * Load Html tag configuration.
 *
 * Loads a file from APP/Config that contains tag data.  By default the file is expected
 * to be compatible with PhpReader:
 *
 * `$this->Html->loadConfig('tags.php');`
 *
 * tags.php could look like:
 *
 * {{{
 * $tags = array(
 *		'meta' => '<meta %s>'
 * );
 * }}}
 *
 * If you wish to store tag definitions in another format you can give an array
 * containing the file name, and reader class name:
 *
 * `$this->Html->loadConfig(array('tags.ini', 'ini'));`
 *
 * Its expected that the `tags` index will exist from any configuration file that is read.
 * You can also specify the path to read the configuration file from, if APP/Config is not
 * where the file is.
 *
 * `$this->Html->loadConfig('tags.php', APP . 'Lib' . DS);`
 *
 * Configuration files can define the following sections:
 *
 * - `tags` The tags to replace.
 * - `minimizedAttributes` The attributes that are represented like `disabled="disabled"`
 * - `docTypes` Additional doctypes to use.
 * - `attributeFormat` Format for long attributes e.g. `'%s="%s"'`
 * - `minimizedAttributeFormat` Format for minimized attributes e.g. `'%s="%s"'`
 *
 * @param mixed $configFile String with the config file (load using PhpReader) or an array with file and reader name
 * @param string $path Path with config file
 * @return mixed False to error or loaded configs
 * @throws ConfigureException
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#changing-the-tags-output-by-htmlhelper
 */
	public function loadConfig($configFile, $path = null) {
		if (!$path) {
			$path = APP . 'Config' . DS;
		}
		$file = null;
		$reader = 'php';

		if (!is_array($configFile)) {
			$file = $configFile;
		} elseif (isset($configFile[0])) {
			$file = $configFile[0];
			if (isset($configFile[1])) {
				$reader = $configFile[1];
			}
		} else {
			throw new ConfigureException(__d('cake_dev', 'Cannot load the configuration file. Wrong "configFile" configuration.'));
		}

		$readerClass = Inflector::camelize($reader) . 'Reader';
		App::uses($readerClass, 'Configure');
		if (!class_exists($readerClass)) {
			throw new ConfigureException(__d('cake_dev', 'Cannot load the configuration file. Unknown reader.'));
		}

		$readerObj = new $readerClass($path);
		$configs = $readerObj->read($file);
		if (isset($configs['tags']) && is_array($configs['tags'])) {
			$this->_tags = array_merge($this->_tags, $configs['tags']);
		}
		if (isset($configs['minimizedAttributes']) && is_array($configs['minimizedAttributes'])) {
			$this->_minimizedAttributes = array_merge($this->_minimizedAttributes, $configs['minimizedAttributes']);
		}
		if (isset($configs['docTypes']) && is_array($configs['docTypes'])) {
			$this->_docTypes = array_merge($this->_docTypes, $configs['docTypes']);
		}
		if (isset($configs['attributeFormat'])) {
			$this->_attributeFormat = $configs['attributeFormat'];
		}
		if (isset($configs['minimizedAttributeFormat'])) {
			$this->_minimizedAttributeFormat = $configs['minimizedAttributeFormat'];
		}
		return $configs;
	}

/**
 * Returns a space-delimited string with items of the $options array. If a
 * key of $options array happens to be one of:
 *
 * - 'compact'
 * - 'checked'
 * - 'declare'
 * - 'readonly'
 * - 'disabled'
 * - 'selected'
 * - 'defer'
 * - 'ismap'
 * - 'nohref'
 * - 'noshade'
 * - 'nowrap'
 * - 'multiple'
 * - 'noresize'
 *
 * And its value is one of:
 *
 * - '1' (string)
 * - 1 (integer)
 * - true (boolean)
 * - 'true' (string)
 *
 * Then the value will be reset to be identical with key's name.
 * If the value is not one of these 3, the parameter is not output.
 *
 * 'escape' is a special option in that it controls the conversion of
 *  attributes to their html-entity encoded equivalents.  Set to false to disable html-encoding.
 *
 * If value for any option key is set to `null` or `false`, that option will be excluded from output.
 *
 * @param array $options Array of options.
 * @param array $exclude Array of options to be excluded, the options here will not be part of the return.
 * @param string $insertBefore String to be inserted before options.
 * @param string $insertAfter String to be inserted after options.
 * @return string Composed attributes.
 */
	protected function _parseAttributes($options, $exclude = null, $insertBefore = ' ', $insertAfter = null) {
		if (is_array($options)) {
			$options = array_merge(array('escape' => true), $options);

			if (!is_array($exclude)) {
				$exclude = array();
			}
			$filtered = array_diff_key($options, array_merge(array_flip($exclude), array('escape' => true)));
			$escape = $options['escape'];
			$attributes = array();

			foreach ($filtered as $key => $value) {
				if ($value !== false && $value !== null) {
					$attributes[] = $this->_formatAttribute($key, $value, $escape);
				}
			}
			$out = implode(' ', $attributes);
		} else {
			$out = $options;
		}
		return $out ? $insertBefore . $out . $insertAfter : '';
	}

/**
 * Formats an individual attribute, and returns the string value of the composed attribute.
 * Works with minimized attributes that have the same value as their name such as 'disabled' and 'checked'
 *
 * @param string $key The name of the attribute to create
 * @param string $value The value of the attribute to create.
 * @param boolean $escape Define if the value must be escaped
 * @return string The composed attribute.
 */
	protected function _formatAttribute($key, $value, $escape = true) {
		$attribute = '';
		if (is_array($value)) {
			$value = '';
		}

		if (is_numeric($key)) {
			$attribute = sprintf($this->_minimizedAttributeFormat, $value, $value);
		} elseif (in_array($key, $this->_minimizedAttributes)) {
			if ($value === 1 || $value === true || $value === 'true' || $value === '1' || $value == $key) {
				$attribute = sprintf($this->_minimizedAttributeFormat, $key, $key);
			}
		} else {
			$attribute = sprintf($this->_attributeFormat, $key, ($escape ? h($value) : $value));
		}
		return $attribute;
	}

}
