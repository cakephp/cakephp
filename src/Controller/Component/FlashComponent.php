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
 * Default configuration
 *
 * @var array
 */
	protected $_defaultConfig = [
		'key' => 'flash',
		'element' => null,
		'class' => 'info',
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

	public function set($message, array $options = []) {
		$opts = array_merge($this->_defaultConfig, $options);

		if ($message instanceof \Exception) {
			$message = $message->getMessage();
		}

		$this->_session->write("Message.{$opts['key']}", [
			'message' => $message,
			'key' => $opts['key'],
			'element' => $opts['element'],
			'class' => $opts['class'],
			'params' => $opts['params']
		]);
	}
}
