<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Form;

use Cake\Form\Form;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use TestApp\Form\AppForm;
use TestApp\Form\FormSchema;
use TestApp\Form\ValidateForm;

/**
 * Form test case.
 */
class FormTest extends TestCase
{

    /**
     * Test schema()
     *
     * @return void
     */
    public function testSchema()
    {
        $form = new Form();
        $schema = $form->schema();

        $this->assertInstanceOf('Cake\Form\Schema', $schema);
        $this->assertSame($schema, $form->schema(), 'Same instance each time');

        $schema = $this->getMockBuilder('Cake\Form\Schema')->getMock();
        $this->assertSame($schema, $form->schema($schema));
        $this->assertSame($schema, $form->schema());

        $form = new AppForm();
        $this->assertInstanceOf(FormSchema::class, $form->schema());
    }

    /**
     * Test validator()
     *
     * @return void
     * @group deprecated
     */
    public function testValidator()
    {
        $this->deprecated(function () {
            $form = new Form();
            $validator = $form->validator();

            $this->assertInstanceOf('Cake\Validation\Validator', $validator);
            $this->assertSame($validator, $form->validator(), 'Same instance each time');

            $validator = $this->getMockBuilder('Cake\Validation\Validator')->getMock();
            $this->assertSame($validator, $form->validator($validator));
            $this->assertSame($validator, $form->validator());
        });
    }

    /**
     * Test getValidator()
     *
     * @return void
     */
    public function testGetValidator()
    {
        $form = $this->getMockBuilder(Form::class)
            ->setMethods(['buildValidator'])
            ->getMock();

        $form->expects($this->once())
            ->method('buildValidator');

        $this->assertInstanceof(Validator::class, $form->getValidator());
    }

    /**
     * Test setValidator()
     *
     * @return void
     */
    public function testSetValidator()
    {
        $form = new Form();
        $validator = $this->getMockBuilder('Cake\Validation\Validator')->getMock();

        $form->setValidator('default', $validator);
        $this->assertSame($validator, $form->getValidator());
    }

    /**
     * Test validate method.
     *
     * @return void
     */
    public function testValidate()
    {
        $form = new Form();
        $form->getValidator()
            ->add('email', 'format', ['rule' => 'email'])
            ->add('body', 'length', ['rule' => ['minLength', 12]]);

        $data = [
            'email' => 'rong',
            'body' => 'too short'
        ];
        $this->assertFalse($form->validate($data));
        $this->assertCount(2, $form->errors());

        $data = [
            'email' => 'test@example.com',
            'body' => 'Some content goes here'
        ];
        $this->assertTrue($form->validate($data));
        $this->assertCount(0, $form->errors());
    }

    /**
     * tests validate using deprecated validate() method
     *
     * @return void
     */
    public function testValidateDeprected()
    {
        $this->deprecated(function () {
            $form = new ValidateForm();
            $this->assertCount(1, $form->validator(), 'should have one rule');

            $data = [];
            $this->assertFalse($form->validate($data));
            $this->assertCount(1, $form->errors());
        });
    }

    /**
     * Test the errors methods.
     *
     * @return void
     */
    public function testErrors()
    {
        $form = new Form();
        $form->getValidator()
            ->add('email', 'format', [
                'message' => 'Must be a valid email',
                'rule' => 'email'
            ])
            ->add('body', 'length', [
                'message' => 'Must be so long',
                'rule' => ['minLength', 12],
            ]);

        $data = [
            'email' => 'rong',
            'body' => 'too short'
        ];
        $form->validate($data);
        $errors = $form->errors();
        $this->assertCount(2, $errors);
        $this->assertEquals('Must be a valid email', $errors['email']['format']);
        $this->assertEquals('Must be so long', $errors['body']['length']);
    }

    /**
     * Test setErrors()
     *
     * @return void
     */
    public function testSetErrors()
    {
        $form = new Form();
        $expected = [
           'field_name' => ['rule_name' => 'message']
        ];

        $form->setErrors($expected);
        $this->assertSame($expected, $form->errors());
    }

    /**
     * Test _execute is skipped on validation failure.
     *
     * @return void
     */
    public function testExecuteInvalid()
    {
        $form = $this->getMockBuilder('Cake\Form\Form')
            ->setMethods(['_execute'])
            ->getMock();
        $form->getValidator()
            ->add('email', 'format', ['rule' => 'email']);
        $data = [
            'email' => 'rong'
        ];
        $form->expects($this->never())
            ->method('_execute');

        $this->assertFalse($form->execute($data));
    }

    /**
     * test execute() when data is valid.
     *
     * @return void
     */
    public function testExecuteValid()
    {
        $form = $this->getMockBuilder('Cake\Form\Form')
            ->setMethods(['_execute'])
            ->getMock();
        $form->getValidator()
            ->add('email', 'format', ['rule' => 'email']);
        $data = [
            'email' => 'test@example.com'
        ];
        $form->expects($this->once())
            ->method('_execute')
            ->with($data)
            ->will($this->returnValue(true));

        $this->assertTrue($form->execute($data));
    }

    /**
     * test __debugInfo
     *
     * @return void
     */
    public function testDebugInfo()
    {
        $form = new Form();
        $result = $form->__debugInfo();
        $this->assertArrayHasKey('_schema', $result);
        $this->assertArrayHasKey('_errors', $result);
        $this->assertArrayHasKey('_validator', $result);
    }
}
