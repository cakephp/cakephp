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
 * Constructor
 *
 * @param ComponentRegistry $collection A ComponentRegistry for this component
 * @param array $config Array of config.
 */
	public function __construct(ComponentRegistry $collection, array $config = []) {
		parent::__construct($collection, $config);
		$this->_session = $collection->getController()->request->session();
	}

	public function set($message, $element = null, array $params = array(), $key = 'flash') {
		if ($message instanceof \Exception) {
			$message = $message->getMessage();
		}
		$this->_writeFlash($message, 'info', $params + compact('element', 'key'));
	}

	protected function _writeFlash($message, $type = 'info', $options = []) {
		$options += ['key' => 'flash'];
		$key = $options['key'];
		unset($options['key']);
		$this->_session->write("Message.$key", [
			'message' => $message,
			'type' => $type,
			'params' => $options
		]);
	}
}
