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

/**
 * This trait contain common logic for paginator
 */
trait PaginatorTrait
{
    /**
     * Default pagination settings.
     *
     * When calling paginate() these settings will be merged with the configuration
     * you provide.
     *
     * - `maxLimit` - The maximum limit users can choose to view. Defaults to 100
     * - `limit` - The initial number of items per page. Defaults to 20.
     * - `page` - The starting page, defaults to 1.
     * - `whitelist` - A list of parameters users are allowed to set using request
     *   parameters. Modifying this list will allow users to have more influence
     *   over pagination, be careful with what you permit.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'page' => 1,
        'limit' => 20,
        'maxLimit' => 100,
        'whitelist' => ['limit', 'sort', 'page', 'direction']
    ];

    /**
     * Paging params after pagination operation is done.
     *
     * @var array
     */
    protected $_pagingParams = [];

    /**
     * Extracts the finder name and options out of the provided pagination options.
     *
     * @param array $options the pagination options.
     * @return array An array containing in the first position the finder name
     *   and in the second the options to be passed to it.
     */
    protected function _extractFinder($options)
    {
        $type = !empty($options['finder']) ? $options['finder'] : 'all';
        unset($options['finder'], $options['maxLimit']);

        if (is_array($type)) {
            $options = (array)current($type) + $options;
            $type = key($type);
        }

        return [$type, $options];
    }

    /**
     * Get paging params after pagination operation.
     *
     * @return array
     */
    public function getPagingParams()
    {
        return $this->_pagingParams;
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
     * combined together. You can change config value `whitelist` to modify
     * which options/values can be set using request parameters.
     *
     * @param array $params Request params.
     * @param array $settings The settings to merge with the request data.
     * @return array Array of merged options.
     */
    public function mergeOptions($params, $settings)
    {
        if (!empty($settings['scope'])) {
            $scope = $settings['scope'];
            $params = !empty($params[$scope]) ? (array)$params[$scope] : [];
        }
        $params = array_intersect_key($params, array_flip($this->getConfig('whitelist')));

        return array_merge($settings, $params);
    }

    /**
     * Get the settings for a $model. If there are no settings for a specific
     * repository, the general settings will be used.
     *
     * @param string $alias Model name to get settings for.
     * @param array $settings The settings which is used for combining.
     * @return array An array of pagination settings for a model,
     *   or the general settings.
     */
    public function getDefaults($alias, $settings)
    {
        if (isset($settings[$alias])) {
            $settings = $settings[$alias];
        }

        $defaults = $this->getConfig();
        $maxLimit = isset($settings['maxLimit']) ? $settings['maxLimit'] : $defaults['maxLimit'];
        $limit = isset($settings['limit']) ? $settings['limit'] : $defaults['limit'];

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
     * You can use the whitelist parameter to control which columns/fields are
     * available for sorting via URL parameters. This helps prevent users from ordering large
     * result sets on un-indexed values.
     *
     * If you need to sort on associated columns or synthetic properties you
     * will need to use a whitelist.
     *
     * Any columns listed in the sort whitelist will be implicitly trusted.
     * You can use this to sort on synthetic columns, or columns added in custom
     * find operations that may not exist in the schema.
     *
     * The default order options provided to paginate() will be merged with the user's
     * requested sorting field/direction.
     *
     * @param \Cake\Datasource\RepositoryInterface $object Repository object.
     * @param array $options The pagination options being used for this request.
     * @return array An array of options with sort + direction removed and
     *   replaced with order if possible.
     */
    public function validateSort(RepositoryInterface $object, array $options)
    {
        if (isset($options['sort'])) {
            $direction = null;
            if (isset($options['direction'])) {
                $direction = strtolower($options['direction']);
            }
            if (!in_array($direction, ['asc', 'desc'])) {
                $direction = 'asc';
            }

            $order = (isset($options['order']) && is_array($options['order'])) ? $options['order'] : [];
            if ($order && $options['sort'] && strpos($options['sort'], '.') === false) {
                $order = $this->_removeAliases($order, $object->getAlias());
            }

            $options['order'] = [$options['sort'] => $direction] + $order;
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

        $inWhitelist = false;
        if (isset($options['sortWhitelist'])) {
            $field = key($options['order']);
            $inWhitelist = in_array($field, $options['sortWhitelist'], true);
            if (!$inWhitelist) {
                $options['order'] = [];
                $options['sort'] = null;

                return $options;
            }
        }

        if ($options['sort'] === null
            && count($options['order']) === 1
            && !is_numeric(key($options['order']))
        ) {
            $options['sort'] = key($options['order']);
        }

        $options['order'] = $this->_prefix($object, $options['order'], $inWhitelist);

        return $options;
    }

    /**
     * Remove alias if needed.
     *
     * @param array $fields Current fields
     * @param string $model Current model alias
     * @return array $fields Unaliased fields where applicable
     */
    protected function _removeAliases($fields, $model)
    {
        $result = [];
        foreach ($fields as $field => $sort) {
            if (strpos($field, '.') === false) {
                $result[$field] = $sort;
                continue;
            }

            list ($alias, $currentField) = explode('.', $field);

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
     * @param bool $whitelisted Whether or not the field was whitelisted.
     * @return array Final order array.
     */
    protected function _prefix(RepositoryInterface $object, $order, $whitelisted = false)
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

            if (strpos($key, '.') !== false) {
                list($alias, $field) = explode('.', $key);
            }
            $correctAlias = ($tableAlias === $alias);

            if ($correctAlias && $whitelisted) {
                // Disambiguate fields in schema. As id is quite common.
                if ($object->hasField($field)) {
                    $field = $alias . '.' . $field;
                }
                $tableOrder[$field] = $value;
            } elseif ($correctAlias && $object->hasField($field)) {
                $tableOrder[$tableAlias . '.' . $field] = $value;
            } elseif (!$correctAlias && $whitelisted) {
                $tableOrder[$alias . '.' . $field] = $value;
            }
        }

        return $tableOrder;
    }

    /**
     * Check the limit parameter and ensure it's within the maxLimit bounds.
     *
     * @param array $options An array of options with a limit key to be checked.
     * @return array An array of options for pagination.
     */
    public function checkLimit(array $options)
    {
        $options['limit'] = (int)$options['limit'];
        if (empty($options['limit']) || $options['limit'] < 1) {
            $options['limit'] = 1;
        }
        $options['limit'] = max(min($options['limit'], $options['maxLimit']), 1);

        return $options;
    }
}
