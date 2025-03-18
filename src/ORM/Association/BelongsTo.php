<?php
declare(strict_types=1);

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
namespace Cake\ORM\Association;

use Cake\Database\Exception\DatabaseException;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use Cake\ORM\Association\Loader\SelectLoader;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Closure;
use function Cake\Core\pluginSplit;

/**
 * Represents an 1 - N relationship where the source side of the relation is
 * related to only one record in the target table.
 *
 * An example of a BelongsTo association would be Article belongs to Author.
 *
 * @template T of \Cake\ORM\Table
 * @mixin T
 */
class BelongsTo extends Association
{
    /**
     * Valid strategies for this type of association
     *
     * @var array<string>
     */
    protected array $_validStrategies = [
        self::STRATEGY_JOIN,
        self::STRATEGY_SELECT,
    ];

    /**
     * @inheritDoc
     */
    public function getForeignKey(): array|string|false
    {
        if (!isset($this->_foreignKey)) {
            $this->_foreignKey = $this->_modelKey($this->getTarget()->getAlias());
        }

        return $this->_foreignKey;
    }

    /**
     * Sets the name of the field representing the foreign key to the target table.
     *
     * @param array<string>|string|false $key the key or keys to be used to link both tables together, if set to `false`
     *  no join conditions will be generated automatically.
     * @return $this
     */
    public function setForeignKey(array|string|false $key)
    {
        $this->_foreignKey = $key;

        return $this;
    }

    /**
     * Handle cascading deletes.
     *
     * BelongsTo associations are never cleared in a cascading delete scenario.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity that started the cascaded delete.
     * @param array<string, mixed> $options The options for the original delete.
     * @return bool Success.
     */
    public function cascadeDelete(EntityInterface $entity, array $options = []): bool
    {
        return true;
    }

    /**
     * Returns default property name based on association name.
     *
     * @return string
     */
    protected function _propertyName(): string
    {
        [, $name] = pluginSplit($this->_name);

        return Inflector::underscore(Inflector::singularize($name));
    }

    /**
     * Returns whether the passed table is the owning side for this
     * association. This means that rows in the 'target' table would miss important
     * or required information if the row in 'source' did not exist.
     *
     * @param \Cake\ORM\Table $side The potential Table with ownership
     * @return bool
     */
    public function isOwningSide(Table $side): bool
    {
        return $side === $this->getTarget();
    }

    /**
     * Get the relationship type.
     *
     * @return string
     */
    public function type(): string
    {
        return self::MANY_TO_ONE;
    }

    /**
     * Takes an entity from the source table and looks if there is a field
     * matching the property name for this association. The found entity will be
     * saved on the target table for this association by passing supplied
     * `$options`
     *
     * @param \Cake\Datasource\EntityInterface $entity an entity from the source table
     * @param array<string, mixed> $options options to be passed to the save method in the target table
     * @return \Cake\Datasource\EntityInterface|false false if $entity could not be saved, otherwise it returns
     * the saved entity
     * @see \Cake\ORM\Table::save()
     */
    public function saveAssociated(EntityInterface $entity, array $options = []): EntityInterface|false
    {
        $targetEntity = $entity->get($this->getProperty());
        if (!$targetEntity instanceof EntityInterface) {
            return $entity;
        }

        $table = $this->getTarget();
        $targetEntity = $table->save($targetEntity, $options);
        if (!$targetEntity) {
            return false;
        }

        /** @var array<string> $foreignKeys */
        $foreignKeys = (array)$this->getForeignKey();
        $properties = array_combine(
            $foreignKeys,
            $targetEntity->extract((array)$this->getBindingKey()),
        );

        if (method_exists($entity, 'patch')) {
            $entity = $entity->patch($properties, ['guard' => false]);
        } else {
            $entity->set($properties, ['guard' => false]);
        }

        return $entity;
    }

    /**
     * Returns a single or multiple conditions to be appended to the generated join
     * clause for getting the results on the target table.
     *
     * @param array<string, mixed> $options list of options passed to attachTo method
     * @return array<\Cake\Database\Expression\IdentifierExpression>
     * @throws \Cake\Database\Exception\DatabaseException if the number of columns in the foreignKey do not
     * match the number of columns in the target table primaryKey
     */
    protected function _joinCondition(array $options): array
    {
        $conditions = [];
        $tAlias = $this->_name;
        $sAlias = $this->_sourceTable->getAlias();
        $foreignKey = (array)$options['foreignKey'];
        $bindingKey = (array)$this->getBindingKey();

        if (count($foreignKey) !== count($bindingKey)) {
            if (!$bindingKey) {
                $msg = 'The `%s` table does not define a primary key. Please set one.';
                throw new DatabaseException(sprintf($msg, $this->getTarget()->getTable()));
            }

            $msg = 'Cannot match provided foreignKey for `%s`, got `(%s)` but expected foreign key for `(%s)`.';
            throw new DatabaseException(sprintf(
                $msg,
                $this->_name,
                implode(', ', $foreignKey),
                implode(', ', $bindingKey),
            ));
        }

        foreach ($foreignKey as $k => $f) {
            $field = sprintf('%s.%s', $tAlias, $bindingKey[$k]);
            $value = new IdentifierExpression(sprintf('%s.%s', $sAlias, $f));
            $conditions[$field] = $value;
        }

        return $conditions;
    }

    /**
     * @inheritDoc
     */
    public function eagerLoader(array $options): Closure
    {
        $loader = new SelectLoader([
            'alias' => $this->getAlias(),
            'sourceAlias' => $this->getSource()->getAlias(),
            'targetAlias' => $this->getTarget()->getAlias(),
            'foreignKey' => $this->getForeignKey(),
            'bindingKey' => $this->getBindingKey(),
            'strategy' => $this->getStrategy(),
            'associationType' => $this->type(),
            'finder' => $this->find(...),
        ]);

        return $loader->buildEagerLoader($options);
    }
}
