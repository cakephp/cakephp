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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\ORM;

use Cake\ORM\Association;
use Cake\ORM\Table;

/**
 * Contains logic to convert array data into entities.
 *
 * Useful when converting request data into entities.
 *
 * @see Cake\ORM\Table::newEntity()
 * @see Cake\ORM\Table::newEntities()
 */
class EntityValidator {

/**
 * The table instance this marshaller is for.
 *
 * @var Cake\ORM\Table
 */
	protected $_table;

/**
 * Validator type yo use
 *
 * @var string
 */
	protected $_type;

/**
 * Constructor.
 *
 * @param Cake\ORM\Table $table
 */
	public function __construct(Table $table, $type = 'default') {
		$this->_table = $table;
		$this->_type = $type;
	}

/**
 * Build the map of property => association names.
 *
 * @param array $include The array of included associations.
 * @return array
 */
	protected function _buildPropertyMap($include) {
		$map = [];
		foreach ($include as $key => $nested) {
			if (is_int($key) && is_scalar($nested)) {
				$key = $nested;
				$nested = [];
			}
			$validate = isset($nested['validate']) ? $nested['validate'] : true;
			$nested = isset($nested['associated']) ? $nested['associated'] : [];
			$assoc = $this->_table->association($key);
			if ($assoc) {
				$map[$assoc->property()] = [
					'association' => $assoc,
					'nested' => $nested,
					'validate' => $validate
				];
			}
		}
		return $map;
	}

	public function one($entity, $include = []) {
		$propertyMap = $this->_buildPropertyMap($include);
		$valid = true;

		foreach ($propertyMap as $key => $assoc) {
			$value = $entity->get($key);
			$validate = $assoc['validate'];
			$assoc = $assoc['association'];
			$nested = $propertyMap[$key]['nested'];

			if (!$value || !$validate) {
				continue;
			}

			if ($validate === true) {
				$validate = 'default';
			}

			$validator = $assoc->target()->entityValidator($validate);
			if ($assoc->type() === Association::ONE_TO_ONE) {
				$valid = $validator->one($value, $nested) && $valid;
			} else {
				$valid = $validator->many($value, $nested) && $valid;
			}
		}

		$validator = $this->_table->validator($this->_type);
		$valid = $entity->validate($validator) && $valid;
		return $valid;
	}

	public function many(array $entities, $include = []) {
		$valid = true;
		foreach ($entities as $entity) {
			$valid = $this->one($entity, $include) && $valid;
		}
		return $valid;
	}

}
