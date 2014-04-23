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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use Cake\Event\Event;

/**
 * Contains logic for validating entities and their associations
 *
 * @see \Cake\ORM\Table::validate()
 * @see \Cake\ORM\Table::validateMany()
 */
class EntityValidator {

/**
 * The table instance this validator is for.
 *
 * @var \Cake\ORM\Table
 */
	protected $_table;

/**
 * Constructor.
 *
 * @param \Cake\ORM\Table $table
 */
	public function __construct(Table $table) {
		$this->_table = $table;
	}

/**
 * Build the map of property => association names.
 *
 * @param array $include The array of included associations.
 * @return array
 */
	protected function _buildPropertyMap($include) {
		if (empty($include['associated'])) {
			return [];
		}

		foreach ($include['associated'] as $key => $options) {
			if (is_int($key) && is_scalar($options)) {
				$key = $options;
				$options = [];
			}

			$options += ['validate' => true, 'associated' => []];
			$assoc = $this->_table->association($key);
			if ($assoc) {
				$map[$assoc->property()] = [
					'association' => $assoc,
					'options' => $options
				];
			}
		}

		return $map;
	}

/**
 * Validates a single entity by getting the correct validator object from
 * the table and traverses associations passed in $options to validate them
 * as well.
 *
 * @param \Cake\ORM\Entity $entity The entity to be validated
 * @param array|\ArrayObject $options options for validation, including an optional key of
 * associations to also be validated.
 * @return bool true if all validations passed, false otherwise
 */
	public function one(Entity $entity, $options = []) {
		$valid = true;
		$propertyMap = $this->_buildPropertyMap($options);

		foreach ($propertyMap as $key => $assoc) {
			$value = $entity->get($key);
			$association = $assoc['association'];

			if (!$value) {
				continue;
			}

			$validator = $association->target()->entityValidator();
			$types = [Association::ONE_TO_ONE, Association::MANY_TO_ONE];
			if (in_array($association->type(), $types)) {
				$valid = $validator->one($value, $assoc['options']) && $valid;
			} else {
				$valid = $validator->many($value, $assoc['options']) && $valid;
			}
		}

		if (!isset($options['validate'])) {
			$options['validate'] = true;
		}

		return $this->_processValidation($entity, $options) && $valid;
	}

/**
 * Validates a list of entities by getting the correct validator for the related
 * table and traverses associations passed in $include to validate them as well.
 *
 * @param array $entities List of entities to be validated
 * @param array|\ArrayObject $options options for validation, including an optional key of
 * associations to also be validated.
 * @return bool true if all validations passed, false otherwise
 */
	public function many(array $entities, $options = []) {
		$valid = true;
		foreach ($entities as $entity) {
			$valid = $this->one($entity, $options) && $valid;
		}
		return $valid;
	}

/**
 * Validates the $entity if the 'validate' key is not set to false in $options
 * If not empty it will construct a default validation object or get one with
 * the name passed in the key
 *
 * @param \Cake\ORM\Entity The entity to validate
 * @param \ArrayObject|array $options
 * @return bool true if the entity is valid, false otherwise
 */
	protected function _processValidation($entity, $options) {
		$type = is_string($options['validate']) ? $options['validate'] : 'default';
		$validator = $this->_table->validator($type);
		$pass = compact('entity', 'options', 'validator');
		$event = new Event('Model.beforeValidate', $this->_table, $pass);
		$this->_table->getEventManager()->dispatch($event);

		if ($event->isStopped()) {
			return (bool)$event->result;
		}

		if (!count($validator)) {
			return true;
		}

		$success = $entity->validate($validator);

		$event = new Event('Model.afterValidate', $this->_table, $pass);
		$this->_table->getEventManager()->dispatch($event);

		if ($event->isStopped()) {
			$success = (bool)$event->result;
		}

		return $success;
	}

}
