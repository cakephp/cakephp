<?php
/**
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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Association;

use Cake\Collection\Collection;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use Cake\ORM\Table;
use InvalidArgumentException;
use RuntimeException;
use Traversable;

/**
 * Represents an N - 1 relationship where the target side of the relationship
 * will have one or multiple records per each one in the source side.
 *
 * An example of a HasMany association would be Author has many Articles.
 */
class HasMany extends Association
{

    use DependentDeleteTrait;
    use ExternalAssociationTrait {
        _options as _externalOptions;
    }

    /**
     * The type of join to be used when adding the association to a query
     *
     * @var string
     */
    protected $_joinType = 'INNER';

    /**
     * The strategy name to be used to fetch associated records.
     *
     * @var string
     */
    protected $_strategy = self::STRATEGY_SELECT;

    /**
     * Valid strategies for this type of association
     *
     * @var array
     */
    protected $_validStrategies = [self::STRATEGY_SELECT, self::STRATEGY_SUBQUERY];

    /**
     * Saving strategy that will only append to the links set
     *
     * @var string
     */
    const SAVE_APPEND = 'append';

    /**
     * Saving strategy that will replace the links with the provided set
     *
     * @var string
     */
    const SAVE_REPLACE = 'replace';

    /**
     * Saving strategy to be used by this association
     *
     * @var string
     */
    protected $_saveStrategy = self::SAVE_APPEND;

    /**
     * Returns whether or not the passed table is the owning side for this
     * association. This means that rows in the 'target' table would miss important
     * or required information if the row in 'source' did not exist.
     *
     * @param \Cake\ORM\Table $side The potential Table with ownership
     * @return bool
     */
    public function isOwningSide(Table $side)
    {
        return $side === $this->source();
    }

    /**
     * Sets the strategy that should be used for saving. If called with no
     * arguments, it will return the currently configured strategy
     *
     * @param string|null $strategy the strategy name to be used
     * @throws \InvalidArgumentException if an invalid strategy name is passed
     * @return string the strategy to be used for saving
     */
    public function saveStrategy($strategy = null)
    {
        if ($strategy === null) {
            return $this->_saveStrategy;
        }
        if (!in_array($strategy, [self::SAVE_APPEND, self::SAVE_REPLACE])) {
            $msg = sprintf('Invalid save strategy "%s"', $strategy);
            throw new InvalidArgumentException($msg);
        }
        return $this->_saveStrategy = $strategy;
    }

    /**
     * Takes an entity from the source table and looks if there is a field
     * matching the property name for this association. The found entity will be
     * saved on the target table for this association by passing supplied
     * `$options`
     *
     * @param \Cake\Datasource\EntityInterface $entity an entity from the source table
     * @param array|\ArrayObject $options options to be passed to the save method in
     * the target table
     * @return bool|\Cake\Datasource\EntityInterface false if $entity could not be saved, otherwise it returns
     * the saved entity
     * @see Table::save()
     * @throws \InvalidArgumentException when the association data cannot be traversed.
     */
    public function saveAssociated(EntityInterface $entity, array $options = [])
    {
        $targetEntities = $entity->get($this->property());
        if (empty($targetEntities)) {
            return $entity;
        }

        if (!is_array($targetEntities) && !($targetEntities instanceof Traversable)) {
            $name = $this->property();
            $message = sprintf('Could not save %s, it cannot be traversed', $name);
            throw new InvalidArgumentException($message);
        }

        $foreignKey = (array)$this->foreignKey();
        $properties = array_combine(
            $foreignKey,
            $entity->extract((array)$this->bindingKey())
        );
        $target = $this->target();
        $original = $targetEntities;
        $options['_sourceTable'] = $this->source();

        if ($this->_saveStrategy === self::SAVE_REPLACE) {
            $this->_unlinkAssociated($properties, $entity, $target, $targetEntities);
        }

        foreach ($targetEntities as $k => $targetEntity) {
            if (!($targetEntity instanceof EntityInterface)) {
                break;
            }

            if (!empty($options['atomic'])) {
                $targetEntity = clone $targetEntity;
            }

            if ($properties !== $targetEntity->extract($foreignKey)) {
                $targetEntity->set($properties, ['guard' => false]);
            }

            if ($target->save($targetEntity, $options)) {
                $targetEntities[$k] = $targetEntity;
                continue;
            }

            if (!empty($options['atomic'])) {
                $original[$k]->errors($targetEntity->errors());
                $entity->set($this->property(), $original);
                return false;
            }
        }

        $entity->set($this->property(), $targetEntities);
        return $entity;
    }

    /**
     * Deletes/sets null the related objects according to the dependency between source and targets and foreign key nullability
     * Skips deleting records present in $remainingEntities
     *
     * @param array $properties array of foreignKey properties
     * @param EntityInterface $entity the entity which should have its associated entities unassigned
     * @param Table $target The associated table
     * @param array $remainingEntities Entities that should not be deleted
     * @return void
     */
    protected function _unlinkAssociated(array $properties, EntityInterface $entity, Table $target, array $remainingEntities = [])
    {
        $primaryKey = (array)$target->primaryKey();
        $mustBeDependent = (!$this->_foreignKeyAcceptsNull($target, $properties) || $this->dependent());
        $exclusions = new Collection($remainingEntities);
        $exclusions = $exclusions->map(
            function ($ent) use ($primaryKey) {
                return $ent->extract($primaryKey);
            }
        )
        ->filter(
            function ($v) {
                return !in_array(null, array_values($v), true);
            }
        )
        ->toArray();

        if (count($exclusions) > 0) {
            $conditions = [
                'NOT' => [
                    'OR' => $exclusions
                ],
                $properties
            ];

            if ($mustBeDependent) {
                if ($this->_cascadeCallbacks) {
                    $query = $this->find('all')->where($conditions);
                    foreach ($query as $assoc) {
                        $target->delete($assoc);
                    }
                } else {
                    $target->deleteAll($conditions);
                }
            } else {
                $updateFields = array_fill_keys(array_keys($properties), null);
                $target->updateAll($updateFields, $conditions);
            }
        }
    }

    /**
     * Checks the nullable flag of the foreign key
     *
     * @param Table $table the table containing the foreign key
     * @param array $properties the list of fields that compose the foreign key
     * @return bool
     */
    protected function _foreignKeyAcceptsNull(Table $table, array $properties)
    {
        return !in_array(
            false,
            array_map(
                function ($prop) use ($table) {
                    return $table->schema()->isNullable($prop);
                },
                array_keys($properties)
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function _linkField($options)
    {
        $links = [];
        $name = $this->alias();
        if ($options['foreignKey'] === false) {
            $msg = 'Cannot have foreignKey = false for hasMany associations. ' .
                   'You must provide a foreignKey column.';
            throw new RuntimeException($msg);
        }

        foreach ((array)$options['foreignKey'] as $key) {
            $links[] = sprintf('%s.%s', $name, $key);
        }

        if (count($links) === 1) {
            return $links[0];
        }

        return $links;
    }

    /**
     * Get the relationship type.
     *
     * @return string
     */
    public function type()
    {
        return self::ONE_TO_MANY;
    }

    /**
     * Parse extra options passed in the constructor.
     *
     * @param array $opts original list of options passed in constructor
     * @return void
     */
    protected function _options(array $opts)
    {
        $this->_externalOptions($opts);
        if (!empty($opts['saveStrategy'])) {
            $this->saveStrategy($opts['saveStrategy']);
        }
    }
}
