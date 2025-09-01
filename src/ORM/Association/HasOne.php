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

use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use Cake\ORM\Association\Loader\SelectLoader;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Closure;
use function Cake\Core\pluginSplit;

/**
 * Represents an 1 - 1 relationship where the source side of the relation is
 * related to only one record in the target table and vice versa.
 *
 * An example of a HasOne association would be User has one Profile.
 *
 * @template T of \Cake\ORM\Table
 * @mixin T
 */
class HasOne extends Association
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
        return $this->_foreignKey ??= $this->_modelKey($this->getSource()->getAlias());
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
        return $side === $this->getSource();
    }

    /**
     * Get the relationship type.
     *
     * @return string
     */
    public function type(): string
    {
        return self::ONE_TO_ONE;
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

        /** @var array<string> $foreignKeys */
        $foreignKeys = (array)$this->getForeignKey();
        $properties = array_combine(
            $foreignKeys,
            $entity->extract((array)$this->getBindingKey()),
        );
        if (method_exists($targetEntity, 'patch')) {
            $targetEntity = $targetEntity->patch($properties, ['guard' => false]);
        } else {
            $targetEntity->set($properties, ['guard' => false]);
        }

        if (!$this->getTarget()->save($targetEntity, $options)) {
            $targetEntity->unset(array_keys($properties));

            return false;
        }

        return $entity;
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

    /**
     * @inheritDoc
     */
    public function cascadeDelete(EntityInterface $entity, array $options = []): bool
    {
        $helper = new DependentDeleteHelper();

        return $helper->cascadeDelete($this, $entity, $options);
    }
}
