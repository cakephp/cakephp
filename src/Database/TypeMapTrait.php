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

/*
 * Represents a class that holds a TypeMap object
 */
trait TypeMapTrait
{

    /**
     * @var \Cake\Database\TypeMap
     */
    protected $_typeMap;

    /**
     * Creates a new TypeMap if $typeMap is an array, otherwise returns the existing type map
     * or exchanges it for the given one.
     *
     * @param array|\Cake\Database\TypeMap|null $typeMap Creates a TypeMap if array, otherwise sets the given TypeMap
     * @return $this|\Cake\Database\TypeMap
     */
    public function typeMap($typeMap = null)
    {
        if ($this->_typeMap === null) {
            $this->_typeMap = new TypeMap();
        }
        if ($typeMap === null) {
            return $this->_typeMap;
        }
        $this->_typeMap = is_array($typeMap) ? new TypeMap($typeMap) : $typeMap;

        return $this;
    }

    /**
     * Allows setting default types when chaining query
     *
     * @param array|null $types The array of types to set.
     * @return $this|array
     */
    public function defaultTypes(array $types = null)
    {
        if ($types === null) {
            return $this->typeMap()->defaults();
        }
        $this->typeMap()->defaults($types);

        return $this;
    }
}
