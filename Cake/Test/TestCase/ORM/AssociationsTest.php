<?php
/**
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

use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Associations;
use Cake\TestSuite\TestCase;

/**
 * Associations test case.
 */
class AssociationsTest extends TestCase {

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->associations = new Associations();
	}

/**
 * Test the simple add/has and get methods.
 *
 * @return void
 */
	public function testAddHasAndGet() {
		$this->assertFalse($this->associations->has('users'));
		$this->assertFalse($this->associations->has('Users'));

		$this->assertNull($this->associations->get('users'));
		$this->assertNull($this->associations->get('Users'));

		$belongsTo = new BelongsTo([]);
		$this->assertNull($this->associations->add('Users', $belongsTo));
		$this->assertTrue($this->associations->has('users'));
		$this->assertTrue($this->associations->has('Users'));

		$this->assertSame($belongsTo, $this->associations->get('users'));
		$this->assertSame($belongsTo, $this->associations->get('Users'));
	}

}
