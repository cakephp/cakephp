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

/**
 * Holds the fields array('field_name'=>'type'), sizes array('field_name'=>'size'),
 * primaryKey and validates array('field_name')
 *
 * @var array
 * @access public
 */
	var $fieldset = array('fields' => array(), 'sizes' => array(), 'key' => 'id', 'validates' => array());
/**
 * Local cache of default generated options for date/time input fields
 *
 * @var array
 * @access private
 */
	var $__options = array('day' => array(), 'minute' => array(), 'hour' => array(),
									'month' => array(), 'year' => array(), 'meridian' => array());
	var $fields = array();

/**
 * List of input fields generated in the current form (between create() and end()).
 *
 * @var array
 * @access public
 */
	var $inputFields = array();
/**
 * Maintains the attributes of the current form (between create() and end()).
 *
 * @var array
 * @access public
 */
	var $current = array();
/**
 * Returns an HTML FORM element.
 *
 * @access public
 * @param string $model The model object which the form is being defined for
 * @param array  $options
 * @return string An formatted opening FORM tag.
 */
	function create($model = null, $options = array()) {
		$defaultModel = null;
		$this->inputFields = array();

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
			$data = array(
				'fields' => array_combine($fields->extract('{n}.name'), $fields->extract('{n}.type')),
				'sizes' => array_combine($fields->extract('{n}.name'), $fields->extract('{n}.length')),
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
			if (!empty($this->plugin)) {
				$controller = $this->plugin;
			} elseif (!empty($model) && $model != $defaultModel) {
				$controller = Inflector::underscore(Inflector::pluralize($model));
			} else {
				$controller = Inflector::underscore($this->params['controller']);
			}
			if (empty($options['action'])) {
				$options['action'] = ife($created, 'edit', 'add');
			}

			$actionDefaults = array(
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
		$this->current = $options;
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
		$append .= $this->authToken();

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

		$this->setFormTag($model . '.');
		return $this->output(sprintf($this->Html->tags['form'], $this->Html->parseHtmlOptions($htmlAttributes, null, ''))) . $append;
	}
/**
 * Closes an HTML form.
 *
 * @access public
 * @return string A closing FORM tag.
 */
	function end($options = array()) {
		$submitOptions = $submit = false;

		if (!is_array($options)) {
			$submitOptions = $options;
		} elseif (isset($options['submit'])) {
			$submitOptions = $options['submit'];
			unset($options['submit']);
			if (!is_array($submitOptions)) {
				$submitOptions = array('label' => $submitOptions);
			}

			if(isset($submitOptions['label'])) {
				$submit = $submitOptions['label'];
				unset($submitOptions['label']);
			}
		}

		if ($submitOptions === true) {
			$submit = 'Submit';
		} elseif (is_string($submitOptions)) {
			$submit = $submitOptions;
		}

		if(!is_array($submitOptions)) {
			$submitOptions = array();
		}
		$out = null;

		if($submit !== false) {
			$out .= $this->submit($submit, $submitOptions);
		}
		$out .= $this->output($this->Html->tags['formend']);

		$this->inputFields = $this->current = array();
		return $out;
	}
/**
 * Creates a serialized hash of the list of fields used in this form
 *
 * @param array $options
 * @param string $key
 * @return array
 */
	function secure($fields) {
		$append = '<p style="display: inline; margin: 0px; padding: 0px;">';
		$append .= $this->hidden('_Token/fields', array('value' => urlencode(Security::hash(serialize($fields) . CAKE_SESSION_STRING)), 'id' => 'TokenFields' . mt_rand()));
		$append .= '</p>';
		return $append;
	}
/**
 * Sets the defaults for an input tag
 *
 * @param array $options
 * @param string $key
 * @return array
 */
	function __initInputField($field, $options = array()) {
		$this->setFormTag($field);
		$options = (array)$options;
		$options = $this->__name($options);
		$options = $this->__value($options);
		$options = $this->domId($options);
		if ($this->tagIsInvalid()) {
			$options = $this->addClass($options, 'form-error');
		}
		$this->inputFields[$this->getFormTag()] = $options;
		unset($options['name']); // Temporary
		return $options;
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
		return $this->output(sprintf($this->Html->tags['label'], $this->domId($tagName), $this->_parseAttributes($attributes), $text));
	}
/**
 * Captures field names to be stored in the list of current fields
 *
 * @access public
 * @param array $options
 * @param string $key
 * @return mixed
 */
	function __name($options = array(), $field = null, $key = 'name') {
		$out = parent::__name($options, $field, $key);
		$this->inputFields[] = implode('.', Set::filter(array($this->model(), $this->field(), $this->modelID())));
		return $out;
	}
/**
 * Will display all the fields passed in an array expects tagName as an array key
 * replaces generateFields
 *
 * @access public
 * @param array $fields works well with Controller::generateFields() or on its own;
 * @param array $blacklist a simple array of fields to skip
 * @return string
 */
	function inputs($fields = null, $blacklist = null) {
		if(!is_array($fields)) {
			$fieldset = $fields;
		} else if(isset($fields['fieldset'])) {
			$fieldset = $fields['fieldset'];
			unset($fields['fieldset']);
		} else {
			$fieldset = true;
		}

		if($fieldset === true) {
			$legend = Inflector::humanize($this->action .' to '. $this->model());
		} else if(is_string($fieldset)){
			$legend = $fields;
		}

		if(!is_array($fields)) {
			$fields = array_keys($this->fieldset['fields']);
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
					'boolean'	=> 'checkbox',	'timestamp'	=> 'datetime',
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
		if (is_array($label)) {
			$labelText = null;
			if (isset($label['text'])) {
				$labelText = $label['text'];
				unset($label['text']);
			}
			$out = $this->label(null, $labelText, $label);
			$label = $labelText;
		} elseif ($label !== false) {
			$out = $this->label(null, $label);
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

		$type    = $options['type'];
		$before  = $options['before'];
		$between = $options['between'];
		$after   = $options['after'];
		unset($options['type'], $options['before'], $options['between'], $options['after']);

		switch ($type) {
			case 'hidden':
				$out = $this->hidden($tagName, $options);
				unset($divOptions);
			break;
			case 'checkbox':
				$out = $before . $this->Html->checkbox($tagName, null, $options) . $between . $out;
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
 * Creates a text input widget.
 *
 * @param string $fieldNamem Name of a field, like this "Modelname.fieldname", "Modelname/fieldname" is deprecated
 * @param array $options Array of HTML attributes.
 * @return string An HTML text input element
 */
	function text($fieldName, $options = array()) {
		$this->fields[$this->model()][] = $this->field();
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
		$this->fields[$this->model()][] = $this->field();
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
		$this->fields[$this->model()][] = $this->field();
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
		unset($options['class']);
		$this->fields[$model][$this->field()] = $options['value'];

		if (in_array($fieldName, array('_method', '_fields'))) {
			$model = null;
		}
		return $this->output(sprintf($this->Html->tags['hidden'], $model, $this->field(), $this->_parseAttributes($options, null, ' ', ' ')));
	}
/**
 * Creates a token field used for authentication with SecurityComponent::requireAuth()
 *
 * @return string
 * @access public
 * @see SecurityComponent::requireAuth
 */
	function authToken($id = null) {
		if (!isset($this->params['_Token']) || empty($this->params['_Token']) || empty($this->current) || in_array('_Token.key', $this->inputFields)) {
			return false;
		}
		if (empty($id) && $id != false) {
			$id = $this->current['id'] . 'Token' . mt_rand();
		}
		return $this->hidden('_Token.key', array('value' => $this->params['_Token']['key'], 'id' => $id));
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
		$this->fields[$this->model()][] = $this->field();
		$options = $this->__initInputField($fieldName, $options);
		return $this->output(sprintf($this->Html->tags['file'], $this->model(), $this->field(), $this->_parseAttributes($options, null, '', ' ')));
	}
/**
 * Creates a button tag.
 *
 * @param  mixed  $params  Array of params [content, type, options] or the
 *                         content of the button.
 * @param  string $type    Type of the button (button, submit or reset).
 * @param  array  $options Array of options.
 * @return string A HTML button tag.
 * @access public
 */
	function button($params, $type = 'button', $options = array()) {

		trigger_error(__("Don't use me yet"), E_USER_ERROR);
		if (isset($options['name'])) {
			if (strpos($options['name'], "/") !== false) {
				if ($this->fieldValue($options['name'])) {
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
 * @param  string  $caption  The label appearing on the button
 * @param  array   $options
 * @return string A HTML submit button
 */
	function submit($caption = 'Submit', $options = array()) {
		$options['value'] = $caption;
		$secured = null;
		if(isset($this->params['_Token']) && !empty($this->params['_Token'])) {
			$secured = $this->secure($this->fields);
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

		$out =  $secured . $this->output(sprintf($this->Html->tags['submit'], $this->_parseAttributes($options, null, '', ' ')));
		if (isset($divOptions)) {
			$out = $secured . $this->Html->div($divOptions['class'], $out, $divOptions);
		}

		return $out;
	}
/**
 * Creates an image input widget.
 *
 * @param  string  $path           Path to the image file, relative to the webroot/img/ directory.
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
		$this->fields[$this->model()][] = $this->field();
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
		}
		$select[] = sprintf($tag, $this->model(), $this->field(), $this->Html->parseHtmlOptions($attributes));

		if ($showEmpty !== null && $showEmpty !== false) {
			if($showEmpty === true) {
				$showEmpty = '';
			}
			$options = array_reverse($options, true);
			$options[''] = $showEmpty;
			$options = array_reverse($options, true);
		}
		$select = am($select, $this->__selectOptions(array_reverse($options, true), $selected, array(), $showParents));
		$select[] = sprintf($this->Html->tags['selectend']);
		return $this->output(implode("\n", $select));
	}
/**
 * Returns a SELECT element for days.
 *
 * @param string $fieldName Prefix name for the SELECT element
 * @param string $selected Option which is selected.
 * @param array  $attributes HTML attributes for the select element
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
		$day      = null;
		$month    = null;
		$year     = null;
		$hour     = null;
		$min      = null;
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
 				$data = array('01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December');
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
/**
 * @deprecated
 * @see FormHelper::input()
 */
	function generateInputDiv($tagName, $prompt, $required = false, $errorMsg = null, $size = 20, $htmlOptions = null) {
		trigger_error(__('Deprecated: Use FormHelper::input() or FormHelper::text() instead', true), E_USER_WARNING);
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
		trigger_error(__('Deprecated: Use FormHelper::input() or FormHelper::checkbox() instead', true), E_USER_WARNING);
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
		trigger_error(__('Deprecated: Use FormHelper::input() instead', true), E_USER_WARNING);
		$htmlOptions['id']=strtolower(str_replace('/', '_', $tagName));
		$str = $this->dateTime($tagName, 'MDY', 'NONE', $selected, $htmlOptions);
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
		trigger_error(__('Deprecated: Use FormHelper::input() instead', true), E_USER_WARNING);
		$str = $this->dateTime($tagName, 'NONE', '24', $selected, $htmlOptions);
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
		trigger_error(__('Deprecated: Use FormHelper::input() instead', true), E_USER_WARNING);
		$htmlOptions['id']=strtolower(str_replace('/', '_', $tagName));
		$str = $this->dateTime($tagName, 'MDY', '12', $selected, $htmlOptions, null, false);
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
		trigger_error(__('Deprecated: Use FormHelper::input() instead', true), E_USER_WARNING);
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
		trigger_error(__('Deprecated: Use FormHelper::input() or FormHelper::select() instead', true), E_USER_WARNING);
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
		trigger_error(__('Deprecated: Use FormHelper::submit() instead', true), E_USER_WARNING);
		return $this->divTag('submit', $this->Html->submit($displayText, $htmlOptions));
	}
/**
 * @deprecated
 * @see FormHelper::inputs()
 */
	function generateFields($fields, $readOnly = false) {
		trigger_error(__('Deprecated: Use FormHelper::input() instead', true), E_USER_WARNING);
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
						$strFormFields = $strFormFields . $this->hidden($field['tagName'], $field['value']);
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
		trigger_error(__('Deprecated: Use FormHelper::label() instead', true), E_USER_WARNING);
		return sprintf($this->Html->tags['label'], Inflector::camelize(r('/', '_', $tagName)), $text);
	}
/**
 * @deprecated
 * @see HtmlHelper::div
 */
	function divTag($class, $text) {
		trigger_error(__('(FormHelper::divTag) Deprecated: Use HtmlHelper::div instead', true), E_USER_WARNING);
		return sprintf(TAG_DIV, $class, $text);
	}
/**
 * @deprecated
 * @see HtmlHelper::para
 */
	function pTag($class, $text) {
		trigger_error(__('(FormHelper::pTag) Deprecated: Use HtmlHelper::para instead', true), E_USER_WARNING);
		return sprintf(TAG_P_CLASS, $class, $text);
	}
}

?>
