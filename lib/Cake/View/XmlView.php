<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('View', 'View');
App::uses('Xml', 'Utility');

/**
 * A view class that is used for creating XML responses.
 *
 * By setting the '_serialize' key in your controller, you can specify a view variable
 * that should be serialized to XML and used as the response for the request.
 * This allows you to omit views + layouts, if your just need to emit a single view
 * variable as the XML response.
 *
 * In your controller, you could do the following:
 *
 * `$this->set(array('posts' => $posts, '_serialize' => 'posts'));`
 *
 * When the view is rendered, the `$posts` view variable will be serialized
 * into XML.
 *
 * **Note** The view variable you specify must be compatible with Xml::fromArray().
 *
 * You can also define `'_serialize'` as an array.  This will create an additional
 * top level element named `<response>` containing all the named view variables:
 *
 * {{{
 * $this->set(compact('posts', 'users', 'stuff'));
 * $this->set('_serialize', array('posts', 'users'));
 * }}}
 *
 * The above would generate a XML object that looks like:
 *
 * `<response><posts>...</posts><users>...</users></response>`
 *
 * If you don't use the `_serialize` key, you will need a view.  You can use extended
 * views to provide layout like functionality.
 *
 * @package       Cake.View
 * @since         CakePHP(tm) v 2.1.0
 */
class XmlView extends View {

/**
 * The subdirectory.  XML views are always in xml.
 *
 * @var string
 */
	public $subDir = 'xml';

/**
 * Constructor
 *
 * @param Controller $controller
 */
	public function __construct(Controller $controller = null) {
		parent::__construct($controller);

		if (isset($controller->response) && $controller->response instanceof CakeResponse) {
			$controller->response->type('xml');
		}
	}

/**
 * Render a XML view.
 *
 * Uses the special '_serialize' parameter to convert a set of
 * view variables into a XML response.  Makes generating simple
 * XML responses very easy.  You can omit the '_serialize' parameter,
 * and use a normal view + layout as well.
 *
 * @param string $view The view being rendered.
 * @param string $layout The layout being rendered.
 * @return string The rendered view.
 */
	public function render($view = null, $layout = null) {
		if (isset($this->viewVars['_serialize'])) {
			return $this->_serialize($this->viewVars['_serialize']);
		}
		if ($view !== false && $viewFileName = $this->_getViewFileName($view)) {
			return parent::render($view, false);
		}
	}

/**
 * Serialize view vars
 *
 * @param array $serialize The viewVars that need to be serialized
 * @return string The serialized data
 */
	protected function _serialize($serialize) {
		if (is_array($serialize)) {
			$data = array('response' => array());
			foreach ($serialize as $key) {
				$data['response'][$key] = $this->viewVars[$key];
			}
		} else {
			$data = isset($this->viewVars[$serialize]) ? $this->viewVars[$serialize] : null;
			if (is_array($data) && Set::numeric(array_keys($data))) {
				$data = array('response' => array($serialize => $data));
			}
		}
		 return Xml::fromArray($data)->asXML();
	}

}
