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

use DateTime;
use DateTimeImmutable;

/**
 * Class DateType
 */
class DateType extends DateTimeType
{
    /**
     * The class to use for representing date objects
     *
     * This property can only be used before an instance of this type
     * class is constructed. After that use `useMutable()` or `useImmutable()` instead.
     *
     * @var string
     * @deprecated 3.2.0 Use DateType::useMutable() or DateType::useImmutable() instead.
     */
    public static $dateTimeClass = 'Cake\I18n\Date';

    /**
     * Date format for DateTime object
     *
     * @var string|array
     */
    protected $_format = 'Y-m-d';

    /**
     * In this class we want Date objects to  have their time
     * set to the beginning of the day.
     *
     * @var bool
     */
    protected $setToDateStart = true;

    /**
     * Change the preferred class name to the FrozenDate implementation.
     *
     * @return $this
     */
    public function useImmutable()
    {
        $this->_setClassName('Cake\I18n\FrozenDate', 'DateTimeImmutable');

        return $this;
    }

    /**
     * Change the preferred class name to the mutable Date implementation.
     *
     * @return $this
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
     * @return \DateTimeInterface
     */
    public function marshal($value)
    {
        $date = parent::marshal($value);
        if ($date instanceof DateTime || $date instanceof DateTimeImmutable) {
            $date = $date->setTime(0, 0, 0);
        }

        return $date;
    }

    /**
     * {@inheritDoc}
     */
    protected function _parseValue($value)
    {
        /** @var \Cake\I18n\Time $class */
        $class = $this->_className;

        return $class::parseDate($value, $this->_localeFormat);
    }
}
