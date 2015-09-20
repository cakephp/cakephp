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
namespace Cake\ORM\Association;

use Cake\Database\Expression\IdentifierExpression;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use RuntimeException;

/**
 * Represents an 1 - N relationship where the source side of the relation is
 * related to only one record in the target table.
 *
 * An example of a BelongsTo association would be Article belongs to Author.
 */
class BelongsTo extends Association
{

    use SelectableAssociationTrait;

    /**
     * Valid strategies for this type of association
     *
     * @var array
     */
    protected $_validStrategies = [self::STRATEGY_JOIN, self::STRATEGY_SELECT];

    /**
     * Sets the name of the field representing the foreign key to the target table.
     * If no parameters are passed current field is returned
     *
     * @param string|null $key the key to be used to link both tables together
     * @return string
     */
    public function foreignKey($key = null)
    {
        if ($key === null) {
            if ($this->_foreignKey === null) {
                $this->_foreignKey = $this->_modelKey($this->target()->alias());
            }
            return $this->_foreignKey;
        }
        return parent::foreignKey($key);
    }

    /**
     * Handle cascading deletes.
     *
     * BelongsTo associations are never cleared in a cascading delete scenario.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity that started the cascaded delete.
     * @param array $options The options for the original delete.
     * @return bool Success.
     */
    public function cascadeDelete(EntityInterface $entity, array $options = [])
    {
        return true;
    }

    /**
     * Sets the property name that should be filled with data from the target table
     * in the source table record.
     * If no arguments are passed, currently configured type is returned.
     *
     * @param string|null $name The property name, use null to read the current property.
     * @return string
     */
    public function property($name = null)
    {
        if ($name !== null) {
            return parent::property($name);
        }
        if ($name === null && !$this->_propertyName) {
            list(, $name) = pluginSplit($this->_name);
            $this->_propertyName = Inflector::underscore(Inflector::singularize($name));
        }
        return $this->_propertyName;
    }

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
        return $side === $this->target();
    }

    /**
     * Get the relationship type.
     *
     * @return string
     */
    public function type()
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
     * @param array|\ArrayObject $options options to be passed to the save method in
     * the target table
     * @return bool|\Cake\Datasource\EntityInterface false if $entity could not be saved, otherwise it returns
     * the saved entity
     * @see Table::save()
     */
    public function saveAssociated(EntityInterface $entity, array $options = [])
    {
        $targetEntity = $entity->get($this->property());
        if (empty($targetEntity) || !($targetEntity instanceof EntityInterface)) {
            return $entity;
        }

        $table = $this->target();
        $targetEntity = $table->save($targetEntity, $options);
        if (!$targetEntity) {
            return false;
        }

        $properties = array_combine(
            (array)$this->foreignKey(),
            $targetEntity->extract((array)$this->bindingKey())
        );
        $entity->set($properties, ['guard' => false]);
        return $entity;
    }

    /**
     * Returns a single or multiple conditions to be appended to the generated join
     * clause for getting the results on the target table.
     *
     * @param array $options list of options passed to attachTo method
     * @return array
     * @throws \RuntimeException if the number of columns in the foreignKey do not
     * match the number of columns in the target table primaryKey
     */
    protected function _joinCondition($options)
    {
        $conditions = [];
        $tAlias = $this->target()->alias();
        $sAlias = $this->_sourceTable->alias();
        $foreignKey = (array)$options['foreignKey'];
        $bindingKey = (array)$this->bindingKey();

        if (count($foreignKey) !== count($bindingKey)) {
            $msg = 'Cannot match provided foreignKey for "%s", got "(%s)" but expected foreign key for "(%s)"';
            throw new RuntimeException(sprintf(
                $msg,
                $this->_name,
                implode(', ', $foreignKey),
                implode(', ', $bindingKey)
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
     * {@inheritDoc}
     */
    protected function _linkField($options)
    {
        $links = [];
        $name = $this->alias();

        foreach ((array)$this->bindingKey() as $key) {
            $links[] = sprintf('%s.%s', $name, $key);
        }

        if (count($links) === 1) {
            return $links[0];
        }

        return $links;
    }

    /**
     * {@inheritDoc}
     */
    protected function _buildResultMap($fetchQuery, $options)
    {
        $resultMap = [];
        $key = (array)$this->bindingKey();

        foreach ($fetchQuery->all() as $result) {
            $values = [];
            foreach ($key as $k) {
                $values[] = $result[$k];
            }
            $resultMap[implode(';', $values)] = $result;
        }
        return $resultMap;
    }
}
