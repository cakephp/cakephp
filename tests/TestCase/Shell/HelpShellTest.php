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
namespace Cake\Test\TestCase\Shell;

use Cake\Console\CommandCollection;
use Cake\Console\ConsoleIo;
use Cake\Core\Plugin;
use Cake\Shell\HelpShell;
use Cake\TestSuite\Stub\ConsoleOutput;
use Cake\TestSuite\TestCase;

/**
 * HelpShell test.
 */
class HelpShellTest extends TestCase
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
        Plugin::load('TestPlugin');

        $this->out = new ConsoleOutput();
        $this->err = new ConsoleOutput();
        $this->io = new ConsoleIo($this->out, $this->err);
        $this->shell = new HelpShell($this->io);

        $commands = new CommandCollection();
        $commands->addMany($commands->autoDiscover());
        $this->shell->setCommandCollection($commands);
    }

    /**
     * Test the command listing fallback when no commands are set
     *
     * @return void
     */
    public function testMainNoCommandsFallback()
    {
        $shell = new HelpShell($this->io);
        $this->assertNull($shell->main());

        $output = implode("\n", $this->out->messages());
        $this->assertOutput($output);
    }

    /**
     * Test the command listing
     *
     * @return void
     */
    public function testMain()
    {
        $this->assertNull($this->shell->main());

        $output = implode("\n", $this->out->messages());
        $this->assertOutput($output);
    }

    /**
     * Assert the help output.
     *
     * @param string $output The output to check.
     * @return void
     */
    protected function assertOutput($output)
    {
        $this->assertContains('- sample', $output, 'app shell');
        $this->assertContains('- test_plugin.sample', $output, 'Long plugin name');
        $this->assertContains('- routes', $output, 'core shell');
        $this->assertContains('- test_plugin.example', $output, 'Long plugin name');
        $this->assertContains('To run a command', $output, 'more info present');
        $this->assertContains('To get help', $output, 'more info present');
    }

    /**
     * Test help --xml
     *
     * @return void
     */
    public function testMainAsXml()
    {
        $this->shell->params['xml'] = true;
        $this->shell->main();
        $output = implode("\n", $this->out->messages());

        $this->assertContains('<shells>', $output);

        $find = '<shell name="sample" call_as="sample" provider="TestApp\Shell\SampleShell" help="sample -h"';
        $this->assertContains($find, $output);

        $find = '<shell name="orm_cache" call_as="orm_cache" provider="Cake\Shell\OrmCacheShell" help="orm_cache -h"';
        $this->assertContains($find, $output);

        $find = '<shell name="test_plugin.sample" call_as="test_plugin.sample" provider="TestPlugin\Shell\SampleShell" help="test_plugin.sample -h"';
        $this->assertContains($find, $output);
    }
}
