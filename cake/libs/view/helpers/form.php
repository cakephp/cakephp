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
class FormHelper extends Helper {

	var $helpers = array('Html');
/**
 * Returns a formatted error message for given FORM field, NULL if no errors.
 *
 * @param string $field This should be "Modelname/fieldname"
 * @return bool If there are errors this method returns true, else false.
 */
	function isFieldError($field) {
		$error = 1;
		$this->Html->setFormTag($field);

		if ($error == $this->Html->tagIsInvalid($this->Html->model, $this->Html->field)) {
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
	function label($tagName, $text, $attributes = array()) {
		if (strpos($tagName, '/') !== false) {
			$tagName = Inflector::camelize(r('/', '_', $tagName));
		}
		return $this->output(sprintf($this->tags['label'], $tagName, $this->Html->_parseAttributes($attributes), $text));
	}
/**
 * @deprecated
 */
	function divTag($class, $text) {
		return sprintf(TAG_DIV, $class, $text);
	}
/**
 * Returns a formatted P tag with class for HTML FORMs.
 *
 * @param string $class CSS class name of the p element.
 * @param string $text Text that will appear inside the p element.
 * @return string The formatted P element
 */
	function pTag($class, $text) {
		return sprintf(TAG_P_CLASS, $class, $text);
	}
/**
 * Creates a text input widget.
 *
 * @param string $fieldNamem Name of a field, like this "Modelname/fieldname"
 * @param array $htmlAttributes Array of HTML attributes.
 * @return string An HTML text input element
 */
	function text($fieldName, $htmlAttributes = null) {
		$this->Html->setFormTag($fieldName);

		if (!isset($htmlAttributes['value'])) {
			$htmlAttributes['value'] = $this->tagValue($fieldName);
		}

		if (!isset($htmlAttributes['type'])) {
			$htmlAttributes['type'] = 'text';
		}

		if (!isset($htmlAttributes['id'])) {
			$htmlAttributes['id'] = $this->Html->model . Inflector::camelize($this->Html->field);
		}

		if ($this->Html->tagIsInvalid($this->Html->model, $this->Html->field)) {
			if (isset($htmlAttributes['class']) && trim($htmlAttributes['class']) != "") {
				$htmlAttributes['class'] .= ' form_error';
			} else {
				$htmlAttributes['class'] = 'form_error';
			}
		}

		return sprintf($this->tags['input'], $this->Html->model, $this->Html->field, $this->Html->_parseAttributes($htmlAttributes, null, ' ', ' '));
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
		return sprintf($this->tags['submitimage'], $url, $this->_parseAttributes($htmlAttributes, null, '', ' '));
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
			$strError = $this->pTag('error', $errorMsg);
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
			$strError = $this->pTag('error', $errorMsg);
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
			$strError = $this->pTag('error', $errorMsg);
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
			$strError = $this->pTag('error', $errorMsg);
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
			$strError = $this->pTag('error', $errorMsg);
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
			$strError = $this->pTag('error', $errorMsg);
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
			$strError=$this->pTag('error', $errorMsg);
			$divClass=sprintf("%s error", $divClass);
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
}
?>