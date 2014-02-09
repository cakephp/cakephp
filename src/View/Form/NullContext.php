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
 * @since         CakePHP(tm) v 3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Form;

use Cake\Network\Request;

/**
 * Provides a context provider that does nothing.
 *
 * This context provider simply fulfils the interface requirements
 * that FormHelper has and allows access to the request data.
 */
class NullContext {

/**
 * The request object.
 *
 * @var Cake\Network\Request
 */
	protected $_request;

/**
 * Constructor.
 *
 * @param Cake\Network\Request
 * @param array
 */
	public function __construct(Request $request, array $context) {
		$this->_request = $request;
	}

/**
 * Get the current value for a given field.
 *
 * This method will coalesce the current request data and the 'defaults'
 * array.
 *
 * @param string $field A dot separated path to the field a value
 *   is needed for.
 * @return mixed
 */
	public function val($field) {
		return $this->_request->data($field);
	}

/**
 * Check if a given field is 'required'.
 *
 * In this context class, this is simply defined by the 'required' array.
 *
 * @param string $field A dot separated path to check required-ness for.
 * @return boolean
 */
	public function isRequired($field) {
		return false;
	}

/**
 * Get the abstract field type for a given field name.
 *
 * @param string $field A dot separated path to get a schema type for.
 * @return null|string An abstract data type or null.
 * @see Cake\Database\Type
 */
	public function type($field) {
		return null;
	}

/**
 * Get an associative array of other attributes for a field name.
 *
 * @param string $field A dot separated path to get additional data on.
 * @return array An array of data describing the additional attributes on a field.
 */
	public function attributes($field) {
		return [];
	}

/**
 * Check whether or not a field has an error attached to it
 *
 * @param string $field A dot separated path to check errors on.
 * @return boolean Returns true if the errors for the field are not empty.
 */
	public function hasError($field) {
		return false;
	}

/**
 * Get the errors for a given field
 *
 * @param string $field A dot separated path to check errors on.
 * @return array An array of errors, an empty array will be returned when the
 *    context has no errors.
 */
	public function error($field) {
		return [];
	}

}
