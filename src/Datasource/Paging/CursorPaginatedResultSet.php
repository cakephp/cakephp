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
 * @since         5.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource\Paging;

/**
 * Paginated result set returned by the CursorPaginator.
 *
 * @template TKey
 * @template TValue
 * @template-extends \Cake\Datasource\Paging\PaginatedResultSet<TKey, TValue>
 * @template-implements \Cake\Datasource\Paging\CursorPaginatedInterface<TKey, TValue>
 */
class CursorPaginatedResultSet extends PaginatedResultSet implements CursorPaginatedInterface
{
    /**
     * @inheritDoc
     */
    public function nextCursor(): ?string
    {
        return $this->params['nextCursor'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function prevCursor(): ?string
    {
        return $this->params['prevCursor'] ?? null;
    }
}
