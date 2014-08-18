<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;

/**
 * The CakePHP SessionComponent provides a way to persist client data between
 * page requests. It acts as a wrapper for the `$_SESSION` as well as providing
 * convenience methods for several `$_SESSION` related functions.
 *
 * This class is here for backwards compatibility with CakePHP 2.x
 *
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/sessions.html
 * @link http://book.cakephp.org/2.0/en/development/sessions.html
 * @deprecated
 */
class SessionComponent extends Component {

/**
 * The Session object instance
 *
 * @var Cake\Network\Session
 */
	protected $_session;

/**
 * Constructor. Parses the accepted content types accepted by the client using HTTP_ACCEPT
 *
 * @param ComponentRegistry $collection ComponentRegistry object.
 * @param array $config Array of config.
 */
	public function __construct(ComponentRegistry $collection, array $config = array()) {
		parent::__construct($collection, $config);
		$this->_session = $collection->getController()->request->session();
	}

/**
 * Used to write a value to a session key.
 *
 * In your controller: $this->Session->write('Controller.sessKey', 'session value');
 *
 * @param string $name The name of the key your are setting in the session.
 *    This should be in a Controller.key format for better organizing
 * @param string $value The value you want to store in a session.
 * @return void
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/sessions.html#SessionComponent::write
 */
	public function write($name, $value = null) {
		$this->_session->write($name, $value);
	}

/**
 * Used to read a session values for a key or return values for all keys.
 *
 * In your controller: $this->Session->read('Controller.sessKey');
 * Calling the method without a param will return all session vars
 *
 * @param string $name the name of the session key you want to read
 * @return mixed value from the session vars
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/sessions.html#SessionComponent::read
 */
	public function read($name = null) {
		return $this->_session->read($name);
	}

/**
 * Wrapper for SessionComponent::del();
 *
 * In your controller: $this->Session->delete('Controller.sessKey');
 *
 * @param string $name the name of the session key you want to delete
 * @return void
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/sessions.html#SessionComponent::delete
 */
	public function delete($name) {
		$this->_session->delete($name);
	}

/**
 * Used to check if a session variable is set
 *
 * In your controller: $this->Session->check('Controller.sessKey');
 *
 * @param string $name the name of the session key you want to check
 * @return bool true is session variable is set, false if not
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/sessions.html#SessionComponent::check
 */
	public function check($name) {
		return $this->_session->check($name);
	}

/**
 * Used to set a session variable that can be used to output messages in the view.
 *
 * In your controller: $this->Session->setFlash('This has been saved');
 *
 * Additional params below can be passed to customize the output, or the Message.[key].
 * You can also set additional parameters when rendering flash messages. See SessionHelper::flash()
 * for more information on how to do that.
 *
 * @param string $message Message to be flashed
 * @param string $element Element to wrap flash message in.
 * @param array $params Parameters to be sent to layout as view variables
 * @param string $key Message key, default is 'flash'
 * @return void
 * @deprecated 3.0 Use FlashComponent::set() instead.
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/sessions.html#creating-notification-messages
 */
	public function setFlash($message, $element = null, array $params = array(), $key = 'flash') {
		$this->_session->write('Flash.' . $key, [
			'message' => $message,
			'key' => $key,
			'element' => $element,
			'params' => $params
		]);
	}

/**
 * Used to renew a session id
 *
 * In your controller: $this->Session->renew();
 *
 * @return void
 */
	public function renew() {
		$this->_session->renew();
	}

/**
 * Used to destroy sessions
 *
 * In your controller: $this->Session->destroy();
 *
 * @return void
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/sessions.html#SessionComponent::destroy
 */
	public function destroy() {
		$this->_session->destroy();
	}

/**
 * Get/Set the session id.
 *
 * When fetching the session id, the session will be started
 * if it has not already been started. When setting the session id,
 * the session will not be started.
 *
 * @param string $id Id to use (optional)
 * @return string The current session id.
 */
	public function id($id = null) {
		if ($id === null) {
			$session = $this->_session;
			$session->start();
			return $session->id();
		}
		$this->_session->id($id);
	}

/**
 * Returns a bool, whether or not the session has been started.
 *
 * @return bool
 */
	public function started() {
		return $this->_session->started();
	}

/**
 * Events supported by this component.
 *
 * @return array
 */
	public function implementedEvents() {
		return [];
	}

}
