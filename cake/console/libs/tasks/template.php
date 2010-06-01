<?php
/**
 * Template Task can generate templated output Used in other Tasks
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.console.libs.tasks
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TemplateTask extends Shell {

/**
 * variables to add to template scope
 *
 * @var array
 */
	var $templateVars = array();

/**
 * Paths to look for templates on.
 * Contains a list of $theme => $path
 *
 * @var array
 */
	var $templatePaths = array();

/**
 * Initialize callback.  Setup paths for the template task.
 *
 * @access public
 * @return void
 */
	function initialize() {
		$this->templatePaths = $this->_findThemes();
	}

/**
 * Find the paths to all the installed shell themes in the app.
 *
 * Bake themes are directories not named `skel` inside a `vendors/shells/templates` path.
 *
 * @return array Array of bake themes that are installed.
 */
	function _findThemes() {
		$paths = App::path('shells');
		$core = array_pop($paths);
		$separator = DS === '/' ? '/' : '\\\\';
		$core = preg_replace('#libs' . $separator . '$#', '', $core);
		$paths[] = $core;
		$Folder =& new Folder($core . 'templates' . DS . 'default');
		$contents = $Folder->read();
		$themeFolders = $contents[0];

		$plugins = App::objects('plugin');
		foreach ($plugins as $plugin) {
			$paths[] = $this->_pluginPath($plugin) . 'vendors' . DS . 'shells' . DS;
		}

		// TEMPORARY TODO remove when all paths are DS terminated
		foreach ($paths as $i => $path) {
			$paths[$i] = rtrim($path, DS) . DS;
		}

		$themes = array();
		foreach ($paths as $path) {
			$Folder =& new Folder($path . 'templates', false);
			$contents = $Folder->read();
			$subDirs = $contents[0];
			foreach ($subDirs as $dir) {
				if (empty($dir) || preg_match('@^skel$|_skel$@', $dir)) {
					continue;
				}
				$Folder =& new Folder($path . 'templates' . DS . $dir);
				$contents = $Folder->read();
				$subDirs = $contents[0];
				if (array_intersect($contents[0], $themeFolders)) {
					$templateDir = $path . 'templates' . DS . $dir . DS;
					$themes[$dir] = $templateDir;
				}
			}
		}
		return $themes;
	}

/**
 * Set variable values to the template scope
 *
 * @param mixed $one A string or an array of data.
 * @param mixed $two Value in case $one is a string (which then works as the key).
 *   Unused if $one is an associative array, otherwise serves as the values to $one's keys.
 * @return void
 */
	function set($one, $two = null) {
		$data = null;
		if (is_array($one)) {
			if (is_array($two)) {
				$data = array_combine($one, $two);
			} else {
				$data = $one;
			}
		} else {
			$data = array($one => $two);
		}

		if ($data == null) {
			return false;
		}

		foreach ($data as $name => $value) {
			$this->templateVars[$name] = $value;
		}
	}

/**
 * Runs the template
 *
 * @param string $directory directory / type of thing you want
 * @param string $filename template name
 * @param string $vars Additional vars to set to template scope.
 * @access public
 * @return contents of generated code template
 */
	function generate($directory, $filename, $vars = null) {
		if ($vars !== null) {
			$this->set($vars);
		}
		if (empty($this->templatePaths)) {
			$this->initialize();
		}
		$themePath = $this->getThemePath();
		$templateFile = $this->_findTemplate($themePath, $directory, $filename);
		if ($templateFile) {
			extract($this->templateVars);
			ob_start();
			ob_implicit_flush(0);
			include($templateFile);
			$content = ob_get_clean();
			return $content;
		}
		return '';
	}

/**
 * Find the theme name for the current operation.
 * If there is only one theme in $templatePaths it will be used.
 * If there is a -theme param in the cli args, it will be used.
 * If there is more than one installed theme user interaction will happen
 *
 * @return string returns the path to the selected theme.
 */
	function getThemePath() {
		if (count($this->templatePaths) == 1) {
			$paths = array_values($this->templatePaths);
			return $paths[0];
		}
		if (!empty($this->params['theme']) && isset($this->templatePaths[$this->params['theme']])) {
			return $this->templatePaths[$this->params['theme']];
		}

		$this->hr();
		$this->out(__('You have more than one set of templates installed.', true));
		$this->out(__('Please choose the template set you wish to use:', true));
		$this->hr();

		$i = 1;
		$indexedPaths = array();
		foreach ($this->templatePaths as $key => $path) {
			$this->out($i . '. ' . $key);
			$indexedPaths[$i] = $path;
			$i++;
		}
		$index = $this->in(__('Which bake theme would you like to use?', true), range(1, $i - 1), 1);
		$themeNames = array_keys($this->templatePaths);
		$this->Dispatch->params['theme'] = $themeNames[$index - 1];
		return $indexedPaths[$index];
	}

/**
 * Find a template inside a directory inside a path.
 * Will scan all other theme dirs if the template is not found in the first directory.
 *
 * @param string $path The initial path to look for the file on. If it is not found fallbacks will be used.
 * @param string $directory Subdirectory to look for ie. 'views', 'objects'
 * @param string $filename lower_case_underscored filename you want.
 * @access public
 * @return string filename will exit program if template is not found.
 */
	function _findTemplate($path, $directory, $filename) {
		$themeFile = $path . $directory . DS . $filename . '.ctp';
		if (file_exists($themeFile)) {
			return $themeFile;
		}
		foreach ($this->templatePaths as $path) {
			$templatePath = $path . $directory . DS . $filename . '.ctp';
			if (file_exists($templatePath)) {
				return $templatePath;
			}
		}
		$this->err(sprintf(__('Could not find template for %s', true), $filename));
		return false;
	}
}
