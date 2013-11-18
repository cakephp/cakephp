<?php
/**
 * Short description for file.
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for class.
 *
 */
class ContentFixture extends TestFixture {

	public $table = 'Content';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'iContentId' => ['type' => 'integer'],
		'cDescription' => ['type' => 'string', 'length' => 50, 'null' => true],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['iContentId']]]
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('cDescription' => 'Test Content 1'),
		array('cDescription' => 'Test Content 2'),
		array('cDescription' => 'Test Content 3'),
		array('cDescription' => 'Test Content 4')
	);
}
