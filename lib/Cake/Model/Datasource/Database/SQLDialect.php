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
namespace Cake\Model\Datasource\Database;

trait SQLDialect {

/**
 *  String used to start a database identifier quoting to make it safe
 *
 * @var string
 **/
	public $startQuote = '"';

/**
 * String used to end a database identifier quoting to make it safe
 *
 * @var string
 **/
	public $endQuote = '"';

/**
 * Quotes a database identifier (a column name, table name, etc..) to
 * be used safely in queries without the risk of using reserver words
 *
 * @param string $identifier
 * @return string
 **/
	public function quoteIdentifier($identifier) {
		$identifier = trim($identifier);

		if ($identifier === '*') {
			return '*';
		}

		if (preg_match('/^[\w-]+(?:\.[^ \*]*)*$/', $identifier)) { // string, string.string
			if (strpos($identifier, '.') === false) { // string
				return $this->startQuote . $identifier . $this->endQuote;
			}
			$items = explode('.', $identifier);
			return $this->startQuote . implode($this->endQuote . '.' . $this->startQuote, $items) . $this->endQuote;
		}

		if (preg_match('/^[\w-]+\.\*$/', $identifier)) { // string.*
			return $this->startQuote . str_replace('.*', $this->endQuote . '.*', $identifier);
		}

		if (preg_match('/^([\w-]+)\((.*)\)$/', $identifier, $matches)) { // Functions
			return $matches[1] . '(' . $this->quoteIdentifier($matches[2]) . ')';
		}

		if (preg_match('/^([\w-]+(\.[\w-]+|\(.*\))*)\s+AS\s*([\w-]+)$/i', $identifier, $matches)) {
			return preg_replace(
				'/\s{2,}/', ' ', $this->quoteIdentifier($matches[1]) . ' AS  ' . $this->quoteIdentifier($matches[3])
			);
		}

		if (preg_match('/^[\w-_\s]*[\w-_]+/', $identifier)) {
			return $this->startQuote . $identifier . $this->endQuote;
		}

		return $identifier;
	}

	public function queryTranslator($type) {
		return function($query) use ($type) {
			return $this->{'_' . $type . 'QueryTranslator'}($query);
		};
	}

	protected function _selectQueryTranslator($query) {
		if (is_array($query->clause('distinct'))) {
			$query->group($query->clause('distinct'), true);
			$query->distinct(false);
		}

		return $query;
	}

}

