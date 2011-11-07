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
App::uses('Xml', 'Utility');

/**
 * A view class that is used for creating XML responses.
 *
 * By setting the 'serialize' key in your controller, you can specify a view variable
 * that should be serialized to XML and used as the response for the request.
 * This allows you to omit views + layouts, if your just need to emit a single view
 * variable as the XML response.
 *
 * In your controller, you could do the following:
 *
 * `$this->set(array('posts' => $posts, 'serialize' => 'posts'));`
 *
 * When the view is rendered, the `$posts` view variable will be serialized 
 * into XML.
 *
 * **Note** The view variable you specify must be compatible with Xml::fromArray().
 *
 * If you don't use the `serialize` key, you will need a view + layout just like a
 * normal view.
 *
 * @package       Cake.View
 * @since         CakePHP(tm) v 2.1.0
 */
class XmlView extends View {

/**
 * Constructor
 *
 * @param Controller $controller
 */
	public function __construct($controller) {
		parent::__construct($controller);

		if (is_object($controller)) {
			$controller->response->type('xml');
		}
	}

/**
 * Render a XML view.
 *
 * Uses the special 'serialize' parameter to convert a set of
 * view variables into a XML response.  Makes generating simple 
 * XML responses very easy.  You can omit the 'serialize' parameter, 
 * and use a normal view + layout as well.
 *
 * @param string $view The view being rendered.
 * @param string $layout The layout being rendered.
 * @return string The rendered view.
 */
	public function render($view = null, $layout = null) {
		if (isset($this->viewVars['serialize']) && is_array($this->viewVars['serialize'])) {
			return $this->output = Xml::fromArray($this->viewVars['serialize'])->asXML();
		}
		return parent::render($view, $layout);
	}

}
