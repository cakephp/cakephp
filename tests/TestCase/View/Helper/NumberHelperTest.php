<?php
declare(strict_types=1);

/**
 * NumberHelperTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\I18n\Number;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\NumberHelper;
use Cake\View\View;
use ReflectionMethod;
use TestApp\Utility\NumberMock;
use TestApp\Utility\TestAppEngine;
use TestApp\View\Helper\NumberHelperTestObject;
use TestPlugin\Utility\TestPluginEngine;

/**
 * NumberHelperTest class
 */
class NumberHelperTest extends TestCase
{
    /**
     * @var \Cake\View\View
     */
    protected $View;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->View = new View();

        $this->_appNamespace = Configure::read('App.namespace');
        static::setAppNamespace();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
        static::setAppNamespace($this->_appNamespace);
        unset($this->View);
    }

    /**
     * Provider for method proxying.
     *
     * @return array
     */
    public function methodProvider()
    {
        return [
            ['precision'],
            ['toReadableSize'],
            ['toPercentage'],
            ['currency'],
            ['format'],
            ['formatDelta'],
            ['ordinal'],
        ];
    }

    /**
     * test CakeNumber class methods are called correctly
     *
     * @dataProvider methodProvider
     * @return void
     */
    public function testNumberHelperProxyMethodCalls($method)
    {
        $number = $this->getMockBuilder(NumberMock::class)
            ->addMethods([$method])
            ->getMock();
        $helper = new NumberHelperTestObject($this->View, ['engine' => NumberMock::class]);
        $helper->attach($number);
        $number->expects($this->once())
            ->method($method)
            ->with(12.3)
            ->willReturn('');
        $helper->{$method}(12.3);
    }

    /**
     * Test that number of argument of helper's proxy methods matches
     * corresponding method of Number class.
     *
     * @dataProvider methodProvider
     * @return void
     */
    public function testParameterCountMatch($method)
    {
        $numberMethod = new ReflectionMethod(Number::class, $method);
        $helperMethod = new ReflectionMethod(NumberHelper::class, $method);

        $this->assertSame($numberMethod->getNumberOfParameters(), $helperMethod->getNumberOfParameters());
    }

    /**
     * test engine override
     *
     * @return void
     */
    public function testEngineOverride()
    {
        $Number = new NumberHelperTestObject($this->View, ['engine' => 'TestAppEngine']);
        $this->assertInstanceOf(TestAppEngine::class, $Number->engine());

        $this->loadPlugins(['TestPlugin']);
        $Number = new NumberHelperTestObject($this->View, ['engine' => 'TestPlugin.TestPluginEngine']);
        $this->assertInstanceOf(TestPluginEngine::class, $Number->engine());
        $this->removePlugins(['TestPlugin']);
    }
}
