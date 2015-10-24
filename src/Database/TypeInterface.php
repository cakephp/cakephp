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

use Cake\Datasource\TypeInterface as DatasourceTypeInterface;

/**
 * Interface describing a database specific type
 *
 * @package Cake\Database
 */
interface TypeInterface extends DatasourceTypeInterface
{

    /**
     * Casts give value to Statement equivalent
     *
     * @param mixed $value value to be converted to PHP equivalent
     * @param Driver $driver The driver.
     *
     * @return mixed
     */
    public function toStatement($value, Driver $driver);
}
