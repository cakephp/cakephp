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
use Cake\TestSuite\TestCase;

/**
 * SortFieldFactory Test Case
 */
class SortFieldFactoryTest extends TestCase
{
    /**
     * Test create() method
     *
     * @return void
     */
    public function testCreate(): void
    {
        $factory = SortFieldFactory::create();
        $this->assertInstanceOf(SortFieldFactory::class, $factory);
    }

    /**
     * Test fluent interface for building sort fields
     *
     * @return void
     */
    public function testFluentInterface(): void
    {
        $fields = SortFieldFactory::create()
            ->asc('title')
            ->desc('created')
            ->locked('score', 'desc')
            ->field('author', 'asc')
            ->build();

        $this->assertCount(4, $fields);

        // Test first field (asc)
        $this->assertInstanceOf(SortField::class, $fields[0]);
        $this->assertSame('title', $fields[0]->getField());
        $this->assertFalse($fields[0]->isLocked());
        $this->assertSame('asc', $fields[0]->getDirection('desc', false));

        // Test second field (desc)
        $this->assertInstanceOf(SortField::class, $fields[1]);
        $this->assertSame('created', $fields[1]->getField());
        $this->assertFalse($fields[1]->isLocked());
        $this->assertSame('desc', $fields[1]->getDirection('asc', false));

        // Test third field (locked)
        $this->assertInstanceOf(SortField::class, $fields[2]);
        $this->assertSame('score', $fields[2]->getField());
        $this->assertTrue($fields[2]->isLocked());
        $this->assertSame('desc', $fields[2]->getDirection('asc', true));

        // Test fourth field (field with default)
        $this->assertInstanceOf(SortField::class, $fields[3]);
        $this->assertSame('author', $fields[3]->getField());
        $this->assertFalse($fields[3]->isLocked());
        $this->assertSame('asc', $fields[3]->getDirection('desc', false));
    }

    /**
     * Test add() method with custom SortField
     *
     * @return void
     */
    public function testAddCustomSortField(): void
    {
        $customField = new SortField('custom', 'desc', true);

        $fields = SortFieldFactory::create()
            ->add($customField)
            ->asc('title')
            ->build();

        $this->assertCount(2, $fields);
        $this->assertSame($customField, $fields[0]);
        $this->assertSame('custom', $fields[0]->getField());
        $this->assertTrue($fields[0]->isLocked());
    }

    /**
     * Test complex real-world usage example
     *
     * @return void
     */
    public function testComplexRealWorldExample(): void
    {
        // Build a complex sortMap for an e-commerce product listing
        $sortMap = [
            'relevance' => SortFieldFactory::create()
                ->locked('search_score', SortField::DESC)
                ->desc('popularity')
                ->asc('title')
                ->build(),
            'price_low' => SortFieldFactory::create()
                ->locked('price', SortField::ASC)
                ->asc('title')
                ->build(),
            'price_high' => SortFieldFactory::create()
                ->locked('price', SortField::DESC)
                ->asc('title')
                ->build(),
            'newest' => SortFieldFactory::create()
                ->desc('created_at')
                ->asc('title')
                ->build(),
            'bestselling' => SortFieldFactory::create()
                ->locked('sales_count', SortField::DESC)
                ->desc('rating')
                ->build(),
            'rating' => SortFieldFactory::create()
                ->desc('rating')
                ->desc('review_count')
                ->asc('title')
                ->build(),
        ];

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
        $this->assertSame('review_count', $sortMap['rating'][1]->getField());
        $this->assertSame('title', $sortMap['rating'][2]->getField());
    }
}
