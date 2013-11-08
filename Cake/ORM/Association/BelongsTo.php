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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\ORM\Association;

use Cake\Database\Expression\IdentifierExpression;
use Cake\ORM\Association;
use Cake\ORM\Entity;
use Cake\ORM\Query;
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
 * @param Cake\ORM\Entity $entity The entity that started the cascaded delete.
 * @param array $options The options for the original delete.
 * @return boolean Success.
 */
	public function cascadeDelete(Entity $entity, $options = []) {
		return true;
	}

/**
 * Returns a single or multiple conditions to be appended to the generated join
 * clause for getting the results on the target table.
 *
 * @param array $options list of options passed to attachTo method
 * @return array
 */
	protected function _joinCondition(array $options) {
		$field = sprintf(
			'%s.%s',
			$this->target()->alias(),
			$this->_targetTable->primaryKey()
		);
		$value = new IdentifierExpression(sprintf(
			'%s.%s',
			$this->_sourceTable->alias(),
			$options['foreignKey']
		));
		return [$field => $value];
	}

}
