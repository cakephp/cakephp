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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

// phpcs:disable PSR1.Files.SideEffects
use Cake\ORM\Table;
use function Cake\ORM\fetchTable as cakefetchTable;

if (!function_exists('fetchTable')) {
    /**
     * Convenience function to get a table instance.
     *
     * @template T of \Cake\ORM\Table
     * @param class-string<T>|string $alias The alias name you want to get. Should be in CamelCase format.
     * @param array<string, mixed> $options The options you want to build the table with.
     *   If a table has already been loaded the registry options will be ignored.
     * @return ($alias is class-string<T> ? T : \Cake\ORM\Table)
     * @throws \Cake\Core\Exception\CakeException If `$alias` argument and `$defaultTable` property both are `null`.
     * @see \Cake\ORM\TableLocator::get()
     */
    function fetchTable(string $alias, array $options = []): Table
    {
        return cakefetchTable($alias, $options);
    }
}
