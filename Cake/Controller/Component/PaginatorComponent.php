<?php
/**
 * Paginator Component
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Error;
use Cake\ORM\Table;

/**
 * This component is used to handle automatic model data pagination. The primary way to use this
 * component is to call the paginate() method. There is a convenience wrapper on Controller as well.
 *
 * ### Configuring pagination
 *
 * You configure pagination when calling paginate(). See that method for more details.
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/pagination.html
 */
class PaginatorComponent extends Component {

/**
 * The current request instance.
 *
 * @var Cake\Network\Request
 */
	public $request;

/**
 * Default pagination settings.
 *
 * When calling paginate() these settings will be merged with the configuration
 * you provide.
 *
 * - `maxLimit` The maximum limit users can choose to view. Defaults to 100
 * - `limit` The initial number of items per page. Defaults to 20.
 * - `page` The starting page, defaults to 1.
 *
 * @var array
 */
	protected $_defaultConfig = array(
		'page' => 1,
		'limit' => 20,
		'maxLimit' => 100,
	);

/**
 * A list of parameters users are allowed to set using request parameters. Modifying
 * this list will allow users to have more influence over pagination,
 * be careful with what you permit.
 *
 * @var array
 */
	public $whitelist = array(
		'limit', 'sort', 'page', 'direction'
	);

/**
 * Constructor
 *
 * @param ComponentRegistry $collection A ComponentRegistry this component can use to lazy load its components
 * @param array $settings Array of configuration settings.
 */
	public function __construct(ComponentRegistry $collection, $settings = []) {
		$settings = array_merge($this->_defaultConfig, (array)$settings);
		$this->request = $collection->getController()->request;
		parent::__construct($collection, $settings);
	}

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
 *  $settings = array(
 *    'limit' => 20,
 *    'maxLimit' => 100
 *  );
 *  $results = $paginator->paginate($table, $settings);
 * }}}
 *
 * The above settings will be used to paginate any Table. You can configure Table specific settings by
 * keying the settings with the Table alias.
 *
 * {{{
 *  $settings = array(
 *    'Posts' => array(
 *      'limit' => 20,
 *      'maxLimit' => 100
 *    ),
 *    'Comments' => array( ... )
 *  );
 *  $results = $paginator->paginate($table, $settings);
 * }}}
 *
 * This would allow you to have different pagination settings for `Comments` and `Posts` tables.
 *
 * #### Paginating with custom finders
 *
 * You can paginate with any find type defined on your table using the `findType` option.
 *
 * {{{
 * $settings = array(
 *   'Post' => array(
 *     'findType' => 'popular'
 *   )
 * );
 * $results = $paginator->paginate($table, $settings);
 * }}}
 *
 * Would paginate using the `find('popular')` method.
 *
 * @param Table $object The table to paginate.
 * @param array $settings The settings/configuration used for pagination.
 * @param array $whitelist List of allowed fields for ordering. This allows you to prevent ordering
 *   on non-indexed, or undesirable columns. See PaginatorComponent::validateSort() for additional details
 *   on how the whitelisting and sort field validation works.
 * @return array Query results
 * @throws Cake\Error\MissingModelException
 * @throws Cake\Error\NotFoundException
 */
	public function paginate($object, $settings = array(), $whitelist = array()) {
		$alias = $object->alias();

		$options = $this->mergeOptions($alias, $settings);
		$options = $this->validateSort($object, $options, $whitelist);
		$options = $this->checkLimit($options);

		$conditions = $fields = $limit = $page = null;
		$order = [];

		if (!isset($options['conditions'])) {
			$options['conditions'] = [];
		}

		$type = 'all';

		if (isset($options[0])) {
			$type = $options[0];
			unset($options[0]);
		}

		extract($options);
		$extra = array_diff_key($options, compact(
			'conditions', 'fields', 'order', 'limit', 'page'
		));

		if (!empty($extra['findType'])) {
			$type = $extra['findType'];
		}
		unset($extra['findType'], $extra['maxLimit']);

		if (intval($page) < 1) {
			$page = 1;
		}
		$page = $options['page'] = (int)$page;

		$parameters = compact('conditions', 'fields', 'order', 'limit', 'page');
		$query = $object->find($type, array_merge($parameters, $extra));

		$results = $query->execute();
		$numResults = count($results);

		$defaults = $this->getDefaults($alias, $settings);
		unset($defaults[0]);

		if (!$numResults) {
			$count = 0;
		} else {
			$parameters = compact('conditions');
			$count = $object->find($type, array_merge($parameters, $extra))->count();
		}

		$pageCount = intval(ceil($count / $limit));
		$requestedPage = $page;
		$page = max(min($page, $pageCount), 1);
		if ($requestedPage > $page) {
			throw new Error\NotFoundException();
		}

		if (!is_array($order)) {
			$order = (array)$order;
		}
		reset($order);
		$paging = array(
			'findType' => $type,
			'page' => $page,
			'current' => $numResults,
			'count' => $count,
			'prevPage' => ($page > 1),
			'nextPage' => ($count > ($page * $limit)),
			'pageCount' => $pageCount,
			'sort' => key($order),
			'direction' => current($order),
			'limit' => $defaults['limit'] != $options['limit'] ? $options['limit'] : null,
		);

		if (!isset($this->request['paging'])) {
			$this->request['paging'] = array();
		}
		$this->request['paging'] = array_merge(
			(array)$this->request['paging'],
			array($alias => $paging)
		);
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
 * PaginatorComponent::$whitelist to modify which options/values can be set using request parameters.
 *
 * @param string $alias Model alias being paginated, if the general settings has a key with this value
 *   that key's settings will be used for pagination instead of the general ones.
 * @param array $settings The settings to merge with the request data.
 * @return array Array of merged options.
 */
	public function mergeOptions($alias, $settings) {
		$defaults = $this->getDefaults($alias, $settings);
		$request = $this->request->query;
		$request = array_intersect_key($request, array_flip($this->whitelist));
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
 * Any columns listed in the sort whitelist will be implicitly trusted. You can use this to sort
 * on synthetic columns, or columns added in custom find operations that may not exist in the schema.
 *
 * @param Table $object The model being paginated.
 * @param array $options The pagination options being used for this request.
 * @param array $whitelist The list of columns that can be used for sorting. If empty all keys are allowed.
 * @return array An array of options with sort + direction removed and replaced with order if possible.
 */
	public function validateSort(Table $object, array $options, array $whitelist = array()) {
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

		if (!empty($whitelist) && isset($options['order']) && is_array($options['order'])) {
			$field = key($options['order']);
			$inWhitelist = in_array($field, $whitelist, true);
			if (!$inWhitelist) {
				$options['order'] = null;
			}
			return $options;
		}

		if (!empty($options['order']) && is_array($options['order'])) {
			$tableAlias = $object->alias();
			$order = array();
			foreach ($options['order'] as $key => $value) {
				$field = $key;
				$alias = $tableAlias;
				if (strpos($key, '.') !== false) {
					list($alias, $field) = explode('.', $key);
				}
				$correctAlias = ($tableAlias == $alias);

				if ($correctAlias && $object->hasField($field)) {
					$order[$tableAlias . '.' . $field] = $value;
				} elseif ($correctAlias && $object->hasField($key, true)) {
					$order[$field] = $value;
				} elseif (isset($object->{$alias}) && $object->{$alias}->hasField($field, true)) {
					// TODO fix associated sorting.
					$order[$alias . '.' . $field] = $value;
				}
			}
			$options['order'] = $order;
		}
		unset($options['sort'], $options['direction']);

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
