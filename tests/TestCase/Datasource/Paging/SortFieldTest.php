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
use Cake\TestSuite\TestCase;

/**
 * SortField Test Case
 */
class SortFieldTest extends TestCase
{
    /**
     * Test constructor and getters
     *
     * @return void
     */
    public function testConstructorAndGetters(): void
    {
        $field = new SortField('created', SortField::DESC, false);
        $this->assertSame('created', $field->getField());
        $this->assertFalse($field->isLocked());

        $lockedField = new SortField('score', SortField::ASC, true);
        $this->assertSame('score', $lockedField->getField());
        $this->assertTrue($lockedField->isLocked());
    }

    /**
     * Test asc() static factory method
     *
     * @return void
     */
    public function testAscFactory(): void
    {
        $field = SortField::asc('title');
        $this->assertSame('title', $field->getField());
        $this->assertFalse($field->isLocked());

        // Should use default direction when no direction specified
        $this->assertSame(SortField::ASC, $field->getDirection(SortField::DESC, false));

        // Should allow override when direction is specified
        $this->assertSame(SortField::DESC, $field->getDirection(SortField::DESC, true));
    }

    /**
     * Test desc() static factory method
     *
     * @return void
     */
    public function testDescFactory(): void
    {
        $field = SortField::desc('created');
        $this->assertSame('created', $field->getField());
        $this->assertFalse($field->isLocked());

        // Should use default direction when no direction specified
        $this->assertSame(SortField::DESC, $field->getDirection(SortField::ASC, false));

        // Should allow override when direction is specified
        $this->assertSame(SortField::ASC, $field->getDirection(SortField::ASC, true));
    }

    /**
     * Test asc() with locked parameter
     *
     * @return void
     */
    public function testAscFactoryLocked(): void
    {
        $field = SortField::asc('score', locked: true);
        $this->assertSame('score', $field->getField());
        $this->assertTrue($field->isLocked());

        // Should always return locked direction regardless of request
        $this->assertSame(SortField::ASC, $field->getDirection(SortField::DESC, false));
        $this->assertSame(SortField::ASC, $field->getDirection(SortField::DESC, true));
    }

    /**
     * Test desc() with locked parameter
     *
     * @return void
     */
    public function testDescFactoryLocked(): void
    {
        $field = SortField::desc('score', locked: true);
        $this->assertSame('score', $field->getField());
        $this->assertTrue($field->isLocked());

        // Should always return locked direction regardless of request
        $this->assertSame(SortField::DESC, $field->getDirection(SortField::ASC, false));
        $this->assertSame(SortField::DESC, $field->getDirection(SortField::ASC, true));
    }

    /**
     * Test getDirection() with no default direction
     *
     * @return void
     */
    public function testGetDirectionNoDefault(): void
    {
        $field = new SortField('name', null, false);

        // Should use requested direction when no default is set
        $this->assertSame(SortField::ASC, $field->getDirection(SortField::ASC, false));
        $this->assertSame(SortField::DESC, $field->getDirection(SortField::DESC, false));
        $this->assertSame(SortField::ASC, $field->getDirection(SortField::ASC, true));
        $this->assertSame(SortField::DESC, $field->getDirection(SortField::DESC, true));
    }

    /**
     * Test getDirection() with default direction
     *
     * @return void
     */
    public function testGetDirectionWithDefault(): void
    {
        $field = new SortField('created', SortField::DESC, false);

        // Should use default when direction not specified
        $this->assertSame(SortField::DESC, $field->getDirection(SortField::ASC, false));

        // Should use requested direction when explicitly specified
        $this->assertSame(SortField::ASC, $field->getDirection(SortField::ASC, true));
        $this->assertSame(SortField::DESC, $field->getDirection(SortField::DESC, true));
    }

    /**
     * Test locked field behavior
     *
     * @return void
     */
    public function testLockedFieldBehavior(): void
    {
        $field = new SortField('priority', SortField::ASC, true);

        // Locked field should always return its default direction
        $this->assertSame(SortField::ASC, $field->getDirection(SortField::DESC, false));
        $this->assertSame(SortField::ASC, $field->getDirection(SortField::DESC, true));
        $this->assertSame(SortField::ASC, $field->getDirection(SortField::ASC, false));
        $this->assertSame(SortField::ASC, $field->getDirection(SortField::ASC, true));
    }

    /**
     * Test usage examples from the documentation
     *
     * @return void
     */
    public function testUsageExamples(): void
    {
        // Example sortMap configuration
        $sortMap = [
            'newest' => [
                SortField::desc('created'), // Default desc, toggleable
                SortField::asc('title'), // Default asc, toggleable
            ],
            'popular' => [
                SortField::desc('score', locked: true), // Always desc
                'author', // Still support strings for BC
            ],
        ];

        // Test 'newest' configuration
        $newestFields = $sortMap['newest'];

        $createdField = $newestFields[0];
        $this->assertInstanceOf(SortField::class, $createdField);
        $this->assertSame('created', $createdField->getField());
        $this->assertSame(SortField::DESC, $createdField->getDirection(SortField::ASC, false));
        $this->assertSame(SortField::ASC, $createdField->getDirection(SortField::ASC, true));

        $titleField = $newestFields[1];
        $this->assertInstanceOf(SortField::class, $titleField);
        $this->assertSame('title', $titleField->getField());
        $this->assertSame(SortField::ASC, $titleField->getDirection(SortField::DESC, false));
        $this->assertSame(SortField::DESC, $titleField->getDirection(SortField::DESC, true));

        // Test 'popular' configuration
        $popularFields = $sortMap['popular'];

        $scoreField = $popularFields[0];
        $this->assertInstanceOf(SortField::class, $scoreField);
        $this->assertSame('score', $scoreField->getField());
        $this->assertSame(SortField::DESC, $scoreField->getDirection(SortField::ASC, true));
        $this->assertTrue($scoreField->isLocked());

        // Test backward compatibility with string
        $this->assertSame('author', $popularFields[1]);
    }
}
