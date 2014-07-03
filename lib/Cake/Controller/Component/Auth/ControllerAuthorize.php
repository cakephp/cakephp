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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('BaseAuthorize', 'Controller/Component/Auth');

/**
 * An authorization adapter for AuthComponent. Provides the ability to authorize using a controller callback.
 * Your controller's isAuthorized() method should return a boolean to indicate whether or not the user is authorized.
 *
 * {{{
 *	public function isAuthorized($user) {
 *		if (!empty($this->request->params['admin'])) {
 *			return $user['role'] === 'admin';
 *		}
 *		return !empty($user);
 *	}
 * }}}
 *
 * the above is simple implementation that would only authorize users of the 'admin' role to access
 * admin routing.
 *
 * @package       Cake.Controller.Component.Auth
 * @since 2.0
 * @see AuthComponent::$authenticate
 */
class ControllerAuthorize extends BaseAuthorize {

/**
 * Get/set the controller this authorize object will be working with. Also checks that isAuthorized is implemented.
 *
 * @param Controller $controller null to get, a controller to set.
 * @return mixed
 * @throws CakeException
 */
	public function controller(Controller $controller = null) {
		if ($controller) {
			if (!method_exists($controller, 'isAuthorized')) {
				throw new CakeException(__d('cake_dev', '$controller does not implement an %s method.', 'isAuthorized()'));
			}
		}
		return parent::controller($controller);
	}

/**
 * Checks user authorization using a controller callback.
 *
 * @param array $user Active user data
 * @param CakeRequest $request Request instance.
 * @return bool
 */
	public function authorize($user, CakeRequest $request) {
		return (bool)$this->_Controller->isAuthorized($user);
	}

}
