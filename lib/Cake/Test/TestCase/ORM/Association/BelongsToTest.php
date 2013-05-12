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
namespace Cake\Test\TestCase\ORM\Association;

use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Table;
use Cake\ORM\Query;

/**
 * Tests BelongsTo class
 *
 */
class BelongsToTest extends \Cake\TestSuite\TestCase {

/**
 * Set up
 *
 * @return void
 */
	public function setUp() {
		$this->company = Table::build('Company', [
			'schema' => [
				'id' => ['type' => 'integer'],
				'company_name' => ['type' => 'string'],
			]
		]);
		$this->client = Table::build('Client', [
			'schema' => [
				'id' => ['type' => 'integer'],
				'client_name' => ['type' => 'string'],
				'company_id' => ['type' => 'integer'],
			]
		]);
	}

/**
 * Tear down
 *
 * @return void
 */
	public function tearDown() {
		Table::clearRegistry();
	}

/**
 * Tests that the association reports it can be joined
 *
 * @return void
 */
	public function testCanBeJoined() {
		$assoc = new BelongsTo('Test');
		$this->assertTrue($assoc->canBeJoined());
	}

/**
 * Tests that the correct join and fields are attached to a query depending on
 * the association config
 *
 * @return void
 */
	public function testAttachTo() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null]);
		$config = [
			'foreignKey' => 'company_id',
			'sourceTable' => $this->client,
			'targetTable' => $this->company
		];
		$association = new BelongsTo('Company', $config);
		$query->expects($this->once())->method('join')->with([
			'Company' => ['conditions' => ['Company.id = Client.company_id']]
		]);
		$query->expects($this->once())->method('select')->with([
			'Company__id' => 'Company.id',
			'Company__company_name' => 'Company.company_name'
		]);
		$association->attachTo($query);
	}
}

