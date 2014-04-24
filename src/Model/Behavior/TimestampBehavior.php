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
namespace Cake\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\Utility\Time;

class TimestampBehavior extends Behavior {

/**
 * Default config
 *
 * These are merged with user-provided config when the behavior is used.
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
	protected $_defaultConfig = [
		'implementedFinders' => [],
		'implementedMethods' => [
			'timestamp' => 'timestamp',
			'touch' => 'touch'
		],
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
 * Constructor
 *
 * If events are specified - do *not* merge them with existing events,
 * overwrite the events to listen on
 *
 * @param Table $table The table this behavior is attached to.
 * @param array $config The config for this behavior.
 */
	public function __construct(Table $table, array $config = []) {
		parent::__construct($table, $config);

		if (isset($config['events'])) {
			$this->config('events', $config['events'], false);
		}
	}

/**
 * handleEvent
 *
 * There is only one event handler, it can be configured to be called for any event
 *
 * @param Event $event
 * @param Entity $entity
 * @throws \UnexpectedValueException if a field's when value is misdefined
 * @return true (irrespective of the behavior logic, the save will not be prevented)
 * @throws \UnexpectedValueException When the value for an event is not 'always', 'new' or 'existing'
 */
	public function handleEvent(Event $event, Entity $entity) {
		$eventName = $event->name();
		$events = $this->_config['events'];

		$new = $entity->isNew() !== false;
		$refresh = $this->_config['refreshTimestamp'];

		foreach ($events[$eventName] as $field => $when) {
			if (!in_array($when, ['always', 'new', 'existing'])) {
				throw new \UnexpectedValueException(
					sprintf('When should be one of "always", "new" or "existing". The passed value "%s" is invalid', $when)
				);
			}
			if (
				$when === 'always' ||
				($when === 'new' && $new) ||
				($when === 'existing' && !$new)
			) {
				$this->_updateField($entity, $field, $refresh);
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
		return array_fill_keys(array_keys($this->_config['events']), 'handleEvent');
	}

/**
 * Get or set the timestamp to be used
 *
 * Set the timestamp to the given DateTime object, or if not passed a new DateTime object
 * If an explicit date time is passed, the config option `refreshTimestamp` is
 * automatically set to false.
 *
 * @param \DateTime $ts
 * @param bool $refreshTimestamp
 * @return \Cake\Utility\Time
 */
	public function timestamp(\DateTime $ts = null, $refreshTimestamp = false) {
		if ($ts) {
			if ($this->_config['refreshTimestamp']) {
				$this->_config['refreshTimestamp'] = false;
			}
			$this->_ts = new Time($ts);
		} elseif ($this->_ts === null || $refreshTimestamp) {
			$this->_ts = new Time();
		}

		return $this->_ts;
	}

/**
 * Touch an entity
 *
 * Bumps timestamp fields for an entity. For any fields configured to be updated
 * "always" or "existing", update the timestamp value. This method will overwrite
 * any pre-existing value.
 *
 * @param Entity $entity
 * @param string $eventName
 * @return bool true if a field is updated, false if no action performed
 */
	public function touch(Entity $entity, $eventName = 'Model.beforeSave') {
		$events = $this->_config['events'];
		if (empty($events[$eventName])) {
			return false;
		}

		$return = false;
		$refresh = $this->_config['refreshTimestamp'];

		foreach ($events[$eventName] as $field => $when) {
			if (in_array($when, ['always', 'existing'])) {
				$return = true;
				$entity->dirty($field, false);
				$this->_updateField($entity, $field, $refresh);
			}
		}

		return $return;
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
