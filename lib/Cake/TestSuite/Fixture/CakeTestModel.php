<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.TestSuite.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Model', 'Model');

/**
 * A model to extend from to help you during testing.
 *
 * @package       Cake.TestSuite.Fixture
 */
class CakeTestModel extends Model {
	public $useDbConfig = 'test';
	public $cacheSources = false;

/**
 * Sets default order for the model to avoid failing tests caused by
 * incorrect order when no order has been defined in the finds.
 * Postgres can return the results in any order it considers appropriate if none is specified
 *
 * @param array $queryData
 * @return array $queryData
 */
	public function beforeFind($queryData) {
		$pk = $this->primaryKey;
		$aliasedPk = $this->alias . '.' . $this->primaryKey;
		switch (true) {
			case !$pk:
			case !$this->useTable:
			case !$this->schema('id'):
			case !empty($queryData['order'][0]):
			case !empty($queryData['group']):
			case
				(is_string($queryData['fields']) && !($queryData['fields'] == $pk || $queryData['fields'] == $aliasedPk)) ||
				(is_array($queryData['fields']) && !(array_key_exists($pk, $queryData['fields']) || array_key_exists($aliasedPk, $queryData['fields']))):
			break;
			default:
				$queryData['order'] = array($this->alias . '.' . $this->primaryKey => 'ASC');
			break;
		}
		return $queryData;
	}

}
