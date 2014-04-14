<?php
/**
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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component\Auth;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\InstanceConfigTrait;
use Cake\Error;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * Abstract base authorization adapter for AuthComponent.
 *
 * @see AuthComponent::$authenticate
 */
abstract class BaseAuthorize {

	use InstanceConfigTrait;

/**
 * Controller for the request.
 *
 * @var Controller
 */
	protected $_Controller = null;

/**
 * ComponentRegistry instance for getting more components.
 *
 * @var ComponentRegistry
 */
	protected $_registry;

/**
 * Default config for authorize objects.
 *
 * - `actionPath` - The path to ACO nodes that contains the nodes for controllers. Used as a prefix
 *    when calling $this->action();
 * - `actionMap` - Action -> crud mappings. Used by authorization objects that want to map actions to CRUD roles.
 * - `userModel` - Model name that ARO records can be found under. Defaults to 'User'.
 *
 * @var array
 */
	protected $_defaultConfig = [
		'actionPath' => null,
		'actionMap' => [
			'index' => 'read',
			'add' => 'create',
			'edit' => 'update',
			'view' => 'read',
			'delete' => 'delete',
			'remove' => 'delete'
		],
		'userModel' => 'Users'
	];

/**
 * Constructor
 *
 * @param ComponentRegistry $registry The controller for this request.
 * @param array $config An array of config. This class does not use any config.
 */
	public function __construct(ComponentRegistry $registry, array $config = array()) {
		$this->_registry = $registry;
		$controller = $registry->getController();
		$this->controller($controller);
		$this->config($config);
	}

/**
 * Checks user authorization.
 *
 * @param array $user Active user data
 * @param \Cake\Network\Request $request
 * @return bool
 */
	abstract public function authorize($user, Request $request);

/**
 * Accessor to the controller object.
 *
 * @param Controller $controller null to get, a controller to set.
 * @return mixed
 * @throws \Cake\Error\Exception
 */
	public function controller(Controller $controller = null) {
		if ($controller) {
			if (!$controller instanceof Controller) {
				throw new Error\Exception('$controller needs to be an instance of Controller');
			}
			$this->_Controller = $controller;
			return true;
		}
		return $this->_Controller;
	}

/**
 * Get the action path for a given request. Primarily used by authorize objects
 * that need to get information about the plugin, controller, and action being invoked.
 *
 * @param \Cake\Network\Request $request The request a path is needed for.
 * @param string $path
 * @return string the action path for the given request.
 */
	public function action(Request $request, $path = '/:plugin/:controller/:action') {
		$plugin = empty($request['plugin']) ? null : Inflector::camelize($request['plugin']) . '/';
		$path = str_replace(
			array(':controller', ':action', ':plugin/'),
			array(Inflector::camelize($request['controller']), $request['action'], $plugin),
			$this->_config['actionPath'] . $path
		);
		$path = str_replace('//', '/', $path);
		return trim($path, '/');
	}

/**
 * Maps crud actions to actual action names. Used to modify or get the current mapped actions.
 *
 * Create additional mappings for a standard CRUD operation:
 *
 * {{{
 * $this->Auth->mapActions(array('create' => array('add', 'register'));
 * }}}
 *
 * Or equivalently:
 *
 * {{{
 * $this->Auth->mapActions(array('register' => 'create', 'add' => 'create'));
 * }}}
 *
 * Create mappings for custom CRUD operations:
 *
 * {{{
 * $this->Auth->mapActions(array('range' => 'search'));
 * }}}
 *
 * You can use the custom CRUD operations to create additional generic permissions
 * that behave like CRUD operations. Doing this will require additional columns on the
 * permissions lookup. For example if one wanted an additional search CRUD operation
 * one would create and additional column '_search' in the aros_acos table. One could
 * create a custom admin CRUD operation for administration functions similarly if needed.
 *
 * @param array $map Either an array of mappings, or undefined to get current values.
 * @return mixed Either the current mappings or null when setting.
 * @see AuthComponent::mapActions()
 */
	public function mapActions(array $map = array()) {
		if (empty($map)) {
			return $this->_config['actionMap'];
		}
		foreach ($map as $action => $type) {
			if (is_array($type)) {
				foreach ($type as $typedAction) {
					$this->_config['actionMap'][$typedAction] = $action;
				}
			} else {
				$this->_config['actionMap'][$action] = $type;
			}
		}
	}

}
