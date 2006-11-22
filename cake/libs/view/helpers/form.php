<?php
/* SVN FILE: $Id$ */
/**
 * Automatic generation of HTML FORMs from given data.
 *
 * Used for scaffolding.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs.view.helpers
 * @since			CakePHP v 0.10.0.1076
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/*	Deprecated	*/

/**
 * Tag template for a div with a class attribute.
 */
	define('TAG_DIV', '<div class="%s">%s</div>');
/**
 * Tag template for a paragraph with a class attribute.
 */
	define('TAG_P_CLASS', '<p class="%s">%s</p>');
/**
 * Tag template for a label with a for attribute.
 */
	define('TAG_LABEL', '<label for="%s">%s</label>');
/**
 * Tag template for a fieldset with a legend tag inside.
 */
	define('TAG_FIELDSET', '<fieldset><legend>%s</legend>%s</label>');
/**
 * Form helper library.
 *
 * Automatic generation of HTML FORMs from given data.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.view.helpers
 */
class FormHelper extends AppHelper {

	var $helpers = array('Html');
/**
 * html tags used by this helper.
 *
 * @var array
 */
	var $tags = array('form' => '<form %s>',
							'label' => '<label for="%s"%s>%s</label>',
							'input' => '<input name="data[%s][%s]" %s/>',
							'password' => '<input type="password" name="data[%s][%s]" %s/>',
							'textarea' => '<textarea name="data[%s][%s]" %s>%s</textarea>',
							'submitimage' => '<input type="image" src="%s" %s/>',
							'selectmultiplestart' => '<select name="data[%s][%s][]" %s>',
							'selectstart' => '<select name="data[%s][%s]" %s>',
							'selectend' => '</select>',
							'optiongroupend' => '</optgroup>',
							'optiongroup' => '<optgroup label="%s"%s>',
							'selectoption' => '<option value="%s" %s>%s</option>');

/**
 * Returns an HTML FORM element.
 *
 * @param string $target URL for the FORM's ACTION attribute.
 * @param string $type		FORM type (POST/GET).
 * @param array  $htmlAttributes
 * @return string An formatted opening FORM tag.
 */
	function create($target = null, $type = 'post', $htmlAttributes = null) {
		$htmlAttributes['action'] = $this->url($target);
		$htmlAttributes['method'] = low($type) == 'get' ? 'get' : 'post';
		$type == 'file' ? $htmlAttributes['enctype'] = 'multipart/form-data' : null;
		$token = '';

		if (isset($this->params['_Token']) && !empty($this->params['_Token'])) {
			$token = $this->Html->hidden('_Token/key', array('value' => $this->params['_Token']['key']), true);
		}

		return sprintf($this->tags['form'], $this->Html->parseHtmlOptions($htmlAttributes, null, '')) . $token;
	}
/**
 * Returns a formatted error message for given FORM field, NULL if no errors.
 *
 * @param string $field This should be "Modelname/fieldname"
 * @return bool If there are errors this method returns true, else false.
 */
	function isFieldError($field) {
		$error = 1;
		$this->setFormTag($field);

		if ($error == $this->Html->tagIsInvalid()) {
			return true;
		} else {
			return false;
		}
	}
/**
 * @deprecated
 */
	function labelTag($tagName, $text) {
		return sprintf($this->tags['label'], Inflector::camelize(r('/', '_', $tagName)), $text);
	}
/**
 * Returns a formatted LABEL element for HTML FORMs.
 *
 * @param string $tagName This should be "Modelname/fieldname"
 * @param string $text Text that will appear in the label field.
 * @return string The formatted LABEL element
 */
	function label($tagName, $text = null, $attributes = array()) {
		if ($text == null) {
			if (strpos($tagName, '/') !== false) {
				list( , $text) = explode('/', $tagName);
			} else {
				$text = $tagName;
			}
			$text = Inflector::humanize($text);
		}
		if (strpos($tagName, '/') !== false) {
			$tagName = Inflector::camelize(r('/', '_', $tagName));
		}
		return $this->output(sprintf($this->tags['label'], $tagName, $this->_parseAttributes($attributes), $text));
	}
/**
 * Generates a form input element complete with label and wrapper div
 *
 * @param string $tagName This should be "Modelname/fieldname"
 * @param array $options
 * @return string
 */
	function input($tagName, $options = array()) {

		$this->setFormTag($tagName);

		if (!isset($options['type'])) {
			if (isset($options['options'])) {
				$options['type'] = 'select';
			} elseif ($this->field() == 'passwd' || $this->field() == 'password') {
				$options['type'] = 'password';
			} else {
				$options['type'] = 'text';
			}
		}

		$wrap = true;
		if (isset($options['wrap'])) {
			$wrap = $options['wrap'];
			unset($options['wrap']);
		}

		$divOptions = array();
		if (!isset($options['class']) || empty($options['class'])) {
			$divOptions['class'] = 'input';
		} else {
			$divOptions['class'] = $options['class'];
		}

		$label = null;
		if (isset($options['label'])) {
			$label = $options['label'];
			unset($options['label']);
		}
		$out = $this->label($tagName, $label);

		$error = null;
		if (isset($options['error'])) {
			$error = $options['error'];
			unset($options['error']);
		}

		switch ($options['type']) {
			case 'text':
				$out .= $this->text($tagName);
			break;
			case 'password':
				$out .= $this->password($tagName);
			break;
			case 'file':
				$out .= $this->Html->file($tagName);
			break;
			case 'select':
				$list = $options['options'];
				$empty = (isset($options['empty']) ? $options['empty'] : '');
				unset($options['options'], $options['empty']);
				$out .= $this->select($tagName, $list, null, $options, $empty);
			break;
			case 'textarea':
			default:
				$out .= $this->textarea($tagName);
			break;
		}

		if ($error != null) {
			$out .= $this->Html->tagErrorMsg($tagName, $error);
		}

		if ($wrap) {
			$out = $this->Html->div($divOptions['class'], $out);
		}
		return $this->output($out);
	}
/**
 * Creates a text input widget.
 *
 * @param string $fieldNamem Name of a field, like this "Modelname/fieldname"
 * @param array $htmlAttributes Array of HTML attributes.
 * @return string An HTML text input element
 */
	function text($fieldName, $htmlAttributes = null) {
		$htmlAttributes = $this->__value($htmlAttributes, $fieldName);
		$htmlAttributes = $this->domId($htmlAttributes);

		if (!isset($htmlAttributes['type'])) {
			$htmlAttributes['type'] = 'text';
		}

		if ($this->tagIsInvalid()) {
			$htmlAttributes = $this->Html->addClass($htmlAttributes, 'form_error');
		}
		return $this->output(sprintf($this->tags['input'], $this->model(), $this->field(), $this->_parseAttributes($htmlAttributes, null, ' ', ' ')));
	}
/**
 * Creates a password input widget.
 *
 * @param  string  $fieldName Name of a field, like this "Modelname/fieldname"
 * @param  array	$htmlAttributes Array of HTML attributes.
 * @return string
 */
	function password($fieldName, $htmlAttributes = null) {
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
 * @param string $fieldNamem Name of a field, like this "Modelname/fieldname"
 * @param array $htmlAttributes Array of HTML attributes.
 * @return string An HTML text input element
 */
	function textarea($fieldName, $htmlAttributes = null) {
		$value = $this->Html->tagValue($fieldName);
		if (!empty($htmlAttributes['value'])) {
			$value = $htmlAttributes['value'];
			unset($htmlAttributes['value']);
		}
		$htmlAttributes = $this->domId($htmlAttributes);

		if ($this->tagIsInvalid()) {
			$htmlAttributes = $this->Html->addClass($htmlAttributes, 'form_error');
		}
		return $this->output(sprintf($this->tags['textarea'], $this->model(), $this->field(), $this->Html->_parseAttributes($htmlAttributes, null, ' '), $value));
	}
/**
 * Creates a button tag.
 *
 * @param  mixed  $params  Array of params [content, type, options] or the
 *                         content of the button.
 * @param  string $type    Type of the button (button, submit or reset).
 * @param  array  $options Array of options.
 * @return string A HTML button tag.
 */
	function button($params, $type = 'button', $options = array()) {

		trigger_error('Don\'t use me yet', E_USER_ERROR);
		if (isset($options['name'])) {
			if (strpos($options['name'], "/") !== false) {
				if ($this->fieldValue($options['name'])) {
					$options['checked'] = 'checked';
				}
				$this->setFieldName($options['name']);
				$options['name'] = 'data['.$this->_model.']['.$this->_field.']';
			}
		}

		$options['type'] = $type;

		$values = array(
			'options'  => $this->_parseOptions($options),
			'tagValue' => $content
		);
		return $this->_assign('button', $values);
	}
/**
 * Creates an image input widget.
 *
 * @param  string  $path           Path to the image file, relative to the webroot/img/ directory.
 * @param  array   $htmlAttributes Array of HTML attributes.
 * @return string  HTML submit image element
 */
	function submitImage($path, $htmlAttributes = null) {
		if (strpos($path, '://')) {
			$url = $path;
		} else {
			$url = $this->webroot . $this->themeWeb . IMAGES_URL . $path;
		}
		return $this->output(sprintf($this->tags['submitimage'], $url, $this->_parseAttributes($htmlAttributes, null, '', ' ')));
	}
 /**
 * Returns a formatted SELECT element.
 *
 * @param string $fieldName Name attribute of the SELECT
 * @param array $options Array of the OPTION elements (as 'value'=>'Text' pairs) to be used in the SELECT element
 * @param mixed $selected Selected option
 * @param array $attributes
 * @param boolean $show_empty If true, the empty select option is shown
 * @param boolean $showParents
 * @return string Formatted SELECT element
 */
	function select($fieldName, $options = array(), $selected = null, $attributes = array(), $showEmpty = '', $showParents = null) {
		$this->setFormTag($fieldName);
		$attributes = $this->domId($attributes);

		if ($this->tagIsInvalid()) {
			$attributes = $this->Html->addClass($attributes, 'form_error');
		}

		if (!isset($selected)) {
			$selected = $this->__value($fieldName);
		}

		if (isset($attributes) && array_key_exists("multiple", $attributes)) {
			$tag = $this->tags['selectmultiplestart'];
		} else {
			$tag = $this->tags['selectstart'];
		}
		$select[] = sprintf($tag, $this->model(), $this->field(), $this->Html->parseHtmlOptions($attributes));

		if ($showEmpty !== null && $showEmpty !== false) {
			if($showEmpty === true) {
				$showEmpty = '';
			}
			$options = array_reverse($options, true);
			$options[] = $showEmpty;
			$options = array_reverse($options, true);
		}
		$select = am($select, $this->__selectOptions(array_reverse($options, true), $selected, array(), $showParents));
		$select[] = sprintf($this->tags['selectend']);
		return $this->output(implode("\n", $select));
	}
/**
 * Returns an array of formatted OPTION/OPTGROUP elements
 *
 * @return array
 */
	function __selectOptions($elements = array(), $selected = null, $parents = array(), $showParents = null) {
		$select = array();
		foreach($elements as $name => $title) {
			$htmlOptions = array();
			if (is_array($title) && (!isset($title['name']) || !isset($title['value']))) {
				if (!empty($name)) {
					$select[] = $this->tags['optiongroupend'];
					$parents[] = $name;
				}
				$select = am($select, $this->__selectOptions($title, $selected, $parents, $showParents));
				if (!empty($name)) {
					$select[] = sprintf($this->tags['optiongroup'], $name, '');
				}
				$name = null;
			} elseif (is_array($title)) {
				$htmlOptions = $title;
				$name = $title['value'];
				$title = $title['name'];
				unset($htmlOptions['name'], $htmlOptions['value']);
			}
			if ($name !== null) {
				if (($selected !== null) && ($selected == $name)) {
					$htmlOptions['selected'] = 'selected';
				} else if(is_array($selected) && in_array($name, $selected)) {
					$htmlOptions['selected'] = 'selected';
				}

				if($showParents || (!in_array($title, $parents))) {
					$select[] = sprintf($this->tags['selectoption'], $name, $this->Html->parseHtmlOptions($htmlOptions), h($title));
				}
			}
		}

		return array_reverse($select, true);
	}
/**
 * Returns a formatted INPUT tag for HTML FORMs.
 *
 * @param string $tagName	This should be "Modelname/fieldname"
 * @param string $prompt Text that will appear in the label field.
 * @param bool $required True if this field is a required field.
 * @param string $errorMsg	Text that will appear if an error has occurred.
 * @param int $size Size attribute for INPUT element
 * @param array $htmlOptions	HTML options array.
 * @return string The formatted INPUT element, with a label and wrapped in a div.
 */
	function generateInputDiv($tagName, $prompt, $required = false, $errorMsg = null, $size = 20, $htmlOptions = null) {
		$htmlOptions['id'] = strtolower(str_replace('/', '_', $tagName));
		$htmlAttributes = $htmlOptions;
		$htmlAttributes['size'] = $size;
		$str = $this->Html->input($tagName, $htmlAttributes);
		$strLabel = $this->label($tagName, $prompt);
		$divClass = "optional";
		if ($required) {
			$divClass = "required";
		}
		$strError = "";

		if ($this->isFieldError($tagName)) {
			$strError = $this->Html->para('error', $errorMsg);
			$divClass = sprintf("%s error", $divClass);
		}
		$divTagInside = sprintf("%s %s %s", $strError, $strLabel, $str);
		return $this->divTag($divClass, $divTagInside);
	}
/**
 * Returns a formatted CHECKBOX tag inside a DIV for HTML FORMs.
 *
 * @param string $tagName This should be "Modelname/fieldname"
 * @param string $prompt Text that will appear in the label field.
 * @param bool $required True if this field is a required field.
 * @param string $errorMsg Text that will appear if an error has occurred.
 * @param array $htmlOptions	HTML options array.
 * @return string The formatted checkbox div
 */
	function generateCheckboxDiv($tagName, $prompt, $required = false, $errorMsg = null, $htmlOptions = null) {
		$htmlOptions['class'] = "inputCheckbox";
		$htmlOptions['id'] = strtolower(str_replace('/', '_', $tagName));
		$str = $this->Html->checkbox($tagName, null, $htmlOptions);
		$strLabel = $this->label($tagName, $prompt);
		$divClass = "optional";
		if ($required) {
			$divClass = "required";
		}
		$strError = "";

		if ($this->isFieldError($tagName)) {
			$strError = $this->Html->para('error', $errorMsg);
			$divClass = sprintf("%s error", $divClass);
		}
		$divTagInside = sprintf("%s %s %s", $strError, $strLabel, $str);
		return $this->divTag($divClass, $divTagInside);
	}
/**
 * Returns a formatted date option element for HTML FORMs.
 *
 * @param string $tagName	This should be "Modelname/fieldname"
 * @param string $prompt Text that will appear in the label field.
 * @param bool $required True if this field is a required field.
 * @param string $errorMsg	Text that will appear if an error has occurred.
 * @param int $size Not used.
 * @todo  Remove the $size parameter from this method.
 * @param array $htmlOptions HTML options array
 * @return string Date option wrapped in a div.
 */
	function generateDate($tagName, $prompt, $required = false, $errorMsg = null, $size = 20, $htmlOptions = null, $selected = null) {
		$htmlOptions['id']=strtolower(str_replace('/', '_', $tagName));
		$str = $this->Html->dateTimeOptionTag($tagName, 'MDY', 'NONE', $selected, $htmlOptions);
		$strLabel = $this->label($tagName, $prompt);
		$divClass = "optional";
		if ($required) {
			$divClass = "required";
		}
		$strError = "";

		if ($this->isFieldError($tagName)) {
			$strError = $this->Html->para('error', $errorMsg);
			$divClass = sprintf("%s error", $divClass);
		}
		$divTagInside = sprintf("%s %s %s", $strError, $strLabel, $str);
		$requiredDiv = $this->divTag($divClass, $divTagInside);
		return $this->divTag("date", $requiredDiv);
	}
/**
 * Returns a formatted date option element for HTML FORMs.
 *
 * @param string $tagName	This should be "Modelname/fieldname"
 * @param string $prompt Text that will appear in the label field.
 * @param bool $required True if this field is a required field.
 * @param string $errorMsg	Text that will appear if an error has occurred.
 * @param int $size Not used.
 * @todo  Remove the $size parameter from this method.
 * @param array $htmlOptions HTML options array
 * @return string Date option wrapped in a div.
 */
	function generateTime($tagName, $prompt, $required = false, $errorMsg = null, $size = 20, $htmlOptions = null, $selected = null) {
		$str = $this->Html->dateTimeOptionTag($tagName, 'NONE', '24', $selected, $htmlOptions);
		$strLabel = $this->label($tagName, $prompt);
		$divClass = "optional";
		if ($required) {
			$divClass = "required";
		}
		$strError = "";

		if ($this->isFieldError($tagName)) {
			$strError = $this->Html->para('error', $errorMsg);
			$divClass = sprintf("%s error", $divClass);
		}
		$divTagInside = sprintf("%s %s %s", $strError, $strLabel, $str);
		$requiredDiv = $this->divTag($divClass, $divTagInside);
		return $this->divTag("time", $requiredDiv);
	}





/**
 * Returns a formatted datetime option element for HTML FORMs.
 *
 * @param string $tagName This should be "Modelname/fieldname"
 * @param string $prompt Text that will appear in the label field.
 * @param bool $required True if this field is required.
 * @param string $errorMsg Text that will appear if an error has occurred.
 * @param int $size Not used.
 * @todo  Remove the $size parameter from this method.
 * @param array $htmlOptions  HTML options array
 * @param array $selected Selected index in the dateTimeOption tag.
 * @return string The formatted datetime option element wrapped in a div.
 */
	function generateDateTime($tagName, $prompt, $required = false, $errorMsg = null, $size = 20, $htmlOptions = null, $selected = null) {
		$htmlOptions['id']=strtolower(str_replace('/', '_', $tagName));
		$str = $this->Html->dateTimeOptionTag($tagName, 'MDY', '12', $selected, $htmlOptions);
		$strLabel = $this->label($tagName, $prompt);
		$divClass = "optional";
		if ($required) {
			$divClass = "required";
		}
		$strError = "";

		if ($this->isFieldError($tagName)) {
			$strError = $this->Html->para('error', $errorMsg);
			$divClass = sprintf("%s error", $divClass);
		}
		$divTagInside = sprintf("%s %s %s", $strError, $strLabel, $str);
		$requiredDiv = $this->divTag($divClass, $divTagInside);
		return $this->divTag("date", $requiredDiv);
	}
/**
 * Returns a formatted TEXTAREA inside a DIV for use with HTML forms.
 *
 * @param string $tagName	This should be "Modelname/fieldname"
 * @param string $prompt	Text that will appear in the label field.
 * @param boolean $required	True if this field is required.
 * @param string $errorMsg	ext that will appear if an error has occurred.
 * @param integer $cols		Number of columns.
 * @param integer $rows		Number of rows.
 * @param array $htmlOptions	HTML options array.
 * @return string The formatted TEXTAREA element, wrapped in a div.
 */
	function generateAreaDiv($tagName, $prompt, $required = false, $errorMsg = null, $cols = 60, $rows = 10, $htmlOptions = null) {
		$htmlOptions['id'] = strtolower(str_replace('/', '_', $tagName));
		$htmlAttributes = $htmlOptions;
		$htmlAttributes['cols'] = $cols;
		$htmlAttributes['rows'] = $rows;
		$str = $this->Html->textarea($tagName, $htmlAttributes);
		$strLabel = $this->label($tagName, $prompt);
		$divClass = "optional";

		if ($required) {
			$divClass="required";
		}
		$strError = "";

		if ($this->isFieldError($tagName)) {
			$strError = $this->Html->para('error', $errorMsg);
			$divClass = sprintf("%s error", $divClass);
		}
		$divTagInside = sprintf("%s %s %s", $strError, $strLabel, $str);
		return $this->divTag($divClass, $divTagInside);
	}
/**
 * Returns a formatted SELECT tag for HTML FORMs.
 *
 * @param string $tagName This should be "Modelname/fieldname"
 * @param string $prompt Text that will appear in the label field
 * @param array $options Options to be contained in SELECT element
 * @param string $selected Currently selected item
 * @param array $selectAttr Array of HTML attributes for the SELECT element
 * @param array $optionAttr Array of HTML attributes for the OPTION elements
 * @param bool $required True if this field is required
 * @param string $errorMsg Text that will appear if an error has occurred
 * @return string The formatted INPUT element, wrapped in a div
 */
	function generateSelectDiv($tagName, $prompt, $options, $selected = null, $selectAttr = null, $optionAttr = null, $required = false, $errorMsg = null) {
		$selectAttr['id'] = strtolower(str_replace('/', '_', $tagName));
		$str = $this->Html->selectTag($tagName, $options, $selected, $selectAttr, $optionAttr);
		$strLabel = $this->label($tagName, $prompt);
		$divClass = "optional";

		if ($required) {
			$divClass = "required";
		}
		$strError = "";

		if ($this->isFieldError($tagName)) {
			$strError = $this->Html->para('error', $errorMsg);
			$divClass = sprintf("%s error", $divClass);
		}
		$divTagInside = sprintf("%s %s %s", $strError, $strLabel, $str);
		return $this->divTag($divClass, $divTagInside);
	}
/**
 * Returns a formatted submit widget for HTML FORMs.
 *
 * @param string $displayText Text that will appear on the widget
 * @param array $htmlOptions HTML options array
 * @return string The formatted submit widget
 */
	function generateSubmitDiv($displayText, $htmlOptions = null) {
		return $this->divTag('submit', $this->Html->submit($displayText, $htmlOptions));
	}
/**
 * Generates a form to go onto a HtmlHelper object.
 *
 * @param array $fields An array of form field definitions
 * @param boolean $readOnly True if the form should be rendered as READONLY
 * @return string The completed form specified by the $fields parameter
 */
	function generateFields($fields, $readOnly = false) {
		$strFormFields = '';

		foreach($fields as $field) {
			if (isset($field['type'])) {

				if (!isset($field['required'])) {
					$field['required'] = false;
				}

				if (!isset($field['errorMsg'])) {
					$field['errorMsg'] = null;
				}

				if (!isset($field['htmlOptions'])) {
					$field['htmlOptions'] = array();
				}

				if ($readOnly) {
					$field['htmlOptions']['READONLY'] = "readonly";
				}

				switch($field['type']) {
					case "input":
						if (!isset($field['size'])) {
							$field['size'] = 40;
						}
						$strFormFields = $strFormFields . $this->generateInputDiv($field['tagName'], $field['prompt'],
																		$field['required'], $field['errorMsg'], $field['size'], $field['htmlOptions']);
					break;
					case "checkbox":
						$strFormFields = $strFormFields . $this->generateCheckboxDiv($field['tagName'], $field['prompt'],
																		$field['required'], $field['errorMsg'], $field['htmlOptions']);
					break;
					case "select":
					case "selectMultiple":
						if ("selectMultiple" == $field['type']) {
							$field['selectAttr']['multiple'] = 'multiple';
							$field['selectAttr']['class'] = 'selectMultiple';
						}

						if (!isset($field['selected'])) {
							$field['selected'] = null;
						}

						if (!isset($field['selectAttr'])) {
							$field['selectAttr'] = null;
						}

						if (!isset($field['optionsAttr'])) {
							$field['optionsAttr'] = null;
						}

						if ($readOnly) {
							$field['selectAttr']['DISABLED'] = true;
						}

						if (!isset($field['options'])) {
							$field['options'] = null;
						}
						$strFormFields = $strFormFields . $this->generateSelectDiv($field['tagName'], $field['prompt'], $field['options'],
																		$field['selected'], $field['selectAttr'], $field['optionsAttr'], $field['required'], $field['errorMsg']);
					break;
					case "area":
						if (!isset($field['rows'])) {
							$field['rows'] = 10;
						}

						if (!isset($field['cols'])) {
							$field['cols'] = 60;
						}
						$strFormFields = $strFormFields . $this->generateAreaDiv($field['tagName'], $field['prompt'],
																		$field['required'], $field['errorMsg'], $field['cols'], $field['rows'], $field['htmlOptions']);
					break;
					case "fieldset":
						$strFieldsetFields = $this->generateFields($field['fields']);
						$strFieldSet = sprintf(' <fieldset><legend>%s</legend><div class="notes"><h4>%s</h4><p class="last">%s</p></div>%s</fieldset>',
														$field['legend'], $field['noteHeading'], $field['note'], $strFieldsetFields);
						$strFormFields = $strFormFields . $strFieldSet;
					break;
					case "hidden":
						if(!isset($field['value'])){
							$field['value'] = null;
						}
						$strFormFields = $strFormFields . $this->Html->hidden($field['tagName'], $field['value']);
					break;
					case "date":
						if (!isset($field['selected'])) {
							$field['selected'] = null;
						}
						$strFormFields = $strFormFields . $this->generateDate($field['tagName'], $field['prompt'], null,
																		null, null, null, $field['selected']);
					break;
					case "datetime":
						if (!isset($field['selected'])) {
							$field['selected'] = null;
						}
						$strFormFields = $strFormFields . $this->generateDateTime($field['tagName'], $field['prompt'], '', '', '', '', $field['selected']);
					break;
					case "time":
						if (!isset($field['selected'])) {
							$field['selected'] = null;
						}
						$strFormFields = $strFormFields . $this->generateTime($field['tagName'], $field['prompt'], '', '', '', '', $field['selected']);
					break;
					default:
					break;
				}
			}
		}
		return $strFormFields;
	}
/**
 * @deprecated
 */
	function divTag($class, $text) {
		return sprintf(TAG_DIV, $class, $text);
	}
/**
 * @deprecated
 */
	function pTag($class, $text) {
		return sprintf(TAG_P_CLASS, $class, $text);
	}
}
?>