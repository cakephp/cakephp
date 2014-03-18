<?php
/**
 * PHP Version 5.4
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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\ORM\Association;

use Cake\Database\Expression\IdentifierExpression;
use Cake\ORM\Association;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

/**
 * Represents an 1 - N relationship where the source side of the relation is
 * related to only one record in the target table.
 *
 * An example of a BelongsTo association would be Article belongs to Author.
 */
class BelongsTo extends Association {

/**
 * Whether this association can be expressed directly in a query join
 *
 * @var boolean
 */
	protected $_canBeJoined = true;

/**
 * Sets the name of the field representing the foreign key to the target table.
 * If no parameters are passed current field is returned
 *
 * @param string $key the key to be used to link both tables together
 * @return string
 */
	public function foreignKey($key = null) {
		if ($key === null) {
			if ($this->_foreignKey === null) {
				$key = Inflector::singularize($this->target()->alias());
				$this->_foreignKey = Inflector::underscore($key) . '_id';
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
 * @param \Cake\ORM\Entity $entity The entity that started the cascaded delete.
 * @param array $options The options for the original delete.
 * @return boolean Success.
 */
	public function cascadeDelete(Entity $entity, $options = []) {
		return true;
	}

/**
 * Sets the property name that should be filled with data from the target table
 * in the source table record.
 * If no arguments are passed, currently configured type is returned.
 *
 * @param string $name
 * @return string
 */
	public function property($name = null) {
		if ($name !== null) {
			return parent::property($name);
		}
		if ($name === null && !$this->_propertyName) {
			list($plugin, $name) = pluginSplit($this->_name);
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
 * @return boolean
 */
	public function isOwningSide(Table $side) {
		return $side === $this->target();
	}

/**
 * Takes an entity from the source table and looks if there is a field
 * matching the property name for this association. The found entity will be
 * saved on the target table for this association by passing supplied
 * `$options`
 *
 * @param \Cake\ORM\Entity $entity an entity from the source table
 * @param array|\ArrayObject $options options to be passed to the save method in
 * the target table
 * @return boolean|Entity false if $entity could not be saved, otherwise it returns
 * the saved entity
 * @see Table::save()
 */
	public function save(Entity $entity, $options = []) {
		$targetEntity = $entity->get($this->property());
		if (empty($targetEntity) || !($targetEntity instanceof Entity)) {
			return $entity;
		}

		$table = $this->target();
		$targetEntity = $table->save($targetEntity, $options);
		if (!$targetEntity) {
			return false;
		}

		$properties = array_combine(
			(array)$this->foreignKey(),
			$targetEntity->extract((array)$table->primaryKey())
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
	protected function _joinCondition(array $options) {
		$conditions = [];
		$tAlias = $this->target()->alias();
		$sAlias = $this->_sourceTable->alias();
		$foreignKey = (array)$options['foreignKey'];
		$primaryKey = (array)$this->_targetTable->primaryKey();

		if (count($foreignKey) !== count($primaryKey)) {
			$msg = 'Cannot match provided foreignKey, got %d columns expected %d';
			throw new \RuntimeException(sprintf($msg, count($foreignKey), count($primaryKey)));
		}

		foreach ($foreignKey as $k => $f) {
			$field = sprintf('%s.%s', $tAlias, $primaryKey[$k]);
			$value = new IdentifierExpression(sprintf('%s.%s', $sAlias, $f));
			$conditions[$field] = $value;
		}

		return $conditions;
	}

}
