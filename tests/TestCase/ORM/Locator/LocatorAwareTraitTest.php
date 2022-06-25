<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\ORM\Locator;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Locator\LocatorInterface;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use TestApp\Model\Table\PaginatorPostsTable;
use TestApp\Stub\LocatorAwareStub;
use UnexpectedValueException;

/**
 * LocatorAwareTrait test case
 */
class LocatorAwareTraitTest extends TestCase
{
    /**
     * @var object|\Cake\ORM\Locator\LocatorAwareTrait
     */
    protected $subject;

    /**
     * setup
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->getObjectForTrait(LocatorAwareTrait::class);
    }

    /**
     * Tests testGetTableLocator method
     */
    public function testGetTableLocator(): void
    {
        $tableLocator = $this->subject->getTableLocator();
        $this->assertSame($this->getTableLocator(), $tableLocator);
    }

    /**
     * Tests testSetTableLocator method
     */
    public function testSetTableLocator(): void
    {
        $newLocator = $this->getMockBuilder(LocatorInterface::class)->getMock();
        $this->subject->setTableLocator($newLocator);
        $subjectLocator = $this->subject->getTableLocator();
        $this->assertSame($newLocator, $subjectLocator);
    }

    public function testFetchTable(): void
    {
        $stub = new LocatorAwareStub('Articles');

        $result = $stub->fetchTable();
        $this->assertInstanceOf(Table::class, $result);

        $result = $stub->fetchTable('Comments');
        $this->assertInstanceOf(Table::class, $result);

        $result = $stub->fetchTable(PaginatorPostsTable::class);
        $this->assertInstanceOf(PaginatorPostsTable::class, $result);
        $this->assertSame('PaginatorPosts', $result->getAlias());
    }

    public function testfetchTableException()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'You must provide an `$alias` or set the `$defaultTable` property to a non empty string.'
        );

        $stub = new LocatorAwareStub();
        $stub->fetchTable();
    }

    public function testfetchTableExceptionForEmptyString()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'You must provide an `$alias` or set the `$defaultTable` property to a non empty string.'
        );

        $stub = new LocatorAwareStub('');
        $stub->fetchTable();
    }
}
