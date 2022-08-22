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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Datasource\Paging\Exception\PageOutOfBoundsException;
use Cake\Datasource\Paging\NumericPaginator;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\NotFoundException;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * This component is used to handle automatic model data pagination. The primary way to use this
 * component is to call the paginate() method. There is a convenience wrapper on Controller as well.
 *
 * ### Configuring pagination
 *
 * You configure pagination when calling paginate(). See that method for more details.
 *
 * @link https://book.cakephp.org/4/en/controllers/components/pagination.html
 * @mixin \Cake\Datasource\Paging\NumericPaginator
 * @deprecated 4.4.0 Use Cake\Datasource\Paging\Paginator directly.
 */
class PaginatorComponent extends Component
{
    /**
     * Datasource paginator instance.
     *
     * @var \Cake\Datasource\Paging\NumericPaginator
     */
    protected $_paginator;

    /**
     * @inheritDoc
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        deprecationWarning(
            'PaginatorComponent is deprecated, use a Cake\Datasource\Pagination\NumericPaginator instance directly.'
        );

        if (!empty($this->_defaultConfig)) {
            throw new UnexpectedValueException('Default configuration must be set using a custom Paginator class.');
        }

        if (isset($config['paginator'])) {
            $config['className'] = $config['paginator'];
            deprecationWarning(
                '`paginator` option is deprecated,'
                . ' use `className` instead a specify a paginator name/FQCN.'
            );
        }

        if (isset($config['className'])) {
            if (!$config['className'] instanceof NumericPaginator) {
                throw new InvalidArgumentException('Paginator must be an instance of ' . NumericPaginator::class);
            }
            $this->_paginator = $config['className'];
            unset($config['className']);
        } else {
            $this->_paginator = new NumericPaginator();
        }

        parent::__construct($registry, $config);
    }

    /**
     * Events supported by this component.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [];
    }

    /**
     * Handles automatic pagination of model records.
     *
     * ### Configuring pagination
     *
     * When calling `paginate()` you can use the $settings parameter to pass in pagination settings.
     * These settings are used to build the queries made and control other pagination settings.
     *
     * If your settings contain a key with the current table's alias. The data inside that key will be used.
     * Otherwise, the top level configuration will be used.
     *
     * ```
     *  $settings = [
     *    'limit' => 20,
     *    'maxLimit' => 100
     *  ];
     *  $results = $paginator->paginate($table, $settings);
     * ```
     *
     * The above settings will be used to paginate any Table. You can configure Table specific settings by
     * keying the settings with the Table alias.
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
     * This would allow you to have different pagination settings for `Articles` and `Comments` tables.
     *
     * ### Controlling sort fields
     *
     * By default CakePHP will automatically allow sorting on any column on the table object being
     * paginated. Often times you will want to allow sorting on either associated columns or calculated
     * fields. In these cases you will need to define an allowed list of fields you wish to allow
     * sorting on. You can define the allowed fields in the `$settings` parameter:
     *
     * ```
     * $settings = [
     *   'Articles' => [
     *     'finder' => 'custom',
     *     'sortableFields' => ['title', 'author_id', 'comment_count'],
     *   ]
     * ];
     * ```
     *
     * Passing an empty array as allowed list disallows sorting altogether.
     *
     * ### Paginating with custom finders
     *
     * You can paginate with any find type defined on your table using the `finder` option.
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
     * By using request parameter scopes you can paginate multiple queries in the same controller action:
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
     * @param \Cake\Datasource\RepositoryInterface|\Cake\Datasource\QueryInterface $object Table or query to paginate.
     * @param array<string, mixed> $settings The settings/configuration used for pagination.
     * @return \Cake\Datasource\ResultSetInterface Query results
     * @throws \Cake\Http\Exception\NotFoundException
     */
    public function paginate(object $object, array $settings = []): ResultSetInterface
    {
        $request = $this->_registry->getController()->getRequest();

        try {
            $results = $this->_paginator->paginate(
                $object,
                $request->getQueryParams(),
                $settings
            );

            $this->_setPagingParams();
        } catch (PageOutOfBoundsException $e) {
            $this->_setPagingParams();

            throw new NotFoundException(null, null, $e);
        }

        return $results;
    }

    /**
     * Merges the various options that Pagination uses.
     * Pulls settings together from the following places:
     *
     * - General pagination settings
     * - Model specific settings.
     * - Request parameters
     *
     * The result of this method is the aggregate of all the option sets combined together. You can change
     * config value `allowedParameters` to modify which options/values can be set using request parameters.
     *
     * @param string $alias Model alias being paginated, if the general settings has a key with this value
     *   that key's settings will be used for pagination instead of the general ones.
     * @param array<string, mixed> $settings The settings to merge with the request data.
     * @return array<string, mixed> Array of merged options.
     */
    public function mergeOptions(string $alias, array $settings): array
    {
        $request = $this->_registry->getController()->getRequest();

        return $this->_paginator->mergeOptions(
            $request->getQueryParams(),
            $this->_paginator->getDefaults($alias, $settings)
        );
    }

    /**
     * Set paginator instance.
     *
     * @param \Cake\Datasource\Paging\NumericPaginator $paginator Paginator instance.
     * @return $this
     */
    public function setPaginator(NumericPaginator $paginator)
    {
        $this->_paginator = $paginator;

        return $this;
    }

    /**
     * Get paginator instance.
     *
     * @return \Cake\Datasource\Paging\NumericPaginator
     */
    public function getPaginator(): NumericPaginator
    {
        return $this->_paginator;
    }

    /**
     * Set paging params to request instance.
     *
     * @return void
     */
    protected function _setPagingParams(): void
    {
        $controller = $this->getController();
        $request = $controller->getRequest();
        $paging = $this->_paginator->getPagingParams() + (array)$request->getAttribute('paging', []);

        $controller->setRequest($request->withAttribute('paging', $paging));
    }

    /**
     * Proxy setting config options to Paginator.
     *
     * @param array<string, mixed>|string $key The key to set, or a complete array of configs.
     * @param mixed|null $value The value to set.
     * @param bool $merge Whether to recursively merge or overwrite existing config, defaults to true.
     * @return $this
     */
    public function setConfig($key, $value = null, $merge = true)
    {
        $this->_paginator->setConfig($key, $value, $merge);

        return $this;
    }

    /**
     * Proxy getting config options to Paginator.
     *
     * @param string|null $key The key to get or null for the whole config.
     * @param mixed $default The return value when the key does not exist.
     * @return mixed Config value being read.
     */
    public function getConfig(?string $key = null, $default = null)
    {
        return $this->_paginator->getConfig($key, $default);
    }

    /**
     * Proxy setting config options to Paginator.
     *
     * @param array<string, mixed>|string $key The key to set, or a complete array of configs.
     * @param mixed|null $value The value to set.
     * @return $this
     */
    public function configShallow($key, $value = null)
    {
        $this->_paginator->configShallow($key, null);

        return $this;
    }

    /**
     * Proxy method calls to Paginator.
     *
     * @param string $method Method name.
     * @param array $args Method arguments.
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        return $this->_paginator->{$method}(...$args);
    }
}
