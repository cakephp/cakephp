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
 * @package       Cake.Controller.Component.Acl
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AclInterface', 'Controller/Component/Acl');
App::uses('Hash', 'Utility');
App::uses('ClassRegistry', 'Utility');

/**
 * DbAcl implements an ACL control system in the database. ARO's and ACO's are
 * structured into trees and a linking table is used to define permissions. You
 * can install the schema for DbAcl with the Schema Shell.
 *
 * `$aco` and `$aro` parameters can be slash delimited paths to tree nodes.
 *
 * eg. `controllers/Users/edit`
 *
 * Would point to a tree structure like
 *
 * ```
 *	controllers
 *		Users
 *			edit
 * ```
 *
 * @package       Cake.Controller.Component.Acl
 */
class DbAcl extends Object implements AclInterface {

/**
 * Constructor
 */
	public function __construct() {
		parent::__construct();
		$this->Permission = ClassRegistry::init(array('class' => 'Permission', 'alias' => 'Permission'));
		$this->Aro = $this->Permission->Aro;
		$this->Aco = $this->Permission->Aco;
	}

/**
 * Initializes the containing component and sets the Aro/Aco objects to it.
 *
 * @param AclComponent $component The AclComponent instance.
 * @return void
 */
	public function initialize(Component $component) {
		$component->Aro = $this->Aro;
		$component->Aco = $this->Aco;
	}

/**
 * Checks if the given $aro has access to action $action in $aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return bool Success (true if ARO has access to action in ACO, false otherwise)
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/access-control-lists.html#checking-permissions-the-acl-component
 */
	public function check($aro, $aco, $action = "*") {
		return $this->Permission->check($aro, $aco, $action);
	}

/**
 * Allow $aro to have access to action $actions in $aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $actions Action (defaults to *)
 * @param int $value Value to indicate access type (1 to give access, -1 to deny, 0 to inherit)
 * @return bool Success
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/access-control-lists.html#assigning-permissions
 */
	public function allow($aro, $aco, $actions = "*", $value = 1) {
		return $this->Permission->allow($aro, $aco, $actions, $value);
	}

/**
 * Deny access for $aro to action $action in $aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return bool Success
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/access-control-lists.html#assigning-permissions
 */
	public function deny($aro, $aco, $action = "*") {
		return $this->allow($aro, $aco, $action, -1);
	}

/**
 * Let access for $aro to action $action in $aco be inherited
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return bool Success
 */
	public function inherit($aro, $aco, $action = "*") {
		return $this->allow($aro, $aco, $action, 0);
	}

/**
 * Allow $aro to have access to action $actions in $aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return bool Success
 * @see allow()
 */
	public function grant($aro, $aco, $action = "*") {
		return $this->allow($aro, $aco, $action);
	}

/**
 * Deny access for $aro to action $action in $aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return bool Success
 * @see deny()
 */
	public function revoke($aro, $aco, $action = "*") {
		return $this->deny($aro, $aco, $action);
	}

/**
 * Get an array of access-control links between the given Aro and Aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @return array Indexed array with: 'aro', 'aco' and 'link'
 */
	public function getAclLink($aro, $aco) {
		return $this->Permission->getAclLink($aro, $aco);
	}

/**
 * Get the keys used in an ACO
 *
 * @param array $keys Permission model info
 * @return array ACO keys
 */
	protected function _getAcoKeys($keys) {
		return $this->Permission->getAcoKeys($keys);
	}

}
