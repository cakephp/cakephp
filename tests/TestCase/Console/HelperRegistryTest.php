<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\HelperRegistry;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use TestApp\Command\Helper\CommandHelper;
use TestApp\Shell\Helper\SimpleHelper;
use TestPlugin\Shell\Helper\ExampleHelper;

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
        static::setAppNamespace();
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
        $this->assertInstanceOf(SimpleHelper::class, $result);
        $this->assertInstanceOf(SimpleHelper::class, $this->helpers->Simple);

        $result = $this->helpers->loaded();
        $this->assertEquals(['Simple'], $result, 'loaded() results are wrong.');
    }

    /**
     * test loading helpers.
     *
     * @return void
     */
    public function testLoadCommandNamespace()
    {
        $result = $this->helpers->load('Command');
        $this->assertInstanceOf(CommandHelper::class, $result);
        $this->assertInstanceOf(CommandHelper::class, $this->helpers->Command);

        $result = $this->helpers->loaded();
        $this->assertEquals(['Command'], $result, 'loaded() results are wrong.');
    }

    /**
     * test triggering callbacks on loaded helpers
     *
     * @return void
     */
    public function testLoadWithConfig()
    {
        $result = $this->helpers->load('Simple', ['key' => 'value']);
        $this->assertEquals('value', $result->getConfig('key'));
    }

    /**
     * test missing helper exception
     *
     * @return void
     */
    public function testLoadMissingHelper()
    {
        $this->expectException(\Cake\Console\Exception\MissingHelperException::class);
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
        $this->assertInstanceOf(SimpleHelper::class, $result);
        $this->assertInstanceOf(SimpleHelper::class, $this->helpers->SimpleAliased);

        $result = $this->helpers->loaded();
        $this->assertEquals(['SimpleAliased'], $result, 'loaded() results are wrong.');

        $result = $this->helpers->load('SomeHelper', ['className' => 'TestPlugin.Example']);
        $this->assertInstanceOf(ExampleHelper::class, $result);
        $this->assertInstanceOf(ExampleHelper::class, $this->helpers->SomeHelper);

        $result = $this->helpers->loaded();
        $this->assertEquals(['SimpleAliased', 'SomeHelper'], $result, 'loaded() results are wrong.');
    }
}
