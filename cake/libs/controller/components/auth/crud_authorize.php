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
App::import('Component', 'auth/base_authorize');

/**
 * An authorization adapter for AuthComponent.  Provides the ability to authorize using CRUD mappings.
 * CRUD mappings allow you to translate controller actions into *C*reate *R*ead *U*pdate *D*elete actions.
 * This is then checked in the AclComponent as specific permissions.
 *
 * For example, taking `/posts/index` as the current request.  The default mapping for `index`, is a `read` permission
 * check. The Acl check would then be for the `posts` controller with the `read` permission.  This allows you
 * to create permission systems that focus more on what is being done to resources, rather than the specific actions
 * being visited.
 *
 * @package cake.libs.controller.components.auth
 * @since 2.0
 * @see AuthComponent::$authenticate
 * @see AclComponent::check()
 */
class CrudAuthorize extends BaseAuthorize {

/**
 * Authorize a user using the mapped actions and the AclComponent.
 *
 * @param array $user The user to authorize
 * @param CakeRequest $request The request needing authorization.
 * @return boolean
 */
	public function authorize($user, CakeRequest $request) {
		if (!isset($this->_actionMap[$request->params['action']])) {
			trigger_error(__(
				'CrudAuthorize::authorize() - Attempted access of un-mapped action "%1$s" in controller "%2$s"',
				$request->action, 
				$request->controller
				),
				E_USER_WARNING
			);
			return false;
		}
		$Acl = $this->_controller->Components->load('Acl');
		return $Acl->check(
			$user,
			$this->action($request, ':controller'),
			$this->_actionMap[$request->params['action']]
		);
	}
}