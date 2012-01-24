<?php
/**
 * A custom view class that is used for themeing
 *
 * PHP 5
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
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('View', 'View');

/**
 * Theme view class
 *
 * Allows the creation of multiple themes to be used in an app. Theme views are regular view files
 * that can provide unique HTML and static assets.  If theme views are not found for the current view
 * the default app view files will be used. You can set `$this->theme` and `$this->viewClass = 'Theme'`
 * in your Controller to use the ThemeView.
 *
 * Example of theme path with `$this->theme = 'SuperHot';` Would be `app/View/Themed/SuperHot/Posts`
 *
 * @package       Cake.View
 */
class ThemeView extends View {
/**
 * Constructor for ThemeView sets $this->theme.
 *
 * @param Controller $controller Controller object to be rendered.
 */
	public function __construct($controller) {
		parent::__construct($controller);
		if ($controller) {
			$this->theme = $controller->theme;
		}
	}

/**
 * Return all possible paths to find view files in order
 *
 * @param string $plugin The name of the plugin views are being found for.
 * @param boolean $cached Set to true to force dir scan.
 * @return array paths
 * @todo Make theme path building respect $cached parameter.
 */
	protected function _paths($plugin = null, $cached = true) {
		$paths = parent::_paths($plugin, $cached);
		$themePaths = array();

		if (!empty($this->theme)) {
			foreach ($paths as $path) {
				if (strpos($path, DS . 'Plugin' . DS) === false
					&& strpos($path, DS . 'Cake' . DS . 'View') === false) {
						if ($plugin) {
							$themePaths[] = $path . 'Themed'. DS . $this->theme . DS . 'Plugin' . DS . $plugin . DS;
						}
						$themePaths[] = $path . 'Themed'. DS . $this->theme . DS;
					}
			}
			$paths = array_merge($themePaths, $paths);
		}
		return $paths;
	}
}
