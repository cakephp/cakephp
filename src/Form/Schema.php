<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
     * @var array
     */
    protected $_fields = [];

    /**
     * The default values for fields.
     *
     * @var array
     */
    protected $_fieldDefaults = [
        'type' => null,
        'length' => null,
        'precision' => null,
    ];

    /**
     * Add multiple fields to the schema.
     *
     * @param array $fields The fields to add.
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
     * @param string|array $attrs The attributes for the field, or the type
     *   as a string.
     * @return $this
     */
    public function addField($name, $attrs)
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
    public function removeField($name)
    {
        unset($this->_fields[$name]);
        return $this;
    }

    /**
     * Get the list of fields in the schema.
     *
     * @return array The list of field names.
     */
    public function fields()
    {
        return array_keys($this->_fields);
    }

    /**
     * Get the attributes for a given field.
     *
     * @param string $name The field name.
     * @return null|array The attributes for a field, or null.
     */
    public function field($name)
    {
        if (!isset($this->_fields[$name])) {
            return null;
        }
        return $this->_fields[$name];
    }

    /**
     * Get the type of the named field.
     *
     * @param string $name The name of the field.
     * @return string|null Either the field type or null if the
     *   field does not exist.
     */
    public function fieldType($name)
    {
        $field = $this->field($name);
        if (!$field) {
            return null;
        }
        return $field['type'];
    }
}
