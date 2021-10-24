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

/**
 * Input widget for creating checkbox widgets.
 *
 * This class is usually used internally by `Cake\View\Helper\FormHelper`,
 * it but can be used to generate standalone checkboxes.
 */
class CheckboxWidget extends BasicWidget
{
    /**
     * Data defaults.
     *
     * @var array<string, mixed>
     */
    protected $defaults = [
        'name' => '',
        'value' => 1,
        'val' => null,
        'disabled' => false,
        'templateVars' => [],
    ];

    /**
     * Render a checkbox element.
     *
     * Data supports the following keys:
     *
     * - `name` - The name of the input.
     * - `value` - The value attribute. Defaults to '1'.
     * - `val` - The current value. If it matches `value` the checkbox will be checked.
     *   You can also use the 'checked' attribute to make the checkbox checked.
     * - `disabled` - Whether the checkbox should be disabled.
     *
     * Any other attributes passed in will be treated as HTML attributes.
     *
     * @param array<string, mixed> $data The data to create a checkbox with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string Generated HTML string.
     */
    public function render(array $data, ContextInterface $context): string
    {
        $data += $this->mergeDefaults($data, $context);

        if ($this->_isChecked($data)) {
            $data['checked'] = true;
        }
        unset($data['val']);

        $attrs = $this->_templates->formatAttributes(
            $data,
            ['name', 'value']
        );

        return $this->_templates->format('checkbox', [
            'name' => $data['name'],
            'value' => $data['value'],
            'templateVars' => $data['templateVars'],
            'attrs' => $attrs,
        ]);
    }

    /**
     * Checks whether the checkbox should be checked.
     *
     * @param array<string, mixed> $data Data to look at and determine checked state.
     * @return bool
     */
    protected function _isChecked(array $data): bool
    {
        if (array_key_exists('checked', $data)) {
            return (bool)$data['checked'];
        }

        return (string)$data['val'] === (string)$data['value'];
    }
}
