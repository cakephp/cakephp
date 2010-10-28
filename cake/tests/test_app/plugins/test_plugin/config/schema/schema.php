<?php
/**
 * TestAppSchema file
 *
 * Use for testing the loading of schema files from plugins.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.app.config.sql
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TestPluginAppSchema extends CakeSchema {

	var $name = 'TestPluginApp';

	var $acos = array(
		'id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'length' => 10, 'key' => 'primary'),
		'parent_id' => array('type'=>'integer', 'null' => true, 'default' => NULL, 'length' => 10),
		'model' => array('type'=>'string', 'null' => true),
		'foreign_key' => array('type'=>'integer', 'null' => true, 'default' => NULL, 'length' => 10),
		'alias' => array('type'=>'string', 'null' => true),
		'lft' => array('type'=>'integer', 'null' => true, 'default' => NULL, 'length' => 10),
		'rght' => array('type'=>'integer', 'null' => true, 'default' => NULL, 'length' => 10),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1))
	);
}
