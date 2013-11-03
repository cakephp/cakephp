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

use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Table;

class TimestampBehavior extends Behavior {

/**
 * Default settings
 *
 * These are merged with user-provided settings when the behavior is used.
 *
 * refreshTimestamp - if true (the default) the timestamp used will be the current time when
 * the code is executed, to set to an explicit date time value - set refreshTimetamp to false
 * and call setTimestamp() on the behavior class before use.
 *
 * @var array
 */
	protected $_defaultSettings = [
		'fields' => [
			'created' => 'created',
			'updated' => 'modified'
		],
		'refreshTimestamp' => true
	];

/**
 * Current timestamp
 *
 * @var \DateTime
 */
	protected $_ts;

/**
 * Constructor
 *
 * Merge settings with the default and store in the settings property
 *
 * @param Table $table The table this behavior is attached to.
 * @param array $settings The settings for this behavior.
 */
	public function __construct(Table $table, array $settings = []) {
		$this->_settings = $settings + $this->_defaultSettings;
	}

/**
 * beforeSave
 *
 * There is only one event handler, it can be configured to be called for any event
 *
 * @param Event $event
 * @param Entity $entity
 * @return true (irrespective of the behavior logic, the save will not be prevented)
 */
	public function beforeSave(Event $event, Entity $entity) {
		$settings = $this->settings();
		$new = $entity->isNew() !== false;

		if ($new) {
			$this->_updateField($entity, $settings['fields']['created'], $settings['refreshTimestamp']);
		}
		$this->_updateField($entity, $settings['fields']['updated'], $settings['refreshTimestamp']);

		return true;
	}

/**
 * getTimestamp
 *
 * Gets the current timestamp. If $refreshTimestamp is not truthy, the existing timestamp will be
 * returned
 *
 * @return \DateTime
 */
	public function getTimestamp($refreshTimestamp = null) {
		if ($this->_ts === null || $refreshTimestamp) {
			$this->setTimestamp();
		}

		return $this->_ts;
	}

/**
 * setTimestamp
 *
 * Set the timestamp to the given DateTime object, or if not passed a new DateTime object
 *
 * @param int $ts
 * @return void
 */
	public function setTimestamp(\DateTime $ts = null) {
		if ($ts === null) {
			$ts = new \DateTime();
		}
		$this->_ts = $ts;
	}

/**
 * Update a field, if it hasn't been updated already
 *
 * @param Entity $entity
 * @param string $field
 * @param bool $refreshTimestamp
 * @return void
 */
	protected function _updateField(Entity $entity, $field, $refreshTimestamp) {
		if ($entity->dirty($field)) {
			return;
		}
		$entity->set($field, $this->getTimestamp($refreshTimestamp));
	}
}
