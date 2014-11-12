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
namespace Cake\Shell\Task;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\ConventionsTrait;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\View\BakeView;
use Cake\View\Exception\MissingTemplateException;
use Cake\View\ViewVarsTrait;

/**
 * Template Task can generate templated output Used in other Tasks.
 * Acts like a simplified View class.
 */
class TemplateTask extends Shell {

	use ConventionsTrait;

	use ViewVarsTrait {
		getView as _getView;
	}

/**
 * BakeView instance
 *
 * @var Cake\View\BakeView
 */
	public $View;

/**
 * Which view class to use for baking
 *
 * @var string
 */
	public $viewClass = 'Cake\View\BakeView';

/**
 * Get view instance
 *
 * @param string $viewClass View class name or null to use $viewClass
 * @return \Cake\View\View
 * @throws \Cake\View\Exception\MissingViewException If view class was not found.
 */
	public function getView() {
		if ($this->View) {
			return $this->View;
		}

		$this->View = $this->_getView();
		$this->View->theme = isset($this->params['template']) ? $this->params['template'] : '';
		if ($this->View->theme === 'default') {
			$this->View->theme = '';
		}

		return $this->View;
	}

/**
 * Constructs the view class instance based on object properties.
 *
 * @param string $viewClass Optional namespaced class name of the View class to instantiate.
 * @return \Cake\View\View
 * @throws \Cake\View\Exception\MissingViewException If view class was not found.
 */
	public function createView($viewClass = null) {
		if ($viewClass === null) {
			$viewClass = $this->viewClass;
		}
		$className = App::className($viewClass, 'View');
		if (!$className) {
			throw new Exception\MissingViewException([$viewClass]);
		}
		return new $className(new Request(), new Response());
	}

/**
 * Runs the template
 *
 * @param string $template bake template to render
 * @param array $vars Additional vars to set to template scope.
 * @return string contents of generated code template
 */
	public function generate($template, $vars = null) {
		if ($vars !== null) {
			$this->set($vars);
		}

		$this->getView()->set($this->viewVars);

		try {
			return $this->View->render($template);
		} catch (MissingTemplateException $e) {
			$this->_io->verbose(sprintf('No bake template found for "%s"', $template));
			return '';
		}
	}

}
