<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\Shell;
use Cake\Console\ShellDispatcher;
use Cake\TestSuite\TestCase;

/**
 * ShellDispatcherTest
 */
class ShellDispatcherTest extends TestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->loadPlugins(['TestPlugin', 'Company/TestPluginThree']);
        static::setAppNamespace();
        $this->dispatcher = $this->getMockBuilder('Cake\Console\ShellDispatcher')
            ->addMethods(['_stop'])
            ->getMock();
    }

    /**
     * teardown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        ShellDispatcher::resetAliases();
        $this->clearPlugins();
    }

    /**
     * Test error on missing shell
     *
     * @return void
     */
    public function testFindShellMissing()
    {
        $this->expectException(\Cake\Console\Exception\MissingShellException::class);
        $this->dispatcher->findShell('nope');
    }

    /**
     * Test error on missing plugin shell
     *
     * @return void
     */
    public function testFindShellMissingPlugin()
    {
        $this->expectException(\Cake\Console\Exception\MissingShellException::class);
        $this->dispatcher->findShell('test_plugin.nope');
    }

    /**
     * Verify loading of (plugin-) shells
     *
     * @return void
     */
    public function testFindShell()
    {
        $result = $this->dispatcher->findShell('sample');
        $this->assertInstanceOf('TestApp\Shell\SampleShell', $result);

        $result = $this->dispatcher->findShell('test_plugin.example');
        $this->assertInstanceOf('TestPlugin\Shell\ExampleShell', $result);
        $this->assertSame('TestPlugin', $result->plugin);
        $this->assertSame('Example', $result->name);

        $result = $this->dispatcher->findShell('TestPlugin.example');
        $this->assertInstanceOf('TestPlugin\Shell\ExampleShell', $result);

        $result = $this->dispatcher->findShell('TestPlugin.Example');
        $this->assertInstanceOf('TestPlugin\Shell\ExampleShell', $result);
    }

    /**
     * testAddShortPluginAlias
     *
     * @return void
     */
    public function testAddShortPluginAlias()
    {
        $expected = [
            'Company' => 'Company/TestPluginThree.company',
            'Example' => 'TestPlugin.example',
        ];
        $result = $this->dispatcher->addShortPluginAliases();
        $this->assertSame($expected, $result, 'Should return the list of aliased plugin shells');

        ShellDispatcher::alias('Example', 'SomeOther.PluginsShell');
        $expected = [
            'Company' => 'Company/TestPluginThree.company',
            'Example' => 'SomeOther.PluginsShell',
        ];
        $result = $this->dispatcher->addShortPluginAliases();
        $this->assertSame($expected, $result, 'Should not overwrite existing aliases');
    }

    /**
     * Test getting shells with aliases.
     *
     * @return void
     */
    public function testFindShellAliased()
    {
        ShellDispatcher::alias('short', 'test_plugin.example');

        $result = $this->dispatcher->findShell('short');
        $this->assertInstanceOf('TestPlugin\Shell\ExampleShell', $result);
        $this->assertSame('TestPlugin', $result->plugin);
        $this->assertSame('Example', $result->name);
    }

    /**
     * Test finding a shell that has a matching alias.
     *
     * Aliases should not overload concrete shells.
     *
     * @return void
     */
    public function testFindShellAliasedAppShadow()
    {
        ShellDispatcher::alias('sample', 'test_plugin.example');

        $result = $this->dispatcher->findShell('sample');
        $this->assertInstanceOf('TestApp\Shell\SampleShell', $result);
        $this->assertEmpty($result->plugin);
        $this->assertSame('Sample', $result->name);
    }

    /**
     * Verify dispatch handling stop errors
     *
     * @return void
     */
    public function testDispatchShellWithAbort()
    {
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->addMethods(['main'])
            ->setConstructorArgs([$io])
            ->getMock();
        $shell->expects($this->once())
            ->method('main')
            ->will($this->returnCallback(function () use ($shell) {
                $shell->abort('Bad things', 99);
            }));

        $dispatcher = $this->getMockBuilder('Cake\Console\ShellDispatcher')
            ->onlyMethods(['findShell'])
            ->getMock();
        $dispatcher->expects($this->any())
            ->method('findShell')
            ->with('aborter')
            ->will($this->returnValue($shell));

        $dispatcher->args = ['aborter'];
        $result = $dispatcher->dispatch();
        $this->assertSame(99, $result, 'Should return the exception error code.');
    }

    /**
     * Verify correct dispatch of Shell subclasses with a main method
     *
     * @return void
     */
    public function testDispatchShellWithMain()
    {
        $dispatcher = $this->getMockBuilder('Cake\Console\ShellDispatcher')
            ->onlyMethods(['findShell'])
            ->getMock();
        $Shell = $this->getMockBuilder('Cake\Console\Shell')
            ->disableOriginalConstructor()
            ->getMock();

        $Shell->expects($this->exactly(2))->method('initialize');
        $Shell->expects($this->exactly(2))
            ->method('runCommand')
            ->will($this->returnValue(null));

        $dispatcher->expects($this->any())
            ->method('findShell')
            ->with('mock_with_main')
            ->will($this->returnValue($Shell));

        $dispatcher->args = ['mock_with_main'];
        $result = $dispatcher->dispatch();
        $this->assertSame(Shell::CODE_SUCCESS, $result);
        $this->assertEquals([], $dispatcher->args);

        $dispatcher->args = ['mock_with_main'];
        $result = $dispatcher->dispatch();
        $this->assertSame(Shell::CODE_SUCCESS, $result);
        $this->assertEquals([], $dispatcher->args);
    }

    /**
     * Verifies correct dispatch of Shell subclasses with integer exit codes.
     *
     * @return void
     */
    public function testDispatchShellWithIntegerSuccessCode()
    {
        $dispatcher = $this->getMockBuilder('Cake\Console\ShellDispatcher')
            ->onlyMethods(['findShell'])
            ->getMock();
        $Shell = $this->getMockBuilder('Cake\Console\Shell')
            ->disableOriginalConstructor()
            ->getMock();

        $Shell->expects($this->once())->method('initialize');
        $Shell->expects($this->once())->method('runCommand')
            ->with(['initdb'])
            ->will($this->returnValue(Shell::CODE_SUCCESS));

        $dispatcher->expects($this->any())
            ->method('findShell')
            ->with('mock_without_main')
            ->will($this->returnValue($Shell));

        $dispatcher->args = ['mock_without_main', 'initdb'];
        $result = $dispatcher->dispatch();
        $this->assertSame(Shell::CODE_SUCCESS, $result);
    }

    /**
     * Verifies correct dispatch of Shell subclasses with custom integer exit codes.
     *
     * @return void
     */
    public function testDispatchShellWithCustomIntegerCodes()
    {
        $customErrorCode = 3;

        $dispatcher = $this->getMockBuilder('Cake\Console\ShellDispatcher')
            ->onlyMethods(['findShell'])
            ->getMock();
        $Shell = $this->getMockBuilder('Cake\Console\Shell')
            ->disableOriginalConstructor()
            ->getMock();

        $Shell->expects($this->once())->method('initialize');
        $Shell->expects($this->once())->method('runCommand')
            ->with(['initdb'])
            ->will($this->returnValue($customErrorCode));

        $dispatcher->expects($this->any())
            ->method('findShell')
            ->with('mock_without_main')
            ->will($this->returnValue($Shell));

        $dispatcher->args = ['mock_without_main', 'initdb'];
        $result = $dispatcher->dispatch();
        $this->assertSame($customErrorCode, $result);
    }

    /**
     * Verify correct dispatch of Shell subclasses without a main method
     *
     * @return void
     */
    public function testDispatchShellWithoutMain()
    {
        $dispatcher = $this->getMockBuilder('Cake\Console\ShellDispatcher')
            ->onlyMethods(['findShell'])
            ->getMock();
        $Shell = $this->getMockBuilder('Cake\Console\Shell')
            ->disableOriginalConstructor()
            ->getMock();

        $Shell->expects($this->once())->method('initialize');
        $Shell->expects($this->once())->method('runCommand')
            ->with(['initdb'])
            ->will($this->returnValue(true));

        $dispatcher->expects($this->any())
            ->method('findShell')
            ->with('mock_without_main')
            ->will($this->returnValue($Shell));

        $dispatcher->args = ['mock_without_main', 'initdb'];
        $result = $dispatcher->dispatch();
        $this->assertSame(Shell::CODE_SUCCESS, $result);
    }

    /**
     * Verify you can dispatch a plugin's main shell with the shell name alone
     *
     * @return void
     */
    public function testDispatchShortPluginAlias()
    {
        $dispatcher = $this->getMockBuilder('Cake\Console\ShellDispatcher')
            ->onlyMethods(['_shellExists', '_createShell'])
            ->getMock();
        $Shell = $this->getMockBuilder('Cake\Console\Shell')
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher->expects($this->exactly(2))
            ->method('_shellExists')
            ->withConsecutive(['example'], ['TestPlugin.Example'])
            ->will($this->onConsecutiveCalls(null, 'TestPlugin\Console\Command\TestPluginShell'));

        $dispatcher->expects($this->once())
            ->method('_createShell')
            ->with('TestPlugin\Console\Command\TestPluginShell', 'TestPlugin.Example')
            ->will($this->returnValue($Shell));

        $dispatcher->args = ['example'];
        $result = $dispatcher->dispatch();
        $this->assertSame(Shell::CODE_SUCCESS, $result);
    }

    /**
     * Ensure short plugin shell usage is case/camelized insensitive
     *
     * @return void
     */
    public function testDispatchShortPluginAliasCamelized()
    {
        $dispatcher = $this->getMockBuilder('Cake\Console\ShellDispatcher')
            ->onlyMethods(['_shellExists', '_createShell'])
            ->getMock();
        $Shell = $this->getMockBuilder('Cake\Console\Shell')
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher->expects($this->exactly(2))
            ->method('_shellExists')
            ->withConsecutive(['Example'], ['TestPlugin.Example'])
            ->will($this->onConsecutiveCalls(null, 'TestPlugin\Console\Command\TestPluginShell'));

        $dispatcher->expects($this->once())
            ->method('_createShell')
            ->with('TestPlugin\Console\Command\TestPluginShell', 'TestPlugin.Example')
            ->will($this->returnValue($Shell));

        $dispatcher->args = ['Example'];
        $result = $dispatcher->dispatch();
        $this->assertSame(Shell::CODE_SUCCESS, $result);
    }

    /**
     * Verify that in case of conflict, app shells take precedence in alias list
     *
     * @return void
     */
    public function testDispatchShortPluginAliasConflict()
    {
        $dispatcher = $this->getMockBuilder('Cake\Console\ShellDispatcher')
            ->onlyMethods(['_shellExists', '_createShell'])
            ->getMock();
        $Shell = $this->getMockBuilder('Cake\Console\Shell')
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher->expects($this->once())
            ->method('_shellExists')
            ->with('sample')
            ->will($this->returnValue('App\Shell\SampleShell'));

        $dispatcher->expects($this->once())
            ->method('_createShell')
            ->with('App\Shell\SampleShell', 'sample')
            ->will($this->returnValue($Shell));

        $dispatcher->args = ['sample'];
        $result = $dispatcher->dispatch();
        $this->assertSame(Shell::CODE_SUCCESS, $result);
    }

    /**
     * Verify shifting of arguments
     *
     * @return void
     */
    public function testShiftArgs()
    {
        $this->dispatcher->args = ['a', 'b', 'c'];
        $this->assertSame('a', $this->dispatcher->shiftArgs());
        $this->assertSame($this->dispatcher->args, ['b', 'c']);

        $this->dispatcher->args = ['a' => 'b', 'c', 'd'];
        $this->assertSame('b', $this->dispatcher->shiftArgs());
        $this->assertSame($this->dispatcher->args, ['c', 'd']);

        $this->dispatcher->args = ['a', 'b' => 'c', 'd'];
        $this->assertSame('a', $this->dispatcher->shiftArgs());
        $this->assertSame($this->dispatcher->args, ['b' => 'c', 'd']);

        $this->dispatcher->args = [0 => 'a', 2 => 'b', 30 => 'c'];
        $this->assertSame('a', $this->dispatcher->shiftArgs());
        $this->assertSame($this->dispatcher->args, [0 => 'b', 1 => 'c']);

        $this->dispatcher->args = [];
        $this->assertNull($this->dispatcher->shiftArgs());
        $this->assertSame([], $this->dispatcher->args);
    }

    /**
     * Test how `bin/cake --help` works.
     *
     * @return void
     */
    public function testHelpOption()
    {
        $this->expectWarning();
        $dispatcher = $this->getMockBuilder('Cake\Console\ShellDispatcher')
            ->addMethods(['_stop'])
            ->getMock();
        $dispatcher->args = ['--help'];
        $dispatcher->dispatch();
    }

    /**
     * Test how `bin/cake --version` works.
     *
     * @return void
     */
    public function testVersionOption()
    {
        $this->expectWarning();
        $dispatcher = $this->getMockBuilder('Cake\Console\ShellDispatcher')
            ->addMethods(['_stop'])
            ->getMock();
        $dispatcher->args = ['--version'];
        $dispatcher->dispatch();
    }
}
