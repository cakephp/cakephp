<?php
/**
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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * An authorization adapter for AuthComponent.  Provides the ability to authorize using a controller callback.
 * Your controller's isAuthorized() method should return a boolean to indicate whether or not the user is authorized.
 *
 * {{{
 *	function isAuthorized($user) {
 *		if (!empty($this->request->params['admin'])) {
 *			return $user['role'] == 'admin';
 *		}
 *		return !empty($user);
 *	}
 * }}}
 *
 * the above is simple implementation that would only authorize users of the 'admin' role to access
 * admin routing.
 *
 * @package cake.libs.controller.components.auth
 * @since 2.0
 * @see AuthComponent::$authenticate
 */
class ControllerAuthorize {
/**
 * Controller for the request.
 *
 * @var Controller
 */
	protected $_controller = null;

/**
 * Constructor
 *
 * @param Controller $controller The controller for this request.
 * @param string $settings An array of settings.  This class does not use any settings.
 */
	public function __construct(Controller $controller, $settings = array()) {
		$this->controller($controller);
	}

/**
 * Checks user authorization using a controller callback.
 *
 * @param array $user Active user data
 * @param CakeRequest $request 
 * @return boolean
 */
	public function authorize($user, CakeRequest $request) {
		return (bool) $this->_controller->isAuthorized($user);
	}

/**
 * Accessor to the controller object.
 *
 * @param mixed $controller null to get, a controller to set.
 * @return mixed.
 */
	public function controller($controller = null) {
		if ($controller) {
			if (!$controller instanceof Controller) {
				throw new CakeException(__('$controller needs to be an instance of Controller'));
			}
			if (!method_exists($controller, 'isAuthorized')) {
				throw new CakeException(__('$controller does not implement an isAuthorized() method.'));
			}
			$this->_controller = $controller;
			return true;
		}
		return $this->_controller;
	}
}