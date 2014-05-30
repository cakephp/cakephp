<?php
/**
 * This is Sessions Schema file
 *
 * Use it to configure database for Sessions
 *
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config.Schema
 * @since         CakePHP(tm) v 0.2.9
 */

/**
 *
 * Using the Schema command line utility
 * cake schema run create Sessions
 *
 */
class SessionsSchema extends CakeSchema {

/**
 * Name property
 *
 * @var string
 */
	public $name = 'Sessions';

/**
 * Before event.
 *
 * @param array $event The event data.
 * @return bool Success
 */
	public function before($event = array()) {
		return true;
	}

/**
 * After event.
 *
 * @param array $event The event data.
 * @return void
 */
	public function after($event = array()) {
	}

/**
 * cake_sessions table definition
 *
 * @var array
 */
	public $cake_sessions = array(
		'id' => array('type' => 'string', 'null' => false, 'key' => 'primary'),
		'data' => array('type' => 'text', 'null' => true, 'default' => null),
		'expires' => array('type' => 'integer', 'null' => true, 'default' => null),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1))
	);

}
