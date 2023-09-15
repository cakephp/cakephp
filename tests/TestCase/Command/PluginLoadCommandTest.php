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

use Cake\Console\CommandInterface;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Routing\Router;
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
    protected $configFile;

    /**
     * @var string
     */
    protected $originalContent;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->configFile = CONFIG . 'plugins.php';
        $this->originalContent = file_get_contents($this->configFile);

        $this->setAppNamespace();
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();

        file_put_contents($this->configFile, $this->originalContent);
    }

    /**
     * Test generating help succeeds
     */
    public function testHelp(): void
    {
        $this->exec('plugin load --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('plugin load');
    }

    /**
     * Test loading a plugin modifies the config file
     */
    public function testLoad(): void
    {
        $this->exec('plugin load TestPlugin');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);

        // Needed to not have duplicate named routes
        Router::reload();
        $this->exec('plugin load TestPluginTwo --no-bootstrap --no-console --no-middleware --no-routes --no-services');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);

        // Needed to not have duplicate named routes
        Router::reload();
        $this->exec('plugin load Company/TestPluginThree --only-debug --only-cli');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);

        $config = include $this->configFile;
        $this->assertTrue(isset($config['TestPlugin']));
        $this->assertTrue(isset($config['TestPluginTwo']));
        $this->assertTrue(isset($config['Company/TestPluginThree']));
        $this->assertSame(['onlyDebug' => true, 'onlyCli' => true], $config['Company/TestPluginThree']);
        $this->assertSame(
            ['bootstrap' => false, 'console' => false, 'middleware' => false, 'routes' => false, 'services' => false],
            $config['TestPluginTwo']
        );
    }

    /**
     * Test loading an unknown plugin
     */
    public function testLoadUnknownPlugin(): void
    {
        $this->exec('plugin load NopeNotThere');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
        $this->assertErrorContains('Plugin `NopeNotThere` could not be found');

        $config = include $this->configFile;
        $this->assertFalse(isset($config['NopeNotThere']));
    }

    /**
     * Test loading optional plugin
     */
    public function testLoadOptionalPlugin(): void
    {
        $this->exec('plugin load NopeNotThere --optional');

        $config = include $this->configFile;
        $this->assertTrue(isset($config['NopeNotThere']));
        $this->assertSame(['optional' => true], $config['NopeNotThere']);
    }
}
