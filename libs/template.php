<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005, Cake Authors/Developers                     + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
 * Purpose: Renderer
 * Templating for Controller class.
 *
 * @filesource 
 * @author Cake Authors/Developers
 * @copyright Copyright (c) 2005, Cake Authors/Developers
 * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.libs
 * @since Cake v 0.2.9
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

uses('object');

/**
 * Templating for Controller class. Takes care of rendering views.
 *
 * @package cake
 * @subpackage cake.libs
 * @since Cake v 0.2.9
 */
class Template extends Object
{

	/**
	 * Enter description here...
	 *
	 * @var unknown_type
	 * @access public
	 */
	var $base = null;

	/**
	 * Enter description here...
	 *
	 * @var string
	 * @access public
	 */
	var $layout = 'default';

	/**
	 * Enter description here...
	 *
	 * @var boolean
	 * @access public
	 */
	var $autoRender = true;

	/**
	 * Enter description here...
	 *
	 * @var boolean
	 * @access public
	 */
	var $autoLayout = true;

	/**
	 * Variables for the view
	 *
	 * @var array
	 * @access private
	 */
	var $_viewVars = array();

	/**
	 * Enter description here...
	 *
	 * @var boolean
	 * @access private
	 */
	var $pageTitle = false;

	/**
	 * Choose the layout to be used when rendering.
	 *
	 * @param string $layout
	 */
	function setLayout($layout)
	{
		$this->layout = $layout;
	}

	/**
	 * Saves a variable to use inside a template.
	 *
	 * @param mixed $one A string or an array of data.
	 * @param string $two Value in case $one is a string (which then works as the key), otherwise unused.
	 * @return unknown
	 */
	function set($one, $two=null)
	{
		return $this->_setArray(is_array($one)? $one: array($one=>$two));
	}

	/**
	 * Set the title element of the page.
	 *
	 * @param string $pageTitle Text for the title
	 */
	function setTitle($pageTitle)
	{
		$this->pageTitle = $pageTitle;
	}

	/**
	 * Sets data for this view. Will set title if the key "title" is in given $data array.
	 *
	 * @param array $data Array of 
	 */
	function _setArray($data)
	{
		foreach ($data as $name => $value)
		{
			if ($name == 'title')
			$this->setTitle($value);
			else
			$this->_viewVars[$name] = $value;
		}
	}

	/**
	 * Displays a flash message. A flash message is feedback to the user that displays after editing actions, among other things.
	 *
	 * @param string $message Text to display to the user
	 * @param string $url URL fragment
	 * @param int $time Display time, in seconds
	 */
	function flash($message, $url, $time=1)
	{
		$this->autoRender = false;
		$this->autoLayout = false;

		$this->set('url', $this->base.$url);
		$this->set('message', $message);
		$this->set('time', $time);

		$this->render(null,false,VIEWS.'layouts'.DS.'flash.thtml');
	}

	/**
	 * Render view for given action and layout. If $file is given, that is used 
	 * for a view filename (e.g. customFunkyView.thtml).
	 *
	 * @param string $action Name of action to render for
	 * @param string $layout 
	 * @param string $file Custom filename for view
	 */
	function render($action=null, $layout=null, $file=null)
	{
		if (isset($this->hasRendered) && $this->hasRendered)
		{
			return true;
		}
		else
		{
			$this->hasRendered = false;
		}

		$this->autoRender = false;

		if (!$action) $action = $this->action;
		if ($layout) $this->setLayout($layout);

		//$isFatal = isset($this->isFatal) ? $this->isFatal : false;

		$viewFn = $file? $file: $this->_getViewFn($action);

		if (!is_file($viewFn))
		{
			if (strtolower(get_class($this)) == 'template')
			{
				return array('action' => $action, 'layout' => $layout, 'viewFn' => $viewFn);
			}

			// check to see if the missing view is due to a custom missingAction
			if (strpos($action, 'missingAction') !== false)
			{
				$errorAction = 'missingAction';
			}
			else
			{
				$errorAction = 'missingView';
			}

			// check for controller-level view handler
			foreach(array($this->name, 'errors') as $view_dir)
			{
				$missingViewFn = VIEWS.$view_dir.DS.Inflector::underscore($errorAction).'.thtml';
				$missingViewExists = is_file($missingViewFn);
				if ($missingViewExists)
				{
					break;
				}
			}

			if (strpos($action, 'missingView') === false)
			{
				$controller = $this;
				$controller->missingView = $viewFn;
				$controller->action      = $action;
				call_user_func_array(array(&$controller, 'missingView'), empty($params['pass'])? null: $params['pass']);
				$isFatal = isset($this->isFatal) ? $this->isFatal : false;
				if (!$isFatal)
				{
					$viewFn = $missingViewFn;
				}
			}
			else
			{
				$missingViewExists = false;
			}

			if (!$missingViewExists || $isFatal)
			{
				// app/view/errors/missing_view.thtml view is missing!
				if (DEBUG)
				{
					trigger_error(sprintf(ERROR_NO_VIEW, $action, $viewFn), E_USER_ERROR);
				}
				else
				{
					$this->error('404', 'Not found', sprintf(ERROR_404, '', "missing view \"{$action}\""));
				}

				die();
			}
		}

		if ($viewFn && !$this->hasRendered)
		{
			$out = $this->_render($viewFn, $this->_viewVars, 0);
			if ($out !== false)
			{
				if ($this->layout && $this->autoLayout)
				{
					$out = $this->renderLayout($out);
				}

				print $out;
				$this->hasRendered = true;
			}
			else
			{
				$out = $this->_render($viewFn, $this->_viewVars, false);
				trigger_error(sprintf(ERROR_IN_VIEW, $viewFn, $out), E_USER_ERROR);
			}

			return true;
		}
	}

	/**
	 * Renders a layout. Returns output from _render(). Returns false on error.
	 *
	 * @param string $content_for_layout Content to render in a view
	 * @return string Rendered output
	 */
	function renderLayout($content_for_layout)
	{
		$layout_fn = $this->_getLayoutFn();

		$data_for_layout = array_merge($this->_viewVars, array(
		'title_for_layout'=>$this->pageTitle !== false? $this->pageTitle: Inflector::humanize($this->viewpath),
		'content_for_layout'=>$content_for_layout));

		if (is_file($layout_fn)) {
			$out = $this->_render($layout_fn, $data_for_layout);

			if ($out === false) {
				$out = $this->_render($layout_fn, $data_for_layout, false);
				trigger_error(sprintf(ERROR_IN_LAYOUT, $layout_fn, $out), E_USER_ERROR);
				return false;
			}
			else {
				return $out;
			}
		}
		else {
			trigger_error(sprintf(ERROR_NO_LAYOUT, $this->layout, $layout_fn), E_USER_ERROR);
			return false;
		}
	}

	/**
	 * Renders a piece of PHP with provided parameters and returns HTML, XML, or any other string.
	 *
	 * @param string $name Name of template file
	 * @param array $params Array of data for rendered view 
	 * @return string Rendered output
	 */
	function renderElement($name, $params=array())
	{
		$fn = ELEMENTS.$name.'.thtml';

		if (!file_exists($fn))
		return "(Error rendering {$name})";

		return $this->_render($fn, array_merge($this->_viewVars, $params));
	}

	/**
	 * Returns layout filename for this template as a string.
	 *
	 * @return string Filename for layout file (.thtml).
	 */
	function _getLayoutFn()
	{
		return VIEWS."layouts".DS."{$this->layout}.thtml";
	}

	/**
	 * Returns filename of given action's template file (.thtml) as a string.
	 *
	 * @param string $action Controller action to find template filename for
	 * @return string Template filename
	 */
	function _getViewFn($action)
	{
		$action = Inflector::underscore($action);
		$viewFn = VIEWS.$this->viewpath.DS."{$action}.thtml";
		$viewPath = explode(DS, $viewFn);
		
		$i = array_search('..', $viewPath);
		
		unset($viewPath[$i-1]);
		unset($viewPath[$i]);
		
		return '/'.implode('/', $viewPath);
	}

	/**
	 * Renders and returns output for given view filename with its 
	 * array of data.
	 *
	 * @param string $___viewFn Filename of the view
	 * @param array $___data_for_view Data to include in rendered view
	 * @param boolean $___play_safe If set to false, the include() of the $__viewFn is done without suppressing output of errors
	 * @return string Rendered output
	 */
	function _render($___viewFn, $___data_for_view, $___play_safe = true)
	{
		/**
		 * Fetching helpers
		 */
		if ($this->helpers !== false)
		{
			foreach ($this->helpers as $helper)
			{
				$helperFn = LIBS.'helpers'.DS.Inflector::underscore($helper).'.php';
				$helperCn = ucfirst($helper).'Helper';
				if (is_file($helperFn))
				{
					require_once $helperFn;						
					if(class_exists($helperCn)===true);
					{
						${$helper} = new $helperCn;
						${$helper}->base   = $this->base;
						${$helper}->here   = $this->here;
						${$helper}->params = $this->params;
						${$helper}->action = $this->action;
						${$helper}->data   = $this->data;
					}
				}
			}
		}
		
		extract($___data_for_view, EXTR_SKIP); # load all view variables
		/**
		 * Local template variables.
		 */
		$BASE = $this->base;
		$params = &$this->params;
		$page_title = $this->pageTitle;
		
		/**
		 * Start caching output (eval outputs directly so we need to cache).
		 */
		ob_start(); 

		/**
		 * Include the template.
		 */
		$___play_safe? @include($___viewFn): include($___viewFn);

		$out = ob_get_clean();

		return $out;
	}

	
	///////////////////////////////////////////////////////////////////////////

	/**
	 * Returns an URL for a combination of controller and action.
	 *
	 * @param string $url
	 * @return string Full constructed URL as a string.
	 */
	function urlFor($url=null)
	{
		if (empty($url))
		{
			return $this->here;
		}
		elseif ($url[0] == '/')
		{
			$out = $this->base . $url;
		}
		else
		{
			$out = $this->base . '/' . strtolower($this->params['controller']) . '/' . $url;
		}

		return ereg_replace('&([^a])', '&amp;\1', $out);
	}

	/**
	 * Returns a space-separated string with items of the $options array.
	 *
	 * @param array $options Array of HTML options.
	 * @param string $insert_before
	 * @param unknown_type $insert_after
	 * @return string
	 */
	function parseHtmlOptions($options, $exclude=null, $insert_before=' ', $insert_after=null)
	{
		if (!is_array($exclude)) $exclude = array();

		if (is_array($options))
		{
			$out = array();
			foreach ($options as $k=>$v)
			{
				if (!in_array($k, $exclude))
				{
					$out[] = "{$k}=\"{$v}\"";
				}
			}
			$out = join(' ', $out);
			return $out? $insert_before.$out.$insert_after: null;
		}
		else
		{
			return $options? $insert_before.$options.$insert_after: null;
		}
	}

	/**
	 * Returns an HTML link to $url for given $title, optionally using $html_options and $confirm_message (for "flash").
	 *
	 * @param string $title The content of the A tag.
	 * @param string $url
	 * @param array $html_options Array of HTML options.
	 * @param string $confirm_message Message to be shown in "flash".
	 * @return string
	 */
	function linkTo($title, $url, $html_options=null, $confirm_message=false)
	{
		$confirm_message? $html_options['onClick'] = "return confirm('{$confirm_message}')": null;
		return sprintf(TAG_LINK, $this->UrlFor($url), $this->parseHtmlOptions($html_options), $title);
	}

	/**
	 * Returns an external HTML link to $url for given $title, optionally using $html_options.
	 * The ereg_replace is to replace the '&' in the URL into &amp; for XHTML purity.
	 *
	 * @param string $title
	 * @param string $url
	 * @param array $html_options
	 * @return string
	 */
	function linkOut($title, $url=null, $html_options=null)
	{
		$url = $url? $url: $title;
		return sprintf(TAG_LINK, ereg_replace('&([^a])', '&amp;\1', $url), $this->parseHtmlOptions($html_options), $title);
	}

	/**
	 * Returns an HTML FORM element. 
	 *
	 * @param string $target URL for the FORM's ACTION attribute.
	 * @param string $type FORM type (POST/GET).
	 * @param array $html_options
	 * @return string An formatted opening FORM tag.
	 */
	function formTag($target=null, $type='post', $html_options=null)
	{
		$html_options['action'] = $this->UrlFor($target);
		$html_options['method'] = $type=='get'? 'get': 'post';
		$type == 'file'? $html_options['enctype'] = 'multipart/form-data': null;

		return sprintf(TAG_FORM, $this->parseHtmlOptions($html_options, null, ''));
	}

	/**
	 * Returns a generic HTML tag (no content). 
	 * 
	 * Examples:
	 * * <i>tag("br") => <br /></i>
	 * * <i>tag("input", array("type" => "text")) => <input type="text" /></i>
	 *
	 * @param string $name Name of HTML element
	 * @param array $options HTML options
	 * @param bool $open Is the tag open or closed? (defaults to closed "/>")
	 * @return string The formatted HTML tag
	 */	
	function tag($name, $options=null, $open=false)
	{
		$tag = "<$name ". $this->parseHtmlOptions($options);
		$tag .= $open? ">" : " />";
		return $tag;
	}

	/**
	 * Returns a generic HTML tag with content.
	 * 
	 * Examples:
	 * * <i>content_tag("p", "Hello world!") => <p>Hello world!</p></i>
	 * * <i>content_tag("div", content_tag("p", "Hello world!"), array("class" => "strong")) => </i>
	 *   <i><div class="strong"><p>Hello world!</p></div></i>
	 *
	 * @param string $name Name of HTML element
	 * @param array $options HTML options
	 * @param bool $open Is the tag open or closed? (defaults to closed "/>")
	 * @return string The formatted HTML tag
	 */	
	function contentTag($name, $content, $options=null)
	{
		return "<$name ". $this->parseHtmlOptions($options). ">$content</$name>";
	}

	/**
	 * Returns a formatted SUBMIT button for HTML FORMs.
	 *
	 * @param string $caption Text on SUBMIT button
	 * @param array $html_options HTML options
	 * @return string The formatted SUBMIT button
	 */
	function submitTag($caption='Submit', $html_options=null)
	{
		$html_options['value'] = $caption;
		return sprintf(TAG_SUBMIT, $this->parseHtmlOptions($html_options, null, '', ' '));
	}

	/**
	 * Returns a formatted INPUT tag for HTML FORMs.
	 *
	 * @param string $tag_name Name attribute for INPUT element
	 * @param int $size Size attribute for INPUT element
	 * @param array $html_options
	 * @return string The formatted INPUT element
	 */
	function inputTag($tag_name, $size=20, $html_options=null)
	{
		$html_options['size'] = $size;
		$html_options['value'] = isset($html_options['value'])? $html_options['value']: $this->tagValue($tag_name);
		$this->tagIsInvalid($tag_name)? $html_options['class'] = 'form_error': null;
		return sprintf(TAG_INPUT, $tag_name, $this->parseHtmlOptions($html_options, null, '', ' '));
	}

	/**
	 * Returns an INPUT element with type="password".
	 *
	 * @param string $tag_name
	 * @param int $size
	 * @param array $html_options
	 * @return string
	 */
	function passwordTag($tag_name, $size=20, $html_options=null)
	{
		$html_options['size'] = $size;
		empty($html_options['value'])? $html_options['value'] = $this->tagValue($tag_name): null;
		return sprintf(TAG_PASSWORD, $tag_name, $this->parseHtmlOptions($html_options, null, '', ' '));
	}

	/**
	 * Returns an INPUT element with type="hidden".
	 *
	 * @param string $tag_name
	 * @param string $value
	 * @param array $html_options
	 * @return string
	 */
	function hiddenTag($tag_name, $value=null, $html_options=null)
	{
		$html_options['value'] = $value? $value: $this->tagValue($tag_name);
		return sprintf(TAG_HIDDEN, $tag_name, $this->parseHtmlOptions($html_options, null, '', ' '));
	}

	/**
	 * Returns an INPUT element with type="file".
	 *
	 * @param string $tag_name
	 * @param array $html_options
	 * @return string
	 */
	function fileTag($tag_name, $html_options=null)
	{
		return sprintf(TAG_FILE, $tag_name, $this->parseHtmlOptions($html_options, null, '', ' '));
	}

	/**
	 * Returns a TEXTAREA element.
	 *
	 * @param string $tag_name
	 * @param int $cols
	 * @param int $rows
	 * @param array $html_options
	 * @return string
	 */
	function areaTag($tag_name, $cols=60, $rows=10, $html_options=null)
	{
		$value = empty($html_options['value'])? $this->tagValue($tag_name): empty($html_options['value']);
		$html_options['cols'] = $cols;
		$html_options['rows'] = $rows;
		return sprintf(TAG_AREA, $tag_name, $this->parseHtmlOptions($html_options, null, ' '), $value);
	}

	/**
	 * Returns an INPUT element with type="checkbox". Checkedness is to be passed as string "checked" with the key "checked" in the $html_options array.
	 *
	 * @param string $tag_name
	 * @param string $title
	 * @param array $html_options
	 * @return string
	 */
	function checkboxTag($tag_name, $title=null, $html_options=null)
	{
		$this->tagValue($tag_name)? $html_options['checked'] = 'checked': null;
		$title = $title? $title: ucfirst($tag_name);
		return sprintf(TAG_CHECKBOX, $tag_name, $tag_name, $tag_name, $this->parseHtmlOptions($html_options, null, '', ' '), $title);
	}

	/**
	 * Returns a set of radio buttons. 
	 *
	 * @param string $tag_name
	 * @param array $options Array of options to select from
	 * @param string $inbetween String to separate options. See PHP's implode() function
	 * @param array $html_options
	 * @return string
	 */
	function radioTags($tag_name, $options, $inbetween=null, $html_options=null)
	{
		$value = isset($html_options['value'])? $html_options['value']: $this->tagValue($tag_name);
		$out = array();
		foreach ($options as $opt_value=>$opt_title)
		{
			$options_here = array('value' => $opt_value);
			$opt_value==$value? $options_here['checked'] = 'checked': null;
			$parsed_options = $this->parseHtmlOptions(array_merge($html_options, $options_here), null, '', ' ');
			$individual_tag_name = "{$tag_name}_{$opt_value}";
			$out[] = sprintf(TAG_RADIOS, $individual_tag_name, $tag_name, $individual_tag_name, $parsed_options, $opt_title);
		}

		$out = join($inbetween, $out);
		return $out? $out: null;
	}

	/**
	 * Returns a SELECT element, 
	 *
	 * @param string $tag_name Name attribute of the SELECT
	 * @param array $option_elements Array of the OPTION elements (as 'value'=>'Text' pairs) to be used in the SELECT element
	 * @param array $select_attr Array of HTML options for the opening SELECT element
	 * @param array $option_attr Array of HTML options for the enclosed OPTION elements 
	 * @return string Formatted SELECT element
	 */
	function selectTag($tag_name, $option_elements, $selected=null, $select_attr=null, $option_attr=null)
	{
		if (!is_array($option_elements) || !count($option_elements))
		return null;

		$select[] = sprintf(TAG_SELECT_START, $tag_name, $this->parseHtmlOptions($select_attr));
		$select[] = sprintf(TAG_SELECT_EMPTY, $this->parseHtmlOptions($option_attr));

		foreach ($option_elements as $name=>$title)
		{
			$options_here = $option_attr;

			if ($selected == $name)
			$options_here['selected'] = 'selected';

			$select[] = sprintf(TAG_SELECT_OPTION, $name, $this->parseHtmlOptions($options_here), $title);
		}

		$select[] = sprintf(TAG_SELECT_END);

		return implode("\n", $select);
	}

	/**
	 * Returns a formatted IMG element.
	 *
	 * @param string $path Path to the image file
	 * @param string $alt ALT attribute for the IMG tag
	 * @param array $html_options
	 * @return string Formatted IMG tag
	 */
	function imageTag($path, $alt=null, $html_options=null)
	{
		$url = $this->base.IMAGES_URL.$path;
		return sprintf(TAG_IMAGE, $url, $alt, $this->parseHtmlOptions($html_options, null, '', ' '));
	}

	/**
	 * Returns a mailto: link.
	 *
	 * @param string $title Title of the link, or the e-mail address (if the same)
	 * @param string $email E-mail address if different from title
	 * @param array $options
	 * @return string Formatted A tag
	 */
	function linkEmail($title, $email=null, $options=null)
	{
		// if no $email, then title contains the email.
		if (empty($email)) $email = $title;

		$match = array();
		
		// does the address contain extra attributes?
		preg_match('!^(.*)(\?.*)$!', $email, $match);

		// plaintext
		if (empty($options['encode']) || !empty($match[2]))
		{
			return sprintf(TAG_MAILTO, $email, $this->parseHtmlOptions($options), $title);
		}
		// encoded to avoid spiders
		else
		{
			$email_encoded = null;
			for ($ii=0; $ii < strlen($email); $ii++)
			{
				if(preg_match('!\w!',$email[$ii]))
				{
					$email_encoded .= '%' . bin2hex($email[$ii]);
				}
				else
				{
					$email_encoded .= $email[$ii];
				}
			}

			$title_encoded = null;
			for ($ii=0; $ii < strlen($title); $ii++)
			{
				$title_encoded .= preg_match('/^[A-Za-z0-9]$/', $title[$ii])? '&#x' . bin2hex($title[$ii]).';': $title[$ii];
			}

			return sprintf(TAG_MAILTO, $email_encoded, $this->parseHtmlOptions($options, array('encode')), $title_encoded);
		}
	}

	/**
	 * Returns a LINK element for CSS stylesheets.
	 *
	 * @param string $path Path to CSS file
	 * @param string $rel Rel attribute. Defaults to "stylesheet".
	 * @param array $html_options
	 * @return string Formatted LINK element.
	 */
	function cssTag($path, $rel='stylesheet', $html_options=null)
	{
		$url = "{$this->base}/".(COMPRESS_CSS? 'c': '')."css/{$path}.css";
		return sprintf(TAG_CSS, $rel, $url, $this->parseHtmlOptions($html_options, null, '', ' '));
	}

	/**
	 * Returns a charset meta-tag
	 *
	 * @param string $charset
	 * @return string
	 */
	function charsetTag($charset)
	{
		return sprintf(TAG_CHARSET, $charset);
	}

	/**
	 * Returns a JavaScript script tag.
	 *
	 * @param string $script The JavaScript to be wrapped in SCRIPT tags.
	 * @return string The full SCRIPT element, with the JavaScript inside it.
	 */
	function javascriptTag($script)
	{
		return sprintf(TAG_JAVASCRIPT, $script);
	}

	/**
	 * Returns a JavaScript include tag
	 *
	 * @param string $url URL to JavaScript file.
	 * @return string
	 */
	function javascriptIncludeTag($url)
	{
		return sprintf(TAG_JAVASCRIPT_INCLUDE, $this->base.$url);
	}

	/**
	 * Returns a row of formatted and named TABLE headers.
	 *
	 * @param array $names
	 * @param array $tr_options
	 * @param array $th_options
	 * @return string
	 */
	function tableHeaders($names, $tr_options=null, $th_options=null)
	{
		$out = array();
		foreach ($names as $arg)
		{
			$out[] = sprintf(TAG_TABLE_HEADER, $this->parseHtmlOptions($th_options), $arg);
		}

		return sprintf(TAG_TABLE_HEADERS, $this->parseHtmlOptions($tr_options), join(' ', $out));
	}

	/**
	 * Returns a formatted string of table rows (TR's with TD's in them).
	 *
	 * @param array $data Array of table data
	 * @param array $tr_options HTML options for TR elements
	 * @param array $td_options HTML options for TD elements
	 * @return string
	 */
	function tableCells($data, $odd_tr_options=null, $even_tr_options=null)
	{
		if (empty($data[0]) || !is_array($data[0]))
		{
			$data = array($data);
		}

		$count=0;
		foreach ($data as $line)
		{
			$count++;
			$cells_out = array();
			foreach ($line as $cell)
			{
				$cells_out[] = sprintf(TAG_TABLE_CELL, null, $cell);
			}

			$options = $this->parseHtmlOptions($count%2? $odd_tr_options: $even_tr_options);
			$out[] = sprintf(TAG_TABLE_ROW, $options, join(' ', $cells_out));
		}

		return join("\n", $out);
	}

	/**
	 * Generates a nested <UL> (unordered list) tree from an array
	 *
	 * @param array $data
	 * @param array $htmlOptions
	 * @param string $bodyKey
	 * @param childrenKey $bodyKey
	 * @return string
	 */
	function guiListTree($data, $htmlOptions=null, $bodyKey='body', $childrenKey='children')
	{
		$out = "<ul".$this->parseHtmlOptions($htmlOptions).">\n";

		foreach ($data as $item)
		{
			$out .= "<li>{$item[$bodyKey]}</li>\n";
			if (isset($item[$childrenKey]) && is_array($item[$childrenKey]) && count($item[$childrenKey]))
			{
				$out .= $this->guiListTree($item[$childrenKey], $htmlOptions, $bodyKey, $childrenKey);
			}
		}

		$out .= "</ul>\n";

		return $out;
	}

	/**
	 * Adds $name and $link to the breadcrumbs array.
	 *
	 * @param string $name Text for link
	 * @param string $link URL for link
	 */
	function addCrumb($name, $link)
	{
		$this->_crumbs[] = array ($name, $link);
	}

	/**
	 * Returns the breadcrumb trail as a sequence of &raquo;-separated links.
	 *
	 * @param string $separator Text to separate crumbs.
	 * @return string Formatted -separated list of breadcrumb links. Returns NULL if $this->_crumbs is empty.
	 */
	function getCrumbs($separator = '&raquo;')
	{

		if (count($this->_crumbs))
		{

			$out = array("<a href=\"{$this->base}\">START</a>");
			foreach ($this->_crumbs as $crumb)
			{
				$out[] = "<a href=\"{$this->base}{$crumb[1]}\">{$crumb[0]}</a>";
			}

			return join($separator, $out);
		}
		else
		{
			return null;
		}
	}

	
	///////////////////////////////////////////////////////////////////////////
	
	
	/**
	 * Returns link to javascript function
	 * 
	 * Returns a link that'll trigger a javascript function using the 
	 * onclick handler and return false after the fact.
	 * 
	 * Examples:
	 * <code>
	 *   linkToFunction("Greeting", "alert('Hello world!')");
	 *   linkToFunction(imageTag("delete"), "if confirm('Really?'){ do_delete(); }");
	 * </code>
	 *
	 * @param string $title title of link
	 * @param string $func javascript function to be called on click
	 * @param array $html_options html options for link
	 * @return string html code for link to javascript function
	 */
	function linkToFunction($title, $func, $html_options=null)
	{
		$html_options['onClick'] = "$func; return false;";
		return $this->linkTo($title, '#', $html_options);
	}

	/**
	 * Returns link to remote action
	 * 
	 * Returns a link to a remote action defined by <i>options[url]</i> 
	 * (using the urlFor format) that's called in the background using 
	 * XMLHttpRequest. The result of that request can then be inserted into a
	 * DOM object whose id can be specified with <i>options[update]</i>. 
	 * Usually, the result would be a partial prepared by the controller with
	 * either renderPartial or renderPartialCollection. 
	 *
	 * Examples:
	 * <code>
	 *  linkToRemote("Delete this post", 
	 *  		array("update" => "posts", "url" => "delete/{$postid->id}"));
	 *  linkToRemote(imageTag("refresh"), 
	 *		array("update" => "emails", "url" => "list_emails" ));
	 * </code>
	 *
	 * By default, these remote requests are processed asynchronous during 
	 * which various callbacks can be triggered (for progress indicators and
	 * the likes).
	 *
	 * Example:
	 * <code>
	 *   linkToRemote (word,
	 *       array("url" => "undo", "n" => word_counter),
	 *       array("complete" => "undoRequestCompleted(request)"));
	 * </code> 
	 *
	 * The callbacks that may be specified are:
	 *
	 * - <i>loading</i>::       Called when the remote document is being 
	 *                           loaded with data by the browser.
	 * - <i>loaded</i>::        Called when the browser has finished loading
	 *                           the remote document.
	 * - <i>interactive</i>::   Called when the user can interact with the 
	 *                           remote document, even though it has not 
	 *                           finished loading.
	 * - <i>complete</i>::      Called when the XMLHttpRequest is complete.
	 *
	 * If you for some reason or another need synchronous processing (that'll
	 * block the browser while the request is happening), you can specify 
	 * <i>options[type] = synchronous</i>.
	 *
	 * You can customize further browser side call logic by passing
	 * in Javascript code snippets via some optional parameters. In
	 * their order of use these are:
	 *
	 * - <i>confirm</i>::      Adds confirmation dialog.
	 * -<i>condition</i>::    Perform remote request conditionally
	 *                          by this expression. Use this to
	 *                          describe browser-side conditions when
	 *                          request should not be initiated.
	 * - <i>before</i>::       Called before request is initiated.
	 * - <i>after</i>::        Called immediately after request was
	 *  		             initiated and before <i>loading</i>.
	 *
	 * @param string $title title of link
	 * @param array $options options for javascript function
	 * @param array $html_options options for link
	 * @return string html code for link to remote action
	 */
	function linkToRemote($title, $options=null, $html_options=null)
	{
		return $this->linkToFunction($title, $this->remoteFunction($options), $html_options);
	}

	/**
	 * Creates javascript function for remote AJAX call
	 * 
	 * This function creates the javascript needed to make a remote call 
	 * it is primarily used as a helper for linkToRemote.
	 * 
	 * @see linkToRemote() for docs on options parameter.
	 *
	 * @param array $options options for javascript 
	 * @return string html code for link to remote action
	 */
	function remoteFunction($options=null)
	{
		$javascript_options = $this->__optionsForAjax($options);
		$func = isset($options['update'])
			? "new Ajax.Updater('{$options['update']}', " 
			: "new Ajax.Request(";

		$func .= "'" . $this->urlFor($options['url']) . "'";
		$func .= ", $javascript_options)";

		if (isset($options['before']))
		{
			$func = "{$options['before']}; $func";
		}
		if (isset($options['after']))
		{
			$func = "$func; {$options['before']};";
		}
		if (isset($options['condition']))
		{
			$func = "if ({$options['condition']}) { $func; }";
		}
		if (isset($options['confirm']))
		{
			$func = "if (confirm('" . $this->escapeJavascript($options['confirm']) . "')) { $func; }";
		}

		return $func;
	}

	/**
	 * Escape carrier returns and single and double quotes for Javascript segments. 
	 * 
	 * @param string $javascript string that might have javascript elements
	 * @return string escaped string
	 */	
	function escapeJavascript($javascript)
	{
		$javascript = str_replace(array("\r\n","\n","\r"),'\n', $javascript);
		$javascript = str_replace(array('"', "'"), array('\"', "\\'"), $javascript);
		return $javascript;
	}

	/**
	 * Periodically call remote url via AJAX. 
	 * 
	 * Periodically calls the specified url (<i>options[url]</i>) every <i>options[frequency]</i> seconds (default is 10).
	 * Usually used to update a specified div (<i>options[update]</i>) with the results of the remote call.
	 * The options for specifying the target with url and defining callbacks is the same as linkToRemote.
	 *
	 * @param array $options callback options
	 * @return string javascript code
	 */	
	function periodicallyCallRemote($options=null)
	{
		$frequency = (isset($options['frequency']))? $options['frequency'] : 10;
		$code = "new PeriodicalExecuter(function() {" . $this->remote_function($options) . "}, $frequency)";
		return $this->javascriptTag($code);
	}

	/**
	 * Returns form tag that will submit using Ajax.
	 * 
	 * Returns a form tag that will submit using XMLHttpRequest in the background instead of the regular 
	 * reloading POST arrangement. Even though it's using Javascript to serialize the form elements, the form submission 
	 * will work just like a regular submission as viewed by the receiving side (all elements available in params).
	 * The options for specifying the target with :url and defining callbacks is the same as link_to_remote.
	 *
	 * @param array $options callback options
	 * @return string javascript code
	 */	
	function formRemoteTag($options=null)
	{
		$options['form'] = true;
		$options['html']['onsubmit']=$this->remoteFunction($options) . "; return false;";
		return $this->tag("form", $options['html'], true);
	}

	/**
	 * Returns a button input tag that will submit using Ajax
	 * 
	 * Returns a button input tag that will submit form using XMLHttpRequest in the background instead of regular
	 * reloading POST arrangement. <i>options</i> argument is the same as in <i>form_remote_tag</i>
	 *
	 * @param string $name input button name
	 * @param string $value input button value
	 * @param array $options callback options
	 * @return string ajaxed input button
	 */ 
	function submitToRemote($name, $value, $options = null)
	{
		$options['with'] = 'Form.serialize(this.form)';
		$options['html']['type'] = 'button';
		$options['html']['onclick'] = $this->remoteFunction($options)."; return false;";
		$options['html']['name'] = $name;
		$options['html']['value'] = $value;
		return $this->tag("input", $options['html'], false);
	}


	/**
	 * Includes the Prototype Javascript library (in /vendors/javascript/prototype.js).
	 *
	 * @return string Javascript include tag for prototype library.
	 */ 
	function defineJavascriptFunctions()
	{
		return $this->javascriptIncludeTag(DS.'js'.DS.'vendors.php?file=prototype.js');
	}

	/**
	 * Observe field and call ajax on change.
	 * 
	 * Observes the field with the DOM ID specified by <i>field_id</i> and makes
	 * an Ajax when its contents have changed.
	 * 
	 * Required +options+ are:
	 * - <i>frequency</i>:: The frequency (in seconds) at which changes to
	 *                       this field will be detected.
	 * - <i>url</i>::       @see urlFor() -style options for the action to call
	 *                       when the field has changed.
	 * 
	 * Additional options are:
	 * - <i>update</i>::    Specifies the DOM ID of the element whose 
	 *                       innerHTML should be updated with the
	 *                       XMLHttpRequest response text.
	 * - <i>with</i>::      A Javascript expression specifying the
	 *                       parameters for the XMLHttpRequest. This defaults
	 *                       to Form.Element.serialize('$field_id'), which can be
	 *                       accessed from params['form']['field_id'].
	 *
	 * Additionally, you may specify any of the options documented in
	 * @see linkToRemote().
	 *
	 * @param string $field_id DOM ID of field to observe
	 * @param array $options ajax options
	 * @return string ajax script
	 */ 
	function observeField($field_id, $options = null)
	{
		if (!isset($options['with']))
		{
			$options['with'] = "Form.Element.serialize('$field_id')";
		}
		return $this->__buildObserver('Form.Element.Observer', $field_id, $options);
	}

	/**
	 * Observe entire form and call ajax on change.
	 * 
	 * Like @see observeField(), but operates on an entire form identified by the
	 * DOM ID <b>form_id</b>. <b>options</b> are the same as <b>observe_field</b>, except 
	 * the default value of the <i>with</i> option evaluates to the
	 * serialized (request string) value of the form.
	 *
	 * @param string $field_id DOM ID of field to observe
	 * @param array $options ajax options
	 * @return string ajax script
	 */ 
	function observeForm($field_id, $options = null)
	{
		//i think this is a rails bug... should be set
		if (!isset($options['with']))
		{
			$options['with'] = 'Form.serialize(this.form)';
		}

		return $this->__buildObserver('Form.Observer', $field_id, $options);
	}


	/**
	 * Javascript helper function (private).
	 *
	 */
	function __optionsForAjax($options)
	{
		$js_options = $this->__buildCallbacks($options);
		$js_options['asynchronous'] = 'true';
		if (isset($options['type']))
		{
			if ($options['type'] == 'synchronous')
			{
				$js_options['asynchronous'] = 'false';
			}
		}
		if (isset($options['method']))
		{
			$js_options['method'] = $this->__methodOptionToString($options['method']);
		}
		if (isset($options['position']))
		{
			$js_options['insertion'] = "Insertion." . Inflector::camelize($options['position']);
		}

		if (isset($options['form']))
		{
			$js_options['parameters'] = 'Form.serialize(this)';
		}
		elseif (isset($options['with']))
		{
			$js_options['parameters'] = $options['with'];
		}

		$out = array();
		foreach ($js_options as $k => $v)
		{
			$out[] = "$k:$v";
		}
		$out = join(', ', $out);
		$out = '{' . $out . '}';
		return $out;
	}


	function __methodOptionToString($method)
	{
		return (is_string($method) && !$method[0]=="'")? $method : "'$method'";
	}

	function __buildObserver($klass, $name, $options=null)
	{
		if(!isset($options['with']) && isset($options['update']))
		{
			$options['with'] = 'value';
		}
		$callback = $this->remoteFunction($options);
		$javascript = "new $klass('$name', ";
		$javascript .= "{$options['frequency']}, function(element, value) {";
		$javascript .= "$callback})";
		return $this->javascriptTag($javascript);
	}

	function __buildCallbacks($options)
	{
		$actions= array('uninitialized', 'loading', 'loaded', 'interactive', 'complete');
		$callbacks=array();
		foreach($actions as $callback)
		{
			if(isset($options[$callback]))
			{
				$name = 'on' . ucfirst($callback);
				$code = $options[$callback];
				$callbacks[$name] = "function(request){".$code."}";
			}
		}
		return $callbacks;
	}

}

?>
