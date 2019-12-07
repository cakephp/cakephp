<?php
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

use Cake\Http\ServerRequest;
use Cake\Utility\Hash;
use Cake\Validation\Validator;

/**
 * Provides a basic array based context provider for FormHelper.
 *
 * This adapter is useful in testing or when you have forms backed by
 * simple array data structures.
 *
 * Important keys:
 *
 * - `defaults` The default values for fields. These values
 *   will be used when there is no request data set. Data should be nested following
 *   the dot separated paths you access your fields with.
 * - `required` A nested array of fields, relationships and boolean
 *   flags to indicate a field is required. The value can also be a string to be used
 *   as the required error message
 * - `schema` An array of data that emulate the column structures that
 *   Cake\Database\Schema\Schema uses. This array allows you to control
 *   the inferred type for fields and allows auto generation of attributes
 *   like maxlength, step and other HTML attributes. If you want
 *   primary key/id detection to work. Make sure you have provided a `_constraints`
 *   array that contains `primary`. See below for an example.
 * - `errors` An array of validation errors. Errors should be nested following
 *   the dot separated paths you access your fields with.
 *
 *  ### Example
 *
 *  ```
 *  $data = [
 *    'schema' => [
 *      'id' => ['type' => 'integer'],
 *      'title' => ['type' => 'string', 'length' => 255],
 *      '_constraints' => [
 *        'primary' => ['type' => 'primary', 'columns' => ['id']]
 *      ]
 *    ],
 *    'defaults' => [
 *      'id' => 1,
 *      'title' => 'First post!',
 *    ],
 *    'required' => [
 *      'id' => true, // will use default required message
 *      'title' => 'Please enter a title',
 *      'body' => false,
 *    ],
 *  ];
 *  ```
 */
class ArrayContext implements ContextInterface
{
    /**
     * The request object.
     *
     * @var \Cake\Http\ServerRequest
     */
    protected $_request;

    /**
     * Context data for this object.
     *
     * @var array
     */
    protected $_context;

    /**
     * Constructor.
     *
     * @param \Cake\Http\ServerRequest $request The request object.
     * @param array $context Context info.
     */
    public function __construct(ServerRequest $request, array $context)
    {
        $this->_request = $request;
        $context += [
            'schema' => [],
            'required' => [],
            'defaults' => [],
            'errors' => [],
        ];
        $this->_context = $context;
    }

    /**
     * Get the fields used in the context as a primary key.
     *
     * @return array
     */
    public function primaryKey()
    {
        if (
            empty($this->_context['schema']['_constraints']) ||
            !is_array($this->_context['schema']['_constraints'])
        ) {
            return [];
        }
        foreach ($this->_context['schema']['_constraints'] as $data) {
            if (isset($data['type']) && $data['type'] === 'primary') {
                return isset($data['columns']) ? (array)$data['columns'] : [];
            }
        }

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function isPrimaryKey($field)
    {
        $primaryKey = $this->primaryKey();

        return in_array($field, $primaryKey, true);
    }

    /**
     * Returns whether or not this form is for a create operation.
     *
     * For this method to return true, both the primary key constraint
     * must be defined in the 'schema' data, and the 'defaults' data must
     * contain a value for all fields in the key.
     *
     * @return bool
     */
    public function isCreate()
    {
        $primary = $this->primaryKey();
        foreach ($primary as $column) {
            if (!empty($this->_context['defaults'][$column])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the current value for a given field.
     *
     * This method will coalesce the current request data and the 'defaults'
     * array.
     *
     * @param string $field A dot separated path to the field a value
     *   is needed for.
     * @param array $options Options:
     *   - `default`: Default value to return if no value found in request
     *     data or context record.
     *   - `schemaDefault`: Boolean indicating whether default value from
     *      context's schema should be used if it's not explicitly provided.
     * @return mixed
     */
    public function val($field, $options = [])
    {
        $options += [
            'default' => null,
            'schemaDefault' => true,
        ];

        $val = $this->_request->getData($field);
        if ($val !== null) {
            return $val;
        }
        if ($options['default'] !== null || !$options['schemaDefault']) {
            return $options['default'];
        }
        if (empty($this->_context['defaults']) || !is_array($this->_context['defaults'])) {
            return null;
        }

        // Using Hash::check here incase the default value is actually null
        if (Hash::check($this->_context['defaults'], $field)) {
            return Hash::get($this->_context['defaults'], $field);
        }

        return Hash::get($this->_context['defaults'], $this->stripNesting($field));
    }

    /**
     * Check if a given field is 'required'.
     *
     * In this context class, this is simply defined by the 'required' array.
     *
     * @param string $field A dot separated path to check required-ness for.
     * @return bool
     */
    public function isRequired($field)
    {
        return (bool)$this->getRequiredMessage($field);
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredMessage($field)
    {
        if (!is_array($this->_context['required'])) {
            return null;
        }
        $required = Hash::get($this->_context['required'], $field);
        if ($required === null) {
            $required = Hash::get($this->_context['required'], $this->stripNesting($field));
        }

        if ($required === false) {
            return null;
        }

        if ($required === true) {
            $required = __d('cake', 'This field is required');
        }

        return $required;
    }

    /**
     * Get field length from validation
     *
     * In this context class, this is simply defined by the 'length' array.
     *
     * @param string $field A dot separated path to check required-ness for.
     * @return int|null
     */
    public function getMaxLength($field)
    {
        if (!is_array($this->_context['schema'])) {
            return null;
        }

        return Hash::get($this->_context['schema'], "$field.length");
    }

    /**
     * {@inheritDoc}
     */
    public function fieldNames()
    {
        $schema = $this->_context['schema'];
        unset($schema['_constraints'], $schema['_indexes']);

        return array_keys($schema);
    }

    /**
     * Get the abstract field type for a given field name.
     *
     * @param string $field A dot separated path to get a schema type for.
     * @return string|null An abstract data type or null.
     * @see \Cake\Database\Type
     */
    public function type($field)
    {
        if (!is_array($this->_context['schema'])) {
            return null;
        }

        $schema = Hash::get($this->_context['schema'], $field);
        if ($schema === null) {
            $schema = Hash::get($this->_context['schema'], $this->stripNesting($field));
        }

        return isset($schema['type']) ? $schema['type'] : null;
    }

    /**
     * Get an associative array of other attributes for a field name.
     *
     * @param string $field A dot separated path to get additional data on.
     * @return array An array of data describing the additional attributes on a field.
     */
    public function attributes($field)
    {
        if (!is_array($this->_context['schema'])) {
            return [];
        }
        $schema = Hash::get($this->_context['schema'], $field);
        if ($schema === null) {
            $schema = Hash::get($this->_context['schema'], $this->stripNesting($field));
        }
        $whitelist = ['length' => null, 'precision' => null];

        return array_intersect_key((array)$schema, $whitelist);
    }

    /**
     * Check whether or not a field has an error attached to it
     *
     * @param string $field A dot separated path to check errors on.
     * @return bool Returns true if the errors for the field are not empty.
     */
    public function hasError($field)
    {
        if (empty($this->_context['errors'])) {
            return false;
        }

        return (bool)Hash::check($this->_context['errors'], $field);
    }

    /**
     * Get the errors for a given field
     *
     * @param string $field A dot separated path to check errors on.
     * @return array An array of errors, an empty array will be returned when the
     *    context has no errors.
     */
    public function error($field)
    {
        if (empty($this->_context['errors'])) {
            return [];
        }

        return (array)Hash::get($this->_context['errors'], $field);
    }

    /**
     * Strips out any numeric nesting
     *
     * For example users.0.age will output as users.age
     *
     * @param string $field A dot separated path
     * @return string A string with stripped numeric nesting
     */
    protected function stripNesting($field)
    {
        return preg_replace('/\.\d*\./', '.', $field);
    }
}
