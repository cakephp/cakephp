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
 * Input widget class for generating a textarea control.
 *
 * This class is usually used internally by `Cake\View\Helper\FormHelper`,
 * it but can be used to generate standalone text areas.
 */
class TextareaWidget extends BasicWidget
{
    /**
     * Data defaults.
     *
     * @var array<string, mixed>
     */
    protected $defaults = [
        'val' => '',
        'name' => '',
        'escape' => true,
        'rows' => 5,
        'templateVars' => [],
    ];

    /**
     * Render a text area form widget.
     *
     * Data supports the following keys:
     *
     * - `name` - Set the input name.
     * - `val` - A string of the option to mark as selected.
     * - `escape` - Set to false to disable HTML escaping.
     *
     * All other keys will be converted into HTML attributes.
     *
     * @param array<string, mixed> $data The data to build a textarea with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string HTML elements.
     */
    public function render(array $data, ContextInterface $context): string
    {
        $data += $this->mergeDefaults($data, $context);

        if (
            !array_key_exists('maxlength', $data)
            && isset($data['fieldName'])
        ) {
            $data = $this->setMaxLength($data, $context, $data['fieldName']);
        }

        return $this->_templates->format('textarea', [
            'name' => $data['name'],
            'value' => $data['escape'] ? h($data['val']) : $data['val'],
            'templateVars' => $data['templateVars'],
            'attrs' => $this->_templates->formatAttributes(
                $data,
                ['name', 'val']
            ),
        ]);
    }
}
