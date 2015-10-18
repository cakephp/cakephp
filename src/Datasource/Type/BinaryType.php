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
namespace Cake\Datasource\Type;

use Cake\Core\Exception\Exception;
use Cake\Datasource\Type;

/**
 * Binary type converter.
 *
 * Use to convert binary data between PHP and the database types.
 */
class BinaryType extends Type
{

    /**
     * Convert binary data into the database format.
     *
     * Binary data is not altered before being inserted into the database.
     * As PDO will handle reading file handles.
     *
     * @param string|resource $value The value to convert.
     * @return string|resource
     */
    public function toDatasource($value)
    {
        return $value;
    }

    /**
     * Convert binary into resource handles
     *
     * @param null|string|resource $value The value to convert.
     * @return resource|null
     * @throws \Cake\Core\Exception\Exception
     */
    public function toPHP($value)
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            return fopen('data:text/plain;base64,' . base64_encode($value), 'rb');
        }
        if (is_resource($value)) {
            return $value;
        }
        throw new Exception(sprintf('Unable to convert %s into binary.', gettype($value)));
    }
}
