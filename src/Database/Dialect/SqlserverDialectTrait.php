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
namespace Cake\Database\Dialect;

use Cake\Database\SqlDialectTrait;

/**
 * Contains functions that encapsulates the SQL dialect used by MySQL,
 * including query translators and schema introspection.
 */
trait SqlserverDialectTrait {

	use SqlDialectTrait;

/**
 *  String used to start a database identifier quoting to make it safe
 *
 * @var string
 */
	protected $_startQuote = '[';

/**
 * String used to end a database identifier quoting to make it safe
 *
 * @var string
 */
	protected $_endQuote = ']';

/**
 * Modify the limit/offset to TSQL
 *
 * @param Cake\Database\Query $query The query to translate
 * @return Cake\Database\Query The modified query
 */
	protected function _selectQueryTranslator($query) {
		$limit = $query->clause('limit');
		$offset = $query->clause('offset');

		if ($limit && $offset === null) {
			// @todo implement TOP
			$query->clause('order') || $query->order([$query->connection()->newQuery()->select(['NULL'])]);
			throw new \Cake\Error\NotImplementedException();
		}

		if ($offset) {
			$offsetSql = sprintf('%d ROWS', $offset);
			if ($limit) {
				$offsetSql .= sprintf(' FETCH FIRST %d ROWS ONLY', $limit);
			}
			$query->offset($query->newExpr()->add($offsetSql));
			$query->limit(null);

			$query->clause('order') || $query->order([$query->connection()->newQuery()->select(['NULL'])]);
		}

		return $query;
	}

/**
 * Check identify before insert
 *
 * @param Cake\Database\Query $query
 * @return Cake\Database\Query
 */
	protected function _insertQueryTranslator($query) {
		// @todo check primary key and than execute: SET IDENTITY_INSERT [table] ON (before) and OFF after the insert
		// @see https://github.com/cakephp/cakephp/blob/master/lib/Cake/Model/Datasource/Database/Sqlserver.php#L345
		return $query;
	}

/**
 * Returns an dictionary of expressions to be transformed when compiling a Query
 * to SQL. Array keys are method names to be called in this class
 *
 * @return array
 */
	protected function _expressionTranslators() {
		$namespace = 'Cake\Database\Expression';
		return [
			$namespace . '\FunctionExpression' => '_transformFunctionExpression'
		];
	}

/**
 * Receives a FunctionExpression and changes it so that it conforms to this
 * SQL dialect.
 *
 * @param Cake\Database\Expression\FunctionExpression
 * @return void
 */
	protected function _transformFunctionExpression(FunctionExpression $expression) {
		switch ($expression->name()) {
			case 'CONCAT':
				// CONCAT function is expressed as exp1 + exp2
				$expression->name('')->type(' +');
				break;
/*
			@todo Implement prepend method on FunctionExpression
			case 'DATEDIFF':
				$expression
					->name('')
					->type('-')
					->iterateParts(function($p) {
						return new FunctionExpression('DATE', [$p['value']], [$p['type']]);
					});
				break;
*/
			case 'CURRENT_DATE':
				$time = new FunctionExpression('GETDATE');
				$expression->name('CONVERT')->add(['date' => 'literal', $time]);
				break;
			case 'CURRENT_TIME':
				$time = new FunctionExpression('GETDATE');
				$expression->name('CONVERT')->add(['time' => 'literal', $time]);
				break;
			case 'NOW':
				$expression->name('GETDATE');
				break;
		}
	}

/**
 * Get the schema dialect.
 *
 * Used by Cake\Schema package to reflect schema and
 * generate schema.
 *
 * @return Cake\Database\Schema\MysqlSchema
 */
	public function schemaDialect() {
		return new \Cake\Database\Schema\SqlserverSchema($this);
	}

}
