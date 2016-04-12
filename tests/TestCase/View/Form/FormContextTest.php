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
namespace Cake\Test\TestCase\View\Form;

use Cake\Form\Form;
use Cake\Network\Request;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use Cake\View\Form\FormContext;

/**
 * Form context test case.
 */
class FormContextTest extends TestCase
{

    /**
     * setup method.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->request = new Request();
    }

    /**
     * Test getting the primary key.
     *
     * @return void
     */
    public function testPrimaryKey()
    {
        $context = new FormContext($this->request, ['entity' => new Form()]);
        $this->assertEquals([], $context->primaryKey());
    }

    /**
     * Test isPrimaryKey.
     *
     * @return void
     */
    public function testIsPrimaryKey()
    {
        $context = new FormContext($this->request, ['entity' => new Form()]);
        $this->assertFalse($context->isPrimaryKey('id'));
    }

    /**
     * Test the isCreate method.
     *
     * @return void
     */
    public function testIsCreate()
    {
        $context = new FormContext($this->request, ['entity' => new Form()]);
        $this->assertTrue($context->isCreate());
    }

    /**
     * Test reading values from the request & defaults.
     */
    public function testValPresent()
    {
        $this->request->data = [
            'Articles' => [
                'title' => 'New title',
                'body' => 'My copy',
            ]
        ];
        $context = new FormContext($this->request, ['entity' => new Form()]);
        $this->assertEquals('New title', $context->val('Articles.title'));
        $this->assertEquals('My copy', $context->val('Articles.body'));
        $this->assertNull($context->val('Articles.nope'));
    }

    /**
     * Test getting values when the request and defaults are missing.
     *
     * @return void
     */
    public function testValMissing()
    {
        $context = new FormContext($this->request, ['entity' => new Form()]);
        $this->assertNull($context->val('Comments.field'));
    }

    /**
     * Test isRequired
     *
     * @return void
     */
    public function testIsRequired()
    {
        $form = new Form();
        $form->validator()
            ->requirePresence('name')
            ->add('email', 'format', ['rule' => 'email']);

        $context = new FormContext($this->request, [
            'entity' => $form
        ]);
        $this->assertTrue($context->isRequired('name'));
        $this->assertTrue($context->isRequired('email'));
        $this->assertFalse($context->isRequired('body'));
        $this->assertFalse($context->isRequired('Prefix.body'));
    }

    /**
     * Test the type method.
     *
     * @return void
     */
    public function testType()
    {
        $form = new Form();
        $form->schema()
            ->addField('email', 'string')
            ->addField('user_id', 'integer');

        $context = new FormContext($this->request, [
            'entity' => $form
        ]);
        $this->assertNull($context->type('undefined'));
        $this->assertEquals('integer', $context->type('user_id'));
        $this->assertEquals('string', $context->type('email'));
        $this->assertNull($context->type('Prefix.email'));
    }

    /**
     * Test fetching attributes.
     *
     * @return void
     */
    public function testAttributes()
    {
        $form = new Form();
        $form->schema()
            ->addField('email', [
                'type' => 'string',
                'length' => 10,
            ])
            ->addField('amount', [
                'type' => 'decimal',
                'length' => 5,
                'precision' => 2,
            ]);
        $context = new FormContext($this->request, [
            'entity' => $form
        ]);
        $this->assertEquals([], $context->attributes('id'));
        $this->assertEquals(['length' => 10, 'precision' => null], $context->attributes('email'));
        $this->assertEquals(['precision' => 2, 'length' => 5], $context->attributes('amount'));
    }

    /**
     * Test fetching errors.
     *
     * @return void
     */
    public function testError()
    {
        $nestedValidator = new Validator();
        $nestedValidator
            ->add('password', 'length', ['rule' => ['minLength', 8]])
            ->add('confirm', 'length', ['rule' => ['minLength', 8]]);
        $form = new Form();
        $form->validator()
            ->add('email', 'format', ['rule' => 'email'])
            ->add('name', 'length', ['rule' => ['minLength', 10]])
            ->addNested('pass', $nestedValidator);
        $form->validate([
            'email' => 'derp',
            'name' => 'derp',
            'pass' => [
                'password' => 'short',
                'confirm' => 'long enough',
            ],
        ]);

        $context = new FormContext($this->request, ['entity' => $form]);
        $this->assertEquals([], $context->error('empty'));
        $this->assertEquals(['The provided value is invalid'], $context->error('email'));
        $this->assertEquals(['The provided value is invalid'], $context->error('name'));
        $this->assertEquals(['The provided value is invalid'], $context->error('pass.password'));
        $this->assertEquals([], $context->error('Alias.name'));
        $this->assertEquals([], $context->error('nope.nope'));

        $mock = $this->getMock('Cake\Validation\Validator', ['errors']);
        $mock->expects($this->once())
            ->method('errors')
            ->willReturn(['key' => 'should be an array, not a string']);
        $form->validator($mock);
        $form->validate([]);
        $context = new FormContext($this->request, ['entity' => $form]);
        $this->assertEquals(
            ['should be an array, not a string'],
            $context->error('key'),
            'This test should not produce a PHP warning from array_values().'
        );
    }

    /**
     * Test checking errors.
     *
     * @return void
     */
    public function testHasError()
    {
        $nestedValidator = new Validator();
        $nestedValidator
            ->add('password', 'length', ['rule' => ['minLength', 8]])
            ->add('confirm', 'length', ['rule' => ['minLength', 8]]);
        $form = new Form();
        $form->validator()
            ->add('email', 'format', ['rule' => 'email'])
            ->add('name', 'length', ['rule' => ['minLength', 10]])
            ->addNested('pass', $nestedValidator);
        $form->validate([
            'email' => 'derp',
            'name' => 'derp',
            'pass' => [
                'password' => 'short',
                'confirm' => 'long enough',
            ],
        ]);

        $context = new FormContext($this->request, ['entity' => $form]);
        $this->assertTrue($context->hasError('email'));
        $this->assertTrue($context->hasError('name'));
        $this->assertFalse($context->hasError('nope'));
        $this->assertFalse($context->hasError('nope.nope'));
        $this->assertTrue($context->hasError('pass.password'));
    }
}
