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
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         5.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource\Paging;

/**
 * This interface describes the methods for paginator instance.
 */
interface PaginatorInterface
{
    /**
     * Handles pagination of data.
     *
     * @param mixed $target Anything that needs to be paginated.
     * @param array $params Request params.
     * @param array $settings The settings/configuration used for pagination.
     * @return \Cake\Datasource\Paging\PaginatedInterface
     */
    public function paginate(
        mixed $target,
        array $params = [],
        array $settings = [],
    ): PaginatedInterface;
}
