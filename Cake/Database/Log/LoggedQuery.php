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
namespace Cake\Database\Log;

/**
 * Contains a query string, the params used to executed it, time taken to do it
 * and the number of rows found or affected by its execution.
 */
class LoggedQuery {

/**
 * Query string that was executed
 *
 * @var string
 */
	public $query = '';

/**
 * Number of milliseconds this query took to complete
 *
 * @var float
 */
	public $took = 0;

/**
 * Associative array with the params bound to the query string
 *
 * @var string
 */
	public $params = [];

/**
 * Number of rows affected or returned by the query execution
 *
 * @var integer
 */
	public $numRows = 0;

/**
 * Returns the string representation of this logged query
 *
 * @return void
 */
	public function __toString() {
		return $this->query;
	}

}
