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
namespace Cake\Database;

/**
 * Implements default and single-use mappings for columns to their associated types
 */
class TypeMap
{

    /**
     * Associative array with the default fields and the related types this query might contain.
     *
     * Used to avoid repetition when calling multiple functions inside this class that
     * may require a custom type for a specific field.
     *
     * @var array
     */
    protected $_defaults;

    /**
     * Associative array with the fields and the related types that override defaults this query might contain
     *
     * Used to avoid repetition when calling multiple functions inside this class that
     * may require a custom type for a specific field.
     *
     * @var array
     */
    protected $_types = [];

    /**
     * Creates an instance with the given defaults
     *
     * @param array $defaults The defaults to use.
     */
    public function __construct(array $defaults = [])
    {
        $this->setDefaults($defaults);
    }

    /**
     * Configures a map of default fields and their associated types to be
     * used as the default list of types for every function in this class
     * with a $types param. Useful to avoid repetition when calling the same
     * functions using the same fields and types.
     *
     * ### Example
     *
     * ```
     * $query->setDefaults(['created' => 'datetime', 'is_visible' => 'boolean']);
     * ```
     *
     * This method will replace all the existing type maps with the ones provided.
     *
     * @param array $defaults Associative array where keys are field names and values
     * are the correspondent type.
     * @return $this
     */
    public function setDefaults(array $defaults)
    {
        $this->_defaults = $defaults;

        return $this;
    }

    /**
     * Returns the currently configured types.
     *
     * @return array
     */
    public function getDefaults()
    {
        return $this->_defaults;
    }

    /**
     * Configures a map of default fields and their associated types to be
     * used as the default list of types for every function in this class
     * with a $types param. Useful to avoid repetition when calling the same
     * functions using the same fields and types.
     *
     * If called with no arguments it will return the currently configured types.
     *
     * ### Example
     *
     * ```
     * $query->defaults(['created' => 'datetime', 'is_visible' => 'boolean']);
     * ```
     *
     * This method will replace all the existing type maps with the ones provided.
     *
     * @deprecated 3.4.0 Use setDefaults()/getDefaults() instead.
     * @param array|null $defaults associative array where keys are field names and values
     * are the correspondent type.
     * @return $this|array
     */
    public function defaults(array $defaults = null)
    {
        if ($defaults !== null) {
            return $this->setDefaults($defaults);
        }

        return $this->getDefaults();
    }

    /**
     * Add additional default types into the type map.
     *
     * If a key already exists it will not be overwritten.
     *
     * @param array $types The additional types to add.
     * @return void
     */
    public function addDefaults(array $types)
    {
        $this->_defaults += $types;
    }

    /**
     * Sets a map of fields and their associated types for single-use.
     *
     * ### Example
     *
     * ```
     * $query->setTypes(['created' => 'time']);
     * ```
     *
     * This method will replace all the existing type maps with the ones provided.
     *
     * @param array $types Associative array where keys are field names and values
     * are the correspondent type.
     * @return $this
     */
    public function setTypes(array $types)
    {
        $this->_types = $types;

        return $this;
    }

    /**
     * Gets a map of fields and their associated types for single-use.
     *
     * @return array
     */
    public function getTypes()
    {
        return $this->_types;
    }

    /**
     * Sets a map of fields and their associated types for single-use.
     *
     * If called with no arguments it will return the currently configured types.
     *
     * ### Example
     *
     * ```
     * $query->types(['created' => 'time']);
     * ```
     *
     * This method will replace all the existing type maps with the ones provided.
     *
     * @deprecated 3.4.0 Use setTypes()/getTypes() instead.
     * @param array|null $types associative array where keys are field names and values
     * are the correspondent type.
     * @return $this|array
     */
    public function types(array $types = null)
    {
        if ($types !== null) {
            return $this->setTypes($types);
        }

        return $this->getTypes();
    }

    /**
     * Returns the type of the given column. If there is no single use type is configured,
     * the column type will be looked for inside the default mapping. If neither exist,
     * null will be returned.
     *
     * @param string $column The type for a given column
     * @return null|string
     */
    public function type($column)
    {
        if (isset($this->_types[$column])) {
            return $this->_types[$column];
        }
        if (isset($this->_defaults[$column])) {
            return $this->_defaults[$column];
        }

        return null;
    }

    /**
     * Returns an array of all types mapped types
     *
     * @return array
     */
    public function toArray()
    {
        return $this->_types + $this->_defaults;
    }
}
