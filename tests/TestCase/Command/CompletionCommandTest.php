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
 * @since         2.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Command;

use Cake\Command\Command;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * CompletionCommandTest
 */
class CompletionCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();
        Configure::write('Plugins.autoload', ['TestPlugin', 'TestPluginTwo']);

        $this->useCommandRunner();
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Router::reload();
        $this->clearPlugins();
    }

    /**
     * test that the startup method suppresses the command header
     *
     * @return void
     */
    public function testStartup()
    {
        $this->exec('completion');
        $this->assertExitCode(Command::CODE_ERROR);

        $this->assertOutputNotContains('Welcome to CakePHP');
    }

    /**
     * test commands method that list all available commands
     *
     * @return void
     */
    public function testCommands()
    {
        $this->exec('completion commands');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $expected = [
            'test_plugin.example',
            'test_plugin.sample',
            'test_plugin_two.example',
            'unique',
            'welcome',
            'cache',
            'help',
            'i18n',
            'plugin',
            'routes',
            'schema_cache',
            'server',
            'version',
            'abort',
            'auto_load_model',
            'demo',
            'i18m',
            'integration',
            'merge',
            'sample',
            'shell_test',
            'testing_dispatch',
        ];
        foreach ($expected as $value) {
            $this->assertOutputContains($value);
        }
    }

    /**
     * test that options without argument returns nothing
     *
     * @return void
     */
    public function testOptionsNoArguments()
    {
        $this->exec('completion options');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputEmpty();
    }

    /**
     * test that options with a nonexistent command returns nothing
     *
     * @return void
     */
    public function testOptionsNonExistentCommand()
    {
        $this->exec('completion options foo');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputEmpty();
    }

    /**
     * test that options with an existing command returns the proper options
     *
     * @return void
     */
    public function testOptionsCommand()
    {
        $this->exec('completion options schema_cache');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $expected = [
            '--connection -c',
            '--help -h',
            '--quiet -q',
            '--verbose -v',
        ];
        foreach ($expected as $value) {
            $this->assertOutputContains($value);
        }
    }

    /**
     * test that options with an existing command / subcommand pair returns the proper options
     *
     * @return void
     */
    public function testOptionsShellTask()
    {
        //details: https://github.com/cakephp/cakephp/pull/13533
        $this->markTestIncomplete(
            'This test does not work correctly with shells.'
        );
        $this->exec('completion options sample sample');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $expected = [
            '--help -h',
            '--quiet -q',
            '--sample -s',
            '--verbose -v',
        ];
        foreach ($expected as $value) {
            $this->assertOutputContains($value);
        }
    }

    /**
     * test that options with an existing command / subcommand pair returns the proper options
     *
     * @return void
     */
    public function testOptionsSubCommand()
    {
        $this->exec('completion options cache list');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $expected = [
            '--help -h',
            '--quiet -q',
            '--verbose -v',
        ];
        foreach ($expected as $value) {
            $this->assertOutputContains($value);
        }
    }

    /**
     * test that nested command returns subcommand's options not command.
     *
     * @return void
     */
    public function testOptionsNestedCommand()
    {
        $this->exec('completion options i18n extract');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $expected = [
            '--plugin',
            '--app',
        ];
        foreach ($expected as $value) {
            $this->assertOutputContains($value);
        }
    }

    /**
     * test that subCommands with a existing CORE command returns the proper sub commands
     *
     * @return void
     */
    public function testSubCommandsCorePlugin()
    {
        $this->exec('completion subcommands schema_cache');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $expected = 'build clear';
        $this->assertOutputContains($expected);
    }

    /**
     * test that subCommands with a existing APP command returns the proper sub commands (in this case none)
     *
     * @return void
     */
    public function testSubCommandsAppPlugin()
    {
        $this->exec('completion subcommands sample');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $expected = [
            'derp',
            'returnValue',
            'sample',
            'withAbort',
        ];
        foreach ($expected as $value) {
            $this->assertOutputContains($value);
        }
        //Methods overwritten from Shell class should not be included
        $notExpected = [
            'runCommand',
            'getOptionParser',
        ];
        foreach ($notExpected as $method) {
            $this->assertOutputNotContains($method);
        }
    }

    /**
     * test that subCommands with a existing CORE command
     *
     * @return void
     */
    public function testSubCommandsCoreMultiwordCommand()
    {
        $this->exec('completion subcommands cache');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $expected = [
            'list', 'clear', 'clear_all',
        ];
        foreach ($expected as $value) {
            $this->assertOutputContains($value);
        }
    }

    /**
     * test that subCommands with an existing plugin command returns the proper sub commands
     * when the Shell name is unique and the dot notation not mandatory
     *
     * @return void
     */
    public function testSubCommandsPlugin()
    {
        $this->exec('completion subcommands welcome');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $expected = 'say_hello';
        $this->assertOutputContains($expected);
    }

    /**
     * test that using the dot notation when not mandatory works to provide backward compatibility
     *
     * @return void
     */
    public function testSubCommandsPluginDotNotationBackwardCompatibility()
    {
        $this->exec('completion subcommands test_plugin_two.welcome');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $expected = 'say_hello';
        $this->assertOutputContains($expected);
    }

    /**
     * test that subCommands with an existing plugin command returns the proper sub commands
     *
     * @return void
     */
    public function testSubCommandsPluginDotNotation()
    {
        $this->exec('completion subcommands test_plugin_two.example');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $expected = 'say_hello';
        $this->assertOutputContains($expected);
    }

    /**
     * test that subCommands with an app shell that is also defined in a plugin and without the prefix "app."
     * returns proper sub commands
     *
     * @return void
     */
    public function testSubCommandsAppDuplicatePluginNoDot()
    {
        $this->exec('completion subcommands sample');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $expected = [
            'derp',
            'returnValue',
            'sample',
            'withAbort',
        ];
        foreach ($expected as $value) {
            $this->assertOutputContains($value);
        }
    }

    /**
     * test that subCommands with a plugin shell that is also defined in the returns proper sub commands
     *
     * @return void
     */
    public function testSubCommandsPluginDuplicateApp()
    {
        $this->exec('completion subcommands test_plugin.sample');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $expected = 'example';
        $this->assertOutputContains($expected);
    }

    /**
     * test that subcommands without arguments returns nothing
     *
     * @return void
     */
    public function testSubCommandsNoArguments()
    {
        $this->exec('completion subcommands');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $this->assertOutputEmpty();
    }

    /**
     * test that subcommands with a nonexistent command returns nothing
     *
     * @return void
     */
    public function testSubCommandsNonExistentCommand()
    {
        $this->exec('completion subcommands foo');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $this->assertOutputEmpty();
    }

    /**
     * test that subcommands returns the available subcommands for the given command
     *
     * @return void
     */
    public function testSubCommands()
    {
        $this->exec('completion subcommands schema_cache');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $expected = 'build clear';
        $this->assertOutputContains($expected);
    }

    /**
     * test that fuzzy returns nothing
     *
     * @return void
     */
    public function testFuzzy()
    {
        $this->exec('completion fuzzy');
        $this->assertOutputEmpty();
    }

    /**
     * test that help returns content
     *
     * @return void
     */
    public function testHelp()
    {
        $this->exec('completion --help');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $this->assertOutputContains('Output a list of available commands');
        $this->assertOutputContains('Output a list of available sub-commands');
    }
}
