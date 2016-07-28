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
namespace Cake\Database\Type;

use Cake\Database\TypeInterface;

/**
 * Time type converter.
 *
 * Use to convert time instances to strings & back.
 */
class TimeType extends DateTimeType implements TypeInterface
{

    /**
     * Time format for DateTime object
     *
     * @var string
     */
    protected $_format = 'H:i:s';

    /**
     * {@inheritDoc}
     */
    protected function _parseValue($value)
    {
        $class = $this->_className;

        return $class::parseTime($value, $this->_localeFormat);
    }
}
