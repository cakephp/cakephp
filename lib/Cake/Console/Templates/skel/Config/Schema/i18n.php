<?php
/**
 * This is i18n Schema file
 *
 * Use it to configure database for i18n
 *
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config.Schema
 * @since         CakePHP(tm) v 0.2.9
 */

/**
 *
 * Using the Schema command line utility
 *
 * Use it to configure database for i18n
 *
 * cake schema run create i18n
 */
class I18nSchema extends CakeSchema {

	public $name = 'i18n';

	public function before($event = array()) {
		return true;
	}

	public function after($event = array()) {
	}

	public $i18n = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary'),
		'locale' => array('type' => 'string', 'null' => false, 'length' => 6, 'key' => 'index'),
		'model' => array('type' => 'string', 'null' => false, 'key' => 'index'),
		'foreign_key' => array('type' => 'integer', 'null' => false, 'length' => 10, 'key' => 'index'),
		'field' => array('type' => 'string', 'null' => false, 'key' => 'index'),
		'content' => array('type' => 'text', 'null' => true, 'default' => null),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'locale' => array('column' => 'locale', 'unique' => 0), 'model' => array('column' => 'model', 'unique' => 0), 'row_id' => array('column' => 'foreign_key', 'unique' => 0), 'field' => array('column' => 'field', 'unique' => 0))
	);

}
