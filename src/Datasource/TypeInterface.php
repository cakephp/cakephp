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
namespace Cake\Datasource;

/**
 * Interface describing a basic datasource type
 *
 * @package Cake\Datasource
 */
interface TypeInterface
{

    /**
     * Casts given value from a PHP type to one acceptable by database
     *
     * @param mixed $value value to be converted to database equivalent
     * @return mixed
     */
    public function toDatasource($value);

    /**
     * Casts given value from a PHP type to one acceptable by database
     *
     * @param mixed $value value to be converted to database equivalent
     *
     * @return mixed
     *
     * @deprecated Use toDatasource instead
     * @see \Cake\Datasource\TypeInterface::toDatasource
     */
    public function toDatabase($value);

    /**
     * Casts given value from a datasource type to PHP equivalent
     *
     * @param mixed $value value to be converted to PHP equivalent
     * @return mixed
     */
    public function toPHP($value);
}
