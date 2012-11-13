<?php
/**
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of the files must retain the above copyright notice.
 *
 * @copyright	Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link	http://cakephp.org CakePHP(tm) Project
 * @license	MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('FormAuthenticate', 'Controller/Component/Auth');

/**
 * An authentication adapter for AuthComponent. Provides the ability to authenticate using POST data using Blowfish
 * hashing. Can be used by configuring AuthComponent to use it via the AuthComponent::$authenticate setting.
 *
 * {{{
 * 	$this->Auth->authenticate = array(
 * 		'Blowfish' => array(
 * 			'scope' => array('User.active' => 1)
 * 		)
 * 	)
 * }}}
 *
 * When  configuring BlowfishAuthenticate you can pass in settings to which fields, model and additional conditions
 * are used. See FormAuthenticate::$settings for more information.
 *
 * For inital password hashing/creation see Security::hash(). Other than how the password is initally hashed,
 * BlowfishAuthenticate works exactly the same way as FormAuthenticate.
 *
 * @package	Cake.Controller.Component.Auth
 * @since	CakePHP(tm) v 2.3
 * @see		AuthComponent::$authenticate
 */
class BlowfishAuthenticate extends FormAuthenticate {

/**
 * Authenticates the identity contained in a request. Will use the `settings.userModel`, and `settings.fields`
 * to find POST data that is used to find a matching record in the`settings.userModel`. Will return false if
 * there is no post data, either username or password is missing, or if the scope conditions have not been met.
 *
 * @param CakeRequest $request The request that contains login information.
 * @param CakeResponse $response Unused response object.
 * @return mixed False on login failure. An array of User data on success.
 */
	public function authenticate(CakeRequest $request, CakeResponse $response) {
		$userModel = $this->settings['userModel'];
		list($plugin, $model) = pluginSplit($userModel);

		$fields = $this->settings['fields'];
		if (!$this->_checkFields($request, $model, $fields)) {
			return false;
		}
		$user = $this->_findUser(
			array(
				$model . '.' . $fields['username'] => $request->data[$model][$fields['username']],
			)
		);
		if (!$user) {
			return false;
		}
		$password = Security::hash(
			$request->data[$model][$fields['password']],
			'blowfish',
			$user[$fields['password']]
		);
		if ($password === $user[$fields['password']]) {
			unset($user[$fields['password']]);
			return $user;
		}
		return false;
	}
}
