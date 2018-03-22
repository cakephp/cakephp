<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Rule;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use Cake\ORM\TableRegistry;
use RuntimeException;

/**
 * Checks that the value provided in a field exists as the primary key of another
 * table.
 */
class ExistsIn
{

    /**
     * The list of fields to check
     *
     * @var array
     */
    protected $_fields;

    /**
     * The repository where the field will be looked for
     *
     * @var \Cake\Datasource\RepositoryInterface|\Cake\ORM\Association|string
     */
    protected $_repository;

    /**
     * Options for the constructor
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Constructor.
     *
     * Available option for $options is 'allowNullableNulls' flag.
     * Set to true to accept composite foreign keys where one or more nullable columns are null.
     *
     * @param string|array $fields The field or fields to check existence as primary key.
     * @param \Cake\Datasource\RepositoryInterface|\Cake\ORM\Association|string $repository The repository where the field will be looked for,
     * or the association name for the repository.
     * @param array $options The options that modify the rules behavior.
     *     Options 'allowNullableNulls' will make the rule pass if given foreign keys are set to `null`.
     *     Notice: allowNullableNulls cannot pass by database columns set to `NOT NULL`.
     */
    public function __construct($fields, $repository, array $options = [])
    {
        $options += ['allowNullableNulls' => false];
        $this->_options = $options;

        $this->_fields = (array)$fields;
        $this->_repository = $repository;
    }

    /**
     * Performs the existence check
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity from where to extract the fields
     * @param array $options Options passed to the check,
     * where the `repository` key is required.
     * @throws \RuntimeException When the rule refers to an undefined association.
     * @return bool
     */
    public function __invoke(EntityInterface $entity, array $options)
    {
        if (is_string($this->_repository)) {
            if (!$options['repository']->hasAssociation($this->_repository)) {
                throw new RuntimeException(sprintf(
                    "ExistsIn rule for '%s' is invalid. '%s' is not associated with '%s'.",
                    implode(', ', $this->_fields),
                    $this->_repository,
                    get_class($options['repository'])
                ));
            }
            $repository = $options['repository']->getAssociation($this->_repository);
            $this->_repository = $repository;
        }

        $source = $target = $this->_repository;
        $isAssociation = $target instanceof Association;
        $bindingKey = $isAssociation ? (array)$target->getBindingKey() : (array)$target->getPrimaryKey();
        $realTarget = $isAssociation ? $target->getTarget() : $target;

        if (!empty($options['_sourceTable']) && $realTarget === $options['_sourceTable']) {
            return true;
        }

        if (!empty($options['repository'])) {
            $source = $options['repository'];
        }
        if ($source instanceof Association) {
            $source = $source->getSource();
        }

        if (!$entity->extract($this->_fields, true)) {
            return true;
        }

        if ($this->_fieldsAreNull($entity, $source)) {
            return true;
        }

        if ($this->_options['allowNullableNulls']) {
            $schema = $source->getSchema();
            foreach ($this->_fields as $i => $field) {
                if ($schema->getColumn($field) && $schema->isNullable($field) && $entity->get($field) === null) {
                    unset($bindingKey[$i], $this->_fields[$i]);
                }
            }
        }

        if (isset($options['parent']) && $options['parent'] instanceof \Cake\Datasource\EntityInterface) {
            /**
             * @var \Cake\Datasource\EntityInterface $parent
             */
            $parent = $options['parent'];
            $parentEntity = $this->_extractParentEntity($parent, $target, $isAssociation);

            if (isset($parentEntity)) {
                //extract fields to match
                $parentFields = $this->_extractParentFields($parentEntity, $bindingKey);

                //just do this check if one or more entity is new
                if (!empty($parentFields)) {
                    $child_fields = $entity->extract($this->_fields);

                    return empty(array_diff($child_fields, $parentFields));
                }
            }
        }

        $primary = array_map(
            [$target, 'aliasField'],
            $bindingKey
        );
        $conditions = array_combine(
            $primary,
            $entity->extract($this->_fields)
        );

        return $target->exists($conditions);
    }

    /**
     * Checks whether or not the given entity fields are nullable and null.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to check.
     * @param \Cake\ORM\Table $source The table to use schema from.
     * @return bool
     */
    protected function _fieldsAreNull($entity, $source)
    {
        $nulls = 0;
        $schema = $source->getSchema();
        foreach ($this->_fields as $field) {
            if ($schema->getColumn($field) && $schema->isNullable($field) && $entity->get($field) === null) {
                $nulls++;
            }
        }

        return $nulls === count($this->_fields);
    }

    /**
     * Extract entity from given parent regarding HM and HABTM associations
     *
     * @param \Cake\Datasource\EntityInterface $parent Parent of current entity.
     * @param \Cake\Datasource\RepositoryInterface|\Cake\ORM\Association|string $target Target table of current entity.
     * @param bool $isAssociation True if target is association of parent.
     * @return EntityInterface|mixed|null
     */
    protected function _extractParentEntity($parent, $target, $isAssociation)
    {
        $parentEntity = null;
        if ($isAssociation) {
            if (TableRegistry::get($parent->getSource()) === $target->getTarget()) {
                //parent is of same  as target
                $parentEntity = $parent;
            } else {
                if (!empty($parent->get($target->getName()))) {
                    $parentEntity = $parent->get($target->getName());
                }
            }
        } else {
            $parentEntity = $parent;
        }

        return $parentEntity;
    }

    /**
     * Extracts all the foreign key values from parent entity.
     *
     * @param EntityInterface $parentEntity Entity/Entities to extract the values from.
     * @param mixed $bindingKey Name of foreign key field.
     * @return array|null Returns null if there is no new parentEntity otherwise the extracted values.
     */
    protected function _extractParentFields($parentEntity, $bindingKey)
    {
        $parentFields = [];
        if (is_array($parentEntity)) {
            //regard HM and HABTM associations
            $isNew = false;
            foreach ($parentEntity as $record) {
                $isNew |= $record->isNew();
                $parentFields = array_merge($parentFields, $record->extract($bindingKey));
            }
        } else {
            $isNew = $parentEntity->isNew();
            $parentFields = $parentEntity->extract($bindingKey);
        }

        if (!$isNew) {
            $parentFields = null;
        }

        return $parentFields;
    }
}
