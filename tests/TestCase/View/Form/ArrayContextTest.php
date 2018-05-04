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
namespace Cake\Test\TestCase\View\Form;

use Cake\Http\ServerRequest;
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
    public function setUp()
    {
        parent::setUp();
        $this->request = new ServerRequest();
    }

    /**
     * Test getting the primary key.
     *
     * @return void
     */
    public function testPrimaryKey()
    {
        $context = new ArrayContext($this->request, []);
        $this->assertEquals([], $context->primaryKey());

        $context = new ArrayContext($this->request, [
            'schema' => [
                '_constraints' => 'mistake',
            ]
        ]);
        $this->assertEquals([], $context->primaryKey());

        $data = [
            'schema' => [
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['id']]
                ]
            ],
        ];
        $context = new ArrayContext($this->request, $data);

        $expected = ['id'];
        $this->assertEquals($expected, $context->primaryKey());
    }

    /**
     * Test isPrimaryKey.
     *
     * @return void
     */
    public function testIsPrimaryKey()
    {
        $context = new ArrayContext($this->request, []);
        $this->assertFalse($context->isPrimaryKey('id'));

        $context = new ArrayContext($this->request, [
            'schema' => [
                '_constraints' => 'mistake',
            ]
        ]);
        $this->assertFalse($context->isPrimaryKey('mistake'));

        $data = [
            'schema' => [
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['id']]
                ]
            ],
        ];
        $context = new ArrayContext($this->request, $data);
        $this->assertTrue($context->isPrimaryKey('id'));
        $this->assertFalse($context->isPrimaryKey('name'));

        $data = [
            'schema' => [
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['id', 'name']]
                ]
            ],
        ];
        $context = new ArrayContext($this->request, $data);
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
        $context = new ArrayContext($this->request, []);
        $this->assertTrue($context->isCreate());

        $data = [
            'schema' => [
                '_constraints' => [
                    'primary' => ['type' => 'primary', 'columns' => ['id']]
                ]
            ],
        ];
        $context = new ArrayContext($this->request, $data);
        $this->assertTrue($context->isCreate());

        $data['defaults'] = ['id' => 2];
        $context = new ArrayContext($this->request, $data);
        $this->assertFalse($context->isCreate());
    }

    /**
     * Test reading values from the request & defaults.
     */
    public function testValPresent()
    {
        $this->request = $this->request->withParsedBody([
            'Articles' => [
                'title' => 'New title',
                'body' => 'My copy',
            ]
        ]);
        $context = new ArrayContext($this->request, [
            'defaults' => [
                'Articles' => [
                    'title' => 'Default value',
                    'published' => 0
                ]
            ]
        ]);
        $this->assertEquals('New title', $context->val('Articles.title'));
        $this->assertEquals('My copy', $context->val('Articles.body'));
        $this->assertEquals(0, $context->val('Articles.published'));
        $this->assertNull($context->val('Articles.nope'));
    }

    /**
     * Test getting values when the request and defaults are missing.
     *
     * @return void
     */
    public function testValMissing()
    {
        $context = new ArrayContext($this->request, []);
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
        $context = new ArrayContext($this->request, [
            'defaults' => [
                'title' => 'Default value',
                'users' => ['tags' => 'common1', '9tags' => 'common2']
            ]
        ]);

        $this->assertEquals('Default value', $context->val('title'));
        $this->assertEquals('common1', $context->val('users.0.tags'));
        $this->assertEquals('common1', $context->val('users.99.tags'));
        $this->assertEquals('common2', $context->val('users.9.9tags'));
        $result = $context->val('title', ['default' => 'explicit default']);
        $this->assertEquals('explicit default', $result);
    }

    /**
     * Test isRequired
     *
     * @return void
     */
    public function testIsRequired()
    {
        $context = new ArrayContext($this->request, [
            'required' => [
                'Comments' => [
                    'required' => true,
                    'nope' => false,
                    'tags' => true
                ]
            ]
        ]);
        $this->assertTrue($context->isRequired('Comments.required'));
        $this->assertFalse($context->isRequired('Comments.nope'));
        $this->assertTrue($context->isRequired('Comments.0.tags'));
        $this->assertFalse($context->isRequired('Articles.id'));
    }

    /**
     * Test isRequired when the required key is omitted
     *
     * @return void
     */
    public function testIsRequiredUndefined()
    {
        $context = new ArrayContext($this->request, []);
        $this->assertFalse($context->isRequired('Comments.field'));
    }

    /**
     * Test the type method.
     *
     * @return void
     */
    public function testType()
    {
        $context = new ArrayContext($this->request, [
            'schema' => [
                'Comments' => [
                    'id' => ['type' => 'integer'],
                    'tags' => ['type' => 'string'],
                    'comment' => ['length' => 255]
                ]
            ]
        ]);
        $this->assertNull($context->type('Comments.undefined'));
        $this->assertEquals('integer', $context->type('Comments.id'));
        $this->assertEquals('string', $context->type('Comments.0.tags'));
        $this->assertNull($context->type('Comments.comment'));
    }

    /**
     * Test the type method when the data is missing.
     *
     * @return void
     */
    public function testIsTypeUndefined()
    {
        $context = new ArrayContext($this->request, []);
        $this->assertNull($context->type('Comments.undefined'));
    }

    /**
     * Test fetching attributes.
     *
     * @return void
     */
    public function testAttributes()
    {
        $context = new ArrayContext($this->request, [
            'schema' => [
                'Comments' => [
                    'id' => ['type' => 'integer'],
                    'comment' => ['type' => 'string', 'length' => 255],
                    'decimal' => ['type' => 'decimal', 'precision' => 2, 'length' => 5],
                    'floaty' => ['type' => 'float', 'precision' => 2, 'length' => 5],
                    'tags' => ['type' => 'string', 'length' => 25],
                ]
            ]
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
        $context = new ArrayContext($this->request, []);
        $this->assertEquals([], $context->error('Comments.empty'));

        $context = new ArrayContext($this->request, [
            'errors' => [
                'Comments' => [
                    'comment' => ['Comment is required'],
                    'empty' => [],
                    'user_id' => 'A valid userid is required',
                ]
            ]
        ]);
        $this->assertEquals(['Comment is required'], $context->error('Comments.comment'));
        $this->assertEquals('A valid userid is required', $context->error('Comments.user_id'));
        $this->assertEquals([], $context->error('Comments.empty'));
        $this->assertNull($context->error('Comments.not_there'));
    }

    /**
     * Test checking errors.
     *
     * @return void
     */
    public function testHasError()
    {
        $context = new ArrayContext($this->request, [
            'errors' => [
                'Comments' => [
                    'comment' => ['Comment is required'],
                    'empty' => [],
                    'user_id' => 'A valid userid is required',
                ]
            ]
        ]);
        $this->assertFalse($context->hasError('Comments.not_there'));
        $this->assertFalse($context->hasError('Comments.empty'));
        $this->assertTrue($context->hasError('Comments.user_id'));
        $this->assertTrue($context->hasError('Comments.comment'));
    }
}
