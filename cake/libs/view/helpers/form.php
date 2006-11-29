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
 * @deprecated 
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

	var $Html = null;

/**
 * Returns an HTML FORM element.
 *
 * @access public
 * @param string $model The model object which the form is being defined for
 * @param array  $options
 * @return string An formatted opening FORM tag.
 */
	function create($model = null, $options = array()) {
		if (empty($model) || is_string($model)) {
			$models = $this->params['models'];
			$model = $models[0];
		}
		
		if (ClassRegistry::isKeySet($model)) {
			$object =& ClassRegistry::getObject($model);
		} else {
			trigger_error('Model '.$model.' does not exist', E_USER_WARNING);
			return;
		}
		$this->setFormTag($model . '/');

		$append = '';
		$created = false;
		$fields = $object->loadInfo();
		$data = array(
			'fields' => array_combine($fields->extract('{n}.name'), $fields->extract('{n}.type')),
			'key' => $object->primaryKey,
			'validates' => array_keys($object->validate)
		);
		
		if (isset($this->data[$model]) && isset($this->data[$model][$data['key']]) && !empty($this->data[$model][$data['key']])) {
			$created = true;
		}
		$options = am(array('type' => ($created && empty($options['action'])) ? 'put' : 'post', 'id' => $model . 'Form', 'action' => array()), $options);

		if (empty($options['action']) || is_array($options['action'])) {
			$actionDefaults = array(
				'controller' => Inflector::underscore($this->params['controller']),
				'action' => $created ? 'edit' : 'create'
			);
			$options['action'] = am($actionDefaults, $options['action']);
		}

		switch (low($options['type'])) {
			case 'get':
				$htmlAttributes['method'] = 'GET';
			break;
			case 'put':
			case 'delete':
				$append .= $this->hidden('method/method', array('value' => up($options['type'])));
				$htmlAttributes['method'] = 'POST';
			break;
			case 'file':
				$htmlAttributes['enctype'] = 'multipart/form-data';
			default:
			case 'post':
				$htmlAttributes['method'] = 'POST';
			break;
		}
		$htmlAttributes['action'] = $this->url($options['action']);
		unset($options['type'], $options['action']);
		$htmlAttributes = am($options, $htmlAttributes);

		if (isset($this->params['_Token']) && !empty($this->params['_Token'])) {
			$append .= $this->hidden('_Token/key', array('value' => $this->params['_Token']['key']));
		}
		return $this->output(sprintf($this->Html->tags['form'], $this->Html->parseHtmlOptions($htmlAttributes, null, ''))) . $append;
	}
/**
 * Closes an HTML form.
 *
 * @access public
 * @return string A closing FORM tag.
 */
	function end($model = null) {
		if (empty($model) && !empty($this->params['models'])) {
			$models = $this->params['models'][0];
		}
		return $this->output($this->Html->tags['formend']);
	}
/**
 * Returns a formatted error message for given FORM field, NULL if no errors.
 *
 * @access public
 * @param string $field This should be "Modelname/fieldname"
 * @return bool If there are errors this method returns true, else false.
 */
	function isFieldError($field) {
		$error = 1;
		$this->setFormTag($field);

		if ($error == $this->tagIsInvalid()) {
			return true;
		} else {
			return false;
		}
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
		return $this->output(sprintf($this->Html->tags['label'], $tagName, $this->_parseAttributes($attributes), $text));
	}
/* Will display all the fields passed in an array expects tagName as an array key
 * replaces generateFields
 *
 * @access public
 * @param array $fields works well with Controller::generateFieldNames();
 * @return output 
 */	
	function fields($fields) {
		$out = null;
		foreach($fields as $name => $options) {
			if(isset($options['tagName'])){
				$tagName = $options['tagName'];
				unset($options['tagName']);
			}
			$out .= $this->input($tagName, $options);
		}
		return $this->output($out);
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
		} else {
			$label = Inflector::humanize($this->field());
		}
		
		$out = $this->label($tagName, $label);

		$error = null;
		if (isset($options['error'])) {
			$error = $options['error'];
			unset($options['error']);
		} else {
			$error = $label . ' is required';
		}
		
		$selected = null;
		if (isset($options['selected'])) {
			$selected = $options['selected'];
			unset($options['selected']);
		}
		
		switch ($options['type']) {
			case 'hidden':
				$wrap = false;
				$out = $this->hidden($tagName);
			break;
			case 'checkbox':
				$out = $this->Html->checkbox($tagName);
				$out .= $this->label($tagName, $label);
			break;
			case 'text':
				$out .= $this->text($tagName, $options);
			break;
			case 'password':
				$out .= $this->password($tagName, $options);
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
			case 'time':
				$out .= $this->Html->dateTimeOptionTag($tagName, null, '12', $selected, $options, null, false);
			break;
			case 'date':
				$out .= $this->Html->dateTimeOptionTag($tagName, 'MDY', null, $selected, $options, null, false);
			break;
			case 'datetime':
				$out .= $this->Html->dateTimeOptionTag($tagName, 'MDY', '12', $selected, $options, null, false);
			break;
			case 'submit':
				$out .= $this->Html->submit($label);
			break;
			case 'textarea':
			default:
				$out .= $this->textarea($tagName, $options);
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
			$htmlAttributes = $this->addClass($htmlAttributes, 'form-error');
		}
		return $this->output(sprintf($this->Html->tags['input'], $this->model(), $this->field(), $this->_parseAttributes($htmlAttributes, null, ' ', ' ')));
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
			$htmlAttributes = $this->addClass($htmlAttributes, 'form-error');
		}
		return $this->output(sprintf($this->Html->tags['password'], $this->model(), $this->field(), $this->_parseAttributes($htmlAttributes, null, ' ', ' ')));
	}
/**
 * Creates a textarea widget.
 *
 * @param string $fieldNamem Name of a field, like this "Modelname/fieldname"
 * @param array $htmlAttributes Array of HTML attributes.
 * @return string An HTML text input element
 */
	function textarea($fieldName, $htmlAttributes = null) {
		$htmlAttributes = $this->__value($htmlAttributes, $fieldName);
		$htmlAttributes = $this->domId($htmlAttributes);
		
		$value = null;
		if (!empty($htmlAttributes['value'])) {
			$value = $htmlAttributes['value'];
			unset($htmlAttributes['value']);
		}

		if ($this->tagIsInvalid()) {
			$htmlAttributes = $this->addClass($htmlAttributes, 'form-error');
		}
		return $this->output(sprintf($this->Html->tags['textarea'], $this->model(), $this->field(), $this->Html->_parseAttributes($htmlAttributes, null, ' '), $value));
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
		return $this->output(sprintf($this->Html->tags['hidden'], $this->model(), $this->field(), $this->_parseAttributes($htmlAttributes, null, ' ', ' ')));
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
		return $this->output(sprintf($this->Html->tags['submitimage'], $url, $this->_parseAttributes($htmlAttributes, null, '', ' ')));
	}
 /**
 * Returns a formatted SELECT element.
 *
 * @param string $fieldName Name attribute of the SELECT
 * @param array $options Array of the OPTION elements (as 'value'=>'Text' pairs) to be used in the SELECT element
 * @param mixed $selected The option selected by default.  If null, the default value
 *                        from POST data will be used when available.
 * @param array $attributes  The HTML attributes of the select element.  If
 *                           'showParents' is included in the array and set to true,
 *                           an additional option element will be added for the parent
 *                           of each option group.
 * @param mixed $showEmpty If true, the empty select option is shown.  If a string,
 *                         that string is displayed as the empty element.
 * @return string Formatted SELECT element
 */
	function select($fieldName, $options = array(), $selected = null, $attributes = array(), $showEmpty = '') {
		$showParents = false;
		$this->setFormTag($fieldName);
		$attributes = $this->domId($attributes);

		if ($this->tagIsInvalid()) {
			$attributes = $this->addClass($attributes, 'form-error');
		}
		if(!is_array($options)) {
			$options = array();
		}
		if (isset($attributes['showParents']) && $attributes['showParents']) {
			unset($attributes['showParents']);
			$showParents = true;
		}

		if (!isset($selected)) {
			$selected = $this->__value($fieldName);
		}
		if (isset($attributes) && array_key_exists("multiple", $attributes)) {
			$tag = $this->Html->tags['selectmultiplestart'];
		} else {
			$tag = $this->Html->tags['selectstart'];
		}
		$select[] = sprintf($tag, $this->model(), $this->field(), $this->Html->parseHtmlOptions($attributes));

		if ($showEmpty !== null && $showEmpty !== false) {
			if($showEmpty === true) {
				$showEmpty = '';
			}
			array_unshift($options, ' ');
		}
		$select = am($select, $this->__selectOptions(array_reverse($options, true), $selected, array(), $showParents));
		$select[] = sprintf($this->Html->tags['selectend']);
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
					$select[] = $this->Html->tags['optiongroupend'];
					$parents[] = $name;
				}
				$select = am($select, $this->__selectOptions($title, $selected, $parents, $showParents));
				if (!empty($name)) {
					$select[] = sprintf($this->Html->tags['optiongroup'], $name, '');
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
					$select[] = sprintf($this->Html->tags['selectoption'], $name, $this->Html->parseHtmlOptions($htmlOptions), h($title));
				}
			}
		}

		return array_reverse($select, true);
	}
/**
 * @deprecated
 * @see FormHelper::input()
 */
	function generateInputDiv($tagName, $prompt, $required = false, $errorMsg = null, $size = 20, $htmlOptions = null) {
		trigger_error('Deprecated: Use FormHelper::input() or FormHelper::text() instead', E_USER_WARNING);
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
 * @deprecated
 * @see FormHelper::input()
 */
	function generateCheckboxDiv($tagName, $prompt, $required = false, $errorMsg = null, $htmlOptions = null) {
		trigger_error('Deprecated: Use FormHelper::input() or FormHelper::checkbox() instead', E_USER_WARNING);
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
 * @deprecated
 * @see FormHelper::input()
 */
	function generateDate($tagName, $prompt, $required = false, $errorMsg = null, $size = 20, $htmlOptions = null, $selected = null) {
		trigger_error('Deprecated: Use FormHelper::input() instead', E_USER_WARNING);
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
 * @deprecated
 * @see FormHelper::input()
 */
	function generateTime($tagName, $prompt, $required = false, $errorMsg = null, $size = 20, $htmlOptions = null, $selected = null) {
		trigger_error('Deprecated: Use FormHelper::input() instead', E_USER_WARNING);
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
 * @deprecated
 * @see FormHelper::input()
 */
	function generateDateTime($tagName, $prompt, $required = false, $errorMsg = null, $size = 20, $htmlOptions = null, $selected = null) {
		trigger_error('Deprecated: Use FormHelper::input() instead', E_USER_WARNING);
		$htmlOptions['id']=strtolower(str_replace('/', '_', $tagName));
		$str = $this->Html->dateTimeOptionTag($tagName, 'MDY', '12', $selected, $htmlOptions, null, false);
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
 * @deprecated
 * @see FormHelper::input()
 */
	function generateAreaDiv($tagName, $prompt, $required = false, $errorMsg = null, $cols = 60, $rows = 10, $htmlOptions = null) {
		trigger_error('Deprecated: Use FormHelper::input() instead', E_USER_WARNING);
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
 * @deprecated
 * @see FormHelper::input()
 */
	function generateSelectDiv($tagName, $prompt, $options, $selected = null, $selectAttr = null, $optionAttr = null, $required = false, $errorMsg = null) {
		trigger_error('Deprecated: Use FormHelper::input() or FormHelper::select() instead', E_USER_WARNING);
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
 * @deprecated
 * @see FormHelper::input()
 */
	function generateSubmitDiv($displayText, $htmlOptions = null) {
		//trigger_error('Deprecated: Use FormHelper::input() or FormHelper::submit() instead', E_USER_WARNING);
		return $this->divTag('submit', $this->Html->submit($displayText, $htmlOptions));
	}
/**
 * @deprecated
 * @see FormHelper::fields()
 */
	function generateFields($fields, $readOnly = false) {
		trigger_error('Deprecated: Use FormHelper::input() instead', E_USER_WARNING);
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
 * @deprecated will not be available after 1.1.x.x
 * @see FormHelper::label()
 */
	function labelTag($tagName, $text) {
		trigger_error('Deprecated: Use FormHelper::label() instead', E_USER_WARNING);
		return sprintf($this->Html->tags['label'], Inflector::camelize(r('/', '_', $tagName)), $text);
	}
/**
 * @deprecated
 * @see HtmlHelper::div
 */
	function divTag($class, $text) {
		//trigger_error('(FormHelper::divTag) Deprecated: Use HtmlHelper::div instead', E_USER_WARNING);
		return sprintf(TAG_DIV, $class, $text);
	}
/**
 * @deprecated
 * @see HtmlHelper::para
 */
	function pTag($class, $text) {
		//trigger_error('(FormHelper::pTag) Deprecated: Use HtmlHelper::para instead', E_USER_WARNING);
		return sprintf(TAG_P_CLASS, $class, $text);
	}
}

?>