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
namespace Cake\Form;

/**
 * Contains the schema information for Form instances.
 */
class Schema
{
    /**
     * The fields in this schema.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $_fields = [];

    /**
     * The default values for fields.
     *
     * @var array<string, mixed>
     */
    protected array $_fieldDefaults = [
        'type' => null,
        'length' => null,
        'precision' => null,
        'default' => null,
    ];

    /**
     * Add multiple fields to the schema.
     *
     * @param array<string, array<string, mixed>|string> $fields The fields to add.
     * @return $this
     */
    public function addFields(array $fields)
    {
        foreach ($fields as $name => $attrs) {
            $this->addField($name, $attrs);
        }

        return $this;
    }

    /**
     * Adds a field to the schema.
     *
     * @param string $name The field name.
     * @param array<string, mixed>|string $attrs The attributes for the field, or the type
     *   as a string.
     * @return $this
     */
    public function addField(string $name, array|string $attrs)
    {
        if (is_string($attrs)) {
            $attrs = ['type' => $attrs];
        }
        $attrs = array_intersect_key($attrs, $this->_fieldDefaults);
        $this->_fields[$name] = $attrs + $this->_fieldDefaults;

        return $this;
    }

    /**
     * Removes a field to the schema.
     *
     * @param string $name The field to remove.
     * @return $this
     */
    public function removeField(string $name)
    {
        unset($this->_fields[$name]);

        return $this;
    }

    /**
     * Get the list of fields in the schema.
     *
     * @return array<string> The list of field names.
     */
    public function fields(): array
    {
        return array_keys($this->_fields);
    }

    /**
     * Get the attributes for a given field.
     *
     * @param string $name The field name.
     * @return array<string, mixed>|null The attributes for a field, or null.
     */
    public function field(string $name): ?array
    {
        return $this->_fields[$name] ?? null;
    }

    /**
     * Get the type of the named field.
     *
     * @param string $name The name of the field.
     * @return string|null Either the field type or null if the
     *   field does not exist.
     */
    public function fieldType(string $name): ?string
    {
        $field = $this->field($name);
        if (!$field) {
            return null;
        }

        return $field['type'];
    }

    /**
     * Get the printable version of this object
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            '_fields' => $this->_fields,
        ];
    }
}
