<?php
/**
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('BaseAuthorize', 'Controller/Component/Auth');

/**
 * An authorization adapter for AuthComponent.  Provides the ability to authorize using the AclComponent,
 * If AclComponent is not already loaded it will be loaded using the Controller's ComponentCollection.
 *
 * @package       Cake.Controller.Component.Auth
 * @since 2.0
 * @see AuthComponent::$authenticate
 * @see AclComponent::check()
 */
class ActionsAuthorize extends BaseAuthorize {

/**
 * Authorize a user using the AclComponent.
 *
 * @param array $user The user to authorize
 * @param CakeRequest $request The request needing authorization.
 * @return boolean
 */
	public function authorize($user, CakeRequest $request) {
		$Acl = $this->_Collection->load('Acl');
		$user = array($this->settings['userModel'] => $user);
		return $Acl->check($user, $this->action($request));
	}
}
