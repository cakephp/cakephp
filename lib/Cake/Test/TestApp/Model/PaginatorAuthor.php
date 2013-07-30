<?php
/**
 * PaginatorAuthor
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
 * @package       Cake.Test.TestApp.Model
 * @since         CakePHP v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace TestApp\Model;

use Cake\TestSuite\Fixture\TestModel;

/**
 * PaginatorAuthorclass
 *
 */
class PaginatorAuthor extends TestModel {

/**
 * name property
 *
 * @var string 'PaginatorAuthor'
 */
	public $name = 'PaginatorAuthor';

/**
 * useTable property
 *
 * @var string 'authors'
 */
	public $useTable = 'authors';

/**
 * alias property
 *
 * @var string 'PaginatorAuthor'
 */
	public $alias = 'PaginatorAuthor';

/**
 * alias property
 *
 * @var string 'PaginatorAuthor'
 */
	public $virtualFields = array(
		'joined_offset' => 'PaginatorAuthor.id + 1'
	);

}
