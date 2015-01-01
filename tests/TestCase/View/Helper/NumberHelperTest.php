<?php
/**
 * NumberHelperTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\Core\App;
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
     * test CakeNumber class methods are called correctly
     */
    public function testNumberHelperProxyMethodCalls()
    {
        $methods = [
            'precision', 'toReadableSize',
        ];
        $CakeNumber = $this->getMock(__NAMESPACE__ . '\NumberMock', $methods);
        $Number = new NumberHelperTestObject($this->View, ['engine' => __NAMESPACE__ . '\NumberMock']);
        $Number->attach($CakeNumber);

        foreach ($methods as $method) {
            $CakeNumber->expects($this->at(0))->method($method);
            $Number->{$method}('who', 'what', 'when', 'where', 'how');
        }

        $CakeNumber = $this->getMock(__NAMESPACE__ . '\NumberMock', ['toPercentage']);
        $Number = new NumberHelperTestObject($this->View, ['engine' => __NAMESPACE__ . '\NumberMock']);
        $Number->attach($CakeNumber);
        $CakeNumber->expects($this->at(0))->method('toPercentage');
        $Number->toPercentage('who', 'what', ['when']);

        $CakeNumber = $this->getMock(__NAMESPACE__ . '\NumberMock', ['currency']);
        $Number = new NumberHelperTestObject($this->View, ['engine' => __NAMESPACE__ . '\NumberMock']);
        $Number->attach($CakeNumber);
        $CakeNumber->expects($this->at(0))->method('currency');
        $Number->currency('who', 'what', ['when']);

        $CakeNumber = $this->getMock(__NAMESPACE__ . '\NumberMock', ['format']);
        $Number = new NumberHelperTestObject($this->View, ['engine' => __NAMESPACE__ . '\NumberMock']);
        $Number->attach($CakeNumber);
        $CakeNumber->expects($this->at(0))->method('format');
        $Number->format('who', ['when']);

        $CakeNumber = $this->getMock(__NAMESPACE__ . '\NumberMock', ['addFormat']);
        $Number = new NumberHelperTestObject($this->View, ['engine' => __NAMESPACE__ . '\NumberMock']);
        $Number->attach($CakeNumber);
        $CakeNumber->expects($this->at(0))->method('addFormat');
        $Number->addFormat('who', ['when']);
    }

    /**
     * test engine override
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
