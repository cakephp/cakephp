<?php
/**
 * Helpers collection is used as a registry for loaded helpers and handles loading
 * and constructing helper class objects.
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
 * @package       Cake.View
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Core\App;
use Cake\Error;
use Cake\Event\EventManager;
use Cake\View\View;

/**
 * Helpers collection is used as a registry for loaded helpers and handles loading
 * and constructing helper class objects.
 *
 * @package       Cake.View
 */
class HelperCollection {

/**
 * Hash of already loaded helpers.
 *
 * @var array
 */
	protected $_loaded = [];

/**
 * View object to use when making helpers.
 *
 * @var View
 */
	protected $_View;


/**
 * EventManager instance.
 *
 * Helpers constructed by this object will be subscribed to this manager.
 *
 * @var Cake\Event\EventManager
 */
	protected $_eventManager;

/**
 * Constructor
 *
 * @param View $view
 */
	public function __construct(View $view) {
		$this->_View = $view;
		$this->_eventManager = $view->getEventManager();
	}

/**
 * Tries to lazy load a helper based on its name, if it cannot be found
 * in the application folder, then it tries looking under the current plugin
 * if any
 *
 * @param string $helper The helper name to be loaded
 * @return boolean whether the helper could be loaded or not
 * @throws MissingHelperException When a helper could not be found.
 *    App helpers are searched, and then plugin helpers.
 */
	public function __isset($helper) {
		if (isset($this->_loaded[$helper])) {
			return true;
		}

		try {
			$this->load($helper);
		} catch (Error\MissingHelperException $exception) {
			if ($this->_View->plugin) {
				$this->load($this->_View->plugin . '.' . $helper);
				return true;
			}
		}

		if (!empty($exception)) {
			throw $exception;
		}

		return true;
	}

/**
 * Provide public read access to the loaded objects
 *
 * @param string $name Name of property to read
 * @return mixed
 */
	public function __get($name) {
		if (isset($this->_loaded[$name])) {
			return $this->_loaded[$name];
		}
		if ($this->__isset($name)) {
			return $this->_loaded[$name];
		}
		return null;
	}

/**
 * Loads/constructs a helper. Will return the instance in the registry if it already exists.
 * By setting `$enable` to false you can disable callbacks for a helper. Alternatively you
 * can set `$settings['enabled'] = false` to disable callbacks. This alias is provided so that when
 * declaring $helpers arrays you can disable callbacks on helpers.
 *
 * You can alias your helper as an existing helper by setting the 'className' key, i.e.,
 * {{{
 * public $helpers = array(
 *   'Html' => array(
 *     'className' => '\App\View\Helper\AliasedHtmlHelper'
 *   );
 * );
 * }}}
 * All calls to the `Html` helper would use `AliasedHtml` instead.
 *
 * @param string $helper Helper name to load
 * @param array $settings Settings for the helper.
 * @return Helper A helper object, Either the existing loaded helper or a new one.
 * @throws Cake\Error\MissingHelperException when the helper could not be found
 */
	public function load($helper, $settings = array()) {
		list($plugin, $name) = pluginSplit($helper);
		if (isset($this->_loaded[$name])) {
			return $this->_loaded[$name];
		}
		if (is_array($settings) && isset($settings['className'])) {
			$helperClass = App::classname($settings['className'], 'View/Helper', 'Helper');
		}
		if (!isset($helperClass)) {
			$helperClass = App::classname($helper, 'View/Helper', 'Helper');
		}
		if (!$helperClass) {
			throw new Error\MissingHelperException(array(
				'class' => $helper,
				'plugin' => substr($plugin, 0, -1)
			));
		}
		$helperObject = new $helperClass($this->_View, $settings);

		$vars = array('request', 'theme', 'plugin');
		foreach ($vars as $var) {
			$helperObject->{$var} = $this->_View->{$var};
		}

		$this->_loaded[$name] = $helperObject;

		$enable = isset($settings['enabled']) ? $settings['enabled'] : true;
		if ($enable) {
			$this->_eventManager->attach($helperObject);
		}
		return $helperObject;
	}

/**
 * Get the loaded helpers list, or get the helper instance at a given name.
 *
 * @param null|string $name The helper name to get or null.
 * @return array|Helper Either a list of helper names, or a loaded helper.
 */
	public function loaded($name = null) {
		if (!empty($name)) {
			return isset($this->_loaded[$name]);
		}
		return array_keys($this->_loaded);
	}

}
