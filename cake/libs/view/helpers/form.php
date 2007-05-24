<?php
/* SVN FILE: $Id$ */
/**
 * Automatic generation of HTML FORMs from given data.
 *
 * Used for scaffolding.
 *
 * PHP versions 4 and 5
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
 * @since			CakePHP(tm) v 0.10.0.1076
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Form helper library.
 *
 * Automatic generation of HTML FORMs from given data.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.view.helpers
 */
class FormHelper extends AppHelper {
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $helpers = array('Html');
/**
 * holds the fields array('field_name'=>'type'), sizes array('field_name'=>'size'),
 * primaryKey and validates array('field_name')
 *
 * @access public
 */
	var $fieldset = array('fields'=>array(), 'sizes'=>array(), 'key'=>'id', 'validates'=>array());
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $__options = array('day' => array(), 'minute' => array(), 'hour' => array(),
									'month' => array(), 'year' => array(), 'meridian' => array());
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $fields = array();
/**
 * Returns an HTML FORM element.
 *
 * @access public
 * @param string $model The model object which the form is being defined for
 * @param array	 $options
 * @return string An formatted opening FORM tag.
 */
	function create($model = null, $options = array()) {
		$defaultModel = null;
		$data = array('fields' => '','key' => '', 'validates' => '');
		$view =& ClassRegistry::getObject('view');

		if (is_array($model) && empty($options)) {
			$options = $model;
			$model = null;
		}

		if (empty($model) && $model !== false && !empty($this->params['models'])) {
			$model = $this->params['models'][0];
			$defaultModel = $this->params['models'][0];
		} elseif (empty($model) && empty($this->params['models'])) {
			$model = false;
		} else if (is_string($model) && (strpos($model, '/') !== false || strpos($model, '.') !== false)) {
			$path = preg_split('/\/|\./', $model);
			$model = $path[count($path) - 1];
		}

		if (ClassRegistry::isKeySet($model)) {
			$object =& ClassRegistry::getObject($model);
		}

		$models = ClassRegistry::keys();
		foreach($models as $currentModel) {
			if (ClassRegistry::isKeySet($currentModel)) {
				$currentObject =& ClassRegistry::getObject($currentModel);
				if(is_a($currentObject, 'Model') && !empty($currentObject->validationErrors)) {
					$this->validationErrors[Inflector::camelize($currentModel)] =& $currentObject->validationErrors;
				}
			}
		}

		$this->setFormTag($model . '.');
		$append = '';
		$created = $id = false;

		if(isset($object)) {
			$fields = $object->loadInfo();
			$fieldNames = $fields->extract('{n}.name');
			$fieldTypes = $fields->extract('{n}.type');
			$fieldLengths = $fields->extract('{n}.length');
			if (!count($fieldNames) || !count($fieldTypes)) {
				 trigger_error(__('(FormHelper::create) Unable to use model field data. If you are using a model without a database table, try implementing loadInfo()', true), E_USER_WARNING);
			}
			if (!count($fieldNames) || !count($fieldLengths) || (count($fieldNames) != count($fieldTypes))) {
				 trigger_error(__('(FormHelper::create) Unable to use model field data. If you are using a model without a database table, try implementing loadInfo()', true), E_USER_WARNING);
			}
			$data = array(
				'fields' => array_combine($fieldNames, $fieldTypes),
				'sizes' => array_combine($fieldNames, $fieldLengths),
				'key' => $object->primaryKey,
				'validates' => (ife(empty($object->validate), array(), array_keys($object->validate)))
			);
			$this->fieldset = $data;
		}

		if (isset($this->data[$model]) && isset($this->data[$model][$data['key']]) && !empty($this->data[$model][$data['key']])) {
			$created = true;
			$id = $this->data[$model][$data['key']];
		}
		$view->modelId = $id;
		$options = am(array(
			'type' => ($created && empty($options['action'])) ? 'put' : 'post',
			'id' => $model . ife($created, 'Edit', 'Add') . 'Form',
			'action' => null,
			'url' => null,
			'default' => true),
		$options);

		if (empty($options['url']) || is_array($options['url'])) {
			$options = (array)$options;
			if (!empty($model) && $model != $defaultModel) {
				$controller = Inflector::underscore(Inflector::pluralize($model));
			} else {
				$controller = Inflector::underscore($this->params['controller']);
			}
			if (empty($options['action'])) {
				$options['action'] = ife($created, 'edit', 'add');
			}

			$actionDefaults = array(
				'plugin' => $this->plugin,
				'controller' => $controller,
				'action' => $options['action'],
				'id' => $id
			);
			if(!empty($options['action']) && !isset($options['id'])) {
				$options['id'] = $model . Inflector::camelize($options['action']) . 'Form';
			}
			$options['action'] = am($actionDefaults, (array)$options['url']);
		} elseif (is_string($options['url'])) {
			$options['action'] = $options['url'];
		}
		unset($options['url']);

		switch (low($options['type'])) {
			case 'get':
				$htmlAttributes['method'] = 'get';
			break;
			case 'file':
				$htmlAttributes['enctype'] = 'multipart/form-data';
				$options['type'] = ife($created, 'put', 'post');
			case 'post':
			case 'put':
			case 'delete':
				//$append .= $this->hidden('_method', array('name' => '_method', 'value' => up($options['type']), 'id' => $options['id'] . 'Method'));
			default:
				$htmlAttributes['method'] = 'post';
			break;
		}

		$htmlAttributes['action'] = $this->url($options['action']);
		unset($options['type'], $options['action']);

		if ($options['default'] == false) {
			if (isset($htmlAttributes['onSubmit'])) {
				$htmlAttributes['onSubmit'] .= ' return false;';
			} else {
				$htmlAttributes['onSubmit'] = 'return false;';
			}
		}
		unset($options['default']);
		$htmlAttributes = am($options, $htmlAttributes);

		if (isset($this->params['_Token']) && !empty($this->params['_Token'])) {
			$append .= '<p style="display: inline; margin: 0px; padding: 0px;">';
			$append .= $this->hidden('_Token/key', array('value' => $this->params['_Token']['key'], 'id' => $options['id'] . 'Token' . mt_rand()));
			$append .= '</p>';
		}

		$this->setFormTag($model . '.');
		return $this->output(sprintf($this->Html->tags['form'], $this->Html->_parseAttributes($htmlAttributes, null, ''))) . $append;
	}
/**
 * Closes an HTML form.
 *
 * @access public
 * @return string A closing FORM tag.
 */
	function end($options = null) {
		if (!empty($this->params['models'])) {
			$models = $this->params['models'][0];
		}

		$submitOptions = true;
		if(!is_array($options)) {
			$submitOptions = $options;
		} else if(isset($options['submit'])) {
			$submitOptions = $options['submit'];
			unset($options['submit']);

			if(isset($options['label'])) {
				$submitOptions = $options['label'];
				unset($options['label']);
			}
		}

		if($submitOptions === true) {
			$submit = 'Submit';
		} else if(is_string($submitOptions)) {
			$submit = $submitOptions;
		}

		if(!is_array($submitOptions)) {
			$submitOptions = array();
		}
		$out = null;

		if(isset($submit)) {
			$out .= $this->submit($submit, $submitOptions);
		}
		$out .= $this->Html->tags['formend'];
		return $this->output($out);
	}
	function secure($fields) {
		$append = '<p style="display: inline; margin: 0px; padding: 0px;">';
		$append .= $this->hidden('_Token/fields', array('value' => urlencode(Security::hash(serialize($fields) . CAKE_SESSION_STRING)), 'id' => 'TokenFields' . mt_rand()));
		$append .= '</p>';
		return $append;
	}
	function __secure($model = null, $options = null) {
		if(!$model) {
			$model = $this->model();
		}

		if(isset($this->params['_Token']) && !empty($this->params['_Token'])) {
			if(!empty($this->params['_Token']['disabledFields'])) {
				foreach ($this->params['_Token']['disabledFields'] as $value) {
					$parts = preg_split('/\/|\./', $value);
					if (count($parts) == 1) {
						if($parts[0] === $this->field()) {
							return;
						}
					} elseif (count($parts) == 2) {
						if($parts[0] === $this->model() && $parts[1] === $this->field()) {
							return;
						}
					}
				}
				if(!is_null($options)) {
					$this->fields[$model][$this->field()] = $options;
					return;
				}
				$this->fields[$model][] = $this->field();
				return;
			}
			if(!is_null($options)) {
				$this->fields[$model][$this->field()] = $options;
				return;
			}
			$this->fields[$model][] = $this->field();
			return;
		}
	}
/**
 * Returns true if there is an error for the given field, otherwise false
 *
 * @access public
 * @param string $field This should be "Modelname.fieldname", "Modelname/fieldname" is deprecated
 * @return bool If there are errors this method returns true, else false.
 */
	function isFieldError($field) {
		$this->setFormTag($field);
		return (bool)$this->tagIsInvalid();
	}
/**
 * Returns a formatted error message for given FORM field, NULL if no errors.
 *
 * @param string $field A field name, like "Modelname.fieldname", "Modelname/fieldname" is deprecated
 * @param string $text		Error message
 * @param array $options	Rendering options for <div /> wrapper tag
 * @return string If there are errors this method returns an error message, otherwise null.
 */
	function error($field, $text = null, $options = array()) {
		$this->setFormTag($field);
		$options = am(array('wrap' => true, 'class' => 'error-message', 'escape' => true), $options);

		if ($error = $this->tagIsInvalid()) {
			if (is_array($text) && is_numeric($error) && $error > 0) {
				$error--;
			}
			if (is_array($text) && isset($text[$error])) {
				$text = $text[$error];
			} else if (is_array($text)) {
				$text = null;
			}

			if ($text != null) {
				$error = $text;
			} elseif (is_numeric($error)) {
				$error = 'Error in field ' . Inflector::humanize($this->field());
			}
			if ($options['escape']) {
				$error = h($error);
			}
			if ($options['wrap'] === true) {
				return $this->Html->div($options['class'], $error);
			} else {
				return $error;
			}
		} else {
			return null;
		}
	}
/**
 * Returns a formatted LABEL element for HTML FORMs.
 *
 * @param string $tagName This should be "Modelname.fieldname", "Modelname/fieldname" is deprecated
 * @param string $text Text that will appear in the label field.
 * @return string The formatted LABEL element
 */
	function label($tagName = null, $text = null, $attributes = array()) {
		if (empty($tagName)) {
			$tagName = implode('.', array_filter(array($this->model(), $this->field())));
		}

		if ($text === null) {
			if (strpos($tagName, '/') !== false || strpos($tagName, '.') !== false) {
				list( , $text) = preg_split('/[\/\.]+/', $tagName);
			} else {
				$text = $tagName;
			}
			if (substr($text, -3) == '_id') {
				$text = substr($text, 0, strlen($text) - 3);
			}
			$text = Inflector::humanize($text);
		}

		if (isset($attributes['for'])) {
			$labelFor = $attributes['for'];
			unset($attributes['for']);
		} else {
			$labelFor = $this->domId($tagName);
		}

		return $this->output(sprintf($this->Html->tags['label'], $labelFor, $this->_parseAttributes($attributes), $text));
	}
/**
 * Will display all the fields passed in an array expects tagName as an array key
 * replaces generateFields
 *
 * @access public
 * @param array $fields works well with Controller::generateFields() or on its own;
 * @param array $blacklist a simple array of fields to skip
 * @return output
 */
	function inputs($fields = null, $blacklist = null) {
		if(!is_array($fields)) {
			$fieldset = $fields;
			$fields = array_keys($this->fieldset['fields']);
		}
		if(isset($fields['fieldset'])) {
			$fieldset = $fields['fieldset'];
			unset($fields['fieldset']);
		} else {
			$fieldset = true;
		}

		if($fieldset === true) {
			$legend = 'New ';
			if(in_array($this->action, array('update', 'edit'))) {
				$legend = 'Edit ';
			}
			$legend .= Inflector::humanize(Inflector::underscore($this->model()));
		} else if(is_string($fieldset)){
			$legend = $fieldset;
		} else if(isset($fieldset['legend'])) {
			$legend = $fields['legend'];
			unset($fields['legend']);
		}

		$out = null;
		foreach($fields as $name => $options) {
			if(is_array($blacklist) && in_array($name, $blacklist)) {
				break;
			}
			if (is_numeric($name) && !is_array($options)) {
				$name = $options;
				$options = array();
			}
			if(is_array($options) && isset($options['tagName'])) {
				$name = $options['tagName'];
				unset($options['tagName']);
			}
			$out .= $this->input($name, $options);
		}
		if(isset($legend)) {
			return sprintf($this->Html->tags['fieldset'], $legend, $out);
		} else {
			return $out;
		}
	}
/**
 * Generates a form input element complete with label and wrapper div
 *
 * @param string $tagName This should be "Modelname.fieldname", "Modelname/fieldname" is deprecated
 * @param array $options
 * @return string
 */
	function input($tagName, $options = array()) {
		$this->setFormTag($tagName);
		$options = am(
			array(
				'before' => null,
				'between' => null,
				'after' => null
			),
		$options);

		if ((!isset($options['type']) || $options['type'] == 'select') && !isset($options['options'])) {
			$view =& ClassRegistry::getObject('view');
			$varName = Inflector::variable(Inflector::pluralize(preg_replace('/_id$/', '', $this->field())));
			$varOptions = $view->getVar($varName);
			if (is_array($varOptions)) {
				$options['type'] = 'select';
				$options['options'] = $varOptions;
			}
		}

		if (!isset($options['type'])) {
			$options['type'] = 'text';
			if (isset($options['options'])) {
				$options['type'] = 'select';
			} elseif (in_array($this->field(), array('passwd', 'password'))) {
				$options['type'] = 'password';
			} else if(isset($this->fieldset['fields'][$this->field()])) {
				$type = $this->fieldset['fields'][$this->field()];
				$primaryKey = $this->fieldset['key'];
			} else if (ClassRegistry::isKeySet($this->model())) {
				$model =& ClassRegistry::getObject($this->model());
				$type = $model->getColumnType($this->field());
				$primaryKey = $model->primaryKey;
			}

			if(isset($type)) {
				$map = array(
					'string'	=> 'text',	'datetime'	=> 'datetime',
					'boolean'	=> 'checkbox',	'timestamp' => 'datetime',
					'text'		=> 'textarea',	'time'		=> 'time',
					'date'		=> 'date'
				);
				if (isset($map[$type])) {
					$options['type'] = $map[$type];
				}
				if($this->field() == $primaryKey) {
					$options['type'] = 'hidden';
				}

			}
		}

		if(!array_key_exists('maxlength', $options) && $options['type'] == 'text') {
			if(isset($this->fieldset['sizes'][$this->field()])) {
				$options['maxlength'] = $this->fieldset['sizes'][$this->field()];
			}
		}

		$out = '';
		$div = true;
		if (array_key_exists('div', $options)) {
			$div = $options['div'];
			unset($options['div']);
		}

		if(!empty($div)) {
			$divOptions = array('class'=>'input');
			if (is_string($div)) {
				$divOptions['class'] = $div;
			} elseif (is_array($div)) {
				$divOptions = am($divOptions, $div);
			}
			if (in_array($this->field(), $this->fieldset['validates'])) {
				$divOptions = $this->addClass($divOptions, 'required');
			}
		}

		$label = null;
		if (isset($options['label'])) {
			$label = $options['label'];
			unset($options['label']);
		}

		if ($label !== false) {
			$labelAttributes = array();

			if (in_array($options['type'], array('date', 'datetime'))) {
				$labelFor = $this->domId(implode('.', array_filter(array($this->model(), $this->field()))));
				$labelAttributes = array( 'for' => $labelFor . 'Month' );
			}

			if (is_array($label)) {
				$labelText = null;
				if (isset($label['text'])) {
					$labelText = $label['text'];
					unset($label['text']);
				}

				$labelAttributes = am($labelAttributes, $label);
			} else {
				$labelText = $label;
			}

			$out = $this->label(null, $labelText, $labelAttributes);
		}

		$error = null;
		if (isset($options['error'])) {
			$error = $options['error'];
			unset($options['error']);
		}

		$selected = null;
		if (array_key_exists('selected', $options)) {
			$selected = $options['selected'];
			unset($options['selected']);
		}
		if(isset($options['rows']) || isset($options['cols'])) {
			$options['type'] = 'textarea';
		}

		$empty = false;
		if(isset($options['empty'])) {
			$empty = $options['empty'];
			unset($options['empty']);
		}

		$type	 = $options['type'];
		$before	 = $options['before'];
		$between = $options['between'];
		$after	 = $options['after'];
		unset($options['type'], $options['before'], $options['between'], $options['after']);

		switch ($type) {
			case 'hidden':
				$out = $this->hidden($tagName, $options);
				unset($divOptions);
			break;
			case 'checkbox':
				$out = $before . $this->checkbox($tagName, $options) . $between . $out;
			break;
			case 'text':
			case 'password':
				$out = $before . $out . $between . $this->{$type}($tagName, $options);
			break;
			case 'file':
				$out = $before . $out . $between . $this->file($tagName, $options);
			break;
			case 'select':
				$options = am(array('options' => array()), $options);
				$list = $options['options'];
				unset($options['options']);
				$out = $before . $out . $between . $this->select($tagName, $list, $selected, $options, $empty);
			break;
			case 'time':
				$out = $before . $out . $between . $this->dateTime($tagName, null, '12', $selected, $options, $empty);
			break;
			case 'date':
				$out = $before . $out . $between . $this->dateTime($tagName, 'MDY', null, $selected, $options, $empty);
			break;
			case 'datetime':
				$out = $before . $out . $between . $this->dateTime($tagName, 'MDY', '12', $selected, $options, $empty);
			break;
			case 'textarea':
			default:
				$out = $before . $out . $between . $this->textarea($tagName, am(array('cols' => '30', 'rows' => '6'), $options));
			break;
		}

		if ($type != 'hidden' && $error !== false) {
			$out .= $this->error($tagName, $error);
			$out .= $after;
		}
		if (isset($divOptions)) {
			$out = $this->Html->div($divOptions['class'], $out, $divOptions);
		}
		return $out;
	}
/**
 * Creates a checkbox input widget.
 *
 * @param string $fieldNamem Name of a field, like this "Modelname.fieldname", "Modelname/fieldname" is deprecated
 * @param array $options Array of HTML attributes.
 * @return string An HTML text input element
 */
	function checkbox($fieldName, $options = array()) {
		$this->__secure();

		$value = 1;
		if(isset($options['value'])) {
			$value = $options['value'];
			unset($options['value']);
		}

		$options = $this->__initInputField($fieldName, am(array('type' => 'checkbox'), $options));

		$model = $this->model();
		if (ClassRegistry::isKeySet($model)) {
			$object =& ClassRegistry::getObject($model);
		}

		$output = null;
		if(isset($object) && is_int($options['value'])) {
			$db =& ConnectionManager::getDataSource($object->useDbConfig);
			$value = $db->boolean($options['value']);
			$options['value'] = 1;
			$output = $this->hidden($fieldName, array('value' => '-1', 'id' => $options['id'] . '_'), true);
		}

		if(isset($options['value']) && $value == $options['value']) {
			$options['checked'] = 'checked';
		} else if(!empty($value)) {
			$options['value'] = $value;
		}

		$output .= sprintf($this->Html->tags['checkbox'], $this->model(), $this->field(), $this->_parseAttributes($options, null, null, ' '));
		return $this->output($output);
	}
/**
 * Creates a text input widget.
 *
 * @param string $fieldNamem Name of a field, like this "Modelname.fieldname", "Modelname/fieldname" is deprecated
 * @param array $options Array of HTML attributes.
 * @return string An HTML text input element
 */
	function text($fieldName, $options = array()) {
		$this->__secure();
		$options = $this->__initInputField($fieldName, am(array('type' => 'text'), $options));
		return $this->output(sprintf($this->Html->tags['input'], $this->model(), $this->field(), $this->_parseAttributes($options, null, null, ' ')));
	}
/**
 * Creates a password input widget.
 *
 * @param  string  $fieldName Name of a field, like this "Modelname.fieldname", "Modelname/fieldname" is deprecated
 * @param  array	$options Array of HTML attributes.
 * @return string
 */
	function password($fieldName, $options = array()) {
		$this->__secure();
		$options = $this->__initInputField($fieldName, $options);
		return $this->output(sprintf($this->Html->tags['password'], $this->model(), $this->field(), $this->_parseAttributes($options, null, null, ' ')));
	}
/**
 * Creates a textarea widget.
 *
 * @param string $fieldNamem Name of a field, like this "Modelname.fieldname", "Modelname/fieldname" is deprecated
 * @param array $options Array of HTML attributes.
 * @return string An HTML text input element
 */
	function textarea($fieldName, $options = array()) {
		$this->__secure();
		$options = $this->__initInputField($fieldName, $options);
		unset($options['type']);
		$value = null;

		if (array_key_exists('value', $options)) {
			$value = $options['value'];
			unset($options['value']);
		}
		return $this->output(sprintf($this->Html->tags['textarea'], $this->model(), $this->field(), $this->_parseAttributes($options, null, ' '), $value));
	}
/**
 * Creates a hidden input field.
 *
 * @param  string  $fieldName Name of a field, like this "Modelname.fieldname", "Modelname/fieldname" is deprecated
 * @param  array	$options Array of HTML attributes.
 * @return string
 * @access public
 */
	function hidden($fieldName, $options = array()) {
		$options = $this->__initInputField($fieldName, $options);
		$model = $this->model();

		if(isset($this->params['_Token']) && !empty($this->params['_Token'])) {
			$model = '_' . $model;
		}
		$this->__secure($model, ife($options['value'], $options['value'], ''));

		if (in_array($fieldName, array('_method', '_fields'))) {
			$model = null;
		}
		return $this->output(sprintf($this->Html->tags['hidden'], $model, $this->field(), $this->_parseAttributes($options, null, '', ' ')));
	}
/**
 * Creates file input widget.
 *
 * @param string $fieldName Name of a field, like this "Modelname.fieldname", "Modelname/fieldname" is deprecated
 * @param array $options Array of HTML attributes.
 * @return string
 * @access public
 */
	function file($fieldName, $options = array()) {
		$this->__secure();
		$options = $this->__initInputField($fieldName, $options);
		return $this->output(sprintf($this->Html->tags['file'], $this->model(), $this->field(), $this->_parseAttributes($options, null, '', ' ')));
	}
/**
 * Creates a button tag.
 *
 * @param  mixed  $params  Array of params [content, type, options] or the
 *						   content of the button.
 * @param  string $type	   Type of the button (button, submit or reset).
 * @param  array  $options Array of options.
 * @return string A HTML button tag.
 * @access public
 */
	function button($params, $type = 'button', $options = array()) {

		trigger_error(__("Don't use me yet"), E_USER_ERROR);
		if (isset($options['name'])) {
			if (strpos($options['name'], "/") !== false || strpos($options['name'], ".") !== false) {
				if ($this->__value($options['name'])) {
					$options['checked'] = 'checked';
				}
				$this->setFieldName($options['name']);
				$options['name'] = 'data[' . $this->model() . '][' . $this->field() . ']';
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
 * Creates a submit button element.
 *
 * @param  string  $caption	 The label appearing on the button
 * @param  array   $options
 * @return string A HTML submit button
 */
	function submit($caption = 'Submit', $options = array()) {
		$options['value'] = $caption;
		$secured = null;
		if(isset($this->params['_Token']) && !empty($this->params['_Token'])) {
			$secured = $this->secure($this->fields);
			$this->fields = array();
		}
		$div = true;
		if (isset($options['div'])) {
			$div = $options['div'];
			unset($options['div']);
		}

		$divOptions = array();
		if ($div === true) {
			$divOptions['class'] = 'submit';
		} elseif ($div === false) {
			unset($divOptions);
		} elseif (is_string($div)) {
			$divOptions['class'] = $div;
		} elseif (is_array($div)) {
			$divOptions = am(array('class' => 'submit'), $div);
		}

		$out =	$secured . $this->output(sprintf($this->Html->tags['submit'], $this->_parseAttributes($options, null, '', ' ')));
		if (isset($divOptions)) {
			$out = $secured . $this->Html->div($divOptions['class'], $out, $divOptions);
		}

		return $out;
	}
/**
 * Creates an image input widget.
 *
 * @param  string  $path		   Path to the image file, relative to the webroot/img/ directory.
 * @param  array   $options Array of HTML attributes.
 * @return string  HTML submit image element
 */
	function submitImage($path, $options = array()) {
		if (strpos($path, '://')) {
			$url = $path;
		} else {
			$url = $this->webroot(IMAGES_URL . $path);
		}
		return $this->output(sprintf($this->Html->tags['submitimage'], $url, $this->_parseAttributes($options, null, '', ' ')));
	}
 /**
 * Returns a formatted SELECT element.
 *
 * @param string $fieldName Name attribute of the SELECT
 * @param array $options Array of the OPTION elements (as 'value'=>'Text' pairs) to be used in the SELECT element
 * @param mixed $selected The option selected by default.  If null, the default value
 *						  from POST data will be used when available.
 * @param array $attributes	 The HTML attributes of the select element.	 If
 *							 'showParents' is included in the array and set to true,
 *							 an additional option element will be added for the parent
 *							 of each option group.
 * @param mixed $showEmpty If true, the empty select option is shown.  If a string,
 *						   that string is displayed as the empty element.
 * @return string Formatted SELECT element
 */
	function select($fieldName, $options = array(), $selected = null, $attributes = array(), $showEmpty = '') {
		$showParents = false;
		$escapeOptions = true;

		if (isset($attributes['escape'])) {
			$escapeOptions = $attributes['escape'];
			unset($attributes['escape']);
		}

		$this->setFormTag($fieldName);
		$attributes = $this->domId((array)$attributes);

		if ($this->tagIsInvalid()) {
			$attributes = $this->addClass($attributes, 'form-error');
		}
		if (is_string($options) && isset($this->__options[$options])) {
			$options = $this->__generateOptions($options);
		} elseif(!is_array($options)) {
			$options = array();
		}
		if (isset($attributes['type'])) {
			unset($attributes['type']);
		}
		if (in_array('showParents', $attributes)) {
			$showParents = true;
			unset($attributes['showParents']);
		}

		if (!isset($selected)) {
			$selected = $this->__value($fieldName);
		}

		if (isset($attributes) && array_key_exists('multiple', $attributes)) {
			$tag = $this->Html->tags['selectmultiplestart'];
		} else {
			$tag = $this->Html->tags['selectstart'];
			$this->__secure();
		}
		$select[] = sprintf($tag, $this->model(), $this->field(), $this->Html->_parseAttributes($attributes));

		if ($showEmpty !== null && $showEmpty !== false) {
			if($showEmpty === true) {
				$showEmpty = '';
			}
			$options = array_reverse($options, true);
			$options[''] = $showEmpty;
			$options = array_reverse($options, true);
		}
		$select = am($select, $this->__selectOptions(array_reverse($options, true), $selected, array(), $showParents, array('escape' => $escapeOptions)));
		$select[] = sprintf($this->Html->tags['selectend']);
		return $this->output(implode("\n", $select));
	}
/**
 * Returns a SELECT element for days.
 *
 * @param string $fieldName Prefix name for the SELECT element
 * @param string $selected Option which is selected.
 * @param array	 $attributes HTML attributes for the select element
 * @param mixed $showEmpty Show/hide the empty select option
 * @return string
 */
	function day($fieldName, $selected = null, $attributes = array(), $showEmpty = true) {
		if (empty($selected) && $value = $this->__value($fieldName)) {
			$selected = date('d', strtotime($value));
		}
		if (empty($selected) && !$showEmpty) {
			$selected = date('d');
		}
		return $this->select($fieldName . "_day", $this->__generateOptions('day'), $selected, $attributes, $showEmpty);
	}
/**
 * Returns a SELECT element for years
 *
 * @param string $fieldName Prefix name for the SELECT element
 * @param integer $minYear First year in sequence
 * @param integer $maxYear Last year in sequence
 * @param string $selected Option which is selected.
 * @param array $attributes Attribute array for the select elements.
 * @param boolean $showEmpty Show/hide the empty select option
 * @return string
 */
	function year($fieldName, $minYear = null, $maxYear = null, $selected = null, $attributes = array(), $showEmpty = true) {
		if (empty($selected) && $value = $this->__value($fieldName)) {
			$selected = date('Y', strtotime($value));
		}
		if (empty($selected) && !$showEmpty) {
			$selected = date('Y');
		}
		return $this->select($fieldName . "_year", $this->__generateOptions('year', $minYear, $maxYear), $selected, $attributes, $showEmpty);
	}
/**
 * Returns a SELECT element for months.
 *
 * @param string $tagName Prefix name for the SELECT element
 * @param string $selected Option which is selected.
 * @param boolean $showEmpty Show/hide the empty select option
 * @return string
 */
	function month($tagName, $selected = null, $attributes = array(), $showEmpty = true) {
		if (empty($selected) && $value = $this->__value($tagName)) {
			$selected = date('m', strtotime($value));
		}
		$selected = empty($selected) ? ($showEmpty ? NULL : date('m')) : $selected;
		return $this->select($tagName . "_month", $this->__generateOptions('month'), $selected, $attributes, $showEmpty);
	}
/**
 * Returns a SELECT element for hours.
 *
 * @param string $tagName Prefix name for the SELECT element
 * @param boolean $format24Hours True for 24 hours format
 * @param string $selected Option which is selected.
 * @param array $attributes List of HTML attributes
 * @param mixed $showEmpty True to show an empty element, or a string to provide default empty element text
 * @return string
 */
	function hour($tagName, $format24Hours = false, $selected = null, $attributes = array(), $showEmpty = true) {
		if (empty($selected) && $value = $this->__value($tagName)) {
			if ($format24Hours) {
				$selected = date('H', strtotime($value));
			} else {
				$selected = date('g', strtotime($value));
			}
		}

		if ($format24Hours) {
			$selected = empty($selected) ? ($showEmpty ? NULL : date('H')) : $selected;
		} else {
			$hourValue = empty($selected) ? ($showEmpty ? NULL : date('g')) : $selected;
			if (isset($selected) && intval($hourValue) == 0 && !$showEmpty) {
				$selected = 12;
			} else {
				$selected = $hourValue;
			}
		}
		return $this->select($tagName . "_hour", $this->__generateOptions($format24Hours ? 'hour24' : 'hour'), $selected, $attributes, $showEmpty);
	}
/**
 * Returns a SELECT element for minutes.
 *
 * @param string $tagName Prefix name for the SELECT element
 * @param string $selected Option which is selected.
 * @return string
 */
	function minute($tagName, $selected = null, $attributes = array(), $showEmpty = true) {
		if (empty($selected) && $value = $this->__value($tagName)) {
			$selected = date('i', strtotime($value));
		}
		$selected = empty($selected) ? ($showEmpty ? NULL : date('i')) : $selected;
		return $this->select($tagName . "_min", $this->__generateOptions('minute'), $selected, $attributes, $showEmpty);
	}
/**
 * Returns a SELECT element for AM or PM.
 *
 * @param string $tagName Prefix name for the SELECT element
 * @param string $selected Option which is selected.
 * @return string
 */
	function meridian($tagName, $selected = null, $attributes = array(), $showEmpty = true) {
		if (empty($selected) && $value = $this->__value($tagName)) {
			$selected = date('a', strtotime($value));
		}
		$selected = empty($selected) ? ($showEmpty ? null : date('a')) : $selected;
		return $this->select($tagName . "_meridian", $this->__generateOptions('meridian'), $selected, $attributes, $showEmpty);
	}
/**
 * Returns a set of SELECT elements for a full datetime setup: day, month and year, and then time.
 *
 * @param string $tagName Prefix name for the SELECT element
 * @param string $dateFormat DMY, MDY, YMD or NONE.
 * @param string $timeFormat 12, 24, NONE
 * @param string $selected Option which is selected.
 * @return string The HTML formatted OPTION element
 */
	function dateTime($tagName, $dateFormat = 'DMY', $timeFormat = '12', $selected = null, $attributes = array(), $showEmpty = true) {
		$day	  = null;
		$month	  = null;
		$year	  = null;
		$hour	  = null;
		$min	  = null;
		$meridian = null;

		if (empty($selected)) {
			$selected = $this->__value($tagName);
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
				$check = str_replace(':', '', $days[1]);

				if (($check > 115959) && $timeFormat == '12') {
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
		if (isset($attributes['id'])) {
			if (is_string($attributes['id'])) {
				// build out an array version
				foreach ($elements as $element) {
					$selectAttrName = 'select' . $element . 'Attr';
					${$selectAttrName} = $selectAttr;
					${$selectAttrName}['id'] = $attributes['id'] . $element;
				}
			} elseif (is_array($attributes['id'])) {
				// check for missing ones and build selectAttr for each element
				foreach ($elements as $element) {
					$selectAttrName = 'select' . $element . 'Attr';
					${$selectAttrName} = $attributes;
					${$selectAttrName}['id'] = $attributes['id'][strtolower($element)];
				}
			}
		} else {
			// build the selectAttrName with empty id's to pass
			foreach ($elements as $element) {
				$selectAttrName = 'select' . $element . 'Attr';
				${$selectAttrName} = $attributes;
			}
		}

		$attributes = am(array('minYear' => null, 'maxYear' => null), $attributes);

		switch($dateFormat) {
			case 'DMY': // so uses the new selex
				$opt = $this->day($tagName, $day, $selectDayAttr, $showEmpty) . '-' .
				$this->month($tagName, $month, $selectMonthAttr, $showEmpty) . '-' . $this->year($tagName, $attributes['minYear'], $attributes['maxYear'], $year, $selectYearAttr, $showEmpty);
			break;
			case 'MDY':
				$opt = $this->month($tagName, $month, $selectMonthAttr, $showEmpty) . '-' .
				$this->day($tagName, $day, $selectDayAttr, $showEmpty) . '-' . $this->year($tagName, $attributes['minYear'], $attributes['maxYear'], $year, $selectYearAttr, $showEmpty);
			break;
			case 'YMD':
				$opt = $this->year($tagName, $attributes['minYear'], $attributes['maxYear'], $year, $selectYearAttr, $showEmpty) . '-' .
				$this->month($tagName, $month, $selectMonthAttr, $showEmpty) . '-' .
				$this->day($tagName, $day, $selectDayAttr, $showEmpty);
			break;
			case 'Y':
				$opt = $this->year($tagName, $attributes['minYear'], $attributes['maxYear'], $selected, $selectYearAttr, $showEmpty);
			break;
			case 'NONE':
			default:
				$opt = '';
			break;
		}

		switch($timeFormat) {
			case '24':
				$opt .= $this->hour($tagName, true, $hour, $selectHourAttr, $showEmpty) . ':' .
				$this->minute($tagName, $min, $selectMinuteAttr, $showEmpty);
			break;
			case '12':
				$opt .= $this->hour($tagName, false, $hour, $selectHourAttr, $showEmpty) . ':' .
				$this->minute($tagName, $min, $selectMinuteAttr, $showEmpty) . ' ' .
				$this->meridian($tagName, $meridian, $selectMeridianAttr, $showEmpty);
			break;
			case 'NONE':
			default:
				$opt .= '';
			break;
		}
		return $opt;
	}
/**
 * Returns an array of formatted OPTION/OPTGROUP elements
 *
 * @return array
 */
	function __selectOptions($elements = array(), $selected = null, $parents = array(), $showParents = null, $attributes = array()) {
		$attributes = am(array('escape' => true), $attributes);

		$select = array();
		foreach($elements as $name => $title) {
			$htmlOptions = array();
			if (is_array($title) && (!isset($title['name']) || !isset($title['value']))) {
				if (!empty($name)) {
					$select[] = $this->Html->tags['optiongroupend'];
					$parents[] = $name;
				}
				$select = am($select, $this->__selectOptions($title, $selected, $parents, $showParents, $attributes));
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
					$title = ife($attributes['escape'], h($title), $title);
					$select[] = sprintf($this->Html->tags['selectoption'], $name, $this->Html->_parseAttributes($htmlOptions), $title);
				}
			}
		}

		return array_reverse($select, true);
	}
/**
 * Generates option lists for common <select /> menus
 *
 * @return void
 */
	function __generateOptions($name, $min = null, $max = null) {
		if (!empty($this->options[$name])) {
			return $this->options[$name];
		}
		$data = array();

		switch ($name) {
			case 'minute':
				for($i = 0; $i < 60; $i++) {
					$data[$i] = sprintf('%02d', $i);
				}
			break;
			case 'hour':
				for($i = 1; $i <= 12; $i++) {
					$data[sprintf('%02d', $i)] = $i;
				}
			break;
			case 'hour24':
				for($i = 0; $i <= 23; $i++) {
					$data[sprintf('%02d', $i)] = $i;
				}
			break;
			case 'meridian':
				$data = array('am' => 'am', 'pm' => 'pm');
			break;
			case 'day':
				if (empty($min)) {
					$min = 1;
				}
				if (empty($max)) {
					$max = 31;
				}
				for($i = $min; $i <= $max; $i++) {
					$data[sprintf('%02d', $i)] = $i;
				}
			break;
			case 'month':
				for($i = 1; $i <= 12; $i++) {
					$data[sprintf("%02s", $i)] = strftime("%B", mktime(1,1,1,$i,1,1999));
				}
			break;
			case 'year':
				$current = intval(date('Y'));
				if (empty($min)) {
					$min = $current - 20;
				}
				if (empty($max)) {
					$max = $current + 20;
				}
				if ($min > $max) {
					list($min, $max) = array($max, $min);
				}
				for ($i = $min; $i <= $max; $i++) {
					$data[$i] = $i;
				}
			break;
		}
		$this->__options[$name] = $data;
		return $this->__options[$name];
	}
}
?>
