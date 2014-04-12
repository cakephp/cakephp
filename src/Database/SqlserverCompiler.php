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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Database;

use Cake\Database\QueryCompiler;

/**
 * Responsible for compiling a Query object into its SQL representation
 * for SQL Server
 *
 */
class SqlserverCompiler extends QueryCompiler {

/**
 * {@inheritdoc}
 *
 */
	protected $_templates = [
		'delete' => 'DELETE',
		'update' => 'UPDATE %s',
		'where' => ' WHERE %s',
		'group' => ' GROUP BY %s ',
		'having' => ' HAVING %s ',
		'order' => ' %s',
		'offset' => ' OFFSET %s ROWS',
		'epilog' => ' %s'
	];

/**
 * {@inheritdoc}
 *
 */
	protected $_selectParts = [
		'select', 'from', 'join', 'where', 'group', 'having', 'order', 'offset',
		'limit', 'union', 'epilog'
	];

/**
 * Generates the LIMIT part of a SQL query
 *
 * @param int $limit the limit clause
 * @param \Cake\Database\Query $query The query that is being compiled
 * @return string
 */
	protected function _buildLimitPart($limit, $query) {
		if ($limit === null) {
			return '';
		}

		if ($query->clause('offset') === null) {
			return;
		}

		return sprintf(' FETCH FIRST %d ROWS ONLY', $limit);
	}

}
