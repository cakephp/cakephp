<?php
/**
 *
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component\Auth;

use Cake\Controller\ComponentRegistry;
use Cake\Network\Request;
use Cake\Network\Response;

/**
 * An authentication adapter for AuthComponent. Provides the ability to authenticate using POST
 * data. Can be used by configuring AuthComponent to use it via the AuthComponent::$authenticate config.
 *
 * {{{
 *	$this->Auth->authenticate = array(
 *		'Form' => array(
 *			'scope' => array('Users.active' => 1)
 *		)
 *	)
 * }}}
 *
 * When configuring FormAuthenticate you can pass in config to which fields, model and additional conditions
 * are used. See FormAuthenticate::$_config for more information.
 *
 * @see AuthComponent::$authenticate
 */
class FormAuthenticate extends BaseAuthenticate {

/**
 * Checks the fields to ensure they are supplied.
 *
 * @param \Cake\Network\Request $request The request that contains login information.
 * @param string $model The model used for login verification.
 * @param array $fields The fields to be checked.
 * @return boolean False if the fields have not been supplied. True if they exist.
 */
	protected function _checkFields(Request $request, $model, $fields) {
		if (empty($request->data[$model])) {
			return false;
		}
		foreach (array($fields['username'], $fields['password']) as $field) {
			$value = $request->data($model . '.' . $field);
			if (empty($value) || !is_string($value)) {
				return false;
			}
		}
		return true;
	}

/**
 * Authenticates the identity contained in a request. Will use the `config.userModel`, and `config.fields`
 * to find POST data that is used to find a matching record in the `config.userModel`. Will return false if
 * there is no post data, either username or password is missing, or if the scope conditions have not been met.
 *
 * @param \Cake\Network\Request $request The request that contains login information.
 * @param \Cake\Network\Response $response Unused response object.
 * @return mixed False on login failure.  An array of User data on success.
 */
	public function authenticate(Request $request, Response $response) {
		list(, $model) = pluginSplit($this->config('userModel'));

		$fields = $this->config('fields');
		if (!$this->_checkFields($request, $model, $fields)) {
			return false;
		}
		return $this->_findUser(
			$request->data[$model][$fields['username']],
			$request->data[$model][$fields['password']]
		);
	}

}
