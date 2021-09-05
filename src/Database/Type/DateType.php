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
use Cake\I18n\I18nDateTimeInterface;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Class DateType
 */
class DateType extends DateTimeType
{
    /**
     * @inheritDoc
     */
    protected string $_format = 'Y-m-d';

    /**
     * @inheritDoc
     */
    protected array $_marshalFormats = [
        'Y-m-d',
    ];

    /**
     * In this class we want Date objects to  have their time
     * set to the beginning of the day.
     *
     * @var bool
     */
    protected bool $setToDateStart = true;

    /**
     * @inheritDoc
     */
    public function __construct(?string $name = null)
    {
        parent::__construct($name);

        $this->_className = class_exists(Date::class) ? Date::class : DateTimeImmutable::class;
    }

    /**
     * Convert request data into a datetime object.
     *
     * @param mixed $value Request data
     * @return \DateTimeInterface|null
     */
    public function marshal(mixed $value): ?DateTimeInterface
    {
        $date = parent::marshal($value);
        if ($date && !$date instanceof I18nDateTimeInterface) {
            // Clear time manually when I18n types aren't available and raw DateTime used
            /** @psalm-var \DateTime|\DateTimeImmutable $date */
            $date->setTime(0, 0, 0);
        }

        return $date;
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
