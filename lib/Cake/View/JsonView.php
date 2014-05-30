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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('View', 'View');

/**
 * A view class that is used for JSON responses.
 *
 * By setting the '_serialize' key in your controller, you can specify a view variable
 * that should be serialized to JSON and used as the response for the request.
 * This allows you to omit views and layouts if you just need to emit a single view
 * variable as the JSON response.
 *
 * In your controller, you could do the following:
 *
 * `$this->set(array('posts' => $posts, '_serialize' => 'posts'));`
 *
 * When the view is rendered, the `$posts` view variable will be serialized
 * into JSON.
 *
 * You can also define `'_serialize'` as an array. This will create a top level object containing
 * all the named view variables:
 *
 * {{{
 * $this->set(compact('posts', 'users', 'stuff'));
 * $this->set('_serialize', array('posts', 'users'));
 * }}}
 *
 * The above would generate a JSON object that looks like:
 *
 * `{"posts": [...], "users": [...]}`
 *
 * If you don't use the `_serialize` key, you will need a view. You can use extended
 * views to provide layout-like functionality.
 *
 * You can also enable JSONP support by setting parameter `_jsonp` to true or a string to specify
 * custom query string paramater name which will contain the callback function name.
 *
 * @package       Cake.View
 * @since         CakePHP(tm) v 2.1.0
 */
class JsonView extends View {

/**
 * JSON views are always located in the 'json' sub directory for
 * controllers' views.
 *
 * @var string
 */
	public $subDir = 'json';

/**
 * Constructor
 *
 * @param Controller $controller Controller instance.
 */
	public function __construct(Controller $controller = null) {
		parent::__construct($controller);
		if (isset($controller->response) && $controller->response instanceof CakeResponse) {
			$controller->response->type('json');
		}
	}

/**
 * Skip loading helpers if this is a _serialize based view.
 *
 * @return void
 */
	public function loadHelpers() {
		if (isset($this->viewVars['_serialize'])) {
			return;
		}
		parent::loadHelpers();
	}

/**
 * Render a JSON view.
 *
 * ### Special parameters
 * `_serialize` To convert a set of view variables into a JSON response.
 *   Its value can be a string for single variable name or array for multiple names.
 *   You can omit the`_serialize` parameter, and use a normal view + layout as well.
 * `_jsonp` Enables JSONP support and wraps response in callback function provided in query string.
 *   - Setting it to true enables the default query string parameter "callback".
 *   - Setting it to a string value, uses the provided query string parameter for finding the
 *     JSONP callback name.
 *
 * @param string $view The view being rendered.
 * @param string $layout The layout being rendered.
 * @return string The rendered view.
 */
	public function render($view = null, $layout = null) {
		$return = null;
		if (isset($this->viewVars['_serialize'])) {
			$return = $this->_serialize($this->viewVars['_serialize']);
		} elseif ($view !== false && $this->_getViewFileName($view)) {
			$return = parent::render($view, false);
		}

		if (!empty($this->viewVars['_jsonp'])) {
			$jsonpParam = $this->viewVars['_jsonp'];
			if ($this->viewVars['_jsonp'] === true) {
				$jsonpParam = 'callback';
			}
			if (isset($this->request->query[$jsonpParam])) {
				$return = sprintf('%s(%s)', h($this->request->query[$jsonpParam]), $return);
				$this->response->type('js');
			}
		}

		return $return;
	}

/**
 * Serialize view vars
 *
 * @param array $serialize The viewVars that need to be serialized
 * @return string The serialized data
 */
	protected function _serialize($serialize) {
		if (is_array($serialize)) {
			$data = array();
			foreach ($serialize as $alias => $key) {
				if (is_numeric($alias)) {
					$alias = $key;
				}
				if (array_key_exists($key, $this->viewVars)) {
					$data[$alias] = $this->viewVars[$key];
				}
			}
			$data = !empty($data) ? $data : null;
		} else {
			$data = isset($this->viewVars[$serialize]) ? $this->viewVars[$serialize] : null;
		}

		if (version_compare(PHP_VERSION, '5.4.0', '>=') && Configure::read('debug')) {
			return json_encode($data, JSON_PRETTY_PRINT);
		}

		return json_encode($data);
	}

}
