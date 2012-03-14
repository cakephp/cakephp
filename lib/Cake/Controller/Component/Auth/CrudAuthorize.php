<?php
/**
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('BaseAuthorize', 'Controller/Component/Auth');
App::uses('Router', 'Routing');

/**
 * An authorization adapter for AuthComponent.  Provides the ability to authorize using CRUD mappings.
 * CRUD mappings allow you to translate controller actions into *C*reate *R*ead *U*pdate *D*elete actions.
 * This is then checked in the AclComponent as specific permissions.
 *
 * For example, taking `/posts/index` as the current request.  The default mapping for `index`, is a `read` permission
 * check. The Acl check would then be for the `posts` controller with the `read` permission.  This allows you
 * to create permission systems that focus more on what is being done to resources, rather than the specific actions
 * being visited.
 *
 * @package       Cake.Controller.Component.Auth
 * @since 2.0
 * @see AuthComponent::$authenticate
 * @see AclComponent::check()
 */
class CrudAuthorize extends BaseAuthorize {

/**
 * Sets up additional actionMap values that match the configured `Routing.prefixes`.
 *
 * @param ComponentCollection $collection The component collection from the controller.
 * @param string $settings An array of settings.  This class does not use any settings.
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
		$this->_setPrefixMappings();
	}

/**
 * sets the crud mappings for prefix routes.
 *
 * @return void
 */
	protected function _setPrefixMappings() {
		$crud = array('create', 'read', 'update', 'delete');
		$map = array_combine($crud, $crud);

		$prefixes = Router::prefixes();
		if (!empty($prefixes)) {
			foreach ($prefixes as $prefix) {
				$map = array_merge($map, array(
					$prefix . '_index' => 'read',
					$prefix . '_add' => 'create',
					$prefix . '_edit' => 'update',
					$prefix . '_view' => 'read',
					$prefix . '_remove' => 'delete',
					$prefix . '_create' => 'create',
					$prefix . '_read' => 'read',
					$prefix . '_update' => 'update',
					$prefix . '_delete' => 'delete'
				));
			}
		}
		$this->mapActions($map);
	}

/**
 * Authorize a user using the mapped actions and the AclComponent.
 *
 * @param array $user The user to authorize
 * @param CakeRequest $request The request needing authorization.
 * @return boolean
 */
	public function authorize($user, CakeRequest $request) {
		if (!isset($this->settings['actionMap'][$request->params['action']])) {
			trigger_error(__d('cake_dev',
				'CrudAuthorize::authorize() - Attempted access of un-mapped action "%1$s" in controller "%2$s"',
				$request->action,
				$request->controller
				),
				E_USER_WARNING
			);
			return false;
		}
		$user = array($this->settings['userModel'] => $user);
		$Acl = $this->_Collection->load('Acl');
		return $Acl->check(
			$user,
			$this->action($request, ':controller'),
			$this->settings['actionMap'][$request->params['action']]
		);
	}

}
