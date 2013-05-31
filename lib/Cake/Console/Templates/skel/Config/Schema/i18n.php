<?php
/**
 * This is i18n Schema file
 *
 * Use it to configure database for i18n
 *
 * PHP 5
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
 * @package       app.Config.Schema
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
