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
namespace Cake\Form;

use Cake\Form\Schema;
use Cake\Validation\Validator;

/**
 * Form abstraction used to create forms not tied to ORM backed models,
 * or to other permanent datastores. Ideal for implement forms on top of
 * API services, or contact forms.
 *
 */
class Form {

	protected $_schema;
	protected $_errors = [];
	protected $_validator;

	public function schema(Schema $schema = null) {
		if ($schema === null && empty($this->_schema)) {
			$schema = $this->_buildSchema(new Schema());
		}
		if ($schema) {
			$this->_schema = $schema;
		}
		return $this->_schema;
	}

	protected function _buildSchema($schema) {
		return $schema;
	}

	public function validator(Validator $validator = null) {
		if ($validator === null && empty($this->_validator)) {
			$validator = $this->_buildValidator(new Validator());
		}
		if ($validator) {
			$this->_validator = $validator;
		}
		return $this->_validator;
	}

	protected function _buildValidator($validator) {
		return $validator;
	}

	public function isValid($data) {
		$validator = $this->validator();
		$this->_errors = $validator->errors($data);
		return count($this->_errors) === 0;
	}

	public function errors() {
		return $this->_errors;
	}

	public function execute($data) {
	}

}
