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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Network\Session;
use Cake\View\Helper;
use Cake\View\View;

/**
 * FlashHelper class to render flash messages.
 *
 * After setting messsages in your controllers with FlashComponent, you can use
 * this class to output your flash messages in your views.
 */
class FlashHelper extends Helper {

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
 * Used to render the message set in FlashComponent::set()
 *
 * In your view: $this->Flash->render('somekey');
 * Will default to flash if no param is passed
 *
 * You can pass additional information into the flash message generation. This allows you
 * to consolidate all the parameters for a given type of flash message into the view.
 *
 * {{{
 * echo $this->Flash->render('flash', ['class' => 'new-flash']);
 * }}}
 *
 * The above would generate a flash message with a custom class name. Using $attrs['params'] you
 * can pass additional data into the element rendering that will be made available as local variables
 * when the element is rendered:
 *
 * {{{
 * echo $this->Flash->render('flash', ['params' => ['name' => $user['User']['name']]]);
 * }}}
 *
 * This would pass the current user's name into the flash message, so you could create personalized
 * messages without the controller needing access to that data.
 *
 * Lastly you can choose the element that is rendered when creating the flash message. Using
 * custom elements allows you to fully customize how flash messages are generated.
 *
 * {{{
 * echo $this->Flash->render('flash', [element' => 'my_custom_element']);
 * }}}
 *
 * If you want to use an element from a plugin for rendering your flash message
 * you can use the dot notation for the plugin's element name:
 *
 * {{{
 * echo $this->Flash->render('flash', [
 *   'element' => 'MyPlugin.my_custom_element',
 * ]);
 * }}}
 *
 * @param string $key The [Message.]key you are rendering in the view.
 * @param array $attrs Additional attributes to use for the creation of this flash message.
 *    Supports the 'params', and 'element' keys that are used in the helper.
 * @return string
 */
	public function render($key = 'flash', $attrs = []) {
		$flash = $this->request->session()->read("Message.$key");
		$this->request->session()->delete("Message.$key");

		if (!$flash) {
			return '';
		}

		$flash = array_merge($flash, $attrs);

		if ($flash['element'] === null) {
			return $this->formatTemplate('flash', [
				'key' => $key,
				'class' => $flash['class'],
				'message' => $flash['message']
			]);
		}

		return $this->_View->element($flash['element'], $flash);
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
