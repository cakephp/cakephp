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
     * Test schema()
     *
     * @group deprecated
     * @return void
     */
    public function testSchema()
    {
        $this->deprecated(function () {
            $form = new Form();
            $schema = $form->schema();

            $this->assertInstanceOf('Cake\Form\Schema', $schema);
            $this->assertSame($schema, $form->schema(), 'Same instance each time');

            $schema = new Schema();
            $this->assertSame($schema, $form->schema($schema));
            $this->assertSame($schema, $form->schema());

            $form = new AppForm();
            $this->assertInstanceOf(FormSchema::class, $form->schema());
        });
    }

    /**
     * Test setSchema() and getSchema()
     *
     * @return void
     */
    public function testSetGetSchema()
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
     *
     * @return void
     */
    public function testGetValidator()
    {
        $form = $this->getMockBuilder(Form::class)
            ->addMethods(['buildValidator'])
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
        $validator = new Validator();

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
     * Test the get errors methods.
     *
     * @return void
     */
    public function testGetErrors()
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
     *
     * @return void
     */
    public function testSetErrors()
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
     *
     * @return void
     */
    public function testExecuteInvalid()
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
     *
     * @return void
     */
    public function testExecuteValid()
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
     * Test set() with one param.
     *
     * @return void
     */
    public function testSetOneParam()
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
     *
     * @return void
     */
    public function testSetTwoParam()
    {
        $form = new Form();
        $form->set('testing', 'value');
        $this->assertEquals(['testing' => 'value'], $form->getData());
    }

    /**
     * test chainable set()
     *
     * @return void
     */
    public function testSetChained()
    {
        $form = new Form();
        $result = $form->set('testing', 'value')
            ->set('foo', 'bar');
        $this->assertSame($form, $result);
        $this->assertEquals(['testing' => 'value', 'foo' => 'bar'], $form->getData());
    }

    /**
     * Test setting and getting form data.
     *
     * @return void
     */
    public function testDataSetGet()
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
        $this->assertArrayHasKey('_data', $result);
    }
}
