<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         5.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource\Paging;

use Countable;
use Traversable;

/**
 * This interface describes the methods for pagination instance.
 *
 * @template-extends \Traversable<mixed>
 */
interface PaginatedInterface extends Countable, Traversable
{
    /**
     * Get current page number.
     */
    public function currentPage(): int;

    /**
     * Get items per page.
     */
    public function perPage(): int;

    /**
     * Get Total items counts.
     */
    public function totalCount(): ?int;

    /**
     * Get total page count.
     */
    public function pageCount(): ?int;

    /**
     * Get whether there's a previous page.
     */
    public function hasPrevPage(): bool;

    /**
     * Get whether there's a next page.
     */
    public function hasNextPage(): bool;

    /**
     * Get paginated items.
     */
    public function items(): iterable;

    /**
     * Get paging param.
     */
    public function pagingParam(string $name): mixed;

    /**
     * Get all paging params.
     */
    public function pagingParams(): array;
}
