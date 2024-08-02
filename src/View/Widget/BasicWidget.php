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
    /**
     * StringTemplate instance.
     *
     * @var \Cake\View\StringTemplate
     */
    protected StringTemplate $_templates;

    /**
     * Data defaults.
     *
     * @var array<string, mixed>
     */
    protected array $defaults = [
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
     * @param array<string, mixed> $data The data to build an input with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string
     */
    public function render(array $data, ContextInterface $context): string
    {
        $data = $this->mergeDefaults($data, $context);

        $data['value'] = $data['val'];
        unset($data['val']);
        if ($data['value'] === false) {
            // explicitly convert to 0 to avoid empty string which is marshaled as null
            $data['value'] = '0';
        }

        $fieldName = $data['fieldName'] ?? null;
        if ($fieldName) {
            if ($data['type'] === 'number' && !isset($data['step'])) {
                $data = $this->setStep($data, $context, $fieldName);
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

    /**
     * Merge default values with supplied data.
     *
     * @param array<string, mixed> $data Data array
     * @param \Cake\View\Form\ContextInterface $context Context instance.
     * @return array<string, mixed> Updated data array.
     */
    protected function mergeDefaults(array $data, ContextInterface $context): array
    {
        $data += $this->defaults;

        if (isset($data['fieldName']) && !array_key_exists('required', $data)) {
            $data = $this->setRequired($data, $context, $data['fieldName']);
        }

        return $data;
    }

    /**
     * Set value for "required" attribute if applicable.
     *
     * @param array<string, mixed> $data Data array
     * @param \Cake\View\Form\ContextInterface $context Context instance.
     * @param string $fieldName Field name.
     * @return array<string, mixed> Updated data array.
     */
    protected function setRequired(array $data, ContextInterface $context, string $fieldName): array
    {
        if (
            empty($data['disabled'])
            && (
                (isset($data['type'])
                    && $data['type'] !== 'hidden'
                )
                || !isset($data['type'])
            )
            && $context->isRequired($fieldName)
        ) {
            $data['required'] = true;
        }

        return $data;
    }

    /**
     * Set value for "maxlength" attribute if applicable.
     *
     * @param array<string, mixed> $data Data array
     * @param \Cake\View\Form\ContextInterface $context Context instance.
     * @param string $fieldName Field name.
     * @return array<string, mixed> Updated data array.
     */
    protected function setMaxLength(array $data, ContextInterface $context, string $fieldName): array
    {
        $maxLength = $context->getMaxLength($fieldName);
        if ($maxLength !== null) {
            $data['maxlength'] = min($maxLength, 100000);
        }

        return $data;
    }

    /**
     * Set value for "step" attribute if applicable.
     *
     * @param array<string, mixed> $data Data array
     * @param \Cake\View\Form\ContextInterface $context Context instance.
     * @param string $fieldName Field name.
     * @return array<string, mixed> Updated data array.
     */
    protected function setStep(array $data, ContextInterface $context, string $fieldName): array
    {
        $dbType = $context->type($fieldName);
        $fieldDef = $context->attributes($fieldName);

        if ($dbType === 'decimal' && isset($fieldDef['precision'])) {
            $decimalPlaces = $fieldDef['precision'];
            $data['step'] = sprintf('%.' . $decimalPlaces . 'F', pow(10, -1 * $decimalPlaces));
        } elseif ($dbType === 'float') {
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
