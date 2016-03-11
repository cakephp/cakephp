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
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Datasource\EntityInterface;
use Cake\Form\Form;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\View\Form\ArrayContext;
use Cake\View\Form\ContextInterface;
use Cake\View\Form\EntityContext;
use Cake\View\Form\FormContext;
use Cake\View\Form\NullContext;
use Cake\View\Helper;
use Cake\View\StringTemplateTrait;
use Cake\View\View;
use Cake\View\Widget\WidgetRegistry;
use DateTime;
use RuntimeException;
use Traversable;

/**
 * Form helper library.
 *
 * Automatic generation of HTML FORMs from given data.
 *
 * @property HtmlHelper $Html
 * @property UrlHelper $Url
 * @link http://book.cakephp.org/3.0/en/views/helpers/form.html
 */
class FormHelper extends Helper
{

    use IdGeneratorTrait;
    use SecureFieldTokenTrait;
    use StringTemplateTrait;

    /**
     * Other helpers used by FormHelper
     *
     * @var array
     */
    public $helpers = ['Url', 'Html'];

    /**
     * The various pickers that make up a datetime picker.
     *
     * @var array
     */
    protected $_datetimeParts = ['year', 'month', 'day', 'hour', 'minute', 'second', 'meridian'];

    /**
     * Special options used for datetime inputs.
     *
     * @var array
     */
    protected $_datetimeOptions = [
        'interval', 'round', 'monthNames', 'minYear', 'maxYear',
        'orderYear', 'timeFormat', 'second'
    ];

    /**
     * Default config for the helper.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'idPrefix' => null,
        'errorClass' => 'form-error',
        'typeMap' => [
            'string' => 'text', 'datetime' => 'datetime', 'boolean' => 'checkbox',
            'timestamp' => 'datetime', 'text' => 'textarea', 'time' => 'time',
            'date' => 'date', 'float' => 'number', 'integer' => 'number',
            'decimal' => 'number', 'binary' => 'file', 'uuid' => 'string'
        ],
        'templates' => [
            'button' => '<button{{attrs}}>{{text}}</button>',
            'checkbox' => '<input type="checkbox" name="{{name}}" value="{{value}}"{{attrs}}>',
            'checkboxFormGroup' => '{{label}}',
            'checkboxWrapper' => '<div class="checkbox">{{label}}</div>',
            'dateWidget' => '{{year}}{{month}}{{day}}{{hour}}{{minute}}{{second}}{{meridian}}',
            'error' => '<div class="error-message">{{content}}</div>',
            'errorList' => '<ul>{{content}}</ul>',
            'errorItem' => '<li>{{text}}</li>',
            'file' => '<input type="file" name="{{name}}"{{attrs}}>',
            'fieldset' => '<fieldset{{attrs}}>{{content}}</fieldset>',
            'formStart' => '<form{{attrs}}>',
            'formEnd' => '</form>',
            'formGroup' => '{{label}}{{input}}',
            'hiddenBlock' => '<div style="display:none;">{{content}}</div>',
            'input' => '<input type="{{type}}" name="{{name}}"{{attrs}}/>',
            'inputSubmit' => '<input type="{{type}}"{{attrs}}/>',
            'inputContainer' => '<div class="input {{type}}{{required}}">{{content}}</div>',
            'inputContainerError' => '<div class="input {{type}}{{required}} error">{{content}}{{error}}</div>',
            'label' => '<label{{attrs}}>{{text}}</label>',
            'nestingLabel' => '{{hidden}}<label{{attrs}}>{{input}}{{text}}</label>',
            'legend' => '<legend>{{text}}</legend>',
            'multicheckboxTitle' => '<legend>{{text}}</legend>',
            'multicheckboxWrapper' => '<fieldset{{attrs}}>{{content}}</fieldset>',
            'option' => '<option value="{{value}}"{{attrs}}>{{text}}</option>',
            'optgroup' => '<optgroup label="{{label}}"{{attrs}}>{{content}}</optgroup>',
            'select' => '<select name="{{name}}"{{attrs}}>{{content}}</select>',
            'selectMultiple' => '<select name="{{name}}[]" multiple="multiple"{{attrs}}>{{content}}</select>',
            'radio' => '<input type="radio" name="{{name}}" value="{{value}}"{{attrs}}>',
            'radioWrapper' => '{{label}}',
            'textarea' => '<textarea name="{{name}}"{{attrs}}>{{value}}</textarea>',
            'submitContainer' => '<div class="submit">{{content}}</div>',
        ]
    ];

    /**
     * Default widgets
     *
     * @var array
     */
    protected $_defaultWidgets = [
        'button' => ['Button'],
        'checkbox' => ['Checkbox'],
        'file' => ['File'],
        'label' => ['Label'],
        'nestingLabel' => ['NestingLabel'],
        'multicheckbox' => ['MultiCheckbox', 'nestingLabel'],
        'radio' => ['Radio', 'nestingLabel'],
        'select' => ['SelectBox'],
        'textarea' => ['Textarea'],
        'datetime' => ['DateTime', 'select'],
        '_default' => ['Basic'],
    ];

    /**
     * List of fields created, used with secure forms.
     *
     * @var array
     */
    public $fields = [];

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
     * @see \Cake\View\Helper\FormHelper::_secure()
     * @see \Cake\Controller\Component\SecurityComponent::validatePost()
     * @var array
     */
    protected $_unlockedFields = [];

    /**
     * Registry for input widgets.
     *
     * @var \Cake\View\Widget\WidgetRegistry
     */
    protected $_registry;

    /**
     * Context for the current form.
     *
     * @var \Cake\View\Form\ContextInterface
     */
    protected $_context;

    /**
     * Context provider methods.
     *
     * @var array
     * @see \Cake\View\Helper\FormHelper::addContextProvider()
     */
    protected $_contextProviders = [];

    /**
     * The action attribute value of the last created form.
     * Used to make form/request specific hashes for SecurityComponent.
     *
     * @var string
     */
    protected $_lastAction = '';

    /**
     * Construct the widgets and binds the default context providers
     *
     * @param \Cake\View\View $View The View this helper is being attached to.
     * @param array $config Configuration settings for the helper.
     */
    public function __construct(View $View, array $config = [])
    {
        $registry = null;
        $widgets = $this->_defaultWidgets;
        if (isset($config['registry'])) {
            $registry = $config['registry'];
            unset($config['registry']);
        }
        if (isset($config['widgets'])) {
            if (is_string($config['widgets'])) {
                $config['widgets'] = (array)$config['widgets'];
            }
            $widgets = $config['widgets'] + $widgets;
            unset($config['widgets']);
        }

        parent::__construct($View, $config);

        $this->widgetRegistry($registry, $widgets);
        $this->_addDefaultContextProviders();
        $this->_idPrefix = $this->config('idPrefix');
    }

    /**
     * Set the widget registry the helper will use.
     *
     * @param \Cake\View\Widget\WidgetRegistry|null $instance The registry instance to set.
     * @param array $widgets An array of widgets
     * @return \Cake\View\Widget\WidgetRegistry
     */
    public function widgetRegistry(WidgetRegistry $instance = null, $widgets = [])
    {
        if ($instance === null) {
            if ($this->_registry === null) {
                $this->_registry = new WidgetRegistry($this->templater(), $this->_View, $widgets);
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
    protected function _addDefaultContextProviders()
    {
        $this->addContextProvider('orm', function ($request, $data) {
            if (is_array($data['entity']) || $data['entity'] instanceof Traversable) {
                $pass = (new Collection($data['entity']))->first() !== null;
                if ($pass) {
                    return new EntityContext($request, $data);
                }
            }
            if ($data['entity'] instanceof EntityInterface) {
                return new EntityContext($request, $data);
            }
            if (is_array($data['entity']) && empty($data['entity']['schema'])) {
                return new EntityContext($request, $data);
            }
        });

        $this->addContextProvider('form', function ($request, $data) {
            if ($data['entity'] instanceof Form) {
                return new FormContext($request, $data);
            }
        });

        $this->addContextProvider('array', function ($request, $data) {
            if (is_array($data['entity']) && isset($data['entity']['schema'])) {
                return new ArrayContext($request, $data['entity']);
            }
        });
    }

    /**
     * Returns if a field is required to be filled based on validation properties from the validating object.
     *
     * @param \Cake\Validation\ValidationSet $validationRules Validation rules set.
     * @return bool true if field is required to be filled, false otherwise
     */
    protected function _isRequiredField($validationRules)
    {
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
     *   don't need to change the controller from the current request's controller. Deprecated since 3.2, use `url`.
     * - `url` The URL the form submits to. Can be a string or a URL array. If you use 'url'
     *    you should leave 'action' undefined.
     * - `encoding` Set the accept-charset encoding for the form. Defaults to `Configure::read('App.encoding')`
     * - `templates` The templates you want to use for this form. Any templates will be merged on top of
     *   the already loaded templates. This option can either be a filename in /config that contains
     *   the templates you want to load, or an array of templates to use.
     * - `context` Additional options for the context class. For example the EntityContext accepts a 'table'
     *   option that allows you to set the specific Table class the form should be based on.
     * - `idPrefix` Prefix for generated ID attributes.
     *
     * @param mixed $model The context for which the form is being defined. Can
     *   be an ORM entity, ORM resultset, or an array of meta data. You can use false or null
     *   to make a model-less form.
     * @param array $options An array of html attributes and options.
     * @return string An formatted opening FORM tag.
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#Cake\View\Helper\FormHelper::create
     */
    public function create($model = null, array $options = [])
    {
        $append = '';

        if (empty($options['context'])) {
            $options['context'] = [];
        }
        $options['context']['entity'] = $model;
        $context = $this->_getContext($options['context']);
        unset($options['context']);

        $isCreate = $context->isCreate();

        $options += [
            'type' => $isCreate ? 'post' : 'put',
            'action' => null,
            'url' => null,
            'encoding' => strtolower(Configure::read('App.encoding')),
            'templates' => null,
            'idPrefix' => null,
        ];

        if (isset($options['action'])) {
            trigger_error('Using key `action` is deprecated, use `url` directly instead.', E_USER_DEPRECATED);
        }

        if ($options['idPrefix'] !== null) {
            $this->_idPrefix = $options['idPrefix'];
        }
        $templater = $this->templater();

        if (!empty($options['templates'])) {
            $templater->push();
            $method = is_string($options['templates']) ? 'load' : 'add';
            $templater->{$method}($options['templates']);
        }
        unset($options['templates']);

        if ($options['action'] === false || $options['url'] === false) {
            $url = $this->request->here(false);
            $action = null;
        } else {
            $url = $this->_formUrl($context, $options);
            $action = $this->Url->build($url);
        }

        $this->_lastAction($url);
        unset($options['url'], $options['action'], $options['idPrefix']);

        $htmlAttributes = [];
        switch (strtolower($options['type'])) {
            case 'get':
                $htmlAttributes['method'] = 'get';
                break;
            // Set enctype for form
            case 'file':
                $htmlAttributes['enctype'] = 'multipart/form-data';
                $options['type'] = ($isCreate) ? 'post' : 'put';
            // Move on
            case 'post':
            // Move on
            case 'put':
            // Move on
            case 'delete':
            // Set patch method
            case 'patch':
                $append .= $this->hidden('_method', [
                    'name' => '_method',
                    'value' => strtoupper($options['type']),
                    'secure' => static::SECURE_SKIP
                ]);
            // Default to post method
            default:
                $htmlAttributes['method'] = 'post';
        }
        $this->requestType = strtolower($options['type']);

        if (!empty($options['encoding'])) {
            $htmlAttributes['accept-charset'] = $options['encoding'];
        }
        unset($options['type'], $options['encoding']);

        $htmlAttributes += $options;

        $this->fields = [];
        if ($this->requestType !== 'get') {
            $append .= $this->_csrfField();
        }

        if (!empty($append)) {
            $append = $templater->format('hiddenBlock', ['content' => $append]);
        }

        $actionAttr = $templater->formatAttributes(['action' => $action, 'escape' => false]);
        return $this->formatTemplate('formStart', [
            'attrs' => $templater->formatAttributes($htmlAttributes) . $actionAttr
        ]) . $append;
    }

    /**
     * Create the URL for a form based on the options.
     *
     * @param \Cake\View\Form\ContextInterface $context The context object to use.
     * @param array $options An array of options from create()
     * @return string The action attribute for the form.
     */
    protected function _formUrl($context, $options)
    {
        if ($options['action'] === null && $options['url'] === null) {
            return $this->request->here(false);
        }

        if (is_string($options['url']) ||
            (is_array($options['url']) && isset($options['url']['_name']))
        ) {
            return $options['url'];
        }

        if (isset($options['action']) && empty($options['url']['action'])) {
            $options['url']['action'] = $options['action'];
        }

        $actionDefaults = [
            'plugin' => $this->plugin,
            'controller' => $this->request->params['controller'],
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

    /**
     * Correctly store the last created form action URL.
     *
     * @param string|array $url The URL of the last form.
     * @return void
     */
    protected function _lastAction($url)
    {
        $action = Router::url($url, true);
        $query = parse_url($action, PHP_URL_QUERY);
        $query = $query ? '?' . $query : '';
        $this->_lastAction = parse_url($action, PHP_URL_PATH) . $query;
    }

    /**
     * Return a CSRF input if the request data is present.
     * Used to secure forms in conjunction with CsrfComponent &
     * SecurityComponent
     *
     * @return string
     */
    protected function _csrfField()
    {
        if (!empty($this->request['_Token']['unlockedFields'])) {
            foreach ((array)$this->request['_Token']['unlockedFields'] as $unlocked) {
                $this->_unlockedFields[] = $unlocked;
            }
        }
        if (empty($this->request->params['_csrfToken'])) {
            return '';
        }
        return $this->hidden('_csrfToken', [
            'value' => $this->request->params['_csrfToken'],
            'secure' => static::SECURE_SKIP
        ]);
    }

    /**
     * Closes an HTML form, cleans up values set by FormHelper::create(), and writes hidden
     * input fields where appropriate.
     *
     * @param array $secureAttributes Secure attributes which will be passed as HTML attributes
     *   into the hidden input elements generated for the Security Component.
     * @return string A closing FORM tag.
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#closing-the-form
     */
    public function end(array $secureAttributes = [])
    {
        $out = '';

        if ($this->requestType !== 'get' &&
            !empty($this->request['_Token'])
        ) {
            $out .= $this->secure($this->fields, $secureAttributes);
            $this->fields = [];
        }
        $out .= $this->formatTemplate('formEnd', []);

        $this->templater()->pop();
        $this->requestType = null;
        $this->_context = null;
        $this->_idPrefix = $this->config('idPrefix');
        return $out;
    }

    /**
     * Generates a hidden field with a security hash based on the fields used in
     * the form.
     *
     * If $secureAttributes is set, these HTML attributes will be merged into
     * the hidden input tags generated for the Security Component. This is
     * especially useful to set HTML5 attributes like 'form'.
     *
     * @param array $fields If set specifies the list of fields to use when
     *    generating the hash, else $this->fields is being used.
     * @param array $secureAttributes will be passed as HTML attributes into the hidden
     *    input elements generated for the Security Component.
     * @return string|null A hidden input field with a security hash
     */
    public function secure(array $fields = [], array $secureAttributes = [])
    {
        if (empty($this->request['_Token'])) {
            return null;
        }
        $debugSecurity = Hash::get($secureAttributes, 'debugSecurity') ?: Configure::read('debug');
        unset($secureAttributes['debugSecurity']);
        
        $tokenData = $this->_buildFieldToken(
            $this->_lastAction,
            $fields,
            $this->_unlockedFields
        );
        $tokenFields = array_merge($secureAttributes, [
            'value' => $tokenData['fields'],
        ]);
        $out = $this->hidden('_Token.fields', $tokenFields);
        $tokenUnlocked = array_merge($secureAttributes, [
            'value' => $tokenData['unlocked'],
        ]);
        $out .= $this->hidden('_Token.unlocked', $tokenUnlocked);
        if ($debugSecurity) {
            $tokenDebug = array_merge($secureAttributes, [
                'value' => urlencode(json_encode([
                    $this->_lastAction,
                    $fields,
                    $this->_unlockedFields
                ])),
            ]);
            $out .= $this->hidden('_Token.debug', $tokenDebug);
        }
        return $this->formatTemplate('hiddenBlock', ['content' => $out]);
    }

    /**
     * Add to or get the list of fields that are currently unlocked.
     * Unlocked fields are not included in the field hash used by SecurityComponent
     * unlocking a field once its been added to the list of secured fields will remove
     * it from the list of fields.
     *
     * @param string|null $name The dot separated name for the field.
     * @return array|null Either null, or the list of fields.
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#working-with-securitycomponent
     */
    public function unlockField($name = null)
    {
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
     * @param bool $lock Whether this field should be part of the validation
     *   or excluded as part of the unlockedFields.
     * @param string|array $field Reference to field to be secured. Can be dot
     *   separated string to indicate nesting or array of fieldname parts.
     * @param mixed $value Field value, if value should not be tampered with.
     * @return void
     */
    protected function _secure($lock, $field, $value = null)
    {
        if (empty($field) && $field !== '0') {
            return;
        }

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
                if (isset($this->fields[$field]) && $value === null) {
                    unset($this->fields[$field]);
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
     * @param string $field This should be "modelname.fieldname"
     * @return bool If there are errors this method returns true, else false.
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#displaying-and-checking-errors
     */
    public function isFieldError($field)
    {
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
     * @param string $field A field name, like "modelname.fieldname"
     * @param string|array|null $text Error message as string or array of messages. If an array,
     *   it should be a hash of key names => messages.
     * @param array $options See above.
     * @return string Formatted errors or ''.
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#displaying-and-checking-errors
     */
    public function error($field, $text = null, array $options = [])
    {
        if (substr($field, -5) === '._ids') {
            $field = substr($field, 0, -5);
        }
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
     * ```
     * echo $this->Form->label('published');
     * <label for="PostPublished">Published</label>
     * ```
     *
     * Custom text:
     *
     * ```
     * echo $this->Form->label('published', 'Publish');
     * <label for="published">Publish</label>
     * ```
     *
     * Custom attributes:
     *
     * ```
     * echo $this->Form->label('published', 'Publish', [
     *   'for' => 'post-publish'
     * ]);
     * <label for="post-publish">Publish</label>
     * ```
     *
     * Nesting an input tag:
     *
     * ```
     * echo $this->Form->label('published', 'Publish', [
     *   'for' => 'published',
     *   'input' => $this->text('published'),
     * ]);
     * <label for="post-publish">Publish <input type="text" name="published"></label>
     * ```
     *
     * If you want to nest inputs in the labels, you will need to modify the default templates.
     *
     * @param string $fieldName This should be "modelname.fieldname"
     * @param string|null $text Text that will appear in the label field. If
     *   $text is left undefined the text will be inflected from the
     *   fieldName.
     * @param array $options An array of HTML attributes.
     * @return string The formatted LABEL element
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-labels
     */
    public function label($fieldName, $text = null, array $options = [])
    {
        if ($text === null) {
            $text = $fieldName;
            if (substr($text, -5) === '._ids') {
                $text = substr($text, 0, -5);
            }
            if (strpos($text, '.') !== false) {
                $fieldElements = explode('.', $text);
                $text = array_pop($fieldElements);
            }
            if (substr($text, -3) === '_id') {
                $text = substr($text, 0, -3);
            }
            $text = __(Inflector::humanize(Inflector::underscore($text)));
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
        if (isset($options['input'])) {
            if (is_array($options['input'])) {
                $attrs = $options['input'] + $attrs;
            }
            return $this->widget('nestingLabel', $attrs);
        }
        return $this->widget('label', $attrs);
    }

    /**
     * Generate a set of inputs for `$fields`. If $fields is empty the fields of current model
     * will be used.
     *
     * You can customize individual inputs through `$fields`.
     * ```
     * $this->Form->allInputs([
     *   'name' => ['label' => 'custom label']
     * ]);
     * ```
     *
     * You can exclude fields by specifying them as false:
     *
     * ```
     * $this->Form->allInputs(['title' => false]);
     * ```
     *
     * In the above example, no field would be generated for the title field.
     *
     * @param array $fields An array of customizations for the fields that will be
     *   generated. This array allows you to set custom types, labels, or other options.
     * @param array $options Options array. Valid keys are:
     * - `fieldset` Set to false to disable the fieldset. You can also pass an array of params to be
     *    applied as HTML attributes to the fieldset tag. If you pass an empty array, the fieldset will
     *    be enabled
     * - `legend` Set to false to disable the legend for the generated input set. Or supply a string
     *    to customize the legend text.
     * @return string Completed form inputs.
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#generating-entire-forms
     */
    public function allInputs(array $fields = [], array $options = [])
    {
        $context = $this->_getContext();

        $modelFields = $context->fieldNames();

        $fields = array_merge(
            Hash::normalize($modelFields),
            Hash::normalize($fields)
        );

        return $this->inputs($fields, $options);
    }

    /**
     * Generate a set of inputs for `$fields` wrapped in a fieldset element.
     *
     * You can customize individual inputs through `$fields`.
     * ```
     * $this->Form->inputs([
     *   'name' => ['label' => 'custom label'],
     *   'email'
     * ]);
     * ```
     *
     * @param array $fields An array of the fields to generate. This array allows you to set custom
     *   types, labels, or other options.
     * @param array $options Options array. Valid keys are:
     * - `fieldset` Set to false to disable the fieldset. You can also pass an array of params to be
     *    applied as HTML attributes to the fieldset tag. If you pass an empty array, the fieldset will
     *    be enabled
     * - `legend` Set to false to disable the legend for the generated input set. Or supply a string
     *    to customize the legend text.
     * @return string Completed form inputs.
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#generating-entire-forms
     */
    public function inputs(array $fields, array $options = [])
    {
        $fields = Hash::normalize($fields);

        $out = '';
        foreach ($fields as $name => $opts) {
            if ($opts === false) {
                continue;
            }

            $out .= $this->input($name, (array)$opts);
        }

        return $this->fieldset($out, $options);
    }

    /**
     * Wrap a set of inputs in a fieldset
     *
     * @param string $fields the form inputs to wrap in a fieldset
     * @param array $options Options array. Valid keys are:
     * - `fieldset` Set to false to disable the fieldset. You can also pass an array of params to be
     *    applied as HTML attributes to the fieldset tag. If you pass an empty array, the fieldset will
     *    be enabled
     * - `legend` Set to false to disable the legend for the generated input set. Or supply a string
     *    to customize the legend text.
     * @return string Completed form inputs.
     */
    public function fieldset($fields = '', array $options = [])
    {
        $fieldset = $legend = true;
        $context = $this->_getContext();
        $out = $fields;

        if (isset($options['legend'])) {
            $legend = $options['legend'];
        }
        if (isset($options['fieldset'])) {
            $fieldset = $options['fieldset'];
        }

        if ($legend === true) {
            $actionName = __d('cake', 'New %s');
            $isCreate = $context->isCreate();
            if (!$isCreate) {
                $actionName = __d('cake', 'Edit %s');
            }
            $modelName = Inflector::humanize(Inflector::singularize($this->request->params['controller']));
            $legend = sprintf($actionName, $modelName);
        }

        if ($fieldset !== false) {
            if ($legend) {
                $out = $this->formatTemplate('legend', ['text' => $legend]) . $out;
            }

            $fieldsetParams = ['content' => $out, 'attrs' => ''];
            if (is_array($fieldset) && !empty($fieldset)) {
                $fieldsetParams['attrs'] = $this->templater()->formatAttributes($fieldset);
            }
            $out = $this->formatTemplate('fieldset', $fieldsetParams);
        }
        return $out;
    }

    /**
     * Generates a form input element complete with label and wrapper div
     *
     * ### Options
     *
     * See each field type method for more information. Any options that are part of
     * $attributes or $options for the different **type** methods can be included in `$options` for input().
     * Additionally, any unknown keys that are not in the list below, or part of the selected type's options
     * will be treated as a regular HTML attribute for the generated input.
     *
     * - `type` - Force the type of widget you want. e.g. `type => 'select'`
     * - `label` - Either a string label, or an array of options for the label. See FormHelper::label().
     * - `options` - For widgets that take options e.g. radio, select.
     * - `error` - Control the error message that is produced. Set to `false` to disable any kind of error reporting (field
     *    error and error messages).
     * - `empty` - String or boolean to enable empty select box options.
     * - `nestedInput` - Used with checkbox and radio inputs. Set to false to render inputs outside of label
     *   elements. Can be set to true on any input to force the input inside the label. If you
     *   enable this option for radio buttons you will also need to modify the default `radioWrapper` template.
     * - `templates` - The templates you want to use for this input. Any templates will be merged on top of
     *   the already loaded templates. This option can either be a filename in /config that contains
     *   the templates you want to load, or an array of templates to use.
     *
     * @param string $fieldName This should be "modelname.fieldname"
     * @param array $options Each type of input takes different options.
     * @return string Completed form widget.
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-form-inputs
     */
    public function input($fieldName, array $options = [])
    {
        $options += [
            'type' => null,
            'label' => null,
            'error' => null,
            'required' => null,
            'options' => null,
            'templates' => [],
            'templateVars' => []
        ];
        $options = $this->_parseOptions($fieldName, $options);
        $options += ['id' => $this->_domId($fieldName)];

        $templater = $this->templater();
        $newTemplates = $options['templates'];

        if ($newTemplates) {
            $templater->push();
            $templateMethod = is_string($options['templates']) ? 'load' : 'add';
            $templater->{$templateMethod}($options['templates']);
        }
        unset($options['templates']);

        $error = null;
        $errorSuffix = '';
        if ($options['type'] !== 'hidden' && $options['error'] !== false) {
            $error = $this->error($fieldName, $options['error']);
            $errorSuffix = empty($error) ? '' : 'Error';
            unset($options['error']);
        }

        $label = $options['label'];
        unset($options['label']);

        $nestedInput = false;
        if ($options['type'] === 'checkbox') {
            $nestedInput = true;
        }
        $nestedInput = isset($options['nestedInput']) ? $options['nestedInput'] : $nestedInput;

        if ($nestedInput === true && $options['type'] === 'checkbox' && !array_key_exists('hiddenField', $options) && $label !== false) {
            $options['hiddenField'] = '_split';
        }

        $input = $this->_getInput($fieldName, $options);
        if ($options['type'] === 'hidden' || $options['type'] === 'submit') {
            if ($newTemplates) {
                $templater->pop();
            }
            return $input;
        }

        $label = $this->_getLabel($fieldName, compact('input', 'label', 'error', 'nestedInput') + $options);
        $result = $this->_groupTemplate(compact('input', 'label', 'error', 'options'));
        $result = $this->_inputContainerTemplate([
            'content' => $result,
            'error' => $error,
            'errorSuffix' => $errorSuffix,
            'options' => $options
        ]);

        if ($newTemplates) {
            $templater->pop();
        }

        return $result;
    }

    /**
     * Generates an group template element
     *
     * @param array $options The options for group template
     * @return string The generated group template
     */
    protected function _groupTemplate($options)
    {
        $groupTemplate = $options['options']['type'] . 'FormGroup';
        if (!$this->templater()->get($groupTemplate)) {
            $groupTemplate = 'formGroup';
        }
        return $this->formatTemplate($groupTemplate, [
            'input' => $options['input'],
            'label' => $options['label'],
            'error' => $options['error'],
            'templateVars' => isset($options['options']['templateVars']) ? $options['options']['templateVars'] : []
        ]);
    }

    /**
     * Generates an input container template
     *
     * @param array $options The options for input container template
     * @return string The generated input container template
     */
    protected function _inputContainerTemplate($options)
    {
        $inputContainerTemplate = $options['options']['type'] . 'Container' . $options['errorSuffix'];
        if (!$this->templater()->get($inputContainerTemplate)) {
            $inputContainerTemplate = 'inputContainer' . $options['errorSuffix'];
        }

        return $this->formatTemplate($inputContainerTemplate, [
            'content' => $options['content'],
            'error' => $options['error'],
            'required' => $options['options']['required'] ? ' required' : '',
            'type' => $options['options']['type'],
            'templateVars' => isset($options['options']['templateVars']) ? $options['options']['templateVars'] : []
        ]);
    }

    /**
     * Generates an input element
     *
     * @param string $fieldName the field name
     * @param array $options The options for the input element
     * @return string The generated input element
     */
    protected function _getInput($fieldName, $options)
    {
        switch ($options['type']) {
            case 'select':
                $opts = $options['options'];
                unset($options['options']);
                return $this->select($fieldName, $opts, $options);
            case 'radio':
                $opts = $options['options'];
                unset($options['options']);
                return $this->radio($fieldName, $opts, $options);
            case 'multicheckbox':
                $opts = $options['options'];
                unset($options['options']);
                return $this->multicheckbox($fieldName, $opts, $options);
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
     * @param string $fieldName The name of the field to parse options for.
     * @param array $options Options list.
     * @return array Options
     */
    protected function _parseOptions($fieldName, $options)
    {
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
     * variables that can be found in the view template
     *
     * @param string $fieldName the name of the field to guess a type for
     * @param array $options the options passed to the input method
     * @return string
     */
    protected function _inputType($fieldName, $options)
    {
        $context = $this->_getContext();

        if ($context->isPrimaryKey($fieldName)) {
            return 'hidden';
        }

        if (substr($fieldName, -3) === '_id') {
            return 'select';
        }

        $internalType = $context->type($fieldName);
        $map = $this->_config['typeMap'];
        $type = isset($map[$internalType]) ? $map[$internalType] : 'text';
        $fieldName = array_slice(explode('.', $fieldName), -1)[0];

        switch (true) {
            case isset($options['checked']):
                return 'checkbox';
            case isset($options['options']):
                return 'select';
            case in_array($fieldName, ['passwd', 'password']):
                return 'password';
            case in_array($fieldName, ['tel', 'telephone', 'phone']):
                return 'tel';
            case $fieldName === 'email':
                return 'email';
            case isset($options['rows']) || isset($options['cols']):
                return 'textarea';
        }

        return $type;
    }

    /**
     * Selects the variable containing the options for a select field if present,
     * and sets the value to the 'options' key in the options array.
     *
     * @param string $fieldName The name of the field to find options for.
     * @param array $options Options list.
     * @return array
     */
    protected function _optionsOptions($fieldName, $options)
    {
        if (isset($options['options'])) {
            return $options;
        }

        $pluralize = true;
        if (substr($fieldName, -5) === '._ids') {
            $fieldName = substr($fieldName, 0, -5);
            $pluralize = false;
        } elseif (substr($fieldName, -3) === '_id') {
            $fieldName = substr($fieldName, 0, -3);
        }
        $fieldName = array_slice(explode('.', $fieldName), -1)[0];

        $varName = Inflector::variable(
            $pluralize ? Inflector::pluralize($fieldName) : $fieldName
        );
        $varOptions = $this->_View->get($varName);
        if (!is_array($varOptions) && !($varOptions instanceof Traversable)) {
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
     * @param string $fieldName The name of the field to generate options for.
     * @param array $options Options list.
     * @param bool $allowOverride Whether or not it is allowed for this method to
     * overwrite the 'type' key in options.
     * @return array
     */
    protected function _magicOptions($fieldName, $options, $allowOverride)
    {
        $context = $this->_getContext();

        if (!isset($options['required']) && $options['type'] !== 'hidden') {
            $options['required'] = $context->isRequired($fieldName);
        }

        $type = $context->type($fieldName);
        $fieldDef = $context->attributes($fieldName);

        if ($options['type'] === 'number' && !isset($options['step'])) {
            if ($type === 'decimal' && isset($fieldDef['precision'])) {
                $decimalPlaces = $fieldDef['precision'];
                $options['step'] = sprintf('%.' . $decimalPlaces . 'F', pow(10, -1 * $decimalPlaces));
            } elseif ($type === 'float') {
                $options['step'] = 'any';
            }
        }

        $typesWithOptions = ['text', 'number', 'radio', 'select'];
        $magicOptions = (in_array($options['type'], ['radio', 'select']) || $allowOverride);
        if ($magicOptions && in_array($options['type'], $typesWithOptions)) {
            $options = $this->_optionsOptions($fieldName, $options);
        }

        if ($allowOverride && substr($fieldName, -5) === '._ids') {
            $options['type'] = 'select';
            if (empty($options['multiple'])) {
                $options['multiple'] = true;
            }
        }

        if ($options['type'] === 'select' && array_key_exists('step', $options)) {
            unset($options['step']);
        }

        $autoLength = !array_key_exists('maxlength', $options)
            && !empty($fieldDef['length'])
            && $options['type'] !== 'select';

        $allowedTypes = ['text', 'textarea', 'email', 'tel', 'url', 'search'];
        if ($autoLength && in_array($options['type'], $allowedTypes)) {
            $options['maxlength'] = min($fieldDef['length'], 100000);
        }

        if (in_array($options['type'], ['datetime', 'date', 'time', 'select'])) {
            $options += ['empty' => false];
        }

        return $options;
    }

    /**
     * Generate label for input
     *
     * @param string $fieldName The name of the field to generate label for.
     * @param array $options Options list.
     * @return bool|string false or Generated label element
     */
    protected function _getLabel($fieldName, $options)
    {
        if ($options['type'] === 'hidden') {
            return false;
        }

        $label = null;
        if (isset($options['label'])) {
            $label = $options['label'];
        }

        if ($label === false && $options['type'] === 'checkbox') {
            return $options['input'];
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
    protected function _extractOption($name, $options, $default = null)
    {
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
     * @param string $fieldName The name of the field to generate label for.
     * @param string $label Label text.
     * @param array $options Options for the label element.
     * @return string Generated label element
     */
    protected function _inputLabel($fieldName, $label, $options)
    {
        $options += ['id' => null, 'input' => null, 'nestedInput' => false, 'templateVars' => []];
        $labelAttributes = ['templateVars' => $options['templateVars']];
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

        $labelAttributes['for'] = $options['id'];
        $groupTypes = ['radio', 'multicheckbox', 'date', 'time', 'datetime'];
        if (in_array($options['type'], $groupTypes, true)) {
            $labelAttributes['for'] = false;
        }
        if ($options['nestedInput']) {
            $labelAttributes['input'] = $options['input'];
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
     * @param string $fieldName Name of a field, like this "modelname.fieldname"
     * @param array $options Array of HTML attributes.
     * @return string|array An HTML text input element.
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-checkboxes
     */
    public function checkbox($fieldName, array $options = [])
    {
        $options += ['hiddenField' => true, 'value' => 1];

        // Work around value=>val translations.
        $value = $options['value'];
        unset($options['value']);
        $options = $this->_initInputField($fieldName, $options);
        $options['value'] = $value;

        $output = '';
        if ($options['hiddenField']) {
            $hiddenOptions = [
                'name' => $options['name'],
                'value' => ($options['hiddenField'] !== true && $options['hiddenField'] !== '_split' ? $options['hiddenField'] : '0'),
                'form' => isset($options['form']) ? $options['form'] : null,
                'secure' => false
            ];
            if (isset($options['disabled']) && $options['disabled']) {
                $hiddenOptions['disabled'] = 'disabled';
            }
            $output = $this->hidden($fieldName, $hiddenOptions);
        }

        if ($options['hiddenField'] === '_split') {
            unset($options['hiddenField'], $options['type']);
            return ['hidden' => $output, 'input' => $this->widget('checkbox', $options)];
        }
        unset($options['hiddenField'], $options['type']);
        return $output . $this->widget('checkbox', $options);
    }

    /**
     * Creates a set of radio widgets.
     *
     * ### Attributes:
     *
     * - `value` - Indicates the value when this radio button is checked.
     * - `label` - boolean to indicate whether or not labels for widgets should be displayed.
     * - `hiddenField` - boolean to indicate if you want the results of radio() to include
     *    a hidden input with a value of ''. This is useful for creating radio sets that are non-continuous.
     * - `disabled` - Set to `true` or `disabled` to disable all the radio buttons.
     * - `empty` - Set to `true` to create an input with the value '' as the first option. When `true`
     *   the radio label will be 'empty'. Set this option to a string to control the label value.
     *
     * @param string $fieldName Name of a field, like this "modelname.fieldname"
     * @param array|\Traversable $options Radio button options array.
     * @param array $attributes Array of attributes.
     * @return string Completed radio widget set.
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-radio-buttons
     */
    public function radio($fieldName, $options = [], array $attributes = [])
    {
        $attributes['options'] = $options;
        $attributes['idPrefix'] = $this->_idPrefix;
        $attributes = $this->_initInputField($fieldName, $attributes);

        $hiddenField = isset($attributes['hiddenField']) ? $attributes['hiddenField'] : true;
        unset($attributes['hiddenField']);

        $radio = $this->widget('radio', $attributes);

        $hidden = '';
        if ($hiddenField) {
            $hidden = $this->hidden($fieldName, [
                'value' => '',
                'form' => isset($attributes['form']) ? $attributes['form'] : null,
                'name' => $attributes['name'],
            ]);
        }

        return $hidden . $radio;
    }

    /**
     * Missing method handler - implements various simple input types. Is used to create inputs
     * of various types. e.g. `$this->Form->text();` will create `<input type="text" />` while
     * `$this->Form->range();` will create `<input type="range" />`
     *
     * ### Usage
     *
     * ```
     * $this->Form->search('User.query', ['value' => 'test']);
     * ```
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
     * @throws \Cake\Core\Exception\Exception When there are no params for the method call.
     */
    public function __call($method, $params)
    {
        $options = [];
        if (empty($params)) {
            throw new Exception(sprintf('Missing field name for FormHelper::%s', $method));
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
     * @param string $fieldName Name of a field, in the form "modelname.fieldname"
     * @param array $options Array of HTML attributes, and special options above.
     * @return string A generated HTML text input element
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-textareas
     */
    public function textarea($fieldName, array $options = [])
    {
        $options = $this->_initInputField($fieldName, $options);
        unset($options['type']);
        return $this->widget('textarea', $options);
    }

    /**
     * Creates a hidden input field.
     *
     * @param string $fieldName Name of a field, in the form of "modelname.fieldname"
     * @param array $options Array of HTML attributes.
     * @return string A generated hidden input
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-hidden-inputs
     */
    public function hidden($fieldName, array $options = [])
    {
        $options += ['required' => false, 'secure' => true];

        $secure = $options['secure'];
        unset($options['secure']);

        $options = $this->_initInputField($fieldName, array_merge(
            $options,
            ['secure' => static::SECURE_SKIP]
        ));

        if ($secure === true) {
            $this->_secure(true, $this->_secureFieldName($options['name']), (string)$options['val']);
        }

        $options['type'] = 'hidden';
        return $this->widget('hidden', $options);
    }

    /**
     * Creates file input widget.
     *
     * @param string $fieldName Name of a field, in the form "modelname.fieldname"
     * @param array $options Array of HTML attributes.
     * @return string A generated file input.
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-file-inputs
     */
    public function file($fieldName, array $options = [])
    {
        $options += ['secure' => true];
        $options = $this->_initInputField($fieldName, $options);

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
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-button-elements
     */
    public function button($title, array $options = [])
    {
        $options += ['type' => 'submit', 'escape' => false, 'secure' => false];
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
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-standalone-buttons-and-post-links
     */
    public function postButton($title, $url, array $options = [])
    {
        $out = $this->create(false, ['url' => $url]);
        if (isset($options['data']) && is_array($options['data'])) {
            foreach (Hash::flatten($options['data']) as $key => $value) {
                $out .= $this->hidden($key, ['value' => $value]);
            }
            unset($options['data']);
        }
        $out .= $this->button($title, $options);
        $out .= $this->end();
        return $out;
    }

    /**
     * Creates an HTML link, but access the URL using the method you specify
     * (defaults to POST). Requires javascript to be enabled in browser.
     *
     * This method creates a `<form>` element. If you want to use this method inside of an
     * existing form, you must use the `block` option so that the new form is being set to
     * a view block that can be rendered outside of the main form.
     *
     * If all you are looking for is a button to submit your form, then you should use
     * `FormHelper::button()` or `FormHelper::submit()` instead.
     *
     * ### Options:
     *
     * - `data` - Array with key/value to pass in input hidden
     * - `method` - Request method to use. Set to 'delete' to simulate
     *   HTTP/1.1 DELETE request. Defaults to 'post'.
     * - `confirm` - Confirm message to show.
     * - `block` - Set to true to append form to view block "postLink" or provide
     *   custom block name.
     * - Other options are the same of HtmlHelper::link() method.
     * - The option `onclick` will be replaced.
     *
     * @param string $title The content to be wrapped by <a> tags.
     * @param string|array|null $url Cake-relative URL or array of URL parameters, or
     *   external URL (starts with http://)
     * @param array $options Array of HTML attributes.
     * @return string An `<a />` element.
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-standalone-buttons-and-post-links
     */
    public function postLink($title, $url = null, array $options = [])
    {
        $options += ['block' => null, 'confirm' => null];

        $requestMethod = 'POST';
        if (!empty($options['method'])) {
            $requestMethod = strtoupper($options['method']);
            unset($options['method']);
        }

        $confirmMessage = $options['confirm'];
        unset($options['confirm']);

        $formName = str_replace('.', '', uniqid('post_', true));
        $formOptions = [
            'name' => $formName,
            'style' => 'display:none;',
            'method' => 'post',
        ];
        if (isset($options['target'])) {
            $formOptions['target'] = $options['target'];
            unset($options['target']);
        }
        $templater = $this->templater();

        $this->_lastAction($url);
        $action = $templater->formatAttributes([
            'action' => $this->Url->build($url),
            'escape' => false
        ]);

        $out = $this->formatTemplate('formStart', [
            'attrs' => $templater->formatAttributes($formOptions) . $action
        ]);
        $out .= $this->hidden('_method', ['value' => $requestMethod]);
        $out .= $this->_csrfField();

        $fields = [];
        if (isset($options['data']) && is_array($options['data'])) {
            foreach (Hash::flatten($options['data']) as $key => $value) {
                $fields[$key] = $value;
                $out .= $this->hidden($key, ['value' => $value]);
            }
            unset($options['data']);
        }
        $out .= $this->secure($fields);
        $out .= $this->formatTemplate('formEnd', []);

        if ($options['block']) {
            if ($options['block'] === true) {
                $options['block'] = __FUNCTION__;
            }
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
     * - `type` - Set to 'reset' for reset inputs. Defaults to 'submit'
     * - `templateVars` - Additional template variables for the input element and its container.
     * - Other attributes will be assigned to the input element.
     *
     * @param string|null $caption The label appearing on the button OR if string contains :// or the
     *  extension .jpg, .jpe, .jpeg, .gif, .png use an image if the extension
     *  exists, AND the first character is /, image is relative to webroot,
     *  OR if the first character is not /, image is relative to webroot/img.
     * @param array $options Array of options. See above.
     * @return string A HTML submit button
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-buttons-and-submit-elements
     */
    public function submit($caption = null, array $options = [])
    {
        if (!is_string($caption) && empty($caption)) {
            $caption = __d('cake', 'Submit');
        }
        $options += [
            'type' => 'submit',
            'secure' => false,
            'templateVars' => []
        ];

        if (isset($options['name'])) {
            $this->_secure($options['secure'], $this->_secureFieldName($options['name']));
        }
        unset($options['secure']);

        $isUrl = strpos($caption, '://') !== false;
        $isImage = preg_match('/\.(jpg|jpe|jpeg|gif|png|ico)$/', $caption);

        $type = $options['type'];
        unset($options['type']);

        if ($isUrl || $isImage) {
            $unlockFields = ['x', 'y'];
            if (isset($options['name'])) {
                $unlockFields = [
                    $options['name'] . '_x',
                    $options['name'] . '_y'
                ];
            }
            foreach ($unlockFields as $ignore) {
                $this->unlockField($ignore);
            }
            $type = 'image';
        }

        if ($isUrl) {
            $options['src'] = $caption;
        } elseif ($isImage) {
            if ($caption{0} !== '/') {
                $url = $this->Url->webroot(Configure::read('App.imageBaseUrl') . $caption);
            } else {
                $url = $this->Url->webroot(trim($caption, '/'));
            }
            $url = $this->Url->assetTimestamp($url);
            $options['src'] = $url;
        } else {
            $options['value'] = $caption;
        }

        $input = $this->formatTemplate('inputSubmit', [
            'type' => $type,
            'attrs' => $this->templater()->formatAttributes($options),
            'templateVars' => $options['templateVars']
        ]);

        return $this->formatTemplate('submitContainer', [
            'content' => $input,
            'templateVars' => $options['templateVars']
        ]);
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
     * ```
     * $options = [1 => 'one', 2 => 'two'];
     * $this->Form->select('Model.field', $options));
     * ```
     *
     * While a nested options array will create optgroups with options inside them.
     * ```
     * $options = [
     *  1 => 'bill',
     *     'fred' => [
     *         2 => 'fred',
     *         3 => 'fred jr.'
     *     ]
     * ];
     * $this->Form->select('Model.field', $options);
     * ```
     *
     * If you have multiple options that need to have the same value attribute, you can
     * use an array of arrays to express this:
     *
     * ```
     * $options = [
     *     ['text' => 'United states', 'value' => 'USA'],
     *     ['text' => 'USA', 'value' => 'USA'],
     * ];
     * ```
     *
     * @param string $fieldName Name attribute of the SELECT
     * @param array|\Traversable $options Array of the OPTION elements (as 'value'=>'Text' pairs) to be used in the
     *   SELECT element
     * @param array $attributes The HTML attributes of the select element.
     * @return string Formatted SELECT element
     * @see \Cake\View\Helper\FormHelper::multiCheckbox() for creating multiple checkboxes.
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-select-pickers
     */
    public function select($fieldName, $options = [], array $attributes = [])
    {
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

        // Secure the field if there are options, or it's a multi select.
        // Single selects with no options don't submit, but multiselects do.
        if ($attributes['secure'] &&
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
            $hiddenAttributes = [
                'name' => $attributes['name'],
                'value' => '',
                'form' => isset($attributes['form']) ? $attributes['form'] : null,
                'secure' => false,
            ];
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
     * @param array|\Traversable $options Array of the OPTION elements
     *   (as 'value'=>'Text' pairs) to be used in the checkboxes element.
     * @param array $attributes The HTML attributes of the select element.
     * @return string Formatted SELECT element
     * @see \Cake\View\Helper\FormHelper::select() for supported option formats.
     */
    public function multiCheckbox($fieldName, $options, array $attributes = [])
    {
        $attributes += [
            'disabled' => null,
            'escape' => true,
            'hiddenField' => true,
            'secure' => true,
        ];
        $attributes = $this->_initInputField($fieldName, $attributes);
        $attributes['options'] = $options;
        $attributes['idPrefix'] = $this->_idPrefix;

        $hidden = '';
        if ($attributes['hiddenField']) {
            $hiddenAttributes = [
                'name' => $attributes['name'],
                'value' => '',
                'secure' => false,
                'disabled' => ($attributes['disabled'] === true || $attributes['disabled'] === 'disabled'),
            ];
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
    protected function _singleDatetime($options, $keep)
    {
        $off = array_diff($this->_datetimeParts, [$keep]);
        $off = array_combine(
            $off,
            array_fill(0, count($off), false)
        );

        $attributes = array_diff_key(
            $options,
            array_flip(array_merge($this->_datetimeOptions, ['value', 'empty']))
        );
        $options = $options + $off + [$keep => $attributes];

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
     * @param string|null $fieldName Prefix name for the SELECT element
     * @param array $options Options & HTML attributes for the select element
     * @return string A generated day select box.
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-day-inputs
     */
    public function day($fieldName = null, array $options = [])
    {
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
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-year-inputs
     */
    public function year($fieldName, array $options = [])
    {
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
     *   If an array, the given array will be used.
     * - `empty` - If true, the empty select option is shown. If a string,
     *   that string is displayed as the empty element.
     * - `value` The selected value of the input.
     *
     * @param string $fieldName Prefix name for the SELECT element
     * @param array $options Attributes for the select element
     * @return string A generated month select dropdown.
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-month-inputs
     */
    public function month($fieldName, array $options = [])
    {
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
     * - `format` Set to 12 or 24 to use 12 or 24 hour formatting. Defaults to 24.
     *
     * @param string $fieldName Prefix name for the SELECT element
     * @param array $options List of HTML attributes
     * @return string Completed hour select input
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-hour-inputs
     */
    public function hour($fieldName, array $options = [])
    {
        $options += ['format' => 24];
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
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-minute-inputs
     */
    public function minute($fieldName, array $options = [])
    {
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
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-meridian-inputs
     */
    public function meridian($fieldName, array $options = [])
    {
        $options = $this->_singleDatetime($options, 'meridian');

        if (isset($options['val'])) {
            $hour = date('H');
            $options['val'] = [
                'hour' => $hour,
                'minute' => (int)$options['val'],
                'meridian' => $hour > 11 ? 'pm' : 'am',
            ];
        }
        return $this->datetime($fieldName, $options);
    }

    /**
     * Returns a set of SELECT elements for a full datetime setup: day, month and year, and then time.
     *
     * ### Date Options:
     *
     * - `empty` - If true, the empty select option is shown. If a string,
     *   that string is displayed as the empty element.
     * - `value` | `default` The default value to be used by the input. A value in `$this->data`
     *   matching the field name will override this value. If no default is provided `time()` will be used.
     * - `monthNames` If false, 2 digit numbers will be used instead of text.
     *   If an array, the given array will be used.
     * - `minYear` The lowest year to use in the year select
     * - `maxYear` The maximum year to use in the year select
     * - `orderYear` - Order of year values in select options.
     *   Possible values 'asc', 'desc'. Default 'desc'.
     *
     * ### Time options:
     *
     * - `empty` - If true, the empty select option is shown. If a string,
     * - `value` | `default` The default value to be used by the input. A value in `$this->data`
     *   matching the field name will override this value. If no default is provided `time()` will be used.
     * - `timeFormat` The time format to use, either 12 or 24.
     * - `interval` The interval for the minutes select. Defaults to 1
     * - `round` - Set to `up` or `down` if you want to force rounding in either direction. Defaults to null.
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
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-date-and-time-inputs
     */
    public function dateTime($fieldName, array $options = [])
    {
        $options += [
            'empty' => true,
            'value' => null,
            'interval' => 1,
            'round' => null,
            'monthNames' => true,
            'minYear' => null,
            'maxYear' => null,
            'orderYear' => 'desc',
            'timeFormat' => 24,
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
    protected function _datetimeOptions($options)
    {
        foreach ($this->_datetimeParts as $type) {
            if (!array_key_exists($type, $options)) {
                $options[$type] = [];
            }
            if ($options[$type] === true) {
                $options[$type] = [];
            }

            // Pass empty options to each type.
            if (!empty($options['empty']) &&
                is_array($options[$type])
            ) {
                $options[$type]['empty'] = $options['empty'];
            }

            // Move empty options into each type array.
            if (isset($options['empty'][$type])) {
                $options[$type]['empty'] = $options['empty'][$type];
            }
        }

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

        if ($options['val'] === true || $options['val'] === null && isset($options['empty']) && $options['empty'] === false) {
            $val = new DateTime();
            $currentYear = $val->format('Y');
            if (isset($options['year']['end']) && $options['year']['end'] < $currentYear) {
                $val->setDate($options['year']['end'], $val->format('n'), $val->format('j'));
            }
            $options['val'] = $val;
        }

        unset($options['empty']);

        return $options;
    }

    /**
     * Generate time inputs.
     *
     * ### Options:
     *
     * See dateTime() for time options.
     *
     * @param string $fieldName Prefix name for the SELECT element
     * @param array $options Array of Options
     * @return string Generated set of select boxes for time formats chosen.
     * @see \Cake\View\Helper\FormHelper::dateTime() for templating options.
     */
    public function time($fieldName, array $options = [])
    {
        $options += [
            'empty' => true,
            'value' => null,
            'interval' => 1,
            'round' => null,
            'timeFormat' => 24,
            'second' => false,
        ];
        $options['year'] = $options['month'] = $options['day'] = false;
        $options = $this->_initInputField($fieldName, $options);
        $options = $this->_datetimeOptions($options);

        return $this->widget('datetime', $options);
    }

    /**
     * Generate date inputs.
     *
     * ### Options:
     *
     * See dateTime() for date options.
     *
     * @param string $fieldName Prefix name for the SELECT element
     * @param array $options Array of Options
     * @return string Generated set of select boxes for time formats chosen.
     * @see \Cake\View\Helper\FormHelper::dateTime() for templating options.
     */
    public function date($fieldName, array $options = [])
    {
        $options += [
            'empty' => true,
            'value' => null,
            'monthNames' => true,
            'minYear' => null,
            'maxYear' => null,
            'orderYear' => 'desc',
        ];
        $options['hour'] = $options['minute'] = false;
        $options['meridian'] = $options['second'] = false;

        $options = $this->_initInputField($fieldName, $options);
        $options = $this->_datetimeOptions($options);
        return $this->widget('datetime', $options);
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
     * - `id` - mixed - If `true` it will be auto generated based on field name.
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
    protected function _initInputField($field, $options = [])
    {
        if (!isset($options['secure'])) {
            $options['secure'] = !empty($this->request->params['_Token']);
        }
        $context = $this->_getContext();

        if (isset($options['id']) && $options['id'] === true) {
            $options['id'] = $this->_domId($field);
        }

        $disabledIndex = array_search('disabled', $options, true);
        if (is_int($disabledIndex)) {
            unset($options[$disabledIndex]);
            $options['disabled'] = true;
        }

        if (!isset($options['name'])) {
            $endsWithBrackets = '';
            if (substr($field, -2) === '[]') {
                $field = substr($field, 0, -2);
                $endsWithBrackets = '[]';
            }
            $parts = explode('.', $field);
            $first = array_shift($parts);
            $options['name'] = $first . (!empty($parts) ? '[' . implode('][', $parts) . ']' : '') . $endsWithBrackets;
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
            $options = $this->addClass($options, $this->_config['errorClass']);
        }
        $isDisabled = false;
        if (isset($options['disabled'])) {
            $isDisabled = (
                $options['disabled'] === true ||
                $options['disabled'] === 'disabled' ||
                (is_array($options['disabled']) &&
                    !empty($options['options']) &&
                    array_diff($options['options'], $options['disabled']) === []
                )
            );
        }
        if ($isDisabled) {
            $options['secure'] = self::SECURE_SKIP;
        }
        if ($options['secure'] === self::SECURE_SKIP) {
            return $options;
        }
        if (!isset($options['required']) && empty($options['disabled']) && $context->isRequired($field)) {
            $options['required'] = true;
        }
        return $options;
    }

    /**
     * Get the field name for use with _secure().
     *
     * Parses the name attribute to create a dot separated name value for use
     * in secured field hash. If filename is of form Model[field] an array of
     * fieldname parts like ['Model', 'field'] is returned.
     *
     * @param string $name The form inputs name attribute.
     * @return array Array of field name params like ['Model.field'] or
     *   ['Model', 'field'] for array fields or empty array if $name is empty.
     */
    protected function _secureFieldName($name)
    {
        if (empty($name) && $name !== '0') {
            return [];
        }

        if (strpos($name, '[') === false) {
            return [$name];
        }
        $parts = explode('[', $name);
        $parts = array_map(function ($el) {
            return trim($el, ']');
        }, $parts);
        return array_filter($parts, 'strlen');
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
     * @param callable $check A callable that returns an object
     *   when the form context is the correct type.
     * @return void
     */
    public function addContextProvider($type, callable $check)
    {
        foreach ($this->_contextProviders as $i => $provider) {
            if ($provider['type'] === $type) {
                unset($this->_contextProviders[$i]);
            }
        }
        array_unshift($this->_contextProviders, ['type' => $type, 'callable' => $check]);
    }

    /**
     * Get the context instance for the current form set.
     *
     * If there is no active form null will be returned.
     *
     * @param \Cake\View\Form\ContextInterface|null $context Either the new context when setting, or null to get.
     * @return null|\Cake\View\Form\ContextInterface The context for the form.
     */
    public function context($context = null)
    {
        if ($context instanceof ContextInterface) {
            $this->_context = $context;
        }
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
    protected function _getContext($data = [])
    {
        if (isset($this->_context) && empty($data)) {
            return $this->_context;
        }
        $data += ['entity' => null];

        foreach ($this->_contextProviders as $provider) {
            $check = $provider['callable'];
            $context = $check($this->request, $data);
            if ($context) {
                break;
            }
        }
        if (!isset($context)) {
            $context = new NullContext($this->request, $data);
        }
        if (!($context instanceof ContextInterface)) {
            throw new RuntimeException(
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
     * @param array|\Cake\View\Widget\WidgetInterface $spec Either a string class
     *   name or an object implementing the WidgetInterface.
     * @return void
     */
    public function addWidget($name, $spec)
    {
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
     * @param array $data The data to render.
     * @return string
     */
    public function widget($name, array $data = [])
    {
        $secure = null;
        if (isset($data['secure'])) {
            $secure = $data['secure'];
            unset($data['secure']);
        }
        $widget = $this->_registry->get($name);
        $out = $widget->render($data, $this->context());
        if (isset($data['name']) && $secure !== null && $secure !== self::SECURE_SKIP) {
            foreach ($widget->secureFields($data) as $field) {
                $this->_secure($secure, $this->_secureFieldName($field));
            }
        }
        return $out;
    }

    /**
     * Restores the default values built into FormHelper.
     *
     * This method will not reset any templates set in custom widgets.
     *
     * @return void
     */
    public function resetTemplates()
    {
        $this->templates($this->_defaultConfig['templates']);
    }

    /**
     * Event listeners.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [];
    }
}
