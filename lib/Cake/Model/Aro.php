<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model
 * @since         CakePHP(tm) v 0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AclNode', 'Model');

/**
 * Access Request Object
 *
 * @package       Cake.Model
 */
class Aro extends AclNode {

/**
 * Model name
 *
 * @var string
 */
	public $name = 'Aro';

/**
 * AROs are linked to ACOs by means of Permission
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('Aco' => array('with' => 'Permission'));
}
