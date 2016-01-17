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
        $this->defaults($defaults);
    }

    /**
     * Gets/Sets configured map defaults.
     *
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
     * @param array $defaults associative array where keys are field names and values
     * are the correspondent type.
     * @return $this|array
     */
    public function defaults(array $defaults = null)
    {
        if (func_num_args() === 0) {
            return $this->_defaults;
        }
        $this->_defaults = $defaults;
        return $this;
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
        $this->_defaults = $this->_defaults + $types;
    }

    /**
     * Gets/Sets configured types.
     *
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
     * @param array $types associative array where keys are field names and values
     * are the correspondent type.
     * @return $this|array
     */
    public function types(array $types = null)
    {
        if (func_num_args() === 0) {
            return $this->_types;
        }

        $this->_types = $types;
        return $this;
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
}
