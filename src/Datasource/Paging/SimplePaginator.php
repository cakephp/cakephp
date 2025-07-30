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
 * @since         3.9.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource\Paging;

use Cake\Datasource\QueryInterface;
use Cake\Datasource\ResultSetInterface;

/**
 * Simplified paginator which avoids potentially expensive queries
 * to get the total count of records.
 *
 * When using a simple paginator you will not be able to generate page numbers.
 * Instead use only the prev/next pagination controls.
 */
class SimplePaginator extends NumericPaginator
{
    /**
     * Get paginated items.
     *
     * Get one additional record than the limit. This helps deduce if next page exits.
     *
     * @param \Cake\Datasource\QueryInterface $query Query to fetch items.
     * @param array $data Paging data.
     * @return \Cake\Datasource\ResultSetInterface
     */
    protected function getItems(QueryInterface $query, array $data): ResultSetInterface
    {
        return $query->limit($data['options']['limit'] + 1)->all();
    }

    /**
     * @inheritDoc
     */
    protected function buildParams(array $data): array
    {
        $hasNextPage = false;
        if ($this->pagingParams['count'] > $data['options']['limit']) {
            $hasNextPage = true;
            $this->pagingParams['count'] -= 1;
        }

        parent::buildParams($data);

        $this->pagingParams['hasNextPage'] = $hasNextPage;

        return $this->pagingParams;
    }

    /**
     * Build paginated result set.
     *
     * Since the query fetches an extra record, drop the last record if records
     * fetched exceeds the limit/per page.
     *
     * @param \Cake\Datasource\ResultSetInterface $items
     * @param array $pagingParams
     * @return \Cake\Datasource\Paging\PaginatedInterface
     */
    protected function buildPaginated(ResultSetInterface $items, array $pagingParams): PaginatedInterface
    {
        if (count($items) > $this->pagingParams['perPage']) {
            $items = $items->take($this->pagingParams['perPage']);
        }

        return new PaginatedResultSet($items, $pagingParams);
    }

    /**
     * Simple pagination does not perform any count query, so this method returns `null`.
     *
     * @param \Cake\Datasource\QueryInterface $query Query instance.
     * @param array $data Pagination data.
     * @return int|null
     */
    protected function getCount(QueryInterface $query, array $data): ?int
    {
        return null;
    }
}
