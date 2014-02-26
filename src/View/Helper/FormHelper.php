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
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Core\Configure;
use Cake\Error;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use Cake\View\Form\ArrayContext;
use Cake\View\Form\ContextInterface;
use Cake\View\Form\EntityContext;
use Cake\View\Form\NullContext;
use Cake\View\Helper;
use Cake\View\Helper\StringTemplateTrait;
use Cake\View\StringTemplate;
use Cake\View\View;
use Cake\View\Widget\WidgetRegistry;
use DateTime;
use Traversable;

/**
 * Form helper library.
 *
 * Automatic generation of HTML FORMs from given data.
 *
 * @property      HtmlHelper $Html
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html
 */
class FormHelper extends Helper {

	use StringTemplateTrait;

/**
 * Other helpers used by FormHelper
 *
 * @var array
 */
	public $helpers = array('Html');

/**
 * The various pickers that make up a datetime picker.
 *
 * @var array
 */
	protected $_datetimeParts = ['year', 'month', 'day', 'hour', 'minute', 'second', 'meridian'];

/**
 * Settings for the helper.
 *
 * @var array
 */
	public $settings = [
		'errorClass' => 'form-error',
		'typeMap' => [
			'string' => 'text', 'datetime' => 'datetime', 'boolean' => 'checkbox',
			'timestamp' => 'datetime', 'text' => 'textarea', 'time' => 'time',
			'date' => 'date', 'float' => 'number', 'integer' => 'number',
			'decimal' => 'number', 'binary' => 'file', 'uuid' => 'string'
		]
	];

/**
 * List of fields created, used with secure forms.
 *
 * @var array
 */
	public $fields = array();

/**
 * Constant used internally to skip the securing process,
 * and neither add the field to the hash or to the unlocked fields.
 *
 * @var string
 */
	const SECURE_SKIP = 'skip';

/**
 * Defines the type of form being created. Set by FormHelper::create().
 *
 * @var string
 */
	public $requestType = null;

/**
 * An array of field names that have been excluded from
 * the Token hash used by SecurityComponent's validatePost method
 *
 * @see FormHelper::_secure()
 * @see SecurityComponent::validatePost()
 * @var array
 */
	protected $_unlockedFields = array();

/**
 * Registry for input widgets.
 *
 * @var \Cake\View\Widget\WidgetRegistry
 */
	protected $_registry;

/**
 * Context for the current form.
 *
 * @var \Cake\View\Form\Context
 */
	protected $_context;

/**
 * Context provider methods.
 *
 * @var array
 * @see addContextProvider
 */
	protected $_contextProviders;

/**
 * Default templates the FormHelper uses.
 *
 * @var array
 */
	protected $_defaultTemplates = [
		'button' => '<button{{attrs}}>{{text}}</button>',
		'checkbox' => '<input type="checkbox" name="{{name}}" value="{{value}}"{{attrs}}>',
		'checkboxContainer' => '<div class="checkbox">{{input}}{{label}}</div>',
		'dateWidget' => '{{month}}{{day}}{{year}}{{hour}}{{minute}}{{second}}{{meridian}}',
		'error' => '<div class="error-message">{{content}}</div>',
		'errorList' => '<ul>{{content}}</ul>',
		'errorItem' => '<li>{{text}}</li>',
		'file' => '<input type="file" name="{{name}}"{{attrs}}>',
		'formstart' => '<form{{attrs}}>',
		'formend' => '</form>',
		'hiddenblock' => '<div style="display:none;">{{content}}</div>',
		'input' => '<input type="{{type}}" name="{{name}}"{{attrs}}>',
		'label' => '<label{{attrs}}>{{text}}</label>',
		'option' => '<option value="{{value}}"{{attrs}}>{{text}}</option>',
		'optgroup' => '<optgroup label="{{label}}"{{attrs}}>{{content}}</optgroup>',
		'select' => '<select name="{{name}}"{{attrs}}>{{content}}</select>',
		'selectMultiple' => '<select name="{{name}}[]" multiple="multiple"{{attrs}}>{{content}}</select>',
		'radio' => '<input type="radio" name="{{name}}" value="{{value}}"{{attrs}}>',
		'radioContainer' => '{{input}}{{label}}',
		'textarea' => '<textarea name="{{name}}"{{attrs}}>{{value}}</textarea>',
		'formGroup' => '{{label}}{{input}}',
		'checkboxFormGroup' => '{{input}}{{label}}',
		'groupContainer' => '<div class="input {{type}}{{required}}">{{content}}</div>',
		'groupContainerError' => '<div class="input {{type}}{{required}} error">{{content}}{{error}}</div>'
	];

/**
 * Construct the widgets and binds the default context providers
 *
 * @param \Cake\View\View $View The View this helper is being attached to.
 * @param array $settings Configuration settings for the helper.
 */
	public function __construct(View $View, $settings = array()) {
		$settings += ['widgets' => [], 'templates' => null, 'registry' => null];
		parent::__construct($View, $settings);

		$this->initStringTemplates($this->_defaultTemplates);
		$this->widgetRegistry($settings['registry'], $settings['widgets']);
		unset($this->settings['widgets'], $this->settings['registry']);

		$this->_addDefaultContextProviders();
	}

/**
 * Set the input registry the helper will use.
 *
 * @param \Cake\View\Widget\WidgetRegistry $instance The registry instance to set.
 * @param array $widgets An array of widgets
 * @return \Cake\View\Widget\WidgetRegistry
 */
	public function widgetRegistry(WidgetRegistry $instance = null, $widgets = []) {
		if ($instance === null) {
			if ($this->_registry === null) {
				$this->_registry = new WidgetRegistry($this->_templater, $widgets);
			}
			return $this->_registry;
		}
		$this->_registry = $instance;
		return $this->_registry;
	}

/**
 * Add the default suite of context providers provided by CakePHP.
 *
 * @return void
 */
	protected function _addDefaultContextProviders() {
		$this->addContextProvider('array', function ($request, $data) {
			if (is_array($data['entity']) && isset($data['entity']['schema'])) {
				return new ArrayContext($request, $data['entity']);
			}
		});

		$this->addContextProvider('orm', function ($request, $data) {
			if (
				$data['entity'] instanceof Entity ||
				$data['entity'] instanceof Traversable ||
				(is_array($data['entity']) && !isset($data['entity']['schema']))
			) {
				return new EntityContext($request, $data);
			}
		});
	}

/**
 * Returns if a field is required to be filled based on validation properties from the validating object.
 *
 * @param \Cake\Validation\ValidationSet $validationRules
 * @return boolean true if field is required to be filled, false otherwise
 */
	protected function _isRequiredField($validationRules) {
		if (empty($validationRules) || count($validationRules) === 0) {
			return false;
		}
		$validationRules->isUpdate($this->requestType === 'put');
		foreach ($validationRules as $rule) {
			if ($rule->skip()) {
				continue;
			}
			return !$validationRules->isEmptyAllowed();
		}
		return false;
	}

/**
 * Returns an HTML FORM element.
 *
 * ### Options:
 *
 * - `type` Form method defaults to autodetecting based on the form context. If
 *   the form context's isCreate() method returns false, a PUT request will be done.
 * - `action` The controller action the form submits to, (optional). Use this option if you
 *   don't need to change the controller from the current request's controller.
 * - `url` The URL the form submits to. Can be a string or a URL array. If you use 'url'
 *    you should leave 'action' undefined.
 * - `default` Allows for the creation of Ajax forms. Set this to false to prevent the default event handler.
 *   Will create an onsubmit attribute if it doesn't not exist. If it does, default action suppression
 *   will be appended.
 * - `onsubmit` Used in conjunction with 'default' to create ajax forms.
 * - `encoding` Set the accept-charset encoding for the form. Defaults to `Configure::read('App.encoding')`
 * - `context` Additional options for the context class. For example the EntityContext accepts a 'table'
 *   option that allows you to set the specific Table class the form should be based on.
 *
 * @param mixed $model The context for which the form is being defined. Can
 *   be an ORM entity, ORM resultset, or an array of meta data. You can use false or null
 *   to make a model-less form.
 * @param array $options An array of html attributes and options.
 * @return string An formatted opening FORM tag.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#options-for-create
 */
	public function create($model = null, $options = []) {
		$append = '';

		if (empty($options['context'])) {
			$options['context'] = [];
		}
		$options['context']['entity'] = $model;
		$context = $this->_getContext($options['context']);
		unset($options['context']);

		$isCreate = $context->isCreate();

		$options = $options + [
			'type' => $isCreate ? 'post' : 'put',
			'action' => null,
			'url' => null,
			'default' => true,
			'encoding' => strtolower(Configure::read('App.encoding')),
		];

		$action = $this->url($this->_formUrl($context, $options));
		unset($options['url'], $options['action']);

		$htmlAttributes = [];
		switch (strtolower($options['type'])) {
			case 'get':
				$htmlAttributes['method'] = 'get';
				break;
			case 'file':
				$htmlAttributes['enctype'] = 'multipart/form-data';
				$options['type'] = ($isCreate) ? 'post' : 'put';
			case 'post':
			case 'put':
			case 'delete':
			case 'patch':
				$append .= $this->hidden('_method', array(
					'name' => '_method',
					'value' => strtoupper($options['type']),
					'secure' => static::SECURE_SKIP
				));
			default:
				$htmlAttributes['method'] = 'post';
		}
		$this->requestType = strtolower($options['type']);

		if (!$options['default']) {
			if (!isset($options['onsubmit'])) {
				$options['onsubmit'] = '';
			}
			$htmlAttributes['onsubmit'] = $options['onsubmit'] . 'event.returnValue = false; return false;';
		}

		if (!empty($options['encoding'])) {
			$htmlAttributes['accept-charset'] = $options['encoding'];
		}
		unset($options['type'], $options['encoding'], $options['default']);

		$htmlAttributes = array_merge($options, $htmlAttributes);

		$this->fields = array();
		if ($this->requestType !== 'get') {
			$append .= $this->_csrfField();
		}

		if (!empty($append)) {
			$append = $this->formatTemplate('hiddenblock', ['content' => $append]);
		}
		$actionAttr = $this->_templater->formatAttributes(['action' => $action, 'escape' => false]);
		return $this->formatTemplate('formstart', [
			'attrs' => $this->_templater->formatAttributes($htmlAttributes) . $actionAttr
		]) . $append;
	}

/**
 * Create the URL for a form based on the options.
 *
 * @param \Cake\View\Form\ContextInterface $context The context object to use.
 * @param array $options An array of options from create()
 * @return string The action attribute for the form.
 */
	protected function _formUrl($context, $options) {
		if ($options['action'] === null && $options['url'] === null) {
			return $this->request->here(false);
		}
		if (empty($options['url']) || is_array($options['url'])) {
			if (isset($options['action']) && empty($options['url']['action'])) {
				$options['url']['action'] = $options['action'];
			}

			$plugin = $this->plugin ? Inflector::underscore($this->plugin) : null;
			$actionDefaults = [
				'plugin' => $plugin,
				'controller' => Inflector::underscore($this->request->params['controller']),
				'action' => $this->request->params['action'],
			];

			$action = (array)$options['url'] + $actionDefaults;

			$pk = $context->primaryKey();
			if (count($pk)) {
				$id = $context->val($pk[0]);
			}
			if (empty($action[0]) && isset($id)) {
				$action[0] = $id;
			}
			return $action;
		}
		if (is_string($options['url'])) {
			return $options['url'];
		}
	}

/**
 * Return a CSRF input if the request data is present.
 * Used to secure forms in conjunction with CsrfComponent &
 * SecurityComponent
 *
 * @return string
 */
	protected function _csrfField() {
		if (!empty($this->request['_Token']['unlockedFields'])) {
			foreach ((array)$this->request['_Token']['unlockedFields'] as $unlocked) {
				$this->_unlockedFields[] = $unlocked;
			}
		}
		if (empty($this->request->params['_csrfToken'])) {
			return '';
		}
		return $this->hidden('_csrfToken', array(
			'value' => $this->request->params['_csrfToken'],
			'secure' => static::SECURE_SKIP
		));
	}

/**
 * Closes an HTML form, cleans up values set by FormHelper::create(), and writes hidden
 * input fields where appropriate.
 *
 * @param array $secureAttributes will be passed as html attributes into the hidden input elements generated for the
 *   Security Component.
 * @return string A closing FORM tag.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#closing-the-form
 */
	public function end($secureAttributes = []) {
		$out = '';

		if (
			$this->requestType !== 'get' &&
			!empty($this->request['_Token'])
		) {
			$out .= $this->secure($this->fields, $secureAttributes);
			$this->fields = array();
		}

		$out .= $this->formatTemplate('formend', []);

		$this->requestType = null;
		$this->_context = null;
		return $out;
	}

/**
 * Generates a hidden field with a security hash based on the fields used in
 * the form.
 *
 * If $secureAttributes is set, these html attributes will be merged into
 * the hidden input tags generated for the Security Component. This is
 * especially useful to set HTML5 attributes like 'form'.
 *
 * @param array|null $fields If set specifies the list of fields to use when
 *    generating the hash, else $this->fields is being used.
 * @param array $secureAttributes will be passed as html attributes into the hidden
 *    input elements generated for the Security Component.
 * @return string A hidden input field with a security hash
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::secure
 */
	public function secure($fields = array(), $secureAttributes = array()) {
		if (!isset($this->request['_Token']) || empty($this->request['_Token'])) {
			return;
		}
		$locked = array();
		$unlockedFields = $this->_unlockedFields;

		foreach ($fields as $key => $value) {
			if (!is_int($key)) {
				$locked[$key] = $value;
				unset($fields[$key]);
			}
		}

		sort($unlockedFields, SORT_STRING);
		sort($fields, SORT_STRING);
		ksort($locked, SORT_STRING);
		$fields += $locked;

		$locked = implode(array_keys($locked), '|');
		$unlocked = implode($unlockedFields, '|');
		$fields = Security::hash(serialize($fields) . $unlocked . Configure::read('Security.salt'), 'sha1');

		$tokenFields = array_merge($secureAttributes, array(
			'value' => urlencode($fields . ':' . $locked),
		));
		$out = $this->hidden('_Token.fields', $tokenFields);
		$tokenUnlocked = array_merge($secureAttributes, array(
			'value' => urlencode($unlocked),
		));
		$out .= $this->hidden('_Token.unlocked', $tokenUnlocked);
		return $this->formatTemplate('hiddenblock', ['content' => $out]);
	}

/**
 * Add to or get the list of fields that are currently unlocked.
 * Unlocked fields are not included in the field hash used by SecurityComponent
 * unlocking a field once its been added to the list of secured fields will remove
 * it from the list of fields.
 *
 * @param string $name The dot separated name for the field.
 * @return mixed Either null, or the list of fields.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::unlockField
 */
	public function unlockField($name = null) {
		if ($name === null) {
			return $this->_unlockedFields;
		}
		if (!in_array($name, $this->_unlockedFields)) {
			$this->_unlockedFields[] = $name;
		}
		$index = array_search($name, $this->fields);
		if ($index !== false) {
			unset($this->fields[$index]);
		}
		unset($this->fields[$name]);
	}

/**
 * Determine which fields of a form should be used for hash.
 * Populates $this->fields
 *
 * @param boolean $lock Whether this field should be part of the validation
 *   or excluded as part of the unlockedFields.
 * @param string|array $field Reference to field to be secured. Can be dot
 *   separated string to indicate nesting or array of fieldname parts.
 * @param mixed $value Field value, if value should not be tampered with.
 * @return mixed|null Not used yet
 */
	protected function _secure($lock, $field, $value = null) {
		if (is_string($field)) {
			$field = Hash::filter(explode('.', $field));
		}

		foreach ($this->_unlockedFields as $unlockField) {
			$unlockParts = explode('.', $unlockField);
			if (array_values(array_intersect($field, $unlockParts)) === $unlockParts) {
				return;
			}
		}

		$field = implode('.', $field);
		$field = preg_replace('/(\.\d+)+$/', '', $field);

		if ($lock) {
			if (!in_array($field, $this->fields)) {
				if ($value !== null) {
					return $this->fields[$field] = $value;
				}
				$this->fields[] = $field;
			}
		} else {
			$this->unlockField($field);
		}
	}

/**
 * Returns true if there is an error for the given field, otherwise false
 *
 * @param string $field This should be "Modelname.fieldname"
 * @return boolean If there are errors this method returns true, else false.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::isFieldError
 */
	public function isFieldError($field) {
		return $this->_getContext()->hasError($field);
	}

/**
 * Returns a formatted error message for given form field, '' if no errors.
 *
 * Uses the `error`, `errorList` and `errorItem` templates. The `errorList` and
 * `errorItem` templates are used to format multiple error messages per field.
 *
 * ### Options:
 *
 * - `escape` boolean - Whether or not to html escape the contents of the error.
 *
 * @param string $field A field name, like "Modelname.fieldname"
 * @param string|array $text Error message as string or array of messages. If an array,
 *   it should be a hash of key names => messages.
 * @param array $options See above.
 * @return string Formatted errors or ''.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::error
 */
	public function error($field, $text = null, $options = []) {
		$options += ['escape' => true];

		$context = $this->_getContext();
		if (!$context->hasError($field)) {
			return '';
		}
		$error = (array)$context->error($field);

		if (is_array($text)) {
			$tmp = [];
			foreach ($error as $e) {
				if (isset($text[$e])) {
					$tmp[] = $text[$e];
				} else {
					$tmp[] = $e;
				}
			}
			$text = $tmp;
		}

		if ($text !== null) {
			$error = $text;
		}

		if ($options['escape']) {
			$error = h($error);
			unset($options['escape']);
		}

		if (is_array($error)) {
			if (count($error) > 1) {
				$errorText = [];
				foreach ($error as $err) {
					$errorText[] = $this->formatTemplate('errorItem', ['text' => $err]);
				}
				$error = $this->formatTemplate('errorList', [
					'content' => implode('', $errorText)
				]);
			} else {
				$error = array_pop($error);
			}
		}
		return $this->formatTemplate('error', ['content' => $error]);
	}

/**
 * Returns a formatted LABEL element for HTML forms.
 *
 * Will automatically generate a `for` attribute if one is not provided.
 *
 * ### Options
 *
 * - `for` - Set the for attribute, if its not defined the for attribute
 *   will be generated from the $fieldName parameter using
 *   FormHelper::_domId().
 *
 * Examples:
 *
 * The text and for attribute are generated off of the fieldname
 *
 * {{{
 * echo $this->Form->label('Post.published');
 * <label for="PostPublished">Published</label>
 * }}}
 *
 * Custom text:
 *
 * {{{
 * echo $this->Form->label('Post.published', 'Publish');
 * <label for="PostPublished">Publish</label>
 * }}}
 *
 * Custom class name:
 *
 * {{{
 * echo $this->Form->label('Post.published', 'Publish', 'required');
 * <label for="PostPublished" class="required">Publish</label>
 * }}}
 *
 * Custom attributes:
 *
 * {{{
 * echo $this->Form->label('Post.published', 'Publish', array(
 *   'for' => 'post-publish'
 * ));
 * <label for="post-publish">Publish</label>
 * }}}
 *
 * @param string $fieldName This should be "Modelname.fieldname"
 * @param string $text Text that will appear in the label field. If
 *   $text is left undefined the text will be inflected from the
 *   fieldName.
 * @param array|string $options An array of HTML attributes, or a string, to be used as a class name.
 * @return string The formatted LABEL element
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::label
 */
	public function label($fieldName, $text = null, $options = array()) {
		if ($text === null) {
			if (strpos($fieldName, '.') !== false) {
				$fieldElements = explode('.', $fieldName);
				$text = array_pop($fieldElements);
			} else {
				$text = $fieldName;
			}
			if (substr($text, -3) === '_id') {
				$text = substr($text, 0, -3);
			}
			$text = __(Inflector::humanize(Inflector::underscore($text)));
		}

		if (is_string($options)) {
			$options = array('class' => $options);
		}

		if (isset($options['for'])) {
			$labelFor = $options['for'];
			unset($options['for']);
		} else {
			$labelFor = $this->_domId($fieldName);
		}
		$attrs = $options + [
			'for' => $labelFor,
			'text' => $text,
		];
		return $this->widget('label', $attrs);
	}

/**
 * Generate an ID suitable for use in an ID attribute.
 *
 * @param string $value The value to convert into an ID.
 * @return string The generated id.
 */
	protected function _domId($value) {
		return mb_strtolower(Inflector::slug($value, '-'));
	}

/**
 * Generate a set of inputs for `$fields`. If $fields is null the fields of current model
 * will be used.
 *
 * You can customize individual inputs through `$fields`.
 * {{{
 *	$this->Form->inputs(array(
 *		'name' => array('label' => 'custom label')
 *	));
 * }}}
 *
 * In addition to controller fields output, `$fields` can be used to control legend
 * and fieldset rendering.
 * `$this->Form->inputs('My legend');` Would generate an input set with a custom legend.
 * Passing `fieldset` and `legend` key in `$fields` array has been deprecated since 2.3,
 * for more fine grained control use the `fieldset` and `legend` keys in `$options` param.
 *
 * @param array $fields An array of fields to generate inputs for, or null.
 * @param array $blacklist A simple array of fields to not create inputs for.
 * @param array $options Options array. Valid keys are:
 * - `fieldset` Set to false to disable the fieldset. If a string is supplied it will be used as
 *    the class name for the fieldset element.
 * - `legend` Set to false to disable the legend for the generated input set. Or supply a string
 *    to customize the legend text.
 * @return string Completed form inputs.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::inputs
 */
	public function inputs($fields = null, $blacklist = null, $options = array()) {
		$fieldset = $legend = true;
		$modelFields = array();
		$model = $this->model();
		if ($model) {
			$modelFields = array_keys((array)$this->_introspectModel($model, 'fields'));
		}
		if (is_array($fields)) {
			if (array_key_exists('legend', $fields) && !in_array('legend', $modelFields)) {
				$legend = $fields['legend'];
				unset($fields['legend']);
			}

			if (isset($fields['fieldset']) && !in_array('fieldset', $modelFields)) {
				$fieldset = $fields['fieldset'];
				unset($fields['fieldset']);
			}
		} elseif ($fields !== null) {
			$fieldset = $legend = $fields;
			if (!is_bool($fieldset)) {
				$fieldset = true;
			}
			$fields = array();
		}

		if (isset($options['legend'])) {
			$legend = $options['legend'];
		}
		if (isset($options['fieldset'])) {
			$fieldset = $options['fieldset'];
		}

		if (empty($fields)) {
			$fields = $modelFields;
		}

		if ($legend === true) {
			$actionName = __d('cake', 'New %s');
			$isEdit = (
				strpos($this->request->params['action'], 'update') !== false ||
				strpos($this->request->params['action'], 'edit') !== false
			);
			if ($isEdit) {
				$actionName = __d('cake', 'Edit %s');
			}
			$modelName = Inflector::humanize(Inflector::underscore($model));
			$legend = sprintf($actionName, __($modelName));
		}

		$out = null;
		foreach ($fields as $name => $options) {
			if (is_numeric($name) && !is_array($options)) {
				$name = $options;
				$options = array();
			}
			$entity = explode('.', $name);
			$blacklisted = (
				is_array($blacklist) &&
				(in_array($name, $blacklist) || in_array(end($entity), $blacklist))
			);
			if ($blacklisted) {
				continue;
			}
			$out .= $this->input($name, $options);
		}

		if (is_string($fieldset)) {
			$fieldsetClass = sprintf(' class="%s"', $fieldset);
		} else {
			$fieldsetClass = '';
		}

		if ($fieldset) {
			if ($legend) {
				$out = $this->Html->useTag('legend', $legend) . $out;
			}
			$out = $this->Html->useTag('fieldset', $fieldsetClass, $out);
		}
		return $out;
	}

/**
 * Generates a form input element complete with label and wrapper div
 *
 * ### Options
 *
 * See each field type method for more information. Any options that are part of
 * $attributes or $options for the different **type** methods can be included in `$options` for input().i
 * Additionally, any unknown keys that are not in the list below, or part of the selected type's options
 * will be treated as a regular html attribute for the generated input.
 *
 * - `type` - Force the type of widget you want. e.g. `type => 'select'`
 * - `label` - Either a string label, or an array of options for the label. See FormHelper::label().
 * - `div` - Either `false` to disable the div, or an array of options for the div.
 *	See HtmlHelper::div() for more options.
 * - `options` - For widgets that take options e.g. radio, select.
 * - `error` - Control the error message that is produced. Set to `false` to disable any kind of error reporting (field
 *    error and error messages).
 * - `empty` - String or boolean to enable empty select box options.
 * - `before` - Content to place before the label + input.
 * - `after` - Content to place after the label + input.
 * - `between` - Content to place between the label + input.
 * - `format` - Format template for element order. Any element that is not in the array, will not be in the output.
 *	- Default input format order: array('before', 'label', 'between', 'input', 'after', 'error')
 *	- Default checkbox format order: array('before', 'input', 'between', 'label', 'after', 'error')
 *	- Hidden input will not be formatted
 *	- Radio buttons cannot have the order of input and label elements controlled with these settings.
 *
 * @param string $fieldName This should be "Modelname.fieldname"
 * @param array $options Each type of input takes different options.
 * @return string Completed form widget.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#creating-form-elements
 */
	public function input($fieldName, $options = array()) {
		$options += [
			'type' => null,
			'label' => null,
			'error' => null,
			'options' => null,
			'templates' => []
		];
		$options = $this->_parseOptions($fieldName, $options);
		$options += ['id' => $this->_domId($fieldName)];

		$originalTemplates = $this->templates();
		$this->templates($options['templates']);
		unset($options['templates']);
		$label = $this->_getLabel($fieldName, $options);
		if ($options['type'] !== 'radio') {
			unset($options['label']);
		}

		$template = 'groupContainer';
		$error = null;
		if ($options['type'] !== 'hidden' && $options['error'] !== false) {
			$error = $this->error($fieldName, $options['error']);
			$template = empty($error) ? $template : 'groupContainerError';
			unset($options['error']);
		}

		$groupTemplate = $options['type'] === 'checkbox' ? 'checkboxFormGroup' : 'formGroup';
		$input = $this->_getInput($fieldName, $options);
		$result = $this->formatTemplate($groupTemplate, compact('input', 'label'));

		if ($options['type'] !== 'hidden') {
			$result = $this->formatTemplate($template, [
				'content' => $result,
				'error' => $error,
				'required' => null,
				'type' => $options['type'],
			]);
		}

		$this->templates($originalTemplates);
		return $result;
	}

/**
 * Generates an input element
 *
 * @param string $fieldName the field name
 * @param array $options The options for the input element
 * @return string The generated input element
 */
	protected function _getInput($fieldName, $options) {
		switch ($options['type']) {
			case 'select':
				$opts = $options['options'];
				unset($options['options']);
				return $this->select($fieldName, $opts, $options);
			case 'url':
				$options = $this->_initInputField($fieldName, $options);
				return $this->widget($options['type'], $options);
			default:
				return $this->{$options['type']}($fieldName, $options);
		}
	}

/**
 * Generates input options array
 *
 * @param string $fieldName the name of the field to parse options for
 * @param array $options
 * @return array Options
 */
	protected function _parseOptions($fieldName, $options) {
		$needsMagicType = false;
		if (empty($options['type'])) {
			$needsMagicType = true;
			$options['type'] = $this->_inputType($fieldName, $options);
		}

		$options = $this->_magicOptions($fieldName, $options, $needsMagicType);
		return $options;
	}

/**
 * Returns the input type that was guessed for the provided fieldName,
 * based on the internal type it is associated too, its name and the
 * variales that can be found in the view template
 *
 * @param string $fieldName the name of the field to guess a type for
 * @param array $options the options passed to the input method
 * @return string
 */
	protected function _inputType($fieldName, $options) {
		$context = $this->_getContext();
		$primaryKey = (array)$context->primaryKey();

		if (in_array($fieldName, $primaryKey)) {
			return 'hidden';
		}

		if (substr($fieldName, -3) === '_id') {
			return 'select';
		}

		$internalType = $context->type($fieldName);
		$map = $this->settings['typeMap'];
		$type = isset($map[$internalType]) ? $map[$internalType] : 'text';
		$fieldName = array_slice(explode('.', $fieldName), -1)[0];

		switch (true) {
			case isset($options['checked']) :
				return 'checkbox';
			case isset($options['options']) :
				return 'select';
			case in_array($fieldName, ['passwd', 'password']) :
				return 'password';
			case in_array($fieldName, ['tel', 'telephone', 'phone']) :
				return 'tel';
			case $fieldName === 'email':
				return 'email';
			case isset($options['rows']) || isset($options['cols']) :
				return 'textarea';
		}

		return $type;
	}

/**
 * Selects the variable containing the options for a select field if present,
 * and sets the value to the 'options' key in the options array.
 *
 * @param string $fieldName the name of the field to find options for
 * @param array $options
 * @return array
 */
	protected function _optionsOptions($fieldName, $options) {
		if (isset($options['options'])) {
			return $options;
		}

		$fieldName = array_slice(explode('.', $fieldName), -1)[0];
		$varName = Inflector::variable(
			Inflector::pluralize(preg_replace('/_id$/', '', $fieldName))
		);
		$varOptions = $this->_View->get($varName);
		if (!is_array($varOptions)) {
			return $options;
		}
		if ($options['type'] !== 'radio') {
			$options['type'] = 'select';
		}
		$options['options'] = $varOptions;
		return $options;
	}

/**
 * Magically set option type and corresponding options
 *
 * @param string $fieldName the name of the field to generate options for
 * @param array $options
 * @param boolean $allowOverride whether or not it is allowed for this method to
 * overwrite the 'type' key in options
 * @return array
 */
	protected function _magicOptions($fieldName, $options, $allowOverride) {
		$context = $this->_getContext();
		$type = $context->type($fieldName);
		$fieldDef = $context->attributes($fieldName);

		if ($options['type'] === 'number' && !isset($options['step'])) {
			if ($type === 'decimal') {
				$decimalPlaces = substr($fieldDef['length'], strpos($fieldDef['length'], ',') + 1);
				$options['step'] = sprintf('%.' . $decimalPlaces . 'F', pow(10, -1 * $decimalPlaces));
			} elseif ($type === 'float') {
				$options['step'] = 'any';
			}
		}

		// Missing HABTM
		//...

		$typesWithOptions = ['text', 'number', 'radio', 'select'];
		if ($allowOverride && in_array($options['type'], $typesWithOptions)) {
			$options = $this->_optionsOptions($fieldName, $options);
		}

		if ($options['type'] === 'select' && array_key_exists('step', $options)) {
			unset($options['step']);
		}

		$autoLength = !array_key_exists('maxlength', $options)
			&& !empty($fieldDef['length'])
			&& $options['type'] !== 'select';

		$allowedTypes = ['text', 'email', 'tel', 'url', 'search'];
		if ($autoLength && in_array($options['type'], $allowedTypes)) {
			$options['maxlength'] = $fieldDef['length'];
		}

		if (in_array($options['type'], ['datetime', 'date', 'time', 'select'])) {
			$options += ['empty' => false];
		}

		return $options;
	}

/**
 * Generate label for input
 *
 * @param string $fieldName
 * @param array $options
 * @return boolean|string false or Generated label element
 */
	protected function _getLabel($fieldName, $options) {
		if ($options['type'] === 'radio') {
			return false;
		}

		$label = null;
		if (isset($options['label'])) {
			$label = $options['label'];
		}

		if ($label === false) {
			return false;
		}
		return $this->_inputLabel($fieldName, $label, $options);
	}

/**
 * Extracts a single option from an options array.
 *
 * @param string $name The name of the option to pull out.
 * @param array $options The array of options you want to extract.
 * @param mixed $default The default option value
 * @return mixed the contents of the option or default
 */
	protected function _extractOption($name, $options, $default = null) {
		if (array_key_exists($name, $options)) {
			return $options[$name];
		}
		return $default;
	}

/**
 * Generate a label for an input() call.
 *
 * $options can contain a hash of id overrides. These overrides will be
 * used instead of the generated values if present.
 *
 * @param string $fieldName
 * @param string $label
 * @param array $options Options for the label element. 'NONE' option is deprecated and will be removed in 3.0
 * @return string Generated label element
 */
	protected function _inputLabel($fieldName, $label, $options) {
		$labelAttributes = [];
		$idKey = null;
		if ($options['type'] === 'date' || $options['type'] === 'datetime') {
			$firstInput = 'M';
			if (
				array_key_exists('dateFormat', $options) &&
				($options['dateFormat'] === null || $options['dateFormat'] === 'NONE')
			) {
				$firstInput = 'H';
			} elseif (!empty($options['dateFormat'])) {
				$firstInput = substr($options['dateFormat'], 0, 1);
			}
			switch ($firstInput) {
				case 'D':
					$idKey = 'day';
					$labelAttributes['for'] .= 'Day';
					break;
				case 'Y':
					$idKey = 'year';
					$labelAttributes['for'] .= 'Year';
					break;
				case 'M':
					$idKey = 'month';
					$labelAttributes['for'] .= 'Month';
					break;
				case 'H':
					$idKey = 'hour';
					$labelAttributes['for'] .= 'Hour';
			}
		}
		if ($options['type'] === 'time') {
			$labelAttributes['for'] .= 'Hour';
			$idKey = 'hour';
		}
		if (isset($idKey) && isset($options['id']) && isset($options['id'][$idKey])) {
			$labelAttributes['for'] = $options['id'][$idKey];
		}

		if (is_array($label)) {
			$labelText = null;
			if (isset($label['text'])) {
				$labelText = $label['text'];
				unset($label['text']);
			}
			$labelAttributes = array_merge($labelAttributes, $label);
		} else {
			$labelText = $label;
		}

		if (isset($options['id']) && is_string($options['id'])) {
			$labelAttributes = array_merge($labelAttributes, array('for' => $options['id']));
		}
		return $this->label($fieldName, $labelText, $labelAttributes);
	}

/**
 * Creates a checkbox input widget.
 *
 * ### Options:
 *
 * - `value` - the value of the checkbox
 * - `checked` - boolean indicate that this checkbox is checked.
 * - `hiddenField` - boolean to indicate if you want the results of checkbox() to include
 *    a hidden input with a value of ''.
 * - `disabled` - create a disabled input.
 * - `default` - Set the default value for the checkbox. This allows you to start checkboxes
 *    as checked, without having to check the POST data. A matching POST data value, will overwrite
 *    the default value.
 *
 * @param string $fieldName Name of a field, like this "Modelname.fieldname"
 * @param array $options Array of HTML attributes.
 * @return string An HTML text input element.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#options-for-select-checkbox-and-radio-inputs
 */
	public function checkbox($fieldName, $options = []) {
		$options += array('hiddenField' => true, 'value' => 1);

		// Work around value=>val translations.
		$value = $options['value'];
		unset($options['value']);
		$options = $this->_initInputField($fieldName, $options);
		$options['value'] = $value;

		$output = '';
		if ($options['hiddenField']) {
			$hiddenOptions = array(
				'name' => $options['name'],
				'value' => ($options['hiddenField'] !== true ? $options['hiddenField'] : '0'),
				'secure' => false
			);
			if (isset($options['disabled']) && $options['disabled']) {
				$hiddenOptions['disabled'] = 'disabled';
			}
			$output = $this->hidden($fieldName, $hiddenOptions);
		}
		unset($options['hiddenField'], $options['type']);
		return $output . $this->widget('checkbox', $options);
	}

/**
 * Creates a set of radio widgets.
 *
 * ### Attributes:
 *
 * - `value` - Indicate a value that is should be checked
 * - `label` - boolean to indicate whether or not labels for widgets show be displayed
 * - `hiddenField` - boolean to indicate if you want the results of radio() to include
 *    a hidden input with a value of ''. This is useful for creating radio sets that non-continuous
 * - `disabled` - Set to `true` or `disabled` to disable all the radio buttons.
 * - `empty` - Set to `true` to create a input with the value '' as the first option. When `true`
 *   the radio label will be 'empty'. Set this option to a string to control the label value.
 *
 * @param string $fieldName Name of a field, like this "Modelname.fieldname"
 * @param array $options Radio button options array.
 * @param array $attributes Array of HTML attributes, and special attributes above.
 * @return string Completed radio widget set.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#options-for-select-checkbox-and-radio-inputs
 */
	public function radio($fieldName, $options = [], $attributes = []) {
		$attributes = $this->_initInputField($fieldName, $attributes);

		$out = [];
		$hiddenField = isset($attributes['hiddenField']) ? $attributes['hiddenField'] : true;
		unset($attributes['hiddenField']);

		$value = $attributes['val'];
		$hidden = '';
		if ($hiddenField && (!isset($value) || $value === '')) {
			$hidden = $this->hidden($fieldName, [
				'value' => '',
				'name' => $attributes['name']
			]);
		}
		$attributes['options'] = $options;

		return $hidden . $this->widget('radio', $attributes);
	}

/**
 * Missing method handler - implements various simple input types. Is used to create inputs
 * of various types. e.g. `$this->Form->text();` will create `<input type="text" />` while
 * `$this->Form->range();` will create `<input type="range" />`
 *
 * ### Usage
 *
 * `$this->Form->search('User.query', array('value' => 'test'));`
 *
 * Will make an input like:
 *
 * `<input type="search" id="UserQuery" name="User[query]" value="test" />`
 *
 * The first argument to an input type should always be the fieldname, in `Model.field` format.
 * The second argument should always be an array of attributes for the input.
 *
 * @param string $method Method name / input type to make.
 * @param array $params Parameters for the method call
 * @return string Formatted input method.
 * @throws \Cake\Error\Exception When there are no params for the method call.
 */
	public function __call($method, $params) {
		$options = [];
		if (empty($params)) {
			throw new Error\Exception(sprintf('Missing field name for FormHelper::%s', $method));
		}
		if (isset($params[1])) {
			$options = $params[1];
		}
		if (!isset($options['type'])) {
			$options['type'] = $method;
		}
		$options = $this->_initInputField($params[0], $options);
		return $this->widget($options['type'], $options);
	}

/**
 * Creates a textarea widget.
 *
 * ### Options:
 *
 * - `escape` - Whether or not the contents of the textarea should be escaped. Defaults to true.
 *
 * @param string $fieldName Name of a field, in the form "Modelname.fieldname"
 * @param array $options Array of HTML attributes, and special options above.
 * @return string A generated HTML text input element
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::textarea
 */
	public function textarea($fieldName, $options = array()) {
		$options = $this->_initInputField($fieldName, $options);
		return $this->widget('textarea', $options);
	}

/**
 * Creates a hidden input field.
 *
 * @param string $fieldName Name of a field, in the form of "Modelname.fieldname"
 * @param array $options Array of HTML attributes.
 * @return string A generated hidden input
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::hidden
 */
	public function hidden($fieldName, $options = array()) {
		$options += array('required' => false, 'secure' => true);

		$secure = $options['secure'];
		unset($options['secure']);

		$options = $this->_initInputField($fieldName, array_merge(
			$options, array('secure' => static::SECURE_SKIP)
		));

		if ($secure === true) {
			$this->_secure(true, $this->_secureFieldName($options), $options['val']);
		}

		$options['type'] = 'hidden';
		return $this->widget('hidden', $options);
	}

/**
 * Creates file input widget.
 *
 * @param string $fieldName Name of a field, in the form "Modelname.fieldname"
 * @param array $options Array of HTML attributes.
 * @return string A generated file input.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::file
 */
	public function file($fieldName, $options = array()) {
		$options += array('secure' => true);
		$secure = $options['secure'];
		$options['secure'] = static::SECURE_SKIP;

		$options = $this->_initInputField($fieldName, $options);

		foreach (array('name', 'type', 'tmp_name', 'error', 'size') as $suffix) {
			$this->_secure(
				$secure,
				$this->_secureFieldName(['name' => $options['name'] . '[' . $suffix . ']'])
			);
		}

		unset($options['type']);
		return $this->widget('file', $options);
	}

/**
 * Creates a `<button>` tag.
 *
 * The type attribute defaults to `type="submit"`
 * You can change it to a different value by using `$options['type']`.
 *
 * ### Options:
 *
 * - `escape` - HTML entity encode the $title of the button. Defaults to false.
 *
 * @param string $title The button's caption. Not automatically HTML encoded
 * @param array $options Array of options and HTML attributes.
 * @return string A HTML button tag.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::button
 */
	public function button($title, $options = array()) {
		$options += array('type' => 'submit', 'escape' => false, 'secure' => false);
		if (isset($options['name'])) {
			$this->_secure($options['secure'], $this->_secureFieldName($options));
		}
		unset($options['secure']);

		$options['text'] = $title;
		return $this->widget('button', $options);
	}

/**
 * Create a `<button>` tag with a surrounding `<form>` that submits via POST.
 *
 * This method creates a `<form>` element. So do not use this method in an already opened form.
 * Instead use FormHelper::submit() or FormHelper::button() to create buttons inside opened forms.
 *
 * ### Options:
 *
 * - `data` - Array with key/value to pass in input hidden
 * - Other options is the same of button method.
 *
 * @param string $title The button's caption. Not automatically HTML encoded
 * @param string|array $url URL as string or array
 * @param array $options Array of options and HTML attributes.
 * @return string A HTML button tag.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::postButton
 */
	public function postButton($title, $url, $options = array()) {
		$out = $this->create(false, array('url' => $url));
		if (isset($options['data']) && is_array($options['data'])) {
			foreach (Hash::flatten($options['data']) as $key => $value) {
				$out .= $this->hidden($key, array('value' => $value));
			}
			unset($options['data']);
		}
		$out .= $this->button($title, $options);
		$out .= $this->end();
		return $out;
	}

/**
 * Creates an HTML link, but access the URL using the method you specify (defaults to POST).
 * Requires javascript to be enabled in browser.
 *
 * This method creates a `<form>` element. So do not use this method inside an existing form.
 * Instead you should add a submit button using FormHelper::submit()
 *
 * ### Options:
 *
 * - `data` - Array with key/value to pass in input hidden
 * - `method` - Request method to use. Set to 'delete' to simulate HTTP/1.1 DELETE request. Defaults to 'post'.
 * - `confirm` - Can be used instead of $confirmMessage.
 * - `inline` - Whether or not the associated form tag should be output inline.
 *   Set to false to have the form tag appended to the 'postLink' view block.
 *   Defaults to true.
 * - `block` - Choose a custom block to append the form tag to. Using this option
 *   will override the inline option.
 * - Other options are the same of HtmlHelper::link() method.
 * - The option `onclick` will be replaced.
 *
 * @param string $title The content to be wrapped by <a> tags.
 * @param string|array $url Cake-relative URL or array of URL parameters, or external URL (starts with http://)
 * @param array $options Array of HTML attributes.
 * @param boolean|string $confirmMessage JavaScript confirmation message.
 * @return string An `<a />` element.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::postLink
 */
	public function postLink($title, $url = null, $options = array(), $confirmMessage = false) {
		$options += array('inline' => true, 'block' => null);
		if (!$options['inline'] && empty($options['block'])) {
			$options['block'] = __FUNCTION__;
		}
		unset($options['inline']);

		$requestMethod = 'POST';
		if (!empty($options['method'])) {
			$requestMethod = strtoupper($options['method']);
			unset($options['method']);
		}
		if (!empty($options['confirm'])) {
			$confirmMessage = $options['confirm'];
			unset($options['confirm']);
		}

		$formName = str_replace('.', '', uniqid('post_', true));
		$formOptions = array(
			'action' => $this->url($url),
			'name' => $formName,
			'style' => 'display:none;',
			'method' => 'post',
		);
		if (isset($options['target'])) {
			$formOptions['target'] = $options['target'];
			unset($options['target']);
		}

		$out = $this->formatTemplate('formstart', [
			'attrs' => $this->_templater->formatAttributes($formOptions)
		]);
		$out .= $this->hidden('_method', ['value' => $requestMethod]);
		$out .= $this->_csrfField();

		$fields = array();
		if (isset($options['data']) && is_array($options['data'])) {
			foreach (Hash::flatten($options['data']) as $key => $value) {
				$fields[$key] = $value;
				$out .= $this->hidden($key, array('value' => $value));
			}
			unset($options['data']);
		}
		$out .= $this->secure($fields);
		$out .= $this->formatTemplate('formend', []);

		if ($options['block']) {
			$this->_View->append($options['block'], $out);
			$out = '';
		}
		unset($options['block']);

		$url = '#';
		$onClick = 'document.' . $formName . '.submit();';
		if ($confirmMessage) {
			$options['onclick'] = $this->_confirm($confirmMessage, $onClick, '', $options);
		} else {
			$options['onclick'] = $onClick . ' ';
		}
		$options['onclick'] .= 'event.returnValue = false; return false;';

		$out .= $this->Html->link($title, $url, $options);
		return $out;
	}

/**
 * Creates a submit button element. This method will generate `<input />` elements that
 * can be used to submit, and reset forms by using $options. image submits can be created by supplying an
 * image path for $caption.
 *
 * ### Options
 *
 * - `div` - Include a wrapping div?  Defaults to true. Accepts sub options similar to
 *   FormHelper::input().
 * - `before` - Content to include before the input.
 * - `after` - Content to include after the input.
 * - `type` - Set to 'reset' for reset inputs. Defaults to 'submit'
 * - Other attributes will be assigned to the input element.
 *
 * ### Options
 *
 * - `div` - Include a wrapping div?  Defaults to true. Accepts sub options similar to
 *   FormHelper::input().
 * - Other attributes will be assigned to the input element.
 *
 * @param string $caption The label appearing on the button OR if string contains :// or the
 *  extension .jpg, .jpe, .jpeg, .gif, .png use an image if the extension
 *  exists, AND the first character is /, image is relative to webroot,
 *  OR if the first character is not /, image is relative to webroot/img.
 * @param array $options Array of options. See above.
 * @return string A HTML submit button
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::submit
 */
	public function submit($caption = null, $options = array()) {
		if (!is_string($caption) && empty($caption)) {
			$caption = __d('cake', 'Submit');
		}
		$out = null;
		$div = true;

		if (isset($options['div'])) {
			$div = $options['div'];
			unset($options['div']);
		}
		$options += array('type' => 'submit', 'before' => null, 'after' => null, 'secure' => false);
		$divOptions = array('tag' => 'div');

		if ($div === true) {
			$divOptions['class'] = 'submit';
		} elseif ($div === false) {
			unset($divOptions);
		} elseif (is_string($div)) {
			$divOptions['class'] = $div;
		} elseif (is_array($div)) {
			$divOptions = array_merge(array('class' => 'submit', 'tag' => 'div'), $div);
		}

		if (isset($options['name'])) {
			$name = str_replace(array('[', ']'), array('.', ''), $options['name']);
			$this->_secure($options['secure'], $this->_secureFieldName($options));
		}
		unset($options['secure']);

		$before = $options['before'];
		$after = $options['after'];
		unset($options['before'], $options['after']);

		$isUrl = strpos($caption, '://') !== false;
		$isImage = preg_match('/\.(jpg|jpe|jpeg|gif|png|ico)$/', $caption);

		if ($isUrl || $isImage) {
			$unlockFields = array('x', 'y');
			if (isset($options['name'])) {
				$unlockFields = array(
					$options['name'] . '_x', $options['name'] . '_y'
				);
			}
			foreach ($unlockFields as $ignore) {
				$this->unlockField($ignore);
			}
		}

		if ($isUrl) {
			unset($options['type']);
			$tag = $this->Html->useTag('submitimage', $caption, $options);
		} elseif ($isImage) {
			unset($options['type']);
			if ($caption{0} !== '/') {
				$url = $this->webroot(Configure::read('App.imageBaseUrl') . $caption);
			} else {
				$url = $this->webroot(trim($caption, '/'));
			}
			$url = $this->assetTimestamp($url);
			$tag = $this->Html->useTag('submitimage', $url, $options);
		} else {
			$options['value'] = $caption;
			$tag = $this->Html->useTag('submit', $options);
		}
		$out = $before . $tag . $after;

		if (isset($divOptions)) {
			$tag = $divOptions['tag'];
			unset($divOptions['tag']);
			$out = $this->Html->tag($tag, $out, $divOptions);
		}
		return $out;
	}

/**
 * Returns a formatted SELECT element.
 *
 * ### Attributes:
 *
 * - `multiple` - show a multiple select box. If set to 'checkbox' multiple checkboxes will be
 *   created instead.
 * - `empty` - If true, the empty select option is shown. If a string,
 *   that string is displayed as the empty element.
 * - `escape` - If true contents of options will be HTML entity encoded. Defaults to true.
 * - `val` The selected value of the input.
 * - `disabled` - Control the disabled attribute. When creating a select box, set to true to disable the
 *   select box. Set to an array to disable specific option elements.
 *
 * ### Using options
 *
 * A simple array will create normal options:
 *
 * {{{
 * $options = array(1 => 'one', 2 => 'two);
 * $this->Form->select('Model.field', $options));
 * }}}
 *
 * While a nested options array will create optgroups with options inside them.
 * {{{
 * $options = array(
 *  1 => 'bill',
 *  'fred' => array(
 *     2 => 'fred',
 *     3 => 'fred jr.'
 *  )
 * );
 * $this->Form->select('Model.field', $options);
 * }}}
 *
 * If you have multiple options that need to have the same value attribute, you can
 * use an array of arrays to express this:
 *
 * {{{
 * $options = array(
 *  array('name' => 'United states', 'value' => 'USA'),
 *  array('name' => 'USA', 'value' => 'USA'),
 * );
 * }}}
 *
 * @param string $fieldName Name attribute of the SELECT
 * @param array $options Array of the OPTION elements (as 'value'=>'Text' pairs) to be used in the
 *   SELECT element
 * @param array $attributes The HTML attributes of the select element.
 * @return string Formatted SELECT element
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#options-for-select-checkbox-and-radio-inputs
 * @see \Cake\View\Helper\FormHelper::multiCheckbox() for creating multiple checkboxes.
 */
	public function select($fieldName, $options = [], $attributes = []) {
		$attributes += [
			'disabled' => null,
			'escape' => true,
			'hiddenField' => true,
			'multiple' => null,
			'secure' => true,
			'empty' => false,
		];

		if ($attributes['multiple'] === 'checkbox') {
			unset($attributes['multiple'], $attributes['empty']);
			return $this->multiCheckbox($fieldName, $options, $attributes);
		}

		// Secure the field if there are options, or its a multi select.
		// Single selects with no options don't submit, but multiselects do.
		if (
			$attributes['secure'] &&
			empty($options) &&
			empty($attributes['empty']) &&
			empty($attributes['multiple'])
		) {
			$attributes['secure'] = false;
		}

		$attributes = $this->_initInputField($fieldName, $attributes);
		$attributes['options'] = $options;

		$hidden = '';
		if ($attributes['multiple'] && $attributes['hiddenField']) {
			$hiddenAttributes = array(
				'name' => $attributes['name'],
				'value' => '',
				'secure' => false,
			);
			$hidden = $this->hidden($fieldName, $hiddenAttributes);
		}
		unset($attributes['hiddenField'], $attributes['type']);
		return $hidden . $this->widget('select', $attributes);
	}

/**
 * Creates a set of checkboxes out of options.
 *
 * ### Options
 *
 * - `escape` - If true contents of options will be HTML entity encoded. Defaults to true.
 * - `val` The selected value of the input.
 * - `class` - When using multiple = checkbox the class name to apply to the divs. Defaults to 'checkbox'.
 * - `disabled` - Control the disabled attribute. When creating checkboxes, `true` will disable all checkboxes.
 *   You can also set disabled to a list of values you want to disable when creating checkboxes.
 * - `hiddenField` - Set to false to remove the hidden field that ensures a value
 *   is always submitted.
 *
 * Can be used in place of a select box with the multiple attribute.
 *
 * @param string $fieldName Name attribute of the SELECT
 * @param array $options Array of the OPTION elements (as 'value'=>'Text' pairs) to be used in the
 *   checkboxes element.
 * @param array $attributes The HTML attributes of the select element.
 * @return string Formatted SELECT element
 * @see \Cake\View\Helper\FormHelper::select() for supported option formats.
 */
	public function multiCheckbox($fieldName, $options, $attributes = []) {
		$attributes += [
			'disabled' => null,
			'escape' => true,
			'hiddenField' => true,
			'secure' => true,
		];
		$attributes = $this->_initInputField($fieldName, $attributes);
		$attributes['options'] = $options;

		$hidden = '';
		if ($attributes['hiddenField']) {
			$hiddenAttributes = array(
				'name' => $attributes['name'],
				'value' => '',
				'secure' => false,
			);
			$hidden = $this->hidden($fieldName, $hiddenAttributes);
		}
		return $hidden . $this->widget('multicheckbox', $attributes);
	}

/**
 * Helper method for the various single datetime component methods.
 *
 * @param array $options The options array.
 * @param string $keep The option to not disable.
 * @return array
 */
	protected function _singleDatetime($options, $keep) {
		$off = array_diff($this->_datetimeParts, [$keep]);
		$off = array_combine(
			$off,
			array_fill(0, count($off), false)
		);
		$options = $off + $options;

		if (isset($options['value'])) {
			$options['val'] = $options['value'];
		}
		return $options;
	}

/**
 * Returns a SELECT element for days.
 *
 * ### Options:
 *
 * - `empty` - If true, the empty select option is shown. If a string,
 *   that string is displayed as the empty element.
 * - `value` The selected value of the input.
 *
 * @param string $fieldName Prefix name for the SELECT element
 * @param array $option Options & HTML attributes for the select element
 * @return string A generated day select box.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::day
 */
	public function day($fieldName = null, $options = []) {
		$options = $this->_singleDatetime($options, 'day');

		if (isset($options['val']) && $options['val'] > 0 && $options['val'] <= 31) {
			$options['val'] = [
				'year' => date('Y'),
				'month' => date('m'),
				'day' => (int)$options['val']
			];
		}
		return $this->datetime($fieldName, $options);
	}

/**
 * Returns a SELECT element for years
 *
 * ### Attributes:
 *
 * - `empty` - If true, the empty select option is shown. If a string,
 *   that string is displayed as the empty element.
 * - `orderYear` - Ordering of year values in select options.
 *   Possible values 'asc', 'desc'. Default 'desc'
 * - `value` The selected value of the input.
 * - `maxYear` The max year to appear in the select element.
 * - `minYear` The min year to appear in the select element.
 *
 * @param string $fieldName Prefix name for the SELECT element
 * @param array $options Options & attributes for the select elements.
 * @return string Completed year select input
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::year
 */
	public function year($fieldName, $options = []) {
		$options = $this->_singleDatetime($options, 'year');

		$len = isset($options['val']) ? strlen($options['val']) : 0;
		if (isset($options['val']) && $len > 0 && $len < 5) {
			$options['val'] = [
				'year' => (int)$options['val'],
				'month' => date('m'),
				'day' => date('d')
			];
		}

		return $this->datetime($fieldName, $options);
	}

/**
 * Returns a SELECT element for months.
 *
 * ### Options:
 *
 * - `monthNames` - If false, 2 digit numbers will be used instead of text.
 *   If a array, the given array will be used.
 * - `empty` - If true, the empty select option is shown. If a string,
 *   that string is displayed as the empty element.
 * - `value` The selected value of the input.
 *
 * @param string $fieldName Prefix name for the SELECT element
 * @param array $options Attributes for the select element
 * @return string A generated month select dropdown.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::month
 */
	public function month($fieldName, $options = array()) {
		$options = $this->_singleDatetime($options, 'month');

		if (isset($options['val']) && $options['val'] > 0 && $options['val'] <= 12) {
			$options['val'] = [
				'year' => date('Y'),
				'month' => (int)$options['val'],
				'day' => date('d')
			];
		}
		return $this->datetime($fieldName, $options);
	}

/**
 * Returns a SELECT element for hours.
 *
 * ### Attributes:
 *
 * - `empty` - If true, the empty select option is shown. If a string,
 *   that string is displayed as the empty element.
 * - `value` The selected value of the input.
 * - `format` Set to 12 or 24 to use 12 or 24 hour formatting. Defaults to 12.
 *
 * @param string $fieldName Prefix name for the SELECT element
 * @param array $attributes List of HTML attributes
 * @return string Completed hour select input
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::hour
 */
	public function hour($fieldName, $options = []) {
		$options += ['format' => 12];
		$options = $this->_singleDatetime($options, 'hour');

		$options['timeFormat'] = $options['format'];
		unset($options['format']);

		if (isset($options['val']) && $options['val'] > 0 && $options['val'] <= 24) {
			$options['val'] = [
				'hour' => (int)$options['val'],
				'minute' => date('i'),
			];
		}
		return $this->datetime($fieldName, $options);
	}

/**
 * Returns a SELECT element for minutes.
 *
 * ### Attributes:
 *
 * - `empty` - If true, the empty select option is shown. If a string,
 *   that string is displayed as the empty element.
 * - `value` The selected value of the input.
 * - `interval` The interval that minute options should be created at.
 * - `round` How you want the value rounded when it does not fit neatly into an
 *   interval. Accepts 'up', 'down', and null.
 *
 * @param string $fieldName Prefix name for the SELECT element
 * @param array $options Array of options.
 * @return string Completed minute select input.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::minute
 */
	public function minute($fieldName, $options = []) {
		$options = $this->_singleDatetime($options, 'minute');

		if (isset($options['val']) && $options['val'] > 0 && $options['val'] <= 60) {
			$options['val'] = [
				'hour' => date('H'),
				'minute' => (int)$options['val'],
			];
		}
		return $this->datetime($fieldName, $options);
	}

/**
 * Returns a SELECT element for AM or PM.
 *
 * ### Attributes:
 *
 * - `empty` - If true, the empty select option is shown. If a string,
 *   that string is displayed as the empty element.
 * - `value` The selected value of the input.
 *
 * @param string $fieldName Prefix name for the SELECT element
 * @param array $options Array of options
 * @return string Completed meridian select input
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::meridian
 */
	public function meridian($fieldName, $options = array()) {
		$options = $this->_singleDatetime($options, 'meridian');

		if (isset($options['val'])) {
			$options['val'] = [
				'hour' => date('H'),
				'minute' => (int)$options['val'],
			];
		}
		return $this->datetime($fieldName, $options);
	}

/**
 * Returns a set of SELECT elements for a full datetime setup: day, month and year, and then time.
 *
 * ### Options:
 *
 * - `monthNames` If false, 2 digit numbers will be used instead of text.
 *   If a array, the given array will be used.
 * - `minYear` The lowest year to use in the year select
 * - `maxYear` The maximum year to use in the year select
 * - `interval` The interval for the minutes select. Defaults to 1
 * - `empty` - If true, the empty select option is shown. If a string,
 *   that string is displayed as the empty element.
 * - `round` - Set to `up` or `down` if you want to force rounding in either direction. Defaults to null.
 * - `value` | `default` The default value to be used by the input. A value in `$this->data`
 *   matching the field name will override this value. If no default is provided `time()` will be used.
 * - `timeFormat` The time format to use, either 12 or 24.
 * - `second` Set to true to enable seconds drop down.
 *
 * To control the order of inputs, and any elements/content between the inputs you
 * can override the `dateWidget` template. By default the `dateWidget` template is:
 *
 * `{{month}}{{day}}{{year}}{{hour}}{{minute}}{{second}}{{meridian}}`
 *
 * @param string $fieldName Prefix name for the SELECT element
 * @param array $options Array of Options
 * @return string Generated set of select boxes for the date and time formats chosen.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::dateTime
 */
	public function dateTime($fieldName, $options = array()) {
		$options += [
			'empty' => true,
			'value' => null,
			'interval' => 1,
			'round' => null,
			'monthNames' => true,
			'minYear' => null,
			'maxYear' => null,
			'orderYear' => 'desc',
			'timeFormat' => 12,
			'second' => false,
		];
		$options = $this->_initInputField($fieldName, $options);
		$options = $this->_datetimeOptions($options);

		return $this->widget('datetime', $options);
	}

/**
 * Helper method for converting from FormHelper options data to widget format.
 *
 * @param array $options Options to convert.
 * @return array Converted options.
 */
	protected function _datetimeOptions($options) {
		foreach ($this->_datetimeParts as $type) {
			if (!isset($options[$type])) {
				$options[$type] = [];
			}

			// Pass empty boolean to each type.
			if (
				!empty($options['empty']) &&
				is_bool($options['empty']) &&
				is_array($options[$type])
			) {
				$options[$type]['empty'] = $options['empty'];
			}

			// Move empty options into each type array.
			if (isset($options['empty'][$type])) {
				$options[$type]['empty'] = $options['empty'][$type];
			}
		}
		unset($options['empty']);

		$hasYear = is_array($options['year']);
		if ($hasYear && isset($options['minYear'])) {
			$options['year']['start'] = $options['minYear'];
		}
		if ($hasYear && isset($options['maxYear'])) {
			$options['year']['end'] = $options['maxYear'];
		}
		if ($hasYear && isset($options['orderYear'])) {
			$options['year']['order'] = $options['orderYear'];
		}
		unset($options['minYear'], $options['maxYear'], $options['orderYear']);

		if (is_array($options['month'])) {
			$options['month']['names'] = $options['monthNames'];
		}
		unset($options['monthNames']);

		if (is_array($options['hour']) && isset($options['timeFormat'])) {
			$options['hour']['format'] = $options['timeFormat'];
		}
		unset($options['timeFormat']);

		if (is_array($options['minute'])) {
			$options['minute']['interval'] = $options['interval'];
			$options['minute']['round'] = $options['round'];
		}
		unset($options['interval'], $options['round']);

		if (!isset($options['val'])) {
			$options['val'] = new \DateTime();
		}
		return $options;
	}

/**
 * Sets field defaults and adds field to form security input hash.
 * Will also add the error class if the field contains validation errors.
 *
 * ### Options
 *
 * - `secure` - boolean whether or not the field should be added to the security fields.
 *   Disabling the field using the `disabled` option, will also omit the field from being
 *   part of the hashed key.
 * - `default` - mixed - The value to use if there is no value in the form's context.
 * - `disabled` - mixed - Either a boolean indicating disabled state, or the string in
 *   a numerically indexed value.
 *
 * This method will convert a numerically indexed 'disabled' into an associative
 * array value. FormHelper's internals expect associative options.
 *
 * The output of this function is a more complete set of input attributes that
 * can be passed to a form widget to generate the actual input.
 *
 * @param string $field Name of the field to initialize options for.
 * @param array $options Array of options to append options into.
 * @return array Array of options for the input.
 */
	protected function _initInputField($field, $options = []) {
		$secure = !empty($this->request->params['_Token']);
		if (isset($options['secure'])) {
			$secure = $options['secure'];
			unset($options['secure']);
		}
		$context = $this->_getContext();

		$disabledIndex = array_search('disabled', $options, true);
		if (is_int($disabledIndex)) {
			unset($options[$disabledIndex]);
			$options['disabled'] = true;
		}

		if (!isset($options['name'])) {
			$parts = explode('.', $field);
			$first = array_shift($parts);
			$options['name'] = $first . ($parts ? '[' . implode('][', $parts) . ']' : '');
		}

		if (isset($options['value']) && !isset($options['val'])) {
			$options['val'] = $options['value'];
			unset($options['value']);
		}
		if (!isset($options['val'])) {
			$options['val'] = $context->val($field);
		}
		if (!isset($options['val']) && isset($options['default'])) {
			$options['val'] = $options['default'];
		}
		unset($options['value'], $options['default']);

		if ($context->hasError($field)) {
			$options = $this->addClass($options, $this->settings['errorClass']);
		}
		if (!empty($options['disabled']) || $secure === static::SECURE_SKIP) {
			return $options;
		}

		if (!isset($options['required']) && $context->isRequired($field)) {
			$options['required'] = true;
		}

		if ($secure === self::SECURE_SKIP) {
			return $options;
		}

		$this->_secure($secure, $this->_secureFieldName($options));
		return $options;
	}

/**
 * Get the field name for use with _secure().
 *
 * Parses the name attribute to create a dot separated name value for use
 * in secured field hash. If filename is of form Model[field] an array of
 * fieldname parts like ['Model', 'field'] is returned.
 *
 * @param array $options An array of options possibly containing a name key.
 * @return string|array|null Dot separated string like Foo.bar, array of filename
 *   params like ['Model', 'field'] or null if options does not contain name.
 */
	protected function _secureFieldName($options) {
		if (!isset($options['name'])) {
			return null;
		}
		if (strpos($options['name'], '[') === false) {
			return [$options['name']];
		}
		$parts = explode('[', $options['name']);
		$parts = array_map(function($el) {
			return trim($el, ']');
		}, $parts);
		return $parts;
	}

/**
 * Add a new context type.
 *
 * Form context types allow FormHelper to interact with
 * data providers that come from outside CakePHP. For example
 * if you wanted to use an alternative ORM like Doctrine you could
 * create and connect a new context class to allow FormHelper to
 * read metadata from doctrine.
 *
 * @param string $type The type of context. This key
 *   can be used to overwrite existing providers.
 * @param callable $check A callable that returns a object
 *   when the form context is the correct type.
 * @return void
 */
	public function addContextProvider($name, callable $check) {
		$this->_contextProviders[$name] = $check;
	}

/**
 * Get the context instance for the current form set.
 *
 * If there is no active form null will be returned.
 *
 * @return null|\Cake\View\Form\ContextInterface The context for the form.
 */
	public function context() {
		return $this->_getContext();
	}

/**
 * Find the matching context provider for the data.
 *
 * If no type can be matched a NullContext will be returned.
 *
 * @param mixed $data The data to get a context provider for.
 * @return mixed Context provider.
 * @throws \RuntimeException when the context class does not implement the
 *   ContextInterface.
 */
	protected function _getContext($data = []) {
		if (isset($this->_context) && empty($data)) {
			return $this->_context;
		}
		$data += ['entity' => null];

		foreach ($this->_contextProviders as $key => $check) {
			$context = $check($this->request, $data);
			if ($context) {
				break;
			}
		}
		if (!isset($context)) {
			$context = new NullContext($this->request, $data);
		}
		if (!($context instanceof ContextInterface)) {
			throw new \RuntimeException(
				'Context objects must implement Cake\View\Form\ContextInterface'
			);
		}
		return $this->_context = $context;
	}

/**
 * Add a new widget to FormHelper.
 *
 * Allows you to add or replace widget instances with custom code.
 *
 * @param string $name The name of the widget. e.g. 'text'.
 * @param array|WidgetInterface Either a string class name or an object
 *    implementing the WidgetInterface.
 * @return void
 */
	public function addWidget($name, $spec) {
		$this->_registry->add([$name => $spec]);
	}

/**
 * Render a named widget.
 *
 * This is a lower level method. For built-in widgets, you should be using
 * methods like `text`, `hidden`, and `radio`. If you are using additional
 * widgets you should use this method render the widget without the label
 * or wrapping div.
 *
 * @param string $name The name of the widget. e.g. 'text'.
 * @param array $attrs The attributes for rendering the input.
 * @return void
 */
	public function widget($name, array $data = []) {
		return $this->_registry->get($name)->render($data);
	}

}
