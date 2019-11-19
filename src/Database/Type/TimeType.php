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
namespace Cake\Database\Type;

/**
 * Time type converter.
 *
 * Use to convert time instances to strings & back.
 */
class TimeType extends DateTimeType
{
    /**
     * Time format for DateTime object
     *
     * @var string|array
     */
    protected $_format = 'H:i:s';

    /**
     * {@inheritDoc}
     */
    protected function _parseValue($value)
    {
        /** @var \Cake\I18n\Time $class */
        $class = $this->_className;

        return $class::parseTime($value, $this->_localeFormat);
    }
}
