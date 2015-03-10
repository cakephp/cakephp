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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Shell\Task;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\Shell\PluginAssetsTask;
use Cake\TestSuite\TestCase;
use Cake\Filesystem\File;

/**
 * ExtractTaskTest class
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

        $this->io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);

        $this->Task = $this->getMock(
                'Cake\Shell\Task\LoadTask', ['in', 'out', 'err', '_stop'], [$this->io]
        );

        $this->bootstrap = ROOT . DS . 'config' . DS . 'bootstrap.php';

        $bootstrap = new File($this->bootstrap, false);

        $this->original_bootstrap_content = $bootstrap->read();
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

        $bootstrap->write($this->original_bootstrap_content);
    }

    /**
     * testLoad
     *
     * @return void
     */
    public function testLoad()
    {
        Plugin::load('Company/TestPluginThree');

        $action = $this->Task->main('TestPlugin');

        $this->assertTrue($action);

        $expected = "Plugin::load('TestPlugin', ['autoload' => true, 'bootstrap' => false, 'routes' => false]);";
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
        Plugin::load('Company/TestPluginThree');

        $this->Task->params = [
            'bootstrap' => true,
            'routes'    => false,
        ];

        $action = $this->Task->main('TestPlugin');

        $this->assertTrue($action);

        $expected = "Plugin::load('TestPlugin', ['autoload' => true, 'bootstrap' => true, 'routes' => false]);";
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
        Plugin::load('Company/TestPluginThree');

        $this->Task->params = [
            'bootstrap' => false,
            'routes'    => true,
        ];

        $action = $this->Task->main('TestPlugin');

        $this->assertTrue($action);

        $expected = "Plugin::load('TestPlugin', ['autoload' => true, 'bootstrap' => false, 'routes' => true]);";
        $bootstrap = new File($this->bootstrap, false);
        $this->assertContains($expected, $bootstrap->read());
    }

    /**
     * testLoadNoName
     *
     * @return void
     */
    public function testLoadNoName()
    {
        Plugin::load('Company/TestPluginThree');

        $this->Task->params = [
            'bootstrap' => false,
            'routes'    => true,
        ];

        $action = $this->Task->main();

        $this->assertFalse($action);

        $expected = "Plugin::load(";
        $bootstrap = new File($this->bootstrap, false);
        $this->assertNotContains($expected, $bootstrap->read());
    }

}
