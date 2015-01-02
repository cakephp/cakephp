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

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use Cake\ORM\Table;
use Cake\Validation\ValidatableInterface;

/**
 * Contains logic for validating entities and their associations.
 *
 * This class is generally used by the internals of the ORM. It
 * provides methods for traversing a set of entities and their associated
 * properties.
 */
class EntityValidator
{

    /**
     * The table instance this validator is for.
     *
     * @var \Cake\ORM\Table
     */
    protected $_table;

    /**
     * Constructor.
     *
     * @param \Cake\ORM\Table $table The table this validator is for
     */
    public function __construct(Table $table)
    {
        $this->_table = $table;
    }

    /**
     * Build the map of property => association names.
     *
     * @param array $include The array of included associations.
     * @return array
     */
    protected function _buildPropertyMap($include)
    {
        if (empty($include['associated'])) {
            return [];
        }

        $map = [];
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
     * @param \Cake\Datasource\EntityInterface $entity The entity to be validated
     * @param array|\ArrayObject $options options for validation, including an optional key of
     *   associations to also be validated. This argument should use the same format as the $options
     *   argument to \Cake\ORM\Table::save().
     * @return bool true if all validations passed, false otherwise
     */
    public function one(EntityInterface $entity, $options = [])
    {
        $valid = true;
        $types = [Association::ONE_TO_ONE, Association::MANY_TO_ONE];
        $propertyMap = $this->_buildPropertyMap($options);
        $options = new ArrayObject($options);

        foreach ($propertyMap as $key => $assoc) {
            $value = $entity->get($key);
            $association = $assoc['association'];

            if (!$value) {
                continue;
            }
            $isOne = in_array($association->type(), $types);
            if ($isOne && !($value instanceof EntityInterface)) {
                $valid = false;
                continue;
            }

            $validator = new self($association->target());
            if ($isOne) {
                $valid = $validator->one($value, $assoc['options']) && $valid;
            } else {
                $valid = $validator->many($value, $assoc['options']) && $valid;
            }
        }

        if (!isset($options['validate'])) {
            $options['validate'] = true;
        }

        if (!($entity instanceof ValidatableInterface)) {
            return $valid;
        }

        return $this->_processValidation($entity, $options) && $valid;
    }

    /**
     * Validates a list of entities by getting the correct validator for the related
     * table and traverses associations passed in $include to validate them as well.
     *
     * If any of the entities in `$entities` does not implement `Cake\Datasource\EntityInterface`,
     * it will be treated as an invalid result.
     *
     * @param array $entities List of entities to be validated
     * @param array|\ArrayObject $options options for validation, including an optional key of
     *   associations to also be validated. This argument should use the same format as the $options
     *   argument to \Cake\ORM\Table::save().
     * @return bool true if all validations passed, false otherwise
     */
    public function many(array $entities, $options = [])
    {
        $valid = true;
        foreach ($entities as $entity) {
            if (!($entity instanceof EntityInterface)) {
                return false;
            }
            $valid = $this->one($entity, $options) && $valid;
        }
        return $valid;
    }

    /**
     * Validates the $entity if the 'validate' key is not set to false in $options
     * If not empty it will construct a default validation object or get one with
     * the name passed in the key
     *
     * @param \Cake\ORM\Entity $entity The entity to validate
     * @param \ArrayObject $options The option for processing validation
     * @return bool true if the entity is valid, false otherwise
     */
    protected function _processValidation($entity, $options)
    {
        $type = is_string($options['validate']) ? $options['validate'] : 'default';
        $validator = $this->_table->validator($type);
        $pass = compact('entity', 'options', 'validator');
        $event = $this->_table->dispatchEvent('Model.beforeValidate', $pass);

        if ($event->isStopped()) {
            return (bool)$event->result;
        }

        if (!count($validator)) {
            return true;
        }

        $success = !$entity->validate($validator);

        $event = $this->_table->dispatchEvent('Model.afterValidate', $pass);
        if ($event->isStopped()) {
            $success = (bool)$event->result;
        }

        return $success;
    }
}
