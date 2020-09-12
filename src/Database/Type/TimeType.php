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

use Cake\I18n\I18nDateTimeInterface;

/**
 * Time type converter.
 *
 * Use to convert time instances to strings & back.
 */
class TimeType extends DateTimeType
{
    /**
     * @inheritDoc
     */
    protected $_format = 'H:i:s';

    /**
     * @inheritDoc
     */
    protected $_marshalFormats = [
        'H:i:s',
        'H:i',
    ];

    /**
     * @inheritDoc
     */
    protected function _parseLocaleValue(string $value): ?I18nDateTimeInterface
    {
        /** @psalm-var class-string<\Cake\I18n\I18nDateTimeInterface> $class */
        $class = $this->_className;

        /** @psalm-suppress PossiblyInvalidArgument */
        return $class::parseTime($value, $this->_localeMarshalFormat);
    }
}
