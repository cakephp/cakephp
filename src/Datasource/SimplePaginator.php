<?php
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
 * @since         3.9.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

/**
 * Simplified paginator which avoids potentially expensives queries
 * to get the total count of records.
 *
 * When using a simple paginator you will not be able to generate page numbers.
 * Instead use only the prev/next pagination controls, and handle 404 errors
 * when pagination goes past the available result set.
 */
class SimplePaginator extends Paginator
{
    /**
     * Simple pagination does not perform any count query, so this method returns `null`.
     *
     * @param \Cake\Datasource\QueryInterface $query Query instance.
     * @param array $data Pagination data.
     * @return int|null
     */
    protected function getCount(QueryInterface $query, array $data)
    {
        return null;
    }
}
