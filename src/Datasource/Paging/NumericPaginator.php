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
 * @since         3.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource\Paging;

use Cake\Core\Exception\CakeException;
use Cake\Core\InstanceConfigTrait;
use Cake\Datasource\Paging\Exception\PageOutOfBoundsException;
use Cake\Datasource\QueryInterface;
use Cake\Datasource\RepositoryInterface;
use Cake\Datasource\ResultSetInterface;
use function Cake\Core\triggerWarning;

/**
 * This class is used to handle automatic model data pagination.
 */
class NumericPaginator implements PaginatorInterface
{
    use InstanceConfigTrait;

    /**
     * Default pagination settings.
     *
     * When calling paginate() these settings will be merged with the configuration
     * you provide.
     *
     * - `maxLimit` - The maximum limit users can choose to view. Defaults to 100
     * - `limit` - The initial number of items per page. Defaults to 20.
     * - `page` - The starting page, defaults to 1.
     * - `allowedParameters` - A list of parameters users are allowed to set using request
     *   parameters. Modifying this list will allow users to have more influence
     *   over pagination, be careful with what you permit.
     * - `sortableFields` - Controls which fields can be used for sorting. Accepts multiple formats:
     *   - Simple array: A list of field names that can be sorted. By default all table
     *     columns can be used. Use this to restrict sorting to specific fields. An empty
     *     array will disable sorting altogether.
     *   - Map with SortField objects: A map of sort keys to their corresponding database fields.
     *     Allows creating friendly sort keys that map to one or more actual fields. Supports
     *     simple mapping, multi-column sorting, locked directions, and default directions.
     *     Can accept a callable that receives a SortableFieldsBuilder instance.
     *
     *   Examples:
     *   ```
     *   // Simple array (traditional)
     *   'sortableFields' => ['title', 'created', 'author_id']
     *
     *   // Map with SortField objects
     *   'sortableFields' => [
     *       'name' => 'Users.name',
     *       'newest' => [
     *           SortField::desc('created'),
     *           SortField::asc('title'),
     *       ],
     *   ]
     *
     *   // Callable with builder
     *   'sortableFields' => function(SortableFieldsBuilder $builder) {
     *       return $builder
     *           ->add('name', SortField::asc('Users.name'))
     *           ->add('popularity', SortField::desc('score', locked: true), 'created');
     *   }
     *   ```
     * - `finder` - The table finder to use. Defaults to `all`.
     * - `scope` - If specified this scope will be used to get the paging options
     *   from the query params passed to paginate(). Scopes allow namespacing the
     *   paging options and allows paginating multiple models in the same action.
     *   Default `null`.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'page' => 1,
        'limit' => 20,
        'maxLimit' => 100,
        'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
        'sortableFields' => null,
        'finder' => 'all',
        'scope' => null,
    ];

    /**
     * Calculated paging params.
     *
     * @var array
     */
    protected array $pagingParams = [
        'limit' => null,
        'maxLimit' => null,
        'count' => null,
        'totalCount' => null,
        'perPage' => null,
        'pageCount' => null,
        'currentPage' => null,
        'requestedPage' => null,
        'start' => null,
        'end' => null,
        'hasPrevPage' => null,
        'hasNextPage' => null,
        'sort' => null,
        'sortDefault' => null,
        'direction' => null,
        'directionDefault' => null,
        'completeSort' => null,
        'alias' => null,
        'scope' => null,
    ];

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
     * inside that key will be used. Otherwise, the top level configuration will
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
     * you will need to define an allowed list of all the columns you wish to allow
     * sorting on. You can define the allowed sort fields in the `$settings` parameter:
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
     * Passing an empty array as sortableFields disallows sorting altogether.
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
     * @param mixed $target The repository or query
     *   to paginate.
     * @param array $params Request params
     * @param array $settings The settings/configuration used for pagination.
     * @return \Cake\Datasource\Paging\PaginatedInterface
     * @throws \Cake\Datasource\Paging\Exception\PageOutOfBoundsException
     */
    public function paginate(
        mixed $target,
        array $params = [],
        array $settings = [],
    ): PaginatedInterface {
        $query = null;
        if ($target instanceof QueryInterface) {
            $query = $target;
            $target = $query->getRepository();
            if ($target === null) {
                throw new CakeException('No repository set for query.');
            }
        }

        assert(
            $target instanceof RepositoryInterface,
            'Pagination target must be an instance of `' . QueryInterface::class
                . '` or `' . RepositoryInterface::class . '`.',
        );

        $data = $this->extractData($target, $params, $settings);
        $query = $this->getQuery($target, $query, $data);

        $countQuery = clone $query;
        $items = $this->getItems($query, $data);
        $this->pagingParams['count'] = count($items);
        $this->pagingParams['totalCount'] = $this->getCount($countQuery, $data);

        $pagingParams = $this->buildParams($data);
        if ($pagingParams['requestedPage'] > $pagingParams['currentPage']) {
            throw new PageOutOfBoundsException([
                'requestedPage' => $pagingParams['requestedPage'],
                'pagingParams' => $pagingParams,
            ]);
        }

        return $this->buildPaginated($items, $pagingParams);
    }

    /**
     * Build paginated result set.
     *
     * @param \Cake\Datasource\ResultSetInterface $items
     * @param array $pagingParams
     * @return \Cake\Datasource\Paging\PaginatedInterface
     */
    protected function buildPaginated(ResultSetInterface $items, array $pagingParams): PaginatedInterface
    {
        return new PaginatedResultSet($items, $pagingParams);
    }

    /**
     * Get query for fetching paginated results.
     *
     * @param \Cake\Datasource\RepositoryInterface $object Repository instance.
     * @param \Cake\Datasource\QueryInterface|null $query Query Instance.
     * @param array<string, mixed> $data Pagination data.
     * @return \Cake\Datasource\QueryInterface
     */
    protected function getQuery(RepositoryInterface $object, ?QueryInterface $query, array $data): QueryInterface
    {
        $options = $data['options'];
        $queryOptions = array_intersect_key(
            $options,
            ['order' => null, 'page' => null, 'limit' => null],
        );

        $args = [];
        $type = $options['finder'] ?? null;
        if (is_array($type)) {
            $args = (array)current($type);
            $type = key($type);
        }

        if ($query === null) {
            $query = $object->find($type ?? 'all', ...$args);
        } elseif ($type !== null) {
            $query->find($type, ...$args);
        }

        $query->applyOptions($queryOptions);

        return $query;
    }

    /**
     * Get paginated items.
     *
     * @param \Cake\Datasource\QueryInterface $query Query to fetch items.
     * @param array $data Paging data.
     * @return \Cake\Datasource\ResultSetInterface
     */
    protected function getItems(QueryInterface $query, array $data): ResultSetInterface
    {
        return $query->all();
    }

    /**
     * Get total count of records.
     *
     * @param \Cake\Datasource\QueryInterface $query Query instance.
     * @param array $data Pagination data.
     * @return int|null
     */
    protected function getCount(QueryInterface $query, array $data): ?int
    {
        return $query->count();
    }

    /**
     * Extract pagination data needed
     *
     * @param \Cake\Datasource\RepositoryInterface $object The repository object.
     * @param array<string, mixed> $params Request params
     * @param array<string, mixed> $settings The settings/configuration used for pagination.
     * @return array
     */
    protected function extractData(RepositoryInterface $object, array $params, array $settings): array
    {
        $alias = $object->getAlias();
        $defaults = $this->getDefaults($alias, $settings);

        $validSettings = array_keys($this->_defaultConfig);
        $validSettings[] = 'order';
        $extraSettings = array_diff_key($defaults, array_flip($validSettings));
        if ($extraSettings) {
            triggerWarning(
                'Passing query options as paginator settings is no longer supported.'
                . ' Use a custom finder through the `finder` config or pass a SelectQuery instance to paginate().'
                . ' Extra keys found are: `' . implode('`, `', array_keys($extraSettings)) . '`.',
            );
        }

        $options = $this->mergeOptions($params, $defaults);
        $options = $this->validateSort($object, $options);
        $options = $this->checkLimit($options);

        $options['page'] = max((int)$options['page'], 1);

        return compact('defaults', 'options', 'alias');
    }

    /**
     * Build pagination params.
     *
     * @param array<string, mixed> $data Paginator data containing keys 'options',
     *  'defaults', 'alias'.
     * @return array<string, mixed> Paging params.
     */
    protected function buildParams(array $data): array
    {
        $this->pagingParams = [
            'perPage' => $data['options']['limit'],
            'requestedPage' => $data['options']['page'],
            'alias' => $data['alias'],
            'scope' => $data['options']['scope'],
            'maxLimit' => $data['options']['maxLimit'],
        ] + $this->pagingParams;

        $this->addPageCountParams($data);
        $this->addStartEndParams($data);
        $this->addPrevNextParams($data);
        $this->addSortingParams($data);

        $this->pagingParams['limit'] = $data['defaults']['limit'] != $data['options']['limit']
            ? $data['options']['limit']
            : null;

        // Add sortableFields configuration for view helpers
        if (isset($data['options']['sortableFields'])) {
            $sortableFields = $data['options']['sortableFields'];
            if ($sortableFields instanceof SortableFieldsBuilder) {
                $this->pagingParams['sortableFields'] = $sortableFields->toArray();
            }
        }

        return $this->pagingParams;
    }

    /**
     * Add "currentPage" and "pageCount" params.
     *
     * @param array $data Paginator data.
     * @return void
     */
    protected function addPageCountParams(array $data): void
    {
        $page = $data['options']['page'];
        $pageCount = null;

        if ($this->pagingParams['totalCount'] !== null) {
            $pageCount = max((int)ceil($this->pagingParams['totalCount'] / $this->pagingParams['perPage']), 1);
            $page = min($page, $pageCount);
        } elseif ($this->pagingParams['count'] === 0 && $this->pagingParams['requestedPage'] > 1) {
            $page = 1;
        }

        $this->pagingParams['currentPage'] = $page;
        $this->pagingParams['pageCount'] = $pageCount;
    }

    /**
     * Add "start" and "end" params.
     *
     * @param array $data Paginator data.
     * @return void
     */
    protected function addStartEndParams(array $data): void
    {
        $start = 0;
        $end = 0;
        if ($this->pagingParams['count'] > 0) {
            $start = (($this->pagingParams['currentPage'] - 1) * $this->pagingParams['perPage']) + 1;
            $end = $start + $this->pagingParams['count'] - 1;
        }

        $this->pagingParams['start'] = $start;
        $this->pagingParams['end'] = $end;
    }

    /**
     * Add "prevPage" and "nextPage" params.
     *
     * @param array $data Paging data.
     * @return void
     */
    protected function addPrevNextParams(array $data): void
    {
        $this->pagingParams['hasPrevPage'] = $this->pagingParams['currentPage'] > 1;
        if ($this->pagingParams['totalCount'] === null) {
            $this->pagingParams['hasNextPage'] = true;
        } else {
            $this->pagingParams['hasNextPage'] = $this->pagingParams['totalCount']
                > $this->pagingParams['currentPage'] * $this->pagingParams['perPage'];
        }
    }

    /**
     * Add sorting / ordering params.
     *
     * @param array $data Paging data.
     * @return void
     */
    protected function addSortingParams(array $data): void
    {
        $defaults = $data['defaults'];
        $order = (array)$data['options']['order'];
        $sortDefault = false;
        $directionDefault = false;

        if (!empty($defaults['order']) && count($defaults['order']) >= 1) {
            $sortDefault = key($defaults['order']);
            $directionDefault = current($defaults['order']);
        }
        if (isset($data['options']['sortDirection'])) {
            $direction = $data['options']['sortDirection'];
        } else {
            $direction = isset($data['options']['sort']) && count($order) ? current($order) : null;
        }

        $this->pagingParams = [
            'sort' => $data['options']['sort'],
            'direction' => $direction,
            'sortDefault' => $sortDefault,
            'directionDefault' => $directionDefault,
            'completeSort' => $order,
        ] + $this->pagingParams;
    }

    /**
     * Merges the various options that Paginator uses.
     * Pulls settings together from the following places:
     *
     * - General pagination settings
     * - Model specific settings.
     * - Request parameters
     *
     * The result of this method is the aggregate of all the option sets
     * combined together. You can change config value `allowedParameters` to modify
     * which options/values can be set using request parameters.
     *
     * @param array<string, mixed> $params Request params.
     * @param array $settings The settings to merge with the request data.
     * @return array<string, mixed> Array of merged options.
     */
    protected function mergeOptions(array $params, array $settings): array
    {
        if (!empty($settings['scope'])) {
            $scope = $settings['scope'];
            $params = !empty($params[$scope]) ? (array)$params[$scope] : [];
        }
        $params = array_intersect_key($params, array_flip($this->getConfig('allowedParameters')));

        return array_merge($settings, $params);
    }

    /**
     * Get the settings for a $model. If there are no settings for a specific
     * repository, the general settings will be used.
     *
     * @param string $alias Model name to get settings for.
     * @param array<string, mixed> $settings The settings which is used for combining.
     * @return array<string, mixed> An array of pagination settings for a model,
     *   or the general settings.
     */
    protected function getDefaults(string $alias, array $settings): array
    {
        if (isset($settings[$alias])) {
            $settings = $settings[$alias];
        }

        $defaults = $this->getConfig();

        $maxLimit = $settings['maxLimit'] ?? $defaults['maxLimit'];
        $limit = $settings['limit'] ?? $defaults['limit'];

        if ($limit > $maxLimit) {
            $limit = $maxLimit;
        }

        $settings['maxLimit'] = $maxLimit;
        $settings['limit'] = $limit;

        return $settings + $defaults;
    }

    /**
     * Validate that the desired sorting can be performed on the $object.
     *
     * Only fields or virtualFields can be sorted on. The direction param will
     * also be sanitized. Lastly sort + direction keys will be converted into
     * the model friendly order key.
     *
     /**
     * You can use the allowedParameters option to control which columns/fields are
     * available for sorting via URL parameters. This helps prevent users from ordering large
     * result sets on un-indexed values.
     *
     * If you need to sort on associated columns or synthetic properties you
     * will need to use the `sortableFields` option.
     *
     * Any columns listed in the allowed sort fields will be implicitly trusted.
     * You can use this to sort on synthetic columns, or columns added in custom
     * find operations that may not exist in the schema.
     *
     * The default order options provided to paginate() will be merged with the user's
     * requested sorting field/direction.
     *
     * @param \Cake\Datasource\RepositoryInterface $object Repository object.
     * @param array<string, mixed> $options The pagination options being used for this request.
     * @return array<string, mixed> An array of options with sort + direction removed and
     *   replaced with order if possible.
     */
    protected function validateSort(RepositoryInterface $object, array $options): array
    {
        // Check if we have sortableFields configured
        $sortableFields = $options['sortableFields'] ?? null;
        $builder = $sortableFields instanceof SortableFieldsBuilder
            ? $sortableFields
            : SortableFieldsBuilder::create($sortableFields);

        // Store the converted builder for later use in paging params
        if ($builder !== null) {
            $options['sortableFields'] = $builder;
        }

        $sortAllowed = $builder !== null;

        if (isset($options['sort'])) {
            // Parse sort and direction parameters
            $sortParams = $this->parseSortParams($options);

            // Update options with parsed sort key (handles combined format)
            $options['sort'] = $sortParams['sortKey'];

            if ($builder !== null) {
                // Use builder to resolve sort key
                $order = $builder->resolve(
                    $sortParams['sortKey'],
                    $sortParams['direction'],
                    $sortParams['directionSpecified'],
                );

                if ($order === null) {
                    // Invalid sort key, clear sort
                    $options['order'] = [];
                    $options['sort'] = null;
                    unset($options['direction']);

                    return $options;
                }

                // Merge with existing order - existing order comes AFTER our resolved order
                $existingOrder = isset($options['order']) && is_array($options['order']) ? $options['order'] : [];
                // Only keep fields from existing order that aren't already in our resolved order
                foreach ($existingOrder as $field => $dir) {
                    if (!isset($order[$field])) {
                        $order[$field] = $dir;
                    }
                }
                $options['order'] = $order;
                $options['sortDirection'] = $sortParams['direction'];
            } else {
                // No sortableFields configured - allow any field (default behavior)
                $order = isset($options['order']) && is_array($options['order']) ? $options['order'] : [];
                if ($order && $sortParams['sortKey'] && !str_contains($sortParams['sortKey'], '.')) {
                    $order = $this->_removeAliases($order, $object->getAlias());
                }

                $options['order'] = [$sortParams['sortKey'] => $sortParams['direction']] + $order;
            }
        } else {
            $options['sort'] = null;
        }

        unset($options['direction']);

        if (empty($options['order'])) {
            $options['order'] = [];
        }
        if (!is_array($options['order'])) {
            return $options;
        }

        if (
            $options['sort'] === null
            && count($options['order']) >= 1
            && !is_numeric(key($options['order']))
        ) {
            $options['sort'] = key($options['order']);
        }

        $options['order'] = $this->_prefix($object, $options['order'], $sortAllowed);

        return $options;
    }

    /**
     * Remove alias if needed.
     *
     * @param array<string, mixed> $fields Current fields
     * @param string $model Current model alias
     * @return array<string, mixed> $fields Unaliased fields where applicable
     */
    protected function _removeAliases(array $fields, string $model): array
    {
        $result = [];
        foreach ($fields as $field => $sort) {
            if (is_int($field)) {
                throw new CakeException(sprintf(
                    'The `order` config must be an associative array. Found invalid value with numeric key: `%s`',
                    $sort,
                ));
            }

            if (!str_contains($field, '.')) {
                $result[$field] = $sort;
                continue;
            }

            [$alias, $currentField] = explode('.', $field);

            if ($alias === $model) {
                $result[$currentField] = $sort;
                continue;
            }

            $result[$field] = $sort;
        }

        return $result;
    }

    /**
     * Prefixes the field with the table alias if possible.
     *
     * @param \Cake\Datasource\RepositoryInterface $object Repository object.
     * @param array $order Order array.
     * @param bool $allowed Whether the field was allowed.
     * @return array Final order array.
     */
    protected function _prefix(RepositoryInterface $object, array $order, bool $allowed = false): array
    {
        $tableAlias = $object->getAlias();
        $tableOrder = [];
        foreach ($order as $key => $value) {
            if (is_numeric($key)) {
                $tableOrder[] = $value;
                continue;
            }
            $field = $key;
            $alias = $tableAlias;

            if (str_contains($key, '.')) {
                [$alias, $field] = explode('.', $key);
            }
            $correctAlias = ($tableAlias === $alias);

            if ($correctAlias && $allowed) {
                // Disambiguate fields in schema. As id is quite common.
                if ($object->hasField($field)) {
                    $field = $alias . '.' . $field;
                }
                $tableOrder[$field] = $value;
            } elseif ($correctAlias && $object->hasField($field)) {
                $tableOrder[$tableAlias . '.' . $field] = $value;
            } elseif (!$correctAlias && $allowed) {
                $tableOrder[$alias . '.' . $field] = $value;
            }
        }

        return $tableOrder;
    }

    /**
     * Parse sort parameters from options.
     *
     * Extracts and normalizes sort key and direction from pagination options.
     * Supports both traditional format (?sort=field&direction=asc) and
     * combined format (?sort=field-asc).
     *
     * @param array<string, mixed> $options The options array
     * @return array{sortKey: string, direction: string, directionSpecified: bool}
     */
    protected function parseSortParams(array $options): array
    {
        $sortKey = $options['sort'];
        $direction = isset($options['direction']) ? strtolower($options['direction']) : SortField::ASC;
        $directionSpecified = isset($options['direction']);

        // Check for combined sort-direction format (e.g., 'title-asc' or 'title-desc')
        if (preg_match('/^(.+)-(asc|desc)$/i', $sortKey, $matches)) {
            $sortKey = $matches[1];
            $direction = strtolower($matches[2]);
            $directionSpecified = true;
        }

        // Validate direction
        if (!in_array($direction, [SortField::ASC, SortField::DESC], true)) {
            $direction = SortField::ASC;
        }

        return [
            'sortKey' => $sortKey,
            'direction' => $direction,
            'directionSpecified' => $directionSpecified,
        ];
    }

    /**
     * Check the limit parameter and ensure it's within the maxLimit bounds.
     *
     * @param array<string, mixed> $options An array of options with a limit key to be checked.
     * @return array<string, mixed> An array of options for pagination.
     */
    protected function checkLimit(array $options): array
    {
        $options['limit'] = (int)$options['limit'];
        if ($options['limit'] < 1) {
            $options['limit'] = 1;
        }
        $options['limit'] = max(min($options['limit'], $options['maxLimit']), 1);

        return $options;
    }
}
