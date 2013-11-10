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
 * events - an event-name keyed array of which fields to update, and when, for a given event
 * possible values for when a field will be updated are "always", "new" or "existing", to set
 * the field value always, only when a new record or only when an existing record.
 *
 * refreshTimestamp - if true (the default) the timestamp used will be the current time when
 * the code is executed, to set to an explicit date time value - set refreshTimetamp to false
 * and call setTimestamp() on the behavior class before use.
 *
 * @var array
 */
	protected $_defaultSettings = [
		'implementedFinders' => [],
		'implementedMethods' => ['timestamp' => 'timestamp'],
		'events' => [
			'Model.beforeSave' => [
				'created' => 'new',
				'modified' => 'always'
			]
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
 * handleEvent
 *
 * There is only one event handler, it can be configured to be called for any event
 *
 * @param Event $event
 * @param Entity $entity
 * @throws \UnexpectedValueException if a field's when value is misdefined
 * @return true (irrespective of the behavior logic, the save will not be prevented)
 * @throws \UnexpectedValueException When the value for an event is not 'new', 'existing' or true.
 */
	public function handleEvent(Event $event, Entity $entity) {
		$eventName = $event->name();
		$settings = $this->settings();

		$new = $entity->isNew() !== false;

		foreach ($settings['events'][$eventName] as $field => $when) {
			if (!in_array($when, ['always', 'new', 'existing'])) {
				throw new \UnexpectedValueException(
					__d('cake', 'When should be one of "always", "new" or "existing". The passed value "%s" is invalid', $when)
				);
			}
			if (
				$when === 'always' ||
				($when === 'new' && $new) ||
				($when === 'existing' && !$new)
			) {
				$this->_updateField($entity, $field, $settings['refreshTimestamp']);
			}
		}

		return true;
	}

/**
 * implementedEvents
 *
 * The implemented events of this behavior depend on configuration
 *
 * @return array
 */
	public function implementedEvents() {
		return array_fill_keys(array_keys($this->_settings['events']), 'handleEvent');
	}

/**
 * Get or set the timestamp to be used
 *
 * Set the timestamp to the given DateTime object, or if not passed a new DateTime object
 *
 * @param \DateTime $ts
 * @param bool $refreshTimestamp
 * @return \DateTime
 */
	public function timestamp(\DateTime $ts = null, $refreshTimestamp = false) {
		if ($ts) {
			$this->_ts = $ts;
		} elseif ($this->_ts === null || $refreshTimestamp) {
			$this->_ts = new \DateTime();
		}

		return $this->_ts;
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
		$entity->set($field, $this->timestamp(null, $refreshTimestamp));
	}
}
