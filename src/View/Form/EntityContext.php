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

	protected $_pluralName;

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
		// TODO handle the other cases (string, array, instance)
		if (is_string($this->_context['table'])) {
			$plural = $this->_context['table'];
		}
		$this->_pluralName = $plural;
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

		// Remove the model name if present.
		if (count($parts) > 1 && $parts[0] === $this->_pluralName) {
			array_shift($parts);
		}

		$val = $this->_context['entity'];
		foreach ($parts as $prop) {
			$val = $this->_getProp($val, $prop);
			if (
				!is_array($val) &&
				!($val instanceof Traversable) &&
				!($val instanceof Entity)
			) {
				return $val;
			}
		}
		return $val;
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

	public function isRequired($field) {
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
