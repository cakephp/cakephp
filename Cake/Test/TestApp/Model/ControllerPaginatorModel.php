<?php
/**
 * ControllerPaginateModel
 *
 * PHP 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @since         CakePHP v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace TestApp\Model;

use Cake\TestSuite\Fixture\TestModel;

/**
 * ControllerPaginatorModel class
 *
 */
class ControllerPaginatorModel extends TestModel {

/**
 * name property
 *
 * @var string 'ControllerPaginateModel'
 */
	public $name = 'ControllerPaginatorModel';

/**
 * useTable property
 *
 * @var string 'comments'
 */
	public $useTable = 'comments';

/**
 * paginate method
 *
 * @return void
 */
	public function paginate($conditions, $fields, $order, $limit, $page, $recursive, $extra) {
		$this->extra = $extra;
	}

/**
 * paginateCount
 *
 * @return void
 */
	public function paginateCount($conditions, $recursive, $extra) {
		$this->extraCount = $extra;
	}
}
