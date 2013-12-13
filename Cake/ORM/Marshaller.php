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
use Cake\Utility\Hash;

/**
 * Contains logic to convert array data into entities.
 *
 * Useful when converting request data into entities.
 *
 * @see Cake\ORM\Table::newEntity()
 * @see Cake\ORM\Table::newEntities()
 */
class Marshaller {

/**
 * Whether or not this marhshaller is in safe mode.
 *
 * @var boolean
 */
	protected $_safe;

/**
 * The table instance this marshaller is for.
 *
 * @var Cake\ORM\Table
 */
	protected $_table;

/**
 * Constructor.
 *
 * @param Cake\ORM\Table $table
 */
	public function __construct(Table $table, $safe = false) {
		$this->_table = $table;
		$this->_safe = $safe;
	}

/**
 * Hydrate one entity and its associated data.
 *
 * @param array $data The data to hydrate.
 * @param array $include The associations to include.
 * @return Cake\ORM\Entity
 */
	public function one(array $data, $include = []) {
		$include = Hash::normalize($include);

		$entityClass = $this->_table->entityClass();

		$entity = new $entityClass();
		foreach ($data as $key => $value) {
			if (array_key_exists($key, $include)) {
				$assoc = $this->_table->association($key);
				$value = $this->_marshalAssociation($assoc, $value, $include[$key]);
				$entity->set($assoc->property(), $value);
			} else {
				$entity->set($key, $value);
			}
		}
		return $entity;
	}

/**
 * Create a new sub-marshaller and marshal the associated data.
 *
 * @return mixed
 */
	protected function _marshalAssociation($assoc, $value, $include) {
		$targetTable = $assoc->target();
		$marshaller = $targetTable->marshaller();
		if ($assoc->type() === Association::ONE_TO_ONE) {
			return $marshaller->one($value, (array)$include);
		} else {
			return $marshaller->many($value, (array)$include);
		}
	}

/**
 * Hydrate many entities and their associated data.
 *
 * @param array $data The data to hydrate.
 * @param array $include The associations to include.
 * @return array An array of hydrated records.
 */
	public function many(array $data, $include = []) {
		$output = [];
		foreach ($data as $record) {
			$output[] = $this->one($record, $include);
		}
		return $output;
	}

}
