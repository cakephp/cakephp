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
namespace Cake\Validation;

/**
 * A Proxy class used to remove any extra arguments when the user intended to call
 * a method in another class that is not aware of validation providers signature
 */
class RulesProvider {

/**
 * The class to proxy, defaults to \Cake\Validation\Validation in construction
 *
 * @var object
 */
	protected $_class;

/**
 * Constructor, sets the default class to use for calling methods
 *
 * @param string $class the default class to proxy
 */
	public function __construct($class = '\Cake\Validation\Validation') {
		$this->_class = $class;
	}

/**
 * Proxies validation method calls to the Validation class, it slices
 * the arguments array to avoid passing more arguments than required to
 * the validation methods.
 *
 * @param string $method the validation method to call
 * @param array $arguments the list of arguments to pass to the method
 * @return bool whether or not the validation rule passed
 */
	public function __call($method, $arguments) {
		$arguments = array_slice($arguments, 0, -1);
		return call_user_func_array([$this->_class, $method], $arguments);
	}

}
