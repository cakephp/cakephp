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
 * @since         3.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use Cake\Database\Type\OptionalConvertInterface;

/**
 * A callable class to be used for processing each of the rows in a statement
 * result, so that the values are converted to the right PHP types.
 */
class FieldTypeConverter
{

    /**
     * An array containing the name of the fields and the Type objects
     * each should use when converting them.
     *
     * @var array
     */
    protected $_typeMap;

    /**
     * The driver object to be used in the type conversion
     *
     * @var \Cake\Database\Driver
     */
    protected $_driver;

    /**
     * Builds the type map
     *
     * @param \Cake\Database\TypeMap $typeMap Contains the types to use for converting results
     * @param \Cake\Database\Driver $driver The driver to use for the type conversion
     */
    public function __construct(TypeMap $typeMap, Driver $driver)
    {
        $this->_driver = $driver;
        $map = $typeMap->toArray();
        $types = Type::buildAll();
        $result = [];

        foreach ($types as $k => $type) {
            if ($type instanceof OptionalConvertInterface && !$type->requiresToPhpCast()) {
                unset($types[$k]);
            }
        }

        foreach ($map as $field => $type) {
            if (isset($types[$type])) {
                $result[$field] = $types[$type];
            }
        }
        $this->_typeMap = $result;
    }

    /**
     * Converts each of the fields in the array that are present in the type map
     * using the corresponding Type class.
     *
     * @param array $row The array with the fields to be casted
     * @return array
     */
    public function __invoke($row)
    {
        foreach ($this->_typeMap as $field => $type) {
            $row[$field] = $type->toPHP($row[$field], $this->_driver);
        }

        return $row;
    }
}
