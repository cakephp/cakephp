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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Collection\Collection;

if (!function_exists('collection')) {
    /**
     * Returns a new Cake\Collection\Collection object wrapping the passed argument.
     *
     * @param \Traversable|array $items The items from which the collection will be built.
     * @return \Cake\Collection\Collection
     */
    function collection($items)
    {
        return new Collection($items);
    }

}
