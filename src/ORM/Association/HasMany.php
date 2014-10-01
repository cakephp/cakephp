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

use Cake\ORM\Association;
use Cake\ORM\Association\DependentDeleteTrait;
use Cake\ORM\Association\ExternalAssociationTrait;
use Cake\ORM\Entity;
use Cake\ORM\Table;

/**
 * Represents an N - 1 relationship where the target side of the relationship
 * will have one or multiple records per each one in the source side.
 *
 * An example of a HasMany association would be Author has many Articles.
 */
class HasMany extends Association {

	use DependentDeleteTrait;
	use ExternalAssociationTrait;

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
	protected $_strategy = parent::STRATEGY_SELECT;

/**
 * Returns whether or not the passed table is the owning side for this
 * association. This means that rows in the 'target' table would miss important
 * or required information if the row in 'source' did not exist.
 *
 * @param \Cake\ORM\Table $side The potential Table with ownership
 * @return bool
 */
	public function isOwningSide(Table $side) {
		return $side === $this->source();
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
 * @return bool|Entity false if $entity could not be saved, otherwise it returns
 * the saved entity
 * @see Table::save()
 * @throws \InvalidArgumentException when the association data cannot be traversed.
 */
	public function saveAssociated(Entity $entity, array $options = []) {
		$targetEntities = $entity->get($this->property());
		if (empty($targetEntities)) {
			return $entity;
		}

		if (!is_array($targetEntities) && !($targetEntities instanceof \Traversable)) {
			$name = $this->property();
			$message = sprintf('Could not save %s, it cannot be traversed', $name);
			throw new \InvalidArgumentException($message);
		}

		$properties = array_combine(
			(array)$this->foreignKey(),
			$entity->extract((array)$this->source()->primaryKey())
		);
		$target = $this->target();
		$original = $targetEntities;

		foreach ($targetEntities as $k => $targetEntity) {
			if (!($targetEntity instanceof Entity)) {
				break;
			}

			if (!empty($options['atomic'])) {
				$targetEntity = clone $targetEntity;
			}

			$targetEntity->set($properties, ['guard' => false]);
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
 * {@inheritDoc}
 */
	protected function _linkField($options) {
		$links = [];
		$name = $this->alias();

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
	public function type() {
		return self::ONE_TO_MANY;
	}

}
