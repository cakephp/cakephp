<?php
/**
 * 
 * PHP Version 5.4
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2013, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Model\Datasource\Database\Statement;

class CallbackStatement extends \Cake\Model\Datasource\Database\Statement {

	protected $_callback;

	public function __construct($statement, $driver, $callback) {
		parent::__construct($statement, $driver);
		$this->_callback = $callback;
	}

	public function fetch($type = 'num') {
		$callback = $this->_callback;
		$row = $this->_statement->fetch($type);
		return $row === false ? $row : $callback($row);
	}

	public function fetchAll($type = 'num') {
		return array_map($this->_callback, $this->_statement->fetchAll($type));
	}

}
