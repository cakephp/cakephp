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

use Cake\Datasource\Paging\SortField;
use Cake\Datasource\Paging\SortFieldFactory;
use Cake\Datasource\Paging\SortMapFactory;
use Cake\TestSuite\TestCase;

/**
 * SortMapFactory Test Case
 */
class SortMapFactoryTest extends TestCase
{
    /**
     * Test create() method
     *
     * @return void
     */
    public function testCreate(): void
    {
        $factory = SortMapFactory::create();
        $this->assertInstanceOf(SortMapFactory::class, $factory);
    }

    /**
     * Test fluent interface for building complete sortMaps
     *
     * @return void
     */
    public function testFluentInterface(): void
    {
        $sortMap = SortMapFactory::create()
            ->sortKey('newest')
                ->desc('created')
                ->asc('title')
            ->sortKey('oldest')
                ->asc('created')
                ->asc('title')
            ->sortKey('popular')
                ->locked('score', SortField::DESC)
                ->desc('views')
            ->sortKey('alphabetical')
                ->asc('name')
            ->build();

        $this->assertArrayHasKey('newest', $sortMap);
        $this->assertArrayHasKey('oldest', $sortMap);
        $this->assertArrayHasKey('popular', $sortMap);
        $this->assertArrayHasKey('alphabetical', $sortMap);

        // Check newest configuration
        $this->assertCount(2, $sortMap['newest']);
        $this->assertSame('created', $sortMap['newest'][0]->getField());
        $this->assertSame(SortField::DESC, $sortMap['newest'][0]->getDirection(SortField::ASC, false));
        $this->assertSame('title', $sortMap['newest'][1]->getField());

        // Check popular configuration
        $this->assertCount(2, $sortMap['popular']);
        $this->assertTrue($sortMap['popular'][0]->isLocked());
        $this->assertSame('score', $sortMap['popular'][0]->getField());
        $this->assertSame('views', $sortMap['popular'][1]->getField());

        // Check alphabetical configuration
        $this->assertCount(1, $sortMap['alphabetical']);
        $this->assertSame('name', $sortMap['alphabetical'][0]->getField());
    }

    /**
     * Test mixing SortField objects and strings
     *
     * @return void
     */
    public function testMixedFieldTypes(): void
    {
        $customField = SortField::locked('custom', SortField::ASC);

        $sortMap = SortMapFactory::create()
            ->sortKey('mixed')
                ->add($customField)
                ->string('plain_field')
                ->desc('regular')
            ->build();

        $this->assertCount(3, $sortMap['mixed']);
        $this->assertSame($customField, $sortMap['mixed'][0]);
        $this->assertSame('plain_field', $sortMap['mixed'][1]);
        $this->assertInstanceOf(SortField::class, $sortMap['mixed'][2]);
    }

    /**
     * Test complex e-commerce example
     *
     * @return void
     */
    public function testComplexEcommerceExample(): void
    {
        $sortMap = SortMapFactory::create()
            ->sortKey('relevance')
                ->locked('search_score', SortField::DESC)
                ->desc('popularity')
                ->asc('title')
            ->sortKey('price_low')
                ->locked('price', SortField::ASC)
                ->asc('title')
            ->sortKey('price_high')
                ->locked('price', SortField::DESC)
                ->asc('title')
            ->sortKey('newest')
                ->desc('created_at')
                ->asc('title')
            ->sortKey('bestselling')
                ->locked('sales_count', SortField::DESC)
                ->desc('rating')
            ->sortKey('rating')
                ->desc('rating')
                ->desc('review_count')
                ->asc('title')
            ->build();

        // Test relevance sort
        $this->assertCount(3, $sortMap['relevance']);
        $this->assertTrue($sortMap['relevance'][0]->isLocked());
        $this->assertSame('search_score', $sortMap['relevance'][0]->getField());

        // Test price sorts
        $this->assertCount(2, $sortMap['price_low']);
        $this->assertTrue($sortMap['price_low'][0]->isLocked());
        $this->assertSame(SortField::ASC, $sortMap['price_low'][0]->getDirection(SortField::DESC, true));

        $this->assertCount(2, $sortMap['price_high']);
        $this->assertTrue($sortMap['price_high'][0]->isLocked());
        $this->assertSame(SortField::DESC, $sortMap['price_high'][0]->getDirection(SortField::ASC, true));

        // Test rating sort
        $this->assertCount(3, $sortMap['rating']);
        $this->assertFalse($sortMap['rating'][0]->isLocked());
        $this->assertSame('rating', $sortMap['rating'][0]->getField());
    }

    /**
     * Test integration with SortFieldFactory
     *
     * @return void
     */
    public function testIntegrationWithSortFieldFactory(): void
    {
        // You can still use SortFieldFactory for individual sort configurations
        $newestFields = SortFieldFactory::create()
            ->desc('created')
            ->asc('title')
            ->build();

        $popularFields = SortFieldFactory::create()
            ->locked('score', SortField::DESC)
            ->desc('views')
            ->build();

        // And combine them in a map
        $sortMap = [
            'newest' => $newestFields,
            'popular' => $popularFields,
        ];

        $this->assertCount(2, $sortMap['newest']);
        $this->assertCount(2, $sortMap['popular']);
    }

    /**
     * Test shorthand where sort key is used as field name
     *
     * @return void
     */
    public function testShorthandSortKeyAsField(): void
    {
        // When no fields are added, the sort key becomes the field
        $sortMap = SortMapFactory::create()
            ->sortKey('created')
            ->sortKey('title')
            ->sortKey('author')
            ->build();

        $this->assertArrayHasKey('created', $sortMap);
        $this->assertArrayHasKey('title', $sortMap);
        $this->assertArrayHasKey('author', $sortMap);

        // Each should have the key as the field
        $this->assertCount(1, $sortMap['created']);
        $this->assertSame('created', $sortMap['created'][0]);

        $this->assertCount(1, $sortMap['title']);
        $this->assertSame('title', $sortMap['title'][0]);

        $this->assertCount(1, $sortMap['author']);
        $this->assertSame('author', $sortMap['author'][0]);
    }

    /**
     * Test mixed shorthand and explicit fields
     *
     * @return void
     */
    public function testMixedShorthandAndExplicitFields(): void
    {
        $sortMap = SortMapFactory::create()
            ->sortKey('created') // Shorthand - uses 'created' as field
            ->sortKey('newest')
                ->desc('created_at')
                ->asc('title')
            ->sortKey('title') // Back to shorthand
            ->sortKey('popular')
                ->locked('score', SortField::DESC)
            ->build();

        // Check shorthand keys
        $this->assertCount(1, $sortMap['created']);
        $this->assertSame('created', $sortMap['created'][0]);

        $this->assertCount(1, $sortMap['title']);
        $this->assertSame('title', $sortMap['title'][0]);

        // Check explicit field configurations
        $this->assertCount(2, $sortMap['newest']);
        $this->assertSame('created_at', $sortMap['newest'][0]->getField());
        $this->assertSame('title', $sortMap['newest'][1]->getField());

        $this->assertCount(1, $sortMap['popular']);
        $this->assertTrue($sortMap['popular'][0]->isLocked());
    }
}
