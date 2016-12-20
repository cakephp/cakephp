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

use Cake\Database\Driver;
use DateTime;

class DateType extends DateTimeType
{

    /**
     * The class to use for representing date objects
     *
     * This property can only be used before an instance of this type
     * class is constructed. After that use `useMutable()` or `useImmutable()` instead.
     *
     * @var string
     * @deprecated Use DateType::useMutable() or DateType::useImmutable() instead.
     */
    public static $dateTimeClass = 'Cake\I18n\Date';

    /**
     * Date format for DateTime object
     *
     * @var string
     */
    protected $_format = 'Y-m-d';

    /**
     * Change the preferred class name to the FrozenDate implementation.
     *
     * @return self
     */
    public function useImmutable()
    {
        $this->_setClassName('Cake\I18n\FrozenDate', 'DateTimeImmutable');

        return $this;
    }

    /**
     * Change the preferred class name to the mutable Date implementation.
     *
     * @return self
     */
    public function useMutable()
    {
        $this->_setClassName('Cake\I18n\Date', 'DateTime');

        return $this;
    }

    /**
     * Convert request data into a datetime object.
     *
     * @param mixed $value Request data
     * @return \DateTime
     */
    public function marshal($value)
    {
        $date = parent::marshal($value);
        if ($date instanceof DateTime) {
            $date->setTime(0, 0, 0);
        }

        return $date;
    }

    /**
     * Convert strings into Date instances.
     *
     * @param string $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return \Cake\I18n\Date|\DateTime
     */
    public function toPHP($value, Driver $driver)
    {
        $date = parent::toPHP($value, $driver);
        if ($date instanceof DateTime) {
            $date->setTime(0, 0, 0);
        }

        return $date;
    }

    /**
     * {@inheritDoc}
     */
    protected function _parseValue($value)
    {
        /* @var \Cake\I18n\Time $class */
        $class = $this->_className;

        return $class::parseDate($value, $this->_localeFormat);
    }
}
