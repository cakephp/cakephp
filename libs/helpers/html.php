<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
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
 * Purpose: Dispatcher
 * Dispatches the request, creating aproppriate models and controllers.
 * 
 * @filesource 
 * @author CakePHP Authors/Developers
 * @copyright Copyright (c) 2005, Cake Authors/Developers
 * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.libs.helpers
 * @since CakePHP v 0.2.9
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Html helper library.
 *
 * @package cake
 * @subpackage cake.libs.helpers
 * @since CakePHP v 0.9.1
 *
 */
class HtmlHelper
{
	/**
	 * Breadcrumbs.
	 *
	 * @var array
	 * @access private
	 */
	var $_crumbs = array();

	
	var $base = null;
	var $here = null;
	var $params = array();
	var $action = null;
	var $data = null;
	
	/**
	 * Returns given string trimmed to given length, adding an ending (default: "..") if necessary.
	 *
	 * @param string $string String to trim
	 * @param integer $length Length of returned string, excluding ellipsis
	 * @param string $ending Ending to be appended after trimmed string
	 * @return string Trimmed string
	 */
	function trim($string, $length, $ending='..')
	{
		return substr($string, 0, $length).(strlen($string)>$length? $ending: null);
	}

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
		return sprintf(TAG_LINK, $this->urlFor($url), $this->parseHtmlOptions($html_options), $title);
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
	   $elements = explode("/", $tag_name);
	   
		$html_options['size'] = $size;
		$html_options['value'] = isset($html_options['value'])? $html_options['value']: $this->tagValue($elements[1]);
		$this->tagIsInvalid($elements[0],$elements[1])? $html_options['class'] = 'form_error': null;
		return sprintf(TAG_INPUT, $elements[1], $this->parseHtmlOptions($html_options, null, '', ' '));
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

	/**
	 * Returns value of $tag_name. False is the tag does not exist.
	 *
	 * @param string $tag_name
	 * @return unknown Value of the named tag.
	 */
	function tagValue ($tag_name)
	{
		return isset($this->params['data'][$tag_name])? $this->params['data'][$tag_name]: false;
	}

	/**
	 * Returns false if given FORM field has no errors. Otherwise it returns the constant set in the array Model->validationErrors.
	 *
	 * @param unknown_type $field
	 * @return unknown
	 */
	function tagIsInvalid ($model, $field)
	{
		return empty($this->validationErrors[$model][$field])? 0: $this->validationErrors[$model][$field];
	}

	/**
	 * Returns number of errors in a submitted FORM.
	 *
	 * @return int Number of errors
	 */
	function validate ()
	{
		$args = func_get_args();
		$errors = call_user_func_array(array(&$this, 'validateErrors'), $args);

		return count($errors);
	}

	/**
	 * Validates a FORM according to the rules set up in the Model.
	 *
	 * @return int Number of errors
	 */
	function validateErrors ()
	{
		$objects = func_get_args();
		if (!count($objects)) return false;

		$errors = array();
		foreach ($objects as $object)
		{
			$errors = array_merge($errors, $object->invalidFields($object->data));
		}

		return $this->validationErrors = (count($errors)? $errors: false);
	}

	/**
	 * Returns a formatted error message for given FORM field, NULL if no errors.
	 *
	 * @param string $name
	 * @param string $field
	 * @param string $text
	 * @return string If there are errors this method returns an error message, else NULL. 
	 */
	function tagErrorMsg ($field, $text)
	{
	   $elements = explode("/", $field);
	   $error = 1;
		if ($error == $this->tagIsInvalid($elements[0], $elements[1]))
		{
			return sprintf(SHORT_ERROR_MESSAGE, is_array($text)? (empty($text[$error-1])? 'Error in field': $text[$error-1]): $text);
		}
		else
		{
			return null;
		}
	}
}

?>