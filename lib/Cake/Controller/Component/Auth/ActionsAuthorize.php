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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('BaseAuthorize', 'Controller/Component/Auth');

/**
 * An authorization adapter for AuthComponent. Provides the ability to authorize using the AclComponent,
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
 * @return bool
 */
	public function authorize($user, CakeRequest $request) {
		$Acl = $this->_Collection->load('Acl');
		$user = array($this->settings['userModel'] => $user);
		return $Acl->check($user, $this->action($request));
	}

}
