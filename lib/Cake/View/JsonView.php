<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('View', 'View');

/**
 * A view class that is used for JSON responses.
 *
 * By setting the 'serialize' key in your controller, you can specify a view variable
 * that should be serialized to JSON and used as the response for the request.
 * This allows you to omit views + layouts, if your just need to emit a single view
 * variable as the JSON response.
 *
 * In your controller, you could do the following:
 *
 * `$this->set(array('posts' => $posts, 'serialize' => 'posts'));`
 *
 * When the view is rendered, the `$posts` view variable will be serialized 
 * into JSON.
 *
 * If you don't use the `serialize` key, you will need a view + layout just like a
 * normal view.
 *
 * @package       Cake.View
 * @since         CakePHP(tm) v 2.1.0
 */
class JsonView extends View {

/**
 * JSON views are always located in the 'json' sub directory for a 
 * controllers views.
 * 
 * @var string
 */
	public $subDir = 'json';

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
		if (isset($this->viewVars['serialize'])) {
			$serialize = $this->viewVars['serialize'];
			$data = isset($this->viewVars[$serialize]) ? $this->viewVars[$serialize] : null;
			return $this->output = json_encode($data);
		}
		if ($view !== false && $viewFileName = $this->_getViewFileName($view)) {
			return $this->output = $this->_render($viewFileName);
		}
	}

}
