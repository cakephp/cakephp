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

use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\View\HtmlStringable;
use Cake\View\View;
use Cake\View\Widget\TrustedRadioWidget;
use function Cake\Core\h;
use function Cake\I18n\__;

/**
 * Opt-in trusted HTML support that preserves the 5.x FormHelper signatures.
 *
 * @property \Cake\View\Helper\TrustedHtmlHelper $Html
 */
class TrustedFormHelper extends FormHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['Url', 'Html' => ['className' => TrustedHtmlHelper::class]];

    /**
     * @param \Cake\View\View $view The view using this helper.
     * @param array<string, mixed> $config Helper configuration.
     */
    public function __construct(View $view, array $config = [])
    {
        $this->_defaultWidgets['radio'] = [TrustedRadioWidget::class, 'nestingLabel'];
        parent::__construct($view, $config);
    }

    /**
     * Returns a formatted error message for given form field, '' if no errors.
     *
     * Uses the `error`, `errorList` and `errorItem` templates. The `errorList` and
     * `errorItem` templates are used to format multiple error messages per field.
     *
     * ### Options:
     *
     * - `escape` boolean - Whether to html escape the contents of the error.
     *
     * @param string $field A field name, like "modelname.fieldname"
     * @param array|string|null $text Error message as string or array of messages. If an array,
     *   it should be a hash of key names => messages.
     * @param array<string, mixed> $options See above.
     * @return string Formatted errors or ''.
     * @link https://book.cakephp.org/5/en/views/helpers/form.html#displaying-and-checking-errors
     */
    public function error(string $field, HtmlStringable|array|string|null $text = null, array $options = []): string
    {
        if (str_ends_with($field, '._ids')) {
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
                } elseif (!($e instanceof HtmlStringable) && isset($text[$e])) {
                    $tmp[] = $text[$e];
                } else {
                    $tmp[] = $e instanceof HtmlStringable ? (string)$e : $e;
                }
            }
            $text = $tmp;
        }

        if ($text !== null) {
            $error = $text;
        }

        if ($options['escape']) {
            $escape = static fn(mixed $message): mixed => $text !== null && $message instanceof HtmlStringable
                ? (string)$message
                : h($message);
            $error = is_array($error) ? array_map($escape, $error) : $escape($error);
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

        return $this->formatTemplate('error', [
            'content' => $error,
            'id' => $this->_domId($field) . '-error',
            'inputId' => $this->_domId($field),
        ]);
    }

    /**
     * Returns a formatted LABEL element for HTML forms.
     *
     * Will automatically generate a `for` attribute if one is not provided.
     *
     * ### Options
     *
     * - `for` - Set the for attribute. If it is not defined, the for attribute
     *   will be generated from the $fieldName parameter using FormHelper::_domId().
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
     * @param array<string, mixed> $options An array of HTML attributes.
     * @return string The formatted LABEL element
     * @link https://book.cakephp.org/5/en/views/helpers/form.html#creating-labels
     */
    public function label(string $fieldName, HtmlStringable|string|null $text = null, array $options = []): string
    {
        if ($text === null) {
            $text = $fieldName;
            if (str_ends_with($text, '._ids')) {
                $text = substr($text, 0, -5);
            }
            if (str_contains($text, '.')) {
                $fieldElements = explode('.', $text);
                $text = array_pop($fieldElements);
            }
            if (str_ends_with($text, '_id')) {
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
     * Generate a label for an input() call.
     *
     * $options can contain a hash of id overrides. These overrides will be
     * used instead of the generated values if present.
     *
     * @param string $fieldName The name of the field to generate label for.
     * @param array<string, mixed>|string|null $label Label text or array with label attributes.
     * @param array<string, mixed> $options Options for the label element.
     * @return string Generated label element
     */
    protected function _inputLabel(
        string $fieldName,
        HtmlStringable|array|string|null $label = null,
        array $options = [],
    ): string {
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
     * Creates a `<button>` tag.
     *
     * ### Options:
     *
     * - `type` - Value for "type" attribute of button. Defaults to "submit".
     * - `escapeTitle` - HTML entity encode the title of the button. Defaults to true.
     * - `escape` - HTML entity encode the attributes of button tag. Defaults to true.
     * - `confirm` - Confirm message to show. Form execution will only continue if confirmed then.
     * - Any other key is used as an HTML attribute on the generated `<button>` element.
     *
     * @param string $title The button's caption. Not automatically HTML encoded
     * @param array<string, mixed> $options Array of options and HTML attributes.
     * @return string An HTML button tag.
     * @link https://book.cakephp.org/5/en/views/helpers/form.html#creating-button-elements
     */
    public function button(HtmlStringable|string $title, array $options = []): string
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
     * - Other options are the same as for button(). HTML attributes are applied to the generated `<button>` element.
     * - `confirm` - Confirm message to show. Form execution will only continue if confirmed then.
     *
     * @param string $title The button's caption. Not automatically HTML encoded
     * @param array|string $url URL as string or array
     * @param array<string, mixed> $options Array of options and HTML attributes.
     * @return string An HTML button tag.
     * @link https://book.cakephp.org/5/en/views/helpers/form.html#creating-standalone-buttons-and-post-links
     */
    public function postButton(HtmlStringable|string $title, array|string $url, array $options = []): string
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
     * - Other options are the same as for HtmlHelper::link(). HTML attributes are applied to the generated `<a>`
     *   element.
     * - The option `onclick` will be replaced.
     *
     * @param string $title The content to be wrapped by <a> tags.
     * @param array|string|null $url Cake-relative URL or array of URL parameters, or
     *   external URL (starts with http://)
     * @param array<string, mixed> $options Array of HTML attributes.
     * @return string A form based `<a>` element.
     * @link https://book.cakephp.org/5/en/views/helpers/form.html#creating-standalone-buttons-and-post-links
     */
    public function postLink(HtmlStringable|string $title, array|string|null $url = null, array $options = []): string
    {
        $options += ['block' => $this->getConfig('defaultPostLinkBlock'), 'confirm' => null];

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
            'method' => 'post',
        ];
        $hiddenClass = $this->templater()->get('hiddenClass');
        if ($hiddenClass === '' || $hiddenClass === null) {
            // HTML5 `hidden` boolean attribute — functionally equivalent to
            // `style="display:none;"` but strict-CSP compatible.
            $formOptions['hidden'] = true;
        } else {
            $formOptions['class'] = $hiddenClass;
        }
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

        $url = '#';
        $onClick = 'document.' . $formName . '.requestSubmit();';
        if ($confirmMessage) {
            $onClick = $this->_confirm($onClick, '');
            $onClick .= 'event.returnValue = false; return false;';
            $onClick = $this->templater()->format('confirmJs', [
                'confirmMessage' => h($confirmMessage),
                'formName' => $formName,
                'confirm' => $onClick,
            ]);
            $options['data-confirm-message'] = $confirmMessage;
        } else {
            $onClick .= ' event.returnValue = false; return false;';
        }

        $script = null;
        if ($this->_View->getRequest()->getAttribute('cspScriptNonce')) {
            $options['id'] ??= $this->_domId('link-' . $formName);
            $script = $this->templater()->format('postLinkJs', [
                'linkId' => $options['id'],
                'content' => $onClick,
            ]);
            $script = $this->Html->scriptBlock($script, ['block' => $options['block']]);
        } else {
            $options['onclick'] = $onClick;
        }

        unset($options['block']);

        $out .= $this->Html->link($title, $url, $options) . $script;

        return $out;
    }

    /**
     * Creates an HTML link, that submits a form to the given URL using the DELETE method.
     *  Requires javascript to be enabled in browser.
     *
     * @param string $title The content to be wrapped by <a> tags.
     * @param array|string|null $url Cake-relative URL or array of URL parameters, or
     *    external URL (starts with http://)
     * @param array<string, mixed> $options Array of HTML attributes.
     * @return string A form based `<a>` element.
     * @see \Cake\View\Helper\FormHelper::postLink() for options and details.
     * @link https://book.cakephp.org/5/en/views/helpers/form.html#creating-standalone-buttons-and-post-links
     * @since 5.2.0
     */
    public function deleteLink(HtmlStringable|string $title, array|string|null $url = null, array $options = []): string
    {
        $options['method'] = 'delete';

        return $this->postLink($title, $url, $options);
    }
}
