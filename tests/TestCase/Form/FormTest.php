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
    }

    /**
     * Test validator()
     *
     * @return void
     */
    public function testValidator()
    {
        $form = new Form();
        $validator = $form->validator();

        $this->assertInstanceOf('Cake\Validation\Validator', $validator);
        $this->assertSame($validator, $form->validator(), 'Same instance each time');

        $validator = $this->getMockBuilder('Cake\Validation\Validator')->getMock();
        $this->assertSame($validator, $form->validator($validator));
        $this->assertSame($validator, $form->validator());
    }

    /**
     * Test validate method.
     *
     * @return void
     */
    public function testValidate()
    {
        $form = new Form();
        $form->validator()
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
     * Test the errors methods.
     *
     * @return void
     */
    public function testErrors()
    {
        $form = new Form();
        $form->validator()
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
     * Test _execute is skipped on validation failure.
     *
     * @return void
     */
    public function testExecuteInvalid()
    {
        $form = $this->getMock('Cake\Form\Form', ['_execute']);
        $form->validator()
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
        $form = $this->getMock('Cake\Form\Form', ['_execute']);
        $form->validator()
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
