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
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\HelperRegistry;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * HelperRegistryTest
 */
class HelperRegistryTest extends TestCase
{

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')
            ->disableOriginalConstructor()
            ->getMock();
        $this->helpers = new HelperRegistry();
        $this->helpers->setIo($io);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->helpers);
        parent::tearDown();
    }

    /**
     * test loading helpers.
     *
     * @return void
     */
    public function testLoad()
    {
        $result = $this->helpers->load('Simple');
        $this->assertInstanceOf('TestApp\Shell\Helper\SimpleHelper', $result);
        $this->assertInstanceOf('TestApp\Shell\Helper\SimpleHelper', $this->helpers->Simple);

        $result = $this->helpers->loaded();
        $this->assertEquals(['Simple'], $result, 'loaded() results are wrong.');
    }

    /**
     * test triggering callbacks on loaded helpers
     *
     * @return void
     */
    public function testLoadWithConfig()
    {
        $result = $this->helpers->load('Simple', ['key' => 'value']);
        $this->assertEquals('value', $result->config('key'));
    }

    /**
     * test missing helper exception
     *
     * @expectedException \Cake\Console\Exception\MissingHelperException
     * @return void
     */
    public function testLoadMissingHelper()
    {
        $this->helpers->load('ThisTaskShouldAlwaysBeMissing');
    }

    /**
     * Tests loading as an alias
     *
     * @return void
     */
    public function testLoadWithAlias()
    {
        Plugin::load('TestPlugin');

        $result = $this->helpers->load('SimpleAliased', ['className' => 'Simple']);
        $this->assertInstanceOf('TestApp\Shell\Helper\SimpleHelper', $result);
        $this->assertInstanceOf('TestApp\Shell\Helper\SimpleHelper', $this->helpers->SimpleAliased);

        $result = $this->helpers->loaded();
        $this->assertEquals(['SimpleAliased'], $result, 'loaded() results are wrong.');

        $result = $this->helpers->load('SomeHelper', ['className' => 'TestPlugin.Example']);
        $this->assertInstanceOf('TestPlugin\Shell\Helper\ExampleHelper', $result);
        $this->assertInstanceOf('TestPlugin\Shell\Helper\ExampleHelper', $this->helpers->SomeHelper);

        $result = $this->helpers->loaded();
        $this->assertEquals(['SimpleAliased', 'SomeHelper'], $result, 'loaded() results are wrong.');
    }
}
