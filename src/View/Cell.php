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

use Cake\Core\App;
use Cake\Event\EventManager;
use Cake\Model\ModelAwareTrait;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Utility\Inflector;
use Cake\View\ViewVarsTrait;

/**
 * Cell base.
 *
 */
abstract class Cell {

	use ModelAwareTrait;
	use ViewVarsTrait;

/**
 * Instance of the View created during rendering. Won't be set until after
 * Cell::__toString() is called.
 *
 * @var \Cake\View\View
 */
	public $View;

/**
 * Name of the template that will be rendered.
 * This property is inflected from the action name that was invoked.
 *
 * @var string
 */
	public $template;

/**
 * Automatically set to the name of a plugin.
 *
 * @var string
 */
	public $plugin = null;

/**
 * An instance of a Cake\Network\Request object that contains information about the current request.
 * This object contains all the information about a request and several methods for reading
 * additional information about the request.
 *
 * @var \Cake\Network\Request
 */
	public $request;

/**
 * An instance of a Response object that contains information about the impending response
 *
 * @var \Cake\Network\Response
 */
	public $response;

/**
 * The name of the View class this cell sends output to.
 *
 * @var string
 */
	public $viewClass = 'Cake\View\View';

/**
 * The theme name that will be used to render.
 *
 * @var string
 */
	public $theme;

/**
 * Instance of the Cake\Event\EventManager this cell is using
 * to dispatch inner events.
 *
 * @var \Cake\Event\EventManager
 */
	protected $_eventManager = null;

/**
 * These properties can be set directly on Cell and passed to the View as options.
 *
 * @var array
 * @see \Cake\View\View
 */
	protected $_validViewOptions = [
		'viewVars', 'helpers', 'viewPath', 'plugin', 'theme'
	];

/**
 * List of valid options (constructor's fourth arguments)
 * Override this property in subclasses to whitelist
 * which options you want set as properties in your Cell.
 *
 * @var array
 */
	protected $_validCellOptions = [];

/**
 * Constructor.
 *
 * @param \Cake\Network\Request $request
 * @param \Cake\Network\Response $response
 * @param \Cake\Event\EventManager $eventManager
 * @param array $cellOptions
 */
	public function __construct(Request $request = null, Response $response = null,
			EventManager $eventManager = null, array $cellOptions = []) {
		$this->_eventManager = $eventManager;
		$this->request = $request;
		$this->response = $response;
		$this->modelFactory('Table', ['Cake\ORM\TableRegistry', 'get']);

		foreach ($this->_validCellOptions as $var) {
			if (isset($cellOptions[$var])) {
				$this->{$var} = $cellOptions[$var];
			}
		}
	}

/**
 * Render the cell.
 *
 * @param string $template Custom template name to render. If not provided (null), the last
 * value will be used. This value is automatically set by `CellTrait::cell()`.
 * @return void
 */
	public function render($template = null) {
		if ($template !== null) {
			$template = Inflector::underscore($template);
		}
		if (empty($template)) {
			$template = $this->template;
		}

		$this->View = $this->createView();

		$this->View->layout = false;
		$className = explode('\\', get_class($this));
		$className = array_pop($className);
		$this->View->subDir = 'Cell' . DS . substr($className, 0, strpos($className, 'Cell'));

		return $this->View->render($template);
	}

/**
 * Magic method.
 *
 * Starts the rendering process when Cell is echoed.
 *
 * @return string Rendered cell
 */
	public function __toString() {
		return $this->render();
	}

/**
 * Debug info.
 *
 * @return void
 */
	public function __debugInfo() {
		return [
			'plugin' => $this->plugin,
			'template' => $this->template,
			'viewClass' => $this->viewClass,
			'request' => $this->request,
			'response' => $this->response,
		];
	}

/**
 * Returns the Cake\Event\EventManager manager instance for this cell.
 *
 * You can use this instance to register any new listeners or callbacks to the
 * cell events, or create your own events and trigger them at will.
 *
 * @return \Cake\Event\EventManager
 */
	public function getEventManager() {
		if (empty($this->_eventManager)) {
			$this->_eventManager = new EventManager();
		}
		return $this->_eventManager;
	}

}
