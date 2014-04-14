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
 * @since         0.9.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Error;
use Cake\Network\Response;
use Cake\Utility\Inflector;
use Cake\View\Helper;
use Cake\View\Helper\StringTemplateTrait;
use Cake\View\View;

/**
 * Html Helper class for easy use of HTML widgets.
 *
 * HtmlHelper encloses all methods needed while working with HTML pages.
 *
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html
 */
class HtmlHelper extends Helper {

	use StringTemplateTrait;

/**
 * Reference to the Response object
 *
 * @var \Cake\Network\Response
 */
	public $response;

/**
 * Default config for this class
 *
 * @var array
 */
	protected $_defaultConfig = [
		'templates' => [
			'meta' => '<meta{{attrs}}/>',
			'metalink' => '<link href="{{url}}"{{attrs}}/>',
			'link' => '<a href="{{url}}"{{attrs}}>{{content}}</a>',
			'mailto' => '<a href="mailto:{{url}}"{{attrs}}>{{content}}</a>',
			'image' => '<img src="{{url}}"{{attrs}}/>',
			'tableheader' => '<th{{attrs}}>{{content}}</th>',
			'tableheaderrow' => '<tr{{attrs}}>{{content}}</tr>',
			'tablecell' => '<td{{attrs}}>{{content}}</td>',
			'tablerow' => '<tr{{attrs}}>{{content}}</tr>',
			'block' => '<div{{attrs}}>{{content}}</div>',
			'blockstart' => '<div{{attrs}}>',
			'blockend' => '</div>',
			'tag' => '<{{tag}}{{attrs}}>{{content}}</{{tag}}>',
			'tagstart' => '<{{tag}}{{attrs}}>',
			'tagend' => '</{{tag}}>',
			'tagselfclosing' => '<{{tag}}{{attrs}}/>',
			'para' => '<p{{attrs}}>{{content}}</p>',
			'parastart' => '<p{{attrs}}>',
			'css' => '<link rel="{{rel}}" href="{{url}}"{{attrs}}/>',
			'style' => '<style{{attrs}}>{{content}}</style>',
			'charset' => '<meta http-equiv="Content-Type" content="text/html; charset={{charset}}" />',
			'ul' => '<ul{{attrs}}>{{content}}</ul>',
			'ol' => '<ol{{attrs}}>{{content}}</ol>',
			'li' => '<li{{attrs}}>{{content}}</li>',
			'javascriptblock' => '<script{{attrs}}>{{content}}</script>',
			'javascriptstart' => '<script>',
			'javascriptlink' => '<script src="{{url}}"{{attrs}}></script>',
			'javascriptend' => '</script>'
		]
	];

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
 * - `templates` Either a filename to a config containing templates.
 *   Or an array of templates to load. See Cake\View\StringTemplate for
 *   template formatting.
 *
 * ### Customizing tag sets
 *
 * Using the `templates` option you can redefine the tag HtmlHelper will use.
 *
 * @param View $View The View this helper is being attached to.
 * @param array $config Configuration settings for the helper.
 */
	public function __construct(View $View, array $config = array()) {
		parent::__construct($View, $config);
		$this->response = $this->_View->response ?: new Response();
	}

/**
 * Adds a link to the breadcrumbs array.
 *
 * @param string $name Text for link
 * @param string $link URL for link (if empty it won't be a link)
 * @param string|array $options Link attributes e.g. array('id' => 'selected')
 * @return this HtmlHelper
 * @see HtmlHelper::link() for details on $options that can be used.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#creating-breadcrumb-trails-with-htmlhelper
 */
	public function addCrumb($name, $link = null, array $options = array()) {
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
 * @return string Doctype string
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
 * Append the meta tag to custom view block "meta":
 *
 * `$this->Html->meta('description', 'A great page', array('block' => true));`
 *
 * Append the meta tag to custom view block:
 *
 * `$this->Html->meta('description', 'A great page', array('block' => 'metaTags'));`
 *
 * ### Options
 *
 * - `block` - Set to true to append output to view block "meta" or provide
 *   custom block name.
 *
 * @param string $type The title of the external resource
 * @param string|array $content The address of the external resource or string for content attribute
 * @param array $options Other attributes for the generated tag. If the type attribute is html,
 *    rss, atom, or icon, the mime-type is returned.
 * @return string A completed `<link />` element.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::meta
 */
	public function meta($type, $content = null, array $options = array()) {
		$options += array('block' => null);

		$types = array(
			'rss' => array('type' => 'application/rss+xml', 'rel' => 'alternate', 'title' => $type, 'link' => $content),
			'atom' => array('type' => 'application/atom+xml', 'title' => $type, 'link' => $content),
			'icon' => array('type' => 'image/x-icon', 'rel' => 'icon', 'link' => $content),
			'keywords' => array('name' => 'keywords', 'content' => $content),
			'description' => array('name' => 'description', 'content' => $content),
			'robots' => array('name' => 'robots', 'content' => $content),
		);

		if ($type === 'icon' && $content === null) {
			$types['icon']['link'] = 'favicon.ico';
		}

		if (isset($types[$type])) {
			$type = $types[$type];
		} elseif (!isset($options['type']) && $content !== null) {
			if (is_array($content) && isset($content['ext'])) {
				$type = $types[$content['ext']];
			} else {
				$type = $types['rss'];
			}
		} elseif (isset($options['type']) && isset($types[$options['type']])) {
			$type = $types[$options['type']];
			unset($options['type']);
		} else {
			$type = array();
		}

		$options += $type;
		$out = null;

		if (isset($options['link'])) {
			$options['link'] = $this->assetUrl($options['link']);
			if (isset($options['rel']) && $options['rel'] === 'icon') {
				$out = $this->formatTemplate('metalink', [
					'url' => $options['link'],
					'attrs' => $this->templater()->formatAttributes($options, ['block', 'link'])
				]);
				$options['rel'] = 'shortcut icon';
			}
			$out .= $this->formatTemplate('metalink', [
				'url' => $options['link'],
				'attrs' => $this->templater()->formatAttributes($options, ['block', 'link'])
			]);
		} else {
			$out = $this->formatTemplate('meta', [
				'attrs' => $this->templater()->formatAttributes($options, ['block', 'type'])
			]);
		}

		if (empty($options['block'])) {
			return $out;
		}
		if ($options['block'] === true) {
			$options['block'] = __FUNCTION__;
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
		return $this->formatTemplate('charset', [
			'charset' => (!empty($charset) ? $charset : 'utf-8')
		]);
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
 * @param string $confirmMessage JavaScript confirmation message.
 * @return string An `<a />` element.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::link
 */
	public function link($title, $url = null, array $options = array(), $confirmMessage = false) {
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
		return $this->formatTemplate('link', [
			'url' => $url,
			'attrs' => $this->templater()->formatAttributes($options),
			'content' => $title
		]);
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
 * Add the stylesheet to view block "css":
 *
 * `$this->Html->css('styles.css', array('block' => true));`
 *
 * Add the stylesheet to a custom block:
 *
 * `$this->Html->css('styles.css', array('block' => 'layoutCss'));`
 *
 * ### Options
 *
 * - `block` Set to true to append output to view block "css" or provide
 *   custom block name.
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
	public function css($path, array $options = array()) {
		$options += array('block' => null, 'rel' => 'stylesheet');

		if (is_array($path)) {
			$out = '';
			foreach ($path as $i) {
				$out .= "\n\t" . $this->css($i, $options);
			}
			if (empty($options['block'])) {
				return $out . "\n";
			}
			return;
		}

		if (strpos($path, '//') !== false) {
			$url = $path;
		} else {
			$url = $this->assetUrl($path, $options + array('pathPrefix' => Configure::read('App.cssBaseUrl'), 'ext' => '.css'));
			$options = array_diff_key($options, array('fullBase' => null, 'pathPrefix' => null));
		}

		if ($options['rel'] === 'import') {
			$out = $this->formatTemplate('style', [
				'attrs' => $this->templater()->formatAttributes($options, ['rel', 'block']),
				'content' => '@import url(' . $url . ');',
			]);
		} else {
			$out = $this->formatTemplate('css', [
				'rel' => $options['rel'],
				'url' => $url,
				'attrs' => $this->templater()->formatAttributes($options, ['rel', 'block']),
			]);
		}

		if (empty($options['block'])) {
			return $out;
		}
		if ($options['block'] === true) {
			$options['block'] = __FUNCTION__;
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
 * Add the script file to a custom block:
 *
 * `$this->Html->script('styles.js', null, array('block' => 'bodyScript'));`
 *
 * ### Options
 *
 * - `block` Set to true to append output to view block "script" or provide
 *   custom block name.
 * - `once` Whether or not the script should be checked for uniqueness. If true scripts will only be
 *   included once, use false to allow the same script to be included more than once per request.
 * - `plugin` False value will prevent parsing path as a plugin
 * - `fullBase` If true the url will get a full address for the script file.
 *
 * @param string|array $url String or array of javascript files to include
 * @param array $options Array of options, and html attributes see above.
 * @return mixed String of `<script />` tags or null if block is specified in options
 *   or if $once is true and the file has been included before.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::script
 */
	public function script($url, array $options = array()) {
		$options = array_merge(array('block' => null, 'once' => true), $options);

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
		if ($options['once'] && isset($this->_includedScripts[$url])) {
			return null;
		}
		$this->_includedScripts[$url] = true;

		if (strpos($url, '//') === false) {
			$url = $this->assetUrl($url, $options + array('pathPrefix' => Configure::read('App.jsBaseUrl'), 'ext' => '.js'));
			$options = array_diff_key($options, array('fullBase' => null, 'pathPrefix' => null));
		}
		$out = $this->formatTemplate('javascriptlink', [
			'url' => $url,
			'attrs' => $this->templater()->formatAttributes($options, ['block', 'once']),
		]);

		if (empty($options['block'])) {
			return $out;
		}
		if ($options['block'] === true) {
			$options['block'] = __FUNCTION__;
		}
		$this->_View->append($options['block'], $out);
	}

/**
 * Wrap $script in a script tag.
 *
 * ### Options
 *
 * - `safe` (boolean) Whether or not the $script should be wrapped in <![CDATA[ ]]>
 * - `block` Set to true to append output to view block "script" or provide
 *   custom block name.
 *
 * @param string $script The script to wrap
 * @param array $options The options to use. Options not listed above will be
 *    treated as HTML attributes.
 * @return mixed string or null depending on the value of `$options['block']`
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::scriptBlock
 */
	public function scriptBlock($script, array $options = array()) {
		$options += array('safe' => true, 'block' => null);
		if ($options['safe']) {
			$script = "\n" . '//<![CDATA[' . "\n" . $script . "\n" . '//]]>' . "\n";
		}
		unset($options['safe']);

		$out = $this->formatTemplate('javascriptblock', [
			'attrs' => $this->templater()->formatAttributes($options, ['block']),
			'content' => $script
		]);

		if (empty($options['block'])) {
			return $out;
		}
		if ($options['block'] === true) {
			$options['block'] = 'script';
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
 * - `block` Set to true to append output to view block "script" or provide
 *   custom block name.
 *
 * @param array $options Options for the code block.
 * @return void
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::scriptStart
 */
	public function scriptStart(array $options = array()) {
		$options += array('safe' => true, 'block' => null);
		$this->_scriptBlockOptions = $options;
		ob_start();
		return null;
	}

/**
 * End a Buffered section of JavaScript capturing.
 * Generates a script tag inline or appends to specified view block depending on
 * the settings used when the scriptBlock was started
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
 * echo $this->Html->style(array('margin' => '10px', 'padding' => '10px'), true);
 *
 * // creates
 * 'margin:10px;padding:10px;'
 * }}}
 *
 * @param array $data Style data array, keys will be used as property names, values as property values.
 * @param bool $oneLine Whether or not the style block should be displayed on one line.
 * @return string CSS styling data
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::style
 */
	public function style(array $data, $oneLine = true) {
		$out = array();
		foreach ($data as $key => $value) {
			$out[] = $key . ':' . $value . ';';
		}
		if ($oneLine) {
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
 * @return string Composed bread crumbs
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
 *
 * - `separator` Separator content to insert in between breadcrumbs, defaults to ''
 * - `firstClass` Class for wrapper tag on the first breadcrumb, defaults to 'first'
 * - `lastClass` Class for wrapper tag on current active page, defaults to 'last'
 *
 * @param array $options Array of html attributes to apply to the generated list elements.
 * @param string|array|bool $startText This will be the first crumb, if false it defaults to first crumb in array. Can
 *   also be an array, see `HtmlHelper::getCrumbs` for details.
 * @return string breadcrumbs html list
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#creating-breadcrumb-trails-with-htmlhelper
 */
	public function getCrumbList(array $options = array(), $startText = false) {
		$defaults = array('firstClass' => 'first', 'lastClass' => 'last', 'separator' => '', 'escape' => true);
		$options += $defaults;
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
			$result .= $this->formatTemplate('li', [
				'content' => $elementContent,
				'attrs' => $this->templater()->formatAttributes($options)
			]);
		}
		return $this->formatTemplate('ul', [
			'content' => $result,
			'attrs' => $this->templater()->formatAttributes($ulOptions)
		]);
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
	public function image($path, array $options = array()) {
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

		$image = $this->formatTemplate('image', [
			'url' => $path,
			'attrs' => $this->templater()->formatAttributes($options),
		]);

		if ($url) {
			return $this->formatTemplate('link', [
				'url' => $this->url($url),
				'attrs' => null,
				'content' => $image
			]);
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
	public function tableHeaders(array $names, array $trOptions = null, array $thOptions = null) {
		$out = array();
		foreach ($names as $arg) {
			if (!is_array($arg)) {
				$out[] = $this->formatTemplate('tableheader', [
					'attrs' => $this->templater()->formatAttributes($thOptions),
					'content' => $arg
				]);
			} else {
				$out[] = $this->formatTemplate('tableheader', [
					'attrs' => $this->templater()->formatAttributes(current($arg)),
					'content' => key($arg)
				]);
			}
		}
		return $this->formatTemplate('tablerow', [
			'attrs' => $this->templater()->formatAttributes($trOptions),
			'content' => implode(' ', $out)
		]);
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
				} elseif ($useCount) {
					$cellOptions['class'] = 'column-' . ++$i;
				}
				$cellsOut[] = $this->formatTemplate('tablecell', [
					'attrs' => $this->templater()->formatAttributes($cellOptions),
					'content' => $cell
				]);
			}
			$opts = $count % 2 ? $oddTrOptions : $evenTrOptions;
			$out[] = $this->formatTemplate('tablerow', [
				'attrs' => $this->templater()->formatAttributes($opts),
				'content' => implode(' ', $cellsOut),
			]);
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
	public function tag($name, $text = null, array $options = array()) {
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
		return $this->formatTemplate($tag, [
			'attrs' => $this->templater()->formatAttributes($options),
			'tag' => $name,
			'content' => $text,
		]);
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
	public function div($class = null, $text = null, array $options = array()) {
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
	public function para($class, $text, array $options = array()) {
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
		return $this->formatTemplate($tag, [
			'attrs' => $this->templater()->formatAttributes($options),
			'content' => $text,
		]);
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
 * {{{
 * echo $this->Html->media(
 * 		array('video.mp4', array('src' => 'video.ogv', 'type' => "video/ogg; codecs='theora, vorbis'")),
 * 		array('tag' => 'video', 'autoplay')
 * );
 * }}}
 *
 * Outputs:
 *
 * {{{
 * <video autoplay="autoplay">
 * 		<source src="/files/video.mp4" type="video/mp4"/>
 * 		<source src="/files/video.ogv" type="video/ogv; codecs='theora, vorbis'"/>
 * </video>
 * }}}
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
	public function media($path, array $options = array()) {
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
				$sourceTags .= $this->formatTemplate('tagselfclosing', [
					'tag' => 'source',
					'attrs' => $this->templater()->formatAttributes($source)
				]);
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
 * Options for $options:
 *
 * - `tag` - Type of list tag to use (ol/ul)
 *
 * Options for $itemOptions:
 *
 * - `even` - Class to use for even rows.
 * - `odd` - Class to use for odd rows.
 *
 * @param array $list Set of elements to list
 * @param array $options Options and additional HTML attributes of the list (ol/ul) tag.
 * @param array $itemOptions Options and additional HTML attributes of the list item (LI) tag.
 * @return string The nested list
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/html.html#HtmlHelper::nestedList
 */
	public function nestedList(array $list, array $options = [], array $itemOptions = []) {
		$options += array('tag' => 'ul');
		$items = $this->_nestedListItem($list, $options, $itemOptions);
		return $this->formatTemplate($options['tag'], [
			'attrs' => $this->templater()->formatAttributes($options, ['tag']),
			'content' => $items
		]);
	}

/**
 * Internal function to build a nested list (UL/OL) out of an associative array.
 *
 * @param array $items Set of elements to list.
 * @param array $options Additional HTML attributes of the list (ol/ul) tag.
 * @param array $itemOptions Options and additional HTML attributes of the list item (LI) tag.
 * @return string The nested list element
 * @see HtmlHelper::nestedList()
 */
	protected function _nestedListItem($items, $options, $itemOptions) {
		$out = '';

		$index = 1;
		foreach ($items as $key => $item) {
			if (is_array($item)) {
				$item = $key . $this->nestedList($item, $options, $itemOptions);
			}
			if (isset($itemOptions['even']) && $index % 2 === 0) {
				$itemOptions['class'] = $itemOptions['even'];
			} elseif (isset($itemOptions['odd']) && $index % 2 !== 0) {
				$itemOptions['class'] = $itemOptions['odd'];
			}
			$out .= $this->formatTemplate('li', [
				'attrs' => $this->templater()->formatAttributes($itemOptions, ['even', 'odd']),
				'content' => $item
			]);
			$index++;
		}
		return $out;
	}

/**
 * Event listeners.
 *
 * @return array
 */
	public function implementedEvents() {
		return [];
	}

}
