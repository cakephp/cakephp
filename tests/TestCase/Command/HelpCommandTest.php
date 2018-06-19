<?php
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
namespace Cake\Test\TestCase\Command;

use Cake\Console\Shell;
use Cake\Core\Plugin;
use Cake\TestSuite\ConsoleIntegrationTestCase;

/**
 * HelpCommand test.
 */
class HelpCommandTest extends ConsoleIntegrationTestCase
{
    /**
     * setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->setAppNamespace();
        $this->useCommandRunner(true);
        Plugin::load('TestPlugin');
    }

    /**
     * Test the command listing fallback when no commands are set
     *
     * @return void
     */
    public function testMainNoCommandsFallback()
    {
        $this->exec('help');
        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertCommandList();
    }

    /**
     * Test the command listing
     *
     * @return void
     */
    public function testMain()
    {
        $this->exec('help');
        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertCommandList();
    }

    /**
     * Assert the help output.
     *
     * @return void
     */
    protected function assertCommandList()
    {
        $this->assertOutputContains('- widget', 'plugin command');
        $this->assertOutputNotContains(
            '- test_plugin.widget',
            'only short alias for plugin command.'
        );
        $this->assertOutputContains('- sample', 'app shell');
        $this->assertOutputContains('- test_plugin.sample', 'Long plugin name');
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
        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertOutputContains('<shells>');

        $find = '<shell name="sample" call_as="sample" provider="TestApp\Shell\SampleShell" help="sample -h"';
        $this->assertOutputContains($find);

        $find = '<shell name="orm_cache" call_as="orm_cache" provider="Cake\Shell\OrmCacheShell" help="orm_cache -h"';
        $this->assertOutputContains($find);

        $find = '<shell name="test_plugin.sample" call_as="test_plugin.sample" provider="TestPlugin\Shell\SampleShell" help="test_plugin.sample -h"';
        $this->assertOutputContains($find);
    }
}
