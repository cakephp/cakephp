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
use Cake\ORM\Entity;

/**
 * A container/collection for association classes.
 *
 * Contains methods for managing associations, and
 * ordering operations around saving and deleting.
 */
class Associations {

	protected $_items = [];

/**
 * Add an association to the collection
 *
 * @param string $alias The association alias
 * @param Association The association to add.
 * @return void
 */
	public function add($alias, Association $association) {
		$this->_items[strtolower($alias)] = $association;
	}

/**
 * Fetch an attached association by name.
 *
 * @param string $alias The association alias to get.
 * @return Association|null Either the association or null.
 */
	public function get($alias) {
		$alias = strtolower($alias);
		if (isset($this->_items[$alias])) {
			return $this->_items[$alias];
		}
		return null;
	}

/**
 * Check for an attached association by name.
 *
 * @param string $alias The association alias to get.
 * @return boolean Whether or not the association exists.
 */
	public function has($alias) {
		return isset($this->_items[strtolower($alias)]);
	}

	public function drop($alias) {
	}

	public function saveParents(Entity $entity, $associations, $options) {
	}

	protected function saveChildren(Entity $entity, $associations, $options) {
	}

	public function cascadeDelete(Entity $entity, $options) {
	}

}
