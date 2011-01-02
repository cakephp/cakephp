<?php
/**
 * SessionComponent.  Provides access to Sessions from the Controller layer
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
 * @package       cake.libs.controller.components
 * @since         CakePHP(tm) v 0.10.0.1232
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
if (!class_exists('cakesession')) {
	require LIBS . 'cake_session.php';
}

/**
 * Session Component.
 *
 * Session handling from the controller.
 *
 * @package       cake.libs.controller.components
 * @link http://book.cakephp.org/view/1310/Sessions
 *
 */
class SessionComponent extends Component {

/**
 * Get / Set the userAgent 
 *
 * @param string $userAgent Set the userAgent
 * @return void
 */
	public function userAgent($userAgent = null) {
		return CakeSession::userAgent($userAgent);
	}

/**
 * Used to write a value to a session key.
 *
 * In your controller: $this->Session->write('Controller.sessKey', 'session value');
 *
 * @param string $name The name of the key your are setting in the session.
 * 							This should be in a Controller.key format for better organizing
 * @param string $value The value you want to store in a session.
 * @return boolean Success
 * @link http://book.cakephp.org/view/1312/write
 */
	public function write($name, $value = null) {
		return CakeSession::write($name, $value);
	}

/**
 * Used to read a session values for a key or return values for all keys.
 *
 * In your controller: $this->Session->read('Controller.sessKey');
 * Calling the method without a param will return all session vars
 *
 * @param string $name the name of the session key you want to read
 * @return mixed value from the session vars
 * @link http://book.cakephp.org/view/1314/read
 */
	public function read($name = null) {
		return CakeSession::read($name);
	}

/**
 * Wrapper for SessionComponent::del();
 *
 * In your controller: $this->Session->delete('Controller.sessKey');
 *
 * @param string $name the name of the session key you want to delete
 * @return boolean true is session variable is set and can be deleted, false is variable was not set.
 * @link http://book.cakephp.org/view/1316/delete
 */
	public function delete($name) {
		return CakeSession::delete($name);
	}

/**
 * Used to check if a session variable is set
 *
 * In your controller: $this->Session->check('Controller.sessKey');
 *
 * @param string $name the name of the session key you want to check
 * @return boolean true is session variable is set, false if not
 * @link http://book.cakephp.org/view/1315/check
 */
	public function check($name) {
		return CakeSession::check($name);
	}

/**
 * Used to determine the last error in a session.
 *
 * In your controller: $this->Session->error();
 *
 * @return string Last session error
 * @link http://book.cakephp.org/view/1318/error
 */
	public function error() {
		return CakeSession::error();
	}

/**
 * Used to set a session variable that can be used to output messages in the view.
 *
 * In your controller: $this->Session->setFlash('This has been saved');
 *
 * Additional params below can be passed to customize the output, or the Message.[key]
 *
 * @param string $message Message to be flashed
 * @param string $element Element to wrap flash message in.
 * @param array $params Parameters to be sent to layout as view variables
 * @param string $key Message key, default is 'flash'
 * @link http://book.cakephp.org/view/1313/setFlash
 */
	public function setFlash($message, $element = 'default', $params = array(), $key = 'flash') {
		CakeSession::write('Message.' . $key, compact('message', 'element', 'params'));
	}

/**
 * Used to renew a session id
 *
 * In your controller: $this->Session->renew();
 *
 * @return void
 */
	public function renew() {
		return CakeSession::renew();
	}

/**
 * Used to check for a valid session.
 *
 * In your controller: $this->Session->valid();
 *
 * @return boolean true is session is valid, false is session is invalid
 */
	public function valid() {
		return CakeSession::valid();
	}

/**
 * Used to destroy sessions
 *
 * In your controller: $this->Session->destroy();
 *
 * @return void
 * @link http://book.cakephp.org/view/1317/destroy
 */
	public function destroy() {
		return CakeSession::destroy();
	}

/**
 * Returns Session id
 *
 * If $id is passed in a beforeFilter, the Session will be started
 * with the specified id
 *
 * @param $id string
 * @return string
 */
	public function id($id = null) {
		return CakeSession::id($id);
	}

/**
 * Returns a bool, whether or not the session has been started.
 *
 * @return boolean
 */
	public function started() {
		return CakeSession::started();
	}

}
