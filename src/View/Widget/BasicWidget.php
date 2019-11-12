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
 * Basic input class.
 *
 * This input class can be used to render basic simple
 * input elements like hidden, text, email, tel and other
 * types.
 */
class BasicWidget implements WidgetInterface
{
    use HtmlAttributesTrait;

    /**
     * StringTemplate instance.
     *
     * @var \Cake\View\StringTemplate
     */
    protected $_templates;

    /**
     * Data defaults.
     */
    protected $defaults = [
        'name' => '',
        'val' => null,
        'type' => 'text',
        'escape' => true,
        'templateVars' => [],
    ];

    /**
     * Constructor.
     *
     * @param \Cake\View\StringTemplate $templates Templates list.
     */
    public function __construct(StringTemplate $templates)
    {
        $this->_templates = $templates;
    }

    /**
     * Render a text widget or other simple widget like email/tel/number.
     *
     * This method accepts a number of keys:
     *
     * - `name` The name attribute.
     * - `val` The value attribute.
     * - `escape` Set to false to disable escaping on all attributes.
     *
     * Any other keys provided in $data will be converted into HTML attributes.
     *
     * @param array $data The data to build an input with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string
     */
    public function render(array $data, ContextInterface $context): string
    {
        $data = $this->mergeDefaults($data, $context);

        $data['value'] = $data['val'];
        unset($data['val']);

        $fieldName = $data['fieldName'] ?? null;
        if ($fieldName) {
            if (
                $data['type'] === 'number'
                && !isset($data['step'])
            ) {
                $data = $this->setStep($data, $context, $data['fieldName']);
            }

            $typesWithMaxLength = ['text', 'email', 'tel', 'url', 'search'];
            if (
                !array_key_exists('maxlength', $data)
                && in_array($data['type'], $typesWithMaxLength, true)
            ) {
                $data = $this->setMaxLength($data, $context, $fieldName);
            }
        }

        return $this->_templates->format('input', [
            'name' => $data['name'],
            'type' => $data['type'],
            'templateVars' => $data['templateVars'],
            'attrs' => $this->_templates->formatAttributes(
                $data,
                ['name', 'type']
            ),
        ]);
    }

    protected function mergeDefaults(array $data, ContextInterface $context): array
    {
        $data += $this->defaults;

        if (isset($data['fieldName']) && !isset($data['required'])) {
            $data = $this->setRequired($data, $context, $data['fieldName']);
        }

        return $data;
    }

    protected function setStep(array $data, ContextInterface $context, string $fieldName): array
    {
        $type = $context->type($fieldName);
        $fieldDef = $context->attributes($fieldName);

        if ($type === 'decimal' && isset($fieldDef['precision'])) {
            $decimalPlaces = $fieldDef['precision'];
            $data['step'] = sprintf('%.' . $decimalPlaces . 'F', pow(10, -1 * $decimalPlaces));
        } elseif ($type === 'float') {
            $data['step'] = 'any';
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function secureFields(array $data): array
    {
        if (!isset($data['name']) || $data['name'] === '') {
            return [];
        }

        return [$data['name']];
    }
}
