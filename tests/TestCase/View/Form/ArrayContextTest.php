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

use Cake\Network\Request;
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
        $this->request = new Request();
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
        $this->request->data = [
            'Articles' => [
                'title' => 'New title',
                'body' => 'My copy',
            ]
        ];
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
                    'nope' => false
                ]
            ]
        ]);
        $this->assertTrue($context->isRequired('Comments.required'));
        $this->assertFalse($context->isRequired('Comments.nope'));
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
                    'comment' => ['length' => 255]
                ]
            ]
        ]);
        $this->assertNull($context->type('Comments.undefined'));
        $this->assertEquals('integer', $context->type('Comments.id'));
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
                ]
            ]
        ]);
        $this->assertEquals([], $context->attributes('Comments.id'));
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
