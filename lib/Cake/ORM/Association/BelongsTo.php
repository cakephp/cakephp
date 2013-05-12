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

use Cake\ORM\Association;
use Cake\ORM\Query;
use Cake\Utility\Inflector;

class BelongsTo extends Association {

	protected $_canBeJoined = true;

	public function foreignKey($key = null) {
		if ($key === null) {
			if ($this->_foreignKey === null) {
				$this->_foreignKey =  Inflector::underscore($this->target()->alias()) . '_id';
			}
			return $this->_foreignKey;
		}
		return parent::foreignKey($key);
	}

	public function attachTo(Query $query, array $options = []) {
		$target = $this->target();
		$source = $this->source();
		$options += ['includeFields' => true, 'foreignKey' => $this->foreignKey()];

		if (!empty($options['foreignKey'])) {
			$options['conditions'][] =  sprintf('%s.%s = %s.%s',
				$target->alias(),
				$source->primaryKey(),
				$source->alias(),
				$options['foreignKey']
			);
		}

		$joinOptions = ['table' => 1, 'conditions' => 1, 'type' => 1];
		$query->join([$target->alias() => array_intersect_key($options, $joinOptions)]);

		if (empty($options['fields'])) {
			$f = isset($options['fields']) ? $options['fields'] : null;
			if ($options['includeFields'] && ($f === null || $f !== false)) {
				$options['fields'] = array_keys($target->schema());
			}
		}

		if (!empty($options['fields'])) {
			$query->select($query->aliasFields($options['fields'], $target->alias()));
		}
	}

}
