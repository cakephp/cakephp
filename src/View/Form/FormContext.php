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
    protected $_form;

    /**
     * Constructor.
     *
     * @param array $context Context info.
     */
    public function __construct(array $context)
    {
        $context += [
            'entity' => null,
        ];
        $this->_form = $context['entity'];
    }

    /**
     * Get the fields used in the context as a primary key.
     *
     * @return array<string>
     * @deprecated 4.0.0 Renamed to {@link getPrimaryKey()}.
     */
    public function primaryKey(): array
    {
        deprecationWarning('`FormContext::primaryKey()` is deprecated. Use `FormContext::getPrimaryKey()`.');

        return [];
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
    public function val(string $field, array $options = [])
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
    protected function _schemaDefault(string $field)
    {
        $field = $this->_form->getSchema()->field($field);
        if ($field) {
            return $field['default'];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function isRequired(string $field): ?bool
    {
        $validator = $this->_form->getValidator();
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

        $validator = $this->_form->getValidator();
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
        $validator = $this->_form->getValidator();
        if (!$validator->hasField($field)) {
            return null;
        }
        foreach ($validator->field($field)->rules() as $rule) {
            if ($rule->get('rule') === 'maxLength') {
                return $rule->get('pass')[0];
            }
        }

        $attributes = $this->attributes($field);
        if (!empty($attributes['length'])) {
            return $attributes['length'];
        }

        return null;
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
            array_flip(static::VALID_ATTRIBUTES)
        );
    }

    /**
     * @inheritDoc
     */
    public function hasError(string $field): bool
    {
        $errors = $this->error($field);

        return count($errors) > 0;
    }

    /**
     * @inheritDoc
     */
    public function error(string $field): array
    {
        return (array)Hash::get($this->_form->getErrors(), $field, []);
    }
}
