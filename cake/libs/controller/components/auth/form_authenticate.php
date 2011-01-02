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
 * An authentication adapter for AuthComponent.  Provides the ability to authenticate using POST
 * data.  Can be used by configuring AuthComponent to use it via the AuthComponent::$authenticate setting.
 *
 * {{{
 *	$this->Auth->authenticate = array(
 *		'Form' => array(
 *			'scope' => array('User.active' => 1)
 *		)
 *	)
 * }}}
 *
 * When configuring FormAuthenticate you can pass in settings to which fields, model and additional conditions
 * are used. See FormAuthenticate::$settings for more information.
 *
 * @package cake.libs.controller.components.auth
 * @since 2.0
 * @see AuthComponent::$authenticate
 */
class FormAuthenticate {

/**
 * Settings for this object.
 *
 * - `fields` The fields to use to identify a user by.
 * - `userModel` The model name of the User, defaults to User.
 * - `scope` Additional conditions to use when looking up and authenticating users,
 *    i.e. `array('User.is_active' => 1).`
 *
 * @var array
 */
	public $settings = array(
		'fields' => array(
			'username' => 'username',
			'password' => 'password'
		),
		'userModel' => 'User',
		'scope' => array()
	);

/**
 * Constructor
 *
 * @param array $settings Array of settings to use.
 */
	public function __construct($settings) {
		$this->settings = Set::merge($this->settings, $settings);
	}

/**
 * Authenticates the identity contained in a request.  Will use the `settings.userModel`, and `settings.fields`
 * to find POST data that is used to find a matching record in the `settings.userModel`.  Will return false if 
 * there is no post data, either username or password is missing, of if the scope conditions have not been met.
 *
 * @param CakeRequest $request The request that contains login information.
 * @return mixed.  False on login failure.  An array of User data on success.
 */
	public function authenticate(CakeRequest $request) {
		$userModel = $this->settings['userModel'];
		$fields = $this->settings['fields'];
		if (empty($request->data[$userModel])) {
			return false;
		}
		if (
			empty($request->data[$userModel][$fields['username']]) || 
			empty($request->data[$userModel][$fields['password']])
		) {
			return false;
		}
		$conditions = array(
			$userModel . '.' . $fields['username'] => $request->data[$userModel][$fields['username']],
			$userModel . '.' . $fields['password'] => $request->data[$userModel][$fields['password']],
		);
		if (!empty($this->settings['scope'])) {
			$conditions = array_merge($conditions, $this->settings['scope']);
		}
		$result = ClassRegistry::init($userModel)->find('first', array(
			'conditions' => $conditions,
			'recursive' => 0
		));
		if (empty($result) || empty($result[$userModel])) {
			return false;
		}
		unset($result[$userModel][$fields['password']]);
		return $result[$userModel];
	}
}