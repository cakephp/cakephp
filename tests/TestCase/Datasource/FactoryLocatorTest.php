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
use TestApp\Datasource\StubFactory;

/**
 * FactoryLocatorTest test case
 */
class FactoryLocatorTest extends TestCase
{
    /**
     * Test get factory
     */
    public function testGet(): void
    {
        $factory = FactoryLocator::get('Table');
        $this->assertTrue(is_callable($factory) || $factory instanceof LocatorInterface);
    }

    /**
     * Test get nonexistent factory
     */
    public function testGetNonExistent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown repository type `Test`. Make sure you register a type before trying to use it.');
        FactoryLocator::get('Test');
    }

    /**
     * test add()
     */
    public function testAdd(): void
    {
        FactoryLocator::add('MyType', new StubFactory());
        $this->assertInstanceOf(LocatorInterface::class, FactoryLocator::get('MyType'));
    }

    /**
     * test drop()
     */
    public function testDrop(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown repository type `Test`. Make sure you register a type before trying to use it.');
        FactoryLocator::drop('Test');

        FactoryLocator::get('Test');
    }

    protected function tearDown(): void
    {
        FactoryLocator::drop('Test');
        FactoryLocator::drop('MyType');

        parent::tearDown();
    }
}
