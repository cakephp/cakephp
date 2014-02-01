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
 *   from, an array of table instances in the case of an form spanning
 *   multiple entities, or the name(s) of the table.
 *   If this is null the table name(s) will be determined using conventions.
 *   This table object will be used to fetch the schema and
 *   validation information.
 * - `validator` Either the Validation\Validator to use, or the name of the
 *   validation method to call on the table object. For example 'default'.
 *   Defaults to 'default'.
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
 * The plural name of the top level entity/table object.
 *
 * @var string
 */
	protected $_pluralName;

/**
 * A dictionary of validators and their
 * related tables.
 *
 * @var array
 */
	protected $_validators = [];

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
			'schema' => null,
			'table' => null,
			'validator' => null
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
		$table = null;
		// TODO handle the other cases (string, array, instance)
		if (is_string($this->_context['table'])) {
			$plural = $this->_context['table'];
		}
		$table = TableRegistry::get($plural);

		if (is_object($this->_context['validator'])) {
			$this->_validators['_default'] = $this->_context['validator'];
		} elseif (is_string($this->_context['validator'])) {
			$this->_validators['_default'] = $table->validator($this->_context['validator']);
		}

		$this->_pluralName = $plural;
		$this->_tables[$plural] = $table;
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
 * @return boolean|Entity Either the leaf entity or false.
 */
	protected function _getEntity($path) {
		$entity = $this->_context['entity'];
		if (count($path) === 1) {
			return $entity;
		}

		// Remove the Table name if present.
		if (count($path) > 1 && $path[0] === $this->_pluralName) {
			array_shift($path);
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
		return false;
	}

/**
 * Read property values or traverse arrays/iterators.
 *
 * @param mixed $target The entity/array/collection to fetch $field from.
 * @param string $field The next field to fetch.
 * @return mixed.
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
		$entity = $this->_getEntity($parts);
		if (!$entity) {
			return false;
		}

		$field = array_pop($parts);
		$validator = $this->_getValidator($entity);
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
 * If no match is found the `_root` validator will be used.
 *
 * @param Cake\ORM\Entity $entity
 * @return Validator
 */
	protected function _getValidator($entity) {
		list($ns, $entityClass) = namespaceSplit(get_class($entity));
		if (isset($this->_validators[$entityClass])) {
			return $this->_validators[$entityClass];
		}
		if (isset($this->_validators['_default'])) {
			return $this->_validators['_default'];
		}
	}

	public function type($field) {
	}

	public function attributes($field) {
	}

	public function hasError($field) {
	}

	public function error($field) {
	}

}
