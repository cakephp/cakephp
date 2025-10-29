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
namespace Cake\ORM\Locator;

use Cake\ORM\Table;
use Psr\Container\ContainerInterface;

/**
 * Dependency injection container for Tables. Will create Tables
 * as if fetchTable() was called with default options.
 *
 * Register as a delegate in your Application::services() function
 * before any auto-wire delegates.
 */
class TableContainer implements ContainerInterface
{
    use LocatorAwareTrait;

    /**
     * @inheritDoc
     */
    public function get(string $id): Table
    {
        return $this->fetchTable($id);
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return str_ends_with($id, 'Table') && is_subclass_of($id, Table::class);
    }
}
