<?php
App::uses('Inflector', 'Utility');

if (!defined('CLASS_USER')) {
	define('CLASS_USER', 'User'); # override if you have it in a plugin: PluginName.User etc
}
if (!defined('AUTH_CACHE')) {
	define('AUTH_CACHE', '_cake_core_'); # use the most persistent cache by default
}
if (!defined('ACL_FILE')) {
	define('ACL_FILE', 'acl.ini'); # stored in /app/Config/
}

/**
 * Probably the most simple and fastest Acl out there.
 * Only one config file `acl.ini` necessary
 * Doesn't even need a Role Model / roles table
 * Uses most persistent _cake_core_ cache by default
 * @link http://www.dereuromark.de/2011/12/18/tinyauth-the-fastest-and-easiest-authorization-for-cake2
 * 
 * Usage:
 * Include it in your beforeFilter() method of the AppController
 * $this->Auth->authorize = array('Tools.Tiny');
 * 
 * Or with admin prefix protection only
 * $this->Auth->authorize = array('Tools.Tiny'=>array('allowUser'=>true));
 * 
 * @version 1.2 - now allows other parent model relations besides Role/role_id
 * @author Mark Scherer
 * @cakephp 2.0
 * @license MIT
 * 2012-01-09 ms
 */
class TinyAuthorize extends BaseAuthorize {

	protected $_matchArray = array();

	protected $_defaults = array(
		'allowUser' => false, # quick way to allow user access to non prefixed urls
		'adminPrefix' => 'admin_',
		'cache' => AUTH_CACHE,
		'cacheKey' => 'tiny_auth_acl',
		'autoClearCache' => false, # usually done by Cache automatically in debug mode,
		'aclModel' => 'Role', # only for multiple roles per user (HABTM)
		'aclKey' => 'role_id', # only for single roles per user (BT)
	);

	public function __construct(ComponentCollection $Collection, $settings = array()) {
		$settings = am($this->_defaults, $settings);
		parent::__construct($Collection, $settings);
		
		if (Cache::config($settings['cache']) === false) {
			throw new CakeException(__('TinyAuth could not find `%s` cache - expects at least a `default` cache', $settings['cache']));
		}
		$this->_matchArray = $this->_getRoles();
	}
	
	/**
	 * Authorize a user using the AclComponent.
	 * allows single or multi role based authorization
	 * 
	 * Examples:
	 * - User HABTM Roles (Role array in User array)
	 * - User belongsTo Roles (role_id in User array) 
	 *
	 * @param array $user The user to authorize
	 * @param CakeRequest $request The request needing authorization.
	 * @return boolean
	 */
	public function authorize($user, CakeRequest $request) {
		if (isset($user[$this->settings['aclModel']])) {
			$roles = (array)$user[$this->settings['aclModel']];
		} elseif (isset($user[$this->settings['aclKey']])) {
			$roles = array($user[$this->settings['aclKey']]);
		} else {
			$acl = $this->settings['aclModel'].'/'.$this->settings['aclKey'];
			trigger_error(__('Missing acl information (%s) in user session', $acl));
			$roles = array();
		}
		return $this->validate($roles, $request->params['plugin'], $request->params['controller'], $request->params['action']);
	}

	/**
	 * validate the url to the role(s)
	 * allows single or multi role based authorization
	 * @return bool $success
	 */
	public function validate($roles, $plugin, $controller, $action) {
		$action = Inflector::underscore($action);
		$controller = Inflector::underscore($controller);
		$plugin = Inflector::underscore($plugin);
		
		if (!empty($this->settings['allowUser'])) {
			# all user actions are accessable for logged in users
			if (mb_strpos($action, $this->settings['adminPrefix']) !== 0) {
				return true;
			}
		}
		
		if (isset($this->_matchArray[$controller]['*'])) {
			$matchArray = $this->_matchArray[$controller]['*'];
			if (in_array(-1, $matchArray)) {
				return true;
			}
			foreach ($roles as $role) {
				if (in_array($role, $matchArray)) {
					return true;
				}
			}
		}
		if (!empty($controller) && !empty($action)) {
			if (array_key_exists($controller, $this->_matchArray) && !empty($this->_matchArray[$controller][$action])) {
				$matchArray = $this->_matchArray[$controller][$action];

				# direct access? (even if he has no roles = GUEST)
				if (in_array(-1, $matchArray)) {
					return true;
				}

				# normal access (rolebased)
				foreach ($roles as $role) {
					if (in_array($role, $matchArray)) {
						return true;
					}
				}
			}
		}
		return false;
	}
	
	/**
	 * @return object $User: the User model 
	 */
	public function getModel() {
		return ClassRegistry::init(CLASS_USER); 
	}

	/**
	 * parse ini file and returns the allowed roles per action
	 * - uses cache for maximum performance
	 * improved speed by several actions before caching:
	 * - resolves role slugs to their primary key / identifier
	 * - resolves wildcards to their verbose translation
	 * @return array $roles
	 */	
	protected function _getRoles() {
		$res = array();
		if ($this->settings['autoClearCache'] && Configure::read('debug') > 0) {
			Cache::delete($this->settings['cacheKey'], $this->settings['cache']);
		}
		if (($roles = Cache::read($this->settings['cacheKey'], $this->settings['cache'])) !== false) {
			return $roles;
		}
		if (!file_exists(APP . 'Config' . DS . ACL_FILE)) {
			touch(APP . 'Config' . DS . ACL_FILE);
		}
		$iniArray = parse_ini_file(APP . 'Config' . DS . ACL_FILE, true);
		
		$availableRoles = Configure::read($this->settings['aclModel']);
		if (!is_array($availableRoles)) {
			$Model = $this->getModel();
			$availableRoles = $Model->{$this->settings['aclModel']}->find('list', array('fields'=>array('alias', 'id')));
			Configure::write($this->settings['aclModel'], $availableRoles);
		}
		if (!is_array($availableRoles) || !is_array($iniArray)) {
			trigger_error(__('Invalid Role Setup for TinyAuthorize (no roles found)'));
			return false;
		}
		
		foreach ($iniArray as $key => $array) {
			list($plugin, $controllerName) = pluginSplit($key);
			$controllerName = Inflector::underscore($controllerName);
			
			foreach ($array as $actions => $roles) {
				$actions = explode(',', $actions);
				$roles = explode(',', $roles);
				
				foreach ($roles as $key => $role) {
					if (!($role = trim($role))) {
						continue;
					}
					if ($role == '*') {
						unset($roles[$key]);
						$roles = array_merge($roles, array_keys(Configure::read($this->settings['aclModel'])));
					}
				}
				
				foreach ($actions as $action) {
					if (!($action = trim($action))) {
						continue;
					}
					$actionName = Inflector::underscore($action);
					
					foreach ($roles as $role) {
						if (!($role = trim($role)) || $role == '*') {
							continue;
						}
						$newRole = Configure::read($this->settings['aclModel'].'.'.strtolower($role));
						if (!empty($res[$controllerName][$actionName]) && in_array($newRole, $res[$controllerName][$actionName])) {
							continue;
						}
						$res[$controllerName][$actionName][] = $newRole;
					}
				}
			}
		}
		Cache::write($this->settings['cacheKey'], $res, $this->settings['cache']);
		return $res;
	}
		
}