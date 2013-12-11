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

use Cake\ORM\Table;

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
 * @param array $associations The associations to include.
 * @return Cake\ORM\Entity
 */
	public function one(array $data, $associations = []) {
	}

/**
 * Hydrate many entities and their associated data.
 *
 * @param array $data The data to hydrate.
 * @param array $associations The associations to include.
 * @return array An array of hydrated records.
 */
	public function many(array $data, $associations = []) {
	}

}
