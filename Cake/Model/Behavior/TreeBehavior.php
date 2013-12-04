<?php
/**
 * Tree behavior class.
 *
 * Enables a model object to act as a node-based tree.
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         CakePHP v 1.2.0.4487
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Table;

/**
 * Tree Behavior.
 *
 * Meta behavior which loads the appropriate implementation dynamically based on database schema
 */
class TreeBehavior extends Behavior {

/**
 * Default settings
 *
 * These are merged with user-provided settings when the behavior is used.
 *
 * @var array
 */
	protected $_defaultSettings = [
		'implementedFinders' => [],
		'implementedMethods' => [],
		'fields' => [
			'parent' => 'parent_id'
		],
		'strategy' => null
	];

/**
 * __construct
 *
 * Determin the right strategy to use, and then add that behavior
 *
 * @param Table $table
 * @param array $settings
 * @return void
 */
	public function __construct(Table $table, array $settings = []) {
		$this->_settings = $settings + $this->_defaultSettings;
		$strategy = $this->_strategy($table);
		$table->addBehavior($strategy, $settings);
	}

/**
 * Derive the right strategy based on the database schema
 *
 * @return string
 */
	protected function _strategy(Table $table) {
		$fields = $this->_fields;
		$schema = $table->schema();

		if ($schema->hasField($fields['parent'])) {
			return 'Tree/AdjacentList';
		}

		throw new Exception(__d('cake_dev', 'Tree behavior added, but no implementation could be found'));
	}
}
