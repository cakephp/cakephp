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
use Cake\View\StringTemplate;

/**
 * Form 'widget' for creating labels.
 *
 * Generally this element is used by other widgets,
 * and FormHelper itself.
 */
class LabelWidget implements WidgetInterface
{
    /**
     * Templates
     *
     * @var \Cake\View\StringTemplate
     */
    protected $_templates;

    /**
     * The template to use.
     *
     * @var string
     */
    protected $_labelTemplate = 'label';

    /**
     * Constructor.
     *
     * This class uses the following template:
     *
     * - `label` Used to generate the label for a radio button.
     *   Can use the following variables `attrs`, `text` and `input`.
     *
     * @param \Cake\View\StringTemplate $templates Templates list.
     */
    public function __construct(StringTemplate $templates)
    {
        $this->_templates = $templates;
    }

    /**
     * Render a label widget.
     *
     * Accepts the following keys in $data:
     *
     * - `text` The text for the label.
     * - `input` The input that can be formatted into the label if the template allows it.
     * - `escape` Set to false to disable HTML escaping.
     *
     * All other attributes will be converted into HTML attributes.
     *
     * @param array $data Data array.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string
     */
    public function render(array $data, ContextInterface $context): string
    {
        $data += [
            'text' => '',
            'input' => '',
            'hidden' => '',
            'escape' => true,
            'templateVars' => [],
        ];

        return $this->_templates->format($this->_labelTemplate, [
            'text' => $data['escape'] ? h($data['text']) : $data['text'],
            'input' => $data['input'],
            'hidden' => $data['hidden'],
            'templateVars' => $data['templateVars'],
            'attrs' => $this->_templates->formatAttributes($data, ['text', 'input', 'hidden']),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function secureFields(array $data): array
    {
        return [];
    }
}
