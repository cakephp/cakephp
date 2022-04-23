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
namespace Cake\Test\TestCase\View\Form;

use Cake\Core\Exception\CakeException;
use Cake\Form\Form;
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
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testConstructor()
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('`$context[\'entity\']` must be an instance of Cake\Form\Form');

        new FormContext([]);
    }

    /**
     * tests getRequiredMessage
     */
    public function testGetRequiredMessage(): void
    {
        $validator = new Validator();
        $validator->notEmptyString('title', 'Don\'t forget a title!');

        $form = new Form();
        $form->setValidator(Form::DEFAULT_VALIDATOR, $validator);

        $context = new FormContext([
            'entity' => $form,
        ]);

        $this->assertNull($context->getRequiredMessage('body'));
        $this->assertSame('Don\'t forget a title!', $context->getRequiredMessage('title'));
    }

    /**
     * Test getting the primary key.
     */
    public function testPrimaryKey(): void
    {
        $context = new FormContext(['entity' => new Form()]);
        $this->assertEquals([], $context->getPrimaryKey());
    }

    /**
     * Test isPrimaryKey.
     */
    public function testIsPrimaryKey(): void
    {
        $context = new FormContext(['entity' => new Form()]);
        $this->assertFalse($context->isPrimaryKey('id'));
    }

    /**
     * Test the isCreate method.
     */
    public function testIsCreate(): void
    {
        $context = new FormContext(['entity' => new Form()]);
        $this->assertTrue($context->isCreate());
    }

    /**
     * Test reading values from form data.
     */
    public function testValPresent(): void
    {
        $form = new Form();
        $form->setData(['title' => 'set title']);

        $context = new FormContext(['entity' => $form]);

        $this->assertSame('set title', $context->val('title'));
    }

    /**
     * Test getting values when data and defaults are missing.
     */
    public function testValMissing(): void
    {
        $context = new FormContext(['entity' => new Form()]);
        $this->assertNull($context->val('Comments.field'));
    }

    /**
     * Test getting default value
     */
    public function testValDefault(): void
    {
        $form = new Form();
        $form->getSchema()->addField('name', ['default' => 'schema default']);
        $context = new FormContext(['entity' => $form]);

        $result = $context->val('title');
        $this->assertNull($result);

        $result = $context->val('title', ['default' => 'default default']);
        $this->assertSame('default default', $result);

        $result = $context->val('name');
        $this->assertSame('schema default', $result);

        $result = $context->val('name', ['default' => 'custom default']);
        $this->assertSame('custom default', $result);

        $result = $context->val('name', ['schemaDefault' => false]);
        $this->assertNull($result);
    }

    /**
     * Test isRequired
     */
    public function testIsRequired(): void
    {
        $form = new Form();
        $form->getValidator()
            ->requirePresence('name')
            ->add('email', 'format', ['rule' => 'email']);

        $context = new FormContext([
            'entity' => $form,
        ]);
        $this->assertTrue($context->isRequired('name'));
        $this->assertTrue($context->isRequired('email'));
        $this->assertNull($context->isRequired('body'));
        $this->assertNull($context->isRequired('Prefix.body'));

        // Non-default validator name.
        $form = new Form();
        $form->setValidator('custom', new Validator());
        $form->getValidator('custom')
            ->notEmptyString('title');
        $form->validate([
            'title' => '',
        ], 'custom');

        $context = new FormContext(['entity' => $form, 'validator' => 'custom']);
        $this->assertTrue($context->isRequired('title'));
    }

    /**
     * Test the type method.
     */
    public function testType(): void
    {
        $form = new Form();
        $form->getSchema()
            ->addField('email', 'string')
            ->addField('user_id', 'integer');

        $context = new FormContext([
            'entity' => $form,
        ]);
        $this->assertNull($context->type('undefined'));
        $this->assertSame('integer', $context->type('user_id'));
        $this->assertSame('string', $context->type('email'));
        $this->assertNull($context->type('Prefix.email'));
    }

    /**
     * Test the fieldNames method.
     */
    public function testFieldNames(): void
    {
        $form = new Form();
        $context = new FormContext([
            'entity' => $form,
        ]);
        $expected = [];
        $result = $context->fieldNames();
        $this->assertEquals($expected, $result);

        $form->getSchema()
            ->addField('email', 'string')
            ->addField('password', 'string');
        $context = new FormContext([
            'entity' => $form,
        ]);

        $expected = ['email', 'password'];
        $result = $context->fieldNames();
        $this->assertEquals($expected, $result);
    }

    /**
     * Test fetching attributes.
     */
    public function testAttributes(): void
    {
        $form = new Form();
        $form->getSchema()
            ->addField('email', [
                'type' => 'string',
                'length' => 10,
            ])
            ->addField('amount', [
                'type' => 'decimal',
                'length' => 5,
                'precision' => 2,
            ]);
        $context = new FormContext([
            'entity' => $form,
        ]);
        $this->assertEquals([], $context->attributes('id'));
        $this->assertEquals(
            ['length' => 10, 'precision' => null, 'default' => null],
            $context->attributes('email')
        );
        $this->assertEquals(
            ['precision' => 2, 'length' => 5, 'default' => null],
            $context->attributes('amount')
        );
    }

    /**
     * Test fetching errors.
     */
    public function testError(): void
    {
        $nestedValidator = new Validator();
        $nestedValidator
            ->add('password', 'length', ['rule' => ['minLength', 8]])
            ->add('confirm', 'length', ['rule' => ['minLength', 8]]);
        $form = new Form();
        $form->getValidator()
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

        $context = new FormContext(['entity' => $form]);
        $this->assertEquals([], $context->error('empty'));
        $this->assertEquals(['format' => 'The provided value is invalid'], $context->error('email'));
        $this->assertEquals(['length' => 'The provided value is invalid'], $context->error('name'));
        $this->assertEquals(['length' => 'The provided value is invalid'], $context->error('pass.password'));
        $this->assertEquals([], $context->error('Alias.name'));
        $this->assertEquals([], $context->error('nope.nope'));

        $validator = new Validator();
        $validator->requirePresence('key', true, 'should be an array, not a string');
        $form->setValidator('default', $validator);
        $form->validate([]);
        $context = new FormContext(['entity' => $form]);
        $this->assertEquals(
            ['_required' => 'should be an array, not a string'],
            $context->error('key'),
            'This test should not produce a PHP warning from array_values().'
        );
    }

    /**
     * Test checking errors.
     */
    public function testHasError(): void
    {
        $nestedValidator = new Validator();
        $nestedValidator
            ->add('password', 'length', ['rule' => ['minLength', 8]])
            ->add('confirm', 'length', ['rule' => ['minLength', 8]]);
        $form = new Form();
        $form->getValidator()
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

        $context = new FormContext(['entity' => $form]);
        $this->assertTrue($context->hasError('email'));
        $this->assertTrue($context->hasError('name'));
        $this->assertFalse($context->hasError('nope'));
        $this->assertFalse($context->hasError('nope.nope'));
        $this->assertTrue($context->hasError('pass.password'));
    }
}
