<?php
/* SVN FILE: $Id$ */
/**
 * Html Helper class file.
 *
 * Simplifies the construction of HTML elements.
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
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
		'metalink' => '<link href="%s" title="%s"%s />',
		'link' => '<a href="%s" %s>%s</a>',
		'mailto' => '<a href="mailto:%s" %s>%s</a>',
		'form' => '<form %s>',
		'formend' => '</form>',
		'input' => '<input name="data[%s][%s]" %s/>',
		'textarea' => '<textarea name="data[%s][%s]" %s>%s</textarea>',
		'hidden' => '<input type="hidden" name="data[%s][%s]" %s/>',
		'textarea' => '<textarea name="data[%s][%s]" %s>%s</textarea>',
		'checkbox' => '<input type="checkbox" name="data[%s][%s]" %s/>',
		'radio' => '<input type="radio" name="data[%s][%s]" id="%s" %s />%s',
		'selectstart' => '<select name="data[%s][%s]" %s>',
		'selectmultiplestart' => '<select name="data[%s][%s][]" %s>',
		'selectempty' => '<option value="" %s>&nbsp;</option>',
		'selectoption' => '<option value="%s" %s>%s</option>',
		'selectend' => '</select>',
		'optiongroup' => '<optgroup label="%s"%s>',
		'optiongroupend' => '</optgroup>',
		'password' => '<input type="password" name="data[%s][%s]" %s/>',
		'file' => '<input type="file" name="data[%s][%s]" %s/>',
		'file_no_model' => '<input type="file" name="%s" %s/>',
		'submit' => '<input type="submit" %s/>',
		'submitimage' => '<input type="image" src="%s" %s/>',
		'image' => '<img src="%s" %s/>',
		'tableheader' => '<th%s>%s</th>',
		'tableheaderrow' => '<tr%s>%s</tr>',
		'tablecell' => '<td%s>%s</td>',
		'tablerow' => '<tr%s>%s</tr>',
		'block' => '<div%s>%s</div>',
		'blockstart' => '<div%s>',
		'blockend' => '</div>',
		'para' => '<p%s>%s</p>',
		'parastart' => '<p%s>',
		'label' => '<label for="%s"%s>%s</label>',
		'fieldset' => '<fieldset><legend>%s</legend>%s</fieldset>',
		'fieldsetstart' => '<fieldset><legend>%s</legend>',
		'fieldsetend' => '</fieldset>',
		'legend' => '<legend>%s</legend>',
		'css' => '<link rel="%s" type="text/css" href="%s" %s/>',
		'style' => '<style type="text/css"%s>%s</style>',
		'charset' => '<meta http-equiv="Content-Type" content="text/html; charset=%s" />'
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
 * @param string $link URL for link
 */
	function addCrumb($name, $link) {
		$this->_crumbs[] = array($name, $link);
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
	}
/**
 * Creates a link to an external resource
 *
 * @param  string  $title The title of the external resource
 * @param  mixed   $url   The address of the external resource
 * @param  array   $attributes
 * @return string
 */
	function meta($title = null, $url = null, $attributes = array(), $inline = true) {
		$types = array(
			'html'	=> 'text/html',
			'rss'	=> 'application/rss+xml',
			'atom'	=> 'application/atom+xml',
			'icon'	=> 'image/x-icon'
		);

		if (!isset($attributes['type']) && is_array($url) && isset($url['ext'])) {
			if (in_array($url['ext'], array_keys($types))) {
				$attributes['type'] = $url['ext'];
			} else {
				$attributes['type'] = 'rss';
			}
		} elseif (!isset($attributes['type'])) {
			$attributes['type'] = 'rss';
		}

		if (isset($attributes['type']) && in_array($attributes['type'], array_keys($types))) {
			$attributes['type'] = $types[$attributes['type']];
		}

		if (!isset($attributes['rel'])) {
			$attributes['rel'] = 'alternate';
		}
		$out = $this->output(sprintf($this->tags['metalink'], $this->url($url, true), $title, $this->_parseAttributes($attributes)));

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
 * @param  string  $charset
 * @return string
 */
	function charset($charset = null) {
		if(is_null($charset)){
			$charset = Configure::read('charset');
			if(is_null($charset)){
				$charset = 'utf-8';
			}
		}

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
 * @param  string  $title The content of the A tag.
 * @param  mixed   $url Cake-relative URL or array of URL parameters, or external URL (starts with http://)
 * @param  array   $htmlAttributes Array of HTML attributes.
 * @param  string  $confirmMessage Confirmation message.
 * @param  boolean $escapeTitle	Whether or not the text in the $title variable should be HTML escaped.
 * @return string	An <a /> element.
 */
	function link($title, $url = null, $htmlAttributes = array(), $confirmMessage = false, $escapeTitle = true) {
		if($url !== null) {
			$url = $this->url($url);
		} else {
			$url = $this->url($title);
			$title = $url;
			$escapeTitle = false;
		}

		if(isset($htmlAttributes['escape'])) {
			$escapeTitle = $htmlAttributes['escape'];
			unset($htmlAttributes['escape']);
		}
		if($escapeTitle === true) {
			$title = htmlspecialchars($title, ENT_QUOTES);
		} else if (is_string($escapeTitle)) {
			$title = htmlentities($title, $escapeTitle);
		}

		if(!empty($htmlAttributes['confirm'])) {
			$confirmMessage = $htmlAttributes['confirm'];
			unset($htmlAttributes['confirm']);
		}
		if ($confirmMessage) {
			$confirmMessage = htmlspecialchars($confirmMessage, ENT_NOQUOTES);
			$confirmMessage = str_replace("'", "\'", $confirmMessage);
			$confirmMessage = str_replace('"', '&quot;', $confirmMessage);
			$htmlAttributes['onclick'] = "return confirm('{$confirmMessage}');";
		} elseif (isset($htmlAttributes['default'])) {
			if ($htmlAttributes['default'] == false) {
				if (isset($htmlAttributes['onclick'])) {
					$htmlAttributes['onclick'] .= ' return false;';
				} else {
					$htmlAttributes['onclick'] = 'return false;';
				}
				unset($htmlAttributes['default']);
			}
		}
		return $this->output(sprintf($this->tags['link'], $url, $this->_parseAttributes($htmlAttributes), $title));
	}
/**
 * Creates a link element for CSS stylesheets.
 *
 * @param string $path Path to CSS file
 * @param string $rel Rel attribute. Defaults to "stylesheet".
 * @param array $htmlAttributes Array of HTML attributes.
 * @param boolean $inline
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
		$url = "{$this->webroot}" . (COMPRESS_CSS ? 'c' : '') . $this->themeWeb  . CSS_URL . $path . ".css";

		if ($rel == 'import') {
			$out = sprintf($this->tags['style'], $this->parseHtmlOptions($htmlAttributes, null, '', ' '), '@import url(' . $url . ');');
		} else {
			if ($rel == null) {
				$rel = 'stylesheet';
			}
			$out = sprintf($this->tags['css'], $rel, $url, $this->parseHtmlOptions($htmlAttributes, null, '', ' '));
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
	function style($data) {
		if (!is_array($data)) {
			return $data;
		}
	}
/**
 * @deprecated
 * @see FormHelper::submit
 */
	function submit($caption = 'Submit', $htmlAttributes = array()) {
		$htmlAttributes['value'] = $caption;
		return $this->output(sprintf($this->tags['submit'], $this->_parseAttributes($htmlAttributes, null, '', ' ')));
	}
/**
 * Creates a password input widget.
 *
 * @param  string  $fieldName Name of a field, like this "Modelname/fieldname"
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @return string
 */
	function password($fieldName, $htmlAttributes = array()) {
		$htmlAttributes = $this->__value($htmlAttributes, $fieldName);
		$htmlAttributes = $this->domId($htmlAttributes);
		if ($this->tagIsInvalid()) {
			$htmlAttributes = $this->addClass($htmlAttributes, 'form_error');
		}
		return $this->output(sprintf($this->tags['password'], $this->model(), $this->field(), $this->_parseAttributes($htmlAttributes, null, ' ', ' ')));
	}
/**
 * Creates a textarea widget.
 *
 * @param  string  $fieldName Name of a field, like this "Modelname/fieldname"
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @return string
 */
	function textarea($fieldName, $htmlAttributes = array()) {
		$htmlAttributes = $this->__value($htmlAttributes, $fieldName);

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
 * @param  string  $fieldName Name of a field, like this "Modelname/fieldname"
 * @deprecated  string  $title
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @return string
 */
	function checkbox($fieldName, $title = null, $htmlAttributes = array()) {
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
			if (isset($htmlAttributes['value']) || (!class_exists($model) && !loadModel($model))) {
				$htmlAttributes['checked'] = ($htmlAttributes['value'] == $value) ? 'checked' : null;

				if ($htmlAttributes['value'] == '0') {
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
 * Creates file input widget.
 *
 * @param string $fieldName Name of a field, like this "Modelname/fieldname"
 * @param array $htmlAttributes Array of HTML attributes.
 * @return string
 */
	function file($fieldName, $htmlAttributes = array()) {
		trigger_error(__('(HtmlHelper::file) Deprecated: Use FormHelper::file instead'), E_USER_WARNING);
		if (strpos($fieldName, '/')) {
			$this->setFormTag($fieldName);
			$htmlAttributes = $this->domId($htmlAttributes);
			return $this->output(sprintf($this->tags['file'], $this->model(), $this->field(), $this->_parseAttributes($htmlAttributes, null, '', ' ')));
		}
		return $this->output(sprintf($this->tags['file_no_model'], $fieldName, $this->_parseAttributes($htmlAttributes, null, '', ' ')));
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

			foreach($this->_crumbs as $crumb) {
				$out[] = $this->link($crumb[0], $crumb[1]);
			}
			return $this->output(join($separator, $out));
		} else {
			return null;
		}
	}
/**
 * Creates a hidden input field.
 *
 * @param  string  $fieldName Name of a field, like this "Modelname/fieldname"
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @return string
 */
	function hidden($fieldName, $htmlAttributes = array()) {
		$htmlAttributes = $this->__value($htmlAttributes, $fieldName);
		$htmlAttributes = $this->domId($htmlAttributes);
		return $this->output(sprintf($this->tags['hidden'], $this->model(), $this->field(), $this->_parseAttributes($htmlAttributes, null, ' ', ' ')));
	}
/**
 * Creates a formatted IMG element.
 *
 * @param string $path Path to the image file, relative to the webroot/img/ directory.
 * @param array	$htmlAttributes Array of HTML attributes.
 * @return string
 */
	function image($path, $htmlAttributes = array()) {
		if (strpos($path, '://')) {
			$url = $path;
		} else {
			$url = $this->webroot . $this->themeWeb . IMAGES_URL . $path;
		}

		if (!isset($htmlAttributes['alt'])) {
			$htmlAttributes['alt'] = '';
		}
		return $this->output(sprintf($this->tags['image'], $url, $this->parseHtmlOptions($htmlAttributes, null, '', ' ')));
	}
/**
 * Creates a text input widget.
 *
 * @param string $fieldNamem Name of a field, like this "Modelname/fieldname"
 * @param array $htmlAttributes Array of HTML attributes.
 * @return string
 */
	function input($fieldName, $htmlAttributes = array()) {
		$htmlAttributes = $this->__value($htmlAttributes, $fieldName);
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
 * Creates a set of radio widgets.
 *
 * @param  string  $fieldName Name of a field, like this "Modelname/fieldname"
 * @param  array	$options			Radio button options array
 * @param  array	$inbetween		String that separates the radio buttons.
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @return string
 */
	function radio($fieldName, $options, $inbetween = null, $htmlAttributes = array()) {

		$this->setFormTag($fieldName);
		$value = isset($htmlAttributes['value']) ? $htmlAttributes['value'] : $this->tagValue($fieldName);
		$out = array();

		foreach($options as $optValue => $optTitle) {
			$optionsHere = array('value' => $optValue);
			$optValue == $value ? $optionsHere['checked'] = 'checked' : null;
			$parsedOptions = $this->parseHtmlOptions(array_merge($htmlAttributes, $optionsHere), null, '', ' ');
			$individualTagName = $this->field() . "_{$optValue}";
			$out[] = sprintf($this->tags['radio'], $this->model(), $this->field(), $individualTagName, $parsedOptions, $optTitle);
		}

		$out = join($inbetween, $out);
		return $this->output($out ? $out : null);
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
		foreach($names as $arg) {
			$out[] = sprintf($this->tags['tableheader'], $this->parseHtmlOptions($thOptions), $arg);
		}
		$data = sprintf($this->tags['tablerow'], $this->parseHtmlOptions($trOptions), join(' ', $out));
		return $this->output($data);
	}
/**
 * Returns a formatted string of table rows (TR's with TD's in them).
 *
 * @param array $data		Array of table data
 * @param array $oddTrOptionsHTML options for odd TR elements
 * @param array $evenTrOptionsHTML options for even TR elements
 * @return string	Formatted HTML
 */
	function tableCells($data, $oddTrOptions = null, $evenTrOptions = null) {
		if (empty($data[0]) || !is_array($data[0])) {
			$data = array($data);
		}
		static $count = 0;

		foreach($data as $line) {
			$count++;
			$cellsOut = array();

			foreach($line as $cell) {
				$cellsOut[] = sprintf($this->tags['tablecell'], null, $cell);
			}
			$options = $this->parseHtmlOptions($count % 2 ? $oddTrOptions : $evenTrOptions);
			$out[] = sprintf($this->tags['tablerow'], $options, join(' ', $cellsOut));
		}
		return $this->output(join("\n", $out));
	}
/**
 * Returns value of $fieldName. False if the tag does not exist.
 *
 * @param string $fieldName		Fieldname as "Modelname/fieldname" string
 * @return unknown Value of the named tag.
 */
	function tagValue($fieldName) {
		$this->setFormTag($fieldName);
		if (isset($this->data[$this->model()][$this->field()])) {
			return h($this->data[$this->model()][$this->field()]);
		}
		return false;
	}
/**
 * Returns number of errors in a submitted FORM.
 *
 * @return int Number of errors
 */
	function validate() {
		$args = func_get_args();
		$errors = call_user_func_array(array(&$this, 'validateErrors'),  $args);
		return count($errors);
	}
/**
 * Validates a FORM according to the rules set up in the Model.
 *
 * @return int Number of errors
 */
	function validateErrors() {
		$objects = func_get_args();
		if (!count($objects)) {
			return false;
		}

		$errors = array();
		foreach($objects as $object) {
			$errors = array_merge($errors, $object->invalidFields($object->data));
		}
		return $this->validationErrors = (count($errors) ? $errors : false);
	}
/**
 * Returns a formatted error message for given FORM field, NULL if no errors.
 *
 * @param string $field A field name, like "Modelname/fieldname"
 * @param string $text		Error message
 * @return string If there are errors this method returns an error message, else NULL.
 */
	function tagErrorMsg($field, $text) {
		$error = 1;
		$this->setFormTag($field);
		if ($error == $this->tagIsInvalid()) {
			return sprintf('<div class="error-message">%s</div>', is_array($text) ? (empty($text[$error - 1]) ? 'Error in field' : $text[$error - 1]) : $text);
		} else {
			return null;
		}
	}
/**#@-*/
/*************************************************************************
 * Renamed methods
 *************************************************************************/
 /**
 * Returns a formatted SELECT element.
 *
 * @param string $fieldName Name attribute of the SELECT
 * @param array $optionElements Array of the OPTION elements (as 'value'=>'Text' pairs) to be used in the SELECT element
 * @param mixed $selected Selected option
 * @param array $selectAttr Array of HTML options for the opening SELECT element
 * @param array $optionAttr Array of HTML options for the enclosed OPTION elements
 * @param boolean $show_empty If true, the empty select option is shown
 * @return string Formatted SELECT element
 */
	function selectTag($fieldName, $optionElements, $selected = array(), $selectAttr = array(), $optionAttr = array(), $showEmpty = true) {
		$this->setFormTag($fieldName);
		if ($this->tagIsInvalid()) {
			if (isset($selectAttr['class']) && trim($selectAttr['class']) != "") {
				$selectAttr['class'] .= ' form_error';
			} else {
				$selectAttr['class'] = 'form_error';
			}
		}
		$selectAttr = $this->domId($selectAttr);

		if (!is_array($optionElements)) {
			return null;
		}

		if (!isset($selected)) {
			$selected = $this->tagValue($fieldName);
		}
		if (isset($selectAttr) && array_key_exists("multiple", $selectAttr)) {
			$select[] = sprintf($this->tags['selectmultiplestart'], $this->model(), $this->field(), $this->parseHtmlOptions($selectAttr));
		} else {
			$select[] = sprintf($this->tags['selectstart'], $this->model(), $this->field(), $this->parseHtmlOptions($selectAttr));
		}

		if ($showEmpty == true) {
			$select[] = sprintf($this->tags['selectempty'], $this->parseHtmlOptions($optionAttr));
		}

		foreach($optionElements as $name => $title) {
			$optionsHere = $optionAttr;

			if (($selected != null) && ($selected == $name)) {
				$optionsHere['selected'] = 'selected';
			} else if(is_array($selected) && in_array($name, $selected)) {
				$optionsHere['selected'] = 'selected';
			}

			$select[] = sprintf($this->tags['selectoption'], $name, $this->parseHtmlOptions($optionsHere), h($title));
		}

		$select[] = sprintf($this->tags['selectend']);
		return $this->output(implode("\n", $select));
	}
/*************************************************************************
 * Deprecated methods
 *************************************************************************/
/**
 * Returns an HTML FORM element.
 *
 * @param string $target URL for the FORM's ACTION attribute.
 * @param string $type		FORM type (POST/GET).
 * @param array  $htmlAttributes
 * @return string An formatted opening FORM tag.
 * @deprecated
 * @see FormHelper::create
 */
	function formTag($target = null, $type = 'post', $htmlAttributes = array()) {
		trigger_error(__('(HtmlHelper::formTag) Deprecated: Use FormHelper::create instead'), E_USER_WARNING);
		$htmlAttributes['action'] = $this->url($target);
		$htmlAttributes['method'] = low($type) == 'get' ? 'get' : 'post';
		$type == 'file' ? $htmlAttributes['enctype'] = 'multipart/form-data' : null;
		$token = '';

		if (isset($this->params['_Token']) && !empty($this->params['_Token'])) {
			$token = $this->hidden('_Token/key', array('value' => $this->params['_Token']['key']), true);
		}

		return sprintf($this->tags['form'], $this->parseHtmlOptions($htmlAttributes, null, '')) . $token;
	}

/**
 * Generates a nested unordered list tree from an array.
 *
 * @param array	$data
 * @param array	$htmlAttributes
 * @param string  $bodyKey
 * @param string  $childrenKey
 * @return string
 * @deprecated This seems useless. Version 0.9.2.
 */
	function guiListTree($data, $htmlAttributes = array(), $bodyKey = 'body', $childrenKey = 'children') {
		$out="<ul" . $this->_parseAttributes($htmlAttributes) . ">\n";
		foreach($data as $item) {
			$out .= "<li>{$item[$bodyKey]}\n";
			if (isset($item[$childrenKey]) && is_array($item[$childrenKey]) && count($item[$childrenKey])) {
				$out .= $this->guiListTree($item[$childrenKey], $htmlAttributes, $bodyKey, $childrenKey);
			}
			$out .= "</li>\n";
		}
		$out .= "</ul>\n";
		return $this->output($out);
	}
/**
 * Returns a mailto: link.
 *
 * @param string $title Title of the link, or the e-mail address (if the same).
 * @param string $email E-mail address if different from title.
 * @param array  $options
 * @return string Formatted A tag
 * @deprecated
 * @see HtmlHelper::link
 */
	function linkEmail($title, $email = null, $options = null) {
		trigger_error(__('(HtmlHelper::linkEmail) Deprecated: Use HtmlHelper::link instead'), E_USER_WARNING);
		// if no $email, then title contains the email.
		if (empty($email)) {
			$email = $title;
		}

		// does the address contain extra attributes?
		$match = array();
		preg_match('!^(.*)(\?.*)$!', $email, $match);

		// plaintext
		if (empty($options['encode']) || !empty($match[2])) {
			return sprintf($this->tags['mailto'], $email, $this->parseHtmlOptions($options), $title);
		} else {
			// encoded to avoid spiders
			$email_encoded = null;

			for($ii = 0; $ii < strlen($email); $ii++) {
				if (preg_match('!\w!', $email[$ii])) {
					$email_encoded .= '%' . bin2hex($email[$ii]);
				} else {
					$email_encoded .= $email[$ii];
				}
			}

			$title_encoded = null;

			for($ii = 0; $ii < strlen($title); $ii++) {
				$title_encoded .= preg_match('/^[A-Za-z0-9]$/', $title[$ii]) ? '&#x' . bin2hex($title[$ii]) . ';' : $title[$ii];
			}
			return sprintf($this->tags['mailto'], $email_encoded, $this->parseHtmlOptions($options, array('encode')), $title_encoded);
		}
	}

/* NEW METHODS */

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
		if ($escape) {
			$text = h($text);
		}
		if ($class != null && !empty($class)) {
			$attributes['class'] = $class;
		}
		if ($text === null) {
			$tag = 'blockstart';
		} else {
			$tag = 'block';
		}
		return $this->output(sprintf($this->tags[$tag], $this->parseHtmlOptions($attributes, null, ' ', ''), $text));
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
		return $this->output(sprintf($this->tags[$tag], $this->parseHtmlOptions($attributes, null, ' ', ''), $text));
	}
/**
 * Returns a SELECT element for days.
 *
 * @param string $tagName Prefix name for the SELECT element
 * @deprecated  string $value
 * @param string $selected Option which is selected.
 * @param array $optionAttr Attribute array for the option elements.
 * @param boolean $show_empty Show/hide the empty select option
 * @return string
 */
	function dayOptionTag($tagName, $value = null, $selected = null, $selectAttr = null, $optionAttr = null, $showEmpty = true) {
		if (empty($selected) && $this->tagValue($tagName)) {
			$selected = date('d', strtotime($this->tagValue($tagName)));
		}
		$dayValue = empty($selected) ? ($showEmpty == true ? NULL : date('d')) : $selected;
		$days = array('01' => '1', '02' => '2', '03' => '3', '04' => '4', '05' => '5', '06' => '6', '07' => '7', '08' => '8', '09' => '9', '10' => '10', '11' => '11', '12' => '12', '13' => '13', '14' => '14', '15' => '15', '16' => '16', '17' => '17', '18' => '18', '19' => '19', '20' => '20', '21' => '21', '22' => '22', '23' => '23', '24' => '24', '25' => '25', '26' => '26', '27' => '27', '28' => '28', '29' => '29', '30' => '30', '31' => '31');
		$option = $this->selectTag($tagName . "_day", $days, $dayValue, $selectAttr, $optionAttr, $showEmpty);
		return $option;
	}
/**
 * Returns a SELECT element for years
 *
 * @param string $tagName Prefix name for the SELECT element
 * @deprecated  string $value
 * @param integer $minYear First year in sequence
 * @param integer $maxYear Last year in sequence
 * @param string $selected Option which is selected.
 * @param array $optionAttr Attribute array for the option elements.
 * @param boolean $showEmpty Show/hide the empty select option
 * @return string
 */
	function yearOptionTag($tagName, $value = null, $minYear = null, $maxYear = null, $selected = null, $selectAttr = null, $optionAttr = null, $showEmpty = true) {
		if (empty($selected) && ($this->tagValue($tagName))) {
			$selected = date('Y', strtotime($this->tagValue($tagName)));
		}

		$yearValue = empty($selected) ? ($showEmpty ? NULL : date('Y')) : $selected;
		$currentYear = date('Y');
		$maxYear = is_null($maxYear) ? $currentYear + 11 : $maxYear + 1;
		$minYear = is_null($minYear) ? $currentYear - 60 : $minYear;

		if ($minYear > $maxYear) {
			$tmpYear = $minYear;
			$minYear = $maxYear;
			$maxYear = $tmpYear;
		}

		$minYear = $currentYear < $minYear ? $currentYear : $minYear;
		$maxYear = $currentYear > $maxYear ? $currentYear : $maxYear;

		for($yearCounter = $minYear; $yearCounter < $maxYear; $yearCounter++) {
			$years[$yearCounter] = $yearCounter;
		}

		$option = $this->selectTag($tagName . "_year", $years, $yearValue, $selectAttr, $optionAttr, $showEmpty);
		return $option;
	}
/**
 * Returns a SELECT element for months.
 *
 * @param string $tagName Prefix name for the SELECT element
 * @deprecated  string $value
 * @param string $selected Option which is selected.
 * @param array $optionAttr Attribute array for the option elements.
 * @param boolean $showEmpty Show/hide the empty select option
 * @return string
 */
	function monthOptionTag($tagName, $value = null, $selected = null, $selectAttr = null, $optionAttr = null, $showEmpty = true) {
		if (empty($selected) && ($this->tagValue($tagName))) {
			$selected = date('m', strtotime($this->tagValue($tagName)));
		}
		$monthValue = empty($selected) ? ($showEmpty ? NULL : date('m')) : $selected;
		$months = array('01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December');

		$option = $this->selectTag($tagName . "_month", $months, $monthValue, $selectAttr, $optionAttr, $showEmpty);
		return $option;
	}
/**
 * Returns a SELECT element for hours.
 *
 * @param string $tagName Prefix name for the SELECT element
 * @deprecated  string $value
 * @param boolean $format24Hours True for 24 hours format
 * @param string $selected Option which is selected.
 * @param array $optionAttr Attribute array for the option elements.
 * @return string
 */
	function hourOptionTag($tagName, $value = null, $format24Hours = false, $selected = null, $selectAttr = null, $optionAttr = null, $showEmpty = true) {
		if (empty($selected) && ($this->tagValue($tagName))) {
			if ($format24Hours) {
				$selected = date('H', strtotime($this->tagValue($tagName)));
			} else {
				$selected = date('g', strtotime($this->tagValue($tagName)));
			}
		}
		if ($format24Hours) {
			$hourValue = empty($selected) ? ($showEmpty ? NULL : date('H')) : $selected;
		} else {
			$hourValue = empty($selected) ? ($showEmpty ? NULL : date('g')) : $selected;
			if (isset($selected) && intval($hourValue) == 0 && !$showEmpty) {
				$hourValue = 12;
			}
		}

		if ($format24Hours) {
			$hours = array('00' => '00', '01' => '01', '02' => '02', '03' => '03', '04' => '04', '05' => '05', '06' => '06', '07' => '07', '08' => '08', '09' => '09', '10' => '10', '11' => '11', '12' => '12', '13' => '13', '14' => '14', '15' => '15', '16' => '16', '17' => '17', '18' => '18', '19' => '19', '20' => '20', '21' => '21', '22' => '22', '23' => '23');
		} else {
			$hours = array('01' => '1', '02' => '2', '03' => '3', '04' => '4', '05' => '5', '06' => '6', '07' => '7', '08' => '8', '09' => '9', '10' => '10', '11' => '11', '12' => '12');
		}

		$option = $this->selectTag($tagName . "_hour", $hours, $hourValue, $selectAttr, $optionAttr, $showEmpty);
		return $option;
	}
/**
 * Returns a SELECT element for minutes.
 *
 * @param string $tagName Prefix name for the SELECT element
 * @deprecated  string $value
 * @param string $selected Option which is selected.
 * @param array $optionAttr Attribute array for the option elements.
 * @return string
 */
	function minuteOptionTag($tagName, $value = null, $selected = null, $selectAttr = null, $optionAttr = null, $showEmpty = true) {
		if (empty($selected) && ($this->tagValue($tagName))) {
			$selected = date('i', strtotime($this->tagValue($tagName)));
		}
		$minValue = empty($selected) ? ($showEmpty ? NULL : date('i')) : $selected;

		for($minCount = 0; $minCount < 60; $minCount++) {
			$mins[$minCount] = sprintf('%02d', $minCount);
		}
		$option = $this->selectTag($tagName . "_min", $mins, $minValue, $selectAttr, $optionAttr, $showEmpty);
		return $option;
	}

/**
 * Returns a SELECT element for AM or PM.
 *
 * @param string $tagName Prefix name for the SELECT element
 * @deprecated  string $value
 * @param string $selected Option which is selected.
 * @param array $optionAttr Attribute array for the option elements.
 * @return string
 */
	function meridianOptionTag($tagName, $value = null, $selected = null, $selectAttr = null, $optionAttr = null, $showEmpty = true) {
		if (empty($selected) && ($this->tagValue($tagName))) {
			$selected = date('a', strtotime($this->tagValue($tagName)));
		}
		$merValue = empty($selected) ? ($showEmpty ? NULL : date('a')) : $selected;
		$meridians = array('am' => 'am', 'pm' => 'pm');

		$option = $this->selectTag($tagName . "_meridian", $meridians, $merValue, $selectAttr, $optionAttr, $showEmpty);
		return $option;
	}
/**
 * Returns a set of SELECT elements for a full datetime setup: day, month and year, and then time.
 *
 * @param string $tagName Prefix name for the SELECT element
 * @param string $dateFormat DMY, MDY, YMD or NONE.
 * @param string $timeFormat 12, 24, NONE
 * @param string $selected Option which is selected.
 * @param array $optionAttr Attribute array for the option elements.
 * @return string The HTML formatted OPTION element
 */
	function dateTimeOptionTag($tagName, $dateFormat = 'DMY', $timeFormat = '12', $selected = null, $selectAttr = null, $optionAttr = null, $showEmpty = true) {
		$day      = null;
		$month    = null;
		$year     = null;
		$hour     = null;
		$min      = null;
		$meridian = null;

		if (empty($selected)) {
			$selected = $this->tagValue($tagName);
		}

		if (!empty($selected)) {

			if (is_int($selected)) {
				$selected = strftime('%Y-%m-%d %H:%M:%S', $selected);
			}

			$meridian = 'am';
			$pos = strpos($selected, '-');
			if($pos !== false){
				$date = explode('-', $selected);
				$days = explode(' ', $date[2]);
				$day = $days[0];
				$month = $date[1];
				$year = $date[0];
			} else {
				$days[1] = $selected;
			}

			if ($timeFormat != 'NONE' && !empty($timeFormat)) {
				$time = explode(':', $days[1]);

				if (($time[0] > 12) && $timeFormat == '12') {
					$time[0] = $time[0] - 12;
					$meridian = 'pm';
				} elseif($time[0] > 12) {
					$meridian = 'pm';
				}

				$hour = $time[0];
				$min = $time[1];
			}
		}

		$elements = array('Day','Month','Year','Hour','Minute','Meridian');
		if (isset($selectAttr['id'])) {
			if (is_string($selectAttr['id'])) {
				// build out an array version
				foreach ($elements as $element) {
					$selectAttrName = 'select' . $element . 'Attr';
					${$selectAttrName} = $selectAttr;
					${$selectAttrName}['id'] = $selectAttr['id'] . $element;
				}
			} elseif (is_array($selectAttr['id'])) {
				// check for missing ones and build selectAttr for each element
				foreach ($elements as $element) {
					$selectAttrName = 'select' . $element . 'Attr';
					${$selectAttrName} = $selectAttr;
					${$selectAttrName}['id'] = $selectAttr['id'][strtolower($element)];
				}
			}
		} else {
			// build the selectAttrName with empty id's to pass
			foreach ($elements as $element) {
				$selectAttrName = 'select' . $element . 'Attr';
				${$selectAttrName} = $selectAttr;
			}
		}

		switch($dateFormat) {
			case 'DMY': // so uses the new selex
				$opt = $this->dayOptionTag($tagName, null, $day, $selectDayAttr, $optionAttr, $showEmpty) . '-' .
				$this->monthOptionTag($tagName, null, $month, $selectMonthAttr, $optionAttr, $showEmpty) . '-' . $this->yearOptionTag($tagName, null, null, null, $year, $selectYearAttr, $optionAttr, $showEmpty);
			break;
			case 'MDY':
				$opt = $this->monthOptionTag($tagName, null, $month, $selectMonthAttr, $optionAttr, $showEmpty) . '-' .
				$this->dayOptionTag($tagName, null, $day, $selectDayAttr, $optionAttr, $showEmpty) . '-' . $this->yearOptionTag($tagName, null, null, null, $year, $selectYearAttr, $optionAttr, $showEmpty);
			break;
			case 'YMD':
				$opt = $this->yearOptionTag($tagName, null, null, null, $year, $selectYearAttr, $optionAttr, $showEmpty) . '-' .
				$this->monthOptionTag($tagName, null, $month, $selectMonthAttr, $optionAttr, $showEmpty) . '-' .
				$this->dayOptionTag($tagName, null, $day, $selectDayAttr, $optionAttr, $showEmpty);
			break;
			case 'Y':
				$opt = $this->yearOptionTag($tagName, null, null, null, $selected, $selectYearAttr, $optionAttr, $showEmpty);
			break;
			case 'NONE':
			default:
				$opt = '';
			break;
		}

		switch($timeFormat) {
			case '24':
				$opt .= $this->hourOptionTag($tagName, null, true, $hour, $selectHourAttr, $optionAttr, $showEmpty) . ':' .
				$this->minuteOptionTag($tagName, null, $min, $selectMinuteAttr, $optionAttr, $showEmpty);
			break;
			case '12':
				$opt .= $this->hourOptionTag($tagName, null, false, $hour, $selectHourAttr, $optionAttr, $showEmpty) . ':' .
				$this->minuteOptionTag($tagName, null, $min, $selectMinuteAttr, $optionAttr, $showEmpty) . ' ' .
				$this->meridianOptionTag($tagName, null, $meridian, $selectMeridianAttr, $optionAttr, $showEmpty);
			break;
			case 'NONE':
			default:
				$opt .= '';
			break;
		}
		return $opt;
	}
}

?>