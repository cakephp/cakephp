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
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource;

use Cake\Datasource\FactoryLocator;
use Cake\Datasource\Locator\LocatorInterface;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * FactoryLocatorTest test case
 */
class FactoryLocatorTest extends TestCase
{
    /**
     * Test get factory
     *
     * @return void
     */
    public function testGet()
    {
        $factory = FactoryLocator::get('Table');
        $this->assertTrue(is_callable($factory) || $factory instanceof LocatorInterface);
    }

    /**
     * Test get nonexistent factory
     *
     * @return void
     */
    public function testGetNonExistent()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown repository type "Test". Make sure you register a type before trying to use it.');
        FactoryLocator::get('Test');
    }

    /**
     * test add()
     *
     * @return void
     */
    public function testAdd()
    {
        FactoryLocator::add('Test', function ($name) {
            $mock = new \stdClass();
            $mock->name = $name;

            return $mock;
        });
        $this->assertIsCallable(FactoryLocator::get('Test'));

        $locator = $this->getMockBuilder(LocatorInterface::class)->getMock();
        FactoryLocator::add('MyType', $locator);
        $this->assertInstanceOf(LocatorInterface::class, FactoryLocator::get('MyType'));
    }

    public function testFactoryAddException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '`$factory` must be an instance of Cake\Datasource\Locator\LocatorInterface or a callable.'
            . ' Got type `string` instead.'
        );

        FactoryLocator::add('Test', 'fail');
    }

    /**
     * test drop()
     *
     * @return void
     */
    public function testDrop()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown repository type "Test". Make sure you register a type before trying to use it.');
        FactoryLocator::drop('Test');

        FactoryLocator::get('Test');
    }

    public function tearDown(): void
    {
        FactoryLocator::drop('Test');
        FactoryLocator::drop('MyType');

        parent::tearDown();
    }
}
