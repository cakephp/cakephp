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
 * @since         3.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use Cake\Database\TypeInterface as DatabaseTypeInterface;
use Cake\Datasource\Type as DatasourceType;
use Cake\Datasource\TypeInterface;

/**
 * Base class for database specific types
 *
 * @package Cake\Database
 */
class Type extends DatasourceType implements TypeInterface
{

    use TypeTrait;

    /**
     * Casts give value to Statement equivalent
     *
     * @param \Cake\Datasource\TypeInterface $type The type to check against
     * @param mixed $value value to be converted to PHP equivalent
     * @param Driver $driver The driver.
     *
     * @return mixed
     */
    public static function toStatementType(TypeInterface $type, $value, Driver $driver)
    {
        if (is_null($value)) {
            return \PDO::PARAM_NULL;
        }
        if (is_int($value)) {
            return \PDO::PARAM_INT;
        }
        if (is_bool($value)) {
            return \PDO::PARAM_BOOL;
        }
        if (is_string($value)) {
            return \PDO::PARAM_STR;
        }
        if (is_float($value)) {
            return \PDO::PARAM_STR;
        }

        if ($type instanceof DatabaseTypeInterface) {
            return $type->toStatement($value, $driver);
        }

        return \PDO::PARAM_STR;
    }
}
