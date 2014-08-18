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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class UuidFixture.
 *
 */
class UuidFixture extends TestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'uuid'],
		'title' => 'string',
		'count' => ['type' => 'integer', 'default' => 0],
		'created' => 'datetime',
		'updated' => 'datetime',
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('id' => '47c36f9c-bc00-4d17-9626-4e183ca6822b', 'title' => 'Unique record 1', 'count' => 2, 'created' => '2008-03-13 01:16:23', 'updated' => '2008-03-13 01:18:31'),
		array('id' => '47c36f9c-f2b0-43f5-b3f7-4e183ca6822b', 'title' => 'Unique record 2', 'count' => 4, 'created' => '2008-03-13 01:18:24', 'updated' => '2008-03-13 01:20:32'),
		array('id' => '47c36f9c-0ffc-4084-9b03-4e183ca6822b', 'title' => 'Unique record 3', 'count' => 5, 'created' => '2008-03-13 01:20:25', 'updated' => '2008-03-13 01:22:33'),
		array('id' => '47c36f9c-2578-4c2e-aeab-4e183ca6822b', 'title' => 'Unique record 4', 'count' => 3, 'created' => '2008-03-13 01:22:26', 'updated' => '2008-03-13 01:24:34'),
	);
}
