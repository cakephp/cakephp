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
     * Sets and returns mock LocatorInterface instance.
     *
     * @return \Cake\ORM\Locator\LocatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function _setMockLocator()
    {
        $locator = $this->getMockBuilder('Cake\ORM\Locator\LocatorInterface')->getMock();
        TableRegistry::setTableLocator($locator);

        return $locator;
    }

    /**
     * Test testSetLocator() method.
     */
    public function testSetLocator(): void
    {
        $locator = $this->_setMockLocator();

        $this->assertSame($locator, TableRegistry::getTableLocator());
    }

    /**
     * Test testSetLocator() method.
     */
    public function testGetLocator(): void
    {
        $this->assertInstanceOf('Cake\ORM\Locator\LocatorInterface', TableRegistry::getTableLocator());
    }

    /**
     * Test that locator() method is returning TableLocator by default.
     */
    public function testLocatorDefault(): void
    {
        $locator = TableRegistry::getTableLocator();
        $this->assertInstanceOf('Cake\ORM\Locator\TableLocator', $locator);
    }

    /**
     * Test the get() method.
     */
    public function testGet(): void
    {
        $locator = $this->_setMockLocator();
        $locator->expects($this->once())->method('get')->with('Test', []);

        TableRegistry::get('Test', []);
    }

    /**
     * Test the get() method.
     */
    public function testSet(): void
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();

        $locator = $this->_setMockLocator();
        $locator->expects($this->once())->method('set')->with('Test', $table);

        TableRegistry::set('Test', $table);
    }

    /**
     * Test the remove() method.
     */
    public function testRemove(): void
    {
        $locator = $this->_setMockLocator();
        $locator->expects($this->once())->method('remove')->with('Test');

        TableRegistry::remove('Test');
    }

    /**
     * Test the clear() method.
     */
    public function testClear(): void
    {
        $locator = $this->_setMockLocator();
        $locator->expects($this->once())->method('clear');

        TableRegistry::clear();
    }
}
