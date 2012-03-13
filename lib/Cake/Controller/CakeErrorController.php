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

/**
 * Error Handling Controller
 *
 * Controller used by ErrorHandler to render error views.
 *
 * @package       Cake.Controller
 */
class CakeErrorController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'CakeError';

/**
 * Uses Property
 *
 * @var array
 */
	public $uses = array();

/**
 * __construct
 *
 * @param CakeRequest $request
 * @param CakeResponse $response
 */
	public function __construct($request = null, $response = null) {
		parent::__construct($request, $response);
		if (count(Router::extensions())) {
			$this->components[] = 'RequestHandler';
		}
		$this->constructClasses();
		$this->Components->trigger('initialize', array(&$this));

		$this->_set(array('cacheAction' => false, 'viewPath' => 'Errors'));
		if (isset($this->RequestHandler)) {
			$this->RequestHandler->startup($this);
		}
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
