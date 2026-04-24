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
 * @since         5.4.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource\Paging;

/**
 * Cursor/seek paginated result set.
 *
 * Extends {@see \Cake\Datasource\Paging\PaginatedResultSet} with cursor metadata
 * for seek pagination. Page-oriented accessors inherited from the parent class
 * return neutral values — `currentPage()` is always `1`, `pageCount()`/`totalCount()`
 * are `null`.
 *
 * @template TKey
 * @template TValue
 * @extends \Cake\Datasource\Paging\PaginatedResultSet<TKey, TValue>
 * @implements \Cake\Datasource\Paging\CursorPaginatedInterface<TKey, TValue>
 */
class CursorPaginatedResultSet extends PaginatedResultSet implements CursorPaginatedInterface
{
    /**
     * @inheritDoc
     */
    public function paginationType(): string
    {
        return 'seek';
    }

    /**
     * @inheritDoc
     */
    public function nextCursor(): ?array
    {
        return $this->params['nextCursor'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function previousCursor(): ?array
    {
        return $this->params['previousCursor'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function nextCursorToken(): ?string
    {
        return $this->params['nextCursorToken'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function previousCursorToken(): ?string
    {
        return $this->params['previousCursorToken'] ?? null;
    }
}
