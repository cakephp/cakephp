<?php
declare(strict_types=1);

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
namespace Cake\Test\TestCase\Command;

use Cake\Console\Command;
use Cake\Filesystem\File;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * PluginLoadCommandTest class.
 */
class PluginLoadCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    protected $app;

    /**
     * @var string
     */
    protected $originalAppContent;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->app = APP . DS . 'Application.php';

        $app = new File($this->app, false);
        $this->originalAppContent = $app->read();

        $this->useCommandRunner();
        $this->setAppNamespace();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        $app = new File($this->app, false);
        $app->write($this->originalAppContent);
    }

    /**
     * Test loading the app
     *
     * @return void
     */
    public function testLoadApp()
    {
        $this->exec('plugin load TestPlugin');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $contents = file_get_contents($this->app);
        $this->assertStringContainsString("\$this->addPlugin('TestPlugin');", $contents);
    }

    /**
     * Test loading the app
     *
     * @return void
     */
    public function testLoadAppBootstrap()
    {
        $this->exec('plugin load --bootstrap TestPlugin');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $contents = file_get_contents($this->app);
        $this->assertStringContainsString("\$this->addPlugin('TestPlugin', ['bootstrap' => true]);", $contents);
    }

    /**
     * Test loading the app
     *
     * @return void
     */
    public function testLoadAppRoutes()
    {
        $this->exec('plugin load --routes TestPlugin');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $contents = file_get_contents($this->app);
        $this->assertStringContainsString("\$this->addPlugin('TestPlugin', ['routes' => true]);", $contents);
    }
}
