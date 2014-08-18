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

use Cake\Event\EventManager;
use Cake\Event\EventManagerTrait;
use Cake\Model\ModelAwareTrait;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Utility\Inflector;
use Cake\View\Error\MissingCellViewException;
use Cake\View\Error\MissingViewException;
use Cake\View\ViewVarsTrait;

/**
 * Cell base.
 *
 */
abstract class Cell {

	use EventManagerTrait;
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
 * @param \Cake\Network\Request $request the request to use in the cell
 * @param \Cake\Network\Response $response the response to use in the cell
 * @param \Cake\Event\EventManager $eventManager then eventManager to bind events to
 * @param array $cellOptions cell options to apply
 */
	public function __construct(Request $request = null, Response $response = null,
			EventManager $eventManager = null, array $cellOptions = []) {
		$this->eventManager($eventManager);
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
 * @throws \Cake\View\Error\MissingCellViewException When a MissingViewException is raised during rendering.
 */
	public function render($template = null) {
		if ($template !== null && strpos($template, '/') === false) {
			$template = Inflector::underscore($template);
		}
		if ($template === null) {
			$template = $this->template;
		}

		$this->View = $this->createView();

		$this->View->layout = false;
		$className = explode('\\', get_class($this));
		$className = array_pop($className);
		$name = substr($className, 0, strpos($className, 'Cell'));
		$this->View->subDir = 'Cell' . DS . $name;

		try {
			return $this->View->render($template);
		} catch (MissingViewException $e) {
			throw new MissingCellViewException(['file' => $template, 'name' => $name]);
		}
	}

/**
 * Magic method.
 *
 * Starts the rendering process when Cell is echoed.
 *
 * *Note* This method will trigger an error when view rendering has a problem.
 * This is because PHP will not allow a __toString() method to throw an exception.
 *
 * @return string Rendered cell
 */
	public function __toString() {
		try {
			return $this->render();
		} catch (\Exception $e) {
			trigger_error('Could not render cell - ' . $e->getMessage(), E_USER_WARNING);
			return '';
		}
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

}
