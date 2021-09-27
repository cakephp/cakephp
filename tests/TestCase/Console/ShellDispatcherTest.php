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
     * @var \Cake\Console\ShellDispatcher|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dispatcher;

    /**
     * setUp method
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
     */
    public function tearDown(): void
    {
        parent::tearDown();
        ShellDispatcher::resetAliases();
        $this->clearPlugins();
    }

    /**
     * Test error on missing shell
     */
    public function testFindShellMissing(): void
    {
        $this->expectException(\Cake\Console\Exception\MissingShellException::class);
        $this->dispatcher->findShell('nope');
    }

    /**
     * Test error on missing plugin shell
     */
    public function testFindShellMissingPlugin(): void
    {
        $this->expectException(\Cake\Console\Exception\MissingShellException::class);
        $this->dispatcher->findShell('test_plugin.nope');
    }

    /**
     * Verify loading of (plugin-) shells
     */
    public function testFindShell(): void
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
     */
    public function testAddShortPluginAlias(): void
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
     */
    public function testFindShellAliased(): void
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
     */
    public function testFindShellAliasedAppShadow(): void
    {
        ShellDispatcher::alias('sample', 'test_plugin.example');

        $result = $this->dispatcher->findShell('sample');
        $this->assertInstanceOf('TestApp\Shell\SampleShell', $result);
        $this->assertEmpty($result->plugin);
        $this->assertSame('Sample', $result->name);
    }

    /**
     * Verify dispatch handling stop errors
     */
    public function testDispatchShellWithAbort(): void
    {
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->addMethods(['main'])
            ->setConstructorArgs([$io])
            ->getMock();
        $shell->expects($this->once())
            ->method('main')
            ->will($this->returnCallback(function () use ($shell): void {
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
     */
    public function testDispatchShellWithMain(): void
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
     */
    public function testDispatchShellWithIntegerSuccessCode(): void
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
     */
    public function testDispatchShellWithCustomIntegerCodes(): void
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
     */
    public function testDispatchShellWithoutMain(): void
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
     */
    public function testDispatchShortPluginAlias(): void
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
     */
    public function testDispatchShortPluginAliasCamelized(): void
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
     */
    public function testDispatchShortPluginAliasConflict(): void
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
     */
    public function testShiftArgs(): void
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
     */
    public function testHelpOption(): void
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
     */
    public function testVersionOption(): void
    {
        $this->expectWarning();
        $dispatcher = $this->getMockBuilder('Cake\Console\ShellDispatcher')
            ->addMethods(['_stop'])
            ->getMock();
        $dispatcher->args = ['--version'];
        $dispatcher->dispatch();
    }
}
