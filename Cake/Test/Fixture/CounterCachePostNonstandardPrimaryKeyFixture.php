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
class CounterCachePostNonstandardPrimaryKeyFixture extends TestFixture {

	public $fields = array(
		'pid' => ['type' => 'integer'],
		'title' => ['type' => 'string', 'length' => 255, 'null' => false],
		'uid' => ['type' => 'integer', 'null' => true],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['pid']]]
	);

	public $records = array(
		array('pid' => 1, 'title' => 'Rock and Roll', 'uid' => 66),
		array('pid' => 2, 'title' => 'Music', 'uid' => 66),
		array('pid' => 3, 'title' => 'Food', 'uid' => 301),
	);
}
