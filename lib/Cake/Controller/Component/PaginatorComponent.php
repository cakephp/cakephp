<?php
/**
 * Paginator Component
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Controller.Component
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Hash', 'Utility');

/**
 * This component is used to handle automatic model data pagination.  The primary way to use this
 * component is to call the paginate() method. There is a convenience wrapper on Controller as well.
 *
 * ### Configuring pagination
 *
 * You configure pagination using the PaginatorComponent::$settings.  This allows you to configure
 * the default pagination behavior in general or for a specific model. General settings are used when there
 * are no specific model configuration, or the model you are paginating does not have specific settings.
 *
 * {{{
 *	$this->Paginator->settings = array(
 *		'limit' => 20,
 *		'maxLimit' => 100
 *	);
 * }}}
 *
 * The above settings will be used to paginate any model.  You can configure model specific settings by
 * keying the settings with the model name.
 *
 * {{{
 *	$this->Paginator->settings = array(
 *		'Post' => array(
 *			'limit' => 20,
 *			'maxLimit' => 100
 *		),
 *		'Comment' => array( ... )
 *	);
 * }}}
 *
 * This would allow you to have different pagination settings for `Comment` and `Post` models.
 *
 * @package       Cake.Controller.Component
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/pagination.html
 */
class PaginatorComponent extends Component {

/**
 * Pagination settings.  These settings control pagination at a general level.
 * You can also define sub arrays for pagination settings for specific models.
 *
 * - `maxLimit` The maximum limit users can choose to view. Defaults to 100
 * - `limit` The initial number of items per page.  Defaults to 20.
 * - `page` The starting page, defaults to 1.
 * - `paramType` What type of parameters you want pagination to use?
 *      - `named` Use named parameters / routed parameters.
 *      - `querystring` Use query string parameters.
 *
 * @var array
 */
	public $settings = array(
		'page' => 1,
		'limit' => 20,
		'maxLimit' => 100,
		'paramType' => 'named'
	);

/**
 * A list of parameters users are allowed to set using request parameters.  Modifying
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
 * @param array $whitelist List of allowed fields for ordering.  This allows you to prevent ordering
 *   on non-indexed, or undesirable columns.
 * @return array Model query results
 * @throws MissingModelException
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
		if ($type !== 'all') {
			$extra['type'] = $type;
		}

		if (intval($page) < 1) {
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

		if ($object->hasMethod('paginateCount')) {
			$count = $object->paginateCount($conditions, $recursive, $extra);
		} else {
			$parameters = compact('conditions');
			if ($recursive != $object->recursive) {
				$parameters['recursive'] = $recursive;
			}
			$count = $object->find('count', array_merge($parameters, $extra));
		}
		$pageCount = intval(ceil($count / $limit));
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
			'paramType' => $options['paramType']
		);
		if (!isset($this->Controller->request['paging'])) {
			$this->Controller->request['paging'] = array();
		}
		$this->Controller->request['paging'] = array_merge(
			(array)$this->Controller->request['paging'],
			array($object->alias => $paging)
		);

		if (
			!in_array('Paginator', $this->Controller->helpers) &&
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
				$object = $this->Controller->{$object}->{$assoc};
			} elseif (
				$assoc && isset($this->Controller->{$this->Controller->modelClass}) &&
				isset($this->Controller->{$this->Controller->modelClass}->{$assoc}
			)) {
				$object = $this->Controller->{$this->Controller->modelClass}->{$assoc};
			} elseif (isset($this->Controller->{$object})) {
				$object = $this->Controller->{$object};
			} elseif (
				isset($this->Controller->{$this->Controller->modelClass}) && isset($this->Controller->{$this->Controller->modelClass}->{$object}
			)) {
				$object = $this->Controller->{$this->Controller->modelClass}->{$object};
			}
		} elseif (empty($object) || $object === null) {
			if (isset($this->Controller->{$this->Controller->modelClass})) {
				$object = $this->Controller->{$this->Controller->modelClass};
			} else {
				$className = null;
				$name = $this->Controller->uses[0];
				if (strpos($this->Controller->uses[0], '.') !== false) {
					list($name, $className) = explode('.', $this->Controller->uses[0]);
				}
				if ($className) {
					$object = $this->Controller->{$className};
				} else {
					$object = $this->Controller->{$name};
				}
			}
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
 * The result of this method is the aggregate of all the option sets combined together.  You can change
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
		$request = array_intersect_key($request, array_flip($this->whitelist));
		return array_merge($defaults, $request);
	}

/**
 * Get the default settings for a $model.  If there are no settings for a specific model, the general settings
 * will be used.
 *
 * @param string $alias Model name to get default settings for.
 * @return array An array of pagination defaults for a model, or the general settings.
 */
	public function getDefaults($alias) {
		if (isset($this->settings[$alias])) {
			$defaults = $this->settings[$alias];
		} else {
			$defaults = $this->settings;
		}
		return array_merge(
			array('page' => 1, 'limit' => 20, 'maxLimit' => 100, 'paramType' => 'named'),
			$defaults
		);
	}

/**
 * Validate that the desired sorting can be performed on the $object.  Only fields or
 * virtualFields can be sorted on.  The direction param will also be sanitized.  Lastly
 * sort + direction keys will be converted into the model friendly order key.
 *
 * You can use the whitelist parameter to control which columns/fields are available for sorting.
 * This helps prevent users from ordering large result sets on un-indexed values.
 *
 * @param Model $object The model being paginated.
 * @param array $options The pagination options being used for this request.
 * @param array $whitelist The list of columns that can be used for sorting.  If empty all keys are allowed.
 * @return array An array of options with sort + direction removed and replaced with order if possible.
 */
	public function validateSort($object, $options, $whitelist = array()) {
		if (isset($options['sort'])) {
			$direction = null;
			if (isset($options['direction'])) {
				$direction = strtolower($options['direction']);
			}
			if ($direction != 'asc' && $direction != 'desc') {
				$direction = 'asc';
			}
			$options['order'] = array($options['sort'] => $direction);
		}

		if (!empty($whitelist) && isset($options['order']) && is_array($options['order'])) {
			$field = key($options['order']);
			if (!in_array($field, $whitelist)) {
				$options['order'] = null;
			}
		}

		if (!empty($options['order']) && is_array($options['order'])) {
			$order = array();
			foreach ($options['order'] as $key => $value) {
				$field = $key;
				$alias = $object->alias;
				if (strpos($key, '.') !== false) {
					list($alias, $field) = explode('.', $key);
				}

				if ($object->hasField($field)) {
					$order[$alias . '.' . $field] = $value;
				} elseif ($object->hasField($key, true)) {
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
	public function checkLimit($options) {
		$options['limit'] = (int)$options['limit'];
		if (empty($options['limit']) || $options['limit'] < 1) {
			$options['limit'] = 1;
		}
		$options['limit'] = min($options['limit'], $options['maxLimit']);
		return $options;
	}

}
