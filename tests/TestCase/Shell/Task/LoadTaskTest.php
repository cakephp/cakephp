<?php
/**
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell\Task;

use Cake\Console\Shell;
use Cake\Filesystem\File;
use Cake\TestSuite\ConsoleIntegrationTestCase;

/**
 * LoadTaskTest class.
 */
class LoadTaskTest extends ConsoleIntegrationTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->app = APP . DS . 'Application.php';
        $this->bootstrap = ROOT . DS . 'config' . DS . 'bootstrap.php';
        $this->bootstrapCli = ROOT . DS . 'config' . DS . 'bootstrap_cli.php';
        copy($this->bootstrap, $this->bootstrapCli);

        $bootstrap = new File($this->bootstrap, false);
        $this->originalBootstrapContent = $bootstrap->read();

        $app = new File($this->app, false);
        $this->originalAppContent = $app->read();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        $bootstrap = new File($this->bootstrap, false);
        $bootstrap->write($this->originalBootstrapContent);
        unlink($this->bootstrapCli);

        $app = new File($this->app, false);
        $app->write($this->originalAppContent);
    }

    /**
     * testLoad
     *
     * @return void
     */
    public function testLoad()
    {
        $this->exec('plugin load --no_app --autoload TestPlugin');
        $this->assertExitCode(Shell::CODE_SUCCESS);

        $contents = file_get_contents($this->bootstrap);
        $this->assertContains(
            "Plugin::load('TestPlugin', ['autoload' => true]);",
            $contents
        );
    }

    /**
     * testLoadWithBootstrap
     *
     * @return void
     */
    public function testLoadWithBootstrap()
    {
        $this->exec('plugin load --no_app --bootstrap --autoload TestPlugin');
        $this->assertExitCode(Shell::CODE_SUCCESS);

        $contents = file_get_contents($this->bootstrap);
        $this->assertContains(
            "Plugin::load('TestPlugin', ['autoload' => true, 'bootstrap' => true]);",
            $contents
        );
    }

    /**
     * Tests that loading with bootstrap_cli works.
     *
     * @return void
     */
    public function testLoadBootstrapCli()
    {
        $this->exec('plugin load --no_app --cli TestPlugin');
        $this->assertExitCode(Shell::CODE_SUCCESS);

        $contents = file_get_contents($this->bootstrapCli);
        $this->assertContains(
            "Plugin::load('TestPlugin');",
            $contents
        );
    }

    /**
     * testLoadWithRoutes
     *
     * @return void
     */
    public function testLoadWithRoutes()
    {
        $this->exec('plugin load --no_app --routes --autoload TestPlugin');
        $this->assertExitCode(Shell::CODE_SUCCESS);

        $contents = file_get_contents($this->bootstrap);
        $this->assertContains(
            "Plugin::load('TestPlugin', ['autoload' => true, 'routes' => true]);",
            $contents
        );
    }

    /**
     * test load no autoload
     *
     * @return void
     */
    public function testLoadNoAutoload()
    {
        $this->exec('plugin load --no_app --routes TestPlugin');
        $this->assertExitCode(Shell::CODE_SUCCESS);

        $contents = file_get_contents($this->bootstrap);
        $this->assertContains("Plugin::load('TestPlugin', ['routes' => true]);", $contents);
    }

    /**
     * testLoad
     *
     * @return void
     */
    public function testLoadNothing()
    {
        $this->exec('plugin load --no_app TestPlugin');
        $this->assertExitCode(Shell::CODE_SUCCESS);

        $contents = file_get_contents($this->bootstrap);
        $this->assertContains("Plugin::load('TestPlugin');", $contents);
    }

    /**
     * Test loading the app
     *
     * @return void
     */
    public function testLoadApp()
    {
        $this->exec('plugin load TestPlugin');
        $this->assertExitCode(Shell::CODE_SUCCESS);

        $contents = file_get_contents($this->app);
        $this->assertContains("\$this->addPlugin('TestPlugin');", $contents);
    }

    /**
     * Test loading the app
     *
     * @return void
     */
    public function testLoadAppBootstrap()
    {
        $this->exec('plugin load --bootstrap TestPlugin');
        $this->assertExitCode(Shell::CODE_SUCCESS);

        $contents = file_get_contents($this->app);
        $this->assertContains("\$this->addPlugin('TestPlugin', ['bootstrap' => true]);", $contents);
    }

    /**
     * Test loading the app
     *
     * @return void
     */
    public function testLoadAppRoutes()
    {
        $this->exec('plugin load --routes TestPlugin');
        $this->assertExitCode(Shell::CODE_SUCCESS);

        $contents = file_get_contents($this->app);
        $this->assertContains("\$this->addPlugin('TestPlugin', ['routes' => true]);", $contents);
    }
}
