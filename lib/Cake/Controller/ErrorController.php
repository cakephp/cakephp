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
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
		$this->constructClasses();
		if (count(Router::extensions()) &&
			!isset($this->RequestHandler)
		) {
			$this->RequestHandler = $this->Components->load('RequestHandler');
		}
		$eventManager = $this->getEventManager();
		if (isset($this->Auth)) {
			$eventManager->detach($this->Auth);
		}
		if (isset($this->Security)) {
			$eventManager->detach($this->Security);
		}
		$this->_set(array('cacheAction' => false, 'viewPath' => 'Errors'));
	}

}
