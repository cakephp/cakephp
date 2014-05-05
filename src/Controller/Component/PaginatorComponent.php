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

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Datasource\RepositoryInterface;
use Cake\Error;
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/pagination.html
 */
class PaginatorComponent extends Component {

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
	protected $_defaultConfig = array(
		'page' => 1,
		'limit' => 20,
		'maxLimit' => 100,
		'whitelist' => ['limit', 'sort', 'page', 'direction']
	);

/**
 * Handles automatic pagination of model records.
 *
 * ## Configuring pagination
 *
 * When calling `paginate()` you can use the $settings parameter to pass in pagination settings.
 * These settings are used to build the queries made and control other pagination settings.
 *
 * If your settings contain a key with the current table's alias. The data inside that key will be used.
 * Otherwise the top level configuration will be used.
 *
 * {{{
 *  $settings = [
 *    'limit' => 20,
 *    'maxLimit' => 100
 *  ];
 *  $results = $paginator->paginate($table, $settings);
 * }}}
 *
 * The above settings will be used to paginate any Table. You can configure Table specific settings by
 * keying the settings with the Table alias.
 *
 * {{{
 *  $settings = [
 *    'Articles' => [
 *      'limit' => 20,
 *      'maxLimit' => 100
 *    ],
 *    'Comments' => [ ... ]
 *  ];
 *  $results = $paginator->paginate($table, $settings);
 * }}}
 *
 * This would allow you to have different pagination settings for `Articles` and `Comments` tables.
 *
 * ### Controlling sort fields
 *
 * By default CakePHP will automatically allow sorting on any column on the table object being
 * paginated. Often times you will want to allow sorting on either associated columns or calculated
 * fields. In these cases you will need to define a whitelist of all the columns you wish to allow
 * sorting on. You can define the whitelist in the `$settings` parameter:
 *
 * {{{
 * $settings = [
 *   'Articles' => [
 *     'findType' => 'custom',
 *     'sortWhitelist' => ['title', 'author_id', 'comment_count'],
 *   ]
 * ];
 * }}}
 *
 * ### Paginating with custom finders
 *
 * You can paginate with any find type defined on your table using the `findType` option.
 *
 * {{{
 *  $settings = array(
 *    'Articles' => array(
 *      'findType' => 'popular'
 *    )
 *  );
 *  $results = $paginator->paginate($table, $settings);
 * }}}
 *
 * Would paginate using the `find('popular')` method.
 *
 * You can also pass an already created instance of a query to this method:
 *
 * {{{
 * $query = $this->Articles->find('popular')->matching('Tags', function($q) {
 *	return $q->where(['name' => 'CakePHP'])
 * });
 * $results = $paginator->paginate($query);
 * }}}
 *
 * @param \Cake\Datasource\RepositoryInterface|\Cake\ORM\Query $object The table or query to paginate.
 * @param array $settings The settings/configuration used for pagination.
 * @return array Query results
 * @throws \Cake\Error\NotFoundException
 */
	public function paginate($object, array $settings = []) {
		if ($object instanceof Query) {
			$query = $object;
			$object = $query->repository();
		}

		$alias = $object->alias();
		$options = $this->mergeOptions($alias, $settings);
		$options = $this->validateSort($object, $options);
		$options = $this->checkLimit($options);

		$options += ['page' => 1];
		$options['page'] = intval($options['page']) < 1 ? 1 : (int)$options['page'];

		$type = !empty($options['findType']) ? $options['findType'] : 'all';
		unset($options['findType'], $options['maxLimit']);

		if (empty($query)) {
			$query = $object->find($type);
		}

		$query->applyOptions($options);
		$results = $query->all();
		$numResults = count($results);
		$count = $numResults ? $query->count() : 0;

		$defaults = $this->getDefaults($alias, $settings);
		unset($defaults[0]);

		$page = $options['page'];
		$limit = $options['limit'];
		$pageCount = intval(ceil($count / $limit));
		$requestedPage = $page;
		$page = max(min($page, $pageCount), 1);
		$request = $this->_registry->getController()->request;

		$order = (array)$options['order'];
		$sortDefault = $directionDefault = false;
		if (!empty($defaults['order']) && count($defaults['order']) == 1) {
			$sortDefault = key($defaults['order']);
			$directionDefault = current($defaults['order']);
		}

		$paging = array(
			'findType' => $type,
			'page' => $page,
			'current' => $numResults,
			'count' => $count,
			'perPage' => $limit,
			'prevPage' => ($page > 1),
			'nextPage' => ($count > ($page * $limit)),
			'pageCount' => $pageCount,
			'sort' => key($order),
			'direction' => current($order),
			'limit' => $defaults['limit'] != $limit ? $limit : null,
			'sortDefault' => $sortDefault,
			'directionDefault' => $directionDefault
		);

		if (!isset($request['paging'])) {
			$request['paging'] = [];
		}
		$request['paging'] = array_merge(
			(array)$request['paging'],
			array($alias => $paging)
		);

		if ($requestedPage > $page) {
			throw new Error\NotFoundException();
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
 * config value `whitelist` to modify which options/values can be set using request parameters.
 *
 * @param string $alias Model alias being paginated, if the general settings has a key with this value
 *   that key's settings will be used for pagination instead of the general ones.
 * @param array $settings The settings to merge with the request data.
 * @return array Array of merged options.
 */
	public function mergeOptions($alias, $settings) {
		$defaults = $this->getDefaults($alias, $settings);
		$request = $this->_registry->getController()->request;
		$request = array_intersect_key($request->query, array_flip($this->_config['whitelist']));
		return array_merge($defaults, $request);
	}

/**
 * Get the default settings for a $model. If there are no settings for a specific model, the general settings
 * will be used.
 *
 * @param string $alias Model name to get default settings for.
 * @param array $defaults The defaults to use for combining settings.
 * @return array An array of pagination defaults for a model, or the general settings.
 */
	public function getDefaults($alias, $defaults) {
		if (isset($defaults[$alias])) {
			$defaults = $defaults[$alias];
		}
		if (isset($defaults['limit']) &&
			(empty($defaults['maxLimit']) || $defaults['limit'] > $defaults['maxLimit'])
		) {
			$defaults['maxLimit'] = $defaults['limit'];
		}
		return array_merge(
			array('page' => 1, 'limit' => 20, 'maxLimit' => 100),
			$defaults
		);
	}

/**
 * Validate that the desired sorting can be performed on the $object. Only fields or
 * virtualFields can be sorted on. The direction param will also be sanitized. Lastly
 * sort + direction keys will be converted into the model friendly order key.
 *
 * You can use the whitelist parameter to control which columns/fields are available for sorting.
 * This helps prevent users from ordering large result sets on un-indexed values.
 *
 * If you need to sort on associated columns or synthetic properties you will need to use a whitelist.
 *
 * Any columns listed in the sort whitelist will be implicitly trusted. You can use this to sort
 * on synthetic columns, or columns added in custom find operations that may not exist in the schema.
 *
 * @param Table $object The model being paginated.
 * @param array $options The pagination options being used for this request.
 * @return array An array of options with sort + direction removed and replaced with order if possible.
 */
	public function validateSort(Table $object, array $options) {
		if (isset($options['sort'])) {
			$direction = null;
			if (isset($options['direction'])) {
				$direction = strtolower($options['direction']);
			}
			if (!in_array($direction, array('asc', 'desc'))) {
				$direction = 'asc';
			}
			$options['order'] = array($options['sort'] => $direction);
		}
		unset($options['sort'], $options['direction']);

		if (empty($options['order'])) {
			$options['order'] = [];
		}
		if (!is_array($options['order'])) {
			return $options;
		}

		if (!empty($options['sortWhitelist'])) {
			$field = key($options['order']);
			$inWhitelist = in_array($field, $options['sortWhitelist'], true);
			if (!$inWhitelist) {
				$options['order'] = [];
			}
			return $options;
		}

		$tableAlias = $object->alias();
		$order = [];

		foreach ($options['order'] as $key => $value) {
			$field = $key;
			$alias = $tableAlias;
			if (is_numeric($key)) {
				$order[] = $value;
			} else {
				if (strpos($key, '.') !== false) {
					list($alias, $field) = explode('.', $key);
				}
				$correctAlias = ($tableAlias === $alias);

				if ($correctAlias && $object->hasField($field)) {
					$order[$tableAlias . '.' . $field] = $value;
				}
			}
		}
		$options['order'] = $order;

		return $options;
	}

/**
 * Check the limit parameter and ensure its within the maxLimit bounds.
 *
 * @param array $options An array of options with a limit key to be checked.
 * @return array An array of options for pagination
 */
	public function checkLimit(array $options) {
		$options['limit'] = (int)$options['limit'];
		if (empty($options['limit']) || $options['limit'] < 1) {
			$options['limit'] = 1;
		}
		$options['limit'] = min($options['limit'], $options['maxLimit']);
		return $options;
	}

}
