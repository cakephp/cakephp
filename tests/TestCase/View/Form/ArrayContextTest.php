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
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testGetRequiredMessage()
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
        $this->assertSame(null, $context->getRequiredMessage('Comments.nope'));
    }

    /**
     * Test getting the primary key.
     *
     * @return void
     */
    public function testPrimaryKey()
    {
        $context = new ArrayContext([]);
        $this->assertEquals([], $context->getPrimaryKey());

        $context = new ArrayContext([
            'schema' => [
                '_constraints' => 'mistake',
            ],
        ]);
        $this->assertEquals([], $context->getPrimaryKey());

        $data = [
            'schema' => [
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['id']],
                ],
            ],
        ];
        $context = new ArrayContext($data);

        $expected = ['id'];
        $this->assertEquals($expected, $context->getPrimaryKey());
    }

    /**
     * Test isPrimaryKey.
     *
     * @return void
     */
    public function testIsPrimaryKey()
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
     *
     * @return void
     */
    public function testIsCreate()
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
    public function testValPresent()
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
     *
     * @return void
     */
    public function testValMissing()
    {
        $context = new ArrayContext([]);
        $this->assertNull($context->val('Comments.field'));
    }

    /**
     * Test getting default value
     *
     * Tests includes making sure numeric elements are stripped but not keys beginning with numeric
     * value
     *
     * @return void
     */
    public function testValDefault()
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
     *
     * @return void
     */
    public function testIsRequired()
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
     *
     * @return void
     */
    public function testIsRequiredUndefined()
    {
        $context = new ArrayContext([]);
        $this->assertNull($context->isRequired('Comments.field'));
    }

    /**
     * Test the type method.
     *
     * @return void
     */
    public function testType()
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
     *
     * @return void
     */
    public function testIsTypeUndefined()
    {
        $context = new ArrayContext([]);
        $this->assertNull($context->type('Comments.undefined'));
    }

    /**
     * Test fetching attributes.
     *
     * @return void
     */
    public function testAttributes()
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
        $this->assertEquals([], $context->attributes('Comments.id'));
        $this->assertEquals(['length' => 25], $context->attributes('Comments.0.tags'));
        $this->assertEquals(['length' => 255], $context->attributes('Comments.comment'));
        $this->assertEquals(['precision' => 2, 'length' => 5], $context->attributes('Comments.decimal'));
        $this->assertEquals(['precision' => 2, 'length' => 5], $context->attributes('Comments.floaty'));
    }

    /**
     * Test fetching errors.
     *
     * @return void
     */
    public function testError()
    {
        $context = new ArrayContext([]);
        $this->assertEquals([], $context->error('Comments.empty'));

        $context = new ArrayContext([
            'errors' => [
                'Comments' => [
                    'comment' => ['Comment is required'],
                    'empty' => [],
                    'user_id' => 'A valid userid is required',
                ],
            ],
        ]);
        $this->assertEquals(['Comment is required'], $context->error('Comments.comment'));
        $this->assertEquals(['A valid userid is required'], $context->error('Comments.user_id'));
        $this->assertEquals([], $context->error('Comments.empty'));
        $this->assertEquals([], $context->error('Comments.not_there'));
    }

    /**
     * Test checking errors.
     *
     * @return void
     */
    public function testHasError()
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
