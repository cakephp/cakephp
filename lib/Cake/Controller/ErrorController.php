<?php
/**
 * Error Handling Controller
 *
 * Controller used by ErrorHandler to render error views.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Controller
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Controller;
use Cake\Routing\Router;

/**
 * Error Handling Controller
 *
 * Controller used by ErrorHandler to render error views.
 *
 * @package       Cake.Controller
 */
class ErrorController extends Controller {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Error';

/**
 * Uses Property
 *
 * @var array
 */
	public $uses = array();

/**
 * __construct
 *
 * @param Cake\Network\Request $request
 * @param Cake\Network\Response $response
 */
	public function __construct($request = null, $response = null) {
		parent::__construct($request, $response);
		if (count(Router::extensions())) {
			$this->components[] = 'RequestHandler';
		}
		$this->constructClasses();
		if ($this->Components->enabled('Auth')) {
			$this->Components->disable('Auth');
		}
		if ($this->Components->enabled('Security')) {
			$this->Components->disable('Security');
		}
		$this->startupProcess();

		$this->_set(array('cacheAction' => false, 'viewPath' => 'Errors'));
	}

/**
 * Escapes the viewVars.
 *
 * @return void
 */
	public function beforeRender() {
		parent::beforeRender();
		foreach ($this->viewVars as $key => $value) {
			if (!is_object($value)) {
				$this->viewVars[$key] = h($value);
			}
		}
	}

}
