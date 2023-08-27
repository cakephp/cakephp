<?php
declare(strict_types=1);

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
use Cake\Form\Schema;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use TestApp\Form\AppForm;
use TestApp\Form\FormSchema;

/**
 * Form test case.
 */
class FormTest extends TestCase
{
    /**
     * Test setSchema() and getSchema()
     */
    public function testSetGetSchema(): void
    {
        $form = new Form();
        $schema = $form->getSchema();

        $this->assertInstanceOf('Cake\Form\Schema', $schema);
        $this->assertSame($schema, $form->getSchema(), 'Same instance each time');

        $schema = new Schema();
        $this->assertSame($form, $form->setSchema($schema));
        $this->assertSame($schema, $form->getSchema());

        $form = new AppForm();
        $this->assertInstanceOf(FormSchema::class, $form->getSchema());
    }

    /**
     * Test getValidator()
     */
    public function testGetValidator(): void
    {
        $form = new Form();

        $this->assertInstanceof(Validator::class, $form->getValidator());
    }

    /**
     * Test setValidator()
     */
    public function testSetValidator(): void
    {
        $form = new Form();
        $validator = new Validator();

        $form->setValidator('default', $validator);
        $this->assertSame($validator, $form->getValidator());
    }

    /**
     * Test validate method.
     */
    public function testValidate(): void
    {
        $form = new Form();
        $form->getValidator()
            ->add('email', 'format', ['rule' => 'email'])
            ->add('body', 'length', ['rule' => ['minLength', 12]]);

        $data = [
            'email' => 'rong',
            'body' => 'too short',
        ];
        $this->assertFalse($form->validate($data));
        $this->assertCount(2, $form->getErrors());

        $data = [
            'email' => 'test@example.com',
            'body' => 'Some content goes here',
        ];
        $this->assertTrue($form->validate($data));
        $this->assertCount(0, $form->getErrors());
    }

    /**
     * Test validate with custom validator
     */
    public function testValidateCustomValidator(): void
    {
        $form = new Form();

        $validator = clone $form->getValidator();
        $validator->add('email', 'format', ['rule' => 'email']);

        $form->setValidator('custom', $validator);

        $data = ['email' => 'wrong'];

        $this->assertFalse($form->validate($data, 'custom'));
    }

    /**
     * Test the get errors methods.
     */
    public function testGetErrors(): void
    {
        $form = new Form();
        $form->getValidator()
            ->add('email', 'format', [
                'message' => 'Must be a valid email',
                'rule' => 'email',
            ])
            ->add('body', 'length', [
                'message' => 'Must be so long',
                'rule' => ['minLength', 12],
            ]);

        $data = [
            'email' => 'rong',
            'body' => 'too short',
        ];
        $form->validate($data);
        $errors = $form->getErrors();
        $this->assertCount(2, $errors);
        $this->assertSame('Must be a valid email', $errors['email']['format']);
        $this->assertSame('Must be so long', $errors['body']['length']);
    }

    /**
     * Test setErrors()
     */
    public function testSetErrors(): void
    {
        $form = new Form();
        $expected = [
           'field_name' => ['rule_name' => 'message'],
        ];

        $form->setErrors($expected);
        $this->assertSame($expected, $form->getErrors());
    }

    /**
     * Test _execute is skipped on validation failure.
     */
    public function testExecuteInvalid(): void
    {
        $form = $this->getMockBuilder('Cake\Form\Form')
            ->onlyMethods(['_execute'])
            ->getMock();
        $form->getValidator()
            ->add('email', 'format', ['rule' => 'email']);
        $data = [
            'email' => 'rong',
        ];
        $form->expects($this->never())
            ->method('_execute');

        $this->assertFalse($form->execute($data));
    }

    /**
     * test execute() when data is valid.
     */
    public function testExecuteValid(): void
    {
        $form = new Form();
        $form->getValidator()
            ->add('email', 'format', ['rule' => 'email']);
        $data = [
            'email' => 'test@example.com',
        ];

        $this->assertTrue($form->execute($data));
    }

    /**
     * test execute() when data is valid.
     */
    public function testExecuteSkipValidation(): void
    {
        $form = new Form();
        $form->getValidator()
            ->add('email', 'format', ['rule' => 'email']);
        $data = [
            'email' => 'wrong',
        ];

        $this->assertTrue($form->execute($data, ['validate' => false]));
    }

    /**
     * Test set() with one param.
     */
    public function testSetOneParam(): void
    {
        $form = new Form();
        $data = ['test' => 'val', 'foo' => 'bar'];
        $form->set($data);
        $this->assertEquals($data, $form->getData());

        $update = ['test' => 'updated'];
        $form->set($update);
        $this->assertSame('updated', $form->getData()['test']);
    }

    /**
     * test set() with 2 params
     */
    public function testSetTwoParam(): void
    {
        $form = new Form();
        $form->set('testing', 'value');
        $this->assertEquals(['testing' => 'value'], $form->getData());
    }

    /**
     * test chainable set()
     */
    public function testSetChained(): void
    {
        $form = new Form();
        $result = $form->set('testing', 'value')
            ->set('foo', 'bar');
        $this->assertSame($form, $result);
        $this->assertEquals(['testing' => 'value', 'foo' => 'bar'], $form->getData());
    }

    /**
     * Test setting and getting form data.
     */
    public function testDataSetGet(): void
    {
        $form = new Form();
        $expected = ['title' => 'title', 'is_published' => true];
        $form->setData(['title' => 'title', 'is_published' => true]);

        $this->assertSame($expected, $form->getData());
        $this->assertSame('title', $form->getData('title'));
        $this->assertNull($form->getData('nonexistent'));
    }

    /**
     * test __debugInfo
     */
    public function testDebugInfo(): void
    {
        $form = new Form();
        $result = $form->__debugInfo();
        $this->assertArrayHasKey('_schema', $result);
        $this->assertArrayHasKey('_errors', $result);
        $this->assertArrayHasKey('_validator', $result);
        $this->assertArrayHasKey('_data', $result);
    }
}
