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

/**
 * Represents an 1 - 1 relationship where the source side of the relation is
 * related to only one record in the target table and vice versa.
 *
 * An example of a HasOne association would be User has one Profile.
 */
class HasOne extends Association
{
    /**
     * Valid strategies for this type of association
     *
     * @var string[]
     */
    protected $_validStrategies = [
        self::STRATEGY_JOIN,
        self::STRATEGY_SELECT,
    ];

    /**
     * Gets the name of the field representing the foreign key to the target table.
     *
     * @return string|string[]
     */
    public function getForeignKey()
    {
        if ($this->_foreignKey === null) {
            $this->_foreignKey = $this->_modelKey($this->getSource()->getAlias());
        }

        return $this->_foreignKey;
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
     * Returns whether or not the passed table is the owning side for this
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
     * @param array $options options to be passed to the save method in the target table
     * @return \Cake\Datasource\EntityInterface|false false if $entity could not be saved, otherwise it returns
     * the saved entity
     * @see \Cake\ORM\Table::save()
     */
    public function saveAssociated(EntityInterface $entity, array $options = [])
    {
        $targetEntity = $entity->get($this->getProperty());
        if (empty($targetEntity) || !($targetEntity instanceof EntityInterface)) {
            return $entity;
        }

        $properties = array_combine(
            (array)$this->getForeignKey(),
            $entity->extract((array)$this->getBindingKey())
        );
        $targetEntity->set($properties, ['guard' => false]);

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
            'finder' => [$this, 'find'],
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
