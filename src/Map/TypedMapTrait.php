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
 * @since         5.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Map;

use function Cake\Core\toBool;
use function Cake\Core\toDate;
use function Cake\Core\toDateTime;
use function Cake\Core\toFloat;
use function Cake\Core\toInt;

/**
 * Trait for fetching values from a Map with type casting.
 *
 * This trait expects that the implementing class define a get() method for
 * obtaining raw values.
 */
trait TypedMapTrait
{
    /**
     * Returns the string value of the key.
     *
     * @param string $key
     * @return string
     */
    public function getString(string $key): string
    {
        return (string)$this->get($key);
    }

    /**
     * Returns the integer value of the key.
     *
     * @param string $key
     * @return int|null
     */
    public function getInteger(string $key): ?int
    {
        return toInt($this->get($key));
    }

    /**
     * Returns the float value of the key.
     *
     * @param string $key
     * @return float|null
     */
    public function getFloat(string $key): ?float
    {
        return toFloat($this->get($key));
    }

    /**
     * Returns the boolean value of the key.
     *
     * @param string $key
     * @return bool|null
     */
    public function getBool(string $key): ?bool
    {
        return toBool($this->get($key));
    }

    /**
     * Returns the value of the key as a Date object.
     *
     * @param string $key
     * @return \Cake\I18n\FrozenDate|null
     */
    public function getDate(string $key): ?bool
    {
        return toDate($this->get($key));
    }

    /**
     * Returns the value of the key as a DateTime object.
     *
     * @param string $key
     * @return \Cake\I18n\FrozenTime|null
     */
    public function getDateTime(string $key): ?bool
    {
        return toDateTime($this->get($key));
    }
}
