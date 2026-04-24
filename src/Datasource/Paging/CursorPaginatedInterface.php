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
 * Describes cursor/seek paginated result sets.
 *
 * Cursor paginated results do not have a meaningful page number or total count.
 * Instead, callers advance through the result set via opaque cursor tokens
 * pointing at a stable position in a deterministic ordering.
 *
 * @template TKey
 * @template-covariant TValue
 * @template-extends \Cake\Datasource\Paging\PaginatedInterface<TKey, TValue>
 */
interface CursorPaginatedInterface extends PaginatedInterface
{
    /**
     * Pagination type identifier.
     *
     * Returns `'seek'` for cursor paginated results.
     *
     * @return string
     */
    public function paginationType(): string;

    /**
     * Structured cursor pointing at the record after the last item on this page.
     *
     * Returns `null` if there is no next page or cursor metadata is unavailable.
     *
     * @return array<string, mixed>|null
     */
    public function nextCursor(): ?array;

    /**
     * Structured cursor pointing at the record before the first item on this page.
     *
     * Returns `null` if there is no previous page or cursor metadata is unavailable.
     *
     * @return array<string, mixed>|null
     */
    public function previousCursor(): ?array;

    /**
     * Opaque signed token for the next cursor, safe to expose over HTTP.
     *
     * @return string|null
     */
    public function nextCursorToken(): ?string;

    /**
     * Opaque signed token for the previous cursor, safe to expose over HTTP.
     *
     * @return string|null
     */
    public function previousCursorToken(): ?string;
}
