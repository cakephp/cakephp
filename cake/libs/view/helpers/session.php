<?php
/**
 * Session Helper provides access to the Session in the Views.
 *
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
 * @package       cake.libs.view.helpers
 * @since         CakePHP(tm) v 1.1.7.3328
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
if (!class_exists('CakeSession')) {
	require LIBS . 'cake_session.php';
}
/**
 * Session Helper.
 *
 * Session reading from the view.
 *
 * @package       cake.libs.view.helpers
 * @link http://book.cakephp.org/view/1465/Session
 */
class SessionHelper extends AppHelper {

/**
 * Used to read a session values set in a controller for a key or return values for all keys.
 *
 * In your view: `$session->read('Controller.sessKey');`
 * Calling the method without a param will return all session vars
 *
 * @param string $name the name of the session key you want to read
 * @return values from the session vars
 * @link http://book.cakephp.org/view/1466/Methods
 */
	public function read($name = null) {
		return CakeSession::read($name);
	}

/**
 * Used to check is a session key has been set
 *
 * In your view: `$session->check('Controller.sessKey');`
 *
 * @param string $name
 * @return boolean
 * @link http://book.cakephp.org/view/1466/Methods
 */
	public function check($name) {
		return CakeSession::check($name);
	}

/**
 * Returns last error encountered in a session
 *
 * In your view: `$session->error();`
 *
 * @return string last error
 * @link http://book.cakephp.org/view/1466/Methods
 */
	public function error() {
		return CakeSession::error();
	}

/**
 * Used to render the message set in Controller::Session::setFlash()
 *
 * In your view: $session->flash('somekey');
 * Will default to flash if no param is passed
 *
 * @param string $key The [Message.]key you are rendering in the view.
 * @return boolean|string Will return the value if $key is set, or false if not set.
 * @access public
 * @link http://book.cakephp.org/view/1466/Methods
 * @link http://book.cakephp.org/view/1467/flash
 */
	public function flash($key = 'flash') {
		$out = false;
		
		if (CakeSession::check('Message.' . $key)) {
			$flash = CakeSession::read('Message.' . $key);

			if ($flash['element'] == 'default') {
				$class = 'message';
				if (!empty($flash['params']['class'])) {
					$class = $flash['params']['class'];
				}
				$out = '<div id="' . $key . 'Message" class="' . $class . '">' . $flash['message'] . '</div>';
			} elseif ($flash['element'] == '' || $flash['element'] == null) {
				$out = $flash['message'];
			} else {
				$tmpVars = $flash['params'];
				$tmpVars['message'] = $flash['message'];
				$out = $this->_View->element($flash['element'], $tmpVars);
			}
			CakeSession::delete('Message.' . $key);
		}
		return $out;
	}

/**
 * Used to check is a session is valid in a view
 *
 * @return boolean
 */
	public function valid() {
		return CakeSession::valid();
	}
}
