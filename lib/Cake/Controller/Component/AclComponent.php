<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Controller.Component
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Component', 'Controller');
App::uses('AclInterface', 'Controller/Component/Acl');

/**
 * Access Control List factory class.
 *
 * Uses a strategy pattern to allow custom ACL implementations to be used with the same component interface.
 * You can define by changing `Configure::write('Acl.classname', 'DbAcl');` in your core.php. Concrete ACL
 * implementations should extend `AclBase` and implement the methods it defines.
 *
 * @package       Cake.Controller.Component
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/access-control-lists.html
 */
class AclComponent extends Component {

/**
 * Instance of an ACL class
 *
 * @var AclInterface
 */
	protected $_Instance = null;

/**
 * Aro object.
 *
 * @var string
 */
	public $Aro;

/**
 * Aco object
 *
 * @var string
 */
	public $Aco;

/**
 * Constructor. Will return an instance of the correct ACL class as defined in `Configure::read('Acl.classname')`
 *
 * @param ComponentCollection $collection
 * @param array $settings
 * @throws CakeException when Acl.classname could not be loaded.
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
		$name = Configure::read('Acl.classname');
		if (!class_exists($name)) {
			list($plugin, $name) = pluginSplit($name, true);
			App::uses($name, $plugin . 'Controller/Component/Acl');
			if (!class_exists($name)) {
				throw new CakeException(__d('cake_dev', 'Could not find %s.', $name));
			}
		}
		$this->adapter($name);
	}

/**
 * Sets or gets the Adapter object currently in the AclComponent.
 *
 * `$this->Acl->adapter();` will get the current adapter class while
 * `$this->Acl->adapter($obj);` will set the adapter class
 *
 * Will call the initialize method on the adapter if setting a new one.
 *
 * @param AclInterface|string $adapter Instance of AclInterface or a string name of the class to use. (optional)
 * @return AclInterface|void either null, or the adapter implementation.
 * @throws CakeException when the given class is not an instance of AclInterface
 */
	public function adapter($adapter = null) {
		if ($adapter) {
			if (is_string($adapter)) {
				$adapter = new $adapter();
			}
			if (!$adapter instanceof AclInterface) {
				throw new CakeException(__d('cake_dev', 'AclComponent adapters must implement AclInterface'));
			}
			$this->_Instance = $adapter;
			$this->_Instance->initialize($this);
			return;
		}
		return $this->_Instance;
	}

/**
 * Pass-thru function for ACL check instance.  Check methods
 * are used to check whether or not an ARO can access an ACO
 *
 * @param array|string|Model $aro ARO The requesting object identifier. See `AclNode::node()` for possible formats
 * @param array|string|Model $aco ACO The controlled object identifier. See `AclNode::node()` for possible formats
 * @param string $action Action (defaults to *)
 * @return boolean Success
 */
	public function check($aro, $aco, $action = "*") {
		return $this->_Instance->check($aro, $aco, $action);
	}

/**
 * Pass-thru function for ACL allow instance. Allow methods
 * are used to grant an ARO access to an ACO.
 *
 * @param array|string|Model $aro ARO The requesting object identifier. See `AclNode::node()` for possible formats
 * @param array|string|Model $aco ACO The controlled object identifier. See `AclNode::node()` for possible formats
 * @param string $action Action (defaults to *)
 * @return boolean Success
 */
	public function allow($aro, $aco, $action = "*") {
		return $this->_Instance->allow($aro, $aco, $action);
	}

/**
 * Pass-thru function for ACL deny instance. Deny methods
 * are used to remove permission from an ARO to access an ACO.
 *
 * @param array|string|Model $aro ARO The requesting object identifier. See `AclNode::node()` for possible formats
 * @param array|string|Model $aco ACO The controlled object identifier. See `AclNode::node()` for possible formats
 * @param string $action Action (defaults to *)
 * @return boolean Success
 */
	public function deny($aro, $aco, $action = "*") {
		return $this->_Instance->deny($aro, $aco, $action);
	}

/**
 * Pass-thru function for ACL inherit instance. Inherit methods
 * modify the permission for an ARO to be that of its parent object.
 *
 * @param array|string|Model $aro ARO The requesting object identifier. See `AclNode::node()` for possible formats
 * @param array|string|Model $aco ACO The controlled object identifier. See `AclNode::node()` for possible formats
 * @param string $action Action (defaults to *)
 * @return boolean Success
 */
	public function inherit($aro, $aco, $action = "*") {
		return $this->_Instance->inherit($aro, $aco, $action);
	}

/**
 * Pass-thru function for ACL grant instance. An alias for AclComponent::allow()
 *
 * @param array|string|Model $aro ARO The requesting object identifier. See `AclNode::node()` for possible formats
 * @param array|string|Model $aco ACO The controlled object identifier. See `AclNode::node()` for possible formats
 * @param string $action Action (defaults to *)
 * @return boolean Success
 * @deprecated
 */
	public function grant($aro, $aco, $action = "*") {
		trigger_error(__d('cake_dev', 'AclComponent::grant() is deprecated, use allow() instead'), E_USER_WARNING);
		return $this->_Instance->allow($aro, $aco, $action);
	}

/**
 * Pass-thru function for ACL grant instance. An alias for AclComponent::deny()
 *
 * @param array|string|Model $aro ARO The requesting object identifier. See `AclNode::node()` for possible formats
 * @param array|string|Model $aco ACO The controlled object identifier. See `AclNode::node()` for possible formats
 * @param string $action Action (defaults to *)
 * @return boolean Success
 * @deprecated
 */
	public function revoke($aro, $aco, $action = "*") {
		trigger_error(__d('cake_dev', 'AclComponent::revoke() is deprecated, use deny() instead'), E_USER_WARNING);
		return $this->_Instance->deny($aro, $aco, $action);
	}

}
