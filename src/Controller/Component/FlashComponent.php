<?php
/**
 * FlashComponent. Handles session flash messages.
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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component;

use Cake\Controller\Component;
use Cake\Error\InternalErrorException;
use Cake\Network\Session;

/**
 * The CakePHP FlashComponent provides a way to easily set flash messages of any
 * type (i.e. notice, error, success, etc.).
 */
class FlashComponent extends Component {

/**
 * Default config
 *
 * - `element` - Element to wrap flash message in.
 * - `key` - Message key, default is 'flash'.
 * - `type` - The message type, default is 'notice'.
 *
 * @var array
 */
	protected $_defaultConfig = [
		'element' => 'default',
		'key' => 'flash',
		'type' => 'notice',
	];

/**
 * Used to set a session message that can be used to output messages in the view.
 *
 * In your controller: $this->Flash->set('This has been saved');
 *
 * Additional params below can be passed to customize the output, or the Message.[key].
 * You can also set additional parameters when rendering flash messages. See SessionHelper::flash()
 * for more information on how to do that.
 *
 * @param string $message Message to be flashed
 * @param string|array $options
 * @return void
 */
	public function set($message, $options = []) {
		if (is_string($options)) {
			$options = ['type' => $options];
		}

		if ($message instanceof \Exception) {
			$options += ['type' => 'error'];
			$message = $message->getMessage();
		}

		$params = $options + $this->_config;

		list($plugin, $element) = pluginSplit($params['element']);
		if ($plugin) {
			$params += compact('plugin');
		}

		$key = $params['key'];

		unset(
			$params['element'],
			$params['key']
		);

		Session::write('Message.' . $key, compact('message', 'element', 'params'));
	}

	public function __call($name, $args) {
		$options = ['type' => $name];

		if (count($args) < 1) {
			throw new InternalErrorException('Flash message missing.');
		}

		if (!empty($args[1])) {
			$options += (array)$args[1];
		}

		$this->set($args[0], $options);
	}

}
