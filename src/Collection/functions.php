<?php
// phpcs:ignoreFile
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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Collection;

/**
 * Returns a new {@link \Cake\Collection\Collection} object wrapping the passed argument.
 *
 * @param iterable $items The items from which the collection will be built.
 * @return \Cake\Collection\Collection
 */
function collection(iterable $items): CollectionInterface
{
    return new Collection($items);
}

/**
 * Include global functions.
 */
if (!getenv('CAKE_DISABLE_GLOBAL_FUNCS')) {
    include 'functions_global.php';
}
