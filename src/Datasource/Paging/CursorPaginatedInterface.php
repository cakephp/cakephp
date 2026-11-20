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
 * Paginated results that are navigated with opaque cursors instead of page numbers.
 *
 * @template TKey
 * @template-covariant TValue
 * @template-extends \Cake\Datasource\Paging\PaginatedInterface<TKey, TValue>
 */
interface CursorPaginatedInterface extends PaginatedInterface
{
    /**
     * Get the cursor pointing to the page after the current one.
     *
     * @return string|null Null when there is no next page.
     */
    public function nextCursor(): ?string;

    /**
     * Get the cursor pointing to the page before the current one.
     *
     * @return string|null Null when there is no previous page.
     */
    public function prevCursor(): ?string;
}
