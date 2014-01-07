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
 * @since         CakePHP(tm) v3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Input;

/**
 * Form input generation context.
 *
 * Coaleseces request data, form entities. Also provides methods
 * for checking if fields are required, and schema introspection.
 *
 * The context class insulates the FormHelper and Input classes
 * from various ORM implementations making them ORM independent.
 */
class Context {

	protected $_requestData;
	protected $_entities;
	protected $_schema;

	public function __construct($requestData = [], $entities = [], $schema = []) {
		$this->_requestData = $requestData;
		$this->_entities = $entities;
		$this->_schema = $schema;
	}

}
