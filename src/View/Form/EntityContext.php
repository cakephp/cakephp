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

use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Traversable;

/**
 * Provides a form context around a single entity and its relations.
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
 *   If this is null the table name(s) will be determined using conventions.
 * - `validator` Either the Validation\Validator to use, or the name of the
 *   validation method to call on the table object. For example 'default'.
 *   Defaults to 'default'. Can be an array of table alias=>validators when
 *   dealing with associated forms.
 */
class EntityContext {

/**
 * The request object.
 *
 * @var Cake\Network\Request
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
 * A dictionary of tables
 *
 * @var array
 */
	protected $_tables = [];

/**
 * Constructor.
 *
 * @param Cake\Network\Request
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
 * @return void
 */
	protected function _prepare() {
		$table = $this->_context['table'];
		if (is_string($table)) {
			$table = TableRegistry::get($table);
		}
		$alias = $this->_rootName = $table->alias();
		$this->_tables[$alias] = $table;
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
		list($entity, $prop) = $this->_getEntity($parts);
		if (!$entity) {
			return null;
		}
		return $entity->get(array_pop($parts));
	}

/**
 * Fetch the leaf entity for the given path.
 *
 * This method will traverse the given path and find the leaf
 * entity. If the path does not contain a leaf entity false
 * will be returned.
 *
 * @param array $path The path to traverse to find the leaf entity.
 * @return array
 */
	protected function _getEntity($path) {
		$entity = $this->_context['entity'];
		if (count($path) === 1) {
			return [$entity, $this->_rootName];
		}

		// Remove the Table name if present.
		if (count($path) > 1 && $path[0] === $this->_rootName) {
			array_shift($path);
		}

		$lastProp = $this->_rootName;
		foreach ($path as $prop) {
			$next = $this->_getProp($entity, $prop);
			if (
				!is_array($next) &&
				!($next instanceof Traversable) &&
				!($next instanceof Entity)
			) {
				return [$entity, $lastProp];
			}
			if (!is_numeric($prop)) {
				$lastProp = $prop;
			}
			$entity = $next;
		}
		return [false, false];
	}

/**
 * Read property values or traverse arrays/iterators.
 *
 * @param mixed $target The entity/array/collection to fetch $field from.
 * @param string $field The next field to fetch.
 * @return mixed
 */
	protected function _getProp($target, $field) {
		if (is_array($target) || $target instanceof Traversable) {
			foreach ($target as $i => $val) {
				if ($i == $field) {
					return $val;
				}
			}
		}
		return $target->get($field);
	}

/**
 * Check if a field should be marked as required.
 *
 * @param string $field The dot separated path to the field you want to check.
 * @return boolean
 */
	public function isRequired($field) {
		if (empty($this->_context['validator'])) {
			return false;
		}
		$parts = explode('.', $field);
		list($entity, $prop) = $this->_getEntity($parts);
		if (!$entity) {
			return false;
		}

		$validator = $this->_getValidator($prop);
		if (!$validator) {
			return false;
		}

		$field = array_pop($parts);
		if (!$validator->hasField($field)) {
			return false;
		}
		$allowed = $validator->isEmptyAllowed($field, $entity->isNew());
		return $allowed === false;
	}

/**
 * Get the validator associated to an entity based on naming
 * conventions.
 *
 * @param string $entity The entity name to get a validator for.
 * @return Validator|false
 */
	protected function _getValidator($entity) {
		$table = $this->_getTable($entity);
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
 * Get the table instance
 *
 * @param string $prop The property name to get a table for.
 * @return Cake\ORM\Table The table instance.
 */
	protected function _getTable($prop) {
		if (isset($this->_tables[$prop])) {
			return $this->_tables[$prop];
		}
		$root = $this->_tables[$this->_rootName];
		$assoc = $root->associations()->getByProperty($prop);

		// No assoc, use the default table to prevent
		// downstream failures.
		if (!$assoc) {
			return $root;
		}

		$target = $assoc->target();
		$this->_tables[$prop] = $target;
		return $target;
	}

/**
 * Get the abstract field type for a given field name.
 *
 * @param string $field A dot separated path to get a schema type for.
 * @return null|string An abstract data type or null.
 * @see Cake\Database\Type
 */
	public function type($field) {
		$parts = explode('.', $field);
		list($entity, $prop) = $this->_getEntity($parts);
		if (!$entity) {
			return null;
		}
		$table = $this->_getTable($prop);
		$column = array_pop($parts);
		return $table->schema()->columnType($column);
	}

/**
 * Get an associative array of other attributes for a field name.
 *
 * @param string $field A dot separated path to get additional data on.
 * @return array An array of data describing the additional attributes on a field.
 */
	public function attributes($field) {
		$parts = explode('.', $field);
		list($entity, $prop) = $this->_getEntity($parts);
		if (!$entity) {
			return [];
		}
		$table = $this->_getTable($prop);
		$column = $table->schema()->column(array_pop($parts));
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
		$parts = explode('.', $field);
		list($entity, $prop) = $this->_getEntity($parts);
		if (!$entity) {
			return false;
		}
		$errors = $entity->errors(array_pop($parts));
		return !empty($errors);
	}

/**
 * Get the errors for a given field
 *
 * @param string $field A dot separated path to check errors on.
 * @return array An array of errors.
 */
	public function error($field) {
		$parts = explode('.', $field);
		list($entity, $prop) = $this->_getEntity($parts);
		if (!$entity) {
			return [];
		}
		return $entity->errors(array_pop($parts));
	}

}
