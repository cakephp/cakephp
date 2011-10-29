<?php
/**
 * A custom view class that is used for JSON responses
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View
 * @since         CakePHP(tm) v 2.1.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('View', 'View');

/**
 * JsonView
 *
 * @package       Cake.View
 */
class JsonView extends View {

/**
 * Constructor
 *
 * @param Controller $controller
 */
	public function __construct($controller) {
		if (is_object($controller)) {
			foreach (array('viewVars', 'viewPath', 'view', 'response') as $var) {
				$this->{$var} = $controller->{$var};
			}
			$this->response->type('json');
		}
		Object::__construct();
	}

/**
 * Render a JSON view.
 *
 * Uses the special 'serialize' parameter to convert a set of
 * view variables into a JSON response.  Makes generating simple 
 * JSON responses very easy.  You can omit the 'serialize' parameter, 
 * and use a normal view + layout as well.
 *
 * @param string $view The view being rendered.
 * @param string $layout The layout being rendered.
 * @return string The rendered view.
 */
	public function render($view = null, $layout = null) {
		if ($view !== false && $viewFileName = $this->_getViewFileName($view)) {
			$this->_render($viewFileName);
		}

		$data = isset($this->viewVars['serialize']) ? $this->viewVars['serialize'] : null;

		return $this->output = json_encode($data);
	}

}
