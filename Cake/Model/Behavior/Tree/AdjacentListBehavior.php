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
namespace Cake\Model\Behavior\Tree;

use Cake\ORM\Behavior;

class AdjacentListBehavior extends Behavior {

/**
 * Default settings
 *
 * These are merged with user-provided settings when the behavior is used.
 *
 * @var array
 */
	protected $_defaultSettings = [
		'implementedFinders' => ['threaded'],
		'implementedMethods' => [],
	];

/**
 * Results for this finder will be a nested array, and is appropriate if you want
 * to use the parent_id field of your model data to build nested results.
 *
 * Values belonging to a parent row based on their parent_id value will be
 * recursively nested inside the parent row values using the `children` property
 *
 * @param \Cake\ORM\Query $query
 * @param array $options
 * @return \Cake\ORM\Query
 */
	public function findThreaded(Query $query, array $options = []) {
		$parents = [];
		$hydrate = $query->hydrate();
		$mapper = function($key, $row, $mapReduce) use (&$parents) {
			$row['children'] = [];
			$parents[$row['id']] =& $row;
			$mapReduce->emitIntermediate($row['parent_id'], $row['id']);
		};

		$reducer = function($key, $values, $mapReduce) use (&$parents, $hydrate) {
			if (empty($key) || !isset($parents[$key])) {
				foreach ($values as $id) {
					$parents[$id] = $hydrate ? $parents[$id] : new \ArrayObject($parents[$id]);
					$mapReduce->emit($parents[$id]);
				}
				return;
			}

			foreach ($values as $id) {
				$parents[$key]['children'][] =& $parents[$id];
			}
		};

		$query->mapReduce($mapper, $reducer);
		if (!$hydrate) {
			$query->mapReduce(function($key, $row, $mapReduce) {
				$mapReduce->emit($row->getArrayCopy());
			});
		}

		return $query;
	}

}
