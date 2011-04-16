<?php
/**
 *
 * PHP 5
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
 * @subpackage    cake.cake.libs.model
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Load Model and AppModel
 */
App::uses('AppModel', 'Model');

/**
 * Access Control Object
 *
 * @package       cake
 * @subpackage    cake.cake.libs.model
 */
class Aco extends AclNode {

/**
 * Model name
 *
 * @var string
 * @access public
 */
	public $name = 'Aco';

/**
 * Binds to ARO nodes through permissions settings
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('Aro' => array('with' => 'Permission'));
}