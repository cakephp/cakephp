<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console\Command;

use Cake\Console\CommandInterface;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * HelpCommand test.
 */
class HelpCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * setup method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setAppNamespace();
        $this->loadPlugins(['TestPlugin']);
    }

    /**
     * tearDown
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
    }

    /**
     * Test the verbose command listing
     */
    public function testMainVerbose(): void
    {
        $this->exec('help -v');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('<info>CakePHP:</info>', 'header should appear in verbose mode');
        $this->assertCommandListVerbose();
    }

    /**
     * Test the compact command listing (default)
     */
    public function testMainCompact(): void
    {
        $this->exec('help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('<info>CakePHP:</info>', 'header should appear in compact mode');
        $this->assertOutputContains('<info>Available Commands:</info>', 'single commands header');
        $this->assertOutputContains('<info>routes:</info>', 'routes group header');
        $this->assertOutputContains('<info>cache:</info>', 'cache group header');
        $this->assertOutputContains('<comment>cache clear</comment>', 'cache subcommand listed');
        $this->assertOutputContains('Clear all data in a single cache engine', 'inline description shown');
        $this->assertOutputNotContains('<info>app</info>:', 'no plugin group headers in compact mode');
        $this->assertOutputNotContains('<comment>help</comment>', 'help command should be hidden');
        $this->assertOutputContains('To run a command', 'more info present');
    }

    /**
     * Test that the default header is omitted when Cake version is unknown.
     */
    public function testMainCompactOmitsHeaderWhenVersionUnknown(): void
    {
        $version = Configure::read('Cake.version');
        Configure::write('Cake.version', 'unknown');

        try {
            $this->exec('help');
            $this->assertExitCode(CommandInterface::CODE_SUCCESS);
            $this->assertOutputNotContains('<info>CakePHP:</info>', 'header should be omitted when version is unknown');
        } finally {
            Configure::write('Cake.version', $version);
        }
    }

    /**
     * Assert the verbose help output.
     */
    protected function assertCommandListVerbose(): void
    {
        $this->assertOutputContains('<info>test_plugin</info>', 'plugin header should appear');
        $this->assertOutputContains('<comment>sample</comment>', 'plugin command should appear');
        $this->assertOutputNotContains(
            '- test_plugin.sample',
            'only short alias for plugin command.',
        );
        $this->assertOutputNotContains(
            ' - abstract',
            'Abstract command classes should not appear.',
        );
        $this->assertOutputContains('<info>app</info>', 'app header should appear');
        $this->assertOutputContains('<comment>sample</comment>', 'app shell');
        $this->assertOutputContains('<info>cakephp</info>', 'cakephp header should appear');
        $this->assertOutputContains('<comment>routes</comment>', 'core shell');
        $this->assertOutputContains('<comment>sample</comment>', 'short plugin name');
        $this->assertOutputContains('<comment>abort</comment>', 'command object');
        $this->assertOutputContains('To run a command', 'more info present');
        $this->assertOutputContains('To get help', 'more info present');
        $this->assertOutputContains('This is a demo command', 'command description missing');
        $this->assertOutputContains('<info>custom_group</info>');
        $this->assertOutputContains('<comment>grouped</comment>');
        $this->assertOutputNotContains(
            '<comment>hidden</comment>',
            'Hidden commands should not appear in help output.',
        );
    }

    /**
     * Test filtering by command prefix (compact mode)
     */
    public function testFilterByPrefixCompact(): void
    {
        $this->exec('help cache');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('<info>cache:</info>');
        $this->assertOutputContains('<comment>cache clear</comment>');
        $this->assertOutputContains('<comment>cache list</comment>');
        $this->assertOutputNotContains('routes');
        $this->assertOutputNotContains('sample');
    }

    /**
     * Test filtering by command prefix with verbose mode shows descriptions
     */
    public function testFilterByPrefixVerbose(): void
    {
        $this->exec('help cache -v');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('Available Commands');
        $this->assertOutputContains('<comment>cache clear</comment>');
        $this->assertOutputContains('Clear all data in a single cache engine');
        $this->assertOutputNotContains('<comment>routes</comment>');
    }

    /**
     * Test help --xml
     */
    public function testMainAsXml(): void
    {
        $this->exec('help --xml');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('<shells>');

        $find = '<shell name="sample" call_as="sample" provider="TestApp\Command\SampleCommand" help="sample -h"';
        $this->assertOutputContains($find);

        $find = '<shell name="schema_cache build" call_as="schema_cache build" ' .
            'provider="Cake\Command\SchemacacheBuildCommand" help="schema_cache build -h"';
        $this->assertOutputContains($find);

        $find = '<shell name="test_plugin.sample" call_as="test_plugin.sample" provider="TestPlugin\Command\SampleCommand" help="test_plugin.sample -h"';
        $this->assertOutputContains($find);
        $this->assertOutputNotContains('<shell name="help"', 'help command should be hidden in XML output');

        $this->assertOutputNotContains(
            'HiddenCommand',
            'Hidden commands should not appear in XML output.',
        );
    }

    /**
     * Test that hidden commands are still executable
     */
    public function testHiddenCommandStillExecutable(): void
    {
        $this->exec('hidden');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('Hidden Command Executed!');
    }
}
