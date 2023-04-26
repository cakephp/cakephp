<?php
declare(strict_types=1);

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

use Cake\I18n\Date;
use Cake\I18n\FrozenDate;
use Cake\I18n\I18nDateTimeInterface;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use function Cake\Core\deprecationWarning;

/**
 * Class DateType
 */
class DateType extends DateTimeType
{
    /**
     * @inheritDoc
     */
    protected $_format = 'Y-m-d';

    /**
     * @inheritDoc
     */
    protected $_marshalFormats = [
        'Y-m-d',
    ];

    /**
     * In this class we want Date objects to  have their time
     * set to the beginning of the day.
     *
     * @var bool
     */
    protected $setToDateStart = true;

    /**
     * @inheritDoc
     */
    public function __construct(?string $name = null)
    {
        parent::__construct($name);

        $this->_setClassName(FrozenDate::class, DateTimeImmutable::class);
    }

    /**
     * Change the preferred class name to the FrozenDate implementation.
     *
     * @return $this
     * @deprecated 4.3.0 This method is no longer needed as using immutable datetime class is the default behavior.
     */
    public function useImmutable()
    {
        deprecationWarning(
            'Configuring immutable or mutable classes is deprecated and immutable'
            . ' classes will be the permanent configuration in 5.0. Calling `useImmutable()` is unnecessary.'
        );

        $this->_setClassName(FrozenDate::class, DateTimeImmutable::class);

        return $this;
    }

    /**
     * Change the preferred class name to the mutable Date implementation.
     *
     * @return $this
     * @deprecated 4.3.0 Using mutable datetime objects is deprecated.
     */
    public function useMutable()
    {
        deprecationWarning(
            'Configuring immutable or mutable classes is deprecated and immutable'
            . ' classes will be the permanent configuration in 5.0. Calling `useImmutable()` is unnecessary.'
        );

        $this->_setClassName(Date::class, DateTime::class);

        return $this;
    }

    /**
     * Convert request data into a datetime object.
     *
     * @param mixed $value Request data
     * @return \DateTimeInterface|null
     */
    public function marshal($value): ?DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return new FrozenDate($value);
        }

        /** @var class-string<\Cake\Chronos\ChronosDate> $class */
        $class = $this->_className;
        try {
            if ($value === '' || $value === null || is_bool($value)) {
                return null;
            }

            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                /** @var \Cake\I18n\FrozenDate|\DateTimeImmutable $dateTime */
                $dateTime = new $class('@' . $value);

                return $dateTime;
            }

            if (is_string($value)) {
                if ($this->_useLocaleMarshal) {
                    $dateTime = $this->_parseLocaleValue($value);
                } else {
                    $dateTime = $this->_parseValue($value);
                }

                return $dateTime;
            }
        } catch (Exception $e) {
            return null;
        }

        if (is_array($value) && implode('', $value) === '') {
            return null;
        }
        $format = '';
        if (
            isset($value['year'], $value['month'], $value['day']) &&
            (
                is_numeric($value['year']) &&
                is_numeric($value['month']) &&
                is_numeric($value['day'])
            )
        ) {
            $format .= sprintf('%d-%02d-%02d', $value['year'], $value['month'], $value['day']);
        }

        if (empty($format)) {
            // Invalid array format.
            return null;
        }

        /** @var \Cake\I18n\FrozenDate|\DateTimeImmutable $dateTime */
        $dateTime = new $class($format);

        return $dateTime;
    }

    /**
     * @inheritDoc
     */
    protected function _parseLocaleValue(string $value): ?I18nDateTimeInterface
    {
        /** @psalm-var class-string<\Cake\I18n\I18nDateTimeInterface> $class */
        $class = $this->_className;

        return $class::parseDate($value, $this->_localeMarshalFormat);
    }
}
