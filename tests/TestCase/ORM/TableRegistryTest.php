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
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->_originalLocator = TableRegistry::locator();
    }

    /**
     * tear down
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::locator($this->_originalLocator);
    }

    /**
     * Sets and returns mock LocatorInterface instance.
     *
     * @return \Cake\ORM\Locator\LocatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function _setMockLocator()
    {
        $locator = $this->getMockBuilder('Cake\ORM\Locator\LocatorInterface')->getMock();
        TableRegistry::locator($locator);

        return $locator;
    }

    /**
     * Test locator() method.
     *
     * @return void
     */
    public function testLocator()
    {
        $this->assertInstanceOf('Cake\ORM\Locator\LocatorInterface', TableRegistry::locator());

        $locator = $this->_setMockLocator();

        $this->assertSame($locator, TableRegistry::locator());
    }

    /**
     * Test that locator() method is returing TableLocator by default.
     *
     * @return void
     */
    public function testLocatorDefault()
    {
        $locator = TableRegistry::locator();
        $this->assertInstanceOf('Cake\ORM\Locator\TableLocator', $locator);
    }

    /**
     * Test config() method.
     *
     * @return void
     */
    public function testConfig()
    {
        $locator = $this->_setMockLocator();
        $locator->expects($this->once())->method('config')->with('Test', []);

        TableRegistry::config('Test', []);
    }

    /**
     * Test the get() method.
     *
     * @return void
     */
    public function testGet()
    {
        $locator = $this->_setMockLocator();
        $locator->expects($this->once())->method('get')->with('Test', []);

        TableRegistry::get('Test', []);
    }

    /**
     * Test the get() method.
     *
     * @return void
     */
    public function testSet()
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();

        $locator = $this->_setMockLocator();
        $locator->expects($this->once())->method('set')->with('Test', $table);

        TableRegistry::set('Test', $table);
    }

    /**
     * Test the remove() method.
     *
     * @return void
     */
    public function testRemove()
    {
        $locator = $this->_setMockLocator();
        $locator->expects($this->once())->method('remove')->with('Test');

        TableRegistry::remove('Test');
    }

    /**
     * Test the clear() method.
     *
     * @return void
     */
    public function testClear()
    {
        $locator = $this->_setMockLocator();
        $locator->expects($this->once())->method('clear');

        TableRegistry::clear();
    }
}
