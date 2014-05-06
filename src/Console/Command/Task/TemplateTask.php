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
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command\Task;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Utility\ConventionsTrait;
use Cake\Utility\Folder;
use Cake\View\ViewVarsTrait;

/**
 * Template Task can generate templated output Used in other Tasks.
 * Acts like a simplified View class.
 */
class TemplateTask extends Shell {

	use ConventionsTrait;
	use ViewVarsTrait;

/**
 * Paths to look for templates on.
 * Contains a list of $theme => $path
 *
 * @var array
 */
	public $templatePaths = [];

/**
 * Initialize callback. Setup paths for the template task.
 *
 * @return void
 */
	public function initialize() {
		$this->templatePaths = $this->_findThemes();
	}

/**
 * Find the paths to all the installed shell themes in the app.
 *
 * Bake themes are directories not named `skel` inside a `Console/Templates` path.
 * They are listed in this order: app -> plugin -> default
 *
 * @return array Array of bake themes that are installed.
 */
	protected function _findThemes() {
		$paths = App::path('Console');

		$plugins = App::objects('plugin');
		foreach ($plugins as $plugin) {
			$paths[] = $this->_pluginPath($plugin) . 'Console/';
		}

		$core = current(App::core('Console'));
		$Folder = new Folder($core . 'Templates/default');

		$contents = $Folder->read();
		$themeFolders = $contents[0];

		$paths[] = $core;

		foreach ($paths as $i => $path) {
			$paths[$i] = rtrim($path, DS) . DS;
		}

		$this->_io->verbose('Found the following bake themes:');

		$themes = [];
		foreach ($paths as $path) {
			$Folder = new Folder($path . 'Templates', false);
			$contents = $Folder->read();
			$subDirs = $contents[0];
			foreach ($subDirs as $dir) {
				$Folder = new Folder($path . 'Templates/' . $dir);
				$contents = $Folder->read();
				$subDirs = $contents[0];
				if (array_intersect($contents[0], $themeFolders)) {
					$templateDir = $path . 'Templates/' . $dir . DS;
					$themes[$dir] = $templateDir;

					$this->_io->verbose(sprintf("- %s -> %s", $dir, $templateDir));
				}
			}
		}
		return $themes;
	}

/**
 * Runs the template
 *
 * @param string $directory directory / type of thing you want
 * @param string $filename template name
 * @param array $vars Additional vars to set to template scope.
 * @return string contents of generated code template
 */
	public function generate($directory, $filename, $vars = null) {
		if ($vars !== null) {
			$this->set($vars);
		}
		if (empty($this->templatePaths)) {
			$this->initialize();
		}
		$themePath = $this->getThemePath();
		$templateFile = $this->_findTemplate($themePath, $directory, $filename);
		if ($templateFile) {
			extract($this->viewVars);
			ob_start();
			ob_implicit_flush(0);
			include $templateFile;
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
 * @throws \RuntimeException When the chosen theme cannot be found.
 */
	public function getThemePath() {
		if (empty($this->params['theme'])) {
			$this->params['theme'] = 'default';
		}
		if (!isset($this->templatePaths[$this->params['theme']])) {
			$msg = sprintf('Unable to locate "%s" bake theme templates.', $this->params['theme']);
			throw new \RuntimeException($msg);
		}
		$this->_io->verbose(sprintf('Using "%s" bake theme', $this->params['theme']));
		return $this->templatePaths[$this->params['theme']];
	}

/**
 * Find a template inside a directory inside a path.
 * Will scan all other theme dirs if the template is not found in the first directory.
 *
 * @param string $path The initial path to look for the file on. If it is not found fallbacks will be used.
 * @param string $directory Subdirectory to look for ie. 'views', 'objects'
 * @param string $filename lower_case_underscored filename you want.
 * @return string filename will exit program if template is not found.
 */
	protected function _findTemplate($path, $directory, $filename) {
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
		$this->err(__d('cake_console', 'Could not find template for %s', $filename));
		return false;
	}

}
