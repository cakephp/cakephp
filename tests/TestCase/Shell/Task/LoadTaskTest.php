<?php
/**
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell\Task;

use Cake\Core\Plugin;
use Cake\Filesystem\File;
use Cake\TestSuite\TestCase;

/**
 * LoadTaskTest class.
 *
 */
class LoadTaskTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')
            ->disableOriginalConstructor()
            ->getMock();

        $this->Task = $this->getMockBuilder('Cake\Shell\Task\LoadTask')
            ->setMethods(['in', 'out', 'err', '_stop'])
            ->setConstructorArgs([$this->io])
            ->getMock();

        $this->bootstrap = ROOT . DS . 'config' . DS . 'bootstrap.php';

        $bootstrap = new File($this->bootstrap, false);
        $this->originalBootstrapContent = $bootstrap->read();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->shell);
        Plugin::unload();

        $bootstrap = new File($this->bootstrap, false);
        $bootstrap->write($this->originalBootstrapContent);
    }

    /**
     * testLoad
     *
     * @return void
     */
    public function testLoad()
    {
        $this->Task->params = [
            'bootstrap' => false,
            'routes' => false,
            'autoload' => true,
        ];

        $action = $this->Task->main('TestPlugin');

        $this->assertTrue($action);

        $expected = "Plugin::load('TestPlugin', ['autoload' => true]);";
        $bootstrap = new File($this->bootstrap, false);
        $this->assertContains($expected, $bootstrap->read());
    }

    /**
     * testLoadWithBootstrap
     *
     * @return void
     */
    public function testLoadWithBootstrap()
    {
        $this->Task->params = [
            'bootstrap' => true,
            'routes' => false,
            'autoload' => true,
        ];

        $action = $this->Task->main('TestPlugin');

        $this->assertTrue($action);

        $expected = "Plugin::load('TestPlugin', ['autoload' => true, 'bootstrap' => true]);";
        $bootstrap = new File($this->bootstrap, false);
        $this->assertContains($expected, $bootstrap->read());
    }

    /**
     * testLoadWithRoutes
     *
     * @return void
     */
    public function testLoadWithRoutes()
    {
        $this->Task->params = [
            'bootstrap' => false,
            'routes' => true,
            'autoload' => true,
        ];

        $action = $this->Task->main('TestPlugin');

        $this->assertTrue($action);

        $expected = "Plugin::load('TestPlugin', ['autoload' => true, 'routes' => true]);";
        $bootstrap = new File($this->bootstrap, false);
        $this->assertContains($expected, $bootstrap->read());
    }

    /**
     * test load no autoload
     *
     * @return void
     */
    public function testLoadNoAutoload()
    {
        $this->Task->params = [
            'bootstrap' => false,
            'routes' => true,
            'autoload' => false,
        ];

        $action = $this->Task->main('TestPlugin');

        $this->assertTrue($action);

        $expected = "Plugin::load('TestPlugin', ['routes' => true]);";
        $bootstrap = new File($this->bootstrap, false);
        $this->assertContains($expected, $bootstrap->read());
    }

    /**
     * testLoad
     *
     * @return void
     */
    public function testLoadNothing()
    {
        $this->Task->params = [
            'bootstrap' => false,
            'routes' => false,
            'autoload' => false,
        ];

        $action = $this->Task->main('TestPlugin');

        $this->assertTrue($action);

        $expected = "Plugin::load('TestPlugin');";
        $bootstrap = new File($this->bootstrap, false);
        $this->assertContains($expected, $bootstrap->read());
    }
}
