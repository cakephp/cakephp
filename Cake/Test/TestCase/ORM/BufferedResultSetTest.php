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
namespace Cake\Test\TestCase\ORM;

use Cake\Core\Configure;
use Cake\Database\ConnectionManager;
use Cake\ORM\BufferedResultSet;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * BufferedResultSet test case.
 */
class BufferedResultSetTest extends TestCase {

	public $fixtures = ['core.article'];

	public function setUp() {
		parent::setUp();
		$this->connection = ConnectionManager::get('test');
		$this->table = new Table(['table' => 'articles', 'connection' => $this->connection]);
	}

/**
 * Test that result sets can be rewound and re-used.
 *
 * @return void
 */
	public function testRewind() {
		$query = $this->table->find('all');
		$results = $query->bufferResults()->execute();
		$first = $second = [];
		foreach ($results as $result) {
			$first[] = $result;
		}
		foreach ($results as $result) {
			$second[] = $result;
		}
		$this->assertEquals($first, $second);
	}

}
