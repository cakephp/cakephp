<?php
/**
 * 
 * PHP Version 5.4
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2013, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Model\Datasource\Database\Dialect;

use Cake\Model\Datasource\Database\Expression\UnaryExpression;
use Cake\Model\Datasource\Database\Query;

trait PostgresDialect {

	protected function _selectQueryTranslator($query) {
		$limit = $query->clause('limit');
		$offset = $query->clause('offset');
		$order = $query->clause('order');

		if ($limit || $offset) {
			$query = clone $query;
			$field = '_cake_paging_._cake_page_rownum_';
			$outer = (new Query($query->connection()))
				->select('*')
				->from(['_cake_paging_' => $query]);

			if ($offset) {
				$outer->where(["$field >" => $offset]);
			}
			if ($limit) {
				$outer->where(["$field <=" => (int)$offset + (int)$limit]);
			}

			$query
				->select(['_cake_page_rownum_' => new UnaryExpression($order, [], 'ROW_NUMBER() OVER')])
				->limit(null)
				->offset(null)
				->order([], true);
			return $outer->decorateResults($this->_rowNumberRemover());
		}

		return $query;
	}


	protected function _rowNumberRemover() {
		return function($row) {
			if (isset($row['_cake_page_rownum_'])) {
				unset($row['_cake_page_rownum_']);
			} else {
				array_pop($row);
			}
			return $row;
		};
	}

}
