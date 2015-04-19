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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component;

use BadMethodCallException;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\Query;
use Cake\ORM\Table;

/**
 * This component is used to handle automatic model data pagination. The primary way to use this
 * component is to call the paginate() method. There is a convenience wrapper on Controller as well.
 *
 * ### Configuring pagination
 *
 * You configure pagination when calling paginate(). See that method for more details.
 *
 * @link http://book.cakephp.org/3.0/en/controllers/components/pagination.html
 */
class PaginatorComponent extends Component
{

    /**
     * Events supported by this component.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [];
    }

    /**
     * Constructor
     *
     * @param \Cake\Controller\ComponentRegistry $registry A ComponentRegistry this component can use to lazy load its components.
     * @param array $config Array of configuration settings.
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        $this->_paginator = new \Cake\ORM\Paginator($config);
        parent::__construct($registry, $config);
    }

    /**
     * Handles automatic pagination of model records.
     * ### Configuring pagination
     * When calling `paginate()` you can use the $settings parameter to pass in pagination settings.
     * These settings are used to build the queries made and control other pagination settings.
     * If your settings contain a key with the current table's alias. The data inside that key will be used.
     * Otherwise the top level configuration will be used.
     * ```
     *  $settings = [
     *    'limit' => 20,
     *    'maxLimit' => 100
     *  ];
     *  $results = $paginator->paginate($table, $settings);
     * ```
     * The above settings will be used to paginate any Table. You can configure Table specific settings by
     * keying the settings with the Table alias.
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
     * This would allow you to have different pagination settings for `Articles` and `Comments` tables.
     * ### Controlling sort fields
     * By default CakePHP will automatically allow sorting on any column on the table object being
     * paginated. Often times you will want to allow sorting on either associated columns or calculated
     * fields. In these cases you will need to define a whitelist of all the columns you wish to allow
     * sorting on. You can define the whitelist in the `$settings` parameter:
     * ```
     * $settings = [
     *   'Articles' => [
     *     'finder' => 'custom',
     *     'sortWhitelist' => ['title', 'author_id', 'comment_count'],
     *   ]
     * ];
     * ```
     * ### Paginating with custom finders
     * You can paginate with any find type defined on your table using the `finder` option.
     * ```
     *  $settings = [
     *    'Articles' => [
     *      'finder' => 'popular'
     *    ]
     *  ];
     *  $results = $paginator->paginate($table, $settings);
     * ```
     * Would paginate using the `find('popular')` method.
     * You can also pass an already created instance of a query to this method:
     * ```
     * $query = $this->Articles->find('popular')->matching('Tags', function ($q) {
     *   return $q->where(['name' => 'CakePHP'])
     * });
     * $results = $paginator->paginate($query);
     * ```
     *
     * @param \Cake\Datasource\RepositoryInterface|\Cake\ORM\Query $object The table or query to paginate.
     * @param array $settings The settings/configuration used for pagination.
     * @return array Query results.
     */
    public function paginate($object, array $settings = [])
    {
        $this->_paginator->setParams($this->request->query);
        $result = $this->_paginator->paginate($object, $settings);
        $pagingParams = $this->_paginator->getPagingParams();

        if (!isset($this->request['paging'])) {
            $this->request['paging'] = [];
        }
        $this->request['paging'] = [$pagingParams['alias'] => $pagingParams] + (array)$this->request['paging'];

        if ($pagingParams['requestedPage'] > $pagingParams['page']) {
            throw new NotFoundException();
        }

        return $result;
    }

    /**
     * Overloading the config method to be able to pass the config to the paginator.
     *
     * @param string|array|null $key The key to get/set, or a complete array of configs.
     * @param mixed|null $value The value to set.
     * @param bool $merge Whether to recursively merge or overwrite existing config, defaults to true.
     * @return mixed Config value being read, or the object itself on write operations.
     * @throws \Cake\Core\Exception\Exception When trying to set a key that is invalid.
     */
    public function config($key = null, $value = null, $merge = true)
    {
        return $this->_paginator->config($key, $value, $merge);
    }

    /**
     * Using the magic call as a proxy to the refactored paginator.
     *
     * @param string $method Method name.
     * @param array $args The arguments passed to the called method.
     * @throws \BadMethodCallException When the method does not exist.
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (method_exists($this->_paginator, $method)) {
            return call_user_func_array([$this->_paginator, $method], $args);
        }
        throw new BadMethodCallException(sprintf('Method %s does not exist.', $method));
    }
}
