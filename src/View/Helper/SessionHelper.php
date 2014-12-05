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
 * @since         1.1.7
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\View\Helper;
use Cake\View\Helper\StringTemplateTrait;

/**
 * Session Helper.
 *
 * Session reading from the view.
 *
 * @link http://book.cakephp.org/3.0/en/views/helpers/session.html
 */
class SessionHelper extends Helper {

	use StringTemplateTrait;

/**
 * Default config for this class
 *
 * @var array
 */
	protected $_defaultConfig = [
		'templates' => [
			'flash' => '<div id="{{key}}-message" class="message-{{class}}">{{message}}</div>'
		]
	];

/**
 * Used to read a session values set in a controller for a key or return values for all keys.
 *
 * In your view: `$this->Session->read('Controller.sessKey');`
 * Calling the method without a param will return all session vars
 *
 * @param string|null $name the name of the session key you want to read
 * @return mixed values from the session vars
 */
	public function read($name = null) {
		return $this->request->session()->read($name);
	}

/**
 * Used to check is a session key has been set
 *
 * In your view: `$this->Session->check('Controller.sessKey');`
 *
 * @param string $name Session key to check.
 * @return bool
 */
	public function check($name) {
		return $this->request->session()->check($name);
	}

/**
 * Used to render the message set in Controller::$this->request->session()->setFlash()
 *
 * In your view: $this->Session->flash('somekey');
 * Will default to flash if no param is passed
 *
 * You can pass additional information into the flash message generation. This allows you
 * to consolidate all the parameters for a given type of flash message into the view.
 *
 * {{{
 * echo $this->Session->flash('flash', array('params' => array('class' => 'new-flash')));
 * }}}
 *
 * The above would generate a flash message with a custom class name. Using $attrs['params'] you
 * can pass additional data into the element rendering that will be made available as local variables
 * when the element is rendered:
 *
 * {{{
 * echo $this->Session->flash('flash', array('params' => array('name' => $user['User']['name'])));
 * }}}
 *
 * This would pass the current user's name into the flash message, so you could create personalized
 * messages without the controller needing access to that data.
 *
 * Lastly you can choose the element that is rendered when creating the flash message. Using
 * custom elements allows you to fully customize how flash messages are generated.
 *
 * {{{
 * echo $this->Session->flash('flash', array('element' => 'my_custom_element'));
 * }}}
 *
 * If you want to use an element from a plugin for rendering your flash message you can do that using the
 * plugin param:
 *
 * {{{
 * echo $this->Session->flash('flash', array(
 *   'element' => 'my_custom_element',
 *   'params' => array('plugin' => 'my_plugin')
 * ));
 * }}}
 *
 * @param string $key The [Message.]key you are rendering in the view.
 * @param array $attrs Additional attributes to use for the creation of this flash message.
 *    Supports the 'params', and 'element' keys that are used in the helper.
 * @return string
 * @deprecated 3.0 Use FlashHelper::render() instead.
 */
	public function flash($key = 'flash', $attrs = []) {
		$flash = $this->request->session()->read('Flash.' . $key);

		if (!$flash) {
			return '';
		}

		$this->request->session()->delete('Flash.' . $key);

		if (!empty($attrs)) {
			$flash = $attrs + $flash;
		}
		$flash += ['message' => '', 'type' => 'info', 'params' => []];

		$message = $flash['message'];
		$class = $flash['type'];
		$params = $flash['params'];

		if (isset($flash['element'])) {
			$params['element'] = $flash['element'];
		}

		if (empty($params['element'])) {
			if (!empty($flash['class'])) {
				$class = $flash['class'];
			}
			return $this->formatTemplate('flash', [
				'class' => $class,
				'key' => $key,
				'message' => $message
			]);
		}

		$params['message'] = $message;
		$params['type'] = $class;
		return $this->_View->element($params['element'], $params);
	}

/**
 * Event listeners.
 *
 * @return array
 */
	public function implementedEvents() {
		return [];
	}

}
