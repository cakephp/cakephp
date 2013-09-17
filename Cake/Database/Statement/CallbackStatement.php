<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Database\Statement;

class CallbackStatement extends StatementDecorator {

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
