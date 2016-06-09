<?php
/**
 * NumberHelperTest file
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\NumberHelper;
use Cake\View\View;

/**
 * NumberHelperTestObject class
 */
class NumberHelperTestObject extends NumberHelper
{

    public function attach(NumberMock $cakeNumber)
    {
        $this->_engine = $cakeNumber;
    }

    public function engine()
    {
        return $this->_engine;
    }
}

/**
 * NumberMock class
 */
class NumberMock
{
}

/**
 * NumberHelperTest class
 *
 */
class NumberHelperTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->View = new View();

        $this->_appNamespace = Configure::read('App.namespace');
        Configure::write('App.namespace', 'TestApp');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Configure::write('App.namespace', $this->_appNamespace);
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
            ['addFormat'],
            ['formatDelta'],
            ['defaultCurrency'],
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
        $number = $this->getMockBuilder(__NAMESPACE__ . '\NumberMock')
            ->setMethods([$method])
            ->getMock();
        $helper = new NumberHelperTestObject($this->View, ['engine' => __NAMESPACE__ . '\NumberMock']);
        $helper->attach($number);
        $number->expects($this->at(0))
            ->method($method)
            ->with(12.3);
        $helper->{$method}(12.3, ['options']);
    }

    /**
     * test engine override
     *
     * @return void
     */
    public function testEngineOverride()
    {
        $Number = new NumberHelperTestObject($this->View, ['engine' => 'TestAppEngine']);
        $this->assertInstanceOf('TestApp\Utility\TestAppEngine', $Number->engine());

        Plugin::load('TestPlugin');
        $Number = new NumberHelperTestObject($this->View, ['engine' => 'TestPlugin.TestPluginEngine']);
        $this->assertInstanceOf('TestPlugin\Utility\TestPluginEngine', $Number->engine());
        Plugin::unload('TestPlugin');
    }
}
