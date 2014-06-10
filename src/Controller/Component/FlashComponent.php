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
namespace Cake\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Utility\Inflector;
use Cake\Error\InternalErrorException;
use Cake\Event\Event;


/**
 * FlashComponent
 */
class FlashComponent extends Component {

/**
 * The session
 *
 * @var \Cake\Network\Session
 */
	protected $_session;

/**
 * Default configuration
 *
 * @var array
 */
	protected $_defaultConfig = [
		'key' => 'flash',
		'element' => null,
		'params' => []
	];

/**
 * Constructor
 *
 * @param ComponentRegistry $collection A ComponentRegistry for this component
 * @param array $config Array of config.
 */
	public function __construct(ComponentRegistry $collection, array $config = []) {
		parent::__construct($collection, $config);
		$this->_session = $collection->getController()->request->session();
	}

/**
 * Used to set a session variable that can be used to output messages in the view.
 *
 * In your controller: $this->Flash->set('This has been saved');
 *
 * ### Options:
 *
 * - `key` The key to set under the session's Message key
 * - `element` The element used to render the flash message
 * - `escape` Whether or not to escape the flash message, defaults to true
 * - `params` An array of variables to make available when using an element
 *
 * @param string $message Message to be flashed
 * @param array $options An array of options
 * @return void
 */
	public function set($message, array $options = []) {
		$opts = array_merge($this->_defaultConfig, $options);

		if ($message instanceof \Exception) {
			$message = $message->getMessage();
		}

		$this->_session->write("Message.{$opts['key']}", [
			'message' => $message,
			'key' => $opts['key'],
			'element' => $opts['element'],
			'params' => $opts['params']
		]);
	}

/**
 * Magic method for verbose flash methods based on element names.
 *
 * @param string $name Element name to use, omitting the "flash_" prefix.
 * @param array $args Parameters to pass when calling `FlashComponent::set`.
 * @return void
 * @throws \Cake\Error\InternalErrorException If missing the flash message.
*/
	public function __call($name, $args) {
		$options = ['element' => 'flash_' . Inflector::underscore($name)];

		if (count($args) < 1) {
			throw new InternalErrorException('Flash message missing.');
		}

		if (!empty($args[1])) {
			$options += (array)$args[1];
		}

		$this->set($args[0], $options);
	}
}
