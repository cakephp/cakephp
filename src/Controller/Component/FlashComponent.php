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
use Cake\Network\Session;
use Cake\Utility\Hash;
use Cake\Utility\String;

/**
 * The CakePHP FlashComponent provides a way to easily set flash messages of any
 * type (i.e. notice, error, success, etc.).
 */
class FlashComponent extends Component {

/**
 * The controller.
 *
 * @var \Cake\Controller\Controller
 */
	protected $_controller;

/**
 * Default config
 *
 * - `element` - Element to wrap flash message in.
 * - `key` - Message key, default is 'flash'.
 * - `log` - Adds associated log entry. If true, uses the flash message. Default to false.
 * - `modelName` - Name of the model to use in the default CRUD templates, default is 'record'
 *      or `Controller::$modelName` if not empty.
 * - `redirect` - A string or array-based URL pointing to another location within the app,
 *     or an absolute URL. If true, the result of `Controller::referer()` is used. Default
 *     to false.
 * - `templates` - Re-usable message templates.
 * - `type` - The message type, default is 'notice'.
 *
 * @var array
 */
	protected $_defaultConfig = [
		'element' => 'default',
		'key' => 'flash',
		'log' => false,
		'modelName' => 'record',
		'redirect' => false,
		'templates' => [],
		'type' => 'notice',
	];

/**
 * Constructor
 *
 * @param ComponentRegistry $collection A ComponentRegistry for this component
 * @param array $config Array of config.
 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$this->_defaultConfig['templates'] = [
			'create' => [
				'success' => [
					'message' => __d('cake', 'Successfully created {{modelName}}.'),
					'redirect' => ['action' => 'index'],
				],
				'failure' => [
					'message' => __d('cake', 'There was a problem creating your {{modelName}}, fix the error(s) and try again.'),
					'type' => 'error',
				],
			],
			'read' => [
				'failure' => [
					'message' => __d('cake', 'Invalid {{modelName}}, please try again.'),
					'type' => 'error',
					'redirect' => true,
				],
			],
			'update' => [
				'success' => [
					'message' => __d('cake', 'Successfully updated {{modelName}}.'),
				],
				'failure' => [
					'message' => __d('cake', 'There was a problem updating your {{modelName}}, fix the error(s) and try again.'),
					'type' => 'error',
				]
			],
			'delete' => [
				'success' => [
					'message' => __d('cake', 'Successfully deleted {{modelName}}.'),
				],
				'failure' => [
					'message' => __d('cake', 'There was a problem deleting your {{modelName}}, please try again.'),
					'type' => 'error',
				]
			],
		];
		parent::__construct($registry, $config);
	}

/**
 * Startup callback.
 *
 * Sets controller to be used for redirects.
 *
 * @param \Cake\Event\Event $event
 * @return void
 */
	public function startup(Event $event) {
		$this->_controller = $event->subject();
		if (!empty($this->_controller->modelName)) {
			$this->_config['modelName'] = $this->_controller->modelName;
		}
	}

/**
 * Used to set a session message that can be used to output messages in the view.
 *
 * In your controller: $this->Flash->set('This has been saved');
 *
 * Additional params below can be passed to customize the output, or the Message.[key].
 * You can also set additional parameters when rendering flash messages. See SessionHelper::flash()
 * for more information on how to do that.
 *
 * @param string|Exception $message Message to be flashed or \Exception.
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

		if (Hash::check($this->_config, 'templates.' . $message)) {
			$template = Hash::extract($this->_config, 'templates.' . $message);

			if (!is_array($template)) {
				$template = ['message' => $template];
			}

			$options += $template;

			if (!empty($options['message'])) {
				$message = $options['message'];
				unset($options['message']);
			}
		}

		$params = $options + $this->_config;

		list($plugin, $element) = pluginSplit($params['element']);
		if ($plugin) {
			$params += compact('plugin');
		}

		$insertOpts = ['before' => '{{', 'after' => '}}', 'clean' => true];

		$message = String::insert($message, array_filter($params, 'is_string'), $insertOpts);

		$key = $params['key'];
		$redirect = $params['redirect'];

		if (!empty($params['log'])) {
			if (is_string($params['log'])) {
				$params['log'] = ['message' => String::insert($params['log'], $params, $insertOpts)];
			}

			if (is_callable($params['log'])) {
				call_user_func_array($params['log'], compact('message', 'params'));
			} else {
				$log = ['level' => $params['type'], 'message' => $message, 'scope' => []];
				$log = $params['log'] + $log;
				$this->_controller->log($log['message'], $log['level'], $log['scope']);
			}
		}

		unset(
			$params['element'],
			$params['key'],
			$params['log'],
			$params['modelName'],
			$params['redirect'],
			$params['templates']
		);

		Session::write('Message.' . $key, compact('message', 'element', 'params'));

		if (!empty($redirect)) {
			if (true === $redirect) {
				$redirect = $this->_controller->referer();
			}
			return $this->_controller->redirect($redirect);
		}
	}

/**
 * Magic method to create messages of different types.
 *
 * @param string $name Type to use.
 * @param array $args Parameters to pass when calling `FlashComponent::set`.
 * @return void
 * @throws \Cake\Error\InternalErrorException If missing the flash message.
 */
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
