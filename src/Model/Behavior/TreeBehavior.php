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
namespace Cake\Model\Behavior;

use ArrayObject;
use Cake\Collection\Collection;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class TreeBehavior extends Behavior {

/**
 * Table instance
 *
 * @var \Cake\ORM\Table
 */
	protected $_table;

/**
 * Default config
 *
 * These are merged with user-provided configuration when the behavior is used.
 *
 * @var array
 */
	protected static $_defaultConfig = [
		'parent' => 'parent_id',
		'left' => 'lft',
		'right' => 'rght',
		'scope' => null
	];

/**
 * Constructor
 *
 * @param Table $table The table this behavior is attached to.
 * @param array $config The config for this behavior.
 */
	public function __construct(Table $table, array $config = []) {
		parent::__construct($table, $config);
		$this->_table = $table;
	}

	public function beforeSave(Event $event, $entity) {
		$config = $this->config();

	}

}
