<?php
/* SVN FILE: $Id$ */

/**
 * A custom view class that is used for themeing
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view
 * @since         CakePHP(tm) v 0.10.0.1076
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Theme view class
 *
 * @package       cake
 * @subpackage    cake.cake.libs.view
 */
class ThemeView extends View {
/**
 * Constructor for ThemeView sets $this->theme.
 *
 * @param Controller $controller
 */
	function __construct(&$controller) {
		parent::__construct($controller);
		$this->theme =& $controller->theme;

		if (!empty($this->theme)) {
			if (is_dir(WWW_ROOT . 'themed' . DS . $this->theme)) {
				$this->themeWeb = 'themed/'. $this->theme .'/';
			}
		}
	}

/**
 * Return all possible paths to find view files in order
 *
 * @param string $plugin
 * @return array paths
 * @access private
 */
	function _paths($plugin = null, $cached = true) {
		$paths = parent::_paths($plugin, $cached);

		if (!empty($this->theme)) {
			$count = count($paths);
			for ($i = 0; $i < $count; $i++) {
				$themePaths[] = $paths[$i] . 'themed'. DS . $this->theme . DS;
			}
			$paths = array_merge($themePaths, $paths);
		}

		if (empty($this->__paths)) {
			$this->__paths = $paths;
		}
		return $paths;
	}
}
?>