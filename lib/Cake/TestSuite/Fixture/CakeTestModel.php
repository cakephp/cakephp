<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
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
 * Short description for class.
 *
 * @package       Cake.TestSuite.Fixture
 */
class CakeTestModel extends Model {
	public $useDbConfig = 'test';
	public $cacheSources = false;

/**
 * Constructor, sets default order for the model to avoid failing tests caused by
 * incorrect order when no order has been defined in the finds.
 * Postgres can return the results in any order it considers appropriate if none is specified
 *
 * @param mixed $id Set this ID for this model on startup, can also be an array of options, see Model::__construct().
 * @param string $table Name of database table to use.
 * @param string $ds DataSource connection name.
 */
	function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		if ($this->useTable && $this->primaryKey && $this->schema($this->primaryKey)) {
			$this->order = array($this->alias . '.' . $this->primaryKey => 'ASC');
			$this->_schema = null;
		}
		$this->_sourceConfigured = false;
	}

}
