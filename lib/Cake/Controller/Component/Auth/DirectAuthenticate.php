<?php
App::uses('BaseAuthenticate', 'Controller/Component/Auth');

/**
 * An authentication adapter for AuthComponent to directly log in a user by username, id or
 * any other distinct identification.
 *
 * Inside a controller(/component):
 *
 *   $this->request->data = array('User' => array('id' => $userId));
 *   $this->Auth->authenticate = array('Direct' => array('contain' => array('Role.id'), 'fields'=>array('username' => 'id')));
 *   $result = $this->Auth->login();
 *
 * This has several advantages over using Auth->login($data) directly:
 * - You keep it dry, especially when using contain ($data would have to have the exact same data).
 * - No overhead - retrieving the data prior to the login is not necessary. It's short and easy.
 * - You keep it centralized, only one single mechanism to login (using your Authentication adapters
 *   and its common _findUser() method). It also respects the scope and contain settings specified
 *   in your AppController just as any other adapter.
 *
 */
class DirectAuthenticate extends BaseAuthenticate {

/**
 * Authenticates the identity contained in a request.  Will use the `settings.userModel`, and `settings.fields`
 * to find POST data that is used to find a matching record in the `settings.userModel`.  Will return false if
 * there is no post data, username is missing, of if the scope conditions have not been met.
 *
 * @param CakeRequest $request The request that contains login information.
 * @param CakeResponse $response Unused response object.
 * @return mixed.  False on login failure.  An array of User data on success.
 */
	public function authenticate(CakeRequest $request, CakeResponse $response) {
		$userModel = $this->settings['userModel'];
		list($plugin, $model) = pluginSplit($userModel);

		$fields = $this->settings['fields'];
		if (!$this->_checkFields($request, $model, $fields)) {
			return false;
		}
		$conditions = array(
			$model . '.' . $fields['username'] => $request->data[$model][$fields['username']]
		);
		return $this->_findUser($conditions);
	}

/**
 * Checks the fields to ensure they are supplied.
 *
 * @param CakeRequest $request The request that contains login information.
 * @param string $model The model used for login verification.
 * @param array $fields The fields to be checked.
 * @return boolean False if the fields have not been supplied. True if they exist.
 */
	protected function _checkFields(CakeRequest $request, $model, $fields) {
		if (empty($request->data[$model])) {
			return false;
		}
		if (empty($request->data[$model][$fields['username']])) {
			return false;
		}
		return true;
	}

/**
 * Find a user record using the standard options.
 *
 * The $conditions parameter can be a (string)username or an array containing conditions for Model::find('first').
 *
 * @param array $conditions An array of find conditions.
 * @return Mixed Either false on failure, or an array of user data.
 */
	protected function _findUser($conditions, $password = null) {
		$userModel = $this->settings['userModel'];
		list($plugin, $model) = pluginSplit($userModel);
		$fields = $this->settings['fields'];

		$user = parent::_findUser($conditions);
		if (isset($user[$fields['password']])) {
			unset($user[$fields['password']]);
		}
		return $user;
	}

}
