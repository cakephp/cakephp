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
namespace Cake\ORM;

/**
 * A container/collection for association classes.
 *
 * Contains methods for managing associations, and
 * ordering operations around saving and deleting.
 */
class Associations {

	public function add($alias, Association $association) {
	}

	public function get($alias) {
	}

	public function has($alias) {
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
