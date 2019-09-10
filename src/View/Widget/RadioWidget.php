<?php
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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Widget;

use Cake\View\Form\ContextInterface;
use Cake\View\Helper\IdGeneratorTrait;
use Traversable;

/**
 * Input widget class for generating a set of radio buttons.
 *
 * This class is intended as an internal implementation detail
 * of Cake\View\Helper\FormHelper and is not intended for direct use.
 */
class RadioWidget implements WidgetInterface
{
    use IdGeneratorTrait;

    /**
     * Template instance.
     *
     * @var \Cake\View\StringTemplate
     */
    protected $_templates;

    /**
     * Label instance.
     *
     * @var \Cake\View\Widget\LabelWidget
     */
    protected $_label;

    /**
     * Constructor
     *
     * This class uses a few templates:
     *
     * - `radio` Used to generate the input for a radio button.
     *   Can use the following variables `name`, `value`, `attrs`.
     * - `radioWrapper` Used to generate the container element for
     *   the radio + input element. Can use the `input` and `label`
     *   variables.
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
     * Render a set of radio buttons.
     *
     * Data supports the following keys:
     *
     * - `name` - Set the input name.
     * - `options` - An array of options. See below for more information.
     * - `disabled` - Either true or an array of inputs to disable.
     *    When true, the select element will be disabled.
     * - `val` - A string of the option to mark as selected.
     * - `label` - Either false to disable label generation, or
     *   an array of attributes for all labels.
     * - `required` - Set to true to add the required attribute
     *   on all generated radios.
     * - `idPrefix` Prefix for generated ID attributes.
     *
     * @param array $data The data to build radio buttons with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string
     */
    public function render(array $data, ContextInterface $context)
    {
        $data += [
            'name' => '',
            'options' => [],
            'disabled' => null,
            'val' => null,
            'escape' => true,
            'label' => true,
            'empty' => false,
            'idPrefix' => null,
            'templateVars' => [],
        ];
        if ($data['options'] instanceof Traversable) {
            $options = iterator_to_array($data['options']);
        } else {
            $options = (array)$data['options'];
        }

        if (!empty($data['empty'])) {
            $empty = $data['empty'] === true ? 'empty' : $data['empty'];
            $options = ['' => $empty] + $options;
        }
        unset($data['empty']);

        $this->_idPrefix = $data['idPrefix'];
        $this->_clearIds();
        $opts = [];
        foreach ($options as $val => $text) {
            $opts[] = $this->_renderInput($val, $text, $data, $context);
        }

        return implode('', $opts);
    }

    /**
     * Disabled attribute detection.
     *
     * @param array $radio Radio info.
     * @param array|true|null $disabled The disabled values.
     * @return bool
     */
    protected function _isDisabled($radio, $disabled)
    {
        if (!$disabled) {
            return false;
        }
        if ($disabled === true) {
            return true;
        }
        $isNumeric = is_numeric($radio['value']);

        return (!is_array($disabled) || in_array((string)$radio['value'], $disabled, !$isNumeric));
    }

    /**
     * Renders a single radio input and label.
     *
     * @param string|int $val The value of the radio input.
     * @param string|array $text The label text, or complex radio type.
     * @param array $data Additional options for input generation.
     * @param \Cake\View\Form\ContextInterface $context The form context
     * @return string
     */
    protected function _renderInput($val, $text, $data, $context)
    {
        $escape = $data['escape'];
        if (is_int($val) && isset($text['text'], $text['value'])) {
            $radio = $text;
        } else {
            $radio = ['value' => $val, 'text' => $text];
        }
        $radio['name'] = $data['name'];

        if (!isset($radio['templateVars'])) {
            $radio['templateVars'] = [];
        }
        if (!empty($data['templateVars'])) {
            $radio['templateVars'] = array_merge($data['templateVars'], $radio['templateVars']);
        }

        if (empty($radio['id'])) {
            if (isset($data['id'])) {
                $radio['id'] = $data['id'] . '-' . trim(
                    $this->_idSuffix($radio['value']),
                    '-'
                );
            } else {
                $radio['id'] = $this->_id($radio['name'], $radio['value']);
            }
        }
        if (isset($data['val']) && is_bool($data['val'])) {
            $data['val'] = $data['val'] ? 1 : 0;
        }
        if (isset($data['val']) && (string)$data['val'] === (string)$radio['value']) {
            $radio['checked'] = true;
            $radio['templateVars']['activeClass'] = 'active';
        }

        if (!is_bool($data['label']) && isset($radio['checked']) && $radio['checked']) {
            $data['label'] = $this->_templates->addClass($data['label'], 'selected');
        }

        $radio['disabled'] = $this->_isDisabled($radio, $data['disabled']);
        if (!empty($data['required'])) {
            $radio['required'] = true;
        }
        if (!empty($data['form'])) {
            $radio['form'] = $data['form'];
        }

        $input = $this->_templates->format('radio', [
            'name' => $radio['name'],
            'value' => $escape ? h($radio['value']) : $radio['value'],
            'templateVars' => $radio['templateVars'],
            'attrs' => $this->_templates->formatAttributes($radio + $data, ['name', 'value', 'text', 'options', 'label', 'val', 'type']),
        ]);

        $label = $this->_renderLabel(
            $radio,
            $data['label'],
            $input,
            $context,
            $escape
        );

        if ($label === false &&
            strpos($this->_templates->get('radioWrapper'), '{{input}}') === false
        ) {
            $label = $input;
        }

        return $this->_templates->format('radioWrapper', [
            'input' => $input,
            'label' => $label,
            'templateVars' => $data['templateVars'],
        ]);
    }

    /**
     * Renders a label element for a given radio button.
     *
     * In the future this might be refactored into a separate widget as other
     * input types (multi-checkboxes) will also need labels generated.
     *
     * @param array $radio The input properties.
     * @param array|string|false $label The properties for a label.
     * @param string $input The input widget.
     * @param \Cake\View\Form\ContextInterface $context The form context.
     * @param bool $escape Whether or not to HTML escape the label.
     * @return string|bool Generated label.
     */
    protected function _renderLabel($radio, $label, $input, $context, $escape)
    {
        if (isset($radio['label'])) {
            $label = $radio['label'];
        } elseif ($label === false) {
            return false;
        }
        $labelAttrs = is_array($label) ? $label : [];
        $labelAttrs += [
            'for' => $radio['id'],
            'escape' => $escape,
            'text' => $radio['text'],
            'templateVars' => $radio['templateVars'],
            'input' => $input,
        ];

        return $this->_label->render($labelAttrs, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function secureFields(array $data)
    {
        return [$data['name']];
    }
}
