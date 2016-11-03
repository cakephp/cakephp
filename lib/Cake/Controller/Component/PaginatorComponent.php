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
 * @package       Cake.Controller.Component
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Component', 'Controller');
App::uses('Hash', 'Utility');

/**
 * This component is used to handle automatic model data pagination. The primary way to use this
 * component is to call the paginate() method. There is a convenience wrapper on Controller as well.
 *
 * ### Configuring pagination
 *
 * You configure pagination using the PaginatorComponent::$settings. This allows you to configure
 * the default pagination behavior in general or for a specific model. General settings are used when there
 * are no specific model configuration, or the model you are paginating does not have specific settings.
 *
 * ```
 *	$this->Paginator->settings = array(
 *		'limit' => 20,
 *		'maxLimit' => 100
 *	);
 * ```
 *
 * The above settings will be used to paginate any model. You can configure model specific settings by
 * keying the settings with the model name.
 *
 * ```
 *	$this->Paginator->settings = array(
 *		'Post' => array(
 *			'limit' => 20,
 *			'maxLimit' => 100
 *		),
 *		'Comment' => array( ... )
 *	);
 * ```
 *
 * This would allow you to have different pagination settings for `Comment` and `Post` models.
 *
 * #### Paginating with custom finders
 *
 * You can paginate with any find type defined on your model using the `findType` option.
 *
 * ```
 * $this->Paginator->settings = array(
 *		'Post' => array(
 *			'findType' => 'popular'
 *		)
 * );
 * ```
 *
 * Would paginate using the `find('popular')` method.
 *
 * @package       Cake.Controller.Component
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/pagination.html
 */
class PaginatorComponent extends Component {

/**
 * Pagination settings. These settings control pagination at a general level.
 * You can also define sub arrays for pagination settings for specific models.
 *
 * - `maxLimit` The maximum limit users can choose to view. Defaults to 100
 * - `limit` The initial number of items per page. Defaults to 20.
 * - `page` The starting page, defaults to 1.
 * - `paramType` What type of parameters you want pagination to use?
 *      - `named` Use named parameters / routed parameters.
 *      - `querystring` Use query string parameters.
 * - `queryScope` By using request parameter scopes you can paginate multiple queries in the same controller action.
 *
 * ```
 * $paginator->paginate = array(
 *	'Article' => array('queryScope' => 'articles'),
 *	'Tag' => array('queryScope' => 'tags'),
 * );
 * ```
 *
 * Each of the above queries will use different query string parameter sets
 * for pagination data. An example URL paginating both results would be:
 *
 * ```
 * /dashboard/articles[page]:1/tags[page]:2
 * ```
 *
 * @var array
 */
	public $settings = array(
		'page' => 1,
		'limit' => 20,
		'maxLimit' => 100,
		'paramType' => 'named',
		'queryScope' => null
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
 * @param ComponentCollection $collection A ComponentCollection this component can use to lazy load its components
 * @param array $settings Array of configuration settings.
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		$settings = array_merge($this->settings, (array)$settings);
		$this->Controller = $collection->getController();
		parent::__construct($collection, $settings);
	}

/**
 * Handles automatic pagination of model records.
 *
 * @param Model|string $object Model to paginate (e.g: model instance, or 'Model', or 'Model.InnerModel')
 * @param string|array $scope Additional find conditions to use while paginating
 * @param array $whitelist List of allowed fields for ordering. This allows you to prevent ordering
 *   on non-indexed, or undesirable columns. See PaginatorComponent::validateSort() for additional details
 *   on how the whitelisting and sort field validation works.
 * @return array Model query results
 * @throws MissingModelException
 * @throws NotFoundException
 */
	public function paginate($object = null, $scope = array(), $whitelist = array()) {
		if (is_array($object)) {
			$whitelist = $scope;
			$scope = $object;
			$object = null;
		}

		$object = $this->_getObject($object);

		if (!is_object($object)) {
			throw new MissingModelException($object);
		}

		$options = $this->mergeOptions($object->alias);
		$options = $this->validateSort($object, $options, $whitelist);
		$options = $this->checkLimit($options);

		$conditions = $fields = $order = $limit = $page = $recursive = null;

		if (!isset($options['conditions'])) {
			$options['conditions'] = array();
		}

		$type = 'all';

		if (isset($options[0])) {
			$type = $options[0];
			unset($options[0]);
		}

		extract($options);

		if (is_array($scope) && !empty($scope)) {
			$conditions = array_merge($conditions, $scope);
		} elseif (is_string($scope)) {
			$conditions = array($conditions, $scope);
		}
		if ($recursive === null) {
			$recursive = $object->recursive;
		}

		$extra = array_diff_key($options, compact(
			'conditions', 'fields', 'order', 'limit', 'page', 'recursive'
		));

		if (!empty($extra['findType'])) {
			$type = $extra['findType'];
			unset($extra['findType']);
		}

		if ($type !== 'all') {
			$extra['type'] = $type;
		}

		if ((int)$page < 1) {
			$page = 1;
		}
		$page = $options['page'] = (int)$page;

		if ($object->hasMethod('paginate')) {
			$results = $object->paginate(
				$conditions, $fields, $order, $limit, $page, $recursive, $extra
			);
		} else {
			$parameters = compact('conditions', 'fields', 'order', 'limit', 'page');
			if ($recursive != $object->recursive) {
				$parameters['recursive'] = $recursive;
			}
			$results = $object->find($type, array_merge($parameters, $extra));
		}
		$defaults = $this->getDefaults($object->alias);
		unset($defaults[0]);

		if (!$results) {
			$count = 0;
		} elseif ($object->hasMethod('paginateCount')) {
			$count = $object->paginateCount($conditions, $recursive, $extra);
		} elseif ($page === 1 && count($results) < $limit) {
			$count = count($results);
		} else {
			$parameters = compact('conditions');
			if ($recursive != $object->recursive) {
				$parameters['recursive'] = $recursive;
			}
			$count = $object->find('count', array_merge($parameters, $extra));
		}
		$pageCount = (int)ceil($count / $limit);
		$requestedPage = $page;
		$page = max(min($page, $pageCount), 1);

		$paging = array(
			'page' => $page,
			'current' => count($results),
			'count' => $count,
			'prevPage' => ($page > 1),
			'nextPage' => ($count > ($page * $limit)),
			'pageCount' => $pageCount,
			'order' => $order,
			'limit' => $limit,
			'options' => Hash::diff($options, $defaults),
			'paramType' => $options['paramType'],
			'queryScope' => $options['queryScope'],
		);

		if (!isset($this->Controller->request['paging'])) {
			$this->Controller->request['paging'] = array();
		}
		$this->Controller->request['paging'] = array_merge(
			(array)$this->Controller->request['paging'],
			array($object->alias => $paging)
		);

		if ($requestedPage > $page) {
			throw new NotFoundException();
		}

		if (!in_array('Paginator', $this->Controller->helpers) &&
			!array_key_exists('Paginator', $this->Controller->helpers)
		) {
			$this->Controller->helpers[] = 'Paginator';
		}
		return $results;
	}

/**
 * Get the object pagination will occur on.
 *
 * @param string|Model $object The object you are looking for.
 * @return mixed The model object to paginate on.
 */
	protected function _getObject($object) {
		if (is_string($object)) {
			$assoc = null;
			if (strpos($object, '.') !== false) {
				list($object, $assoc) = pluginSplit($object);
			}
			if ($assoc && isset($this->Controller->{$object}->{$assoc})) {
				return $this->Controller->{$object}->{$assoc};
			}
			if ($assoc && isset($this->Controller->{$this->Controller->modelClass}->{$assoc})) {
				return $this->Controller->{$this->Controller->modelClass}->{$assoc};
			}
			if (isset($this->Controller->{$object})) {
				return $this->Controller->{$object};
			}
			if (isset($this->Controller->{$this->Controller->modelClass}->{$object})) {
				return $this->Controller->{$this->Controller->modelClass}->{$object};
			}
		}
		if (empty($object) || $object === null) {
			if (isset($this->Controller->{$this->Controller->modelClass})) {
				return $this->Controller->{$this->Controller->modelClass};
			}

			$className = null;
			$name = $this->Controller->uses[0];
			if (strpos($this->Controller->uses[0], '.') !== false) {
				list($name, $className) = explode('.', $this->Controller->uses[0]);
			}
			if ($className) {
				return $this->Controller->{$className};
			}

			return $this->Controller->{$name};
		}
		return $object;
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
 * @return array Array of merged options.
 */
	public function mergeOptions($alias) {
		$defaults = $this->getDefaults($alias);
		switch ($defaults['paramType']) {
			case 'named':
				$request = $this->Controller->request->params['named'];
				break;
			case 'querystring':
				$request = $this->Controller->request->query;
				break;
		}
		if ($defaults['queryScope']) {
			$request = Hash::get($request, $defaults['queryScope'], array());
		}
		$request = array_intersect_key($request, array_flip($this->whitelist));
		return array_merge($defaults, $request);
	}

/**
 * Get the default settings for a $model. If there are no settings for a specific model, the general settings
 * will be used.
 *
 * @param string $alias Model name to get default settings for.
 * @return array An array of pagination defaults for a model, or the general settings.
 */
	public function getDefaults($alias) {
		$defaults = $this->settings;
		if (isset($this->settings[$alias])) {
			$defaults = $this->settings[$alias];
		}
		$defaults += array(
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'paramType' => 'named',
			'queryScope' => null
		);
		return $defaults;
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
 * @param Model $object The model being paginated.
 * @param array $options The pagination options being used for this request.
 * @param array $whitelist The list of columns that can be used for sorting. If empty all keys are allowed.
 * @return array An array of options with sort + direction removed and replaced with order if possible.
 */
	public function validateSort(Model $object, array $options, array $whitelist = array()) {
		if (empty($options['order']) && is_array($object->order)) {
			$options['order'] = $object->order;
		}

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
			$order = array();
			foreach ($options['order'] as $key => $value) {
				if (is_int($key)) {
					$key = $value;
					$value = 'asc';
				}
				$field = $key;
				$alias = $object->alias;
				if (strpos($key, '.') !== false) {
					list($alias, $field) = explode('.', $key);
				}
				$correctAlias = ($object->alias === $alias);

				if ($correctAlias && $object->hasField($field)) {
					$order[$object->alias . '.' . $field] = $value;
				} elseif ($correctAlias && $object->hasField($key, true)) {
					$order[$field] = $value;
				} elseif (isset($object->{$alias}) && $object->{$alias}->hasField($field, true)) {
					$order[$alias . '.' . $field] = $value;
				}
			}
			$options['order'] = $order;
		}

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
