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
namespace Cake\View\Form;

use Cake\Form\Form;
use Cake\Utility\Hash;

/**
 * Provides a context provider for {@link \Cake\Form\Form} instances.
 *
 * This context provider simply fulfils the interface requirements
 * that FormHelper has and allows access to the form data.
 */
class FormContext implements ContextInterface
{
    /**
     * The form object.
     *
     * @var \Cake\Form\Form
     */
    protected Form $_form;

    /**
     * Validator name.
     *
     * @var string|null
     */
    protected ?string $_validator = null;

    /**
     * Constructor.
     *
     * @param array $context Context info.
     *
     * Keys:
     *
     * - `entity` The Form class instance this context is operating on. **(required)**
     * - `validator` Optional name of the validation method to call on the Form object.
     */
    public function __construct(array $context)
    {
        assert(
            isset($context['entity']) && $context['entity'] instanceof Form,
            "`\$context['entity']` must be an instance of " . Form::class,
        );

        $this->_form = $context['entity'];
        $this->_validator = $context['validator'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getPrimaryKey(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function isPrimaryKey(string $field): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isCreate(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function val(string $field, array $options = []): mixed
    {
        $options += [
            'default' => null,
            'schemaDefault' => true,
        ];

        $val = $this->_form->getData($field);
        if ($val !== null) {
            return $val;
        }

        if ($options['default'] !== null || !$options['schemaDefault']) {
            return $options['default'];
        }

        return $this->_schemaDefault($field);
    }

    /**
     * Get default value from form schema for given field.
     *
     * @param string $field Field name.
     * @return mixed
     */
    protected function _schemaDefault(string $field): mixed
    {
        $field = $this->_form->getSchema()->field($field);
        if (!$field) {
            return null;
        }

        return $field['default'];
    }

    /**
     * @inheritDoc
     */
    public function isRequired(string $field): ?bool
    {
        $validator = $this->_form->getValidator($this->_validator);
        if (!$validator->hasField($field)) {
            return null;
        }
        if ($this->type($field) !== 'boolean') {
            return !$validator->isEmptyAllowed($field, $this->isCreate());
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredMessage(string $field): ?string
    {
        $parts = explode('.', $field);

        $validator = $this->_form->getValidator($this->_validator);
        $fieldName = array_pop($parts);
        if (!$validator->hasField($fieldName)) {
            return null;
        }

        $ruleset = $validator->field($fieldName);
        if (!$ruleset->isEmptyAllowed()) {
            return $validator->getNotEmptyMessage($fieldName);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getMaxLength(string $field): ?int
    {
        $validator = $this->_form->getValidator($this->_validator);
        if (!$validator->hasField($field)) {
            return null;
        }
        foreach ($validator->field($field)->rules() as $rule) {
            if ($rule->get('rule') === 'maxLength') {
                return $rule->get('pass')[0];
            }
        }

        $attributes = $this->attributes($field);
        if (empty($attributes['length'])) {
            return null;
        }

        return $attributes['length'];
    }

    /**
     * @inheritDoc
     */
    public function fieldNames(): array
    {
        return $this->_form->getSchema()->fields();
    }

    /**
     * @inheritDoc
     */
    public function type(string $field): ?string
    {
        return $this->_form->getSchema()->fieldType($field);
    }

    /**
     * @inheritDoc
     */
    public function attributes(string $field): array
    {
        return array_intersect_key(
            (array)$this->_form->getSchema()->field($field),
            array_flip(static::VALID_ATTRIBUTES),
        );
    }

    /**
     * @inheritDoc
     */
    public function hasError(string $field): bool
    {
        $errors = $this->error($field);

        return $errors !== [];
    }

    /**
     * @inheritDoc
     */
    public function error(string $field): array
    {
        return (array)Hash::get($this->_form->getErrors(), $field, []);
    }
}
