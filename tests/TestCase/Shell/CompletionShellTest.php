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
 * @since         2.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * Class TestCompletionStringOutput
 *
 */
class TestCompletionStringOutput extends ConsoleOutput
{

    public $output = '';

    protected function _write($message)
    {
        $this->output .= $message;
    }
}

/**
 * Class CompletionShellTest
 */
class CompletionShellTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
        Plugin::load(['TestPlugin', 'TestPluginTwo']);

        $this->out = new TestCompletionStringOutput();
        $io = new ConsoleIo($this->out);

        $this->Shell = $this->getMockBuilder('Cake\Shell\CompletionShell')
            ->setMethods(['in', '_stop', 'clear'])
            ->setConstructorArgs([$io])
            ->getMock();

        $this->Shell->Command = $this->getMockBuilder('Cake\Shell\Task\CommandTask')
            ->setMethods(['in', '_stop', 'clear'])
            ->setConstructorArgs([$io])
            ->getMock();
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Shell);
        Configure::write('App.namespace', 'App');
        Plugin::unload();
    }

    /**
     * test that the startup method supresses the shell header
     *
     * @return void
     */
    public function testStartup()
    {
        $this->Shell->runCommand(['main']);
        $output = $this->out->output;

        $needle = 'Welcome to CakePHP';
        $this->assertTextNotContains($needle, $output);
    }

    /**
     * test that main displays a warning
     *
     * @return void
     */
    public function testMain()
    {
        $this->Shell->runCommand(['main']);
        $output = $this->out->output;

        $expected = "/This command is not intended to be called manually/";
        $this->assertRegExp($expected, $output);
    }

    /**
     * test commands method that list all available commands
     *
     * @return void
     */
    public function testCommands()
    {
        $this->Shell->runCommand(['commands']);
        $output = $this->out->output;

        $expected = "TestPlugin.example TestPlugin.sample TestPluginTwo.example unique welcome " .
            "cache i18n orm_cache plugin routes server i18m sample testing_dispatch\n";
        $this->assertTextEquals($expected, $output);
    }

    /**
     * test that options without argument returns nothing
     *
     * @return void
     */
    public function testOptionsNoArguments()
    {
        $this->Shell->runCommand(['options']);
        $output = $this->out->output;

        $expected = "";
        $this->assertTextEquals($expected, $output);
    }

    /**
     * test that options with a nonexisting command returns nothing
     *
     * @return void
     */
    public function testOptionsNonExistingCommand()
    {
        $this->Shell->runCommand(['options', 'foo']);
        $output = $this->out->output;
        $expected = "";
        $this->assertTextEquals($expected, $output);
    }

    /**
     * test that options with an existing command returns the proper options
     *
     * @return void
     */
    public function testOptions()
    {
        $this->Shell->runCommand(['options', 'orm_cache']);
        $output = $this->out->output;

        $expected = "--connection -c --help -h --quiet -q --verbose -v\n";
        $this->assertTextEquals($expected, $output);
    }

    /**
     * test that options with an existing command / subcommand pair returns the proper options
     *
     * @return void
     */
    public function testOptionsTask()
    {
        $this->Shell->runCommand(['options', 'sample', 'sample']);
        $output = $this->out->output;

        $expected = "--help -h --quiet -q --sample -s --verbose -v\n";
        $this->assertTextEquals($expected, $output);
    }

    /**
     * test that subCommands with a existing CORE command returns the proper sub commands
     *
     * @return void
     */
    public function testSubCommandsCorePlugin()
    {
        $this->Shell->runCommand(['subcommands', 'CORE.orm_cache']);
        $output = $this->out->output;

        $expected = "build clear\n";
        $this->assertTextEquals($expected, $output);
    }

    /**
     * test that subCommands with a existing APP command returns the proper sub commands (in this case none)
     *
     * @return void
     */
    public function testSubCommandsAppPlugin()
    {
        $this->Shell->runCommand(['subcommands', 'app.sample']);
        $output = $this->out->output;

        $expected = "derp load sample\n";
        $this->assertTextEquals($expected, $output);
    }

    /**
     * test that subCommands with an existing plugin command returns the proper sub commands
     * when the Shell name is unique and the dot notation not mandatory
     *
     * @return void
     */
    public function testSubCommandsPlugin()
    {
        $this->Shell->runCommand(['subcommands', 'welcome']);
        $output = $this->out->output;

        $expected = "say_hello\n";
        $this->assertTextEquals($expected, $output);
    }

    /**
     * test that using the dot notation when not mandatory works to provide backward compatibility
     *
     * @return void
     */
    public function testSubCommandsPluginDotNotationBackwardCompatibility()
    {
        $this->Shell->runCommand(['subcommands', 'TestPluginTwo.welcome']);
        $output = $this->out->output;

        $expected = "say_hello\n";
        $this->assertTextEquals($expected, $output);
    }

    /**
     * test that subCommands with an existing plugin command returns the proper sub commands
     *
     * @return void
     */
    public function testSubCommandsPluginDotNotation()
    {
        $this->Shell->runCommand(['subcommands', 'TestPluginTwo.example']);
        $output = $this->out->output;

        $expected = "say_hello\n";
        $this->assertTextEquals($expected, $output);
    }

    /**
     * test that subCommands with an app shell that is also defined in a plugin and without the prefix "app."
     * returns proper sub commands
     *
     * @return void
     */
    public function testSubCommandsAppDuplicatePluginNoDot()
    {
        $this->Shell->runCommand(['subcommands', 'sample']);
        $output = $this->out->output;

        $expected = "derp load sample\n";
        $this->assertTextEquals($expected, $output);
    }

    /**
     * test that subCommands with a plugin shell that is also defined in the returns proper sub commands
     *
     * @return void
     */
    public function testSubCommandsPluginDuplicateApp()
    {
        $this->Shell->runCommand(['subcommands', 'TestPlugin.sample']);
        $output = $this->out->output;

        $expected = "example\n";
        $this->assertTextEquals($expected, $output);
    }

    /**
     * test that subcommands without arguments returns nothing
     *
     * @return void
     */
    public function testSubCommandsNoArguments()
    {
        $this->Shell->runCommand(['subcommands']);
        $output = $this->out->output;

        $expected = '';
        $this->assertEquals($expected, $output);
    }

    /**
     * test that subcommands with a nonexisting command returns nothing
     *
     * @return void
     */
    public function testSubCommandsNonExistingCommand()
    {
        $this->Shell->runCommand(['subcommands', 'foo']);
        $output = $this->out->output;

        $expected = '';
        $this->assertEquals($expected, $output);
    }

    /**
     * test that subcommands returns the available subcommands for the given command
     *
     * @return void
     */
    public function testSubCommands()
    {
        $this->Shell->runCommand(['subcommands', 'orm_cache']);
        $output = $this->out->output;

        $expected = "build clear\n";
        $this->assertTextEquals($expected, $output);
    }

    /**
     * test that fuzzy returns nothing
     *
     * @return void
     */
    public function testFuzzy()
    {
        $this->Shell->runCommand(['fuzzy']);
        $output = $this->out->output;

        $expected = '';
        $this->assertEquals($expected, $output);
    }
}
