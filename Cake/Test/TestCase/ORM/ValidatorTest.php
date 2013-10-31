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

use \Cake\ORM\Validator;

/**
 * Tests Validator class
 *
 */
class ValidatorTest extends \Cake\TestSuite\TestCase {

/**
 * Testing you can dynamically add rules to a field
 *
 * @return void
 */
	public function testAddingRulesToField() {
		$validator = new Validator;
		$validator->add('title', 'not-empty', ['rule' => 'notEmpty']);
		$set = $validator->field('title');
		$this->assertInstanceOf('\Cake\ORM\Validation\ValidationSet', $set);
		$this->assertCount(1, $set);

		$validator->add('title', 'another', ['rule' => 'alphanumeric']);
		$this->assertCount(2, $set);

		$validator->add('body', 'another', ['rule' => 'crazy']);
		$this->assertCount(1, $validator->field('body'));
		$this->assertCount(2, $validator);
	}
}
