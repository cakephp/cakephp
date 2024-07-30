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

use Cake\TestSuite\TestCase;
use Cake\View\Form\ArrayContext;

/**
 * Array context test case.
 */
class ArrayContextTest extends TestCase
{
    /**
     * setup method.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testGetRequiredMessage(): void
    {
        $context = new ArrayContext([
            'required' => [
                'Comments' => [
                    'required' => 'My custom message',
                    'nope' => false,
                    'tags' => true,
                ],
            ],
        ]);

        $this->assertSame('My custom message', $context->getRequiredMessage('Comments.required'));
        $this->assertSame('This field cannot be left empty', $context->getRequiredMessage('Comments.tags'));
        $this->assertNull($context->getRequiredMessage('Comments.nope'));
    }

    /**
     * Test getting the primary key.
     */
    public function testPrimaryKey(): void
    {
        $context = new ArrayContext([]);
        $this->assertSame([], $context->getPrimaryKey());

        $context = new ArrayContext([
            'schema' => [
                '_constraints' => 'mistake',
            ],
        ]);
        $this->assertSame([], $context->getPrimaryKey());

        $data = [
            'schema' => [
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['id']],
                ],
            ],
        ];
        $context = new ArrayContext($data);

        $expected = ['id'];
        $this->assertSame($expected, $context->getPrimaryKey());
    }

    /**
     * Test isPrimaryKey.
     */
    public function testIsPrimaryKey(): void
    {
        $context = new ArrayContext([]);
        $this->assertFalse($context->isPrimaryKey('id'));

        $context = new ArrayContext([
            'schema' => [
                '_constraints' => 'mistake',
            ],
        ]);
        $this->assertFalse($context->isPrimaryKey('mistake'));

        $data = [
            'schema' => [
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['id']],
                ],
            ],
        ];
        $context = new ArrayContext($data);
        $this->assertTrue($context->isPrimaryKey('id'));
        $this->assertFalse($context->isPrimaryKey('name'));

        $data = [
            'schema' => [
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['id', 'name']],
                ],
            ],
        ];
        $context = new ArrayContext($data);
        $this->assertTrue($context->isPrimaryKey('id'));
        $this->assertTrue($context->isPrimaryKey('name'));
    }

    /**
     * Test the isCreate method.
     */
    public function testIsCreate(): void
    {
        $context = new ArrayContext([]);
        $this->assertTrue($context->isCreate());

        $data = [
            'schema' => [
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['id']],
                ],
            ],
        ];
        $context = new ArrayContext($data);
        $this->assertTrue($context->isCreate());

        $data['defaults'] = ['id' => 2];
        $context = new ArrayContext($data);
        $this->assertFalse($context->isCreate());
    }

    /**
     * Test reading values from data & defaults.
     */
    public function testValPresent(): void
    {
        $context = new ArrayContext([
            'data' => [
                'Articles' => [
                    'title' => 'New title',
                    'body' => 'My copy',
                ],
            ],
            'defaults' => [
                'Articles' => [
                    'title' => 'Default value',
                    'published' => 0,
                ],
            ],
        ]);
        $this->assertSame('New title', $context->val('Articles.title'));
        $this->assertSame('My copy', $context->val('Articles.body'));
        $this->assertSame(0, $context->val('Articles.published'));
        $this->assertNull($context->val('Articles.nope'));
    }

    /**
     * Test getting values when the data and defaults are missing.
     */
    public function testValMissing(): void
    {
        $context = new ArrayContext([]);
        $this->assertNull($context->val('Comments.field'));
    }

    /**
     * Test getting default value
     *
     * Tests includes making sure numeric elements are stripped but not keys beginning with numeric
     * value
     */
    public function testValDefault(): void
    {
        $context = new ArrayContext([
            'defaults' => [
                'title' => 'Default value',
                'users' => ['tags' => 'common1', '9tags' => 'common2'],
            ],
        ]);

        $this->assertSame('Default value', $context->val('title'));
        $this->assertSame('common1', $context->val('users.0.tags'));
        $this->assertSame('common1', $context->val('users.99.tags'));
        $this->assertSame('common2', $context->val('users.9.9tags'));
        $result = $context->val('title', ['default' => 'explicit default']);
        $this->assertSame('explicit default', $result);
    }

    /**
     * Test isRequired
     */
    public function testIsRequired(): void
    {
        $context = new ArrayContext([
            'required' => [
                'Comments' => [
                    'required' => true,
                    'nope' => false,
                    'tags' => true,
                ],
            ],
        ]);
        $this->assertTrue($context->isRequired('Comments.required'));
        $this->assertFalse($context->isRequired('Comments.nope'));
        $this->assertTrue($context->isRequired('Comments.0.tags'));
        $this->assertNull($context->isRequired('Articles.id'));
    }

    /**
     * Test isRequired when the required key is omitted
     */
    public function testIsRequiredUndefined(): void
    {
        $context = new ArrayContext([]);
        $this->assertNull($context->isRequired('Comments.field'));
    }

    /**
     * Test the type method.
     */
    public function testType(): void
    {
        $context = new ArrayContext([
            'schema' => [
                'Comments' => [
                    'id' => ['type' => 'integer'],
                    'tags' => ['type' => 'string'],
                    'comment' => ['length' => 255],
                ],
            ],
        ]);
        $this->assertNull($context->type('Comments.undefined'));
        $this->assertSame('integer', $context->type('Comments.id'));
        $this->assertSame('string', $context->type('Comments.0.tags'));
        $this->assertNull($context->type('Comments.comment'));
    }

    /**
     * Test the type method when the data is missing.
     */
    public function testIsTypeUndefined(): void
    {
        $context = new ArrayContext([]);
        $this->assertNull($context->type('Comments.undefined'));
    }

    /**
     * Test fetching attributes.
     */
    public function testAttributes(): void
    {
        $context = new ArrayContext([
            'schema' => [
                'Comments' => [
                    'id' => ['type' => 'integer'],
                    'comment' => ['type' => 'string', 'length' => 255],
                    'decimal' => ['type' => 'decimal', 'precision' => 2, 'length' => 5],
                    'floaty' => ['type' => 'float', 'precision' => 2, 'length' => 5],
                    'tags' => ['type' => 'string', 'length' => 25],
                ],
            ],
        ]);
        $this->assertSame([], $context->attributes('Comments.id'));
        $this->assertSame(['length' => 25], $context->attributes('Comments.0.tags'));
        $this->assertSame(['length' => 255], $context->attributes('Comments.comment'));
        $this->assertSame(['precision' => 2, 'length' => 5], $context->attributes('Comments.decimal'));
        $this->assertSame(['precision' => 2, 'length' => 5], $context->attributes('Comments.floaty'));
    }

    /**
     * Test fetching errors.
     */
    public function testError(): void
    {
        $context = new ArrayContext([]);
        $this->assertSame([], $context->error('Comments.empty'));

        $context = new ArrayContext([
            'errors' => [
                'Comments' => [
                    'comment' => ['Comment is required'],
                    'empty' => [],
                    'user_id' => 'A valid userid is required',
                ],
            ],
        ]);
        $this->assertSame(['Comment is required'], $context->error('Comments.comment'));
        $this->assertSame(['A valid userid is required'], $context->error('Comments.user_id'));
        $this->assertSame([], $context->error('Comments.empty'));
        $this->assertSame([], $context->error('Comments.not_there'));
    }

    /**
     * Test checking errors.
     */
    public function testHasError(): void
    {
        $context = new ArrayContext([
            'errors' => [
                'Comments' => [
                    'comment' => ['Comment is required'],
                    'empty' => [],
                    'user_id' => 'A valid userid is required',
                ],
            ],
        ]);
        $this->assertFalse($context->hasError('Comments.not_there'));
        $this->assertFalse($context->hasError('Comments.empty'));
        $this->assertTrue($context->hasError('Comments.user_id'));
        $this->assertTrue($context->hasError('Comments.comment'));
    }
}
