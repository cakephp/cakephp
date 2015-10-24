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

use Cake\Datasource\Type;
use Cake\Utility\Text;

/**
 * Provides behavior for the uuid type
 */
class UuidType extends StringType
{

    /**
     * Casts given value from a PHP type to one acceptable by database
     *
     * @param mixed $value value to be converted to database equivalent
     * @return mixed
     */
    public function toDatasource($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return parent::toDatasource($value);
    }

    /**
     * Generate a new UUID
     *
     * @return string A new primary key value.
     */
    public function newId()
    {
        return Text::uuid();
    }

    /**
     * Marshalls request data into a PHP string
     *
     * @param mixed $value The value to convert.
     * @return string|null Converted value.
     */
    public function marshal($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (string)$value;
    }
}
