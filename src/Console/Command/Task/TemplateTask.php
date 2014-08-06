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
 * Contains a list of $template => $path
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
		$this->templatePaths = $this->_findTemplates();
	}

/**
 * Find the paths to all the installed shell templates in the app.
 *
 * Bake templates are directories under `Template/Bake` path.
 * They are listed in this order: app -> plugin -> default
 *
 * @return array Array of bake templates that are installed.
 */
	protected function _findTemplates() {
		$paths = App::path('Template');

		$plugins = App::objects('Plugin');
		foreach ($plugins as $plugin) {
			$paths[] = $this->_pluginPath($plugin) . 'src' . DS . 'Template' . DS;
		}

		$core = current(App::core('Template'));
		$Folder = new Folder($core . 'Bake' . DS . 'default');

		$contents = $Folder->read();
		$templateFolders = $contents[0];

		$paths[] = $core;

		foreach ($paths as $i => $path) {
			$paths[$i] = rtrim($path, DS) . DS;
		}

		$this->_io->verbose('Found the following bake templates:');

		$templates = [];
		foreach ($paths as $path) {
			$Folder = new Folder($path . 'Bake', false);
			$contents = $Folder->read();
			$subDirs = $contents[0];
			foreach ($subDirs as $dir) {
				$Folder = new Folder($path . 'Bake' . DS . $dir);
				$contents = $Folder->read();
				$subDirs = $contents[0];
				if (array_intersect($contents[0], $templateFolders)) {
					$templateDir = $path . 'Bake' . DS . $dir . DS;
					$templates[$dir] = $templateDir;

					$this->_io->verbose(sprintf("- %s -> %s", $dir, $templateDir));
				}
			}
		}
		return $templates;
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
		$templatePath = $this->getTemplatePath();
		$templateFile = $this->_findTemplate($templatePath, $directory, $filename);
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
 * Find the template name for the current operation.
 * If there is only one template in $templatePaths it will be used.
 * If there is a -template param in the cli args, it will be used.
 * If there is more than one installed template user interaction will happen
 *
 * @return string returns the path to the selected template.
 * @throws \RuntimeException When the chosen template cannot be found.
 */
	public function getTemplatePath() {
		if (empty($this->params['template'])) {
			$this->params['template'] = 'default';
		}
		if (!isset($this->templatePaths[$this->params['template']])) {
			$msg = sprintf('Unable to locate "%s" bake template', $this->params['template']);
			throw new \RuntimeException($msg);
		}
		$this->_io->verbose(sprintf('Using "%s" bake template', $this->params['template']));
		return $this->templatePaths[$this->params['template']];
	}

/**
 * Find a template inside a directory inside a path.
 * Will scan all other template dirs if the template is not found in the first directory.
 *
 * @param string $path The initial path to look for the file on. If it is not found fallbacks will be used.
 * @param string $directory Subdirectory to look for ie. 'views', 'objects'
 * @param string $filename lower_case_underscored filename you want.
 * @return string filename will exit program if template is not found.
 */
	protected function _findTemplate($path, $directory, $filename) {
		$templateFile = $path . $directory . DS . $filename . '.ctp';
		if (file_exists($templateFile)) {
			return $templateFile;
		}
		foreach ($this->templatePaths as $path) {
			$templatePath = $path . $directory . DS . $filename . '.ctp';
			if (file_exists($templatePath)) {
				return $templatePath;
			}
		}
		$this->err('Could not find template for %s', $filename);
		return false;
	}

}
