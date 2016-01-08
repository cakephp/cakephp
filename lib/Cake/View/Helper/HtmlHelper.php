<?php
/**
 * Html Helper class file.
 *
 * Simplifies the construction of HTML elements.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Helper
 * @since         CakePHP(tm) v 0.9.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppHelper', 'View/Helper');
App::uses('CakeResponse', 'Network');

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
 * Reference to the Response object
 *
 * @var CakeResponse
 */
	public $response;

/**
 * html tags used by this helper.
 *
 * @var array
 */
	protected $_tags = array(
		'meta' => '<meta%s/>',
		'metalink' => '<link href="%s"%s/>',
		'link' => '<a href="%s"%s>%s</a>',
		'mailto' => '<a href="mailto:%s"%s>%s</a>',
		'form' => '<form action="%s"%s>',
		'formwithoutaction' => '<form%s>',
		'formend' => '</form>',
		'input' => '<input name="%s"%s/>',
		'textarea' => '<textarea name="%s"%s>%s</textarea>',
		'hidden' => '<input type="hidden" name="%s"%s/>',
		'checkbox' => '<input type="checkbox" name="%s"%s/>',
		'checkboxmultiple' => '<input type="checkbox" name="%s[]"%s />',
		'radio' => '<input type="radio" name="%s" id="%s"%s />%s',
		'selectstart' => '<select name="%s"%s>',
		'selectmultiplestart' => '<select name="%s[]"%s>',
		'selectempty' => '<option value=""%s>&nbsp;</option>',
		'selectoption' => '<option value="%s"%s>%s</option>',
		'selectend' => '</select>',
		'optiongroup' => '<optgroup label="%s"%s>',
		'optiongroupend' => '</optgroup>',
		'checkboxmultiplestart' => '',
		'checkboxmultipleend' => '',
		'password' => '<input type="password" name="%s"%s/>',
		'file' => '<input type="file" name="%s"%s/>',
		'file_no_model' => '<input type="file" name="%s"%s/>',
		'submit' => '<input%s/>',
		'submitimage' => '<input type="image" src="%s"%s/>',
		'button' => '<button%s>%s</button>',
		'image' => '<img src="%s"%s/>',
		'tableheader' => '<th%s>%s</th>',
		'tableheaderrow' => '<tr%s>%s</tr>',
		'tablecell' => '<td%s>%s</td>',
		'tablerow' => '<tr%s>%s</tr>',
		'block' => '<div%s>%s</div>',
		'blockstart' => '<div%s>',
		'blockend' => '</div>',
		'hiddenblock' => '<div style="display:none;">%s</div>',
		'tag' => '<%s%s>%s</%s>',
		'tagstart' => '<%s%s>',
		'tagend' => '</%s>',
		'tagselfclosing' => '<%s%s/>',
		'para' => '<p%s>%s</p>',
		'parastart' => '<p%s>',
		'label' => '<label for="%s"%s>%s</label>',
		'fieldset' => '<fieldset%s>%s</fieldset>',
		'fieldsetstart' => '<fieldset><legend>%s</legend>',
		'fieldsetend' => '</fieldset>',
		'legend' => '<legend>%s</legend>',
		'css' => '<link rel="%s" type="text/css" href="%s"%s/>',
		'style' => '<style type="text/css"%s>%s</style>',
		'charset' => '<meta http-equiv="Content-Type" content="text/html; charset=%s" />',
		'ul' => '<ul%s>%s</ul>',
		'ol' => '<ol%s>%s</ol>',
		'li' => '<li%s>%s</li>',
		'error' => '<div%s>%s</div>',
		'javascriptblock' => '<script%s>%s</script>',
		'javascriptstart' => '<script>',
		'javascriptlink' => '<script type="text/javascript" src="%s"%s></script>',
		'javascriptend' => '</script>'
	);

/**
 * Breadcrumbs.
 *
 * @var array
 */
	protected $_crumbs = array();

/**
 * Names of script & css files that have been included once
 *
 * @var array
 */
	protected $_includedAssets = array();

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
		'html4-strict' => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
		'html4-trans' => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
		'html4-frame' => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">',
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
		if (is_object($this->_View->response)) {
			$this->response = $this->_View->response;
		} else {
			$this->response = new CakeResponse();
		}
		if (!empty($settings['configFile'])) {
			$this->loadConfig($settings['configFile']);
		}
	}

/**
 * Adds a link to the breadcrumbs array.
 *
 * @param string $name Text for link
 * @param string $link URL for link (if empty it won't be a link)
 * @param string|array $options Link attributes e.g. array('id' => 'selected')
 * @return $this
 * @see HtmlHelper::link() for details on $options that can be used.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#creating-breadcrumb-trails-with-htmlhelper
 */
	public function addCrumb($name, $link = null, $options = null) {
		$this->_crumbs[] = array($name, $link, $options);
		return $this;
	}

/**
 * Returns a doctype string.
 *
 * Possible doctypes:
 *
 *  - html4-strict:  HTML4 Strict.
 *  - html4-trans:  HTML4 Transitional.
 *  - html4-frame:  HTML4 Frameset.
 *  - html5: HTML5. Default value.
 *  - xhtml-strict: XHTML1 Strict.
 *  - xhtml-trans: XHTML1 Transitional.
 *  - xhtml-frame: XHTML1 Frameset.
 *  - xhtml11: XHTML1.1.
 *
 * @param string $type Doctype to use.
 * @return string|null Doctype string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::docType
 */
	public function docType($type = 'html5') {
		if (isset($this->_docTypes[$type])) {
			return $this->_docTypes[$type];
		}
		return null;
	}

/**
 * Creates a link to an external resource and handles basic meta tags
 *
 * Create a meta tag that is output inline:
 *
 * `$this->Html->meta('icon', 'favicon.ico');
 *
 * Append the meta tag to `$scripts_for_layout`:
 *
 * `$this->Html->meta('description', 'A great page', array('inline' => false));`
 *
 * Append the meta tag to custom view block:
 *
 * `$this->Html->meta('description', 'A great page', array('block' => 'metaTags'));`
 *
 * ### Options
 *
 * - `inline` Whether or not the link element should be output inline. Set to false to
 *   have the meta tag included in `$scripts_for_layout`, and appended to the 'meta' view block.
 * - `block` Choose a custom block to append the meta tag to. Using this option
 *   will override the inline option.
 *
 * @param string $type The title of the external resource
 * @param string|array $url The address of the external resource or string for content attribute
 * @param array $options Other attributes for the generated tag. If the type attribute is html,
 *    rss, atom, or icon, the mime-type is returned.
 * @return string A completed `<link />` element.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::meta
 */
	public function meta($type, $url = null, $options = array()) {
		$options += array('inline' => true, 'block' => null);
		if (!$options['inline'] && empty($options['block'])) {
			$options['block'] = __FUNCTION__;
		}
		unset($options['inline']);

		if (!is_array($type)) {
			$types = array(
				'rss' => array('type' => 'application/rss+xml', 'rel' => 'alternate', 'title' => $type, 'link' => $url),
				'atom' => array('type' => 'application/atom+xml', 'title' => $type, 'link' => $url),
				'icon' => array('type' => 'image/x-icon', 'rel' => 'icon', 'link' => $url),
				'keywords' => array('name' => 'keywords', 'content' => $url),
				'description' => array('name' => 'description', 'content' => $url),
			);

			if ($type === 'icon' && $url === null) {
				$types['icon']['link'] = 'favicon.ico';
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
		}

		$options += $type;
		$out = null;

		if (isset($options['link'])) {
			$options['link'] = $this->assetUrl($options['link']);
			if (isset($options['rel']) && $options['rel'] === 'icon') {
				$out = sprintf($this->_tags['metalink'], $options['link'], $this->_parseAttributes($options, array('block', 'link')));
				$options['rel'] = 'shortcut icon';
			}
			$out .= sprintf($this->_tags['metalink'], $options['link'], $this->_parseAttributes($options, array('block', 'link')));
		} else {
			$out = sprintf($this->_tags['meta'], $this->_parseAttributes($options, array('block', 'type')));
		}

		if (empty($options['block'])) {
			return $out;
		}
		$this->_View->append($options['block'], $out);
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
 * - `escapeTitle` Set to false to disable escaping of title. (Takes precedence over value of `escape`)
 * - `confirm` JavaScript confirmation message.
 *
 * @param string $title The content to be wrapped by <a> tags.
 * @param string|array $url Cake-relative URL or array of URL parameters, or external URL (starts with http://)
 * @param array $options Array of options and HTML attributes.
 * @param string $confirmMessage JavaScript confirmation message. This
 *   argument is deprecated as of 2.6. Use `confirm` key in $options instead.
 * @return string An `<a />` element.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::link
 */
	public function link($title, $url = null, $options = array(), $confirmMessage = false) {
		$escapeTitle = true;
		if ($url !== null) {
			$url = $this->url($url);
		} else {
			$url = $this->url($title);
			$title = htmlspecialchars_decode($url, ENT_QUOTES);
			$title = h(urldecode($title));
			$escapeTitle = false;
		}

		if (isset($options['escapeTitle'])) {
			$escapeTitle = $options['escapeTitle'];
			unset($options['escapeTitle']);
		} elseif (isset($options['escape'])) {
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
			$options['onclick'] = $this->_confirm($confirmMessage, 'return true;', 'return false;', $options);
		} elseif (isset($options['default']) && !$options['default']) {
			if (isset($options['onclick'])) {
				$options['onclick'] .= ' ';
			} else {
				$options['onclick'] = '';
			}
			$options['onclick'] .= 'event.returnValue = false; return false;';
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
 * `$this->Html->css('styles.css', array('inline' => false));`
 *
 * Add the stylesheet to a custom block:
 *
 * `$this->Html->css('styles.css', array('block' => 'layoutCss'));`
 *
 * ### Options
 *
 * - `inline` If set to false, the generated tag will be appended to the 'css' block,
 *   and included in the `$scripts_for_layout` layout variable. Defaults to true.
 * - `once` Whether or not the css file should be checked for uniqueness. If true css
 *   files  will only be included once, use false to allow the same
 *   css to be included more than once per request.
 * - `block` Set the name of the block link/style tag will be appended to.
 *   This overrides the `inline` option.
 * - `plugin` False value will prevent parsing path as a plugin
 * - `rel` Defaults to 'stylesheet'. If equal to 'import' the stylesheet will be imported.
 * - `fullBase` If true the URL will get a full address for the css file.
 *
 * @param string|array $path The name of a CSS style sheet or an array containing names of
 *   CSS stylesheets. If `$path` is prefixed with '/', the path will be relative to the webroot
 *   of your application. Otherwise, the path will be relative to your CSS path, usually webroot/css.
 * @param array $options Array of options and HTML arguments.
 * @return string CSS <link /> or <style /> tag, depending on the type of link.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::css
 */
	public function css($path, $options = array()) {
		if (!is_array($options)) {
			$rel = $options;
			$options = array();
			if ($rel) {
				$options['rel'] = $rel;
			}
			if (func_num_args() > 2) {
				$options = func_get_arg(2) + $options;
			}
			unset($rel);
		}

		$options += array(
			'block' => null,
			'inline' => true,
			'once' => false,
			'rel' => 'stylesheet'
		);
		if (!$options['inline'] && empty($options['block'])) {
			$options['block'] = __FUNCTION__;
		}
		unset($options['inline']);

		if (is_array($path)) {
			$out = '';
			foreach ($path as $i) {
				$out .= "\n\t" . $this->css($i, $options);
			}
			if (empty($options['block'])) {
				return $out . "\n";
			}
			return '';
		}

		if ($options['once'] && isset($this->_includedAssets[__METHOD__][$path])) {
			return '';
		}
		unset($options['once']);
		$this->_includedAssets[__METHOD__][$path] = true;

		if (strpos($path, '//') !== false) {
			$url = $path;
		} else {
			$url = $this->assetUrl($path, $options + array('pathPrefix' => Configure::read('App.cssBaseUrl'), 'ext' => '.css'));
			$options = array_diff_key($options, array('fullBase' => null, 'pathPrefix' => null));

			if (Configure::read('Asset.filter.css')) {
				$pos = strpos($url, Configure::read('App.cssBaseUrl'));
				if ($pos !== false) {
					$url = substr($url, 0, $pos) . 'ccss/' . substr($url, $pos + strlen(Configure::read('App.cssBaseUrl')));
				}
			}
		}

		if ($options['rel'] === 'import') {
			$out = sprintf(
				$this->_tags['style'],
				$this->_parseAttributes($options, array('rel', 'block')),
				'@import url(' . $url . ');'
			);
		} else {
			$out = sprintf(
				$this->_tags['css'],
				$options['rel'],
				$url,
				$this->_parseAttributes($options, array('rel', 'block'))
			);
		}

		if (empty($options['block'])) {
			return $out;
		}
		$this->_View->append($options['block'], $out);
	}

/**
 * Returns one or many `<script>` tags depending on the number of scripts given.
 *
 * If the filename is prefixed with "/", the path will be relative to the base path of your
 * application. Otherwise, the path will be relative to your JavaScript path, usually webroot/js.
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
 * `$this->Html->script('styles.js', array('inline' => false));`
 *
 * Add the script file to a custom block:
 *
 * `$this->Html->script('styles.js', array('block' => 'bodyScript'));`
 *
 * ### Options
 *
 * - `inline` Whether script should be output inline or into `$scripts_for_layout`. When set to false,
 *   the script tag will be appended to the 'script' view block as well as `$scripts_for_layout`.
 * - `block` The name of the block you want the script appended to. Leave undefined to output inline.
 *   Using this option will override the inline option.
 * - `once` Whether or not the script should be checked for uniqueness. If true scripts will only be
 *   included once, use false to allow the same script to be included more than once per request.
 * - `plugin` False value will prevent parsing path as a plugin
 * - `fullBase` If true the url will get a full address for the script file.
 *
 * @param string|array $url String or array of javascript files to include
 * @param array|bool $options Array of options, and html attributes see above. If boolean sets $options['inline'] = value
 * @return mixed String of `<script />` tags or null if $inline is false or if $once is true and the file has been
 *   included before.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::script
 */
	public function script($url, $options = array()) {
		if (is_bool($options)) {
			list($inline, $options) = array($options, array());
			$options['inline'] = $inline;
		}
		$options += array('block' => null, 'inline' => true, 'once' => true);
		if (!$options['inline'] && empty($options['block'])) {
			$options['block'] = __FUNCTION__;
		}
		unset($options['inline']);

		if (is_array($url)) {
			$out = '';
			foreach ($url as $i) {
				$out .= "\n\t" . $this->script($i, $options);
			}
			if (empty($options['block'])) {
				return $out . "\n";
			}
			return null;
		}
		if ($options['once'] && isset($this->_includedAssets[__METHOD__][$url])) {
			return null;
		}
		$this->_includedAssets[__METHOD__][$url] = true;

		if (strpos($url, '//') === false) {
			$url = $this->assetUrl($url, $options + array('pathPrefix' => Configure::read('App.jsBaseUrl'), 'ext' => '.js'));
			$options = array_diff_key($options, array('fullBase' => null, 'pathPrefix' => null));

			if (Configure::read('Asset.filter.js')) {
				$url = str_replace(Configure::read('App.jsBaseUrl'), 'cjs/', $url);
			}
		}
		$attributes = $this->_parseAttributes($options, array('block', 'once'));
		$out = sprintf($this->_tags['javascriptlink'], $url, $attributes);

		if (empty($options['block'])) {
			return $out;
		}
		$this->_View->append($options['block'], $out);
	}

/**
 * Wrap $script in a script tag.
 *
 * ### Options
 *
 * - `safe` (boolean) Whether or not the $script should be wrapped in <![CDATA[ ]]>
 * - `inline` (boolean) Whether or not the $script should be added to
 *   `$scripts_for_layout` / `script` block, or output inline. (Deprecated, use `block` instead)
 * - `block` Which block you want this script block appended to.
 *   Defaults to `script`.
 *
 * @param string $script The script to wrap
 * @param array $options The options to use. Options not listed above will be
 *    treated as HTML attributes.
 * @return mixed string or null depending on the value of `$options['block']`
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::scriptBlock
 */
	public function scriptBlock($script, $options = array()) {
		$options += array('type' => 'text/javascript', 'safe' => true, 'inline' => true);
		if ($options['safe']) {
			$script = "\n" . '//<![CDATA[' . "\n" . $script . "\n" . '//]]>' . "\n";
		}
		if (!$options['inline'] && empty($options['block'])) {
			$options['block'] = 'script';
		}
		unset($options['inline'], $options['safe']);

		$attributes = $this->_parseAttributes($options, array('block'));
		$out = sprintf($this->_tags['javascriptblock'], $attributes, $script);

		if (empty($options['block'])) {
			return $out;
		}
		$this->_View->append($options['block'], $out);
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
	}

/**
 * End a Buffered section of JavaScript capturing.
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
 * ```
 * echo $this->Html->style(array('margin' => '10px', 'padding' => '10px'), true);
 *
 * // creates
 * 'margin:10px;padding:10px;'
 * ```
 *
 * @param array $data Style data array, keys will be used as property names, values as property values.
 * @param bool $oneline Whether or not the style block should be displayed on one line.
 * @return string CSS styling data
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::style
 */
	public function style($data, $oneline = true) {
		if (!is_array($data)) {
			return $data;
		}
		$out = array();
		foreach ($data as $key => $value) {
			$out[] = $key . ':' . $value . ';';
		}
		if ($oneline) {
			return implode(' ', $out);
		}
		return implode("\n", $out);
	}

/**
 * Returns the breadcrumb trail as a sequence of &raquo;-separated links.
 *
 * If `$startText` is an array, the accepted keys are:
 *
 * - `text` Define the text/content for the link.
 * - `url` Define the target of the created link.
 *
 * All other keys will be passed to HtmlHelper::link() as the `$options` parameter.
 *
 * @param string $separator Text to separate crumbs.
 * @param string|array|bool $startText This will be the first crumb, if false it defaults to first crumb in array. Can
 *   also be an array, see above for details.
 * @return string|null Composed bread crumbs
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#creating-breadcrumb-trails-with-htmlhelper
 */
	public function getCrumbs($separator = '&raquo;', $startText = false) {
		$crumbs = $this->_prepareCrumbs($startText);
		if (!empty($crumbs)) {
			$out = array();
			foreach ($crumbs as $crumb) {
				if (!empty($crumb[1])) {
					$out[] = $this->link($crumb[0], $crumb[1], $crumb[2]);
				} else {
					$out[] = $crumb[0];
				}
			}
			return implode($separator, $out);
		}
		return null;
	}

/**
 * Returns breadcrumbs as a (x)html list
 *
 * This method uses HtmlHelper::tag() to generate list and its elements. Works
 * similar to HtmlHelper::getCrumbs(), so it uses options which every
 * crumb was added with.
 *
 * ### Options
 * - `separator` Separator content to insert in between breadcrumbs, defaults to ''
 * - `firstClass` Class for wrapper tag on the first breadcrumb, defaults to 'first'
 * - `lastClass` Class for wrapper tag on current active page, defaults to 'last'
 *
 * @param array $options Array of html attributes to apply to the generated list elements.
 * @param string|array|bool $startText This will be the first crumb, if false it defaults to first crumb in array. Can
 *   also be an array, see `HtmlHelper::getCrumbs` for details.
 * @return string|null breadcrumbs html list
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#creating-breadcrumb-trails-with-htmlhelper
 */
	public function getCrumbList($options = array(), $startText = false) {
		$defaults = array('firstClass' => 'first', 'lastClass' => 'last', 'separator' => '', 'escape' => true);
		$options = (array)$options + $defaults;
		$firstClass = $options['firstClass'];
		$lastClass = $options['lastClass'];
		$separator = $options['separator'];
		$escape = $options['escape'];
		unset($options['firstClass'], $options['lastClass'], $options['separator'], $options['escape']);

		$crumbs = $this->_prepareCrumbs($startText, $escape);
		if (empty($crumbs)) {
			return null;
		}

		$result = '';
		$crumbCount = count($crumbs);
		$ulOptions = $options;
		foreach ($crumbs as $which => $crumb) {
			$options = array();
			if (empty($crumb[1])) {
				$elementContent = $crumb[0];
			} else {
				$elementContent = $this->link($crumb[0], $crumb[1], $crumb[2]);
			}
			if (!$which && $firstClass !== false) {
				$options['class'] = $firstClass;
			} elseif ($which == $crumbCount - 1 && $lastClass !== false) {
				$options['class'] = $lastClass;
			}
			if (!empty($separator) && ($crumbCount - $which >= 2)) {
				$elementContent .= $separator;
			}
			$result .= $this->tag('li', $elementContent, $options);
		}
		return $this->tag('ul', $result, $ulOptions);
	}

/**
 * Prepends startText to crumbs array if set
 *
 * @param string $startText Text to prepend
 * @param bool $escape If the output should be escaped or not
 * @return array Crumb list including startText (if provided)
 */
	protected function _prepareCrumbs($startText, $escape = true) {
		$crumbs = $this->_crumbs;
		if ($startText) {
			if (!is_array($startText)) {
				$startText = array(
					'url' => '/',
					'text' => $startText
				);
			}
			$startText += array('url' => '/', 'text' => __d('cake', 'Home'));
			list($url, $text) = array($startText['url'], $startText['text']);
			unset($startText['url'], $startText['text']);
			array_unshift($crumbs, array($text, $url, $startText + array('escape' => $escape)));
		}
		return $crumbs;
	}

/**
 * Creates a formatted IMG element.
 *
 * This method will set an empty alt attribute if one is not supplied.
 *
 * ### Usage:
 *
 * Create a regular image:
 *
 * `echo $this->Html->image('cake_icon.png', array('alt' => 'CakePHP'));`
 *
 * Create an image link:
 *
 * `echo $this->Html->image('cake_icon.png', array('alt' => 'CakePHP', 'url' => 'http://cakephp.org'));`
 *
 * ### Options:
 *
 * - `url` If provided an image link will be generated and the link will point at
 *   `$options['url']`.
 * - `fullBase` If true the src attribute will get a full address for the image file.
 * - `plugin` False value will prevent parsing path as a plugin
 *
 * @param string $path Path to the image file, relative to the app/webroot/img/ directory.
 * @param array $options Array of HTML attributes. See above for special options.
 * @return string completed img tag
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::image
 */
	public function image($path, $options = array()) {
		$path = $this->assetUrl($path, $options + array('pathPrefix' => Configure::read('App.imageBaseUrl')));
		$options = array_diff_key($options, array('fullBase' => null, 'pathPrefix' => null));

		if (!isset($options['alt'])) {
			$options['alt'] = '';
		}

		$url = false;
		if (!empty($options['url'])) {
			$url = $options['url'];
			unset($options['url']);
		}

		$image = sprintf($this->_tags['image'], $path, $this->_parseAttributes($options));

		if ($url) {
			return sprintf($this->_tags['link'], $this->url($url), null, $image);
		}
		return $image;
	}

/**
 * Returns a row of formatted and named TABLE headers.
 *
 * @param array $names Array of tablenames. Each tablename also can be a key that points to an array with a set
 *     of attributes to its specific tag
 * @param array $trOptions HTML options for TR elements.
 * @param array $thOptions HTML options for TH elements.
 * @return string Completed table headers
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::tableHeaders
 */
	public function tableHeaders($names, $trOptions = null, $thOptions = null) {
		$out = array();
		foreach ($names as $arg) {
			if (!is_array($arg)) {
				$out[] = sprintf($this->_tags['tableheader'], $this->_parseAttributes($thOptions), $arg);
			} else {
				$out[] = sprintf($this->_tags['tableheader'], $this->_parseAttributes(current($arg)), key($arg));
			}
		}
		return sprintf($this->_tags['tablerow'], $this->_parseAttributes($trOptions), implode(' ', $out));
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
				}

				if ($useCount) {
					if (isset($cellOptions['class'])) {
						$cellOptions['class'] .= ' column-' . ++$i;
					} else {
						$cellOptions['class'] = 'column-' . ++$i;
					}
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
		if (empty($name)) {
			return $text;
		}
		if (isset($options['escape']) && $options['escape']) {
			$text = h($text);
			unset($options['escape']);
		}
		if ($text === null) {
			$tag = 'tagstart';
		} else {
			$tag = 'tag';
		}
		return sprintf($this->_tags[$tag], $name, $this->_parseAttributes($options), $text, $name);
	}

/**
 * Returns a formatted existent block of $tags
 *
 * @param string $tag Tag name
 * @return string Formatted block
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::useTag
 */
	public function useTag($tag) {
		if (!isset($this->_tags[$tag])) {
			return '';
		}
		$args = func_get_args();
		array_shift($args);
		foreach ($args as &$arg) {
			if (is_array($arg)) {
				$arg = $this->_parseAttributes($arg);
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
		if ($class && !empty($class)) {
			$options['class'] = $class;
		}
		$tag = 'para';
		if ($text === null) {
			$tag = 'parastart';
		}
		return sprintf($this->_tags[$tag], $this->_parseAttributes($options), $text);
	}

/**
 * Returns an audio/video element
 *
 * ### Usage
 *
 * Using an audio file:
 *
 * `echo $this->Html->media('audio.mp3', array('fullBase' => true));`
 *
 * Outputs:
 *
 * `<video src="http://www.somehost.com/files/audio.mp3">Fallback text</video>`
 *
 * Using a video file:
 *
 * `echo $this->Html->media('video.mp4', array('text' => 'Fallback text'));`
 *
 * Outputs:
 *
 * `<video src="/files/video.mp4">Fallback text</video>`
 *
 * Using multiple video files:
 *
 * ```
 * echo $this->Html->media(
 * 		array('video.mp4', array('src' => 'video.ogv', 'type' => "video/ogg; codecs='theora, vorbis'")),
 * 		array('tag' => 'video', 'autoplay')
 * );
 * ```
 *
 * Outputs:
 *
 * ```
 * <video autoplay="autoplay">
 * 		<source src="/files/video.mp4" type="video/mp4"/>
 * 		<source src="/files/video.ogv" type="video/ogv; codecs='theora, vorbis'"/>
 * </video>
 * ```
 *
 * ### Options
 *
 * - `tag` Type of media element to generate, either "audio" or "video".
 * 	If tag is not provided it's guessed based on file's mime type.
 * - `text` Text to include inside the audio/video tag
 * - `pathPrefix` Path prefix to use for relative URLs, defaults to 'files/'
 * - `fullBase` If provided the src attribute will get a full address including domain name
 *
 * @param string|array $path Path to the video file, relative to the webroot/{$options['pathPrefix']} directory.
 *  Or an array where each item itself can be a path string or an associate array containing keys `src` and `type`
 * @param array $options Array of HTML attributes, and special options above.
 * @return string Generated media element
 */
	public function media($path, $options = array()) {
		$options += array(
			'tag' => null,
			'pathPrefix' => 'files/',
			'text' => ''
		);

		if (!empty($options['tag'])) {
			$tag = $options['tag'];
		} else {
			$tag = null;
		}

		if (is_array($path)) {
			$sourceTags = '';
			foreach ($path as &$source) {
				if (is_string($source)) {
					$source = array(
						'src' => $source,
					);
				}
				if (!isset($source['type'])) {
					$ext = pathinfo($source['src'], PATHINFO_EXTENSION);
					$source['type'] = $this->response->getMimeType($ext);
				}
				$source['src'] = $this->assetUrl($source['src'], $options);
				$sourceTags .= $this->useTag('tagselfclosing', 'source', $source);
			}
			unset($source);
			$options['text'] = $sourceTags . $options['text'];
			unset($options['fullBase']);
		} else {
			if (empty($path) && !empty($options['src'])) {
				$path = $options['src'];
			}
			$options['src'] = $this->assetUrl($path, $options);
		}

		if ($tag === null) {
			if (is_array($path)) {
				$mimeType = $path[0]['type'];
			} else {
				$mimeType = $this->response->getMimeType(pathinfo($path, PATHINFO_EXTENSION));
			}
			if (preg_match('#^video/#', $mimeType)) {
				$tag = 'video';
			} else {
				$tag = 'audio';
			}
		}

		if (isset($options['poster'])) {
			$options['poster'] = $this->assetUrl($options['poster'], array('pathPrefix' => Configure::read('App.imageBaseUrl')) + $options);
		}
		$text = $options['text'];

		$options = array_diff_key($options, array(
			'tag' => null,
			'fullBase' => null,
			'pathPrefix' => null,
			'text' => null
		));
		return $this->tag($tag, $text, $options);
	}

/**
 * Build a nested list (UL/OL) out of an associative array.
 *
 * @param array $list Set of elements to list
 * @param array $options Additional HTML attributes of the list (ol/ul) tag or if ul/ol use that as tag
 * @param array $itemOptions Additional HTML attributes of the list item (LI) tag
 * @param string $tag Type of list tag to use (ol/ul)
 * @return string The nested list
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::nestedList
 */
	public function nestedList($list, $options = array(), $itemOptions = array(), $tag = 'ul') {
		if (is_string($options)) {
			$tag = $options;
			$options = array();
		}
		$items = $this->_nestedListItem($list, $options, $itemOptions, $tag);
		return sprintf($this->_tags[$tag], $this->_parseAttributes($options), $items);
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
			if (isset($itemOptions['even']) && $index % 2 === 0) {
				$itemOptions['class'] = $itemOptions['even'];
			} elseif (isset($itemOptions['odd']) && $index % 2 !== 0) {
				$itemOptions['class'] = $itemOptions['odd'];
			}
			$out .= sprintf($this->_tags['li'], $this->_parseAttributes($itemOptions, array('even', 'odd')), $item);
			$index++;
		}
		return $out;
	}

/**
 * Load Html tag configuration.
 *
 * Loads a file from APP/Config that contains tag data. By default the file is expected
 * to be compatible with PhpReader:
 *
 * `$this->Html->loadConfig('tags.php');`
 *
 * tags.php could look like:
 *
 * ```
 * $tags = array(
 *		'meta' => '<meta%s>'
 * );
 * ```
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
 * @param string|array $configFile String with the config file (load using PhpReader) or an array with file and reader name
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
			$this->_tags = $configs['tags'] + $this->_tags;
		}
		if (isset($configs['minimizedAttributes']) && is_array($configs['minimizedAttributes'])) {
			$this->_minimizedAttributes = $configs['minimizedAttributes'] + $this->_minimizedAttributes;
		}
		if (isset($configs['docTypes']) && is_array($configs['docTypes'])) {
			$this->_docTypes = $configs['docTypes'] + $this->_docTypes;
		}
		if (isset($configs['attributeFormat'])) {
			$this->_attributeFormat = $configs['attributeFormat'];
		}
		if (isset($configs['minimizedAttributeFormat'])) {
			$this->_minimizedAttributeFormat = $configs['minimizedAttributeFormat'];
		}
		return $configs;
	}

}
