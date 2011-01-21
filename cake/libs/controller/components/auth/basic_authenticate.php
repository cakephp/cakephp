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
App::import('Component', 'auth/base_authenticate');

class BasicAuthenticate extends BaseAuthenticate {
/**
 * Authenticate a user using basic HTTP auth.  Will use the configured User model and attempt a 
 * login using basic HTTP auth.
 *
 * @return mixed Either false on failure, or an array of user data on success.
 */
	public function authenticate(CakeRequest $request) {
		
	}
}