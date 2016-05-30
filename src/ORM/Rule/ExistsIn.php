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
namespace Cake\ORM\Rule;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
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
     * @var array
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
     * Available option for $options is 'allowPartialNulls' flag.
     * Set to true to accept composite foreign keys where one or more nullable columns are null.'
     *
     * @param string|array $fields The field or fields to check existence as primary key.
     * @param object|string $repository The repository where the field will be looked for,
     * or the association name for the repository.
     * @param array $options The options that modify the rules behavior.
     */
    public function __construct($fields, $repository, array $options = [])
    {
        $options += ['allowPartialNulls' => false];
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
            $alias = $this->_repository;
            $this->_repository = $options['repository']->association($alias);

            if (empty($this->_repository)) {
                throw new RuntimeException(sprintf(
                    "ExistsIn rule for '%s' is invalid. The '%s' association is not defined.",
                    implode(', ', $this->_fields),
                    $alias
                ));
            }
        }

        $source = $target = $this->_repository;
        $isAssociation = $target instanceof Association;
        $bindingKey = $isAssociation ? (array)$target->bindingKey() : (array)$target->primaryKey();
        $realTarget = $isAssociation ? $target->target() : $target;

        if (!empty($options['_sourceTable']) && $realTarget === $options['_sourceTable']) {
            return true;
        }

        if (!empty($options['repository'])) {
            $source = $options['repository'];
        }
        if ($source instanceof Association) {
            $source = $source->source();
        }

        if (!$entity->extract($this->_fields, true)) {
            return true;
        }

        if ($this->_options['allowPartialNulls'] === true
            && $this->_checkPartialSchemaNulls($entity, $source) === true
        ) {
            return true;
        }
        if ($this->_fieldsAreNull($entity, $source)) {
            return true;
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
     * Check whether or not the entity fields are nullable and null.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to check.
     * @param \Cake\ORM\Table $source The table to use schema from.
     * @return bool
     */
    protected function _fieldsAreNull($entity, $source)
    {
        $nulls = 0;
        $schema = $source->schema();
        foreach ($this->_fields as $field) {
            if ($schema->column($field) && $schema->isNullable($field) && $entity->get($field) === null) {
                $nulls++;
            }
        }
        return $nulls === count($this->_fields);
    }

    /**
     * Check whether there are partial, nullable, nulls.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to check.
     * @param \Cake\ORM\Table $source The table to use schema from.
     * @return bool
     */
    protected function _checkPartialSchemaNulls($entity, $source)
    {
        $schema = $source->schema();
        foreach ($this->_fields as $field) {
            if ($schema->isNullable($field) === true && $entity->get($field) === null) {
                return true;
            }
        }
        return false;
    }
}
