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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Form;

use Cake\Form\Form;
use Cake\TestSuite\TestCase;

/**
 * Form test case.
 */
class FormTest extends TestCase {

/**
 * Test schema()
 *
 * @return void
 */
	public function testSchema() {
		$form = new Form();
		$schema = $form->schema();

		$this->assertInstanceOf('Cake\Form\Schema', $schema);
		$this->assertSame($schema, $form->schema(), 'Same instance each time');

		$schema = $this->getMock('Cake\Form\Schema');
		$this->assertSame($schema, $form->schema($schema));
		$this->assertSame($schema, $form->schema());
	}

/**
 * Test validator()
 *
 * @return void
 */
	public function testValidator() {
		$form = new Form();
		$validator = $form->validator();

		$this->assertInstanceOf('Cake\Validation\Validator', $validator);
		$this->assertSame($validator, $form->validator(), 'Same instance each time');

		$schema = $this->getMock('Cake\Validation\Validator');
		$this->assertSame($validator, $form->validator($validator));
		$this->assertSame($validator, $form->validator());
	}

/**
 * Test isValid method.
 *
 * @return void
 */
	public function testIsValid() {
		$form = new Form();
		$form->validator()
			->add('email', 'format', ['rule' => 'email'])
			->add('body', 'length', ['rule' => ['minLength', 12]]);

		$data = [
			'email' => 'rong',
			'body' => 'too short'
		];
		$this->assertFalse($form->isValid($data));
		$this->assertCount(2, $form->errors());

		$data = [
			'email' => 'test@example.com',
			'body' => 'Some content goes here'
		];
		$this->assertTrue($form->isValid($data));
		$this->assertCount(0, $form->errors());
	}

	public function testExecuteInvalid() {
	}

	public function testExecuteValid() {
	}

}
