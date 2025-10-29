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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Widget;

use Cake\View\Form\ContextInterface;
use Cake\View\Helper\IdGeneratorTrait;
use Cake\View\StringTemplate;
use Traversable;
use function Cake\Core\h;

/**
 * Input widget class for generating a set of radio buttons.
 *
 * This class is usually used internally by `Cake\View\Helper\FormHelper`,
 * it but can be used to generate standalone radio buttons.
 */
class RadioWidget extends BasicWidget
{
    use IdGeneratorTrait;

    /**
     * Data defaults.
     *
     * @var array<string, mixed>
     */
    protected array $defaults = [
        'name' => '',
        'options' => [],
        'disabled' => null,
        'val' => null,
        'escape' => true,
        'label' => true,
        'empty' => false,
        'idPrefix' => null,
        'templateVars' => [],
        'nestedInput' => true,
    ];

    /**
     * Label instance.
     *
     * @var \Cake\View\Widget\LabelWidget
     */
    protected LabelWidget $_label;

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
    public function __construct(StringTemplate $templates, LabelWidget $label)
    {
        parent::__construct($templates);

        $this->defaults['nestedInput'] = $label instanceof NestingLabelWidget;
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
     * @param array<string, mixed> $data The data to build radio buttons with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string
     */
    public function render(array $data, ContextInterface $context): string
    {
        $data += $this->mergeDefaults($data, $context);

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
     * @param array<string, mixed> $radio Radio info.
     * @param array|string|true|null $disabled The disabled values.
     * @return bool
     */
    protected function _isDisabled(array $radio, array|string|bool|null $disabled): bool
    {
        if (!$disabled) {
            return false;
        }
        if ($disabled === true) {
            return true;
        }
        $isNumeric = is_numeric($radio['value']);

        return !is_array($disabled) || in_array((string)$radio['value'], $disabled, !$isNumeric);
    }

    /**
     * Renders a single radio input and label.
     *
     * @param string|int $val The value of the radio input.
     * @param array<string, mixed>|string|int $text The label text, or complex radio type.
     * @param array<string, mixed> $data Additional options for input generation.
     * @param \Cake\View\Form\ContextInterface $context The form context
     * @return string
     */
    protected function _renderInput(
        string|int $val,
        array|string|int $text,
        array $data,
        ContextInterface $context,
    ): string {
        $escape = $data['escape'];
        if (is_array($text) && isset($text['text'], $text['value'])) {
            $radio = $text;
        } else {
            $radio = ['value' => $val, 'text' => $text];
        }
        $radio['name'] = $data['name'];

        $radio['templateVars'] ??= [];
        if (!empty($data['templateVars'])) {
            $radio['templateVars'] = array_merge($data['templateVars'], $radio['templateVars']);
        }

        if (empty($radio['id'])) {
            if (isset($data['id'])) {
                $radio['id'] = $data['id'] . '-' . rtrim(
                    $this->_idSuffix((string)$radio['value']),
                    '-',
                );
            } else {
                $radio['id'] = $this->_id((string)$radio['name'], (string)$radio['value']);
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
            $selectedClass = $this->_templates->format('selectedClass', []);
            $data['label'] = $this->_templates->addClass((array)$data['label'], $selectedClass);
        }

        $radio['disabled'] = $this->_isDisabled($radio, $data['disabled']);
        if (!empty($data['required'])) {
            $radio['required'] = true;
        }
        if (!empty($data['form'])) {
            $radio['form'] = $data['form'];
        }

        $nestedInput = $data['nestedInput'];
        unset($data['nestedInput']);

        $input = $this->_templates->format('radio', [
            'name' => $radio['name'],
            'value' => $escape ? h($radio['value']) : $radio['value'],
            'templateVars' => $radio['templateVars'],
            'attrs' => $this->_templates->formatAttributes(
                $radio + $data,
                ['name', 'value', 'text', 'options', 'label', 'val', 'type'],
            ),
        ]);

        if (
            $data['label'] === false &&
            ($nestedInput || !str_contains($this->_templates->get('radioWrapper'), '{{input}}'))
        ) {
            $label = $input;
            $input = '';
        } else {
            $labelInput = $input;
            if ($nestedInput && (!isset($data['label']['input']) || $data['label']['input'] !== false)) {
                $input = '';
            } else {
                $labelInput = '';
            }

            $label = $this->_renderLabel(
                $radio,
                $data['label'],
                $labelInput,
                $context,
                $escape,
            );
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
     * @param array<string, mixed> $radio The input properties.
     * @param array<string, mixed>|string|bool|null $label The properties for a label.
     * @param string $input The input widget.
     * @param \Cake\View\Form\ContextInterface $context The form context.
     * @param bool $escape Whether to HTML escape the label.
     * @return string|false Generated label.
     */
    protected function _renderLabel(
        array $radio,
        array|string|bool|null $label,
        string $input,
        ContextInterface $context,
        bool $escape,
    ): string|false {
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
}
