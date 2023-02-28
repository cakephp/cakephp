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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Collection\Iterator;

class UniqueIterator extends FilterIterator
{
    protected array $_exists = [];

    /**
     * @param \Traversable|array $items The items to be filtered.
     * @param string|null $key An object property or array key that will be used to determine uniqueness.
     */
    public function __construct($items, ?string $key)
    {
        parent::__construct($items, function ($item) use ($key) {

            if (is_null($key)) {
                $key = $value = $item;
            } else {
                if (is_object($item) && method_exists($item, 'get' . ucfirst($key))) {
                    $value = $item->{'get' . ucfirst($key)};
                } elseif (is_object($item) && method_exists($item, '__toString')) {
                    $value = (string)$item;
                } elseif (is_object($item) && property_exists($item, $key)) {
                    $value = $item->{$key};
                } elseif (is_array($item)) {
                    $value = array_key_exists($key, $item) ? $item[$key] : null;
                } else {
                    throw new \InvalidArgumentException(sprintf(
                        "Can't get value by key '%s' from item '%s', you need to implement get<Key> or __toString
                        method or make property public in case object or there just no needed key in case of array",
                        $key,
                        is_object($item) ? get_class($item) : gettype($item)
                    ));
                }
            }

            if (is_object($value)) {
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d\TH:i:sO');
                } elseif (method_exists($value, '__toString')) {
                    $value = (string)$value;
                } else {
                    $pValue = print_r($value, true);
                    if ($pValue) {
                        $value = md5($pValue);
                    } else {
                        throw new \InvalidArgumentException(sprintf(
                            "Value '%s' couldn't be used as array index, please implement __toString method first",
                            get_class($value)
                        ));
                    }
                }
            }

            if (isset($this->_exists[$value])) {
                return false;
            }

            $this->_exists[$value] = $key;

            return true;
        });
    }
}
