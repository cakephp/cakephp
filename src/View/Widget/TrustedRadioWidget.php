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
use Cake\View\HtmlStringable;
use function Cake\Core\h;

/**
 * Opt-in trusted HTML support that preserves the 5.x RadioWidget signatures.
 */
class TrustedRadioWidget extends RadioWidget
{
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
        HtmlStringable|array|string|int $text,
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
}
