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
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

use Cake\Core\InstanceConfigTrait;
use Cake\Datasource\Exception\PageOutOfBoundsException;

/**
 * This class is used to handle automatic model data pagination.
 */
class Paginator implements PaginatorInterface
{
    use InstanceConfigTrait;
    use PaginatorTrait;

    /**
     * Handles automatic pagination of model records.
     *
     * ### Configuring pagination
     *
     * When calling `paginate()` you can use the $settings parameter to pass in
     * pagination settings. These settings are used to build the queries made
     * and control other pagination settings.
     *
     * If your settings contain a key with the current table's alias. The data
     * inside that key will be used. Otherwise the top level configuration will
     * be used.
     *
     * ```
     *  $settings = [
     *    'limit' => 20,
     *    'maxLimit' => 100
     *  ];
     *  $results = $paginator->paginate($table, $settings);
     * ```
     *
     * The above settings will be used to paginate any repository. You can configure
     * repository specific settings by keying the settings with the repository alias.
     *
     * ```
     *  $settings = [
     *    'Articles' => [
     *      'limit' => 20,
     *      'maxLimit' => 100
     *    ],
     *    'Comments' => [ ... ]
     *  ];
     *  $results = $paginator->paginate($table, $settings);
     * ```
     *
     * This would allow you to have different pagination settings for
     * `Articles` and `Comments` repositories.
     *
     * ### Controlling sort fields
     *
     * By default CakePHP will automatically allow sorting on any column on the
     * repository object being paginated. Often times you will want to allow
     * sorting on either associated columns or calculated fields. In these cases
     * you will need to define a whitelist of all the columns you wish to allow
     * sorting on. You can define the whitelist in the `$settings` parameter:
     *
     * ```
     * $settings = [
     *   'Articles' => [
     *     'finder' => 'custom',
     *     'sortWhitelist' => ['title', 'author_id', 'comment_count'],
     *   ]
     * ];
     * ```
     *
     * Passing an empty array as whitelist disallows sorting altogether.
     *
     * ### Paginating with custom finders
     *
     * You can paginate with any find type defined on your table using the
     * `finder` option.
     *
     * ```
     *  $settings = [
     *    'Articles' => [
     *      'finder' => 'popular'
     *    ]
     *  ];
     *  $results = $paginator->paginate($table, $settings);
     * ```
     *
     * Would paginate using the `find('popular')` method.
     *
     * You can also pass an already created instance of a query to this method:
     *
     * ```
     * $query = $this->Articles->find('popular')->matching('Tags', function ($q) {
     *   return $q->where(['name' => 'CakePHP'])
     * });
     * $results = $paginator->paginate($query);
     * ```
     *
     * ### Scoping Request parameters
     *
     * By using request parameter scopes you can paginate multiple queries in
     * the same controller action:
     *
     * ```
     * $articles = $paginator->paginate($articlesQuery, ['scope' => 'articles']);
     * $tags = $paginator->paginate($tagsQuery, ['scope' => 'tags']);
     * ```
     *
     * Each of the above queries will use different query string parameter sets
     * for pagination data. An example URL paginating both results would be:
     *
     * ```
     * /dashboard?articles[page]=1&tags[page]=2
     * ```
     *
     * @param \Cake\Datasource\RepositoryInterface|\Cake\Datasource\QueryInterface $object The table or query to paginate.
     * @param array $params Request params
     * @param array $settings The settings/configuration used for pagination.
     * @return \Cake\Datasource\ResultSetInterface Query results
     * @throws \Cake\Datasource\Exception\PageOutOfBoundsException
     */
    public function paginate($object, array $params = [], array $settings = [])
    {
        $query = null;
        if ($object instanceof QueryInterface) {
            $query = $object;
            $object = $query->getRepository();
        }

        $data = $this->_extractData($object, $params, $settings);

        if (empty($query)) {
            $query = $object->find($data['finder'], $data['options']);
        } else {
            $query->applyOptions($data['options']);
        }

        $cleanQuery = clone $query;
        $results = $query->all();
        $data['numResults'] = count($results);
        $data['count'] = $cleanQuery->count();
        $this->_buildParams($data);

        return $results;
    }

    /**
     * Extract pagination data needed
     *
     * @param \Cake\Datasource\RepositoryInterface|\Cake\Datasource\QueryInterface $object The table or query to paginate.
     * @param array $params Request params
     * @param array $settings The settings/configuration used for pagination.
     *
     * @return array Array with keys 'alias', 'defaults', 'options' and 'finder'
     */
    protected function _extractData($object, array $params, array $settings)
    {
        $alias = $object->getAlias();
        $defaults = $this->getDefaults($alias, $settings);
        $options = $this->mergeOptions($params, $defaults);
        $options = $this->validateSort($object, $options);
        $options = $this->checkLimit($options);

        $options += ['page' => 1, 'scope' => null];
        $options['page'] = (int)$options['page'] < 1 ? 1 : (int)$options['page'];
        list($finder, $options) = $this->_extractFinder($options);

        return compact('alias', 'defaults', 'options', 'finder');
    }

    /**
     * Build pagination params
     *
     * @param array $data Paginator data containing keys 'alias', 'options', 'count', 'defaults', 'finder', 'numResults'
     *
     * @return void
     */
    protected function _buildParams($data)
    {
        $defaults = $data['defaults'];
        $count = $data['count'];
        $page = $data['options']['page'];
        $limit = $data['options']['limit'];
        $pageCount = max((int)ceil($count / $limit), 1);
        $requestedPage = $page;
        $page = min($page, $pageCount);

        $order = (array)$data['options']['order'];
        $sortDefault = $directionDefault = false;
        if (!empty($defaults['order']) && count($defaults['order']) === 1) {
            $sortDefault = key($defaults['order']);
            $directionDefault = current($defaults['order']);
        }

        $start = 0;
        if ($count >= 1) {
            $start = (($page - 1) * $limit) + 1;
        }
        $end = $start + $limit - 1;
        if ($count < $end) {
            $end = $count;
        }

        $paging = [
            'finder' => $data['finder'],
            'page' => $page,
            'current' => $data['numResults'],
            'count' => $count,
            'perPage' => $limit,
            'start' => $start,
            'end' => $end,
            'prevPage' => $page > 1,
            'nextPage' => $count > ($page * $limit),
            'pageCount' => $pageCount,
            'sort' => $data['options']['sort'],
            'direction' => isset($data['options']['sort']) ? current($order) : null,
            'limit' => $defaults['limit'] != $limit ? $limit : null,
            'sortDefault' => $sortDefault,
            'directionDefault' => $directionDefault,
            'scope' => $data['options']['scope'],
            'completeSort' => $order,
        ];

        $this->_pagingParams = [$data['alias'] => $paging];

        if ($requestedPage > $page) {
            throw new PageOutOfBoundsException([
                'requestedPage' => $requestedPage,
                'pagingParams' => $this->_pagingParams
            ]);
        }
    }
}
