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
namespace Cake\Test\TestCase\ORM;

use Cake\ORM\Locator\LocatorInterface;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Test case for TableRegistry
 */
class TableRegistryTest extends TestCase
{
    /**
     * Original TableLocator.
     *
     * @var \Cake\ORM\Locator\LocatorInterface
     */
    protected $_originalLocator;

    /**
     * Remember original instance to set it back on tearDown() just to make sure
     * other tests are not broken.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->_originalLocator = TableRegistry::getTableLocator();
    }

    /**
     * tear down
     */
    public function tearDown(): void
    {
        parent::tearDown();
        TableRegistry::setTableLocator($this->_originalLocator);
    }

    /**
     * Test testSetLocator() method.
     */
    public function testSetLocator(): void
    {
        $locator = new TableLocator();
        TableRegistry::setTableLocator($locator);

        $this->assertSame($locator, TableRegistry::getTableLocator());
    }

    /**
     * Test testSetLocator() method.
     */
    public function testGetLocator(): void
    {
        $this->assertInstanceOf(LocatorInterface::class, TableRegistry::getTableLocator());
    }

    /**
     * Test that locator() method is returning TableLocator by default.
     */
    public function testLocatorDefault(): void
    {
        $locator = TableRegistry::getTableLocator();
        $this->assertInstanceOf(TableLocator::class, $locator);
    }
}
