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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource\Paging;

use Cake\Datasource\Paging\SortableFieldsBuilder;
use Cake\Datasource\Paging\SortField;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * SortableFieldsBuilder Test Case
 */
class SortableFieldsBuilderTest extends TestCase
{
    /**
     * Test basic add() functionality
     *
     * @return void
     */
    public function testAdd(): void
    {
        $factory = new SortableFieldsBuilder();
        $factory->add('newest', SortField::desc('created'));
        $sorts = $factory->toArray();

        $this->assertArrayHasKey('newest', $sorts);
        $this->assertIsArray($sorts['newest']);
        $this->assertCount(1, $sorts['newest']);
        $this->assertInstanceOf(SortField::class, $sorts['newest'][0]);
    }

    /**
     * Test add() with multiple fields
     *
     * @return void
     */
    public function testAddMultipleFields(): void
    {
        $factory = new SortableFieldsBuilder();
        $factory->add('relevance', SortField::desc('score'), SortField::asc('title'));
        $sorts = $factory->toArray();

        $this->assertIsArray($sorts['relevance']);
        $this->assertCount(2, $sorts['relevance']);
        $this->assertInstanceOf(SortField::class, $sorts['relevance'][0]);
        $this->assertInstanceOf(SortField::class, $sorts['relevance'][1]);
    }

    /**
     * Test add() with string fields
     *
     * @return void
     */
    public function testAddStringFields(): void
    {
        $factory = new SortableFieldsBuilder();
        $factory->add('simple', 'title', 'created');
        $sorts = $factory->toArray();

        $this->assertIsArray($sorts['simple']);
        $this->assertCount(2, $sorts['simple']);
        $this->assertSame('title', $sorts['simple'][0]);
        $this->assertSame('created', $sorts['simple'][1]);
    }

    /**
     * Test add() with no fields (shorthand)
     *
     * @return void
     */
    public function testAddShorthand(): void
    {
        $factory = new SortableFieldsBuilder();
        $factory->add('title');
        $sorts = $factory->toArray();

        $this->assertIsArray($sorts['title']);
        $this->assertCount(1, $sorts['title']);
        $this->assertSame('title', $sorts['title'][0]);
    }

    /**
     * Test create() with null returns null
     *
     * @return void
     */
    public function testCreateWithNull(): void
    {
        $builder = SortableFieldsBuilder::create(null);
        $this->assertNull($builder);
    }

    /**
     * Test create() from simple array
     *
     * @return void
     */
    public function testCreateFromSimpleArray(): void
    {
        $builder = SortableFieldsBuilder::create(['title', 'created', 'author_id']);
        $this->assertInstanceOf(SortableFieldsBuilder::class, $builder);

        $map = $builder->toArray();
        $this->assertArrayHasKey('title', $map);
        $this->assertArrayHasKey('created', $map);
        $this->assertArrayHasKey('author_id', $map);
    }

    /**
     * Test create() from associative map
     *
     * @return void
     */
    public function testCreateFromMap(): void
    {
        $config = [
            'name' => 'Users.name',
            'newest' => [SortField::desc('created')],
        ];

        $builder = SortableFieldsBuilder::create($config);
        $this->assertInstanceOf(SortableFieldsBuilder::class, $builder);

        $map = $builder->toArray();
        $this->assertSame('Users.name', $map['name']);
        $this->assertInstanceOf(SortField::class, $map['newest'][0]);
    }

    /**
     * Test create() from callable
     *
     * @return void
     */
    public function testCreateFromCallable(): void
    {
        $builder = SortableFieldsBuilder::create(function ($factory) {
            return $factory
                ->add('name', SortField::asc('Users.name'))
                ->add('newest', SortField::desc('created'));
        });

        $this->assertInstanceOf(SortableFieldsBuilder::class, $builder);
        $map = $builder->toArray();
        $this->assertArrayHasKey('name', $map);
        $this->assertArrayHasKey('newest', $map);
    }

    /**
     * Test resolve() with simple string mapping
     *
     * @return void
     */
    public function testResolveSimpleMapping(): void
    {
        $builder = SortableFieldsBuilder::create([
            'name' => 'Users.name',
        ]);
        $this->assertNotNull($builder);

        $result = $builder->resolve('name', 'asc', true);
        $this->assertSame(['Users.name' => 'asc'], $result);

        $result = $builder->resolve('name', 'desc', true);
        $this->assertSame(['Users.name' => 'desc'], $result);
    }

    /**
     * Test resolve() with invalid key returns null
     *
     * @return void
     */
    public function testResolveInvalidKey(): void
    {
        $builder = SortableFieldsBuilder::create([
            'name' => 'Users.name',
        ]);
        $this->assertNotNull($builder);

        $result = $builder->resolve('invalid', 'asc', true);
        $this->assertNull($result);
    }

    /**
     * Test resolve() with multi-column array
     *
     * @return void
     */
    public function testResolveMultiColumn(): void
    {
        $builder = SortableFieldsBuilder::create([
            'newest' => ['created', 'title'],
        ]);
        $this->assertNotNull($builder);

        $result = $builder->resolve('newest', 'desc', true);
        $expected = [
            'created' => 'desc',
            'title' => 'desc',
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test resolve() with SortField objects
     *
     * @return void
     */
    public function testResolveWithSortFieldObjects(): void
    {
        $builder = SortableFieldsBuilder::create([
            'popular' => [
                SortField::desc('score'),
                SortField::asc('title'),
            ],
        ]);
        $this->assertNotNull($builder);

        // Without direction specified - use defaults
        $result = $builder->resolve('popular', 'asc', false);
        $expected = [
            'score' => 'desc',
            'title' => 'asc',
        ];
        $this->assertSame($expected, $result);

        // With direction specified - toggleable fields use it
        $result = $builder->resolve('popular', 'desc', true);
        $expected = [
            'score' => 'desc',
            'title' => 'desc',
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test resolve() with locked SortField
     *
     * @return void
     */
    public function testResolveWithLockedSortField(): void
    {
        $builder = SortableFieldsBuilder::create([
            'relevance' => [
                SortField::desc('score', locked: true),
                SortField::asc('title'),
            ],
        ]);
        $this->assertNotNull($builder);

        // Try to override locked field with asc
        $result = $builder->resolve('relevance', 'asc', true);
        $expected = [
            'score' => 'desc', // Locked, stays desc
            'title' => 'asc', // Toggleable, uses requested
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test resolve() with default directions when not specified
     *
     * @return void
     */
    public function testResolveWithDefaultDirections(): void
    {
        $builder = SortableFieldsBuilder::create([
            'custom' => [
                'title' => 'asc',
                'created' => 'desc',
            ],
        ]);
        $this->assertNotNull($builder);

        // No direction specified - use defaults
        $result = $builder->resolve('custom', 'asc', false);
        $expected = [
            'title' => 'asc',
            'created' => 'desc',
        ];
        $this->assertSame($expected, $result);

        // Direction 'asc' specified - use defaults as-is
        $result = $builder->resolve('custom', 'asc', true);
        $expected = [
            'title' => 'asc',
            'created' => 'desc',
        ];
        $this->assertSame($expected, $result);

        // Direction 'desc' specified - invert all defaults
        $result = $builder->resolve('custom', 'desc', true);
        $expected = [
            'title' => 'desc', // default asc, inverted to desc
            'created' => 'asc', // default desc, inverted to asc
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test resolve() with simple array format
     *
     * @return void
     */
    public function testResolveWithSimpleArray(): void
    {
        $builder = SortableFieldsBuilder::create(['title', 'created', 'author_id']);
        $this->assertNotNull($builder);

        $result = $builder->resolve('title', 'asc', true);
        $this->assertSame(['title' => 'asc'], $result);

        $result = $builder->resolve('created', 'desc', true);
        $this->assertSame(['created' => 'desc'], $result);

        $result = $builder->resolve('invalid', 'asc', true);
        $this->assertNull($result);
    }

    /**
     * Test resolve() with empty array uses key as field
     *
     * @return void
     */
    public function testResolveWithEmptyArray(): void
    {
        $builder = new SortableFieldsBuilder();
        $builder->add('title'); // Adds empty array

        $result = $builder->resolve('title', 'asc', true);
        $this->assertSame(['title' => 'asc'], $result);
    }

    /**
     * Test fromArray() static method with simple array format
     *
     * @return void
     */
    public function testFromArrayWithSimpleFormat(): void
    {
        $builder = SortableFieldsBuilder::fromArray(['title', 'created']);
        $map = $builder->toArray();

        $this->assertArrayHasKey('title', $map);
        $this->assertArrayHasKey('created', $map);
        $this->assertSame(['title'], $map['title']);
        $this->assertSame(['created'], $map['created']);
    }

    /**
     * Test fromArray() static method with associative map format
     *
     * @return void
     */
    public function testFromArrayWithMapFormat(): void
    {
        $config = [
            'name' => 'Users.name',
            'newest' => ['created', 'title'],
        ];

        $builder = SortableFieldsBuilder::fromArray($config);
        $map = $builder->toArray();

        $this->assertSame('Users.name', $map['name']);
        $this->assertSame(['created', 'title'], $map['newest']);
    }

    /**
     * Test fromArray() with SortField object (not in array)
     *
     * @return void
     */
    public function testFromArrayWithSortFieldObject(): void
    {
        $config = [
            'newest' => SortField::desc('created'),
        ];

        $builder = SortableFieldsBuilder::fromArray($config);
        $map = $builder->toArray();

        $this->assertIsArray($map['newest']);
        $this->assertCount(1, $map['newest']);
        $this->assertInstanceOf(SortField::class, $map['newest'][0]);
    }

    /**
     * Test fromArray() with mixed format (numeric and string keys)
     *
     * @return void
     */
    public function testFromArrayWithMixedFormat(): void
    {
        $config = [
            'title',
            'name' => 'Users.name',
            'created',
        ];

        $builder = SortableFieldsBuilder::fromArray($config);
        $map = $builder->toArray();

        $this->assertArrayHasKey('title', $map);
        $this->assertArrayHasKey('name', $map);
        $this->assertArrayHasKey('created', $map);
        $this->assertSame('Users.name', $map['name']);
        $this->assertIsArray($map['title']);
        $this->assertIsArray($map['created']);
    }

    /**
     * Test fromArray() with invalid type throws exception
     *
     * @return void
     */
    public function testFromArrayWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sortable field value type for key `invalid`. Expected string, array, or SortField, got `int`.');

        $config = [
            'invalid' => 123,
        ];

        SortableFieldsBuilder::fromArray($config);
    }

    /**
     * Test fromCallable() static method
     *
     * @return void
     */
    public function testFromCallable(): void
    {
        $builder = SortableFieldsBuilder::fromCallable(function ($factory) {
            return $factory
                ->add('name', 'Users.name')
                ->add('newest', SortField::desc('created'));
        });

        $map = $builder->toArray();
        $this->assertArrayHasKey('name', $map);
        $this->assertArrayHasKey('newest', $map);
    }
}
