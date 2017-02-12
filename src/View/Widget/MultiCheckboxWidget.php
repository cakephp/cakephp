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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Widget;

use Cake\View\Form\ContextInterface;
use Cake\View\Helper\IdGeneratorTrait;

/**
 * Input widget class for generating multiple checkboxes.
 */
class MultiCheckboxWidget implements WidgetInterface
{

    use IdGeneratorTrait;

    /**
     * Template instance to use.
     *
     * @var \Cake\View\StringTemplate
     */
    protected $_templates;

    /**
     * Label widget instance.
     *
     * @var \Cake\View\Widget\LabelWidget
     */
    protected $_label;

    /**
     * Render multi-checkbox widget.
     *
     * This class uses the following templates:
     *
     * - `checkbox` Renders checkbox input controls. Accepts
     *   the `name`, `value` and `attrs` variables.
     * - `checkboxWrapper` Renders the containing div/element for
     *   a checkbox and its label. Accepts the `input`, and `label`
     *   variables.
     * - `multicheckboxWrapper` Renders a wrapper around grouped inputs.
     * - `multicheckboxTitle` Renders the title element for grouped inputs.
     *
     * @param \Cake\View\StringTemplate $templates Templates list.
     * @param \Cake\View\Widget\LabelWidget $label Label widget instance.
     */
    public function __construct($templates, $label)
    {
        $this->_templates = $templates;
        $this->_label = $label;
    }

    /**
     * Render multi-checkbox widget.
     *
     * Data supports the following options.
     *
     * - `name` The name attribute of the inputs to create.
     *   `[]` will be appended to the name.
     * - `options` An array of options to create checkboxes out of.
     * - `val` Either a string/integer or array of values that should be
     *   checked. Can also be a complex options set.
     * - `disabled` Either a boolean or an array of checkboxes to disable.
     * - `escape` Set to false to disable HTML escaping.
     * - `options` An associative array of value=>labels to generate options for.
     * - `idPrefix` Prefix for generated ID attributes.
     *
     * ### Options format
     *
     * The options option can take a variety of data format depending on
     * the complexity of HTML you want generated.
     *
     * You can generate simple options using a basic associative array:
     *
     * ```
     * 'options' => ['elk' => 'Elk', 'beaver' => 'Beaver']
     * ```
     *
     * If you need to define additional attributes on your option elements
     * you can use the complex form for options:
     *
     * ```
     * 'options' => [
     *   ['value' => 'elk', 'text' => 'Elk', 'data-foo' => 'bar'],
     * ]
     * ```
     *
     * This form **requires** that both the `value` and `text` keys be defined.
     * If either is not set options will not be generated correctly.
     *
     * @param array $data The data to generate a checkbox set with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string
     */
    public function render(array $data, ContextInterface $context)
    {
        $data += [
            'name' => '',
            'escape' => true,
            'options' => [],
            'disabled' => null,
            'val' => null,
            'idPrefix' => null,
            'templateVars' => [],
            'label' => true
        ];
        $this->_idPrefix = $data['idPrefix'];
        $this->_clearIds();

        return implode('', $this->_renderInputs($data, $context));
    }

    /**
     * Render the checkbox inputs.
     *
     * @param array $data The data array defining the checkboxes.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return array An array of rendered inputs.
     */
    protected function _renderInputs($data, $context)
    {
        $out = [];
        foreach ($data['options'] as $key => $val) {
            // Grouped inputs in a fieldset.
            if (is_string($key) && is_array($val) && !isset($val['text'], $val['value'])) {
                $inputs = $this->_renderInputs(['options' => $val] + $data, $context);
                $title = $this->_templates->format('multicheckboxTitle', ['text' => $key]);
                $out[] = $this->_templates->format('multicheckboxWrapper', [
                    'content' => $title . implode('', $inputs)
                ]);
                continue;
            }

            // Standard inputs.
            $checkbox = [
                'value' => $key,
                'text' => $val,
            ];
            if (is_array($val) && isset($val['text'], $val['value'])) {
                $checkbox = $val;
            }
            if (!isset($checkbox['templateVars'])) {
                $checkbox['templateVars'] = $data['templateVars'];
            }
            if (!isset($checkbox['label'])) {
                $checkbox['label'] = $data['label'];
            }
            if (!empty($data['templateVars'])) {
                $checkbox['templateVars'] = array_merge($data['templateVars'], $checkbox['templateVars']);
            }
            $checkbox['name'] = $data['name'];
            $checkbox['escape'] = $data['escape'];
            $checkbox['checked'] = $this->_isSelected($checkbox['value'], $data['val']);
            $checkbox['disabled'] = $this->_isDisabled($checkbox['value'], $data['disabled']);
            if (empty($checkbox['id'])) {
                $checkbox['id'] = $this->_id($checkbox['name'], $checkbox['value']);
            }
            $out[] = $this->_renderInput($checkbox + $data, $context);
        }

        return $out;
    }

    /**
     * Render a single checkbox & wrapper.
     *
     * @param array $checkbox An array containing checkbox key/value option pairs
     * @param \Cake\View\Form\ContextInterface $context Context object.
     * @return string
     */
    protected function _renderInput($checkbox, $context)
    {
        $input = $this->_templates->format('checkbox', [
            'name' => $checkbox['name'] . '[]',
            'value' => $checkbox['escape'] ? h($checkbox['value']) : $checkbox['value'],
            'templateVars' => $checkbox['templateVars'],
            'attrs' => $this->_templates->formatAttributes(
                $checkbox,
                ['name', 'value', 'text', 'options', 'label', 'val', 'type', 'hiddenField']
            )
        ]);

        if ($checkbox['label'] === false && strpos($this->_templates->get('radioWrapper'), '{{input}}') === false) {
            $label = $input;
        } else {
            $labelAttrs = [
                'for' => $checkbox['id'],
                'escape' => $checkbox['escape'],
                'text' => $checkbox['text'],
                'templateVars' => $checkbox['templateVars'],
                'input' => $input
            ];

            if (is_array($checkbox['label'])) {
                $labelAttrs += $checkbox['label'];
            }

            if ($checkbox['checked']) {
                $labelAttrs = $this->_templates->addClass($labelAttrs, 'selected');
            }

            $label = $this->_label->render($labelAttrs, $context);
        }

        return $this->_templates->format('checkboxWrapper', [
            'templateVars' => $checkbox['templateVars'],
            'label' => $label,
            'input' => $input
        ]);
    }

    /**
     * Helper method for deciding what options are selected.
     *
     * @param string $key The key to test.
     * @param array|string|null $selected The selected values.
     * @return bool
     */
    protected function _isSelected($key, $selected)
    {
        if ($selected === null) {
            return false;
        }
        $isArray = is_array($selected);
        if (!$isArray) {
            return (string)$key === (string)$selected;
        }
        $strict = !is_numeric($key);

        return in_array((string)$key, $selected, $strict);
    }

    /**
     * Helper method for deciding what options are disabled.
     *
     * @param string $key The key to test.
     * @param array|null $disabled The disabled values.
     * @return bool
     */
    protected function _isDisabled($key, $disabled)
    {
        if ($disabled === null || $disabled === false) {
            return false;
        }
        if ($disabled === true || is_string($disabled)) {
            return true;
        }
        $strict = !is_numeric($key);

        return in_array((string)$key, $disabled, $strict);
    }

    /**
     * {@inheritDoc}
     */
    public function secureFields(array $data)
    {
        return [$data['name']];
    }
}
