<?php
/**
 * Helpers collection is used as a registry for loaded helpers and handles loading
 * and constructing helper class objects.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ObjectCollection', 'Utility');

class HelperCollection extends ObjectCollection {

/**
 * View object to use when making helpers.
 *
 * @var View
 */
	protected $_View;

/**
 * Constructor
 *
 * @param View $view
 */
	public function __construct(View $view) {
		$this->_View = $view;
	}

/**
 * Loads/constructs a helper.  Will return the instance in the registry if it already exists.
 * By setting `$enable` to false you can disable callbacks for a helper.  Alternatively you
 * can set `$settings['enabled'] = false` to disable callbacks.  This alias is provided so that when
 * declaring $helpers arrays you can disable callbacks on helpers.
 *
 * You can alias your helper as an existing helper by setting the 'className' key, i.e.,
 * {{{
 * public $helpers = array(
 *   'Html' => array(
 *     'className' => 'AliasedHtml'
 *   );
 * );
 * }}}
 * All calls to the `Html` helper would use `AliasedHtml` instead.
 *
 * @param string $helper Helper name to load
 * @param array $settings Settings for the helper.
 * @return Helper A helper object, Either the existing loaded helper or a new one.
 * @throws MissingHelperException when the helper could not be found
 */
	public function load($helper, $settings = array()) {
		if (is_array($settings) && isset($settings['className'])) {
			$alias = $helper;
			$helper = $settings['className'];
		}
		list($plugin, $name) = pluginSplit($helper, true);
		if (!isset($alias)) {
			$alias = $name;
		}

		if (isset($this->_loaded[$alias])) {
			return $this->_loaded[$alias];
		}
		$helperClass = $name . 'Helper';
		App::uses($helperClass, $plugin . 'View/Helper');
		if (!class_exists($helperClass)) {
			throw new MissingHelperException(array(
				'class' => $helperClass,
				'plugin' => substr($plugin, 0, -1)
			));
		}
		$this->_loaded[$alias] = new $helperClass($this->_View, $settings);

		$vars = array('request', 'theme', 'plugin');
		foreach ($vars as $var) {
			$this->_loaded[$alias]->{$var} = $this->_View->{$var};
		}
		$enable = isset($settings['enabled']) ? $settings['enabled'] : true;
		if ($enable === true) {
			$this->_enabled[] = $alias;
		}
		return $this->_loaded[$alias];
	}

}