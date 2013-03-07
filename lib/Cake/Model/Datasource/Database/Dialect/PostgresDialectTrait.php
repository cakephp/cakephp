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
 * @package       Cake.Model
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Model\Datasource\Database\Dialect;

use Cake\Model\Datasource\Database\Expression\UnaryExpression;
use Cake\Model\Datasource\Database\Query;
use Cake\Model\Datasource\Database\SqlDialectTrait;

trait PostgresDialectTrait extends SqlDialectTrait {

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
