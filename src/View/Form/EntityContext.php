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
 * @since         CakePHP(tm) v 3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Form;

use Cake\Collection\Collection;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\View\Form\ContextInterface;
use Traversable;

/**
 * Provides a form context around a single entity and its relations.
 * It also can be used as context around an array or iterator of entities.
 *
 * This class lets FormHelper interface with entities or collections
 * of entities.
 *
 * Important Keys:
 *
 * - `entity` The entity this context is operating on.
 * - `table` Either the ORM\Table instance to fetch schema/validators
 *   from, an array of table instances in the case of a form spanning
 *   multiple entities, or the name(s) of the table.
 *   If this is null the table name(s) will be determined using naming
 *   conventions.
 * - `validator` Either the Validation\Validator to use, or the name of the
 *   validation method to call on the table object. For example 'default'.
 *   Defaults to 'default'. Can be an array of table alias=>validators when
 *   dealing with associated forms.
 */
class EntityContext implements ContextInterface {

/**
 * The request object.
 *
 * @var \Cake\Network\Request
 */
	protected $_request;

/**
 * Context data for this object.
 *
 * @var array
 */
	protected $_context;

/**
 * The name of the top level entity/table object.
 *
 * @var string
 */
	protected $_rootName;

/**
 * Boolean to track whether or not the entity is a
 * collection.
 *
 * @var boolean
 */
	protected $_isCollection = false;

/**
 * A dictionary of tables
 *
 * @var array
 */
	protected $_tables = [];

/**
 * Constructor.
 *
 * @param \Cake\Network\Request
 * @param array
 */
	public function __construct(Request $request, array $context) {
		$this->_request = $request;
		$context += [
			'entity' => null,
			'table' => null,
			'validator' => [],
		];
		$this->_context = $context;
		$this->_prepare();
	}

/**
 * Prepare some additional data from the context.
 *
 * If the table option was provided to the constructor and it
 * was a string, ORM\TableRegistry will be used to get the correct table instance.
 *
 * If an object is provided as the table option, it will be used as is.
 *
 * If no table option is provided, the table name will be derived based on
 * naming conventions. This inference will work with a number of common objects
 * like arrays, Collection objects and ResultSets.
 *
 * @return void
 * @throws \RuntimeException When a table object cannot be located/inferred.
 */
	protected function _prepare() {
		$table = $this->_context['table'];
		$entity = $this->_context['entity'];
		if (empty($table)) {
			if (is_array($entity) || $entity instanceof Traversable) {
				$entity = (new Collection($entity))->first();
			}
			if ($entity instanceof Entity) {
				list($ns, $entityClass) = namespaceSplit(get_class($entity));
				$table = Inflector::pluralize($entityClass);
			}
		}
		if (is_string($table)) {
			$table = TableRegistry::get($table);
		}

		if (!is_object($table)) {
			throw new \RuntimeException(
				'Unable to find table class for current entity'
			);
		}
		$this->_isCollection = (
			is_array($entity) ||
			$entity instanceof Traversable
		);
		$alias = $this->_rootName = $table->alias();
		$this->_tables[$alias] = $table;
	}

/**
 * Get the primary key data for the context.
 *
 * Gets the primary key columns from the root entity's schema.
 *
 * @return boolean
 */
	public function primaryKey() {
		return (array)$this->_tables[$this->_rootName]->primaryKey();
	}

/**
 * {@inheritDoc}
 */
	public function isPrimaryKey($field) {
		$parts = explode('.', $field);
		$table = $this->_getTable($parts);
		$primaryKey = (array)$table->primaryKey();
		return in_array(array_pop($parts), $primaryKey);
	}

/**
 * Check whether or not this form is a create or update.
 *
 * If the context is for a single entity, the entity's isNew() method will
 * be used. If isNew() returns null, a create operation will be assumed.
 *
 * If the context is for a collection or array the first object in the
 * collection will be used.
 *
 * @return boolean
 */
	public function isCreate() {
		$entity = $this->_context['entity'];
		if (is_array($entity) || $entity instanceof Traversable) {
			$entity = (new Collection($entity))->first();
		}
		if ($entity instanceof Entity) {
			return $entity->isNew() !== false;
		}
		return true;
	}

/**
 * Get the value for a given path.
 *
 * Traverses the entity data and finds the value for $path.
 *
 * @param string $field The dot separated path to the value.
 * @return mixed The value of the field or null on a miss.
 */
	public function val($field) {
		if (empty($this->_context['entity'])) {
			return null;
		}
		$parts = explode('.', $field);
		$entity = $this->_getEntity($parts);
		if ($entity instanceof Entity) {
			return $entity->get(array_pop($parts));
		}
		return null;
	}

/**
 * Fetch the leaf entity for the given path.
 *
 * This method will traverse the given path and find the leaf
 * entity. If the path does not contain a leaf entity false
 * will be returned.
 *
 * @param array $path Each one of the parts in a path for a field name
 * @return \Cake\DataSource\EntityInterface|boolean
 * @throws \RuntimeException When properties cannot be read.
 */
	protected function _getEntity($path) {
		$oneElement = count($path) === 1;
		if ($oneElement && $this->_isCollection) {
			return false;
		}
		$entity = $this->_context['entity'];
		if ($oneElement) {
			return $entity;
		}

		if ($path[0] === $this->_rootName) {
			$path = array_slice($path, 1);
		}

		foreach ($path as $prop) {
			$next = $this->_getProp($entity, $prop);
			if (
				!is_array($next) &&
				!($next instanceof Traversable) &&
				!($next instanceof Entity)
			) {
				return $entity;
			}
			$entity = $next;
		}
		throw \RuntimeException(sprintf(
			'Unable to fetch property "%s"',
			implode(".", $path)
		));
	}

/**
 * Read property values or traverse arrays/iterators.
 *
 * @param mixed $target The entity/array/collection to fetch $field from.
 * @param string $field The next field to fetch.
 * @return mixed
 */
	protected function _getProp($target, $field) {
		if (is_array($target) && isset($target[$field])) {
			return $target[$field];
		}
		if ($target instanceof Entity) {
			return $target->get($field);
		}
		if ($target instanceof Traversable) {
			foreach ($target as $i => $val) {
				if ($i == $field) {
					return $val;
				}
			}
			return false;
		}
	}

/**
 * Check if a field should be marked as required.
 *
 * @param string $field The dot separated path to the field you want to check.
 * @return boolean
 */
	public function isRequired($field) {
		$parts = explode('.', $field);
		$entity = $this->_getEntity($parts);

		$isNew = true;
		if ($entity instanceof Entity) {
			$isNew = $entity->isNew();
		}

		$validator = $this->_getValidator($parts);
		$field = array_pop($parts);
		if (!$validator->hasField($field)) {
			return false;
		}
		$allowed = $validator->isEmptyAllowed($field, $isNew);
		return $allowed === false;
	}

/**
 * Get the validator associated to an entity based on naming
 * conventions.
 *
 * @param array $parts Each one of the parts in a path for a field name
 * @return Validator
 */
	protected function _getValidator($parts) {
		$table = $this->_getTable($parts);
		$alias = $table->alias();

		$method = 'default';
		if (is_string($this->_context['validator'])) {
			$method = $this->_context['validator'];
		} elseif (isset($this->_context['validator'][$alias])) {
			$method = $this->_context['validator'][$alias];
		}
		return $table->validator($method);
	}

/**
 * Get the table instance from a property path
 *
 * @param array $parts Each one of the parts in a path for a field name
 * @return array containing the table instance in the first position and the
 * property name in the second position
 */
	protected function _getTable($parts) {
		if (count($parts) === 1) {
			return $this->_tables[$this->_rootName];
		}

		$normalized = array_slice(array_filter($parts, function($part) {
			return !is_numeric($part);
		}), 0, -1);

		$path = implode('.', $normalized);
		if (isset($this->_tables[$path])) {
			return $this->_tables[$path];
		}

		if (current($normalized) === $this->_rootName) {
			$normalized = array_slice($normalized, 1);
		}

		$table = $this->_tables[$this->_rootName];
		foreach ($normalized as $part) {
			if (is_numeric($part)) {
				continue;
			}

			$assoc = $table->associations()->getByProperty($part);
			if (!$assoc) {
				break;
			}

			$table = $assoc->target();
		}

		return $this->_tables[$path] = $table;
	}

/**
 * Get the abstract field type for a given field name.
 *
 * @param string $field A dot separated path to get a schema type for.
 * @return null|string An abstract data type or null.
 * @see \Cake\Database\Type
 */
	public function type($field) {
		$parts = explode('.', $field);
		$table = $this->_getTable($parts);
		return $table->schema()->columnType(array_pop($parts));
	}

/**
 * Get an associative array of other attributes for a field name.
 *
 * @param string $field A dot separated path to get additional data on.
 * @return array An array of data describing the additional attributes on a field.
 */
	public function attributes($field) {
		$parts = explode('.', $field);
		$table = $this->_getTable($parts);
		$column = (array)$table->schema()->column(array_pop($parts));
		$whitelist = ['length' => null, 'precision' => null];
		return array_intersect_key($column, $whitelist);
	}

/**
 * Check whether or not a field has an error attached to it
 *
 * @param string $field A dot separated path to check errors on.
 * @return boolean Returns true if the errors for the field are not empty.
 */
	public function hasError($field) {
		return $this->error($field) !== [];
	}

/**
 * Get the errors for a given field
 *
 * @param string $field A dot separated path to check errors on.
 * @return array An array of errors.
 */
	public function error($field) {
		$parts = explode('.', $field);
		$entity = $this->_getEntity($parts);

		if ($entity instanceof Entity) {
			return $entity->errors(array_pop($parts));
		}
		return [];
	}

}
