<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 2.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DebugTimer', 'DebugKit.Lib');
App::uses('DebugMemory', 'DebugKit.Lib');
App::uses('Helper', 'View');

/**
 * Class DebugTimerHelper
 *
 * Tracks time and memory usage while rendering view.
 *
 */
class DebugTimerHelper extends Helper {

/**
 * Set to true when rendering is complete.
 * Used to not add timers for rendering the toolbar.
 *
 * @var boolean
 */
	protected $_renderComplete = false;

/**
 * Constructor
 *
 * @param View $View
 * @param array $settings
 */
	public function __construct(View $View, $settings = array()) {
		parent::__construct($View, $settings);
		DebugTimer::start(
			'viewRender',
			__d('debug_kit', 'Rendering View')
		);
	}

/**
 * Sets a timer point before rendering a file.
 *
 * @param string $viewFile The view being rendered
 */
	public function beforeRenderFile($viewFile) {
		if ($this->_renderComplete) {
			return;
		}
		DebugTimer::start(
			'render_' . basename($viewFile),
			__d('debug_kit', 'Rendering %s',
			Debugger::trimPath($viewFile))
		);
	}

/**
 * Stops the timer point before rendering a file.
 *
 * @param string $viewFile The view being rendered
 * @param string $content The contents of the view.
 */
	public function afterRenderFile($viewFile, $content) {
		if ($this->_renderComplete) {
			return;
		}
		DebugTimer::stop('render_' . basename($viewFile));
	}

/**
 * Stop timers for rendering.
 *
 * @param string $layoutFile
 */
	public function afterLayout($layoutFile) {
		DebugTimer::stop('viewRender');
		DebugTimer::stop('controllerRender');
		DebugMemory::record(__d('debug_kit', 'View render complete'));
		$this->_renderComplete = true;
	}

}
