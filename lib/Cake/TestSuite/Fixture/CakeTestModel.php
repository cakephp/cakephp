<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.TestSuite.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
 * @param integer|string|array $id Set this ID for this model on startup, can also be an array of options, see above.
 * @param string $table Name of database table to use.
 * @param string $ds DataSource connection name.
 */
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->order = array($this->alias . '.' . $this->primaryKey => 'ASC');
	}

/**
 * Overriding save() to set CakeTestSuiteDispatcher::date() as formatter for created, modified and updated fields
 *
 * @param array $data
 * @param boolean|array $validate
 * @param array $fieldList
 */

	public function save($data = null, $validate = true, $fieldList = array()) {
		$db = $this->getDataSource();
		$db->columns['datetime']['formatter'] = 'CakeTestSuiteDispatcher::date';
		return parent::save($data, $validate, $fieldList);
	}

}
