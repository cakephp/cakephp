<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Form\FormProtector;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\View\Form\ContextFactory;
use Cake\View\Form\ContextInterface;
use Cake\View\Helper;
use Cake\View\StringTemplateTrait;
use Cake\View\View;
use Cake\View\Widget\WidgetLocator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Form helper library.
 *
 * Automatic generation of HTML FORMs from given data.
 *
 * @method string text(string $fieldName, array $options = []) Creates input of type text.
 * @method string number(string $fieldName, array $options = []) Creates input of type number.
 * @method string email(string $fieldName, array $options = []) Creates input of type email.
 * @method string password(string $fieldName, array $options = []) Creates input of type password.
 * @method string search(string $fieldName, array $options = []) Creates input of type search.
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\UrlHelper $Url
 * @link https://book.cakephp.org/4/en/views/helpers/form.html
 */
class FormHelper extends Helper
{
    use IdGeneratorTrait;
    use StringTemplateTrait;

    /**
     * Other helpers used by FormHelper
     *
     * @var array
     */
    protected $helpers = ['Url', 'Html'];

    /**
     * Default config for the helper.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'idPrefix' => null,
        'errorClass' => 'form-error',
        'typeMap' => [
            'string' => 'text',
            'text' => 'textarea',
            'uuid' => 'string',
            'datetime' => 'datetime',
            'datetimefractional' => 'datetime',
            'timestamp' => 'datetime',
            'timestampfractional' => 'datetime',
            'timestamptimezone' => 'datetime',
            'date' => 'date',
            'time' => 'time',
            'year' => 'year',
            'boolean' => 'checkbox',
            'float' => 'number',
            'integer' => 'number',
            'tinyinteger' => 'number',
            'smallinteger' => 'number',
            'decimal' => 'number',
            'binary' => 'file',
        ],
        'templates' => [
            // Used for button elements in button().
            'button' => '<button{{attrs}}>{{text}}</button>',
            // Used for checkboxes in checkbox() and multiCheckbox().
            'checkbox' => '<input type="checkbox" name="{{name}}" value="{{value}}"{{attrs}}>',
            // Input group wrapper for checkboxes created via control().
            'checkboxFormGroup' => '{{label}}',
            // Wrapper container for checkboxes.
            'checkboxWrapper' => '<div class="checkbox">{{label}}</div>',
            // Error message wrapper elements.
            'error' => '<div class="error-message">{{content}}</div>',
            // Container for error items.
            'errorList' => '<ul>{{content}}</ul>',
            // Error item wrapper.
            'errorItem' => '<li>{{text}}</li>',
            // File input used by file().
            'file' => '<input type="file" name="{{name}}"{{attrs}}>',
            // Fieldset element used by allControls().
            'fieldset' => '<fieldset{{attrs}}>{{content}}</fieldset>',
            // Open tag used by create().
            'formStart' => '<form{{attrs}}>',
            // Close tag used by end().
            'formEnd' => '</form>',
            // General grouping container for control(). Defines input/label ordering.
            'formGroup' => '{{label}}{{input}}',
            // Wrapper content used to hide other content.
            'hiddenBlock' => '<div style="display:none;">{{content}}</div>',
            // Generic input element.
            'input' => '<input type="{{type}}" name="{{name}}"{{attrs}}/>',
            // Submit input element.
            'inputSubmit' => '<input type="{{type}}"{{attrs}}/>',
            // Container element used by control().
            'inputContainer' => '<div class="input {{type}}{{required}}">{{content}}</div>',
            // Container element used by control() when a field has an error.
            'inputContainerError' => '<div class="input {{type}}{{required}} error">{{content}}{{error}}</div>',
            // Label element when inputs are not nested inside the label.
            'label' => '<label{{attrs}}>{{text}}</label>',
            // Label element used for radio and multi-checkbox inputs.
            'nestingLabel' => '{{hidden}}<label{{attrs}}>{{input}}{{text}}</label>',
            // Legends created by allControls()
            'legend' => '<legend>{{text}}</legend>',
            // Multi-Checkbox input set title element.
            'multicheckboxTitle' => '<legend>{{text}}</legend>',
            // Multi-Checkbox wrapping container.
            'multicheckboxWrapper' => '<fieldset{{attrs}}>{{content}}</fieldset>',
            // Option element used in select pickers.
            'option' => '<option value="{{value}}"{{attrs}}>{{text}}</option>',
            // Option group element used in select pickers.
            'optgroup' => '<optgroup label="{{label}}"{{attrs}}>{{content}}</optgroup>',
            // Select element,
            'select' => '<select name="{{name}}"{{attrs}}>{{content}}</select>',
            // Multi-select element,
            'selectMultiple' => '<select name="{{name}}[]" multiple="multiple"{{attrs}}>{{content}}</select>',
            // Radio input element,
            'radio' => '<input type="radio" name="{{name}}" value="{{value}}"{{attrs}}>',
            // Wrapping container for radio input/label,
            'radioWrapper' => '{{label}}',
            // Textarea input element,
            'textarea' => '<textarea name="{{name}}"{{attrs}}>{{value}}</textarea>',
            // Container for submit buttons.
            'submitContainer' => '<div class="submit">{{content}}</div>',
            // Confirm javascript template for postLink()
            'confirmJs' => '{{confirm}}',
            // selected class
            'selectedClass' => 'selected',
        ],
        // set HTML5 validation message to custom required/empty messages
        'autoSetCustomValidity' => true,
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
        'year' => ['Year', 'select'],
        '_default' => ['Basic'],
    ];

    /**
     * Constant used internally to skip the securing process,
     * and neither add the field to the hash or to the unlocked fields.
     *
     * @var string
     */
    public const SECURE_SKIP = 'skip';

    /**
     * Defines the type of form being created. Set by FormHelper::create().
     *
     * @var string|null
     */
    public $requestType;

    /**
     * Locator for input widgets.
     *
     * @var \Cake\View\Widget\WidgetLocator
     */
    protected $_locator;

    /**
     * Context for the current form.
     *
     * @var \Cake\View\Form\ContextInterface|null
     */
    protected $_context;

    /**
     * Context factory.
     *
     * @var \Cake\View\Form\ContextFactory|null
     */
    protected $_contextFactory;

    /**
     * The action attribute value of the last created form.
     * Used to make form/request specific hashes for form tampering protection.
     *
     * @var string
     */
    protected $_lastAction = '';

    /**
     * The supported sources that can be used to populate input values.
     *
     * `context` - Corresponds to `ContextInterface` instances.
     * `data` - Corresponds to request data (POST/PUT).
     * `query` - Corresponds to request's query string.
     *
     * @var string[]
     */
    protected $supportedValueSources = ['context', 'data', 'query'];

    /**
     * The default sources.
     *
     * @see FormHelper::$supportedValueSources for valid values.
     * @var string[]
     */
    protected $_valueSources = ['data', 'context'];

    /**
     * Grouped input types.
     *
     * @var string[]
     */
    protected $_groupedInputTypes = ['radio', 'multicheckbox'];

    /**
     * Form protector
     *
     * @var \Cake\Form\FormProtector|null
     */
    protected $formProtector;

    /**
     * Construct the widgets and binds the default context providers
     *
     * @param \Cake\View\View $view The View this helper is being attached to.
     * @param array $config Configuration settings for the helper.
     */
    public function __construct(View $view, array $config = [])
    {
        $locator = null;
        $widgets = $this->_defaultWidgets;
        if (isset($config['locator'])) {
            $locator = $config['locator'];
            unset($config['locator']);
        }
        if (isset($config['widgets'])) {
            if (is_string($config['widgets'])) {
                $config['widgets'] = (array)$config['widgets'];
            }
            $widgets = $config['widgets'] + $widgets;
            unset($config['widgets']);
        }

        if (isset($config['groupedInputTypes'])) {
            $this->_groupedInputTypes = $config['groupedInputTypes'];
            unset($config['groupedInputTypes']);
        }

        parent::__construct($view, $config);

        if (!$locator) {
            $locator = new WidgetLocator($this->templater(), $this->_View, $widgets);
        }
        $this->setWidgetLocator($locator);
        $this->_idPrefix = $this->getConfig('idPrefix');
    }

    /**
     * Get the widget locator currently used by the helper.
     *
     * @return \Cake\View\Widget\WidgetLocator Current locator instance
     * @since 3.6.0
     */
    public function getWidgetLocator(): WidgetLocator
    {
        return $this->_locator;
    }

    /**
     * Set the widget locator the helper will use.
     *
     * @param \Cake\View\Widget\WidgetLocator $instance The locator instance to set.
     * @return $this
     * @since 3.6.0
     */
    public function setWidgetLocator(WidgetLocator $instance)
    {
        $this->_locator = $instance;

        return $this;
    }

    /**
     * Set the context factory the helper will use.
     *
     * @param \Cake\View\Form\ContextFactory|null $instance The context factory instance to set.
     * @param array $contexts An array of context providers.
     * @return \Cake\View\Form\ContextFactory
     */
    public function contextFactory(?ContextFactory $instance = null, array $contexts = []): ContextFactory
    {
        if ($instance === null) {
            if ($this->_contextFactory === null) {
                $this->_contextFactory = ContextFactory::createWithDefaults($contexts);
            }

            return $this->_contextFactory;
        }
        $this->_contextFactory = $instance;

        return $this->_contextFactory;
    }

    /**
     * Returns an HTML form element.
     *
     * ### Options:
     *
     * - `type` Form method defaults to autodetecting based on the form context. If
     *   the form context's isCreate() method returns false, a PUT request will be done.
     * - `method` Set the form's method attribute explicitly.
     * - `url` The URL the form submits to. Can be a string or a URL array.
     * - `encoding` Set the accept-charset encoding for the form. Defaults to `Configure::read('App.encoding')`
     * - `enctype` Set the form encoding explicitly. By default `type => file` will set `enctype`
     *   to `multipart/form-data`.
     * - `templates` The templates you want to use for this form. Any templates will be merged on top of
     *   the already loaded templates. This option can either be a filename in /config that contains
     *   the templates you want to load, or an array of templates to use.
     * - `context` Additional options for the context class. For example the EntityContext accepts a 'table'
     *   option that allows you to set the specific Table class the form should be based on.
     * - `idPrefix` Prefix for generated ID attributes.
     * - `valueSources` The sources that values should be read from. See FormHelper::setValueSources()
     * - `templateVars` Provide template variables for the formStart template.
     *
     * @param mixed $context The context for which the form is being defined.
     *   Can be a ContextInterface instance, ORM entity, ORM resultset, or an
     *   array of meta data. You can use `null` to make a context-less form.
     * @param array $options An array of html attributes and options.
     * @return string An formatted opening FORM tag.
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#Cake\View\Helper\FormHelper::create
     */
    public function create($context = null, array $options = []): string
    {
        $append = '';

        if ($context instanceof ContextInterface) {
            $this->context($context);
        } else {
            if (empty($options['context'])) {
                $options['context'] = [];
            }
            $options['context']['entity'] = $context;
            $context = $this->_getContext($options['context']);
            unset($options['context']);
        }

        $isCreate = $context->isCreate();

        $options += [
            'type' => $isCreate ? 'post' : 'put',
            'url' => null,
            'encoding' => strtolower(Configure::read('App.encoding')),
            'templates' => null,
            'idPrefix' => null,
            'valueSources' => null,
        ];

        if (isset($options['valueSources'])) {
            $this->setValueSources($options['valueSources']);
            unset($options['valueSources']);
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

        if ($options['url'] === false) {
            $url = $this->_View->getRequest()->getRequestTarget();
            $action = null;
        } else {
            $url = $this->_formUrl($context, $options);
            $action = $this->Url->build($url);
        }

        $this->_lastAction($url);
        unset($options['url'], $options['idPrefix']);

        $htmlAttributes = [];
        switch (strtolower($options['type'])) {
            case 'get':
                $htmlAttributes['method'] = 'get';
                break;
            // Set enctype for form
            case 'file':
                $htmlAttributes['enctype'] = 'multipart/form-data';
                $options['type'] = $isCreate ? 'post' : 'put';
            // Move on
            case 'put':
            // Move on
            case 'delete':
            // Set patch method
            case 'patch':
                $append .= $this->hidden('_method', [
                    'name' => '_method',
                    'value' => strtoupper($options['type']),
                    'secure' => static::SECURE_SKIP,
                ]);
            // Default to post method
            default:
                $htmlAttributes['method'] = 'post';
        }
        if (isset($options['method'])) {
            $htmlAttributes['method'] = strtolower($options['method']);
        }
        if (isset($options['enctype'])) {
            $htmlAttributes['enctype'] = strtolower($options['enctype']);
        }

        $this->requestType = strtolower($options['type']);

        if (!empty($options['encoding'])) {
            $htmlAttributes['accept-charset'] = $options['encoding'];
        }
        unset($options['type'], $options['encoding']);

        $htmlAttributes += $options;

        if ($this->requestType !== 'get') {
            $formTokenData = $this->_View->getRequest()->getAttribute('formTokenData');
            if ($formTokenData !== null) {
                $this->formProtector = $this->createFormProtector($formTokenData);
            }

            $append .= $this->_csrfField();
        }

        if (!empty($append)) {
            $append = $templater->format('hiddenBlock', ['content' => $append]);
        }

        $actionAttr = $templater->formatAttributes(['action' => $action, 'escape' => false]);

        return $this->formatTemplate('formStart', [
            'attrs' => $templater->formatAttributes($htmlAttributes) . $actionAttr,
            'templateVars' => $options['templateVars'] ?? [],
        ]) . $append;
    }

    /**
     * Create the URL for a form based on the options.
     *
     * @param \Cake\View\Form\ContextInterface $context The context object to use.
     * @param array $options An array of options from create()
     * @return string|array The action attribute for the form.
     */
    protected function _formUrl(ContextInterface $context, array $options)
    {
        $request = $this->_View->getRequest();

        if ($options['url'] === null) {
            return $request->getRequestTarget();
        }

        if (
            is_string($options['url']) ||
            (is_array($options['url']) &&
            isset($options['url']['_name']))
        ) {
            return $options['url'];
        }

        $actionDefaults = [
            'plugin' => $this->_View->getPlugin(),
            'controller' => $request->getParam('controller'),
            'action' => $request->getParam('action'),
        ];

        $action = (array)$options['url'] + $actionDefaults;

        return $action;
    }

    /**
     * Correctly store the last created form action URL.
     *
     * @param string|array|null $url The URL of the last form.
     * @return void
     */
    protected function _lastAction($url = null): void
    {
        $action = Router::url($url, true);
        $query = parse_url($action, PHP_URL_QUERY);
        $query = $query ? '?' . $query : '';

        $path = parse_url($action, PHP_URL_PATH) ?: '';
        $this->_lastAction = $path . $query;
    }

    /**
     * Return a CSRF input if the request data is present.
     * Used to secure forms in conjunction with CsrfMiddleware.
     *
     * @return string
     */
    protected function _csrfField(): string
    {
        $request = $this->_View->getRequest();

        $csrfToken = $request->getAttribute('csrfToken');
        if (!$csrfToken) {
            return '';
        }

        return $this->hidden('_csrfToken', [
            'value' => $csrfToken,
            'secure' => static::SECURE_SKIP,
            'autocomplete' => 'off',
        ]);
    }

    /**
     * Closes an HTML form, cleans up values set by FormHelper::create(), and writes hidden
     * input fields where appropriate.
     *
     * Resets some parts of the state, shared among multiple FormHelper::create() calls, to defaults.
     *
     * @param array $secureAttributes Secure attributes which will be passed as HTML attributes
     *   into the hidden input elements generated for the Security Component.
     * @return string A closing FORM tag.
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#closing-the-form
     */
    public function end(array $secureAttributes = []): string
    {
        $out = '';

        if ($this->requestType !== 'get' && $this->_View->getRequest()->getAttribute('formTokenData') !== null) {
            $out .= $this->secure([], $secureAttributes);
        }
        $out .= $this->formatTemplate('formEnd', []);

        $this->templater()->pop();
        $this->requestType = null;
        $this->_context = null;
        $this->_valueSources = ['data', 'context'];
        $this->_idPrefix = $this->getConfig('idPrefix');
        $this->formProtector = null;

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
     * @param array $fields If set specifies the list of fields to be added to
     *    FormProtector for generating the hash.
     * @param array $secureAttributes will be passed as HTML attributes into the hidden
     *    input elements generated for the Security Component.
     * @return string A hidden input field with a security hash, or empty string when
     *   secured forms are not in use.
     */
    public function secure(array $fields = [], array $secureAttributes = []): string
    {
        if (!$this->formProtector) {
            return '';
        }

        foreach ($fields as $field => $value) {
            if (is_int($field)) {
                $field = $value;
                $value = null;
            }
            $this->formProtector->addField($field, true, $value);
        }

        $debugSecurity = (bool)Configure::read('debug');
        if (isset($secureAttributes['debugSecurity'])) {
            $debugSecurity = $debugSecurity && $secureAttributes['debugSecurity'];
            unset($secureAttributes['debugSecurity']);
        }
        $secureAttributes['secure'] = static::SECURE_SKIP;
        $secureAttributes['autocomplete'] = 'off';

        $tokenData = $this->formProtector->buildTokenData(
            $this->_lastAction,
            $this->_View->getRequest()->getSession()->id()
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
                'value' => $tokenData['debug'],
            ]);
            $out .= $this->hidden('_Token.debug', $tokenDebug);
        }

        return $this->formatTemplate('hiddenBlock', ['content' => $out]);
    }

    /**
     * Add to the list of fields that are currently unlocked.
     *
     * Unlocked fields are not included in the form protection field hash.
     *
     * @param string $name The dot separated name for the field.
     * @return $this
     */
    public function unlockField(string $name)
    {
        $this->getFormProtector()->unlockField($name);

        return $this;
    }

    /**
     * Create FormProtector instance.
     *
     * @param array $formTokenData Token data.
     * @return \Cake\Form\FormProtector
     */
    protected function createFormProtector(array $formTokenData): FormProtector
    {
        $session = $this->_View->getRequest()->getSession();
        $session->start();

        return new FormProtector(
            $formTokenData
        );
    }

    /**
     * Get form protector instance.
     *
     * @return \Cake\Form\FormProtector
     * @throws \Cake\Core\Exception\CakeException
     */
    public function getFormProtector(): FormProtector
    {
        if ($this->formProtector === null) {
            throw new CakeException(
                'FormHelper::create() must be called first for FormProtector instance to be created.'
            );
        }

        return $this->formProtector;
    }

    /**
     * Returns true if there is an error for the given field, otherwise false
     *
     * @param string $field This should be "modelname.fieldname"
     * @return bool If there are errors this method returns true, else false.
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#displaying-and-checking-errors
     */
    public function isFieldError(string $field): bool
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
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#displaying-and-checking-errors
     */
    public function error(string $field, $text = null, array $options = []): string
    {
        if (substr($field, -5) === '._ids') {
            $field = substr($field, 0, -5);
        }
        $options += ['escape' => true];

        $context = $this->_getContext();
        if (!$context->hasError($field)) {
            return '';
        }
        $error = $context->error($field);

        if (is_array($text)) {
            $tmp = [];
            foreach ($error as $k => $e) {
                if (isset($text[$k])) {
                    $tmp[] = $text[$k];
                } elseif (isset($text[$e])) {
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
                    'content' => implode('', $errorText),
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
     * - `escape` - Set to `false` to turn off escaping of label text.
     *   Defaults to `true`.
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
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#creating-labels
     */
    public function label(string $fieldName, ?string $text = null, array $options = []): string
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
     * Generate a set of controls for `$fields`. If $fields is empty the fields
     * of current model will be used.
     *
     * You can customize individual controls through `$fields`.
     * ```
     * $this->Form->allControls([
     *   'name' => ['label' => 'custom label']
     * ]);
     * ```
     *
     * You can exclude fields by specifying them as `false`:
     *
     * ```
     * $this->Form->allControls(['title' => false]);
     * ```
     *
     * In the above example, no field would be generated for the title field.
     *
     * @param array $fields An array of customizations for the fields that will be
     *   generated. This array allows you to set custom types, labels, or other options.
     * @param array $options Options array. Valid keys are:
     *
     * - `fieldset` Set to false to disable the fieldset. You can also pass an array of params to be
     *    applied as HTML attributes to the fieldset tag. If you pass an empty array, the fieldset will
     *    be enabled
     * - `legend` Set to false to disable the legend for the generated control set. Or supply a string
     *    to customize the legend text.
     * @return string Completed form controls.
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#generating-entire-forms
     */
    public function allControls(array $fields = [], array $options = []): string
    {
        $context = $this->_getContext();

        $modelFields = $context->fieldNames();

        $fields = array_merge(
            Hash::normalize($modelFields),
            Hash::normalize($fields)
        );

        return $this->controls($fields, $options);
    }

    /**
     * Generate a set of controls for `$fields` wrapped in a fieldset element.
     *
     * You can customize individual controls through `$fields`.
     * ```
     * $this->Form->controls([
     *   'name' => ['label' => 'custom label'],
     *   'email'
     * ]);
     * ```
     *
     * @param array $fields An array of the fields to generate. This array allows
     *   you to set custom types, labels, or other options.
     * @param array $options Options array. Valid keys are:
     *
     * - `fieldset` Set to false to disable the fieldset. You can also pass an
     *    array of params to be applied as HTML attributes to the fieldset tag.
     *    If you pass an empty array, the fieldset will be enabled.
     * - `legend` Set to false to disable the legend for the generated input set.
     *    Or supply a string to customize the legend text.
     * @return string Completed form inputs.
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#generating-entire-forms
     */
    public function controls(array $fields, array $options = []): string
    {
        $fields = Hash::normalize($fields);

        $out = '';
        foreach ($fields as $name => $opts) {
            if ($opts === false) {
                continue;
            }

            $out .= $this->control($name, (array)$opts);
        }

        return $this->fieldset($out, $options);
    }

    /**
     * Wrap a set of inputs in a fieldset
     *
     * @param string $fields the form inputs to wrap in a fieldset
     * @param array $options Options array. Valid keys are:
     *
     * - `fieldset` Set to false to disable the fieldset. You can also pass an array of params to be
     *    applied as HTML attributes to the fieldset tag. If you pass an empty array, the fieldset will
     *    be enabled
     * - `legend` Set to false to disable the legend for the generated input set. Or supply a string
     *    to customize the legend text.
     * @return string Completed form inputs.
     */
    public function fieldset(string $fields = '', array $options = []): string
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
            $isCreate = $context->isCreate();
            $modelName = Inflector::humanize(
                Inflector::singularize($this->_View->getRequest()->getParam('controller'))
            );
            if (!$isCreate) {
                $legend = __d('cake', 'Edit {0}', $modelName);
            } else {
                $legend = __d('cake', 'New {0}', $modelName);
            }
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
     * Generates a form control element complete with label and wrapper div.
     *
     * ### Options
     *
     * See each field type method for more information. Any options that are part of
     * $attributes or $options for the different **type** methods can be included in `$options` for control().
     * Additionally, any unknown keys that are not in the list below, or part of the selected type's options
     * will be treated as a regular HTML attribute for the generated input.
     *
     * - `type` - Force the type of widget you want. e.g. `type => 'select'`
     * - `label` - Either a string label, or an array of options for the label. See FormHelper::label().
     * - `options` - For widgets that take options e.g. radio, select.
     * - `error` - Control the error message that is produced. Set to `false` to disable any kind of error reporting
     *   (field error and error messages).
     * - `empty` - String or boolean to enable empty select box options.
     * - `nestedInput` - Used with checkbox and radio inputs. Set to false to render inputs outside of label
     *   elements. Can be set to true on any input to force the input inside the label. If you
     *   enable this option for radio buttons you will also need to modify the default `radioWrapper` template.
     * - `templates` - The templates you want to use for this input. Any templates will be merged on top of
     *   the already loaded templates. This option can either be a filename in /config that contains
     *   the templates you want to load, or an array of templates to use.
     * - `labelOptions` - Either `false` to disable label around nestedWidgets e.g. radio, multicheckbox or an array
     *   of attributes for the label tag. `selected` will be added to any classes e.g. `class => 'myclass'` where
     *   widget is checked
     *
     * @param string $fieldName This should be "modelname.fieldname"
     * @param array $options Each type of input takes different options.
     * @return string Completed form widget.
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#creating-form-inputs
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    public function control(string $fieldName, array $options = []): string
    {
        $options += [
            'type' => null,
            'label' => null,
            'error' => null,
            'required' => null,
            'options' => null,
            'templates' => [],
            'templateVars' => [],
            'labelOptions' => true,
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
            if (is_array($options['error'])) {
                $error = $this->error($fieldName, $options['error'], $options['error']);
            } else {
                $error = $this->error($fieldName, $options['error']);
            }
            $errorSuffix = empty($error) ? '' : 'Error';
            unset($options['error']);
        }

        $label = $options['label'];
        unset($options['label']);

        $labelOptions = $options['labelOptions'];
        unset($options['labelOptions']);

        $nestedInput = false;
        if ($options['type'] === 'checkbox') {
            $nestedInput = true;
        }
        $nestedInput = $options['nestedInput'] ?? $nestedInput;
        unset($options['nestedInput']);

        if (
            $nestedInput === true
            && $options['type'] === 'checkbox'
            && !array_key_exists('hiddenField', $options)
            && $label !== false
        ) {
            $options['hiddenField'] = '_split';
        }

        $input = $this->_getInput($fieldName, $options + ['labelOptions' => $labelOptions]);
        if ($options['type'] === 'hidden' || $options['type'] === 'submit') {
            if ($newTemplates) {
                $templater->pop();
            }

            return $input;
        }

        $label = $this->_getLabel($fieldName, compact('input', 'label', 'error', 'nestedInput') + $options);
        if ($nestedInput) {
            $result = $this->_groupTemplate(compact('label', 'error', 'options'));
        } else {
            $result = $this->_groupTemplate(compact('input', 'label', 'error', 'options'));
        }
        $result = $this->_inputContainerTemplate([
            'content' => $result,
            'error' => $error,
            'errorSuffix' => $errorSuffix,
            'options' => $options,
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
    protected function _groupTemplate(array $options): string
    {
        $groupTemplate = $options['options']['type'] . 'FormGroup';
        if (!$this->templater()->get($groupTemplate)) {
            $groupTemplate = 'formGroup';
        }

        return $this->formatTemplate($groupTemplate, [
            'input' => $options['input'] ?? [],
            'label' => $options['label'],
            'error' => $options['error'],
            'templateVars' => $options['options']['templateVars'] ?? [],
        ]);
    }

    /**
     * Generates an input container template
     *
     * @param array $options The options for input container template
     * @return string The generated input container template
     */
    protected function _inputContainerTemplate(array $options): string
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
            'templateVars' => $options['options']['templateVars'] ?? [],
        ]);
    }

    /**
     * Generates an input element
     *
     * @param string $fieldName the field name
     * @param array $options The options for the input element
     * @return string|array The generated input element string
     *  or array if checkbox() is called with option 'hiddenField' set to '_split'.
     */
    protected function _getInput(string $fieldName, array $options)
    {
        $label = $options['labelOptions'];
        unset($options['labelOptions']);
        switch (strtolower($options['type'])) {
            case 'select':
            case 'radio':
            case 'multicheckbox':
                $opts = $options['options'];
                if ($opts == null) {
                    $opts = [];
                }
                unset($options['options']);

                return $this->{$options['type']}($fieldName, $opts, $options + ['label' => $label]);
            case 'input':
                throw new RuntimeException("Invalid type 'input' used for field '$fieldName'");

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
    protected function _parseOptions(string $fieldName, array $options): array
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
    protected function _inputType(string $fieldName, array $options): string
    {
        $context = $this->_getContext();

        if ($context->isPrimaryKey($fieldName)) {
            return 'hidden';
        }

        if (substr($fieldName, -3) === '_id') {
            return 'select';
        }

        $type = 'text';
        $internalType = $context->type($fieldName);
        $map = $this->_config['typeMap'];
        if ($internalType !== null && isset($map[$internalType])) {
            $type = $map[$internalType];
        }
        $fieldName = array_slice(explode('.', $fieldName), -1)[0];

        switch (true) {
            case isset($options['checked']):
                return 'checkbox';
            case isset($options['options']):
                return 'select';
            case in_array($fieldName, ['passwd', 'password'], true):
                return 'password';
            case in_array($fieldName, ['tel', 'telephone', 'phone'], true):
                return 'tel';
            case $fieldName === 'email':
                return 'email';
            case isset($options['rows']) || isset($options['cols']):
                return 'textarea';
            case $fieldName === 'year':
                return 'year';
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
    protected function _optionsOptions(string $fieldName, array $options): array
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
        if (!is_iterable($varOptions)) {
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
    protected function _magicOptions(string $fieldName, array $options, bool $allowOverride): array
    {
        $options += [
            'templateVars' => [],
        ];

        $options = $this->setRequiredAndCustomValidity($fieldName, $options);

        $typesWithOptions = ['text', 'number', 'radio', 'select'];
        $magicOptions = (in_array($options['type'], ['radio', 'select'], true) || $allowOverride);
        if ($magicOptions && in_array($options['type'], $typesWithOptions, true)) {
            $options = $this->_optionsOptions($fieldName, $options);
        }

        if ($allowOverride && substr($fieldName, -5) === '._ids') {
            $options['type'] = 'select';
            if (!isset($options['multiple']) || ($options['multiple'] && $options['multiple'] !== 'checkbox')) {
                $options['multiple'] = true;
            }
        }

        return $options;
    }

    /**
     * Set required attribute and custom validity JS.
     *
     * @param string $fieldName The name of the field to generate options for.
     * @param array $options Options list.
     * @return array Modified options list.
     */
    protected function setRequiredAndCustomValidity(string $fieldName, array $options)
    {
        $context = $this->_getContext();

        if (!isset($options['required']) && $options['type'] !== 'hidden') {
            $options['required'] = $context->isRequired($fieldName);
        }

        $message = $context->getRequiredMessage($fieldName);
        $message = h($message);

        if ($options['required'] && $message) {
            $options['templateVars']['customValidityMessage'] = $message;

            if ($this->getConfig('autoSetCustomValidity')) {
                $options['data-validity-message'] = $message;
                $options['oninvalid'] = "this.setCustomValidity(''); "
                    . 'if (!this.value) this.setCustomValidity(this.dataset.validityMessage)';
                $options['oninput'] = "this.setCustomValidity('')";
            }
        }

        return $options;
    }

    /**
     * Generate label for input
     *
     * @param string $fieldName The name of the field to generate label for.
     * @param array $options Options list.
     * @return string|false Generated label element or false.
     */
    protected function _getLabel(string $fieldName, array $options)
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
    protected function _extractOption(string $name, array $options, $default = null)
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
     * @param string|array|null $label Label text or array with label attributes.
     * @param array $options Options for the label element.
     * @return string Generated label element
     */
    protected function _inputLabel(string $fieldName, $label = null, array $options = []): string
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
        if (in_array($options['type'], $this->_groupedInputTypes, true)) {
            $labelAttributes['for'] = false;
        }
        if ($options['nestedInput']) {
            $labelAttributes['input'] = $options['input'];
        }
        if (isset($options['escape'])) {
            $labelAttributes['escape'] = $options['escape'];
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
     * @return string[]|string An HTML text input element.
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#creating-checkboxes
     */
    public function checkbox(string $fieldName, array $options = [])
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
                'value' => $options['hiddenField'] !== true
                    && $options['hiddenField'] !== '_split'
                    ? $options['hiddenField'] : '0',
                'form' => $options['form'] ?? null,
                'secure' => false,
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
     * - `label` - Either `false` to disable label around the widget or an array of attributes for
     *    the label tag. `selected` will be added to any classes e.g. `'class' => 'myclass'` where widget
     *    is checked
     * - `hiddenField` - boolean to indicate if you want the results of radio() to include
     *    a hidden input with a value of ''. This is useful for creating radio sets that are non-continuous.
     * - `disabled` - Set to `true` or `disabled` to disable all the radio buttons. Use an array of
     *   values to disable specific radio buttons.
     * - `empty` - Set to `true` to create an input with the value '' as the first option. When `true`
     *   the radio label will be 'empty'. Set this option to a string to control the label value.
     *
     * @param string $fieldName Name of a field, like this "modelname.fieldname"
     * @param iterable $options Radio button options array.
     * @param array $attributes Array of attributes.
     * @return string Completed radio widget set.
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#creating-radio-buttons
     */
    public function radio(string $fieldName, iterable $options = [], array $attributes = []): string
    {
        $attributes['options'] = $options;
        $attributes['idPrefix'] = $this->_idPrefix;
        $attributes = $this->_initInputField($fieldName, $attributes);

        $hiddenField = $attributes['hiddenField'] ?? true;
        unset($attributes['hiddenField']);

        $radio = $this->widget('radio', $attributes);

        $hidden = '';
        if ($hiddenField) {
            $hidden = $this->hidden($fieldName, [
                'value' => $hiddenField === true ? '' : $hiddenField,
                'form' => $attributes['form'] ?? null,
                'name' => $attributes['name'],
            ]);
        }

        return $hidden . $radio;
    }

    /**
     * Missing method handler - implements various simple input types. Is used to create inputs
     * of various types. e.g. `$this->Form->text();` will create `<input type="text"/>` while
     * `$this->Form->range();` will create `<input type="range"/>`
     *
     * ### Usage
     *
     * ```
     * $this->Form->search('User.query', ['value' => 'test']);
     * ```
     *
     * Will make an input like:
     *
     * `<input type="search" id="UserQuery" name="User[query]" value="test"/>`
     *
     * The first argument to an input type should always be the fieldname, in `Model.field` format.
     * The second argument should always be an array of attributes for the input.
     *
     * @param string $method Method name / input type to make.
     * @param array $params Parameters for the method call
     * @return string Formatted input method.
     * @throws \Cake\Core\Exception\CakeException When there are no params for the method call.
     */
    public function __call(string $method, array $params)
    {
        $options = [];
        if (empty($params)) {
            throw new CakeException(sprintf('Missing field name for FormHelper::%s', $method));
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
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#creating-textareas
     */
    public function textarea(string $fieldName, array $options = []): string
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
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#creating-hidden-inputs
     */
    public function hidden(string $fieldName, array $options = []): string
    {
        $options += ['required' => false, 'secure' => true];

        $secure = $options['secure'];
        unset($options['secure']);

        $options = $this->_initInputField($fieldName, array_merge(
            $options,
            ['secure' => static::SECURE_SKIP]
        ));

        if ($secure === true && $this->formProtector) {
            $this->formProtector->addField(
                $options['name'],
                true,
                $options['val'] === false ? '0' : (string)$options['val']
            );
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
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#creating-file-inputs
     */
    public function file(string $fieldName, array $options = []): string
    {
        $options += ['secure' => true];
        $options = $this->_initInputField($fieldName, $options);

        unset($options['type']);

        return $this->widget('file', $options);
    }

    /**
     * Creates a `<button>` tag.
     *
     * ### Options:
     *
     * - `type` - Value for "type" attribute of button. Defaults to "submit".
     * - `escapeTitle` - HTML entity encode the title of the button. Defaults to true.
     * - `escape` - HTML entity encode the attributes of button tag. Defaults to true.
     * - `confirm` - Confirm message to show. Form execution will only continue if confirmed then.
     *
     * @param string $title The button's caption. Not automatically HTML encoded
     * @param array $options Array of options and HTML attributes.
     * @return string A HTML button tag.
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#creating-button-elements
     */
    public function button(string $title, array $options = []): string
    {
        $options += [
            'type' => 'submit',
            'escapeTitle' => true,
            'escape' => true,
            'secure' => false,
            'confirm' => null,
        ];
        $options['text'] = $title;

        $confirmMessage = $options['confirm'];
        unset($options['confirm']);
        if ($confirmMessage) {
            $confirm = $this->_confirm('return true;', 'return false;');
            $options['data-confirm-message'] = $confirmMessage;
            $options['onclick'] = $this->templater()->format('confirmJs', [
                'confirmMessage' => h($confirmMessage),
                'confirm' => $confirm,
            ]);
        }

        return $this->widget('button', $options);
    }

    /**
     * Create a `<button>` tag with a surrounding `<form>` that submits via POST as default.
     *
     * This method creates a `<form>` element. So do not use this method in an already opened form.
     * Instead use FormHelper::submit() or FormHelper::button() to create buttons inside opened forms.
     *
     * ### Options:
     *
     * - `data` - Array with key/value to pass in input hidden
     * - `method` - Request method to use. Set to 'delete' or others to simulate
     *   HTTP/1.1 DELETE (or others) request. Defaults to 'post'.
     * - `form` - Array with any option that FormHelper::create() can take
     * - Other options is the same of button method.
     * - `confirm` - Confirm message to show. Form execution will only continue if confirmed then.
     *
     * @param string $title The button's caption. Not automatically HTML encoded
     * @param string|array $url URL as string or array
     * @param array $options Array of options and HTML attributes.
     * @return string A HTML button tag.
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#creating-standalone-buttons-and-post-links
     */
    public function postButton(string $title, $url, array $options = []): string
    {
        $formOptions = ['url' => $url];
        if (isset($options['method'])) {
            $formOptions['type'] = $options['method'];
            unset($options['method']);
        }
        if (isset($options['form']) && is_array($options['form'])) {
            $formOptions = $options['form'] + $formOptions;
            unset($options['form']);
        }
        $out = $this->create(null, $formOptions);
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
     * - `confirm` - Confirm message to show. Form execution will only continue if confirmed then.
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
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#creating-standalone-buttons-and-post-links
     */
    public function postLink(string $title, $url = null, array $options = []): string
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

        $restoreAction = $this->_lastAction;
        $this->_lastAction($url);
        $restoreFormProtector = $this->formProtector;

        $action = $templater->formatAttributes([
            'action' => $this->Url->build($url),
            'escape' => false,
        ]);

        $out = $this->formatTemplate('formStart', [
            'attrs' => $templater->formatAttributes($formOptions) . $action,
        ]);
        $out .= $this->hidden('_method', [
            'value' => $requestMethod,
            'secure' => static::SECURE_SKIP,
        ]);
        $out .= $this->_csrfField();

        $formTokenData = $this->_View->getRequest()->getAttribute('formTokenData');
        if ($formTokenData !== null) {
            $this->formProtector = $this->createFormProtector($formTokenData);
        }

        $fields = [];
        if (isset($options['data']) && is_array($options['data'])) {
            foreach (Hash::flatten($options['data']) as $key => $value) {
                $fields[$key] = $value;
                $out .= $this->hidden($key, ['value' => $value, 'secure' => static::SECURE_SKIP]);
            }
            unset($options['data']);
        }
        $out .= $this->secure($fields);
        $out .= $this->formatTemplate('formEnd', []);

        $this->_lastAction = $restoreAction;
        $this->formProtector = $restoreFormProtector;

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
            $onClick = $this->_confirm($onClick, '');
            $onClick = $onClick . 'event.returnValue = false; return false;';
            $onClick = $this->templater()->format('confirmJs', [
                'confirmMessage' => h($confirmMessage),
                'formName' => $formName,
                'confirm' => $onClick,
            ]);
            $options['data-confirm-message'] = $confirmMessage;
        } else {
            $onClick .= ' event.returnValue = false; return false;';
        }
        $options['onclick'] = $onClick;

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
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#creating-buttons-and-submit-elements
     */
    public function submit(?string $caption = null, array $options = []): string
    {
        if ($caption === null) {
            $caption = __d('cake', 'Submit');
        }
        $options += [
            'type' => 'submit',
            'secure' => false,
            'templateVars' => [],
        ];

        if (isset($options['name']) && $this->formProtector) {
            $this->formProtector->addField(
                $options['name'],
                $options['secure']
            );
        }
        unset($options['secure']);

        $isUrl = strpos($caption, '://') !== false;
        $isImage = preg_match('/\.(jpg|jpe|jpeg|gif|png|ico)$/', $caption);

        $type = $options['type'];
        unset($options['type']);

        if ($isUrl || $isImage) {
            $type = 'image';

            if ($this->formProtector) {
                $unlockFields = ['x', 'y'];
                if (isset($options['name'])) {
                    $unlockFields = [
                        $options['name'] . '_x',
                        $options['name'] . '_y',
                    ];
                }
                foreach ($unlockFields as $ignore) {
                    $this->unlockField($ignore);
                }
            }
        }

        if ($isUrl) {
            $options['src'] = $caption;
        } elseif ($isImage) {
            if ($caption[0] !== '/') {
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
            'templateVars' => $options['templateVars'],
        ]);

        return $this->formatTemplate('submitContainer', [
            'content' => $input,
            'templateVars' => $options['templateVars'],
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
     * @param iterable $options Array of the OPTION elements (as 'value'=>'Text' pairs) to be used in the
     *   SELECT element
     * @param array $attributes The HTML attributes of the select element.
     * @return string Formatted SELECT element
     * @see \Cake\View\Helper\FormHelper::multiCheckbox() for creating multiple checkboxes.
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#creating-select-pickers
     */
    public function select(string $fieldName, iterable $options = [], array $attributes = []): string
    {
        $attributes += [
            'disabled' => null,
            'escape' => true,
            'hiddenField' => true,
            'multiple' => null,
            'secure' => true,
            'empty' => null,
        ];

        if ($attributes['empty'] === null && $attributes['multiple'] !== 'checkbox') {
            $required = $this->_getContext()->isRequired($fieldName);
            $attributes['empty'] = $required === null ? false : !$required;
        }

        if ($attributes['multiple'] === 'checkbox') {
            unset($attributes['multiple'], $attributes['empty']);

            return $this->multiCheckbox($fieldName, $options, $attributes);
        }

        unset($attributes['label']);

        // Secure the field if there are options, or it's a multi select.
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
            $hiddenAttributes = [
                'name' => $attributes['name'],
                'value' => '',
                'form' => $attributes['form'] ?? null,
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
     * - `label` - Either `false` to disable label around the widget or an array of attributes for
     *   the label tag. `selected` will be added to any classes e.g. `'class' => 'myclass'` where
     *   widget is checked
     *
     * Can be used in place of a select box with the multiple attribute.
     *
     * @param string $fieldName Name attribute of the SELECT
     * @param iterable $options Array of the OPTION elements
     *   (as 'value'=>'Text' pairs) to be used in the checkboxes element.
     * @param array $attributes The HTML attributes of the select element.
     * @return string Formatted SELECT element
     * @see \Cake\View\Helper\FormHelper::select() for supported option formats.
     */
    public function multiCheckbox(string $fieldName, iterable $options, array $attributes = []): string
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
                'disabled' => $attributes['disabled'] === true || $attributes['disabled'] === 'disabled',
            ];
            $hidden = $this->hidden($fieldName, $hiddenAttributes);
        }
        unset($attributes['hiddenField']);

        return $hidden . $this->widget('multicheckbox', $attributes);
    }

    /**
     * Returns a SELECT element for years
     *
     * ### Attributes:
     *
     * - `empty` - If true, the empty select option is shown. If a string,
     *   that string is displayed as the empty element.
     * - `order` - Ordering of year values in select options.
     *   Possible values 'asc', 'desc'. Default 'desc'
     * - `value` The selected value of the input.
     * - `max` The max year to appear in the select element.
     * - `min` The min year to appear in the select element.
     *
     * @param string $fieldName The field name.
     * @param array $options Options & attributes for the select elements.
     * @return string Completed year select input
     * @link https://book.cakephp.org/4/en/views/helpers/form.html#creating-year-inputs
     */
    public function year(string $fieldName, array $options = []): string
    {
        $options += [
            'empty' => true,
        ];
        $options = $this->_initInputField($fieldName, $options);
        unset($options['type']);

        return $this->widget('year', $options);
    }

    /**
     * Generate an input tag with type "month".
     *
     * ### Options:
     *
     * See dateTime() options.
     *
     * @param string $fieldName The field name.
     * @param array $options Array of options or HTML attributes.
     * @return string
     */
    public function month(string $fieldName, array $options = []): string
    {
        $options += [
            'value' => null,
        ];

        $options = $this->_initInputField($fieldName, $options);
        $options['type'] = 'month';

        return $this->widget('datetime', $options);
    }

    /**
     * Generate an input tag with type "datetime-local".
     *
     * ### Options:
     *
     * - `value` | `default` The default value to be used by the input.
     *   If set to `true` current datetime will be used.
     *
     * @param string $fieldName The field name.
     * @param array $options Array of options or HTML attributes.
     * @return string
     */
    public function dateTime(string $fieldName, array $options = []): string
    {
        $options += [
            'value' => null,
        ];
        $options = $this->_initInputField($fieldName, $options);
        $options['type'] = 'datetime-local';
        $options['fieldName'] = $fieldName;

        return $this->widget('datetime', $options);
    }

    /**
     * Generate an input tag with type "time".
     *
     * ### Options:
     *
     * See dateTime() options.
     *
     * @param string $fieldName The field name.
     * @param array $options Array of options or HTML attributes.
     * @return string
     */
    public function time(string $fieldName, array $options = []): string
    {
        $options += [
            'value' => null,
        ];
        $options = $this->_initInputField($fieldName, $options);
        $options['type'] = 'time';

        return $this->widget('datetime', $options);
    }

    /**
     * Generate an input tag with type "date".
     *
     * ### Options:
     *
     * See dateTime() options.
     *
     * @param string $fieldName The field name.
     * @param array $options Array of options or HTML attributes.
     * @return string
     */
    public function date(string $fieldName, array $options = []): string
    {
        $options += [
            'value' => null,
        ];

        $options = $this->_initInputField($fieldName, $options);
        $options['type'] = 'date';

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
    protected function _initInputField(string $field, array $options = []): array
    {
        $options += ['fieldName' => $field];

        if (!isset($options['secure'])) {
            $options['secure'] = $this->_View->getRequest()->getAttribute('formTokenData') === null ? false : true;
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
            $valOptions = [
                'default' => $options['default'] ?? null,
                'schemaDefault' => $options['schemaDefault'] ?? true,
            ];
            $options['val'] = $this->getSourceValue($field, $valOptions);
        }
        if (!isset($options['val']) && isset($options['default'])) {
            $options['val'] = $options['default'];
        }
        unset($options['value'], $options['default']);

        if ($context->hasError($field)) {
            $options = $this->addClass($options, $this->_config['errorClass']);
        }
        $isDisabled = $this->_isDisabled($options);
        if ($isDisabled) {
            $options['secure'] = self::SECURE_SKIP;
        }
        if ($options['secure'] === self::SECURE_SKIP) {
            return $options;
        }

        return $options;
    }

    /**
     * Determine if a field is disabled.
     *
     * @param array $options The option set.
     * @return bool Whether or not the field is disabled.
     */
    protected function _isDisabled(array $options): bool
    {
        if (!isset($options['disabled'])) {
            return false;
        }
        if (is_scalar($options['disabled'])) {
            return $options['disabled'] === true || $options['disabled'] === 'disabled';
        }
        if (!isset($options['options'])) {
            return false;
        }
        if (is_array($options['options'])) {
            // Simple list options
            $first = $options['options'][array_keys($options['options'])[0]];
            if (is_scalar($first)) {
                return array_diff($options['options'], $options['disabled']) === [];
            }
            // Complex option types
            if (is_array($first)) {
                $disabled = array_filter($options['options'], function ($i) use ($options) {
                    return in_array($i['value'], $options['disabled'], true);
                });

                return count($disabled) > 0;
            }
        }

        return false;
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
    public function addContextProvider(string $type, callable $check): void
    {
        $this->contextFactory()->addProvider($type, $check);
    }

    /**
     * Get the context instance for the current form set.
     *
     * If there is no active form null will be returned.
     *
     * @param \Cake\View\Form\ContextInterface|null $context Either the new context when setting, or null to get.
     * @return \Cake\View\Form\ContextInterface The context for the form.
     */
    public function context(?ContextInterface $context = null): ContextInterface
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
     * @return \Cake\View\Form\ContextInterface Context provider.
     * @throws \RuntimeException when the context class does not implement the
     *   ContextInterface.
     */
    protected function _getContext($data = []): ContextInterface
    {
        if (isset($this->_context) && empty($data)) {
            return $this->_context;
        }
        $data += ['entity' => null];

        return $this->_context = $this->contextFactory()
            ->get($this->_View->getRequest(), $data);
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
    public function addWidget(string $name, $spec): void
    {
        $this->_locator->add([$name => $spec]);
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
    public function widget(string $name, array $data = []): string
    {
        $secure = null;
        if (isset($data['secure'])) {
            $secure = $data['secure'];
            unset($data['secure']);
        }
        $widget = $this->_locator->get($name);
        $out = $widget->render($data, $this->context());
        if (
            $this->formProtector !== null &&
            isset($data['name']) &&
            $secure !== null &&
            $secure !== self::SECURE_SKIP
        ) {
            foreach ($widget->secureFields($data) as $field) {
                $this->formProtector->addField($field, $secure);
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
    public function resetTemplates(): void
    {
        $this->setTemplates($this->_defaultConfig['templates']);
    }

    /**
     * Event listeners.
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        return [];
    }

    /**
     * Gets the value sources.
     *
     * Returns a list, but at least one item, of valid sources, such as: `'context'`, `'data'` and `'query'`.
     *
     * @return string[] List of value sources.
     */
    public function getValueSources(): array
    {
        return $this->_valueSources;
    }

    /**
     * Validate value sources.
     *
     * @param string[] $sources A list of strings identifying a source.
     * @return void
     * @throws \InvalidArgumentException If sources list contains invalid value.
     */
    protected function validateValueSources(array $sources): void
    {
        $diff = array_diff($sources, $this->supportedValueSources);

        if ($diff) {
            throw new InvalidArgumentException(sprintf(
                'Invalid value source(s): %s. Valid values are: %s',
                implode(', ', $diff),
                implode(', ', $this->supportedValueSources)
            ));
        }
    }

    /**
     * Sets the value sources.
     *
     * You need to supply one or more valid sources, as a list of strings.
     * Order sets priority.
     *
     * @see FormHelper::$supportedValueSources for valid values.
     * @param string|string[] $sources A string or a list of strings identifying a source.
     * @return $this
     * @throws \InvalidArgumentException If sources list contains invalid value.
     */
    public function setValueSources($sources)
    {
        $sources = (array)$sources;

        $this->validateValueSources($sources);
        $this->_valueSources = $sources;

        return $this;
    }

    /**
     * Gets a single field value from the sources available.
     *
     * @param string $fieldname The fieldname to fetch the value for.
     * @param array $options The options containing default values.
     * @return mixed Field value derived from sources or defaults.
     */
    public function getSourceValue(string $fieldname, array $options = [])
    {
        $valueMap = [
            'data' => 'getData',
            'query' => 'getQuery',
        ];
        foreach ($this->getValueSources() as $valuesSource) {
            if ($valuesSource === 'context') {
                $val = $this->_getContext()->val($fieldname, $options);
                if ($val !== null) {
                    return $val;
                }
            }
            if (isset($valueMap[$valuesSource])) {
                $method = $valueMap[$valuesSource];
                $value = $this->_View->getRequest()->{$method}($fieldname);
                if ($value !== null) {
                    return $value;
                }
            }
        }

        return null;
    }
}
