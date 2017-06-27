<?php
/**
 * Flash Helper
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Helper
 * @since         CakePHP(tm) v 2.7.0-dev
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppHelper', 'View/Helper');
App::uses('CakeSession', 'Model/Datasource');

/**
 * FlashHelper class to render flash messages.
 *
 * After setting messages in your controllers with FlashComponent, you can use
 * this class to output your flash messages in your views.
 *
 * @package       Cake.View.Helper
 */
class FlashHelper extends AppHelper {

/**
 * Used to render the message set in FlashComponent::set()
 *
 * In your view: $this->Flash->render('somekey');
 * Will default to flash if no param is passed
 *
 * You can pass additional information into the flash message generation. This allows you
 * to consolidate all the parameters for a given type of flash message into the view.
 *
 * ```
 * echo $this->Flash->render('flash', array('params' => array('name' => $user['User']['name'])));
 * ```
 *
 * This would pass the current user's name into the flash message, so you could create personalized
 * messages without the controller needing access to that data.
 *
 * Lastly you can choose the element that is used for rendering the flash message. Using
 * custom elements allows you to fully customize how flash messages are generated.
 *
 * ```
 * echo $this->Flash->render('flash', array('element' => 'my_custom_element'));
 * ```
 *
 * If you want to use an element from a plugin for rendering your flash message
 * you can use the dot notation for the plugin's element name:
 *
 * ```
 * echo $this->Flash->render('flash', array(
 *   'element' => 'MyPlugin.my_custom_element',
 * ));
 * ```
 *
 * @param string $key The [Message.]key you are rendering in the view.
 * @param array $options Additional options to use for the creation of this flash message.
 *    Supports the 'params', and 'element' keys that are used in the helper.
 * @return string|null Rendered flash message or null if flash key does not exist
 *   in session.
 * @throws UnexpectedValueException If value for flash settings key is not an array.
 */
	public function render($key = 'flash', $options = array()) {
		if (!CakeSession::check("Message.$key")) {
			return null;
		}

		$flash = CakeSession::read("Message.$key");

		if (!is_array($flash)) {
			throw new UnexpectedValueException(sprintf(
				'Value for flash setting key "%s" must be an array.',
				$key
			));
		}

		CakeSession::delete("Message.$key");

		$out = '';
		foreach ($flash as $message) {
			$message['key'] = $key;
			$message = $options + $message;
			if ($message['element'] === 'default') {
				$message['element'] = 'Flash/default';
			}
			$out .= $this->_View->element($message['element'], $message);
		}

		return $out;
	}
}
