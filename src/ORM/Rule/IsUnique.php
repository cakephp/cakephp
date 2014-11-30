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
namespace Cake\ORM\Rule;

use Cake\Datasource\EntityInterface;

/**
 *
 *
 */
class IsUnique {

	public function __construct(array $fields) {
		$this->_fields = $fields;
	}

	public function __invoke(EntityInterface $entity, array $options) {
		$conditions = $entity->extract($this->_fields);
		if ($entity->isNew() === false) {
			$keys = (array)$options['repository']->primaryKey();
			$conditions['NOT'] = $entity->extract($keys);
		}

		return !$options['repository']->exists($conditions);
	}

}
