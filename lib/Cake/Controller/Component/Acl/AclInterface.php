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
 * @package       Cake.Controller.Component.Acl
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Access Control List interface.
 * Implementing classes are used by AclComponent to perform ACL checks in Cake.
 *
 * @package       Cake.Controller.Component.Acl
 */
interface AclInterface {

/**
 * Empty method to be overridden in subclasses
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return bool Success
 */
	public function check($aro, $aco, $action = "*");

/**
 * Allow methods are used to grant an ARO access to an ACO.
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return bool Success
 */
	public function allow($aro, $aco, $action = "*");

/**
 * Deny methods are used to remove permission from an ARO to access an ACO.
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return bool Success
 */
	public function deny($aro, $aco, $action = "*");

/**
 * Inherit methods modify the permission for an ARO to be that of its parent object.
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return bool Success
 */
	public function inherit($aro, $aco, $action = "*");

/**
 * Initialization method for the Acl implementation
 *
 * @param Component $component The AclComponent instance.
 * @return void
 */
	public function initialize(Component $component);

}
