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

use Cake\Command\Command;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
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
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->app = APP . DS . 'Application.php';
        $this->originalAppContent = file_get_contents($this->app);

        $this->useCommandRunner();
        $this->setAppNamespace();
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();

        file_put_contents($this->app, $this->originalAppContent);
    }

    /**
     * Test generating help succeeds
     */
    public function testHelp(): void
    {
        $this->exec('plugin load --help');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('plugin load');
    }

    /**
     * Test loading a plugin modifies the app
     */
    public function testLoadModifiesApplication(): void
    {
        $this->exec('plugin load TestPlugin');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $contents = file_get_contents($this->app);
        $this->assertMatchesRegularExpression('/Check plugins added here\n {8}\$this->addPlugin\(\'TestPlugin\'\);\n {4}\}\n/u', $contents);
    }

    /**
     * Test loading an unknown plugin
     */
    public function testLoadUnknownPlugin(): void
    {
        $this->exec('plugin load NopeNotThere');
        $this->assertExitCode(Command::CODE_ERROR);
        $this->assertErrorContains('Plugin NopeNotThere could not be found');

        $contents = file_get_contents($this->app);
        $this->assertStringNotContainsString("\$this->addPlugin('NopeNotThere');", $contents);
    }
}
