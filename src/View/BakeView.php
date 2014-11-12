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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Core\ConventionsTrait;
use Cake\Utility\Inflector;

class BakeView extends View {

	use ConventionsTrait;

/**
 * An array of names of built-in helpers to include.
 *
 * @var array
 */
	public $helpers = [
		'Bake'
	];

/**
 * Renders view for given view file and layout.
 *
 * Render triggers helper callbacks, which are fired before and after the view are rendered,
 * as well as before and after the layout. The helper callbacks are called:
 *
 * - `beforeRender`
 * - `afterRender`
 *
 * View names can point to plugin views/layouts. Using the `Plugin.view` syntax
 * a plugin view/layout can be used instead of the app ones. If the chosen plugin is not found
 * the view will be located along the regular view path cascade.
 *
 * View can also be a template string, rather than the name of a view file
 *
 * @param string $view Name of view file to use, or a template string to render
 * @param string $layout Layout to use. Not used, for consistency with other views only
 * @return string|null Rendered content.
 * @throws \Cake\Core\Exception\Exception If there is an error in the view.
 */
	public function render($view = null, $layout = null) {
		$viewFileName = $this->_getViewFileName($view);

		$this->_currentType = static::TYPE_VIEW;
		$this->dispatchEvent('View.beforeRender', [$viewFileName]);
		$this->Blocks->set('content', $this->_render($viewFileName));
		$this->dispatchEvent('View.afterRender', [$viewFileName]);

		if ($layout === null) {
			$layout = $this->layout;
		}
		if ($layout && $this->autoLayout) {
			$this->Blocks->set('content', $this->renderLayout('', $layout));
		}

		return $this->Blocks->get('content');
	}

/**
 * Wrapper for creating and dispatching events.
 *
 * Use the Bake prefix for bake related view events
 *
 * @param string $name Name of the event.
 * @param array $data Any value you wish to be transported with this event to
 * it can be read by listeners.
 *
 * @param object $subject The object that this event applies to
 * ($this by default).
 *
 * @return \Cake\Event\Event
 */
	public function dispatchEvent($name, $data = null, $subject = null) {
		$name = str_replace('View.', 'Bake.', $name);
		return parent::dispatchEvent($name, $data, $subject);
	}

/**
 * Sandbox method to evaluate a template / view script in.
 *
 * @param string $viewFile Filename of the view
 * @param array $dataForView Data to include in rendered view.
 *    If empty the current View::$viewVars will be used.
 * @return string Rendered output
 */
	protected function _evaluate($viewFile, $dataForView) {
		$viewString = $this->_getViewFileContents($viewFile);

		$randomString = sha1($viewString);
		$unPhp = [
			'<?=' => "<$randomString=",
			'<?php' => "<$randomString",
			' ?>' => " $randomString>"
		];
		$templatify = [
			'<%=' => '<?=',
			'<%' => '<?php',
			'%>' => '?>'
		];

		$viewString = str_replace(array_keys($unPhp), array_values($unPhp), $viewString);
		$viewString = preg_replace('/\n[ \t]+<% /', "\n<% ", $viewString);
		$viewString = str_replace(array_keys($templatify), array_values($templatify), $viewString);
		$viewString = preg_replace('/<\?=(.*)\?>\n(.)/', "<?=$1?>\n\n$2", $viewString);

		$this->__viewFile = TMP . Inflector::slug(preg_replace('@.*Template[/\\\\]@', '', $viewFile)) . '.php';
		file_put_contents($this->__viewFile, $viewString);

		unset($randomString, $templatify, $viewFile, $viewString);
		extract($dataForView);
		ob_start();

		include $this->__viewFile;
		if (file_exists($this->__viewFile)) {
			unlink($this->__viewFile);
		}

		$content = ob_get_clean();

		return str_replace(array_values($unPhp), array_keys($unPhp), $content);
	}

/**
 * Returns filename of given template file (.ctp) as a string.
 * CamelCased template names will be under_scored! This means that you can have
 * LongTemplateNames that refer to long_template_names.ctp views.
 *
 * Also allows rendering a template string directly
 *
 * @param string $name Controller action to find template filename for
 * @return string Template filename or a Bake template string
 * @throws \Cake\View\Exception\MissingTemplateException when a view file could not be found.
 */
	protected function _getViewFileName($name = null) {
		if (strpos($name, '<') !== false) {
			return $name;
		}
		return parent::_getViewFileName($name);
	}

/**
 * Get the contents of the template file
 *
 * @param string $name A template name or a Bake template string
 * @return string Bake template to evaluate
 */
	protected function _getViewFileContents($name) {
		if (strpos($name, '<') !== false) {
			return $name;
		}

		$filename = $this->_getViewFileName($name);
		return file_get_contents($filename);
	}

/**
 * Return all possible paths to find view files in order
 *
 * @param string $plugin Optional plugin name to scan for view files.
 * @param bool $cached Set to false to force a refresh of view paths. Default true.
 * @return array paths
 */
	protected function _paths($plugin = null, $cached = true) {
		$paths = parent::_paths($plugin, false);
		foreach ($paths as &$path) {
			$path .= 'Bake' . DS;
		}
		return $paths;
	}
}
