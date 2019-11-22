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

use Cake\Command\Command;
use Cake\Core\Plugin;
use Cake\Http\BaseApplication;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * HelpCommand test.
 */
class HelpCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * setup method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setAppNamespace();
        $this->useCommandRunner();
        Plugin::getCollection()->clear();

        $app = $this->getMockForAbstractClass(
            BaseApplication::class,
            ['']
        );
        $app->addPlugin('TestPlugin');
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
    }

    /**
     * Test the command listing fallback when no commands are set
     *
     * @return void
     */
    public function testMainNoCommandsFallback()
    {
        $this->exec('help');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertCommandList();
        $this->clearPlugins();
    }

    /**
     * Test the command listing
     *
     * @return void
     */
    public function testMain()
    {
        $this->exec('help');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertCommandList();
    }

    /**
     * Assert the help output.
     *
     * @return void
     */
    protected function assertCommandList()
    {
        $this->assertOutputContains('<info>TestPlugin</info>', 'plugin header should appear');
        $this->assertOutputContains('- widget', 'plugin command should appear');
        $this->assertOutputNotContains(
            '- test_plugin.widget',
            'only short alias for plugin command.'
        );
        $this->assertOutputContains('<info>App</info>', 'app header should appear');
        $this->assertOutputContains('- sample', 'app shell');
        $this->assertOutputContains('<info>CakePHP</info>', 'cakephp header should appear');
        $this->assertOutputContains('- routes', 'core shell');
        $this->assertOutputContains('- example', 'short plugin name');
        $this->assertOutputContains('- abort', 'command object');
        $this->assertOutputContains('To run a command', 'more info present');
        $this->assertOutputContains('To get help', 'more info present');
    }

    /**
     * Test help --xml
     *
     * @return void
     */
    public function testMainAsXml()
    {
        $this->exec('help --xml');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('<shells>');

        $find = '<shell name="sample" call_as="sample" provider="TestApp\Shell\SampleShell" help="sample -h"';
        $this->assertOutputContains($find);

        $find = '<shell name="schema_cache build" call_as="schema_cache build" ' .
            'provider="Cake\Command\SchemacacheBuildCommand" help="schema_cache build -h"';
        $this->assertOutputContains($find);

        $find = '<shell name="test_plugin.sample" call_as="test_plugin.sample" provider="TestPlugin\Shell\SampleShell" help="test_plugin.sample -h"';
        $this->assertOutputContains($find);
    }
}
